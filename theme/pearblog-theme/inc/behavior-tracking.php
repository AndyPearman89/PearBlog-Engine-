<?php
/**
 * PearBlog Behavior Tracking System
 *
 * Zbierasz:
 * - scroll depth
 * - time on page
 * - click events
 *
 * Zapis: pb_user_metrics()
 *
 * @package PearBlog
 * @version 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Record user metrics
 *
 * @param array $metrics Metrics data
 * @return bool Success
 */
function pb_user_metrics($metrics = array()) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pb_user_metrics';

    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        pb_create_metrics_table();
    }

    $defaults = array(
        'session_id' => pb_get_session_id(),
        'post_id' => get_the_ID(),
        'scroll_depth' => 0,
        'time_on_page' => 0,
        'clicks' => 0,
        'cta_clicks' => 0,
        'ad_views' => 0,
        'ad_clicks' => 0,
        'timestamp' => current_time('mysql'),
    );

    $metrics = wp_parse_args($metrics, $defaults);

    return $wpdb->insert(
        $table_name,
        array(
            'session_id' => $metrics['session_id'],
            'post_id' => $metrics['post_id'],
            'scroll_depth' => $metrics['scroll_depth'],
            'time_on_page' => $metrics['time_on_page'],
            'clicks' => $metrics['clicks'],
            'cta_clicks' => $metrics['cta_clicks'],
            'ad_views' => $metrics['ad_views'],
            'ad_clicks' => $metrics['ad_clicks'],
            'timestamp' => $metrics['timestamp'],
        ),
        array('%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s')
    );
}

/**
 * Create metrics table
 */
function pb_create_metrics_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pb_user_metrics';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(100) NOT NULL,
        post_id bigint(20) NOT NULL,
        scroll_depth int(3) NOT NULL DEFAULT 0,
        time_on_page int(11) NOT NULL DEFAULT 0,
        clicks int(11) NOT NULL DEFAULT 0,
        cta_clicks int(11) NOT NULL DEFAULT 0,
        ad_views int(11) NOT NULL DEFAULT 0,
        ad_clicks int(11) NOT NULL DEFAULT 0,
        timestamp datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY session_id (session_id),
        KEY post_id (post_id),
        KEY timestamp (timestamp)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'pb_create_metrics_table');

/**
 * Get post engagement metrics
 *
 * @param int $post_id Post ID
 * @param int $days Number of days to analyze (default 30)
 * @return array Engagement metrics
 */
function pb_get_post_metrics($post_id, $days = 30) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pb_user_metrics';
    $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days"));

    $metrics = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COUNT(*) as views,
            AVG(scroll_depth) as avg_scroll,
            AVG(time_on_page) as avg_time,
            SUM(clicks) as total_clicks,
            SUM(cta_clicks) as total_cta_clicks,
            SUM(ad_clicks) as total_ad_clicks
        FROM $table_name
        WHERE post_id = %d AND timestamp > %s",
        $post_id,
        $date_threshold
    ), ARRAY_A);

    return $metrics ?: array(
        'views' => 0,
        'avg_scroll' => 0,
        'avg_time' => 0,
        'total_clicks' => 0,
        'total_cta_clicks' => 0,
        'total_ad_clicks' => 0,
    );
}

/**
 * Get user engagement score
 *
 * @param array $behavior Behavior data
 * @return int Engagement score (0-100)
 */
function pb_calculate_engagement_score($behavior) {
    $score = 0;

    // Scroll depth (40 points max)
    $scroll_score = min(40, ($behavior['scroll_depth_avg'] ?? 0) * 0.4);
    $score += $scroll_score;

    // Time on page (30 points max) - 1 point per 10 seconds, max at 5 minutes
    $time_score = min(30, (($behavior['total_time'] ?? 0) / 10));
    $score += $time_score;

    // Clicks (20 points max)
    $click_score = min(20, ($behavior['clicks'] ?? 0) * 2);
    $score += $click_score;

    // Pages viewed (10 points max)
    $page_score = min(10, ($behavior['pages_viewed'] ?? 1) * 2);
    $score += $page_score;

    return round($score);
}

/**
 * Determine optimal CTA based on scroll depth
 *
 * @param int $scroll_depth Current scroll depth percentage
 * @param string $user_segment User segment
 * @return array CTA configuration
 */
