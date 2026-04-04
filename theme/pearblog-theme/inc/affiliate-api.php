<?php
/**
 * Affiliate API
 *
 * Handles affiliate offer fetching and tracking
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API endpoints for affiliate functionality
 */
function pearblog_register_affiliate_endpoints() {
    // Endpoint to get affiliate offers
    register_rest_route('pearblog/v1', '/affiliate/offers', array(
        'methods' => 'GET',
        'callback' => 'pearblog_api_get_affiliate_offers',
        'permission_callback' => '__return_true',
        'args' => array(
            'location' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'post_id' => array(
                'required' => false,
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ),
        ),
    ));

    // Endpoint to track affiliate clicks
    register_rest_route('pearblog/v1', '/track-affiliate', array(
        'methods' => 'POST',
        'callback' => 'pearblog_api_track_affiliate_click',
        'permission_callback' => '__return_true',
        'args' => array(
            'source' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'position' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'post_id' => array(
                'required' => true,
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'url' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
            ),
        ),
    ));
}
add_action('rest_api_init', 'pearblog_register_affiliate_endpoints');

/**
 * API Callback: Get affiliate offers
 */
function pearblog_api_get_affiliate_offers($request) {
    $location = $request->get_param('location');
    $post_id = $request->get_param('post_id');

    // If no location provided, try to extract from post
    if (empty($location) && !empty($post_id)) {
        $location = get_post_meta($post_id, 'pearblog_location', true);

        // Or extract from post title/content
        if (empty($location)) {
            $post = get_post($post_id);
            if ($post) {
                $location = pearblog_extract_location_from_content($post->post_title . ' ' . $post->post_content);
            }
        }
    }

    $offers = pearblog_get_affiliate_offers($location);

    return new WP_REST_Response(array(
        'success' => true,
        'location' => $location,
        'offers' => $offers,
        'count' => count($offers),
    ), 200);
}

/**
 * API Callback: Track affiliate click
 */
function pearblog_api_track_affiliate_click($request) {
    $source = $request->get_param('source');
    $position = $request->get_param('position');
    $post_id = $request->get_param('post_id');
    $url = $request->get_param('url');

    // Store click data
    $click_data = array(
        'source' => $source,
        'position' => $position,
        'url' => $url,
        'timestamp' => current_time('mysql'),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    );

    // Track in post meta
    $existing_clicks = get_post_meta($post_id, '_pearblog_affiliate_clicks', true);
    if (!is_array($existing_clicks)) {
        $existing_clicks = array();
    }
    $existing_clicks[] = $click_data;

    // Keep only last 100 clicks per post
    if (count($existing_clicks) > 100) {
        $existing_clicks = array_slice($existing_clicks, -100);
    }

    update_post_meta($post_id, '_pearblog_affiliate_clicks', $existing_clicks);

    // Update click counter by source
    $click_count_key = '_pearblog_affiliate_clicks_' . $source;
    $current_count = get_post_meta($post_id, $click_count_key, true);
    $current_count = intval($current_count);
    update_post_meta($post_id, $click_count_key, $current_count + 1);

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Click tracked successfully',
    ), 200);
}

/**
 * Get affiliate offers for a location
 *
 * This function can be extended to integrate with Booking.com API, Airbnb API,
 * or use cached/manual offers stored in WordPress
 *
 * @param string $location Location name
 * @return array Array of offers
 */
