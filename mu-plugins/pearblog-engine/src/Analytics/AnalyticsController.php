<?php
/**
 * Analytics REST Controller — exposes AnalyticsDashboard data via /pearblog/v1/analytics.
 *
 * Endpoints:
 *   GET  /analytics/summary         – site-wide GA4 summary (total views, last sync)
 *   GET  /analytics/top-posts       – top N posts by performance score
 *   POST /analytics/sync            – trigger a full GA4 sync for all posts
 *   POST /analytics/sync/{post_id}  – sync a single post
 *   GET  /analytics/predictive      – predictive insights (trending, at-risk, refresh candidates)
 *   GET  /analytics/export          – export post analytics data as JSON
 *
 * Authentication: manage_options capability or Bearer API key.
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * REST API controller for the analytics sub-system.
 */
class AnalyticsController {

	public const REST_NAMESPACE = 'pearblog/v1';
	public const REST_BASE      = '/analytics';

	private AnalyticsDashboard $dashboard;

	public function __construct( ?AnalyticsDashboard $dashboard = null ) {
		$this->dashboard = $dashboard ?? new AnalyticsDashboard();
	}

	// -------------------------------------------------------------------------
	// Registration
	// -------------------------------------------------------------------------

	public function register_routes(): void {
		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/summary', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_summary' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/top-posts', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_top_posts' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'limit' => [
					'type'    => 'integer',
					'default' => 20,
					'minimum' => 1,
					'maximum' => 100,
				],
			],
		] );

		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/sync', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'sync_all' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/sync/(?P<post_id>\\d+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'sync_post' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/predictive', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_predictive' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/export', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'export_data' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'limit' => [
					'type'    => 'integer',
					'default' => 100,
					'minimum' => 1,
					'maximum' => 1000,
				],
			],
		] );
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * GET /analytics/summary
	 */
	public function get_summary( \WP_REST_Request $request ): \WP_REST_Response {
		$summary = $this->dashboard->get_summary();

		// Enrich with post count.
		$summary['published_posts'] = (int) wp_count_posts()->publish;
		$summary['last_refresh']    = (string) get_option( 'pearblog_analytics_last_sync', 'never' );

		return new \WP_REST_Response( $summary, 200 );
	}

	/**
	 * GET /analytics/top-posts?limit=N
	 */
	public function get_top_posts( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = (int) $request->get_param( 'limit' );
		$posts = $this->dashboard->get_top_performing_posts( $limit );

		// Enrich with post URL.
		foreach ( $posts as &$post ) {
			$post['url'] = get_permalink( $post['post_id'] ) ?: '';
		}
		unset( $post );

		return new \WP_REST_Response( [
			'count' => count( $posts ),
			'posts' => $posts,
		], 200 );
	}

	/**
	 * POST /analytics/sync — bulk sync all posts with GA4.
	 */
	public function sync_all( \WP_REST_Request $request ): \WP_REST_Response {
		$updated = $this->dashboard->sync_all_posts();

		return new \WP_REST_Response( [
			'synced'    => $updated,
			'synced_at' => gmdate( 'Y-m-d H:i:s' ),
		], 200 );
	}

	/**
	 * POST /analytics/sync/{post_id} — sync a single post.
	 */
	public function sync_post( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		$post    = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return new \WP_REST_Response( [ 'error' => 'Post not found.' ], 404 );
		}

		$this->dashboard->sync_post( $post_id );

		return new \WP_REST_Response( [
			'post_id'   => $post_id,
			'views_30d' => (int) get_post_meta( $post_id, AnalyticsDashboard::META_VIEWS_30D, true ),
			'views_7d'  => (int) get_post_meta( $post_id, AnalyticsDashboard::META_VIEWS_7D,  true ),
			'synced_at' => gmdate( 'Y-m-d H:i:s' ),
		], 200 );
	}

	/**
	 * GET /analytics/predictive — trending, at-risk, and refresh candidates.
	 */
	public function get_predictive( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		// Trending: posts with highest 7d views relative to 30d average.
		$posts = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'meta_key'       => AnalyticsDashboard::META_VIEWS_30D,
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		] );

		$trending       = [];
		$at_risk        = [];
		$refresh_needed = [];

		foreach ( $posts as $post_id ) {
			$views_30d    = (int) get_post_meta( $post_id, AnalyticsDashboard::META_VIEWS_30D, true );
			$views_7d     = (int) get_post_meta( $post_id, AnalyticsDashboard::META_VIEWS_7D,  true );
			$refreshed_at = (string) get_post_meta( $post_id, '_pearblog_refreshed_at', true );
			$quality      = (float) get_post_meta( $post_id, '_pearblog_quality_score', true );
			$trend        = (string) get_post_meta( $post_id, '_pearblog_traffic_trend', true );

			// Momentum score: 7d pace vs 30d average.
			$daily_avg_30d = $views_30d / 30;
			$daily_avg_7d  = $views_7d  / 7;
			$momentum      = $daily_avg_30d > 0 ? round( $daily_avg_7d / $daily_avg_30d, 2 ) : 0.0;

			$row = [
				'post_id'      => (int) $post_id,
				'title'        => get_the_title( $post_id ),
				'url'          => get_permalink( $post_id ) ?: '',
				'views_30d'    => $views_30d,
				'views_7d'     => $views_7d,
				'momentum'     => $momentum,
				'quality'      => $quality,
				'traffic_trend'=> $trend ?: 'unknown',
			];

			// Trending: momentum > 1.5 (accelerating last 7 days vs. 30d baseline).
			if ( $momentum >= 1.5 && count( $trending ) < 10 ) {
				$trending[] = $row;
			}

			// At-risk: declining traffic or momentum < 0.5.
			if ( ( 'declining' === $trend || $momentum < 0.5 ) && $views_30d > 50 && count( $at_risk ) < 10 ) {
				$at_risk[] = $row;
			}

			// Refresh candidates: refreshed > 90 days ago (or never) + quality < 80.
			$days_since_refresh = PHP_INT_MAX;
			if ( '' !== $refreshed_at ) {
				$days_since_refresh = (int) floor( ( time() - strtotime( $refreshed_at ) ) / DAY_IN_SECONDS );
			}

			if ( $days_since_refresh > 90 && $quality < 80 && count( $refresh_needed ) < 10 ) {
				$row['days_since_refresh'] = $days_since_refresh === PHP_INT_MAX ? 'never' : $days_since_refresh;
				$refresh_needed[] = $row;
			}
		}

		return new \WP_REST_Response( [
			'trending'        => $trending,
			'at_risk'         => $at_risk,
			'refresh_needed'  => $refresh_needed,
			'generated_at'    => gmdate( 'Y-m-d H:i:s' ),
		], 200 );
	}

	/**
	 * GET /analytics/export?limit=N — export post analytics as JSON.
	 */
	public function export_data( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = (int) $request->get_param( 'limit' );

		$post_ids = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$rows = [];
		foreach ( $post_ids as $post_id ) {
			$rows[] = [
				'post_id'          => (int) $post_id,
				'title'            => get_the_title( $post_id ),
				'url'              => get_permalink( $post_id ) ?: '',
				'published'        => get_post_field( 'post_date_gmt', $post_id ),
				'views_30d'        => (int)   get_post_meta( $post_id, AnalyticsDashboard::META_VIEWS_30D,  true ),
				'views_7d'         => (int)   get_post_meta( $post_id, AnalyticsDashboard::META_VIEWS_7D,   true ),
				'quality_score'    => (float) get_post_meta( $post_id, '_pearblog_quality_score',            true ),
				'refresh_count'    => (int)   get_post_meta( $post_id, '_pearblog_refresh_count',            true ),
				'refreshed_at'     => (string)get_post_meta( $post_id, '_pearblog_refreshed_at',             true ),
				'traffic_trend'    => (string)get_post_meta( $post_id, '_pearblog_traffic_trend',            true ),
				'ga4_updated_at'   => (string)get_post_meta( $post_id, AnalyticsDashboard::META_UPDATED_AT, true ),
			];
		}

		return new \WP_REST_Response( [
			'count'        => count( $rows ),
			'exported_at'  => gmdate( 'Y-m-d H:i:s' ),
			'posts'        => $rows,
		], 200 );
	}

	// -------------------------------------------------------------------------
	// Permission
	// -------------------------------------------------------------------------

	public function check_permission( \WP_REST_Request $request ): bool {
		if ( function_exists( '\current_user_can' ) && \current_user_can( 'manage_options' ) ) {
			return true;
		}

		$api_key = (string) get_option( 'pearblog_api_key', '' );
		if ( '' === $api_key ) {
			return false;
		}

		$auth  = (string) ( $request->get_header( 'authorization' ) ?? '' );
		$token = str_starts_with( strtolower( $auth ), 'bearer ' ) ? substr( $auth, 7 ) : '';

		return hash_equals( $api_key, $token );
	}
}
