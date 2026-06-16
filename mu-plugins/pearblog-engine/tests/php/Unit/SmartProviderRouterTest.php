<?php
/**
 * Unit tests for SmartProviderRouter.
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
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$this->router = new SmartProviderRouter();
	}

	// -----------------------------------------------------------------------
	// Strategy management
	// -----------------------------------------------------------------------

	public function test_get_strategy_returns_default_when_not_set(): void {
		$this->assertSame( SmartProviderRouter::DEFAULT_STRATEGY, $this->router->get_strategy() );
	}

	public function test_set_strategy_persists_valid_strategy(): void {
		$this->router->set_strategy( 'round_robin' );

		$this->assertSame( 'round_robin', $this->router->get_strategy() );
	}

	public function test_set_strategy_throws_on_unknown_strategy(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->router->set_strategy( 'magic' );
	}

	public function test_all_strategies_are_accepted(): void {
		foreach ( SmartProviderRouter::STRATEGIES as $strategy ) {
			$this->router->set_strategy( $strategy );
			$this->assertSame( $strategy, $this->router->get_strategy() );
		}
	}

	// -----------------------------------------------------------------------
	// Stats & health
	// -----------------------------------------------------------------------

	public function test_get_stats_empty_initially(): void {
		$this->assertSame( [], $this->router->get_stats() );
	}

	public function test_record_success_increments_stats(): void {
		$this->router->record_success( 'openai', 120 );
		$stats = $this->router->get_stats();

		$this->assertSame( 1, $stats['openai']['total_calls'] );
		$this->assertSame( 1, $stats['openai']['success'] );
		$this->assertSame( 0, $stats['openai']['failures'] );
		$this->assertSame( 120, $stats['openai']['total_latency_ms'] );
	}

	public function test_record_failure_increments_failures(): void {
		$this->router->record_failure( 'anthropic' );
		$stats = $this->router->get_stats();

		$this->assertSame( 1, $stats['anthropic']['failures'] );
		$this->assertSame( 1, $stats['anthropic']['consecutive_failures'] );
	}

	public function test_record_success_resets_consecutive_failures(): void {
		$this->router->record_failure( 'openai' );
		$this->router->record_failure( 'openai' );
		$this->router->record_success( 'openai', 50 );

		$stats = $this->router->get_stats();
		$this->assertSame( 0, $stats['openai']['consecutive_failures'] );
	}

	public function test_circuit_opens_after_threshold_failures(): void {
		for ( $i = 0; $i < SmartProviderRouter::CIRCUIT_OPEN_THRESHOLD; $i++ ) {
			$this->router->record_failure( 'gemini' );
		}

		$health = $this->router->get_health();
		$this->assertSame( 'circuit_open', $health['gemini'] );
	}

	public function test_circuit_not_open_before_threshold(): void {
		for ( $i = 0; $i < SmartProviderRouter::CIRCUIT_OPEN_THRESHOLD - 1; $i++ ) {
			$this->router->record_failure( 'gemini' );
		}

		$health = $this->router->get_health();
		$this->assertNotSame( 'circuit_open', $health['gemini'] ?? 'healthy' );
	}

	public function test_set_health_stores_status(): void {
		$this->router->set_health( 'openai', 'healthy', 0 );
		$health = $this->router->get_health();

		$this->assertSame( 'healthy', $health['openai'] );
	}

	// -----------------------------------------------------------------------
	// pick_providers()
	// -----------------------------------------------------------------------

	public function test_pick_providers_override_returns_single_provider(): void {
		$providers = $this->router->pick_providers( 'openai' );

		$this->assertSame( [ 'openai' ], $providers );
	}

	public function test_pick_providers_unknown_override_returns_all(): void {
		$providers = $this->router->pick_providers( 'unknown' );

		$this->assertGreaterThanOrEqual( 1, count( $providers ) );
	}

	public function test_pick_providers_excludes_circuit_open_providers(): void {
		// Open circuit for openai.
		for ( $i = 0; $i < SmartProviderRouter::CIRCUIT_OPEN_THRESHOLD; $i++ ) {
			$this->router->record_failure( 'openai' );
		}

		$providers = $this->router->pick_providers();

		$this->assertNotContains( 'openai', $providers );
	}

	public function test_pick_providers_returns_all_slugs_when_all_circuits_open(): void {
		// Open all circuits — router should fall back to trying all anyway.
		foreach ( [ 'openai', 'anthropic', 'gemini' ] as $slug ) {
			for ( $i = 0; $i < SmartProviderRouter::CIRCUIT_OPEN_THRESHOLD; $i++ ) {
				$this->router->record_failure( $slug );
			}
		}

		$providers = $this->router->pick_providers();

		$this->assertNotEmpty( $providers );
	}

	public function test_cost_optimised_orders_by_cost(): void {
		$this->router->set_strategy( 'cost_optimised' );
		$providers = $this->router->pick_providers();

		// Gemini is cheapest (0.001) so should be first.
		$this->assertSame( 'gemini', $providers[0] );
	}

	public function test_round_robin_orders_by_call_count(): void {
		$this->router->set_strategy( 'round_robin' );

		// Give openai the most calls.
		$this->router->record_success( 'openai', 10 );
		$this->router->record_success( 'openai', 10 );
		$this->router->record_success( 'openai', 10 );

		$providers = $this->router->pick_providers();

		// openai has most calls so should be last in round-robin order.
		$this->assertNotSame( 'openai', $providers[0] );
	}
}
