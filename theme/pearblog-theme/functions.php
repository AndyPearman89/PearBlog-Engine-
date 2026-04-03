<?php
/**
 * PearBlog Theme Functions
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme constants
define('PEARBLOG_VERSION', '1.0.0');
define('PEARBLOG_DIR', get_template_directory());
define('PEARBLOG_URI', get_template_directory_uri());

// Include helper files
require_once PEARBLOG_DIR . '/inc/ui.php';
require_once PEARBLOG_DIR . '/inc/layout.php';
require_once PEARBLOG_DIR . '/inc/components.php';

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
 * Enqueue scripts and styles
 */
function pearblog_enqueue_assets() {
    // Main stylesheet
    wp_enqueue_style('pearblog-style', get_stylesheet_uri(), array(), PEARBLOG_VERSION);

    // Component styles
    wp_enqueue_style('pearblog-components', PEARBLOG_URI . '/assets/css/components.css', array('pearblog-style'), PEARBLOG_VERSION);

    // Minimal JS for lazy loading and interactions
    wp_enqueue_script('pearblog-main', PEARBLOG_URI . '/assets/js/main.js', array(), PEARBLOG_VERSION, true);

    // Multisite branding - dynamic CSS
    wp_add_inline_style('pearblog-style', pearblog_get_multisite_css());
}
add_action('wp_enqueue_scripts', 'pearblog_enqueue_assets');

/**
 * Get multisite dynamic CSS
 */
function pearblog_get_multisite_css() {
    $css = '';

    // Get site-specific branding options
    $primary_color = get_option('pearblog_primary_color', '#2563eb');
    $secondary_color = get_option('pearblog_secondary_color', '#7c3aed');

    if ($primary_color || $secondary_color) {
        $css .= ':root {';

        if ($primary_color) {
            $css .= '--pb-primary: ' . esc_attr($primary_color) . ';';
        }

        if ($secondary_color) {
            $css .= '--pb-secondary: ' . esc_attr($secondary_color) . ';';
        }

        $css .= '}';
    }

    return $css;
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
