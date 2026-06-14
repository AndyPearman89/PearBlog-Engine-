<?php
/**
 * Unit tests for BayesianOptimizer.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Testing\BayesianOptimizer;

class BayesianOptimizerTest extends TestCase {

	// -----------------------------------------------------------------------
	// Constructor validation
	// -----------------------------------------------------------------------

	public function test_requires_at_least_two_arms(): void {
		$this->expectException( \InvalidArgumentException::class );
		new BayesianOptimizer( [ 'a' ] );
	}

	public function test_allows_two_arms(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$this->assertInstanceOf( BayesianOptimizer::class, $opt );
	}

	public function test_allows_three_arms(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b', 'c' ] );
		$this->assertInstanceOf( BayesianOptimizer::class, $opt );
	}

	// -----------------------------------------------------------------------
	// Initial state
	// -----------------------------------------------------------------------

	public function test_initial_params_are_uniform_prior(): void {
		$opt    = new BayesianOptimizer( [ 'a', 'b' ] );
		$params = $opt->get_arm_params();

		$this->assertSame( 1.0, $params['a']['alpha'] );
		$this->assertSame( 1.0, $params['a']['beta'] );
		$this->assertSame( 1.0, $params['b']['alpha'] );
		$this->assertSame( 1.0, $params['b']['beta'] );
	}

	public function test_initial_total_observations_is_zero(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$this->assertSame( 0, $opt->get_total_observations() );
	}

	// -----------------------------------------------------------------------
	// select_arm
	// -----------------------------------------------------------------------

	public function test_select_arm_returns_valid_label(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$arm = $opt->select_arm();
		$this->assertContains( $arm, [ 'a', 'b' ] );
	}

	public function test_select_arm_returns_only_registered_labels(): void {
		$opt = new BayesianOptimizer( [ 'x', 'y', 'z' ] );
		for ( $i = 0; $i < 20; $i++ ) {
			$this->assertContains( $opt->select_arm(), [ 'x', 'y', 'z' ] );
		}
	}

	// -----------------------------------------------------------------------
	// update
	// -----------------------------------------------------------------------

	public function test_positive_reward_increments_alpha(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$opt->update( 'a', true );
		$params = $opt->get_arm_params();
		$this->assertSame( 2.0, $params['a']['alpha'] );
		$this->assertSame( 1.0, $params['a']['beta'] );
	}

	public function test_negative_reward_increments_beta(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$opt->update( 'a', false );
		$params = $opt->get_arm_params();
		$this->assertSame( 1.0, $params['a']['alpha'] );
		$this->assertSame( 2.0, $params['a']['beta'] );
	}

	public function test_update_throws_for_unknown_arm(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$this->expectException( \InvalidArgumentException::class );
		$opt->update( 'z', true );
	}

	public function test_update_increments_total_observations(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$opt->update( 'a', true );
		$opt->update( 'b', false );
		$this->assertSame( 2, $opt->get_total_observations() );
	}

	// -----------------------------------------------------------------------
	// get_probabilities
	// -----------------------------------------------------------------------

	public function test_probabilities_sum_to_approximately_one(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		// Feed asymmetric data so one arm dominates.
		for ( $i = 0; $i < 50; $i++ ) {
			$opt->update( 'a', true );
		}
		for ( $i = 0; $i < 10; $i++ ) {
			$opt->update( 'b', true );
		}

		$probs = $opt->get_probabilities();
		$sum   = array_sum( $probs );

		$this->assertEqualsWithDelta( 1.0, $sum, 0.05 );
	}

	public function test_probabilities_keys_match_arm_labels(): void {
		$opt   = new BayesianOptimizer( [ 'x', 'y' ] );
		$probs = $opt->get_probabilities();

		$this->assertArrayHasKey( 'x', $probs );
		$this->assertArrayHasKey( 'y', $probs );
	}

	public function test_winning_arm_has_higher_probability_after_many_successes(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );

		// Arm A: 80 successes, 20 failures.
		for ( $i = 0; $i < 80; $i++ ) {
			$opt->update( 'a', true );
		}
		for ( $i = 0; $i < 20; $i++ ) {
			$opt->update( 'a', false );
		}
		// Arm B: 20 successes, 80 failures.
		for ( $i = 0; $i < 20; $i++ ) {
			$opt->update( 'b', true );
		}
		for ( $i = 0; $i < 80; $i++ ) {
			$opt->update( 'b', false );
		}

		$probs = $opt->get_probabilities();
		$this->assertGreaterThan( $probs['b'], $probs['a'] );
	}

	// -----------------------------------------------------------------------
	// get_confidence
	// -----------------------------------------------------------------------

	public function test_get_confidence_returns_float_in_range(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$c   = $opt->get_confidence();
		$this->assertGreaterThanOrEqual( 0.0, $c );
		$this->assertLessThanOrEqual( 1.0, $c );
	}

	public function test_get_confidence_equals_max_probability(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		for ( $i = 0; $i < 60; $i++ ) {
			$opt->update( 'a', true );
		}

		$probs      = $opt->get_probabilities();
		$confidence = $opt->get_confidence();

		$this->assertEqualsWithDelta( max( $probs ), $confidence, 0.01 );
	}

	// -----------------------------------------------------------------------
	// is_converged
	// -----------------------------------------------------------------------

	public function test_is_converged_returns_false_with_no_data(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$this->assertFalse( $opt->is_converged() );
	}

	public function test_is_converged_returns_true_with_overwhelming_data(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );

		for ( $i = 0; $i < 200; $i++ ) {
			$opt->update( 'a', true );
		}
		for ( $i = 0; $i < 200; $i++ ) {
			$opt->update( 'b', false );
		}

		$this->assertTrue( $opt->is_converged() );
	}

	public function test_is_converged_respects_custom_threshold(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );

		// Even with no data both arms have 50% — should converge at 0.4 threshold.
		$this->assertTrue( $opt->is_converged( 0.40 ) );
	}

	// -----------------------------------------------------------------------
	// get_leading_arm
	// -----------------------------------------------------------------------

	public function test_get_leading_arm_returns_arm_label(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$arm = $opt->get_leading_arm();
		$this->assertContains( $arm, [ 'a', 'b' ] );
	}

	public function test_get_leading_arm_favours_arm_with_more_successes(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );

		for ( $i = 0; $i < 100; $i++ ) {
			$opt->update( 'a', true );
		}
		for ( $i = 0; $i < 100; $i++ ) {
			$opt->update( 'b', false );
		}

		$this->assertSame( 'a', $opt->get_leading_arm() );
	}

	// -----------------------------------------------------------------------
	// get_total_observations
	// -----------------------------------------------------------------------

	public function test_total_observations_counts_all_updates(): void {
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$opt->update( 'a', true );
		$opt->update( 'a', false );
		$opt->update( 'b', true );
		$this->assertSame( 3, $opt->get_total_observations() );
	}
}
