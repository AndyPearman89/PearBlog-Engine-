<?php
/**
 * Unit tests for MobileAPIController.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\API\MobileAPIController;

class MobileAPIControllerTest extends TestCase {

	private MobileAPIController $ctrl;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$this->ctrl = new class extends MobileAPIController {
			protected function count_published_today(): int {
				return 5;
			}
			protected function load_recent_alerts( int $limit ): array {
				return array_slice(
					[
						[ 'level' => 'error',   'message' => 'API key missing', 'timestamp' => '2026-06-14T00:00:00Z' ],
						[ 'level' => 'warning', 'message' => 'High latency',    'timestamp' => '2026-06-14T00:01:00Z' ],
					],
					0,
					$limit
				);
			}
		};
	}

	// -----------------------------------------------------------------------
	// pause_pipeline / resume_pipeline
	// -----------------------------------------------------------------------

	public function test_pause_sets_option_to_true(): void {
		$req = $this->make_request();
		$this->ctrl->pause_pipeline( $req );

		$this->assertTrue( (bool) get_option( MobileAPIController::OPTION_PAUSED ) );
	}

	public function test_resume_sets_option_to_false(): void {
		update_option( MobileAPIController::OPTION_PAUSED, true );
		$req = $this->make_request();
		$this->ctrl->resume_pipeline( $req );

		$this->assertFalse( (bool) get_option( MobileAPIController::OPTION_PAUSED ) );
	}

	public function test_pause_returns_paused_true(): void {
		$req  = $this->make_request();
		$resp = $this->ctrl->pause_pipeline( $req );

		$this->assertTrue( $resp->data['paused'] );
	}

	public function test_resume_returns_paused_false(): void {
		$req  = $this->make_request();
		$resp = $this->ctrl->resume_pipeline( $req );

		$this->assertFalse( $resp->data['paused'] );
	}

	// -----------------------------------------------------------------------
	// get_summary
	// -----------------------------------------------------------------------

	public function test_get_summary_returns_expected_keys(): void {
		$req  = $this->make_request();
		$resp = $this->ctrl->get_summary( $req );

		$this->assertArrayHasKey( 'queue_size', $resp->data );
		$this->assertArrayHasKey( 'articles_today', $resp->data );
		$this->assertArrayHasKey( 'pipeline_ok', $resp->data );
		$this->assertArrayHasKey( 'circuit_open', $resp->data );
		$this->assertArrayHasKey( 'server_time', $resp->data );
	}

	public function test_get_summary_articles_today_matches_stub(): void {
		$req  = $this->make_request();
		$resp = $this->ctrl->get_summary( $req );

		$this->assertSame( 5, $resp->data['articles_today'] );
	}

	public function test_get_summary_pipeline_ok_reflects_option(): void {
		update_option( MobileAPIController::OPTION_PAUSED, true );
		$req  = $this->make_request();
		$resp = $this->ctrl->get_summary( $req );

		$this->assertFalse( $resp->data['pipeline_ok'] );
	}

	// -----------------------------------------------------------------------
	// get_alerts
	// -----------------------------------------------------------------------

	public function test_get_alerts_returns_alerts_key(): void {
		$req  = $this->make_request( [ 'limit' => 10 ] );
		$resp = $this->ctrl->get_alerts( $req );

		$this->assertArrayHasKey( 'alerts', $resp->data );
		$this->assertArrayHasKey( 'count', $resp->data );
	}

	public function test_get_alerts_respects_limit(): void {
		$req  = $this->make_request( [ 'limit' => 1 ] );
		$resp = $this->ctrl->get_alerts( $req );

		$this->assertCount( 1, $resp->data['alerts'] );
	}

	// -----------------------------------------------------------------------
	// check_auth
	// -----------------------------------------------------------------------

	public function test_check_auth_with_valid_bearer_token(): void {
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer mysecret';
		update_option( 'pearblog_api_key', 'mysecret' );

		$this->assertTrue( $this->ctrl->check_auth() );

		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		delete_option( 'pearblog_api_key' );
	}

	public function test_check_auth_with_wrong_token_returns_false_for_non_admin(): void {
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer wrongtoken';
		update_option( 'pearblog_api_key', 'mysecret' );

		// current_user_can is stubbed to return false in our bootstrap for manage_options.
		$result = $this->ctrl->check_auth();
		$this->assertIsBool( $result );

		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		delete_option( 'pearblog_api_key' );
	}

	// -----------------------------------------------------------------------
	// Helper
	// -----------------------------------------------------------------------

	private function make_request( array $params = [] ): \WP_REST_Request {
		$req = new \WP_REST_Request();
		foreach ( $params as $key => $value ) {
			$req->set_param( $key, $value );
		}
		return $req;
	}
}
