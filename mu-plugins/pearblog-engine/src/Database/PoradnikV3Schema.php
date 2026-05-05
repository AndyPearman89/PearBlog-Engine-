<?php
/**
 * Poradnik V3 Database Schema
 *
 * Creates tables for:
 * - Calculator submissions
 * - Conversion events tracking
 * - A/B test results
 *
 * @package PearBlogEngine\Database
 * @version 3.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Database;

/**
 * Poradnik V3 Schema Manager
 */
class PoradnikV3Schema {

	/**
	 * Create all V3 tables.
	 *
	 * @return array Results array (table => success).
	 */
	public static function create_tables(): array {
		$results = [];

		$results['calculator_submissions'] = self::create_calculator_submissions_table();
		$results['conversion_events'] = self::create_conversion_events_table();
		$results['ab_test_variants'] = self::create_ab_test_variants_table();

		return $results;
	}

	/**
	 * Create calculator submissions table.
	 *
	 * @return bool Success status.
	 */
	private static function create_calculator_submissions_table(): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pearblog_calculator_submissions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			service varchar(100) NOT NULL,
			metraz decimal(10,2) DEFAULT NULL,
			standard varchar(50) DEFAULT NULL,
			lokalizacja varchar(50) DEFAULT NULL,
			typ varchar(50) DEFAULT NULL,
			min_cost decimal(12,2) NOT NULL,
			max_cost decimal(12,2) NOT NULL,
			avg_cost decimal(12,2) NOT NULL,
			cost_per_unit decimal(12,2) NOT NULL,
			session_id varchar(100) DEFAULT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			converted_to_lead tinyint(1) DEFAULT 0,
			submitted_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY service (service),
			KEY submitted_at (submitted_at),
			KEY session_id (session_id),
			KEY converted_to_lead (converted_to_lead)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Create conversion events table.
	 *
	 * @return bool Success status.
	 */
	private static function create_conversion_events_table(): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pearblog_conversion_events';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id varchar(100) NOT NULL,
			event_type varchar(50) NOT NULL COMMENT 'page_view, calculator_use, form_view, form_submit, cta_click',
			event_data text DEFAULT NULL COMMENT 'JSON data',
			page_url varchar(500) DEFAULT NULL,
			service varchar(100) DEFAULT NULL,
			ab_variant varchar(50) DEFAULT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			referrer varchar(500) DEFAULT NULL,
			utm_source varchar(100) DEFAULT NULL,
			utm_medium varchar(100) DEFAULT NULL,
			utm_campaign varchar(100) DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY session_id (session_id),
			KEY event_type (event_type),
			KEY service (service),
			KEY ab_variant (ab_variant),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Create A/B test variants table.
	 *
	 * @return bool Success status.
	 */
	private static function create_ab_test_variants_table(): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pearblog_ab_test_variants';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			test_name varchar(100) NOT NULL,
			variant_name varchar(50) NOT NULL,
			service varchar(100) DEFAULT NULL,
			config text DEFAULT NULL COMMENT 'JSON config',
			traffic_allocation int(3) DEFAULT 50 COMMENT 'Percentage 0-100',
			views int(10) UNSIGNED DEFAULT 0,
			calculator_uses int(10) UNSIGNED DEFAULT 0,
			form_submissions int(10) UNSIGNED DEFAULT 0,
			leads int(10) UNSIGNED DEFAULT 0,
			conversion_rate decimal(5,2) DEFAULT 0.00,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY test_variant (test_name, variant_name),
			KEY service (service),
			KEY is_active (is_active)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Drop all V3 tables (for cleanup/testing).
	 *
	 * @return array Results array.
	 */
	public static function drop_tables(): array {
		global $wpdb;

		$tables = [
			'calculator_submissions',
			'conversion_events',
			'ab_test_variants',
		];

		$results = [];

		foreach ( $tables as $table_suffix ) {
			$table_name = $wpdb->prefix . 'pearblog_' . $table_suffix;
			$result = $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
			$results[ $table_suffix ] = ( false !== $result );
		}

		return $results;
	}

	/**
	 * Check if V3 tables exist.
	 *
	 * @return bool True if all tables exist.
	 */
	public static function tables_exist(): bool {
		global $wpdb;

		$required_tables = [
			'pearblog_calculator_submissions',
			'pearblog_conversion_events',
			'pearblog_ab_test_variants',
		];

		foreach ( $required_tables as $table_suffix ) {
			$table_name = $wpdb->prefix . $table_suffix;
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get database schema version.
	 *
	 * @return string Version string.
	 */
	public static function get_version(): string {
		return get_option( 'pearblog_v3_schema_version', '0.0.0' );
	}

	/**
	 * Update schema version.
	 *
	 * @param string $version Version string.
	 * @return bool Success status.
	 */
	public static function update_version( string $version ): bool {
		return update_option( 'pearblog_v3_schema_version', $version );
	}
}
