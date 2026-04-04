<?php
/**
 * Email List Building System
 *
 * Handles newsletter subscriptions, content upgrades, and ESP integration
 *
 * @package PearBlog
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Email Subscriber Custom Post Type
 */
function pearblog_register_subscriber_post_type() {
    register_post_type('pearblog_subscriber', array(
        'labels' => array(
            'name' => __('Email Subscribers', 'pearblog-theme'),
            'singular_name' => __('Subscriber', 'pearblog-theme'),
            'add_new' => __('Add Subscriber', 'pearblog-theme'),
            'add_new_item' => __('Add New Subscriber', 'pearblog-theme'),
            'edit_item' => __('Edit Subscriber', 'pearblog-theme'),
            'view_item' => __('View Subscriber', 'pearblog-theme'),
            'search_items' => __('Search Subscribers', 'pearblog-theme'),
            'not_found' => __('No subscribers found', 'pearblog-theme'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-email-alt',
        'capability_type' => 'post',
        'supports' => array('title'),
        'has_archive' => false,
        'rewrite' => false,
    ));

    // Register Content Upgrade Custom Post Type
    register_post_type('pearblog_upgrade', array(
        'labels' => array(
            'name' => __('Content Upgrades', 'pearblog-theme'),
            'singular_name' => __('Content Upgrade', 'pearblog-theme'),
            'add_new' => __('Add Content Upgrade', 'pearblog-theme'),
            'add_new_item' => __('Add New Content Upgrade', 'pearblog-theme'),
            'edit_item' => __('Edit Content Upgrade', 'pearblog-theme'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-download',
        'supports' => array('title', 'editor', 'thumbnail'),
        'has_archive' => false,
    ));
}
add_action('init', 'pearblog_register_subscriber_post_type');

/**
 * Register REST API endpoints for email subscriptions
 */
function pearblog_register_email_endpoints() {
    // Subscribe endpoint
    register_rest_route('pearblog/v1', '/subscribe', array(
        'methods' => 'POST',
        'callback' => 'pearblog_api_subscribe',
        'permission_callback' => '__return_true',
        'args' => array(
            'email' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => 'is_email',
                'sanitize_callback' => 'sanitize_email',
            ),
            'name' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'subscription_type' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'post_id' => array(
                'required' => false,
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'content_upgrade_id' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    // Get subscriber stats
    register_rest_route('pearblog/v1', '/subscribers/stats', array(
        'methods' => 'GET',
        'callback' => 'pearblog_api_subscriber_stats',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ));

    // Confirm subscription (double opt-in)
    register_rest_route('pearblog/v1', '/subscribe/confirm/(?P<token>[a-zA-Z0-9]+)', array(
        'methods' => 'GET',
        'callback' => 'pearblog_api_confirm_subscription',
        'permission_callback' => '__return_true',
    ));

    // Unsubscribe endpoint
    register_rest_route('pearblog/v1', '/unsubscribe/(?P<token>[a-zA-Z0-9]+)', array(
        'methods' => 'GET',
        'callback' => 'pearblog_api_unsubscribe',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'pearblog_register_email_endpoints');

/**
 * API Callback: Subscribe to email list
 */
function pearblog_api_subscribe($request) {
    $email = $request->get_param('email');
    $name = $request->get_param('name');
    $subscription_type = $request->get_param('subscription_type');
    $post_id = $request->get_param('post_id');
    $content_upgrade_id = $request->get_param('content_upgrade_id');

    // Check if already subscribed
    $existing = get_posts(array(
        'post_type' => 'pearblog_subscriber',
        'meta_query' => array(
            array(
                'key' => 'subscriber_email',
                'value' => $email,
                'compare' => '=',
            ),
        ),
        'posts_per_page' => 1,
    ));

    if (!empty($existing)) {
        $subscriber_id = $existing[0]->ID;
        $status = get_post_meta($subscriber_id, 'subscriber_status', true);

        if ($status === 'active') {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('This email is already subscribed.', 'pearblog-theme'),
            ), 400);
        }
    } else {
        // Create new subscriber
        $subscriber_id = wp_insert_post(array(
            'post_type' => 'pearblog_subscriber',
            'post_title' => $email,
            'post_status' => 'publish',
        ));

        if (is_wp_error($subscriber_id)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to create subscriber.', 'pearblog-theme'),
            ), 500);
        }
    }

    // Generate confirmation token
    $confirmation_token = bin2hex(random_bytes(32));

    // Save subscriber meta
    update_post_meta($subscriber_id, 'subscriber_email', $email);
    update_post_meta($subscriber_id, 'subscriber_name', $name);
    update_post_meta($subscriber_id, 'subscriber_status', 'pending');
    update_post_meta($subscriber_id, 'subscription_type', $subscription_type);
    update_post_meta($subscriber_id, 'subscribed_from_post', $post_id);
    update_post_meta($subscriber_id, 'subscribed_date', current_time('mysql'));
    update_post_meta($subscriber_id, 'confirmation_token', $confirmation_token);
    update_post_meta($subscriber_id, 'subscriber_ip', $_SERVER['REMOTE_ADDR'] ?? '');
    update_post_meta($subscriber_id, 'subscriber_user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');

    // Content upgrade specific
    if (!empty($content_upgrade_id)) {
        update_post_meta($subscriber_id, 'content_upgrade_id', $content_upgrade_id);
    }

    // Track subscription in post meta
    if (!empty($post_id)) {
        $sub_count = get_post_meta($post_id, '_pearblog_subscriber_count', true);
        update_post_meta($post_id, '_pearblog_subscriber_count', intval($sub_count) + 1);
    }

    // Send confirmation email (double opt-in)
    pearblog_send_confirmation_email($email, $name, $confirmation_token);

    // Sync with ESP if configured
    pearblog_sync_to_esp($email, $name, $subscription_type, 'pending');

    $response_data = array(
        'success' => true,
        'message' => __('Success! Please check your email to confirm your subscription.', 'pearblog-theme'),
    );

    // If content upgrade, provide download URL after confirmation
    if (!empty($content_upgrade_id)) {
        $response_data['requires_confirmation'] = true;
    }

    return new WP_REST_Response($response_data, 200);
}

/**
 * API Callback: Confirm subscription (double opt-in)
 */
function pearblog_api_confirm_subscription($request) {
    $token = $request->get_param('token');

    // Find subscriber by token
    $subscribers = get_posts(array(
        'post_type' => 'pearblog_subscriber',
        'meta_query' => array(
            array(
                'key' => 'confirmation_token',
                'value' => $token,
                'compare' => '=',
            ),
        ),
        'posts_per_page' => 1,
    ));

    if (empty($subscribers)) {
        wp_redirect(home_url('/?subscription=invalid'));
        exit;
    }

    $subscriber_id = $subscribers[0]->ID;
    $email = get_post_meta($subscriber_id, 'subscriber_email', true);
    $name = get_post_meta($subscriber_id, 'subscriber_name', true);
    $subscription_type = get_post_meta($subscriber_id, 'subscription_type', true);
    $content_upgrade_id = get_post_meta($subscriber_id, 'content_upgrade_id', true);

    // Activate subscription
    update_post_meta($subscriber_id, 'subscriber_status', 'active');
    update_post_meta($subscriber_id, 'confirmed_date', current_time('mysql'));
    delete_post_meta($subscriber_id, 'confirmation_token');

    // Sync with ESP
    pearblog_sync_to_esp($email, $name, $subscription_type, 'active');

    // Send welcome email
    pearblog_send_welcome_email($email, $name);

    // If content upgrade, redirect to download
    if (!empty($content_upgrade_id)) {
        $download_url = pearblog_get_content_upgrade_download_url($content_upgrade_id, $subscriber_id);
        wp_redirect($download_url);
        exit;
    }

    wp_redirect(home_url('/?subscription=confirmed'));
    exit;
}

/**
 * API Callback: Unsubscribe from email list
 */
function pearblog_api_unsubscribe($request) {
    $token = $request->get_param('token');

    // Find subscriber by unsubscribe token
    $subscribers = get_posts(array(
        'post_type' => 'pearblog_subscriber',
        'meta_query' => array(
            array(
                'key' => 'unsubscribe_token',
                'value' => $token,
                'compare' => '=',
            ),
        ),
        'posts_per_page' => 1,
    ));

    if (empty($subscribers)) {
        wp_redirect(home_url('/?unsubscribe=invalid'));
        exit;
    }

    $subscriber_id = $subscribers[0]->ID;
    $email = get_post_meta($subscriber_id, 'subscriber_email', true);

    // Deactivate subscription
    update_post_meta($subscriber_id, 'subscriber_status', 'unsubscribed');
    update_post_meta($subscriber_id, 'unsubscribed_date', current_time('mysql'));

    // Sync with ESP
    pearblog_sync_to_esp($email, '', '', 'unsubscribed');

    wp_redirect(home_url('/?unsubscribe=confirmed'));
    exit;
}

/**
 * API Callback: Get subscriber statistics
 */
function pearblog_api_subscriber_stats($request) {
    global $wpdb;

    $total_subscribers = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta}
        WHERE meta_key = 'subscriber_status' AND meta_value = 'active'"
    );

    $pending_subscribers = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta}
        WHERE meta_key = 'subscriber_status' AND meta_value = 'pending'"
    );

    $unsubscribed = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta}
        WHERE meta_key = 'subscriber_status' AND meta_value = 'unsubscribed'"
    );

    // Get growth stats (last 30 days)
    $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
    $recent_subscribers = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm1
        INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
        WHERE pm1.meta_key = 'subscriber_status' AND pm1.meta_value = 'active'
        AND pm2.meta_key = 'subscribed_date' AND pm2.meta_value > %s",
        $thirty_days_ago
    ));

    return new WP_REST_Response(array(
        'success' => true,
        'stats' => array(
            'total_active' => intval($total_subscribers),
            'pending' => intval($pending_subscribers),
            'unsubscribed' => intval($unsubscribed),
            'recent_30_days' => intval($recent_subscribers),
        ),
    ), 200);
}

