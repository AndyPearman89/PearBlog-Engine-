<?php
/**
 * Poradnik.PRO URL Routing & Rewrite Rules
 *
 * Registers clean URL structure for all Poradnik.PRO page types:
 *   /poradnik/{slug}              → article / guide
 *   /porownanie/{slug}            → comparison (A vs B)
 *   /ranking/{category}           → ranking list
 *   /kalkulator/{slug}            → single calculator
 *   /pytanie/{slug}               → single Q&A
 *   /specjalista/{slug}           → specialist profile
 *   /{city}/specjalisci/          → city specialists listing
 *   /{city}/{category}/           → city + category landing
 *   /kategoria/{slug}             → category archive
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Poradnik.PRO Routing Class
 */
class PearBlog_Poradnik_Pro_Routing {

    /**
     * Supported categories for URL matching
     */
    private static $categories = [
        'prawo'          => 'Prawo',
        'finanse'        => 'Finanse',
        'nieruchomosci'  => 'Nieruchomości',
        'budownictwo'    => 'Budownictwo',
        'energia'        => 'Energia',
        'zdrowie'        => 'Zdrowie',
        'edukacja'       => 'Edukacja',
        'motoryzacja'    => 'Motoryzacja',
        'technologia'    => 'Technologia',
        'dom-i-ogrod'    => 'Dom i ogród',
    ];

    /**
     * Supported cities for URL matching
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
    ];

    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('init', [__CLASS__, 'add_rewrite_rules']);
        add_filter('template_include', [__CLASS__, 'route_template'], 20);
    }

    /**
     * Register rewrite rules for Poradnik.PRO URL structure
     */
    public static function add_rewrite_rules() {
        // /poradniki (articles archive)
        add_rewrite_rule(
            '^poradniki/?$',
            'index.php?poradnik_type=articles',
            'top'
        );

        // /poradnik/{slug}
        add_rewrite_rule(
            '^poradnik/([^/]+)/?$',
            'index.php?poradnik_type=article&poradnik_slug=$matches[1]',
            'top'
        );

        // /porownanie/{slug}
        add_rewrite_rule(
            '^porownanie/([^/]+)/?$',
            'index.php?poradnik_type=comparison&poradnik_slug=$matches[1]',
            'top'
        );

        // /ranking/{category}
        add_rewrite_rule(
            '^ranking/([^/]+)/?$',
            'index.php?poradnik_type=ranking&poradnik_category=$matches[1]',
            'top'
        );

        // /kalkulator/{slug}
        add_rewrite_rule(
            '^kalkulator/([^/]+)/?$',
            'index.php?poradnik_type=calculator&poradnik_slug=$matches[1]',
            'top'
        );

        // /kalkulatory
        add_rewrite_rule(
            '^kalkulatory/?$',
            'index.php?poradnik_type=calculators',
            'top'
        );

        // /pytanie/{slug}
        add_rewrite_rule(
            '^pytanie/([^/]+)/?$',
            'index.php?poradnik_type=question&poradnik_slug=$matches[1]',
            'top'
        );

        // /pytania (Q&A archive)
        add_rewrite_rule(
            '^pytania/?$',
            'index.php?poradnik_type=questions',
            'top'
        );

        // /specjalista/{slug}
        add_rewrite_rule(
            '^specjalista/([^/]+)/?$',
            'index.php?poradnik_type=specialist&poradnik_slug=$matches[1]',
            'top'
        );

        // /specjalisci
        add_rewrite_rule(
            '^specjalisci/?$',
            'index.php?poradnik_type=specialists',
            'top'
        );

        // /ai-doradca
        add_rewrite_rule(
            '^ai-doradca/?$',
            'index.php?poradnik_type=ai-advisor',
            'top'
        );

        // /dla-specjalistow
        add_rewrite_rule(
            '^dla-specjalistow/?$',
            'index.php?poradnik_type=for-specialists',
            'top'
        );

        // /cennik
        add_rewrite_rule(
            '^cennik/?$',
            'index.php?poradnik_type=pricing',
            'top'
        );

        // /faq
        add_rewrite_rule(
            '^faq/?$',
            'index.php?poradnik_type=faq',
            'top'
        );

        // /kontakt
        add_rewrite_rule(
            '^kontakt/?$',
            'index.php?poradnik_type=contact',
            'top'
        );

        // /panel (user dashboard)
        add_rewrite_rule(
            '^panel/?$',
            'index.php?poradnik_type=dashboard',
            'top'
        );

        // /kategoria/{slug}
        add_rewrite_rule(
            '^kategoria/([^/]+)/?$',
            'index.php?poradnik_type=category&poradnik_category=$matches[1]',
            'top'
        );

        // /{city}/specjalisci/
        add_rewrite_rule(
            '^([^/]+)/specjalisci/?$',
            'index.php?poradnik_type=city-specialists&poradnik_city=$matches[1]',
            'top'
        );

        // /{city}/{category}/ — city + category landing
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/?$',
            'index.php?poradnik_type=city-category&poradnik_city=$matches[1]&poradnik_category=$matches[2]',
            'bottom'
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
        $vars[] = 'poradnik_type';
        $vars[] = 'poradnik_slug';
        $vars[] = 'poradnik_category';
        $vars[] = 'poradnik_city';
        return $vars;
    }

