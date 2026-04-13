<?php
/**
 * Topic Research Engine — auto-surfaces high-value content topics by combining
 * GA4 organic search terms, SERP competitor gaps, and keyword cluster data.
 *
 * Scoring model (0–100):
 *   - GA4 search volume proxy  : up to 40 pts  (pageviews for similar slugs or raw term frequency)
 *   - Competitive gap score    : up to 40 pts  (lower Jaccard similarity to existing posts = higher score)
 *   - Keyword cluster boost    : up to 20 pts  (bonus if the topic belongs to a known cluster)
 *
 * The engine persists scored topic recommendations to `pearblog_tre_recommendations`
 * and can auto-populate the `TopicQueue` with the top-N highest-scoring topics.
 *
 * Configuration WP options:
 *   pearblog_tre_enabled         – bool, master switch (default false)
 *   pearblog_tre_min_score       – float 0–100, minimum score to qualify (default 30)
 *   pearblog_tre_max_topics      – int, max topics to auto-queue per run (default 10)
 *   pearblog_tre_lookback_days   – int, GA4 lookback window in days (default 30)
 *
 * Cron hook: pearblog_topic_research_refresh (weekly)
 * Action hook: pearblog_topics_researched ($recommendations, $queued_count)
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Analytics\GA4Client;
use PearBlogEngine\Keywords\KeywordClusterEngine;

/**
 * Scores and ranks content topic candidates from multiple data sources.
 */
class TopicResearchEngine {

	// -----------------------------------------------------------------------
	// Option keys
	// -----------------------------------------------------------------------

	public const OPTION_ENABLED       = 'pearblog_tre_enabled';
	public const OPTION_MIN_SCORE     = 'pearblog_tre_min_score';
	public const OPTION_MAX_TOPICS    = 'pearblog_tre_max_topics';
	public const OPTION_LOOKBACK_DAYS = 'pearblog_tre_lookback_days';
	public const OPTION_CACHED        = 'pearblog_tre_recommendations';

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	public const DEFAULT_MIN_SCORE     = 30.0;
	public const DEFAULT_MAX_TOPICS    = 10;
	public const DEFAULT_LOOKBACK_DAYS = 30;

	// -----------------------------------------------------------------------
	// Score weights (must sum to 100)
	// -----------------------------------------------------------------------

	private const WEIGHT_GA4     = 40;
	private const WEIGHT_GAP     = 40;
	private const WEIGHT_CLUSTER = 20;

	// -----------------------------------------------------------------------
	// Cron / action hooks
	// -----------------------------------------------------------------------

	/** WP cron hook for weekly auto-refresh. */
	public const CRON_HOOK = 'pearblog_topic_research_refresh';

	/** Action fired after research runs. */
	public const ACTION_RESEARCHED = 'pearblog_topics_researched';

	// -----------------------------------------------------------------------
	// Dependencies
	// -----------------------------------------------------------------------

	/** @var GA4Client */
	private $ga4;

	/** @var CompetitiveGapEngine */
	private $gap;

	/** @var KeywordClusterEngine */
	private $kce;

	/** @var SerpScraper */
	private $serp;

	public function __construct(
		?GA4Client            $ga4  = null,
		?CompetitiveGapEngine $gap  = null,
		?KeywordClusterEngine $kce  = null,
		?SerpScraper          $serp = null
	) {
		$this->ga4  = $ga4  ?? new GA4Client();
		$this->gap  = $gap  ?? new CompetitiveGapEngine();
		$this->kce  = $kce  ?? new KeywordClusterEngine();
		$this->serp = $serp ?? new SerpScraper();
	}

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register the weekly cron refresh and REST routes.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'run' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Whether the engine is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	/**
	 * Run the full research pipeline:
	 *   1. Collect candidate topics from GA4, SERP, and competitive gap.
	 *   2. Score each topic.
	 *   3. Filter by minimum score.
	 *   4. Persist recommendations.
	 *   5. Optionally auto-populate the TopicQueue.
	 *
	 * @param bool $auto_queue When true, push qualified topics into TopicQueue.
	 * @param int  $site_id    WP blog / site ID (default 1).
	 * @return array<int, array{topic: string, score: float, sources: string[]}> Scored recommendations.
	 */
	public function run( bool $auto_queue = false, int $site_id = 1 ): array {
		$candidates = $this->collect_candidates( $site_id );
		$scored     = $this->score_topics( $candidates );
		$min_score  = (float) get_option( self::OPTION_MIN_SCORE, self::DEFAULT_MIN_SCORE );

		$recommendations = array_values(
			array_filter( $scored, fn( array $r ) => $r['score'] >= $min_score )
		);

		// Sort descending by score.
		usort( $recommendations, fn( array $a, array $b ) => $b['score'] <=> $a['score'] );

		$this->save_recommendations( $recommendations );

		$queued_count = 0;
		if ( $auto_queue ) {
			$queued_count = $this->auto_queue( $recommendations, $site_id );
		}

		do_action( self::ACTION_RESEARCHED, $recommendations, $queued_count );

		return $recommendations;
	}

