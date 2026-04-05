<?php
/**
 * Unit tests for SEOEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\SEO\SEOEngine;

class SEOEngineTest extends TestCase {

	private SEOEngine $seo;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_post_meta'] = [];
		$this->seo = new SEOEngine();
	}

	public function test_extracts_meta_description(): void {
		$content = "META: This is a great meta description.\n\n# Title\n\nBody content.";
		$result  = $this->seo->apply( 1, $content );

		$this->assertSame( 'This is a great meta description.', $result['meta_description'] );
	}

	public function test_extracts_markdown_h1_title(): void {
		$content = "META: Description.\n\n# My Article Title\n\nBody text.";
		$result  = $this->seo->apply( 1, $content );

		$this->assertSame( 'My Article Title', $result['title'] );
	}

	public function test_extracts_html_h1_title(): void {
		$content = "META: Description.\n\n<h1>HTML Article Title</h1>\n\nBody text.";
		$result  = $this->seo->apply( 1, $content );

		$this->assertSame( 'HTML Article Title', $result['title'] );
	}

	public function test_meta_line_stripped_from_content(): void {
		$content = "META: Description line.\n\n# Title\n\nBody paragraph.";
		$result  = $this->seo->apply( 1, $content );

		$this->assertStringNotContainsString( 'META:', $result['content'] );
	}

	public function test_stores_yoast_meta_in_post_meta(): void {
		$content = "META: A Yoast description.\n\n# Title\n\nBody.";
		$this->seo->apply( 42, $content );

		$stored = $GLOBALS['_post_meta'][42]['_yoast_wpseo_metadesc'][0] ?? '';
		$this->assertSame( 'A Yoast description.', $stored );
	}

	public function test_stores_rankmath_meta_in_post_meta(): void {
		$content = "META: A RankMath description.\n\n# Title\n\nBody.";
		$this->seo->apply( 7, $content );

		$stored = $GLOBALS['_post_meta'][7]['rank_math_description'][0] ?? '';
		$this->assertSame( 'A RankMath description.', $stored );
	}

	public function test_empty_content_returns_empty_fields(): void {
		$result = $this->seo->apply( 1, '' );

		$this->assertSame( '', $result['title'] );
		$this->assertSame( '', $result['meta_description'] );
	}

	public function test_content_without_meta_returns_empty_description(): void {
		$content = "# Title Only\n\nJust body text.";
		$result  = $this->seo->apply( 1, $content );

		$this->assertSame( '', $result['meta_description'] );
		$this->assertSame( 'Title Only', $result['title'] );
	}
}
