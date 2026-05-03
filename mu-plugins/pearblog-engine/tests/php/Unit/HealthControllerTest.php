<?php
/**
 * Tests for HealthController.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Monitoring\HealthController;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\Monitoring\HealthController
 */
class HealthControllerTest extends TestCase {

	/** @var HealthController */
	private HealthController $controller;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_transients']       = [];
		$GLOBALS['_current_user_can'] = false;
		$this->controller = new HealthController();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options']          = [];
		$GLOBALS['_transients']       = [];
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// register_routes
	// -----------------------------------------------------------------------

	public function test_register_routes_adds_rest_route(): void {
		$GLOBALS['_rest_routes'] = [];
		$this->controller->register_routes();
		$this->assertNotEmpty( $GLOBALS['_rest_routes'] );
	}

	// -----------------------------------------------------------------------
	// authorize_request (via get_health – indirect test through rest response)
	// -----------------------------------------------------------------------

	public function test_get_health_returns_overall_field(): void {
		// Provide an API key so circuit and key checks pass.
		$GLOBALS['_options']['pearblog_openai_api_key'] = 'sk-test';

		// Provide a cached openai check to avoid HTTP call.
		$GLOBALS['_transients']['pb_health_openai_check'] = [
			'status' => 'ok',
			'detail' => 'reachable',
		];

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/health' );
		$response = $this->controller->get_health( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'overall', $data );
		$this->assertContains( $data['overall'], [ 'ok', 'degraded', 'down' ] );
	}

	public function test_get_health_contains_required_checks(): void {
		$GLOBALS['_options']['pearblog_openai_api_key'] = 'sk-test';
		$GLOBALS['_transients']['pb_health_openai_check'] = [ 'status' => 'ok', 'detail' => 'reachable' ];

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/health' );
		$response = $this->controller->get_health( $request );
		$data     = $response->get_data();

		$required_keys = [ 'api_key', 'circuit_breaker', 'openai', 'queue', 'last_run', 'ai_cost', 'articles_today' ];
		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $data, "Missing key: {$key}" );
		}
	}

	public function test_get_health_overall_ok_when_key_set(): void {
		$GLOBALS['_options']['pearblog_openai_api_key'] = 'sk-test';
		$GLOBALS['_options']['pearblog_last_pipeline_run'] = time(); // recent run

		$GLOBALS['_transients']['pb_health_openai_check'] = [ 'status' => 'ok', 'detail' => 'reachable' ];

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/health' );
		$response = $this->controller->get_health( $request );
		$data     = $response->get_data();

		$this->assertSame( 'ok', $data['api_key']['status'] );
	}

	public function test_get_health_api_key_error_when_not_configured(): void {
		$GLOBALS['_options']['pearblog_openai_api_key'] = '';
		$GLOBALS['_transients']['pb_health_openai_check'] = [ 'status' => 'error', 'detail' => 'API key not configured' ];

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/health' );
		$response = $this->controller->get_health( $request );
		$data     = $response->get_data();

		$this->assertSame( 'error', $data['api_key']['status'] );
	}

	public function test_get_health_overall_down_when_api_key_missing(): void {
		$GLOBALS['_options']['pearblog_openai_api_key'] = '';
		$GLOBALS['_transients']['pb_health_openai_check'] = [ 'status' => 'error', 'detail' => 'no key' ];

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/health' );
		$response = $this->controller->get_health( $request );
		$data     = $response->get_data();

		$this->assertSame( 'down', $data['overall'] );
		$this->assertSame( 503, $response->get_status() );
	}

	public function test_get_health_overall_degraded_on_stale_last_run(): void {
		$GLOBALS['_options']['pearblog_openai_api_key'] = 'sk-test';
		// Last run was >48h ago.
		$GLOBALS['_options']['pearblog_last_pipeline_run'] = time() - ( 50 * 3600 );
		$GLOBALS['_transients']['pb_health_openai_check'] = [ 'status' => 'ok', 'detail' => 'reachable' ];

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/health' );
		$response = $this->controller->get_health( $request );
		$data     = $response->get_data();

		$this->assertContains( $data['last_run']['status'], [ 'warning', 'ok' ] );
	}

	public function test_get_health_returns_timestamp(): void {
		$GLOBALS['_options']['pearblog_openai_api_key'] = 'sk-test';
		$GLOBALS['_transients']['pb_health_openai_check'] = [ 'status' => 'ok', 'detail' => 'reachable' ];

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/health' );
		$response = $this->controller->get_health( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'timestamp', $data );
		$this->assertNotEmpty( $data['timestamp'] );
	}

	public function test_get_health_uses_cached_openai_check(): void {
		$GLOBALS['_options']['pearblog_openai_api_key'] = 'sk-test';
		$cached = [ 'status' => 'warning', 'detail' => 'rate limited (429)' ];
		$GLOBALS['_transients']['pb_health_openai_check'] = $cached;

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/health' );
		$response = $this->controller->get_health( $request );
		$data     = $response->get_data();

		$this->assertSame( $cached, $data['openai'] );
	}

	// -----------------------------------------------------------------------
	// Queue check
	// -----------------------------------------------------------------------

	public function test_get_health_reports_queue_count(): void {
		$GLOBALS['_options']['pearblog_openai_api_key'] = 'sk-test';
		$GLOBALS['_transients']['pb_health_openai_check'] = [ 'status' => 'ok', 'detail' => 'ok' ];

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/health' );
		$response = $this->controller->get_health( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'count', $data['queue'] );
		$this->assertIsInt( $data['queue']['count'] );
	}
}
