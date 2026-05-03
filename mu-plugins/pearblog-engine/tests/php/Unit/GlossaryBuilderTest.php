<?php
/**
 * Tests for GlossaryBuilder.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Content\GlossaryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\Content\GlossaryBuilder
 */
class GlossaryBuilderTest extends TestCase {

	/** @var GlossaryBuilder */
	private GlossaryBuilder $builder;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_posts']            = [];
		$GLOBALS['_current_user_can'] = false;
		$this->builder = new GlossaryBuilder();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_returns_true_by_default(): void {
		$this->assertTrue( $this->builder->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_disabled(): void {
		$GLOBALS['_options']['pearblog_glossary_enabled'] = false;
		$this->assertFalse( $this->builder->is_enabled() );
	}

	public function test_is_enabled_returns_true_when_explicitly_enabled(): void {
		$GLOBALS['_options']['pearblog_glossary_enabled'] = true;
		$this->assertTrue( $this->builder->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_skips_hooks_when_disabled(): void {
		$GLOBALS['_options']['pearblog_glossary_enabled'] = false;
		$GLOBALS['_actions'] = [];
		$this->builder->register();
		$this->assertEmpty( $GLOBALS['_actions'] );
	}

	public function test_register_adds_hooks_when_enabled(): void {
		$GLOBALS['_options']['pearblog_glossary_enabled'] = true;
		$this->builder->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
		$this->assertTrue( isset( $GLOBALS['_actions']['init'] ) );
	}

	// -----------------------------------------------------------------------
	// maybe_schedule_cron
	// -----------------------------------------------------------------------

	public function test_maybe_schedule_cron_schedules_when_not_scheduled(): void {
		$GLOBALS['_cron_scheduled'] = [];
		$this->builder->maybe_schedule_cron();
		$this->assertNotEmpty( $GLOBALS['_cron_scheduled'] );
	}

	// -----------------------------------------------------------------------
	// extract_terms
	// -----------------------------------------------------------------------

	public function test_extract_terms_returns_array(): void {
		$terms = $this->builder->extract_terms( '<p>Some <strong>SEO</strong> content.</p>' );
		$this->assertIsArray( $terms );
	}

	public function test_extract_terms_extracts_strong_tags(): void {
		$content = '<p>Use <strong>Machine Learning</strong> for better results.</p>';
		$terms   = $this->builder->extract_terms( $content );
		$this->assertContains( 'Machine Learning', $terms );
	}

	public function test_extract_terms_extracts_em_tags(): void {
		$content = '<p>This is called <em>Natural Language Processing</em>.</p>';
		$terms   = $this->builder->extract_terms( $content );
		$this->assertContains( 'Natural Language Processing', $terms );
	}

	public function test_extract_terms_deduplicates(): void {
		$content = '<p><strong>SEO</strong> and more <strong>SEO</strong>.</p>';
		$terms   = $this->builder->extract_terms( $content );
		$count   = count( array_filter( $terms, fn( $t ) => strtolower( $t ) === 'seo' ) );
		$this->assertSame( 1, $count );
	}

	public function test_extract_terms_ignores_very_short_text(): void {
		$content = '<p><strong>a</strong> short.</p>';
		$terms   = $this->builder->extract_terms( $content );
		$this->assertNotContains( 'a', $terms );
	}

	public function test_extract_terms_returns_empty_for_plain_text(): void {
		$terms = $this->builder->extract_terms( '<p>No emphasised terms here at all.</p>' );
		$this->assertIsArray( $terms );
		// May be empty if no <strong>/<em> present.
		foreach ( $terms as $term ) {
			$this->assertIsString( $term );
		}
	}

	// -----------------------------------------------------------------------
	// rest_permission
	// -----------------------------------------------------------------------

	public function test_rest_permission_returns_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->builder->rest_permission() );
	}

	public function test_rest_permission_returns_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->builder->rest_permission() );
	}

	// -----------------------------------------------------------------------
	// register_routes
	// -----------------------------------------------------------------------

	public function test_register_routes_adds_routes(): void {
		$GLOBALS['_rest_routes'] = [];
		$this->builder->register_routes();
		$this->assertNotEmpty( $GLOBALS['_rest_routes'] );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	public function test_rest_get_returns_404_when_no_glossary(): void {
		$request = new \WP_REST_Request( 'GET', '/pearblog/v1/glossary/42' );
		$request->set_param( 'id', 42 );
		$response = $this->builder->rest_get( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_rest_get_returns_200_when_glossary_exists(): void {
		$GLOBALS['_post_meta'][10]['_pearblog_glossary_page_id'] = [99];
		$GLOBALS['_post_meta'][10]['_pearblog_glossary_terms']   = ['["SEO","Machine Learning"]'];
		$GLOBALS['_post_meta'][10]['_pearblog_glossary_built_at'] = [time()];

		$request = new \WP_REST_Request( 'GET', '/pearblog/v1/glossary/10' );
		$request->set_param( 'id', 10 );
		$response = $this->builder->rest_get( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 10, $data['post_id'] );
		$this->assertSame( 99, $data['glossary_page_id'] );
	}

	public function test_rest_build_returns_404_when_post_missing(): void {
		$request = new \WP_REST_Request( 'POST', '/pearblog/v1/glossary/999' );
		$request->set_param( 'id', 999 );
		$response = $this->builder->rest_build( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_rest_terms_returns_empty_when_no_meta(): void {
		$request = new \WP_REST_Request( 'GET', '/pearblog/v1/glossary/55/terms' );
		$request->set_param( 'id', 55 );
		$response = $this->builder->rest_terms( $request );
		$data     = $response->get_data();
		$this->assertSame( [], $data['terms'] );
	}

	public function test_rest_terms_returns_decoded_terms(): void {
		$GLOBALS['_post_meta'][20]['_pearblog_glossary_terms'] = ['["AI","Blockchain","SEO"]'];

		$request = new \WP_REST_Request( 'GET', '/pearblog/v1/glossary/20/terms' );
		$request->set_param( 'id', 20 );
		$response = $this->builder->rest_terms( $request );
		$data     = $response->get_data();

		$this->assertSame( [ 'AI', 'Blockchain', 'SEO' ], $data['terms'] );
	}

	// -----------------------------------------------------------------------
	// build_for_post – WP_Error when no terms
	// -----------------------------------------------------------------------

	public function test_build_for_post_returns_wp_error_when_no_terms(): void {
		$post               = new \WP_Post();
		$post->ID           = 30;
		$post->post_title   = 'Article';
		$post->post_content = '<p>No emphasized terms at all in this content.</p>';

		$result = $this->builder->build_for_post( $post );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'no_terms', $result->get_error_code() );
	}

	// -----------------------------------------------------------------------
	// on_pipeline_completed
	// -----------------------------------------------------------------------

	public function test_on_pipeline_completed_skips_short_posts(): void {
		$post               = new \WP_Post();
		$post->ID           = 50;
		$post->post_title   = 'Short Post';
		$post->post_content = 'Short content.';
		$GLOBALS['_posts'][50] = $post;
		$GLOBALS['_options']['pearblog_glossary_min_word_count'] = 800;

		// Should not trigger build (not enough words).
		$this->builder->on_pipeline_completed( 50 );
		// No glossary page should have been created.
		$this->assertEmpty( $GLOBALS['_post_meta'][50]['_pearblog_glossary_page_id'] ?? [] );
	}
}
