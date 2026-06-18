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
		$GLOBALS['_options']     = [];
		$GLOBALS['_is_singular'] = false;
		$GLOBALS['_query_vars']  = [];
		$this->generator = new AMPGenerator();
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
	// register
	// -----------------------------------------------------------------------

	public function test_register_skips_hooks_when_disabled(): void {
		$GLOBALS['_filters'] = [];
		$GLOBALS['_actions'] = [];
		$this->generator->register();
		$this->assertArrayNotHasKey( 'template_redirect', $GLOBALS['_actions'] ?? [] );
	}

	public function test_register_adds_hooks_when_enabled(): void {
		$GLOBALS['_options'][ AMPGenerator::OPTION_ENABLED ] = true;
		$GLOBALS['_filters'] = [];
		$GLOBALS['_actions'] = [];
		$this->generator->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['template_redirect'] ) );
	}

	// -----------------------------------------------------------------------
	// add_query_var
	// -----------------------------------------------------------------------

	public function test_add_query_var_adds_amp_to_vars(): void {
		$vars   = [ 'p', 'page_id' ];
		$result = $this->generator->add_query_var( $vars );
		$this->assertContains( 'amp', $result );
	}

	public function test_add_query_var_preserves_existing_vars(): void {
		$vars   = [ 'p', 'page_id', 'category_name' ];
		$result = $this->generator->add_query_var( $vars );
		$this->assertContains( 'p', $result );
		$this->assertContains( 'page_id', $result );
		$this->assertContains( 'category_name', $result );
	}

	public function test_add_query_var_returns_array(): void {
		$result = $this->generator->add_query_var( [] );
		$this->assertIsArray( $result );
	}

	// -----------------------------------------------------------------------
	// convert_to_amp_content
	// -----------------------------------------------------------------------

	public function test_convert_to_amp_content_removes_script_tags(): void {
		$content = '<p>Text</p><script>alert("xss")</script><p>More text</p>';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringNotContainsString( '<script', $result );
	}

	public function test_convert_to_amp_content_removes_iframe_tags(): void {
		$content = '<p>Text</p><iframe src="https://example.com"></iframe><p>More</p>';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringNotContainsString( '<iframe', $result );
	}

	public function test_convert_to_amp_content_replaces_img_with_amp_img(): void {
		$content = '<img src="https://example.com/photo.jpg" alt="Photo" width="800" height="450">';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringContainsString( '<amp-img', $result );
		$this->assertStringNotContainsString( '<img ', $result );
	}

	public function test_convert_to_amp_content_amp_img_has_layout_responsive(): void {
		$content = '<img src="https://example.com/img.jpg" alt="Test">';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringContainsString( 'layout="responsive"', $result );
	}

	public function test_convert_to_amp_content_removes_style_attributes(): void {
		$content = '<p style="color:red;">Styled text</p>';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringNotContainsString( 'style=', $result );
	}

	public function test_convert_to_amp_content_returns_string(): void {
		$result = $this->generator->convert_to_amp_content( '' );
		$this->assertIsString( $result );
	}

	public function test_convert_to_amp_content_preserves_regular_html(): void {
		$content = '<p>This is regular paragraph text with no issues.</p>';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringContainsString( 'regular paragraph text', $result );
	}

	public function test_convert_to_amp_content_img_without_src_removed(): void {
		$content = '<img alt="no source">';
		$result  = $this->generator->convert_to_amp_content( $content );
		$this->assertStringNotContainsString( '<img', $result );
	}

	// -----------------------------------------------------------------------
	// serve_amp
	// -----------------------------------------------------------------------

	public function test_serve_amp_does_nothing_when_not_singular(): void {
		$GLOBALS['_is_singular'] = false;
		// Should not throw or output anything.
		ob_start();
		$this->generator->serve_amp();
		$output = ob_get_clean();
		$this->assertEmpty( $output );
	}

	// -----------------------------------------------------------------------
	// output_amphtml_link
	// -----------------------------------------------------------------------

	public function test_output_amphtml_link_outputs_nothing_when_not_singular(): void {
		$GLOBALS['_is_singular'] = false;
		ob_start();
		$this->generator->output_amphtml_link();
		$output = ob_get_clean();
		$this->assertEmpty( $output );
	}
}
