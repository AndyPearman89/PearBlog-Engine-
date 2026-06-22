<?php
/**
 * PT24 Landing Page Generator System
 *
 * Custom Post Type registration and URL rewriting for programmatic landing pages
 * Supports bulk generation for service/city combinations
 *
 * @package PearBlog
 * @subpackage PT24LandingGenerator
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PT24 Landing CPT Class
 */
class PearBlog_PT24_Landing_CPT {

    /**
     * Services configuration
     */
    private static $services = [
        'mechanik' => 'Mechanik',
        'hydraulik' => 'Hydraulik',
        'elektryk' => 'Elektryk',
        'pompa-ciepla' => 'Pompa ciepła',
        'remont-lazienki' => 'Remont łazienki',
        'fotowoltaika' => 'Fotowoltaika',
    ];

    /**
     * Cities configuration
     */
    private static $cities = [
        'krakow' => 'Kraków',
        'warszawa' => 'Warszawa',
        'wroclaw' => 'Wrocław',
        'katowice' => 'Katowice',
        'poznan' => 'Poznań',
        'gdansk' => 'Gdańsk',
    ];

    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('init', [__CLASS__, 'add_rewrite_rules']);
        add_filter('post_type_link', [__CLASS__, 'custom_permalink'], 10, 2);
        add_filter('template_include', [__CLASS__, 'load_template']);
        add_filter('request', [__CLASS__, 'maybe_route_landing']);

