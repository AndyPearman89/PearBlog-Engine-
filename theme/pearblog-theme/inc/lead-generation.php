<?php
/**
 * Lead Generation System
 *
 * Handles lead capture, storage, tracking, and management for Phase 3 monetization
 *
 * @package PearBlog
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register lead custom post type
 */
function pearblog_register_lead_post_type() {
    $labels = array(
        'name' => __('Leads', 'pearblog-theme'),
        'singular_name' => __('Lead', 'pearblog-theme'),
        'menu_name' => __('PearBlog Leads', 'pearblog-theme'),
        'add_new' => __('Add New', 'pearblog-theme'),
        'add_new_item' => __('Add New Lead', 'pearblog-theme'),
        'edit_item' => __('Edit Lead', 'pearblog-theme'),
        'new_item' => __('New Lead', 'pearblog-theme'),
        'view_item' => __('View Lead', 'pearblog-theme'),
        'search_items' => __('Search Leads', 'pearblog-theme'),
        'not_found' => __('No leads found', 'pearblog-theme'),
        'not_found_in_trash' => __('No leads found in trash', 'pearblog-theme'),
    );

    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-groups',
        'menu_position' => 26,
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => 'manage_options',
        ),
        'map_meta_cap' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
        'has_archive' => false,
        'rewrite' => false,
        'query_var' => false,
    );

    register_post_type('pearblog_lead', $args);
}
add_action('init', 'pearblog_register_lead_post_type');

/**
 * Register REST API endpoints for lead submission
 */
function pearblog_register_lead_endpoints() {
    register_rest_route('pearblog/v1', '/leads', array(
        'methods' => 'POST',
        'callback' => 'pearblog_api_submit_lead',
        'permission_callback' => 'pearblog_lead_permission_check',
        'args' => array(
            'name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'email' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email',
            ),
            'phone' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'message' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'travel_dates' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'post_id' => array(
                'required' => false,
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'lead_type' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'general',
            ),
            'form_position' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    // Get lead statistics
    register_rest_route('pearblog/v1', '/leads/stats', array(
        'methods' => 'GET',
        'callback' => 'pearblog_api_get_lead_stats',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ));
}
add_action('rest_api_init', 'pearblog_register_lead_endpoints');

/**
 * Permission check for lead submission with rate limiting
 *
 * @param WP_REST_Request $request Request object
 * @return bool|WP_Error True if allowed, WP_Error if rate limited
 */
function pearblog_lead_permission_check($request) {
    // Check rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (empty($ip)) {
        return new WP_Error('no_ip', __('Unable to determine IP address', 'pearblog-theme'), array('status' => 403));
    }

    // Rate limit: 5 submissions per IP per hour
    $rate_limit_key = 'pearblog_lead_rate_' . md5($ip);
    $submissions = get_transient($rate_limit_key);

    if ($submissions === false) {
        $submissions = array();
    }

    // Clean old submissions (older than 1 hour)
    $one_hour_ago = time() - HOUR_IN_SECONDS;
    $submissions = array_filter($submissions, function($timestamp) use ($one_hour_ago) {
        return $timestamp > $one_hour_ago;
    });

    // Check if limit exceeded
    if (count($submissions) >= 5) {
        return new WP_Error(
            'rate_limit_exceeded',
            __('Too many submissions. Please try again later.', 'pearblog-theme'),
            array('status' => 429)
        );
    }

    // Add current submission
    $submissions[] = time();
    set_transient($rate_limit_key, $submissions, HOUR_IN_SECONDS);

    return true;
}

/**
 * API Callback: Submit a lead
 */
function pearblog_api_submit_lead($request) {
    // Verify nonce
    $nonce = $request->get_header('X-WP-Nonce');
    if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_Error('invalid_nonce', __('Security check failed', 'pearblog-theme'), array('status' => 403));
    }

    // Get parameters
    $name = $request->get_param('name');
    $email = $request->get_param('email');
    $phone = $request->get_param('phone');
    $message = $request->get_param('message');
    $travel_dates = $request->get_param('travel_dates');
    $post_id = $request->get_param('post_id');
    $lead_type = $request->get_param('lead_type') ?: 'general';
    $form_position = $request->get_param('form_position') ?: 'inline';

    // Validate email
    if (!is_email($email)) {
        return new WP_Error('invalid_email', __('Invalid email address', 'pearblog-theme'), array('status' => 400));
    }

    // Create lead post
    $lead_data = array(
        'post_type' => 'pearblog_lead',
        'post_title' => sprintf('%s - %s', $name, $email),
        'post_content' => $message,
        'post_status' => 'publish',
        'post_author' => 0, // System generated
    );

    $lead_id = wp_insert_post($lead_data);

    if (is_wp_error($lead_id)) {
        return new WP_Error('creation_failed', __('Failed to create lead', 'pearblog-theme'), array('status' => 500));
    }

    // Store lead meta data
    update_post_meta($lead_id, '_pearblog_lead_name', $name);
    update_post_meta($lead_id, '_pearblog_lead_email', $email);
    update_post_meta($lead_id, '_pearblog_lead_phone', $phone);
    update_post_meta($lead_id, '_pearblog_lead_type', $lead_type);
    update_post_meta($lead_id, '_pearblog_lead_travel_dates', $travel_dates);
    update_post_meta($lead_id, '_pearblog_lead_source_post', $post_id);
    update_post_meta($lead_id, '_pearblog_lead_form_position', $form_position);
    update_post_meta($lead_id, '_pearblog_lead_status', 'new');
    update_post_meta($lead_id, '_pearblog_lead_created_at', current_time('mysql'));
    update_post_meta($lead_id, '_pearblog_lead_ip', $_SERVER['REMOTE_ADDR'] ?? '');
    update_post_meta($lead_id, '_pearblog_lead_user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');

    // Track lead on source post
    if ($post_id) {
        $current_count = get_post_meta($post_id, '_pearblog_leads_count', true);
        $current_count = intval($current_count);
        update_post_meta($post_id, '_pearblog_leads_count', $current_count + 1);

        // Track by lead type
        $type_count_key = '_pearblog_leads_' . $lead_type;
        $type_count = get_post_meta($post_id, $type_count_key, true);
        $type_count = intval($type_count);
        update_post_meta($post_id, $type_count_key, $type_count + 1);
    }

    // Send notification email
    pearblog_send_lead_notification($lead_id, array(
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'travel_dates' => $travel_dates,
        'lead_type' => $lead_type,
        'source_post_id' => $post_id,
    ));

    // Return success response
    return new WP_REST_Response(array(
        'success' => true,
        'message' => __('Thank you! We\'ll contact you soon.', 'pearblog-theme'),
        'lead_id' => $lead_id,
    ), 201);
}

