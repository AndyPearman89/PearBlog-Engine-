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
		$this->lock             = new DistributedLockManager();
	}

	// -----------------------------------------------------------------------
	// acquire — transient backend (default)
	// -----------------------------------------------------------------------

	public function test_acquire_returns_true_when_lock_is_free(): void {
		$this->assertTrue( $this->lock->acquire( 'test_lock' ) );
	}

	public function test_acquire_sets_transient_entry(): void {
		$this->lock->acquire( 'my_lock' );
		$key = 'pearblog_lock_my_lock';
		$this->assertNotFalse( $GLOBALS['_transients'][ $key ] ?? false );
	}

	public function test_acquire_returns_false_when_lock_already_held(): void {
		$this->lock->acquire( 'dup_lock' );
		// A second, independent instance should see the lock via transient.
		$lock2 = new DistributedLockManager();
		$this->assertFalse( $lock2->acquire( 'dup_lock' ) );
	}

	public function test_acquire_same_instance_cannot_double_acquire(): void {
		$this->lock->acquire( 'double_lock' );
		// Same instance — the transient is still set, so acquire fails.
		$this->assertFalse( $this->lock->acquire( 'double_lock' ) );
	}

	public function test_acquire_different_lock_names_are_independent(): void {
		$this->assertTrue( $this->lock->acquire( 'lock_a' ) );
		$this->assertTrue( $this->lock->acquire( 'lock_b' ) );
	}

	// -----------------------------------------------------------------------
	// release
	// -----------------------------------------------------------------------

	public function test_release_returns_true_after_acquire(): void {
		$this->lock->acquire( 'rel_lock' );
		$this->assertTrue( $this->lock->release( 'rel_lock' ) );
	}

	public function test_release_removes_transient(): void {
		$this->lock->acquire( 'rem_lock' );
		$this->lock->release( 'rem_lock' );
		$key = 'pearblog_lock_rem_lock';
		$this->assertFalse( $GLOBALS['_transients'][ $key ] ?? false );
	}

	public function test_release_returns_false_when_lock_not_held(): void {
		$this->assertFalse( $this->lock->release( 'never_acquired' ) );
	}

	public function test_release_does_not_release_another_instances_lock(): void {
		$lock2 = new DistributedLockManager();
		$lock2->acquire( 'shared_lock' );

		// Our lock instance did not acquire it.
		$this->assertFalse( $this->lock->release( 'shared_lock' ) );
		// The lock is still held by lock2.
		$this->assertTrue( $lock2->is_locked( 'shared_lock' ) );
	}

	// -----------------------------------------------------------------------
	// acquire → release → re-acquire cycle
	// -----------------------------------------------------------------------

	public function test_re_acquire_succeeds_after_release(): void {
		$this->lock->acquire( 'cycle_lock' );
		$this->lock->release( 'cycle_lock' );

		$lock2 = new DistributedLockManager();
		$this->assertTrue( $lock2->acquire( 'cycle_lock' ) );
	}

	// -----------------------------------------------------------------------
	// is_locked
	// -----------------------------------------------------------------------

	public function test_is_locked_returns_false_when_no_lock(): void {
		$this->assertFalse( $this->lock->is_locked( 'no_lock' ) );
	}

	public function test_is_locked_returns_true_after_acquire(): void {
		$this->lock->acquire( 'check_lock' );
		$this->assertTrue( $this->lock->is_locked( 'check_lock' ) );
	}

	public function test_is_locked_returns_false_after_release(): void {
		$this->lock->acquire( 'gone_lock' );
		$this->lock->release( 'gone_lock' );
		$this->assertFalse( $this->lock->is_locked( 'gone_lock' ) );
	}

	// -----------------------------------------------------------------------
	// with_lock
	// -----------------------------------------------------------------------

	public function test_with_lock_executes_callback_and_returns_value(): void {
		$result = $this->lock->with_lock( 'cb_lock', function () {
			return 42;
		} );

		$this->assertSame( 42, $result );
	}

	public function test_with_lock_releases_lock_after_callback(): void {
		$this->lock->with_lock( 'auto_rel', function () {
			return 'done';
		} );

		$this->assertFalse( $this->lock->is_locked( 'auto_rel' ) );
	}

	public function test_with_lock_returns_null_when_lock_already_held(): void {
		$lock2 = new DistributedLockManager();
		$lock2->acquire( 'busy_lock' );

		$result = $this->lock->with_lock( 'busy_lock', function () {
			return 'should_not_run';
		} );

		$this->assertNull( $result );
	}

	public function test_with_lock_releases_lock_even_on_exception(): void {
		try {
			$this->lock->with_lock( 'exc_lock', function () {
				throw new \RuntimeException( 'Test error' );
			} );
		} catch ( \RuntimeException ) {
			// Expected.
		}

		$this->assertFalse( $this->lock->is_locked( 'exc_lock' ) );
	}

	// -----------------------------------------------------------------------
	// option keys (constants)
	// -----------------------------------------------------------------------

	public function test_option_backend_constant_is_defined(): void {
		$this->assertSame( 'pearblog_lock_backend', DistributedLockManager::OPTION_BACKEND );
	}

	public function test_option_redis_url_constant_is_defined(): void {
		$this->assertSame( 'pearblog_lock_redis_url', DistributedLockManager::OPTION_REDIS_URL );
	}

	// -----------------------------------------------------------------------
	// backend fallback when set to unknown value
	// -----------------------------------------------------------------------

	public function test_unknown_backend_falls_back_to_transient(): void {
		$GLOBALS['_options'][ DistributedLockManager::OPTION_BACKEND ] = 'unknown';
		$lock = new DistributedLockManager();
		$this->assertTrue( $lock->acquire( 'fallback_lock' ) );
	}
}
