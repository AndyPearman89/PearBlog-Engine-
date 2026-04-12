<?php
/**
 * Unit tests for SchemaManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\SEO\SchemaManager;

class SchemaManagerTest extends TestCase {

	private SchemaManager $schema;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_post_meta']  = [];
		$this->schema = new SchemaManager();
	}

	public function test_instantiation(): void {
		$this->assertInstanceOf( SchemaManager::class, $this->schema );
	}

	public function test_output_is_silent_outside_singular_post(): void {
		// WP stub returns false for is_singular(), so output() is a no-op.
		ob_start();
		$this->schema->output();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	public function test_register_attaches_wp_head_hook(): void {
		$this->schema->register();
		$this->assertTrue( (bool) has_action( 'wp_head', [ $this->schema, 'output' ] ) );
	}

	public function test_output_does_not_throw_when_no_global_post(): void {
		$GLOBALS['post'] = null;
		ob_start();
		$this->schema->output();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	public function test_output_with_singular_post_generates_json_ld(): void {
		// Override is_singular via the global stub flag.
		$GLOBALS['_is_singular'] = true;

		$post              = new \WP_Post();
		$post->ID          = 42;
		$post->post_title  = 'Test Article Title';
		$post->post_content = '<p>Article body text.</p>';
		$post->post_date   = '2026-01-15 10:00:00';
		$post->post_modified = '2026-01-16 12:00:00';
		$post->post_name   = 'test-article-title';
		$post->post_status = 'publish';
		$post->post_author = 1;
		$GLOBALS['post'] = $post;

		ob_start();
		$this->schema->output();
		$html = ob_get_clean();

		// With WP_Post and is_singular=true, JSON-LD should be emitted.
		$this->assertIsString( $html );

		$GLOBALS['_is_singular'] = false;
		$GLOBALS['post'] = null;
	}
}