/**
 * Send email notification for new lead
 */
function pearblog_send_lead_notification($lead_id, $lead_data) {
    $notification_email = get_option('pearblog_lead_notification_email', get_option('admin_email'));

    if (empty($notification_email)) {
        return false;
    }

    $subject = sprintf(
        __('[%s] New Lead: %s', 'pearblog-theme'),
        get_bloginfo('name'),
        $lead_data['name']
    );

    $message = sprintf(
        __('New lead received from %s', 'pearblog-theme') . "\n\n" .
        __('Name: %s', 'pearblog-theme') . "\n" .
        __('Email: %s', 'pearblog-theme') . "\n" .
        __('Phone: %s', 'pearblog-theme') . "\n" .
        __('Lead Type: %s', 'pearblog-theme') . "\n" .
        __('Travel Dates: %s', 'pearblog-theme') . "\n\n" .
        __('Message:', 'pearblog-theme') . "\n%s\n\n" .
        __('View in admin:', 'pearblog-theme') . "\n%s",
        get_bloginfo('name'),
        $lead_data['name'],
        $lead_data['email'],
        $lead_data['phone'] ?: __('Not provided', 'pearblog-theme'),
        $lead_data['lead_type'],
        $lead_data['travel_dates'] ?: __('Not provided', 'pearblog-theme'),
        $lead_data['message'],
        admin_url('post.php?post=' . $lead_id . '&action=edit')
    );

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $lead_data['email'],
    );

    return wp_mail($notification_email, $subject, $message, $headers);
}

/**
 * API Callback: Get lead statistics
 */
