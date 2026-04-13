<?php
/**
 * Keyword Cluster Engine — groups GA4 organic search terms into topical clusters
 * and surfaces them as KeywordCluster value objects ready for content planning.
 *
 * Algorithm overview
 * ──────────────────
 * 1. Retrieve raw search terms from Google Analytics 4 (via GA4Client) or from
 *    a manually supplied list.
 * 2. Tokenise each term; compute a simple TF score (term frequency across the
 *    full corpus of terms).
 * 3. Group terms: a term belongs to the same cluster as the highest-IDF "pillar"
 *    whose token set has a Jaccard similarity ≥ threshold with the term.
 * 4. Return `KeywordCluster` objects sorted by aggregate impression/click
 *    potential (approximated by term frequency if GA4 click data is unavailable).
 *
 * Configuration WP options:
 *   pearblog_kce_similarity_thresh  – Jaccard similarity threshold (default 0.25)
 *   pearblog_kce_min_cluster_size   – Min terms per cluster before it is returned (default 2)
 *   pearblog_kce_max_clusters       – Max clusters to return (default 20)
 *   pearblog_kce_ga4_days           – GA4 lookback window in days (default 90)
 *
 * @package PearBlogEngine\Keywords
 */

declare(strict_types=1);

namespace PearBlogEngine\Keywords;

use PearBlogEngine\Analytics\GA4Client;

/**
 * Builds keyword clusters from a corpus of search terms.
 */
class KeywordClusterEngine {

	// -----------------------------------------------------------------------
	// Option keys
	// -----------------------------------------------------------------------

	public const OPTION_SIMILARITY_THRESH  = 'pearblog_kce_similarity_thresh';
	public const OPTION_MIN_CLUSTER_SIZE   = 'pearblog_kce_min_cluster_size';
	public const OPTION_MAX_CLUSTERS       = 'pearblog_kce_max_clusters';
	public const OPTION_GA4_DAYS           = 'pearblog_kce_ga4_days';

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	public const DEFAULT_SIMILARITY_THRESH = 0.25;
	public const DEFAULT_MIN_CLUSTER_SIZE  = 2;
	public const DEFAULT_MAX_CLUSTERS      = 20;
	public const DEFAULT_GA4_DAYS          = 90;

	/** WP option: JSON array of cached clusters. */
	public const OPTION_CACHED_CLUSTERS    = 'pearblog_kce_clusters';

	/** WP cron hook for weekly cluster refresh. */
	public const CRON_HOOK = 'pearblog_keyword_cluster_refresh';

	/** @var GA4Client */
	private GA4Client $ga4;

	public function __construct( ?GA4Client $ga4 = null ) {
		$this->ga4 = $ga4 ?? new GA4Client();
	}

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register the weekly cron refresh.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'refresh_clusters' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Get cached keyword clusters, refreshing if empty.
	 *
	 * @return KeywordCluster[]
	 */
	public function get_clusters(): array {
		$raw     = get_option( self::OPTION_CACHED_CLUSTERS, '[]' );
		$decoded = json_decode( is_string( $raw ) ? $raw : '[]', true );

		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			return $this->refresh_clusters();
		}

		$clusters = [];
		foreach ( $decoded as $item ) {
			if ( is_array( $item ) ) {
				$clusters[] = KeywordCluster::from_array( $item );
			}
		}