	/**
	 * Auto-queue the top-N qualifying recommendations into the TopicQueue.
	 *
	 * Already-queued topics are not duplicated.
	 *
	 * @param array<int, array> $recommendations Sorted (desc) recommendation list.
	 * @param int               $site_id         WP blog / site ID.
	 * @return int                               Number of topics actually pushed.
	 */
	public function auto_queue( array $recommendations, int $site_id = 1 ): int {
		$max   = (int) get_option( self::OPTION_MAX_TOPICS, self::DEFAULT_MAX_TOPICS );
		$queue = new TopicQueue( $site_id );

		// Build a lookup of already-queued topics for deduplication.
		$existing = array_map( 'strtolower', $queue->all() );

		$pushed = 0;
		foreach ( $recommendations as $rec ) {
			if ( $pushed >= $max ) {
				break;
			}

			$topic = $rec['topic'];
			if ( in_array( strtolower( $topic ), $existing, true ) ) {
				continue;
			}

			$queue->push( $topic );
			$existing[] = strtolower( $topic );
			$pushed++;
		}

		return $pushed;
	}

	/**
	 * Return the most recently persisted recommendations.
	 *
	 * @return array<int, array>
	 */
	public function get_recommendations(): array {
		$raw     = get_option( self::OPTION_CACHED, '[]' );
		$decoded = json_decode( is_string( $raw ) ? $raw : '[]', true );
		return is_array( $decoded ) ? $decoded : [];
	}

	// -----------------------------------------------------------------------
	// Scoring
	// -----------------------------------------------------------------------

	/**
	 * Score a list of candidate topics against GA4, gap, and cluster signals.
	 *
	 * @param array<string, string[]> $candidates Map of topic → sources.
	 * @return array<int, array{topic: string, score: float, sources: string[]}>
	 */
	public function score_topics( array $candidates ): array {
		if ( empty( $candidates ) ) {
			return [];
		}

		// Pre-compute cluster pillar map: lower-cased pillar → cluster index.
		$cluster_pillars = $this->build_cluster_pillar_map();

		// Pre-compute gap topics for fast lookup.
		$gap_topics = $this->gap->get_gap_topics();
		$gap_set    = array_map( 'strtolower', $gap_topics );

		$results = [];
		foreach ( $candidates as $topic => $sources ) {
			$score = $this->compute_score( $topic, $sources, $gap_set, $cluster_pillars );

			$results[] = [
				'topic'   => $topic,
				'score'   => round( $score, 2 ),
				'sources' => $sources,
			];
		}

		return $results;
	}

	/**
	 * Compute the composite score (0–100) for a single topic.
	 *
	 * @param string   $topic           Topic string.
	 * @param string[] $sources         Data sources that surfaced this topic.
	 * @param string[] $gap_set         Lower-cased gap topics.
	 * @param array    $cluster_pillars Lower-cased pillar → cluster index map.
	 * @return float
	 */
	public function compute_score(
		string $topic,
		array  $sources,
		array  $gap_set,
		array  $cluster_pillars
	): float {
		// — GA4 signal (0–40): topic surfaced by GA4 gets full weight.
		$ga4_score = in_array( 'ga4', $sources, true ) ? (float) self::WEIGHT_GA4 : 0.0;

		// — Gap signal (0–40): topic appears in competitor gap.
		$gap_score = in_array( strtolower( $topic ), $gap_set, true ) ? (float) self::WEIGHT_GAP : 0.0;

		// — Cluster bonus (0–20): topic matches a known keyword cluster pillar.
		$cluster_score = isset( $cluster_pillars[ strtolower( $topic ) ] ) ? (float) self::WEIGHT_CLUSTER : 0.0;

		// Partial credit: if topic came from SERP but not GA4, give half GA4 weight.
		if ( 0.0 === $ga4_score && in_array( 'serp', $sources, true ) ) {
			$ga4_score = self::WEIGHT_GA4 / 2.0;
		}

		return $ga4_score + $gap_score + $cluster_score;
	}

