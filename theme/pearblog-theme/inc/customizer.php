<?php
/**
 * PearBlog PRO Advanced Customization Panel
 *
 * Registers WordPress Customizer sections, settings, and controls.
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer sections, settings, and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
 */
function pearblog_customize_register( $wp_customize ) {

	// -----------------------------------------------------------------------
	// Panel: PearBlog PRO
	// -----------------------------------------------------------------------
	$wp_customize->add_panel( 'pearblog_pro_panel', array(
		'title'       => __( 'PearBlog PRO', 'pearblog-theme' ),
		'description' => __( 'Advanced customization options for PearBlog PRO theme.', 'pearblog-theme' ),
		'priority'    => 25,
	) );

	// -----------------------------------------------------------------------
	// Section: Brand Colors
	// -----------------------------------------------------------------------
	$wp_customize->add_section( 'pearblog_colors', array(
		'title'    => __( 'Brand Colors', 'pearblog-theme' ),
		'panel'    => 'pearblog_pro_panel',
		'priority' => 10,
	) );

	$color_settings = array(
		'pearblog_primary_color'   => array(
			'label'   => __( 'Primary Color', 'pearblog-theme' ),
			'default' => '#2563eb',
		),
		'pearblog_secondary_color' => array(
			'label'   => __( 'Secondary Color', 'pearblog-theme' ),
			'default' => '#7c3aed',
		),
		'pearblog_accent_color'    => array(
			'label'   => __( 'Accent Color', 'pearblog-theme' ),
			'default' => '#f59e0b',
		),
	);

	foreach ( $color_settings as $setting_id => $setting ) {
		$wp_customize->add_setting( $setting_id, array(
			'default'           => $setting['default'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $setting_id, array(
			'label'   => $setting['label'],
			'section' => 'pearblog_colors',
		) ) );
	}

	// -----------------------------------------------------------------------
	// Section: Typography
	// -----------------------------------------------------------------------
	$wp_customize->add_section( 'pearblog_typography', array(
		'title'    => __( 'Typography', 'pearblog-theme' ),
		'panel'    => 'pearblog_pro_panel',
		'priority' => 20,
	) );

	$wp_customize->add_setting( 'pearblog_font_family', array(
		'default'           => 'system',
		'sanitize_callback' => 'sanitize_text_field',
	) );

	$wp_customize->add_control( 'pearblog_font_family', array(
		'label'   => __( 'Font Family', 'pearblog-theme' ),
		'section' => 'pearblog_typography',
		'type'    => 'select',
		'choices' => array(
			'system'     => __( 'System Default', 'pearblog-theme' ),
			'inter'      => 'Inter',
			'roboto'     => 'Roboto',
			'opensans'   => 'Open Sans',
			'lato'       => 'Lato',
			'montserrat' => 'Montserrat',
			'playfair'   => 'Playfair Display',
			'merriweather' => 'Merriweather',
		),
	) );

	$wp_customize->add_setting( 'pearblog_font_size_base', array(
		'default'           => '16',
		'sanitize_callback' => 'absint',
	) );

	$wp_customize->add_control( 'pearblog_font_size_base', array(
		'label'       => __( 'Base Font Size (px)', 'pearblog-theme' ),
		'section'     => 'pearblog_typography',
		'type'        => 'number',
		'input_attrs' => array(
			'min'  => 12,
			'max'  => 24,
			'step' => 1,
		),
	) );

	$wp_customize->add_setting( 'pearblog_heading_font_family', array(
		'default'           => 'system',
		'sanitize_callback' => 'sanitize_text_field',
	) );

	$wp_customize->add_control( 'pearblog_heading_font_family', array(
		'label'   => __( 'Heading Font Family', 'pearblog-theme' ),
		'section' => 'pearblog_typography',
		'type'    => 'select',
		'choices' => array(
			'system'     => __( 'System Default', 'pearblog-theme' ),
			'inter'      => 'Inter',
			'roboto'     => 'Roboto',
			'opensans'   => 'Open Sans',
			'lato'       => 'Lato',
			'montserrat' => 'Montserrat',
			'playfair'   => 'Playfair Display',
			'merriweather' => 'Merriweather',
		),
	) );

	// -----------------------------------------------------------------------
	// Section: Hero Settings
	// -----------------------------------------------------------------------
	$wp_customize->add_section( 'pearblog_hero', array(
		'title'    => __( 'Hero Section', 'pearblog-theme' ),
		'panel'    => 'pearblog_pro_panel',
		'priority' => 30,
	) );

	$wp_customize->add_setting( 'pearblog_hero_style', array(
		'default'           => 'gradient',
		'sanitize_callback' => 'sanitize_text_field',
	) );

	$wp_customize->add_control( 'pearblog_hero_style', array(
		'label'   => __( 'Hero Style', 'pearblog-theme' ),
		'section' => 'pearblog_hero',
		'type'    => 'select',
		'choices' => array(
			'gradient' => __( 'Gradient', 'pearblog-theme' ),
			'image'    => __( 'Background Image', 'pearblog-theme' ),
			'video'    => __( 'Background Video', 'pearblog-theme' ),
		),
	) );

	$wp_customize->add_setting( 'pearblog_hero_title', array(
		'default'           => get_bloginfo( 'name' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );

	$wp_customize->add_control( 'pearblog_hero_title', array(
		'label'   => __( 'Hero Title', 'pearblog-theme' ),
		'section' => 'pearblog_hero',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'pearblog_hero_subtitle', array(
		'default'           => get_bloginfo( 'description' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );

	$wp_customize->add_control( 'pearblog_hero_subtitle', array(
		'label'   => __( 'Hero Subtitle', 'pearblog-theme' ),
		'section' => 'pearblog_hero',
		'type'    => 'textarea',
	) );

	$wp_customize->add_setting( 'pearblog_hero_image', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );

	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'pearblog_hero_image', array(
		'label'   => __( 'Hero Background Image', 'pearblog-theme' ),
		'section' => 'pearblog_hero',
	) ) );

	$wp_customize->add_setting( 'pearblog_hero_video', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );

	$wp_customize->add_control( 'pearblog_hero_video', array(
		'label'   => __( 'Hero Background Video URL', 'pearblog-theme' ),
		'section' => 'pearblog_hero',
		'type'    => 'url',
	) );

	// -----------------------------------------------------------------------
	// Section: Layout
	// -----------------------------------------------------------------------
	$wp_customize->add_section( 'pearblog_layout', array(
		'title'    => __( 'Layout', 'pearblog-theme' ),
		'panel'    => 'pearblog_pro_panel',
		'priority' => 40,
	) );

	$wp_customize->add_setting( 'pearblog_layout_variant', array(
		'default'           => 'default',
		'sanitize_callback' => 'sanitize_text_field',
	) );

	$wp_customize->add_control( 'pearblog_layout_variant', array(
		'label'   => __( 'Layout Variant', 'pearblog-theme' ),
		'section' => 'pearblog_layout',
		'type'    => 'select',
		'choices' => array(
			'default'  => __( 'Default', 'pearblog-theme' ),
			'minimal'  => __( 'Minimal', 'pearblog-theme' ),
			'magazine' => __( 'Magazine', 'pearblog-theme' ),
		),
	) );

	$wp_customize->add_setting( 'pearblog_container_width', array(
		'default'           => '1200',
		'sanitize_callback' => 'absint',
	) );

	$wp_customize->add_control( 'pearblog_container_width', array(
		'label'       => __( 'Container Max Width (px)', 'pearblog-theme' ),
		'section'     => 'pearblog_layout',
		'type'        => 'number',
		'input_attrs' => array(
			'min'  => 800,
			'max'  => 1600,
			'step' => 20,
		),
	) );

	$wp_customize->add_setting( 'pearblog_sidebar_position', array(
		'default'           => 'right',
		'sanitize_callback' => 'sanitize_text_field',
	) );

	$wp_customize->add_control( 'pearblog_sidebar_position', array(
		'label'   => __( 'Sidebar Position', 'pearblog-theme' ),
		'section' => 'pearblog_layout',
		'type'    => 'select',
		'choices' => array(
			'right' => __( 'Right', 'pearblog-theme' ),
			'left'  => __( 'Left', 'pearblog-theme' ),
			'none'  => __( 'No Sidebar', 'pearblog-theme' ),
		),
	) );

	// -----------------------------------------------------------------------
	// Section: Feature Toggles
	// -----------------------------------------------------------------------
	$wp_customize->add_section( 'pearblog_features', array(
		'title'    => __( 'Feature Toggles', 'pearblog-theme' ),
		'panel'    => 'pearblog_pro_panel',
		'priority' => 50,
	) );

	$feature_toggles = array(
		'pearblog_dark_mode_enabled' => array(
			'label'   => __( 'Enable Dark Mode', 'pearblog-theme' ),
			'default' => true,
		),
		'pearblog_toc_enabled'       => array(
			'label'   => __( 'Enable Table of Contents', 'pearblog-theme' ),
			'default' => true,
		),
		'pearblog_sticky_mobile_cta' => array(
			'label'   => __( 'Sticky Mobile CTA', 'pearblog-theme' ),
			'default' => true,
		),
		'pearblog_auto_ad_injection' => array(
			'label'   => __( 'Auto Ad Injection', 'pearblog-theme' ),
			'default' => false,
		),
	);

	foreach ( $feature_toggles as $setting_id => $feature ) {
		$wp_customize->add_setting( $setting_id, array(
			'default'           => $feature['default'],
			'sanitize_callback' => 'pearblog_sanitize_checkbox',
		) );

		$wp_customize->add_control( $setting_id, array(
			'label'   => $feature['label'],
			'section' => 'pearblog_features',
			'type'    => 'checkbox',
		) );
	}

	$wp_customize->add_setting( 'pearblog_ad_injection_paragraphs', array(
		'default'           => 3,
		'sanitize_callback' => 'absint',
	) );

	$wp_customize->add_control( 'pearblog_ad_injection_paragraphs', array(
		'label'       => __( 'Paragraphs Between Ads', 'pearblog-theme' ),
		'section'     => 'pearblog_features',
		'type'        => 'number',
		'input_attrs' => array(
			'min'  => 1,
			'max'  => 10,
			'step' => 1,
		),
	) );

	// -----------------------------------------------------------------------
	// Section: Branding
	// -----------------------------------------------------------------------
	$wp_customize->add_section( 'pearblog_branding', array(
		'title'    => __( 'Branding', 'pearblog-theme' ),
		'panel'    => 'pearblog_pro_panel',
		'priority' => 5,
	) );

	$wp_customize->add_setting( 'pearblog_logo_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );

	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'pearblog_logo_url', array(
		'label'   => __( 'Logo', 'pearblog-theme' ),
		'section' => 'pearblog_branding',
	) ) );

	$wp_customize->add_setting( 'pearblog_logo_dark_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );

	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'pearblog_logo_dark_url', array(
		'label'   => __( 'Logo (Dark Mode)', 'pearblog-theme' ),
		'section' => 'pearblog_branding',
	) ) );

	// -----------------------------------------------------------------------
	// Section: Footer
	// -----------------------------------------------------------------------
	$wp_customize->add_section( 'pearblog_footer', array(
		'title'    => __( 'Footer', 'pearblog-theme' ),
		'panel'    => 'pearblog_pro_panel',
		'priority' => 60,
	) );

	$wp_customize->add_setting( 'pearblog_footer_text', array(
		'default'           => '',
		'sanitize_callback' => 'wp_kses_post',
	) );

	$wp_customize->add_control( 'pearblog_footer_text', array(
		'label'   => __( 'Footer Copyright Text', 'pearblog-theme' ),
		'section' => 'pearblog_footer',
		'type'    => 'textarea',
	) );

	$wp_customize->add_setting( 'pearblog_footer_columns', array(
		'default'           => '3',
		'sanitize_callback' => 'absint',
	) );

	$wp_customize->add_control( 'pearblog_footer_columns', array(
		'label'   => __( 'Footer Widget Columns', 'pearblog-theme' ),
		'section' => 'pearblog_footer',
		'type'    => 'select',
		'choices' => array(
			'2' => __( '2 Columns', 'pearblog-theme' ),
			'3' => __( '3 Columns', 'pearblog-theme' ),
			'4' => __( '4 Columns', 'pearblog-theme' ),
		),
	) );
}
add_action( 'customize_register', 'pearblog_customize_register' );

/**
 * Sanitize checkbox values for the Customizer.
 *
 * @param mixed $checked Value to sanitize.
 * @return bool
 */
function pearblog_sanitize_checkbox( $checked ) {
	return (bool) $checked;
}

/**
 * Enqueue Customizer live preview script.
 */
function pearblog_customize_preview_js() {
	wp_enqueue_script(
		'pearblog-customizer-preview',
		PEARBLOG_URI . '/assets/js/customizer-preview.js',
		array( 'customize-preview' ),
		PEARBLOG_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'pearblog_customize_preview_js' );

/**
 * Generate additional dynamic CSS from Customizer settings.
 *
 * @return string CSS output.
 */
function pearblog_customizer_css() {
	$css = '';

	$font_family_key = get_theme_mod( 'pearblog_font_family', 'system' );
	$font_map        = array(
		'system'       => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
		'inter'        => '"Inter", sans-serif',
		'roboto'       => '"Roboto", sans-serif',
		'opensans'     => '"Open Sans", sans-serif',
		'lato'         => '"Lato", sans-serif',
		'montserrat'   => '"Montserrat", sans-serif',
		'playfair'     => '"Playfair Display", serif',
		'merriweather' => '"Merriweather", serif',
	);

	$heading_font_key = get_theme_mod( 'pearblog_heading_font_family', 'system' );
	$base_size        = get_theme_mod( 'pearblog_font_size_base', 16 );
	$max_width        = get_theme_mod( 'pearblog_container_width', 1200 );

	$body_font        = $font_map[ $font_family_key ] ?? $font_map['system'];
	$heading_font     = $font_map[ $heading_font_key ] ?? $font_map['system'];

	$css .= ':root {';
	$css .= '--pb-font-body: ' . $body_font . ';';
	$css .= '--pb-font-heading: ' . $heading_font . ';';
	$css .= '--pb-font-size-base: ' . absint( $base_size ) . 'px;';
	$css .= '--pb-container-max-width: ' . absint( $max_width ) . 'px;';
	$css .= '}';

	return $css;
}
