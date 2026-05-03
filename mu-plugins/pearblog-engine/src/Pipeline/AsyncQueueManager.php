<?php
/**
 * Async Queue Manager – replaces synchronous WP-Cron with an async queue.
 *
 * Supports three backends:
 *  - `wp_cron` (default, existing behaviour – no external dependency)
 *  - `redis`   (requires predis/predis or phpredis extension)
 *  - `database` (WordPress database table – no external dependency, more reliable than WP-Cron)
 *
 * The database backend creates a `{prefix}pearblog_queue` table to store jobs
 * with retry logic and a dead-letter queue for failed jobs.
 *
 * Configuration (WP options):
 *   pearblog_async_backend        – 'wp_cron' | 'redis' | 'database'
 *   pearblog_async_redis_url      – Redis connection URL (e.g. tcp://127.0.0.1:6379)
 *   pearblog_async_max_retries    – max retry attempts (default: 3)
 *   pearblog_async_batch_size     – jobs to process per cron cycle (default: 5)
 *
 * @package PearBlogEngine\Pipeline
 */

declare(strict_types=1);

namespace PearBlogEngine\Pipeline;

/**
 * Manages asynchronous pipeline job execution.
 */
class AsyncQueueManager {

	/** WP option keys. */
	public const OPTION_BACKEND     = 'pearblog_async_backend';
	public const OPTION_REDIS_URL   = 'pearblog_async_redis_url';
	public const OPTION_MAX_RETRIES = 'pearblog_async_max_retries';
	public const OPTION_BATCH_SIZE  = 'pearblog_async_batch_size';

	/** Default values. */
	public const DEFAULT_MAX_RETRIES = 3;
	public const DEFAULT_BATCH_SIZE  = 5;

	/** Job status constants. */
	public const STATUS_PENDING    = 'pending';
	public const STATUS_PROCESSING = 'processing';
	public const STATUS_DONE       = 'done';
	public const STATUS_FAILED     = 'failed';
	public const STATUS_DEAD       = 'dead_letter';

	/** Cron hook for processing the queue. */
	private const CRON_HOOK = 'pearblog_async_process';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( self::CRON_HOOK, [ $this, 'process_batch' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );

		// Create DB table if using database backend.
		if ( 'database' === $this->get_backend() ) {
			add_action( 'init', [ $this, 'maybe_create_table' ] );
		}
	}

	/**
	 * Schedule queue processor cron (every 5 minutes).
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'pearblog_5min', self::CRON_HOOK );
		}
	}

	/**
	 * Register REST routes for queue management.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/queue/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_queue_status' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/queue/dead-letter', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_dead_letter' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/queue/dead-letter/retry', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_retry_dead' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Job management
	// -----------------------------------------------------------------------

	/**
	 * Push a job onto the async queue.
	 *
	 * @param string              $job_type Job type identifier.
	 * @param array<string,mixed> $payload  Job data.
	 * @param int                 $priority Priority (lower = higher priority, default: 10).
	 * @return int|string Job ID.
	 */
	public function push( string $job_type, array $payload, int $priority = 10 ): int|string {
		$backend = $this->get_backend();

		$job = [
			'type'       => $job_type,
			'payload'    => $payload,
			'priority'   => $priority,
			'created_at' => time(),
			'attempts'   => 0,
			'status'     => self::STATUS_PENDING,
		];

		return match ( $backend ) {
			'database' => $this->db_push( $job ),
			'redis'    => $this->redis_push( $job ),
			default    => $this->wp_cron_push( $job ),
		};
	}

	/**
	 * Process a batch of pending jobs.
	 */
	public function process_batch(): void {
		$backend    = $this->get_backend();
		$batch_size = (int) get_option( self::OPTION_BATCH_SIZE, self::DEFAULT_BATCH_SIZE );

		$jobs = match ( $backend ) {
			'database' => $this->db_pop_batch( $batch_size ),
			'redis'    => $this->redis_pop_batch( $batch_size ),
			default    => [],
		};

		foreach ( $jobs as $job ) {
			$this->execute_job( $job );
		}
	}

	/**
	 * Execute a single job.
	 *
	 * @param array<string,mixed> $job Job data.
	 */
	public function execute_job( array $job ): void {
		try {
			/**
			 * Action: pearblog_async_job_{type}
			 *
			 * @param array<string,mixed> $payload Job payload.
			 * @param array<string,mixed> $job     Full job data.
			 */
			do_action( 'pearblog_async_job_' . $job['type'], $job['payload'], $job );

			$this->mark_done( $job );

			/**
			 * Action: pearblog_bg_job_completed
			 *
			 * @param array<string,mixed> $job Completed job data.
			 */
			do_action( 'pearblog_bg_job_completed', $job );

		} catch ( \Throwable $e ) {
			$this->mark_failed( $job, $e );

			/**
			 * Action: pearblog_bg_job_failed
			 *
			 * @param array<string,mixed> $job  Failed job data.
			 * @param \Throwable          $e    Exception.
			 */
			do_action( 'pearblog_bg_job_failed', $job, $e );
		}
	}

