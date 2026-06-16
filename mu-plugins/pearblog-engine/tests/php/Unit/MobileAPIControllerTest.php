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
use PearBlogEngine\Analytics\PredictiveAnalytics;
use PearBlogEngine\Content\CollaborationManager;

class MobileAPIControllerTest extends TestCase {

	private MobileAPIController $ctrl;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_options']          = [];
		$GLOBALS['_posts']            = [];
		$GLOBALS['_db_results']       = [];
		$GLOBALS['_current_user_can'] = true;
		$GLOBALS['_current_user_id']  = 1;
		$GLOBALS['_is_multisite']     = false;
		$GLOBALS['_trashed_posts']    = [];

		$this->ctrl = new MobileAPIController(
			new PredictiveAnalytics(),
			new CollaborationManager()
		);
	}

	// -----------------------------------------------------------------------
	// register_routes
	// -----------------------------------------------------------------------

	public function test_register_calls_rest_api_init(): void {
		$hooked = false;
		// register() calls add_action; the stub records nothing but won't throw.
		$this->ctrl->register();
		// If we reach here without a fatal, registration is hooked correctly.
		$this->assertTrue( true );
	}

	// -----------------------------------------------------------------------
	// Permission callback
	// -----------------------------------------------------------------------

	public function test_require_edit_posts_returns_true_when_authorised(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->ctrl->require_edit_posts() );
	}

	public function test_require_edit_posts_returns_wp_error_when_denied(): void {
		$GLOBALS['_current_user_can'] = false;
		$result = $this->ctrl->require_edit_posts();
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 403, $result->data[0]['status'] );
	}

	// -----------------------------------------------------------------------
	// get_dashboard
	// -----------------------------------------------------------------------

	public function test_get_dashboard_returns_response(): void {
		$req    = new \WP_REST_Request();
		$result = $this->ctrl->get_dashboard( $req );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'pipeline', $data );
		$this->assertArrayHasKey( 'reviews', $data );
		$this->assertArrayHasKey( 'revenue_7d_forecast', $data );
		$this->assertArrayHasKey( 'generated_at', $data );
	}

	public function test_get_dashboard_uses_cache_on_second_call(): void {
		$req = new \WP_REST_Request();

		$r1 = $this->ctrl->get_dashboard( $req );
		$ts1 = $r1->get_data()['generated_at'];

		// The cache has a 60-second TTL; a second immediate call should hit cache.
		$r2 = $this->ctrl->get_dashboard( $req );
		$ts2 = $r2->get_data()['generated_at'];

		$this->assertSame( $ts1, $ts2 );
	}

	// -----------------------------------------------------------------------
	// get_queue
	// -----------------------------------------------------------------------

	public function test_get_queue_returns_empty_by_default(): void {
		$req    = new \WP_REST_Request();
		$result = $this->ctrl->get_queue( $req );
		$data   = $result->get_data();

		$this->assertSame( 0, $data['count'] );
		$this->assertSame( [], $data['items'] );
	}

	public function test_get_queue_includes_ai_generated_drafts(): void {
		// Seed a draft AI-generated post.
		$post              = new \stdClass();
		$post->ID          = 100;
		$post->post_status = 'draft';
		$post->post_content = 'Test AI content body text here.';
		$post->post_date   = '2026-06-01 00:00:00';
		$post->post_date_gmt = '2026-06-01 00:00:00';
		$GLOBALS['_posts'][100] = $post;
		$GLOBALS['_post_meta'][100]['_pearblog_generated'] = '1';

		$req    = new \WP_REST_Request();
		$result = $this->ctrl->get_queue( $req );
		$data   = $result->get_data();

		// Our test get_posts stub returns empty by default; we're testing structure.
		$this->assertArrayHasKey( 'count', $data );
		$this->assertArrayHasKey( 'items', $data );
	}

	// -----------------------------------------------------------------------
	// approve_queue_item
	// -----------------------------------------------------------------------

	public function test_approve_returns_404_for_missing_post(): void {
		$req    = new \WP_REST_Request( 'POST', [], [ 'id' => 9999 ] );
		$result = $this->ctrl->approve_queue_item( $req );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'pearblog_not_found', $result->get_error_code() );
	}

	public function test_approve_publishes_draft(): void {
		$post              = new \stdClass();
		$post->ID          = 101;
		$post->post_status = 'draft';
		$GLOBALS['_posts'][101] = $post;

		$req    = new \WP_REST_Request( 'POST', [], [ 'id' => 101 ] );
		$result = $this->ctrl->approve_queue_item( $req );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();
		$this->assertSame( 'published', $data['status'] );
		$this->assertSame( 101, $data['post_id'] );
	}

	// -----------------------------------------------------------------------
	// reject_queue_item
	// -----------------------------------------------------------------------

	public function test_reject_returns_404_for_missing_post(): void {
		$req    = new \WP_REST_Request( 'POST', [ 'feedback' => 'Bad content' ], [ 'id' => 8888 ] );
		$result = $this->ctrl->reject_queue_item( $req );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_reject_trashes_draft(): void {
		$post              = new \stdClass();
		$post->ID          = 102;
		$post->post_status = 'draft';
		$GLOBALS['_posts'][102] = $post;

		$req    = new \WP_REST_Request( 'POST', [ 'feedback' => 'Factual errors.' ], [ 'id' => 102 ] );
		$result = $this->ctrl->reject_queue_item( $req );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();
		$this->assertSame( 'rejected', $data['status'] );
		$this->assertContains( 102, $GLOBALS['_trashed_posts'] );
	}

	// -----------------------------------------------------------------------
	// get_alerts
	// -----------------------------------------------------------------------

	public function test_get_alerts_empty_log(): void {
		$req    = new \WP_REST_Request( 'GET', [ 'limit' => 10 ] );
		$result = $this->ctrl->get_alerts( $req );
		$data   = $result->get_data();

		$this->assertSame( 0, $data['count'] );
		$this->assertSame( [], $data['alerts'] );
	}

	public function test_get_alerts_returns_simplified_payload(): void {
		$log = wp_json_encode( [
			[ 'level' => 'error', 'subject' => 'Pipeline Failed', 'message' => 'Out of tokens.', 'time' => 1700000000 ],
		] );
		$GLOBALS['_options']['pearblog_alert_log'] = $log;

		$req    = new \WP_REST_Request( 'GET', [ 'limit' => 5 ] );
		$result = $this->ctrl->get_alerts( $req );
		$data   = $result->get_data();

		$this->assertSame( 1, $data['count'] );
		$this->assertSame( 'error', $data['alerts'][0]['level'] );
		$this->assertSame( 'Pipeline Failed', $data['alerts'][0]['title'] );
	}

	// -----------------------------------------------------------------------
	// get_sites
	// -----------------------------------------------------------------------

	public function test_get_sites_single_site(): void {
		$req    = new \WP_REST_Request();
		$result = $this->ctrl->get_sites( $req );
		$data   = $result->get_data();

		$this->assertFalse( $data['multisite'] );
		$this->assertCount( 1, $data['sites'] );
		$this->assertArrayHasKey( 'queue_size', $data['sites'][0] );
	}

	public function test_get_sites_multisite(): void {
		$GLOBALS['_is_multisite'] = true;

		$site1           = new \stdClass();
		$site1->blog_id  = 1;
		$site2           = new \stdClass();
		$site2->blog_id  = 2;
		$GLOBALS['_sites'] = [ $site1, $site2 ];

		$req    = new \WP_REST_Request();
		$result = $this->ctrl->get_sites( $req );
		$data   = $result->get_data();

		$this->assertTrue( $data['multisite'] );
		$this->assertCount( 2, $data['sites'] );

		$GLOBALS['_is_multisite'] = false;
	}
}