		return $clusters;
	}

	/**
	 * Rebuild clusters from GA4 search terms and persist to WP options.
	 *
	 * @return KeywordCluster[]
	 */
	public function refresh_clusters(): array {
		$terms    = $this->fetch_search_terms();
		$clusters = $this->build_clusters( $terms );
		$this->persist_clusters( $clusters );
		return $clusters;
	}

	/**
	 * Build clusters from an explicit list of search terms (no GA4 required).
	 *
	 * @param string[] $terms  Raw search term strings.
	 * @return KeywordCluster[]
	 */
	public function build_clusters( array $terms ): array {
		$terms = array_values( array_filter( array_map( 'trim', $terms ) ) );
		if ( empty( $terms ) ) {
			return [];
		}

		$threshold  = (float) get_option( self::OPTION_SIMILARITY_THRESH, self::DEFAULT_SIMILARITY_THRESH );
		$min_size   = (int)   get_option( self::OPTION_MIN_CLUSTER_SIZE,   self::DEFAULT_MIN_CLUSTER_SIZE );
		$max_out    = (int)   get_option( self::OPTION_MAX_CLUSTERS,       self::DEFAULT_MAX_CLUSTERS );

		// Build token sets and compute IDF for each unique term.
		$token_sets  = [];
		$term_counts = [];
		foreach ( $terms as $term ) {
			$tokens = $this->tokenise( $term );
			$token_sets[ $term ] = $tokens;
			foreach ( array_keys( $tokens ) as $tok ) {
				$term_counts[ $tok ] = ( $term_counts[ $tok ] ?? 0 ) + 1;
			}
		}

		// Select pillar terms: terms whose tokens have the highest cumulative IDF.
		$idf_scores = [];
		foreach ( $terms as $term ) {
			$score = 0.0;
			foreach ( array_keys( $token_sets[ $term ] ) as $tok ) {
				$df     = $term_counts[ $tok ] ?? 1;
				$score += 1.0 / $df; // Inverse document frequency (simplified).
			}
			$idf_scores[ $term ] = $score;
		}

		arsort( $idf_scores );

		// Greedy cluster assignment: each term assigned to nearest pillar.
		$assigned = [];
		$clusters = [];

		foreach ( array_keys( $idf_scores ) as $pillar ) {
			if ( isset( $assigned[ $pillar ] ) ) {
				continue;
			}
			$assigned[ $pillar ] = $pillar;
			$supporting          = [];

			foreach ( $terms as $candidate ) {
				if ( $candidate === $pillar || isset( $assigned[ $candidate ] ) ) {
					continue;
				}
				$sim = $this->jaccard_similarity( $token_sets[ $pillar ], $token_sets[ $candidate ] );
				if ( $sim >= $threshold ) {
					$supporting[]              = $candidate;
					$assigned[ $candidate ]    = $pillar;
				}
			}

			if ( count( $supporting ) + 1 >= $min_size ) {
				$clusters[] = new KeywordCluster( $pillar, $supporting );
			}

			if ( count( $clusters ) >= $max_out ) {
				break;
			}
		}

		return $clusters;
	}

	// -----------------------------------------------------------------------
	// GA4 search-term retrieval
	// -----------------------------------------------------------------------

	/**
	 * Pull organic search terms from GA4 (searchTerm dimension).
	 *
	 * @return string[]
	 */
	public function fetch_search_terms(): array {
		if ( ! $this->ga4->is_configured() ) {
			return [];
		}

		$days   = (int) get_option( self::OPTION_GA4_DAYS, self::DEFAULT_GA4_DAYS );
		$report = $this->ga4->run_report(
			[ 'name' => 'searchTerm' ],
			[ 'name' => 'sessions' ],
			"{$days}daysAgo",
			'today',
			[],
			500
		);

		$terms = [];
		foreach ( $report['rows'] ?? [] as $row ) {
			$term = (string) ( $row['dimensionValues'][0]['value'] ?? '' );
			if ( '' !== $term && '(not set)' !== $term && '(not provided)' !== $term ) {
				$terms[] = $term;
			}
		}

		return array_unique( $terms );
	}

	// -----------------------------------------------------------------------
	// Persistence
	// -----------------------------------------------------------------------

	/**
	 * Serialise and store clusters in a WP option.
	 *
	 * @param KeywordCluster[] $clusters
	 */
	private function persist_clusters( array $clusters ): void {
		$data = array_map( fn( KeywordCluster $c ) => $c->to_array(), $clusters );
		update_option( self::OPTION_CACHED_CLUSTERS, wp_json_encode( $data ) );
	}

	// -----------------------------------------------------------------------
	// Text helpers
	// -----------------------------------------------------------------------

	/**
	 * Tokenise a string into a set of lowercase word tokens (≥ 3 chars).
	 *
	 * @return array<string, true>
	 */
	public function tokenise( string $text ): array {
		preg_match_all( '/\b[a-z]{3,}\b/i', mb_strtolower( $text ), $m );
		$tokens = [];
		foreach ( $m[0] ?? [] as $w ) {
			$tokens[ $w ] = true;
		}
		return $tokens;
	}

	/**
	 * Jaccard similarity between two token sets.
	 *
	 * @param array<string, true> $a
	 * @param array<string, true> $b
	 * @return float  0.0 – 1.0
	 */
	public function jaccard_similarity( array $a, array $b ): float {
		if ( empty( $a ) && empty( $b ) ) {
			return 1.0;
		}

		$intersection = count( array_intersect_key( $a, $b ) );
		$union        = count( array_merge( $a, $b ) );

		return $union > 0 ? $intersection / $union : 0.0;
	}
}
