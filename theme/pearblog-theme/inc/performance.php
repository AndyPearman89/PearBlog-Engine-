<?php
/**
 * Performance Optimization Module
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inline Critical CSS for above-the-fold content
 */
function pearblog_inline_critical_css() {
    // Generate critical CSS for current page type
    $critical_css = pearblog_generate_critical_css();

    if (!empty($critical_css)) {
        echo '<style id="pearblog-critical-css">' . $critical_css . '</style>';
    }
}

/**
 * Generate critical CSS based on page type
 */
function pearblog_generate_critical_css() {
    $css = '';

    // Base critical styles (always needed)
    $css .= 'body{margin:0;font-family:var(--pb-font-base);color:var(--pb-text);background:var(--pb-bg)}';
    $css .= '.pb-container{max-width:var(--pb-container-max);margin:0 auto;padding:0 var(--pb-container-padding)}';
    $css .= '.pb-nav{background:var(--pb-bg);box-shadow:var(--pb-shadow-sm);position:sticky;top:0;z-index:100}';

    // Hero critical CSS (for homepage/landing pages)
    if (is_front_page() || is_home()) {
        $css .= '.pb-hero{padding:var(--pb-space-3xl) 0;background:linear-gradient(135deg,var(--pb-primary),var(--pb-secondary));color:var(--pb-text-white)}';
        $css .= '.pb-hero-title{font-size:2.5rem;margin-bottom:var(--pb-space-lg);color:var(--pb-text-white)}';
    }

    // Single post critical CSS
    if (is_singular()) {
        $css .= '.pb-post-title{font-size:2rem;margin-bottom:var(--pb-space-md)}';
        $css .= '.pb-toc{position:sticky;top:80px}';
    }

    return apply_filters('pearblog_critical_css', $css);
}

/**
 * Preload critical resources
 */
function pearblog_preload_resources() {
    // Preload hero image for homepage
    if (is_front_page() || is_home()) {
        $config = pb_get_site_config();
        if (!empty($config['hero_image'])) {
            echo '<link rel="preload" as="image" href="' . esc_url($config['hero_image']) . '">';
        }
    }

    // Preload fonts if custom fonts are used
    $custom_font_url = get_option('pearblog_custom_font_url', '');
    if (!empty($custom_font_url)) {
        echo '<link rel="preload" as="font" href="' . esc_url($custom_font_url) . '" crossorigin>';
    }
}
add_action('wp_head', 'pearblog_preload_resources', 1);

/**
 * Add DNS prefetch for external resources
 */
function pearblog_dns_prefetch() {
    echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
    echo '<link rel="dns-prefetch" href="//www.googletagmanager.com">';
    echo '<link rel="dns-prefetch" href="//pagead2.googlesyndication.com">';
}
add_action('wp_head', 'pearblog_dns_prefetch', 1);

/**
 * Defer non-critical CSS
 */
function pearblog_defer_non_critical_css() {
    // This would be implemented with a filter on style_loader_tag
    // to add media="print" onload="this.media='all'" for non-critical styles
}

/**
 * Optimize images with lazy loading attributes
 */
function pearblog_optimize_content_images($content) {
    if (is_admin() || is_feed()) {
        return $content;
    }

    // Add loading="lazy" to images in content
    $content = preg_replace('/<img((?![^>]*loading=)[^>]*)>/i', '<img$1 loading="lazy">', $content);

    // Add width and height attributes if missing (CLS optimization)
    // This is a basic implementation - in production, you'd want to extract actual dimensions
    $content = preg_replace('/<img(?![^>]*width=)([^>]*)>/i', '<img$1 width="auto">', $content);
    $content = preg_replace('/<img(?![^>]*height=)([^>]*)>/i', '<img$1 height="auto">', $content);

    return $content;
}
add_filter('the_content', 'pearblog_optimize_content_images', 20);

/**
 * Remove query strings from static resources for better caching
 */
