<?php
/**
 * PearBlog Theme Functions - Frontend Operating System (FOS)
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme constants
define('PEARBLOG_VERSION', '4.0.0');
define('PEARBLOG_DIR', get_template_directory());
define('PEARBLOG_URI', get_template_directory_uri());
define('PEARBLOG_IS_PRO', true);
define('PEARBLOG_AI_ENGINE', true);

// Include helper files
require_once PEARBLOG_DIR . '/inc/ui.php';
require_once PEARBLOG_DIR . '/inc/layout.php';
require_once PEARBLOG_DIR . '/inc/components.php';
require_once PEARBLOG_DIR . '/inc/performance.php';
require_once PEARBLOG_DIR . '/inc/monetization.php';
require_once PEARBLOG_DIR . '/inc/affiliate-api.php';

// AI Personalization Engine (v4)
require_once PEARBLOG_DIR . '/inc/user-context.php';
require_once PEARBLOG_DIR . '/inc/behavior-tracking.php';
require_once PEARBLOG_DIR . '/inc/dynamic-content.php';
require_once PEARBLOG_DIR . '/inc/ai-optimizer.php';

/**
 * Theme setup
 */
function pearblog_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    add_theme_support('responsive-embeds');
    add_theme_support('automatic-feed-links');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'pearblog-theme'),
        'footer' => __('Footer Menu', 'pearblog-theme'),
    ));

    // Image sizes for performance
    add_image_size('pearblog-hero', 1200, 600, true);
    add_image_size('pearblog-card', 600, 400, true);
    add_image_size('pearblog-thumbnail', 300, 200, true);
}
add_action('after_setup_theme', 'pearblog_setup');

/**
 * Enqueue scripts and styles - v2 PRO
 */
function pearblog_enqueue_assets() {
    // Main stylesheet
    wp_enqueue_style('pearblog-style', get_stylesheet_uri(), array(), PEARBLOG_VERSION);

    // Base CSS
    wp_enqueue_style('pearblog-base', PEARBLOG_URI . '/assets/css/base.css', array('pearblog-style'), PEARBLOG_VERSION);

    // Component styles
    wp_enqueue_style('pearblog-components', PEARBLOG_URI . '/assets/css/components.css', array('pearblog-base'), PEARBLOG_VERSION);

    // Utilities
    wp_enqueue_style('pearblog-utilities', PEARBLOG_URI . '/assets/css/utilities.css', array('pearblog-components'), PEARBLOG_VERSION);

    // Lazy load script
    wp_enqueue_script('pearblog-lazyload', PEARBLOG_URI . '/assets/js/lazyload.js', array(), PEARBLOG_VERSION, true);

    // Main app JS
    wp_enqueue_script('pearblog-app', PEARBLOG_URI . '/assets/js/app.js', array('pearblog-lazyload'), PEARBLOG_VERSION, true);

    // AI Personalization Engine JS (v4)
    if (defined('PEARBLOG_AI_ENGINE') && PEARBLOG_AI_ENGINE) {
        wp_enqueue_script('pearblog-personalization', PEARBLOG_URI . '/assets/js/personalization.js', array('pearblog-app'), PEARBLOG_VERSION, true);
    }

    // Pass PHP data to JavaScript
    wp_localize_script('pearblog-app', 'pearblogData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pearblog_nonce'),
        'darkMode' => get_option('pearblog_dark_mode_enabled', true),
        'stickyMobileCTA' => get_option('pearblog_sticky_mobile_cta', true),
        'aiEngine' => defined('PEARBLOG_AI_ENGINE') && PEARBLOG_AI_ENGINE,
    ));

    // Multisite branding + Dark mode - dynamic CSS
    wp_add_inline_style('pearblog-style', pearblog_get_dynamic_css());

    // Critical CSS inline for performance
    if (function_exists('pearblog_inline_critical_css')) {
        pearblog_inline_critical_css();
    }
}
add_action('wp_enqueue_scripts', 'pearblog_enqueue_assets');

/**
 * Get multisite dynamic CSS + Dark Mode
 */
function pearblog_get_dynamic_css() {
    $config = pb_get_site_config();
    $css = '';

    $css .= ':root {';
    $css .= '--pb-primary: ' . esc_attr($config['primary_color']) . ';';
    $css .= '--pb-secondary: ' . esc_attr($config['secondary_color']) . ';';
    $css .= '--pb-accent: ' . esc_attr($config['accent_color']) . ';';
    $css .= '}';

    // Dark mode variables
    if ($config['dark_mode_enabled']) {
        $css .= '@media (prefers-color-scheme: dark) {';
        $css .= 'body.pb-dark-mode-auto {';
        $css .= '--pb-bg: #111827;';
        $css .= '--pb-bg-alt: #1f2937;';
        $css .= '--pb-text: #f9fafb;';
        $css .= '--pb-text-light: #d1d5db;';
        $css .= '}';
        $css .= '}';

        $css .= 'body.pb-dark-mode {';
        $css .= '--pb-bg: #111827;';
        $css .= '--pb-bg-alt: #1f2937;';
        $css .= '--pb-text: #f9fafb;';
        $css .= '--pb-text-light: #d1d5db;';
        $css .= '}';
    }

    return $css;
}

