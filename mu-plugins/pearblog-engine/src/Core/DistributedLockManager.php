<?php
/**
 * Distributed Lock Manager – prevents duplicate jobs in multi-server setups.
 *
 * Provides an advisory locking mechanism using either WordPress transients
 * (for single-server) or Redis (for multi-server horizontal scaling).
 *
 * Usage:
 *   $lock = new DistributedLockManager();
 *   if ( $lock->acquire( 'pipeline_run', 300 ) ) {
 *       // ... do work ...
 *       $lock->release( 'pipeline_run' );
 *   }
 *
 * Configuration (WP options):
 *   pearblog_lock_backend   – 'transient' (default) | 'redis'
 *   pearblog_lock_redis_url – Redis URL (shares pearblog_async_redis_url if set)
 *
 * @package PearBlogEngine\Core
 */

declare(strict_types=1);

namespace PearBlogEngine\Core;

/**
 * Advisory distributed lock using transients or Redis.
 */
class DistributedLockManager {

	/** WP option keys. */
	public const OPTION_BACKEND   = 'pearblog_lock_backend';
	public const OPTION_REDIS_URL = 'pearblog_lock_redis_url';

	/** Transient key prefix. */
	private const TRANSIENT_PREFIX = 'pearblog_lock_';

	/** Redis key prefix. */
	private const REDIS_PREFIX = 'pearblog:lock:';

	/** @var array<string, string> Active locks held in this process. */
	private array $held_locks = [];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Attempt to acquire a named lock.
	 *
	 * @param string $name    Lock name (alphanumeric + underscores).
	 * @param int    $ttl     Lock TTL in seconds (default: 300).
	 * @return bool True if lock was acquired, false if already held.
	 */
	public function acquire( string $name, int $ttl = 300 ): bool {
		$token    = wp_generate_uuid4();
		$backend  = $this->get_backend();
		$acquired = match ( $backend ) {
			'redis'  => $this->redis_acquire( $name, $token, $ttl ),
			default  => $this->transient_acquire( $name, $token, $ttl ),
		};

		if ( $acquired ) {
			$this->held_locks[ $name ] = $token;
		}

		return $acquired;
	}

	/**
	 * Release a previously acquired lock.
	 *
	 * Only releases the lock if this process holds it (via token comparison).
	 *
	 * @param string $name Lock name.
	 * @return bool True if lock was released.
	 */
	public function release( string $name ): bool {
		$token   = $this->held_locks[ $name ] ?? '';
		if ( '' === $token ) {
			return false;
		}

		$backend  = $this->get_backend();
		$released = match ( $backend ) {
			'redis'  => $this->redis_release( $name, $token ),
			default  => $this->transient_release( $name, $token ),
		};

		if ( $released ) {
			unset( $this->held_locks[ $name ] );
		}

		return $released;
	}

	/**
	 * Check if a lock is currently held.
	 *
	 * @param string $name Lock name.
	 * @return bool
	 */
	public function is_locked( string $name ): bool {
		$backend = $this->get_backend();
		return match ( $backend ) {
			'redis'  => $this->redis_is_locked( $name ),
			default  => false !== get_transient( self::TRANSIENT_PREFIX . $name ),
		};
	}

	/**
	 * Execute a callback with a lock held, then release the lock.
	 *
	 * @param string   $name    Lock name.
	 * @param callable $callback Callable to execute within the lock.
	 * @param int      $ttl     Lock TTL in seconds.
	 * @return mixed Return value of the callback, or null if lock not acquired.
	 */
	public function with_lock( string $name, callable $callback, int $ttl = 300 ): mixed {
		if ( ! $this->acquire( $name, $ttl ) ) {
			return null;
		}

		try {
			return $callback();
		} finally {
			$this->release( $name );
		}
	}

	// -----------------------------------------------------------------------
	// Transient backend
	// -----------------------------------------------------------------------

