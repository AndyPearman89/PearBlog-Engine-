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
define('PEARBLOG_VERSION', '7.0.0');
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
require_once PEARBLOG_DIR . '/inc/lead-generation.php';
require_once PEARBLOG_DIR . '/inc/email-list.php';

// AI Personalization Engine (v4)
require_once PEARBLOG_DIR . '/inc/user-context.php';
require_once PEARBLOG_DIR . '/inc/behavior-tracking.php';
require_once PEARBLOG_DIR . '/inc/dynamic-content.php';
require_once PEARBLOG_DIR . '/inc/ai-optimizer.php';

// UI PRO Components
require_once PEARBLOG_DIR . '/inc/widgets.php';
require_once PEARBLOG_DIR . '/inc/customizer.php';
require_once PEARBLOG_DIR . '/inc/gutenberg-blocks.php';
require_once PEARBLOG_DIR . '/inc/dashboard-widget.php';
require_once PEARBLOG_DIR . '/inc/ab-testing-metabox.php';
require_once PEARBLOG_DIR . '/inc/analytics-page.php';

// Poradnik.pro V4 — Decision System
require_once PEARBLOG_DIR . '/inc/poradnik-v4-helpers.php';

// Poradnik.pro Landing V5 — Conversion System
require_once PEARBLOG_DIR . '/inc/poradnik-landing-v5-handler.php';

// Poradnik.pro V3 — Front Hub Scripts
require_once PEARBLOG_DIR . '/inc/poradnik-v3-scripts.php';

// Poradnik.pro Landing V5 — Admin Dashboard
require_once PEARBLOG_DIR . '/inc/poradnik-landing-v5-admin.php';

// Poradnik.pro Advanced Monetization Suite
require_once PEARBLOG_DIR . '/inc/poradnik-ads-layout-pro.php';
require_once PEARBLOG_DIR . '/inc/poradnik-affiliate-copy-generator.php';
require_once PEARBLOG_DIR . '/inc/poradnik-rpm-lead-fusion.php';

// Poradnik.PRO URL Routing — Clean URL structure for all page types
require_once PEARBLOG_DIR . '/inc/poradnik-pro-routing.php';

// Navigation & Footer
require_once PEARBLOG_DIR . '/inc/poradnik-pro-navigation.php';

// PT24.PRO platform — the theme is SHARED with poradnik.pro, so the PT24
// directory subsystem must load ONLY for the PT24 install. The install lives
// at home_url() = https://wordpress2614653.home.pl/pt24 (the 'pt24' marker is in
// the PATH, not the host), so match against the full home_url string — this is
// stable regardless of the proxy/Cloudflare Host header.
$pearblog_active_url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : (string) ( $_SERVER['HTTP_HOST'] ?? '' );
if ( false !== stripos( $pearblog_active_url, 'pt24' ) ) {
    require_once PEARBLOG_DIR . '/inc/pt24-database.php';      // Lead / stats tables.
    require_once PEARBLOG_DIR . '/inc/pt24-seo-meta.php';      // Host-guarded SEO meta.
    require_once PEARBLOG_DIR . '/inc/pt24-landing-cpt.php';   // pt24_landing CPT + /{city}/{service} routes.
    require_once PEARBLOG_DIR . '/inc/pt24-landing-admin.php'; // Admin generator UI.
    require_once PEARBLOG_DIR . '/inc/pt24-form-handler.php';  // Lead-form AJAX handlers.
    require_once PEARBLOG_DIR . '/inc/pt24-api.php';           // REST endpoints.
    require_once PEARBLOG_DIR . '/inc/pt24-footer.php';        // PT24-branded site footer.
    require_once PEARBLOG_DIR . '/inc/pt24-sitemap.php';       // XML sitemap + robots.txt.
    require_once PEARBLOG_DIR . '/inc/pt24-adsense.php';       // Configurable AdSense + ads.txt.
    require_once PEARBLOG_DIR . '/inc/pt24-blog.php';          // Blog archive routing.
    require_once PEARBLOG_DIR . '/inc/pt24-firm-cpt.php';      // Company profile CPT (/firma/{slug}/).
    require_once PEARBLOG_DIR . '/inc/pt24-add-firm.php';      // "Dodaj firmę" form + AJAX handler.

    // Bootstrap the landing CPT explicitly. Its own bootstrap hooks init() onto
    // the `init` action, and init() then registers register_post_type() /
    // add_rewrite_rules() onto `init` from *inside* init — a same-priority
    // callback added mid-action that never fires. Calling init() here (before
    // `init` runs) registers those hooks in time so the CPT and its rewrite
    // rules are actually created.
    if ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
        PearBlog_PT24_Landing_CPT::init();
    }

    if ( class_exists( 'PearBlog_PT24_Firm_CPT' ) ) {
        PearBlog_PT24_Firm_CPT::init();
    }

    if ( class_exists( 'PearBlog_PT24_Add_Firm' ) ) {
        PearBlog_PT24_Add_Firm::init();
    }
}
unset( $pearblog_active_url );

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

    // Block editor support
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('editor-color-palette', array(
        array( 'name' => __('Primary', 'pearblog-theme'), 'slug' => 'primary', 'color' => '#2563eb' ),
        array( 'name' => __('Secondary', 'pearblog-theme'), 'slug' => 'secondary', 'color' => '#7c3aed' ),
        array( 'name' => __('Accent', 'pearblog-theme'), 'slug' => 'accent', 'color' => '#f59e0b' ),
        array( 'name' => __('Dark', 'pearblog-theme'), 'slug' => 'dark', 'color' => '#111827' ),
        array( 'name' => __('Light', 'pearblog-theme'), 'slug' => 'light', 'color' => '#f9fafb' ),
    ));

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
 * Enqueue scripts and styles - v3 PRO
 */
