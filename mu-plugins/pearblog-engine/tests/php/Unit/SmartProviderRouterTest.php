<?php
/**
 * Unit tests for SmartProviderRouter (V9.0 F7).
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
	// get_available_providers
	// -----------------------------------------------------------------------

	public function test_all_providers_available_by_default(): void {
		$available = $this->router->get_available_providers();
		$this->assertContains( 'openai', $available );
		$this->assertContains( 'anthropic', $available );
		$this->assertContains( 'gemini', $available );
	}

	public function test_provider_excluded_when_circuit_open(): void {
		update_option( SmartProviderRouter::OPTION_CIRCUIT_STATE, [
			'anthropic' => [ 'open' => true ],
		] );
		$available = $this->router->get_available_providers();
		$this->assertNotContains( 'anthropic', $available );
		$this->assertContains( 'openai', $available );
	}

	public function test_provider_excluded_when_budget_exceeded(): void {
		$today = gmdate( 'Y-m-d' );
		update_option( SmartProviderRouter::OPTION_BUDGET_CAPS, [ 'gemini' => 0.01 ] );
		update_option( SmartProviderRouter::OPTION_DAILY_SPEND, [ 'gemini' => [ $today => 1.00 ] ] );
		$available = $this->router->get_available_providers();
		$this->assertNotContains( 'gemini', $available );
	}

	// -----------------------------------------------------------------------
	// select
	// -----------------------------------------------------------------------

	public function test_select_returns_string(): void {
		$provider = $this->router->select( 'short-form' );
		$this->assertIsString( $provider );
	}

	public function test_select_falls_back_to_openai_when_no_candidates(): void {
		// Disable all providers.
		update_option( SmartProviderRouter::OPTION_CIRCUIT_STATE, [
			'openai'    => [ 'open' => true ],
			'anthropic' => [ 'open' => true ],
			'gemini'    => [ 'open' => true ],
		] );
		$provider = $this->router->select( 'short-form' );
		$this->assertSame( 'openai', $provider );
	}

	// -----------------------------------------------------------------------
	// record_call
	// -----------------------------------------------------------------------

	public function test_record_call_increments_call_count(): void {
		$this->router->record_call( 'openai', 200, true, 80.0, 0.001 );
		$stats = $this->router->get_provider_stats( 'openai' );
		$this->assertSame( 1, $stats['calls'] );
	}

	public function test_record_call_tracks_errors(): void {
		$this->router->record_call( 'openai', 500, false, 0.0 );
		$stats = $this->router->get_provider_stats( 'openai' );
		$this->assertSame( 1, $stats['errors'] );
	}

	public function test_record_call_computes_avg_latency(): void {
		$this->router->record_call( 'openai', 200, true );
		$this->router->record_call( 'openai', 400, true );
		$stats = $this->router->get_provider_stats( 'openai' );
		$this->assertSame( 300, $stats['avg_latency_ms'] );
	}

	public function test_record_call_computes_avg_quality(): void {
		$this->router->record_call( 'gemini', 100, true, 60.0 );
		$this->router->record_call( 'gemini', 100, true, 80.0 );
		$stats = $this->router->get_provider_stats( 'gemini' );
		$this->assertEqualsWithDelta( 70.0, $stats['avg_quality'], 0.1 );
	}

	// -----------------------------------------------------------------------
	// circuit breaker
	// -----------------------------------------------------------------------

	public function test_circuit_opens_after_high_error_rate(): void {
		// Need MIN_CALLS_FOR_EVAL calls, all failures.
		$min = SmartProviderRouter::MIN_CALLS_FOR_EVAL;
		for ( $i = 0; $i < $min; $i++ ) {
			$this->router->record_call( 'anthropic', 500, false );
		}

		$available = $this->router->get_available_providers();
		$this->assertNotContains( 'anthropic', $available );
	}

	public function test_circuit_stays_closed_with_low_error_rate(): void {
		$min = SmartProviderRouter::MIN_CALLS_FOR_EVAL;
		for ( $i = 0; $i < $min; $i++ ) {
			$this->router->record_call( 'openai', 200, true );
		}
		$this->router->record_call( 'openai', 200, false ); // 1 failure.

		$available = $this->router->get_available_providers();
		$this->assertContains( 'openai', $available );
	}

	// -----------------------------------------------------------------------
	// score_provider
	// -----------------------------------------------------------------------

	public function test_score_provider_returns_negative_when_latency_exceeds_sla(): void {
		update_option( SmartProviderRouter::OPTION_STATS, [
			'openai' => [ 'avg_latency_ms' => 10000, 'avg_quality' => 80.0 ],
		] );
		$score = $this->router->score_provider( 'openai', 'short-form', 500, 3000 );
		$this->assertSame( -1.0, $score );
	}

	// -----------------------------------------------------------------------
	// cost_per_1k
	// -----------------------------------------------------------------------

	public function test_cost_per_1k_returns_float(): void {
		$cost = $this->router->cost_per_1k( 'openai', 'short-form' );
		$this->assertIsFloat( $cost );
		$this->assertGreaterThan( 0.0, $cost );
	}

	public function test_gemini_cheaper_than_openai_default(): void {
		$openai_cost = $this->router->cost_per_1k( 'openai', 'default' );
		$gemini_cost = $this->router->cost_per_1k( 'gemini', 'default' );
		// openai_cost (0.002) should be greater than gemini_cost (0.0005).
		$this->assertGreaterThan( $gemini_cost, $openai_cost );
	}
}
