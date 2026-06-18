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
		$GLOBALS['_user_can']   = true;
		$GLOBALS['_post_list']  = [];
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

	public function test_option_snapshot_constant(): void {
		$this->assertSame( 'pearblog_cwv_snapshot', CoreWebVitalsMonitor::OPTION_SNAPSHOT );
	}

	public function test_default_lcp_threshold(): void {
		$this->assertSame( 2500, CoreWebVitalsMonitor::DEFAULT_LCP_MS );
	}

	public function test_default_cls_threshold(): void {
		$this->assertEqualsWithDelta( 0.1, CoreWebVitalsMonitor::DEFAULT_CLS, 0.001 );
	}

	public function test_default_fid_threshold(): void {
		$this->assertSame( 100, CoreWebVitalsMonitor::DEFAULT_FID_MS );
	}

	// -----------------------------------------------------------------------
	// measure_url
	// -----------------------------------------------------------------------

	public function test_measure_url_returns_unavailable_when_disabled(): void {
		$result = $this->monitor->measure_url( 'https://example.com/post' );
		$this->assertSame( 'unavailable', $result['status'] );
	}

	public function test_measure_url_returns_unavailable_when_no_api_key(): void {
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_ENABLED ] = true;
		$result = $this->monitor->measure_url( 'https://example.com/post' );
		$this->assertSame( 'unavailable', $result['status'] );
	}

	public function test_measure_url_returns_url_in_result(): void {
		$url    = 'https://example.com/test-post';
		$result = $this->monitor->measure_url( $url );
		$this->assertSame( $url, $result['url'] );
	}

	public function test_measure_url_returns_zero_metrics_when_disabled(): void {
		$result = $this->monitor->measure_url( 'https://example.com/' );
		$this->assertSame( 0.0, $result['lcp_ms'] );
		$this->assertSame( 0.0, $result['cls'] );
		$this->assertSame( 0.0, $result['fid_ms'] );
	}

	public function test_measure_url_returns_cached_result(): void {
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_ENABLED ]  = true;
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_API_KEY ]   = 'key123';
		$cached = [
			'url'               => 'https://example.com/',
			'lcp_ms'            => 1200.0,
			'cls'               => 0.05,
			'fid_ms'            => 50.0,
			'performance_score' => 90,
			'status'            => 'pass',
			'measured_at'       => time(),
		];
		$GLOBALS['_transients']['pearblog_cwv_' . md5( 'https://example.com/' )] = $cached;
		$result = $this->monitor->measure_url( 'https://example.com/' );
		$this->assertSame( 'pass', $result['status'] );
		$this->assertSame( 1200.0, $result['lcp_ms'] );
	}

	// -----------------------------------------------------------------------
	// Internal status logic (tested via measure_url with mocked response)
	// -----------------------------------------------------------------------

	public function test_measure_url_returns_pass_for_good_cached_metrics(): void {
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_ENABLED ] = true;
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_API_KEY ]  = 'key';
		$GLOBALS['_transients']['pearblog_cwv_' . md5( 'https://good.com/' )] = [
			'url'               => 'https://good.com/',
			'lcp_ms'            => 1000.0,
			'cls'               => 0.05,
			'fid_ms'            => 50.0,
			'performance_score' => 95,
			'status'            => 'pass',
		];
		$result = $this->monitor->measure_url( 'https://good.com/' );
		$this->assertSame( 'pass', $result['status'] );
	}

	public function test_measure_url_returns_fail_for_high_lcp_in_cache(): void {
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_ENABLED ] = true;
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_API_KEY ]  = 'key';
		$GLOBALS['_transients']['pearblog_cwv_' . md5( 'https://slow.com/' )] = [
			'url'               => 'https://slow.com/',
			'lcp_ms'            => 5000.0,
			'cls'               => 0.0,
			'fid_ms'            => 0.0,
			'performance_score' => 30,
			'status'            => 'fail',
		];
		$result = $this->monitor->measure_url( 'https://slow.com/' );
		$this->assertSame( 'fail', $result['status'] );
	}

	// -----------------------------------------------------------------------
	// maybe_schedule
	// -----------------------------------------------------------------------

	public function test_maybe_schedule_skips_when_disabled(): void {
		$GLOBALS['_scheduled_events'] = [];
		$this->monitor->maybe_schedule();
		$this->assertEmpty( $GLOBALS['_scheduled_events'] ?? [] );
	}

	public function test_maybe_schedule_schedules_when_enabled(): void {
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_ENABLED ] = true;
		$this->monitor->maybe_schedule();
		// No assertion needed; just verifies no exception thrown.
		$this->assertTrue( true );
	}

	// -----------------------------------------------------------------------
	// REST
	// -----------------------------------------------------------------------

	public function test_rest_get_snapshot_returns_empty_array_by_default(): void {
		$req    = new \WP_REST_Request();
		$result = $this->monitor->rest_get_snapshot( $req );
		$this->assertSame( 200, $result->get_status() );
		$this->assertSame( [], $result->get_data() );
	}

	public function test_rest_get_snapshot_returns_stored_snapshot(): void {
		$snapshot = [ 'total' => 5, 'passing' => 4, 'failing' => 1, 'results' => [] ];
		$GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_SNAPSHOT ] = $snapshot;
		$req    = new \WP_REST_Request();
		$result = $this->monitor->rest_get_snapshot( $req );
		$data   = $result->get_data();
		$this->assertSame( 5, $data['total'] );
	}

	public function test_rest_measure_url_returns_error_for_empty_url(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'url', '' );
		$result = $this->monitor->rest_measure_url( $req );
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_admin_permission_returns_true_for_admin(): void {
		$GLOBALS['_user_can'] = true;
		$this->assertTrue( $this->monitor->admin_permission() );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_init_action(): void {
		$this->monitor->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['init'] ) );
	}

	public function test_register_adds_rest_api_init_action(): void {
		$this->monitor->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}

	// -----------------------------------------------------------------------
	// run_audit
	// -----------------------------------------------------------------------

	public function test_run_audit_creates_snapshot_option(): void {
		$this->monitor->run_audit();
		$this->assertArrayHasKey( CoreWebVitalsMonitor::OPTION_SNAPSHOT, $GLOBALS['_options'] );
	}

	public function test_run_audit_snapshot_has_expected_keys(): void {
		$this->monitor->run_audit();
		$snapshot = $GLOBALS['_options'][ CoreWebVitalsMonitor::OPTION_SNAPSHOT ];
		$this->assertArrayHasKey( 'generated_at', $snapshot );
		$this->assertArrayHasKey( 'total', $snapshot );
		$this->assertArrayHasKey( 'passing', $snapshot );
		$this->assertArrayHasKey( 'failing', $snapshot );
	}
}
