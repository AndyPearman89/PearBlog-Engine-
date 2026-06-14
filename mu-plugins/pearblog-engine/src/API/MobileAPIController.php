<?php
/**
 * Mobile API Controller — V9.0 F4 (Backend)
 *
 * REST endpoints optimised for mobile clients (React Native / Swift / Kotlin).
 * Provides lightweight, aggregated data that avoids multiple round-trips.
 *
 * Routes registered under /pearblog/v1/mobile/:
 *   GET  /mobile/dashboard      — aggregated metrics snapshot
 *   GET  /mobile/queue          — content awaiting approval
 *   POST /mobile/queue/{id}/approve — approve AI-generated content
 *   POST /mobile/queue/{id}/reject  — reject AI-generated content
 *   GET  /mobile/alerts         — recent actionable alerts
 *   GET  /mobile/sites          — multi-site overview (network installs)
 *
 * @package PearBlogEngine\API
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

use PearBlogEngine\Analytics\PredictiveAnalytics;
use PearBlogEngine\Content\CollaborationManager;
use PearBlogEngine\Monitoring\AlertManager;

/**
 * MobileAPIController
 */
class MobileAPIController {

	/** REST namespace. */
	private const NS = 'pearblog/v1';

	/** Base route. */
	private const BASE = '/mobile';

	/** Option key: cached dashboard snapshot (60-second TTL). */
	private const OPT_DASH_CACHE = 'pearblog_mobile_dash_cache';

	/** Number of seconds before dashboard cache expires. */
	private const DASH_CACHE_TTL = 60;

	private PredictiveAnalytics $analytics;
	private CollaborationManager $collab;

	public function __construct(
		?PredictiveAnalytics $analytics = null,
		?CollaborationManager $collab = null
	) {
		$this->analytics = $analytics ?? new PredictiveAnalytics();
		$this->collab    = $collab    ?? new CollaborationManager();
	}

	/**
	 * Register all mobile REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * WordPress REST API hook: register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NS,
			self::BASE . '/dashboard',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_dashboard' ],
				'permission_callback' => [ $this, 'require_edit_posts' ],
			]
		);

		register_rest_route(
			self::NS,
			self::BASE . '/queue',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_queue' ],
				'permission_callback' => [ $this, 'require_edit_posts' ],
			]
		);

		register_rest_route(
			self::NS,
			self::BASE . '/queue/(?P<id>\d+)/approve',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'approve_queue_item' ],
				'permission_callback' => [ $this, 'require_edit_posts' ],
				'args'                => [
					'id' => [
						'validate_callback' => static fn( $v ) => is_numeric( $v ) && (int) $v > 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			self::NS,
			self::BASE . '/queue/(?P<id>\d+)/reject',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'reject_queue_item' ],
				'permission_callback' => [ $this, 'require_edit_posts' ],
				'args'                => [
					'id'       => [
						'validate_callback' => static fn( $v ) => is_numeric( $v ) && (int) $v > 0,
						'sanitize_callback' => 'absint',
					],
					'feedback' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
					],
				],
			]
		);

		register_rest_route(
			self::NS,
			self::BASE . '/alerts',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_alerts' ],
				'permission_callback' => [ $this, 'require_edit_posts' ],
				'args'                => [
					'limit' => [
						'default'           => 20,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			self::NS,
			self::BASE . '/sites',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_sites' ],
				'permission_callback' => [ $this, 'require_edit_posts' ],
			]
		);
	}

	// -----------------------------------------------------------------------
	// Route handlers
	// -----------------------------------------------------------------------

	/**
	 * GET /mobile/dashboard
	 *
	 * Returns a lightweight aggregated snapshot suitable for the mobile
	 * home screen: pipeline health, queue size, recent revenue, alerts.
	 *
	 * @param  \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_dashboard( \WP_REST_Request $request ): \WP_REST_Response {
		$cache = get_option( self::OPT_DASH_CACHE );
		if ( is_array( $cache ) && ( time() - ( $cache['ts'] ?? 0 ) ) < self::DASH_CACHE_TTL ) {
			return rest_ensure_response( $cache['data'] );
		}

		$last_run      = (int) get_option( 'pearblog_last_pipeline_run', 0 );
		$queue_size    = $this->count_queue();
		$pending       = count( $this->collab->get_pending_reviews() );
		$revenue_fc    = $this->analytics->get_revenue_forecast( 7 );

		$data = [
			'pipeline' => [
				'last_run'    => $last_run,
				'last_run_ago'=> $last_run > 0 ? human_time_diff( $last_run ) : 'never',
				'queue_size'  => $queue_size,
			],
			'reviews' => [
				'pending' => $pending,
			],
			'revenue_7d_forecast' => [
				'total'  => $revenue_fc['total_projected'],
				'trend'  => $revenue_fc['trend'],
			],
			'generated_at' => time(),
		];

		update_option( self::OPT_DASH_CACHE, [ 'ts' => time(), 'data' => $data ], false );

		return rest_ensure_response( $data );
	}

	/**
	 * GET /mobile/queue
	 *
	 * Returns draft posts pending human review, with predictive scores.
	 *
	 * @param  \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_queue( \WP_REST_Request $request ): \WP_REST_Response {
		$posts = get_posts( [
			'post_status' => 'draft',
			'meta_key'    => '_pearblog_generated',
			'meta_value'  => '1',
			'numberposts' => 50,
			'orderby'     => 'date',
			'order'       => 'DESC',
		] );

		$items = [];
		foreach ( $posts as $post ) {
			$items[] = [
				'id'           => $post->ID,
				'title'        => get_the_title( $post->ID ),
				'excerpt'      => wp_trim_words( $post->post_content, 20 ),
				'created'      => $post->post_date_gmt,
				'quality_score'=> (float) get_post_meta( $post->ID, '_pearblog_quality_score', true ),
				'url'          => get_permalink( $post->ID ),
			];
		}

		return rest_ensure_response( [
			'count' => count( $items ),
			'items' => $items,
		] );
	}

	/**
	 * POST /mobile/queue/{id}/approve
	 *
	 * Publishes a draft AI-generated post.
	 *
	 * @param  \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function approve_queue_item( \WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_status !== 'draft' ) {
			return new \WP_Error(
				'pearblog_not_found',
				'Draft post not found.',
				[ 'status' => 404 ]
			);
		}

		$result = wp_update_post( [
			'ID'          => $post_id,
			'post_status' => 'publish',
		], true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires when a mobile operator approves an AI-generated post.
		 *
		 * @param int $post_id   Published post ID.
		 * @param int $approver  Approving user ID.
		 */
		do_action( 'pearblog_mobile_approved', $post_id, get_current_user_id() );