/**
 * Send confirmation email (double opt-in)
 */
function pearblog_send_confirmation_email($email, $name, $token) {
    $confirmation_url = rest_url('pearblog/v1/subscribe/confirm/' . $token);
    $site_name = get_bloginfo('name');

    $subject = sprintf(__('Confirm your subscription to %s', 'pearblog-theme'), $site_name);

    $greeting = !empty($name) ? sprintf(__('Hi %s,', 'pearblog-theme'), $name) : __('Hi there,', 'pearblog-theme');

    $message = $greeting . "\n\n";
    $message .= sprintf(__('Thanks for subscribing to %s!', 'pearblog-theme'), $site_name) . "\n\n";
    $message .= __('Please confirm your subscription by clicking the link below:', 'pearblog-theme') . "\n\n";
    $message .= $confirmation_url . "\n\n";
    $message .= __('If you didn\'t sign up for this newsletter, you can safely ignore this email.', 'pearblog-theme') . "\n\n";
    $message .= __('Best regards,', 'pearblog-theme') . "\n";
    $message .= $site_name;

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    wp_mail($email, $subject, $message, $headers);
}

/**
 * Send welcome email after confirmation
 */
function pearblog_send_welcome_email($email, $name) {
    $site_name = get_bloginfo('name');
    $subject = sprintf(__('Welcome to %s!', 'pearblog-theme'), $site_name);

    $greeting = !empty($name) ? sprintf(__('Hi %s,', 'pearblog-theme'), $name) : __('Hi there,', 'pearblog-theme');

    // Generate unsubscribe token
    $unsubscribe_token = bin2hex(random_bytes(32));

    // Find subscriber and save token
    $subscribers = get_posts(array(
        'post_type' => 'pearblog_subscriber',
        'meta_query' => array(
            array('key' => 'subscriber_email', 'value' => $email, 'compare' => '='),
        ),
        'posts_per_page' => 1,
    ));

    if (!empty($subscribers)) {
        update_post_meta($subscribers[0]->ID, 'unsubscribe_token', $unsubscribe_token);
    }

    $unsubscribe_url = rest_url('pearblog/v1/unsubscribe/' . $unsubscribe_token);

    $message = $greeting . "\n\n";
    $message .= sprintf(__('Welcome to the %s community! 🎉', 'pearblog-theme'), $site_name) . "\n\n";
    $message .= __('You\'re now subscribed to receive exclusive travel tips, destination guides, and insider recommendations.', 'pearblog-theme') . "\n\n";
    $message .= __('Here\'s what you can expect:', 'pearblog-theme') . "\n";
    $message .= __('• Weekly destination inspiration', 'pearblog-theme') . "\n";
    $message .= __('• Exclusive travel guides and resources', 'pearblog-theme') . "\n";
    $message .= __('• Money-saving tips and deals', 'pearblog-theme') . "\n";
    $message .= __('• Behind-the-scenes travel stories', 'pearblog-theme') . "\n\n";
    $message .= sprintf(__('Explore our latest articles: %s', 'pearblog-theme'), home_url()) . "\n\n";
    $message .= __('Safe travels!', 'pearblog-theme') . "\n";
    $message .= $site_name . "\n\n";
    $message .= '---' . "\n";
    $message .= sprintf(__('Unsubscribe: %s', 'pearblog-theme'), $unsubscribe_url);

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    wp_mail($email, $subject, $message, $headers);
}

