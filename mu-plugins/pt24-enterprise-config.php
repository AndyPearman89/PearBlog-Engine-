<?php
/**
 * PT24.PRO — Enterprise Configuration & Integration Hub
 *
 * Final integration layer for PearBlog Engine v8 + PT24 Platform v2 on pt24.pro
 * Status: PRODUCTION READY
 * Version: 1.0.0
 *
 * This plugin serves as the central configuration point for:
 * - Enterprise V8 Admin Dashboard
 * - PT24 LeadAI System
 * - Content-to-Landing Linking
 * - Multi-tenant Architecture
 * - API Integrations
 * - Analytics & Monitoring
 *
 * @package PearBlog\PT24Enterprise
 * @since   1.0.0
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ==========================================
 * PT24.PRO ENTERPRISE CONFIGURATION
 * ==========================================
 */

// === ENVIRONMENT DETECTION ===
define( 'PT24_ENVIRONMENT', wp_get_environment_type() ); // production, staging, development
define( 'PT24_DOMAIN', $_SERVER['HTTP_HOST'] ?? 'pt24.pro' );
define( 'PT24_IS_PRODUCTION', in_array( PT24_ENVIRONMENT, [ 'production', 'staging' ], true ) );

// === PEARBLOG ENGINE SETTINGS ===
if ( ! defined( 'PEARBLOG_ADMIN_VERSION' ) ) {
	define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
}

// === PT24 CORE CONFIGURATION ===
define( 'PT24_PLATFORM_VERSION', '2.0.0' );
define( 'PT24_API_VERSION', 'v1' );
define( 'PT24_CONFIG_VERSION', '1.0.0' );

// === FEATURE FLAGS ===
define( 'PT24_ENABLE_LEADAI', true );           // Enable LeadAI Lead Management
define( 'PT24_ENABLE_CONTENT_LINKING', true );  // Enable content-to-landing links
define( 'PT24_ENABLE_ANALYTICS', true );        // Enable real-time analytics
define( 'PT24_ENABLE_MULTISITE', true );        // Enable multisite mode
define( 'PT24_ENABLE_CDN', true );              // Enable CDN integration
define( 'PT24_ENABLE_CACHE', true );            // Enable advanced caching

// === API CONFIGURATION ===
define( 'PT24_OPENAI_MODEL', getenv( 'OPENAI_MODEL' ) ?: 'gpt-4o-mini' );
define( 'PT24_OPENAI_TIMEOUT', 60 );
define( 'PT24_OPENAI_MAX_TOKENS', 4096 );

// === DATABASE CONFIGURATION ===
define( 'PT24_TABLE_CONTENT_META', $GLOBALS['wpdb']->prefix . 'pearblog_content_meta' );
define( 'PT24_TABLE_CONTENT_LINKS', $GLOBALS['wpdb']->prefix . 'pearblog_content_links' );
define( 'PT24_TABLE_LEAD_ATTRIBUTION', $GLOBALS['wpdb']->prefix . 'pearblog_lead_attribution' );
define( 'PT24_TABLE_ANALYTICS', $GLOBALS['wpdb']->prefix . 'pt24_analytics' );

// === CACHE CONFIGURATION ===
define( 'PT24_CACHE_TTL', 3600 ); // 1 hour
define( 'PT24_CACHE_PREFIX', 'pt24_' );

// === LEAD SYSTEM CONFIGURATION ===
define( 'PT24_LEADAI_ENABLED', true );
define( 'PT24_LEADAI_QUEUE_ENABLED', true );
define( 'PT24_LEADAI_BATCH_SIZE', 10 );
define( 'PT24_SMSAPI_ENABLED', true );
define( 'PT24_EMAIL_ENABLED', true );

// === CONTENT SEEDING ===
define( 'PT24_AUTO_SEED_ENABLED', false ); // Disable auto-seeding in production
define( 'PT24_SEED_BATCH_SIZE', 5 );
define( 'PT24_SEED_DELAY_SECONDS', 2 );

// === SECURITY CONFIGURATION ===
define( 'PT24_ENABLE_SECURITY_AUDIT', true );
define( 'PT24_ENABLE_WAF', PT24_IS_PRODUCTION );
define( 'PT24_ENABLE_RATE_LIMITING', true );
define( 'PT24_RATE_LIMIT_REQUESTS', 100 );
define( 'PT24_RATE_LIMIT_WINDOW', 3600 ); // 1 hour

