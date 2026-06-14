<?php
/**
 * Unit tests for RevenueTracker.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Monetization\RevenueTracker;

class RevenueTrackerTest extends TestCase {

	private RevenueTracker $tracker;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];
		$GLOBALS['_post_list'] = [];
		$GLOBALS['_current_user_can'] = true;
		$this->tracker = new RevenueTracker();
	}

	// -----------------------------------------------------------------------
	// Source constants
	// -----------------------------------------------------------------------

	public function test_source_constants_are_defined(): void {
		$this->assertSame( 'adsense',   RevenueTracker::SOURCE_ADSENSE );
		$this->assertSame( 'affiliate', RevenueTracker::SOURCE_AFFILIATE );
		$this->assertSame( 'paywall',   RevenueTracker::SOURCE_PAYWALL );
		$this->assertSame( 'lead',      RevenueTracker::SOURCE_LEAD );
	}

	// -----------------------------------------------------------------------
	// get_article_data — defaults
	// -----------------------------------------------------------------------

	public function test_get_article_data_returns_default_structure(): void {
		$data = $this->tracker->get_article_data( 999 );

		$this->assertSame( 999,  $data['post_id'] );
		$this->assertSame( 0.0, $data['total_cents'] );
		$this->assertIsArray( $data['totals'] );
		$this->assertIsArray( $data['events'] );
		$this->assertSame( 0.0, $data['ai_cost_cents'] );
		$this->assertSame( 0,   $data['last_updated'] );
	}

	// -----------------------------------------------------------------------
	// track
	// -----------------------------------------------------------------------

	public function test_track_adds_event_to_article_data(): void {
		$this->tracker->track( 1, 100.0, RevenueTracker::SOURCE_ADSENSE );

		$data = $this->tracker->get_article_data( 1 );

		$this->assertCount( 1, $data['events'] );
		$this->assertSame( RevenueTracker::SOURCE_ADSENSE, $data['events'][0]['source'] );
		$this->assertSame( 100.0, $data['events'][0]['amount_cents'] );
	}

	public function test_track_accumulates_total_cents(): void {
		$this->tracker->track( 2, 50.0, RevenueTracker::SOURCE_ADSENSE );
		$this->tracker->track( 2, 30.0, RevenueTracker::SOURCE_AFFILIATE );
		$this->tracker->track( 2, 20.0, RevenueTracker::SOURCE_ADSENSE );

		$data = $this->tracker->get_article_data( 2 );

		$this->assertSame( 100.0, $data['total_cents'] );
	}

	public function test_track_accumulates_per_source_totals(): void {
		$this->tracker->track( 3, 60.0, RevenueTracker::SOURCE_ADSENSE );
		$this->tracker->track( 3, 40.0, RevenueTracker::SOURCE_AFFILIATE );
		$this->tracker->track( 3, 20.0, RevenueTracker::SOURCE_ADSENSE );

		$data = $this->tracker->get_article_data( 3 );

		$this->assertSame( 80.0, $data['totals'][ RevenueTracker::SOURCE_ADSENSE ] );
		$this->assertSame( 40.0, $data['totals'][ RevenueTracker::SOURCE_AFFILIATE ] );
	}

	public function test_track_updates_site_cumulative_total(): void {
		$this->tracker->track( 4, 200.0, RevenueTracker::SOURCE_PAYWALL );
		$this->tracker->track( 5, 100.0, RevenueTracker::SOURCE_LEAD );

		$total = (float) get_option( 'pearblog_revenue_total', 0 );
		$this->assertSame( 300.0, $total );
	}

	public function test_track_accepts_meta_array(): void {
		$meta = [ 'campaign' => 'spring', 'placement' => 'top' ];
		$this->tracker->track( 6, 50.0, RevenueTracker::SOURCE_ADSENSE, $meta );

		$data = $this->tracker->get_article_data( 6 );

		$this->assertSame( $meta, $data['events'][0]['meta'] );
	}

	// -----------------------------------------------------------------------
	// calculate_roi
	// -----------------------------------------------------------------------

	public function test_calculate_roi_returns_correct_structure(): void {
		$roi = $this->tracker->calculate_roi( 10 );

		$this->assertArrayHasKey( 'revenue_cents', $roi );
		$this->assertArrayHasKey( 'cost_cents', $roi );
		$this->assertArrayHasKey( 'roi_cents', $roi );
		$this->assertArrayHasKey( 'roi_pct', $roi );
	}

	public function test_calculate_roi_with_no_cost(): void {
		$this->tracker->track( 11, 500.0, RevenueTracker::SOURCE_ADSENSE );

		$roi = $this->tracker->calculate_roi( 11 );

		$this->assertSame( 500.0, $roi['revenue_cents'] );
		$this->assertSame( 0.0,   $roi['cost_cents'] );
		$this->assertSame( 500.0, $roi['roi_cents'] );
		$this->assertSame( 0.0,   $roi['roi_pct'] );  // no cost → 0% ROI
	}

	public function test_calculate_roi_with_cost_and_revenue(): void {
		// Set AI cost via post meta.
		update_post_meta( 12, 'pearblog_generation_cost_cents', 100.0 );
		$this->tracker->track( 12, 300.0, RevenueTracker::SOURCE_ADSENSE );

		$roi = $this->tracker->calculate_roi( 12 );

		$this->assertSame( 300.0,  $roi['revenue_cents'] );
		$this->assertSame( 100.0,  $roi['cost_cents'] );
		$this->assertSame( 200.0,  $roi['roi_cents'] );
		$this->assertSame( 200.0,  $roi['roi_pct'] );  // (300-100)/100*100 = 200%
	}

	public function test_calculate_roi_rounds_pct_to_two_decimals(): void {
		update_post_meta( 13, 'pearblog_generation_cost_cents', 300.0 );
		$this->tracker->track( 13, 100.0, RevenueTracker::SOURCE_ADSENSE );

		$roi = $this->tracker->calculate_roi( 13 );

		// ROI = (100 - 300) / 300 * 100 = -66.67%
		$this->assertSame( -66.67, $roi['roi_pct'] );
	}

	// -----------------------------------------------------------------------
	// init_article_revenue
	// -----------------------------------------------------------------------

	public function test_init_article_revenue_creates_entry(): void {
		$this->tracker->init_article_revenue( 20 );

		$data = $this->tracker->get_article_data( 20 );

		$this->assertSame( 20,  $data['post_id'] );
		$this->assertSame( 0.0, $data['total_cents'] );
	}

	public function test_init_article_revenue_does_not_overwrite_existing(): void {
		$this->tracker->track( 21, 500.0, RevenueTracker::SOURCE_ADSENSE );

		// Calling init again should not reset the data.
		$this->tracker->init_article_revenue( 21 );

		$data = $this->tracker->get_article_data( 21 );

		$this->assertSame( 500.0, $data['total_cents'] );
	}

	// -----------------------------------------------------------------------
	// get_site_summary
	// -----------------------------------------------------------------------

	public function test_get_site_summary_returns_required_keys(): void {
		$summary = $this->tracker->get_site_summary();

		$this->assertArrayHasKey( 'total_cents',  $summary );
		$this->assertArrayHasKey( 'total_usd',    $summary );
		$this->assertArrayHasKey( 'top_articles', $summary );
		$this->assertArrayHasKey( 'generated_at', $summary );
	}

	public function test_get_site_summary_converts_cents_to_usd(): void {
		update_option( 'pearblog_revenue_total', 1050.0 );

		$summary = $this->tracker->get_site_summary();

		$this->assertSame( 10.5, $summary['total_usd'] );
	}

	// -----------------------------------------------------------------------
	// REST — rest_article_revenue
	// -----------------------------------------------------------------------

	public function test_rest_article_revenue_includes_roi_keys(): void {
		$this->tracker->track( 30, 250.0, RevenueTracker::SOURCE_ADSENSE );

		$req = new \WP_REST_Request( 'GET', '/pearblog/v1/revenue/30' );
		$req->set_param( 'post_id', 30 );

		$response = $this->tracker->rest_article_revenue( $req );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'revenue_cents', $data );
		$this->assertArrayHasKey( 'roi_pct',       $data );
	}

	// -----------------------------------------------------------------------
	// REST — rest_track_event
	// -----------------------------------------------------------------------

	public function test_rest_track_event_returns_200_for_valid_source(): void {
		$req = new \WP_REST_Request( 'POST', '/pearblog/v1/revenue/31/track' );
		$req->set_param( 'post_id',     31 );
		$req->set_param( 'amount_cents', 75.0 );
		$req->set_param( 'source',      RevenueTracker::SOURCE_ADSENSE );

		$response = $this->tracker->rest_track_event( $req );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	public function test_rest_track_event_returns_error_for_invalid_source(): void {
		$req = new \WP_REST_Request( 'POST', '/pearblog/v1/revenue/32/track' );
		$req->set_param( 'post_id',     32 );
		$req->set_param( 'amount_cents', 10.0 );
		$req->set_param( 'source',      'unknown_source' );

		$result = $this->tracker->rest_track_event( $req );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_source', $result->get_error_code() );
	}

	// -----------------------------------------------------------------------
	// admin_permission
	// -----------------------------------------------------------------------

	public function test_admin_permission_true_when_manage_options(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->tracker->admin_permission() );
	}

	public function test_admin_permission_false_when_no_manage_options(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->tracker->admin_permission() );
	}
}
