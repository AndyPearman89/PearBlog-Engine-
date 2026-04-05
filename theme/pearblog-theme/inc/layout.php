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

    <!-- Reading progress bar -->
    <div id="pb-reading-progress-bar" role="progressbar" aria-label="<?php esc_attr_e('Reading progress', 'pearblog-theme'); ?>" aria-valuemin="0" aria-valuemax="100">
        <div id="pb-reading-progress"></div>
    </div>

    <!-- Search panel (hidden by default) -->
    <div id="pb-search-panel" class="pb-search-panel" role="search" aria-hidden="true">
        <div class="pb-container">
            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="pb-search-form">
                <label for="pb-search-input" class="screen-reader-text"><?php esc_html_e('Search', 'pearblog-theme'); ?></label>
                <input type="search" id="pb-search-input" name="s" class="pb-search-input"
                       placeholder="<?php esc_attr_e('Search articles…', 'pearblog-theme'); ?>"
                       value="<?php echo esc_attr(get_search_query()); ?>"
                       autocomplete="off" />
                <button type="submit" class="pb-search-submit" aria-label="<?php esc_attr_e('Submit search', 'pearblog-theme'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </button>
            </form>
            <button class="pb-search-close" id="pb-search-close" aria-label="<?php esc_attr_e('Close search', 'pearblog-theme'); ?>">✕</button>
        </div>
    </div>

    <header class="pb-nav" id="pb-header">
        <div class="pb-container">
            <nav class="pb-nav-container" role="navigation" aria-label="<?php esc_attr_e('Main Navigation', 'pearblog-theme'); ?>">
                <div class="pb-logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                        <?php echo pearblog_get_logo(); ?>
                    </a>
                </div>

                <?php pearblog_render_nav_menu('primary'); ?>

                <div class="pb-nav-actions">
                    <!-- Search toggle -->
                    <button class="pb-icon-btn pb-search-toggle" id="pb-search-toggle" aria-label="<?php esc_attr_e('Open search', 'pearblog-theme'); ?>" aria-expanded="false" aria-controls="pb-search-panel">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </button>

                    <!-- Dark mode toggle -->
                    <button class="pb-icon-btn pb-dark-toggle" id="pb-dark-mode-toggle" aria-label="<?php esc_attr_e('Toggle dark mode', 'pearblog-theme'); ?>">
                        <span class="pb-dark-icon" aria-hidden="true">🌙</span>
                    </button>

                    <!-- Mobile menu toggle -->
                    <button class="pb-menu-toggle" aria-expanded="false" aria-controls="primary-menu" aria-label="<?php esc_attr_e('Toggle Menu', 'pearblog-theme'); ?>">
                        <span class="pb-hamburger" aria-hidden="true">
                            <span></span><span></span><span></span>
                        </span>
                    </button>
                </div>
            </nav>
        </div>
    </header>
    <?php
}

/**
 * Render footer
 */
function pearblog_render_footer() {
    $site_name = get_bloginfo('name');
    $site_desc = get_bloginfo('description');
    ?>
    <footer class="pb-footer">
        <div class="pb-container">
            <div class="pb-footer-grid">
                <!-- Brand column -->
                <div class="pb-footer-brand">
                    <div class="pb-footer-logo">
                        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                            <?php echo pearblog_get_logo(); ?>
                        </a>
                    </div>
                    <?php if ($site_desc) : ?>
                        <p class="pb-footer-desc"><?php echo esc_html($site_desc); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Widget areas -->
                <?php if (is_active_sidebar('footer-1')) : ?>
                    <div class="pb-footer-widgets">
                        <?php dynamic_sidebar('footer-1'); ?>
                    </div>
                <?php endif; ?>

                <?php if (is_active_sidebar('footer-2')) : ?>
                    <div class="pb-footer-widgets">
                        <?php dynamic_sidebar('footer-2'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer nav -->
            <?php if (has_nav_menu('footer')) : ?>
                <div class="pb-footer-nav">
                    <?php pearblog_render_nav_menu('footer'); ?>
                </div>
            <?php endif; ?>

            <div class="pb-footer-bottom">
                <p class="pb-footer-copyright">
                    &copy; <?php echo esc_html(date('Y')); ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($site_name); ?></a>.
                    <?php _e('All rights reserved.', 'pearblog-theme'); ?>
                    &nbsp;·&nbsp;
                    <span class="pb-footer-powered"><?php _e('Powered by PearBlog Engine', 'pearblog-theme'); ?></span>
                </p>
                <button class="pb-back-to-top" id="pb-back-to-top" aria-label="<?php esc_attr_e('Back to top', 'pearblog-theme'); ?>">
                    ↑
                </button>
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
