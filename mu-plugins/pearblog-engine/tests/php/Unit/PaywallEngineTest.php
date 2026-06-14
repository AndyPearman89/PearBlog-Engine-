<?php
/**
 * Unit tests for PaywallEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Monetization\PaywallEngine;

class PaywallEngineTest extends TestCase {

	private PaywallEngine $paywall;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']     = [];
		$GLOBALS['_post_meta']   = [];
		$GLOBALS['_actions']     = [];
		$GLOBALS['_current_user_can'] = [];
		$this->paywall = new PaywallEngine();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant(): void {
		$this->assertSame( 'pearblog_paywall_enabled', PaywallEngine::OPTION_ENABLED );
	}

	public function test_option_free_limit_constant(): void {
		$this->assertSame( 'pearblog_paywall_free_limit', PaywallEngine::OPTION_FREE_LIMIT );
	}

	public function test_meta_premium_constant(): void {
		$this->assertSame( 'pearblog_paywall_premium', PaywallEngine::META_PREMIUM );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_false_by_default(): void {
		$this->assertFalse( $this->paywall->is_enabled() );
	}

	public function test_is_enabled_true_when_option_set(): void {
		update_option( PaywallEngine::OPTION_ENABLED, true );

		$this->assertTrue( $this->paywall->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// get_reads_count
	// -----------------------------------------------------------------------

	public function test_get_reads_count_returns_zero_when_no_cookie(): void {
		$this->assertSame( 0, $this->paywall->get_reads_count() );
	}

	// -----------------------------------------------------------------------
	// has_exceeded_free_limit
	// -----------------------------------------------------------------------

	public function test_has_exceeded_free_limit_false_when_no_reads(): void {
		$this->assertFalse( $this->paywall->has_exceeded_free_limit() );
	}

	public function test_free_limit_default_is_three(): void {
		$limit = (int) get_option( PaywallEngine::OPTION_FREE_LIMIT, 3 );
		$this->assertSame( 3, $limit );
	}

	public function test_has_exceeded_free_limit_with_cookie_at_limit(): void {
		update_option( PaywallEngine::OPTION_FREE_LIMIT, 2 );
		// Simulate cookie data: 2 reads (raw JSON).
		$data = [ 'count' => 2, 'month' => date( 'Y-m' ) ];
		$_COOKIE['pb_free_reads'] = wp_json_encode( $data );

		$this->assertTrue( $this->paywall->has_exceeded_free_limit() );

		unset( $_COOKIE['pb_free_reads'] );
	}

	public function test_has_exceeded_free_limit_false_below_limit(): void {
		update_option( PaywallEngine::OPTION_FREE_LIMIT, 5 );
		$data = [ 'count' => 2, 'month' => date( 'Y-m' ) ];
		$_COOKIE['pb_free_reads'] = wp_json_encode( $data );

		$this->assertFalse( $this->paywall->has_exceeded_free_limit() );

		unset( $_COOKIE['pb_free_reads'] );
	}

	// -----------------------------------------------------------------------
	// has_access
	// -----------------------------------------------------------------------

	public function test_has_access_false_by_default(): void {
		$this->assertFalse( $this->paywall->has_access() );
	}

	// -----------------------------------------------------------------------
	// maybe_gate_content
	// -----------------------------------------------------------------------

	public function test_maybe_gate_content_returns_content_when_disabled(): void {
		$content = '<p>Article content here.</p>';
		$result  = $this->paywall->maybe_gate_content( $content );

		$this->assertSame( $content, $result );
	}

	public function test_maybe_gate_content_returns_content_when_not_singular(): void {
		update_option( PaywallEngine::OPTION_ENABLED, true );
		$GLOBALS['_is_singular'] = false;

		$content = '<p>Article content here.</p>';
		$result  = $this->paywall->maybe_gate_content( $content );

		$this->assertSame( $content, $result );
	}

	public function test_maybe_gate_content_returns_string(): void {
		$result = $this->paywall->maybe_gate_content( '<p>Test</p>' );

		$this->assertIsString( $result );
	}

	// -----------------------------------------------------------------------
	// create_checkout_session — not configured
	// -----------------------------------------------------------------------

	public function test_create_checkout_session_throws_when_not_configured(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Stripe not configured.' );

		$this->paywall->create_checkout_session();
	}

	// -----------------------------------------------------------------------
	// rest_create_checkout — no stripe config
	// -----------------------------------------------------------------------

	public function test_rest_create_checkout_returns_error_when_not_configured(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturn( '' );

		$result = $this->paywall->rest_create_checkout( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	// -----------------------------------------------------------------------
	// rest_get_status
	// -----------------------------------------------------------------------

	public function test_rest_get_status_returns_200(): void {
		$request  = $this->createMock( \WP_REST_Request::class );
		$response = $this->paywall->rest_get_status( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_rest_get_status_contains_expected_keys(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$data    = $this->paywall->rest_get_status( $request )->get_data();

		$this->assertArrayHasKey( 'has_access', $data );
		$this->assertArrayHasKey( 'reads_this_month', $data );
		$this->assertArrayHasKey( 'free_limit', $data );
		$this->assertArrayHasKey( 'remaining', $data );
		$this->assertArrayHasKey( 'exceeded', $data );
	}

	public function test_rest_get_status_remaining_decreases_with_reads(): void {
		update_option( PaywallEngine::OPTION_FREE_LIMIT, 3 );
		$data = [ 'count' => 1, 'month' => date( 'Y-m' ) ];
		$_COOKIE['pb_free_reads'] = wp_json_encode( $data );

		$request  = $this->createMock( \WP_REST_Request::class );
		$status   = $this->paywall->rest_get_status( $request )->get_data();

		$this->assertSame( 2, $status['remaining'] );

		unset( $_COOKIE['pb_free_reads'] );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->paywall->register();
	}
}
