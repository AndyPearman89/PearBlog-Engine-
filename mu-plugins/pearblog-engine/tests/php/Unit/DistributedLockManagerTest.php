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
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_backend_constant(): void {
		$this->assertSame( 'pearblog_lock_backend', DistributedLockManager::OPTION_BACKEND );
	}

	public function test_option_redis_url_constant(): void {
		$this->assertSame( 'pearblog_lock_redis_url', DistributedLockManager::OPTION_REDIS_URL );
	}

	// -----------------------------------------------------------------------
	// Backend
	// -----------------------------------------------------------------------

	public function test_get_backend_defaults_to_transient(): void {
		$lock = new DistributedLockManager();
		// Acquire a lock to indirectly verify transient backend is used.
		$acquired = $lock->acquire( 'test_default' );
		$this->assertTrue( $acquired );
		// Transient should exist.
		$this->assertNotFalse( get_transient( 'pearblog_lock_test_default' ) );
	}

	public function test_get_backend_respects_option(): void {
		// 'database' falls back to transient if Redis not available.
		$GLOBALS['_options']['pearblog_lock_backend'] = 'transient';
		$lock = new DistributedLockManager();
		$this->assertTrue( $lock->acquire( 'test_backend_opt' ) );
	}

	// -----------------------------------------------------------------------
	// acquire
	// -----------------------------------------------------------------------

	public function test_acquire_returns_true_on_first_call(): void {
		$this->assertTrue( $this->lock->acquire( 'lock_a' ) );
	}

	public function test_acquire_returns_false_when_already_locked(): void {
		$this->lock->acquire( 'lock_b' );
		// A second lock manager trying to acquire the same lock.
		$other = new DistributedLockManager();
		$this->assertFalse( $other->acquire( 'lock_b' ) );
	}

	public function test_acquire_uses_ttl(): void {
		// Just ensure it doesn't throw.
		$acquired = $this->lock->acquire( 'lock_ttl', 60 );
		$this->assertTrue( $acquired );
	}

	// -----------------------------------------------------------------------
	// release
	// -----------------------------------------------------------------------

	public function test_release_returns_true_after_acquire(): void {
		$this->lock->acquire( 'lock_c' );
		$this->assertTrue( $this->lock->release( 'lock_c' ) );
	}

	public function test_release_returns_false_when_lock_not_held(): void {
		$this->assertFalse( $this->lock->release( 'nonexistent_lock' ) );
	}

	public function test_release_removes_transient(): void {
		$this->lock->acquire( 'lock_d' );
		$this->lock->release( 'lock_d' );
		$this->assertFalse( get_transient( 'pearblog_lock_lock_d' ) );
	}

	public function test_release_returns_false_from_different_lock_instance(): void {
		// Acquire with one instance.
		$this->lock->acquire( 'lock_e' );
		// Different instance (no token stored) cannot release.
		$other = new DistributedLockManager();
		$this->assertFalse( $other->release( 'lock_e' ) );
	}

	// -----------------------------------------------------------------------
	// is_locked
	// -----------------------------------------------------------------------

	public function test_is_locked_returns_false_by_default(): void {
		$this->assertFalse( $this->lock->is_locked( 'unset_lock' ) );
	}

	public function test_is_locked_returns_true_after_acquire(): void {
		$this->lock->acquire( 'lock_f' );
		$this->assertTrue( $this->lock->is_locked( 'lock_f' ) );
	}

	public function test_is_locked_returns_false_after_release(): void {
		$this->lock->acquire( 'lock_g' );
		$this->lock->release( 'lock_g' );
		$this->assertFalse( $this->lock->is_locked( 'lock_g' ) );
	}

	// -----------------------------------------------------------------------
	// with_lock
	// -----------------------------------------------------------------------

	public function test_with_lock_executes_callback_and_returns_value(): void {
		$result = $this->lock->with_lock( 'lock_h', fn() => 42 );
		$this->assertSame( 42, $result );
	}

	public function test_with_lock_releases_lock_after_callback(): void {
		$this->lock->with_lock( 'lock_i', fn() => null );
		$this->assertFalse( $this->lock->is_locked( 'lock_i' ) );
	}

	public function test_with_lock_returns_null_when_already_locked(): void {
		$this->lock->acquire( 'lock_j' );
		$other  = new DistributedLockManager();
		$result = $other->with_lock( 'lock_j', fn() => 'should_not_run' );
		$this->assertNull( $result );
	}

	public function test_with_lock_releases_lock_on_exception(): void {
		try {
			$this->lock->with_lock( 'lock_k', function () {
				throw new \RuntimeException( 'Test exception' );
			} );
		} catch ( \RuntimeException ) {
			// expected.
		}
		$this->assertFalse( $this->lock->is_locked( 'lock_k' ) );
	}

	// -----------------------------------------------------------------------
	// Multiple locks
	// -----------------------------------------------------------------------

	public function test_different_locks_are_independent(): void {
		$this->assertTrue( $this->lock->acquire( 'lock_x' ) );
		$this->assertTrue( $this->lock->acquire( 'lock_y' ) );
		$this->assertTrue( $this->lock->is_locked( 'lock_x' ) );
		$this->assertTrue( $this->lock->is_locked( 'lock_y' ) );
	}

	public function test_lock_reacquirable_after_release(): void {
		$this->lock->acquire( 'lock_z' );
		$this->lock->release( 'lock_z' );
		$other = new DistributedLockManager();
		$this->assertTrue( $other->acquire( 'lock_z' ) );
	}
}
