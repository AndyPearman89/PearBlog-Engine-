<?php
/**
 * PT24.PRO URL Routing — Clean URL structure for platform pages
 *
 * Registers clean URL structure for PT24.PRO static/platform pages:
 *   /dla-fachowcow/           → professionals landing
 *   /dodaj-zlecenie/          → add order form
 *   /uslugi/{slug}/           → service category page
 *   /miasto/{slug}/           → city landing page
 *   /panel-fachowca/          → professional dashboard
 *
 * Note: Dynamic /{city}/{service} and /ranking/{city}/{service} routes
 * are handled by pt24-landing-cpt.php (CPT-based).
 *
 * @package PearBlog
 * @subpackage PT24Pro
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PT24.PRO Platform Routing Class
 */
class PearBlog_PT24_Pro_Routing {

    /**
     * Service categories for URL matching
     */
    private static $services = [
        'hydraulik'      => 'Hydraulik',
        'elektryk'       => 'Elektryk',
        'malarz'         => 'Malarz',
        'stolarz'        => 'Stolarz',
        'dekarz'         => 'Dekarz',
        'murarz'         => 'Murarz',
        'glazurnik'      => 'Glazurnik',
        'instalator'     => 'Instalator',
        'ogrodnik'       => 'Ogrodnik',
        'sprzatanie'     => 'Sprzątanie',
        'przeprowadzki'  => 'Przeprowadzki',
        'klimatyzacja'   => 'Klimatyzacja',
        'fotowoltaika'   => 'Fotowoltaika',
        'pompy-ciepla'   => 'Pompy ciepła',
        'remont'         => 'Remont',
    ];

    /**
     * Cities for URL matching
     */
    private static $cities = [
        'warszawa'  => 'Warszawa',
        'krakow'    => 'Kraków',
        'wroclaw'   => 'Wrocław',
        'poznan'    => 'Poznań',
        'gdansk'    => 'Gdańsk',
        'katowice'  => 'Katowice',
        'lodz'      => 'Łódź',
        'szczecin'  => 'Szczecin',
        'lublin'    => 'Lublin',
        'bydgoszcz' => 'Bydgoszcz',
        'rzeszow'   => 'Rzeszów',
        'bialystok' => 'Białystok',
    ];

    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('init', [__CLASS__, 'add_rewrite_rules'], 20);
        add_filter('template_include', [__CLASS__, 'route_template'], 15);
    }

    /**
     * Register rewrite rules for PT24.PRO platform pages
     */
    public static function add_rewrite_rules() {
        // /dla-fachowcow/
        add_rewrite_rule(
            '^dla-fachowcow/?$',
            'index.php?pt24_page=for-professionals',
            'top'
        );

        // /dodaj-zlecenie/
        add_rewrite_rule(
            '^dodaj-zlecenie/?$',
            'index.php?pt24_page=add-order',
            'top'
        );

        // /uslugi/{slug}/
        add_rewrite_rule(
            '^uslugi/([^/]+)/?$',
            'index.php?pt24_page=service-category&pt24_service_slug=$matches[1]',
            'top'
        );

        // /uslugi/ (services archive)
        add_rewrite_rule(
            '^uslugi/?$',
            'index.php?pt24_page=services',
            'top'
        );

        // /miasto/{slug}/
        add_rewrite_rule(
            '^miasto/([^/]+)/?$',
            'index.php?pt24_page=city&pt24_city_slug=$matches[1]',
            'top'
        );

        // /panel-fachowca/
        add_rewrite_rule(
            '^panel-fachowca/?$',
            'index.php?pt24_page=pro-dashboard',
            'top'
        );

        // Register query vars
        add_filter('query_vars', [__CLASS__, 'register_query_vars']);
    }

    /**
     * Register custom query variables
     *
     * @param array $vars Existing query vars.
     * @return array Modified query vars.
     */
    public static function register_query_vars($vars) {
        $vars[] = 'pt24_page';
        $vars[] = 'pt24_service_slug';
        $vars[] = 'pt24_city_slug';
        return $vars;
    }

    /**
     * Route to correct template based on query vars
     *
     * @param string $template Current template path.
     * @return string Modified template path.
     */
    public static function route_template($template) {
        $page = get_query_var('pt24_page');

        if (empty($page)) {
            return $template;
        }

        $template_map = [
            'for-professionals' => 'page-pt24-dla-fachowcow.php',
            'add-order'         => 'page-pt24-dodaj-zlecenie.php',
            'service-category'  => 'page-pt24-kategoria-uslugi.php',
            'services'          => 'page-pt24-kategoria-uslugi.php',
            'city'              => 'page-pt24-miasto.php',
            'pro-dashboard'     => 'page-pt24-panel-fachowca.php',
        ];

        if (isset($template_map[$page])) {
            $custom_template = locate_template($template_map[$page]);
            if ($custom_template) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Get the current service slug from query vars
     *
     * @return string
     */
    public static function get_current_service() {
        return sanitize_title(get_query_var('pt24_service_slug', ''));
    }

    /**
     * Get the current city slug from query vars
     *
     * @return string
     */
    public static function get_current_city() {
        return sanitize_title(get_query_var('pt24_city_slug', ''));
    }

    /**
     * Get readable service name from slug
     *
     * @param string $slug Service slug.
     * @return string Service display name.
     */
    public static function get_service_name($slug) {
        return isset(self::$services[$slug]) ? self::$services[$slug] : ucfirst(str_replace('-', ' ', $slug));
    }

    /**
     * Get readable city name from slug
     *
     * @param string $slug City slug.
     * @return string City display name.
     */
    public static function get_city_name($slug) {
        return isset(self::$cities[$slug]) ? self::$cities[$slug] : ucfirst($slug);
    }

    /**
     * Get all registered services
     *
     * @return array Slug => Name pairs.
     */
    public static function get_services() {
        return self::$services;
    }

    /**
     * Alias for get_services() used by templates.
     *
     * @return array Slug => Name pairs.
     */
    public static function get_all_services() {
        return self::$services;
    }

    /**
     * Get all registered cities
     *
     * @return array Slug => Name pairs.
     */
    public static function get_cities() {
        return self::$cities;
    }

    /**
     * Generate canonical URL for a given PT24 page type
     *
     * @param string $type Page type.
     * @param string $slug Optional slug.
     * @return string Full URL.
     */
    public static function url($type, $slug = '') {
        $base = home_url('/');

        switch ($type) {
            case 'for-professionals':
                return $base . 'dla-fachowcow/';
            case 'add-order':
                return $base . 'dodaj-zlecenie/';
            case 'service-category':
                return $base . 'uslugi/' . $slug . '/';
            case 'services':
                return $base . 'uslugi/';
            case 'city':
                return $base . 'miasto/' . $slug . '/';
            case 'pro-dashboard':
                return $base . 'panel-fachowca/';
            default:
                return $base;
        }
    }
}

// Initialize routing
PearBlog_PT24_Pro_Routing::init();
