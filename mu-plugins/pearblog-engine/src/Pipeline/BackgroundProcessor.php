<?php
/**
 * Background Processor — offloads individual pipeline runs to separate WP-Cron
 * events so that they never block the HTTP request that triggers them.
 *
 * Architecture
 * ────────────
 * A pending-jobs queue is maintained as a JSON array in a WordPress option
 * (`pearblog_bg_queue`). Each job is a small array:
 *   { id, topic, tenant_id, scheduled_at, attempts }
 *
 * Dispatch flow:
 *   1. `dispatch(topic, tenant_id)` — pushes a job onto the queue and
 *      schedules a one-off `pearblog_bg_process` cron event 5 seconds later.
 *   2. WordPress cron fires `pearblog_bg_process`.
 *   3. `handle_batch()` is called; it pops up to `MAX_BATCH_SIZE` jobs from
 *      the queue and runs the pipeline for each.
 *   4. Failed jobs are re-queued up to `MAX_ATTEMPTS` times with exponential
 *      back-off (next attempt scheduled at now + 2^attempts minutes).
 *   5. If more jobs remain after the batch, another cron event is scheduled
 *      immediately to continue processing.
 *
 * This is the same pattern used by the popular WP Background Processing
 * library (Delicious Brains) without requiring a Composer dependency.
 *
 * Configuration WP options:
 *   pearblog_bg_max_batch_size  – jobs per cron run (default 5)
 *   pearblog_bg_max_attempts    – retry limit per job (default 3)
 *   pearblog_bg_enabled         – bool, master switch (default true)
 *
 * @package PearBlogEngine\Pipeline
 */

declare(strict_types=1);

namespace PearBlogEngine\Pipeline;

/**
 * Manages a persistent queue of background pipeline jobs.
 */
class BackgroundProcessor {

	// -----------------------------------------------------------------------
	// Constants / option keys
	// -----------------------------------------------------------------------

	/** WP cron action hook. */
	public const CRON_HOOK = 'pearblog_bg_process';

	/** WP option that stores the pending job queue. */
	public const OPTION_QUEUE = 'pearblog_bg_queue';

	/** WP option recording the last batch run timestamp. */
	public const OPTION_LAST_RUN = 'pearblog_bg_last_run';

	public const OPTION_ENABLED       = 'pearblog_bg_enabled';
	public const OPTION_MAX_BATCH     = 'pearblog_bg_max_batch_size';
	public const OPTION_MAX_ATTEMPTS  = 'pearblog_bg_max_attempts';

	public const DEFAULT_MAX_BATCH    = 5;
	public const DEFAULT_MAX_ATTEMPTS = 3;