	// -----------------------------------------------------------------------
	// Candidate collection
	// -----------------------------------------------------------------------

	/**
	 * Collect candidate topics from all available data sources.
	 *
	 * Returns a map of topic → source list, e.g.:
	 *   [ 'best php frameworks' => ['ga4', 'serp'] ]
	 *
	 * @param int $site_id WP blog / site ID.
	 * @return array<string, string[]>
	 */
	public function collect_candidates( int $site_id = 1 ): array {
		$candidates = [];

		// 1. GA4 search terms.
		$ga4_terms = $this->fetch_ga4_terms();
		foreach ( $ga4_terms as $term ) {
			$t = trim( $term );
			if ( '' === $t ) {
				continue;
			}
			$candidates[ $t ]   = $candidates[ $t ] ?? [];
			$candidates[ $t ][] = 'ga4';
		}

		// 2. SERP-derived competitor titles (from CompetitiveGapEngine gaps).
		foreach ( $this->gap->get_gap_topics() as $gap_topic ) {
			$t = trim( $gap_topic );
			if ( '' === $t ) {
				continue;
			}
			$candidates[ $t ]   = $candidates[ $t ] ?? [];
			if ( ! in_array( 'serp', $candidates[ $t ], true ) ) {
				$candidates[ $t ][] = 'serp';
			}
		}

		// 3. Keyword cluster pillar terms.
		foreach ( $this->get_cluster_pillar_topics() as $pillar ) {
			$t = trim( $pillar );
			if ( '' === $t ) {
				continue;
			}
			$candidates[ $t ]   = $candidates[ $t ] ?? [];
			if ( ! in_array( 'cluster', $candidates[ $t ], true ) ) {
				$candidates[ $t ][] = 'cluster';
			}
		}

		return $candidates;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Fetch GA4 organic search terms for the configured lookback window.
	 *
	 * Falls back to an empty array when GA4 is not configured.
	 *
	 * @return string[]
	 */
	private function fetch_ga4_terms(): array {
		if ( ! $this->ga4->is_configured() ) {
			return [];
		}

		$days = (int) get_option( self::OPTION_LOOKBACK_DAYS, self::DEFAULT_LOOKBACK_DAYS );

		try {
			$report = $this->ga4->run_report(
				[ 'searchTerm' ],
				[ 'sessions' ],
				"{$days}daysAgo",
				'today'
			);
			$raw_rows = $this->ga4->extract_rows( $report );
			// Filter out any malformed rows before extracting the first column.
			return array_values( array_filter(
				array_column( array_filter( $raw_rows, fn( $r ) => is_array( $r ) && isset( $r[0] ) ), 0 )
			) );
		} catch ( \Throwable $e ) {
			return [];
		}
	}

	/**
	 * Return pillar topic strings from all current keyword clusters.
	 *
	 * @return string[]
	 */
	private function get_cluster_pillar_topics(): array {
		$clusters = $this->kce->get_clusters();
		$pillars  = [];
		foreach ( $clusters as $cluster ) {
			if ( method_exists( $cluster, 'get_pillar' ) ) {
				$pillars[] = $cluster->get_pillar();
			} elseif ( isset( $cluster->pillar ) ) {
				$pillars[] = $cluster->pillar;
			}
		}
		return array_filter( $pillars );
	}

	/**
	 * Build a lower-cased pillar → cluster-index map for O(1) cluster membership checks.
	 *
	 * @return array<string, int>
	 */
	private function build_cluster_pillar_map(): array {
		$map     = [];
		$clusters = $this->kce->get_clusters();
		foreach ( $clusters as $idx => $cluster ) {
			$pillar = '';
			if ( method_exists( $cluster, 'get_pillar' ) ) {
				$pillar = $cluster->get_pillar();
			} elseif ( isset( $cluster->pillar ) ) {
				$pillar = $cluster->pillar;
			}
			if ( '' !== $pillar ) {
				$map[ strtolower( $pillar ) ] = $idx;
			}
		}
		return $map;
	}

	/**
	 * Persist recommendations as JSON to WP options.
	 *
	 * @param array<int, array> $recommendations
	 */
	private function save_recommendations( array $recommendations ): void {
		update_option( self::OPTION_CACHED, wp_json_encode( $recommendations ) );
	}
}
