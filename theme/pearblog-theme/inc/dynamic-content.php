<?php
/**
 * PearBlog Dynamic Content Rendering System
 *
 * Frontend zmienia:
 * - nagłówki
 * - CTA
 * - kolejność sekcji
 * - rekomendacje
 *
 * @package PearBlog
 * @version 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get dynamic headline based on user context
 *
 * @param string $original_headline Original headline
 * @param array $context User context
 * @return string Optimized headline
 */
function pb_get_dynamic_headline($original_headline, $context) {
    $device = $context['device'] ?? 'desktop';
    $user_segment = $context['user_segment'] ?? 'informational';

    // Mobile users get shorter headlines
    if ($device === 'mobile') {
        $short_headline = get_post_meta(get_the_ID(), 'pb_headline_mobile', true);
        if (!empty($short_headline)) {
            return $short_headline;
        }

        // Auto-shorten if too long (over 60 chars)
        if (strlen($original_headline) > 60) {
            return wp_trim_words($original_headline, 8, '...');
        }
    }

    // Get A/B test variant
    $ab_variant = pb_get_headline_variant(get_the_ID(), $context);
    if (!empty($ab_variant)) {
        return $ab_variant;
    }

    return $original_headline;
}

/**
 * Get dynamic content order based on user intent
 *
 * @param array $context User context
 * @return array Section order
 */
function pb_get_dynamic_section_order($context) {
    $user_segment = $context['user_segment'] ?? 'informational';
    $device = $context['device'] ?? 'desktop';

    $default_order = array('intro', 'tldr', 'content', 'faq', 'related', 'cta');

    // Transactional users see CTA earlier
    if ($user_segment === 'transactional') {
        return array('intro', 'tldr', 'cta', 'content', 'faq', 'related');
    }

    // Mobile users get quick answers first
    if ($device === 'mobile') {
        return array('tldr', 'intro', 'content', 'faq', 'related', 'cta');
    }

    // Navigational users see related content earlier
    if ($user_segment === 'navigational') {
        return array('intro', 'content', 'related', 'faq', 'tldr', 'cta');
    }

    return $default_order;
}

/**
 * Get dynamic TOC based on user context
 *
 * @param array $headings All H2/H3 headings
 * @param array $context User context
 * @return array Filtered headings
 */
function pb_get_dynamic_toc($headings, $context) {
    $device = $context['device'] ?? 'desktop';

    // Mobile: show only top-level headings (H2)
    if ($device === 'mobile') {
        return array_filter($headings, function($heading) {
            return $heading['level'] === 'h2';
        });
    }

    // Desktop: show all
    return $headings;
}

/**
 * Get personalized recommendations
 *
 * @param int $post_id Current post ID
 * @param array $context User context
 * @param int $limit Number of recommendations
 * @return array Post IDs
 */
function pb_get_personalized_recommendations($post_id, $context, $limit = 3) {
    global $wpdb;

    $user_segment = $context['user_segment'] ?? 'informational';
    $traffic_source = $context['traffic_source']['type'] ?? 'direct';

    // Get user's behavior history
    $session_id = $context['session_id'] ?? '';
    $viewed_posts = array();

    if (!empty($session_id)) {
        $metrics_table = $wpdb->prefix . 'pb_user_metrics';
        $viewed_posts = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM $metrics_table WHERE session_id = %s ORDER BY timestamp DESC LIMIT 10",
            $session_id
        ));
    }

    // Get current post categories
    $categories = wp_get_post_categories($post_id);

    // Build query args based on user segment
    $args = array(
        'post__not_in' => array_merge(array($post_id), $viewed_posts),
        'posts_per_page' => $limit * 2, // Get more to filter
        'orderby' => 'date',
        'order' => 'DESC',
    );

    // Segment-specific logic
    if ($user_segment === 'transactional') {
        // Show posts with high conversion rate
        $args['meta_query'] = array(
            array(
                'key' => 'pb_conversion_rate',
                'compare' => 'EXISTS',
            ),
        );
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = 'pb_conversion_rate';
    } elseif ($user_segment === 'navigational' && !empty($categories)) {
        // Show related by category
        $args['category__in'] = $categories;
    } else {
        // Informational: show popular content
        $args['meta_key'] = 'pb_popularity_score';
        $args['orderby'] = 'meta_value_num';
    }

    $recommended = get_posts($args);

    // Use AI scoring to rank
    $scored_posts = array();
    foreach ($recommended as $post) {
        $score = pb_calculate_recommendation_score($post->ID, $context);
        $scored_posts[$post->ID] = $score;
    }

    // Sort by score
    arsort($scored_posts);

    // Return top N
    return array_slice(array_keys($scored_posts), 0, $limit);
}

/**
 * Calculate recommendation score for a post
 *
 * @param int $post_id Post ID
 * @param array $context User context
 * @return float Score
 */
