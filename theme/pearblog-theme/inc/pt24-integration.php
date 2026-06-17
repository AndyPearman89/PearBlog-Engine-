<?php
/**
 * PT24.PRO Integration System
 *
 * Handles cross-site conversion funnel between Poradnik.pro and PT24.pro
 * Provides CTA generation, URL mapping, and analytics tracking
 *
 * @package PearBlog
 * @subpackage PT24Integration
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PT24 Integration Class
 */
class PearBlog_PT24_Integration {

    /**
     * Base URL for PT24.pro
     */
    const PT24_BASE_URL = 'https://pt24.pro';

    /**
     * Category mapping: Poradnik topic → PT24 service
     */
    private static $category_mapping = [
        'pompa-ciepla' => 'pompa-ciepla',
        'pompa ciepla' => 'pompa-ciepla',
        'remont' => 'remont',
        'remont-lazienki' => 'remont',
        'remont łazienki' => 'remont',
        'kredyt-hipoteczny' => 'kredyty',
        'kredyt' => 'kredyty',
        'fotowoltaika' => 'fotowoltaika',
        'panele-fotowoltaiczne' => 'fotowoltaika',
        'instalacja-elektryczna' => 'elektryk',
        'elektryk' => 'elektryk',
        'hydraulik' => 'hydraulika',
        'instalacje-sanitarne' => 'hydraulika',
        'ogrzewanie' => 'ogrzewanie',
        'kociol-gazowy' => 'ogrzewanie',
        'klimatyzacja' => 'klimatyzacja',
        'montaz-klimatyzacji' => 'klimatyzacja',
    ];

    /**
     * Major Polish cities for geo-targeting
     */
    private static $major_cities = [
        'warszawa',
        'krakow',
        'gdansk',
        'wroclaw',
        'poznan',
        'lodz',
        'szczecin',
        'bydgoszcz',
        'lublin',
        'katowice',
    ];

    /**
     * Initialize hooks and filters
     */
    public static function init() {
        // Register shortcode
        add_shortcode('pt24_cta', [__CLASS__, 'shortcode_handler']);

        // Add automatic CTAs to content
        add_filter('the_content', [__CLASS__, 'inject_auto_ctas'], 20);

        // Register AJAX handlers
        add_action('wp_ajax_pt24_track_click', [__CLASS__, 'ajax_track_click']);
        add_action('wp_ajax_nopriv_pt24_track_click', [__CLASS__, 'ajax_track_click']);

        // Register lead submission handler
        add_action('wp_ajax_pt24_submit_lead', [__CLASS__, 'ajax_submit_lead']);
        add_action('wp_ajax_nopriv_pt24_submit_lead', [__CLASS__, 'ajax_submit_lead']);
    }

    /**
     * Generate PT24 URL with city and service
     *
     * @param string $service Service/topic slug
     * @param string $city City name (optional)
     * @return string Complete PT24 URL
     */
    public static function generate_url($service, $city = '') {
        // Sanitize inputs
        $service = sanitize_title($service);
        $city = $city ? sanitize_title($city) : '';

        // Map service category
        $mapped_service = self::map_category($service);

        // Detect or default city
        if (empty($city)) {
            $city = self::detect_user_city();
        }

        // Build URL: https://pt24.pro/{city}/{service}?ref=poradnik
        $url = self::PT24_BASE_URL . '/' . $city . '/' . $mapped_service . '?ref=poradnik';

        return esc_url($url);
    }

    /**
     * Map Poradnik category to PT24 service
     *
     * @param string $topic Topic from Poradnik
     * @return string Mapped service slug
     */
    private static function map_category($topic) {
        $topic_clean = strtolower(trim($topic));

        // Direct mapping
        if (isset(self::$category_mapping[$topic_clean])) {
            return self::$category_mapping[$topic_clean];
        }

        // Fuzzy matching
        foreach (self::$category_mapping as $key => $value) {
            if (strpos($topic_clean, $key) !== false) {
                return $value;
            }
        }

        // Default fallback
        return sanitize_title($topic);
    }

    /**
     * Detect user's city from IP or context
     *
     * @return string City slug
     */
    private static function detect_user_city() {
        // Try to get from cookie
        if (isset($_COOKIE['pt24_user_city'])) {
            return sanitize_title($_COOKIE['pt24_user_city']);
        }

        // Try to get from post meta (if article mentions specific city)
        if (is_singular('post')) {
            $post_cities = get_post_meta(get_the_ID(), 'pt24_target_cities', true);
            if (!empty($post_cities) && is_array($post_cities)) {
                return sanitize_title($post_cities[0]);
            }
        }

        // Default to Warszawa
        return 'warszawa';
    }

