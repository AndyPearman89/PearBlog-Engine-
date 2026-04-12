<?php
/**
 * Unit tests for WhiteLabelManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Admin\WhiteLabelManager;

class WhiteLabelManagerTest extends TestCase {

	private WhiteLabelManager $mgr;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->mgr = new WhiteLabelManager();
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_disabled_by_default(): void {
		$this->assertFalse( $this->mgr->is_enabled() );
	}

	public function test_enabled_when_option_set(): void {
		update_option( WhiteLabelManager::OPTION_ENABLED, true );
		$this->assertTrue( $this->mgr->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// get_brand_name
	// -----------------------------------------------------------------------

	public function test_brand_name_defaults_to_pearblog_when_disabled(): void {
		$this->assertSame( WhiteLabelManager::DEFAULT_BRAND, $this->mgr->get_brand_name() );
	}

	public function test_brand_name_returns_custom_when_enabled(): void {
		update_option( WhiteLabelManager::OPTION_ENABLED, true );
		update_option( WhiteLabelManager::OPTION_BRAND_NAME, 'ContentBot Pro' );
		$this->assertSame( 'ContentBot Pro', $this->mgr->get_brand_name() );
	}

	public function test_brand_name_falls_back_to_default_when_empty(): void {
		update_option( WhiteLabelManager::OPTION_ENABLED, true );
		update_option( WhiteLabelManager::OPTION_BRAND_NAME, '' );
		$this->assertSame( WhiteLabelManager::DEFAULT_BRAND, $this->mgr->get_brand_name() );
	}

	// -----------------------------------------------------------------------
	// get_menu_label
	// -----------------------------------------------------------------------

	public function test_menu_label_defaults_to_pearblog_when_disabled(): void {
		$this->assertSame( WhiteLabelManager::DEFAULT_MENU, $this->mgr->get_menu_label() );
	}

	public function test_menu_label_returns_custom(): void {
		update_option( WhiteLabelManager::OPTION_ENABLED, true );
		update_option( WhiteLabelManager::OPTION_MENU_LABEL, 'My Content Engine' );
		$this->assertSame( 'My Content Engine', $this->mgr->get_menu_label() );
	}

	public function test_menu_label_falls_back_to_brand_name_when_empty(): void {
		update_option( WhiteLabelManager::OPTION_ENABLED, true );
		update_option( WhiteLabelManager::OPTION_BRAND_NAME, 'BrandX' );
		update_option( WhiteLabelManager::OPTION_MENU_LABEL, '' );
		$this->assertSame( 'BrandX', $this->mgr->get_menu_label() );
	}

	// -----------------------------------------------------------------------
	// get_accent_color
	// -----------------------------------------------------------------------

	public function test_accent_defaults_when_disabled(): void {
		$this->assertSame( WhiteLabelManager::DEFAULT_ACCENT, $this->mgr->get_accent_color() );
	}

	public function test_accent_returns_custom_hex(): void {
		update_option( WhiteLabelManager::OPTION_ENABLED, true );
		update_option( WhiteLabelManager::OPTION_ACCENT_COLOR, '#ff5733' );
		$this->assertSame( '#ff5733', $this->mgr->get_accent_color() );
	}

	// -----------------------------------------------------------------------
	// sanitize_hex_color
	// -----------------------------------------------------------------------

	public function test_sanitize_hex_valid_6_digit(): void {
		$this->assertSame( '#1A2B3C', $this->mgr->sanitize_hex_color( '#1A2B3C' ) );
	}

	public function test_sanitize_hex_valid_3_digit(): void {
		$this->assertSame( '#abc', $this->mgr->sanitize_hex_color( '#abc' ) );
	}

	public function test_sanitize_hex_invalid_returns_default(): void {
		$this->assertSame( WhiteLabelManager::DEFAULT_ACCENT, $this->mgr->sanitize_hex_color( 'not-a-color' ) );
	}

	public function test_sanitize_hex_missing_hash_returns_default(): void {
		$this->assertSame( WhiteLabelManager::DEFAULT_ACCENT, $this->mgr->sanitize_hex_color( '1A2B3C' ) );
	}

	// -----------------------------------------------------------------------
	// get_logo_url / get_support_url
	// -----------------------------------------------------------------------

	public function test_logo_url_empty_when_disabled(): void {
		$this->assertSame( '', $this->mgr->get_logo_url() );
	}

	public function test_logo_url_returned_when_enabled(): void {
		update_option( WhiteLabelManager::OPTION_ENABLED, true );
		update_option( WhiteLabelManager::OPTION_LOGO_URL, 'https://example.com/logo.png' );
		$this->assertSame( 'https://example.com/logo.png', $this->mgr->get_logo_url() );
	}

	public function test_support_url_empty_when_disabled(): void {
		$this->assertSame( '', $this->mgr->get_support_url() );
	}

	// -----------------------------------------------------------------------
	// should_hide_footer
	// -----------------------------------------------------------------------

	public function test_hide_footer_false_by_default(): void {
		$this->assertFalse( $this->mgr->should_hide_footer() );
	}

	public function test_hide_footer_true_when_configured(): void {
		update_option( WhiteLabelManager::OPTION_ENABLED, true );
		update_option( WhiteLabelManager::OPTION_HIDE_FOOTER, true );
		$this->assertTrue( $this->mgr->should_hide_footer() );
	}

	// -----------------------------------------------------------------------
	// maybe_hide_footer callback
	// -----------------------------------------------------------------------

	public function test_footer_callback_returns_original_when_not_hiding(): void {
		$text = 'Thank you for creating with WordPress.';
		$this->assertSame( $text, $this->mgr->maybe_hide_footer( $text ) );
	}

	public function test_footer_callback_returns_empty_when_hiding(): void {
		update_option( WhiteLabelManager::OPTION_ENABLED, true );
		update_option( WhiteLabelManager::OPTION_HIDE_FOOTER, true );
		$this->assertSame( '', $this->mgr->maybe_hide_footer( 'Footer text' ) );
	}
}