function pearblog_get_affiliate_offers($location = '') {
    if (empty($location)) {
        return array();
    }

    // Try to get cached offers
    $cache_key = 'pearblog_offers_' . md5($location);
    $cached_offers = get_transient($cache_key);

    if ($cached_offers !== false) {
        return $cached_offers;
    }

    $offers = array();

    // Option 1: Get offers from post meta (manual entry)
    $manual_offers = get_option('pearblog_manual_offers_' . sanitize_title($location), array());
    if (!empty($manual_offers)) {
        $offers = array_merge($offers, $manual_offers);
    }

    // Option 2: Get offers from Booking.com (affiliate ID required; API key optional)
    $booking_api_key = get_option('pearblog_booking_api_key', '');
    $booking_affiliate_id = get_option('pearblog_booking_affiliate_id', '');

    if (!empty($booking_affiliate_id)) {
        $booking_offers = pearblog_fetch_booking_offers($location, $booking_api_key, $booking_affiliate_id);
        if (!empty($booking_offers)) {
            $offers = array_merge($offers, $booking_offers);
        }
    }

    // Option 3: Get offers from Airbnb (if configured)
    $airbnb_api_key = get_option('pearblog_airbnb_api_key', '');
    $airbnb_affiliate_id = get_option('pearblog_airbnb_affiliate_id', '');

    if (!empty($airbnb_api_key) && !empty($airbnb_affiliate_id)) {
        $airbnb_offers = pearblog_fetch_airbnb_offers($location, $airbnb_api_key, $airbnb_affiliate_id);
        if (!empty($airbnb_offers)) {
            $offers = array_merge($offers, $airbnb_offers);
        }
    }

    // Cache offers for 6 hours
    if (!empty($offers)) {
        set_transient($cache_key, $offers, 6 * HOUR_IN_SECONDS);
    }

    return $offers;
}

/**
 * Fetch offers from Booking.com via deep links.
 *
 * Booking.com affiliates use parameterised search URLs rather than a product
 * API, so no API key is required — only the partner affiliate ID (aid).
 * If a Booking.com Affiliate API key is also provided, this function can be
 * extended to call their Demand API for live pricing data.
 *
 * @param string $location     Destination name (e.g. "Zakopane").
 * @param string $api_key      Booking.com API key (optional – reserved for future Demand API use).
 * @param string $affiliate_id Booking.com partner/affiliate ID (aid).
 * @return array Array of offer arrays compatible with pearblog_get_affiliate_offers().
 */
function pearblog_fetch_booking_offers($location, $api_key, $affiliate_id) {
    if (empty($affiliate_id) || empty($location)) {
        return array();
    }

    $search_url = add_query_arg(
        array(
            'aid'  => rawurlencode($affiliate_id),
            'ss'   => rawurlencode($location),
            'lang' => 'pl',
        ),
        'https://www.booking.com/searchresults.html'
    );

    return array(
        array(
            'source' => 'booking',
            'name'   => sprintf(
                /* translators: %s: destination name */
                __('Noclegi w %s – Booking.com', 'pearblog-theme'),
                $location
            ),
            'price'  => '',
            'rating' => 0,
            'url'    => $search_url,
            'image'  => '',
        ),
    );
}

/**
 * Fetch offers from Airbnb via deep links.
 *
 * Airbnb does not expose a public affiliate product API.  Partners generate
 * revenue through parameterised search URLs (deep links) that attribute the
 * click to a specific affiliate ID.  The search URL is built from the
 * location name and a configurable number of guests / check-in window.
 *
 * @param string $location     Destination name (e.g. "Zakopane").
 * @param string $api_key      Unused – kept for signature parity with Booking helper.
 * @param string $affiliate_id Airbnb affiliate / partner ID.
 * @return array Array of offer arrays compatible with pearblog_get_affiliate_offers().
 */
