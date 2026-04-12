<?php
/**
 * Unit tests for RateLimiter.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\API\RateLimiter;

class RateLimiterTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_window_and_limit_constants_are_defined(): void {
		$this->assertSame( 60, RateLimiter::WINDOW_SECONDS );
		$this->assertSame( 120, RateLimiter::LIMIT_READ );
		$this->assertSame( 30, RateLimiter::LIMIT_WRITE );
		$this->assertSame( 5, RateLimiter::LIMIT_PIPELINE );
	}

	// -----------------------------------------------------------------------
	// check() – basic behaviour
	// -----------------------------------------------------------------------

	public function test_first_request_is_allowed(): void {
		$limiter = new RateLimiter();
		$result  = $limiter->check( 'client1', 'endpoint', 10 );

		$this->assertTrue( $result['allowed'] );
		$this->assertSame( 9, $result['remaining'] );
		$this->assertSame( 10, $result['limit'] );
		$this->assertGreaterThan( time(), $result['reset'] );
	}

	public function test_request_within_limit_is_allowed(): void {
		$limiter = new RateLimiter();

		for ( $i = 0; $i < 4; $i++ ) {
			$result = $limiter->check( 'client_a', 'ep', 5 );
			$this->assertTrue( $result['allowed'] );
		}

		// 4th request → remaining = 1.
		$this->assertSame( 1, $result['remaining'] );
	}

	public function test_request_at_exact_limit_is_allowed(): void {
		$limiter = new RateLimiter();

		for ( $i = 0; $i < 5; $i++ ) {
			$result = $limiter->check( 'client_b', 'ep', 5 );
		}

		// Exactly at limit (5th request).
		$this->assertTrue( $result['allowed'] );
		$this->assertSame( 0, $result['remaining'] );
	}

	public function test_request_exceeding_limit_is_blocked(): void {
		$limiter = new RateLimiter();

		for ( $i = 0; $i < 6; $i++ ) {
			$result = $limiter->check( 'client_c', 'ep', 5 );
		}

		// 6th request → over limit.
		$this->assertFalse( $result['allowed'] );
		$this->assertSame( 0, $result['remaining'] );
	}

	public function test_different_clients_have_independent_counters(): void {
		$limiter = new RateLimiter();

		// Exhaust limit for client_x.
		for ( $i = 0; $i < 5; $i++ ) {
			$limiter->check( 'client_x', 'ep', 5 );
		}
		$blocked = $limiter->check( 'client_x', 'ep', 5 );
		$this->assertFalse( $blocked['allowed'] );

		// client_y should still have its full quota.
		$fresh = $limiter->check( 'client_y', 'ep', 5 );
		$this->assertTrue( $fresh['allowed'] );
		$this->assertSame( 4, $fresh['remaining'] );
	}

	public function test_different_endpoints_have_independent_counters(): void {
		$limiter = new RateLimiter();

		for ( $i = 0; $i < 5; $i++ ) {
			$limiter->check( 'client', 'endpoint_a', 5 );
		}
		$blocked = $limiter->check( 'client', 'endpoint_a', 5 );
		$this->assertFalse( $blocked['allowed'] );

		// Different endpoint is unaffected.
		$fresh = $limiter->check( 'client', 'endpoint_b', 5 );
		$this->assertTrue( $fresh['allowed'] );
	}

	// -----------------------------------------------------------------------
	// add_headers()
	// -----------------------------------------------------------------------

	public function test_add_headers_sets_rate_limit_headers(): void {
		$limiter  = new RateLimiter();
		$rate     = $limiter->check( 'client', 'ep', 10 );
		$response = new \WP_REST_Response( [], 200 );
		$limiter->add_headers( $response, $rate );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'X-RateLimit-Limit', $headers );
		$this->assertArrayHasKey( 'X-RateLimit-Remaining', $headers );
		$this->assertArrayHasKey( 'X-RateLimit-Reset', $headers );
		$this->assertSame( '10', $headers['X-RateLimit-Limit'] );
		$this->assertSame( '9', $headers['X-RateLimit-Remaining'] );
	}

	// -----------------------------------------------------------------------
	// too_many_requests()
	// -----------------------------------------------------------------------

	public function test_too_many_requests_returns_wp_error_429(): void {
		$limiter = new RateLimiter();

		for ( $i = 0; $i < 6; $i++ ) {
			$rate = $limiter->check( 'client', 'ep', 5 );
		}

		$error = $limiter->too_many_requests( $rate );
		$this->assertInstanceOf( \WP_Error::class, $error );
		$this->assertSame( 'rate_limit_exceeded', $error->get_error_code() );
	}

	// -----------------------------------------------------------------------
	// get_client_id()
	// -----------------------------------------------------------------------

	public function test_get_client_id_uses_bearer_token_when_present(): void {
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer secret-token-123' );

		$limiter = new RateLimiter();
		$id      = $limiter->get_client_id( $request );

		$this->assertStringStartsWith( 'bearer_', $id );
	}

	public function test_get_client_id_falls_back_to_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
		$request                = new \WP_REST_Request();

		$limiter = new RateLimiter();
		$id      = $limiter->get_client_id( $request );

		$this->assertStringStartsWith( 'ip_', $id );

		unset( $_SERVER['REMOTE_ADDR'] );
	}

	public function test_different_bearer_tokens_produce_different_ids(): void {
		$limiter = new RateLimiter();

		$req1 = new \WP_REST_Request();
		$req1->set_header( 'Authorization', 'Bearer token-aaa' );

		$req2 = new \WP_REST_Request();
		$req2->set_header( 'Authorization', 'Bearer token-bbb' );

		$this->assertNotSame( $limiter->get_client_id( $req1 ), $limiter->get_client_id( $req2 ) );
	}
}
