<?php
/**
 * PT24.PRO - Local Services Platform
 *
 * Custom Post Types and Taxonomies for local services directory
 *
 * @package PT24
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Custom Post Types
 */
function pt24_register_service_category_cpt() {
    register_post_type('pt24_category', [
        'labels' => [
            'name' => 'Kategorie Usług',
            'singular_name' => 'Kategoria',
            'add_new' => 'Dodaj kategorię',
            'add_new_item' => 'Dodaj nową kategorię',
            'edit_item' => 'Edytuj kategorię',
            'view_item' => 'Zobacz kategorię',
            'all_items' => 'Wszystkie kategorie',
        ],
        'public' => true,
        'has_archive' => false,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'kategoria-uslugi'],
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-category',
        'capability_type' => 'post',
    ]);
}
add_action('init', 'pt24_register_service_category_cpt');

/**
 * Register Local Pages CPT
 */
function pt24_register_local_page_cpt() {
    register_post_type('pt24_local', [
        'labels' => [
            'name' => 'Strony Lokalne',
            'singular_name' => 'Strona Lokalna',
            'add_new' => 'Dodaj stronę',
            'add_new_item' => 'Dodaj nową stronę lokalną',
            'edit_item' => 'Edytuj stronę',
            'view_item' => 'Zobacz stronę',
            'all_items' => 'Wszystkie strony',
        ],
        'public' => true,
        'has_archive' => false,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'strona-lokalna'],
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-location-alt',
        'capability_type' => 'post',
    ]);
}
add_action('init', 'pt24_register_local_page_cpt');

/**
 * Register Business Profiles CPT
 */
function pt24_register_business_cpt() {
    register_post_type('pt24_business', [
        'labels' => [
            'name' => 'Firmy',
            'singular_name' => 'Firma',
            'add_new' => 'Dodaj firmę',
            'add_new_item' => 'Dodaj nową firmę',
            'edit_item' => 'Edytuj firmę',
            'view_item' => 'Zobacz firmę',
            'all_items' => 'Wszystkie firmy',
        ],
        'public' => true,
        'has_archive' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'firma'],
        'supports' => ['title', 'editor', 'thumbnail', 'author', 'custom-fields'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-building',
        'capability_type' => 'post',
    ]);
}
add_action('init', 'pt24_register_business_cpt');

/**
 * Register Service Pages CPT
 */
function pt24_register_service_cpt() {
    register_post_type('pt24_service', [
        'labels' => [
            'name' => 'Usługi',
            'singular_name' => 'Usługa',
            'add_new' => 'Dodaj usługę',
            'add_new_item' => 'Dodaj nową usługę',
            'edit_item' => 'Edytuj usługę',
            'view_item' => 'Zobacz usługę',
            'all_items' => 'Wszystkie usługi',
        ],
        'public' => true,
        'has_archive' => false,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'usluga'],
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-admin-tools',
        'capability_type' => 'post',
    ]);
}
add_action('init', 'pt24_register_service_cpt');

/**
 * Register Custom Taxonomies
 */