// === MULTISITE CONFIGURATION ===
define( 'PT24_MULTISITE_ENABLED', true );
define( 'PT24_MAIN_SITE_ID', 1 );
define( 'PT24_SITES_SYNC_ENABLED', true );

/**
 * ==========================================
 * HOOKS & INITIALIZATION
 * ==========================================
 */

/**
 * Initialize PT24 Enterprise on plugins_loaded
 */
add_action( 'plugins_loaded', function () {
	// Initialize all subsystems
	do_action( 'pt24_init' );
	do_action( 'pt24_api_init' );
	do_action( 'pt24_leadai_init' );
	do_action( 'pt24_analytics_init' );
	
	// Register REST namespaces
	register_pt24_rest_namespaces();
	
	// Initialize admin hooks
	if ( is_admin() ) {
		add_action( 'admin_init', 'pt24_admin_init' );
		add_action( 'admin_enqueue_scripts', 'pt24_admin_enqueue_scripts' );
	}
}, 5 );

/**
 * Register REST API namespaces
 */
function register_pt24_rest_namespaces() {
	register_rest_route( 'pt24/v1', '/health', [
		'methods'             => 'GET',
		'callback'            => 'pt24_health_check',
		'permission_callback' => 'pt24_rest_permission_check',
	] );

	register_rest_route( 'pt24/v1', '/config', [
		'methods'             => 'GET',
		'callback'            => 'pt24_get_config',
		'permission_callback' => 'pt24_rest_admin_permission',
	] );

	register_rest_route( 'pt24/v1', '/dashboard/stats', [
		'methods'             => 'GET',
		'callback'            => 'pt24_get_dashboard_stats',
		'permission_callback' => 'pt24_rest_permission_check',
	] );
}

/**
 * PT24 Health Check Endpoint
 */
function pt24_health_check( \WP_REST_Request $request ) {
	global $wpdb;

	$health = [
		'status'      => 'ok',
		'version'     => PT24_PLATFORM_VERSION,
		'environment' => PT24_ENVIRONMENT,
		'timestamp'   => current_time( 'mysql' ),
		'checks'      => [],
	];

	// Check database
	$db_ok = $wpdb->get_var( 'SELECT 1' );
	$health['checks']['database'] = $db_ok ? 'ok' : 'failed';

	// Check file permissions
	$upload_dir = wp_upload_dir();
	$health['checks']['uploads_writable'] = is_writable( $upload_dir['basedir'] ) ? 'ok' : 'failed';

	// Check plugin status
	$health['checks']['pearblog_active'] = is_plugin_active( 'pearblog-engine/pearblog-engine.php' ) ? 'ok' : 'failed';

	// Check API connectivity (if production)
	if ( PT24_IS_PRODUCTION ) {
		$openai_key = getenv( 'OPENAI_API_KEY' );
		$health['checks']['openai_configured'] = ! empty( $openai_key ) ? 'ok' : 'failed';
	}

	// Overall status
	$has_failures = in_array( 'failed', $health['checks'], true );
	$health['status'] = $has_failures ? 'degraded' : 'ok';

	return rest_ensure_response( $health );
}

/**
 * Get Enterprise Configuration (admin only)
 */
function pt24_get_config( \WP_REST_Request $request ) {
	return rest_ensure_response( [
		'platform_version'   => PT24_PLATFORM_VERSION,
		'api_version'        => PT24_API_VERSION,
		'environment'        => PT24_ENVIRONMENT,
		'domain'             => PT24_DOMAIN,
		'features'           => [
			'leadai'           => PT24_ENABLE_LEADAI,
			'content_linking'  => PT24_ENABLE_CONTENT_LINKING,
			'analytics'        => PT24_ENABLE_ANALYTICS,
			'multisite'        => PT24_ENABLE_MULTISITE,
			'cdn'              => PT24_ENABLE_CDN,
			'cache'            => PT24_ENABLE_CACHE,
		],
		'tables'             => [
			'content_meta'     => PT24_TABLE_CONTENT_META,
			'content_links'    => PT24_TABLE_CONTENT_LINKS,
			'lead_attribution' => PT24_TABLE_LEAD_ATTRIBUTION,
			'analytics'        => PT24_TABLE_ANALYTICS,
		],
	] );
}

/**
 * Get Dashboard Statistics
 */
