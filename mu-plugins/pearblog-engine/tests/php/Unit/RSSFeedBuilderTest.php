<?php
/**
 * Unit tests for RSSFeedBuilder.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Distribution\RSSFeedBuilder;

class RSSFeedBuilderTest extends TestCase {

	private RSSFeedBuilder $builder;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']      = [ 'pearblog_rss_enabled' => true ];
		$GLOBALS['_actions']      = [];
		$GLOBALS['_filters']      = [];
		$GLOBALS['_rewrite_rules'] = [];
		$GLOBALS['_post_list']    = [];
		$this->builder = new RSSFeedBuilder();
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_defaults_to_true(): void {
		$GLOBALS['_options'] = [];
		$b = new RSSFeedBuilder();
		$this->assertTrue( $b->is_enabled() );
	}

	public function test_is_enabled_respects_option(): void {
		$GLOBALS['_options']['pearblog_rss_enabled'] = false;
		$b = new RSSFeedBuilder();
		$this->assertFalse( $b->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// register()
	// -----------------------------------------------------------------------

	public function test_register_adds_init_action(): void {
		$this->builder->register();
		$this->assertNotEmpty( $GLOBALS['_actions']['init'] ?? [] );
	}

	public function test_register_adds_template_redirect_action(): void {
		$this->builder->register();
		$this->assertNotEmpty( $GLOBALS['_actions']['template_redirect'] ?? [] );
	}

	public function test_register_adds_rest_api_init_action(): void {
		$this->builder->register();
		$this->assertNotEmpty( $GLOBALS['_actions']['rest_api_init'] ?? [] );
	}

	public function test_register_disabled_skips_hooks(): void {
		$GLOBALS['_options']['pearblog_rss_enabled'] = false;
		$b = new RSSFeedBuilder();
		$b->register();
		$this->assertEmpty( $GLOBALS['_actions']['init'] ?? [] );
	}

	// -----------------------------------------------------------------------
	// register_rewrites
	// -----------------------------------------------------------------------

	public function test_register_rewrites_adds_rewrite_rules(): void {
		$this->builder->register_rewrites();
		$this->assertGreaterThanOrEqual( 3, count( $GLOBALS['_rewrite_rules'] ) );
	}

	public function test_rewrite_rules_cover_main_feed(): void {
		$this->builder->register_rewrites();
		$patterns = array_column( $GLOBALS['_rewrite_rules'], 0 );
		$has_main = false;
		foreach ( $patterns as $p ) {
			if ( str_contains( $p, 'pearblog-feed' ) ) {
				$has_main = true;
				break;
			}
		}
		$this->assertTrue( $has_main );
	}

	public function test_rewrite_rules_cover_podcast_feed(): void {
		$this->builder->register_rewrites();
		$patterns = array_column( $GLOBALS['_rewrite_rules'], 0 );
		$has_podcast = false;
		foreach ( $patterns as $p ) {
			if ( str_contains( $p, 'podcast' ) ) {
				$has_podcast = true;
				break;
			}
		}
		$this->assertTrue( $has_podcast );
	}

	// -----------------------------------------------------------------------
	// output_feed
	// -----------------------------------------------------------------------

	public function test_output_feed_produces_rss_root_element(): void {
		ob_start();
		$this->builder->output_feed( [], 'Test Site', 'Test Description' );
		$xml = ob_get_clean();
		$this->assertStringContainsString( '<rss', $xml );
		$this->assertStringContainsString( 'version="2.0"', $xml );
	}

	public function test_output_feed_contains_channel_title(): void {
		ob_start();
		$this->builder->output_feed( [], 'My Blog', 'A great blog' );
		$xml = ob_get_clean();
		$this->assertStringContainsString( '<title>My Blog</title>', $xml );
	}

	public function test_output_feed_contains_media_namespace(): void {
		ob_start();
		$this->builder->output_feed( [], 'Test', 'Desc' );
		$xml = ob_get_clean();
		$this->assertStringContainsString( 'xmlns:media', $xml );
	}

	public function test_output_feed_contains_dc_namespace(): void {
		ob_start();
		$this->builder->output_feed( [], 'Test', 'Desc' );
		$xml = ob_get_clean();
		$this->assertStringContainsString( 'xmlns:dc', $xml );
	}

	public function test_output_feed_renders_post_items(): void {
		$post = $this->make_post( 1, 'First post' );
		ob_start();
		$this->builder->output_feed( [ $post ], 'Blog', 'Desc' );
		$xml = ob_get_clean();
		$this->assertStringContainsString( 'First post', $xml );
		$this->assertStringContainsString( '<item>', $xml );
	}

	public function test_output_feed_escapes_special_chars_in_title(): void {
		$post = $this->make_post( 2, 'Some content' );
		$post->post_title = 'Test <script>alert(1)</script>';
		ob_start();
		$this->builder->output_feed( [ $post ], 'Blog', 'Desc' );
		$xml = ob_get_clean();
		$this->assertStringNotContainsString( '<script>', $xml );
	}

	// -----------------------------------------------------------------------
	// output_podcast_feed
	// -----------------------------------------------------------------------

	public function test_output_podcast_feed_contains_itunes_namespace(): void {
		ob_start();
		$this->builder->output_podcast_feed( [] );
		$xml = ob_get_clean();
		$this->assertStringContainsString( 'xmlns:itunes', $xml );
	}

	public function test_output_podcast_feed_renders_podcast_channel(): void {
		ob_start();
		$this->builder->output_podcast_feed( [] );
		$xml = ob_get_clean();
		$this->assertStringContainsString( 'Podcast', $xml );
		$this->assertStringContainsString( '<itunes:explicit>false</itunes:explicit>', $xml );
	}

	// -----------------------------------------------------------------------
	// REST permission
	// -----------------------------------------------------------------------

	public function test_rest_permission_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->builder->rest_permission() );
	}

	public function test_rest_permission_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->builder->rest_permission() );
	}

	// -----------------------------------------------------------------------
	// REST status
	// -----------------------------------------------------------------------

	public function test_rest_status_returns_200(): void {
		$GLOBALS['_current_user_can'] = true;
		$request  = new \WP_REST_Request();
		$response = $this->builder->rest_status( $request );
		$this->assertSame( 200, $response->status );
	}

	public function test_rest_status_contains_main_feed(): void {
		$GLOBALS['_current_user_can'] = true;
		$request  = new \WP_REST_Request();
		$response = $this->builder->rest_status( $request );
		$this->assertArrayHasKey( 'main', $response->data['feeds'] );
	}

	public function test_rest_status_includes_podcast_when_enabled(): void {
		$GLOBALS['_current_user_can'] = true;
		$GLOBALS['_options']['pearblog_rss_include_podcast'] = true;

		$request  = new \WP_REST_Request();
		$response = $this->builder->rest_status( $request );
		$this->assertArrayHasKey( 'podcast', $response->data['feeds'] );
	}

	public function test_rest_status_excludes_podcast_when_disabled(): void {
		$GLOBALS['_current_user_can'] = true;
		$GLOBALS['_options']['pearblog_rss_include_podcast'] = false;

		$request  = new \WP_REST_Request();
		$response = $this->builder->rest_status( $request );
		$this->assertArrayNotHasKey( 'podcast', $response->data['feeds'] );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function make_post( int $id, string $title = 'Post Title' ): \WP_Post {
		return new \WP_Post( [
			'ID'           => $id,
			'post_title'   => $title,
			'post_content' => 'Post content for feed test.',
			'post_excerpt' => '',
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_author'  => 1,
			'post_date'    => gmdate( 'Y-m-d H:i:s' ),
		] );
	}
}
