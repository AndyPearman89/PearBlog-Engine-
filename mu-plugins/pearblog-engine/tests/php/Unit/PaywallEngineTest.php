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
		$GLOBALS['_options']     = [];
		$GLOBALS['_post_meta']   = [];
		$GLOBALS['_current_user_can'] = false;
		$GLOBALS['_user_logged_in'] = false;
		$GLOBALS['_is_singular'] = false;
		unset( $_COOKIE );
		$this->engine = new PaywallEngine();
	}

	// -----------------------------------------------------------------------
	// Option constants
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

	public function test_is_enabled_returns_false_by_default(): void {
		$this->assertFalse( $this->engine->is_enabled() );
	}

	public function test_is_enabled_returns_true_when_option_set(): void {
		$GLOBALS['_options'][ PaywallEngine::OPTION_ENABLED ] = true;
		$this->assertTrue( $this->engine->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// has_access
	// -----------------------------------------------------------------------

	public function test_has_access_returns_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->engine->has_access() );
	}

	public function test_has_access_returns_false_for_guest(): void {
		$GLOBALS['_current_user_can'] = false;
		$GLOBALS['_user_logged_in'] = false;
		$this->assertFalse( $this->engine->has_access() );
	}

	// -----------------------------------------------------------------------
	// get_reads_count / has_exceeded_free_limit
	// -----------------------------------------------------------------------

	public function test_get_reads_count_returns_zero_by_default(): void {
		$this->assertSame( 0, $this->engine->get_reads_count() );
	}

	public function test_has_exceeded_free_limit_false_when_zero_reads(): void {
		$this->assertFalse( $this->engine->has_exceeded_free_limit() );
	}

	public function test_has_exceeded_free_limit_uses_default_limit_of_3(): void {
		$_COOKIE['pb_free_reads'] = json_encode( [ 'count' => 3, 'month' => date( 'Y-m' ) ] );
		$this->assertTrue( $this->engine->has_exceeded_free_limit() );
	}

	public function test_has_exceeded_free_limit_uses_configured_limit(): void {
		$GLOBALS['_options'][ PaywallEngine::OPTION_FREE_LIMIT ] = 5;
		$_COOKIE['pb_free_reads'] = json_encode( [ 'count' => 4, 'month' => date( 'Y-m' ) ] );
		$this->assertFalse( $this->engine->has_exceeded_free_limit() );
	}

	public function test_get_reads_count_from_valid_cookie(): void {
		$_COOKIE['pb_free_reads'] = json_encode( [ 'count' => 2, 'month' => date( 'Y-m' ) ] );
		$this->assertSame( 2, $this->engine->get_reads_count() );
	}

	public function test_get_reads_count_resets_for_new_month(): void {
		$_COOKIE['pb_free_reads'] = json_encode( [ 'count' => 5, 'month' => '2000-01' ] );
		$this->assertSame( 0, $this->engine->get_reads_count() );
	}

	// -----------------------------------------------------------------------
	// maybe_gate_content
	// -----------------------------------------------------------------------

	public function test_maybe_gate_content_returns_unchanged_when_disabled(): void {
		$content = '<p>Full article content.</p>';
		$result  = $this->engine->maybe_gate_content( $content );
		$this->assertSame( $content, $result );
	}

	public function test_maybe_gate_content_returns_unchanged_when_not_singular(): void {
		$GLOBALS['_options'][ PaywallEngine::OPTION_ENABLED ] = true;
		$GLOBALS['_is_singular'] = false;
		$content = '<p>Full article content.</p>';
		$result  = $this->engine->maybe_gate_content( $content );
		$this->assertSame( $content, $result );
	}

	public function test_maybe_gate_content_passes_through_when_has_access(): void {
		$GLOBALS['_options'][ PaywallEngine::OPTION_ENABLED ] = true;
		$GLOBALS['_is_singular'] = true;
		$GLOBALS['_current_user_can'] = true;
		$content = '<p>Full article content.</p>';
		$result  = $this->engine->maybe_gate_content( $content );
		$this->assertSame( $content, $result );
	}

	// -----------------------------------------------------------------------
	// create_checkout_session
	// -----------------------------------------------------------------------

	public function test_create_checkout_throws_when_not_configured(): void {
		$this->expectException( \RuntimeException::class );
		$this->engine->create_checkout_session();
	}

	// -----------------------------------------------------------------------
	// REST
	// -----------------------------------------------------------------------

	public function test_rest_get_status_returns_200(): void {
		$req    = new \WP_REST_Request();
		$result = $this->engine->rest_get_status( $req );
		$this->assertSame( 200, $result->get_status() );
	}

	public function test_rest_get_status_contains_has_access(): void {
		$req    = new \WP_REST_Request();
		$result = $this->engine->rest_get_status( $req );
		$this->assertArrayHasKey( 'has_access', $result->get_data() );
	}

	public function test_rest_get_status_contains_remaining(): void {
		$req    = new \WP_REST_Request();
		$result = $this->engine->rest_get_status( $req );
		$data   = $result->get_data();
		$this->assertArrayHasKey( 'remaining', $data );
		$this->assertSame( 3, $data['remaining'] );
	}

	public function test_rest_get_status_contains_free_limit(): void {
		$req    = new \WP_REST_Request();
		$result = $this->engine->rest_get_status( $req );
		$data   = $result->get_data();
		$this->assertArrayHasKey( 'free_limit', $data );
		$this->assertSame( 3, $data['free_limit'] );
	}

	public function test_rest_get_status_exceeded_true_when_limit_reached(): void {
		$_COOKIE['pb_free_reads'] = json_encode( [ 'count' => 3, 'month' => date( 'Y-m' ) ] );
		$req    = new \WP_REST_Request();
		$result = $this->engine->rest_get_status( $req );
		$data   = $result->get_data();
		$this->assertTrue( $data['exceeded'] );
	}

	public function test_rest_create_checkout_returns_error_when_not_configured(): void {
		$req = new \WP_REST_Request();
		$result = $this->engine->rest_create_checkout( $req );
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_content_filter(): void {
		$this->engine->register();
		$this->assertTrue( isset( $GLOBALS['_filters']['the_content'] ) );
	}

	public function test_register_adds_rest_api_init_action(): void {
		$this->engine->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}
}
