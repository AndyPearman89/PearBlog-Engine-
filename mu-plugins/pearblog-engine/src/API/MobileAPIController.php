<?php
/**
 * Mobile API Controller – V9.0 F4: REST endpoints for the PearBlog mobile app.
 *
 * Provides a lightweight, token-authenticated REST surface used by the
 * React-Native mobile companion app.  All responses are optimised for mobile
 * (minimal payload, no HTML markup).
 *
 * Routes (prefix: /wp-json/pearblog/v1/mobile):
 *   GET  /dashboard  – aggregated KPI snapshot
 *   GET  /queue      – current article queue (paginated)
 *   POST /queue/{id}/approve   – approve a queued article
 *   POST /queue/{id}/reject    – reject and feedback a queued article
 *   POST /queue/pause          – pause all AI generation
 *   POST /queue/resume         – resume AI generation
 *   GET  /alerts    – current unacknowledged alerts
 *   POST /alerts/{id}/ack     – acknowledge an alert
 *
 * Authentication:
 *   Standard WP REST cookie/nonce or Application Password.
 *   Capability required: `manage_options` for pause/resume; `edit_posts`
 *   for queue approve/reject.
 *
 * @package PearBlogEngine\API
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

/**
 * REST controller for the PearBlog mobile monitoring app.
 */
class MobileAPIController {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Route base. */
	private const BASE = '/mobile';

	/** WP option: is AI generation paused by the mobile app? */
	public const OPTION_PAUSED = 'pearblog_mobile_generation_paused';