function pearblog_remove_query_strings($src) {
    if (strpos($src, '?ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
// Uncomment if needed - some caching plugins may conflict
// add_filter('script_loader_src', 'pearblog_remove_query_strings', 15, 1);
// add_filter('style_loader_src', 'pearblog_remove_query_strings', 15, 1);

/**
 * Disable embeds for performance
 */
function pearblog_disable_embeds() {
    if (get_option('pearblog_disable_embeds', false)) {
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    }
}
add_action('init', 'pearblog_disable_embeds');

/**
 * Limit post revisions for database performance
 */
if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', 3);
}

/**
 * Enable Gzip compression
 */
function pearblog_enable_gzip() {
    if (!get_option('pearblog_gzip_enabled', false)) {
        return;
    }

    if (!ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler')) {
        @ob_start('ob_gzhandler');
    }
}
add_action('init', 'pearblog_enable_gzip');

/**
 * Cache control headers
 */
function pearblog_cache_headers() {
    if (is_admin()) {
        return;
    }

    // Set cache headers for static pages
    if (!is_user_logged_in() && !is_search() && !is_404()) {
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    }
}
add_action('send_headers', 'pearblog_cache_headers');

/**
 * Get Lighthouse / PageSpeed Insights scores.
 *
 * Queries the Google PageSpeed Insights API (v5) for the given URL.  Results
 * are cached for 24 hours to avoid hitting the free-tier rate limit (25 000
 * requests/day, but a single call returns all four categories).
 *
 * An API key stored in option `pearblog_pagespeed_api_key` is optional but
 * strongly recommended for production use to avoid per-IP quota limits.
 *
 * @param string|null $url URL to analyse. Defaults to the site home page.
 * @return array Associative array with keys: performance, seo, accessibility, best_practices (0-100 each).
 */
function pearblog_get_lighthouse_score($url = null) {
    if (!$url) {
        $url = home_url('/');
    }

    $default_scores = array(
        'performance'    => 0,
        'seo'            => 0,
        'accessibility'  => 0,
        'best_practices' => 0,
    );

    // Check cache first (24-hour TTL).
    $cache_key = 'pb_lighthouse_' . md5($url);
    $cached    = get_transient($cache_key);
    if (false !== $cached && is_array($cached)) {
        return $cached;
    }

    // Build API request.
    $api_key  = get_option('pearblog_pagespeed_api_key', '');
    $endpoint = add_query_arg(
        array_filter(array(
            'url'      => rawurlencode($url),
            'category' => array('PERFORMANCE', 'SEO', 'ACCESSIBILITY', 'BEST_PRACTICES'),
            'strategy' => 'mobile',
            'key'      => $api_key ?: null,
        )),
        'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
    );

    // PageSpeed Insights accepts category as repeated param.
    $endpoint = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=' . rawurlencode($url)
              . '&category=PERFORMANCE&category=SEO&category=ACCESSIBILITY&category=BEST_PRACTICES'
              . '&strategy=mobile'
              . ($api_key ? '&key=' . rawurlencode($api_key) : '');

    $response = wp_remote_get($endpoint, array('timeout' => 60));

    if (is_wp_error($response)) {
        error_log('PearBlog: PageSpeed Insights API error – ' . $response->get_error_message());
        return $default_scores;
    }

    $code = wp_remote_retrieve_response_code($response);
    if (200 !== $code) {
        error_log('PearBlog: PageSpeed Insights API returned HTTP ' . $code);
        return $default_scores;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['lighthouseResult']['categories'])) {
        return $default_scores;
    }

    $cats   = $body['lighthouseResult']['categories'];
    $scores = array(
        'performance'    => isset($cats['performance']['score'])    ? round($cats['performance']['score'] * 100)    : 0,
        'seo'            => isset($cats['seo']['score'])            ? round($cats['seo']['score'] * 100)            : 0,
        'accessibility'  => isset($cats['accessibility']['score'])  ? round($cats['accessibility']['score'] * 100)  : 0,
        'best_practices' => isset($cats['best-practices']['score']) ? round($cats['best-practices']['score'] * 100) : 0,
    );

    // Cache for 24 hours.
    set_transient($cache_key, $scores, DAY_IN_SECONDS);

    return $scores;
}
