<?php
/**
 * Comparison Engine — database schema.
 *
 * Tables:
 *   {prefix}pb_comparisons         – comparison header
 *   {prefix}pb_comparison_items    – subjects being compared
 *   {prefix}pb_comparison_attrs    – per-item attribute rows (pros, cons, specs)
 *
 * @package PearBlogEngine\Compare
 */

declare( strict_types=1 );

namespace PearBlogEngine\Compare;

class ComparisonSchema {

	private const SCHEMA_VERSION_OPTION = 'pearblog_compare_schema_version';
	private const CURRENT_VERSION       = '1.0.0';

	public static function create_tables(): void {
		global $wpdb;
		$cc = $wpdb->get_charset_collate();

		$comparisons = "
			CREATE TABLE {$wpdb->prefix}pb_comparisons (
				id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug            VARCHAR(200)    NOT NULL,
				title           VARCHAR(500)    NOT NULL,
				category        VARCHAR(100)    NOT NULL DEFAULT '',
				ai_verdict      TEXT                     DEFAULT NULL,
				ai_summary      TEXT                     DEFAULT NULL,
				seo_description TEXT                     DEFAULT NULL,
				schema_json     LONGTEXT                 DEFAULT NULL,
				status          VARCHAR(20)     NOT NULL DEFAULT 'publish',
				view_count      BIGINT UNSIGNED NOT NULL DEFAULT 0,
				created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY slug (slug),
				KEY category (category),
				KEY status (status)
			) $cc;
		";

		$items = "
			CREATE TABLE {$wpdb->prefix}pb_comparison_items (
				id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				comparison_id   BIGINT UNSIGNED NOT NULL,
				position        TINYINT UNSIGNED NOT NULL DEFAULT 0,
				label           VARCHAR(300)    NOT NULL,
				image_url       VARCHAR(2000)            DEFAULT NULL,
				overall_score   TINYINT UNSIGNED         DEFAULT NULL,
				ai_verdict_tag  VARCHAR(50)              DEFAULT NULL,
				meta_json       LONGTEXT                 DEFAULT NULL,
				created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY comparison_id (comparison_id)
			) $cc;
		";

		$attrs = "
			CREATE TABLE {$wpdb->prefix}pb_comparison_attrs (
				id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				item_id     BIGINT UNSIGNED NOT NULL,
				attr_key    VARCHAR(100)    NOT NULL,
				attr_label  VARCHAR(300)    NOT NULL DEFAULT '',
				attr_value  TEXT                     DEFAULT NULL,
				attr_type   VARCHAR(30)     NOT NULL DEFAULT 'text',
				position    SMALLINT UNSIGNED        NOT NULL DEFAULT 0,
				PRIMARY KEY (id),
				KEY item_id (item_id),
				KEY attr_key (attr_key)
			) $cc;
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $comparisons );
		dbDelta( $items );
		dbDelta( $attrs );

		update_option( self::SCHEMA_VERSION_OPTION, self::CURRENT_VERSION );
	}

	public static function needs_upgrade(): bool {
		return (string) get_option( self::SCHEMA_VERSION_OPTION, '' ) !== self::CURRENT_VERSION;
	}
}
