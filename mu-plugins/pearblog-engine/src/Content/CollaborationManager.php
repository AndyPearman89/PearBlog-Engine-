<?php
/**
 * Collaboration Manager — F9 (v9.0)
 *
 * Multi-user content collaboration workflow for AI-generated articles:
 *   - Assign reviewers to AI-generated draft posts.
 *   - Track review status: pending → in_review → approved | changes_requested | rejected.
 *   - Support threaded inline comments on post content.
 *   - Notify reviewers via email and the `pearblog_collaboration_*` action hooks.
 *
 * Storage (WP post meta, keyed by post ID):
 *   _pearblog_collab_status        – string: pending|in_review|approved|changes_requested|rejected
 *   _pearblog_collab_reviewers     – JSON array of user IDs
 *   _pearblog_collab_comments      – JSON array of {id, user_id, text, created_at, resolved}
 *   _pearblog_collab_history       – JSON array of {action, user_id, timestamp}
 *
 * REST:
 *   GET    /pearblog/v1/collaboration/{post_id}          – get collaboration state
 *   POST   /pearblog/v1/collaboration/{post_id}/assign   – assign reviewer(s)
 *   POST   /pearblog/v1/collaboration/{post_id}/review   – submit review decision
 *   POST   /pearblog/v1/collaboration/{post_id}/comment  – add inline comment
 *   DELETE /pearblog/v1/collaboration/{post_id}/comment/{comment_id} – remove comment
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Multi-user content review and collaboration workflow.
 */
class CollaborationManager {

	/** Post meta keys. */
	public const META_STATUS    = '_pearblog_collab_status';
	public const META_REVIEWERS = '_pearblog_collab_reviewers';
	public const META_COMMENTS  = '_pearblog_collab_comments';
	public const META_HISTORY   = '_pearblog_collab_history';

