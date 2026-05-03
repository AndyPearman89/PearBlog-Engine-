<?php
/**
 * Poradnik.pro RPM Lead Fusion
 *
 * Revenue Per Mille (RPM) optimization integrated with lead generation metrics.
 * Tracks ad revenue, affiliate earnings, and lead value for comprehensive ROI analysis.
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RPM Lead Fusion Class
 */
class PoradnikRPMLeadFusion {
    /**
     * Revenue source types
     */
    private const REVENUE_SOURCES = [
        'adsense' => 'Google AdSense',
        'affiliate_booking' => 'Booking.com Affiliate',
        'affiliate_airbnb' => 'Airbnb Affiliate',
        'saas' => 'SaaS Referrals',
        'lead_gen' => 'Lead Generation',
        'other' => 'Other',
    ];

    /**
     * Lead value tiers
     */
    private const LEAD_VALUE_TIERS = [
        'cold' => 10,   // Basic inquiry - 10 PLN
        'warm' => 50,   // Qualified lead - 50 PLN
        'hot' => 150,   // Ready to convert - 150 PLN
        'converted' => 500, // Actual conversion - 500 PLN
    ];

    /**
     * Initialize hooks
     */
    public static function init() {
        // Admin interface
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_rpmf_get_dashboard_data', [__CLASS__, 'ajax_get_dashboard_data']);
        add_action('wp_ajax_rpmf_track_revenue', [__CLASS__, 'ajax_track_revenue']);
        add_action('wp_ajax_rpmf_get_post_performance', [__CLASS__, 'ajax_get_post_performance']);
        add_action('wp_ajax_rpmf_export_data', [__CLASS__, 'ajax_export_data']);

        // Automatic tracking hooks
        add_action('plv5_lead_submitted', [__CLASS__, 'track_lead_value'], 10, 2);
        add_action('pearblog_funnel_stage_detected', [__CLASS__, 'optimize_monetization'], 10, 2);

        // Daily aggregation cron
        add_action('rpmf_daily_aggregation', [__CLASS__, 'run_daily_aggregation']);

        if (!wp_next_scheduled('rpmf_daily_aggregation')) {
            wp_schedule_event(time(), 'daily', 'rpmf_daily_aggregation');
        }
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'poradnik-landing-leads',
            'RPM Lead Fusion',
            'RPM Lead Fusion',
            'manage_options',
            'poradnik-rpm-lead-fusion',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        if ('landing-leads_page_poradnik-rpm-lead-fusion' !== $hook) {
            return;
        }

        wp_enqueue_style('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css', [], '3.9.1');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', [], '3.9.1', true);

        wp_enqueue_script('rpmf-admin', get_template_directory_uri() . '/assets/js/rpm-lead-fusion-admin.js', ['jquery', 'chart-js'], '1.0.0', true);

        wp_localize_script('rpmf-admin', 'rpmfData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rpmf_nonce'),
        ]);
    }

    /**
     * Track revenue from any source
     */
    public static function track_revenue($amount, $source, $post_id = null, $metadata = []) {
        global $wpdb;

        $table = $wpdb->prefix . 'rpmf_revenue';
        self::create_revenue_table();

        $data = [
            'amount' => floatval($amount),
            'source' => sanitize_key($source),
            'post_id' => $post_id ? intval($post_id) : null,
            'metadata' => wp_json_encode($metadata),
            'created_at' => current_time('mysql'),
        ];

        $wpdb->insert($table, $data, ['%f', '%s', '%d', '%s', '%s']);

        // Update post meta if post_id provided
        if ($post_id) {
            $current_revenue = floatval(get_post_meta($post_id, '_rpmf_total_revenue', true));
            update_post_meta($post_id, '_rpmf_total_revenue', $current_revenue + $amount);

            // Update by source
            $source_key = '_rpmf_revenue_' . $source;
            $source_revenue = floatval(get_post_meta($post_id, $source_key, true));
            update_post_meta($post_id, $source_key, $source_revenue + $amount);
        }

        // Update daily aggregate
        $date = current_time('Y-m-d');
        $daily_key = 'rpmf_revenue_' . $date;
        $daily_revenue = floatval(get_option($daily_key, 0));
        update_option($daily_key, $daily_revenue + $amount, false);

        // Update by source aggregate
        $source_daily_key = 'rpmf_revenue_' . $source . '_' . $date;
        $source_daily = floatval(get_option($source_daily_key, 0));
        update_option($source_daily_key, $source_daily + $amount, false);

        return true;
    }

    /**
     * Track lead value
     */
    public static function track_lead_value($lead_id, $lead_data) {
        // Determine lead tier based on data
        $tier = self::calculate_lead_tier($lead_data);
        $value = self::LEAD_VALUE_TIERS[$tier];

        // Track as revenue
        $post_id = $lead_data['source_post_id'] ?? null;
        self::track_revenue($value, 'lead_gen', $post_id, [
            'lead_id' => $lead_id,
            'tier' => $tier,
        ]);

        // Update lead meta
        update_post_meta($lead_id, '_rpmf_lead_tier', $tier);
        update_post_meta($lead_id, '_rpmf_lead_value', $value);
    }

    /**
     * Calculate lead tier based on lead data
     */
    private static function calculate_lead_tier($lead_data) {
        $score = 0;

        // Email provided
        if (!empty($lead_data['email'])) {
            $score += 20;
        }

        // Phone provided
        if (!empty($lead_data['phone'])) {
            $score += 30;
        }

        // Service specified
        if (!empty($lead_data['service'])) {
            $score += 20;
        }

        // UTM source is paid (better quality)
        if (!empty($lead_data['utm']['source']) && in_array($lead_data['utm']['source'], ['google_ads', 'facebook_ads'])) {
            $score += 20;
        }

        // Message length (shows engagement)
        if (!empty($lead_data['message']) && strlen($lead_data['message']) > 50) {
            $score += 10;
        }

        // Determine tier
        if ($score >= 80) {
            return 'hot';
        } elseif ($score >= 50) {
            return 'warm';
        } else {
            return 'cold';
        }
    }

    /**
     * Calculate RPM for a post
     */
    public static function calculate_rpm($post_id, $days = 30) {
        $views = self::get_post_views($post_id, $days);

        if ($views === 0) {
            return 0;
        }

        $revenue = floatval(get_post_meta($post_id, '_rpmf_total_revenue', true));

        // RPM = (Revenue / Page Views) * 1000
        $rpm = ($revenue / $views) * 1000;

        return round($rpm, 2);
    }

    /**
     * Get post page views
     */
    private static function get_post_views($post_id, $days = 30) {
        // Try to get from GA4 first
        $views = intval(get_post_meta($post_id, '_pearblog_ga4_views_30d', true));

        if ($views > 0 && $days === 30) {
            return $views;
        }

        if ($views > 0 && $days === 7) {
            return intval(get_post_meta($post_id, '_pearblog_ga4_views_7d', true));
        }

        // Fallback to post views counter if GA4 not available
        return intval(get_post_meta($post_id, '_post_views_count', true));
    }

    /**
     * Get dashboard summary data
     */
    public static function get_dashboard_summary($days = 30) {
        global $wpdb;

        $revenue_table = $wpdb->prefix . 'rpmf_revenue';

        // Total revenue
        $total_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $revenue_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));

        // Revenue by source
        $revenue_by_source = $wpdb->get_results($wpdb->prepare(
            "SELECT source, SUM(amount) as total FROM $revenue_table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY source
            ORDER BY total DESC",
            $days
        ), ARRAY_A);

        // Lead stats
        $leads_table = $wpdb->prefix . 'poradnik_leads';
        $total_leads = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $leads_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));

        // Lead value by tier
        $lead_value_by_tier = [];
        foreach (self::LEAD_VALUE_TIERS as $tier => $value) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                WHERE meta_key = '_rpmf_lead_tier'
                AND meta_value = %s",
                $tier
            ));
            $lead_value_by_tier[$tier] = [
                'count' => intval($count),
                'value' => $value,
                'total' => intval($count) * $value,
            ];
        }

        // Calculate page views
        $total_views = self::get_total_views($days);

        // Calculate overall RPM
        $overall_rpm = $total_views > 0 ? ($total_revenue / $total_views) * 1000 : 0;

        // Get daily revenue trend
        $daily_trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $daily_revenue = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(amount) FROM $revenue_table WHERE DATE(created_at) = %s",
                $date
            ));
            $daily_trend[$date] = floatval($daily_revenue);
        }

        return [
            'total_revenue' => round(floatval($total_revenue), 2),
            'revenue_by_source' => $revenue_by_source,
            'total_leads' => intval($total_leads),
            'lead_value_by_tier' => $lead_value_by_tier,
            'total_views' => intval($total_views),
            'overall_rpm' => round($overall_rpm, 2),
            'daily_trend' => $daily_trend,
        ];
    }

    /**
     * Get total page views
     */
    private static function get_total_views($days = 30) {
        global $wpdb;

        // Try GA4 first
        if ($days === 30) {
            $total = $wpdb->get_var(
                "SELECT SUM(CAST(meta_value AS UNSIGNED))
                FROM {$wpdb->postmeta}
                WHERE meta_key = '_pearblog_ga4_views_30d'"
            );

            if ($total > 0) {
                return intval($total);
            }
        }

        // Fallback to post views counter
        return intval($wpdb->get_var(
            "SELECT SUM(CAST(meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_post_views_count'"
        ));
    }

    /**
     * Get top performing posts by RPM
     */
    public static function get_top_posts_by_rpm($limit = 10, $days = 30) {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'fields' => 'ids',
        ]);

        $results = [];

        foreach ($posts as $post_id) {
            $rpm = self::calculate_rpm($post_id, $days);

            if ($rpm > 0) {
                $results[] = [
                    'post_id' => $post_id,
                    'title' => get_the_title($post_id),
                    'rpm' => $rpm,
                    'revenue' => floatval(get_post_meta($post_id, '_rpmf_total_revenue', true)),
                    'views' => self::get_post_views($post_id, $days),
                    'url' => get_permalink($post_id),
                    'edit_url' => get_edit_post_link($post_id, 'raw'),
                ];
            }
        }

        // Sort by RPM descending
        usort($results, function($a, $b) {
            return $b['rpm'] <=> $a['rpm'];
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * Optimize monetization based on funnel stage
     */
    public static function optimize_monetization($post_id, $funnel_stage) {
        $recommendations = [
            'tofu' => [
                'strategy' => 'aggressive',
                'ad_density' => 'high',
                'affiliate_focus' => 'informational',
                'lead_priority' => 'low',
            ],
            'mofu' => [
                'strategy' => 'balanced',
                'ad_density' => 'medium',
                'affiliate_focus' => 'comparison',
                'lead_priority' => 'medium',
            ],
            'bofu' => [
                'strategy' => 'conservative',
                'ad_density' => 'low',
                'affiliate_focus' => 'direct',
                'lead_priority' => 'high',
            ],
        ];

        $config = $recommendations[$funnel_stage] ?? $recommendations['mofu'];

        update_post_meta($post_id, '_rpmf_monetization_strategy', $config['strategy']);
        update_post_meta($post_id, '_rpmf_lead_priority', $config['lead_priority']);

        return $config;
    }

    /**
     * Run daily aggregation
     */
    public static function run_daily_aggregation() {
        global $wpdb;

        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Aggregate revenue
        $revenue_table = $wpdb->prefix . 'rpmf_revenue';
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $revenue_table WHERE DATE(created_at) = %s",
            $yesterday
        ));

        update_option('rpmf_revenue_' . $yesterday, floatval($total), false);

        // Aggregate by source
        $by_source = $wpdb->get_results($wpdb->prepare(
            "SELECT source, SUM(amount) as total FROM $revenue_table
            WHERE DATE(created_at) = %s
            GROUP BY source",
            $yesterday
        ), ARRAY_A);

        foreach ($by_source as $row) {
            update_option('rpmf_revenue_' . $row['source'] . '_' . $yesterday, floatval($row['total']), false);
        }
    }

    /**
     * Create revenue tracking table
     */
    private static function create_revenue_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'rpmf_revenue';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            source varchar(50) NOT NULL,
            post_id bigint(20) unsigned DEFAULT NULL,
            metadata text DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY source (source),
            KEY post_id (post_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * AJAX: Get dashboard data
     */
    public static function ajax_get_dashboard_data() {
        check_ajax_referer('rpmf_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $days = intval($_POST['days'] ?? 30);
        $summary = self::get_dashboard_summary($days);
        $top_posts = self::get_top_posts_by_rpm(10, $days);

        wp_send_json_success([
            'summary' => $summary,
            'top_posts' => $top_posts,
        ]);
    }

    /**
     * AJAX: Track revenue manually
     */
    public static function ajax_track_revenue() {
        check_ajax_referer('rpmf_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $amount = floatval($_POST['amount'] ?? 0);
        $source = sanitize_key($_POST['source'] ?? 'other');
        $post_id = intval($_POST['post_id'] ?? 0);
        $note = sanitize_text_field($_POST['note'] ?? '');

        if ($amount <= 0) {
            wp_send_json_error(['message' => 'Invalid amount']);
        }

        $metadata = ['note' => $note, 'manual' => true];
        self::track_revenue($amount, $source, $post_id ?: null, $metadata);

        wp_send_json_success(['message' => 'Revenue tracked successfully']);
    }

    /**
     * AJAX: Get post performance
     */
    public static function ajax_get_post_performance() {
        check_ajax_referer('rpmf_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $days = intval($_POST['days'] ?? 30);

        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }

        $rpm = self::calculate_rpm($post_id, $days);
        $revenue = floatval(get_post_meta($post_id, '_rpmf_total_revenue', true));
        $views = self::get_post_views($post_id, $days);

        wp_send_json_success([
            'rpm' => $rpm,
            'revenue' => $revenue,
            'views' => $views,
        ]);
    }

    /**
     * AJAX: Export data
     */
    public static function ajax_export_data() {
        check_ajax_referer('rpmf_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $days = intval($_POST['days'] ?? 30);
        $summary = self::get_dashboard_summary($days);
        $top_posts = self::get_top_posts_by_rpm(100, $days);

        // Generate CSV
        $filename = 'rpm-lead-fusion-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        fputcsv($output, ['Post ID', 'Title', 'RPM', 'Revenue', 'Views', 'URL']);

        // Data
        foreach ($top_posts as $post) {
            fputcsv($output, [
                $post['post_id'],
                $post['title'],
                $post['rpm'],
                $post['revenue'],
                $post['views'],
                $post['url'],
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Render admin page
     */
    public static function render_admin_page() {
        $summary = self::get_dashboard_summary(30);
        $top_posts = self::get_top_posts_by_rpm(10, 30);

        require_once get_template_directory() . '/templates/admin/rpm-lead-fusion.php';
    }
}

// Initialize
PoradnikRPMLeadFusion::init();