/**
 * Multisite Configuration System
 * Central function to get all site-specific settings
 */
function pb_get_site_config() {
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $config = array(
        // Colors
        'primary_color' => get_option('pearblog_primary_color', '#2563eb'),
        'secondary_color' => get_option('pearblog_secondary_color', '#7c3aed'),
        'accent_color' => get_option('pearblog_accent_color', '#f59e0b'),

        // Branding
        'logo_url' => get_option('pearblog_logo_url', ''),
        'logo_dark_url' => get_option('pearblog_logo_dark_url', ''),

        // Hero Style
        'hero_style' => get_option('pearblog_hero_style', 'gradient'), // gradient, image, video
        'hero_title' => get_option('pearblog_hero_title', get_bloginfo('name')),
        'hero_subtitle' => get_option('pearblog_hero_subtitle', get_bloginfo('description')),
        'hero_image' => get_option('pearblog_hero_image', ''),
        'hero_video' => get_option('pearblog_hero_video', ''),

        // Layout Variant
        'layout_variant' => get_option('pearblog_layout_variant', 'default'), // default, minimal, magazine

        // Features
        'dark_mode_enabled' => get_option('pearblog_dark_mode_enabled', true),
        'toc_enabled' => get_option('pearblog_toc_enabled', true),
        'sticky_mobile_cta' => get_option('pearblog_sticky_mobile_cta', true),
        'auto_ad_injection' => get_option('pearblog_auto_ad_injection', false),
        'ad_injection_paragraphs' => get_option('pearblog_ad_injection_paragraphs', 3),

        // AI Features
        'ai_summaries_enabled' => get_option('pearblog_ai_summaries', false),
        'ai_recommendations' => get_option('pearblog_ai_recommendations', false),
    );

    return apply_filters('pearblog_site_config', $config);
}

/**
 * Excerpt length
 */
function pearblog_excerpt_length($length) {
    return 25;
}
add_filter('excerpt_length', 'pearblog_excerpt_length');

/**
 * Excerpt more
 */
function pearblog_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'pearblog_excerpt_more');

/**
 * Add lazy loading to images
 */
function pearblog_add_lazy_loading($attr, $attachment, $size) {
    if (!is_admin()) {
        $attr['loading'] = 'lazy';
        $attr['class'] = isset($attr['class']) ? $attr['class'] . ' pb-lazy-image' : 'pb-lazy-image';
    }
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'pearblog_add_lazy_loading', 10, 3);

/**
 * Add SEO meta tags
 */
function pearblog_add_seo_meta() {
    if (is_singular()) {
        global $post;
        $description = get_the_excerpt($post);
        ?>
        <meta name="description" content="<?php echo esc_attr(wp_strip_all_tags($description)); ?>">
        <?php
    }
}
add_action('wp_head', 'pearblog_add_seo_meta');

/**
 * Register widget areas
 */
function pearblog_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar', 'pearblog-theme'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here.', 'pearblog-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('Footer', 'pearblog-theme'),
        'id'            => 'footer-1',
        'description'   => __('Footer widgets.', 'pearblog-theme'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="footer-widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'pearblog_widgets_init');

/**
 * Get related posts for internal linking
 */
function pearblog_get_related_posts($post_id = null, $limit = 3) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $categories = wp_get_post_categories($post_id);

    if (empty($categories)) {
        return array();
    }

    $args = array(
        'category__in'   => $categories,
        'post__not_in'   => array($post_id),
        'posts_per_page' => $limit,
        'orderby'        => 'rand',
    );

    return get_posts($args);
}

/**
 * Brand Assets - ULTRA PRO System
 * Helper functions for accessing brand assets (logos, favicons, social images)
 */

/**
 * Get brand logo URL
 *
 * @param string $type Logo type: primary, dark, light, icon, wordmark
 * @param string $format File format: svg, png
 * @return string Logo URL
 */
