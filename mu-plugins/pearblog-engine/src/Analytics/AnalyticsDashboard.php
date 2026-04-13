<?php
/**
 * Analytics Dashboard — admin tab displaying per-post and site-wide GA4 metrics.
 *
 * Integrates the GA4Client to fetch traffic data and stores per-post view counts
 * as post meta for display in the admin list and the plugin's Analytics tab.
 *
 * Meta keys written:
 *   _pearblog_ga4_views_30d    – page views last 30 days
 *   _pearblog_ga4_views_7d     – page views last 7 days
 *   _pearblog_ga4_updated_at   – timestamp of last sync
 *
 * WP options:
 *   pearblog_analytics_last_sync  – timestamp of last bulk sync
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * Syncs GA4 traffic data to post meta and provides data for the admin Analytics tab.
 */
class AnalyticsDashboard {

	/** Meta keys. */
	public const META_VIEWS_30D   = '_pearblog_ga4_views_30d';
	public const META_VIEWS_7D    = '_pearblog_ga4_views_7d';
	public const META_UPDATED_AT  = '_pearblog_ga4_updated_at';

	/** WP option: last bulk-sync timestamp. */
	public const OPTION_LAST_SYNC = 'pearblog_analytics_last_sync';

	/** WP cron hook for daily analytics sync. */
	public const CRON_HOOK = 'pearblog_analytics_sync';

	/** Normalisation divisor: N views maps to 100 performance points (e.g. 1000 views = 100). */
	public const VIEWS_SCORE_NORMALIZER = 10;

	public function __construct( ?GA4Client $ga4 = null ) {
		$this->ga4 = $ga4 ?? new GA4Client();
	}

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks and cron.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'sync_all_posts' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	// -----------------------------------------------------------------------
	// Sync
	// -----------------------------------------------------------------------

	/**
	 * Bulk-sync GA4 view counts for all published posts.
	 *
	 * @return int  Number of posts updated.
	 */
	public function sync_all_posts(): int {
		if ( ! $this->ga4->is_configured() ) {
			return 0;
		}

		$top_30d = $this->ga4->get_top_posts( 100, '30daysAgo', 'today' );
		$top_7d  = $this->ga4->get_top_posts( 100, '7daysAgo', 'today' );

		$map_30d = [];
		foreach ( $top_30d as $row ) {
			$map_30d[ $row['path'] ] = $row['views'];
		}

		$map_7d = [];
		foreach ( $top_7d as $row ) {
			$map_7d[ $row['path'] ] = $row['views'];
		}

		$post_ids = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );

		$updated = 0;
		foreach ( $post_ids as $post_id ) {
			$path = rtrim( (string) parse_url( get_permalink( (int) $post_id ), PHP_URL_PATH ), '/' ) . '/';

			$views_30d = $map_30d[ $path ] ?? 0;
			$views_7d  = $map_7d[ $path ]  ?? 0;

			update_post_meta( (int) $post_id, self::META_VIEWS_30D,  $views_30d );
			update_post_meta( (int) $post_id, self::META_VIEWS_7D,   $views_7d );
			update_post_meta( (int) $post_id, self::META_UPDATED_AT, gmdate( 'Y-m-d H:i:s' ) );

			$updated++;
		}

		update_option( self::OPTION_LAST_SYNC, gmdate( 'Y-m-d H:i:s' ) );

		return $updated;
	}

	/**
	 * Sync GA4 data for a single post.
	 *
	 * @param int $post_id
	 */
	public function sync_post( int $post_id ): void {
		if ( ! $this->ga4->is_configured() ) {
			return;
		}

		$path = rtrim( (string) parse_url( get_permalink( $post_id ), PHP_URL_PATH ), '/' ) . '/';

		$views_30d = $this->ga4->get_post_views( $path, '30daysAgo', 'today' );
		$views_7d  = $this->ga4->get_post_views( $path, '7daysAgo', 'today' );

		update_post_meta( $post_id, self::META_VIEWS_30D,  $views_30d );
		update_post_meta( $post_id, self::META_VIEWS_7D,   $views_7d );
		update_post_meta( $post_id, self::META_UPDATED_AT, gmdate( 'Y-m-d H:i:s' ) );
	}

	// -----------------------------------------------------------------------
	// Data for admin Analytics tab
	// -----------------------------------------------------------------------

	/**
	 * Get the top N posts by 30-day GA4 views, merged with quality scores.
	 *
	 * @param int $limit
	 * @return array<int, array{post_id: int, title: string, views_30d: int, quality_score: float, performance_score: float}>
	 */
	public function get_top_performing_posts( int $limit = 20 ): array {
		$post_ids = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => $limit * 3, // Over-fetch.
			'fields'         => 'ids',
			'meta_key'       => self::META_VIEWS_30D,
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		] );

		$results = [];
		foreach ( $post_ids as $post_id ) {
			$views_30d     = (int)   get_post_meta( (int) $post_id, self::META_VIEWS_30D, true );
			$quality_score = (float) get_post_meta( (int) $post_id, '_pearblog_quality_score', true );

			// Performance score: geometric blend of views (normalised to 0–100) and quality.
			$views_score      = min( 100, $views_30d / self::VIEWS_SCORE_NORMALIZER );
			$performance_score = round( sqrt( $views_score * $quality_score ), 1 );

			$results[] = [
				'post_id'           => (int) $post_id,
				'title'             => get_the_title( (int) $post_id ),
				'views_30d'         => $views_30d,
				'quality_score'     => $quality_score,
				'performance_score' => $performance_score,
			];

			if ( count( $results ) >= $limit ) {
				break;
			}
		}

		// Sort by performance_score DESC.
		usort( $results, fn( $a, $b ) => $b['performance_score'] <=> $a['performance_score'] );

		return array_slice( $results, 0, $limit );
	}

	/**
	 * Get site-wide summary stats for the Analytics tab header.
	 *
	 * @return array{total_views_30d: int, last_sync: string, ga4_configured: bool}
	 */
	public function get_summary(): array {
		return [
			'total_views_30d' => $this->ga4->is_configured()
				? $this->ga4->get_total_views( '30daysAgo', 'today' )
				: 0,
			'last_sync'       => (string) get_option( self::OPTION_LAST_SYNC, 'never' ),
			'ga4_configured'  => $this->ga4->is_configured(),
		];
	}
}
