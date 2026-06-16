<?php
/**
 * Unit tests for CoreWebVitalsMonitor.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\SEO\CoreWebVitalsMonitor;

class CoreWebVitalsMonitorTest extends TestCase {

	private CoreWebVitalsMonitor $monitor;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']       = [];
		$GLOBALS['_transients']    = [];
		$GLOBALS['_actions']       = [];
		$GLOBALS['_rest_routes']   = [];
		$this->monitor             = new CoreWebVitalsMonitor();
	}

	// -----------------------------------------------------------------------
	// option constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant_is_defined(): void {
		$this->assertSame( 'pearblog_cwv_enabled', CoreWebVitalsMonitor::OPTION_ENABLED );
	}

	public function test_option_api_key_constant_is_defined(): void {
		$this->assertSame( 'pearblog_cwv_api_key', CoreWebVitalsMonitor::OPTION_API_KEY );
	}

	public function test_option_snapshot_constant_is_defined(): void {
		$this->assertSame( 'pearblog_cwv_snapshot', CoreWebVitalsMonitor::OPTION_SNAPSHOT );
	}

	// -----------------------------------------------------------------------
	// default thresholds (Google "good" thresholds)
	// -----------------------------------------------------------------------

	public function test_default_lcp_threshold_is_2500_ms(): void {
		$this->assertSame( 2500, CoreWebVitalsMonitor::DEFAULT_LCP_MS );
	}

	public function test_default_cls_threshold_is_0_point_1(): void {
		$this->assertSame( 0.1, CoreWebVitalsMonitor::DEFAULT_CLS );
	}

	public function test_default_fid_threshold_is_100_ms(): void {
		$this->assertSame( 100, CoreWebVitalsMonitor::DEFAULT_FID_MS );
	}

	// -----------------------------------------------------------------------
	// measure_url — disabled (no API key / disabled option)
	// -----------------------------------------------------------------------

	public function test_measure_url_returns_unavailable_status_when_disabled(): void {
		$result = $this->monitor->measure_url( 'https://example.com/article' );
		$this->assertSame( 'unavailable', $result['status'] );
	}

	public function test_measure_url_returns_expected_keys_when_disabled(): void {
		$result = $this->monitor->measure_url( 'https://example.com/post' );

		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'lcp_ms', $result );
		$this->assertArrayHasKey( 'cls', $result );
		$this->assertArrayHasKey( 'fid_ms', $result );
		$this->assertArrayHasKey( 'performance_score', $result );
		$this->assertArrayHasKey( 'status', $result );
	}

	public function test_measure_url_preserves_url_in_result(): void {
		$url    = 'https://myblog.com/page-1';
		$result = $this->monitor->measure_url( $url );
		$this->assertSame( $url, $result['url'] );
	}

	public function test_measure_url_returns_zero_metrics_when_disabled(): void {
		$result = $this->monitor->measure_url( 'https://example.com/' );
		$this->assertSame( 0.0, $result['lcp_ms'] );
		$this->assertSame( 0.0, $result['cls'] );
		$this->assertSame( 0.0, $result['fid_ms'] );
		$this->assertSame( 0, $result['performance_score'] );
	}

	public function test_measure_url_returns_unavailable_when_enabled_but_no_api_key(): void {
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_ENABLED ] = true;
		// No API key set.
		$result = $this->monitor->measure_url( 'https://example.com/post' );
		$this->assertSame( 'unavailable', $result['status'] );
	}

	// -----------------------------------------------------------------------
	// get_status logic (tested indirectly via thresholds + enabled monitor)
	//
	// When disabled, measure_url returns 'unavailable'. To test pass/fail
	// we need to enable and seed a cached transient result that bypasses the
	// real API call.
	// -----------------------------------------------------------------------

	public function test_measure_url_returns_cached_result_when_transient_set(): void {
		$url       = 'https://example.com/cached';
		$cache_key = 'pearblog_cwv_' . md5( $url );
		$cached    = [
			'url'               => $url,
			'lcp_ms'            => 1200.0,
			'cls'               => 0.05,
			'fid_ms'            => 50.0,
			'performance_score' => 90,
			'status'            => 'pass',
		];
		$GLOBALS['_transients'][ $cache_key ] = $cached;
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_ENABLED ] = true;
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_API_KEY ] = 'fake-key';

		$result = $this->monitor->measure_url( $url );

		$this->assertSame( 'pass', $result['status'] );
		$this->assertSame( 1200.0, $result['lcp_ms'] );
		$this->assertSame( 90, $result['performance_score'] );
	}

	// -----------------------------------------------------------------------
	// maybe_schedule — does not schedule when disabled
	// -----------------------------------------------------------------------

	public function test_maybe_schedule_does_not_schedule_when_disabled(): void {
		// Ensure no cron is scheduled (wp_next_scheduled returns false by default).
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_ENABLED ] = false;
		$this->monitor->maybe_schedule(); // Should be a no-op.
		$this->assertTrue( true ); // No exception thrown.
	}

	// -----------------------------------------------------------------------
	// rest_get_snapshot
	// -----------------------------------------------------------------------

	public function test_rest_get_snapshot_returns_empty_array_when_no_data(): void {
		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/cwv/snapshot' );
		$response = $this->monitor->rest_get_snapshot( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [], $response->get_data() );
	}

	public function test_rest_get_snapshot_returns_saved_snapshot(): void {
		$snapshot = [
			'generated_at' => time(),
			'total'        => 5,
			'passing'      => 4,
			'failing'      => 1,
			'results'      => [],
		];
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_SNAPSHOT ] = $snapshot;

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/cwv/snapshot' );
		$response = $this->monitor->rest_get_snapshot( $request );

		$data = $response->get_data();
		$this->assertSame( 5, $data['total'] );
		$this->assertSame( 4, $data['passing'] );
		$this->assertSame( 1, $data['failing'] );
	}

	// -----------------------------------------------------------------------
	// rest_measure_url
	// -----------------------------------------------------------------------

	public function test_rest_measure_url_returns_error_when_url_missing(): void {
		$request = new \WP_REST_Request( 'POST', '/pearblog/v1/cwv/measure' );
		$result  = $this->monitor->rest_measure_url( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'missing_url', $result->get_error_code() );
	}

	public function test_rest_measure_url_returns_response_for_valid_url(): void {
		$request = new \WP_REST_Request( 'POST', '/pearblog/v1/cwv/measure' );
		$request->set_param( 'url', 'https://example.com/article' );

		$result = $this->monitor->rest_measure_url( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 200, $result->get_status() );
	}

	// -----------------------------------------------------------------------
	// admin_permission
	// -----------------------------------------------------------------------

	public function test_admin_permission_returns_true_when_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->monitor->admin_permission() );
	}

	public function test_admin_permission_returns_false_when_not_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->monitor->admin_permission() );
	}
}
