<?php
/**
 * Unit tests for MobileAPIController (V9.0 F4).
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\API\MobileAPIController;

class MobileAPIControllerTest extends TestCase {

	private MobileAPIController $controller;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']           = [];
		$GLOBALS['_post_meta']         = [];
		$GLOBALS['_posts']             = [];
		$GLOBALS['_user_logged_in']    = true;
		$GLOBALS['_current_user_can']  = false;
		$this->controller = new MobileAPIController();
	}

	/** @return \WP_REST_Request */
	private function make_request( array $params = [] ): \WP_REST_Request {
		$req = new \WP_REST_Request();
		foreach ( $params as $key => $value ) {
			$req->set_param( $key, $value );
		}
		return $req;
	}

	// -----------------------------------------------------------------------
	// pause / resume
	// -----------------------------------------------------------------------

	public function test_pause_sets_option_true(): void {
		$this->controller->pause_generation( $this->make_request() );
		$this->assertTrue( (bool) get_option( MobileAPIController::OPTION_PAUSED ) );
	}

	public function test_resume_sets_option_false(): void {
		update_option( MobileAPIController::OPTION_PAUSED, true );
		$this->controller->resume_generation( $this->make_request() );
		$this->assertFalse( (bool) get_option( MobileAPIController::OPTION_PAUSED ) );
	}

	public function test_pause_response_contains_paused_true(): void {
		$resp = $this->controller->pause_generation( $this->make_request() );
		$data = $resp->get_data();
		$this->assertTrue( $data['paused'] );
	}

	public function test_resume_response_contains_paused_false(): void {
		$resp = $this->controller->resume_generation( $this->make_request() );
		$data = $resp->get_data();
		$this->assertFalse( $data['paused'] );
	}

	// -----------------------------------------------------------------------
	// get_dashboard
	// -----------------------------------------------------------------------

	public function test_get_dashboard_returns_timestamp(): void {
		$resp = $this->controller->get_dashboard( $this->make_request() );
		$data = $resp->get_data();
		$this->assertArrayHasKey( 'timestamp', $data );
		$this->assertSame( 1, preg_match( '/^\d{4}-\d{2}-\d{2}T/', $data['timestamp'] ) );
	}

	public function test_get_dashboard_includes_paused_status(): void {
		update_option( MobileAPIController::OPTION_PAUSED, true );
		$resp = $this->controller->get_dashboard( $this->make_request() );
		$data = $resp->get_data();
		$this->assertTrue( $data['generation_paused'] );
	}

	public function test_get_dashboard_includes_unread_alerts_count(): void {
		update_option( MobileAPIController::OPTION_ALERTS, [
			[ 'id' => 'a1', 'message' => 'Test alert', 'level' => 'error', 'created_at' => '' ],
		] );
		$resp = $this->controller->get_dashboard( $this->make_request() );
		$data = $resp->get_data();
		$this->assertSame( 1, $data['unread_alerts'] );
	}

	// -----------------------------------------------------------------------
	// alerts
	// -----------------------------------------------------------------------

	public function test_get_alerts_returns_empty_when_no_alerts(): void {
		$resp = $this->controller->get_alerts( $this->make_request() );
		$data = $resp->get_data();
		$this->assertSame( [], $data['alerts'] );
	}

	public function test_get_alerts_returns_stored_alerts(): void {
		update_option( MobileAPIController::OPTION_ALERTS, [
			[ 'id' => 'alert1', 'message' => 'Test', 'level' => 'warning', 'created_at' => '' ],
		] );
		$resp = $this->controller->get_alerts( $this->make_request() );
		$data = $resp->get_data();
		$this->assertCount( 1, $data['alerts'] );
	}

	public function test_ack_alert_removes_it_from_list(): void {
		update_option( MobileAPIController::OPTION_ALERTS, [
			[ 'id' => 'alert1', 'message' => 'Test', 'level' => 'info', 'created_at' => '' ],
			[ 'id' => 'alert2', 'message' => 'Test2', 'level' => 'error', 'created_at' => '' ],
		] );
		$resp = $this->controller->ack_alert( $this->make_request( [ 'id' => 'alert1' ] ) );
		$this->assertSame( 200, $resp->get_status() );

		// alert1 should be gone.
		$remaining = get_option( MobileAPIController::OPTION_ALERTS, [] );
		$this->assertCount( 1, $remaining );
		$this->assertSame( 'alert2', $remaining[0]['id'] );
	}

	public function test_ack_alert_returns_404_for_missing(): void {
		$resp = $this->controller->ack_alert( $this->make_request( [ 'id' => 'nonexistent' ] ) );
		$this->assertInstanceOf( \WP_Error::class, $resp );
	}

	// -----------------------------------------------------------------------
	// Permission callbacks
	// -----------------------------------------------------------------------

	public function test_perm_view_returns_true_when_logged_in(): void {
		$GLOBALS['_user_logged_in'] = true;
		$this->assertTrue( $this->controller->perm_view() );
	}

	public function test_perm_view_returns_false_when_not_logged_in(): void {
		$GLOBALS['_user_logged_in'] = false;
		$this->assertFalse( $this->controller->perm_view() );
	}

	public function test_perm_edit_requires_edit_posts(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->controller->perm_edit() );
	}

	public function test_perm_admin_requires_manage_options(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->controller->perm_admin() );
	}

	public function test_perm_admin_false_without_manage_options(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->controller->perm_admin() );
	}
}