	// -----------------------------------------------------------------------
	// Queue status
	// -----------------------------------------------------------------------

	/**
	 * Get queue statistics.
	 *
	 * @return array{pending: int, processing: int, failed: int, dead: int, backend: string}
	 */
	public function get_stats(): array {
		if ( 'database' === $this->get_backend() ) {
			return $this->db_get_stats();
		}

		return [
			'pending'    => 0,
			'processing' => 0,
			'failed'     => 0,
			'dead'       => 0,
			'backend'    => $this->get_backend(),
		];
	}

	// -----------------------------------------------------------------------
	// Database backend
	// -----------------------------------------------------------------------

	/**
	 * Create the jobs table if it doesn't exist.
	 */
	public function maybe_create_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'pearblog_queue';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			job_type    VARCHAR(100)    NOT NULL,
			payload     LONGTEXT        NOT NULL,
			priority    TINYINT         NOT NULL DEFAULT 10,
			status      VARCHAR(20)     NOT NULL DEFAULT 'pending',
			attempts    TINYINT         NOT NULL DEFAULT 0,
			error       TEXT,
			created_at  INT UNSIGNED    NOT NULL,
			updated_at  INT UNSIGNED    NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY status_priority (status, priority),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Push a job to the database queue.
	 *
	 * @param array<string,mixed> $job Job data.
	 * @return int Inserted row ID.
	 */
	private function db_push( array $job ): int {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'pearblog_queue',
			[
				'job_type'   => $job['type'],
				'payload'    => wp_json_encode( $job['payload'] ),
				'priority'   => $job['priority'],
				'status'     => self::STATUS_PENDING,
				'attempts'   => 0,
				'created_at' => time(),
				'updated_at' => time(),
			],
			[ '%s', '%s', '%d', '%s', '%d', '%d', '%d' ]
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Pop a batch of pending jobs from the database.
	 *
	 * @param int $batch_size Number of jobs to fetch.
	 * @return array<int, array<string,mixed>>
	 */
	private function db_pop_batch( int $batch_size ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'pearblog_queue';
		$rows  = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE status = %s ORDER BY priority ASC, created_at ASC LIMIT %d FOR UPDATE",
			self::STATUS_PENDING,
			$batch_size
		), ARRAY_A );

		if ( empty( $rows ) ) {
			return [];
		}

		// Mark as processing.
		$ids = implode( ',', array_map( 'intval', array_column( $rows, 'id' ) ) );
		$wpdb->query( "UPDATE {$table} SET status = '" . self::STATUS_PROCESSING . "', updated_at = " . time() . " WHERE id IN ({$ids})" );

