<?php
/**
 * Poradnik.pro V4 — Component Helper Functions
 *
 * Helper functions for rendering V4 decision-focused components
 *
 * @package PearBlog
 * @version 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render comparison block
 *
 * @param array $args Comparison arguments
 * @return void
 */
function poradnik_comparison($args = []) {
    get_template_part('template-parts/block-comparison-v4', null, $args);
}

/**
 * Render ranking block
 *
 * @param array $args Ranking arguments
 * @return void
 */
function poradnik_ranking($args = []) {
    get_template_part('template-parts/block-ranking-v4', null, $args);
}

/**
 * Render calculator block
 *
 * @param array $args Calculator arguments
 * @return void
 */
function poradnik_calculator($args = []) {
    get_template_part('template-parts/block-calculator-v4', null, $args);
}

/**
 * Render AI suggestion
 *
 * @param array $args AI suggestion arguments
 * @return void
 */
function poradnik_ai_suggestion($args = []) {
    get_template_part('template-parts/block-ai-suggestion-v4', null, $args);
}

/**
 * Render decision bar
 *
 * @return void
 */
function poradnik_decision_bar() {
    get_template_part('template-parts/decision-bar-v4');
}

/**
 * Get comparison data from post meta
 *
 * @param int $post_id Post ID
 * @return array Comparison items
 */
function poradnik_get_comparison_data($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $comparison_data = get_post_meta($post_id, '_poradnik_comparison', true);

    if (!$comparison_data || !is_array($comparison_data)) {
        return [];
    }

    return $comparison_data;
}

/**
 * Get ranking data from post meta
 *
 * @param int $post_id Post ID
 * @return array Ranking items
 */
function poradnik_get_ranking_data($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $ranking_data = get_post_meta($post_id, '_poradnik_ranking', true);

    if (!$ranking_data || !is_array($ranking_data)) {
        return [];
    }

    return $ranking_data;
}

/**
 * Get calculator configuration from post meta
 *
 * @param int $post_id Post ID
 * @return array Calculator configuration
 */
function poradnik_get_calculator_config($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $calculator_config = get_post_meta($post_id, '_poradnik_calculator', true);

    if (!$calculator_config || !is_array($calculator_config)) {
        return [];
    }

    return $calculator_config;
}

/**
 * Inject AI suggestions into content
 *
 * Automatically adds AI suggestions after certain paragraphs
 *
 * @param string $content Post content
 * @return string Modified content
 */
