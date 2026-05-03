<?php
/**
 * Poradnik Engine V2 Database Schema
 *
 * Creates and manages the database tables for the Poradnik revenue optimization system.
 *
 * @package PearBlog\Database
 */

namespace PearBlog\Database;

/**
 * Class PoradnikSchema
 *
 * Manages database schema for Poradnik Engine V2.
 */
class PoradnikSchema {
	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Create all Poradnik Engine V2 tables.
	 *
	 * @return array Array of results for each table creation.
	 */
	public function create_tables(): array {
		$results = array();

		$results['articles']      = $this->create_articles_table();
		$results['article_stats'] = $this->create_article_stats_table();
		$results['service_data']  = $this->create_service_data_table();
		$results['events']        = $this->create_events_table();
		$results['ab_tests']      = $this->create_ab_tests_table();

		return $results;
	}

	/**
	 * Create articles table.
	 *
	 * Stores generated articles and their metadata.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function create_articles_table(): bool {
		$table_name      = $this->wpdb->prefix . 'pearblog_articles';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED DEFAULT NULL,
			slug VARCHAR(255) NOT NULL,
			topic VARCHAR(255) NOT NULL,
			city VARCHAR(100) DEFAULT NULL,
			service VARCHAR(100) DEFAULT NULL,
			status ENUM('draft', 'review', 'published', 'archived') DEFAULT 'draft',
			variant VARCHAR(10) DEFAULT 'original',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY slug (slug),
			KEY topic (topic),
			KEY city (city),
			KEY service (service),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $this->table_exists( $table_name );
	}

	/**
	 * Create article_stats table.
	 *
	 * Stores daily performance metrics for each article.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function create_article_stats_table(): bool {
		$table_name      = $this->wpdb->prefix . 'pearblog_article_stats';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			article_id BIGINT UNSIGNED NOT NULL,
			date DATE NOT NULL,
			views INT UNSIGNED DEFAULT 0,
			unique_visitors INT UNSIGNED DEFAULT 0,
			avg_time_seconds INT UNSIGNED DEFAULT 0,
			scroll_depth_avg DECIMAL(5,2) DEFAULT 0.00,
			bounce_rate DECIMAL(5,2) DEFAULT 0.00,
			cta_clicks INT UNSIGNED DEFAULT 0,
			cta_ctr DECIMAL(5,4) DEFAULT 0.0000,
			leads INT UNSIGNED DEFAULT 0,
			lead_conversion_rate DECIMAL(5,4) DEFAULT 0.0000,
			revenue DECIMAL(10,2) DEFAULT 0.00,
			seo_impressions INT UNSIGNED DEFAULT 0,
			seo_clicks INT UNSIGNED DEFAULT 0,
			seo_ctr DECIMAL(5,4) DEFAULT 0.0000,
			seo_position_avg DECIMAL(5,2) DEFAULT 0.00,
			score DECIMAL(5,2) DEFAULT 0.00,
			score_category ENUM('SCALE', 'BOOST', 'OPTIMIZE', 'DELETE') DEFAULT NULL,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY article_date (article_id, date),
			KEY article_id (article_id),
			KEY date (date),
			KEY score (score),
			KEY score_category (score_category),
			KEY revenue (revenue),
			KEY views (views)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $this->table_exists( $table_name );
	}

	/**
	 * Create service_data table.
	 *
	 * Stores scraped market data for content generation.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function create_service_data_table(): bool {
		$table_name      = $this->wpdb->prefix . 'pearblog_service_data';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			service VARCHAR(100) NOT NULL,
			city VARCHAR(100) NOT NULL,
			price_min DECIMAL(10,2) DEFAULT NULL,
			price_max DECIMAL(10,2) DEFAULT NULL,
			price_avg DECIMAL(10,2) DEFAULT NULL,
			currency VARCHAR(3) DEFAULT 'PLN',
			services_json TEXT DEFAULT NULL,
			providers_count INT UNSIGNED DEFAULT 0,
			faq_json TEXT DEFAULT NULL,
			data_source VARCHAR(255) DEFAULT NULL,
			scraped_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY service_city (service, city),
			KEY service (service),
			KEY city (city),
			KEY updated_at (updated_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $this->table_exists( $table_name );
	}

	/**
	 * Create events table.
	 *
	 * Tracks all user interactions with articles.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function create_events_table(): bool {
		$table_name      = $this->wpdb->prefix . 'pearblog_events';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_type ENUM('view', 'scroll', 'cta_click', 'lead', 'revenue') NOT NULL,
			article_id BIGINT UNSIGNED DEFAULT NULL,
			post_id BIGINT UNSIGNED DEFAULT NULL,
			user_id BIGINT UNSIGNED DEFAULT NULL,
			session_id VARCHAR(64) DEFAULT NULL,
			ip_hash VARCHAR(64) DEFAULT NULL,
			event_data JSON DEFAULT NULL,
			referrer TEXT DEFAULT NULL,
			utm_source VARCHAR(100) DEFAULT NULL,
			utm_medium VARCHAR(100) DEFAULT NULL,
			utm_campaign VARCHAR(100) DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY event_type (event_type),
			KEY article_id (article_id),
			KEY post_id (post_id),
			KEY session_id (session_id),
			KEY created_at (created_at),
			KEY utm_source (utm_source)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $this->table_exists( $table_name );
	}

	/**
	 * Create ab_tests table.
	 *
	 * Manages A/B testing experiments for content optimization.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function create_ab_tests_table(): bool {
		$table_name      = $this->wpdb->prefix . 'pearblog_ab_tests';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			article_id BIGINT UNSIGNED NOT NULL,
			test_name VARCHAR(255) NOT NULL,
			variant_a TEXT NOT NULL,
			variant_b TEXT NOT NULL,
			variant_a_views INT UNSIGNED DEFAULT 0,
			variant_a_conversions INT UNSIGNED DEFAULT 0,
			variant_b_views INT UNSIGNED DEFAULT 0,
			variant_b_conversions INT UNSIGNED DEFAULT 0,
			status ENUM('running', 'completed', 'paused') DEFAULT 'running',
			winner ENUM('a', 'b', 'inconclusive') DEFAULT NULL,
			started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY article_id (article_id),
			KEY status (status),
			KEY started_at (started_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return $this->table_exists( $table_name );
	}

	/**
	 * Check if a table exists.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @return bool True if table exists, false otherwise.
	 */
	private function table_exists( string $table_name ): bool {
		$query = $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		return $this->wpdb->get_var( $query ) === $table_name;
	}

	/**
	 * Drop all Poradnik Engine V2 tables.
	 *
	 * WARNING: This will permanently delete all data!
	 *
	 * @return array Array of results for each table deletion.
	 */
	public function drop_tables(): array {
		$results = array();
		$tables  = array(
			'pearblog_ab_tests',
			'pearblog_events',
			'pearblog_article_stats',
			'pearblog_service_data',
			'pearblog_articles',
		);

		foreach ( $tables as $table ) {
			$table_name       = $this->wpdb->prefix . $table;
			$results[ $table ] = $this->wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		}

		return $results;
	}

	/**
	 * Get schema version.
	 *
	 * @return string Schema version.
	 */
	public function get_version(): string {
		return '2.0.0';
	}
}