function pearblog_enqueue_assets() {
    // Main stylesheet
    wp_enqueue_style('pearblog-style', get_stylesheet_uri(), array(), PEARBLOG_VERSION);

    // Base CSS
    wp_enqueue_style('pearblog-base', PEARBLOG_URI . '/assets/css/base.css', array('pearblog-style'), PEARBLOG_VERSION);

    // Component styles
    wp_enqueue_style('pearblog-components', PEARBLOG_URI . '/assets/css/components.css', array('pearblog-base'), PEARBLOG_VERSION);

    // V3 High-Conversion Components (enqueue if V3 layout is enabled)
    if (get_option('pearblog_homepage_version', 'v3') === 'v3') {
        wp_enqueue_style('pearblog-v3-components', PEARBLOG_URI . '/assets/css/v3-components.css', array('pearblog-components'), PEARBLOG_VERSION);
    }

    // V7 Dark UI Kit (enqueue if V7 layout is enabled)
    if (get_option('pearblog_homepage_version', 'v3') === 'v7') {
        wp_enqueue_style('pearblog-v7-ui-kit', PEARBLOG_URI . '/assets/css/v7-ui-kit.css', array('pearblog-style'), PEARBLOG_VERSION);
    }

    // Utilities
    wp_enqueue_style('pearblog-utilities', PEARBLOG_URI . '/assets/css/utilities.css', array('pearblog-components'), PEARBLOG_VERSION);

    // Poradnik.PRO navigation & footer
    wp_enqueue_style('pp-navigation', PEARBLOG_URI . '/assets/css/poradnik-pro-navigation.css', array('pearblog-utilities'), PEARBLOG_VERSION);

    // Lazy load script
    wp_enqueue_script('pearblog-lazyload', PEARBLOG_URI . '/assets/js/lazyload.js', array(), PEARBLOG_VERSION, true);

    // Main app JS
    wp_enqueue_script('pearblog-app', PEARBLOG_URI . '/assets/js/app.js', array('pearblog-lazyload'), PEARBLOG_VERSION, true);

    // AI Personalization Engine JS (v4)
    if (defined('PEARBLOG_AI_ENGINE') && PEARBLOG_AI_ENGINE) {
        wp_enqueue_script('pearblog-personalization', PEARBLOG_URI . '/assets/js/personalization.js', array('pearblog-app'), PEARBLOG_VERSION, true);
    }

    // PT24.PRO Integration CSS & JS
    wp_enqueue_style('pt24-cta', PEARBLOG_URI . '/assets/css/pt24-cta.css', array(), PEARBLOG_VERSION);
    wp_enqueue_script('pt24-tracking', PEARBLOG_URI . '/assets/js/pt24-cta-tracking.js', array(), PEARBLOG_VERSION, true);

    // PT24.PRO unified site styles — landings + static pages + homepage.
    // Host-guarded: load only on the PT24 install (home_url path contains 'pt24'),
    // so the shared theme on poradnik.pro / mucharski.pl is unaffected.
    if ( false !== stripos( (string) home_url( '/' ), 'pt24' ) ) {
        wp_enqueue_style('pt24-site', PEARBLOG_URI . '/assets/css/pt24-site.css', array('pearblog-components'), PEARBLOG_VERSION);
        wp_enqueue_script('pt24-ux', PEARBLOG_URI . '/assets/js/pt24-ux.js', array(), PEARBLOG_VERSION, true);
    }

    // Poradnik V4 HI-PRO Content Hub
    if (is_page_template('page-poradnik-v4-hipro.php')) {
        wp_enqueue_style('poradnik-v4-hipro', PEARBLOG_URI . '/assets/css/poradnik-v4-hipro.css', array('pearblog-style'), PEARBLOG_VERSION);
    }

    // Google Fonts — Poppins (display) + Inter (UI)
    wp_enqueue_style(
        'pearblog-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap',
        array(),
        null
    );

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

    // Customizer dynamic CSS
    if (function_exists('pearblog_customizer_css')) {
        wp_add_inline_style('pearblog-style', pearblog_customizer_css());
    }

    // Critical CSS inline for performance
    if (function_exists('pearblog_inline_critical_css')) {
        pearblog_inline_critical_css();
    }
}
add_action('wp_enqueue_scripts', 'pearblog_enqueue_assets');

