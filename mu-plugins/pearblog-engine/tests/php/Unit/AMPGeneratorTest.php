<?php
/**
 * Unit tests for AMPGenerator.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Distribution\AMPGenerator;

class AMPGeneratorTest extends TestCase {

	private AMPGenerator $generator;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->generator     = new AMPGenerator();
	}

	// -----------------------------------------------------------------------
	// Option constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant_value(): void {
		$this->assertSame( 'pearblog_amp_enabled', AMPGenerator::OPTION_ENABLED );
	}

	public function test_option_analytics_constant_value(): void {
		$this->assertSame( 'pearblog_amp_analytics', AMPGenerator::OPTION_ANALYTICS );
	}

	public function test_option_adsense_constant_value(): void {
		$this->assertSame( 'pearblog_amp_adsense', AMPGenerator::OPTION_ADSENSE );
	}

	// -----------------------------------------------------------------------
	// add_query_var
	// -----------------------------------------------------------------------

	public function test_add_query_var_appends_amp_to_vars(): void {
		$vars   = [ 'p', 'page_id', 'cat' ];
		$result = $this->generator->add_query_var( $vars );
		$this->assertContains( 'amp', $result );
	}

	public function test_add_query_var_preserves_existing_vars(): void {
		$vars   = [ 'p', 'page_id' ];
		$result = $this->generator->add_query_var( $vars );
		$this->assertContains( 'p', $result );
		$this->assertContains( 'page_id', $result );
	}

	public function test_add_query_var_returns_array(): void {
		$result = $this->generator->add_query_var( [] );
		$this->assertIsArray( $result );
	}

	public function test_add_query_var_on_empty_array(): void {
		$result = $this->generator->add_query_var( [] );
		$this->assertSame( [ 'amp' ], $result );
	}

	// -----------------------------------------------------------------------
	// convert_to_amp_content — img → amp-img
	// -----------------------------------------------------------------------

	public function test_convert_replaces_img_with_amp_img(): void {
		$content = '<img src="https://example.com/photo.jpg" width="800" height="450" alt="A photo">';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringContainsString( '<amp-img', $result );
		$this->assertStringNotContainsString( '<img', $result );
	}

	public function test_convert_preserves_image_src(): void {
		$content = '<img src="https://example.com/photo.jpg">';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringContainsString( 'https://example.com/photo.jpg', $result );
	}

	public function test_convert_uses_default_dimensions_when_missing(): void {
		$content = '<img src="https://example.com/img.jpg">';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringContainsString( 'width="800"', $result );
		$this->assertStringContainsString( 'height="450"', $result );
	}

	public function test_convert_preserves_custom_dimensions(): void {
		$content = '<img src="https://example.com/img.jpg" width="1200" height="630">';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringContainsString( 'width="1200"', $result );
		$this->assertStringContainsString( 'height="630"', $result );
	}

	public function test_convert_img_without_src_returns_empty(): void {
		$content = '<img alt="no src">';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringNotContainsString( '<amp-img', $result );
	}

	public function test_convert_adds_layout_responsive(): void {
		$content = '<img src="https://example.com/img.jpg">';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringContainsString( 'layout="responsive"', $result );
	}

	// -----------------------------------------------------------------------
	// convert_to_amp_content — script/iframe removal
	// -----------------------------------------------------------------------

	public function test_convert_removes_script_tags(): void {
		$content = '<p>Hello</p><script>alert("xss")</script><p>World</p>';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringNotContainsString( '<script', $result );
		$this->assertStringNotContainsString( 'alert(', $result );
	}

	public function test_convert_removes_iframe_tags(): void {
		$content = '<p>Content</p><iframe src="https://youtube.com/embed/xyz"></iframe>';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringNotContainsString( '<iframe', $result );
	}

	public function test_convert_removes_style_attributes(): void {
		$content = '<p style="color:red; font-size:16px;">Text</p>';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringNotContainsString( 'style=', $result );
	}

	// -----------------------------------------------------------------------
	// convert_to_amp_content — passthrough for safe content
	// -----------------------------------------------------------------------

	public function test_convert_preserves_paragraph_text(): void {
		$content = '<p>This is a safe paragraph with WordPress tips.</p>';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringContainsString( 'WordPress tips', $result );
	}

	public function test_convert_returns_string(): void {
		$result = $this->generator->convert_to_amp_content( '' );
		$this->assertIsString( $result );
	}
}
