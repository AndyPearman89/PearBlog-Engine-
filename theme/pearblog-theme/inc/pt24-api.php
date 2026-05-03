<?php
/**
 * PT24 REST API Endpoints
 *
 * REST API for PT24 platform
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes
 */
function pt24_register_rest_routes() {
    // Namespace
    $namespace = 'pt24/v1';

    // Get businesses endpoint
    register_rest_route($namespace, '/businesses', [
        'methods' => 'GET',
        'callback' => 'pt24_api_get_businesses',
        'permission_callback' => '__return_true',
        'args' => [
            'service' => [
                'required' => false,
                'type' => 'string',
            ],
            'city' => [
                'required' => false,
                'type' => 'string',
            ],
            'per_page' => [
                'default' => 10,
                'type' => 'integer',
            ],
            'page' => [
                'default' => 1,
                'type' => 'integer',
            ],
        ],
    ]);

    // Get single business endpoint
    register_rest_route($namespace, '/businesses/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'pt24_api_get_business',
        'permission_callback' => '__return_true',
    ]);

    // Get leads endpoint (admin only)
    register_rest_route($namespace, '/leads', [
        'methods' => 'GET',
        'callback' => 'pt24_api_get_leads',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => [
            'status' => [
                'required' => false,
                'type' => 'string',
            ],
            'per_page' => [
                'default' => 20,
                'type' => 'integer',
            ],
            'page' => [
                'default' => 1,
                'type' => 'integer',
            ],
        ],
    ]);

    // Get business stats endpoint (business owner or admin)
    register_rest_route($namespace, '/stats/(?P<business_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'pt24_api_get_business_stats',
        'permission_callback' => 'pt24_api_can_view_stats',
    ]);

    // Submit lead endpoint (public)
    register_rest_route($namespace, '/leads/submit', [
        'methods' => 'POST',
        'callback' => 'pt24_api_submit_lead',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'pt24_register_rest_routes');

/**
 * Get businesses
 */
function pt24_api_get_businesses($request) {
    $service = $request->get_param('service');
    $city = $request->get_param('city');
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');

    $args = [
        'post_type' => 'pt24_business',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
    ];

    // Add tax query if service or city specified
    if ($service || $city) {
        $args['tax_query'] = ['relation' => 'AND'];

        if ($service) {
            $args['tax_query'][] = [
                'taxonomy' => 'pt24_service_cat',
                'field' => 'slug',
                'terms' => $service,
            ];
        }

        if ($city) {
            $args['tax_query'][] = [
                'taxonomy' => 'pt24_city',
                'field' => 'slug',
                'terms' => $city,
            ];
        }
    }

    $query = new WP_Query($args);
    $businesses = [];

    foreach ($query->posts as $post) {
        $businesses[] = [
            'id' => $post->ID,
            'name' => $post->post_title,
            'url' => get_permalink($post->ID),
            'phone' => get_post_meta($post->ID, 'pt24_phone', true),
            'email' => get_post_meta($post->ID, 'pt24_email', true),
            'service_area' => get_post_meta($post->ID, 'pt24_service_area', true),
            'rating' => floatval(get_post_meta($post->ID, 'pt24_rating', true) ?: 5),
            'reviews_count' => intval(get_post_meta($post->ID, 'pt24_reviews_count', true) ?: 0),
            'plan' => get_post_meta($post->ID, 'pt24_plan', true) ?: 'free',
        ];
    }

    return new WP_REST_Response([
        'businesses' => $businesses,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages,
        'current_page' => $page,
    ], 200);
}

/**
 * Get single business
 */
function pt24_api_get_business($request) {
    $business_id = $request->get_param('id');
    $post = get_post($business_id);

    if (!$post || $post->post_type !== 'pt24_business' || $post->post_status !== 'publish') {
        return new WP_Error('not_found', 'Business not found', ['status' => 404]);
    }

    $categories = get_the_terms($business_id, 'pt24_service_cat');
    $cities = get_the_terms($business_id, 'pt24_city');

    $business = [
        'id' => $post->ID,
        'name' => $post->post_title,
        'content' => apply_filters('the_content', $post->post_content),
        'url' => get_permalink($post->ID),
        'phone' => get_post_meta($business_id, 'pt24_phone', true),
        'email' => get_post_meta($business_id, 'pt24_email', true),
        'website' => get_post_meta($business_id, 'pt24_website', true),
        'service_area' => get_post_meta($business_id, 'pt24_service_area', true),
        'specialization' => get_post_meta($business_id, 'pt24_specialization', true),
        'years_experience' => intval(get_post_meta($business_id, 'pt24_years_experience', true) ?: 0),
        'mobile_service' => (bool) get_post_meta($business_id, 'pt24_mobile_service', true),
        'rating' => floatval(get_post_meta($business_id, 'pt24_rating', true) ?: 5),
        'reviews_count' => intval(get_post_meta($business_id, 'pt24_reviews_count', true) ?: 0),
        'plan' => get_post_meta($business_id, 'pt24_plan', true) ?: 'free',
        'services' => $categories ? array_map(function($term) {
            return $term->name;
        }, $categories) : [],
        'cities' => $cities ? array_map(function($term) {
            return $term->name;
        }, $cities) : [],
    ];

    return new WP_REST_Response($business, 200);
}

/**
 * Get leads (admin only)
 */
function pt24_api_get_leads($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pt24_leads';

    $status = $request->get_param('status');
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');
    $offset = ($page - 1) * $per_page;

    $where = $status ? $wpdb->prepare("WHERE status = %s", $status) : "";
    $leads = $wpdb->get_results(
        "SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset"
    );

    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");

    return new WP_REST_Response([
        'leads' => $leads,
        'total' => intval($total),
        'pages' => ceil($total / $per_page),
        'current_page' => $page,
    ], 200);
}

/**
 * Get business stats
 */
function pt24_api_get_business_stats($request) {
    $business_id = $request->get_param('business_id');

    global $wpdb;
    $table_name = $wpdb->prefix . 'pt24_business_stats';

    // Get last 30 days stats
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    $stats = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE business_id = %d AND date >= %s ORDER BY date DESC",
        $business_id,
        $thirty_days_ago
    ));

    // Calculate totals
    $totals = [
        'views' => 0,
        'phone_clicks' => 0,
        'email_clicks' => 0,
    ];

    foreach ($stats as $day_stat) {
        $totals['views'] += $day_stat->views;
        $totals['phone_clicks'] += $day_stat->phone_clicks;
        $totals['email_clicks'] += $day_stat->email_clicks;
    }

    return new WP_REST_Response([
        'daily_stats' => $stats,
        'totals_30_days' => $totals,
    ], 200);
}

