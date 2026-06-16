<?php
/**
 * Unit tests for BillingEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Tenant\BillingEngine;

class BillingEngineTest extends TestCase {

	private BillingEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->engine        = new BillingEngine();
	}

	// -----------------------------------------------------------------------
	// get_current_usage — defaults
	// -----------------------------------------------------------------------

	public function test_get_current_usage_returns_default_structure(): void {
		$usage = $this->engine->get_current_usage();

		$this->assertArrayHasKey( 'cost_cents', $usage );
		$this->assertArrayHasKey( 'generations', $usage );
		$this->assertArrayHasKey( 'period_start', $usage );
		$this->assertSame( 0.0, $usage['cost_cents'] );
		$this->assertSame( 0, $usage['generations'] );
	}

	// -----------------------------------------------------------------------
	// add_usage
	// -----------------------------------------------------------------------

	public function test_add_usage_increments_cost(): void {
		$this->engine->add_usage( 50.0 );

		$usage = $this->engine->get_current_usage();
		$this->assertSame( 50.0, $usage['cost_cents'] );
	}

	public function test_add_usage_accumulates_across_calls(): void {
		$this->engine->add_usage( 30.0 );
		$this->engine->add_usage( 20.0 );
		$this->engine->add_usage( 10.0 );

		$usage = $this->engine->get_current_usage();
		$this->assertSame( 60.0, $usage['cost_cents'] );
	}

	public function test_add_usage_accepts_fractional_cents(): void {
		$this->engine->add_usage( 0.5 );
		$this->engine->add_usage( 0.25 );

		$usage = $this->engine->get_current_usage();
		$this->assertEqualsWithDelta( 0.75, $usage['cost_cents'], 0.001 );
	}

	// -----------------------------------------------------------------------
	// get_usage_percentage
	// -----------------------------------------------------------------------

	public function test_get_usage_percentage_is_zero_when_no_usage(): void {
		update_option( BillingEngine::OPTION_QUOTA, 1000 );

		$this->assertSame( 0.0, $this->engine->get_usage_percentage() );
	}

	public function test_get_usage_percentage_calculates_correctly(): void {
		update_option( BillingEngine::OPTION_QUOTA, 1000 );
		$this->engine->add_usage( 500.0 );

		$this->assertSame( 50.0, $this->engine->get_usage_percentage() );
	}

	public function test_get_usage_percentage_returns_zero_when_quota_is_zero(): void {
		update_option( BillingEngine::OPTION_QUOTA, 0 );

		$this->assertSame( 0.0, $this->engine->get_usage_percentage() );
	}

	public function test_get_usage_percentage_can_exceed_100(): void {
		update_option( BillingEngine::OPTION_QUOTA, 100 );
		$this->engine->add_usage( 150.0 );

		$this->assertGreaterThan( 100.0, $this->engine->get_usage_percentage() );
	}

	public function test_get_usage_percentage_uses_default_quota(): void {
		// No quota option set — uses DEFAULT_QUOTA (1000).
		$this->engine->add_usage( 100.0 );

		$expected = round( ( 100.0 / BillingEngine::DEFAULT_QUOTA ) * 100, 1 );
		$this->assertSame( $expected, $this->engine->get_usage_percentage() );
	}

	// -----------------------------------------------------------------------
	// reset_billing_cycle
	// -----------------------------------------------------------------------

	public function test_reset_billing_cycle_clears_current_usage(): void {
		$this->engine->add_usage( 200.0 );
		$this->engine->reset_billing_cycle();

		$usage = $this->engine->get_current_usage();
		$this->assertSame( 0.0, $usage['cost_cents'] );
		$this->assertSame( 0, $usage['generations'] );
	}

	public function test_reset_billing_cycle_archives_previous_period(): void {
		$this->engine->add_usage( 300.0 );
		$this->engine->reset_billing_cycle();

		$history = get_option( BillingEngine::OPTION_USAGE_HISTORY, [] );
		$this->assertCount( 1, $history );
		$this->assertSame( 300.0, $history[0]['cost_cents'] );
	}

	public function test_reset_billing_cycle_accumulates_history(): void {
		for ( $i = 0; $i < 3; $i++ ) {
			$this->engine->add_usage( 100.0 );
			$this->engine->reset_billing_cycle();
		}

		$history = get_option( BillingEngine::OPTION_USAGE_HISTORY, [] );
		$this->assertCount( 3, $history );
	}

	public function test_reset_billing_cycle_caps_history_at_12(): void {
		for ( $i = 0; $i < 15; $i++ ) {
			$this->engine->add_usage( 10.0 );
			$this->engine->reset_billing_cycle();
		}

		$history = get_option( BillingEngine::OPTION_USAGE_HISTORY, [] );
		$this->assertCount( 12, $history );
	}

	public function test_reset_billing_cycle_keeps_most_recent_history(): void {
		for ( $i = 0; $i < 14; $i++ ) {
			$this->engine->add_usage( (float) ( $i + 1 ) );
			$this->engine->reset_billing_cycle();
		}

		$history = get_option( BillingEngine::OPTION_USAGE_HISTORY, [] );
		// The last 12 periods — the 3rd through 14th iterations.
		$this->assertSame( 3.0, $history[0]['cost_cents'] );
		$this->assertSame( 14.0, $history[11]['cost_cents'] );
	}

	public function test_reset_billing_cycle_sets_new_period_start(): void {
		$before_reset = time();
		$this->engine->reset_billing_cycle();
		$after_reset  = time();

		$usage = $this->engine->get_current_usage();
		$this->assertGreaterThanOrEqual( $before_reset, $usage['period_start'] );
		$this->assertLessThanOrEqual( $after_reset, $usage['period_start'] );
	}

	// -----------------------------------------------------------------------
	// record_generation_event
	// -----------------------------------------------------------------------

	public function test_record_generation_event_increments_generation_count(): void {
		$this->engine->record_generation_event( 1 );
		$this->engine->record_generation_event( 2 );

		$usage = $this->engine->get_current_usage();
		$this->assertSame( 2, $usage['generations'] );
	}

	// -----------------------------------------------------------------------
	// Option constants
	// -----------------------------------------------------------------------

	public function test_option_constants_are_unique_strings(): void {
		$constants = [
			BillingEngine::OPTION_ENABLED,
			BillingEngine::OPTION_STRIPE_KEY,
			BillingEngine::OPTION_ITEM_ID,
			BillingEngine::OPTION_QUOTA,
			BillingEngine::OPTION_THRESHOLD,
			BillingEngine::OPTION_USAGE_CURRENT,
			BillingEngine::OPTION_USAGE_HISTORY,
		];

		$this->assertCount( count( $constants ), array_unique( $constants ) );
	}

	// -----------------------------------------------------------------------
	// Default values
	// -----------------------------------------------------------------------

	public function test_default_quota_is_positive(): void {
		$this->assertGreaterThan( 0, BillingEngine::DEFAULT_QUOTA );
	}

	public function test_default_threshold_is_between_zero_and_one(): void {
		$this->assertGreaterThan( 0.0, BillingEngine::DEFAULT_THRESHOLD );
		$this->assertLessThanOrEqual( 1.0, BillingEngine::DEFAULT_THRESHOLD );
	}
}
