<?php
/**
 * PearBlog AI Headline Optimizer & A/B Testing
 *
 * System testuje różne H1:
 * - wersja A
 * - wersja B
 * Wybiera: → najlepszy CTR
 *
 * @package PearBlog
 * @version 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get headline variant for A/B test
 *
 * @param int $post_id Post ID
 * @param array $context User context
 * @return string Headline variant
 */
function pb_get_headline_variant($post_id, $context) {
    // Check if A/B testing is enabled for this post
    $ab_enabled = get_post_meta($post_id, 'pb_ab_test_enabled', true);

    if (!$ab_enabled) {
        return '';
    }

    // Get variants
    $variant_a = get_post_meta($post_id, 'pb_headline_variant_a', true);
    $variant_b = get_post_meta($post_id, 'pb_headline_variant_b', true);

    if (empty($variant_a) || empty($variant_b)) {
        return '';
    }

    // Get session ID for consistent variant assignment
    $session_id = $context['session_id'] ?? '';

    // Use session hash to determine variant (50/50 split)
    $hash = md5($session_id . $post_id);
    $variant = (hexdec(substr($hash, 0, 8)) % 2 === 0) ? 'a' : 'b';

    // Track impression
    pb_track_ab_impression($post_id, $variant);

    return $variant === 'a' ? $variant_a : $variant_b;
}

/**
 * Track A/B test impression
 *
 * @param int $post_id Post ID
 * @param string $variant Variant (a or b)
 */
function pb_track_ab_impression($post_id, $variant) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pb_ab_tests';

    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        pb_create_ab_test_table();
    }

    $wpdb->query($wpdb->prepare(
        "INSERT INTO $table_name (post_id, variant, impressions, clicks, timestamp)
        VALUES (%d, %s, 1, 0, %s)
        ON DUPLICATE KEY UPDATE impressions = impressions + 1",
        $post_id,
        $variant,
        current_time('mysql')
    ));
}

/**
 * Track A/B test click
 *
 * @param int $post_id Post ID
 * @param string $variant Variant (a or b)
 */
function pb_track_ab_click($post_id, $variant) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pb_ab_tests';

    $wpdb->query($wpdb->prepare(
        "UPDATE $table_name SET clicks = clicks + 1, timestamp = %s
        WHERE post_id = %d AND variant = %s",
        current_time('mysql'),
        $post_id,
        $variant
    ));
}

/**
 * Get A/B test results
 *
 * @param int $post_id Post ID
 * @return array Test results
 */
function pb_get_ab_test_results($post_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pb_ab_tests';

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT variant, impressions, clicks,
        (clicks / NULLIF(impressions, 0) * 100) as ctr
        FROM $table_name
        WHERE post_id = %d",
        $post_id
    ), ARRAY_A);

    if (empty($results)) {
        return array(
            'a' => array('impressions' => 0, 'clicks' => 0, 'ctr' => 0),
            'b' => array('impressions' => 0, 'clicks' => 0, 'ctr' => 0),
            'winner' => null,
        );
    }

    $data = array();
    foreach ($results as $result) {
        $data[$result['variant']] = $result;
    }

    // Determine winner (needs statistical significance)
    $winner = pb_determine_ab_winner($data);

    return array_merge($data, array('winner' => $winner));
}

/**
 * Determine A/B test winner
 *
 * @param array $data Test data
 * @return string|null Winner variant or null
 */
function pb_determine_ab_winner($data) {
    $variant_a = $data['a'] ?? array('impressions' => 0, 'ctr' => 0);
    $variant_b = $data['b'] ?? array('impressions' => 0, 'ctr' => 0);

    // Need minimum sample size
    $min_impressions = 100;
    if ($variant_a['impressions'] < $min_impressions || $variant_b['impressions'] < $min_impressions) {
        return null; // Not enough data
    }

    // Need significant difference (at least 10% improvement)
    $ctr_diff_threshold = 10; // 10% relative improvement

    $ctr_a = floatval($variant_a['ctr'] ?? 0);
    $ctr_b = floatval($variant_b['ctr'] ?? 0);

    if ($ctr_a === 0 && $ctr_b === 0) {
        return null;
    }

    $higher_ctr = max($ctr_a, $ctr_b);
    $lower_ctr = min($ctr_a, $ctr_b);

    if ($lower_ctr === 0) {
        return $ctr_a > $ctr_b ? 'a' : 'b';
    }

    $improvement = (($higher_ctr - $lower_ctr) / $lower_ctr) * 100;

    if ($improvement >= $ctr_diff_threshold) {
        return $ctr_a > $ctr_b ? 'a' : 'b';
    }

    return null; // No clear winner
}

/**
 * Auto-apply winning variant
 *
 * @param int $post_id Post ID
 * @return bool Success
 */
function pb_auto_apply_winner($post_id) {
    $results = pb_get_ab_test_results($post_id);

    if (empty($results['winner'])) {
        return false;
    }

    $winner = $results['winner'];
    $winning_headline = get_post_meta($post_id, 'pb_headline_variant_' . $winner, true);

    // Update post title
    wp_update_post(array(
        'ID' => $post_id,
        'post_title' => $winning_headline,
    ));

    // Disable A/B test
    update_post_meta($post_id, 'pb_ab_test_enabled', false);
    update_post_meta($post_id, 'pb_ab_test_winner', $winner);
    update_post_meta($post_id, 'pb_ab_test_completed', current_time('mysql'));

    return true;
}