	/** WP option: active alerts. */
	public const OPTION_ALERTS = 'pearblog_mobile_alerts';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register all REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/dashboard',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_dashboard' ],
				'permission_callback' => [ $this, 'perm_view' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/queue',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_queue' ],
				'permission_callback' => [ $this, 'perm_view' ],
				'args'                => [
					'page'     => [ 'type' => 'integer', 'default' => 1, 'minimum' => 1 ],
					'per_page' => [ 'type' => 'integer', 'default' => 20, 'maximum' => 100 ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/queue/(?P<id>[\d]+)/approve',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'approve_queued' ],
				'permission_callback' => [ $this, 'perm_edit' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/queue/(?P<id>[\d]+)/reject',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'reject_queued' ],
				'permission_callback' => [ $this, 'perm_edit' ],
				'args'                => [
					'reason' => [ 'type' => 'string', 'default' => '' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/queue/pause',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'pause_generation' ],
				'permission_callback' => [ $this, 'perm_admin' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/queue/resume',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'resume_generation' ],
				'permission_callback' => [ $this, 'perm_admin' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/alerts',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_alerts' ],
				'permission_callback' => [ $this, 'perm_view' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/alerts/(?P<id>[\w-]+)/ack',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'ack_alert' ],
				'permission_callback' => [ $this, 'perm_edit' ],
			]
		);
	}

	// -----------------------------------------------------------------------
	// Handlers
	// -----------------------------------------------------------------------

	/**
	 * GET /mobile/dashboard – KPI snapshot.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_dashboard( \WP_REST_Request $request ): \WP_REST_Response {
		$paused     = (bool) get_option( self::OPTION_PAUSED, false );
		$last_run   = get_option( 'pearblog_last_pipeline_run', null );
		$queue_size = $this->count_pending_posts();
		$alerts     = count( $this->load_alerts() );

		return new \WP_REST_Response( [
			'generation_paused' => $paused,
			'last_pipeline_run' => $last_run,
			'queue_size'        => $queue_size,
			'unread_alerts'     => $alerts,
			'timestamp'         => gmdate( 'Y-m-d\TH:i:s\Z' ),
		], 200 );
	}

	/**
	 * GET /mobile/queue – paginated article queue.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_queue( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$posts = get_posts( [
			'post_status'    => 'draft',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		] );

		$items = array_map( static function ( int $id ): array {
			return [
				'id'      => $id,
				'title'   => get_the_title( $id ),
				'date'    => get_post_time( 'Y-m-d\TH:i:s\Z', true, $id ),
				'score'   => (float) get_post_meta( $id, '_pearblog_quality_score', true ),
			];
		}, $posts );

		return new \WP_REST_Response( [
			'items'   => $items,
			'page'    => $page,
			'total'   => $this->count_pending_posts(),
		], 200 );
	}

	/**
	 * POST /mobile/queue/{id}/approve – publish a draft.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function approve_queued( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id = (int) $request->get_param( 'id' );

		if ( ! get_post( $id ) ) {
			return new \WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
		}

		$result = wp_publish_post( $id );

		if ( ! $result ) {
			return new \WP_Error( 'publish_failed', 'Could not publish post.', [ 'status' => 500 ] );
		}

		do_action( 'pearblog_mobile_post_approved', $id );

		return new \WP_REST_Response( [ 'approved' => true, 'post_id' => $id ], 200 );
	}

	/**
	 * POST /mobile/queue/{id}/reject – move a draft to trash with feedback.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function reject_queued( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id     = (int) $request->get_param( 'id' );
		$reason = sanitize_text_field( (string) $request->get_param( 'reason' ) );

		if ( ! get_post( $id ) ) {
			return new \WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
		}

		update_post_meta( $id, '_pearblog_rejection_reason', $reason );
		wp_trash_post( $id );

		do_action( 'pearblog_mobile_post_rejected', $id, $reason );

		return new \WP_REST_Response( [ 'rejected' => true, 'post_id' => $id ], 200 );
	}

	/**
	 * POST /mobile/queue/pause – halt AI generation.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function pause_generation( \WP_REST_Request $request ): \WP_REST_Response {
		update_option( self::OPTION_PAUSED, true );
		do_action( 'pearblog_generation_paused' );
		return new \WP_REST_Response( [ 'paused' => true ], 200 );
	}

	/**
	 * POST /mobile/queue/resume – re-enable AI generation.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function resume_generation( \WP_REST_Request $request ): \WP_REST_Response {
		update_option( self::OPTION_PAUSED, false );
		do_action( 'pearblog_generation_resumed' );
		return new \WP_REST_Response( [ 'paused' => false ], 200 );
	}

	/**
	 * GET /mobile/alerts – list unacknowledged alerts.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_alerts( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( [ 'alerts' => $this->load_alerts() ], 200 );
	}

	/**
	 * POST /mobile/alerts/{id}/ack – acknowledge an alert by ID.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function ack_alert( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$alert_id = sanitize_key( (string) $request->get_param( 'id' ) );
		$alerts   = $this->load_alerts();

		$found = false;
		foreach ( $alerts as $k => $alert ) {
			if ( $alert['id'] === $alert_id ) {
				array_splice( $alerts, $k, 1 );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return new \WP_Error( 'not_found', 'Alert not found.', [ 'status' => 404 ] );
		}

		update_option( self::OPTION_ALERTS, $alerts );
		return new \WP_REST_Response( [ 'acknowledged' => true ], 200 );
	}

	// -----------------------------------------------------------------------
	// Permission callbacks
	// -----------------------------------------------------------------------

	/** @return bool */
	public function perm_view(): bool {
		return is_user_logged_in();
	}

	/** @return bool */
	public function perm_edit(): bool {
		return current_user_can( 'edit_posts' );
	}

	/** @return bool */
	public function perm_admin(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * @return int
	 */
	private function count_pending_posts(): int {
		$count = wp_count_posts( 'post' );
		return (int) ( $count->draft ?? 0 );
	}

	/**
	 * @return array<int,array{id:string,message:string,level:string,created_at:string}>
	 */
	private function load_alerts(): array {
		$raw = get_option( self::OPTION_ALERTS, [] );
		return is_array( $raw ) ? array_values( $raw ) : [];
	}
}
