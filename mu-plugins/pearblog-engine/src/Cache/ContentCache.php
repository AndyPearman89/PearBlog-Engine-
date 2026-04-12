<?php
/**
 * Content caching layer using the WordPress Transients API.
 *
 * Reduces redundant AI API calls and expensive DB queries by caching:
 *  - AI-generated article content (keyed by topic + profile hash)
 *  - SEO data (meta descriptions, schema)
 *  - Duplicate-detection hashes
 *  - Internal-linking candidate posts
 *
 * All cached items are namespaced under the prefix 'pb_cache_' to avoid
 * collisions with other plugins.
 *
 * @package PearBlogEngine\Cache
 */

declare(strict_types=1);

namespace PearBlogEngine\Cache;

/**
 * Thin caching wrapper for PearBlog Engine.
 */
class ContentCache {

	// ------------------------------------------------------------------
	// Constants
	// ------------------------------------------------------------------

	/** Default TTL for AI-generated content (1 hour). */
	public const TTL_AI_CONTENT = HOUR_IN_SECONDS;

	/** TTL for SEO meta data (6 hours). */
	public const TTL_SEO = 6 * HOUR_IN_SECONDS;

	/** TTL for internal-linking candidate list (30 min). */
	public const TTL_LINKS = 30 * MINUTE_IN_SECONDS;

	/** TTL for duplicate-detection hashes (12 hours). */
	public const TTL_DUPLICATES = 12 * HOUR_IN_SECONDS;

	/** TTL for general-purpose cache entries (1 hour). */
	public const TTL_DEFAULT = HOUR_IN_SECONDS;

	/** Transient key prefix. */
	private const PREFIX = 'pb_cache_';

	/** WP option holding cache statistics. */
	public const OPTION_STATS = 'pearblog_cache_stats';

	// ------------------------------------------------------------------
	// Core get / set / delete
	// ------------------------------------------------------------------

	/**
	 * Retrieve a cached value.
	 *
	 * @param string $key Logical cache key (will be namespaced automatically).
	 * @return mixed      Cached value, or false if not found.
	 */
	public function get( string $key ) {
		$value = get_transient( $this->transient_key( $key ) );

		if ( false !== $value ) {
			$this->increment_stat( 'hits' );
		} else {
			$this->increment_stat( 'misses' );
		}

		return $value;
	}

	/**
	 * Store a value in the cache.
	 *
	 * @param string $key        Logical cache key.
	 * @param mixed  $value      Value to store (must be serialisable).
	 * @param int    $expiration TTL in seconds.
	 * @return bool
	 */
	public function set( string $key, $value, int $expiration = self::TTL_DEFAULT ): bool {
		$result = set_transient( $this->transient_key( $key ), $value, $expiration );
		$this->increment_stat( 'writes' );
		return $result;
	}

	/**
	 * Delete a cached entry.
	 *
	 * @param string $key Logical cache key.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		return delete_transient( $this->transient_key( $key ) );
	}

	// ------------------------------------------------------------------
	// Convenience helpers
	// ------------------------------------------------------------------

	/**
	 * Cache AI-generated content keyed by topic + site profile hash.
	 *
	 * @param string $topic   Article topic.
	 * @param string $profile Serialised/hashed site profile string.
	 * @param string $content Generated article content.
	 */
	public function set_ai_content( string $topic, string $profile, string $content ): void {
		$this->set( 'ai_' . $this->content_key( $topic, $profile ), $content, self::TTL_AI_CONTENT );
	}

	/**
	 * Retrieve cached AI content.
	 *
	 * @param string $topic   Article topic.
	 * @param string $profile Serialised/hashed site profile string.
	 * @return string|false   Cached content or false if not cached.
	 */
	public function get_ai_content( string $topic, string $profile ) {
		return $this->get( 'ai_' . $this->content_key( $topic, $profile ) );
	}

	/**
	 * Cache SEO meta description for a post.
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $description Meta description string.
	 */
	public function set_seo_meta( int $post_id, string $description ): void {
		$this->set( 'seo_meta_' . $post_id, $description, self::TTL_SEO );
	}

	/**
	 * Retrieve cached SEO meta description.
	 *
	 * @param int $post_id Post ID.
	 * @return string|false
	 */
	public function get_seo_meta( int $post_id ) {
		return $this->get( 'seo_meta_' . $post_id );
	}