/**
 * Sync subscriber to Email Service Provider (ESP)
 */
function pearblog_sync_to_esp($email, $name, $subscription_type, $status) {
    $esp_provider = get_option('pearblog_esp_provider', 'none');

    if ($esp_provider === 'none') {
        return;
    }

    /**
     * Filter: pearblog_sync_to_esp
     *
     * Allows custom ESP integrations to sync subscribers.
     *
     * @param string $email             Subscriber email
     * @param string $name              Subscriber name
     * @param string $subscription_type Type of subscription
     * @param string $status            Subscription status
     * @param string $esp_provider      ESP provider name
     */
    do_action('pearblog_sync_to_esp', $email, $name, $subscription_type, $status, $esp_provider);

    // Built-in integrations
    switch ($esp_provider) {
        case 'mailchimp':
            pearblog_sync_to_mailchimp($email, $name, $subscription_type, $status);
            break;
        case 'convertkit':
            pearblog_sync_to_convertkit($email, $name, $subscription_type, $status);
            break;
    }
}

/**
 * Sync to Mailchimp
 */
function pearblog_sync_to_mailchimp($email, $name, $subscription_type, $status) {
    $api_key = get_option('pearblog_mailchimp_api_key', '');
    $list_id = get_option('pearblog_mailchimp_list_id', '');

    if (empty($api_key) || empty($list_id)) {
        return;
    }

    // Extract datacenter from API key
    $datacenter = substr($api_key, strpos($api_key, '-') + 1);
    $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$list_id}/members";

    $subscriber_hash = md5(strtolower($email));

    $data = array(
        'email_address' => $email,
        'status' => $status === 'active' ? 'subscribed' : ($status === 'pending' ? 'pending' : 'unsubscribed'),
    );

    if (!empty($name)) {
        $name_parts = explode(' ', $name, 2);
        $data['merge_fields'] = array(
            'FNAME' => $name_parts[0],
            'LNAME' => $name_parts[1] ?? '',
        );
    }

    // Use PUT to create or update
    $response = wp_remote_request("{$url}/{$subscriber_hash}", array(
        'method' => 'PUT',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($data),
        'timeout' => 15,
    ));

    if (is_wp_error($response)) {
        error_log('Mailchimp sync error: ' . $response->get_error_message());
    }
}

