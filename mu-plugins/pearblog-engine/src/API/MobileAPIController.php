<?php
/**
 * Mobile API Controller — F4 (v9.0)
 *
 * Provides lightweight REST endpoints designed for the PearBlog mobile
 * monitoring app (iOS/Android). All endpoints are read-heavy and optimised
 * for low-latency responses.
 *
 * Endpoints (namespace: pearblog/v1):
 *   GET  /mobile/summary        – single-call dashboard snapshot
 *   GET  /mobile/queue          – current topic queue (paginated)
 *   POST /mobile/queue/pause    – emergency pipeline pause
 *   POST /mobile/queue/resume   – resume a paused pipeline
 *   GET  /mobile/alerts         – recent alert history
 *   POST /mobile/content/{id}/approve – approve AI draft
 *   POST /mobile/content/{id}/reject  – reject AI draft with reason
 *
 * Authentication: ****** (pearblog_api_key) or manage_options capability.
 *
 * @package PearBlogEngine\API
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

use PearBlogEngine\Content\TopicQueue;

/**
 * REST controller for mobile monitoring app endpoints.
 */
class MobileAPIController {

	/** REST namespace. */
	public const NAMESPACE = 'pearblog/v1';

	/** WP option: pipeline paused flag. */
	public const OPTION_PAUSED = 'pearblog_pipeline_paused';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/mobile/summary', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_summary' ],
			'permission_callback' => [ $this, 'check_auth' ],
		] );

		register_rest_route( self::NAMESPACE, '/mobile/queue', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_queue' ],
			'permission_callback' => [ $this, 'check_auth' ],
			'args'                => [
				'page'     => [ 'default' => 1, 'type' => 'integer', 'minimum' => 1 ],
				'per_page' => [ 'default' => 20, 'type' => 'integer', 'minimum' => 1, 'maximum' => 100 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/mobile/queue/pause', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'pause_pipeline' ],
			'permission_callback' => [ $this, 'check_auth' ],
		] );

		register_rest_route( self::NAMESPACE, '/mobile/queue/resume', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'resume_pipeline' ],
			'permission_callback' => [ $this, 'check_auth' ],
		] );

		register_rest_route( self::NAMESPACE, '/mobile/alerts', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_alerts' ],
			'permission_callback' => [ $this, 'check_auth' ],
			'args'                => [
				'limit' => [ 'default' => 20, 'type' => 'integer', 'minimum' => 1, 'maximum' => 100 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/mobile/content/(?P<id>\d+)/approve', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'approve_content' ],
			'permission_callback' => [ $this, 'check_auth' ],
		] );

		register_rest_route( self::NAMESPACE, '/mobile/content/(?P<id>\d+)/reject', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'reject_content' ],
			'permission_callback' => [ $this, 'check_auth' ],
			'args'                => [
				'reason' => [ 'type' => 'string', 'default' => '' ],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// Endpoint handlers
	// -----------------------------------------------------------------------

	public function get_summary( \WP_REST_Request $request ): \WP_REST_Response {
		$queue_size    = count( ( new TopicQueue( get_current_blog_id() ) )->get_all() );
		$articles_today = $this->count_published_today();
		$pipeline_ok   = ! (bool) get_option( self::OPTION_PAUSED, false );
		$last_run      = (int) get_option( 'pearblog_last_pipeline_run', 0 );
		$circuit_open  = (bool) get_option( 'pearblog_circuit_open', false );

		return new \WP_REST_Response( [
			'queue_size'     => $queue_size,
			'articles_today' => $articles_today,
			'pipeline_ok'    => $pipeline_ok,
			'circuit_open'   => $circuit_open,
			'last_run_ago'   => $last_run > 0 ? ( time() - $last_run ) : null,
			'server_time'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
		] );
	}

	public function get_queue( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$all    = ( new TopicQueue( get_current_blog_id() ) )->get_all();
		$total  = count( $all );
		$offset = ( $page - 1 ) * $per_page;
		$page_  = array_slice( $all, $offset, $per_page );

		return new \WP_REST_Response( [
			'topics'     => $page_,
			'total'      => $total,
			'page'       => $page,
			'per_page'   => $per_page,
			'total_pages' => (int) ceil( $total / $per_page ),
		] );
	}

	public function pause_pipeline( \WP_REST_Request $request ): \WP_REST_Response {
		update_option( self::OPTION_PAUSED, true );
		do_action( 'pearblog_pipeline_paused' );

		return new \WP_REST_Response( [ 'paused' => true ] );
	}

	public function resume_pipeline( \WP_REST_Request $request ): \WP_REST_Response {
		update_option( self::OPTION_PAUSED, false );
		do_action( 'pearblog_pipeline_resumed' );

		return new \WP_REST_Response( [ 'paused' => false ] );
	}

	public function get_alerts( \WP_REST_Request $request ): \WP_REST_Response {
		$limit  = (int) $request->get_param( 'limit' );
		$alerts = $this->load_recent_alerts( $limit );

		return new \WP_REST_Response( [ 'alerts' => $alerts, 'count' => count( $alerts ) ] );
	}

	public function approve_content( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['id'];

		if ( ! get_post( $post_id ) ) {
			return new \WP_REST_Response( [ 'error' => 'Post not found.' ], 404 );
		}

		wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ] );
		update_post_meta( $post_id, '_pearblog_mobile_approved', current_time( 'mysql', true ) );
		do_action( 'pearblog_content_approved', $post_id );

		return new \WP_REST_Response( [ 'approved' => true, 'post_id' => $post_id ] );
	}

	public function reject_content( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['id'];
		$reason  = sanitize_text_field( (string) $request->get_param( 'reason' ) );

		if ( ! get_post( $post_id ) ) {
			return new \WP_REST_Response( [ 'error' => 'Post not found.' ], 404 );
		}

		wp_update_post( [ 'ID' => $post_id, 'post_status' => 'trash' ] );
		update_post_meta( $post_id, '_pearblog_mobile_rejected', current_time( 'mysql', true ) );
		update_post_meta( $post_id, '_pearblog_reject_reason', $reason );
		do_action( 'pearblog_content_rejected', $post_id, $reason );

		return new \WP_REST_Response( [ 'rejected' => true, 'post_id' => $post_id ] );
	}

	// -----------------------------------------------------------------------
	// Auth
	// -----------------------------------------------------------------------

	public function check_auth(): bool {
		$key = (string) get_option( 'pearblog_api_key', '' );
		if ( '' !== $key ) {
			$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
			if ( str_starts_with( $auth, 'Bearer ' ) && hash_equals( $key, substr( $auth, 7 ) ) ) {
				return true;
			}
		}
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Count posts published today (UTC).
	 */
	protected function count_published_today(): int {
		if ( ! function_exists( 'get_posts' ) ) {
			return 0;
		}
		$today = gmdate( 'Y-m-d' );
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'date_query'     => [ [ 'after' => $today . ' 00:00:00', 'inclusive' => true ] ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );
		return is_array( $posts ) ? count( $posts ) : 0;
	}

	/**
	 * Load recent alert log entries.
	 *
	 * @param int $limit
	 * @return array<int, array{level:string, message:string, timestamp:string}>
	 */
	protected function load_recent_alerts( int $limit ): array {
		$raw = get_option( 'pearblog_alert_log', [] );
		if ( ! is_array( $raw ) ) {
			return [];
		}
		$raw = array_reverse( $raw );
		return array_slice( $raw, 0, $limit );
	}
}