function poradnik_inject_ai_suggestions($content) {
    if (!is_singular() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    // Check if AI suggestions are enabled
    if (!get_option('poradnik_ai_suggestions_enabled', true)) {
        return $content;
    }

    // Split content into paragraphs
    $paragraphs = preg_split('/(<p[^>]*>.*?<\/p>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $modified_content = '';
    $paragraph_count = 0;

    foreach ($paragraphs as $paragraph) {
        $modified_content .= $paragraph;

        // Check if this is an actual paragraph tag
        if (preg_match('/<p[^>]*>.*?<\/p>/is', $paragraph)) {
            $paragraph_count++;

            // Inject AI suggestion after every 4th paragraph
            if ($paragraph_count % 4 === 0) {
                $suggestion = poradnik_get_ai_suggestion_for_context($paragraph);
                if ($suggestion) {
                    ob_start();
                    poradnik_ai_suggestion($suggestion);
                    $modified_content .= ob_get_clean();
                }
            }
        }
    }

    return $modified_content;
}
add_filter('the_content', 'poradnik_inject_ai_suggestions', 20);

/**
 * Get AI suggestion based on content context
 *
 * @param string $context Content context
 * @return array|null Suggestion data or null
 */
function poradnik_get_ai_suggestion_for_context($context) {
    // Extract keywords from context
    $text = wp_strip_all_tags($context);

    // Example suggestions (in production, these would come from AI or database)
    $suggestions = [
        [
            'title' => 'Rekomendacja AI',
            'text' => 'Na podstawie tego artykułu, najlepsza opcja to rozwiązanie z elastycznym budżetem i lokalnym wsparciem.',
            'action_text' => 'Zobacz ekspertów',
            'action_url' => '#calculator',
        ],
        [
            'title' => 'Sugestia AI',
            'text' => 'Użytkownicy w podobnej sytuacji najczęściej wybierają opcję z gwarancją i stałą ceną.',
            'action_text' => 'Porównaj opcje',
            'action_url' => '#comparison',
        ],
    ];

    // Return random suggestion (in production, use context-aware selection)
    if (mt_rand(0, 2) === 0) { // 33% chance
        return $suggestions[array_rand($suggestions)];
    }

    return null;
}

/**
 * Enqueue V4 assets
 */
function poradnik_enqueue_v4_assets() {
    // Check if V4 is enabled for this site
    if (!get_option('poradnik_v4_enabled', false)) {
        return;
    }

    // Enqueue V4 CSS
    wp_enqueue_style(
        'poradnik-v4',
        PEARBLOG_URI . '/assets/css/poradnik-v4.css',
        array('pearblog-base'),
        PEARBLOG_VERSION
    );

    // Enqueue V4 JavaScript
    wp_enqueue_script(
        'poradnik-v4',
        PEARBLOG_URI . '/assets/js/poradnik-v4.js',
        array('pearblog-app'),
        PEARBLOG_VERSION,
        true
    );

    // Add body class for V4
    add_filter('body_class', function($classes) {
        $classes[] = 'poradnik-v4';
        return $classes;
    });
}
add_action('wp_enqueue_scripts', 'poradnik_enqueue_v4_assets');

/**
 * Register V4 REST API endpoints
 */
function poradnik_register_v4_api_routes() {
    register_rest_route('poradnik/v1', '/search-suggestions', [
        'methods' => 'GET',
        'callback' => 'poradnik_api_search_suggestions',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('poradnik/v1', '/matches', [
        'methods' => 'POST',
        'callback' => 'poradnik_api_get_matches',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'poradnik_register_v4_api_routes');

/**
 * API: Get search suggestions
 *
 * @param WP_REST_Request $request Request object
 * @return array Search suggestions
 */
function poradnik_api_search_suggestions($request) {
    $query = sanitize_text_field($request->get_param('q'));

    if (empty($query)) {
        return [];
    }

    // Search for posts matching the query
    $posts = get_posts([
        's' => $query,
        'posts_per_page' => 5,
        'post_status' => 'publish',
    ]);

    $suggestions = array_map(function($post) {
        return [
            'title' => $post->post_title,
            'url' => get_permalink($post),
        ];
    }, $posts);

    return $suggestions;
}

/**
 * API: Get matching experts based on calculator data
 *
 * @param WP_REST_Request $request Request object
 * @return array Matching experts
 */
function poradnik_api_get_matches($request) {
    $data = $request->get_json_params();

    // Example: Get experts based on location and budget
    $location = sanitize_text_field($data['location'] ?? '');
    $budget = absint($data['budget'] ?? 0);

    // Query for matching experts (customize based on your data structure)
    $experts = get_posts([
        'post_type' => 'expert', // Assuming you have an 'expert' post type
        'posts_per_page' => 3,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'location',
                'value' => $location,
                'compare' => 'LIKE',
            ],
        ],
    ]);

    $matches = array_map(function($expert) {
        return [
            'name' => $expert->post_title,
            'description' => wp_trim_words($expert->post_content, 20),
            'url' => get_permalink($expert),
            'cta' => 'Zapytaj o wycenę',
        ];
    }, $experts);

    return $matches;
}

/**
 * Add V4 settings to customizer
 */
function poradnik_v4_customizer_settings($wp_customize) {
    // V4 Section
    $wp_customize->add_section('poradnik_v4', [
        'title' => __('Poradnik.pro V4', 'pearblog-theme'),
        'panel' => 'pearblog_pro_panel',
        'priority' => 15,
    ]);

    // Enable V4
    $wp_customize->add_setting('poradnik_v4_enabled', [
        'default' => false,
        'sanitize_callback' => 'pearblog_sanitize_checkbox',
    ]);

    $wp_customize->add_control('poradnik_v4_enabled', [
        'label' => __('Enable V4 Design System', 'pearblog-theme'),
        'section' => 'poradnik_v4',
        'type' => 'checkbox',
    ]);

    // Decision Bar
    $wp_customize->add_setting('poradnik_decision_bar_enabled', [
        'default' => true,
        'sanitize_callback' => 'pearblog_sanitize_checkbox',
    ]);

    $wp_customize->add_control('poradnik_decision_bar_enabled', [
        'label' => __('Enable Sticky Decision Bar', 'pearblog-theme'),
        'section' => 'poradnik_v4',
        'type' => 'checkbox',
    ]);

    // AI Suggestions
    $wp_customize->add_setting('poradnik_ai_suggestions_enabled', [
        'default' => true,
        'sanitize_callback' => 'pearblog_sanitize_checkbox',
    ]);

    $wp_customize->add_control('poradnik_ai_suggestions_enabled', [
        'label' => __('Enable AI Inline Suggestions', 'pearblog-theme'),
        'section' => 'poradnik_v4',
        'type' => 'checkbox',
    ]);
}
add_action('customize_register', 'poradnik_v4_customizer_settings');

/**
 * Schema.org markup for comparison blocks
 *
 * @param array $items Comparison items
 * @return string JSON-LD schema
 */
function poradnik_comparison_schema($items) {
    if (empty($items)) {
        return '';
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'itemListElement' => [],
    ];

    foreach ($items as $index => $item) {
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'Product',
                'name' => $item['title'] ?? '',
                'description' => $item['description'] ?? '',
            ],
        ];
    }

    return '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>';
}
