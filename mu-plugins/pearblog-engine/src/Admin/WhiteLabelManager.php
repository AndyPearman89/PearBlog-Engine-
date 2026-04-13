<?php
/**
 * White-Label Manager — customises plugin branding from the WordPress admin.
 *
 * Allows agencies and enterprise clients to replace all "PearBlog" references
 * with their own brand name, logo, and admin menu label without touching code.
 *
 * Settings stored in WordPress options:
 *   pearblog_wl_enabled      – bool, enable white-labelling
 *   pearblog_wl_brand_name   – string, replacement brand name (e.g. "ContentBot Pro")
 *   pearblog_wl_menu_label   – string, admin menu item label
 *   pearblog_wl_logo_url     – string, URL to a custom admin logo image (≤ 80 px tall)
 *   pearblog_wl_accent_color – string, CSS hex colour for admin accents
 *   pearblog_wl_support_url  – string, custom support / documentation URL
 *   pearblog_wl_hide_footer  – bool, hide the "Powered by PearBlog Engine" admin footer
 *
 * @package PearBlogEngine\Admin
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Provides white-label branding overrides for the plugin admin UI.
 */
class WhiteLabelManager {

	/** Option keys. */
	public const OPTION_ENABLED      = 'pearblog_wl_enabled';
	public const OPTION_BRAND_NAME   = 'pearblog_wl_brand_name';
	public const OPTION_MENU_LABEL   = 'pearblog_wl_menu_label';
	public const OPTION_LOGO_URL     = 'pearblog_wl_logo_url';
	public const OPTION_ACCENT_COLOR = 'pearblog_wl_accent_color';
	public const OPTION_SUPPORT_URL  = 'pearblog_wl_support_url';
	public const OPTION_HIDE_FOOTER  = 'pearblog_wl_hide_footer';

	/** Fallback default brand name. */
	public const DEFAULT_BRAND = 'PearBlog Engine';

	/** Fallback default menu label. */
	public const DEFAULT_MENU = 'PearBlog Engine';

	/** Fallback accent colour. */
	public const DEFAULT_ACCENT = '#2563eb';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_head', [ $this, 'inject_accent_css' ] );
		add_filter( 'admin_footer_text', [ $this, 'maybe_hide_footer' ] );
	}

	/**
	 * Register all white-label settings with WordPress.
	 */
	public function register_settings(): void {
		$settings = [
			self::OPTION_ENABLED      => [ 'type' => 'boolean' ],
			self::OPTION_BRAND_NAME   => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
			self::OPTION_MENU_LABEL   => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
			self::OPTION_LOGO_URL     => [ 'type' => 'string', 'sanitize_callback' => 'esc_url_raw' ],
			self::OPTION_ACCENT_COLOR => [ 'type' => 'string', 'sanitize_callback' => [ $this, 'sanitize_hex_color' ] ],
			self::OPTION_SUPPORT_URL  => [ 'type' => 'string', 'sanitize_callback' => 'esc_url_raw' ],
			self::OPTION_HIDE_FOOTER  => [ 'type' => 'boolean' ],
		];

		foreach ( $settings as $option => $args ) {
			register_setting( 'pearblog_branding', $option, $args );
		}
	}

	// -----------------------------------------------------------------------
	// Accessor helpers
	// -----------------------------------------------------------------------

	/**
	 * Whether white-labelling is enabled.
	 */
	public function is_enabled(): bool {
		return filter_var( get_option( self::OPTION_ENABLED, false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Get the current brand name (custom or default).
	 */
	public function get_brand_name(): string {
		if ( ! $this->is_enabled() ) {
			return self::DEFAULT_BRAND;
		}
		$name = (string) get_option( self::OPTION_BRAND_NAME, '' );
		return '' !== $name ? $name : self::DEFAULT_BRAND;
	}

	/**
	 * Get the admin menu label.
	 */
	public function get_menu_label(): string {
		if ( ! $this->is_enabled() ) {
			return self::DEFAULT_MENU;
		}
		$label = (string) get_option( self::OPTION_MENU_LABEL, '' );
		return '' !== $label ? $label : $this->get_brand_name();
	}

	/**
	 * Get the custom logo URL, or empty string if none set.
	 */
	public function get_logo_url(): string {
		if ( ! $this->is_enabled() ) {
			return '';
		}
		return (string) get_option( self::OPTION_LOGO_URL, '' );
	}

	/**
	 * Get the accent hex colour.
	 */
	public function get_accent_color(): string {
		if ( ! $this->is_enabled() ) {
			return self::DEFAULT_ACCENT;
		}
		$color = (string) get_option( self::OPTION_ACCENT_COLOR, '' );
		return '' !== $color ? $color : self::DEFAULT_ACCENT;
	}

	/**
	 * Get the custom support URL, or empty string.
	 */
	public function get_support_url(): string {
		if ( ! $this->is_enabled() ) {
			return '';
		}
		return (string) get_option( self::OPTION_SUPPORT_URL, '' );
	}

	/**
	 * Whether the "Powered by PearBlog Engine" footer should be hidden.
	 */
	public function should_hide_footer(): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}
		return filter_var( get_option( self::OPTION_HIDE_FOOTER, false ), FILTER_VALIDATE_BOOLEAN );
	}

	// -----------------------------------------------------------------------
	// WordPress hook callbacks
	// -----------------------------------------------------------------------

	/**
	 * Inject a small CSS snippet that overrides admin accent colours.
	 */
	public function inject_accent_css(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		$color = esc_attr( $this->get_accent_color() );
		echo "<style>.pb-admin-accent{color:{$color}}.pb-admin-btn,.button-primary.pb-btn{background:{$color};border-color:{$color}}</style>\n";
	}

	/**
	 * Hide or replace the admin footer text.
	 *
	 * @param string $text  Default footer text.
	 * @return string
	 */
	public function maybe_hide_footer( string $text ): string {
		if ( $this->should_hide_footer() ) {
			return '';
		}
		return $text;
	}

	// -----------------------------------------------------------------------
	// Sanitisation helpers
	// -----------------------------------------------------------------------

	/**
	 * Sanitise a hex colour value.
	 *
	 * @param mixed $color  Raw input.
	 * @return string       Valid hex colour, or the default accent colour.
	 */
	public function sanitize_hex_color( $color ): string {
		$color = (string) $color;
		if ( preg_match( '/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color ) ) {
			return $color;
		}
		return self::DEFAULT_ACCENT;
	}
}