	/** Valid review statuses. */
	public const STATUSES = [
		'pending',
		'in_review',
		'approved',
		'changes_requested',
		'rejected',
	];

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'pearblog_pipeline_completed', [ $this, 'on_pipeline_completed' ], 30, 2 );
	}

	/**
	 * When the pipeline publishes a post, set its collaboration status to 'pending'.
	 *
	 * @param int    $post_id
	 * @param string $topic
	 */
	public function on_pipeline_completed( int $post_id, string $topic = '' ): void {
		$existing = get_post_meta( $post_id, self::META_STATUS, true );
		if ( '' === $existing || false === $existing ) {
			$this->set_status( $post_id, 'pending' );
		}
	}

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/collaboration/(?P<post_id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/collaboration/(?P<post_id>\d+)/assign', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_assign' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'reviewer_ids' => [ 'type' => 'array', 'required' => true, 'items' => [ 'type' => 'integer' ] ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/collaboration/(?P<post_id>\d+)/review', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_review' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'decision' => [ 'type' => 'string', 'required' => true, 'enum' => [ 'approved', 'changes_requested', 'rejected' ] ],
				'note'     => [ 'type' => 'string', 'default' => '' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/collaboration/(?P<post_id>\d+)/comment', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_add_comment' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'text' => [ 'type' => 'string', 'required' => true ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/collaboration/(?P<post_id>\d+)/comment/(?P<comment_id>[a-z0-9]+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'rest_delete_comment' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );
	}

	public function rest_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	public function rest_get( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];
		return new \WP_REST_Response( $this->get_state( $post_id ) );
	}

	public function rest_assign( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id      = (int) $request['post_id'];
		$reviewer_ids = array_map( 'intval', (array) $request->get_param( 'reviewer_ids' ) );

		$this->assign_reviewers( $post_id, $reviewer_ids );
		$this->set_status( $post_id, 'in_review' );

		return new \WP_REST_Response( $this->get_state( $post_id ) );
	}

	public function rest_review( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id  = (int) $request['post_id'];
		$decision = (string) $request->get_param( 'decision' );
		$note     = (string) $request->get_param( 'note' );

		$this->submit_review( $post_id, $decision, $note );

		return new \WP_REST_Response( $this->get_state( $post_id ) );
	}

	public function rest_add_comment( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];
		$text    = sanitize_textarea_field( (string) $request->get_param( 'text' ) );
		$comment = $this->add_comment( $post_id, $text );

		return new \WP_REST_Response( $comment, 201 );
	}

	public function rest_delete_comment( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id    = (int) $request['post_id'];
		$comment_id = (string) $request['comment_id'];
		$deleted    = $this->delete_comment( $post_id, $comment_id );

		return new \WP_REST_Response( [ 'deleted' => $deleted ] );
	}

	// -----------------------------------------------------------------------
	// Core logic
	// -----------------------------------------------------------------------

	/**
	 * @return array{status:string, reviewers:int[], comments:array, history:array}
	 */
	public function get_state( int $post_id ): array {
		return [
			'post_id'   => $post_id,
			'status'    => $this->get_status( $post_id ),
			'reviewers' => $this->get_reviewers( $post_id ),
			'comments'  => $this->get_comments( $post_id ),
			'history'   => $this->get_history( $post_id ),
		];
	}

	public function get_status( int $post_id ): string {
		$s = (string) get_post_meta( $post_id, self::META_STATUS, true );
		return in_array( $s, self::STATUSES, true ) ? $s : 'pending';
	}

	public function set_status( int $post_id, string $status ): void {
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			throw new \InvalidArgumentException( "Invalid collaboration status: {$status}" );
		}
		update_post_meta( $post_id, self::META_STATUS, $status );
		$this->append_history( $post_id, 'status_changed', [ 'status' => $status ] );
		do_action( 'pearblog_collaboration_status_changed', $post_id, $status );
	}

	/** @return int[] */
	public function get_reviewers( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META_REVIEWERS, true );
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}
		return is_array( $raw ) ? array_map( 'intval', $raw ) : [];
	}

	/** @param int[] $reviewer_ids */
	public function assign_reviewers( int $post_id, array $reviewer_ids ): void {
		$reviewer_ids = array_unique( array_filter( array_map( 'intval', $reviewer_ids ) ) );
		update_post_meta( $post_id, self::META_REVIEWERS, wp_json_encode( $reviewer_ids ) );
		$this->append_history( $post_id, 'reviewers_assigned', [ 'reviewer_ids' => $reviewer_ids ] );
		do_action( 'pearblog_collaboration_reviewers_assigned', $post_id, $reviewer_ids );
	}

	public function submit_review( int $post_id, string $decision, string $note = '' ): void {
		$this->set_status( $post_id, $decision );
		$user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
		$this->append_history( $post_id, 'review_submitted', compact( 'decision', 'note', 'user_id' ) );
		do_action( 'pearblog_collaboration_reviewed', $post_id, $decision, $note );
	}

	/** @return array */
	public function get_comments( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META_COMMENTS, true );
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}
		return is_array( $raw ) ? $raw : [];
	}

	/** @return array{id:string,user_id:int,text:string,created_at:string,resolved:bool} */
	public function add_comment( int $post_id, string $text ): array {
		$comments = $this->get_comments( $post_id );
		$user_id  = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;

		$comment = [
			'id'         => substr( md5( uniqid( (string) $post_id, true ) ), 0, 8 ),
			'user_id'    => $user_id,
			'text'       => $text,
			'created_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'resolved'   => false,
		];

		$comments[] = $comment;
		update_post_meta( $post_id, self::META_COMMENTS, wp_json_encode( $comments ) );
		do_action( 'pearblog_collaboration_comment_added', $post_id, $comment );

		return $comment;
	}

	public function delete_comment( int $post_id, string $comment_id ): bool {
		$comments = $this->get_comments( $post_id );
		$before   = count( $comments );
		$comments = array_filter( $comments, static fn( $c ) => $c['id'] !== $comment_id );
		update_post_meta( $post_id, self::META_COMMENTS, wp_json_encode( array_values( $comments ) ) );

		return count( $comments ) < $before;
	}

	/** @return array */
	public function get_history( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META_HISTORY, true );
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}
		return is_array( $raw ) ? $raw : [];
	}

	private function append_history( int $post_id, string $action, array $context = [] ): void {
		$history   = $this->get_history( $post_id );
		$history[] = array_merge(
			[ 'action' => $action, 'timestamp' => gmdate( 'Y-m-d\TH:i:s\Z' ) ],
			$context
		);
		update_post_meta( $post_id, self::META_HISTORY, wp_json_encode( $history ) );
	}
}