/**
 * Sync to ConvertKit
 */
function pearblog_sync_to_convertkit($email, $name, $subscription_type, $status) {
    $api_key = get_option('pearblog_convertkit_api_key', '');
    $form_id = get_option('pearblog_convertkit_form_id', '');

    if (empty($api_key) || empty($form_id)) {
        return;
    }

    if ($status === 'unsubscribed') {
        // Unsubscribe API
        $url = "https://api.convertkit.com/v3/unsubscribe";
        $data = array(
            'api_secret' => $api_key,
            'email' => $email,
        );
    } else {
        // Subscribe API
        $url = "https://api.convertkit.com/v3/forms/{$form_id}/subscribe";
        $data = array(
            'api_key' => $api_key,
            'email' => $email,
        );

        if (!empty($name)) {
            $data['first_name'] = $name;
        }
    }

    $response = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($data),
        'timeout' => 15,
    ));

    if (is_wp_error($response)) {
        error_log('ConvertKit sync error: ' . $response->get_error_message());
    }
}

/**
 * Get content upgrade download URL
 */
function pearblog_get_content_upgrade_download_url($upgrade_id, $subscriber_id) {
    $upgrade = get_post($upgrade_id);

    if (!$upgrade || $upgrade->post_type !== 'pearblog_upgrade') {
        return home_url('/?download=invalid');
    }

    $file_url = get_post_meta($upgrade_id, 'upgrade_file_url', true);

    if (empty($file_url)) {
        return home_url('/?download=unavailable');
    }

    // Track download
    $download_count = get_post_meta($upgrade_id, '_download_count', true);
    update_post_meta($upgrade_id, '_download_count', intval($download_count) + 1);

    update_post_meta($subscriber_id, 'downloaded_upgrade_' . $upgrade_id, current_time('mysql'));

    return $file_url;
}

/**
 * Admin: Customize subscriber list columns
 */
function pearblog_subscriber_columns($columns) {
    return array(
        'cb' => $columns['cb'],
        'title' => __('Email', 'pearblog-theme'),
        'name' => __('Name', 'pearblog-theme'),
        'status' => __('Status', 'pearblog-theme'),
        'type' => __('Type', 'pearblog-theme'),
        'source' => __('Source Post', 'pearblog-theme'),
        'date' => __('Subscribed', 'pearblog-theme'),
    );
}
add_filter('manage_pearblog_subscriber_posts_columns', 'pearblog_subscriber_columns');

/**
 * Admin: Populate custom columns
 */
