<?php
/**
 * PT24 Database Setup
 *
 * Creates and manages database tables for PT24.PRO platform
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create PT24 database tables
 *
 * Guarded against redeclaration: the pt24-local-services mu-plugin may define a
 * function with the same name and loads before the theme.
 */
if ( ! function_exists( 'pt24_create_database_tables' ) ) {
function pt24_create_database_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Leads table
    $leads_table = $wpdb->prefix . 'pt24_leads';

    $leads_sql = "CREATE TABLE IF NOT EXISTS $leads_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) DEFAULT NULL,
        phone varchar(50) NOT NULL,
        city varchar(100) NOT NULL,
        service varchar(100) NOT NULL,
        message text,
        source varchar(500),
        status varchar(50) DEFAULT 'new',
        created_at datetime NOT NULL,
        updated_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY status (status),
        KEY service (service),
        KEY city (city),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Business stats table
    $stats_table = $wpdb->prefix . 'pt24_business_stats';

    $stats_sql = "CREATE TABLE IF NOT EXISTS $stats_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        business_id bigint(20) unsigned NOT NULL,
        stat_date date NOT NULL,
        profile_views int(11) DEFAULT 0,
        phone_clicks int(11) DEFAULT 0,
        email_clicks int(11) DEFAULT 0,
        website_clicks int(11) DEFAULT 0,
        PRIMARY KEY (id),
        KEY business_id (business_id),
        KEY stat_date (stat_date),
        UNIQUE KEY business_date (business_id, stat_date)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($leads_sql);
    dbDelta($stats_sql);

    // Store database version
    update_option('pt24_db_version', '1.0.0');
}
}

/**
 * Check and update PT24 database
 */
function pt24_check_database() {
    $current_version = get_option('pt24_db_version', '0.0.0');

    if (version_compare($current_version, '1.0.0', '<')) {
        pt24_create_database_tables();
    }
}

// Run database check on admin init
add_action('admin_init', 'pt24_check_database');

/**
 * Activation hook - create tables on plugin activation
 */
function pt24_activate() {
    pt24_create_database_tables();
    flush_rewrite_rules();
}

/**
 * Get lead statistics
 */
function pt24_get_lead_stats($days = 30) {
    global $wpdb;
    $table = $wpdb->prefix . 'pt24_leads';

    $date_from = date('Y-m-d H:i:s', strtotime("-$days days"));

    $stats = array(
        'total' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_at >= %s",
            $date_from
        )),
        'new' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE status = 'new' AND created_at >= %s",
            $date_from
        )),
        'contacted' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE status = 'contacted' AND created_at >= %s",
            $date_from
        )),
        'by_service' => $wpdb->get_results($wpdb->prepare(
            "SELECT service, COUNT(*) as count FROM $table WHERE created_at >= %s GROUP BY service ORDER BY count DESC",
            $date_from
        )),
        'by_city' => $wpdb->get_results($wpdb->prepare(
            "SELECT city, COUNT(*) as count FROM $table WHERE created_at >= %s GROUP BY city ORDER BY count DESC LIMIT 10",
            $date_from
        ))
    );

    return $stats;
}

/**
 * Track business profile view
 */
function pt24_track_profile_view($business_id) {
    if (empty($business_id)) {
        return false;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pt24_business_stats';
    $today = date('Y-m-d');

    // Try to increment existing record
    $result = $wpdb->query($wpdb->prepare(
        "INSERT INTO $table (business_id, stat_date, profile_views)
         VALUES (%d, %s, 1)
         ON DUPLICATE KEY UPDATE profile_views = profile_views + 1",
        $business_id,
        $today
    ));

    return $result !== false;
}

/**
 * Track business contact click
 */
function pt24_track_contact_click($business_id, $type = 'phone') {
    if (empty($business_id)) {
        return false;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pt24_business_stats';
    $today = date('Y-m-d');

    $field = $type . '_clicks';

    $result = $wpdb->query($wpdb->prepare(
        "INSERT INTO $table (business_id, stat_date, $field)
         VALUES (%d, %s, 1)
         ON DUPLICATE KEY UPDATE $field = $field + 1",
        $business_id,
        $today
    ));

    return $result !== false;
}