	/**
	 * Cache internal-linking candidates for a post.
	 *
	 * @param int   $post_id    Post ID.
	 * @param array $candidates Array of candidate post data.
	 */
	public function set_link_candidates( int $post_id, array $candidates ): void {
		$this->set( 'links_' . $post_id, $candidates, self::TTL_LINKS );
	}

	/**
	 * Retrieve cached internal-linking candidates.
	 *
	 * @param int $post_id Post ID.
	 * @return array|false
	 */
	public function get_link_candidates( int $post_id ) {
		return $this->get( 'links_' . $post_id );
	}

	/**
	 * Cache a duplicate-detection hash.
	 *
	 * @param string $hash    Content fingerprint.
	 * @param int    $post_id Post ID the hash belongs to.
	 */
	public function set_duplicate_hash( string $hash, int $post_id ): void {
		$this->set( 'dup_' . $hash, $post_id, self::TTL_DUPLICATES );
	}

	/**
	 * Look up a duplicate-detection hash.
	 *
	 * @param string $hash Content fingerprint.
	 * @return int|false   Post ID of the matching post, or false.
	 */
	public function get_duplicate_hash( string $hash ) {
		return $this->get( 'dup_' . $hash );
	}

	// ------------------------------------------------------------------
	// Cache management
	// ------------------------------------------------------------------

	/**
	 * Flush all PearBlog Engine cache entries.
	 *
	 * Because the WordPress Transients API does not support prefix-based
	 * deletion, this method uses an internal tracking list stored in the
	 * 'pearblog_cache_keys' option.
	 *
	 * @return int Number of entries deleted.
	 */
	public function flush(): int {
		$keys    = (array) get_option( 'pearblog_cache_keys', [] );
		$deleted = 0;

		foreach ( $keys as $transient_key ) {
			if ( delete_transient( $transient_key ) ) {
				$deleted++;
			}
		}

		delete_option( 'pearblog_cache_keys' );
		$this->reset_stats();

		return $deleted;
	}

	// ------------------------------------------------------------------
	// Statistics
	// ------------------------------------------------------------------

	/**
	 * Return cache statistics (hits, misses, writes, hit rate).
	 *
	 * @return array<string, int|float>
	 */
	public function get_stats(): array {
		$stats    = (array) get_option( self::OPTION_STATS, [] );
		$hits     = (int) ( $stats['hits'] ?? 0 );
		$misses   = (int) ( $stats['misses'] ?? 0 );
		$writes   = (int) ( $stats['writes'] ?? 0 );
		$total    = $hits + $misses;
		$hit_rate = $total > 0 ? round( $hits / $total * 100, 1 ) : 0.0;

		return [
			'hits'     => $hits,
			'misses'   => $misses,
			'writes'   => $writes,
			'total'    => $total,
			'hit_rate' => $hit_rate,
		];
	}

	/**
	 * Reset statistics counters.
	 */
	public function reset_stats(): void {
		delete_option( self::OPTION_STATS );
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	/**
	 * Build a transient key from a logical cache key.
	 * Keys are MD5-hashed so they never exceed WordPress's 172-char limit.
	 *
	 * @param string $key Logical key.
	 * @return string     Transient key.
	 */
	private function transient_key( string $key ): string {
		$transient = self::PREFIX . md5( $key );

		// Track the key so flush() can clean up later.
		$keys = (array) get_option( 'pearblog_cache_keys', [] );
		if ( ! in_array( $transient, $keys, true ) ) {
			$keys[] = $transient;
			// Keep list bounded to 5000 entries.
			if ( count( $keys ) > 5000 ) {
				$keys = array_slice( $keys, -5000 );
			}
			update_option( 'pearblog_cache_keys', $keys, false );
		}

		return $transient;
	}

	/**
	 * Build a deterministic hash for AI content caching.
	 *
	 * @param string $topic   Topic string.
	 * @param string $profile Profile descriptor string.
	 * @return string
	 */
	private function content_key( string $topic, string $profile ): string {
		return md5( $topic . '|' . $profile );
	}

	/**
	 * Atomically increment a stats counter.
	 *
	 * @param string $stat Counter name (hits, misses, writes).
	 */
	private function increment_stat( string $stat ): void {
		$stats          = (array) get_option( self::OPTION_STATS, [] );
		$stats[ $stat ] = ( (int) ( $stats[ $stat ] ?? 0 ) ) + 1;
		update_option( self::OPTION_STATS, $stats, false );
	}
}