/**
 * Drop poradnik.pro-only front-end scripts/styles on the PT24 install.
 *
 * The theme is shared, so several poradnik.pro bundles (v3 calculators,
 * conversion trackers, the AI decision platform, v4 hub) get enqueued on PT24
 * where they serve no purpose. Dequeue them by matching the registered source
 * filename so the PT24 pages stay lean. Host-guarded; runs after the default
 * enqueue priority. poradnik.pro / mucharski.pl are untouched.
 */
function pt24_dequeue_foreign_assets() {
    if ( false === stripos( (string) home_url( '/' ), 'pt24' ) ) {
        return;
    }

    $script_needles = array(
        'v3-calculator.js', 'v3-conversion-tracker.js', 'v3-front-hub.js',
        'poradnik-v4.js', 'personalization.js', 'decision-platform.js',
    );
    $scripts = wp_scripts();
    foreach ( (array) $scripts->registered as $handle => $script ) {
        $src = isset( $script->src ) ? (string) $script->src : '';
        foreach ( $script_needles as $needle ) {
            if ( '' !== $src && false !== strpos( $src, $needle ) ) {
                wp_dequeue_script( $handle );
                break;
            }
        }
    }

    $style_needles = array( 'decision-platform.css', 'poradnik-v4.css' );
    $styles = wp_styles();
    foreach ( (array) $styles->registered as $handle => $style ) {
        $src = isset( $style->src ) ? (string) $style->src : '';
        foreach ( $style_needles as $needle ) {
            if ( '' !== $src && false !== strpos( $src, $needle ) ) {
                wp_dequeue_style( $handle );
                break;
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'pt24_dequeue_foreign_assets', 100);

/**
 * Keep every front-end URL on the public pt24.pro domain.
 *
 * The PT24 install is served through Cloudflare, but WordPress generates and
 * stores URLs with the origin host (wordpress2614653.home.pl/pt24). That host
 * otherwise leaks into the menu, footer, internal links and — critically — the
 * lead-form action, which would then POST cross-origin. Rewrite the origin base
 * to https://pt24.pro in the final front-end HTML. Host-guarded; admin, AJAX and
 * REST responses are left untouched so wp-admin keeps working.
 */
function pt24_buffer_public_host() {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }
    if ( false === stripos( (string) home_url( '/' ), 'pt24' ) ) {
        return;
    }
    ob_start( 'pt24_rewrite_public_host' );
}
function pt24_rewrite_public_host( $html ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }
    return str_replace(
        array(
            'https://wordpress2614653.home.pl/pt24',
            'http://wordpress2614653.home.pl/pt24',
            '//wordpress2614653.home.pl/pt24',
            'wordpress2614653.home.pl%2Fpt24',
            'wordpress2614653.home.pl%2fpt24',
            'wordpress2614653.home.pl',
        ),
        array(
            'https://pt24.pro',
            'https://pt24.pro',
            '//pt24.pro',
            'pt24.pro',
            'pt24.pro',
            'pt24.pro',
        ),
        $html
    );
}
add_action( 'template_redirect', 'pt24_buffer_public_host', 1 );

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
        'description'   => __('Footer widgets (column 1).', 'pearblog-theme'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="footer-widget-title">',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Column 2', 'pearblog-theme'),
        'id'            => 'footer-2',
        'description'   => __('Footer widgets (column 2).', 'pearblog-theme'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="footer-widget-title">',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => __('After Post Content', 'pearblog-theme'),
        'id'            => 'after-post',
        'description'   => __('Widgets displayed after post content.', 'pearblog-theme'),
        'before_widget' => '<div id="%1$s" class="after-post-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="after-post-widget-title">',
        'after_title'   => '</h3>',
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
 * Build a public URL for an asset deployed under wp-content/brand-assets.
 *
 * @param string $relative_path Relative path inside brand-assets.
 * @return string
 */
function pearblog_get_brand_asset_url($relative_path) {
    return content_url('brand-assets/' . ltrim($relative_path, '/'));
}

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
    $base_path = pearblog_get_brand_asset_url('logo/');

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
    $base_path = pearblog_get_brand_asset_url('favicon/');

    $special_types = array(
        'ico' => $base_path . 'favicon.svg',
        'apple' => $base_path . 'favicon.svg',
        'safari' => $base_path . 'safari-pinned-tab.svg',
    );

    if (isset($special_types[$size])) {
        return $special_types[$size];
    }

    return $base_path . 'favicon.svg';
}

/**
 * Get social media image URL
 *
 * @param string $type Social image type: og, twitter, profile
 * @return string Social image URL
 */
function pearblog_get_social_image($type = 'og') {
    $images = array(
        'og' => 'brand-assets/social/pearblog-og-default.svg',
        'twitter' => 'brand-assets/social/pearblog-twitter-card.svg',
        'profile' => 'brand-assets/logo/pearblog-icon.svg',
    );

    $selected = $images[$type] ?? $images['og'];
    $absolute = WP_CONTENT_DIR . '/' . $selected;

    if (file_exists($absolute)) {
        return content_url($selected);
    }

    if ('og' === $type) {
        $fallback_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_key' => '_thumbnail_id',
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ));

        if (!empty($fallback_posts)) {
            $fallback_image = get_the_post_thumbnail_url((int) $fallback_posts[0], 'full');
            if (!empty($fallback_image)) {
                return $fallback_image;
            }
        }
    }

    return '';
}

