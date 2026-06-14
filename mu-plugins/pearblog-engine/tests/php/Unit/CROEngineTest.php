<?php
/**
 * Unit tests for CROEngine.
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
		$GLOBALS['_rest_routes'] = [];
		$GLOBALS['_is_admin']   = false;
		$GLOBALS['_current_user_can'] = true; // admin by default in tests
		unset( $_COOKIE );
		$this->engine = new CROEngine();
	}

	// -----------------------------------------------------------------------
	// rest_permission
	// -----------------------------------------------------------------------

	public function test_rest_permission_returns_true_when_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->engine->rest_permission() );
	}

	public function test_rest_permission_returns_false_when_not_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->engine->rest_permission() );
	}

	// -----------------------------------------------------------------------
	// rest_create / rest_list
	// -----------------------------------------------------------------------

	public function test_rest_create_returns_201(): void {
		$request = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$request->set_param( 'name', 'Headline Test' );
		$request->set_param( 'variants', [ 'Buy Now', 'Get Started' ] );

		$response = $this->engine->rest_create( $request );

		$this->assertSame( 201, $response->get_status() );
	}

	public function test_rest_create_stores_experiment(): void {
		$request = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$request->set_param( 'name', 'CTA Test' );
		$request->set_param( 'variants', [ 'Click Here', 'Learn More' ] );

		$response = $this->engine->rest_create( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertSame( 'CTA Test', $data['name'] );
		$this->assertSame( [ 'Click Here', 'Learn More' ], $data['variants'] );
		$this->assertSame( 'active', $data['status'] );
		$this->assertNull( $data['winner'] );
	}

	public function test_rest_create_includes_post_id(): void {
		$request = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$request->set_param( 'name', 'Test' );
		$request->set_param( 'variants', [ 'A' ] );
		$request->set_param( 'post_id', 42 );

		$response = $this->engine->rest_create( $request );
		$data     = $response->get_data();

		$this->assertSame( 42, $data['post_id'] );
	}

	public function test_rest_list_returns_all_experiments(): void {
		// Create two experiments.
		foreach ( [ 'Exp One', 'Exp Two' ] as $name ) {
			$req = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
			$req->set_param( 'name', $name );
			$req->set_param( 'variants', [ 'A', 'B' ] );
			$this->engine->rest_create( $req );
		}

		$listReq  = new \WP_REST_Request( 'GET', '/pearblog/v1/cro/experiments' );
		$response = $this->engine->rest_list( $listReq );
		$data     = $response->get_data();

		$this->assertCount( 2, $data );
		$names = array_column( $data, 'name' );
		$this->assertContains( 'Exp One', $names );
		$this->assertContains( 'Exp Two', $names );
	}

	public function test_rest_list_includes_stats_key(): void {
		$req = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$req->set_param( 'name', 'Stats Test' );
		$req->set_param( 'variants', [ 'A', 'B' ] );
		$this->engine->rest_create( $req );

		$listReq  = new \WP_REST_Request( 'GET', '/pearblog/v1/cro/experiments' );
		$response = $this->engine->rest_list( $listReq );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'stats', $data[0] );
	}

	// -----------------------------------------------------------------------
	// rest_stats
	// -----------------------------------------------------------------------

	public function test_rest_stats_returns_404_for_unknown_experiment(): void {
		$req = new \WP_REST_Request( 'GET', '/pearblog/v1/cro/experiments/nonexistent' );
		$req->set_param( 'id', 'nonexistent' );

		$response = $this->engine->rest_stats( $req );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_rest_stats_returns_experiment_with_stats(): void {
		// Create experiment first.
		$createReq = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$createReq->set_param( 'name', 'My Exp' );
		$createReq->set_param( 'variants', [ 'V1', 'V2' ] );
		$created = $this->engine->rest_create( $createReq );
		$id      = $created->get_data()['id'];

		$req = new \WP_REST_Request( 'GET', "/pearblog/v1/cro/experiments/{$id}" );
		$req->set_param( 'id', $id );

		$response = $this->engine->rest_stats( $req );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $id, $data['id'] );
		$this->assertArrayHasKey( 'stats', $data );
	}

	// -----------------------------------------------------------------------
	// rest_delete
	// -----------------------------------------------------------------------

	public function test_rest_delete_returns_404_for_unknown(): void {
		$req = new \WP_REST_Request( 'DELETE', '/pearblog/v1/cro/experiments/ghost' );
		$req->set_param( 'id', 'ghost' );

		$response = $this->engine->rest_delete( $req );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_rest_delete_removes_experiment(): void {
		$createReq = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$createReq->set_param( 'name', 'To Delete' );
		$createReq->set_param( 'variants', [ 'X' ] );
		$created = $this->engine->rest_create( $createReq );
		$id      = $created->get_data()['id'];

		$delReq = new \WP_REST_Request( 'DELETE', "/pearblog/v1/cro/experiments/{$id}" );
		$delReq->set_param( 'id', $id );
		$response = $this->engine->rest_delete( $delReq );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['deleted'] );

		// Verify the experiment is gone.
		$listReq  = new \WP_REST_Request( 'GET', '/pearblog/v1/cro/experiments' );
		$listData = $this->engine->rest_list( $listReq )->get_data();
		$this->assertEmpty( $listData );
	}

	// -----------------------------------------------------------------------
	// rest_track / record_event
	// -----------------------------------------------------------------------

	public function test_rest_track_impression_returns_200(): void {
		$req = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/track' );
		$req->set_param( 'experiment_id', 'exp123' );
		$req->set_param( 'variant', '0' );
		$req->set_param( 'event', 'impression' );

		$response = $this->engine->rest_track( $req );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['recorded'] );
	}

	public function test_rest_track_accumulates_impressions(): void {
		$expId = 'test_exp_' . uniqid();

		for ( $i = 0; $i < 3; $i++ ) {
			$req = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/track' );
			$req->set_param( 'experiment_id', $expId );
			$req->set_param( 'variant', 'ctrl' );
			$req->set_param( 'event', 'impression' );
			$this->engine->rest_track( $req );
		}

		$stats = get_option( "pearblog_cro_stats_{$expId}", [] );
		$this->assertSame( 3, $stats['ctrl']['impressions'] );
	}

	public function test_rest_track_conversion_increments_conversion_count(): void {
		$expId = 'conv_exp_' . uniqid();

		$req = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/track' );
		$req->set_param( 'experiment_id', $expId );
		$req->set_param( 'variant', 'var_a' );
		$req->set_param( 'event', 'impression' );
		$this->engine->rest_track( $req );

		$req = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/track' );
		$req->set_param( 'experiment_id', $expId );
		$req->set_param( 'variant', 'var_a' );
		$req->set_param( 'event', 'conversion' );
		$this->engine->rest_track( $req );

		$stats = get_option( "pearblog_cro_stats_{$expId}", [] );
		$this->assertSame( 1, $stats['var_a']['impressions'] );
		$this->assertSame( 1, $stats['var_a']['conversions'] );
		$this->assertSame( 100.0, $stats['var_a']['cvr'] );
	}

	public function test_cvr_is_zero_when_no_impressions(): void {
		$expId = 'zero_' . uniqid();

		$req = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/track' );
		$req->set_param( 'experiment_id', $expId );
		$req->set_param( 'variant', 'a' );
		$req->set_param( 'event', 'conversion' );
		$this->engine->rest_track( $req );

		$stats = get_option( "pearblog_cro_stats_{$expId}", [] );
		$this->assertSame( 0.0, $stats['a']['cvr'] );
	}

	// -----------------------------------------------------------------------
	// evaluate_and_promote
	// -----------------------------------------------------------------------

	public function test_evaluate_and_promote_no_winner_when_insufficient_impressions(): void {
		$createReq = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$createReq->set_param( 'name', 'Low Traffic' );
		$createReq->set_param( 'variants', [ 'A', 'B' ] );
		$created = $this->engine->rest_create( $createReq );
		$id      = $created->get_data()['id'];

		// Only 5 impressions — not enough for significance.
		update_option( "pearblog_cro_stats_{$id}", [
			'0' => [ 'impressions' => 5, 'conversions' => 2, 'cvr' => 40.0 ],
			'1' => [ 'impressions' => 5, 'conversions' => 1, 'cvr' => 20.0 ],
		] );

		$this->engine->evaluate_and_promote();

		$experiments = get_option( 'pearblog_cro_experiments', [] );
		$this->assertSame( 'active', $experiments[ $id ]['status'] );
	}

	public function test_evaluate_and_promote_declares_winner_when_significant(): void {
		// Create experiment with two variants.
		$createReq = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$createReq->set_param( 'name', 'High Traffic' );
		$createReq->set_param( 'variants', [ 'Control', 'Challenger' ] );
		$created = $this->engine->rest_create( $createReq );
		$id      = $created->get_data()['id'];

		// Control: 500 impressions, 5% CVR.
		// Challenger: 500 impressions, 30% CVR → z-score >> 1.96 → significant.
		update_option( "pearblog_cro_stats_{$id}", [
			'0' => [ 'impressions' => 500, 'conversions' => 25,  'cvr' => 5.0  ],
			'1' => [ 'impressions' => 500, 'conversions' => 150, 'cvr' => 30.0 ],
		] );

		$this->engine->evaluate_and_promote();

		$experiments = get_option( 'pearblog_cro_experiments', [] );
		$this->assertSame( 'completed', $experiments[ $id ]['status'] );
		$this->assertSame( 'Challenger', $experiments[ $id ]['winner'] );
	}

	public function test_evaluate_and_promote_skips_completed_experiments(): void {
		$createReq = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$createReq->set_param( 'name', 'Already Done' );
		$createReq->set_param( 'variants', [ 'A', 'B' ] );
		$created = $this->engine->rest_create( $createReq );
		$id      = $created->get_data()['id'];

		// Mark as already completed.
		$experiments          = get_option( 'pearblog_cro_experiments', [] );
		$experiments[ $id ]['status'] = 'completed';
		$experiments[ $id ]['winner'] = 'A';
		update_option( 'pearblog_cro_experiments', $experiments );

		// Provide stats that would normally trigger a new winner.
		update_option( "pearblog_cro_stats_{$id}", [
			'0' => [ 'impressions' => 600, 'conversions' => 300, 'cvr' => 50.0 ],
			'1' => [ 'impressions' => 600, 'conversions' => 50,  'cvr' => 8.0  ],
		] );

		$this->engine->evaluate_and_promote();

		// Status must remain 'completed' — no re-evaluation.
		$after = get_option( 'pearblog_cro_experiments', [] );
		$this->assertSame( 'completed', $after[ $id ]['status'] );
	}

	// -----------------------------------------------------------------------
	// inject_cta_variants
	// -----------------------------------------------------------------------

	public function test_inject_cta_variants_passes_through_content_with_no_placeholder(): void {
		$content = '<p>Regular content with no placeholder.</p>';

		$result = $this->engine->inject_cta_variants( $content );

		$this->assertSame( $content, $result );
	}

	public function test_inject_cta_variants_replaces_placeholder_with_winner_html(): void {
		// Create a completed experiment with a winner.
		$createReq = new \WP_REST_Request( 'POST', '/pearblog/v1/cro/experiments' );
		$createReq->set_param( 'name', 'Inject Test' );
		$createReq->set_param( 'variants', [ '<button>Subscribe</button>', '<a href="#">Join</a>' ] );
		$created = $this->engine->rest_create( $createReq );
		$id      = $created->get_data()['id'];

		// Mark as completed with a winner.
		$experiments          = get_option( 'pearblog_cro_experiments', [] );
		$experiments[ $id ]['status'] = 'completed';
		$experiments[ $id ]['winner'] = '<button>Subscribe</button>';
		update_option( 'pearblog_cro_experiments', $experiments );

		$content = "<p>Some article content.</p><!-- cro:{$id} --><p>Footer.</p>";

		$result = $this->engine->inject_cta_variants( $content );

		$this->assertStringContainsString( '<button>Subscribe</button>', $result );
		$this->assertStringNotContainsString( "<!-- cro:{$id} -->", $result );
	}

	public function test_inject_cta_variants_skips_in_admin(): void {
		$GLOBALS['_is_admin'] = true;

		$content = '<p>Admin area content.</p>';
		$result  = $this->engine->inject_cta_variants( $content );

		$this->assertSame( $content, $result );
	}
}
