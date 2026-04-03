<?php
/**
 * PearBlog User Context Engine
 *
 * System zbiera:
 * - lokalizację (geo)
 * - device (mobile/desktop)
 * - zachowanie (scroll, kliknięcia)
 * - źródło ruchu (Google, social)
 *
 * @package PearBlog
 * @version 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get comprehensive user context data
 *
 * @return array User context data
 */
function pb_get_user_context() {
    $context = array(
        'device' => pb_detect_device(),
        'geo' => pb_get_geo_data(),
        'traffic_source' => pb_get_traffic_source(),
        'behavior' => pb_get_user_behavior(),
        'session_id' => pb_get_session_id(),
        'timestamp' => time(),
        'is_returning' => pb_is_returning_user(),
        'user_segment' => pb_determine_user_segment(),
    );

    return apply_filters('pb_user_context', $context);
}

/**
 * Detect device type
 *
 * @return string Device type (mobile, tablet, desktop)
 */
function pb_detect_device() {
    if (wp_is_mobile()) {
        // Further differentiate between mobile and tablet
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
            return 'tablet';
        }
        return 'mobile';
    }
    return 'desktop';
}

/**
 * Get geographic data from IP
 *
 * @return array Geographic data
 */
function pb_get_geo_data() {
    $ip = pb_get_client_ip();

    // Try to get from cache first
    $cache_key = 'pb_geo_' . md5($ip);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $geo_data = array(
        'ip' => $ip,
        'country' => 'unknown',
        'city' => 'unknown',
        'timezone' => wp_timezone_string(),
    );

    // Try to detect from CloudFlare headers (if available)
    if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $geo_data['country'] = sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']);
    }

    // Cache for 24 hours
    set_transient($cache_key, $geo_data, DAY_IN_SECONDS);

    return $geo_data;
}

/**
 * Get client IP address
 *
 * @return string IP address
 */
function pb_get_client_ip() {
    $ip_keys = array(
        'HTTP_CF_CONNECTING_IP', // CloudFlare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    );

    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
            return sanitize_text_field($_SERVER[$key]);
        }
    }

    return '0.0.0.0';
}

/**
 * Detect traffic source
 *
 * @return array Traffic source data
 */
function pb_get_traffic_source() {
    $referer = wp_get_referer() ?: '';
    $utm_source = sanitize_text_field($_GET['utm_source'] ?? '');
    $utm_medium = sanitize_text_field($_GET['utm_medium'] ?? '');
    $utm_campaign = sanitize_text_field($_GET['utm_campaign'] ?? '');

    $source = array(
        'type' => 'direct',
        'referer' => $referer,
        'utm_source' => $utm_source,
        'utm_medium' => $utm_medium,
        'utm_campaign' => $utm_campaign,
    );

    // Detect source type
    if (!empty($utm_source)) {
        $source['type'] = 'campaign';
    } elseif (!empty($referer)) {
        if (preg_match('/google\./i', $referer)) {
            $source['type'] = 'google';
        } elseif (preg_match('/facebook\.|fb\./i', $referer)) {
            $source['type'] = 'facebook';
        } elseif (preg_match('/twitter\.|t\.co/i', $referer)) {
            $source['type'] = 'twitter';
        } elseif (preg_match('/linkedin\./i', $referer)) {
            $source['type'] = 'linkedin';
        } elseif (preg_match('/instagram\./i', $referer)) {
            $source['type'] = 'instagram';
        } else {
            $source['type'] = 'referral';
        }
    }

    return $source;
}

/**
 * Get user behavior data from cookie
 *
 * @return array Behavior data
 */
function pb_get_user_behavior() {
    $behavior = array(
        'pages_viewed' => 1,
        'total_time' => 0,
        'scroll_depth_avg' => 0,
        'clicks' => 0,
        'last_visit' => time(),
    );

    // Get from cookie if exists
    if (isset($_COOKIE['pb_behavior'])) {
        $cookie_data = json_decode(stripslashes($_COOKIE['pb_behavior']), true);
        if (is_array($cookie_data)) {
            $behavior = array_merge($behavior, $cookie_data);
        }
    }

    return $behavior;
}

/**
 * Get or create session ID
 *
 * @return string Session ID
 */
function pb_get_session_id() {
    if (isset($_COOKIE['pb_session_id'])) {
        return sanitize_text_field($_COOKIE['pb_session_id']);
    }

    return 'session_' . uniqid() . '_' . time();
}

/**
 * Check if returning user
 *
 * @return bool True if returning user
 */
function pb_is_returning_user() {
    return isset($_COOKIE['pb_behavior']) && !empty($_COOKIE['pb_behavior']);
}

/**
 * Determine user segment based on context
 *
 * @return string User segment (informational, transactional, navigational)
 */
function pb_determine_user_segment() {
    $source = pb_get_traffic_source();
    $device = pb_detect_device();
    $is_returning = pb_is_returning_user();

    // Simple segmentation logic
    if ($source['type'] === 'google') {
        // Google users often have informational intent
        return 'informational';
    } elseif ($source['type'] === 'campaign' || !empty($source['utm_campaign'])) {
        // Campaign traffic likely transactional
        return 'transactional';
    } elseif ($is_returning) {
        // Returning users are navigational
        return 'navigational';
    }

    // Default to informational
    return 'informational';
}

/**
 * Store user context for analytics
 *
 * @param array $context User context data
 * @return bool Success
 */
function pb_store_user_context($context) {
    global $wpdb;

    // Check if analytics table exists
    $table_name = $wpdb->prefix . 'pb_user_analytics';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return false;
    }

    return $wpdb->insert(
        $table_name,
        array(
            'session_id' => $context['session_id'],
            'device' => $context['device'],
            'country' => $context['geo']['country'] ?? 'unknown',
            'traffic_source' => $context['traffic_source']['type'],
            'user_segment' => $context['user_segment'],
            'timestamp' => current_time('mysql'),
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s')
    );
}

/**
 * Create analytics table on theme activation
 */
function pb_create_analytics_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pb_user_analytics';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(100) NOT NULL,
        device varchar(20) NOT NULL,
        country varchar(10) NOT NULL,
        traffic_source varchar(50) NOT NULL,
        user_segment varchar(50) NOT NULL,
        timestamp datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY session_id (session_id),
        KEY timestamp (timestamp)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'pb_create_analytics_table');

/**
 * AJAX endpoint to store user context
 */
function pb_ajax_store_context() {
    check_ajax_referer('pearblog_nonce', 'nonce');

    $context = json_decode(stripslashes($_POST['context'] ?? '{}'), true);

    if (empty($context)) {
        wp_send_json_error('Invalid context data');
    }

    // Merge with server-side context
    $full_context = array_merge(pb_get_user_context(), $context);

    // Store context
    pb_store_user_context($full_context);

    wp_send_json_success(array(
        'segment' => $full_context['user_segment'],
        'device' => $full_context['device'],
    ));
}
add_action('wp_ajax_pb_store_context', 'pb_ajax_store_context');
add_action('wp_ajax_nopriv_pb_store_context', 'pb_ajax_store_context');
