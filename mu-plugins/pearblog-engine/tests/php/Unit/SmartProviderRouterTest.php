<?php
/**
 * Unit tests for SmartProviderRouter.
 *
 * The router delegates actual HTTP calls to AIProviderFactory::make(), which
 * in a unit-test context will fail because no real providers are registered.
 * We test the pure-logic paths (routing order, cost estimation, stats
 * persistence, budget tracking) without touching network I/O.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\SmartProviderRouter;

class SmartProviderRouterTest extends TestCase {

	private SmartProviderRouter $router;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->router        = new SmartProviderRouter();
	}

	// -----------------------------------------------------------------------
	// get_ordered_providers — routing table
	// -----------------------------------------------------------------------

	public function test_long_form_defaults_to_anthropic_first(): void {
		$providers = $this->router->get_ordered_providers( SmartProviderRouter::CONTENT_LONG_FORM );

		$this->assertSame( 'anthropic', $providers[0] );
	}

	public function test_code_defaults_to_openai_first(): void {
		$providers = $this->router->get_ordered_providers( SmartProviderRouter::CONTENT_CODE );

		$this->assertSame( 'openai', $providers[0] );
	}

	public function test_factual_defaults_to_gemini_first(): void {
		$providers = $this->router->get_ordered_providers( SmartProviderRouter::CONTENT_FACTUAL );

		$this->assertSame( 'gemini', $providers[0] );
	}

	public function test_unknown_content_type_falls_back_to_short_form_order(): void {
		$default   = $this->router->get_ordered_providers( SmartProviderRouter::CONTENT_SHORT_FORM );
		$unknown   = $this->router->get_ordered_providers( 'unknown_type_xyz' );

		$this->assertSame( $default, $unknown );
	}

	public function test_all_providers_present_in_result(): void {
		$providers = $this->router->get_ordered_providers( SmartProviderRouter::CONTENT_SHORT_FORM );

		$this->assertContains( 'openai', $providers );
		$this->assertContains( 'anthropic', $providers );
		$this->assertContains( 'gemini', $providers );
	}

	// -----------------------------------------------------------------------
	// Stats persistence
	// -----------------------------------------------------------------------

	public function test_get_stats_returns_empty_array_initially(): void {
		$this->assertSame( [], $this->router->get_stats() );
	}

	public function test_reset_stats_clears_data(): void {
		// Manually populate stats via the option.
		update_option( SmartProviderRouter::OPT_STATS, wp_json_encode( [ 'openai' => [ 'successes' => 5 ] ] ) );

		$this->router->reset_stats();

		$this->assertSame( [], $this->router->get_stats() );
	}

	// -----------------------------------------------------------------------
	// estimate_cost_cents
	// -----------------------------------------------------------------------

	public function test_openai_cost_estimation(): void {
		$cost = $this->router->estimate_cost_cents( 'openai', 1000, 500 );

		// 1000 input * 0.5 / 1000 + 500 output * 1.5 / 1000 = 0.5 + 0.75 = 1.25
		$this->assertEqualsWithDelta( 1.25, $cost, 0.001 );
	}

	public function test_anthropic_cost_estimation(): void {
		$cost = $this->router->estimate_cost_cents( 'anthropic', 1000, 1000 );

		// 1 * 0.8 + 1 * 2.4 = 3.2
		$this->assertEqualsWithDelta( 3.2, $cost, 0.001 );
	}

	public function test_gemini_cost_is_cheapest(): void {
		$openai    = $this->router->estimate_cost_cents( 'openai', 1000, 1000 );
		$anthropic = $this->router->estimate_cost_cents( 'anthropic', 1000, 1000 );
		$gemini    = $this->router->estimate_cost_cents( 'gemini', 1000, 1000 );

		$this->assertLessThan( $openai, $gemini );
		$this->assertLessThan( $anthropic, $gemini );
	}

	public function test_zero_tokens_costs_nothing(): void {
		$cost = $this->router->estimate_cost_cents( 'openai', 0, 0 );
		$this->assertSame( 0.0, $cost );
	}

	public function test_unknown_provider_returns_fallback_cost(): void {
		$cost = $this->router->estimate_cost_cents( 'mystery_ai', 1000, 1000 );
		// Falls back to in=1.0, out=2.0 => 1.0 + 2.0 = 3.0
		$this->assertEqualsWithDelta( 3.0, $cost, 0.001 );
	}

	// -----------------------------------------------------------------------
	// Daily budget
	// -----------------------------------------------------------------------

	public function test_get_daily_budget_default(): void {
		$this->assertSame( 500.0, $this->router->get_daily_budget() );
	}

	public function test_get_today_cost_starts_at_zero(): void {
		$this->assertSame( 0.0, $this->router->get_today_cost() );
	}

	public function test_budget_not_exhausted_when_no_cost(): void {
		$this->assertFalse( $this->router->is_budget_exhausted() );
	}

	public function test_budget_exhausted_when_cost_meets_limit(): void {
		// Set a tiny budget then simulate spending it.
		update_option( SmartProviderRouter::OPT_DAILY_BUDGET_CENTS, 1.0 );
		update_option( SmartProviderRouter::OPT_TODAY_COST, 1.0 );
		update_option( SmartProviderRouter::OPT_TODAY_DATE, gmdate( 'Y-m-d' ) );

		$this->assertTrue( $this->router->is_budget_exhausted() );
	}

	public function test_budget_not_exhausted_when_zero_budget_set(): void {
		// Budget = 0 means unlimited.
		update_option( SmartProviderRouter::OPT_DAILY_BUDGET_CENTS, 0.0 );
		update_option( SmartProviderRouter::OPT_TODAY_COST, 999.0 );
		update_option( SmartProviderRouter::OPT_TODAY_DATE, gmdate( 'Y-m-d' ) );

		$this->assertFalse( $this->router->is_budget_exhausted() );
	}

	public function test_daily_cost_resets_on_new_day(): void {
		// Set a cost for yesterday.
		update_option( SmartProviderRouter::OPT_TODAY_DATE, '2000-01-01' );
		update_option( SmartProviderRouter::OPT_TODAY_COST, 999.0 );

		// get_today_cost() should detect the stale date and reset.
		$this->assertSame( 0.0, $this->router->get_today_cost() );
	}

	// -----------------------------------------------------------------------
	// Sidelining low-success-rate providers
	// -----------------------------------------------------------------------

	public function test_low_success_rate_provider_is_sidelined(): void {
		// Record many failures for openai.
		$stats = [
			'openai' => [ 'successes' => 0, 'failures' => 10, 'total_tokens' => 0, 'total_cost_cents' => 0.0 ],
		];
		update_option( SmartProviderRouter::OPT_STATS, wp_json_encode( $stats ) );

		$providers = $this->router->get_ordered_providers( SmartProviderRouter::CONTENT_CODE );

		// openai should be removed when it has 0 % success rate over >5 attempts.
		$this->assertNotContains( 'openai', $providers );
	}

	public function test_provider_not_sidelined_below_min_sample(): void {
		// Only 3 failures — below the 5-attempt threshold.
		$stats = [
			'openai' => [ 'successes' => 0, 'failures' => 3, 'total_tokens' => 0, 'total_cost_cents' => 0.0 ],
		];
		update_option( SmartProviderRouter::OPT_STATS, wp_json_encode( $stats ) );

		$providers = $this->router->get_ordered_providers( SmartProviderRouter::CONTENT_CODE );

		$this->assertContains( 'openai', $providers );
	}
}