/**
 * Add favicons to wp_head
 */
function pearblog_add_favicons() {
    $favicon_svg = pearblog_get_favicon('32');
    $favicon_mask = pearblog_get_favicon('safari');
    ?>
    <!-- Favicons - ULTRA PRO -->
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url($favicon_svg); ?>">
    <link rel="apple-touch-icon" href="<?php echo esc_url($favicon_svg); ?>">
    <link rel="mask-icon" href="<?php echo esc_url($favicon_mask); ?>" color="#4ADE80">
    <meta name="theme-color" content="#4ADE80">
    <?php
}
add_action('wp_head', 'pearblog_add_favicons', 1);

/**
 * Add Open Graph and Twitter Card meta tags with canonical image support
 */
function pearblog_add_social_meta_tags() {
    $pt24_service = function_exists('get_query_var') ? get_query_var('pt24_service', '') : '';
    $pt24_city = function_exists('get_query_var') ? get_query_var('pt24_city', '') : '';

    // On the PT24 install, pt24_output_seo_meta() is the single source of
    // canonical / Open Graph / Twitter tags for EVERY page (home, landings AND
    // static pages). Always defer to it here to avoid duplicate, origin-host
    // canonical/OG tags. The old guard only covered home/landings and missed
    // static pages (and landings drop the pt24_* query vars after the CPT swaps
    // the main query), which produced conflicting canonicals.
    if ( function_exists('pt24_is_pt24_site') && pt24_is_pt24_site() ) {
        return;
    }

    if (function_exists('pt24_output_seo_meta') && (is_front_page() || !empty($pt24_service) || !empty($pt24_city))) {
        return;
    }

    $site_name = get_bloginfo('name');
    $site_desc = get_bloginfo('description');
    $og_image = pearblog_get_social_image('og');
    $image_width = 1200;
    $image_height = 630;
    $image_alt = $site_name;

    if (is_singular()) {
        global $post;
        $title = get_the_title();
        $description = get_the_excerpt($post);
        $url = get_permalink();

        // Use featured image if available
        if (has_post_thumbnail()) {
            $thumbnail_id = get_post_thumbnail_id($post);
            $og_image = get_the_post_thumbnail_url($post, 'full');

            // Get image dimensions
            $image_meta = wp_get_attachment_metadata($thumbnail_id);
            if ($image_meta) {
                $image_width = $image_meta['width'] ?? 1200;
                $image_height = $image_meta['height'] ?? 630;
            }

            // Get image alt text (canonical description)
            $image_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
            if (empty($image_alt)) {
                $image_alt = get_the_title($thumbnail_id);
            }
            if (empty($image_alt)) {
                $image_alt = $title;
            }
        }
    } else {
        $title = $site_name;
        $description = $site_desc;
        $url = home_url('/');
    }
    ?>
    <!-- Canonical Link -->
    <link rel="canonical" href="<?php echo esc_url($url); ?>">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo esc_attr($title); ?>">
    <meta property="og:description" content="<?php echo esc_attr(wp_strip_all_tags($description)); ?>">
    <meta property="og:image" content="<?php echo esc_url($og_image); ?>">
    <meta property="og:image:width" content="<?php echo esc_attr($image_width); ?>">
    <meta property="og:image:height" content="<?php echo esc_attr($image_height); ?>">
    <meta property="og:image:alt" content="<?php echo esc_attr($image_alt); ?>">
    <meta property="og:url" content="<?php echo esc_url($url); ?>">
    <meta property="og:type" content="<?php echo is_singular() ? 'article' : 'website'; ?>">
    <meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($title); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr(wp_strip_all_tags($description)); ?>">
    <meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">
    <meta name="twitter:image:alt" content="<?php echo esc_attr($image_alt); ?>">
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
