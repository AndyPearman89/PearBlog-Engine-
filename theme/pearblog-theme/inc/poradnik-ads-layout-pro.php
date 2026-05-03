<?php
/**
 * Poradnik.pro Ads Layout Pro
 *
 * Advanced ad placement system with intelligent positioning, A/B testing,
 * and performance optimization for maximum CTR and RPM.
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ads Layout Pro Class
 */
class PoradnikAdsLayoutPro {
    /**
     * Ad placement strategies
     */
    private const STRATEGIES = [
        'aggressive' => [
            'paragraphs_between' => 2,
            'positions' => ['after_intro', 'mid_content', 'before_conclusion', 'sidebar'],
            'max_ads' => 6,
        ],
        'balanced' => [
            'paragraphs_between' => 4,
            'positions' => ['after_intro', 'mid_content', 'sidebar'],
            'max_ads' => 3,
        ],
        'conservative' => [
            'paragraphs_between' => 6,
            'positions' => ['mid_content'],
            'max_ads' => 2,
        ],
    ];

    /**
     * Initialize hooks
     */
    public static function init() {
        // Admin interface
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_alp_save_settings', [__CLASS__, 'ajax_save_settings']);
        add_action('wp_ajax_alp_get_performance', [__CLASS__, 'ajax_get_performance']);
        add_action('wp_ajax_alp_create_ab_test', [__CLASS__, 'ajax_create_ab_test']);

        // Content filters
        add_filter('the_content', [__CLASS__, 'inject_ads'], 20);

        // Tracking
        add_action('wp_footer', [__CLASS__, 'add_tracking_script']);
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'poradnik-landing-leads',
            'Ads Layout Pro',
            'Ads Layout Pro',
            'manage_options',
            'poradnik-ads-layout-pro',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        if ('landing-leads_page_poradnik-ads-layout-pro' !== $hook) {
            return;
        }

        wp_enqueue_style('alp-admin', get_template_directory_uri() . '/assets/css/ads-layout-pro-admin.css', [], '1.0.0');
        wp_enqueue_script('alp-admin', get_template_directory_uri() . '/assets/js/ads-layout-pro-admin.js', ['jquery'], '1.0.0', true);

        wp_localize_script('alp-admin', 'alpData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alp_nonce'),
        ]);
    }

    /**
     * Inject ads into content
     */
    public static function inject_ads($content) {
        if (is_admin() || is_feed() || !is_singular('post')) {
            return $content;
        }

        // Check if ads are enabled
        $enabled = get_option('alp_enabled', true);
        if (!$enabled) {
            return $content;
        }

        // Get strategy
        $strategy_name = get_option('alp_strategy', 'balanced');
        $strategy = self::STRATEGIES[$strategy_name] ?? self::STRATEGIES['balanced'];

        // Get ad format
        $ad_format = get_option('alp_ad_format', 'adsense');

        // Split content into paragraphs
        $paragraphs = preg_split('/(<p[^>]*>.*?<\/p>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (count($paragraphs) < 3) {
            return $content; // Too short for ad injection
        }

        $new_content = '';
        $paragraph_count = 0;
        $ad_count = 0;
        $max_ads = $strategy['max_ads'];

        foreach ($paragraphs as $paragraph) {
            $new_content .= $paragraph;

            // Check if this is an actual paragraph
            if (preg_match('/<p[^>]*>/i', $paragraph)) {
                $paragraph_count++;

                // Inject ads at strategic positions
                if ($ad_count < $max_ads && $paragraph_count % $strategy['paragraphs_between'] === 0) {
                    $position = self::calculate_position($paragraph_count, count($paragraphs), $strategy['positions']);
                    $ad_html = self::get_ad_html($ad_format, $position, $ad_count + 1);

                    if ($ad_html) {
                        $new_content .= "\n\n" . $ad_html . "\n\n";
                        $ad_count++;

                        // Track ad impression
                        self::track_ad_event(get_the_ID(), 'impression', $position, $ad_count);
                    }
                }
            }
        }

        return $new_content;
    }

    /**
     * Calculate optimal ad position based on content structure
     */
    private static function calculate_position($current_paragraph, $total_paragraphs, $allowed_positions) {
        $progress = $current_paragraph / $total_paragraphs;

        if ($progress < 0.15 && in_array('after_intro', $allowed_positions)) {
            return 'after_intro';
        } elseif ($progress >= 0.15 && $progress < 0.45 && in_array('early_content', $allowed_positions)) {
            return 'early_content';
        } elseif ($progress >= 0.45 && $progress < 0.65 && in_array('mid_content', $allowed_positions)) {
            return 'mid_content';
        } elseif ($progress >= 0.65 && $progress < 0.85 && in_array('late_content', $allowed_positions)) {
            return 'late_content';
        } elseif ($progress >= 0.85 && in_array('before_conclusion', $allowed_positions)) {
            return 'before_conclusion';
        }

        return 'mid_content'; // Fallback
    }

    /**
     * Get ad HTML based on format
     */
    private static function get_ad_html($format, $position, $ad_number) {
        $post_id = get_the_ID();

        switch ($format) {
            case 'adsense':
                return self::get_adsense_ad($position, $ad_number);

            case 'custom':
                return self::get_custom_ad($position, $ad_number);

            case 'ab_test':
                return self::get_ab_test_ad($post_id, $position, $ad_number);

            default:
                return '';
        }
    }

    /**
     * Get AdSense ad unit
     */
    private static function get_adsense_ad($position, $ad_number) {
        $publisher_id = get_option('alp_adsense_publisher_id', get_option('pearblog_adsense_publisher_id', ''));
        $slot = get_option('alp_adsense_slot_' . $position, get_option('pearblog_adsense_slot_content', ''));

        if (empty($publisher_id)) {
            return '';
        }

        $format = get_option('alp_ad_format_type', 'auto');
        $layout = get_option('alp_ad_layout', 'in-article');

        ob_start();
        ?>
        <div class="alp-ad alp-ad--adsense alp-ad--position-<?php echo esc_attr($position); ?>"
             data-position="<?php echo esc_attr($position); ?>"
             data-ad-number="<?php echo esc_attr($ad_number); ?>">
            <div class="alp-ad-label"><?php echo esc_html(get_option('alp_ad_label', 'Advertisement')); ?></div>
            <ins class="adsbygoogle"
                 style="display:block;text-align:center"
                 data-ad-layout="<?php echo esc_attr($layout); ?>"
                 data-ad-format="<?php echo esc_attr($format); ?>"
                 data-ad-client="<?php echo esc_attr($publisher_id); ?>"
                 data-ad-slot="<?php echo esc_attr($slot); ?>"
                 data-full-width-responsive="true"></ins>
            <script>
                (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get custom ad HTML
     */
    private static function get_custom_ad($position, $ad_number) {
        $custom_html = get_option('alp_custom_ad_' . $position, '');

        if (empty($custom_html)) {
            return '';
        }

        ob_start();
        ?>
        <div class="alp-ad alp-ad--custom alp-ad--position-<?php echo esc_attr($position); ?>"
             data-position="<?php echo esc_attr($position); ?>"
             data-ad-number="<?php echo esc_attr($ad_number); ?>">
            <?php echo wp_kses_post($custom_html); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get A/B test ad variant
     */
    private static function get_ab_test_ad($post_id, $position, $ad_number) {
        $test_id = get_option('alp_ab_test_active_' . $position, 0);

        if (!$test_id) {
            return self::get_adsense_ad($position, $ad_number);
        }

        // Determine which variant to show (50/50 split)
        $visitor_id = self::get_visitor_id();
        $variant = (crc32($visitor_id . $test_id) % 2 === 0) ? 'a' : 'b';

        // Track variant impression
        self::track_ab_test_event($test_id, $variant, 'impression');

        // Get variant ad HTML
        if ($variant === 'a') {
            $ad_html = get_post_meta($test_id, '_alp_variant_a_html', true);
        } else {
            $ad_html = get_post_meta($test_id, '_alp_variant_b_html', true);
        }

        ob_start();
        ?>
        <div class="alp-ad alp-ad--ab-test alp-ad--position-<?php echo esc_attr($position); ?>"
             data-position="<?php echo esc_attr($position); ?>"
             data-ad-number="<?php echo esc_attr($ad_number); ?>"
             data-test-id="<?php echo esc_attr($test_id); ?>"
             data-variant="<?php echo esc_attr($variant); ?>">
            <?php echo wp_kses_post($ad_html); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Track ad event (impression/click)
     */
    private static function track_ad_event($post_id, $event_type, $position, $ad_number) {
        $date = current_time('Y-m-d');
        $meta_key = "_alp_{$event_type}_{$position}_{$date}";

        $current_count = get_post_meta($post_id, $meta_key, true);
        $current_count = intval($current_count);

        update_post_meta($post_id, $meta_key, $current_count + 1);

        // Also track globally
        $global_key = "alp_{$event_type}_{$date}";
        $global_count = get_option($global_key, 0);
        update_option($global_key, intval($global_count) + 1, false);
    }

    /**
     * Track A/B test event
     */
    private static function track_ab_test_event($test_id, $variant, $event_type) {
        $meta_key = "_alp_ab_test_{$variant}_{$event_type}";
        $current_count = get_post_meta($test_id, $meta_key, true);
        update_post_meta($test_id, $meta_key, intval($current_count) + 1);
    }

    /**
     * Get or create visitor ID for A/B testing
     */
    private static function get_visitor_id() {
        if (isset($_COOKIE['alp_visitor_id'])) {
            return sanitize_text_field($_COOKIE['alp_visitor_id']);
        }

        $visitor_id = wp_generate_password(32, false);
        setcookie('alp_visitor_id', $visitor_id, time() + (86400 * 365), '/');

        return $visitor_id;
    }

    /**
     * Add tracking script to footer
     */
    public static function add_tracking_script() {
        if (!is_singular('post')) {
            return;
        }

        $tracking_enabled = get_option('alp_tracking_enabled', true);
        if (!$tracking_enabled) {
            return;
        }

        ?>
        <script>
        (function() {
            'use strict';

            // Track ad clicks
            document.addEventListener('click', function(e) {
                const adElement = e.target.closest('.alp-ad');
                if (!adElement) return;

                const position = adElement.dataset.position;
                const adNumber = adElement.dataset.adNumber;
                const testId = adElement.dataset.testId;
                const variant = adElement.dataset.variant;

                // Send tracking data via beacon API
                if (navigator.sendBeacon) {
                    const data = new FormData();
                    data.append('action', 'alp_track_click');
                    data.append('post_id', <?php echo get_the_ID(); ?>);
                    data.append('position', position);
                    data.append('ad_number', adNumber);

                    if (testId) {
                        data.append('test_id', testId);
                        data.append('variant', variant);
                    }

                    navigator.sendBeacon('<?php echo admin_url('admin-ajax.php'); ?>', data);
                }
            });

            // Track viewability (50% in view for 1 second)
            const observeAdViewability = () => {
                const ads = document.querySelectorAll('.alp-ad');
                const viewabilityThreshold = 0.5;
                const viewabilityTime = 1000; // 1 second

                const viewedAds = new Set();
                const viewTimers = new Map();

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        const ad = entry.target;
                        const adKey = ad.dataset.position + '-' + ad.dataset.adNumber;

                        if (entry.intersectionRatio >= viewabilityThreshold) {
                            if (!viewTimers.has(adKey)) {
                                viewTimers.set(adKey, setTimeout(() => {
                                    if (!viewedAds.has(adKey)) {
                                        viewedAds.add(adKey);

                                        // Track viewable impression
                                        const data = new FormData();
                                        data.append('action', 'alp_track_viewable');
                                        data.append('post_id', <?php echo get_the_ID(); ?>);
                                        data.append('position', ad.dataset.position);
                                        data.append('ad_number', ad.dataset.adNumber);

                                        if (navigator.sendBeacon) {
                                            navigator.sendBeacon('<?php echo admin_url('admin-ajax.php'); ?>', data);
                                        }
                                    }
                                }, viewabilityTime));
                            }
                        } else {
                            // Clear timer if ad is no longer in view
                            const timer = viewTimers.get(adKey);
                            if (timer) {
                                clearTimeout(timer);
                                viewTimers.delete(adKey);
                            }
                        }
                    });
                }, {
                    threshold: [0, 0.25, 0.5, 0.75, 1]
                });

                ads.forEach(ad => observer.observe(ad));
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', observeAdViewability);
            } else {
                observeAdViewability();
            }
        })();
        </script>
        <?php
    }

    /**
     * AJAX: Save settings
     */
    public static function ajax_save_settings() {
        check_ajax_referer('alp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $settings = [
            'alp_enabled' => isset($_POST['enabled']) ? (bool) $_POST['enabled'] : false,
            'alp_strategy' => sanitize_text_field($_POST['strategy'] ?? 'balanced'),
            'alp_ad_format' => sanitize_text_field($_POST['ad_format'] ?? 'adsense'),
            'alp_ad_format_type' => sanitize_text_field($_POST['ad_format_type'] ?? 'auto'),
            'alp_ad_layout' => sanitize_text_field($_POST['ad_layout'] ?? 'in-article'),
            'alp_ad_label' => sanitize_text_field($_POST['ad_label'] ?? 'Advertisement'),
            'alp_tracking_enabled' => isset($_POST['tracking_enabled']) ? (bool) $_POST['tracking_enabled'] : true,
        ];

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        wp_send_json_success(['message' => 'Settings saved successfully']);
    }

    /**
     * AJAX: Get performance data
     */
    public static function ajax_get_performance() {
        check_ajax_referer('alp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $days = intval($_POST['days'] ?? 7);
        $performance = self::get_performance_data($days);

        wp_send_json_success($performance);
    }

    /**
     * Get performance data
     */
    private static function get_performance_data($days = 7) {
        global $wpdb;

        $data = [
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_viewable' => 0,
            'ctr' => 0,
            'viewability' => 0,
            'by_position' => [],
            'by_date' => [],
        ];

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));

            $impressions = intval(get_option("alp_impression_{$date}", 0));
            $clicks = intval(get_option("alp_click_{$date}", 0));
            $viewable = intval(get_option("alp_viewable_{$date}", 0));

            $data['total_impressions'] += $impressions;
            $data['total_clicks'] += $clicks;
            $data['total_viewable'] += $viewable;

            $data['by_date'][$date] = [
                'impressions' => $impressions,
                'clicks' => $clicks,
                'viewable' => $viewable,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
            ];
        }

        if ($data['total_impressions'] > 0) {
            $data['ctr'] = round(($data['total_clicks'] / $data['total_impressions']) * 100, 2);
            $data['viewability'] = round(($data['total_viewable'] / $data['total_impressions']) * 100, 2);
        }

        return $data;
    }

    /**
     * AJAX: Create A/B test
     */
    public static function ajax_create_ab_test() {
        check_ajax_referer('alp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $name = sanitize_text_field($_POST['name'] ?? '');
        $position = sanitize_text_field($_POST['position'] ?? '');
        $variant_a = wp_kses_post($_POST['variant_a'] ?? '');
        $variant_b = wp_kses_post($_POST['variant_b'] ?? '');

        if (empty($name) || empty($position) || empty($variant_a) || empty($variant_b)) {
            wp_send_json_error(['message' => 'All fields are required']);
        }

        // Create test post
        $test_id = wp_insert_post([
            'post_type' => 'alp_ab_test',
            'post_title' => $name,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($test_id)) {
            wp_send_json_error(['message' => 'Failed to create test']);
        }

        update_post_meta($test_id, '_alp_position', $position);
        update_post_meta($test_id, '_alp_variant_a_html', $variant_a);
        update_post_meta($test_id, '_alp_variant_b_html', $variant_b);
        update_post_meta($test_id, '_alp_status', 'active');
        update_post_meta($test_id, '_alp_created_at', current_time('mysql'));

        // Set as active test for this position
        update_option('alp_ab_test_active_' . $position, $test_id);

        wp_send_json_success([
            'message' => 'A/B test created successfully',
            'test_id' => $test_id,
        ]);
    }

    /**
     * Render admin page
     */
    public static function render_admin_page() {
        $strategy = get_option('alp_strategy', 'balanced');
        $ad_format = get_option('alp_ad_format', 'adsense');
        $enabled = get_option('alp_enabled', true);
        $performance = self::get_performance_data(7);

        require_once get_template_directory() . '/templates/admin/ads-layout-pro.php';
    }
}

// Initialize
PoradnikAdsLayoutPro::init();

// Register A/B test post type
function alp_register_ab_test_post_type() {
    register_post_type('alp_ab_test', [
        'labels' => [
            'name' => 'Ad A/B Tests',
            'singular_name' => 'Ad A/B Test',
        ],
        'public' => false,
        'show_ui' => false,
        'capability_type' => 'post',
        'supports' => ['title'],
    ]);
}
add_action('init', 'alp_register_ab_test_post_type');

// AJAX handler for click tracking
function alp_ajax_track_click() {
    $post_id = intval($_POST['post_id'] ?? 0);
    $position = sanitize_text_field($_POST['position'] ?? '');

    if ($post_id && $position) {
        PoradnikAdsLayoutPro::track_ad_event($post_id, 'click', $position, 0);
    }

    exit;
}
add_action('wp_ajax_alp_track_click', 'alp_ajax_track_click');
add_action('wp_ajax_nopriv_alp_track_click', 'alp_ajax_track_click');

// AJAX handler for viewable tracking
function alp_ajax_track_viewable() {
    $post_id = intval($_POST['post_id'] ?? 0);
    $position = sanitize_text_field($_POST['position'] ?? '');

    if ($post_id && $position) {
        PoradnikAdsLayoutPro::track_ad_event($post_id, 'viewable', $position, 0);
    }

    exit;
}
add_action('wp_ajax_alp_track_viewable', 'alp_ajax_track_viewable');
add_action('wp_ajax_nopriv_alp_track_viewable', 'alp_ajax_track_viewable');
