<?php
/**
 * Content Approval Workflow – multi-level review before publication.
 *
 * Implements a configurable editorial approval process:
 *   draft → pending_review → approved → published
 *
 * Features:
 *  - Configurable reviewers list (WP user IDs).
 *  - Email notifications to reviewers on submission.
 *  - Admin UI queue (submenu page) listing articles pending review.
 *  - REST endpoints to approve / reject articles.
 *  - WP option `pearblog_approval_required` controls whether the pipeline
 *    holds at `pending_review` instead of publishing immediately.
 *
 * REST:
 *   GET  /pearblog/v1/approval/queue         – pending review queue
 *   POST /pearblog/v1/approval/{post_id}/approve
 *   POST /pearblog/v1/approval/{post_id}/reject
 *
 * @package PearBlogEngine\Pipeline
 */

declare(strict_types=1);

namespace PearBlogEngine\Pipeline;

/**
 * Manages the editorial approval workflow.
 */
class ApprovalWorkflow {

	/** WP option: enable/disable approval requirement. */
	public const OPTION_REQUIRED  = 'pearblog_approval_required';

	/** WP option: comma-separated reviewer user IDs. */
	public const OPTION_REVIEWERS = 'pearblog_approval_reviewers';

	/** Post meta key storing the current approval status. */
	public const META_STATUS      = 'pearblog_approval_status';

	/** Post meta key storing reviewer notes on rejection. */
	public const META_NOTES       = 'pearblog_approval_notes';

