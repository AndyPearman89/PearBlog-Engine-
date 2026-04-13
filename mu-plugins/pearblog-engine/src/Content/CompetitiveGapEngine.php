<?php
/**
 * Competitive Gap Engine — identifies topic gaps vs. the existing content corpus.
 *
 * Workflow:
 *   1. Site admin (or automation scripts) feed in a list of "competitor topics"
 *      (e.g. scraped from SERP titles, competitor RSS feeds, keyword tools).
 *   2. The engine normalises both the competitor list and the existing published
 *      post titles / excerpt keywords.
 *   3. It returns topics that are NOT yet covered (the "gap").
 *   4. The highest-priority gap topics are injected into the AI prompt so the
 *      next generated article fills the most valuable content holes.
 *
 * Storage / options:
 *   pearblog_gap_competitor_topics  – JSON array of competitor topic strings
 *   pearblog_gap_max_inject         – int, max gap topics to inject per prompt (default 3)
 *   pearblog_gap_similarity_thresh  – float 0–1, jaccard similarity above which a topic
 *                                     is considered "already covered" (default 0.5)
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Compares a competitor topic list against the existing post corpus and
 * returns the uncovered ("gap") topics.
 */
class CompetitiveGapEngine {

	/** WP option: JSON array of competitor topic strings. */
	public const OPTION_COMPETITOR_TOPICS = 'pearblog_gap_competitor_topics';

	/** WP option: max gap topics injected per prompt. */
	public const OPTION_MAX_INJECT = 'pearblog_gap_max_inject';

	/** WP option: Jaccard similarity threshold (0–1). */
	public const OPTION_SIMILARITY_THRESH = 'pearblog_gap_similarity_thresh';

	/** Default max topics to inject. */
	public const DEFAULT_MAX_INJECT = 3;

	/** Default similarity threshold. */
	public const DEFAULT_SIMILARITY_THRESH = 0.5;

	// -----------------------------------------------------------------------
	// Competitor topic management
	// -----------------------------------------------------------------------

	/**
	 * Store (replace) the full list of competitor topics.
	 *
	 * @param string[] $topics
	 */
	public function set_competitor_topics( array $topics ): void {
		$sanitized = array_values( array_filter( array_map( 'sanitize_text_field', $topics ) ) );
		update_option( self::OPTION_COMPETITOR_TOPICS, wp_json_encode( $sanitized ) );
	}

	/**
	 * Append topics to the existing competitor list (deduplicates).
	 *
	 * @param string[] $topics
	 */
	public function add_competitor_topics( array $topics ): void {
		$current = $this->get_competitor_topics();
		$merged  = array_unique( array_merge( $current, array_map( 'sanitize_text_field', $topics ) ) );
		update_option( self::OPTION_COMPETITOR_TOPICS, wp_json_encode( array_values( $merged ) ) );
	}

	/**
	 * Retrieve stored competitor topics.
	 *
	 * @return string[]
	 */
	public function get_competitor_topics(): array {
		$raw     = get_option( self::OPTION_COMPETITOR_TOPICS, '[]' );
		$decoded = json_decode( is_string( $raw ) ? $raw : '[]', true );
		return is_array( $decoded ) ? array_values( array_filter( array_map( 'strval', $decoded ) ) ) : [];
	}

	// -----------------------------------------------------------------------
	// Gap analysis
	// -----------------------------------------------------------------------

	/**
	 * Compute the gap: competitor topics not yet covered in the published corpus.
	 *
	 * @param string[]|null $competitor_topics  Competitor list; null = read from option.
	 * @param string[]|null $published_titles   Existing post titles; null = read from WP.
	 * @return string[]                          Uncovered topic strings, sorted by coverage score ASC.
	 */
	public function get_gap_topics( ?array $competitor_topics = null, ?array $published_titles = null ): array {
		if ( null === $competitor_topics ) {
			$competitor_topics = $this->get_competitor_topics();
		}

		if ( null === $published_titles ) {
			$published_titles = $this->fetch_published_titles();
		}

		$threshold = (float) get_option( self::OPTION_SIMILARITY_THRESH, self::DEFAULT_SIMILARITY_THRESH );

		$gap = [];
		foreach ( $competitor_topics as $topic ) {
			$max_sim = 0.0;
			foreach ( $published_titles as $title ) {
				$sim = $this->jaccard_similarity(
					$this->tokenise( $topic ),
					$this->tokenise( $title )
				);
				if ( $sim > $max_sim ) {
					$max_sim = $sim;
				}
			}
			if ( $max_sim < $threshold ) {
				$gap[] = [ 'topic' => $topic, 'max_similarity' => $max_sim ];
			}
		}

		// Sort by lowest similarity first (biggest gaps first).
		usort( $gap, fn( $a, $b ) => $a['max_similarity'] <=> $b['max_similarity'] );

		return array_column( $gap, 'topic' );
	}

	/**
	 * Get at most N gap topics for prompt injection.
	 *
	 * @return string[]
	 */
	public function get_top_gap_topics(): array {
		$max = (int) get_option( self::OPTION_MAX_INJECT, self::DEFAULT_MAX_INJECT );
		return array_slice( $this->get_gap_topics(), 0, $max );
	}

	// -----------------------------------------------------------------------
	// Prompt enrichment
	// -----------------------------------------------------------------------

	/**
	 * Append a "Missing Topics" block to an AI prompt.
	 *
	 * @param string $prompt
	 * @return string
	 */
	public function enrich_prompt( string $prompt ): string {
		$gaps = $this->get_top_gap_topics();
		if ( empty( $gaps ) ) {
			return $prompt;
		}

		$block  = "\n\n---\n## Competitive Content Gaps\n";
		$block .= "Your competitors cover these topics that are missing from this site. ";
		$block .= "Where relevant, address at least one of them in this article:\n\n";
		foreach ( $gaps as $topic ) {
			$block .= "- {$topic}\n";
		}
		$block .= "\n---\n";

		return $prompt . $block;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Fetch the titles of all published posts.
	 *
	 * @return string[]
	 */
	private function fetch_published_titles(): array {
		$ids = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );

		$titles = [];
		foreach ( $ids as $id ) {
			$t = get_the_title( (int) $id );
			if ( '' !== $t ) {
				$titles[] = $t;
			}
		}

		return $titles;
	}

	/**
	 * Split a string into a set of lowercase word tokens (≥ 3 chars).
	 *
	 * @param string $text
	 * @return array<string, true>  Token → true (set)
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
	 * Jaccard similarity between two token sets: |A∩B| / |A∪B|.
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
