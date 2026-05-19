<?php
/**
 * Unit tests for AnalyticsController.
 *
 * Tests REST handler methods directly using bootstrap stubs.
 * AnalyticsDashboard is exercised through the controller.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Analytics\AnalyticsController;
use PearBlogEngine\Analytics\AnalyticsDashboard;

class AnalyticsControllerTest extends TestCase {

	private AnalyticsController $ctrl;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];
		$GLOBALS['_post_list'] = [];

		$this->ctrl = new AnalyticsController();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function request( array $params = [], string $auth = '' ): \WP_REST_Request {
		$req = new \WP_REST_Request();
		foreach ( $params as $k => $v ) {
			$req->set_param( $k, $v );
		}
		if ( '' !== $auth ) {
			$req->set_header( 'Authorization', $auth );
		}
		return $req;
	}

	private function seed_post_meta( int $post_id, int $views_30d = 500, int $views_7d = 100, float $quality = 75.0 ): void {
		$GLOBALS['_post_meta'][ $post_id ][ AnalyticsDashboard::META_VIEWS_30D ] = [ $views_30d ];
		$GLOBALS['_post_meta'][ $post_id ][ AnalyticsDashboard::META_VIEWS_7D  ] = [ $views_7d  ];
		$GLOBALS['_post_meta'][ $post_id ][ '_pearblog_quality_score'           ] = [ $quality   ];
	}

	// -----------------------------------------------------------------------
	// get_summary
	// -----------------------------------------------------------------------

	public function test_get_summary_returns_200(): void {
		$resp = $this->ctrl->get_summary( $this->request() );
		$this->assertSame( 200, $resp->status );
	}

	public function test_get_summary_includes_expected_keys(): void {
		$resp = $this->ctrl->get_summary( $this->request() );
		$data = $resp->data;

		$this->assertArrayHasKey( 'published_posts', $data );
		$this->assertArrayHasKey( 'last_refresh', $data );
	}

	public function test_get_summary_reports_last_sync(): void {
		update_option( 'pearblog_analytics_last_sync', '2026-01-01 12:00:00' );
		$resp = $this->ctrl->get_summary( $this->request() );
		$this->assertSame( '2026-01-01 12:00:00', $resp->data['last_refresh'] );
	}

	// -----------------------------------------------------------------------
	// get_top_posts
	// -----------------------------------------------------------------------

	public function test_get_top_posts_returns_200(): void {
		$resp = $this->ctrl->get_top_posts( $this->request( [ 'limit' => 5 ] ) );
		$this->assertSame( 200, $resp->status );
	}

	public function test_get_top_posts_includes_count_and_posts(): void {
		$resp = $this->ctrl->get_top_posts( $this->request( [ 'limit' => 10 ] ) );
		$this->assertArrayHasKey( 'count', $resp->data );
		$this->assertArrayHasKey( 'posts', $resp->data );
		$this->assertIsArray( $resp->data['posts'] );
	}

	public function test_get_top_posts_uses_limit_parameter(): void {
		// With no posts, count should be 0 regardless of limit.
		$resp = $this->ctrl->get_top_posts( $this->request( [ 'limit' => 3 ] ) );
		$this->assertSame( 200, $resp->status );
		$this->assertLessThanOrEqual( 3, $resp->data['count'] );
	}

	// -----------------------------------------------------------------------
	// sync_all
	// -----------------------------------------------------------------------

	public function test_sync_all_returns_200(): void {
		$resp = $this->ctrl->sync_all( $this->request() );
		$this->assertSame( 200, $resp->status );
	}

	public function test_sync_all_includes_synced_count(): void {
		$resp = $this->ctrl->sync_all( $this->request() );
		$this->assertArrayHasKey( 'synced', $resp->data );
		$this->assertArrayHasKey( 'synced_at', $resp->data );
	}

	public function test_sync_all_returns_zero_when_ga4_not_configured(): void {
		// GA4 not configured → sync returns 0.
		$resp = $this->ctrl->sync_all( $this->request() );
		$this->assertSame( 0, $resp->data['synced'] );
	}

	// -----------------------------------------------------------------------
	// sync_post
	// -----------------------------------------------------------------------

	public function test_sync_post_returns_404_for_nonexistent_post(): void {
		$resp = $this->ctrl->sync_post( $this->request( [ 'post_id' => 99999 ] ) );
		$this->assertSame( 404, $resp->status );
	}

	public function test_sync_post_returns_200_for_existing_post(): void {
		// Seed a post in the global stub.
		$post = new \WP_Post( [ 'ID' => 42, 'post_title' => 'Test Post', 'post_status' => 'publish' ] );
		$GLOBALS['_posts'][42] = $post;

		$this->seed_post_meta( 42, 300, 80 );

		$resp = $this->ctrl->sync_post( $this->request( [ 'post_id' => 42 ] ) );
		$this->assertSame( 200, $resp->status );
		$this->assertSame( 42, $resp->data['post_id'] );
	}

	// -----------------------------------------------------------------------
	// get_predictive
	// -----------------------------------------------------------------------

	public function test_get_predictive_returns_200(): void {
		$resp = $this->ctrl->get_predictive( $this->request() );
		$this->assertSame( 200, $resp->status );
	}

	public function test_get_predictive_includes_three_buckets(): void {
		$resp = $this->ctrl->get_predictive( $this->request() );
		$data = $resp->data;

		$this->assertArrayHasKey( 'trending', $data );
		$this->assertArrayHasKey( 'at_risk', $data );
		$this->assertArrayHasKey( 'refresh_needed', $data );
		$this->assertArrayHasKey( 'generated_at', $data );
	}

	public function test_get_predictive_trending_bucket_is_array(): void {
		$resp = $this->ctrl->get_predictive( $this->request() );
		$this->assertIsArray( $resp->data['trending'] );
	}

	// -----------------------------------------------------------------------
	// export_data
	// -----------------------------------------------------------------------

	public function test_export_data_returns_200(): void {
		$resp = $this->ctrl->export_data( $this->request( [ 'limit' => 10 ] ) );
		$this->assertSame( 200, $resp->status );
	}

	public function test_export_data_includes_expected_keys(): void {
		$resp = $this->ctrl->export_data( $this->request( [ 'limit' => 10 ] ) );
		$data = $resp->data;

		$this->assertArrayHasKey( 'count', $data );
		$this->assertArrayHasKey( 'exported_at', $data );
		$this->assertArrayHasKey( 'posts', $data );
		$this->assertIsArray( $data['posts'] );
	}

	// -----------------------------------------------------------------------
	// check_permission
	// -----------------------------------------------------------------------

	public function test_check_permission_returns_true_with_correct_bearer_token(): void {
		update_option( 'pearblog_api_key', 'analytics-secret' );
		$req = $this->request( [], 'Bearer analytics-secret' );
		$this->assertTrue( $this->ctrl->check_permission( $req ) );
	}

	public function test_check_permission_returns_false_with_wrong_token(): void {
		update_option( 'pearblog_api_key', 'analytics-secret' );
		$req = $this->request( [], 'Bearer wrong-token' );
		$this->assertFalse( $this->ctrl->check_permission( $req ) );
	}

	public function test_check_permission_returns_false_with_no_key_configured(): void {
		$req = $this->request();
		$this->assertFalse( $this->ctrl->check_permission( $req ) );
	}

	public function test_check_permission_accepts_lowercase_bearer(): void {
		update_option( 'pearblog_api_key', 'analytics-secret' );
		$req = $this->request( [], 'bearer analytics-secret' );
		$this->assertTrue( $this->ctrl->check_permission( $req ) );
	}
}