function pearblog_subscriber_column_content($column, $post_id) {
    switch ($column) {
        case 'name':
            $name = get_post_meta($post_id, 'subscriber_name', true);
            echo esc_html($name ?: '—');
            break;

        case 'status':
            $status = get_post_meta($post_id, 'subscriber_status', true);
            $status_labels = array(
                'active' => '<span style="color: green;">● Active</span>',
                'pending' => '<span style="color: orange;">● Pending</span>',
                'unsubscribed' => '<span style="color: red;">● Unsubscribed</span>',
            );
            echo wp_kses_post($status_labels[$status] ?? '—');
            break;

        case 'type':
            $type = get_post_meta($post_id, 'subscription_type', true);
            echo esc_html(ucfirst($type ?: 'newsletter'));
            break;

        case 'source':
            $source_post_id = get_post_meta($post_id, 'subscribed_from_post', true);
            if ($source_post_id) {
                $post = get_post($source_post_id);
                if ($post) {
                    echo '<a href="' . esc_url(get_edit_post_link($source_post_id)) . '">' . esc_html($post->post_title) . '</a>';
                } else {
                    echo '—';
                }
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_pearblog_subscriber_posts_custom_column', 'pearblog_subscriber_column_content', 10, 2);

/**
 * Admin: Add meta box for subscriber details
 */
function pearblog_subscriber_meta_box() {
    add_meta_box(
        'pearblog_subscriber_details',
        __('Subscriber Details', 'pearblog-theme'),
        'pearblog_subscriber_meta_box_content',
        'pearblog_subscriber',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'pearblog_subscriber_meta_box');

function pearblog_subscriber_meta_box_content($post) {
    $email = get_post_meta($post->ID, 'subscriber_email', true);
    $name = get_post_meta($post->ID, 'subscriber_name', true);
    $status = get_post_meta($post->ID, 'subscriber_status', true);
    $type = get_post_meta($post->ID, 'subscription_type', true);
    $subscribed_date = get_post_meta($post->ID, 'subscribed_date', true);
    $confirmed_date = get_post_meta($post->ID, 'confirmed_date', true);
    $ip = get_post_meta($post->ID, 'subscriber_ip', true);
    ?>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Email', 'pearblog-theme'); ?></th>
            <td><strong><?php echo esc_html($email); ?></strong></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Name', 'pearblog-theme'); ?></th>
            <td><?php echo esc_html($name ?: '—'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Status', 'pearblog-theme'); ?></th>
            <td>
                <select name="subscriber_status">
                    <option value="pending" <?php selected($status, 'pending'); ?>><?php esc_html_e('Pending', 'pearblog-theme'); ?></option>
                    <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Active', 'pearblog-theme'); ?></option>
                    <option value="unsubscribed" <?php selected($status, 'unsubscribed'); ?>><?php esc_html_e('Unsubscribed', 'pearblog-theme'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e('Subscription Type', 'pearblog-theme'); ?></th>
            <td><?php echo esc_html(ucfirst($type)); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Subscribed Date', 'pearblog-theme'); ?></th>
            <td><?php echo esc_html($subscribed_date); ?></td>
        </tr>
        <?php if ($confirmed_date): ?>
        <tr>
            <th><?php esc_html_e('Confirmed Date', 'pearblog-theme'); ?></th>
            <td><?php echo esc_html($confirmed_date); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><?php esc_html_e('IP Address', 'pearblog-theme'); ?></th>
            <td><?php echo esc_html($ip); ?></td>
        </tr>
    </table>
    <?php
    wp_nonce_field('pearblog_save_subscriber_meta', 'pearblog_subscriber_nonce');
}

/**
 * Save subscriber meta box data
 */
function pearblog_save_subscriber_meta($post_id) {
    if (!isset($_POST['pearblog_subscriber_nonce']) || !wp_verify_nonce($_POST['pearblog_subscriber_nonce'], 'pearblog_save_subscriber_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['subscriber_status'])) {
        update_post_meta($post_id, 'subscriber_status', sanitize_text_field($_POST['subscriber_status']));
    }
}
add_action('save_post_pearblog_subscriber', 'pearblog_save_subscriber_meta');

/**
 * Register ESP settings in admin
 */
function pearblog_register_esp_settings() {
    register_setting('pearblog_settings', 'pearblog_esp_provider', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('pearblog_settings', 'pearblog_mailchimp_api_key', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('pearblog_settings', 'pearblog_mailchimp_list_id', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('pearblog_settings', 'pearblog_convertkit_api_key', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('pearblog_settings', 'pearblog_convertkit_form_id', array('sanitize_callback' => 'sanitize_text_field'));
}
add_action('admin_init', 'pearblog_register_esp_settings');
