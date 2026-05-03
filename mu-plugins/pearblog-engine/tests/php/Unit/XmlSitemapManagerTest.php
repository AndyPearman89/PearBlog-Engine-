<?php
/**
 * Unit tests for XmlSitemapManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\SEO\XmlSitemapManager;

class XmlSitemapManagerTest extends TestCase {

	private XmlSitemapManager $sitemap;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']  = [
			'pearblog_sitemap_enabled'        => true,
			'pearblog_sitemap_posts_per_page' => 500,
			'pearblog_sitemap_include_images' => true,
			'pearblog_sitemap_include_video'  => true,
			'pearblog_sitemap_include_news'   => false,
			'pearblog_sitemap_ping_google'    => true,
			'pearblog_sitemap_ping_bing'      => true,
		];
		$GLOBALS['_actions']  = [];
		$GLOBALS['_filters']  = [];
		$GLOBALS['_rewrite_rules'] = [];
		$this->sitemap = new XmlSitemapManager();
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// register() hooks
	// -----------------------------------------------------------------------

	public function test_register_adds_init_action(): void {
		$this->sitemap->register();
		$this->assertNotEmpty( $GLOBALS['_actions']['init'] ?? [] );
	}

	public function test_register_disabled_skips_hooks(): void {
		$GLOBALS['_options']['pearblog_sitemap_enabled'] = false;
		$sitemap = new XmlSitemapManager();
		$sitemap->register();
		$this->assertArrayNotHasKey( 'init', $GLOBALS['_actions'] );
	}

	// -----------------------------------------------------------------------
	// REST permission
	// -----------------------------------------------------------------------

	public function test_rest_permission_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->sitemap->rest_permission() );
	}

	public function test_rest_permission_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->sitemap->rest_permission() );
	}

	// -----------------------------------------------------------------------
	// REST status
	// -----------------------------------------------------------------------

	public function test_rest_status_returns_expected_structure(): void {
		$GLOBALS['_current_user_can'] = true;
		$request  = new \WP_REST_Request();
		$response = $this->sitemap->rest_status( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$data = $response->data;
		$this->assertArrayHasKey( 'post_count', $data );
		$this->assertArrayHasKey( 'pages', $data );
		$this->assertArrayHasKey( 'sitemaps', $data );
		$this->assertIsArray( $data['sitemaps'] );
	}

	public function test_rest_status_includes_image_sitemap_when_enabled(): void {
		$GLOBALS['_options']['pearblog_sitemap_include_images'] = true;
		$request  = new \WP_REST_Request();
		$response = $this->sitemap->rest_status( $request );
		$urls     = $response->data['sitemaps'];
		$has_image = array_filter( $urls, fn( $u ) => str_contains( $u, 'images' ) );
		$this->assertNotEmpty( $has_image );
	}

	public function test_rest_status_excludes_news_sitemap_when_disabled(): void {
		$GLOBALS['_options']['pearblog_sitemap_include_news'] = false;
		$request  = new \WP_REST_Request();
		$response = $this->sitemap->rest_status( $request );
		$urls     = $response->data['sitemaps'];
		$has_news = array_filter( $urls, fn( $u ) => str_contains( $u, 'news' ) );
		$this->assertEmpty( $has_news );
	}

	// -----------------------------------------------------------------------
	// robots.txt injection
	// -----------------------------------------------------------------------

	public function test_inject_sitemap_in_robots_appends_sitemap_directive(): void {
		$output = "User-agent: *\nDisallow: /wp-admin/\n";
		$result = $this->sitemap->inject_sitemap_in_robots( $output );
		$this->assertStringContainsString( 'Sitemap:', $result );
		$this->assertStringContainsString( 'pearblog-sitemap-index.xml', $result );
	}

	public function test_inject_sitemap_preserves_existing_robots_content(): void {
		$original = "User-agent: *\nDisallow: /private/\n";
		$result   = $this->sitemap->inject_sitemap_in_robots( $original );
		$this->assertStringContainsString( 'Disallow: /private/', $result );
	}

	// Also remove the helper since we now use WP_REST_Request directly.
	// (No helper needed)
}
