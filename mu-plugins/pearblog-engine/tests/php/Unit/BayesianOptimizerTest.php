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

	private BayesianOptimizer $opt;

	protected function setUp(): void {
		parent::setUp();
		$this->opt = new BayesianOptimizer();
	}

	// -----------------------------------------------------------------------
	// compute_beta_params()
	// -----------------------------------------------------------------------

	public function test_beta_params_zero_runs_returns_uniform_prior(): void {
		[ $alpha, $beta ] = $this->opt->compute_beta_params( 0.0, 0 );

		$this->assertSame( 1.0, $alpha );
		$this->assertSame( 1.0, $beta );
	}

	public function test_beta_params_high_score_has_more_alpha(): void {
		[ $alpha_high, ] = $this->opt->compute_beta_params( 900.0, 10 ); // avg 90
		[ $alpha_low, ]  = $this->opt->compute_beta_params( 400.0, 10 ); // avg 40

		$this->assertGreaterThan( $alpha_low, $alpha_high );
	}

	public function test_beta_params_alpha_and_beta_both_at_least_one(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			[ $alpha, $beta ] = $this->opt->compute_beta_params( (float) ( $i * 200 ), 10 );
			$this->assertGreaterThanOrEqual( 1.0, $alpha );
			$this->assertGreaterThanOrEqual( 1.0, $beta );
		}
	}

	// -----------------------------------------------------------------------
	// beta_sample() and gamma_sample()
	// -----------------------------------------------------------------------

	public function test_beta_sample_returns_value_between_zero_and_one(): void {
		for ( $i = 0; $i < 20; $i++ ) {
			$s = $this->opt->beta_sample( 2.0, 5.0 );
			$this->assertGreaterThanOrEqual( 0.0, $s );
			$this->assertLessThanOrEqual( 1.0, $s );
		}
	}

	public function test_beta_sample_with_very_strong_alpha_biases_towards_one(): void {
		// Beta(100, 1) should be heavily skewed towards 1.
		$sum = 0.0;
		$n   = 100;
		for ( $i = 0; $i < $n; $i++ ) {
			$sum += $this->opt->beta_sample( 100.0, 1.0 );
		}
		$this->assertGreaterThan( 0.8, $sum / $n );
	}

	public function test_gamma_sample_returns_positive_value(): void {
		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertGreaterThan( 0.0, $this->opt->gamma_sample( 2.0 ) );
		}
	}

	public function test_gamma_sample_with_fractional_shape(): void {
		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertGreaterThan( 0.0, $this->opt->gamma_sample( 0.5 ) );
		}
	}

	// -----------------------------------------------------------------------
	// prob_a_beats_b()
	// -----------------------------------------------------------------------

	public function test_prob_a_beats_b_with_identical_distributions_is_near_half(): void {
		$prob = $this->opt->prob_a_beats_b( 5.0, 5.0, 5.0, 5.0 );
		$this->assertEqualsWithDelta( 0.5, $prob, 0.05 );
	}

	public function test_prob_a_beats_b_when_a_clearly_better(): void {
		// A has high alpha (many successes), B has low alpha.
		$prob = $this->opt->prob_a_beats_b( 50.0, 2.0, 2.0, 50.0 );
		$this->assertGreaterThan( 0.90, $prob );
	}

	public function test_prob_a_beats_b_when_b_clearly_better(): void {
		$prob = $this->opt->prob_a_beats_b( 2.0, 50.0, 50.0, 2.0 );
		$this->assertLessThan( 0.10, $prob );
	}

	// -----------------------------------------------------------------------
	// evaluate()
	// -----------------------------------------------------------------------

	private function make_test( float $score_a, int $runs_a, float $score_b, int $runs_b ): array {
		return [
			'variants' => [
				'a' => [ 'runs' => $runs_a, 'total_score' => $score_a ],
				'b' => [ 'runs' => $runs_b, 'total_score' => $score_b ],
			],
		];
	}

	public function test_evaluate_no_data_returns_null_winner(): void {
		$result = $this->opt->evaluate( $this->make_test( 0, 0, 0, 0 ) );

		$this->assertNull( $result['winner'] );
		$this->assertFalse( $result['confident'] );
		$this->assertEqualsWithDelta( 0.5, $result['prob_a_better'], 0.1 );
	}

	public function test_evaluate_overwhelmingly_better_a_returns_winner_a(): void {
		// A: 100 runs, average 95. B: 100 runs, average 30.
		$result = $this->opt->evaluate( $this->make_test( 9500.0, 100, 3000.0, 100 ) );

		$this->assertSame( 'a', $result['winner'] );
		$this->assertTrue( $result['confident'] );
		$this->assertGreaterThanOrEqual( BayesianOptimizer::WIN_THRESHOLD, $result['prob_a_better'] );
	}

	public function test_evaluate_returns_samples_count(): void {
		$result = $this->opt->evaluate( $this->make_test( 800.0, 10, 600.0, 8 ) );

		$this->assertSame( 10, $result['samples_a'] );
		$this->assertSame( 8, $result['samples_b'] );
	}

	public function test_evaluate_result_has_expected_keys(): void {
		$result = $this->opt->evaluate( $this->make_test( 500.0, 5, 600.0, 5 ) );

		$this->assertArrayHasKey( 'winner', $result );
		$this->assertArrayHasKey( 'prob_a_better', $result );
		$this->assertArrayHasKey( 'confident', $result );
		$this->assertArrayHasKey( 'samples_a', $result );
		$this->assertArrayHasKey( 'samples_b', $result );
	}

	// -----------------------------------------------------------------------
	// thompson_sample()
	// -----------------------------------------------------------------------

	public function test_thompson_sample_returns_a_or_b(): void {
		$test   = $this->make_test( 700.0, 10, 600.0, 10 );
		$result = $this->opt->thompson_sample( $test );

		$this->assertContains( $result, [ 'a', 'b' ] );
	}

	public function test_thompson_sample_with_no_data_returns_a_or_b(): void {
		$test   = $this->make_test( 0.0, 0, 0.0, 0 );
		$result = $this->opt->thompson_sample( $test );

		$this->assertContains( $result, [ 'a', 'b' ] );
	}
}
