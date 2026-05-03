<?php
/**
 * Poradnik.pro Landing V5 - Backend Handler
 *
 * AJAX handlers, lead processing, email notifications
 *
 * @package PearBlog
 * @version 5.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Landing V5 Handler Class
 */
class PoradnikLandingV5Handler {
    /**
     * Initialize hooks
     */
    public static function init() {
        // AJAX handlers
        add_action('wp_ajax_plv5_submit_lead', [__CLASS__, 'handle_lead_submission']);
        add_action('wp_ajax_nopriv_plv5_submit_lead', [__CLASS__, 'handle_lead_submission']);

        // Enqueue assets for Landing V5 template
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_landing_assets']);
    }

    /**
     * Enqueue Landing V5 assets
     */
    public static function enqueue_landing_assets() {
        // Check if it's the Landing V5 template
        if (!is_page_template('page-poradnik-landing-v5.php')) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'poradnik-landing-v5',
            get_template_directory_uri() . '/assets/css/poradnik-landing-v5.css',
            [],
            '5.2.0'
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'poradnik-landing-v5',
            get_template_directory_uri() . '/assets/js/poradnik-landing-v5.js',
            [],
            '5.2.0',
            true
        );

        // Localize script with AJAX URL
        wp_localize_script('poradnik-landing-v5', 'poradnikData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('plv5_nonce'),
        ]);
    }

    /**
     * Handle lead form submission
     */
    public static function handle_lead_submission() {
        // Verify request
        if (!isset($_POST['action']) || $_POST['action'] !== 'plv5_submit_lead') {
            wp_send_json_error(['message' => 'Invalid action']);
        }

        // Get form data
        $service = sanitize_text_field($_POST['service'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? 'unknown');
        $meta = self::get_submission_meta();

        // Validate
        $errors = [];

        if (empty($service) && empty($email)) {
            $errors[] = 'Wypełnij przynajmniej jedno pole';
        }

        if (!empty($email) && !is_email($email)) {
            $errors[] = 'Podaj prawidłowy adres email';
        }

        if (!empty($errors)) {
            wp_send_json_error([
                'message' => implode('. ', $errors)
            ]);
        }

        // Process lead
        $lead_id = self::save_lead([
            'service' => $service,
            'email' => $email,
            'source' => $source,
            'ip' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'utm' => $_POST['utm'] ?? null,
            'meta' => $meta,
        ]);

        if (!$lead_id) {
            wp_send_json_error(['message' => 'Nie udało się zapisać zgłoszenia']);
        }

        // Send notification emails
        self::send_admin_notification($lead_id);
        self::send_user_confirmation($email, $service);

        // Return success
        wp_send_json_success([
            'message' => 'Dziękujemy! Skontaktujemy się wkrótce.',
            'lead_id' => $lead_id
        ]);
    }

    /**
     * Save lead to database
     *
     * @param array $data Lead data
     * @return int|false Lead ID or false on failure
     */
    private static function save_lead($data) {
        global $wpdb;

        $table = $wpdb->prefix . 'poradnik_leads';

        // Create table if it doesn't exist
        self::create_leads_table();

        // Insert lead
        $result = $wpdb->insert(
            $table,
            [
                'service' => $data['service'],
                'email' => $data['email'],
                'source' => $data['source'],
                'ip_address' => $data['ip'],
                'user_agent' => $data['user_agent'],
                'utm_data' => is_array($data['utm']) ? wp_json_encode($data['utm']) : null,
                'lead_meta' => !empty($data['meta']) ? wp_json_encode($data['meta']) : null,
                'status' => 'new',
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Create leads table
     */
    private static function create_leads_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'poradnik_leads';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            service varchar(255) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            source varchar(50) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            utm_data text DEFAULT NULL,
            lead_meta text DEFAULT NULL,
            status varchar(20) DEFAULT 'new',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Send admin notification email
     *
     * @param int $lead_id Lead ID
     */
    private static function send_admin_notification($lead_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'poradnik_leads';
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $lead_id));

        if (!$lead) {
            return;
        }

        $admin_email = get_option('admin_email');
        $subject = '[Poradnik.pro] Nowe zgłoszenie z Landing V5';

        $message = "Nowe zgłoszenie z Landing Page:\n\n";
        $message .= "Usługa: " . ($lead->service ?: 'Nie podano') . "\n";
        $message .= "Email: " . ($lead->email ?: 'Nie podano') . "\n";
        $message .= "Źródło: " . $lead->source . "\n";
        $message .= "Data: " . $lead->created_at . "\n";
        $message .= "IP: " . $lead->ip_address . "\n\n";

        if ($lead->utm_data) {
            $utm = json_decode($lead->utm_data, true);
            $message .= "UTM Parameters:\n";
            foreach ($utm as $key => $value) {
                if ($value) {
                    $message .= "  $key: $value\n";
                }
            }
        }

        if ($lead->lead_meta) {
            $meta = json_decode($lead->lead_meta, true);
            if (is_array($meta) && !empty($meta)) {
                $message .= "\nMeta:\n";
                foreach ($meta as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $message .= "  $key: $value\n";
                    }
                }
            }
        }

        $message .= "\n--\nPoradnik.pro Landing V5";

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Send user confirmation email
     *
     * @param string $email User email
     * @param string $service Service requested
     */
    private static function send_user_confirmation($email, $service) {
        if (empty($email)) {
            return;
        }

        $subject = 'Dziękujemy za zgłoszenie - Poradnik.pro';

        $message = "Witaj!\n\n";
        $message .= "Dziękujemy za zgłoszenie przez Poradnik.pro.\n\n";

        if ($service) {
            $message .= "Twoje zapytanie dotyczy: " . $service . "\n\n";
        }

        $message .= "Nasz zespół przeanalizuje Twoje potrzeby i wkrótce skontaktuje się z Tobą z dopasowanymi ofertami.\n\n";
        $message .= "Zazwyczaj pierwsze oferty otrzymujesz w ciągu 2-4 godzin, maksymalnie w 24 godziny.\n\n";
        $message .= "Co dalej?\n";
        $message .= "1. Przeanalizujemy Twoje zapytanie\n";
        $message .= "2. Dopasujemy najlepszych ekspertów\n";
        $message .= "3. Otrzymasz do 5 bezpłatnych ofert\n";
        $message .= "4. Porównasz i wybierzesz najlepszą\n\n";
        $message .= "Masz pytania? Skontaktuj się z nami:\n";
        $message .= "Email: kontakt@poradnik.pro\n";
        $message .= "Tel: +48 123 456 789\n\n";
        $message .= "Pozdrawiamy,\n";
        $message .= "Zespół Poradnik.pro\n\n";
        $message .= "--\n";
        $message .= "Ta wiadomość została wygenerowana automatycznie. Prosimy nie odpowiadać.";

        wp_mail($email, $subject, $message);
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Get first IP if multiple are present
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get sanitized submission metadata
     *
     * @return array
     */
    private static function get_submission_meta() {
        $meta = [
            'ab_variant' => sanitize_text_field($_POST['ab_variant'] ?? ''),
            'industry' => sanitize_text_field($_POST['industry'] ?? 'general'),
            'landing_version' => sanitize_text_field($_POST['landing_version'] ?? ''),
            'device' => sanitize_text_field($_POST['device'] ?? ''),
            'viewport' => sanitize_text_field($_POST['viewport'] ?? ''),
            'page_url' => esc_url_raw($_POST['page_url'] ?? ''),
            'referrer' => esc_url_raw($_POST['referrer'] ?? ''),
        ];

        if (!empty($_POST['utm']) && is_array($_POST['utm'])) {
            $meta['utm_source'] = sanitize_text_field($_POST['utm']['source'] ?? '');
            $meta['utm_medium'] = sanitize_text_field($_POST['utm']['medium'] ?? '');
            $meta['utm_campaign'] = sanitize_text_field($_POST['utm']['campaign'] ?? '');
        }

        return array_filter($meta, static function($value) {
            return $value !== null && $value !== '';
        });
    }
}

// Initialize
PoradnikLandingV5Handler::init();