function pearblog_api_get_lead_stats($request) {
    global $wpdb;

    // Total leads
    $total_leads = wp_count_posts('pearblog_lead')->publish;

    // Leads by type
    $leads_by_type = $wpdb->get_results(
        "SELECT meta_value as type, COUNT(*) as count
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_pearblog_lead_type'
        GROUP BY meta_value",
        ARRAY_A
    );

    // Leads by status
    $leads_by_status = $wpdb->get_results(
        "SELECT meta_value as status, COUNT(*) as count
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_pearblog_lead_status'
        GROUP BY meta_value",
        ARRAY_A
    );

    // Recent leads (last 30 days)
    $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
    $recent_leads = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta}
        WHERE meta_key = '_pearblog_lead_created_at'
        AND meta_value >= %s",
        $thirty_days_ago
    ));

    // Top converting posts
    $top_posts = $wpdb->get_results(
        "SELECT post_id, meta_value as leads_count
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_pearblog_leads_count'
        AND meta_value > 0
        ORDER BY CAST(meta_value AS UNSIGNED) DESC
        LIMIT 10",
        ARRAY_A
    );

    return new WP_REST_Response(array(
        'total_leads' => intval($total_leads),
        'recent_leads' => intval($recent_leads),
        'leads_by_type' => $leads_by_type,
        'leads_by_status' => $leads_by_status,
        'top_converting_posts' => $top_posts,
    ), 200);
}

/**
 * Add custom columns to leads admin list
 */
function pearblog_lead_columns($columns) {
    $new_columns = array(
        'cb' => $columns['cb'],
        'title' => __('Lead', 'pearblog-theme'),
        'lead_email' => __('Email', 'pearblog-theme'),
        'lead_phone' => __('Phone', 'pearblog-theme'),
        'lead_type' => __('Type', 'pearblog-theme'),
        'lead_status' => __('Status', 'pearblog-theme'),
        'lead_source' => __('Source Post', 'pearblog-theme'),
        'date' => __('Date', 'pearblog-theme'),
    );
    return $new_columns;
}
add_filter('manage_pearblog_lead_posts_columns', 'pearblog_lead_columns');

/**
 * Populate custom columns
 */