		return rest_ensure_response( [
			'post_id' => $post_id,
			'status'  => 'published',
			'url'     => get_permalink( $post_id ),
		] );
	}

	/**
	 * POST /mobile/queue/{id}/reject
	 *
	 * Moves a draft post to trash with rejection metadata.
	 *
	 * @param  \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function reject_queue_item( \WP_REST_Request $request ) {
		$post_id  = (int) $request['id'];
		$feedback = sanitize_textarea_field( $request->get_param( 'feedback' ) ?? '' );
		$post     = get_post( $post_id );

		if ( ! $post || $post->post_status !== 'draft' ) {
			return new \WP_Error(
				'pearblog_not_found',
				'Draft post not found.',
				[ 'status' => 404 ]
			);
		}

		update_post_meta( $post_id, '_pearblog_rejection_feedback', $feedback );
		update_post_meta( $post_id, '_pearblog_rejected_at', time() );
		update_post_meta( $post_id, '_pearblog_rejected_by', get_current_user_id() );

		wp_trash_post( $post_id );

		/**
		 * Fires when a mobile operator rejects an AI-generated post.
		 *
		 * @param int    $post_id  Trashed post ID.
		 * @param string $feedback Rejection reason.
		 */
		do_action( 'pearblog_mobile_rejected', $post_id, $feedback );

		return rest_ensure_response( [
			'post_id'  => $post_id,
			'status'   => 'rejected',
			'feedback' => $feedback,
		] );
	}

	/**
	 * GET /mobile/alerts
	 *
	 * Returns recent alerts formatted for push notification display.
	 *
	 * @param  \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_alerts( \WP_REST_Request $request ): \WP_REST_Response {
		$limit     = max( 1, min( 100, (int) $request->get_param( 'limit' ) ) );
		$raw       = get_option( 'pearblog_alert_log', '[]' );
		$all_alerts = json_decode( $raw, true );

		if ( ! is_array( $all_alerts ) ) {
			$all_alerts = [];
		}

		// Newest first.
		$all_alerts = array_reverse( $all_alerts );
		$alerts     = array_slice( $all_alerts, 0, $limit );

		// Simplify for mobile payload.
		$mobile_alerts = array_map( static function ( array $a ): array {
			return [
				'level'   => $a['level']   ?? 'info',
				'title'   => $a['subject'] ?? '',
				'body'    => $a['message'] ?? '',
				'time'    => $a['time']    ?? 0,
			];
		}, $alerts );

		return rest_ensure_response( [
			'count'  => count( $mobile_alerts ),
			'alerts' => $mobile_alerts,
		] );
	}

	/**
	 * GET /mobile/sites
	 *
	 * Returns an overview of all sites in a WordPress multisite network
	 * (or a single-site summary when not running in multisite mode).
	 *
	 * @param  \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_sites( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! is_multisite() ) {
			return rest_ensure_response( [
				'multisite' => false,
				'sites'     => [
					[
						'id'         => get_current_blog_id(),
						'name'       => get_bloginfo( 'name' ),
						'url'        => get_site_url(),
						'queue_size' => $this->count_queue(),
					],
				],
			] );
		}

		$sites     = get_sites( [ 'number' => 100 ] );
		$site_data = [];

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			$site_data[] = [
				'id'         => (int) $site->blog_id,
				'name'       => get_bloginfo( 'name' ),
				'url'        => get_site_url(),
				'queue_size' => $this->count_queue(),
			];
			restore_current_blog();
		}

		return rest_ensure_response( [
			'multisite' => true,
			'sites'     => $site_data,
		] );
	}

	// -----------------------------------------------------------------------
	// Permission callbacks
	// -----------------------------------------------------------------------

	/**
	 * Require the current user to have the edit_posts capability.
	 *
	 * @return bool|\WP_Error
	 */
	public function require_edit_posts() {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}
		return new \WP_Error(
			'pearblog_forbidden',
			'You do not have permission to access this endpoint.',
			[ 'status' => 403 ]
		);
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Count draft AI-generated posts in the queue.
	 *
	 * @return int
	 */
	private function count_queue(): int {
		$posts = get_posts( [
			'post_status' => 'draft',
			'meta_key'    => '_pearblog_generated',
			'meta_value'  => '1',
			'numberposts' => -1,
			'fields'      => 'ids',
		] );
		return count( $posts );
	}
}