function pb_get_optimal_cta($scroll_depth, $user_segment) {
    $ctas = array(
        'informational' => array(
            'low' => array(
                'text' => 'Learn More',
                'type' => 'secondary',
            ),
            'medium' => array(
                'text' => 'Read Related Articles',
                'type' => 'primary',
            ),
            'high' => array(
                'text' => 'Subscribe for Updates',
                'type' => 'primary',
            ),
        ),
        'transactional' => array(
            'low' => array(
                'text' => 'See Details',
                'type' => 'primary',
            ),
            'medium' => array(
                'text' => 'Check Pricing',
                'type' => 'primary',
            ),
            'high' => array(
                'text' => 'Get Started Now',
                'type' => 'primary',
            ),
        ),
        'navigational' => array(
            'low' => array(
                'text' => 'Explore More',
                'type' => 'secondary',
            ),
            'medium' => array(
                'text' => 'View All Posts',
                'type' => 'secondary',
            ),
            'high' => array(
                'text' => 'Join Community',
                'type' => 'primary',
            ),
        ),
    );

    // Determine scroll level
    $scroll_level = 'low';
    if ($scroll_depth > 70) {
        $scroll_level = 'high';
    } elseif ($scroll_depth > 40) {
        $scroll_level = 'medium';
    }

    $segment = $ctas[$user_segment] ?? $ctas['informational'];
    return $segment[$scroll_level];
}

/**
 * Determine if ad should be shown based on engagement
 *
 * @param int $scroll_depth Scroll depth percentage
 * @param int $engagement_score User engagement score
 * @return bool Should show ad
 */
function pb_should_show_ad($scroll_depth, $engagement_score) {
    // Rule: IF scroll > 50% → show CTA
    // IF user engaged → show affiliate

    if ($scroll_depth < 50) {
        return false;
    }

    if ($engagement_score > 60) {
        return true; // Highly engaged users see ads
    }

    if ($scroll_depth > 80) {
        return true; // Deep scrollers see ads
    }

    return false;
}

/**
 * AJAX endpoint to save metrics
 */
function pb_ajax_save_metrics() {
    check_ajax_referer('pearblog_nonce', 'nonce');

    $metrics = array(
        'session_id' => sanitize_text_field($_POST['session_id'] ?? ''),
        'post_id' => intval($_POST['post_id'] ?? 0),
        'scroll_depth' => intval($_POST['scroll_depth'] ?? 0),
        'time_on_page' => intval($_POST['time_on_page'] ?? 0),
        'clicks' => intval($_POST['clicks'] ?? 0),
        'cta_clicks' => intval($_POST['cta_clicks'] ?? 0),
        'ad_views' => intval($_POST['ad_views'] ?? 0),
        'ad_clicks' => intval($_POST['ad_clicks'] ?? 0),
    );

    $result = pb_user_metrics($metrics);

    if ($result) {
        wp_send_json_success(array(
            'message' => 'Metrics saved',
            'engagement_score' => pb_calculate_engagement_score(array(
                'scroll_depth_avg' => $metrics['scroll_depth'],
                'total_time' => $metrics['time_on_page'],
                'clicks' => $metrics['clicks'],
                'pages_viewed' => 1,
            )),
        ));
    } else {
        wp_send_json_error('Failed to save metrics');
    }
}
add_action('wp_ajax_pb_save_metrics', 'pb_ajax_save_metrics');
add_action('wp_ajax_nopriv_pb_save_metrics', 'pb_ajax_save_metrics');

/**
 * Get recommended action based on behavior
 *
 * @param array $context User context
 * @param int $scroll_depth Current scroll depth
 * @return array Recommended action
 */
function pb_get_recommended_action($context, $scroll_depth) {
    $engagement_score = pb_calculate_engagement_score($context['behavior']);
    $user_segment = $context['user_segment'];

    $action = array(
        'type' => 'none',
        'cta' => pb_get_optimal_cta($scroll_depth, $user_segment),
        'show_ad' => pb_should_show_ad($scroll_depth, $engagement_score),
        'show_related' => $scroll_depth > 60,
        'show_newsletter' => $engagement_score > 70,
    );

    return $action;
}
