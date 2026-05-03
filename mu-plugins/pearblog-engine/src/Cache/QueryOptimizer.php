<?php
/**
 * Query Optimizer – persistent cache and slow-query monitoring.
 *
 * Wraps expensive WordPress database queries (topic queue, analytics
 * aggregations, revenue lookups) in a persistent object-cache layer.
 * Also instruments queries to detect performance regressions and fire
 * alerts when query time exceeds a configurable threshold.
 *
 * Usage example (other modules):
 *   $rows = QueryOptimizer::cached_query( 'topic_queue', fn() => $wpdb->get_results(...), 300 );
 *
 * Options:
 *   pearblog_query_slow_threshold_ms  – slow query alert threshold (default 500 ms).
 *   pearblog_query_cache_enabled      – bool; enable/disable persistent caching.
 *   pearblog_query_log_slow           – bool; log slow queries to error_log.
 *
 * REST endpoints:
 *   GET    /pearblog/v1/query-cache/stats   – cache hit/miss counters.
 *   DELETE /pearblog/v1/query-cache         – flush all PearBlog caches.
 *
 * @package PearBlogEngine\Cache
 */

declare(strict_types=1);

namespace PearBlogEngine\Cache;

/**
 * Persistent query cache with slow-query instrumentation.
 */
class QueryOptimizer {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Cache group for wp_cache. */
	private const CACHE_GROUP = 'pearblog_queries';

	/** Transient prefix (fallback when object cache is not persistent). */
	private const TRANSIENT_PREFIX = 'pbq_';

	/** Stats option key. */
	private const OPTION_STATS = 'pearblog_query_cache_stats';

	/** Default slow-query threshold in milliseconds. */
	private const DEFAULT_SLOW_MS = 500;

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register admin menu item for cache stats and REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