function pb_calculate_recommendation_score($post_id, $context) {
    $score = 0;

    // Get post metrics
    $metrics = pb_get_post_metrics($post_id, 30);

    // Factor 1: Engagement (40%)
    $engagement = ($metrics['avg_scroll'] ?? 0) + ($metrics['avg_time'] ?? 0) / 100;
    $score += $engagement * 0.4;

    // Factor 2: Recency (20%)
    $post_date = get_post_time('U', false, $post_id);
    $days_old = (time() - $post_date) / DAY_IN_SECONDS;
    $recency_score = max(0, 100 - ($days_old * 2)); // Decay over time
    $score += $recency_score * 0.2;

    // Factor 3: CTR (20%)
    $views = $metrics['views'] ?? 1;
    $ctr = $views > 0 ? (($metrics['total_cta_clicks'] ?? 0) / $views) * 100 : 0;
    $score += $ctr * 0.2;

    // Factor 4: Content match (20%)
    $device = $context['device'] ?? 'desktop';
    $is_mobile_optimized = get_post_meta($post_id, 'pb_mobile_optimized', true);
    if ($device === 'mobile' && $is_mobile_optimized) {
        $score += 20;
    } else {
        $score += 10;
    }

    return $score;
}

/**
 * Render dynamic content block
 *
 * @param string $block_type Block type
 * @param array $context User context
 */
function pb_render_dynamic_block($block_type, $context) {
    $device = $context['device'] ?? 'desktop';
    $user_segment = $context['user_segment'] ?? 'informational';

    switch ($block_type) {
        case 'intro':
            // Mobile gets shorter intro
            if ($device === 'mobile') {
                echo wp_trim_words(get_the_excerpt(), 20);
            } else {
                the_excerpt();
            }
            break;

        case 'tldr':
            $tldr = get_post_meta(get_the_ID(), 'pearblog_tldr', true);
            if (!empty($tldr)) {
                get_template_part('template-parts/block', 'tldr', array('tldr' => $tldr));
            }
            break;

        case 'content':
            the_content();
            break;

        case 'faq':
            $faq = get_post_meta(get_the_ID(), 'pearblog_faq', true);
            if (!empty($faq)) {
                get_template_part('template-parts/block', 'faq', array('faq_items' => $faq));
            }
            break;

        case 'related':
            $recommendations = pb_get_personalized_recommendations(get_the_ID(), $context, 3);
            if (!empty($recommendations)) {
                get_template_part('template-parts/block', 'related', array(
                    'post_ids' => $recommendations,
                    'title' => 'Recommended for You',
                ));
            }
            break;

        case 'cta':
            $cta = pb_get_optimal_cta(50, $user_segment); // Default 50% scroll
            get_template_part('template-parts/block', 'cta', $cta);
            break;
    }
}

/**
 * Apply dynamic spacing based on device
 *
 * @param array $context User context
 * @return string CSS classes
 */
function pb_get_dynamic_spacing_classes($context) {
    $device = $context['device'] ?? 'desktop';

    $classes = array('pb-dynamic-content');

    if ($device === 'mobile') {
        $classes[] = 'pb-spacing-compact';
    } elseif ($device === 'tablet') {
        $classes[] = 'pb-spacing-comfortable';
    } else {
        $classes[] = 'pb-spacing-spacious';
    }

    return implode(' ', $classes);
}

/**
 * Get adaptive layout classes
 *
 * @param array $context User context
 * @return string CSS classes
 */
function pb_get_adaptive_layout_classes($context) {
    $device = $context['device'] ?? 'desktop';
    $user_segment = $context['user_segment'] ?? 'informational';

    $classes = array('pb-adaptive-layout');
    $classes[] = 'pb-device-' . $device;
    $classes[] = 'pb-segment-' . $user_segment;

    // Add engagement class
    $engagement_score = pb_calculate_engagement_score($context['behavior'] ?? array());
    if ($engagement_score > 70) {
        $classes[] = 'pb-high-engagement';
    } elseif ($engagement_score > 40) {
        $classes[] = 'pb-medium-engagement';
    } else {
        $classes[] = 'pb-low-engagement';
    }

    return implode(' ', $classes);
}

/**
 * AJAX endpoint to get dynamic content
 */
function pb_ajax_get_dynamic_content() {
    check_ajax_referer('pearblog_nonce', 'nonce');

    $post_id = intval($_POST['post_id'] ?? 0);
    $scroll_depth = intval($_POST['scroll_depth'] ?? 0);

    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }

    $context = pb_get_user_context();
    $action = pb_get_recommended_action($context, $scroll_depth);

    wp_send_json_success(array(
        'cta' => $action['cta'],
        'show_ad' => $action['show_ad'],
        'show_related' => $action['show_related'],
        'show_newsletter' => $action['show_newsletter'],
        'recommendations' => pb_get_personalized_recommendations($post_id, $context, 3),
    ));
}
add_action('wp_ajax_pb_get_dynamic_content', 'pb_ajax_get_dynamic_content');
add_action('wp_ajax_nopriv_pb_get_dynamic_content', 'pb_ajax_get_dynamic_content');
