<?php
/**
 * Database Migration for Admin Panel v7.0
 *
 * Creates new tables for revenue tracking, lead management, and expert profiles.
 *
 * @package PearBlogEngine\Admin
 * @since 7.1.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Handle database schema migrations for v7.0 features.
 */
class DatabaseMigration {

	/**
	 * Run all migrations for v7.0.
	 */
	public static function migrate_to_v7(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Create revenue tracking table
		$sql_revenue = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pb_revenue (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT UNSIGNED NOT NULL,
			revenue_date DATE NOT NULL,
			revenue_source VARCHAR(50) NOT NULL COMMENT 'adsense, affiliate, sponsored',
			revenue_amount DECIMAL(10,2) NOT NULL,
			currency VARCHAR(3) DEFAULT 'USD',
			views INT UNSIGNED DEFAULT 0,
			clicks INT UNSIGNED DEFAULT 0,
			rpm DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Revenue Per 1000 views',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX post_date_idx (post_id, revenue_date),
			INDEX source_idx (revenue_source),
			INDEX date_idx (revenue_date)
		) $charset_collate;";

		// Create leads table
		$sql_leads = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pb_leads (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			email VARCHAR(255) NOT NULL,
			phone VARCHAR(50),
			company VARCHAR(255),
			message TEXT,
			source VARCHAR(100) COMMENT 'contact_form, cta_button, popup',
			post_id BIGINT UNSIGNED COMMENT 'Article that generated the lead',
			category_id BIGINT UNSIGNED COMMENT 'Topic category for routing',
			status VARCHAR(50) DEFAULT 'new' COMMENT 'new, contacted, qualified, converted, closed',
			assigned_expert_id BIGINT UNSIGNED COMMENT 'Expert assigned to this lead',
			priority VARCHAR(20) DEFAULT 'medium' COMMENT 'low, medium, high, urgent',
			notes TEXT,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			contacted_at DATETIME NULL,
			converted_at DATETIME NULL,
			INDEX email_idx (email),
			INDEX status_idx (status),
			INDEX expert_idx (assigned_expert_id),
			INDEX post_idx (post_id),
			INDEX category_idx (category_id),
			INDEX created_idx (created_at)
		) $charset_collate;";

		// Create experts table
		$sql_experts = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pb_experts (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL COMMENT 'WordPress user ID',
			name VARCHAR(255) NOT NULL,
			email VARCHAR(255) NOT NULL,
			phone VARCHAR(50),
			bio TEXT,
			specialties TEXT COMMENT 'JSON array of expertise areas',
			category_ids TEXT COMMENT 'JSON array of category IDs for routing',
			max_leads_per_day INT DEFAULT 10,
			availability VARCHAR(20) DEFAULT 'available' COMMENT 'available, busy, offline',
			rating DECIMAL(3,2) DEFAULT 0.00 COMMENT '0.00 to 5.00',
			total_leads INT DEFAULT 0,
			converted_leads INT DEFAULT 0,
			conversion_rate DECIMAL(5,2) DEFAULT 0.00,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY user_idx (user_id),
			INDEX email_idx (email),
			INDEX availability_idx (availability)
		) $charset_collate;";

		// Execute table creation
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_revenue );
		dbDelta( $sql_leads );
		dbDelta( $sql_experts );

		// Mark migration as complete
		update_option( 'pearblog_admin_v7_migrated', true );
		update_option( 'pearblog_admin_v7_migration_date', current_time( 'mysql' ) );
	}

	/**
	 * Check if v7 migration has been run.
	 *
	 * @return bool True if migrated, false otherwise.
	 */
	public static function is_migrated(): bool {
		return (bool) get_option( 'pearblog_admin_v7_migrated', false );
	}

	/**
	 * Rollback v7 migration (drop tables).
	 *
	 * WARNING: This will delete all data in v7 tables!
	 */
	public static function rollback_v7(): void {
		global $wpdb;

		// Drop tables using prepared statements for security best practices
		$table_revenue = $wpdb->prefix . 'pb_revenue';
		$table_leads   = $wpdb->prefix . 'pb_leads';
		$table_experts = $wpdb->prefix . 'pb_experts';

		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_revenue ) );
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_leads ) );
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_experts ) );

		// Remove migration flag
		delete_option( 'pearblog_admin_v7_migrated' );
		delete_option( 'pearblog_admin_v7_migration_date' );
	}

	/**
	 * Get migration status info.
	 *
	 * @return array Migration status details.
	 */
	public static function get_migration_status(): array {
		global $wpdb;

		$is_migrated = self::is_migrated();
		$migration_date = get_option( 'pearblog_admin_v7_migration_date', null );

		$tables_exist = [
			'revenue' => $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}pb_revenue'" ) === "{$wpdb->prefix}pb_revenue",
			'leads'   => $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}pb_leads'" ) === "{$wpdb->prefix}pb_leads",
			'experts' => $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}pb_experts'" ) === "{$wpdb->prefix}pb_experts",
		];

		$row_counts = [];
		if ( $tables_exist['revenue'] ) {
			$row_counts['revenue'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pb_revenue" );
		}
		if ( $tables_exist['leads'] ) {
			$row_counts['leads'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pb_leads" );
		}
		if ( $tables_exist['experts'] ) {
			$row_counts['experts'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pb_experts" );
		}

		return [
			'is_migrated'    => $is_migrated,
			'migration_date' => $migration_date,
			'tables_exist'   => $tables_exist,
			'row_counts'     => $row_counts,
			'all_tables_ok'  => count( array_filter( $tables_exist ) ) === 3,
		];
	}

	/**
	 * Seed initial data for testing/demo purposes.
	 */
	public static function seed_demo_data(): void {
		global $wpdb;

		if ( ! self::is_migrated() ) {
			return;
		}

		// Seed demo revenue data (last 30 days)
		$posts = get_posts( [
			'numberposts' => 10,
			'post_type'   => 'post',
			'post_status' => 'publish',
		] );

		foreach ( $posts as $post ) {
			for ( $i = 0; $i < 30; $i++ ) {
				$date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
				$revenue = rand( 50, 500 ) / 100; // $0.50 to $5.00
				$views = rand( 100, 1000 );
				$rpm = $views > 0 ? ( $revenue / $views ) * 1000 : 0;

				$wpdb->insert(
					$wpdb->prefix . 'pb_revenue',
					[
						'post_id'        => $post->ID,
						'revenue_date'   => $date,
						'revenue_source' => rand( 0, 1 ) ? 'adsense' : 'affiliate',
						'revenue_amount' => $revenue,
						'currency'       => 'USD',
						'views'          => $views,
						'rpm'            => $rpm,
						'created_at'     => current_time( 'mysql' ),
					],
					[ '%d', '%s', '%s', '%f', '%s', '%d', '%f', '%s' ]
				);
			}
		}

		// Seed demo expert (current admin user)
		$current_user = wp_get_current_user();
		if ( $current_user->ID ) {
			$wpdb->insert(
				$wpdb->prefix . 'pb_experts',
				[
					'user_id'           => $current_user->ID,
					'name'              => $current_user->display_name,
					'email'             => $current_user->user_email,
					'bio'               => 'Demo expert profile',
					'specialties'       => wp_json_encode( [ 'General', 'Consulting' ] ),
					'category_ids'      => wp_json_encode( [ 1 ] ),
					'max_leads_per_day' => 10,
					'availability'      => 'available',
					'created_at'        => current_time( 'mysql' ),
				],
				[ '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ]
			);
		}

		update_option( 'pearblog_admin_v7_demo_seeded', true );
	}
}