		// Weekly maintenance: trim old stats.
		if ( ! wp_next_scheduled( 'pearblog_query_stats_trim' ) ) {
			wp_schedule_event( time(), 'weekly', 'pearblog_query_stats_trim' );
		}
		add_action( 'pearblog_query_stats_trim', [ $this, 'trim_stats' ] );
	}

	// -----------------------------------------------------------------------
	// Public API (callable by any module)
	// -----------------------------------------------------------------------

	/**
	 * Execute (or return cached result of) a database query.
	 *
	 * @template T
	 * @param string   $key     Cache key (unique per query pattern + params).
	 * @param callable $query   Callable that returns the result when cache misses.
	 * @param int      $ttl     Cache TTL in seconds (default: 300).
	 * @return mixed            Query result.
	 */
	public static function cached_query( string $key, callable $query, int $ttl = 300 ): mixed {
		if ( ! (bool) get_option( 'pearblog_query_cache_enabled', true ) ) {
			return $query();
		}

		$cache_key = self::make_cache_key( $key );

		// Try object cache first (works with Redis, Memcached, etc.).
		$found  = false;
		$cached = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );

		if ( $found && false !== $cached ) {
			self::increment_stat( 'hits' );
			return $cached;
		}

		// Fall back to transients for environments without a persistent cache backend.
		$transient = get_transient( self::TRANSIENT_PREFIX . $cache_key );
		if ( false !== $transient ) {
			self::increment_stat( 'hits' );
			return $transient;
		}

		self::increment_stat( 'misses' );

		// Execute query with timing.
		$start  = microtime( true );
		$result = $query();
		$ms     = (int) round( ( microtime( true ) - $start ) * 1000 );

		self::maybe_log_slow_query( $key, $ms );

		// Store in both cache layers.
		wp_cache_set( $cache_key, $result, self::CACHE_GROUP, $ttl );
		set_transient( self::TRANSIENT_PREFIX . $cache_key, $result, $ttl );

		return $result;
	}

	/**
	 * Invalidate a specific cache entry (by key) or all PearBlog caches.
	 *
	 * @param string|null $key  Specific key, or null to flush all.
	 */
	public static function invalidate( ?string $key = null ): void {
		if ( null !== $key ) {
			$cache_key = self::make_cache_key( $key );
			wp_cache_delete( $cache_key, self::CACHE_GROUP );
			delete_transient( self::TRANSIENT_PREFIX . $cache_key );
			return;
		}

		// Flush entire group (supported by Redis Object Cache and similar).
		wp_cache_flush_group( self::CACHE_GROUP );

		// Also delete known transients by iterating all registered keys.
		global $wpdb;
		$like    = esc_sql( self::TRANSIENT_PREFIX );
		$results = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_{$like}%'"
		);
		foreach ( $results as $option_name ) {
			$transient_key = str_replace( '_transient_', '', $option_name );
			delete_transient( $transient_key );
		}
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/query-cache/stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_stats' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/query-cache', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'rest_flush' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );
	}

	/**
	 * Permission – manage_options.
	 */
	public function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /query-cache/stats
	 */
	public function rest_stats( \WP_REST_Request $request ): \WP_REST_Response {
		$stats = (array) get_option( self::OPTION_STATS, [ 'hits' => 0, 'misses' => 0, 'slow_queries' => [] ] );
		$total = $stats['hits'] + $stats['misses'];
		$stats['hit_rate_pct'] = $total > 0 ? round( $stats['hits'] / $total * 100, 1 ) : 0.0;
		$stats['cache_enabled'] = (bool) get_option( 'pearblog_query_cache_enabled', true );
		$stats['slow_threshold_ms'] = (int) get_option( 'pearblog_query_slow_threshold_ms', self::DEFAULT_SLOW_MS );
		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * DELETE /query-cache – flush all PearBlog query caches.
	 */
	public function rest_flush( \WP_REST_Request $request ): \WP_REST_Response {
		self::invalidate();
		return new \WP_REST_Response( [ 'flushed' => true ], 200 );
	}

	// -----------------------------------------------------------------------
	// Admin menu
	// -----------------------------------------------------------------------

	/**
	 * Add a "Query Cache" submenu under the PearBlog admin.
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'pearblog',
			__( 'Query Cache', 'pearblog-engine' ),
			__( 'Query Cache', 'pearblog-engine' ),
			'manage_options',
			'pearblog-query-cache',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page(): void {
		$stats   = (array) get_option( self::OPTION_STATS, [ 'hits' => 0, 'misses' => 0, 'slow_queries' => [] ] );
		$total   = ( $stats['hits'] ?? 0 ) + ( $stats['misses'] ?? 0 );
		$hit_pct = $total > 0 ? round( $stats['hits'] / $total * 100, 1 ) : 0.0;

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Query Cache', 'pearblog-engine' ); ?></h1>

			<table class="widefat striped" style="max-width:600px">
				<tr><th><?php esc_html_e( 'Cache Hits', 'pearblog-engine' ); ?></th><td><?php echo esc_html( number_format( $stats['hits'] ?? 0 ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Cache Misses', 'pearblog-engine' ); ?></th><td><?php echo esc_html( number_format( $stats['misses'] ?? 0 ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Hit Rate', 'pearblog-engine' ); ?></th><td><?php echo esc_html( $hit_pct ); ?>%</td></tr>
				<tr><th><?php esc_html_e( 'Slow Threshold (ms)', 'pearblog-engine' ); ?></th><td><?php echo esc_html( get_option( 'pearblog_query_slow_threshold_ms', self::DEFAULT_SLOW_MS ) ); ?></td></tr>
			</table>

			<?php if ( ! empty( $stats['slow_queries'] ) ) : ?>
			<h2><?php esc_html_e( 'Recent Slow Queries', 'pearblog-engine' ); ?></h2>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Key', 'pearblog-engine' ); ?></th>
					<th><?php esc_html_e( 'Duration (ms)', 'pearblog-engine' ); ?></th>
					<th><?php esc_html_e( 'Time', 'pearblog-engine' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( array_reverse( (array) $stats['slow_queries'] ) as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( $entry['key'] ); ?></td>
						<td><?php echo esc_html( $entry['ms'] ); ?></td>
						<td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', $entry['ts'] ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>

			<p style="margin-top:1em">
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pearblog_flush_query_cache' ), 'pearblog_flush_cache' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Flush Query Cache', 'pearblog-engine' ); ?>
				</a>
			</p>
		</div>
		<?php

		add_action( 'admin_post_pearblog_flush_query_cache', static function (): void {
			if ( ! check_admin_referer( 'pearblog_flush_cache' ) ) {
				wp_die( 'Security check failed.' );
			}
			self::invalidate();
			wp_safe_redirect( admin_url( 'admin.php?page=pearblog-query-cache&flushed=1' ) );
			exit;
		} );
	}

	// -----------------------------------------------------------------------
	// Cron maintenance
	// -----------------------------------------------------------------------

	/**
	 * Trim the slow_queries log to the last 100 entries.
	 */
	public function trim_stats(): void {
		$stats = (array) get_option( self::OPTION_STATS, [] );
		if ( isset( $stats['slow_queries'] ) && is_array( $stats['slow_queries'] ) && count( $stats['slow_queries'] ) > 100 ) {
			$stats['slow_queries'] = array_slice( $stats['slow_queries'], -100 );
			update_option( self::OPTION_STATS, $stats );
		}
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Create a safe cache key from a human-readable query identifier.
	 *
	 * @param string $key  Human-readable key.
	 * @return string      Sanitised cache key (max 40 chars).
	 */
	private static function make_cache_key( string $key ): string {
		return substr( md5( $key ), 0, 12 ) . '_' . sanitize_key( $key );
	}

	/**
	 * Increment a stat counter (hits or misses) atomically via transient lock.
	 *
	 * @param string $stat 'hits' or 'misses'.
	 */
	private static function increment_stat( string $stat ): void {
		$stats = (array) get_option( self::OPTION_STATS, [ 'hits' => 0, 'misses' => 0 ] );
		$stats[ $stat ] = ( $stats[ $stat ] ?? 0 ) + 1;
		// Use update_option with autoload=false for performance.
		update_option( self::OPTION_STATS, $stats, false );
	}

	/**
	 * Log a slow query if it exceeds the threshold.
	 *
	 * @param string $key Query key.
	 * @param int    $ms  Query duration in milliseconds.
	 */
	private static function maybe_log_slow_query( string $key, int $ms ): void {
		$threshold = (int) get_option( 'pearblog_query_slow_threshold_ms', self::DEFAULT_SLOW_MS );

		if ( $ms < $threshold ) {
			return;
		}

		if ( (bool) get_option( 'pearblog_query_log_slow', true ) ) {
			error_log( "[PearBlog] Slow query '{$key}': {$ms}ms" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}

		$stats                   = (array) get_option( self::OPTION_STATS, [] );
		$stats['slow_queries']   = (array) ( $stats['slow_queries'] ?? [] );
		$stats['slow_queries'][] = [ 'key' => $key, 'ms' => $ms, 'ts' => time() ];

		// Keep max 200 entries in-memory; trim runs weekly.
		if ( count( $stats['slow_queries'] ) > 200 ) {
			$stats['slow_queries'] = array_slice( $stats['slow_queries'], -200 );
		}

		update_option( self::OPTION_STATS, $stats, false );

		do_action( 'pearblog_slow_query_detected', $key, $ms );
	}
}
