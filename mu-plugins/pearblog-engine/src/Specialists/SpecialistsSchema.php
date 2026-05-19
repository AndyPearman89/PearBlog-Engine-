<?php
/**
 * Specialists Database Schema
 *
 * Creates dedicated tables for the specialist marketplace,
 * supplementing the existing pearblog_expert CPT with relational
 * data that WP post-meta cannot efficiently support at scale.
 *
 * Tables created:
 *   {prefix}pearblog_specialists      — profile data + stats cache
 *   {prefix}pearblog_reviews          — reviews + ratings
 *   {prefix}pearblog_specialist_badges — badge assignments
 *
 * @package PearBlogEngine\Specialists
 */

declare(strict_types=1);

namespace PearBlogEngine\Specialists;

/**
 * SpecialistsSchema — DDL management for the marketplace.
 */
class SpecialistsSchema {

	private \wpdb $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Create all Specialists tables.
	 *
	 * Safe to call on every activation — uses IF NOT EXISTS.
	 *
	 * @return array<string, bool> Results per table name.
	 */
	public function create_tables(): array {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		return [
			'specialists' => $this->create_specialists_table(),
			'reviews'     => $this->create_reviews_table(),
			'badges'      => $this->create_badges_table(),
		];
	}

	// -----------------------------------------------------------------------
	// Table definitions
	// -----------------------------------------------------------------------

	private function create_specialists_table(): bool {
		$table   = $this->wpdb->prefix . 'pearblog_specialists';
		$charset = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			wp_post_id      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Linked pearblog_expert CPT ID',
			name            VARCHAR(200) NOT NULL,
			slug            VARCHAR(200) NOT NULL,
			category        VARCHAR(100) NOT NULL DEFAULT '',
			city            VARCHAR(100) NOT NULL DEFAULT '',
			region          VARCHAR(100) NOT NULL DEFAULT '',
			phone           VARCHAR(30)  NOT NULL DEFAULT '',
			email           VARCHAR(200) NOT NULL DEFAULT '',
			website         VARCHAR(500) NOT NULL DEFAULT '',
			bio             TEXT,
			avatar_url      VARCHAR(500) NOT NULL DEFAULT '',
			verification_level  VARCHAR(20)  NOT NULL DEFAULT 'none' COMMENT 'none|bronze|silver|gold',
			verification_at     DATETIME DEFAULT NULL,
			is_premium      TINYINT(1)   NOT NULL DEFAULT 0,
			is_active       TINYINT(1)   NOT NULL DEFAULT 1,
			avg_rating      DECIMAL(3,2) NOT NULL DEFAULT 0.00,
			review_count    INT UNSIGNED NOT NULL DEFAULT 0,
			response_rate   DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage 0-100',
			response_time   VARCHAR(50)  NOT NULL DEFAULT '' COMMENT 'e.g. \"< 1h\"',
			ranking_score   DECIMAL(6,2) NOT NULL DEFAULT 0.00,
			specialties     TEXT COMMENT 'JSON array of specialty strings',
			portfolio_urls  TEXT COMMENT 'JSON array of URLs',
			pricing_min     INT UNSIGNED DEFAULT NULL COMMENT 'Min price in PLN grosz',
			pricing_max     INT UNSIGNED DEFAULT NULL COMMENT 'Max price in PLN grosz',
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug),
			KEY wp_post_id (wp_post_id),
			KEY category_city (category, city),
			KEY ranking_score (ranking_score),
			KEY verification_level (verification_level)
		) {$charset};";

		dbDelta( $sql );
		return $this->table_exists( $table );
	}

	private function create_reviews_table(): bool {
		$table   = $this->wpdb->prefix . 'pearblog_reviews';
		$charset = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			specialist_id   BIGINT UNSIGNED NOT NULL,
			author_name     VARCHAR(200) NOT NULL,
			author_email    VARCHAR(200) NOT NULL DEFAULT '',
			rating          TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '1-5',
			title           VARCHAR(300) NOT NULL DEFAULT '',
			body            TEXT,
			is_verified     TINYINT(1) NOT NULL DEFAULT 0,
			is_published    TINYINT(1) NOT NULL DEFAULT 0,
			source          VARCHAR(50)  NOT NULL DEFAULT 'platform' COMMENT 'platform|google|fb',
			ip_hash         VARCHAR(64)  NOT NULL DEFAULT '',
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY specialist_id (specialist_id),
			KEY is_published (is_published),
			KEY rating (rating)
		) {$charset};";

		dbDelta( $sql );
		return $this->table_exists( $table );
	}

	private function create_badges_table(): bool {
		$table   = $this->wpdb->prefix . 'pearblog_specialist_badges';
		$charset = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			specialist_id   BIGINT UNSIGNED NOT NULL,
			badge_id        VARCHAR(100) NOT NULL,
			awarded_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at      DATETIME DEFAULT NULL,
			awarded_by      BIGINT UNSIGNED DEFAULT NULL COMMENT 'WP user ID',
			reason          VARCHAR(500) DEFAULT NULL,
			PRIMARY KEY (id),
			KEY specialist_id (specialist_id),
			KEY badge_id (badge_id)
		) {$charset};";

		dbDelta( $sql );
		return $this->table_exists( $table );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function table_exists( string $table ): bool {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare( 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s', $table )
		) > 0;
	}

	/**
	 * Drop all Specialists tables (for uninstall).
	 */
	public function drop_tables(): void {
		foreach ( [ 'pearblog_specialist_badges', 'pearblog_reviews', 'pearblog_specialists' ] as $t ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query( "DROP TABLE IF EXISTS {$this->wpdb->prefix}{$t}" );
		}
	}
}