    /**
     * Generate CTA block HTML
     *
     * @param array $args CTA arguments
     * @return string HTML output
     */
    public static function generate_cta_html($args = []) {
        $defaults = [
            'service' => '',
            'city' => 'auto',
            'style' => 'hybrid', // 'hybrid', 'card', 'banner', 'inline'
            'title' => 'Sprawdź ceny i dostępne firmy w Twojej okolicy',
            'cta_text' => 'Zobacz oferty',
            'post_id' => get_the_ID(),
        ];

        $args = wp_parse_args($args, $defaults);

        // Auto-detect service from post if not provided
        if (empty($args['service']) && is_singular('post')) {
            $args['service'] = self::extract_service_from_post(get_the_ID());
        }

        // Generate PT24 URL
        $city = ($args['city'] === 'auto') ? '' : $args['city'];
        $pt24_url = self::generate_url($args['service'], $city);

        // Get template file
        $template_file = locate_template('template-parts/pt24-cta-block.php');

        if (!$template_file) {
            return '<!-- PT24 CTA: Template not found -->';
        }

        // Start output buffering
        ob_start();

        // Make args available to template
        extract($args);

        // Include template
        include $template_file;

        // Return buffered content
        return ob_get_clean();
    }

    /**
     * Extract service/topic from post content
     *
     * @param int $post_id Post ID
     * @return string Service slug
     */
    private static function extract_service_from_post($post_id) {
        // Try post meta first
        $service_meta = get_post_meta($post_id, 'pt24_service_category', true);
        if (!empty($service_meta)) {
            return $service_meta;
        }

        // Try to extract from post slug
        $post = get_post($post_id);
        if ($post) {
            return sanitize_title($post->post_name);
        }

        return 'uslugi';
    }

    /**
     * Shortcode handler: [pt24_cta service="pompa-ciepla" city="krakow"]
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function shortcode_handler($atts) {
        $atts = shortcode_atts([
            'service' => '',
            'city' => 'auto',
            'style' => 'hybrid',
            'title' => '',
            'cta_text' => '',
        ], $atts);

        return self::generate_cta_html($atts);
    }

    /**
     * Auto-inject CTAs into post content
     *
     * @param string $content Post content
     * @return string Modified content with CTAs
     */
    public static function inject_auto_ctas($content) {
        // Only for single posts
        if (!is_singular('post') || !in_the_loop()) {
            return $content;
        }

        // Check if integration is enabled
        $enabled = get_option('pt24_integration_enabled', true);
        if (!$enabled) {
            return $content;
        }

        // Check if post already has shortcode
        if (has_shortcode($content, 'pt24_cta')) {
            return $content;
        }

        // Split content into paragraphs
        $paragraphs = explode('</p>', $content);
        $total_paragraphs = count($paragraphs);

        // Calculate CTA positions
        $positions = [];
        if ($total_paragraphs >= 3) {
            $positions[] = ceil($total_paragraphs * 0.33); // After 33%
        }
        if ($total_paragraphs >= 6) {
            $positions[] = ceil($total_paragraphs * 0.66); // After 66%
        }

        // Generate CTA HTML
        $cta_html = self::generate_cta_html(['style' => 'inline']);

        // Insert CTAs
        foreach (array_reverse($positions) as $position) {
            if (isset($paragraphs[$position])) {
                $paragraphs[$position] .= '</p>' . $cta_html;
            }
        }

        // Add final CTA at end
        $final_cta = self::generate_cta_html(['style' => 'card']);
        $content = implode('</p>', $paragraphs) . $final_cta;

        return $content;
    }