function pt24_get_dashboard_stats( \WP_REST_Request $request ) {
	global $wpdb;

	$stats = [
		'total_content'     => (int) $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish'" ),
		'total_landings'    => (int) $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'pt24_landing' AND post_status = 'publish'" ),
		'total_leads_30d'   => (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}poradnik_leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" ),
		'engagement_rate'   => get_option( 'pt24_engagement_rate', 0 ),
		'revenue_30d'       => get_option( 'pt24_revenue_30d', 0 ),
	];

	return rest_ensure_response( $stats );
}

/**
 * REST API Permission Checks
 */
function pt24_rest_permission_check( \WP_REST_Request $request ) {
	// Allow public access to health check, but verify API key for sensitive endpoints
	$endpoint = $request->get_route();
	
	if ( '/pt24/v1/health' === $endpoint ) {
		return true;
	}

	// Require authentication for other endpoints
	return current_user_can( 'manage_options' );
}

/**
 * REST API Admin Permission Check
 */
function pt24_rest_admin_permission( \WP_REST_Request $request ) {
	return current_user_can( 'manage_options' );
}

/**
 * Admin Initialization
 */
function pt24_admin_init() {
	// Register settings
	register_setting( 'pt24_options', 'pt24_leadai_config' );
	register_setting( 'pt24_options', 'pt24_content_linking_config' );
	register_setting( 'pt24_options', 'pt24_analytics_config' );

	// Add admin notices for critical issues
	pt24_check_admin_requirements();
}

/**
 * Admin Scripts & Styles
 */
function pt24_admin_enqueue_scripts( $hook ) {
	if ( ! isset( $_GET['page'] ) || 'pearblog-enterprise-v8' !== $_GET['page'] ) {
		return;
	}

	// Dashboard styles
	wp_enqueue_style( 'pt24-enterprise-dashboard', plugins_url( 'assets/css/enterprise-dashboard.css', __FILE__ ) );

	// Dashboard scripts
	wp_enqueue_script( 'pt24-enterprise-dashboard', plugins_url( 'assets/js/enterprise-dashboard.js', __FILE__ ), [ 'jquery', 'wp-api' ], '1.0.0', true );

	// Localize data
	wp_localize_script( 'pt24-enterprise-dashboard', 'pt24Config', [
		'apiUrl'  => rest_url( 'pt24/v1/' ),
		'nonce'   => wp_create_nonce( 'pt24_api' ),
		'version' => PT24_PLATFORM_VERSION,
	] );
}

/**
 * Check Admin Requirements
 */
function pt24_check_admin_requirements() {
	// Check PHP version
	if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
		add_action( 'admin_notices', function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'PT24 Enterprise requires PHP 8.1 or higher.', 'pt24-enterprise' )
			);
		} );
	}

	// Check required plugins
	$required_plugins = [ 'pearblog-engine/pearblog-engine.php' ];
	foreach ( $required_plugins as $plugin ) {
		if ( ! is_plugin_active( $plugin ) ) {
			add_action( 'admin_notices', function () use ( $plugin ) {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					sprintf(
						esc_html__( 'PT24 Enterprise requires %s to be activated.', 'pt24-enterprise' ),
						esc_html( $plugin )
					)
				);
			} );
		}
	}

	// Check database tables
	pt24_check_database_tables();
}

/**
 * Check & Create Database Tables
 */
function pt24_check_database_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// Content Meta Table
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', PT24_TABLE_CONTENT_META ) ) !== PT24_TABLE_CONTENT_META ) {
		$sql = "CREATE TABLE IF NOT EXISTS " . PT24_TABLE_CONTENT_META . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT UNSIGNED NOT NULL,
			content_type VARCHAR(50),
			category_id VARCHAR(50),
			city_id VARCHAR(50),
			seo_score INT DEFAULT 0,
			traffic_estimate INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			KEY idx_content_type (content_type),
			KEY idx_category_city (category_id, city_id),
			KEY idx_post (post_id),
			$charset_collate
		);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	// Content Links Table
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', PT24_TABLE_CONTENT_LINKS ) ) !== PT24_TABLE_CONTENT_LINKS ) {
		$sql = "CREATE TABLE IF NOT EXISTS " . PT24_TABLE_CONTENT_LINKS . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			content_id BIGINT UNSIGNED NOT NULL,
			target_type VARCHAR(50),
			target_id VARCHAR(100),
			link_text VARCHAR(255),
			link_context TEXT,
			position VARCHAR(50),
			click_count INT DEFAULT 0,
			conversion_count INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			KEY idx_content (content_id),
			KEY idx_target (target_type, target_id),
			$charset_collate
		);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	// Lead Attribution Table
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', PT24_TABLE_LEAD_ATTRIBUTION ) ) !== PT24_TABLE_LEAD_ATTRIBUTION ) {
		$sql = "CREATE TABLE IF NOT EXISTS " . PT24_TABLE_LEAD_ATTRIBUTION . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			lead_id BIGINT UNSIGNED NOT NULL,
			source_content_id BIGINT UNSIGNED,
			source_landing_id BIGINT UNSIGNED,
			listing_id BIGINT UNSIGNED,
			funnel_stage VARCHAR(50),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			KEY idx_lead (lead_id),
			KEY idx_source_content (source_content_id),
			$charset_collate
		);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	// Analytics Table
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', PT24_TABLE_ANALYTICS ) ) !== PT24_TABLE_ANALYTICS ) {
		$sql = "CREATE TABLE IF NOT EXISTS " . PT24_TABLE_ANALYTICS . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			event_type VARCHAR(50),
			post_id BIGINT UNSIGNED,
			event_data JSON,
			user_agent TEXT,
			ip_address VARCHAR(45),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			KEY idx_event_type (event_type),
			KEY idx_post (post_id),
			KEY idx_created_at (created_at),
			$charset_collate
		);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

