<?php
/**
 * A/B Testing Framework – split-test prompt variants for the same topic.
 *
 * Workflow:
 *   1. Create a test: ABTestEngine::create_test($topic, $modifier_a, $modifier_b)
 *   2. The engine automatically hooks into the `pearblog_prompt` filter and
 *      appends the assigned variant's modifier to the prompt.
 *   3. After each article is published, the quality score is recorded per variant
 *      via the `pearblog_pipeline_completed` action.
 *   4. After PROMOTION_DAYS (default 7) days, promote_mature_tests() elects the
 *      winning variant (higher average quality score).
 *
 * Storage:
 *   All test records are stored in the WP option `pearblog_ab_tests` as an
 *   associative array keyed by test ID.  Active-run state (which variant was
 *   selected for a particular pipeline execution) is stored in a short-lived
 *   transient so the completion hook can look it up.
 *
 * @package PearBlogEngine\Testing
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

use PearBlogEngine\Content\QualityScorer;

/**
 * Manages prompt A/B tests: creation, variant selection, score recording,
 * and winner promotion.
 */
class ABTestEngine {

	/** WP option key that stores the full tests array. */
	public const OPTION_KEY = 'pearblog_ab_tests';

	/** WP cron hook for daily auto-promotion check. */
	public const CRON_HOOK = 'pearblog_abtest_promote';

	/** How many days before a test is eligible for promotion. */
	public const PROMOTION_DAYS = 7;