    /**
     * AJAX handler for click tracking
     */
    public static function ajax_track_click() {
        // Verify nonce
        check_ajax_referer('pt24_tracking', 'nonce');

        // Get data
        $service = sanitize_text_field($_POST['service'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $post_id = absint($_POST['post_id'] ?? 0);
        $url = esc_url_raw($_POST['url'] ?? '');

        // Store click data
        $click_data = [
            'timestamp' => current_time('mysql'),
            'service' => $service,
            'city' => $city,
            'post_id' => $post_id,
            'url' => $url,
            'user_ip' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        // Save to database (custom table or post meta)
        global $wpdb;
        $table_name = $wpdb->prefix . 'pt24_clicks';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $wpdb->insert($table_name, $click_data);
        } else {
            // Fallback to post meta
            $clicks = get_post_meta($post_id, '_pt24_clicks', true) ?: [];
            $clicks[] = $click_data;
            update_post_meta($post_id, '_pt24_clicks', $clicks);
        }

        wp_send_json_success([
            'message' => 'Click tracked successfully',
            'data' => $click_data,
        ]);
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        return sanitize_text_field($ip);
    }

    /**
     * Create database table for click tracking
     */
    public static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pt24_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            service varchar(100) NOT NULL,
            city varchar(50) NOT NULL,
            post_id bigint(20) NOT NULL,
            url text NOT NULL,
            user_ip varchar(45) NOT NULL,
            user_agent text NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY service (service),
            KEY city (city),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get click statistics for a post
     *
     * @param int $post_id Post ID
     * @return array Statistics
     */
    public static function get_post_stats($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pt24_clicks';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Fallback to post meta
            $clicks = get_post_meta($post_id, '_pt24_clicks', true) ?: [];
            return [
                'total_clicks' => count($clicks),
                'clicks' => $clicks,
            ];
        }

        $stats = [];
        $stats['total_clicks'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
            $post_id
        ));

        $stats['clicks_by_city'] = $wpdb->get_results($wpdb->prepare(
            "SELECT city, COUNT(*) as count FROM $table_name WHERE post_id = %d GROUP BY city ORDER BY count DESC",
            $post_id
        ), ARRAY_A);

        $stats['clicks_by_service'] = $wpdb->get_results($wpdb->prepare(
            "SELECT service, COUNT(*) as count FROM $table_name WHERE post_id = %d GROUP BY service ORDER BY count DESC",
            $post_id
        ), ARRAY_A);

        return $stats;
    }