/**
 * Get PT24 Configuration Array
 *
 * @return array
 */
function pt24_get_full_config() {
	return [
		'platform'  => [
			'version'     => PT24_PLATFORM_VERSION,
			'environment' => PT24_ENVIRONMENT,
			'domain'      => PT24_DOMAIN,
			'is_prod'     => PT24_IS_PRODUCTION,
		],
		'pearblog'  => [
			'version'      => PEARBLOG_ENGINE_VERSION ?? '9.0.0',
			'admin_ui'     => PEARBLOG_ADMIN_VERSION,
			'is_installed' => is_plugin_active( 'pearblog-engine/pearblog-engine.php' ),
		],
		'features'  => [
			'leadai'          => PT24_ENABLE_LEADAI,
			'content_linking' => PT24_ENABLE_CONTENT_LINKING,
			'analytics'       => PT24_ENABLE_ANALYTICS,
			'multisite'       => PT24_ENABLE_MULTISITE,
			'cdn'             => PT24_ENABLE_CDN,
			'cache'           => PT24_ENABLE_CACHE,
		],
		'api'       => [
			'openai_model'  => PT24_OPENAI_MODEL,
			'openai_timeout' => PT24_OPENAI_TIMEOUT,
			'max_tokens'    => PT24_OPENAI_MAX_TOKENS,
		],
		'database'  => [
			'content_meta'     => PT24_TABLE_CONTENT_META,
			'content_links'    => PT24_TABLE_CONTENT_LINKS,
			'lead_attribution' => PT24_TABLE_LEAD_ATTRIBUTION,
			'analytics'        => PT24_TABLE_ANALYTICS,
		],
		'cache'     => [
			'ttl'    => PT24_CACHE_TTL,
			'prefix' => PT24_CACHE_PREFIX,
		],
		'security'  => [
			'audit_enabled'      => PT24_ENABLE_SECURITY_AUDIT,
			'waf_enabled'        => PT24_ENABLE_WAF,
			'rate_limiting'      => PT24_ENABLE_RATE_LIMITING,
			'rate_limit_window'  => PT24_RATE_LIMIT_WINDOW,
		],
	];
}

// Expose configuration to other plugins via filter
add_filter( 'pt24_full_config', 'pt24_get_full_config' );

/**
 * Log PT24 Actions
 *
 * @param string $action
 * @param array  $data
 */
function pt24_log( $action, $data = [] ) {
	if ( ! PT24_IS_PRODUCTION ) {
		error_log(
			sprintf(
				'[PT24 %s] %s: %s',
				PT24_PLATFORM_VERSION,
				$action,
				wp_json_encode( $data )
			)
		);
	}
}

/**
 * Plugin Activation Hook
 */
register_activation_hook( __FILE__, function () {
	pt24_check_database_tables();
	update_option( 'pt24_activated_at', current_time( 'mysql' ) );
	update_option( 'pt24_version', PT24_CONFIG_VERSION );
	pt24_log( 'ACTIVATED', [ 'version' => PT24_CONFIG_VERSION ] );
} );

/**
 * Plugin Deactivation Hook
 */
register_deactivation_hook( __FILE__, function () {
	update_option( 'pt24_deactivated_at', current_time( 'mysql' ) );
	pt24_log( 'DEACTIVATED', [ 'version' => PT24_CONFIG_VERSION ] );
} );
