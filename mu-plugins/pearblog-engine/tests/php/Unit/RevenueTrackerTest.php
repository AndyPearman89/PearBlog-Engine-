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
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_post_list']  = [];
		$GLOBALS['_user_can']   = true;
		$this->tracker = new RevenueTracker();
	}

	// -----------------------------------------------------------------------
	// Source constants
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
	// get_article_data
	// -----------------------------------------------------------------------

	public function test_get_article_data_returns_defaults_for_new_post(): void {
		$data = $this->tracker->get_article_data( 999 );
		$this->assertSame( 999, $data['post_id'] );
		$this->assertSame( 0.0, $data['total_cents'] );
		$this->assertSame( [], $data['events'] );
		$this->assertSame( [], $data['totals'] );
	}

	public function test_get_article_data_returns_stored_data(): void {
		$stored = [
			'post_id'     => 42,
			'total_cents' => 150.0,
			'events'      => [ [ 'source' => 'adsense', 'amount_cents' => 150.0 ] ],
			'totals'      => [ 'adsense' => 150.0 ],
		];
		$GLOBALS['_options']['pearblog_revenue_42'] = $stored;
		$data = $this->tracker->get_article_data( 42 );
		$this->assertSame( 150.0, $data['total_cents'] );
	}

	// -----------------------------------------------------------------------
	// track
	// -----------------------------------------------------------------------

	public function test_track_adds_event(): void {
		$this->tracker->track( 1, 100.0, RevenueTracker::SOURCE_ADSENSE );
		$data = $this->tracker->get_article_data( 1 );
		$this->assertCount( 1, $data['events'] );
		$this->assertSame( 'adsense', $data['events'][0]['source'] );
	}

	public function test_track_increments_total_cents(): void {
		$this->tracker->track( 1, 50.0, RevenueTracker::SOURCE_ADSENSE );
		$this->tracker->track( 1, 75.0, RevenueTracker::SOURCE_AFFILIATE );
		$data = $this->tracker->get_article_data( 1 );
		$this->assertSame( 125.0, $data['total_cents'] );
	}

	public function test_track_updates_site_total(): void {
		$this->tracker->track( 1, 200.0, RevenueTracker::SOURCE_PAYWALL );
		$total = (float) $GLOBALS['_options']['pearblog_revenue_total'];
		$this->assertSame( 200.0, $total );
	}

	public function test_track_accumulates_site_total_across_posts(): void {
		$this->tracker->track( 1, 100.0, RevenueTracker::SOURCE_ADSENSE );
		$this->tracker->track( 2, 50.0, RevenueTracker::SOURCE_AFFILIATE );
		$total = (float) $GLOBALS['_options']['pearblog_revenue_total'];
		$this->assertSame( 150.0, $total );
	}

	public function test_track_separates_totals_by_source(): void {
		$this->tracker->track( 1, 100.0, RevenueTracker::SOURCE_ADSENSE );
		$this->tracker->track( 1, 200.0, RevenueTracker::SOURCE_AFFILIATE );
		$data = $this->tracker->get_article_data( 1 );
		$this->assertSame( 100.0, $data['totals']['adsense'] );
		$this->assertSame( 200.0, $data['totals']['affiliate'] );
	}

	// -----------------------------------------------------------------------
	// calculate_roi
	// -----------------------------------------------------------------------

	public function test_calculate_roi_with_zero_cost(): void {
		$this->tracker->track( 1, 500.0, RevenueTracker::SOURCE_ADSENSE );
		$roi = $this->tracker->calculate_roi( 1 );
		$this->assertSame( 500.0, $roi['revenue_cents'] );
		$this->assertSame( 0.0, $roi['cost_cents'] );
		$this->assertSame( 500.0, $roi['roi_cents'] );
		$this->assertSame( 0.0, $roi['roi_pct'] );
	}

	public function test_calculate_roi_returns_correct_pct(): void {
		$this->tracker->track( 1, 200.0, RevenueTracker::SOURCE_ADSENSE );
		$GLOBALS['_post_meta'][1]['pearblog_generation_cost_cents'] = [ 100.0 ];
		$roi = $this->tracker->calculate_roi( 1 );
		$this->assertSame( 100.0, $roi['roi_cents'] );
		$this->assertSame( 100.0, $roi['roi_pct'] );
	}

	// -----------------------------------------------------------------------
	// get_site_summary
	// -----------------------------------------------------------------------

	public function test_get_site_summary_returns_structure(): void {
		$summary = $this->tracker->get_site_summary();
		$this->assertArrayHasKey( 'total_cents', $summary );
		$this->assertArrayHasKey( 'total_usd', $summary );
		$this->assertArrayHasKey( 'top_articles', $summary );
		$this->assertArrayHasKey( 'generated_at', $summary );
	}

	public function test_get_site_summary_converts_cents_to_usd(): void {
		$GLOBALS['_options']['pearblog_revenue_total'] = 1000.0;
		$summary = $this->tracker->get_site_summary();
		$this->assertSame( 10.0, $summary['total_usd'] );
	}

	// -----------------------------------------------------------------------
	// init_article_revenue
	// -----------------------------------------------------------------------

	public function test_init_article_revenue_creates_record(): void {
		$this->tracker->init_article_revenue( 5 );
		$this->assertArrayHasKey( 'pearblog_revenue_5', $GLOBALS['_options'] );
	}

	public function test_init_article_revenue_skips_existing_record(): void {
		$GLOBALS['_options']['pearblog_revenue_5'] = [ 'total_cents' => 99.0 ];
		$this->tracker->init_article_revenue( 5 );
		// Should not overwrite.
		$this->assertSame( 99.0, $GLOBALS['_options']['pearblog_revenue_5']['total_cents'] );
	}

	// -----------------------------------------------------------------------
	// REST
	// -----------------------------------------------------------------------

	public function test_rest_site_summary_returns_200(): void {
		$req    = new \WP_REST_Request();
		$result = $this->tracker->rest_site_summary( $req );
		$this->assertSame( 200, $result->get_status() );
	}

	public function test_rest_article_revenue_returns_200(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'post_id', '1' );
		$result = $this->tracker->rest_article_revenue( $req );
		$this->assertSame( 200, $result->get_status() );
	}

	public function test_rest_track_invalid_source_returns_error(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'post_id', '1' );
		$req->set_param( 'amount_cents', '100' );
		$req->set_param( 'source', 'invalid_source' );
		$result = $this->tracker->rest_track_event( $req );
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_rest_track_valid_source_returns_200(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'post_id', '1' );
		$req->set_param( 'amount_cents', '50' );
		$req->set_param( 'source', RevenueTracker::SOURCE_ADSENSE );
		$result = $this->tracker->rest_track_event( $req );
		$this->assertSame( 200, $result->get_status() );
	}

	public function test_admin_permission_returns_true(): void {
		$GLOBALS['_user_can'] = true;
		$this->assertTrue( $this->tracker->admin_permission() );
	}

	public function test_register_adds_rest_api_init_action(): void {
		$this->tracker->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}
}
