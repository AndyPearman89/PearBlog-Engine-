<?php
/**
 * Unit tests for DistributedLockManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Core\DistributedLockManager;

class DistributedLockManagerTest extends TestCase {

	private DistributedLockManager $lock;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$this->lock = new DistributedLockManager();
	}

	// -----------------------------------------------------------------------
	// acquire / release
	// -----------------------------------------------------------------------

	public function test_acquire_returns_true_when_lock_is_free(): void {
		$result = $this->lock->acquire( 'pipeline_run' );

		$this->assertTrue( $result );
	}

	public function test_acquire_returns_false_when_lock_already_held(): void {
		$this->lock->acquire( 'pipeline_run' );
		$second = $this->lock->acquire( 'pipeline_run' );

		$this->assertFalse( $second );
	}

	public function test_different_lock_names_do_not_conflict(): void {
		$this->lock->acquire( 'lock_a' );
		$result = $this->lock->acquire( 'lock_b' );

		$this->assertTrue( $result );
	}

	public function test_release_returns_true_after_acquire(): void {
		$this->lock->acquire( 'pipeline_run' );
		$released = $this->lock->release( 'pipeline_run' );

		$this->assertTrue( $released );
	}

	public function test_release_returns_false_when_not_held(): void {
		$released = $this->lock->release( 'never_acquired' );

		$this->assertFalse( $released );
	}

	public function test_lock_can_be_reacquired_after_release(): void {
		$this->lock->acquire( 'pipeline_run' );
		$this->lock->release( 'pipeline_run' );
		$result = $this->lock->acquire( 'pipeline_run' );

		$this->assertTrue( $result );
	}

	// -----------------------------------------------------------------------
	// is_locked
	// -----------------------------------------------------------------------

	public function test_is_locked_false_when_no_lock_held(): void {
		$this->assertFalse( $this->lock->is_locked( 'pipeline_run' ) );
	}

	public function test_is_locked_true_after_acquire(): void {
		$this->lock->acquire( 'pipeline_run' );

		$this->assertTrue( $this->lock->is_locked( 'pipeline_run' ) );
	}

	public function test_is_locked_false_after_release(): void {
		$this->lock->acquire( 'pipeline_run' );
		$this->lock->release( 'pipeline_run' );

		$this->assertFalse( $this->lock->is_locked( 'pipeline_run' ) );
	}

	// -----------------------------------------------------------------------
	// with_lock
	// -----------------------------------------------------------------------

	public function test_with_lock_executes_callback_and_returns_result(): void {
		$result = $this->lock->with_lock( 'pipeline_run', fn() => 'done' );

		$this->assertSame( 'done', $result );
	}

	public function test_with_lock_releases_lock_after_callback(): void {
		$this->lock->with_lock( 'pipeline_run', fn() => null );

		// Should be able to acquire again.
		$this->assertTrue( $this->lock->acquire( 'pipeline_run' ) );
	}

	public function test_with_lock_returns_null_when_lock_unavailable(): void {
		$this->lock->acquire( 'pipeline_run' );
		$result = $this->lock->with_lock( 'pipeline_run', fn() => 'should not run' );

		$this->assertNull( $result );
	}

	public function test_with_lock_releases_on_exception(): void {
		try {
			$this->lock->with_lock( 'pipeline_run', function () {
				throw new \RuntimeException( 'test error' );
			} );
		} catch ( \RuntimeException $e ) {
			// expected
		}

		// Lock should be released even after exception.
		$this->assertFalse( $this->lock->is_locked( 'pipeline_run' ) );
		$this->assertTrue( $this->lock->acquire( 'pipeline_run' ) );
	}

	// -----------------------------------------------------------------------
	// Multiple lock instances (simulating concurrency)
	// -----------------------------------------------------------------------

	public function test_two_instances_cannot_hold_same_lock(): void {
		$lock1 = new DistributedLockManager();
		$lock2 = new DistributedLockManager();

		$lock1->acquire( 'shared_lock' );
		$result = $lock2->acquire( 'shared_lock' );

		$this->assertFalse( $result );
	}

	// -----------------------------------------------------------------------
	// Transient backend (default)
	// -----------------------------------------------------------------------

	public function test_default_backend_uses_transient(): void {
		$this->lock->acquire( 'my_lock' );

		// The transient should have been set.
		$stored = get_transient( 'pearblog_lock_my_lock' );
		$this->assertNotFalse( $stored );
		$this->assertIsString( $stored );
	}

	public function test_release_clears_transient(): void {
		$this->lock->acquire( 'my_lock' );
		$this->lock->release( 'my_lock' );

		$stored = get_transient( 'pearblog_lock_my_lock' );
		$this->assertFalse( $stored );
	}

	// -----------------------------------------------------------------------
	// OPTION_BACKEND / OPTION_REDIS_URL constants
	// -----------------------------------------------------------------------

	public function test_option_backend_constant(): void {
		$this->assertSame( 'pearblog_lock_backend', DistributedLockManager::OPTION_BACKEND );
	}

	public function test_option_redis_url_constant(): void {
		$this->assertSame( 'pearblog_lock_redis_url', DistributedLockManager::OPTION_REDIS_URL );
	}

	// -----------------------------------------------------------------------
	// Redis backend falls back to transient when Redis unavailable
	// -----------------------------------------------------------------------

	public function test_redis_backend_falls_back_to_transient_when_no_redis_class(): void {
		update_option( DistributedLockManager::OPTION_BACKEND, 'redis' );
		// Redis class is not available in test env → falls back to transient.

		$lock   = new DistributedLockManager();
		$result = $lock->acquire( 'fallback_lock' );

		$this->assertTrue( $result );
	}

	public function test_multiple_locks_can_be_held_simultaneously(): void {
		$lock = new DistributedLockManager();

		$a = $lock->acquire( 'lock_alpha' );
		$b = $lock->acquire( 'lock_beta' );
		$c = $lock->acquire( 'lock_gamma' );

		$this->assertTrue( $a );
		$this->assertTrue( $b );
		$this->assertTrue( $c );
	}
}
