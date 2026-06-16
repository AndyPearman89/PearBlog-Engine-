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

	private AMPGenerator $amp;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']     = [];
		$GLOBALS['_post_meta']   = [];
		$GLOBALS['_filters']     = [];
		$GLOBALS['_actions']     = [];
		$GLOBALS['_is_singular'] = false;
		$this->amp = new AMPGenerator();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant(): void {
		$this->assertSame( 'pearblog_amp_enabled', AMPGenerator::OPTION_ENABLED );
	}

	public function test_option_analytics_constant(): void {
		$this->assertSame( 'pearblog_amp_analytics', AMPGenerator::OPTION_ANALYTICS );
	}

	public function test_option_adsense_constant(): void {
		$this->assertSame( 'pearblog_amp_adsense', AMPGenerator::OPTION_ADSENSE );
	}

	// -----------------------------------------------------------------------
	// register — disabled
	// -----------------------------------------------------------------------

	public function test_register_skips_hooks_when_disabled(): void {
		$this->expectNotToPerformAssertions();
		// With option disabled, register() should return early.
		$this->amp->register();
	}

	public function test_register_does_not_throw_when_enabled(): void {
		update_option( AMPGenerator::OPTION_ENABLED, true );
		$this->expectNotToPerformAssertions();
		$this->amp->register();
	}

	// -----------------------------------------------------------------------
	// add_query_var
	// -----------------------------------------------------------------------

	public function test_add_query_var_appends_amp(): void {
		$vars   = [ 'p', 'page_id' ];
		$result = $this->amp->add_query_var( $vars );

		$this->assertContains( 'amp', $result );
		$this->assertContains( 'p', $result );
	}

	public function test_add_query_var_returns_array(): void {
		$result = $this->amp->add_query_var( [] );

		$this->assertIsArray( $result );
	}

	// -----------------------------------------------------------------------
	// convert_to_amp_content
	// -----------------------------------------------------------------------

	public function test_convert_to_amp_content_returns_string(): void {
		$result = $this->amp->convert_to_amp_content( '<p>Hello world</p>' );

		$this->assertIsString( $result );
	}

	public function test_convert_converts_img_to_amp_img(): void {
		$content = '<img src="https://example.com/image.jpg" alt="Test" width="800" height="450">';

		$result = $this->amp->convert_to_amp_content( $content );

		$this->assertStringContainsString( '<amp-img', $result );
		$this->assertStringNotContainsString( '<img', $result );
	}

	public function test_convert_removes_script_tags(): void {
		$content = '<p>Text</p><script>alert("xss")</script>';

		$result = $this->amp->convert_to_amp_content( $content );

		$this->assertStringNotContainsString( '<script', $result );
	}

	public function test_convert_removes_iframe_tags(): void {
		$content = '<p>Text</p><iframe src="https://youtube.com"></iframe>';

		$result = $this->amp->convert_to_amp_content( $content );

		$this->assertStringNotContainsString( '<iframe', $result );
	}

	public function test_convert_removes_style_attributes(): void {
		$content = '<p style="color:red">Text</p>';

		$result = $this->amp->convert_to_amp_content( $content );

		$this->assertStringNotContainsString( 'style=', $result );
	}

	public function test_convert_preserves_regular_paragraphs(): void {
		$content = '<p>Hello world</p><p>Second paragraph.</p>';

		$result = $this->amp->convert_to_amp_content( $content );

		$this->assertStringContainsString( '<p>', $result );
		$this->assertStringContainsString( 'Hello world', $result );
	}

	public function test_convert_img_uses_responsive_layout(): void {
		$content = '<img src="https://example.com/image.jpg" width="600" height="400" alt="Photo">';

		$result = $this->amp->convert_to_amp_content( $content );

		$this->assertStringContainsString( 'layout="responsive"', $result );
	}

	public function test_convert_skips_img_without_src(): void {
		$content = '<img alt="No source">';

		$result = $this->amp->convert_to_amp_content( $content );

		$this->assertStringNotContainsString( '<amp-img', $result );
	}

	public function test_convert_amp_img_uses_default_dimensions_when_missing(): void {
		$content = '<img src="https://example.com/img.jpg" alt="Test">';

		$result = $this->amp->convert_to_amp_content( $content );

		$this->assertStringContainsString( 'width="800"', $result );
		$this->assertStringContainsString( 'height="450"', $result );
	}

	// -----------------------------------------------------------------------
	// output_amphtml_link (non-singular → no output)
	// -----------------------------------------------------------------------

	public function test_output_amphtml_link_produces_no_output_when_not_singular(): void {
		$GLOBALS['_is_singular'] = false;
		ob_start();
		$this->amp->output_amphtml_link();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}
}
