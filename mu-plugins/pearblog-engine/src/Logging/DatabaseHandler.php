<?php
/**
 * Database Log Handler
 *
 * Stores log records in a custom WordPress database table for persistent,
 * queryable logging. Supports bulk inserts and automatic table creation.
 *
 * Table: wp_pearblog_logs
 * Columns: id, timestamp, level, channel, message, context, created_at
 *
 * @package PearBlogEngine\Logging
 */

declare(strict_types=1);

namespace PearBlogEngine\Logging;

/**
 * Database Handler for storing logs in WordPress database
 */
class DatabaseHandler extends AbstractHandler {

	/** @var string Table name (without prefix) */
	private const TABLE_NAME = 'pearblog_logs';

	/** @var array Buffer for bulk inserts */
	private array $buffer = [];

	/** @var int Number of records to buffer before flush */
	private int $buffer_size;

	/** @var bool Whether table has been created */
	private static bool $table_created = false;

	/**
	 * Constructor
	 *
	 * @param string $min_level   Minimum log level
	 * @param int    $buffer_size Number of records to buffer (0 = no buffering)
	 */
	public function __construct( string $min_level = 'INFO', int $buffer_size = 10 ) {
		parent::__construct( $min_level );
		$this->buffer_size = $buffer_size;

		// Create table on first use
		if ( ! self::$table_created ) {
			$this->maybe_create_table();
			self::$table_created = true;
		}

		// Flush buffer on shutdown
		add_action( 'shutdown', [ $this, 'flush' ] );
	}

	/**
	 * Write a log record to the database
	 *
	 * @param array $record Log record
	 * @return bool Success
	 */
	protected function write( array $record ): bool {
		if ( $this->buffer_size > 0 ) {
			return $this->write_buffered( $record );
		}

		return $this->write_immediate( $record );
	}

	/**
	 * Add record to buffer and flush if necessary
	 *
	 * @param array $record Log record
	 * @return bool Success
	 */
	private function write_buffered( array $record ): bool {
		$this->buffer[] = $record;

		if ( count( $this->buffer ) >= $this->buffer_size ) {
			return $this->flush();
		}

		return true;
	}

	/**
	 * Write record immediately to database
	 *
	 * @param array $record Log record
	 * @return bool Success
	 */
	private function write_immediate( array $record ): bool {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;

		$result = $wpdb->insert(
			$table,
			[
				'timestamp'  => $record['timestamp'] ?? gmdate( 'Y-m-d H:i:s' ),
				'level'      => $record['level'] ?? 'INFO',
				'channel'    => $record['channel'] ?? 'pearblog',
				'message'    => $record['message'] ?? '',
				'context'    => json_encode( $record['context'] ?? [] ),
				'extra'      => json_encode( $record['extra'] ?? [] ),
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		return false !== $result;
	}

	/**
	 * Flush all buffered records to database
	 *
	 * @return bool Success
	 */
	public function flush(): bool {
		if ( empty( $this->buffer ) ) {
			return true;
		}

		global $wpdb;

		$table  = $wpdb->prefix . self::TABLE_NAME;
		$values = [];
		$format = [];

		foreach ( $this->buffer as $record ) {
			$values[] = $record['timestamp'] ?? gmdate( 'Y-m-d H:i:s' );
			$values[] = $record['level'] ?? 'INFO';
			$values[] = $record['channel'] ?? 'pearblog';
			$values[] = $record['message'] ?? '';
			$values[] = json_encode( $record['context'] ?? [] );
			$values[] = json_encode( $record['extra'] ?? [] );
			$values[] = current_time( 'mysql', true );

			$format[] = '(%s, %s, %s, %s, %s, %s, %s)';
		}

		$query = "INSERT INTO {$table} (timestamp, level, channel, message, context, extra, created_at) VALUES ";
		$query .= implode( ', ', $format );

		$result = $wpdb->query( $wpdb->prepare( $query, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$this->buffer = [];

		return false !== $result;
	}

	/**
	 * Create the logs table if it doesn't exist
	 */
	private function maybe_create_table(): void {
		global $wpdb;

		$table         = $wpdb->prefix . self::TABLE_NAME;
		$charset       = $wpdb->get_charset_collate();
		$table_exists  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( $table_exists ) {
			return;
		}

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			timestamp DATETIME NOT NULL,
			level VARCHAR(20) NOT NULL,
			channel VARCHAR(50) NOT NULL,
			message TEXT NOT NULL,
			context LONGTEXT,
			extra LONGTEXT,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY level (level),
			KEY channel (channel),
			KEY timestamp (timestamp),
			KEY created_at (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Query logs from database
	 *
	 * @param array $args Query arguments
	 * @return array Log records
	 */
	public function query_logs( array $args = [] ): array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;

		$defaults = [
			'level'    => null,
			'channel'  => null,
			'search'   => null,
			'limit'    => 100,
			'offset'   => 0,
			'order_by' => 'created_at',
			'order'    => 'DESC',
		];

		$args = wp_parse_args( $args, $defaults );

		$where = [];
		$values = [];

		if ( ! empty( $args['level'] ) ) {
			$where[] = 'level = %s';
			$values[] = $args['level'];
		}

		if ( ! empty( $args['channel'] ) ) {
			$where[] = 'channel = %s';
			$values[] = $args['channel'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[] = 'message LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$query = $wpdb->prepare(
			"SELECT * FROM {$table} {$where_sql} ORDER BY {$args['order_by']} {$args['order']} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array_merge( $values, [ $args['limit'], $args['offset'] ] )
		);

		$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Decode JSON fields
		foreach ( $results as &$result ) {
			$result['context'] = json_decode( $result['context'] ?? '{}', true );
			$result['extra']   = json_decode( $result['extra'] ?? '{}', true );
		}

		return $results;
	}

	/**
	 * Delete old log records
	 *
	 * @param int $days Number of days to keep (delete older records)
	 * @return int Number of deleted records
	 */
	public function prune_logs( int $days = 30 ): int {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$days
			)
		);

		return (int) $result;
	}

	/**
	 * Get log statistics
	 *
	 * @return array Statistics
	 */
	public function get_stats(): array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;

		$stats = [
			'total'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'by_level'  => [],
			'by_channel' => [],
		];

		// Count by level
		$level_counts = $wpdb->get_results( "SELECT level, COUNT(*) as count FROM {$table} GROUP BY level", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( $level_counts as $row ) {
			$stats['by_level'][ $row['level'] ] = (int) $row['count'];
		}

		// Count by channel
		$channel_counts = $wpdb->get_results( "SELECT channel, COUNT(*) as count FROM {$table} GROUP BY channel", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( $channel_counts as $row ) {
			$stats['by_channel'][ $row['channel'] ] = (int) $row['count'];
		}

		return $stats;
	}
}
