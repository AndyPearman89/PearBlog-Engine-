<?php
/**
 * Unit tests for SEOEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\SEO\SEOEngine;
use PHPUnit\Framework\TestCase;

class SEOEngineTest extends TestCase {

	private SEOEngine $seo;

	protected function setUp(): void {
		$this->seo = new SEOEngine();
		$GLOBALS['_wp_test_postmeta'] = [];
	}

	public function test_apply_extracts_meta_and_title(): void {
		$content = "META: This is the meta description.\n\n# My Article Title\n\nArticle body here.";

		$result = $this->seo->apply( 1, $content );

		$this->assertSame( 'My Article Title', $result['title'] );
		$this->assertSame( 'This is the meta description.', $result['meta_description'] );
		$this->assertStringNotContainsString( 'META:', $result['content'] );
	}

	public function test_apply_handles_html_h1(): void {
		$content = "META: Test meta\n\n<h1>HTML Title</h1>\n\n<p>Body.</p>";

		$result = $this->seo->apply( 2, $content );

		$this->assertSame( 'HTML Title', $result['title'] );
	}

	public function test_apply_handles_missing_meta(): void {
		$content = "# Just a Title\n\nBody content.";

		$result = $this->seo->apply( 3, $content );

		$this->assertSame( 'Just a Title', $result['title'] );
		$this->assertSame( '', $result['meta_description'] );
	}

	public function test_apply_handles_missing_title(): void {
		$content = "META: Some description\n\nBody content without heading.";

		$result = $this->seo->apply( 4, $content );

		$this->assertSame( '', $result['title'] );
		$this->assertSame( 'Some description', $result['meta_description'] );
	}

	public function test_meta_stored_as_post_meta(): void {
		$content = "META: Stored meta\n\n# Stored Title\n\nBody.";

		$this->seo->apply( 5, $content );

		$this->assertSame( 'Stored meta', get_post_meta( 5, 'pearblog_meta_description', true ) );
		$this->assertSame( 'Stored meta', get_post_meta( 5, '_yoast_wpseo_metadesc', true ) );
		$this->assertSame( 'Stored meta', get_post_meta( 5, 'rank_math_description', true ) );
	}

	public function test_canonical_url(): void {
		$url = $this->seo->canonical_url( 42 );

		$this->assertStringContainsString( '42', $url );
	}

	public function test_strip_directives_removes_meta_line(): void {
		$content = "META: Remove this\nMETA: And this too\n\n# Title\n\nBody.";

		$result = $this->seo->apply( 6, $content );

		$this->assertStringNotContainsString( 'META:', $result['content'] );
		$this->assertStringContainsString( '# Title', $result['content'] );
	}
}