	/**
	 * Acquire lock via WordPress transient (single-server safe).
	 *
	 * @param string $name  Lock name.
	 * @param string $token Unique token for this lock.
	 * @param int    $ttl   TTL in seconds.
	 * @return bool
	 */
	private function transient_acquire( string $name, string $token, int $ttl ): bool {
		$key     = self::TRANSIENT_PREFIX . $name;
		$existing = get_transient( $key );

		if ( false !== $existing ) {
			// Lock already held.
			return false;
		}

		// Race condition mitigation: set and verify.
		set_transient( $key, $token, $ttl );
		$stored = get_transient( $key );

		return $stored === $token;
	}

	/**
	 * Release lock via WordPress transient.
	 *
	 * @param string $name  Lock name.
	 * @param string $token Token to verify ownership.
	 * @return bool
	 */
	private function transient_release( string $name, string $token ): bool {
		$key     = self::TRANSIENT_PREFIX . $name;
		$stored  = get_transient( $key );

		if ( $stored !== $token ) {
			return false; // Not our lock.
		}

		delete_transient( $key );
		return true;
	}

	// -----------------------------------------------------------------------
	// Redis backend
	// -----------------------------------------------------------------------

	/**
	 * Acquire lock via Redis SET NX EX (atomic).
	 *
	 * @param string $name  Lock name.
	 * @param string $token Unique token.
	 * @param int    $ttl   TTL in seconds.
	 * @return bool
	 */
	private function redis_acquire( string $name, string $token, int $ttl ): bool {
		$redis = $this->get_redis();
		if ( ! $redis ) {
			return $this->transient_acquire( $name, $token, $ttl );
		}

		try {
			// SET key value NX EX ttl – atomic, returns true on success.
			$result = $redis->set(
				self::REDIS_PREFIX . $name,
				$token,
				[ 'NX', 'EX' => $ttl ]
			);
			return (bool) $result;
		} catch ( \Throwable $e ) {
			error_log( 'PearBlog DistributedLock: Redis acquire failed – ' . $e->getMessage() );
			return $this->transient_acquire( $name, $token, $ttl );
		}
	}

	/**
	 * Release Redis lock (Lua script for atomicity).
	 *
	 * @param string $name  Lock name.
	 * @param string $token Token to verify ownership.
	 * @return bool
	 */
	private function redis_release( string $name, string $token ): bool {
		$redis = $this->get_redis();
		if ( ! $redis ) {
			return $this->transient_release( $name, $token );
		}

		try {
			// Lua script: only delete if the value matches our token.
			$script = "
				if redis.call('get', KEYS[1]) == ARGV[1] then
					return redis.call('del', KEYS[1])
				else
					return 0
				end
			";

			$result = $redis->eval( $script, [ self::REDIS_PREFIX . $name, $token ], 1 );
			return (bool) $result;
		} catch ( \Throwable $e ) {
			error_log( 'PearBlog DistributedLock: Redis release failed – ' . $e->getMessage() );
			return $this->transient_release( $name, $token );
		}
	}

	/**
	 * Check if a Redis lock is held.
	 *
	 * @param string $name Lock name.
	 * @return bool
	 */
	private function redis_is_locked( string $name ): bool {
		$redis = $this->get_redis();
		if ( ! $redis ) {
			return false !== get_transient( self::TRANSIENT_PREFIX . $name );
		}

		try {
			return (bool) $redis->exists( self::REDIS_PREFIX . $name );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Get the configured backend.
	 *
	 * @return string 'transient' | 'redis'
	 */
	private function get_backend(): string {
		return (string) get_option( self::OPTION_BACKEND, 'transient' );
	}

	/**
	 * Get a Redis connection instance, or null if unavailable.
	 *
	 * @return \Redis|null
	 */
	private function get_redis(): ?\Redis {
		if ( ! class_exists( 'Redis' ) ) {
			return null;
		}

		$url    = (string) get_option( self::OPTION_REDIS_URL, '' )
			?: (string) get_option( 'pearblog_async_redis_url', 'tcp://127.0.0.1:6379' );
		$parsed = parse_url( $url );

		try {
			$redis = new \Redis();
			$redis->connect( $parsed['host'] ?? '127.0.0.1', $parsed['port'] ?? 6379, 2 );
			return $redis;
		} catch ( \Throwable $e ) {
			return null;
		}
	}
}
