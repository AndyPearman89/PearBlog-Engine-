<?php
/**
 * Unit tests for BackgroundProcessor.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Pipeline\BackgroundProcessor;

class BackgroundProcessorTest extends TestCase {

	private BackgroundProcessor $processor;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']        = [];
		$GLOBALS['_cron_scheduled'] = [];
		$GLOBALS['_actions_fired']  = [];
		$this->processor = new BackgroundProcessor();
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_enabled_by_default(): void {
		$this->assertTrue( $this->processor->is_enabled() );
	}

	public function test_disabled_via_option(): void {
		update_option( BackgroundProcessor::OPTION_ENABLED, false );
		$this->assertFalse( $this->processor->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// dispatch
	// -----------------------------------------------------------------------

	public function test_dispatch_adds_job_to_queue(): void {
		$this->processor->dispatch( 'email marketing tips' );
		$this->assertSame( 1, $this->processor->queue_size() );
	}

	public function test_dispatch_returns_non_empty_job_id(): void {
		$job_id = $this->processor->dispatch( 'email marketing tips' );
		$this->assertNotEmpty( $job_id );
	}

	public function test_dispatch_schedules_cron_event(): void {
		$this->processor->dispatch( 'some topic' );
		$this->assertNotFalse( wp_next_scheduled( BackgroundProcessor::CRON_HOOK ) );
	}

	public function test_dispatch_stores_correct_topic(): void {
		$this->processor->dispatch( 'my awesome topic', 'tenant-1' );
		$queue = $this->processor->get_queue();
		$this->assertSame( 'my awesome topic', $queue[0]['topic'] );
		$this->assertSame( 'tenant-1', $queue[0]['tenant_id'] );
	}

	public function test_dispatch_multiple_jobs_queues_all(): void {
		$this->processor->dispatch( 'topic A' );
		$this->processor->dispatch( 'topic B' );
		$this->processor->dispatch( 'topic C' );
		$this->assertSame( 3, $this->processor->queue_size() );
	}

	// -----------------------------------------------------------------------
	// cancel
	// -----------------------------------------------------------------------

	public function test_cancel_removes_job(): void {
		$id = $this->processor->dispatch( 'to cancel' );
		$this->assertTrue( $this->processor->cancel( $id ) );
		$this->assertSame( 0, $this->processor->queue_size() );
	}

	public function test_cancel_returns_false_for_unknown_id(): void {
		$this->assertFalse( $this->processor->cancel( 'non-existent-id' ) );
	}

	public function test_cancel_only_removes_matching_job(): void {
		$id1 = $this->processor->dispatch( 'keep me' );
		$id2 = $this->processor->dispatch( 'remove me' );
		$this->processor->cancel( $id2 );
		$this->assertSame( 1, $this->processor->queue_size() );
		$queue = $this->processor->get_queue();
		$this->assertSame( $id1, $queue[0]['id'] );
	}

	// -----------------------------------------------------------------------
	// clear_queue
	// -----------------------------------------------------------------------

	public function test_clear_queue_empties_all_jobs(): void {
		$this->processor->dispatch( 'a' );
		$this->processor->dispatch( 'b' );
		$this->processor->clear_queue();
		$this->assertSame( 0, $this->processor->queue_size() );
	}

	// -----------------------------------------------------------------------
	// handle_batch
	// -----------------------------------------------------------------------

	public function test_handle_batch_fires_action_for_each_job(): void {
		$fired_jobs = [];
		add_action( 'pearblog_bg_run_pipeline', function( array $job ) use ( &$fired_jobs ) {
			$fired_jobs[] = $job['topic'];
		} );

		$this->processor->dispatch( 'topic_1' );
		$this->processor->dispatch( 'topic_2' );
		$this->processor->handle_batch();

		$this->assertContains( 'topic_1', $fired_jobs );
		$this->assertContains( 'topic_2', $fired_jobs );
	}

	public function test_handle_batch_clears_processed_jobs_from_queue(): void {
		$this->processor->dispatch( 'topic_a' );
		$this->processor->handle_batch();
		$this->assertSame( 0, $this->processor->queue_size() );
	}

	public function test_handle_batch_respects_max_batch_size(): void {
		update_option( BackgroundProcessor::OPTION_MAX_BATCH, 2 );

		$this->processor->dispatch( 'a' );
		$this->processor->dispatch( 'b' );
		$this->processor->dispatch( 'c' );

		$this->processor->handle_batch();

		// 3 dispatched - 2 processed = 1 remaining.
		$this->assertSame( 1, $this->processor->queue_size() );
	}

	public function test_handle_batch_does_nothing_when_disabled(): void {
		update_option( BackgroundProcessor::OPTION_ENABLED, false );

		$this->processor->dispatch( 'a' );
		$this->processor->handle_batch();

		// Queue is unmodified because handle_batch returned early.
		$this->assertSame( 1, $this->processor->queue_size() );
	}

	public function test_handle_batch_updates_last_run_timestamp(): void {
		$before = time();
		$this->processor->dispatch( 'a' );
		$this->processor->handle_batch();
		$this->assertGreaterThanOrEqual( $before, (int) get_option( BackgroundProcessor::OPTION_LAST_RUN ) );
	}

	// -----------------------------------------------------------------------
	// process_job — failure / retry
	// -----------------------------------------------------------------------

	public function test_process_job_fires_completed_action_on_success(): void {
		$completed = false;
		add_action( 'pearblog_bg_job_completed', function() use ( &$completed ) {
			$completed = true;
		} );

		$this->processor->process_job( [
			'id'        => 'test-job',
			'topic'     => 'test',
			'tenant_id' => '',
			'scheduled_at' => time(),
			'attempts'  => 0,
		] );

		$this->assertTrue( $completed );
	}

	public function test_failed_job_requeued_before_max_attempts(): void {
		update_option( BackgroundProcessor::OPTION_MAX_ATTEMPTS, 3 );

		add_action( 'pearblog_bg_run_pipeline', function() {
			throw new \RuntimeException( 'Simulated failure' );
		} );

		$this->processor->process_job( [
			'id'        => 'fail-job',
			'topic'     => 'fail test',
			'tenant_id' => '',
			'scheduled_at' => time(),
			'attempts'  => 1, // already attempted once; 1 < 3 → should re-queue
		] );

		$this->assertSame( 1, $this->processor->queue_size() );
	}

	public function test_failed_job_fires_failed_action_at_max_attempts(): void {
		update_option( BackgroundProcessor::OPTION_MAX_ATTEMPTS, 3 );

		$failed = false;
		add_action( 'pearblog_bg_job_failed', function() use ( &$failed ) {
			$failed = true;
		} );

		add_action( 'pearblog_bg_run_pipeline', function() {
			throw new \RuntimeException( 'Simulated failure' );
		} );

		$this->processor->process_job( [
			'id'        => 'exhaust-job',
			'topic'     => 'fail test',
			'tenant_id' => '',
			'scheduled_at' => time(),
			'attempts'  => 2, // 2 previous attempts; 2 + 1 = 3 = max → discard
		] );

		$this->assertTrue( $failed );
		$this->assertSame( 0, $this->processor->queue_size() );
	}

	// -----------------------------------------------------------------------
	// queue persistence
	// -----------------------------------------------------------------------

	public function test_queue_persists_across_instances(): void {
		$this->processor->dispatch( 'persisted topic' );

		$new_instance = new BackgroundProcessor();
		$this->assertSame( 1, $new_instance->queue_size() );
		$this->assertSame( 'persisted topic', $new_instance->get_queue()[0]['topic'] );
	}
}
