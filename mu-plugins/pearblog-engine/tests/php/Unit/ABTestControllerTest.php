<?php
/**
 * Unit tests for ABTestController.
 *
 * Tests the REST handler methods directly (no HTTP layer) using the
 * bootstrap's WP_REST_Request / WP_REST_Response stubs and a real ABTestEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Testing\ABTestController;
use PearBlogEngine\Testing\ABTestEngine;

class ABTestControllerTest extends TestCase {

	private ABTestController $ctrl;
	private ABTestEngine     $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_transients'] = [];

		$this->engine = new ABTestEngine();
		$this->ctrl   = new ABTestController( $this->engine );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function request( array $params = [] ): \WP_REST_Request {
		$req = new \WP_REST_Request();
		foreach ( $params as $k => $v ) {
			$req->set_param( $k, $v );
		}
		return $req;
	}

	private function create_engine_test( string $topic, float $score_a = 80.0, float $score_b = 70.0 ): string {
		$id = $this->engine->create_test( $topic, 'Modifier A', 'Modifier B' );
		$this->engine->record_article( $id, 'a', 10, $score_a );
		$this->engine->record_article( $id, 'a', 11, $score_a );
		$this->engine->record_article( $id, 'b', 20, $score_b );
		$this->engine->record_article( $id, 'b', 21, $score_b );
		return $id;
	}

	// -----------------------------------------------------------------------
	// list_tests
	// -----------------------------------------------------------------------

	public function test_list_tests_returns_200_with_empty_list(): void {
		$req  = $this->request();
		$resp = $this->ctrl->list_tests( $req );

		$this->assertSame( 200, $resp->status );
		$data = $resp->data;
		$this->assertSame( 0, $data['count'] );
		$this->assertSame( [], $data['tests'] );
	}

	public function test_list_tests_returns_all_tests(): void {
		$this->create_engine_test( 'Topic Alpha' );
		$this->create_engine_test( 'Topic Beta' );

		$resp = $this->ctrl->list_tests( $this->request() );
		$data = $resp->data;

		$this->assertSame( 200, $resp->status );
		$this->assertSame( 2, $data['count'] );
		$this->assertCount( 2, $data['tests'] );
	}

	public function test_list_tests_includes_expected_keys(): void {
		$this->create_engine_test( 'Topic Gamma' );

		$resp = $this->ctrl->list_tests( $this->request() );
		$test = $resp->data['tests'][0];

		foreach ( [ 'id', 'topic', 'status', 'winner', 'runs_a', 'runs_b', 'avg_quality_a', 'avg_quality_b', 'leading', 'modifier_a', 'modifier_b' ] as $key ) {
			$this->assertArrayHasKey( $key, $test, "Missing key: {$key}" );
		}
	}

	public function test_list_tests_shows_running_status(): void {
		$this->create_engine_test( 'Running Topic' );

		$resp = $this->ctrl->list_tests( $this->request() );
		$test = $resp->data['tests'][0];

		$this->assertSame( 'running', $test['status'] );
		$this->assertNull( $test['winner'] );
	}

	// -----------------------------------------------------------------------
	// create_test
	// -----------------------------------------------------------------------

	public function test_create_test_returns_201_on_success(): void {
		$req = $this->request( [
			'topic'      => 'New Topic',
			'modifier_a' => 'Write formally.',
			'modifier_b' => 'Write informally.',
		] );

		$resp = $this->ctrl->create_test( $req );

		$this->assertSame( 201, $resp->status );
		$this->assertNotEmpty( $resp->data['id'] );
		$this->assertSame( 'New Topic', $resp->data['topic'] );
	}

	public function test_create_test_returns_400_when_topic_missing(): void {
		$req = $this->request( [
			'modifier_a' => 'Mod A',
			'modifier_b' => 'Mod B',
		] );
		$resp = $this->ctrl->create_test( $req );
		$this->assertSame( 400, $resp->status );
	}

	public function test_create_test_returns_400_when_modifier_a_missing(): void {
		$req = $this->request( [ 'topic' => 'T', 'modifier_b' => 'B' ] );
		$resp = $this->ctrl->create_test( $req );
		$this->assertSame( 400, $resp->status );
	}

	public function test_create_test_returns_400_when_modifier_b_missing(): void {
		$req = $this->request( [ 'topic' => 'T', 'modifier_a' => 'A' ] );
		$resp = $this->ctrl->create_test( $req );
		$this->assertSame( 400, $resp->status );
	}

	// -----------------------------------------------------------------------
	// get_test
	// -----------------------------------------------------------------------

	public function test_get_test_returns_200_for_existing_test(): void {
		$id  = $this->create_engine_test( 'Existing Topic' );
		$req = $this->request( [ 'id' => $id ] );

		$resp = $this->ctrl->get_test( $req );

		$this->assertSame( 200, $resp->status );
		$this->assertSame( $id, $resp->data['id'] );
	}

	public function test_get_test_returns_404_for_unknown_id(): void {
		$req  = $this->request( [ 'id' => 'ab_nonexistent' ] );
		$resp = $this->ctrl->get_test( $req );
		$this->assertSame( 404, $resp->status );
	}

	// -----------------------------------------------------------------------
	// delete_test
	// -----------------------------------------------------------------------

	public function test_delete_test_returns_200_and_confirms_deletion(): void {
		$id  = $this->create_engine_test( 'Delete Me' );
		$req = $this->request( [ 'id' => $id ] );

		$resp = $this->ctrl->delete_test( $req );

		$this->assertSame( 200, $resp->status );
		$this->assertTrue( $resp->data['deleted'] );
		$this->assertSame( $id, $resp->data['test_id'] );
	}

	public function test_delete_test_returns_404_for_unknown_id(): void {
		$req  = $this->request( [ 'id' => 'ab_ghost' ] );
		$resp = $this->ctrl->delete_test( $req );
		$this->assertSame( 404, $resp->status );
	}

	// -----------------------------------------------------------------------
	// promote_test
	// -----------------------------------------------------------------------

	public function test_promote_test_returns_200_with_winner_when_data_sufficient(): void {
		$id  = $this->create_engine_test( 'Promote Topic', 90.0, 70.0 );
		$req = $this->request( [ 'id' => $id ] );

		$resp = $this->ctrl->promote_test( $req );

		$this->assertSame( 200, $resp->status );
		$this->assertTrue( $resp->data['promoted'] );
		$this->assertSame( 'a', $resp->data['winner'] );
	}

	public function test_promote_test_returns_200_promoted_false_insufficient_data(): void {
		// Create test with only 1 article per variant (below threshold of 2).
		$id = $this->engine->create_test( 'Thin Topic', 'MA', 'MB' );
		$this->engine->record_article( $id, 'a', 1, 80.0 );
		$this->engine->record_article( $id, 'b', 2, 70.0 );

		$req  = $this->request( [ 'id' => $id ] );
		$resp = $this->ctrl->promote_test( $req );

		$this->assertSame( 200, $resp->status );
		$this->assertFalse( $resp->data['promoted'] );
	}

	public function test_promote_test_returns_404_for_unknown_test(): void {
		$req  = $this->request( [ 'id' => 'ab_nope' ] );
		$resp = $this->ctrl->promote_test( $req );
		$this->assertSame( 404, $resp->status );
	}

	// -----------------------------------------------------------------------
	// get_results
	// -----------------------------------------------------------------------

	public function test_get_results_returns_200_with_expected_keys(): void {
		$id  = $this->create_engine_test( 'Results Topic', 85.0, 75.0 );
		$req = $this->request( [ 'id' => $id ] );

		$resp = $this->ctrl->get_results( $req );
		$data = $resp->data;

		$this->assertSame( 200, $resp->status );
		foreach ( [ 'test_id', 'topic', 'winner', 'status', 'variant_a', 'variant_b', 'relative_lift_pct', 'leading_variant' ] as $key ) {
			$this->assertArrayHasKey( $key, $data, "Missing key: {$key}" );
		}
	}

	public function test_get_results_leading_variant_correct(): void {
		$id  = $this->create_engine_test( 'Leading Topic', 90.0, 70.0 );
		$req = $this->request( [ 'id' => $id ] );

		$data = $this->ctrl->get_results( $req )->data;

		$this->assertSame( 'a', $data['leading_variant'] );
	}

	public function test_get_results_relative_lift_positive_when_b_leads(): void {
		$id  = $this->create_engine_test( 'Lift Topic', 60.0, 90.0 );
		$req = $this->request( [ 'id' => $id ] );

		$data = $this->ctrl->get_results( $req )->data;

		$this->assertGreaterThan( 0.0, $data['relative_lift_pct'] );
		$this->assertSame( 'b', $data['leading_variant'] );
	}

	public function test_get_results_returns_404_for_unknown_test(): void {
		$req  = $this->request( [ 'id' => 'ab_missing' ] );
		$resp = $this->ctrl->get_results( $req );
		$this->assertSame( 404, $resp->status );
	}

	// -----------------------------------------------------------------------
	// promote_all
	// -----------------------------------------------------------------------

	public function test_promote_all_returns_evaluated_count(): void {
		$this->create_engine_test( 'Bulk A' );
		$this->create_engine_test( 'Bulk B' );

		$req  = $this->request();
		$resp = $this->ctrl->promote_all( $req );

		$this->assertSame( 200, $resp->status );
		// These tests are young (< 7 days), so 0 promoted but 0 evaluated too.
		$this->assertArrayHasKey( 'evaluated', $resp->data );
		$this->assertArrayHasKey( 'promoted', $resp->data );
	}

	// -----------------------------------------------------------------------
	// check_permission
	// -----------------------------------------------------------------------

	public function test_check_permission_returns_true_with_valid_api_key(): void {
		update_option( 'pearblog_api_key', 'secret-key-123' );
		$GLOBALS['_is_admin'] = false; // Not a logged-in admin.

		$req = $this->request();
		$req->set_header( 'Authorization', 'Bearer secret-key-123' );

		$this->assertTrue( $this->ctrl->check_permission( $req ) );
	}

	public function test_check_permission_returns_false_with_wrong_key(): void {
		update_option( 'pearblog_api_key', 'secret-key-123' );

		$req = $this->request();
		$req->set_header( 'Authorization', 'Bearer wrong-key' );

		$this->assertFalse( $this->ctrl->check_permission( $req ) );
	}

	public function test_check_permission_returns_false_with_no_key_configured(): void {
		// No API key option, and no admin context.
		$req = $this->request();
		$this->assertFalse( $this->ctrl->check_permission( $req ) );
	}
}