        // Admin columns
        add_filter('manage_pt24_landing_posts_columns', [__CLASS__, 'admin_columns']);
        add_action('manage_pt24_landing_posts_custom_column', [__CLASS__, 'admin_column_content'], 10, 2);
    }

    /**
     * Route /{city}/{service} (and /ranking/{city}/{service}) requests to the
     * landing CPT via the `request` filter.
     *
     * The generic ^([^/]+)/([^/]+)/?$ rewrite rule can be stripped on this
     * install by other rewrite filters, so we resolve the landing here based on
     * the request path. We only hijack the request when BOTH path segments are
     * known city + service slugs, so genuine pages/posts are never affected.
     *
     * @param array $query_vars Parsed public query vars.
     * @return array
     */
    public static function maybe_route_landing($query_vars) {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = (string) wp_parse_url($uri, PHP_URL_PATH);

        $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        if ($home_path && '/' !== $home_path) {
            $home_path = untrailingslashit($home_path);
            if (0 === strpos($path, $home_path . '/')) {
                $path = substr($path, strlen($home_path));
            }
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        // /ranking/{city}/{service}
        if (3 === count($segments) && 'ranking' === strtolower($segments[0])) {
            $city = sanitize_title($segments[1]);
            $service = sanitize_title($segments[2]);
            if (isset(self::$cities[$city], self::$services[$service])) {
                return [
                    'post_type'    => 'pt24_landing',
                    'pt24_city'    => $city,
                    'pt24_service' => $service,
                    'pt24_type'    => 'ranking',
                ];
            }
        }

        // /{city}/{service}
        if (2 === count($segments)) {
            $city = sanitize_title($segments[0]);
            $service = sanitize_title($segments[1]);
            if (isset(self::$cities[$city], self::$services[$service])) {
                return [
                    'post_type'    => 'pt24_landing',
                    'pt24_city'    => $city,
                    'pt24_service' => $service,
                ];
            }
        }

        return $query_vars;
    }

    /**
     * Register PT24 Landing CPT
     */
    public static function register_post_type() {
        $labels = [
            'name' => 'PT24 Landing Pages',
            'singular_name' => 'PT24 Landing',
            'menu_name' => 'PT24 Landings',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Landing',
            'edit_item' => 'Edit Landing',
            'new_item' => 'New Landing',
            'view_item' => 'View Landing',
            'search_items' => 'Search Landings',
            'not_found' => 'No landings found',
            'not_found_in_trash' => 'No landings in trash',
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-location-alt',
            'menu_position' => 27,
            'supports' => ['title', 'custom-fields'],
            'has_archive' => false,
            'rewrite' => false, // Custom rewrite rules below
            'capability_type' => 'post',
            'show_in_rest' => true,
        ];

        register_post_type('pt24_landing', $args);
    }

    /**
     * Add custom rewrite rules for /{miasto}/{usluga} and /ranking/{miasto}/{usluga} URL structure
     */
    public static function add_rewrite_rules() {
        // Pattern: /ranking/{city}/{service}
        add_rewrite_rule(
            '^ranking/([^/]+)/([^/]+)/?$',
            'index.php?post_type=pt24_landing&pt24_city=$matches[1]&pt24_service=$matches[2]&pt24_type=ranking',
            'top'
        );

        // Pattern: /{city}/{service}
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/?$',
            'index.php?post_type=pt24_landing&pt24_city=$matches[1]&pt24_service=$matches[2]',
            'top'
        );

        // Register query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'pt24_city';
            $vars[] = 'pt24_service';
            $vars[] = 'pt24_type';
            return $vars;
        });
    }

    /**
     * Custom permalink structure
     */
    public static function custom_permalink($permalink, $post) {
        if ($post->post_type !== 'pt24_landing') {
            return $permalink;
        }

        $city = get_post_meta($post->ID, 'pt24_city', true);
        $service = get_post_meta($post->ID, 'pt24_service', true);

        if ($city && $service) {
            return home_url("/$city/$service/");
        }

        return $permalink;
    }

    /**
     * Load custom template
     */
    public static function load_template($template) {
        // Check if this is a PT24 landing query
        $city = get_query_var('pt24_city');
        $service = get_query_var('pt24_service');
        $type = get_query_var('pt24_type');

        if ($city && $service) {
            // Find the landing page post
            $args = [
                'post_type' => 'pt24_landing',
                'posts_per_page' => 1,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'pt24_city',
                        'value' => $city,
                    ],
                    [
                        'key' => 'pt24_service',
                        'value' => $service,
                    ],
                ],
            ];

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                $query->the_post();

                // Set as main query
                global $wp_query;
                $wp_query = $query;

                // Load ranking or landing template based on type
                if ($type === 'ranking') {
                    $custom_template = locate_template('ranking-pt24_landing.php');
                    if ($custom_template) {
                        return $custom_template;
                    }
                } else {
                    $custom_template = locate_template('single-pt24_landing.php');
                    if ($custom_template) {
                        return $custom_template;
                    }
                }
            }
        }

        // Check if viewing single pt24_landing
        if (is_singular('pt24_landing')) {
            $custom_template = locate_template('single-pt24_landing.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Admin columns
     */
    public static function admin_columns($columns) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['pt24_service'] = 'Service';
        $new_columns['pt24_city'] = 'City';
        $new_columns['pt24_url'] = 'URL';
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Admin column content
     */
    public static function admin_column_content($column, $post_id) {
        switch ($column) {
            case 'pt24_service':
                $service = get_post_meta($post_id, 'pt24_service', true);
                echo esc_html($service ?: '-');
                break;
            case 'pt24_city':
                $city = get_post_meta($post_id, 'pt24_city', true);
                echo esc_html($city ?: '-');
                break;
            case 'pt24_url':
                $city = get_post_meta($post_id, 'pt24_city', true);
                $service = get_post_meta($post_id, 'pt24_service', true);
                if ($city && $service) {
                    $url = home_url("/$city/$service/");
                    echo '<a href="' . esc_url($url) . '" target="_blank">' . esc_html("/$city/$service") . '</a>';
                }
                break;
        }
    }

    /**
     * Get available services
     */
    public static function get_services() {
        return self::$services;
    }

    /**
     * Get available cities
     */
    public static function get_cities() {
        return self::$cities;
    }

    /**
     * Generate landing page
     */
    public static function generate_landing($service, $city, $args = []) {
        // Sanitize inputs
        $service_slug = sanitize_title($service);
        $city_slug = sanitize_title($city);

        // Get display names
        $service_name = self::$services[$service_slug] ?? ucfirst($service);
        $city_name = self::$cities[$city_slug] ?? ucfirst($city);

        // Check if already exists
        $existing = get_posts([
            'post_type' => 'pt24_landing',
            'posts_per_page' => 1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'pt24_city',
                    'value' => $city_slug,
                ],
                [
                    'key' => 'pt24_service',
                    'value' => $service_slug,
                ],
            ],
        ]);

        if (!empty($existing)) {
            return $existing[0]->ID; // Already exists
        }

        // Create post
        $post_data = [
            'post_title' => "$service_name $city_name",
            'post_type' => 'pt24_landing',
            'post_status' => $args['status'] ?? 'publish',
            'meta_input' => [
                'pt24_service' => $service_slug,
                'pt24_city' => $city_slug,
                'pt24_service_display' => $service_name,
                'pt24_city_display' => $city_name,
                'pt24_h1' => "$service_name $city_name — sprawdź ceny i dostępne firmy",
                'pt24_meta_title' => "$service_name $city_name — ceny i oferty",
                'pt24_meta_description' => "Znajdź $service_name w $city_name. Sprawdź ceny i dostępne firmy.",
                'pt24_hero_text' => "Znajdź najlepszych specjalistów w $city_name i otrzymaj dopasowane oferty.",
            ],
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return false;
        }

        return $post_id;
    }

    /**
     * Bulk generate landings
     */
    public static function bulk_generate($services = null, $cities = null) {
        $services = $services ?? array_keys(self::$services);
        $cities = $cities ?? array_keys(self::$cities);

        $generated = [];
        $errors = [];

        foreach ($cities as $city) {
            foreach ($services as $service) {
                $result = self::generate_landing($service, $city);

                if ($result) {
                    $generated[] = [
                        'id' => $result,
                        'service' => $service,
                        'city' => $city,
                    ];
                } else {
                    $errors[] = [
                        'service' => $service,
                        'city' => $city,
                    ];
                }
            }
        }

        return [
            'generated' => $generated,
            'errors' => $errors,
            'total' => count($generated),
        ];
    }

    /**
     * Import from CSV
     */
    public static function import_csv($file_path) {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'CSV file not found');
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return new WP_Error('file_open_error', 'Could not open CSV file');
        }

        $generated = [];
        $errors = [];
        $row = 0;

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $row++;

            // Skip header row
            if ($row === 1 && ($data[0] === 'service' || $data[0] === 'Service')) {
                continue;
            }

            if (count($data) < 2) {
                continue;
            }

            $service = trim($data[0]);
            $city = trim($data[1]);

            if (empty($service) || empty($city)) {
                continue;
            }

            $result = self::generate_landing($service, $city);

            if ($result) {
                $generated[] = [
                    'id' => $result,
                    'service' => $service,
                    'city' => $city,
                    'row' => $row,
                ];
            } else {
                $errors[] = [
                    'service' => $service,
                    'city' => $city,
                    'row' => $row,
                ];
            }
        }

        fclose($handle);

        return [
            'generated' => $generated,
            'errors' => $errors,
            'total' => count($generated),
        ];
    }
}

// Initialize
add_action('init', ['PearBlog_PT24_Landing_CPT', 'init']);

// Flush rewrite rules on activation (run once)
register_activation_hook(__FILE__, function() {
    PearBlog_PT24_Landing_CPT::init();
    flush_rewrite_rules();
});
