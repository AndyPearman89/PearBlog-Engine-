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
		$GLOBALS['_actions']    = [];
		$GLOBALS['_scheduled']  = [];
		$this->engine = new CROEngine();
	}

	// -----------------------------------------------------------------------
	// rest_list — no experiments
	// -----------------------------------------------------------------------

	public function test_rest_list_returns_empty_array_initially(): void {
		$request  = $this->createMock( \WP_REST_Request::class );
		$response = $this->engine->rest_list( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( [], $response->get_data() );
		$this->assertSame( 200, $response->get_status() );
	}

	// -----------------------------------------------------------------------
	// rest_create
	// -----------------------------------------------------------------------

	public function test_rest_create_returns_201(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturnMap( [
			[ 'name',     'CTA Test' ],
			[ 'variants', [ 'Buy now', 'Get started' ] ],
			[ 'post_id',  0 ],
		] );

		$response = $this->engine->rest_create( $request );

		$this->assertSame( 201, $response->get_status() );
	}

	public function test_rest_create_returns_experiment_with_id(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturnMap( [
			[ 'name',     'Button Test' ],
			[ 'variants', [ 'Click here', 'Learn more' ] ],
			[ 'post_id',  0 ],
		] );

		$data = $this->engine->rest_create( $request )->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertStringStartsWith( 'cro_', $data['id'] );
		$this->assertSame( 'active', $data['status'] );
		$this->assertNull( $data['winner'] );
	}

	public function test_rest_create_persists_experiment(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturnMap( [
			[ 'name',     'Headline Test' ],
			[ 'variants', [ 'Headline A', 'Headline B' ] ],
			[ 'post_id',  42 ],
		] );

		$this->engine->rest_create( $request );

		$listRequest  = $this->createMock( \WP_REST_Request::class );
		$experiments  = $this->engine->rest_list( $listRequest )->get_data();

		$this->assertCount( 1, $experiments );
		$this->assertSame( 'Headline Test', $experiments[0]['name'] );
	}

	// -----------------------------------------------------------------------
	// rest_delete
	// -----------------------------------------------------------------------

	public function test_rest_delete_returns_404_for_unknown_experiment(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturn( 'nonexistent_id' );

		$response = $this->engine->rest_delete( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_rest_delete_removes_experiment(): void {
		// Create one.
		$createReq = $this->createMock( \WP_REST_Request::class );
		$createReq->method( 'get_param' )->willReturnMap( [
			[ 'name',     'Delete Me' ],
			[ 'variants', [ 'A', 'B' ] ],
			[ 'post_id',  0 ],
		] );
		$data = $this->engine->rest_create( $createReq )->get_data();

		// Delete it.
		$deleteReq = $this->createMock( \WP_REST_Request::class );
		$deleteReq->method( 'get_param' )->willReturn( $data['id'] );
		$deleteResp = $this->engine->rest_delete( $deleteReq );

		$this->assertSame( 200, $deleteResp->get_status() );
		$this->assertTrue( $deleteResp->get_data()['deleted'] );
	}

	// -----------------------------------------------------------------------
	// rest_stats
	// -----------------------------------------------------------------------

	public function test_rest_stats_returns_404_for_unknown(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturn( 'bad_id' );

		$response = $this->engine->rest_stats( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_rest_stats_returns_experiment_with_stats(): void {
		$createReq = $this->createMock( \WP_REST_Request::class );
		$createReq->method( 'get_param' )->willReturnMap( [
			[ 'name',     'Stats Test' ],
			[ 'variants', [ 'A', 'B' ] ],
			[ 'post_id',  0 ],
		] );
		$exp = $this->engine->rest_create( $createReq )->get_data();

		$statsReq = $this->createMock( \WP_REST_Request::class );
		$statsReq->method( 'get_param' )->willReturn( $exp['id'] );
		$data = $this->engine->rest_stats( $statsReq )->get_data();

		$this->assertArrayHasKey( 'stats', $data );
	}

	// -----------------------------------------------------------------------
	// rest_track
	// -----------------------------------------------------------------------

	public function test_rest_track_returns_recorded_true(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturnMap( [
			[ 'experiment_id', 'cro_test' ],
			[ 'variant',       'a' ],
			[ 'event',         'impression' ],
		] );

		$data = $this->engine->rest_track( $request )->get_data();

		$this->assertTrue( $data['recorded'] );
	}

	// -----------------------------------------------------------------------
	// inject_cta_variants
	// -----------------------------------------------------------------------

	public function test_inject_cta_variants_returns_content_unchanged_in_admin(): void {
		$GLOBALS['_is_admin'] = true;
		$content = '<p>Some content <!-- cro:test123 --></p>';

		$result = $this->engine->inject_cta_variants( $content );

		$this->assertSame( $content, $result );
		$GLOBALS['_is_admin'] = false;
	}

	public function test_inject_cta_variants_returns_string(): void {
		$result = $this->engine->inject_cta_variants( '<p>Test content</p>' );

		$this->assertIsString( $result );
	}

	// -----------------------------------------------------------------------
	// evaluate_and_promote
	// -----------------------------------------------------------------------

	public function test_evaluate_and_promote_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->engine->evaluate_and_promote();
	}

	// -----------------------------------------------------------------------
	// rest_permission
	// -----------------------------------------------------------------------

	public function test_rest_permission_returns_bool(): void {
		$this->assertIsBool( $this->engine->rest_permission() );
	}
}
