<?php
/**
 * Unit tests for AsyncQueueManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Pipeline\AsyncQueueManager;

class AsyncQueueManagerTest extends TestCase {

	private AsyncQueueManager $queue;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_actions']    = [];
		$this->queue = new AsyncQueueManager();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_status_pending_constant(): void {
		$this->assertSame( 'pending', AsyncQueueManager::STATUS_PENDING );
	}

	public function test_status_processing_constant(): void {
		$this->assertSame( 'processing', AsyncQueueManager::STATUS_PROCESSING );
	}

	public function test_status_done_constant(): void {
		$this->assertSame( 'done', AsyncQueueManager::STATUS_DONE );
	}

	public function test_status_failed_constant(): void {
		$this->assertSame( 'failed', AsyncQueueManager::STATUS_FAILED );
	}

	public function test_status_dead_constant(): void {
		$this->assertSame( 'dead_letter', AsyncQueueManager::STATUS_DEAD );
	}

	public function test_default_max_retries_constant(): void {
		$this->assertSame( 3, AsyncQueueManager::DEFAULT_MAX_RETRIES );
	}

	public function test_default_batch_size_constant(): void {
		$this->assertSame( 5, AsyncQueueManager::DEFAULT_BATCH_SIZE );
	}

	// -----------------------------------------------------------------------
	// get_backend
	// -----------------------------------------------------------------------

	public function test_get_backend_defaults_to_wp_cron(): void {
		$this->assertSame( 'wp_cron', $this->queue->get_backend() );
	}

	public function test_get_backend_returns_option_value(): void {
		$GLOBALS['_options'][ AsyncQueueManager::OPTION_BACKEND ] = 'database';
		$this->assertSame( 'database', $this->queue->get_backend() );
	}

	// -----------------------------------------------------------------------
	// push with wp_cron backend
	// -----------------------------------------------------------------------

	public function test_push_with_wp_cron_backend_returns_string_or_int(): void {
		// Default backend is wp_cron; push schedules a cron event and returns the job ID.
		$result = $this->queue->push( 'test_job', [ 'foo' => 'bar' ] );
		$this->assertTrue( is_string( $result ) || is_int( $result ) );
	}

	public function test_push_returns_non_empty_id(): void {
		$result = $this->queue->push( 'generate_content', [ 'post_id' => 42 ] );
		$this->assertNotSame( '', $result );
	}

	// -----------------------------------------------------------------------
	// get_stats
	// -----------------------------------------------------------------------

	public function test_get_stats_returns_array(): void {
		$stats = $this->queue->get_stats();
		$this->assertIsArray( $stats );
	}

	public function test_get_stats_has_pending_key(): void {
		$stats = $this->queue->get_stats();
		$this->assertArrayHasKey( 'pending', $stats );
	}

	public function test_get_stats_has_processing_key(): void {
		$stats = $this->queue->get_stats();
		$this->assertArrayHasKey( 'processing', $stats );
	}

	public function test_get_stats_has_failed_key(): void {
		$stats = $this->queue->get_stats();
		$this->assertArrayHasKey( 'failed', $stats );
	}

	public function test_get_stats_has_dead_key(): void {
		$stats = $this->queue->get_stats();
		$this->assertArrayHasKey( 'dead', $stats );
	}

	public function test_get_stats_has_backend_key(): void {
		$stats = $this->queue->get_stats();
		$this->assertArrayHasKey( 'backend', $stats );
	}

	// -----------------------------------------------------------------------
	// execute_job — fires action
	// -----------------------------------------------------------------------

	public function test_execute_job_fires_typed_action(): void {
		$fired = false;
		add_action( 'pearblog_async_job_test_type', function () use ( &$fired ) {
			$fired = true;
		} );
		$job = [
			'type'       => 'test_type',
			'payload'    => [],
			'priority'   => 10,
			'created_at' => time(),
			'attempts'   => 0,
			'status'     => AsyncQueueManager::STATUS_PENDING,
		];
		$this->queue->execute_job( $job );
		$this->assertTrue( $fired );
	}

	public function test_execute_job_fires_completed_action(): void {
		$fired = false;
		add_action( 'pearblog_bg_job_completed', function () use ( &$fired ) {
			$fired = true;
		} );
		$job = [
			'type'       => 'noop_job',
			'payload'    => [],
			'priority'   => 10,
			'created_at' => time(),
			'attempts'   => 0,
			'status'     => AsyncQueueManager::STATUS_PENDING,
		];
		$this->queue->execute_job( $job );
		$this->assertTrue( $fired );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_hooks(): void {
		$this->queue->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}

	// -----------------------------------------------------------------------
	// REST endpoints
	// -----------------------------------------------------------------------

	public function test_rest_queue_status_returns_200(): void {
		$req    = new \WP_REST_Request();
		$result = $this->queue->rest_queue_status( $req );
		$this->assertSame( 200, $result->get_status() );
	}

	public function test_rest_queue_status_returns_response(): void {
		$req    = new \WP_REST_Request();
		$result = $this->queue->rest_queue_status( $req );
		$this->assertInstanceOf( \WP_REST_Response::class, $result );
	}
}
