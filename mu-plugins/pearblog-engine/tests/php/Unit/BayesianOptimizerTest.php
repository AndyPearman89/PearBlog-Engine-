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
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_options']   = [];
		$this->opt             = new BayesianOptimizer();
	}

	// -----------------------------------------------------------------------
	// record_impression / record_conversion
	// -----------------------------------------------------------------------

	public function test_record_impression_increments_count(): void {
		$this->opt->record_impression( 1, 'test_1', 'A' );
		$this->opt->record_impression( 1, 'test_1', 'A' );

		$summary = $this->opt->summary( 1, 'test_1', [ 'A' ] );
		$this->assertSame( 2, $summary['variants']['A']['impressions'] );
	}

	public function test_record_conversion_increments_count(): void {
		$this->opt->record_impression( 1, 'test_1', 'B' );
		$this->opt->record_conversion( 1, 'test_1', 'B' );

		$summary = $this->opt->summary( 1, 'test_1', [ 'B' ] );
		$this->assertSame( 1, $summary['variants']['B']['conversions'] );
	}

	public function test_multiple_variants_tracked_independently(): void {
		$this->opt->record_impression( 2, 't', 'A' );
		$this->opt->record_impression( 2, 't', 'A' );
		$this->opt->record_conversion( 2, 't', 'A' );

		$this->opt->record_impression( 2, 't', 'B' );
		$this->opt->record_conversion( 2, 't', 'B' );
		$this->opt->record_conversion( 2, 't', 'B' );

		$summary = $this->opt->summary( 2, 't', [ 'A', 'B' ] );
		$this->assertSame( 2, $summary['variants']['A']['impressions'] );
		$this->assertSame( 1, $summary['variants']['A']['conversions'] );
		$this->assertSame( 1, $summary['variants']['B']['impressions'] );
	}

	// -----------------------------------------------------------------------
	// select_variant
	// -----------------------------------------------------------------------

	public function test_select_variant_returns_one_of_given_variants(): void {
		$variants = [ 'A', 'B', 'C' ];
		$selected = $this->opt->select_variant( 1, 'test', $variants );

		$this->assertContains( $selected, $variants );
	}

	public function test_select_variant_empty_list_returns_empty_string(): void {
		$this->assertSame( '', $this->opt->select_variant( 1, 'test', [] ) );
	}

	public function test_select_variant_prefers_high_conversion_variant(): void {
		// Give variant B a much higher conversion rate.
		for ( $i = 0; $i < 200; $i++ ) {
			$this->opt->record_impression( 10, 'big', 'A' );
			$this->opt->record_impression( 10, 'big', 'B' );
			$this->opt->record_conversion( 10, 'big', 'B' );
		}

		// Over many selections, B should be picked far more often than A.
		$counts = [ 'A' => 0, 'B' => 0 ];
		for ( $i = 0; $i < 100; $i++ ) {
			$v = $this->opt->select_variant( 10, 'big', [ 'A', 'B' ] );
			$counts[ $v ]++;
		}

		$this->assertGreaterThan( $counts['A'], $counts['B'] );
	}

	// -----------------------------------------------------------------------
	// win_probabilities
	// -----------------------------------------------------------------------

	public function test_win_probabilities_sum_to_one(): void {
		// Seed some data.
		for ( $i = 0; $i < 50; $i++ ) {
			$this->opt->record_impression( 5, 'wp', 'A' );
			$this->opt->record_impression( 5, 'wp', 'B' );
		}
		for ( $i = 0; $i < 30; $i++ ) {
			$this->opt->record_conversion( 5, 'wp', 'B' );
		}
		for ( $i = 0; $i < 5; $i++ ) {
			$this->opt->record_conversion( 5, 'wp', 'A' );
		}

		$probs = $this->opt->win_probabilities( 5, 'wp', [ 'A', 'B' ] );
		$this->assertArrayHasKey( 'A', $probs );
		$this->assertArrayHasKey( 'B', $probs );
		$this->assertEqualsWithDelta( 1.0, $probs['A'] + $probs['B'], 0.05 );
	}

	public function test_win_probabilities_empty_variants_returns_empty(): void {
		$probs = $this->opt->win_probabilities( 1, 't', [] );
		$this->assertSame( [], $probs );
	}

	// -----------------------------------------------------------------------
	// summary
	// -----------------------------------------------------------------------

	public function test_summary_not_ready_below_min_impressions(): void {
		$this->opt->record_impression( 3, 's', 'A' );
		$summary = $this->opt->summary( 3, 's', [ 'A', 'B' ] );

		$this->assertFalse( $summary['ready'] );
		$this->assertNull( $summary['winner'] );
	}

	public function test_summary_ready_above_min_impressions(): void {
		for ( $i = 0; $i < BayesianOptimizer::MIN_IMPRESSIONS; $i++ ) {
			$this->opt->record_impression( 4, 's2', 'A' );
			$this->opt->record_impression( 4, 's2', 'B' );
		}

		$summary = $this->opt->summary( 4, 's2', [ 'A', 'B' ] );
		$this->assertTrue( $summary['ready'] );
	}

	public function test_summary_conversion_rate_calculated(): void {
		$this->opt->record_impression( 7, 'cr', 'A' );
		$this->opt->record_impression( 7, 'cr', 'A' );
		$this->opt->record_conversion( 7, 'cr', 'A' );

		$summary = $this->opt->summary( 7, 'cr', [ 'A' ] );
		$this->assertEqualsWithDelta( 0.5, $summary['variants']['A']['rate'], 0.001 );
	}

	public function test_summary_winner_identified_with_dominant_variant(): void {
		// Give variant B a 90 % conversion rate over enough impressions.
		$n = (int) ceil( BayesianOptimizer::MIN_IMPRESSIONS * 2 );
		for ( $i = 0; $i < $n; $i++ ) {
			$this->opt->record_impression( 8, 'w', 'A' );
			$this->opt->record_impression( 8, 'w', 'B' );
			$this->opt->record_conversion( 8, 'w', 'B' ); // 100 % for B
			// A gets 0 conversions
		}

		$summary = $this->opt->summary( 8, 'w', [ 'A', 'B' ] );
		// With 100 % vs 0 % and ample data, B should win.
		$this->assertSame( 'B', $summary['winner'] );
	}

	// -----------------------------------------------------------------------
	// reset
	// -----------------------------------------------------------------------

	public function test_reset_clears_stats(): void {
		$this->opt->record_impression( 6, 'r', 'A' );
		$this->opt->reset( 6, 'r' );

		$summary = $this->opt->summary( 6, 'r', [ 'A' ] );
		$this->assertSame( 0, $summary['variants']['A']['impressions'] );
	}

	// -----------------------------------------------------------------------
	// sample_beta — basic sanity checks
	// -----------------------------------------------------------------------

	public function test_sample_beta_returns_value_in_unit_interval(): void {
		for ( $i = 0; $i < 20; $i++ ) {
			$v = $this->opt->sample_beta( 2.0, 2.0 );
			$this->assertGreaterThanOrEqual( 0.0, $v );
			$this->assertLessThanOrEqual( 1.0, $v );
		}
	}

	public function test_sample_beta_high_alpha_skews_toward_one(): void {
		$sum = 0.0;
		$n   = 50;
		for ( $i = 0; $i < $n; $i++ ) {
			$sum += $this->opt->sample_beta( 100.0, 1.0 );
		}
		// Mean of Beta(100, 1) ≈ 0.99
		$this->assertGreaterThan( 0.9, $sum / $n );
	}

	public function test_sample_beta_high_beta_skews_toward_zero(): void {
		$sum = 0.0;
		$n   = 50;
		for ( $i = 0; $i < $n; $i++ ) {
			$sum += $this->opt->sample_beta( 1.0, 100.0 );
		}
		// Mean of Beta(1, 100) ≈ 0.01
		$this->assertLessThan( 0.1, $sum / $n );
	}
}
