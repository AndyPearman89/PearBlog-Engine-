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
		$GLOBALS['_actions']   = [];
		$this->tracker = new RevenueTracker();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_source_adsense_constant(): void {
		$this->assertSame( 'adsense', RevenueTracker::SOURCE_ADSENSE );
	}

	public function test_source_affiliate_constant(): void {
		$this->assertSame( 'affiliate', RevenueTracker::SOURCE_AFFILIATE );
	}

	public function test_source_paywall_constant(): void {
		$this->assertSame( 'paywall', RevenueTracker::SOURCE_PAYWALL );
	}

	public function test_source_lead_constant(): void {
		$this->assertSame( 'lead', RevenueTracker::SOURCE_LEAD );
	}

	// -----------------------------------------------------------------------
	// get_article_data — default structure
	// -----------------------------------------------------------------------

	public function test_get_article_data_returns_default_for_new_post(): void {
		$data = $this->tracker->get_article_data( 999 );

		$this->assertSame( 999, $data['post_id'] );
		$this->assertSame( 0.0, $data['total_cents'] );
		$this->assertSame( [], $data['events'] );
		$this->assertSame( [], $data['totals'] );
	}

	// -----------------------------------------------------------------------
	// track
	// -----------------------------------------------------------------------

	public function test_track_increases_total_cents(): void {
		$this->tracker->track( 1, 500.0, RevenueTracker::SOURCE_ADSENSE );

		$data = $this->tracker->get_article_data( 1 );
		$this->assertSame( 500.0, $data['total_cents'] );
	}

	public function test_track_records_event(): void {
		$this->tracker->track( 1, 250.0, RevenueTracker::SOURCE_AFFILIATE );

		$data = $this->tracker->get_article_data( 1 );
		$this->assertCount( 1, $data['events'] );
		$this->assertSame( RevenueTracker::SOURCE_AFFILIATE, $data['events'][0]['source'] );
		$this->assertSame( 250.0, $data['events'][0]['amount_cents'] );
	}

	public function test_track_accumulates_multiple_events(): void {
		$this->tracker->track( 1, 100.0, RevenueTracker::SOURCE_ADSENSE );
		$this->tracker->track( 1, 200.0, RevenueTracker::SOURCE_AFFILIATE );
		$this->tracker->track( 1, 300.0, RevenueTracker::SOURCE_PAYWALL );

		$data = $this->tracker->get_article_data( 1 );
		$this->assertSame( 600.0, $data['total_cents'] );
		$this->assertCount( 3, $data['events'] );
	}

	public function test_track_updates_source_totals(): void {
		$this->tracker->track( 1, 150.0, RevenueTracker::SOURCE_ADSENSE );
		$this->tracker->track( 1, 100.0, RevenueTracker::SOURCE_ADSENSE );

		$data = $this->tracker->get_article_data( 1 );
		$this->assertSame( 250.0, $data['totals'][RevenueTracker::SOURCE_ADSENSE] );
	}

	public function test_track_updates_site_cumulative_total(): void {
		$this->tracker->track( 1, 500.0, RevenueTracker::SOURCE_ADSENSE );
		$this->tracker->track( 2, 300.0, RevenueTracker::SOURCE_AFFILIATE );

		$summary = $this->tracker->get_site_summary();
		$this->assertSame( 800.0, $summary['total_cents'] );
	}

	public function test_track_stores_meta_in_event(): void {
		$meta = [ 'campaign' => 'summer_sale', 'clicks' => 5 ];
		$this->tracker->track( 1, 100.0, RevenueTracker::SOURCE_AFFILIATE, $meta );

		$data = $this->tracker->get_article_data( 1 );
		$this->assertSame( 'summer_sale', $data['events'][0]['meta']['campaign'] );
	}

	// -----------------------------------------------------------------------
	// calculate_roi
	// -----------------------------------------------------------------------

	public function test_calculate_roi_returns_zero_when_no_revenue(): void {
		$roi = $this->tracker->calculate_roi( 999 );

		$this->assertSame( 0.0, $roi['revenue_cents'] );
		$this->assertSame( 0.0, $roi['roi_cents'] );
	}

	public function test_calculate_roi_is_positive_when_revenue_exceeds_cost(): void {
		update_post_meta( 1, 'pearblog_generation_cost_cents', 200.0 );
		$this->tracker->track( 1, 500.0, RevenueTracker::SOURCE_ADSENSE );

		$roi = $this->tracker->calculate_roi( 1 );

		$this->assertSame( 300.0, $roi['roi_cents'] );
		$this->assertSame( 150.0, $roi['roi_pct'] );
	}

	public function test_calculate_roi_is_negative_when_cost_exceeds_revenue(): void {
		update_post_meta( 1, 'pearblog_generation_cost_cents', 1000.0 );
		$this->tracker->track( 1, 200.0, RevenueTracker::SOURCE_ADSENSE );

		$roi = $this->tracker->calculate_roi( 1 );

		$this->assertSame( -800.0, $roi['roi_cents'] );
	}

	public function test_calculate_roi_zero_pct_when_no_cost(): void {
		$this->tracker->track( 1, 200.0, RevenueTracker::SOURCE_ADSENSE );

		$roi = $this->tracker->calculate_roi( 1 );

		$this->assertSame( 0.0, $roi['roi_pct'] );
	}

	// -----------------------------------------------------------------------
	// get_site_summary
	// -----------------------------------------------------------------------

	public function test_get_site_summary_has_required_keys(): void {
		$summary = $this->tracker->get_site_summary();

		$this->assertArrayHasKey( 'total_cents', $summary );
		$this->assertArrayHasKey( 'total_usd', $summary );
		$this->assertArrayHasKey( 'top_articles', $summary );
		$this->assertArrayHasKey( 'generated_at', $summary );
	}

	public function test_get_site_summary_total_usd_is_cents_divided_by_100(): void {
		$this->tracker->track( 1, 1000.0, RevenueTracker::SOURCE_ADSENSE );

		$summary = $this->tracker->get_site_summary();
		$this->assertSame( 10.0, $summary['total_usd'] );
	}

	// -----------------------------------------------------------------------
	// init_article_revenue
	// -----------------------------------------------------------------------

	public function test_init_article_revenue_creates_record(): void {
		$this->tracker->init_article_revenue( 5 );

		$data = $this->tracker->get_article_data( 5 );
		$this->assertSame( 5, $data['post_id'] );
	}

	public function test_init_article_revenue_does_not_overwrite_existing(): void {
		$this->tracker->track( 5, 999.0, RevenueTracker::SOURCE_ADSENSE );
		$this->tracker->init_article_revenue( 5 );

		$data = $this->tracker->get_article_data( 5 );
		$this->assertSame( 999.0, $data['total_cents'] );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	public function test_rest_site_summary_returns_200(): void {
		$request  = $this->createMock( \WP_REST_Request::class );
		$response = $this->tracker->rest_site_summary( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_rest_article_revenue_returns_200(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturn( 1 );

		$response = $this->tracker->rest_article_revenue( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_admin_permission_returns_bool(): void {
		$this->assertIsBool( $this->tracker->admin_permission() );
	}
}