function pearblog_lead_column_content($column, $post_id) {
    switch ($column) {
        case 'lead_email':
            $email = get_post_meta($post_id, '_pearblog_lead_email', true);
            echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            break;

        case 'lead_phone':
            $phone = get_post_meta($post_id, '_pearblog_lead_phone', true);
            echo $phone ? esc_html($phone) : '—';
            break;

        case 'lead_type':
            $type = get_post_meta($post_id, '_pearblog_lead_type', true);
            echo '<span class="pearblog-lead-type pearblog-lead-type--' . esc_attr($type) . '">' . esc_html(ucfirst($type)) . '</span>';
            break;

        case 'lead_status':
            $status = get_post_meta($post_id, '_pearblog_lead_status', true);
            $status_labels = array(
                'new' => __('New', 'pearblog-theme'),
                'contacted' => __('Contacted', 'pearblog-theme'),
                'qualified' => __('Qualified', 'pearblog-theme'),
                'converted' => __('Converted', 'pearblog-theme'),
                'closed' => __('Closed', 'pearblog-theme'),
            );
            $label = $status_labels[$status] ?? ucfirst($status);
            echo '<span class="pearblog-lead-status pearblog-lead-status--' . esc_attr($status) . '">' . esc_html($label) . '</span>';
            break;

        case 'lead_source':
            $source_post_id = get_post_meta($post_id, '_pearblog_lead_source_post', true);
            if ($source_post_id) {
                $source_post = get_post($source_post_id);
                if ($source_post) {
                    echo '<a href="' . esc_url(get_permalink($source_post_id)) . '" target="_blank">' . esc_html($source_post->post_title) . '</a>';
                }
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_pearblog_lead_posts_custom_column', 'pearblog_lead_column_content', 10, 2);

/**
 * Add lead meta box to edit screen
 */
function pearblog_add_lead_meta_box() {
    add_meta_box(
        'pearblog_lead_details',
        __('Lead Details', 'pearblog-theme'),
        'pearblog_lead_meta_box_callback',
        'pearblog_lead',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'pearblog_add_lead_meta_box');

/**
 * Lead meta box callback
 */
function pearblog_lead_meta_box_callback($post) {
    $name = get_post_meta($post->ID, '_pearblog_lead_name', true);
    $email = get_post_meta($post->ID, '_pearblog_lead_email', true);
    $phone = get_post_meta($post->ID, '_pearblog_lead_phone', true);
    $travel_dates = get_post_meta($post->ID, '_pearblog_lead_travel_dates', true);
    $lead_type = get_post_meta($post->ID, '_pearblog_lead_type', true);
    $status = get_post_meta($post->ID, '_pearblog_lead_status', true);
    $source_post_id = get_post_meta($post->ID, '_pearblog_lead_source_post', true);
    $created_at = get_post_meta($post->ID, '_pearblog_lead_created_at', true);
    $ip = get_post_meta($post->ID, '_pearblog_lead_ip', true);

    wp_nonce_field('pearblog_lead_meta_box', 'pearblog_lead_meta_box_nonce');
    ?>
    <table class="form-table">
        <tr>
            <th><label><?php _e('Name', 'pearblog-theme'); ?></label></th>
            <td><strong><?php echo esc_html($name); ?></strong></td>
        </tr>
        <tr>
            <th><label><?php _e('Email', 'pearblog-theme'); ?></label></th>
            <td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td>
        </tr>
        <tr>
            <th><label><?php _e('Phone', 'pearblog-theme'); ?></label></th>
            <td><?php echo $phone ? esc_html($phone) : '—'; ?></td>
        </tr>
        <tr>
            <th><label><?php _e('Travel Dates', 'pearblog-theme'); ?></label></th>
            <td><?php echo $travel_dates ? esc_html($travel_dates) : '—'; ?></td>
        </tr>
        <tr>
            <th><label for="pearblog_lead_status"><?php _e('Status', 'pearblog-theme'); ?></label></th>
            <td>
                <select name="pearblog_lead_status" id="pearblog_lead_status">
                    <option value="new" <?php selected($status, 'new'); ?>><?php _e('New', 'pearblog-theme'); ?></option>
                    <option value="contacted" <?php selected($status, 'contacted'); ?>><?php _e('Contacted', 'pearblog-theme'); ?></option>
                    <option value="qualified" <?php selected($status, 'qualified'); ?>><?php _e('Qualified', 'pearblog-theme'); ?></option>
                    <option value="converted" <?php selected($status, 'converted'); ?>><?php _e('Converted', 'pearblog-theme'); ?></option>
                    <option value="closed" <?php selected($status, 'closed'); ?>><?php _e('Closed', 'pearblog-theme'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('Lead Type', 'pearblog-theme'); ?></label></th>
            <td><?php echo esc_html(ucfirst($lead_type)); ?></td>
        </tr>
        <?php if ($source_post_id): ?>
        <tr>
            <th><label><?php _e('Source Post', 'pearblog-theme'); ?></label></th>
            <td>
                <a href="<?php echo esc_url(get_edit_post_link($source_post_id)); ?>" target="_blank">
                    <?php echo esc_html(get_the_title($source_post_id)); ?>
                </a>
            </td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><label><?php _e('Submitted', 'pearblog-theme'); ?></label></th>
            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_at))); ?></td>
        </tr>
        <tr>
            <th><label><?php _e('IP Address', 'pearblog-theme'); ?></label></th>
            <td><?php echo esc_html($ip); ?></td>
        </tr>
    </table>
    <?php
}

/**
 * Save lead meta box data
 */
function pearblog_save_lead_meta_box($post_id) {
    if (!isset($_POST['pearblog_lead_meta_box_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['pearblog_lead_meta_box_nonce'], 'pearblog_lead_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['pearblog_lead_status'])) {
        update_post_meta($post_id, '_pearblog_lead_status', sanitize_text_field($_POST['pearblog_lead_status']));
    }
}
add_action('save_post_pearblog_lead', 'pearblog_save_lead_meta_box');

/**
 * Helper function to display lead capture form
 */
function pearblog_lead_capture_form($args = array()) {
    get_template_part('template-parts/form-lead-capture', null, $args);
}
