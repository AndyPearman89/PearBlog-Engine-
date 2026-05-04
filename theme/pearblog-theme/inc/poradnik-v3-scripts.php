<?php
/**
 * Poradnik V3 - Scripts and Styles Enqueue
 *
 * Handles loading of Dark UI CSS and JavaScript for Front Page Decision Hub
 *
 * @package PearBlog
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue Poradnik V3 Dark UI styles and scripts
 */
function pearblog_v3_enqueue_assets() {
    // Enqueue Dark UI CSS
    wp_enqueue_style(
        'poradnik-dark-ui',
        get_template_directory_uri() . '/assets/css/poradnik-dark-ui.css',
        [],
        PEARBLOG_VERSION,
        'all'
    );

    // Enqueue jQuery (WordPress core)
    wp_enqueue_script('jquery');

    // Enqueue Conversion Tracker
    wp_enqueue_script(
        'poradnik-v3-tracker',
        get_template_directory_uri() . '/assets/js/v3-conversion-tracker.js',
        [],
        PEARBLOG_VERSION,
        true
    );

    // Enqueue Calculator
    wp_enqueue_script(
        'poradnik-v3-calculator',
        get_template_directory_uri() . '/assets/js/v3-calculator.js',
        ['jquery', 'poradnik-v3-tracker'],
        PEARBLOG_VERSION,
        true
    );

    // Localize tracker config
    wp_localize_script('poradnik-v3-tracker', 'pearblogV3Tracker', [
        'apiUrl' => rest_url('pearblog/v3/tracking/event'),
        'nonce' => wp_create_nonce('wp_rest'),
        'service' => get_query_var('service', 'unknown'),
        'abVariant' => get_query_var('ab_variant', 'control'),
        'utmSource' => isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : '',
        'utmMedium' => isset($_GET['utm_medium']) ? sanitize_text_field($_GET['utm_medium']) : '',
        'utmCampaign' => isset($_GET['utm_campaign']) ? sanitize_text_field($_GET['utm_campaign']) : '',
    ]);
}
add_action('wp_enqueue_scripts', 'pearblog_v3_enqueue_assets');

/**
 * Enqueue Front Hub scripts on Front Page Decision Hub template
 */
function pearblog_v3_front_hub_assets() {
    // Only load on Front Page template
    if (!is_page_template('page-front-decision-hub.php')) {
        return;
    }

    // Enqueue Front Hub JavaScript
    wp_enqueue_script(
        'poradnik-v3-front-hub',
        get_template_directory_uri() . '/assets/js/v3-front-hub.js',
        ['jquery', 'poradnik-v3-tracker'],
        PEARBLOG_VERSION,
        true
    );

    // Localize script config
    wp_localize_script('poradnik-v3-front-hub', 'pearblogFrontHub', [
        'searchApiUrl' => rest_url('pearblog/v3/search/suggest'),
        'nonce' => wp_create_nonce('wp_rest'),
    ]);
}
add_action('wp_enqueue_scripts', 'pearblog_v3_front_hub_assets');

/**
 * Enqueue Dark UI styles on specific Poradnik templates
 */
function pearblog_v3_conditional_assets() {
    // Templates that use Dark UI
    $dark_ui_templates = [
        'page-poradnik-dark-ui.php',
        'page-front-decision-hub.php',
        'page-poradnik-calculator.php',
        'page-poradnik-comparison.php',
        'page-poradnik-ranking.php',
    ];

    $is_dark_template = false;
    foreach ($dark_ui_templates as $template) {
        if (is_page_template($template)) {
            $is_dark_template = true;
            break;
        }
    }

    if ($is_dark_template) {
        // Add dark mode body class
        add_filter('body_class', function($classes) {
            $classes[] = 'poradnik-dark-ui';
            return $classes;
        });
    }
}
add_action('wp', 'pearblog_v3_conditional_assets');

/**
 * Register custom query vars for tracking
 */
function pearblog_v3_query_vars($vars) {
    $vars[] = 'service';
    $vars[] = 'ab_variant';
    return $vars;
}
add_filter('query_vars', 'pearblog_v3_query_vars');
