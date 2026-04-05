<?php
/**
 * Quality scorer – evaluates content quality after publication.
 *
 * Scores are stored as post meta:
 *   _pearblog_quality_score      – 0–100 composite score
 *   _pearblog_readability_score  – Flesch Reading Ease (0–100)
 *   _pearblog_keyword_density    – Keyword density %
 *   _pearblog_heading_score      – Heading structure score (0–100)
 *   _pearblog_quality_scored_at  – Timestamp of last scoring
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Analyses published post content and produces a quality score.
 */
class QualityScorer {

	/** Meta key for the composite quality score. */
	public const META_QUALITY_SCORE    = '_pearblog_quality_score';
	public const META_READABILITY      = '_pearblog_readability_score';
	public const META_KEYWORD_DENSITY  = '_pearblog_keyword_density';
	public const META_HEADING_SCORE    = '_pearblog_heading_score';
	public const META_SCORED_AT        = '_pearblog_quality_scored_at';

	/**
	 * Score a post and persist the results to post meta.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return array{composite: float, readability: float, keyword_density: float, heading_score: float}
	 */
	public function score( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return $this->empty_scores();
		}

		$plain_text  = wp_strip_all_tags( $post->post_content );
		$title       = get_the_title( $post_id );

		$readability     = $this->flesch_reading_ease( $plain_text );
		$keyword_density = $this->keyword_density( $plain_text, $title );
		$heading_score   = $this->heading_structure_score( $post->post_content );
		$word_count      = str_word_count( $plain_text );

		// Word-count bonus: up to 20 points for 2 000+ words.
		$length_score = min( 20, ( $word_count / 100 ) );

		// Composite: weighted average.
		$composite = (
			( $readability  * 0.35 ) +
			( $heading_score * 0.25 ) +
			( max( 0, 100 - abs( $keyword_density - 1.5 ) * 20 ) * 0.20 ) + // Ideal density ~1.5%
			( $length_score  * 1.0  )   // out of 20, weight 0.20 of 100
		);

		$composite = min( 100, max( 0, round( $composite, 1 ) ) );

		// Persist.
		update_post_meta( $post_id, self::META_QUALITY_SCORE,   $composite );
		update_post_meta( $post_id, self::META_READABILITY,     round( $readability, 1 ) );
		update_post_meta( $post_id, self::META_KEYWORD_DENSITY, round( $keyword_density, 2 ) );
		update_post_meta( $post_id, self::META_HEADING_SCORE,   round( $heading_score, 1 ) );
		update_post_meta( $post_id, self::META_SCORED_AT,       current_time( 'mysql' ) );

		/**
		 * Action: pearblog_quality_scored
		 *
		 * @param int   $post_id   Post ID.
		 * @param float $composite Composite quality score.
		 */
		do_action( 'pearblog_quality_scored', $post_id, $composite );

		return [
			'composite'       => $composite,
			'readability'     => round( $readability, 1 ),
			'keyword_density' => round( $keyword_density, 2 ),
			'heading_score'   => round( $heading_score, 1 ),
			'word_count'      => $word_count,
		];
	}

	/**
	 * Get posts that need re-optimisation (low quality score or not yet scored).
	 *
	 * @param float $threshold Composite score below which a post is flagged.
	 * @param int   $limit     Maximum posts to return.
	 * @return int[]           Post IDs.
	 */
	public function get_low_quality_posts( float $threshold = 50.0, int $limit = 20 ): array {
		return get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => self::META_QUALITY_SCORE,
					'value'   => $threshold,
					'compare' => '<',
					'type'    => 'NUMERIC',
				],
				[
					'key'     => self::META_QUALITY_SCORE,
					'compare' => 'NOT EXISTS',
				],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// Scoring algorithms
	// -----------------------------------------------------------------------

	/**
	 * Approximate Flesch Reading Ease score (0–100, higher = easier).
	 *
	 * Formula: 206.835 − (1.015 × ASL) − (84.6 × ASW)
	 * ASL = average sentence length (words), ASW = average syllables per word.
	 */
	private function flesch_reading_ease( string $text ): float {
		if ( '' === trim( $text ) ) {
			return 0.0;
		}

		// Split into sentences.
		$sentences = preg_split( '/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY );
		$sentences = array_filter( array_map( 'trim', $sentences ) );
		$n_sent    = max( 1, count( $sentences ) );

		// Split into words.
		preg_match_all( '/\b[a-z\']+\b/i', $text, $word_matches );
		$words  = $word_matches[0] ?? [];
		$n_words = max( 1, count( $words ) );

		$asl = $n_words / $n_sent;

		// Syllable count (English approximation).
		$total_syllables = array_sum( array_map( [ $this, 'count_syllables' ], $words ) );
		$asw = $total_syllables / $n_words;

		$score = 206.835 - ( 1.015 * $asl ) - ( 84.6 * $asw );
		return min( 100, max( 0, $score ) );
	}

	/**
	 * Count syllables in a word using vowel-group heuristic (English).
	 */
	private function count_syllables( string $word ): int {
		$word = strtolower( $word );
		if ( mb_strlen( $word ) <= 3 ) {
			return 1;
		}
		$word     = rtrim( $word, 'e' ); // Silent final E.
		$count    = preg_match_all( '/[aeiou]+/', $word );
		return max( 1, (int) $count );
	}

	/**
	 * Calculate keyword density: occurrences of the main keyword phrase
	 * (from the post title) as a percentage of total words.
	 */
	private function keyword_density( string $text, string $title ): float {
		$n_words = max( 1, str_word_count( $text ) );

		// Use first 3 significant words of the title as the keyword phrase.
		preg_match_all( '/\b[a-z]{3,}\b/i', $title, $m );
		$keyword_words = array_slice( $m[0] ?? [], 0, 3 );

		if ( empty( $keyword_words ) ) {
			return 0.0;
		}

		$keyword = implode( ' ', $keyword_words );
		$count   = (int) substr_count( mb_strtolower( $text ), mb_strtolower( $keyword ) );

		return ( $count / $n_words ) * 100;
	}

	/**
	 * Score heading structure (H2/H3 organisation).
	 *
	 * Awards points for:
	 *  - Presence of at least one H2     (+40)
	 *  - Presence of at least one H3     (+20)
	 *  - At least 3 H2 sections         (+20)
	 *  - Proper H2 → H3 nesting         (+20)
	 */
	private function heading_structure_score( string $html ): float {
		$score = 0;

		preg_match_all( '/<h2[^>]*>/i', $html, $h2 );
		preg_match_all( '/<h3[^>]*>/i', $html, $h3 );

		$n_h2 = count( $h2[0] );
		$n_h3 = count( $h3[0] );

		if ( $n_h2 >= 1 ) { $score += 40; }
		if ( $n_h3 >= 1 ) { $score += 20; }
		if ( $n_h2 >= 3 ) { $score += 20; }
		if ( $n_h2 >= 1 && $n_h3 >= 1 ) { $score += 20; } // Has hierarchy.

		return (float) min( 100, $score );
	}

	private function empty_scores(): array {
		return [
			'composite'       => 0.0,
			'readability'     => 0.0,
			'keyword_density' => 0.0,
			'heading_score'   => 0.0,
			'word_count'      => 0,
		];
	}
}