	/** Approval status constants. */
	public const STATUS_PENDING  = 'pending_review';
	public const STATUS_APPROVED = 'approved';
	public const STATUS_REJECTED = 'rejected';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Attach WordPress hooks.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'pearblog_pipeline_completed', [ $this, 'maybe_hold_for_review' ], 5, 1 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/approval/queue', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_queue' ],
			'permission_callback' => [ $this, 'reviewer_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/approval/(?P<post_id>\d+)/approve', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_approve' ],
			'permission_callback' => [ $this, 'reviewer_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/approval/(?P<post_id>\d+)/reject', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_reject' ],
			'permission_callback' => [ $this, 'reviewer_permission' ],
		] );
	}

	/**
	 * Add approval queue page to admin.
	 */
	public function add_menu(): void {
		add_submenu_page(
			'pearblog-engine',
			__( 'Approval Queue', 'pearblog-engine' ),
			__( 'Approval Queue', 'pearblog-engine' ),
			'edit_posts',
			'pearblog-approval-queue',
			[ $this, 'render_queue_page' ]
		);
	}

	// -----------------------------------------------------------------------
	// Core methods
	// -----------------------------------------------------------------------

	/**
	 * Whether the approval workflow is enabled.
	 */
	public function is_required(): bool {
		return (bool) get_option( self::OPTION_REQUIRED, false );
	}

	/**
	 * Return configured reviewer user IDs.
	 *
	 * @return int[]
	 */
	public function get_reviewer_ids(): array {
		$raw = (string) get_option( self::OPTION_REVIEWERS, '' );
		if ( '' === $raw ) {
			return [];
		}
		return array_map( 'intval', array_filter( explode( ',', $raw ) ) );
	}

	/**
	 * Submit a post for review (sets status to pending_review).
	 *
	 * @param int $post_id WordPress post ID.
	 */
	public function submit_for_review( int $post_id ): void {
		update_post_meta( $post_id, self::META_STATUS, self::STATUS_PENDING );

		// Put post in 'pending' WP status so it doesn't show publicly.
		wp_update_post( [
			'ID'          => $post_id,
			'post_status' => 'pending',
		] );

		$this->notify_reviewers( $post_id );

		/**
		 * Action: pearblog_approval_submitted
		 *
		 * @param int $post_id Post ID submitted for review.
		 */
		do_action( 'pearblog_approval_submitted', $post_id );
	}

	/**
	 * Approve an article and publish it.
	 *
	 * @param int $post_id   WordPress post ID.
	 * @param int $approver  Approving user ID.
	 */
	public function approve( int $post_id, int $approver = 0 ): void {
		update_post_meta( $post_id, self::META_STATUS, self::STATUS_APPROVED );
		update_post_meta( $post_id, 'pearblog_approval_approver', $approver ?: get_current_user_id() );
		update_post_meta( $post_id, 'pearblog_approval_date', time() );

		wp_update_post( [
			'ID'          => $post_id,
			'post_status' => 'publish',
		] );

		/**
		 * Action: pearblog_approval_approved
		 *
		 * @param int $post_id  Post ID.
		 * @param int $approver Approving user ID.
		 */
		do_action( 'pearblog_approval_approved', $post_id, $approver );
	}

	/**
	 * Reject an article (remains as draft).
	 *
	 * @param int    $post_id WordPress post ID.
	 * @param string $notes   Rejection notes/reason.
	 * @param int    $rejector Rejecting user ID.
	 */
	public function reject( int $post_id, string $notes = '', int $rejector = 0 ): void {
		update_post_meta( $post_id, self::META_STATUS, self::STATUS_REJECTED );
		update_post_meta( $post_id, self::META_NOTES, $notes );
		update_post_meta( $post_id, 'pearblog_approval_rejector', $rejector ?: get_current_user_id() );

		wp_update_post( [
			'ID'          => $post_id,
			'post_status' => 'draft',
		] );

		/**
		 * Action: pearblog_approval_rejected
		 *
		 * @param int    $post_id  Post ID.
		 * @param string $notes    Rejection notes.
		 */
		do_action( 'pearblog_approval_rejected', $post_id, $notes );
	}

	/**
	 * Return all posts currently in the pending review queue.
	 *
	 * @return \WP_Post[]
	 */
	public function get_pending_posts(): array {
		return get_posts( [
			'post_type'   => 'post',
			'post_status' => 'pending',
			'meta_key'    => self::META_STATUS,
			'meta_value'  => self::STATUS_PENDING,
			'numberposts' => 50,
		] );
	}

	// -----------------------------------------------------------------------
	// Pipeline hook
	// -----------------------------------------------------------------------

	/**
	 * Hold newly generated article for review if approval is required.
	 *
	 * Fires on `pearblog_pipeline_completed` with priority 5 (before default 10).
	 *
	 * @param int $post_id Published post ID.
	 */
	public function maybe_hold_for_review( int $post_id ): void {
		if ( ! $this->is_required() ) {
			return;
		}

		$this->submit_for_review( $post_id );
	}

	// -----------------------------------------------------------------------
	// Notifications
	// -----------------------------------------------------------------------

	/**
	 * Send email notifications to all configured reviewers.
	 *
	 * @param int $post_id Post ID.
	 */
	private function notify_reviewers( int $post_id ): void {
		$reviewers = $this->get_reviewer_ids();
		if ( empty( $reviewers ) ) {
			return;
		}

		$post    = get_post( $post_id );
		$title   = $post ? $post->post_title : "Post #{$post_id}";
		$edit_url = admin_url( "post.php?post={$post_id}&action=edit" );

		foreach ( $reviewers as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				continue;
			}

			wp_mail(
				$user->user_email,
				sprintf( __( '[PearBlog] Review required: %s', 'pearblog-engine' ), $title ),
				sprintf(
					__( "Hello %s,\n\nA new article requires your review:\n\n%s\n\nReview it here: %s\n\n-- PearBlog Engine", 'pearblog-engine' ),
					$user->display_name,
					$title,
					$edit_url
				)
			);
		}
	}

	// -----------------------------------------------------------------------
	// Admin UI
	// -----------------------------------------------------------------------

	/**
	 * Render the approval queue admin page.
	 */
	public function render_queue_page(): void {
		$pending = $this->get_pending_posts();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( '📋 Approval Queue', 'pearblog-engine' ); ?></h1>

			<?php if ( empty( $pending ) ) : ?>
				<p><?php esc_html_e( 'No articles pending review.', 'pearblog-engine' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'Submitted', 'pearblog-engine' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'pearblog-engine' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $pending as $post ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a></td>
							<td><?php echo esc_html( human_time_diff( strtotime( $post->post_modified ), time() ) . ' ago' ); ?></td>
							<td>
								<form method="post" style="display:inline">
									<?php wp_nonce_field( 'pearblog_approve_' . $post->ID ); ?>
									<input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>">
									<button name="approval_action" value="approve" class="button button-primary"><?php esc_html_e( 'Approve', 'pearblog-engine' ); ?></button>
									<button name="approval_action" value="reject" class="button"><?php esc_html_e( 'Reject', 'pearblog-engine' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php

		// Handle form submission.
		if ( isset( $_POST['approval_action'], $_POST['post_id'] ) ) {
			$post_id = (int) $_POST['post_id'];
			if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'pearblog_approve_' . $post_id ) ) {
				if ( 'approve' === $_POST['approval_action'] ) {
					$this->approve( $post_id );
					echo '<div class="notice notice-success"><p>' . esc_html__( 'Article approved and published.', 'pearblog-engine' ) . '</p></div>';
				} else {
					$this->reject( $post_id, sanitize_text_field( $_POST['notes'] ?? '' ) );
					echo '<div class="notice notice-warning"><p>' . esc_html__( 'Article rejected.', 'pearblog-engine' ) . '</p></div>';
				}
			}
		}
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_queue( \WP_REST_Request $request ): \WP_REST_Response {
		$pending = $this->get_pending_posts();
		$items   = array_map( fn( $p ) => [
			'post_id' => $p->ID,
			'title'   => $p->post_title,
			'date'    => $p->post_modified,
		], $pending );

		return new \WP_REST_Response( [ 'count' => count( $items ), 'items' => $items ], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_approve( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		$this->approve( $post_id, get_current_user_id() );

		return new \WP_REST_Response( [ 'success' => true, 'post_id' => $post_id, 'status' => 'published' ], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_reject( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		$notes   = (string) ( $request->get_param( 'notes' ) ?? '' );
		$this->reject( $post_id, $notes, get_current_user_id() );

		return new \WP_REST_Response( [ 'success' => true, 'post_id' => $post_id, 'status' => 'rejected' ], 200 );
	}

	/**
	 * Permission callback – reviewers and admins.
	 */
	public function reviewer_permission(): bool {
		return current_user_can( 'edit_posts' );
	}
}
