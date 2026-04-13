<?php
/**
 * Object Cache Adapter — upgrades PearBlog Engine's caching layer from
 * WP Transients to the WordPress Object Cache (WP_Object_Cache).
 *
 * When a persistent object cache drop-in (Redis, Memcached, APCu, etc.) is
 * installed, `wp_cache_*` calls hit that backend automatically — giving the
 * plugin sub-millisecond cache access without any extra configuration.
 *
 * When no persistent cache is installed, WordPress falls back to an in-memory
 * store that lives for the duration of the request — identical behaviour to
 * the legacy transient implementation with one important benefit: cache reads
 * no longer issue SQL queries.
 *
 * Design notes
 * ────────────
 * - All keys are stored in the `pearblog` cache group, which can be targeted
 *   by cache prefix rules (e.g. Redis `ACL` patterns, Memcached namespace).
 * - The adapter exposes the same `get/set/delete/flush` surface as
 *   `ContentCache` so it can be used as a drop-in replacement.
 * - Cache groups can be marked as "global" (shared across multisite) by
 *   calling `wp_cache_add_global_groups(['pearblog'])` during plugin boot.
 *   This adapter simply documents the pattern; the Plugin class makes the
 *   call in `register()`.
 *
 * Configuration WP options:
 *   pearblog_object_cache_enabled  – bool, master switch (default true when
 *                                    `wp_using_ext_object_cache()` returns true)
 *   pearblog_object_cache_group    – cache group name (default "pearblog")
 *   pearblog_object_cache_ttl_ai   – AI content TTL seconds (default 3600)
 *   pearblog_object_cache_ttl_seo  – SEO data TTL seconds (default 21600)
 *   pearblog_object_cache_ttl_links– Link candidates TTL seconds (default 1800)
 *
 * @package PearBlogEngine\Cache
 */

declare(strict_types=1);

namespace PearBlogEngine\Cache;

/**
 * WP_Object_Cache wrapper for PearBlog Engine data.
 */
class ObjectCacheAdapter {

	// -----------------------------------------------------------------------
	// Option keys
	// -----------------------------------------------------------------------

	public const OPTION_ENABLED   = 'pearblog_object_cache_enabled';
	public const OPTION_GROUP     = 'pearblog_object_cache_group';
	public const OPTION_TTL_AI    = 'pearblog_object_cache_ttl_ai';
	public const OPTION_TTL_SEO   = 'pearblog_object_cache_ttl_seo';
	public const OPTION_TTL_LINKS = 'pearblog_object_cache_ttl_links';

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	public const DEFAULT_GROUP     = 'pearblog';
	public const DEFAULT_TTL_AI    = 3600;    // 1 hour
	public const DEFAULT_TTL_SEO   = 21600;   // 6 hours
	public const DEFAULT_TTL_LINKS = 1800;    // 30 min
	public const DEFAULT_TTL_DUPES = 43200;   // 12 hours

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register the cache group with WordPress so multisite-aware plugins can
	 * share the group across all sub-sites.
	 *
	 * Call this during plugin boot (before any cache reads/writes).
	 */
	public function register(): void {
		wp_cache_add_global_groups( [ $this->get_group() ] );
	}

	// -----------------------------------------------------------------------
	// Core API
	// -----------------------------------------------------------------------

	/**
	 * Retrieve a cached value.
	 *
	 * @param string $key  Logical cache key.
	 * @return mixed       Cached value, or false on a miss.
	 */
	public function get( string $key ) {
		return wp_cache_get( $this->cache_key( $key ), $this->get_group() );
	}

	/**
	 * Store a value in the object cache.
	 *
	 * @param string $key        Logical cache key.
	 * @param mixed  $value      Value to cache (must be serialisable).
	 * @param int    $expiration TTL in seconds. 0 = never expire (in-memory only).
	 * @return bool
	 */
	public function set( string $key, $value, int $expiration = self::DEFAULT_TTL_AI ): bool {
		return (bool) wp_cache_set( $this->cache_key( $key ), $value, $this->get_group(), $expiration );
	}