    /**
     * AJAX handler for lead submission from landing page
     */
    public static function ajax_submit_lead() {
        // Verify nonce
        if (!isset($_POST['pt24_nonce']) || !wp_verify_nonce($_POST['pt24_nonce'], 'pt24_lead_submit')) {
            wp_send_json_error([
                'message' => 'Błąd weryfikacji zabezpieczeń. Odśwież stronę i spróbuj ponownie.',
            ]);
        }

        // Get and sanitize form data
        $service = sanitize_text_field($_POST['service'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $service_need = sanitize_textarea_field($_POST['service_need'] ?? '');
        $city_input = sanitize_text_field($_POST['city_input'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $consent = isset($_POST['consent']) ? 1 : 0;
        $source_url = esc_url_raw($_POST['source_url'] ?? '');

        // Validate required fields
        if (empty($service_need) || empty($name) || empty($phone) || empty($email) || !$consent) {
            wp_send_json_error([
                'message' => 'Proszę wypełnić wszystkie wymagane pola.',
            ]);
        }

        // Validate email
        if (!is_email($email)) {
            wp_send_json_error([
                'message' => 'Proszę podać poprawny adres email.',
            ]);
        }

        // Validate phone (Polish format)
        $phone_clean = preg_replace('/[^0-9+]/', '', $phone);
        if (strlen($phone_clean) < 9) {
            wp_send_json_error([
                'message' => 'Proszę podać poprawny numer telefonu.',
            ]);
        }

        // Prepare lead data
        $lead_data = [
            'timestamp' => current_time('mysql'),
            'service' => $service,
            'city' => $city,
            'service_need' => $service_need,
            'city_input' => $city_input,
            'name' => $name,
            'phone' => $phone_clean,
            'email' => $email,
            'consent' => $consent,
            'source_url' => $source_url,
            'user_ip' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'pt24_leads';

        // Create table if it doesn't exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            self::create_leads_table();
        }

        // Insert lead
        $result = $wpdb->insert($table_name, $lead_data);

        if ($result === false) {
            wp_send_json_error([
                'message' => 'Błąd zapisu danych. Spróbuj ponownie lub skontaktuj się z nami telefonicznie.',
            ]);
        }

        $lead_id = $wpdb->insert_id;

        // Send email notification to admin
        self::send_lead_notification($lead_id, $lead_data);

        // Send confirmation email to user
        self::send_user_confirmation($email, $name);

        // Track conversion
        do_action('pt24_lead_submitted', $lead_id, $lead_data);

        wp_send_json_success([
            'message' => 'Dziękujemy! Twoje zgłoszenie zostało wysłane.',
            'lead_id' => $lead_id,
            'redirect_url' => home_url('/dziekujemy-pt24'),
        ]);
    }

    /**
     * Create leads table
     */
    private static function create_leads_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pt24_leads';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            service varchar(100) NOT NULL,
            city varchar(50) NOT NULL,
            service_need text NOT NULL,
            city_input varchar(100) NOT NULL,
            name varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            email varchar(100) NOT NULL,
            consent tinyint(1) NOT NULL DEFAULT 0,
            source_url text NOT NULL,
            user_ip varchar(45) NOT NULL,
            user_agent text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'new',
            PRIMARY KEY  (id),
            KEY service (service),
            KEY city (city),
            KEY timestamp (timestamp),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Send lead notification to admin
     */
    private static function send_lead_notification($lead_id, $lead_data) {
        $admin_email = get_option('admin_email');
        $subject = '[PT24 Lead] Nowe zgłoszenie: ' . $lead_data['service'] . ' - ' . $lead_data['city_input'];

        $message = "Otrzymano nowe zgłoszenie z PT24.PRO:\n\n";
        $message .= "ID zgłoszenia: #" . $lead_id . "\n";
        $message .= "Data: " . $lead_data['timestamp'] . "\n\n";
        $message .= "=== DANE KONTAKTOWE ===\n";
        $message .= "Imię: " . $lead_data['name'] . "\n";
        $message .= "Email: " . $lead_data['email'] . "\n";
        $message .= "Telefon: " . $lead_data['phone'] . "\n\n";
        $message .= "=== SZCZEGÓŁY ZAPYTANIA ===\n";
        $message .= "Usługa: " . $lead_data['service'] . "\n";
        $message .= "Miasto: " . $lead_data['city_input'] . "\n";
        $message .= "Opis potrzeby:\n" . $lead_data['service_need'] . "\n\n";
        $message .= "=== ŹRÓDŁO ===\n";
        $message .= "URL źródłowy: " . $lead_data['source_url'] . "\n";
        $message .= "IP: " . $lead_data['user_ip'] . "\n\n";
        $message .= "Zarządzaj zgłoszeniami: " . admin_url('admin.php?page=pt24-leads') . "\n";

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Send confirmation email to user
     */
    private static function send_user_confirmation($email, $name) {
        $subject = 'Potwierdzenie otrzymania zgłoszenia - PT24.PRO';

        $message = "Witaj " . $name . ",\n\n";
        $message .= "Dziękujemy za wysłanie zapytania przez PT24.PRO!\n\n";
        $message .= "Otrzymaliśmy Twoje zgłoszenie i przekażemy je sprawdzonym firmom w Twojej okolicy.\n";
        $message .= "W ciągu najbliższych 24 godzin otrzymasz oferty bezpośrednio od wykonawców.\n\n";
        $message .= "Oferty otrzymasz:\n";
        $message .= "- Na adres email: " . $email . "\n";
        $message .= "- Telefonicznie (jeśli podano numer)\n\n";
        $message .= "Pozdrawiamy,\n";
        $message .= "Zespół PT24.PRO\n\n";
        $message .= "---\n";
        $message .= "Nie odpowiadaj na tę wiadomość. W razie pytań skontaktuj się z nami:\n";
        $message .= "Email: kontakt@pt24.pro\n";
        $message .= "Tel: +48 XXX XXX XXX\n";

        wp_mail($email, $subject, $message);
    }
}

// Initialize on WordPress init
add_action('init', ['PearBlog_PT24_Integration', 'init']);

// Create tables on theme activation
add_action('after_switch_theme', ['PearBlog_PT24_Integration', 'create_tables']);

if (!function_exists('pb_pt24_get_ranking_companies')) {
    /**
     * Build default ranking card content for PT24 landing templates.
     * Data is curated template/demo content used as a safe fallback UI dataset.
     *
     * @param string $service_display Service name for display.
     * @param string $city_display City name for display.
     * @return array<int, array<string, string>>
     */
    function pb_pt24_get_ranking_companies($service_display, $city_display) {
        return array(
            array(
                'name' => sprintf('%s Premium %s', $service_display, $city_display),
                'rating' => '4.9',
                'reviews' => '127',
                'availability' => 'Dostępny dziś',
                'response' => 'Odpowiedź do 1h',
                'badge' => 'TOP WYBÓR',
                'badge_class' => 'pt24-badge--top',
            ),
            array(
                'name' => sprintf('%s Expert Team', $service_display),
                'rating' => '4.8',
                'reviews' => '104',
                'availability' => 'Dostępny jutro',
                'response' => 'Odpowiedź do 3h',
                'badge' => 'POLECANY',
                'badge_class' => 'pt24-badge--recommended',
            ),
            array(
                'name' => sprintf('%s 24/7 %s', $service_display, $city_display),
                'rating' => '4.7',
                'reviews' => '89',
                'availability' => 'Dostępny w tym tygodniu',
                'response' => 'Odpowiedź do 6h',
                'badge' => 'ZWERYFIKOWANY',
                'badge_class' => 'pt24-badge--verified',
            ),
        );
    }
}
