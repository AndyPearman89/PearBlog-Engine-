<?php
/**
 * Calculator Engine — database schema.
 *
 * Tables:
 *   {prefix}pb_calculators        – calculator definition (fields, formula meta)
 *   {prefix}pb_calculator_results – persisted calculation results (analytics + lead gen)
 *
 * @package PearBlogEngine\Calculators
 */

declare( strict_types=1 );

namespace PearBlogEngine\Calculators;

class CalculatorsSchema {

	private const SCHEMA_VERSION_OPTION = 'pearblog_calculators_schema_version';
	private const CURRENT_VERSION       = '1.0.0';

	public static function create_tables(): void {
		global $wpdb;
		$cc = $wpdb->get_charset_collate();

		$calculators = "
			CREATE TABLE {$wpdb->prefix}pb_calculators (
				id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug            VARCHAR(200)    NOT NULL,
				title           VARCHAR(500)    NOT NULL,
				category        VARCHAR(100)    NOT NULL DEFAULT '',
				fields_json     LONGTEXT        NOT NULL DEFAULT '[]',
				formula_json    LONGTEXT        NOT NULL DEFAULT '{}',
				output_template TEXT                     DEFAULT NULL,
				recommendation_rules LONGTEXT           DEFAULT NULL,
				status          VARCHAR(20)     NOT NULL DEFAULT 'publish',
				use_count       BIGINT UNSIGNED NOT NULL DEFAULT 0,
				created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY slug (slug),
				KEY category (category),
				KEY status (status)
			) $cc;
		";

		$results = "
			CREATE TABLE {$wpdb->prefix}pb_calculator_results (
				id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				calculator_id   BIGINT UNSIGNED NOT NULL,
				session_hash    VARCHAR(64)              DEFAULT NULL,
				inputs_json     LONGTEXT        NOT NULL DEFAULT '{}',
				result_value    DECIMAL(15,2)            DEFAULT NULL,
				result_label    VARCHAR(300)             DEFAULT NULL,
				recommendations LONGTEXT                 DEFAULT NULL,
				lead_generated  TINYINT(1)      NOT NULL DEFAULT 0,
				created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY calculator_id (calculator_id),
				KEY session_hash (session_hash),
				KEY lead_generated (lead_generated)
			) $cc;
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $calculators );
		dbDelta( $results );

		update_option( self::SCHEMA_VERSION_OPTION, self::CURRENT_VERSION );
	}

	public static function needs_upgrade(): bool {
		return (string) get_option( self::SCHEMA_VERSION_OPTION, '' ) !== self::CURRENT_VERSION;
	}
}