/**
 * Check if user can view stats
 */
function pt24_api_can_view_stats($request) {
    // Admin can view all stats
    if (current_user_can('manage_options')) {
        return true;
    }

    // Business owner can view their own stats
    $business_id = $request->get_param('business_id');
    $post = get_post($business_id);

    if ($post && get_current_user_id() === $post->post_author) {
        return true;
    }

    return false;
}

/**
 * Submit lead via API
 */
function pt24_api_submit_lead($request) {
    $params = $request->get_json_params();

    // Validate required fields
    $required = ['name', 'email', 'phone', 'city', 'service'];
    foreach ($required as $field) {
        if (empty($params[$field])) {
            return new WP_Error('missing_field', "Field '$field' is required", ['status' => 400]);
        }
    }

    // Sanitize
    $name = sanitize_text_field($params['name']);
    $email = sanitize_email($params['email']);
    $phone = sanitize_text_field($params['phone']);
    $city = sanitize_text_field($params['city']);
    $service = sanitize_text_field($params['service']);
    $message = sanitize_textarea_field($params['message'] ?? '');

    // Validate email
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Invalid email address', ['status' => 400]);
    }

    // Store in database
    global $wpdb;
    $table_name = $wpdb->prefix . 'pt24_leads';

    $result = $wpdb->insert(
        $table_name,
        [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'city' => $city,
            'service' => $service,
            'message' => $message,
            'source' => 'api',
            'status' => 'new',
            'created_at' => current_time('mysql'),
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );

    if ($result === false) {
        return new WP_Error('insert_failed', 'Failed to save lead', ['status' => 500]);
    }

    $lead_id = $wpdb->insert_id;

    return new WP_REST_Response([
        'success' => true,
        'lead_id' => $lead_id,
        'message' => 'Lead submitted successfully',
    ], 201);
}
