<?php
/**
 * PT24 Form Handler
 *
 * Handles lead form submissions and business registration
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle lead form submission
 */
function pt24_handle_lead_submission() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pt24_nonce')) {
        // Also support legacy nonce field
        if (!isset($_POST['pt24_nonce']) || !wp_verify_nonce($_POST['pt24_nonce'], 'pt24_lead_submit')) {
            wp_send_json_error(['message' => 'Nieprawidłowe żądanie'], 403);
        }
    }

    // Sanitize input
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $city = sanitize_text_field($_POST['city_input'] ?? $_POST['city'] ?? '');
    $service = sanitize_text_field($_POST['service'] ?? '');
    $service_need = sanitize_textarea_field($_POST['service_need'] ?? $_POST['description'] ?? '');
    $source_url = esc_url_raw($_POST['source_url'] ?? wp_get_referer() ?? '');
    $consent = isset($_POST['consent']) ? true : true; // V3 form has implicit consent

    // Validate required fields (V3 form requires: name, phone, service, city, description)
    if (empty($name) || empty($phone) || empty($service) || empty($city)) {
        wp_send_json_error(['message' => 'Wypełnij wszystkie wymagane pola'], 400);
    }

    // Validate email if provided
    if (!empty($email) && !is_email($email)) {
        wp_send_json_error(['message' => 'Nieprawidłowy adres email'], 400);
    }

    // Store lead in database
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
            'message' => $service_need,
            'source' => $source_url,
            'status' => 'new',
            'created_at' => current_time('mysql'),
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Błąd zapisu. Spróbuj ponownie.'], 500);
    }

    $lead_id = $wpdb->insert_id;

    // Send notification email to admin
    $admin_email = get_option('admin_email');
    $subject = "Nowy lead: $service w $city";
    $message = "Nowy lead od: $name\n\n";
    $message .= "Email: $email\n";
    $message .= "Telefon: $phone\n";
    $message .= "Miasto: $city\n";
    $message .= "Usługa: $service\n";
    $message .= "Potrzeba:\n$service_need\n\n";
    $message .= "Źródło: $source_url\n";
    $message .= "Data: " . current_time('Y-m-d H:i:s') . "\n";

    wp_mail($admin_email, $subject, $message);

    // Send confirmation email to user
    $user_subject = "Otrzymaliśmy Twoje zapytanie — PT24.pro";
    $user_message = "Cześć $name!\n\n";
    $user_message .= "Dziękujemy za skorzystanie z PT24.pro.\n\n";
    $user_message .= "Otrzymaliśmy Twoje zapytanie dotyczące: $service w $city\n\n";
    $user_message .= "W ciągu najbliższych 24 godzin skontaktują się z Tobą lokalne firmy z ofertami.\n\n";
    $user_message .= "Pozdrawiamy,\n";
    $user_message .= "Zespół PT24.pro\n";

    wp_mail($email, $user_subject, $user_message);

    // Hook for third-party integrations
    do_action('pt24_lead_submitted', $lead_id, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'service' => $service,
        'message' => $service_need,
    ]);

    wp_send_json_success([
        'message' => 'Dziękujemy! Otrzymasz oferty w ciągu 24h.',
        'lead_id' => $lead_id
    ]);
}
add_action('wp_ajax_pt24_submit_lead', 'pt24_handle_lead_submission');
add_action('wp_ajax_nopriv_pt24_submit_lead', 'pt24_handle_lead_submission');

/**
 * Track business profile interactions
 */
function pt24_track_business_interaction() {
    check_ajax_referer('pt24_tracking', 'nonce');

    $business_id = intval($_POST['business_id'] ?? 0);
    $action = sanitize_text_field($_POST['track_action'] ?? '');

    if (!$business_id || !$action) {
        wp_send_json_error(['message' => 'Invalid parameters'], 400);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pt24_business_stats';
    $today = current_time('Y-m-d');

    // Get or create today's stats row
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE business_id = %d AND date = %s",
        $business_id,
        $today
    ));

    if (!$stats) {
        // Create new stats row
        $wpdb->insert(
            $table_name,
            [
                'business_id' => $business_id,
                'date' => $today,
                'views' => 0,
                'phone_clicks' => 0,
                'email_clicks' => 0,
            ],
            ['%d', '%s', '%d', '%d', '%d']
        );
    }

    // Update appropriate counter
    $field_map = [
        'phone_click' => 'phone_clicks',
        'phone_click_bottom' => 'phone_clicks',
        'email_click' => 'email_clicks',
        'website_click' => 'website_clicks',
    ];

    $field = $field_map[$action] ?? null;

    if ($field) {
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET $field = $field + 1 WHERE business_id = %d AND date = %s",
            $business_id,
            $today
        ));
    }

    wp_send_json_success(['message' => 'Tracked']);
}
add_action('wp_ajax_pt24_track_interaction', 'pt24_track_business_interaction');
add_action('wp_ajax_nopriv_pt24_track_interaction', 'pt24_track_business_interaction');

/**
 * Track page views for businesses
 */