function pearblog_fetch_airbnb_offers($location, $api_key, $affiliate_id) {
    if (empty($affiliate_id) || empty($location)) {
        return array();
    }

    // Build an Airbnb search deep link.
    // Dates default to 7 days from now for a 2-night stay.
    $checkin  = gmdate('Y-m-d', strtotime('+7 days'));
    $checkout = gmdate('Y-m-d', strtotime('+9 days'));

    $search_url = add_query_arg(
        array(
            'query'    => rawurlencode($location),
            'checkin'  => $checkin,
            'checkout' => $checkout,
            'adults'   => 2,
            'c'        => rawurlencode('.pi80.pk' . $affiliate_id . '_'),  // Airbnb partner cookie param
        ),
        'https://www.airbnb.com/s/' . rawurlencode($location) . '/homes'
    );

    /**
     * Filter the Airbnb affiliate search URL before it is returned.
     *
     * @param string $search_url   Fully‐qualified Airbnb search URL.
     * @param string $location     Location name.
     * @param string $affiliate_id Partner ID.
     */
    $search_url = apply_filters('pearblog_airbnb_search_url', $search_url, $location, $affiliate_id);

    return array(
        array(
            'source' => 'airbnb',
            'name'   => sprintf(
                /* translators: %s: destination name */
                __('Noclegi w %s – Airbnb', 'pearblog-theme'),
                $location
            ),
            'price'  => '',
            'rating' => 0,
            'url'    => $search_url,
            'image'  => '',
        ),
    );
}

/**
 * Extract location from content
 *
 * Simple keyword extraction - can be enhanced with NLP or AI
 */
function pearblog_extract_location_from_content($content) {
    // This is a simple implementation
    // In production, you might use AI/ML or more sophisticated extraction

    // Common Polish location keywords
    $location_keywords = array(
        'Babia Góra', 'Zakopane', 'Kraków', 'Warszawa', 'Gdańsk',
        'Tatry', 'Karkonosze', 'Bieszczady', 'Mazury', 'Karpacz',
    );

    foreach ($location_keywords as $keyword) {
        if (stripos($content, $keyword) !== false) {
            return $keyword;
        }
    }

    return '';
}

/**
 * Helper function to add manual offers for a location
 *
 * Usage: pearblog_add_manual_offer('Babia Góra', array(...))
 */
function pearblog_add_manual_offer($location, $offer_data) {
    $location_key = sanitize_title($location);
    $offers = get_option('pearblog_manual_offers_' . $location_key, array());

    $offer = array_merge(array(
        'source' => 'booking', // or 'airbnb'
        'name' => '',
        'price' => '',
        'rating' => 0,
        'url' => '',
        'image' => '',
    ), $offer_data);

    $offers[] = $offer;
    update_option('pearblog_manual_offers_' . $location_key, $offers);

    // Clear cache
    delete_transient('pearblog_offers_' . md5($location));

    return true;
}

/**
 * Helper function to render affiliate box
 */
function pearblog_affiliate_box($args = array()) {
    $defaults = array(
        'position' => 'middle',
        'location' => '',
        'offers' => array(),
        'fallback_enabled' => true,
    );

    $args = wp_parse_args($args, $defaults);

    get_template_part('template-parts/block-affiliate', null, $args);
}

/**
 * Get affiliate statistics for admin dashboard
 */
function pearblog_get_affiliate_stats($post_id = null) {
    if (empty($post_id)) {
        // Get global stats
        global $wpdb;

        $booking_clicks = $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta}
            WHERE meta_key = '_pearblog_affiliate_clicks_booking'"
        );

        $airbnb_clicks = $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta}
            WHERE meta_key = '_pearblog_affiliate_clicks_airbnb'"
        );

        return array(
            'booking_clicks' => intval($booking_clicks),
            'airbnb_clicks' => intval($airbnb_clicks),
            'total_clicks' => intval($booking_clicks) + intval($airbnb_clicks),
        );
    } else {
        // Get stats for specific post
        $booking_clicks = get_post_meta($post_id, '_pearblog_affiliate_clicks_booking', true);
        $airbnb_clicks = get_post_meta($post_id, '_pearblog_affiliate_clicks_airbnb', true);

        return array(
            'booking_clicks' => intval($booking_clicks),
            'airbnb_clicks' => intval($airbnb_clicks),
            'total_clicks' => intval($booking_clicks) + intval($airbnb_clicks),
        );
    }
}
