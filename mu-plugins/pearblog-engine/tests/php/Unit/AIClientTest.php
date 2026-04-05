<?php
/**
 * Unit tests for AIClient circuit breaker and cost tracking.
 *
 * These tests exercise only the circuit-breaker state management logic.
 * HTTP calls are not made during unit tests.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\AIClient;

class AIClientTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		// Ensure circuit starts closed.
		AIClient::reset_circuit();
	}

	public function test_circuit_starts_closed(): void {
		$this->assertFalse( AIClient::is_circuit_open() );
	}

	public function test_reset_circuit_closes_open_circuit(): void {
		// Force circuit open by writing state directly.
		update_option( 'pearblog_ai_circuit_state', [
			'failures'    => 10,
			'open'        => true,
			'retry_after' => time() + 300,
		] );

		$this->assertTrue( AIClient::is_circuit_open() );

		AIClient::reset_circuit();

		$this->assertFalse( AIClient::is_circuit_open() );
	}

	public function test_circuit_auto_closes_after_cooldown(): void {
		// Set retry_after in the past.
		update_option( 'pearblog_ai_circuit_state', [
			'failures'    => 10,
			'open'        => true,
			'retry_after' => time() - 1, // Already expired.
		] );

		// Should auto-close (transition to half-open).
		$this->assertFalse( AIClient::is_circuit_open() );
	}

	public function test_cost_starts_at_zero_after_reset(): void {
		AIClient::reset_cost();
		$this->assertSame( 0.0, AIClient::get_total_cost_cents() );
	}

	public function test_cost_accumulates(): void {
		AIClient::reset_cost();

		update_option( 'pearblog_ai_cost_cents', 5.0 );
		update_option( 'pearblog_ai_cost_cents', (float) get_option( 'pearblog_ai_cost_cents', 0 ) + 3.2 );

		$this->assertEqualsWithDelta( 8.2, AIClient::get_total_cost_cents(), 0.001 );
	}

	public function test_missing_api_key_throws_runtime_exception(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/API key is not configured/i' );

		$client = new AIClient( '' );
		// We can't actually make an HTTP request in unit tests, but
		// the API key check fires before any network call.
		// We need to reflect or mock – instead just verify the thrown exception message.
		// Use reflection to call generate() safely.
		$client->generate( 'test prompt' );
	}
}