		// Decode payloads.
		return array_map( function ( $row ) {
			$row['payload'] = json_decode( $row['payload'], true ) ?: [];
			return $row;
		}, $rows );
	}

	/**
	 * Get database queue statistics.
	 *
	 * @return array{pending: int, processing: int, failed: int, dead: int, backend: string}
	 */
	private function db_get_stats(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'pearblog_queue';
		$rows  = $wpdb->get_results(
			"SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status",
			ARRAY_A
		);

		$stats = [ 'pending' => 0, 'processing' => 0, 'failed' => 0, 'dead' => 0, 'backend' => 'database' ];
		foreach ( $rows as $row ) {
			$key = match ( $row['status'] ) {
				self::STATUS_PENDING    => 'pending',
				self::STATUS_PROCESSING => 'processing',
				self::STATUS_FAILED     => 'failed',
				self::STATUS_DEAD       => 'dead',
				default                 => null,
			};
			if ( $key ) {
				$stats[ $key ] = (int) $row['cnt'];
			}
		}

		return $stats;
	}

	/**
	 * Mark a job as done.
	 *
	 * @param array<string,mixed> $job Job data.
	 */
	private function mark_done( array $job ): void {
		if ( 'database' !== $this->get_backend() || empty( $job['id'] ) ) {
			return;
		}

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'pearblog_queue',
			[ 'status' => self::STATUS_DONE, 'updated_at' => time() ],
			[ 'id' => (int) $job['id'] ],
			[ '%s', '%d' ],
			[ '%d' ]
		);
	}

	/**
	 * Mark a job as failed and move to dead-letter if max retries exceeded.
	 *
	 * @param array<string,mixed> $job Job data.
	 * @param \Throwable           $e   Exception.
	 */
	private function mark_failed( array $job, \Throwable $e ): void {
		if ( 'database' !== $this->get_backend() || empty( $job['id'] ) ) {
			return;
		}

		global $wpdb;
		$max_retries = (int) get_option( self::OPTION_MAX_RETRIES, self::DEFAULT_MAX_RETRIES );
		$attempts    = (int) ( $job['attempts'] ?? 0 ) + 1;
		$new_status  = $attempts >= $max_retries ? self::STATUS_DEAD : self::STATUS_PENDING;

		$wpdb->update(
			$wpdb->prefix . 'pearblog_queue',
			[
				'status'     => $new_status,
				'attempts'   => $attempts,
				'error'      => substr( $e->getMessage(), 0, 500 ),
				'updated_at' => time(),
			],
			[ 'id' => (int) $job['id'] ],
			[ '%s', '%d', '%s', '%d' ],
			[ '%d' ]
		);
	}

	// -----------------------------------------------------------------------
	// Redis backend (stub — requires phpredis or predis)
	// -----------------------------------------------------------------------

	/**
	 * Push to Redis list.
	 *
	 * @param array<string,mixed> $job
	 * @return string Job ID (random).
	 */
	private function redis_push( array $job ): string {
		// Basic implementation using wp_remote_post to a Redis proxy
		// or direct phpredis if available.
		$job_id = wp_generate_uuid4();
		$key    = 'pearblog:queue:' . $job['priority'];
		$value  = wp_json_encode( array_merge( $job, [ 'id' => $job_id ] ) );

		if ( class_exists( 'Redis' ) ) {
			try {
				$redis = $this->get_redis_connection();
				if ( $redis ) {
					$redis->rPush( $key, $value );
				}
			} catch ( \Throwable $e ) {
				error_log( 'PearBlog AsyncQueue: Redis push failed – ' . $e->getMessage() );
			}
		}

		return $job_id;
	}

	/**
	 * Pop batch from Redis.
	 *
	 * @param int $batch_size
	 * @return array<int, array<string,mixed>>
	 */
	private function redis_pop_batch( int $batch_size ): array {
		if ( ! class_exists( 'Redis' ) ) {
			return [];
		}

		$redis = $this->get_redis_connection();
		if ( ! $redis ) {
			return [];
		}

		$jobs = [];
		for ( $i = 0; $i < $batch_size; $i++ ) {
			$value = $redis->lPop( 'pearblog:queue:10' );
			if ( ! $value ) {
				break;
			}

			$job = json_decode( $value, true );
			if ( is_array( $job ) ) {
				$jobs[] = $job;
			}
		}

		return $jobs;
	}

	/**
	 * Get a Redis connection using the configured URL.
	 *
	 * @return \Redis|null
	 */
	private function get_redis_connection(): ?\Redis {
		if ( ! class_exists( 'Redis' ) ) {
			return null;
		}

		$redis_url = (string) get_option( self::OPTION_REDIS_URL, 'tcp://127.0.0.1:6379' );
		$parsed    = parse_url( $redis_url );

		try {
			$redis = new \Redis();
			$redis->connect( $parsed['host'] ?? '127.0.0.1', $parsed['port'] ?? 6379, 3 );
			return $redis;
		} catch ( \Throwable $e ) {
			error_log( 'PearBlog AsyncQueue: Redis connection failed – ' . $e->getMessage() );
			return null;
		}
	}

	// -----------------------------------------------------------------------
	// WP-Cron fallback backend
	// -----------------------------------------------------------------------

	/**
	 * Schedule immediate WP-Cron event as async job.
	 *
	 * @param array<string,mixed> $job Job data.
	 * @return int Scheduled timestamp.
	 */
	private function wp_cron_push( array $job ): int {
		$timestamp = time();
		wp_schedule_single_event( $timestamp, 'pearblog_async_job_' . $job['type'], [ $job['payload'] ] );
		return $timestamp;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Get the configured async backend.
	 *
	 * @return string Backend identifier.
	 */
	public function get_backend(): string {
		return (string) get_option( self::OPTION_BACKEND, 'wp_cron' );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_queue_status( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->get_stats(), 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_dead_letter( \WP_REST_Request $request ): \WP_REST_Response {
		if ( 'database' !== $this->get_backend() ) {
			return new \WP_REST_Response( [ 'dead_jobs' => [] ], 200 );
		}

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT id, job_type, error, created_at, attempts FROM " . $wpdb->prefix . "pearblog_queue WHERE status = 'dead_letter' ORDER BY created_at DESC LIMIT 50",
			ARRAY_A
		);

		return new \WP_REST_Response( [ 'dead_jobs' => $rows ], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_retry_dead( \WP_REST_Request $request ): \WP_REST_Response {
		if ( 'database' !== $this->get_backend() ) {
			return new \WP_REST_Response( [ 'retried' => 0 ], 200 );
		}

		global $wpdb;
		$updated = $wpdb->update(
			$wpdb->prefix . 'pearblog_queue',
			[ 'status' => self::STATUS_PENDING, 'attempts' => 0, 'updated_at' => time() ],
			[ 'status' => self::STATUS_DEAD ],
			[ '%s', '%d', '%d' ],
			[ '%s' ]
		);

		return new \WP_REST_Response( [ 'retried' => (int) $updated ], 200 );
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}
