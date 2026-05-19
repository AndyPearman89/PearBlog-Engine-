<?php
/**
 * Topic queue – stores and retrieves article topics for a tenant.
 *
 * Topics are persisted as a WordPress option on a per-site basis so that
 * multisite installs keep each blog's queue isolated.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * FIFO queue of topic strings for a given site.
 */
class TopicQueue {

	private const OPTION_KEY = 'pearblog_topic_queue';

	/** @var int WordPress blog ID. */
	private int $site_id;

	public function __construct( int $site_id ) {
		$this->site_id = $site_id;
	}

	/**
	 * Add one or more topics to the end of the queue.
	 *
	 * @param string ...$topics Topic strings to enqueue.
	 */
	public function push( string ...$topics ): void {
		$queue = $this->all();
		foreach ( $topics as $topic ) {
			$topic = trim( $topic );
			if ( '' !== $topic ) {
				$queue[] = $topic;
			}
		}
		$this->save( $queue );
	}

	/**
	 * Remove and return the next topic, or null when the queue is empty.
	 */
	public function pop(): ?string {
		$queue = $this->all();
		if ( empty( $queue ) ) {
			return null;
		}
		$topic = array_shift( $queue );
		$this->save( $queue );
		return $topic;
	}

	/**
	 * Peek at the next topic without removing it.
	 */
	public function peek(): ?string {
		$queue = $this->all();
		return $queue[0] ?? null;
	}

	/**
	 * Return all topics currently in the queue (FIFO order).
	 *
	 * @return string[]
	 */
	public function all(): array {
		$raw = $this->is_multisite_enabled()
			? \get_blog_option( $this->site_id, self::OPTION_KEY, [] )
			: \get_option( self::OPTION_KEY, [] );
		return is_array( $raw ) ? array_values( $raw ) : [];
	}

	/**
	 * Backward-compatible alias used by legacy admin code.
	 *
	 * @return string[]
	 */
	public function get_all(): array {
		return $this->all();
	}

	/**
	 * Return the number of topics waiting in the queue.
	 */
	public function count(): int {
		return count( $this->all() );
	}

	/**
	 * Empty the queue entirely.
	 */
	public function clear(): void {
		$this->save( [] );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function save( array $queue ): void {
		if ( $this->is_multisite_enabled() ) {
			\update_blog_option( $this->site_id, self::OPTION_KEY, array_values( $queue ) );
			return;
		}

		\update_option( self::OPTION_KEY, array_values( $queue ) );
	}

	private function is_multisite_enabled(): bool {
		$has_blog_options = \function_exists( 'get_blog_option' ) && \function_exists( 'update_blog_option' );
		if ( \function_exists( 'is_multisite' ) ) {
			return \is_multisite() || $has_blog_options;
		}

		return $has_blog_options;
	}
}
