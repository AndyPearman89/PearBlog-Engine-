<?php
/**
 * LeadAI Database Schema
 *
 * Creates and manages database tables for the PT24 Lead AI Engine.
 *
 * @package PearBlog\LeadAI\Infrastructure
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\Infrastructure;

/**
 * Lead AI Schema
 *
 * Database schema management for leads, contractors, and logs.
 */
class LeadAISchema {
	private \wpdb $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Create all Lead AI tables.
	 *
	 * @return array Results for each table.
	 */
	public function createTables(): array {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$results = [
			'leads'       => $this->createLeadsTable(),
			'contractors' => $this->createContractorsTable(),
			'sms_log'     => $this->createSMSLogTable(),
			'email_log'   => $this->createEmailLogTable(),
		];

		return $results;
	}

	/**
	 * Create leads table.
	 */
	private function createLeadsTable(): bool {
		$table_name = $this->wpdb->prefix . 'pt24_leads';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			category VARCHAR(100) NOT NULL,
			location VARCHAR(100) NOT NULL,
			message TEXT NOT NULL,
			status VARCHAR(50) DEFAULT 'NEW',
			score INT DEFAULT 0,
			score_breakdown TEXT DEFAULT NULL,
			intent VARCHAR(50) DEFAULT NULL,
			urgency VARCHAR(20) DEFAULT 'MEDIUM',
			package_type VARCHAR(20) DEFAULT 'FREE',
			assigned_contractor_id BIGINT UNSIGNED DEFAULT NULL,
			created_at INT UNSIGNED NOT NULL,
			responded_at INT UNSIGNED DEFAULT NULL,
			closed_at INT UNSIGNED DEFAULT NULL,
			metadata TEXT DEFAULT NULL,
			PRIMARY KEY (id),
			KEY status (status),
			KEY package_type (package_type),
			KEY created_at (created_at),
			KEY assigned_contractor_id (assigned_contractor_id),
			KEY score (score)
		) {$charset_collate};";

		dbDelta($sql);
		return $this->tableExists($table_name);
	}

	/**
	 * Create contractors table.
	 */
	private function createContractorsTable(): bool {
		$table_name = $this->wpdb->prefix . 'pt24_contractors';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			email VARCHAR(255) NOT NULL,
			phone VARCHAR(20) DEFAULT NULL,
			package_type VARCHAR(20) DEFAULT 'FREE',
			categories TEXT DEFAULT NULL,
			location VARCHAR(100) DEFAULT NULL,
			rating DECIMAL(3,2) DEFAULT 0.00,
			response_rate DECIMAL(3,2) DEFAULT 0.00,
			acceptance_rate DECIMAL(3,2) DEFAULT 0.00,
			avg_response_time INT DEFAULT 0,
			last_active DATETIME DEFAULT NULL,
			status VARCHAR(20) DEFAULT 'active',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			metadata TEXT DEFAULT NULL,
			PRIMARY KEY (id),
			KEY email (email),
			KEY package_type (package_type),
			KEY status (status),
			KEY rating (rating)
		) {$charset_collate};";

		dbDelta($sql);
		return $this->tableExists($table_name);
	}

	/**
	 * Create SMS log table.
	 */
	private function createSMSLogTable(): bool {
		$table_name = $this->wpdb->prefix . 'pt24_sms_log';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			phone VARCHAR(20) NOT NULL,
			message TEXT NOT NULL,
			success TINYINT(1) DEFAULT 0,
			sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY sent_at (sent_at),
			KEY phone (phone)
		) {$charset_collate};";

		dbDelta($sql);
		return $this->tableExists($table_name);
	}

	/**
	 * Create email log table.
	 */
	private function createEmailLogTable(): bool {
		$table_name = $this->wpdb->prefix . 'pt24_email_log';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			to_email VARCHAR(255) NOT NULL,
			subject VARCHAR(255) NOT NULL,
			success TINYINT(1) DEFAULT 0,
			sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY sent_at (sent_at),
			KEY to_email (to_email)
		) {$charset_collate};";

		dbDelta($sql);
		return $this->tableExists($table_name);
	}

	/**
	 * Check if table exists.
	 */
	private function tableExists(string $table_name): bool {
		$query = $this->wpdb->prepare('SHOW TABLES LIKE %s', $table_name);
		return $this->wpdb->get_var($query) === $table_name;
	}

	/**
	 * Drop all tables (for uninstall).
	 */
	public function dropTables(): array {
		$tables = [
			'pt24_leads',
			'pt24_contractors',
			'pt24_sms_log',
			'pt24_email_log',
		];

		$results = [];

		foreach ($tables as $table) {
			$table_name = $this->wpdb->prefix . $table;
			$results[$table] = $this->wpdb->query("DROP TABLE IF EXISTS {$table_name}");
		}

		return $results;
	}

	/**
	 * Get schema version.
	 */
	public function getVersion(): string {
		return '2.0.0';
	}
}