// Cities Taxonomy
function pt24_register_city_taxonomy() {
    register_taxonomy('pt24_city', ['pt24_local', 'pt24_business'], [
        'labels' => [
            'name' => 'Miasta',
            'singular_name' => 'Miasto',
            'add_new_item' => 'Dodaj nowe miasto',
            'edit_item' => 'Edytuj miasto',
            'view_item' => 'Zobacz miasto',
            'all_items' => 'Wszystkie miasta',
        ],
        'hierarchical' => true,
        'rewrite' => ['slug' => 'miasto'],
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);
}
add_action('init', 'pt24_register_city_taxonomy');

// Service Categories Taxonomy
function pt24_register_service_category_taxonomy() {
    register_taxonomy('pt24_service_cat', ['pt24_local', 'pt24_service', 'pt24_business'], [
        'labels' => [
            'name' => 'Kategorie Usług',
            'singular_name' => 'Kategoria Usługi',
            'add_new_item' => 'Dodaj nową kategorię',
            'edit_item' => 'Edytuj kategorię',
            'view_item' => 'Zobacz kategorię',
            'all_items' => 'Wszystkie kategorie',
        ],
        'hierarchical' => true,
        'rewrite' => ['slug' => 'kategoria'],
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);
}
add_action('init', 'pt24_register_service_category_taxonomy');

// Regions Taxonomy
function pt24_register_region_taxonomy() {
    register_taxonomy('pt24_region', ['pt24_city'], [
        'labels' => [
            'name' => 'Województwa',
            'singular_name' => 'Województwo',
            'add_new_item' => 'Dodaj nowe województwo',
            'edit_item' => 'Edytuj województwo',
            'view_item' => 'Zobacz województwo',
            'all_items' => 'Wszystkie województwa',
        ],
        'hierarchical' => true,
        'rewrite' => ['slug' => 'wojewodztwo'],
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);
}
add_action('init', 'pt24_register_region_taxonomy');

/**
 * Custom Rewrite Rules for PT24 Local Services
 */
function pt24_custom_rewrite_rules() {
    // Pattern: /mechanik/warszawa/
    add_rewrite_rule(
        '^([^/]+)/([^/]+)/?$',
        'index.php?pt24_category=$matches[1]&pt24_city=$matches[2]',
        'top'
    );

    // Pattern: /mechanik/warszawa/diagnostyka/
    add_rewrite_rule(
        '^([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?pt24_category=$matches[1]&pt24_city=$matches[2]&pt24_service=$matches[3]',
        'top'
    );
}
add_action('init', 'pt24_custom_rewrite_rules');

/**
 * Register Query Vars
 */
function pt24_query_vars($vars) {
    $vars[] = 'pt24_category';
    $vars[] = 'pt24_city';
    $vars[] = 'pt24_service';
    return $vars;
}
add_filter('query_vars', 'pt24_query_vars');

/**
 * Create Database Tables for PT24
 */
function pt24_create_database_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Leads Table
    $table_leads = $wpdb->prefix . 'pt24_leads';
    $sql_leads = "CREATE TABLE IF NOT EXISTS $table_leads (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        city varchar(50) NOT NULL,
        service varchar(50) NOT NULL,
        message text,
        source varchar(100),
        status varchar(20) DEFAULT 'new',
        business_id bigint(20) UNSIGNED,
        PRIMARY KEY (id),
        KEY status (status),
        KEY city (city),
        KEY service (service),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Business Stats Table
    $table_stats = $wpdb->prefix . 'pt24_business_stats';
    $sql_stats = "CREATE TABLE IF NOT EXISTS $table_stats (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        business_id bigint(20) UNSIGNED NOT NULL,
        date date NOT NULL,
        views int(11) DEFAULT 0,
        phone_clicks int(11) DEFAULT 0,
        email_clicks int(11) DEFAULT 0,
        leads int(11) DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY business_date (business_id, date),
        KEY business_id (business_id),
        KEY date (date)
    ) $charset_collate;";

    // Subscriptions Table
    $table_subscriptions = $wpdb->prefix . 'pt24_subscriptions';
    $sql_subscriptions = "CREATE TABLE IF NOT EXISTS $table_subscriptions (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        business_id bigint(20) UNSIGNED NOT NULL,
        plan varchar(20) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        started_at datetime NOT NULL,
        expires_at datetime,
        amount decimal(10,2),
        currency varchar(3) DEFAULT 'PLN',
        stripe_subscription_id varchar(100),
        PRIMARY KEY (id),
        KEY business_id (business_id),
        KEY status (status)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_leads);
    dbDelta($sql_stats);
    dbDelta($sql_subscriptions);
}

// Create tables on plugin activation
register_activation_hook(__FILE__, 'pt24_create_database_tables');

// Also create tables on theme switch (for mu-plugins)
add_action('after_switch_theme', 'pt24_create_database_tables');

/**
 * Initialize default service categories and cities
 */
function pt24_initialize_default_data() {
    // Check if already initialized
    if (get_option('pt24_data_initialized')) {
        return;
    }

    // Add default service categories
    $categories = ['mechanik', 'hydraulik', 'elektryk', 'laweta', 'wulkanizacja'];
    foreach ($categories as $cat) {
        if (!term_exists($cat, 'pt24_service_cat')) {
            wp_insert_term($cat, 'pt24_service_cat');
        }
    }

    // Add top 20 cities
    $cities = [
        'warszawa', 'krakow', 'lodz', 'wroclaw', 'poznan',
        'gdansk', 'szczecin', 'bydgoszcz', 'lublin', 'katowice',
        'bialystok', 'gdynia', 'czestochowa', 'radom', 'sosnowiec',
        'torun', 'kielce', 'rzeszow', 'gliwice', 'zabrze'
    ];

    foreach ($cities as $city) {
        if (!term_exists($city, 'pt24_city')) {
            wp_insert_term(ucfirst($city), 'pt24_city', ['slug' => $city]);
        }
    }

    update_option('pt24_data_initialized', true);
}
add_action('init', 'pt24_initialize_default_data', 99);

/**
 * Flush rewrite rules on activation
 */
function pt24_flush_rewrite_rules() {
    pt24_register_service_category_cpt();
    pt24_register_local_page_cpt();
    pt24_register_business_cpt();
    pt24_register_service_cpt();
    pt24_register_city_taxonomy();
    pt24_register_service_category_taxonomy();
    pt24_register_region_taxonomy();
    pt24_custom_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'pt24_flush_rewrite_rules');
