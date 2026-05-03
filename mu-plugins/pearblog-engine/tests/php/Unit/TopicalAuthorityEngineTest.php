<?php
/**
 * Tests for TopicalAuthorityEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\SEO\TopicalAuthorityEngine;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\SEO\TopicalAuthorityEngine
 */
class TopicalAuthorityEngineTest extends TestCase {

	/** @var TopicalAuthorityEngine */
	private TopicalAuthorityEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_current_user_can'] = false;
		$this->engine = new TopicalAuthorityEngine();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options'] = [];
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_constants_are_defined(): void {
		$this->assertSame( 'pearblog_topical_silos', TopicalAuthorityEngine::OPTION_SILOS );
		$this->assertSame( 'pearblog_topical_config', TopicalAuthorityEngine::OPTION_CONFIG );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_hooks(): void {
		$this->engine->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['init'] ) );
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}

	// -----------------------------------------------------------------------
	// maybe_schedule
	// -----------------------------------------------------------------------

	public function test_maybe_schedule_schedules_cron_when_not_scheduled(): void {
		$GLOBALS['_cron_scheduled'] = [];
		$this->engine->maybe_schedule();
		$this->assertNotEmpty( $GLOBALS['_cron_scheduled'] );
	}

	// -----------------------------------------------------------------------
	// get_silos
	// -----------------------------------------------------------------------

	public function test_get_silos_returns_empty_array_initially(): void {
		$silos = $this->engine->get_silos();
		$this->assertSame( [], $silos );
	}

	public function test_get_silos_returns_stored_option(): void {
		$stored = [ 'keyword' => [ 'pillar' => [], 'clusters' => [], 'missing_clusters' => [], 'coverage_score' => 0 ] ];
		$GLOBALS['_options'][ TopicalAuthorityEngine::OPTION_SILOS ] = $stored;
		$this->assertSame( $stored, $this->engine->get_silos() );
	}

	public function test_get_silos_returns_empty_array_for_invalid_stored_value(): void {
		$GLOBALS['_options'][ TopicalAuthorityEngine::OPTION_SILOS ] = 'not-an-array';
		$this->assertSame( [], $this->engine->get_silos() );
	}

	// -----------------------------------------------------------------------
	// build
	// -----------------------------------------------------------------------

	public function test_build_returns_array(): void {
		$GLOBALS['_posts'] = [];
		$silos = $this->engine->build();
		$this->assertIsArray( $silos );
	}

	public function test_build_returns_empty_when_no_posts(): void {
		$GLOBALS['_posts'] = [];
		$this->assertSame( [], $this->engine->build() );
	}

	// -----------------------------------------------------------------------
	// rebuild
	// -----------------------------------------------------------------------

	public function test_rebuild_persists_silos(): void {
		$GLOBALS['_posts'] = [];
		$this->engine->rebuild();
		$this->assertIsArray( $GLOBALS['_options'][ TopicalAuthorityEngine::OPTION_SILOS ] ?? null );
	}

	// -----------------------------------------------------------------------
	// on_article_published
	// -----------------------------------------------------------------------

	public function test_on_article_published_skips_rebuild_within_hour(): void {
		$GLOBALS['_options']['pearblog_topical_last_rebuild'] = time() - 100; // 100s ago
		$GLOBALS['_posts'] = [];

		// If rebuild IS called, it sets the option again; we track if it changed.
		$before = $GLOBALS['_options']['pearblog_topical_last_rebuild'];
		$this->engine->on_article_published( 1, 'test topic' );
		$after = $GLOBALS['_options']['pearblog_topical_last_rebuild'] ?? $before;

		// Should NOT have changed because debounce is active.
		$this->assertSame( $before, $after );
	}

	public function test_on_article_published_triggers_rebuild_after_hour(): void {
		$GLOBALS['_options']['pearblog_topical_last_rebuild'] = time() - 3700; // >1h ago
		$GLOBALS['_posts'] = [];

		$this->engine->on_article_published( 1, 'test topic' );

		// last_rebuild timestamp should have been updated.
		$updated = $GLOBALS['_options']['pearblog_topical_last_rebuild'];
		$this->assertGreaterThan( time() - 5, $updated );
	}

	// -----------------------------------------------------------------------
	// admin_permission
	// -----------------------------------------------------------------------

	public function test_admin_permission_returns_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->engine->admin_permission() );
	}

	public function test_admin_permission_returns_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->engine->admin_permission() );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	public function test_rest_get_silos_returns_response(): void {
		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/seo/silos' );
		$response = $this->engine->rest_get_silos( $request );
		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'silos', $data );
		$this->assertArrayHasKey( 'silo_count', $data );
	}

	public function test_rest_build_silos_returns_success(): void {
		$GLOBALS['_posts'] = [];
		$request  = new \WP_REST_Request( 'POST', '/pearblog/v1/seo/silos/build' );
		$response = $this->engine->rest_build_silos( $request );
		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
	}

	public function test_rest_get_silos_silo_count_matches_silos_array(): void {
		$stored = [
			'pillar1' => [ 'pillar' => [], 'clusters' => [], 'missing_clusters' => [], 'coverage_score' => 50 ],
			'pillar2' => [ 'pillar' => [], 'clusters' => [], 'missing_clusters' => [], 'coverage_score' => 30 ],
		];
		$GLOBALS['_options'][ TopicalAuthorityEngine::OPTION_SILOS ] = $stored;

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/seo/silos' );
		$response = $this->engine->rest_get_silos( $request );
		$data     = $response->get_data();

		$this->assertSame( 2, $data['silo_count'] );
	}

	// -----------------------------------------------------------------------
	// register_routes
	// -----------------------------------------------------------------------

	public function test_register_routes_registers_routes(): void {
		$GLOBALS['_rest_routes'] = [];
		$this->engine->register_routes();
		$this->assertNotEmpty( $GLOBALS['_rest_routes'] );
	}
}
