<?php
/**
 * Unit tests for GraphQLController.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\API\GraphQLController;

class GraphQLControllerTest extends TestCase {

	private GraphQLController $ctrl;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_posts']      = [];
		$GLOBALS['_post_list']  = [];
		$GLOBALS['_filters']    = [];
		$GLOBALS['_actions']    = [];
		$this->ctrl = new GraphQLController();
	}

	// -----------------------------------------------------------------------
	// resolve() dispatcher
	// -----------------------------------------------------------------------

	public function test_resolve_unknown_query_returns_null(): void {
		$this->assertNull( $this->ctrl->resolve( 'nonExistentQuery' ) );
	}

	public function test_resolve_queue_returns_array(): void {
		// Queue is empty (no topics pushed).
		$result = $this->ctrl->resolve( 'queue' );
		$this->assertIsArray( $result );
	}

	public function test_resolve_stats_returns_expected_keys(): void {
		$stats = $this->ctrl->resolve( 'stats' );
		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'articlesTotal', $stats );
		$this->assertArrayHasKey( 'articlesToday', $stats );
		$this->assertArrayHasKey( 'successRate', $stats );
		$this->assertArrayHasKey( 'queueSize', $stats );
		$this->assertArrayHasKey( 'aiCostCents', $stats );
	}

	public function test_resolve_health_returns_expected_keys(): void {
		$health = $this->ctrl->resolve( 'health' );
		$this->assertIsArray( $health );
		$this->assertArrayHasKey( 'apiConfigured', $health );
		$this->assertArrayHasKey( 'circuitOpen', $health );
		$this->assertArrayHasKey( 'queueSize', $health );
		$this->assertArrayHasKey( 'lastPipelineRun', $health );
	}

	public function test_resolve_top_posts_returns_array(): void {
		$posts = $this->ctrl->resolve( 'topPosts', [ 'limit' => 5 ] );
		$this->assertIsArray( $posts );
	}

	// -----------------------------------------------------------------------
	// resolve_stats — value checks
	// -----------------------------------------------------------------------

	public function test_resolve_stats_defaults_to_100_success_rate_with_no_runs(): void {
		$stats = $this->ctrl->resolve_stats();
		$this->assertSame( 100.0, $stats['successRate'] );
	}

	public function test_resolve_stats_calculates_success_rate(): void {
		update_option( 'pearblog_perf_metrics', wp_json_encode( [
			'pipeline_runs' => 100,
			'pipeline_ok'   => 95,
		] ) );

		$stats = $this->ctrl->resolve_stats();
		$this->assertSame( 95.0, $stats['successRate'] );
	}

	public function test_resolve_stats_cost_reflects_option(): void {
		update_option( 'pearblog_ai_cost_cents', 850 );
		$stats = $this->ctrl->resolve_stats();
		$this->assertSame( 850, $stats['aiCostCents'] );
	}

	// -----------------------------------------------------------------------
	// resolve_health — value checks
	// -----------------------------------------------------------------------

	public function test_resolve_health_api_not_configured_by_default(): void {
		$health = $this->ctrl->resolve_health();
		$this->assertFalse( $health['apiConfigured'] );
	}

	public function test_resolve_health_api_configured_when_key_set(): void {
		update_option( 'pearblog_openai_api_key', 'sk-test-key' );
		$health = $this->ctrl->resolve_health();
		$this->assertTrue( $health['apiConfigured'] );
	}

	public function test_resolve_health_circuit_open(): void {
		update_option( 'pearblog_circuit_breaker', [ 'open' => true ] );
		$health = $this->ctrl->resolve_health();
		$this->assertTrue( $health['circuitOpen'] );
	}

	public function test_resolve_health_circuit_closed_by_default(): void {
		$health = $this->ctrl->resolve_health();
		$this->assertFalse( $health['circuitOpen'] );
	}

	public function test_resolve_health_last_pipeline_run_default(): void {
		$health = $this->ctrl->resolve_health();
		$this->assertSame( 'never', $health['lastPipelineRun'] );
	}

	// -----------------------------------------------------------------------
	// handle_request
	// -----------------------------------------------------------------------

	public function test_handle_request_missing_query_returns_400(): void {
		$request = new \WP_REST_Request();
		$response = $this->ctrl->handle_request( $request );
		$this->assertSame( 400, $response->status );
		$this->assertArrayHasKey( 'errors', $response->data );
	}

	public function test_handle_request_unknown_query_returns_400(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'query', 'unknownQuery' );
		$response = $this->ctrl->handle_request( $request );
		$this->assertSame( 400, $response->status );
	}

	public function test_handle_request_stats_returns_200(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'query', 'stats' );
		$response = $this->ctrl->handle_request( $request );
		$this->assertSame( 200, $response->status );
		$this->assertArrayHasKey( 'data', $response->data );
		$this->assertArrayHasKey( 'stats', $response->data['data'] );
	}

	public function test_handle_request_health_returns_200(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'query', 'health' );
		$response = $this->ctrl->handle_request( $request );
		$this->assertSame( 200, $response->status );
	}

	public function test_handle_request_queue_returns_200(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'query', 'queue' );
		$response = $this->ctrl->handle_request( $request );
		$this->assertSame( 200, $response->status );
	}

	// -----------------------------------------------------------------------
	// check_permission
	// -----------------------------------------------------------------------

	public function test_permission_denied_when_no_api_key_configured(): void {
		$request = new \WP_REST_Request();
		$this->assertFalse( $this->ctrl->check_permission( $request ) );
	}

	public function test_permission_granted_with_valid_bearer_token(): void {
		update_option( 'pearblog_api_key', 'my-secret-key' );
		$request = new \WP_REST_Request();
		$request->set_header( 'authorization', 'Bearer my-secret-key' );
		$this->assertTrue( $this->ctrl->check_permission( $request ) );
	}

	public function test_permission_denied_with_wrong_bearer_token(): void {
		update_option( 'pearblog_api_key', 'correct-key' );
		$request = new \WP_REST_Request();
		$request->set_header( 'authorization', 'Bearer wrong-key' );
		$this->assertFalse( $this->ctrl->check_permission( $request ) );
	}

	public function test_permission_denied_with_no_bearer_prefix(): void {
		update_option( 'pearblog_api_key', 'my-secret-key' );
		$request = new \WP_REST_Request();
		$request->set_header( 'authorization', 'my-secret-key' );
		$this->assertFalse( $this->ctrl->check_permission( $request ) );
	}
}
