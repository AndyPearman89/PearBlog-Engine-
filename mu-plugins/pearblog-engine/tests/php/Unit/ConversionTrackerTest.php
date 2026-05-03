<?php
/**
 * Tests for ConversionTracker.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Analytics\ConversionTracker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\Analytics\ConversionTracker
 */
class ConversionTrackerTest extends TestCase {

	/** @var ConversionTracker */
	private ConversionTracker $tracker;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_current_user_can'] = false;
		$this->tracker = new ConversionTracker();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options'] = [];
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_event_constants_are_defined(): void {
		$this->assertSame( 'page_view', ConversionTracker::EVENT_PAGE_VIEW );
		$this->assertSame( 'email_signup', ConversionTracker::EVENT_EMAIL_SIGNUP );
		$this->assertSame( 'cta_click', ConversionTracker::EVENT_CTA_CLICK );
		$this->assertSame( 'purchase', ConversionTracker::EVENT_PURCHASE );
		$this->assertSame( 'scroll_depth', ConversionTracker::EVENT_SCROLL_DEPTH );
	}

	public function test_all_events_list_complete(): void {
		$this->assertCount( 5, ConversionTracker::ALL_EVENTS );
	}

	public function test_funnel_stages_cover_all_events(): void {
		$covered = array_merge( ...array_values( ConversionTracker::FUNNEL_STAGES ) );
		sort( $covered );
		$all = ConversionTracker::ALL_EVENTS;
		sort( $all );
		$this->assertSame( $all, $covered );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_returns_true_by_default(): void {
		$this->assertTrue( $this->tracker->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_disabled(): void {
		$GLOBALS['_options']['pearblog_conv_tracker_enabled'] = false;
		$this->assertFalse( $this->tracker->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// track
	// -----------------------------------------------------------------------

	public function test_track_returns_entry_with_correct_keys(): void {
		$entry = $this->tracker->track( ConversionTracker::EVENT_PAGE_VIEW, 1 );
		$this->assertArrayHasKey( 'event', $entry );
		$this->assertArrayHasKey( 'post_id', $entry );
		$this->assertArrayHasKey( 'meta', $entry );
		$this->assertArrayHasKey( 'at', $entry );
	}

	public function test_track_stores_correct_event_type(): void {
		$entry = $this->tracker->track( ConversionTracker::EVENT_CTA_CLICK, 5, [ 'label' => 'Subscribe' ] );
		$this->assertSame( ConversionTracker::EVENT_CTA_CLICK, $entry['event'] );
		$this->assertSame( 5, $entry['post_id'] );
		$this->assertSame( [ 'label' => 'Subscribe' ], $entry['meta'] );
	}

	public function test_track_increments_total(): void {
		$this->tracker->track( ConversionTracker::EVENT_PAGE_VIEW, 1 );
		$this->tracker->track( ConversionTracker::EVENT_PAGE_VIEW, 2 );
		$totals = $this->tracker->get_totals();
		$this->assertSame( 2, $totals[ ConversionTracker::EVENT_PAGE_VIEW ] );
	}

	public function test_track_stores_in_event_log(): void {
		$this->tracker->track( ConversionTracker::EVENT_EMAIL_SIGNUP, 10 );
		$log = $this->tracker->get_event_log( ConversionTracker::EVENT_EMAIL_SIGNUP );
		$this->assertCount( 1, $log );
		$this->assertSame( 10, $log[0]['post_id'] );
	}

	public function test_track_fires_action(): void {
		$captured = null;
		$GLOBALS['_action_handlers']['pearblog_conversion_tracked'] = function( $event ) use ( &$captured ) {
			$captured = $event;
		};

		$this->tracker->track( ConversionTracker::EVENT_PURCHASE, 7 );
		$this->assertSame( ConversionTracker::EVENT_PURCHASE, $captured );
	}

	// -----------------------------------------------------------------------
	// get_totals
	// -----------------------------------------------------------------------

	public function test_get_totals_includes_all_event_types(): void {
		$totals = $this->tracker->get_totals();
		foreach ( ConversionTracker::ALL_EVENTS as $event ) {
			$this->assertArrayHasKey( $event, $totals );
		}
	}

	public function test_get_totals_starts_at_zero(): void {
		$totals = $this->tracker->get_totals();
		foreach ( ConversionTracker::ALL_EVENTS as $event ) {
			$this->assertSame( 0, $totals[ $event ] );
		}
	}

	// -----------------------------------------------------------------------
	// get_event_log
	// -----------------------------------------------------------------------

	public function test_get_event_log_returns_empty_initially(): void {
		$log = $this->tracker->get_event_log( ConversionTracker::EVENT_PURCHASE );
		$this->assertSame( [], $log );
	}

	public function test_get_event_log_returns_empty_for_non_array_option(): void {
		$GLOBALS['_options']['pearblog_conv_events_purchase'] = 'corrupted';
		$log = $this->tracker->get_event_log( ConversionTracker::EVENT_PURCHASE );
		$this->assertSame( [], $log );
	}

	// -----------------------------------------------------------------------
	// get_post_stats
	// -----------------------------------------------------------------------

	public function test_get_post_stats_returns_zero_counts_initially(): void {
		$stats = $this->tracker->get_post_stats( 42 );
		$this->assertSame( 42, $stats['post_id'] );
		foreach ( ConversionTracker::ALL_EVENTS as $event ) {
			$this->assertSame( 0, $stats['stats'][ $event ] );
		}
	}

	public function test_get_post_stats_counts_per_post(): void {
		$this->tracker->track( ConversionTracker::EVENT_PAGE_VIEW, 99 );
		$this->tracker->track( ConversionTracker::EVENT_PAGE_VIEW, 99 );
		$this->tracker->track( ConversionTracker::EVENT_PAGE_VIEW, 1 );  // Different post.

		$stats = $this->tracker->get_post_stats( 99 );
		$this->assertSame( 2, $stats['stats'][ ConversionTracker::EVENT_PAGE_VIEW ] );
	}

	// -----------------------------------------------------------------------
	// get_funnel_view
	// -----------------------------------------------------------------------

	public function test_get_funnel_view_returns_all_stages(): void {
		$funnel = $this->tracker->get_funnel_view();
		$this->assertArrayHasKey( 'awareness', $funnel );
		$this->assertArrayHasKey( 'consideration', $funnel );
		$this->assertArrayHasKey( 'conversion', $funnel );
		$this->assertArrayHasKey( 'conversion_rate_pct', $funnel );
	}

	public function test_get_funnel_view_conversion_rate_is_zero_with_no_data(): void {
		$funnel = $this->tracker->get_funnel_view();
		$this->assertSame( 0.0, $funnel['conversion_rate_pct'] );
	}

	public function test_get_funnel_view_calculates_conversion_rate(): void {
		// 100 page views → 10 purchases = 10%.
		for ( $i = 0; $i < 100; $i++ ) {
			$this->tracker->track( ConversionTracker::EVENT_PAGE_VIEW, $i );
		}
		for ( $i = 0; $i < 10; $i++ ) {
			$this->tracker->track( ConversionTracker::EVENT_PURCHASE, $i );
		}

		$funnel = $this->tracker->get_funnel_view();
		$this->assertSame( 10.0, $funnel['conversion_rate_pct'] );
	}

	// -----------------------------------------------------------------------
	// REST routes and permissions
	// -----------------------------------------------------------------------

	public function test_rest_permission_returns_true(): void {
		$this->assertTrue( $this->tracker->rest_permission() );
	}

	public function test_admin_permission_returns_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->tracker->admin_permission() );
	}

	public function test_admin_permission_returns_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->tracker->admin_permission() );
	}

	public function test_register_routes_adds_routes(): void {
		$GLOBALS['_rest_routes'] = [];
		$this->tracker->register_routes();
		$this->assertNotEmpty( $GLOBALS['_rest_routes'] );
	}

	public function test_rest_track_rejects_unknown_event(): void {
		$request = new \WP_REST_Request( 'POST', '/pearblog/v1/conversions/track' );
		$request->set_param( 'event', 'invalid_event' );
		$response = $this->tracker->rest_track( $request );
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_rest_track_accepts_valid_event(): void {
		$request = new \WP_REST_Request( 'POST', '/pearblog/v1/conversions/track' );
		$request->set_param( 'event', ConversionTracker::EVENT_CTA_CLICK );
		$request->set_param( 'post_id', 5 );
		$response = $this->tracker->rest_track( $request );
		$this->assertSame( 200, $response->get_status() );
	}

	public function test_rest_stats_returns_all_event_totals(): void {
		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/conversions/stats' );
		$response = $this->tracker->rest_stats( $request );
		$data     = $response->get_data();
		foreach ( ConversionTracker::ALL_EVENTS as $event ) {
			$this->assertArrayHasKey( $event, $data );
		}
	}

	public function test_rest_funnel_returns_funnel_view(): void {
		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/conversions/funnel' );
		$response = $this->tracker->rest_funnel( $request );
		$data     = $response->get_data();
		$this->assertArrayHasKey( 'awareness', $data );
		$this->assertArrayHasKey( 'conversion_rate_pct', $data );
	}

	public function test_rest_post_stats_returns_stats_for_post(): void {
		$request = new \WP_REST_Request( 'GET', '/pearblog/v1/conversions/post/77' );
		$request->set_param( 'id', 77 );
		$response = $this->tracker->rest_post_stats( $request );
		$data     = $response->get_data();
		$this->assertSame( 77, $data['post_id'] );
	}

	// -----------------------------------------------------------------------
	// register hooks
	// -----------------------------------------------------------------------

	public function test_register_adds_hooks_when_enabled(): void {
		$GLOBALS['_options']['pearblog_conv_tracker_enabled'] = true;
		$this->tracker->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
		$this->assertTrue( isset( $GLOBALS['_actions']['template_redirect'] ) );
	}

	public function test_register_skips_hooks_when_disabled(): void {
		$GLOBALS['_options']['pearblog_conv_tracker_enabled'] = false;
		$GLOBALS['_actions'] = [];
		$this->tracker->register();
		$this->assertEmpty( $GLOBALS['_actions'] );
	}
}
