<?php
/**
 * Collaboration Manager – V9.0 F9: editorial workflow and team collaboration.
 *
 * Implements a lightweight multi-stage content-approval pipeline on top of
 * WordPress post statuses and custom post meta.  No extra tables required.
 *
 * Workflow stages:
 *   draft → in_review → needs_revision → approved → published
 *
 * Features:
 *   - Role-based stage transitions (editors can approve; authors can draft)
 *   - In-line comments stored as post meta JSON array
 *   - Version history: every transition is timestamped
 *   - Reviewer assignment and workload tracking
 *   - REST API for the mobile app and admin panel
 *
 * REST routes (prefix /wp-json/pearblog/v1/collab):
 *   GET  /posts              – list posts in workflow by stage
 *   POST /posts/{id}/submit  – submit draft for review
 *   POST /posts/{id}/approve – editor approves
 *   POST /posts/{id}/revise  – editor requests revision
 *   POST /posts/{id}/comment – add inline comment
 *   GET  /posts/{id}/history – transition history
 *   GET  /workload           – reviewer workload stats
 *
 * @package PearBlogEngine\Pipeline
 */

declare(strict_types=1);

namespace PearBlogEngine\Pipeline;

/**
 * Multi-stage editorial collaboration workflow.
 */
class CollaborationManager {

	/** Allowed workflow stages. */
	public const STAGES = [ 'draft', 'in_review', 'needs_revision', 'approved' ];

	/** Post meta key: current collaboration stage. */
	public const META_STAGE    = '_pearblog_collab_stage';

	/** Post meta key: reviewer user ID. */
	public const META_REVIEWER = '_pearblog_collab_reviewer';

	/** Post meta key: JSON-encoded array of inline comments. */
	public const META_COMMENTS = '_pearblog_collab_comments';

	/** Post meta key: JSON-encoded transition history. */
	public const META_HISTORY  = '_pearblog_collab_history';

	/** REST namespace. */
	private const REST_NAMESPACE = 'pearblog/v1';

