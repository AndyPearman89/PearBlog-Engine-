<?php
/**
 * Search Suggest API - REST endpoint for front page autosuggest
 *
 * Provides multi-type search results for the Decision Hub
 *
 * @package PearBlog
 * @version 3.0.0
 */

namespace PearBlogEngine\API;

class SearchSuggestAPI {

    /**
     * Register REST routes
     */
    public static function register_routes() {
        register_rest_route('pearblog/v3', '/search/suggest', [
            'methods' => 'GET',
            'callback' => [self::class, 'search_suggest'],
            'permission_callback' => '__return_true',
            'args' => [
                'q' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return strlen($param) >= 2 && strlen($param) <= 100;
                    }
                ],
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ]
            ]
        ]);
    }

    /**
     * Search across multiple content types
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function search_suggest($request) {
        $query = $request->get_param('q');
        $limit = min($request->get_param('limit'), 20); // Max 20 results

        // Search across all types
        $results = array_merge(
            self::search_poradniki($query, $limit),
            self::search_comparisons($query, $limit),
            self::search_rankings($query, $limit),
            self::search_experts($query, $limit)
        );

        // Sort by relevance (title matches first)
        usort($results, function($a, $b) use ($query) {
            $query_lower = strtolower($query);
            $a_title_lower = strtolower($a['title']);
            $b_title_lower = strtolower($b['title']);

            // Prioritize exact title matches
            $a_exact = strpos($a_title_lower, $query_lower) === 0;
            $b_exact = strpos($b_title_lower, $query_lower) === 0;

            if ($a_exact && !$b_exact) return -1;
            if (!$a_exact && $b_exact) return 1;

            // Then by title contains
            $a_contains = strpos($a_title_lower, $query_lower) !== false;
            $b_contains = strpos($b_title_lower, $query_lower) !== false;

            if ($a_contains && !$b_contains) return -1;
            if (!$a_contains && $b_contains) return 1;

            return 0;
        });

        // Limit final results
        $results = array_slice($results, 0, $limit);

        return rest_ensure_response($results);
    }

    /**
     * Search poradniki (blog posts)
     */
    private static function search_poradniki($query, $limit) {
        $args = [
            's' => $query,
            'post_type' => 'post',
            'posts_per_page' => ceil($limit / 4),
            'post_status' => 'publish',
            'orderby' => 'relevance',
        ];

        $posts = get_posts($args);
        $results = [];

        foreach ($posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'type' => 'poradnik',
                'title' => $post->post_title,
                'excerpt' => wp_trim_words($post->post_excerpt ?: $post->post_content, 12),
                'url' => get_permalink($post->ID),
            ];
        }

        return $results;
    }

    /**
     * Search comparisons
     */
    private static function search_comparisons($query, $limit) {
        $args = [
            's' => $query,
            'post_type' => 'comparison',
            'posts_per_page' => ceil($limit / 4),
            'post_status' => 'publish',
            'orderby' => 'relevance',
        ];

        $posts = get_posts($args);
        $results = [];

        foreach ($posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'type' => 'comparison',
                'title' => $post->post_title,
                'excerpt' => wp_trim_words($post->post_excerpt ?: $post->post_content, 12),
                'url' => get_permalink($post->ID),
            ];
        }

        return $results;
    }

    /**
     * Search rankings
     */
    private static function search_rankings($query, $limit) {
        $args = [
            's' => $query,
            'post_type' => 'ranking',
            'posts_per_page' => ceil($limit / 4),
            'post_status' => 'publish',
            'orderby' => 'relevance',
        ];

        $posts = get_posts($args);
        $results = [];

        foreach ($posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'type' => 'ranking',
                'title' => $post->post_title,
                'excerpt' => wp_trim_words($post->post_excerpt ?: $post->post_content, 12),
                'url' => get_permalink($post->ID),
            ];
        }

        return $results;
    }

    /**
     * Search experts
     */
    private static function search_experts($query, $limit) {
        $args = [
            's' => $query,
            'post_type' => 'expert',
            'posts_per_page' => ceil($limit / 4),
            'post_status' => 'publish',
            'orderby' => 'relevance',
        ];

        $posts = get_posts($args);
        $results = [];

        foreach ($posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'type' => 'expert',
                'title' => $post->post_title,
                'excerpt' => get_post_meta($post->ID, 'specialty', true) ?: '',
                'url' => get_permalink($post->ID),
            ];
        }

        return $results;
    }
}
