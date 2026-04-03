<?php
/**
 * Layout Helper Functions
 *
 * @package PearBlog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render header
 */
function pearblog_render_header() {
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <?php wp_head(); ?>
    </head>
    <body <?php body_class(); ?> data-post-id="<?php echo esc_attr(get_the_ID()); ?>">
    <?php wp_body_open(); ?>

    <header class="pb-nav">
        <div class="pb-container">
            <nav class="pb-nav-container" role="navigation" aria-label="<?php esc_attr_e('Main Navigation', 'pearblog-theme'); ?>">
                <div class="pb-logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                        <?php echo pearblog_get_logo(); ?>
                    </a>
                </div>

                <button class="pb-menu-toggle" aria-expanded="false" aria-controls="primary-menu" aria-label="<?php esc_attr_e('Toggle Menu', 'pearblog-theme'); ?>">
                    ☰
                </button>

                <?php pearblog_render_nav_menu('primary'); ?>
            </nav>
        </div>
    </header>
    <?php
}

/**
 * Render footer
 */
function pearblog_render_footer() {
    ?>
    <footer class="pb-footer">
        <div class="pb-container">
            <?php if (is_active_sidebar('footer-1')) : ?>
                <div class="pb-footer-widgets">
                    <?php dynamic_sidebar('footer-1'); ?>
                </div>
            <?php endif; ?>

            <?php pearblog_render_nav_menu('footer'); ?>

            <div class="pb-footer-copyright">
                <p>
                    &copy; <?php echo esc_html(date('Y')); ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>.
                    <?php _e('All rights reserved.', 'pearblog-theme'); ?>
                </p>
            </div>
        </div>
    </footer>

    <?php wp_footer(); ?>
    </body>
    </html>
    <?php
}

/**
 * Get main container class
 */
function pearblog_get_container_class() {
    $classes = array('pb-container');

    if (is_singular()) {
        $classes[] = 'pb-single-container';
    } elseif (is_archive() || is_home()) {
        $classes[] = 'pb-archive-container';
    }

    return implode(' ', apply_filters('pearblog_container_class', $classes));
}

/**
 * Get content wrapper class
 */
function pearblog_get_content_class() {
    $classes = array('pb-content');

    if (is_singular()) {
        $classes[] = 'pb-single-content';
    } elseif (is_archive() || is_home()) {
        $classes[] = 'pb-archive-content';
    }

    return implode(' ', apply_filters('pearblog_content_class', $classes));
}

/**
 * Check if sidebar should be displayed
 */
function pearblog_has_sidebar() {
    // No sidebar on home/front page for cleaner SEO layout
    if (is_front_page() || is_home()) {
        return false;
    }

    // Check if sidebar has widgets
    return is_active_sidebar('sidebar-1');
}

/**
 * Render sidebar
 */
function pearblog_render_sidebar() {
    if (!pearblog_has_sidebar()) {
        return;
    }
    ?>
    <aside class="pb-sidebar" role="complementary" aria-label="<?php esc_attr_e('Sidebar', 'pearblog-theme'); ?>">
        <?php dynamic_sidebar('sidebar-1'); ?>

        <?php
        // Ad slot in sidebar
        get_template_part('template-parts/block-ads', null, array(
            'slot_id' => 'sidebar',
            'position' => 'sidebar',
        ));
        ?>
    </aside>
    <?php
}

/**
 * Get layout class for main content area
 */
function pearblog_get_layout_class() {
    if (pearblog_has_sidebar()) {
        return 'pb-layout-with-sidebar';
    }
    return 'pb-layout-full-width';
}

/**
 * Render TL;DR section
 */
function pearblog_render_tldr($items = array(), $post_id = null) {
    if (empty($items)) {
        // Try to get from post meta
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        $items = get_post_meta($post_id, 'pearblog_tldr', true);
    }

    if (empty($items)) {
        return;
    }
    ?>
    <div class="pb-tldr">
        <h2 class="pb-tldr-title"><?php _e('TL;DR - Quick Summary', 'pearblog-theme'); ?></h2>
        <ul>
            <?php foreach ($items as $item) : ?>
                <li><?php echo esc_html($item); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

/**
 * Render CTA section
 */
function pearblog_render_cta($args = array()) {
    $defaults = array(
        'title' => __('Ready to Get Started?', 'pearblog-theme'),
        'button_text' => __('Learn More', 'pearblog-theme'),
        'button_url' => home_url('/'),
    );

    $args = wp_parse_args($args, $defaults);
    ?>
    <div class="pb-cta">
        <div class="pb-container">
            <h2 class="pb-cta-title"><?php echo esc_html($args['title']); ?></h2>
            <a href="<?php echo esc_url($args['button_url']); ?>" class="pb-cta-button">
                <?php echo esc_html($args['button_text']); ?>
            </a>
        </div>
    </div>
    <?php
}