	/** Minimum articles per variant required before promotion. */
	public const MIN_ARTICLES_PER_VARIANT = 2;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks and schedule the daily promotion cron.
	 */
	public function register(): void {
		add_filter( 'pearblog_prompt', [ $this, 'apply_variant_modifier' ], 10, 2 );
		add_action( 'pearblog_pipeline_completed', [ $this, 'on_pipeline_completed' ], 20, 2 );
		add_action( self::CRON_HOOK, [ $this, 'promote_mature_tests' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	// -----------------------------------------------------------------------
	// CRUD
	// -----------------------------------------------------------------------

	/**
	 * Create a new A/B test.
	 *
	 * @param string $topic      The exact topic string to test.
	 * @param string $modifier_a Additional prompt instructions for variant A.
	 * @param string $modifier_b Additional prompt instructions for variant B.
	 * @return string            The new test ID.
	 */
	public function create_test( string $topic, string $modifier_a, string $modifier_b ): string {
		$id = 'ab_' . substr( md5( uniqid( $topic, true ) ), 0, 8 );

		$test = [
			'id'          => $id,
			'topic'       => $topic,
			'modifier_a'  => $modifier_a,
			'modifier_b'  => $modifier_b,
			'created_at'  => time(),
			'promoted_at' => null,
			'winner'      => null,
			'variants'    => [
				'a' => [ 'runs' => 0, 'scores' => [], 'post_ids' => [] ],
				'b' => [ 'runs' => 0, 'scores' => [], 'post_ids' => [] ],
			],
		];

		$tests        = $this->load_tests();
		$tests[ $id ] = $test;
		$this->save_tests( $tests );

		return $id;
	}

	/**
	 * Retrieve a single test by ID.
	 *
	 * @param string $test_id Test ID.
	 * @return array|null     Test data array, or null if not found.
	 */
	public function get_test( string $test_id ): ?array {
		return $this->load_tests()[ $test_id ] ?? null;
	}

	/**
	 * Return all tests.
	 *
	 * @return array<string, array> All test records indexed by test ID.
	 */
	public function list_tests(): array {
		return $this->load_tests();
	}

	/**
	 * Delete a test.
	 *
	 * @param string $test_id Test ID.
	 * @return bool True if deleted, false if not found.
	 */
	public function delete_test( string $test_id ): bool {
		$tests = $this->load_tests();
		if ( ! isset( $tests[ $test_id ] ) ) {
			return false;
		}

		unset( $tests[ $test_id ] );
		$this->save_tests( $tests );
		return true;
	}

	// -----------------------------------------------------------------------
	// Variant selection
	// -----------------------------------------------------------------------

	/**
	 * Choose the next variant for a test (balanced round-robin).
	 *
	 * Whichever variant has fewer runs gets selected next; ties go to 'a'.
	 *
	 * @param string $test_id Test ID.
	 * @return string         'a' or 'b'.
	 */
	public function get_next_variant( string $test_id ): string {
		$test = $this->get_test( $test_id );
		if ( ! $test || null !== $test['winner'] ) {
			return 'a';
		}

		$runs_a = $test['variants']['a']['runs'];
		$runs_b = $test['variants']['b']['runs'];

		return ( $runs_b < $runs_a ) ? 'b' : 'a';
	}

	// -----------------------------------------------------------------------
	// Score recording
	// -----------------------------------------------------------------------

	/**
	 * Record an article and its quality score for a variant.
	 *
	 * @param string $test_id Test ID.
	 * @param string $variant 'a' or 'b'.
	 * @param int    $post_id WordPress post ID.
	 * @param float  $score   Quality score (0–100).
	 */
	public function record_article( string $test_id, string $variant, int $post_id, float $score ): void {
		if ( ! in_array( $variant, [ 'a', 'b' ], true ) ) {
			return;
		}

		$tests = $this->load_tests();
		if ( ! isset( $tests[ $test_id ] ) ) {
			return;
		}

		$tests[ $test_id ]['variants'][ $variant ]['runs']++;
		$tests[ $test_id ]['variants'][ $variant ]['scores'][]   = $score;
		$tests[ $test_id ]['variants'][ $variant ]['post_ids'][] = $post_id;

		$this->save_tests( $tests );
	}

	/**
	 * Return the average quality score for a variant.
	 *
	 * @param string $test_id Test ID.
	 * @param string $variant 'a' or 'b'.
	 * @return float          Average score, or 0.0 if no data.
	 */
	public function get_average_score( string $test_id, string $variant ): float {
		$test = $this->get_test( $test_id );
		if ( ! $test ) {
			return 0.0;
		}

		$scores = $test['variants'][ $variant ]['scores'] ?? [];
		if ( empty( $scores ) ) {
			return 0.0;
		}

		return array_sum( $scores ) / count( $scores );
	}

	// -----------------------------------------------------------------------
	// Winner promotion
	// -----------------------------------------------------------------------

	/**
	 * Evaluate a specific test and elect the winning variant.
	 *
	 * The winner is the variant with the higher average quality score,
	 * provided both variants have at least MIN_ARTICLES_PER_VARIANT articles.
	 *
	 * @param string $test_id Test ID.
	 * @return string|null    'a' or 'b' if a winner was determined, null otherwise.
	 */
	public function promote_winner( string $test_id ): ?string {
		$tests = $this->load_tests();
		if ( ! isset( $tests[ $test_id ] ) ) {
			return null;
		}

		$test = $tests[ $test_id ];

		// Already promoted.
		if ( null !== $test['winner'] ) {
			return $test['winner'];
		}

		$scores_a = $test['variants']['a']['scores'];
		$scores_b = $test['variants']['b']['scores'];

		// Require minimum data before promoting.
		if ( count( $scores_a ) < self::MIN_ARTICLES_PER_VARIANT ||
		     count( $scores_b ) < self::MIN_ARTICLES_PER_VARIANT ) {
			return null;
		}

		$avg_a  = array_sum( $scores_a ) / count( $scores_a );
		$avg_b  = array_sum( $scores_b ) / count( $scores_b );
		$winner = ( $avg_b > $avg_a ) ? 'b' : 'a';

		$tests[ $test_id ]['winner']      = $winner;
		$tests[ $test_id ]['promoted_at'] = time();
		$this->save_tests( $tests );

		/**
		 * Action: pearblog_abtest_winner_promoted
		 *
		 * @param string $test_id  The test ID.
		 * @param string $winner   The winning variant ('a' or 'b').
		 * @param float  $avg_a    Average quality score for variant A.
		 * @param float  $avg_b    Average quality score for variant B.
		 */
		do_action( 'pearblog_abtest_winner_promoted', $test_id, $winner, $avg_a, $avg_b );

		return $winner;
	}

	/**
	 * Promote all tests that are older than PROMOTION_DAYS and not yet promoted.
	 *
	 * Called daily by the WP cron hook.
	 *
	 * @return array<string, string|null> Map of test_id → winner (null if not enough data).
	 */
	public function promote_mature_tests(): array {
		$results  = [];
		$min_time = time() - ( self::PROMOTION_DAYS * DAY_IN_SECONDS );

		foreach ( $this->load_tests() as $test_id => $test ) {
			if ( null !== $test['winner'] ) {
				continue; // Already done.
			}

			if ( $test['created_at'] > $min_time ) {
				continue; // Not mature yet.
			}

			$results[ $test_id ] = $this->promote_winner( $test_id );
		}

		return $results;
	}

	/**
	 * Return the prompt modifier for the winning variant of a test.
	 *
	 * @param string $test_id Test ID.
	 * @return string|null    Modifier string, or null if no winner yet.
	 */
	public function get_winning_modifier( string $test_id ): ?string {
		$test = $this->get_test( $test_id );
		if ( ! $test || null === $test['winner'] ) {
			return null;
		}

		return ( 'a' === $test['winner'] ) ? $test['modifier_a'] : $test['modifier_b'];
	}

	// -----------------------------------------------------------------------
	// Topic lookup
	// -----------------------------------------------------------------------

	/**
	 * Find the first active (not yet promoted) test for a given topic.
	 *
	 * @param string $topic Topic string (case-insensitive match).
	 * @return array|null   Test data array, or null if none found.
	 */
	public function get_active_test_for_topic( string $topic ): ?array {
		$topic_lc = strtolower( trim( $topic ) );

		foreach ( $this->load_tests() as $test ) {
			if ( null !== $test['winner'] ) {
				continue;
			}

			if ( strtolower( trim( $test['topic'] ) ) === $topic_lc ) {
				return $test;
			}
		}

		return null;
	}

	// -----------------------------------------------------------------------
	// WordPress filter/action callbacks
	// -----------------------------------------------------------------------

	/**
	 * Apply the assigned variant's modifier to the prompt.
	 *
	 * Called via the `pearblog_prompt` filter in PromptBuilder::build().
	 * The selected variant is stored in a transient for retrieval when the
	 * pipeline completes.
	 *
	 * @param string $prompt The assembled prompt.
	 * @param string $topic  The article topic.
	 * @return string        Modified prompt.
	 */
	public function apply_variant_modifier( string $prompt, string $topic ): string {
		$test = $this->get_active_test_for_topic( $topic );
		if ( ! $test ) {
			return $prompt;
		}

		$variant  = $this->get_next_variant( $test['id'] );
		$modifier = ( 'a' === $variant ) ? $test['modifier_a'] : $test['modifier_b'];

		// Persist the variant assignment for on_pipeline_completed().
		set_transient(
			'pearblog_abtest_run_' . md5( $topic ),
			[
				'test_id' => $test['id'],
				'variant' => $variant,
			],
			HOUR_IN_SECONDS
		);

		return $prompt . "\n\n" . $modifier;
	}

	/**
	 * Record the quality score for the variant used in this pipeline run.
	 *
	 * Called via the `pearblog_pipeline_completed` action.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $topic   Article topic.
	 */
	public function on_pipeline_completed( int $post_id, string $topic ): void {
		$transient_key = 'pearblog_abtest_run_' . md5( $topic );
		$run           = get_transient( $transient_key );

		if ( ! is_array( $run ) || empty( $run['test_id'] ) || empty( $run['variant'] ) ) {
			return;
		}

		delete_transient( $transient_key );

		$score = (float) get_post_meta( $post_id, QualityScorer::META_QUALITY_SCORE, true );
		$this->record_article( $run['test_id'], $run['variant'], $post_id, $score );
	}

	// -----------------------------------------------------------------------
	// Private storage helpers
	// -----------------------------------------------------------------------

	/**
	 * Load all tests from WP options.
	 *
	 * @return array<string, array>
	 */
	private function load_tests(): array {
		$data = get_option( self::OPTION_KEY, [] );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Persist the full tests array to WP options.
	 *
	 * @param array<string, array> $tests
	 */
	private function save_tests( array $tests ): void {
		update_option( self::OPTION_KEY, $tests );
	}
}
