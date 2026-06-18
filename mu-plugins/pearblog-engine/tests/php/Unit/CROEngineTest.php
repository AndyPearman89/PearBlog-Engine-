<?php
/**
 * Unit tests for CROEngine (Conversion Rate Optimisation).
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Monetization\CROEngine;

class CROEngineTest extends TestCase {

	private CROEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_user_can']   = true;
		$this->engine = new CROEngine();
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_rest_api_init_action(): void {
		$this->engine->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}

	public function test_register_adds_content_filter(): void {
		$this->engine->register();
		$this->assertTrue( isset( $GLOBALS['_filters']['the_content'] ) );
	}

	// -----------------------------------------------------------------------
	// inject_cta_variants
	// -----------------------------------------------------------------------

	public function test_inject_cta_variants_returns_content_unchanged_when_admin(): void {
		$GLOBALS['_is_admin'] = true;
		$content = '<p>Hello <!-- cro:test123 --> world</p>';
		$result  = $this->engine->inject_cta_variants( $content );
		$this->assertSame( $content, $result );
		$GLOBALS['_is_admin'] = false;
	}

	public function test_inject_cta_variants_skips_experiments_without_matching_placeholder(): void {
		$GLOBALS['_is_admin'] = false;
		$GLOBALS['_options']['pearblog_cro_experiments'] = [
			'exp1' => [ 'id' => 'exp1', 'status' => 'active', 'variants' => [ 'CTA A' ], 'winner' => null ],
		];
		$content = '<p>No placeholder here</p>';
		$result  = $this->engine->inject_cta_variants( $content );
		$this->assertStringNotContainsString( 'CTA A', $result );
	}

	public function test_inject_cta_variants_replaces_placeholder_with_variant(): void {
		$GLOBALS['_is_admin'] = false;
		$GLOBALS['_options']['pearblog_cro_experiments'] = [
			'exp1' => [ 'id' => 'exp1', 'status' => 'completed', 'variants' => [ 'Click Here' ], 'winner' => 'Click Here' ],
		];
		$content = '<p><!-- cro:exp1 --></p>';
		$result  = $this->engine->inject_cta_variants( $content );
		$this->assertStringContainsString( 'Click Here', $result );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	public function test_rest_permission_returns_true_for_admin(): void {
		$GLOBALS['_user_can'] = true;
		$this->assertTrue( $this->engine->rest_permission() );
	}

	public function test_rest_permission_returns_false_for_non_admin(): void {
		$GLOBALS['_user_can'] = false;
		$this->assertFalse( $this->engine->rest_permission() );
	}

	public function test_rest_list_returns_empty_when_no_experiments(): void {
		$req    = new \WP_REST_Request();
		$result = $this->engine->rest_list( $req );
		$this->assertSame( [], $result->get_data() );
		$this->assertSame( 200, $result->get_status() );
	}

	public function test_rest_create_creates_experiment_and_returns_201(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'name', 'My Test' );
		$req->set_param( 'variants', [ 'A', 'B' ] );
		$result = $this->engine->rest_create( $req );
		$this->assertSame( 201, $result->get_status() );
		$data = $result->get_data();
		$this->assertSame( 'My Test', $data['name'] );
		$this->assertSame( 'active', $data['status'] );
	}

	public function test_rest_create_stores_experiment_in_option(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'name', 'Stored Test' );
		$req->set_param( 'variants', [ 'X' ] );
		$this->engine->rest_create( $req );
		$stored = $GLOBALS['_options']['pearblog_cro_experiments'];
		$this->assertCount( 1, $stored );
	}

	public function test_rest_stats_returns_404_when_experiment_not_found(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'id', 'nonexistent' );
		$result = $this->engine->rest_stats( $req );
		$this->assertSame( 404, $result->get_status() );
	}

	public function test_rest_stats_returns_200_for_existing_experiment(): void {
		$GLOBALS['_options']['pearblog_cro_experiments'] = [
			'exp1' => [ 'id' => 'exp1', 'name' => 'Test', 'variants' => [], 'status' => 'active', 'winner' => null ],
		];
		$req = new \WP_REST_Request();
		$req->set_param( 'id', 'exp1' );
		$result = $this->engine->rest_stats( $req );
		$this->assertSame( 200, $result->get_status() );
	}

	public function test_rest_delete_returns_404_for_missing_experiment(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'id', 'missing_id' );
		$result = $this->engine->rest_delete( $req );
		$this->assertSame( 404, $result->get_status() );
	}

	public function test_rest_delete_removes_experiment(): void {
		$GLOBALS['_options']['pearblog_cro_experiments'] = [
			'exp1' => [ 'id' => 'exp1', 'name' => 'Test', 'variants' => [], 'status' => 'active', 'winner' => null ],
		];
		$req = new \WP_REST_Request();
		$req->set_param( 'id', 'exp1' );
		$result = $this->engine->rest_delete( $req );
		$this->assertSame( 200, $result->get_status() );
		$stored = $GLOBALS['_options']['pearblog_cro_experiments'];
		$this->assertArrayNotHasKey( 'exp1', $stored );
	}

	public function test_rest_track_records_event(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'experiment_id', 'exp1' );
		$req->set_param( 'variant', 'v_a' );
		$req->set_param( 'event', 'impression' );
		$result = $this->engine->rest_track( $req );
		$this->assertSame( 200, $result->get_status() );
		$data = $result->get_data();
		$this->assertTrue( $data['recorded'] );
	}

	// -----------------------------------------------------------------------
	// evaluate_and_promote
	// -----------------------------------------------------------------------

	public function test_evaluate_and_promote_skips_when_no_experiments(): void {
		$this->engine->evaluate_and_promote();
		// Should complete without errors.
		$this->assertTrue( true );
	}

	public function test_evaluate_and_promote_skips_completed_experiments(): void {
		$GLOBALS['_options']['pearblog_cro_experiments'] = [
			'exp1' => [ 'id' => 'exp1', 'status' => 'completed', 'winner' => 'A', 'variants' => [ 'A' ] ],
		];
		$this->engine->evaluate_and_promote();
		$stored = $GLOBALS['_options']['pearblog_cro_experiments'];
		$this->assertSame( 'completed', $stored['exp1']['status'] );
	}

	public function test_evaluate_and_promote_does_not_promote_with_insufficient_impressions(): void {
		$GLOBALS['_options']['pearblog_cro_experiments'] = [
			'exp1' => [ 'id' => 'exp1', 'status' => 'active', 'winner' => null, 'variants' => [ 'A', 'B' ] ],
		];
		// Store stats with too few impressions.
		$GLOBALS['_options']['pearblog_cro_stats_exp1'] = [
			'0' => [ 'impressions' => 5, 'conversions' => 2, 'cvr' => 40.0 ],
			'1' => [ 'impressions' => 5, 'conversions' => 1, 'cvr' => 20.0 ],
		];
		$this->engine->evaluate_and_promote();
		$stored = $GLOBALS['_options']['pearblog_cro_experiments'];
		$this->assertSame( 'active', $stored['exp1']['status'] );
	}

	public function test_experiment_initial_status_is_active(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'name', 'Status Test' );
		$req->set_param( 'variants', [ 'A', 'B' ] );
		$result = $this->engine->rest_create( $req );
		$data   = $result->get_data();
		$this->assertSame( 'active', $data['status'] );
		$this->assertNull( $data['winner'] );
	}

	public function test_rest_create_includes_created_at(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'name', 'Timestamp Test' );
		$req->set_param( 'variants', [ 'A' ] );
		$result = $this->engine->rest_create( $req );
		$data   = $result->get_data();
		$this->assertArrayHasKey( 'created_at', $data );
		$this->assertGreaterThan( 0, $data['created_at'] );
	}
}