function pearblog_get_brand_logo($type = 'primary', $format = 'svg') {
    // Check if custom logo is set in options (multisite override)
    $config = pb_get_site_config();

    if ($type === 'primary' && !empty($config['logo_url'])) {
        return $config['logo_url'];
    }

    if ($type === 'dark' && !empty($config['logo_dark_url'])) {
        return $config['logo_dark_url'];
    }

    // Fallback to brand assets directory
    $base_path = PEARBLOG_URI . '/../../brand-assets/logo/';

    $logos = array(
        'primary' => $base_path . 'pearblog-logo-primary.' . $format,
        'dark' => $base_path . 'pearblog-logo-dark.' . $format,
        'light' => $base_path . 'pearblog-logo-light.' . $format,
        'icon' => $base_path . 'pearblog-icon.' . $format,
        'wordmark' => $base_path . 'pearblog-wordmark.' . $format,
    );

    return $logos[$type] ?? $logos['primary'];
}

/**
 * Get favicon URL
 *
 * @param string $size Favicon size: 16, 32, 48, 64, 96, 128, 256, 512, or special types
 * @return string Favicon URL
 */
function pearblog_get_favicon($size = '32') {
    $base_path = PEARBLOG_URI . '/../../brand-assets/favicon/';

    $special_types = array(
        'ico' => $base_path . 'favicon.ico',
        'apple' => $base_path . 'apple-touch-icon.png',
        'safari' => $base_path . 'safari-pinned-tab.svg',
    );

    if (isset($special_types[$size])) {
        return $special_types[$size];
    }

    return $base_path . 'favicon-' . $size . 'x' . $size . '.png';
}

/**
 * Get social media image URL
 *
 * @param string $type Social image type: og, twitter, profile
 * @return string Social image URL
 */
function pearblog_get_social_image($type = 'og') {
    $base_path = PEARBLOG_URI . '/../../brand-assets/social/';

    $images = array(
        'og' => $base_path . 'pearblog-og-default.png',
        'twitter' => $base_path . 'pearblog-twitter-card.png',
        'profile' => $base_path . 'pearblog-profile-default.png',
    );

    return $images[$type] ?? $images['og'];
}

/**
 * Add favicons to wp_head
 */
function pearblog_add_favicons() {
    $favicon_path = PEARBLOG_URI . '/../../brand-assets/favicon/';
    ?>
    <!-- Favicons - ULTRA PRO -->
    <link rel="icon" type="image/x-icon" href="<?php echo esc_url($favicon_path . 'favicon.ico'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url($favicon_path . 'favicon-32x32.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url($favicon_path . 'favicon-16x16.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url($favicon_path . 'apple-touch-icon.png'); ?>">
    <link rel="mask-icon" href="<?php echo esc_url($favicon_path . 'safari-pinned-tab.svg'); ?>" color="#4ADE80">
    <meta name="theme-color" content="#4ADE80">
    <?php
}
add_action('wp_head', 'pearblog_add_favicons', 1);

/**
 * Add Open Graph and Twitter Card meta tags
 */
function pearblog_add_social_meta_tags() {
    $site_name = get_bloginfo('name');
    $site_desc = get_bloginfo('description');
    $og_image = pearblog_get_social_image('og');

    if (is_singular()) {
        global $post;
        $title = get_the_title();
        $description = get_the_excerpt($post);
        $url = get_permalink();

        // Use featured image if available
        if (has_post_thumbnail()) {
            $og_image = get_the_post_thumbnail_url($post, 'full');
        }
    } else {
        $title = $site_name;
        $description = $site_desc;
        $url = home_url('/');
    }
    ?>
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo esc_attr($title); ?>">
    <meta property="og:description" content="<?php echo esc_attr(wp_strip_all_tags($description)); ?>">
    <meta property="og:image" content="<?php echo esc_url($og_image); ?>">
    <meta property="og:url" content="<?php echo esc_url($url); ?>">
    <meta property="og:type" content="<?php echo is_singular() ? 'article' : 'website'; ?>">
    <meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($title); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr(wp_strip_all_tags($description)); ?>">
    <meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">
    <?php
}
add_action('wp_head', 'pearblog_add_social_meta_tags', 2);

/**
 * Monetization - AdSense support
 */
function pearblog_adsense_slot($slot_id = 'default') {
    $adsense_client = get_option('pearblog_adsense_client', '');
    $adsense_slot = get_option('pearblog_adsense_slot_' . $slot_id, '');

    if (empty($adsense_client) || empty($adsense_slot)) {
        return '';
    }

    ob_start();
    ?>
    <div class="pb-ad-slot pb-ad-<?php echo esc_attr($slot_id); ?>">
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo esc_attr($adsense_client); ?>"
                crossorigin="anonymous"></script>
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="<?php echo esc_attr($adsense_client); ?>"
             data-ad-slot="<?php echo esc_attr($adsense_slot); ?>"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script>
             (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    </div>
    <?php
    return ob_get_clean();
}