function pt24_track_business_view() {
    if (!is_singular('pt24_business')) {
        return;
    }

    $business_id = get_the_ID();

    global $wpdb;
    $table_name = $wpdb->prefix . 'pt24_business_stats';
    $today = current_time('Y-m-d');

    // Get or create today's stats row
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE business_id = %d AND date = %s",
        $business_id,
        $today
    ));

    if (!$stats) {
        $wpdb->insert(
            $table_name,
            [
                'business_id' => $business_id,
                'date' => $today,
                'views' => 1,
                'phone_clicks' => 0,
                'email_clicks' => 0,
            ],
            ['%d', '%s', '%d', '%d', '%d']
        );
    } else {
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET views = views + 1 WHERE business_id = %d AND date = %s",
            $business_id,
            $today
        ));
    }
}
add_action('wp', 'pt24_track_business_view');

/**
 * Handle business registration form
 */
function pt24_handle_business_registration() {
    // Verify nonce
    if (!isset($_POST['pt24_business_nonce']) || !wp_verify_nonce($_POST['pt24_business_nonce'], 'pt24_business_register')) {
        wp_send_json_error(['message' => 'Nieprawidłowe żądanie'], 403);
    }

    // Sanitize input
    $business_name = sanitize_text_field($_POST['business_name'] ?? '');
    $owner_name = sanitize_text_field($_POST['owner_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $city = sanitize_text_field($_POST['city'] ?? '');
    $service = sanitize_text_field($_POST['service'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');

    // Validate
    if (empty($business_name) || empty($email) || empty($phone) || empty($city) || empty($service)) {
        wp_send_json_error(['message' => 'Wypełnij wszystkie wymagane pola'], 400);
    }

    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Nieprawidłowy adres email'], 400);
    }

    // Create business post (as draft)
    $post_id = wp_insert_post([
        'post_title' => $business_name,
        'post_content' => $description,
        'post_status' => 'pending',
        'post_type' => 'pt24_business',
        'post_author' => 1, // Admin
    ]);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Błąd tworzenia profilu'], 500);
    }

    // Add meta
    update_post_meta($post_id, 'pt24_owner_name', $owner_name);
    update_post_meta($post_id, 'pt24_phone', $phone);
    update_post_meta($post_id, 'pt24_email', $email);
    update_post_meta($post_id, 'pt24_plan', 'free');
    update_post_meta($post_id, 'pt24_status', 'pending_approval');

    // Set taxonomy
    if ($city) {
        $city_term = term_exists($city, 'pt24_city');
        if (!$city_term) {
            $city_term = wp_insert_term($city, 'pt24_city');
        }
        if (!is_wp_error($city_term)) {
            wp_set_object_terms($post_id, $city_term['term_id'], 'pt24_city');
        }
    }

    if ($service) {
        $service_term = term_exists($service, 'pt24_service_cat');
        if (!$service_term) {
            $service_term = wp_insert_term($service, 'pt24_service_cat');
        }
        if (!is_wp_error($service_term)) {
            wp_set_object_terms($post_id, $service_term['term_id'], 'pt24_service_cat');
        }
    }

    // Send notification email to admin
    $admin_email = get_option('admin_email');
    $subject = "Nowa rejestracja firmy: $business_name";
    $message = "Nowa firma oczekuje na zatwierdzenie:\n\n";
    $message .= "Nazwa: $business_name\n";
    $message .= "Właściciel: $owner_name\n";
    $message .= "Email: $email\n";
    $message .= "Telefon: $phone\n";
    $message .= "Miasto: $city\n";
    $message .= "Usługa: $service\n\n";
    $message .= "Link do edycji: " . admin_url("post.php?post=$post_id&action=edit") . "\n";

    wp_mail($admin_email, $subject, $message);

    // Send confirmation to business owner
    $owner_subject = "Rejestracja firmy w PT24.pro — oczekuje na weryfikację";
    $owner_message = "Cześć $owner_name!\n\n";
    $owner_message .= "Dziękujemy za rejestrację firmy $business_name w PT24.pro.\n\n";
    $owner_message .= "Twój profil został wysłany do weryfikacji. Skontaktujemy się z Tobą w ciągu 24-48 godzin.\n\n";
    $owner_message .= "Pozdrawiamy,\n";
    $owner_message .= "Zespół PT24.pro\n";

    wp_mail($email, $owner_subject, $owner_message);

    wp_send_json_success([
        'message' => 'Dziękujemy! Skontaktujemy się w ciągu 24-48h.',
        'business_id' => $post_id
    ]);
}
add_action('wp_ajax_pt24_register_business', 'pt24_handle_business_registration');
add_action('wp_ajax_nopriv_pt24_register_business', 'pt24_handle_business_registration');

/**
 * Track custom events (for V3 homepage analytics)
 */
function pt24_track_event() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pt24_nonce')) {
        wp_send_json_error(['message' => 'Nieprawidłowe żądanie'], 403);
    }

    $event_type = sanitize_text_field($_POST['event_type'] ?? '');

    if (empty($event_type)) {
        wp_send_json_error(['message' => 'Event type is required'], 400);
    }

    // Store event in transient for analytics (optional)
    $events_key = 'pt24_events_' . date('Y-m-d');
    $events = get_transient($events_key) ?: [];

    if (!isset($events[$event_type])) {
        $events[$event_type] = 0;
    }

    $events[$event_type]++;
    set_transient($events_key, $events, DAY_IN_SECONDS);

    // Hook for custom tracking integrations
    do_action('pt24_event_tracked', $event_type);

    wp_send_json_success(['message' => 'Event tracked']);
}
add_action('wp_ajax_pt24_track_event', 'pt24_track_event');
add_action('wp_ajax_nopriv_pt24_track_event', 'pt24_track_event');