	/** Seconds between the dispatch call and the first cron event. */
	public const DISPATCH_DELAY_SECONDS = 5;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'handle_batch' ] );
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Whether the background processor is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, true );
	}

	/**
	 * Push a topic onto the background queue and schedule a cron event.
	 *
	 * @param string $topic     Article topic to generate.
	 * @param string $tenant_id Tenant identifier (empty string for single-site).
	 * @return string           Job ID (UUID-style).
	 */
	public function dispatch( string $topic, string $tenant_id = '' ): string {
		$job_id = $this->generate_id();

		$job = [
			'id'           => $job_id,
			'topic'        => $topic,
			'tenant_id'    => $tenant_id,
			'scheduled_at' => time(),
			'attempts'     => 0,
		];

		$this->push_job( $job );
		$this->schedule_next( self::DISPATCH_DELAY_SECONDS );

		return $job_id;
	}

	/**
	 * Return a copy of the pending job queue.
	 *
	 * @return array<int, array{id: string, topic: string, tenant_id: string, scheduled_at: int, attempts: int}>
	 */
	public function get_queue(): array {
		$raw     = get_option( self::OPTION_QUEUE, '[]' );
		$decoded = json_decode( is_string( $raw ) ? $raw : '[]', true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Return the number of pending jobs.
	 */
	public function queue_size(): int {
		return count( $this->get_queue() );
	}

	/**
	 * Cancel all pending jobs.
	 */
	public function clear_queue(): void {
		update_option( self::OPTION_QUEUE, '[]' );
	}

	/**
	 * Cancel a specific job by ID. Returns true if the job was found and removed.
	 *
	 * @param string $job_id
	 * @return bool
	 */
	public function cancel( string $job_id ): bool {
		$queue    = $this->get_queue();
		$filtered = array_values( array_filter( $queue, fn( $j ) => $j['id'] !== $job_id ) );

		if ( count( $filtered ) === count( $queue ) ) {
			return false;
		}

		$this->save_queue( $filtered );
		return true;
	}

	// -----------------------------------------------------------------------
	// Batch processor (called by WP-Cron)
	// -----------------------------------------------------------------------

	/**
	 * Process a batch of jobs from the queue.
	 *
	 * This method is attached to the `pearblog_bg_process` cron hook.
	 */
	public function handle_batch(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$queue     = $this->get_queue();
		$max_batch = (int) get_option( self::OPTION_MAX_BATCH, self::DEFAULT_MAX_BATCH );
		$batch     = array_splice( $queue, 0, $max_batch );

		// Save remaining queue immediately so a concurrent cron doesn't
		// double-process the same jobs.
		$this->save_queue( $queue );
		update_option( self::OPTION_LAST_RUN, time() );

		foreach ( $batch as $job ) {
			$this->process_job( $job );
		}

		// If more jobs remain, schedule another immediate pass.
		if ( ! empty( $queue ) ) {
			$this->schedule_next( 0 );
		}
	}

	// -----------------------------------------------------------------------
	// Job processing
	// -----------------------------------------------------------------------

	/**
	 * Execute a single pipeline job.
	 *
	 * Fires the `pearblog_bg_run_pipeline` action with the job array.
	 * The ContentPipeline is wired to this hook in Plugin::boot() rather than
	 * hard-coupled here, keeping BackgroundProcessor dependency-free.
	 *
	 * On exception the job is re-queued with incremented attempt count if
	 * attempts < MAX_ATTEMPTS; otherwise it is discarded and the
	 * `pearblog_bg_job_failed` action is fired.
	 *
	 * @param array $job
	 */
	public function process_job( array $job ): void {
		try {
			/**
			 * Run the pipeline for the given background job.
			 *
			 * @param array $job  { id, topic, tenant_id, scheduled_at, attempts }
			 */
			do_action( 'pearblog_bg_run_pipeline', $job );

			/**
			 * Fires when a background job completes successfully.
			 *
			 * @param array $job
			 */
			do_action( 'pearblog_bg_job_completed', $job );

		} catch ( \Throwable $e ) {
			$this->handle_job_failure( $job, $e );
		}
	}

	// -----------------------------------------------------------------------
	// Failure handling
	// -----------------------------------------------------------------------

	/**
	 * Re-queue or discard a failed job.
	 *
	 * @param array      $job
	 * @param \Throwable $e
	 */
	private function handle_job_failure( array $job, \Throwable $e ): void {
		$max_attempts = (int) get_option( self::OPTION_MAX_ATTEMPTS, self::DEFAULT_MAX_ATTEMPTS );
		$job['attempts']++;

		if ( $job['attempts'] < $max_attempts ) {
			// Exponential back-off: schedule retry after 2^attempts minutes.
			$delay_minutes = (int) pow( 2, $job['attempts'] );
			$job['retry_after'] = time() + $delay_minutes * 60;
			$this->push_job( $job );
			$this->schedule_next( $delay_minutes * 60 );
		} else {
			/**
			 * Fires when a background job exhausts all retry attempts.
			 *
			 * @param array      $job
			 * @param \Throwable $error
			 */
			do_action( 'pearblog_bg_job_failed', $job, $e );
		}
	}

	// -----------------------------------------------------------------------
	// Queue helpers
	// -----------------------------------------------------------------------

	/**
	 * Add a job to the end of the persistent queue.
	 *
	 * @param array $job
	 */
	private function push_job( array $job ): void {
		$queue   = $this->get_queue();
		$queue[] = $job;
		$this->save_queue( $queue );
	}

	/**
	 * Persist the queue to the WP option store.
	 *
	 * @param array $queue
	 */
	private function save_queue( array $queue ): void {
		update_option( self::OPTION_QUEUE, wp_json_encode( array_values( $queue ) ) );
	}

	// -----------------------------------------------------------------------
	// Cron helpers
	// -----------------------------------------------------------------------

	/**
	 * Schedule a one-off `pearblog_bg_process` cron event.
	 *
	 * @param int $delay_seconds Seconds from now.
	 */
	private function schedule_next( int $delay_seconds ): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + $delay_seconds, self::CRON_HOOK );
		}
	}

	/**
	 * Generate a simple pseudo-unique job ID.
	 */
	private function generate_id(): string {
		return sprintf(
			'%s-%s',
			dechex( time() ),
			bin2hex( random_bytes( 6 ) )
		);
	}
}