/**
 * Generate headline variations using AI-like logic
 *
 * @param string $original_headline Original headline
 * @return array Headline variations
 */
function pb_generate_headline_variations($original_headline) {
    $variations = array();

    // Variation 1: Add numbers
    if (!preg_match('/\d+/', $original_headline)) {
        $variations[] = preg_replace('/^(.+?)(\s|$)/', '7 $1 ', $original_headline, 1);
    }

    // Variation 2: Add power words
    $power_words = array('Ultimate', 'Complete', 'Essential', 'Proven', 'Secret');
    $random_word = $power_words[array_rand($power_words)];
    $variations[] = $random_word . ' ' . $original_headline;

    // Variation 3: Make it a question
    if (substr($original_headline, -1) !== '?') {
        $variations[] = 'How to ' . $original_headline . '?';
    }

    // Variation 4: Add benefit
    $variations[] = $original_headline . ' (Step-by-Step Guide)';

    // Variation 5: Shorter version
    $words = explode(' ', $original_headline);
    if (count($words) > 5) {
        $variations[] = implode(' ', array_slice($words, 0, 5));
    }

    return array_unique($variations);
}

/**
 * Create A/B test table
 */
function pb_create_ab_test_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pb_ab_tests';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        variant varchar(10) NOT NULL,
        impressions bigint(20) NOT NULL DEFAULT 0,
        clicks bigint(20) NOT NULL DEFAULT 0,
        timestamp datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY post_variant (post_id, variant),
        KEY post_id (post_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'pb_create_ab_test_table');

/**
 * AJAX endpoint to track A/B click
 */
function pb_ajax_track_ab_click() {
    check_ajax_referer('pearblog_nonce', 'nonce');

    $post_id = intval($_POST['post_id'] ?? 0);
    $variant = sanitize_text_field($_POST['variant'] ?? '');

    if (!$post_id || !in_array($variant, array('a', 'b'))) {
        wp_send_json_error('Invalid parameters');
    }

    pb_track_ab_click($post_id, $variant);

    wp_send_json_success(array('message' => 'Click tracked'));
}
add_action('wp_ajax_pb_track_ab_click', 'pb_ajax_track_ab_click');
add_action('wp_ajax_nopriv_pb_track_ab_click', 'pb_ajax_track_ab_click');

/**
 * Cron job to check and apply winners
 */
function pb_check_ab_test_winners() {
    global $wpdb;

    // Get all posts with active A/B tests
    $post_ids = $wpdb->get_col(
        "SELECT post_id FROM {$wpdb->postmeta}
        WHERE meta_key = 'pb_ab_test_enabled' AND meta_value = '1'
        GROUP BY post_id"
    );

    foreach ($post_ids as $post_id) {
        $results = pb_get_ab_test_results($post_id);

        if (!empty($results['winner'])) {
            pb_auto_apply_winner($post_id);
        }
    }
}

/**
 * Schedule A/B test winner check
 */
function pb_schedule_ab_test_check() {
    if (!wp_next_scheduled('pb_ab_test_check')) {
        wp_schedule_event(time(), 'daily', 'pb_ab_test_check');
    }
}
add_action('wp', 'pb_schedule_ab_test_check');
add_action('pb_ab_test_check', 'pb_check_ab_test_winners');

/**
 * Calculate content popularity score
 *
 * @param int $post_id Post ID
 * @return float Popularity score
 */
function pb_calculate_popularity_score($post_id) {
    $metrics = pb_get_post_metrics($post_id, 30);

    $score = 0;

    // Views (30%)
    $score += ($metrics['views'] ?? 0) * 0.3;

    // Engagement (40%)
    $engagement = ($metrics['avg_scroll'] ?? 0) + ($metrics['avg_time'] ?? 0) / 100;
    $score += $engagement * 0.4;

    // CTR (30%)
    $views = $metrics['views'] ?? 1;
    $ctr = $views > 0 ? (($metrics['total_cta_clicks'] ?? 0) / $views) * 100 : 0;
    $score += $ctr * 0.3;

    // Store for future use
    update_post_meta($post_id, 'pb_popularity_score', $score);

    return $score;
}

/**
 * Update popularity scores for all posts
 */
function pb_update_all_popularity_scores() {
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => 100,
        'post_status' => 'publish',
        'orderby' => 'modified',
        'order' => 'DESC',
    );

    $posts = get_posts($args);

    foreach ($posts as $post) {
        pb_calculate_popularity_score($post->ID);
    }
}

/**
 * Schedule popularity score updates
 */
function pb_schedule_popularity_updates() {
    if (!wp_next_scheduled('pb_popularity_update')) {
        wp_schedule_event(time(), 'twicedaily', 'pb_popularity_update');
    }
}
add_action('wp', 'pb_schedule_popularity_updates');
add_action('pb_popularity_update', 'pb_update_all_popularity_scores');
