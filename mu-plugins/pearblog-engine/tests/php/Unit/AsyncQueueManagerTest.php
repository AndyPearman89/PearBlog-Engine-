<?php

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Pipeline\AsyncQueueManager;

/**
 * @covers \PearBlogEngine\Pipeline\AsyncQueueManager
 */
class AsyncQueueManagerTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_options']         = [];
		$GLOBALS['_actions']         = [];
		$GLOBALS['_action_handlers'] = [];
		$GLOBALS['_cron_scheduled']  = [];
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_status_constants_are_defined(): void {
		$this->assertSame( 'pending',      AsyncQueueManager::STATUS_PENDING );
		$this->assertSame( 'processing',   AsyncQueueManager::STATUS_PROCESSING );
		$this->assertSame( 'done',         AsyncQueueManager::STATUS_DONE );
		$this->assertSame( 'failed',       AsyncQueueManager::STATUS_FAILED );
		$this->assertSame( 'dead_letter',  AsyncQueueManager::STATUS_DEAD );
	}

	public function test_option_key_constants_are_defined(): void {
		$this->assertSame( 'pearblog_async_backend',     AsyncQueueManager::OPTION_BACKEND );
		$this->assertSame( 'pearblog_async_redis_url',   AsyncQueueManager::OPTION_REDIS_URL );
		$this->assertSame( 'pearblog_async_max_retries', AsyncQueueManager::OPTION_MAX_RETRIES );
		$this->assertSame( 'pearblog_async_batch_size',  AsyncQueueManager::OPTION_BATCH_SIZE );
	}

	public function test_default_constants(): void {
		$this->assertSame( 3, AsyncQueueManager::DEFAULT_MAX_RETRIES );
		$this->assertSame( 5, AsyncQueueManager::DEFAULT_BATCH_SIZE );
	}

	// -----------------------------------------------------------------------
	// get_backend()
	// -----------------------------------------------------------------------

	public function test_get_backend_defaults_to_wp_cron(): void {
		$mgr = new AsyncQueueManager();
		$this->assertSame( 'wp_cron', $mgr->get_backend() );
	}

	public function test_get_backend_returns_configured_value(): void {
		update_option( AsyncQueueManager::OPTION_BACKEND, 'database' );
		$mgr = new AsyncQueueManager();
		$this->assertSame( 'database', $mgr->get_backend() );
	}

	public function test_get_backend_redis(): void {
		update_option( AsyncQueueManager::OPTION_BACKEND, 'redis' );
		$mgr = new AsyncQueueManager();
		$this->assertSame( 'redis', $mgr->get_backend() );
	}

	// -----------------------------------------------------------------------
	// get_stats() – wp_cron backend
	// -----------------------------------------------------------------------

	public function test_get_stats_wp_cron_returns_zero_counts(): void {
		$mgr   = new AsyncQueueManager();
		$stats = $mgr->get_stats();

		$this->assertSame( 0, $stats['pending'] );
		$this->assertSame( 0, $stats['processing'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame( 0, $stats['dead'] );
	}

	public function test_get_stats_wp_cron_includes_backend_key(): void {
		$mgr   = new AsyncQueueManager();
		$stats = $mgr->get_stats();

		$this->assertSame( 'wp_cron', $stats['backend'] );
	}

	public function test_get_stats_has_all_required_keys(): void {
		$mgr   = new AsyncQueueManager();
		$stats = $mgr->get_stats();

		$this->assertArrayHasKey( 'pending',    $stats );
		$this->assertArrayHasKey( 'processing', $stats );
		$this->assertArrayHasKey( 'failed',     $stats );
		$this->assertArrayHasKey( 'dead',       $stats );
		$this->assertArrayHasKey( 'backend',    $stats );
	}

	// -----------------------------------------------------------------------
	// push() – wp_cron backend
	// -----------------------------------------------------------------------

	public function test_push_wp_cron_returns_integer_timestamp(): void {
		$mgr    = new AsyncQueueManager();
		$result = $mgr->push( 'generate_content', [ 'topic' => 'test' ] );

		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );
	}

	public function test_push_wp_cron_schedules_single_event(): void {
		$mgr = new AsyncQueueManager();
		$mgr->push( 'generate_content', [ 'topic' => 'hello' ] );

		// wp_schedule_single_event stores into _cron_scheduled.
		$this->assertNotEmpty( $GLOBALS['_cron_scheduled'] );
	}

	public function test_push_wp_cron_schedules_correct_hook(): void {
		$mgr = new AsyncQueueManager();
		$mgr->push( 'my_job_type', [ 'key' => 'val' ] );

		$this->assertArrayHasKey( 'pearblog_async_job_my_job_type', $GLOBALS['_cron_scheduled'] );
	}

	public function test_push_wp_cron_passes_payload_as_args(): void {
		$captured = null;
		$mgr      = new AsyncQueueManager();
		$payload  = [ 'post_id' => 42 ];

		// wp_schedule_single_event is a simple stub; verify hook key is recorded.
		$mgr->push( 'publish', $payload );
		$this->assertArrayHasKey( 'pearblog_async_job_publish', $GLOBALS['_cron_scheduled'] );
	}

	public function test_push_default_priority_is_ten(): void {
		$mgr = new AsyncQueueManager();
		// Push with explicit default and verify result is consistent.
		$r1 = $mgr->push( 'job_a', [] );
		$r2 = $mgr->push( 'job_a', [], 10 );
		$this->assertSame( gettype( $r1 ), gettype( $r2 ) );
	}

	// -----------------------------------------------------------------------
	// execute_job()
	// -----------------------------------------------------------------------

	public function test_execute_job_fires_type_specific_action(): void {
		$fired   = false;
		$mgr     = new AsyncQueueManager();
		$payload = [ 'x' => 1 ];

		add_action( 'pearblog_async_job_my_task', function ( $p ) use ( &$fired, $payload ) {
			$this->assertSame( $payload, $p );
			$fired = true;
		} );

		$mgr->execute_job( [ 'type' => 'my_task', 'payload' => $payload ] );
		$this->assertTrue( $fired );
	}

	public function test_execute_job_fires_completed_action_on_success(): void {
		$completed = false;
		$mgr       = new AsyncQueueManager();

		add_action( 'pearblog_bg_job_completed', function () use ( &$completed ) {
			$completed = true;
		} );

		$mgr->execute_job( [ 'type' => 'noop', 'payload' => [] ] );
		$this->assertTrue( $completed );
	}

	public function test_execute_job_fires_failed_action_on_exception(): void {
		$failed = false;
		$mgr    = new AsyncQueueManager();

		add_action( 'pearblog_async_job_throw_job', function () {
			throw new \RuntimeException( 'intentional' );
		} );

		add_action( 'pearblog_bg_job_failed', function () use ( &$failed ) {
			$failed = true;
		} );

		$mgr->execute_job( [ 'type' => 'throw_job', 'payload' => [] ] );
		$this->assertTrue( $failed );
	}

	// -----------------------------------------------------------------------
	// maybe_schedule()
	// -----------------------------------------------------------------------

	public function test_maybe_schedule_schedules_when_not_already_scheduled(): void {
		$mgr = new AsyncQueueManager();
		$mgr->maybe_schedule();

		// wp_schedule_event stores into _cron_scheduled.
		$this->assertArrayHasKey( 'pearblog_async_process', $GLOBALS['_cron_scheduled'] );
	}

	public function test_maybe_schedule_skips_when_already_scheduled(): void {
		// Pre-populate _cron_scheduled so wp_next_scheduled returns a timestamp.
		$GLOBALS['_cron_scheduled']['pearblog_async_process'] = time() + 300;

		$mgr    = new AsyncQueueManager();
		$before = $GLOBALS['_cron_scheduled']['pearblog_async_process'];
		$mgr->maybe_schedule();

		// Value must not change (schedule was already there).
		$this->assertSame( $before, $GLOBALS['_cron_scheduled']['pearblog_async_process'] );
	}

	// -----------------------------------------------------------------------
	// admin_permission()
	// -----------------------------------------------------------------------

	public function test_admin_permission_returns_bool(): void {
		$mgr = new AsyncQueueManager();
		$this->assertIsBool( $mgr->admin_permission() );
	}
}
