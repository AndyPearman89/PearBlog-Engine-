<?php
/**
 * Unit tests for BayesianOptimizer (V9.0 F3).
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Testing\BayesianOptimizer;

class BayesianOptimizerTest extends TestCase {

	private BayesianOptimizer $optimizer;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']  = [];
		$this->optimizer      = new BayesianOptimizer();
	}

	// -----------------------------------------------------------------------
	// register_test
	// -----------------------------------------------------------------------

	public function test_register_test_initialises_priors(): void {
		$this->optimizer->register_test( 't1', [ 'v_a', 'v_b' ] );
		$state = $this->optimizer->get_arm_state( 't1' );
		$this->assertArrayHasKey( 'v_a', $state );
		$this->assertArrayHasKey( 'v_b', $state );
		$this->assertSame( BayesianOptimizer::PRIOR_ALPHA, $state['v_a']['alpha'] );
		$this->assertSame( BayesianOptimizer::PRIOR_BETA, $state['v_a']['beta'] );
	}

	public function test_register_test_does_not_overwrite_existing_arms(): void {
		$this->optimizer->register_test( 't1', [ 'v_a' ] );
		$this->optimizer->record_observation( 't1', 'v_a', true );
		$this->optimizer->register_test( 't1', [ 'v_a', 'v_b' ] );

		$state = $this->optimizer->get_arm_state( 't1' );
		// v_a should have alpha = prior + 1 (not reset).
		$this->assertSame( BayesianOptimizer::PRIOR_ALPHA + 1, $state['v_a']['alpha'] );
		// v_b was newly added.
		$this->assertArrayHasKey( 'v_b', $state );
	}

	// -----------------------------------------------------------------------
	// record_observation
	// -----------------------------------------------------------------------

	public function test_success_increments_alpha(): void {
		$this->optimizer->register_test( 't1', [ 'va' ] );
		$before = $this->optimizer->get_arm_state( 't1' )['va']['alpha'];
		$this->optimizer->record_observation( 't1', 'va', true );
		$after  = $this->optimizer->get_arm_state( 't1' )['va']['alpha'];
		$this->assertSame( $before + 1, $after );
	}

	public function test_failure_increments_beta(): void {
		$this->optimizer->register_test( 't1', [ 'va' ] );
		$before = $this->optimizer->get_arm_state( 't1' )['va']['beta'];
		$this->optimizer->record_observation( 't1', 'va', false );
		$after  = $this->optimizer->get_arm_state( 't1' )['va']['beta'];
		$this->assertSame( $before + 1, $after );
	}

	// -----------------------------------------------------------------------
	// select_variant
	// -----------------------------------------------------------------------

	public function test_select_variant_returns_null_for_unknown_test(): void {
		$this->assertNull( $this->optimizer->select_variant( 'nonexistent' ) );
	}

	public function test_select_variant_returns_registered_variant(): void {
		$this->optimizer->register_test( 't1', [ 'a', 'b' ] );
		$selected = $this->optimizer->select_variant( 't1' );
		$this->assertContains( $selected, [ 'a', 'b' ] );
	}

	public function test_heavily_winning_variant_is_selected_most_often(): void {
		$this->optimizer->register_test( 't1', [ 'winner', 'loser' ] );

		// Give 'winner' a much higher success rate.
		for ( $i = 0; $i < 50; $i++ ) {
			$this->optimizer->record_observation( 't1', 'winner', true );
		}
		for ( $i = 0; $i < 2; $i++ ) {
			$this->optimizer->record_observation( 't1', 'loser', true );
		}
		for ( $i = 0; $i < 50; $i++ ) {
			$this->optimizer->record_observation( 't1', 'loser', false );
		}

		$counts = [ 'winner' => 0, 'loser' => 0 ];
		for ( $i = 0; $i < 100; $i++ ) {
			$selected                  = $this->optimizer->select_variant( 't1' );
			$counts[ $selected ?? '' ] = ( $counts[ $selected ?? '' ] ?? 0 ) + 1;
		}

		$this->assertGreaterThan( $counts['loser'], $counts['winner'] );
	}

	// -----------------------------------------------------------------------
	// expected_conversion_rate
	// -----------------------------------------------------------------------

	public function test_expected_rate_is_05_for_uninformed_prior(): void {
		$this->optimizer->register_test( 't1', [ 'v1' ] );
		$rate = $this->optimizer->expected_conversion_rate( 't1', 'v1' );
		$this->assertEqualsWithDelta( 0.5, $rate, 0.01 );
	}

	public function test_expected_rate_approaches_1_with_all_successes(): void {
		$this->optimizer->register_test( 't1', [ 'v1' ] );
		for ( $i = 0; $i < 100; $i++ ) {
			$this->optimizer->record_observation( 't1', 'v1', true );
		}
		$rate = $this->optimizer->expected_conversion_rate( 't1', 'v1' );
		$this->assertGreaterThan( 0.98, $rate );
	}

	public function test_expected_rate_for_unknown_returns_half(): void {
		// No test registered – returns prior mean = 0.5.
		$rate = $this->optimizer->expected_conversion_rate( 'unknown', 'v1' );
		$this->assertEqualsWithDelta( 0.5, $rate, 0.01 );
	}

	// -----------------------------------------------------------------------
	// get_arm_state
	// -----------------------------------------------------------------------

	public function test_get_arm_state_includes_probability_best(): void {
		$this->optimizer->register_test( 't1', [ 'a', 'b' ] );
		$state = $this->optimizer->get_arm_state( 't1' );
		$this->assertArrayHasKey( 'probability_best', $state['a'] );
		$prob_sum = $state['a']['probability_best'] + $state['b']['probability_best'];
		// Two equal variants: each ≈ 0.5; sum ≈ 1.0 within Monte-Carlo variance.
		$this->assertEqualsWithDelta( 1.0, $prob_sum, 0.1 );
	}

	// -----------------------------------------------------------------------
	// beta_sample
	// -----------------------------------------------------------------------

	public function test_beta_sample_in_unit_interval(): void {
		$opt = new BayesianOptimizer();
		for ( $i = 0; $i < 100; $i++ ) {
			$s = $opt->beta_sample( 2.0, 2.0 );
			$this->assertGreaterThanOrEqual( 0.0, $s );
			$this->assertLessThanOrEqual( 1.0, $s );
		}
	}

	public function test_beta_sample_with_small_shape(): void {
		$opt = new BayesianOptimizer();
		$s   = $opt->beta_sample( 0.5, 0.5 );
		$this->assertGreaterThanOrEqual( 0.0, $s );
		$this->assertLessThanOrEqual( 1.0, $s );
	}
}