	/** REST base. */
	private const BASE = '/collab';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'save_post', [ $this, 'on_save_post' ], 10, 2 );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::REST_NAMESPACE, self::BASE . '/posts', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_list_posts' ],
			'permission_callback' => [ $this, 'perm_view' ],
			'args'                => [
				'stage' => [ 'type' => 'string', 'default' => '' ],
			],
		] );

		register_rest_route( self::REST_NAMESPACE, self::BASE . '/posts/(?P<id>[\d]+)/submit', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_submit' ],
			'permission_callback' => [ $this, 'perm_author' ],
		] );

		register_rest_route( self::REST_NAMESPACE, self::BASE . '/posts/(?P<id>[\d]+)/approve', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_approve' ],
			'permission_callback' => [ $this, 'perm_editor' ],
		] );

		register_rest_route( self::REST_NAMESPACE, self::BASE . '/posts/(?P<id>[\d]+)/revise', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_revise' ],
			'permission_callback' => [ $this, 'perm_editor' ],
			'args'                => [
				'feedback' => [ 'type' => 'string', 'default' => '' ],
			],
		] );

		register_rest_route( self::REST_NAMESPACE, self::BASE . '/posts/(?P<id>[\d]+)/comment', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_add_comment' ],
			'permission_callback' => [ $this, 'perm_view' ],
			'args'                => [
				'text'   => [ 'type' => 'string', 'required' => true ],
				'offset' => [ 'type' => 'integer', 'default' => 0 ],
			],
		] );

		register_rest_route( self::REST_NAMESPACE, self::BASE . '/posts/(?P<id>[\d]+)/history', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_history' ],
			'permission_callback' => [ $this, 'perm_view' ],
		] );

		register_rest_route( self::REST_NAMESPACE, self::BASE . '/workload', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_workload' ],
			'permission_callback' => [ $this, 'perm_editor' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Stage transitions
	// -----------------------------------------------------------------------

	/**
	 * Transition a post to a new collaboration stage.
	 *
	 * @param int    $post_id
	 * @param string $new_stage
	 * @param int    $actor_id  User performing the transition.
	 * @param string $note      Optional note for history.
	 * @return bool
	 */
	public function transition( int $post_id, string $new_stage, int $actor_id = 0, string $note = '' ): bool {
		if ( ! in_array( $new_stage, self::STAGES, true ) ) {
			return false;
		}

		$current = $this->get_stage( $post_id );

		update_post_meta( $post_id, self::META_STAGE, $new_stage );
		$this->append_history( $post_id, $current, $new_stage, $actor_id, $note );

		do_action( 'pearblog_collab_transition', $post_id, $current, $new_stage, $actor_id );

		return true;
	}

	/**
	 * Get the current collaboration stage of a post.
	 *
	 * @param int $post_id
	 * @return string
	 */
	public function get_stage( int $post_id ): string {
		return (string) ( get_post_meta( $post_id, self::META_STAGE, true ) ?: 'draft' );
	}

	/**
	 * Assign a reviewer to a post.
	 *
	 * @param int $post_id
	 * @param int $reviewer_id
	 */
	public function assign_reviewer( int $post_id, int $reviewer_id ): void {
		update_post_meta( $post_id, self::META_REVIEWER, $reviewer_id );
	}

	/**
	 * Add an inline comment to a post.
	 *
	 * @param int    $post_id
	 * @param int    $author_id
	 * @param string $text
	 * @param int    $offset Character offset in the content.
	 * @return array{id:string,author_id:int,text:string,offset:int,created_at:string}
	 */
	public function add_comment( int $post_id, int $author_id, string $text, int $offset = 0 ): array {
		$comments = $this->get_comments( $post_id );
		$comment  = [
			'id'         => wp_generate_uuid4(),
			'author_id'  => $author_id,
			'text'       => sanitize_text_field( $text ),
			'offset'     => $offset,
			'created_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		];
		$comments[] = $comment;
		update_post_meta( $post_id, self::META_COMMENTS, wp_json_encode( $comments ) );
		return $comment;
	}

	/**
	 * Get all inline comments for a post.
	 *
	 * @param int $post_id
	 * @return array<int,array{id:string,author_id:int,text:string,offset:int,created_at:string}>
	 */
	public function get_comments( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META_COMMENTS, true );
		if ( ! $raw ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Get the transition history for a post.
	 *
	 * @param int $post_id
	 * @return array<int,array{from:string,to:string,actor_id:int,note:string,timestamp:string}>
	 */
	public function get_history( int $post_id ): array {
		$raw     = get_post_meta( $post_id, self::META_HISTORY, true );
		$decoded = $raw ? json_decode( $raw, true ) : [];
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Get reviewer workload: count of in_review posts per reviewer.
	 *
	 * @return array<int,array{reviewer_id:int,post_count:int}>
	 */
	public function get_workload(): array {
		$posts = get_posts( [
			'post_status'    => 'draft',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => self::META_STAGE,
			'meta_value'     => 'in_review',
		] );

		$counts = [];
		foreach ( $posts as $id ) {
			$reviewer = (int) get_post_meta( (int) $id, self::META_REVIEWER, true );
			if ( $reviewer > 0 ) {
				$counts[ $reviewer ] = ( $counts[ $reviewer ] ?? 0 ) + 1;
			}
		}

		$workload = [];
		foreach ( $counts as $reviewer_id => $count ) {
			$workload[] = [
				'reviewer_id' => $reviewer_id,
				'post_count'  => $count,
			];
		}

		usort( $workload, static fn( $a, $b ) => $b['post_count'] <=> $a['post_count'] );

		return $workload;
	}

	// -----------------------------------------------------------------------
	// WordPress hooks
	// -----------------------------------------------------------------------

	/**
	 * When a post transitions to 'publish', mark it as approved in meta.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function on_save_post( int $post_id, \WP_Post $post ): void {
		if ( 'publish' === $post->post_status ) {
			$stage = $this->get_stage( $post_id );
			if ( 'approved' !== $stage ) {
				$this->transition( $post_id, 'approved', get_current_user_id(), 'auto-approved on publish' );
			}
		}
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/** @return \WP_REST_Response */
	public function rest_list_posts( \WP_REST_Request $request ): \WP_REST_Response {
		$stage = sanitize_key( (string) $request->get_param( 'stage' ) );
		$args  = [
			'post_status'    => 'draft',
			'posts_per_page' => 50,
			'fields'         => 'ids',
		];
		if ( $stage ) {
			$args['meta_key']   = self::META_STAGE;
			$args['meta_value'] = $stage;
		}

		$posts = get_posts( $args );

		$items = array_map( function ( int $id ): array {
			return [
				'id'       => $id,
				'title'    => get_the_title( $id ),
				'stage'    => $this->get_stage( $id ),
				'reviewer' => (int) get_post_meta( $id, self::META_REVIEWER, true ),
			];
		}, $posts );

		return new \WP_REST_Response( [ 'posts' => $items ], 200 );
	}

	/** @return \WP_REST_Response|\WP_Error */
	public function rest_submit( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id = (int) $request->get_param( 'id' );
		if ( ! get_post( $id ) ) {
			return new \WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
		}
		$this->transition( $id, 'in_review', get_current_user_id() );
		return new \WP_REST_Response( [ 'stage' => 'in_review' ], 200 );
	}

	/** @return \WP_REST_Response|\WP_Error */
	public function rest_approve( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id = (int) $request->get_param( 'id' );
		if ( ! get_post( $id ) ) {
			return new \WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
		}
		$this->transition( $id, 'approved', get_current_user_id() );
		return new \WP_REST_Response( [ 'stage' => 'approved' ], 200 );
	}

	/** @return \WP_REST_Response|\WP_Error */
	public function rest_revise( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id       = (int) $request->get_param( 'id' );
		$feedback = sanitize_text_field( (string) $request->get_param( 'feedback' ) );
		if ( ! get_post( $id ) ) {
			return new \WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
		}
		$this->transition( $id, 'needs_revision', get_current_user_id(), $feedback );
		return new \WP_REST_Response( [ 'stage' => 'needs_revision' ], 200 );
	}

	/** @return \WP_REST_Response|\WP_Error */
	public function rest_add_comment( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id     = (int) $request->get_param( 'id' );
		$text   = (string) $request->get_param( 'text' );
		$offset = (int) $request->get_param( 'offset' );
		if ( ! get_post( $id ) ) {
			return new \WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
		}
		$comment = $this->add_comment( $id, get_current_user_id(), $text, $offset );
		return new \WP_REST_Response( $comment, 201 );
	}

	/** @return \WP_REST_Response|\WP_Error */
	public function rest_get_history( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id = (int) $request->get_param( 'id' );
		if ( ! get_post( $id ) ) {
			return new \WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
		}
		return new \WP_REST_Response( [ 'history' => $this->get_history( $id ) ], 200 );
	}

	/** @return \WP_REST_Response */
	public function rest_get_workload( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( [ 'workload' => $this->get_workload() ], 200 );
	}

	// -----------------------------------------------------------------------
	// Permission callbacks
	// -----------------------------------------------------------------------

	/** @return bool */
	public function perm_view(): bool {
		return is_user_logged_in();
	}

	/** @return bool */
	public function perm_author(): bool {
		return current_user_can( 'edit_posts' );
	}

	/** @return bool */
	public function perm_editor(): bool {
		return current_user_can( 'publish_posts' );
	}

	// -----------------------------------------------------------------------
	// Internal helpers
	// -----------------------------------------------------------------------

	/**
	 * Append an entry to the post's transition history.
	 *
	 * @param int    $post_id
	 * @param string $from
	 * @param string $to
	 * @param int    $actor_id
	 * @param string $note
	 */
	private function append_history( int $post_id, string $from, string $to, int $actor_id, string $note ): void {
		$history   = $this->get_history( $post_id );
		$history[] = [
			'from'      => $from,
			'to'        => $to,
			'actor_id'  => $actor_id,
			'note'      => $note,
			'timestamp' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		];
		update_post_meta( $post_id, self::META_HISTORY, wp_json_encode( $history ) );
	}
}
