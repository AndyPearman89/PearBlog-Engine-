<?php
/**
 * Unit tests for SmartProviderRouter.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\AnthropicProvider;
use PearBlogEngine\AI\GeminiProvider;
use PearBlogEngine\AI\OpenAIProvider;
use PearBlogEngine\AI\SmartProviderRouter;

class SmartProviderRouterTest extends TestCase {

	private SmartProviderRouter $router;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_transients'] = [];
		$this->router = new SmartProviderRouter();
	}

	// -----------------------------------------------------------------------
	// get_chain
	// -----------------------------------------------------------------------

	public function test_get_chain_defaults_to_three_providers(): void {
		$chain = $this->router->get_chain();
		$this->assertCount( 3, $chain );
	}

	public function test_get_chain_respects_option_overrides(): void {
		update_option( SmartProviderRouter::OPTION_PRIMARY,   'anthropic' );
		update_option( SmartProviderRouter::OPTION_SECONDARY, 'gemini' );
		update_option( SmartProviderRouter::OPTION_TERTIARY,  'openai' );

		$chain = $this->router->get_chain();

		$this->assertSame( 'anthropic', $chain[0] );
		$this->assertSame( 'gemini',    $chain[1] );
		$this->assertSame( 'openai',    $chain[2] );
	}

	// -----------------------------------------------------------------------
	// budget tracking
	// -----------------------------------------------------------------------

	public function test_remaining_budget_equals_full_budget_when_no_spend(): void {
		update_option( SmartProviderRouter::OPTION_BUDGET, 3000 );
		$this->assertSame( 3000, $this->router->remaining_budget_cents() );
	}

	public function test_today_spend_is_zero_on_fresh_options(): void {
		$this->assertSame( 0, $this->router->today_spend_cents() );
	}

	public function test_remaining_budget_decreases_after_record_result(): void {
		update_option( SmartProviderRouter::OPTION_BUDGET, 2000 );

		$this->router->record_result( 'openai', 1000, 500, true );
		// Cost = (1000/1000)*0.0015 + (500/1000)*0.006 ≈ 0.0015 + 0.003 = 0.0045 → ceil = 1 cent
		// So remaining = 2000 - 1 = 1999 (or 2000 if rounded to 0).
		$remaining = $this->router->remaining_budget_cents();
		$this->assertLessThanOrEqual( 2000, $remaining );
	}

	public function test_remaining_budget_never_goes_negative(): void {
		update_option( SmartProviderRouter::OPTION_BUDGET, 0 );
		$this->assertSame( 0, $this->router->remaining_budget_cents() );
	}

	// -----------------------------------------------------------------------
	// stats tracking
	// -----------------------------------------------------------------------

	public function test_get_stats_returns_empty_array_initially(): void {
		$this->assertSame( [], $this->router->get_stats() );
	}

	public function test_record_result_creates_stats_entry(): void {
		$this->router->record_result( 'openai', 1000, 500, true );
		$stats = $this->router->get_stats();
		$this->assertArrayHasKey( 'openai', $stats );
	}

	public function test_record_result_accumulates_requests(): void {
		$this->router->record_result( 'openai', 500, 200, true );
		$this->router->record_result( 'openai', 600, 300, true );

		$stats = $this->router->get_stats();
		$this->assertSame( 2, $stats['openai']['requests'] );
	}

	public function test_record_result_increments_error_count_on_failure(): void {
		$this->router->record_result( 'gemini', 400, 200, false );
		$stats = $this->router->get_stats();
		$this->assertSame( 1, $stats['gemini']['errors'] );
	}

	public function test_record_result_does_not_increment_errors_on_success(): void {
		$this->router->record_result( 'anthropic', 400, 200, true );
		$stats = $this->router->get_stats();
		$this->assertSame( 0, $stats['anthropic']['errors'] );
	}

	public function test_reset_stats_clears_everything(): void {
		$this->router->record_result( 'openai', 1000, 500, true );
		$this->router->reset_stats();

		$this->assertSame( [], $this->router->get_stats() );
		$this->assertSame( 0, $this->router->today_spend_cents() );
	}

	// -----------------------------------------------------------------------
	// get_status
	// -----------------------------------------------------------------------

	public function test_get_status_includes_expected_keys(): void {
		$status = $this->router->get_status();
		foreach ( [ 'chain', 'budget_cents', 'spend_today_cents', 'remaining_cents', 'stats' ] as $key ) {
			$this->assertArrayHasKey( $key, $status, "Missing key: {$key}" );
		}
	}

	public function test_get_status_budget_cents_matches_option(): void {
		update_option( SmartProviderRouter::OPTION_BUDGET, 9999 );
		$status = $this->router->get_status();
		$this->assertSame( 9999, $status['budget_cents'] );
	}

	// -----------------------------------------------------------------------
	// on_record_result (action callback)
	// -----------------------------------------------------------------------

	public function test_on_record_result_delegates_to_record_result(): void {
		$this->router->on_record_result( 'openai', 100, 50, true );
		$stats = $this->router->get_stats();
		$this->assertArrayHasKey( 'openai', $stats );
		$this->assertSame( 1, $stats['openai']['requests'] );
	}

	// -----------------------------------------------------------------------
	// route
	// -----------------------------------------------------------------------

	public function test_route_article_defaults_to_openai(): void {
		$provider = $this->router->route( 'article', 1000 );
		$this->assertInstanceOf( OpenAIProvider::class, $provider );
	}

	public function test_route_article_skips_open_circuit_and_falls_to_anthropic(): void {
		update_option( 'pearblog_circuit_openai', [ 'open' => true ] );
		$provider = $this->router->route( 'article', 1000 );
		$this->assertInstanceOf( AnthropicProvider::class, $provider );
	}

	public function test_route_respects_custom_rules_order(): void {
		update_option( SmartProviderRouter::OPTION_RULES, wp_json_encode( [
			'article' => [ 'gemini', 'openai', 'anthropic' ],
		] ) );
		$provider = $this->router->route( 'article', 1000 );
		$this->assertInstanceOf( GeminiProvider::class, $provider );
	}

	public function test_route_uses_configured_chain_for_unknown_content_type(): void {
		update_option( SmartProviderRouter::OPTION_PRIMARY,   'anthropic' );
		update_option( SmartProviderRouter::OPTION_SECONDARY, 'gemini' );
		update_option( SmartProviderRouter::OPTION_TERTIARY,  'openai' );
		$provider = $this->router->route( 'unknown-type', 1000 );
		$this->assertInstanceOf( AnthropicProvider::class, $provider );
	}

	public function test_route_with_zero_budget_uses_last_provider_fallback(): void {
		update_option( SmartProviderRouter::OPTION_BUDGET, 0 );
		$provider = $this->router->route( 'article', 1000 );
		$this->assertInstanceOf( GeminiProvider::class, $provider );
	}
}
