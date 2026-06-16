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
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_actions']    = [];
		$GLOBALS['_scheduled']  = [];
		$this->monitor = new CoreWebVitalsMonitor();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant(): void {
		$this->assertSame( 'pearblog_cwv_enabled', CoreWebVitalsMonitor::OPTION_ENABLED );
	}

	public function test_option_api_key_constant(): void {
		$this->assertSame( 'pearblog_cwv_api_key', CoreWebVitalsMonitor::OPTION_API_KEY );
	}

	public function test_default_lcp_threshold(): void {
		$this->assertSame( 2500, CoreWebVitalsMonitor::DEFAULT_LCP_MS );
	}

	public function test_default_cls_threshold(): void {
		$this->assertSame( 0.1, CoreWebVitalsMonitor::DEFAULT_CLS );
	}

	public function test_default_fid_threshold(): void {
		$this->assertSame( 100, CoreWebVitalsMonitor::DEFAULT_FID_MS );
	}

	// -----------------------------------------------------------------------
	// measure_url — disabled state
	// -----------------------------------------------------------------------

	public function test_measure_url_returns_unavailable_when_disabled(): void {
		$result = $this->monitor->measure_url( 'https://example.com' );

		$this->assertSame( 'unavailable', $result['status'] );
		$this->assertSame( 'https://example.com', $result['url'] );
		$this->assertSame( 0.0, $result['lcp_ms'] );
		$this->assertSame( 0.0, $result['cls'] );
	}

	public function test_measure_url_returns_unavailable_when_no_api_key(): void {
		update_option( CoreWebVitalsMonitor::OPTION_ENABLED, true );

		$result = $this->monitor->measure_url( 'https://example.com' );

		$this->assertSame( 'unavailable', $result['status'] );
	}

	public function test_measure_url_returns_default_structure(): void {
		$result = $this->monitor->measure_url( 'https://test.com' );

		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'lcp_ms', $result );
		$this->assertArrayHasKey( 'cls', $result );
		$this->assertArrayHasKey( 'fid_ms', $result );
		$this->assertArrayHasKey( 'performance_score', $result );
		$this->assertArrayHasKey( 'status', $result );
	}

	// -----------------------------------------------------------------------
	// measure_url — cached result
	// -----------------------------------------------------------------------

	public function test_measure_url_returns_cached_result(): void {
		update_option( CoreWebVitalsMonitor::OPTION_ENABLED, true );
		update_option( CoreWebVitalsMonitor::OPTION_API_KEY, 'test-key' );

		$cached = [
			'url'               => 'https://example.com',
			'lcp_ms'            => 1200.0,
			'cls'               => 0.05,
			'fid_ms'            => 50.0,
			'performance_score' => 92,
			'status'            => 'pass',
		];
		set_transient( 'pearblog_cwv_' . md5( 'https://example.com' ), $cached, 3600 );

		$result = $this->monitor->measure_url( 'https://example.com' );

		$this->assertSame( 'pass', $result['status'] );
		$this->assertSame( 92, $result['performance_score'] );
	}

	// -----------------------------------------------------------------------
	// get_status thresholds (via measure_url with mocked API response)
	// -----------------------------------------------------------------------

	public function test_passing_cwv_status_when_all_within_threshold(): void {
		// All metrics below default thresholds → 'pass'
		// We call get_status via reflection to test pure logic.
		$reflection = new \ReflectionClass( $this->monitor );
		$method     = $reflection->getMethod( 'get_status' );
		$method->setAccessible( true );

		$status = $method->invoke( $this->monitor, 2000.0, 0.05, 80.0 );

		$this->assertSame( 'pass', $status );
	}

	public function test_failing_cwv_status_when_lcp_exceeds_threshold(): void {
		$reflection = new \ReflectionClass( $this->monitor );
		$method     = $reflection->getMethod( 'get_status' );
		$method->setAccessible( true );

		$status = $method->invoke( $this->monitor, 4000.0, 0.05, 80.0 );

		$this->assertSame( 'fail', $status );
	}

	public function test_failing_cwv_status_when_cls_exceeds_threshold(): void {
		$reflection = new \ReflectionClass( $this->monitor );
		$method     = $reflection->getMethod( 'get_status' );
		$method->setAccessible( true );

		$status = $method->invoke( $this->monitor, 2000.0, 0.25, 80.0 );

		$this->assertSame( 'fail', $status );
	}

	public function test_failing_cwv_status_when_fid_exceeds_threshold(): void {
		$reflection = new \ReflectionClass( $this->monitor );
		$method     = $reflection->getMethod( 'get_status' );
		$method->setAccessible( true );

		$status = $method->invoke( $this->monitor, 2000.0, 0.05, 200.0 );

		$this->assertSame( 'fail', $status );
	}

	public function test_custom_lcp_threshold_from_option(): void {
		update_option( CoreWebVitalsMonitor::OPTION_LCP_THRESHOLD, 1000 );

		$reflection = new \ReflectionClass( $this->monitor );
		$method     = $reflection->getMethod( 'get_status' );
		$method->setAccessible( true );

		// 1500ms exceeds custom threshold of 1000ms → fail
		$status = $method->invoke( $this->monitor, 1500.0, 0.05, 80.0 );

		$this->assertSame( 'fail', $status );
	}

	// -----------------------------------------------------------------------
	// maybe_schedule
	// -----------------------------------------------------------------------

	public function test_maybe_schedule_skips_when_disabled(): void {
		$this->monitor->maybe_schedule();

		$this->assertFalse( isset( $GLOBALS['_scheduled']['pearblog_cwv_audit'] ) );
	}

	public function test_maybe_schedule_schedules_weekly_when_enabled(): void {
		update_option( CoreWebVitalsMonitor::OPTION_ENABLED, true );

		$this->monitor->maybe_schedule();

		$this->assertNotFalse( wp_next_scheduled( 'pearblog_cwv_audit' ) );
	}

	// -----------------------------------------------------------------------
	// run_audit — no posts
	// -----------------------------------------------------------------------

	public function test_run_audit_saves_snapshot_to_option(): void {
		$GLOBALS['_posts'] = [];

		$this->monitor->run_audit();

		$snapshot = get_option( CoreWebVitalsMonitor::OPTION_SNAPSHOT );
		$this->assertIsArray( $snapshot );
		$this->assertArrayHasKey( 'generated_at', $snapshot );
		$this->assertArrayHasKey( 'total', $snapshot );
		$this->assertArrayHasKey( 'passing', $snapshot );
		$this->assertArrayHasKey( 'failing', $snapshot );
	}

	public function test_run_audit_snapshot_totals_zero_with_no_posts(): void {
		$GLOBALS['_posts'] = [];

		$this->monitor->run_audit();

		$snapshot = get_option( CoreWebVitalsMonitor::OPTION_SNAPSHOT );
		$this->assertSame( 0, $snapshot['total'] );
		$this->assertSame( 0, $snapshot['failing'] );
	}

	// -----------------------------------------------------------------------
	// admin_permission
	// -----------------------------------------------------------------------

	public function test_admin_permission_returns_bool(): void {
		$result = $this->monitor->admin_permission();
		$this->assertIsBool( $result );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	public function test_rest_get_snapshot_returns_response(): void {
		$request  = $this->createMock( \WP_REST_Request::class );
		$response = $this->monitor->rest_get_snapshot( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
	}

	public function test_rest_get_snapshot_returns_saved_snapshot(): void {
		update_option( CoreWebVitalsMonitor::OPTION_SNAPSHOT, [ 'total' => 5, 'failing' => 1 ] );
		$request  = $this->createMock( \WP_REST_Request::class );
		$response = $this->monitor->rest_get_snapshot( $request );

		$data = $response->get_data();
		$this->assertSame( 5, $data['total'] );
	}

	public function test_rest_measure_url_returns_error_without_url(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturn( '' );

		$result = $this->monitor->rest_measure_url( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}
}
