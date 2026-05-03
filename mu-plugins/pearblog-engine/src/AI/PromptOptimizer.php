<?php
/**
 * Prompt Optimizer – self-learning prompt improvement engine.
 *
 * Analyses the correlation between prompt variants and QualityScorer results
 * to continuously improve content generation prompts.
 *
 * Features:
 *  - Stores prompt variants with their associated quality scores.
 *  - Calculates mean score per variant to identify top performers.
 *  - Applies a configurable "modifier" to the best-performing prompt variant
 *    when building new prompts (integrated with PromptBuilderFactory).
 *  - Filter hook `pearblog_optimized_prompt_modifier` allows external code
 *    to influence the chosen modifier.
 *  - WP option `pearblog_prompt_optimizer_data` stores the learning dataset.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Tracks and optimises prompt performance based on quality scores.
 */
class PromptOptimizer {

	/** WP option key for the learning dataset. */
	public const OPTION_DATA = 'pearblog_prompt_optimizer_data';

	/** Maximum data points retained (ring buffer). */
	public const MAX_DATA_POINTS = 500;

	/** Minimum data points required before optimization kicks in. */
	public const MIN_SAMPLES_REQUIRED = 10;

	/** Available modifier variants to A/B test. */
	public const MODIFIERS = [
		'detailed'     => 'Write an extremely detailed, comprehensive article with extensive examples, statistics, and expert insights.',
		'conversational' => 'Write in a warm, conversational tone that feels like advice from a knowledgeable friend.',
		'listicle'     => 'Structure the article as a numbered list with clear, actionable takeaways.',
		'problem_solution' => 'Start by describing a common problem the reader faces, then provide a step-by-step solution.',
		'expert'       => 'Write from the perspective of an industry expert with first-hand experience and deep technical knowledge.',
		'beginner'     => 'Write for complete beginners. Explain every term, avoid jargon, and use simple analogies.',
	];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Record a quality score for a given prompt modifier variant.
	 *
	 * @param int    $post_id       Post ID.
	 * @param string $modifier_key  Modifier variant key (from MODIFIERS).
	 * @param int    $quality_score Quality score 0–100.
	 */
	public function record( int $post_id, string $modifier_key, int $quality_score ): void {
		$data   = $this->get_data();
		$data[] = [
			'post_id'      => $post_id,
			'modifier'     => $modifier_key,
			'score'        => $quality_score,
			'recorded_at'  => time(),
		];

		// Ring buffer: remove oldest entries.
		if ( count( $data ) > self::MAX_DATA_POINTS ) {
			$data = array_slice( $data, -self::MAX_DATA_POINTS );
		}

		update_option( self::OPTION_DATA, $data );

		/**
		 * Action: pearblog_prompt_optimizer_recorded
		 *
		 * @param int    $post_id      Post ID.
		 * @param string $modifier_key Modifier key.
		 * @param int    $score        Quality score.
		 */
		do_action( 'pearblog_prompt_optimizer_recorded', $post_id, $modifier_key, $quality_score );
	}

	/**
	 * Get the best-performing modifier based on mean quality score.
	 *
	 * Falls back to 'detailed' if insufficient data.
	 *
	 * @return string Modifier key.
	 */
	public function get_best_modifier(): string {
		$stats = $this->get_modifier_stats();

		$best_modifier = 'detailed';
		$best_score    = 0.0;

		foreach ( $stats as $key => $stat ) {
			if ( $stat['count'] >= self::MIN_SAMPLES_REQUIRED && $stat['mean'] > $best_score ) {
				$best_score    = $stat['mean'];
				$best_modifier = $key;
			}
		}

		/**
		 * Filter: pearblog_optimized_prompt_modifier
		 *
		 * @param string $modifier  Best-performing modifier key.
		 * @param array  $stats     Full modifier statistics.
		 */
		return (string) apply_filters( 'pearblog_optimized_prompt_modifier', $best_modifier, $stats );
	}

	/**
	 * Get the modifier text for the best-performing variant.
	 *
	 * @return string Modifier instruction text.
	 */
	public function get_best_modifier_text(): string {
		$key = $this->get_best_modifier();
		return self::MODIFIERS[ $key ] ?? self::MODIFIERS['detailed'];
	}

	/**
	 * Select a modifier for a new article (exploration vs exploitation).
	 *
	 * Uses epsilon-greedy: 20% of the time explores a random modifier,
	 * 80% of the time exploits the best known modifier.
	 *
	 * @return string Modifier key selected for this generation.
	 */
	public function select_modifier(): string {
		$epsilon = 0.2; // 20% exploration rate.

		if ( ( random_int( 1, 100 ) / 100 ) < $epsilon ) {
			// Explore: pick a random modifier.
			$keys = array_keys( self::MODIFIERS );
			return $keys[ array_rand( $keys ) ];
		}

		return $this->get_best_modifier();
	}

	/**
	 * Get per-modifier statistics.
	 *
	 * @return array<string, array{count: int, mean: float, min: int, max: int}>
	 */
	public function get_modifier_stats(): array {
		$data  = $this->get_data();
		$stats = [];

		foreach ( array_keys( self::MODIFIERS ) as $key ) {
			$scores = array_column(
				array_filter( $data, fn( $d ) => $d['modifier'] === $key ),
				'score'
			);

			$count    = count( $scores );
			$stats[ $key ] = [
				'count' => $count,
				'mean'  => $count > 0 ? round( array_sum( $scores ) / $count, 1 ) : 0.0,
				'min'   => $count > 0 ? min( $scores ) : 0,
				'max'   => $count > 0 ? max( $scores ) : 0,
			];
		}

		return $stats;
	}

	/**
	 * Get the raw learning dataset.
	 *
	 * @return array<int, array{post_id: int, modifier: string, score: int, recorded_at: int}>
	 */
	public function get_data(): array {
		$stored = get_option( self::OPTION_DATA, [] );
		return is_array( $stored ) ? $stored : [];
	}

	/**
	 * Clear all learning data.
	 */
	public function reset(): void {
		update_option( self::OPTION_DATA, [] );
	}

	// -----------------------------------------------------------------------
	// WordPress integration
	// -----------------------------------------------------------------------

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'pearblog_quality_scored', [ $this, 'on_quality_scored' ], 10, 2 );
	}

	/**
	 * Auto-record quality scores when articles are scored.
	 *
	 * @param int $post_id       Post ID.
	 * @param int $quality_score Quality score.
	 */
	public function on_quality_scored( int $post_id, int $quality_score ): void {
		$modifier = (string) get_post_meta( $post_id, 'pearblog_prompt_modifier', true );
		if ( '' === $modifier ) {
			return;
		}

		$this->record( $post_id, $modifier, $quality_score );
	}
}