	/**
	 * Delete a cached entry.
	 *
	 * @param string $key Logical cache key.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		return (bool) wp_cache_delete( $this->cache_key( $key ), $this->get_group() );
	}

	/**
	 * Flush the entire PearBlog cache group.
	 *
	 * `wp_cache_flush_group()` is available since WordPress 6.1.  On older
	 * installs the method falls back to `wp_cache_flush()` (flushes all
	 * groups) — acceptable because group-scoped flushing is a performance
	 * optimisation, not a correctness requirement.
	 *
	 * @return bool
	 */
	public function flush_group(): bool {
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			return (bool) wp_cache_flush_group( $this->get_group() );
		}

		return (bool) wp_cache_flush();
	}

	// -----------------------------------------------------------------------
	// Typed convenience helpers
	// -----------------------------------------------------------------------

	/**
	 * Cache AI-generated article content.
	 *
	 * @param string $topic   Topic string.
	 * @param string $profile Site profile hash / descriptor.
	 * @param string $content Generated article HTML/Markdown.
	 */
	public function set_ai_content( string $topic, string $profile, string $content ): void {
		$ttl = (int) get_option( self::OPTION_TTL_AI, self::DEFAULT_TTL_AI );
		$this->set( 'ai_' . $this->content_key( $topic, $profile ), $content, $ttl );
	}

	/**
	 * Retrieve cached AI content.
	 *
	 * @param string $topic
	 * @param string $profile
	 * @return string|false
	 */
	public function get_ai_content( string $topic, string $profile ) {
		return $this->get( 'ai_' . $this->content_key( $topic, $profile ) );
	}

	/**
	 * Cache SEO meta description for a post.
	 *
	 * @param int    $post_id
	 * @param string $description
	 */
	public function set_seo_meta( int $post_id, string $description ): void {
		$ttl = (int) get_option( self::OPTION_TTL_SEO, self::DEFAULT_TTL_SEO );
		$this->set( 'seo_meta_' . $post_id, $description, $ttl );
	}

	/**
	 * Retrieve cached SEO meta description.
	 *
	 * @param int $post_id
	 * @return string|false
	 */
	public function get_seo_meta( int $post_id ) {
		return $this->get( 'seo_meta_' . $post_id );
	}

	/**
	 * Cache internal-linking candidates.
	 *
	 * @param int   $post_id
	 * @param array $candidates
	 */
	public function set_link_candidates( int $post_id, array $candidates ): void {
		$ttl = (int) get_option( self::OPTION_TTL_LINKS, self::DEFAULT_TTL_LINKS );
		$this->set( 'links_' . $post_id, $candidates, $ttl );
	}

	/**
	 * Retrieve cached internal-linking candidates.
	 *
	 * @param int $post_id
	 * @return array|false
	 */
	public function get_link_candidates( int $post_id ) {
		return $this->get( 'links_' . $post_id );
	}

	/**
	 * Cache a duplicate-detection hash → post ID mapping.
	 *
	 * @param string $hash
	 * @param int    $post_id
	 */
	public function set_duplicate_hash( string $hash, int $post_id ): void {
		$this->set( 'dup_' . $hash, $post_id, self::DEFAULT_TTL_DUPES );
	}

	/**
	 * Retrieve a duplicate-detection hash mapping.
	 *
	 * @param string $hash
	 * @return int|false
	 */
	public function get_duplicate_hash( string $hash ) {
		return $this->get( 'dup_' . $hash );
	}

	// -----------------------------------------------------------------------
	// Configuration accessors
	// -----------------------------------------------------------------------

	/**
	 * Whether a persistent external object cache is active.
	 *
	 * This is a passthrough for `wp_using_ext_object_cache()` so it can be
	 * mocked in tests.
	 */
	public function is_persistent(): bool {
		return function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache();
	}

	/**
	 * Get the configured cache group name.
	 */
	public function get_group(): string {
		return (string) get_option( self::OPTION_GROUP, self::DEFAULT_GROUP );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Derive a normalised cache key from a logical key string.
	 * The key is MD5-hashed to avoid length or character restrictions.
	 */
	public function cache_key( string $logical_key ): string {
		return md5( $logical_key );
	}

	/**
	 * Build a deterministic hash for AI content.
	 */
	private function content_key( string $topic, string $profile ): string {
		return md5( $topic . '|' . $profile );
	}
}