    /**
     * Route to correct template based on query vars
     *
     * @param string $template Current template path.
     * @return string Modified template path.
     */
    public static function route_template($template) {
        $type = get_query_var('poradnik_type');

        if (empty($type)) {
            return $template;
        }

        $template_map = [
            'articles'         => 'page-poradnik-pro-poradniki.php',
            'article'          => 'page-poradnik-pro-article.php',
            'comparison'       => 'page-poradnik-pro-porownanie.php',
            'ranking'          => 'page-poradnik-pro-ranking.php',
            'calculator'       => 'page-poradnik-pro-kalkulator.php',
            'calculators'      => 'page-poradnik-pro-kalkulatory.php',
            'question'         => 'page-poradnik-pro-pytanie.php',
            'questions'        => 'page-poradnik-pro-pytania.php',
            'specialist'       => 'page-poradnik-pro-specjalista.php',
            'specialists'      => 'page-poradnik-pro-specjalisci.php',
            'ai-advisor'       => 'page-poradnik-pro-ai-doradca.php',
            'for-specialists'  => 'page-poradnik-pro-dla-specjalistow.php',
            'pricing'          => 'page-poradnik-pro-cennik.php',
            'faq'              => 'page-poradnik-pro-faq.php',
            'contact'          => 'page-poradnik-pro-kontakt.php',
            'dashboard'        => 'page-poradnik-pro-dashboard.php',
            'category'         => 'page-poradnik-pro-kategoria.php',
            'city-specialists' => 'page-poradnik-pro-specjalisci.php',
            'city-category'    => 'page-poradnik-pro-miasto.php',
        ];

        if (isset($template_map[$type])) {
            // Validate city-based routes — only route if city slug is recognized
            if (in_array($type, ['city-specialists', 'city-category'], true)) {
                $city = self::get_current_city();
                if (!isset(self::$cities[$city])) {
                    return $template;
                }
            }

            $custom_template = locate_template($template_map[$type]);
            if ($custom_template) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Get the current page slug from query vars
     *
     * @return string
     */
    public static function get_current_slug() {
        return sanitize_title(get_query_var('poradnik_slug', ''));
    }

    /**
     * Get the current category from query vars
     *
     * @return string
     */
    public static function get_current_category() {
        return sanitize_title(get_query_var('poradnik_category', ''));
    }

    /**
     * Get the current city from query vars
     *
     * @return string
     */
    public static function get_current_city() {
        return sanitize_title(get_query_var('poradnik_city', ''));
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
     * Get readable category name from slug
     *
     * @param string $slug Category slug.
     * @return string Category display name.
     */
    public static function get_category_name($slug) {
        return isset(self::$categories[$slug]) ? self::$categories[$slug] : ucfirst(str_replace('-', ' ', $slug));
    }

    /**
     * Get all registered categories
     *
     * @return array Slug => Name pairs.
     */
    public static function get_categories() {
        return self::$categories;
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
     * Generate canonical URL for a given type/slug combination
     *
     * @param string $type    Content type (article, comparison, etc.).
     * @param string $slug    Content slug.
     * @param string $extra   Extra path segment (city, category).
     * @return string Full URL.
     */
    public static function url($type, $slug = '', $extra = '') {
        $base = home_url('/');

        switch ($type) {
            case 'articles':
                return $base . 'poradniki/';
            case 'article':
                return $base . 'poradnik/' . $slug . '/';
            case 'comparison':
                return $base . 'porownanie/' . $slug . '/';
            case 'ranking':
                return $base . 'ranking/' . $slug . '/';
            case 'calculator':
                return $base . 'kalkulator/' . $slug . '/';
            case 'calculators':
                return $base . 'kalkulatory/';
            case 'question':
                return $base . 'pytanie/' . $slug . '/';
            case 'questions':
                return $base . 'pytania/';
            case 'specialist':
                return $base . 'specjalista/' . $slug . '/';
            case 'specialists':
                return $base . 'specjalisci/';
            case 'ai-advisor':
                return $base . 'ai-doradca/';
            case 'for-specialists':
                return $base . 'dla-specjalistow/';
            case 'pricing':
                return $base . 'cennik/';
            case 'faq':
                return $base . 'faq/';
            case 'contact':
                return $base . 'kontakt/';
            case 'dashboard':
                return $base . 'panel/';
            case 'category':
                return $base . 'kategoria/' . $slug . '/';
            case 'city-specialists':
                return $base . $slug . '/specjalisci/';
            case 'city-category':
                return $base . $slug . '/' . $extra . '/';
            default:
                return $base;
        }
    }
}

// Initialize routing
PearBlog_Poradnik_Pro_Routing::init();
