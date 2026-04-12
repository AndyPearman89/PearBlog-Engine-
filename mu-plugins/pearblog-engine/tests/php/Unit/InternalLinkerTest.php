<?php
/**
 * Unit tests for InternalLinker.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\SEO\InternalLinker;

class InternalLinkerTest extends TestCase {

	private InternalLinker $linker;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_post_meta']  = [];
		$this->linker = new InternalLinker();
	}

	public function test_constants_are_defined(): void {
		$this->assertSame( 'pearblog_keyword_cluster', InternalLinker::META_KEY_CLUSTER );
	}

	public function test_apply_returns_content_unchanged_when_no_candidates(): void {
		// No published posts in stub → no candidates → content unchanged.
		$content = '<p>Hello world this is a test article.</p>';
		$result  = $this->linker->apply( $content, 1 );
		$this->assertSame( $content, $result );
	}

	public function test_apply_returns_string(): void {
		$content = '<p>Some content about technology and software development.</p>';
		$result  = $this->linker->apply( $content, 99 );
		$this->assertIsString( $result );
	}

	public function test_apply_does_not_add_links_to_empty_content(): void {
		$result = $this->linker->apply( '', 1 );
		$this->assertSame( '', $result );
	}

	public function test_apply_accepts_html_content(): void {
		$content = '<p>A paragraph about <strong>bold topics</strong> and more.</p>';
		$result  = $this->linker->apply( $content, 2 );
		$this->assertIsString( $result );
		// Must not corrupt HTML structure — p tag should be present.
		$this->assertStringContainsString( '<p>', $result );
	}

	public function test_apply_does_not_self_link(): void {
		// Even if post 1 has a keyword cluster that matches content, when
		// processing post 1 itself no link to itself should be added.
		$GLOBALS['_post_meta'][1][ InternalLinker::META_KEY_CLUSTER ] = json_encode( [ 'self keyword' ] );
		$content = '<p>This article is about self keyword usage in SEO.</p>';
		$result  = $this->linker->apply( $content, 1 );
		// No link to post 1 should appear.
		$this->assertStringNotContainsString( '?p=1', $result );
	}

	public function test_apply_with_plain_text_does_not_throw(): void {
		$content = 'Plain text without any HTML tags discussing various topics.';
		$result  = $this->linker->apply( $content, 5 );
		$this->assertIsString( $result );
	}
}
