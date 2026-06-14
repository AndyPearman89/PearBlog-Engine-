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

	private PaywallEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_is_singular']      = false;
		$GLOBALS['_current_post_id']  = 0;
		$GLOBALS['_is_user_logged_in'] = false;
		$GLOBALS['_current_user_can']  = false;
		// Clear any cookie state.
		unset( $_COOKIE );
		$this->engine = new PaywallEngine();
	}

	// -----------------------------------------------------------------------
	// Option constants
	// -----------------------------------------------------------------------

	public function test_option_constants_are_defined(): void {
		$this->assertSame( 'pearblog_paywall_enabled',    PaywallEngine::OPTION_ENABLED );
		$this->assertSame( 'pearblog_paywall_free_limit', PaywallEngine::OPTION_FREE_LIMIT );
		$this->assertSame( 'pearblog_paywall_stripe_key', PaywallEngine::OPTION_STRIPE_PUB );
		$this->assertSame( 'pearblog_paywall_stripe_secret', PaywallEngine::OPTION_STRIPE_SEC );
		$this->assertSame( 'pearblog_paywall_price_id',   PaywallEngine::OPTION_PRICE_ID );
		$this->assertSame( 'pearblog_paywall_success_url', PaywallEngine::OPTION_SUCCESS_URL );
	}

	public function test_meta_premium_constant(): void {
		$this->assertSame( 'pearblog_paywall_premium', PaywallEngine::META_PREMIUM );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_false_by_default(): void {
		$this->assertFalse( $this->engine->is_enabled() );
	}

	public function test_is_enabled_true_when_option_set(): void {
		update_option( PaywallEngine::OPTION_ENABLED, true );
		$this->assertTrue( $this->engine->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// get_reads_count / has_exceeded_free_limit
	// -----------------------------------------------------------------------

	public function test_get_reads_count_returns_zero_when_no_cookie(): void {
		$this->assertSame( 0, $this->engine->get_reads_count() );
	}

	public function test_get_reads_count_reads_cookie_value(): void {
		$_COOKIE['pb_free_reads'] = wp_json_encode( [
			'count' => 2,
			'month' => date( 'Y-m' ),
		] );

		$this->assertSame( 2, $this->engine->get_reads_count() );
	}

	public function test_get_reads_count_resets_for_new_month(): void {
		$_COOKIE['pb_free_reads'] = wp_json_encode( [
			'count' => 5,
			'month' => '1990-01',   // Old month.
		] );

		$this->assertSame( 0, $this->engine->get_reads_count() );
	}

	public function test_get_reads_count_returns_zero_for_invalid_cookie(): void {
		$_COOKIE['pb_free_reads'] = 'not_json';

		$this->assertSame( 0, $this->engine->get_reads_count() );
	}

	public function test_has_exceeded_free_limit_false_when_below_limit(): void {
		$_COOKIE['pb_free_reads'] = wp_json_encode( [
			'count' => 1,
			'month' => date( 'Y-m' ),
		] );

		// Default limit is 3.
		$this->assertFalse( $this->engine->has_exceeded_free_limit() );
	}

	public function test_has_exceeded_free_limit_true_when_at_limit(): void {
		$_COOKIE['pb_free_reads'] = wp_json_encode( [
			'count' => 3,
			'month' => date( 'Y-m' ),
		] );

		// Default limit is 3; count >= limit → exceeded.
		$this->assertTrue( $this->engine->has_exceeded_free_limit() );
	}

	public function test_has_exceeded_free_limit_respects_custom_limit(): void {
		update_option( PaywallEngine::OPTION_FREE_LIMIT, 10 );
		$_COOKIE['pb_free_reads'] = wp_json_encode( [
			'count' => 5,
			'month' => date( 'Y-m' ),
		] );

		$this->assertFalse( $this->engine->has_exceeded_free_limit() );
	}

	// -----------------------------------------------------------------------
	// has_access
	// -----------------------------------------------------------------------

	public function test_has_access_false_when_anonymous_no_cookie(): void {
		$this->assertFalse( $this->engine->has_access() );
	}

	public function test_has_access_true_when_manage_options(): void {
		$GLOBALS['_current_user_can'] = true;   // simulate admin

		$this->assertTrue( $this->engine->has_access() );
	}

	public function test_has_access_true_with_valid_subscriber_cookie(): void {
		$token = 'abc123';
		$hash  = hash( 'sha256', $token );

		update_option( 'pb_subscriber_' . $hash, true );
		$_COOKIE['pb_subscriber_token'] = $token;

		$this->assertTrue( $this->engine->has_access() );
	}

	public function test_has_access_false_with_unknown_subscriber_token(): void {
		$_COOKIE['pb_subscriber_token'] = 'invalid_token_xyz';

		$this->assertFalse( $this->engine->has_access() );
	}

	// -----------------------------------------------------------------------
	// maybe_gate_content
	// -----------------------------------------------------------------------

	public function test_maybe_gate_content_passthrough_when_disabled(): void {
		$content = '<p>Full article content.</p>';
		$result  = $this->engine->maybe_gate_content( $content );

		$this->assertSame( $content, $result );
	}

	public function test_maybe_gate_content_passthrough_when_not_singular(): void {
		update_option( PaywallEngine::OPTION_ENABLED, true );
		$GLOBALS['_is_singular'] = false;

		$content = '<p>Full article content.</p>';
		$result  = $this->engine->maybe_gate_content( $content );

		$this->assertSame( $content, $result );
	}

	public function test_maybe_gate_content_passthrough_for_admin(): void {
		update_option( PaywallEngine::OPTION_ENABLED, true );
		$GLOBALS['_is_singular']     = true;
		$GLOBALS['_current_post_id'] = 1;
		$GLOBALS['_current_user_can'] = true;  // admin

		$content = '<p>Premium article content.</p>';
		$result  = $this->engine->maybe_gate_content( $content );

		$this->assertSame( $content, $result );
	}

	public function test_maybe_gate_content_shows_gate_when_limit_exceeded(): void {
		update_option( PaywallEngine::OPTION_ENABLED, true );
		$GLOBALS['_is_singular']      = true;
		$GLOBALS['_current_post_id']  = 2;
		$GLOBALS['_current_user_can'] = false;  // not admin

		$_COOKIE['pb_free_reads'] = wp_json_encode( [
			'count' => 3,  // at default limit of 3
			'month' => date( 'Y-m' ),
		] );

		$content = '<p>Content.</p><p>More.</p><p>Even more.</p>';
		$result  = $this->engine->maybe_gate_content( $content );

		// Should contain the paywall gate div.
		$this->assertStringContainsString( 'pb-paywall-gate', $result );
	}

	public function test_maybe_gate_content_shows_gate_for_premium_post(): void {
		update_option( PaywallEngine::OPTION_ENABLED, true );
		$GLOBALS['_is_singular']      = true;
		$GLOBALS['_current_post_id']  = 3;
		$GLOBALS['_current_user_can'] = false;

		// Mark post as premium.
		update_post_meta( 3, PaywallEngine::META_PREMIUM, true );

		$content = '<p>Premium content here.</p>';
		$result  = $this->engine->maybe_gate_content( $content );

		$this->assertStringContainsString( 'pb-paywall-gate', $result );
	}

	// -----------------------------------------------------------------------
	// create_checkout_session — error path
	// -----------------------------------------------------------------------

	public function test_create_checkout_session_throws_when_not_configured(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Stripe not configured.' );

		$this->engine->create_checkout_session();
	}

	// -----------------------------------------------------------------------
	// rest_get_status
	// -----------------------------------------------------------------------

	public function test_rest_get_status_returns_required_keys(): void {
		$req      = new \WP_REST_Request( 'GET', '/pearblog/v1/paywall/status' );
		$response = $this->engine->rest_get_status( $req );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'has_access',        $data );
		$this->assertArrayHasKey( 'reads_this_month',  $data );
		$this->assertArrayHasKey( 'free_limit',        $data );
		$this->assertArrayHasKey( 'remaining',         $data );
		$this->assertArrayHasKey( 'exceeded',          $data );
	}

	public function test_rest_get_status_remaining_calculation(): void {
		update_option( PaywallEngine::OPTION_FREE_LIMIT, 5 );

		$_COOKIE['pb_free_reads'] = wp_json_encode( [
			'count' => 2,
			'month' => date( 'Y-m' ),
		] );

		$req      = new \WP_REST_Request( 'GET', '/pearblog/v1/paywall/status' );
		$response = $this->engine->rest_get_status( $req );
		$data     = $response->get_data();

		$this->assertSame( 5,     $data['free_limit'] );
		$this->assertSame( 2,     $data['reads_this_month'] );
		$this->assertSame( 3,     $data['remaining'] );
		$this->assertFalse( $data['exceeded'] );
	}
}
