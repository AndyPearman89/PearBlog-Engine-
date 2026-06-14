<?php
/**
 * Collaboration Manager — V9.0 F9
 *
 * Content collaboration platform: approval workflows, editorial assignment,
 * inline comments, version tracking, and team workload distribution.
 *
 * @package PearBlogEngine\Content
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * CollaborationManager
 *
 * Manages the complete content review lifecycle and team collaboration
 * features for AI-generated and manually authored content.
 *
 * Persistence is handled via WordPress post meta and options so no
 * additional database tables are required at this stage.
 */
class CollaborationManager {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** Meta key: JSON-encoded array of review requests for a post. */
	private const META_REVIEWS = '_pearblog_review_requests';

	/** Meta key: JSON-encoded array of inline comments for a post. */
	private const META_COMMENTS = '_pearblog_collab_comments';

	/** Meta key: assigned editor user ID. */
	private const META_EDITOR = '_pearblog_assigned_editor';

	/** Meta key: JSON-encoded version history snapshots. */
	private const META_VERSIONS = '_pearblog_version_history';

	/** Option key: running counter for review IDs. */
	private const OPT_REVIEW_COUNTER = 'pearblog_review_counter';

	/** Option key: running counter for comment IDs. */
	private const OPT_COMMENT_COUNTER = 'pearblog_comment_counter';

	/** Max version snapshots to keep per post. */
	private const MAX_VERSIONS = 20;

	// Review statuses.
	public const STATUS_PENDING  = 'pending';
	public const STATUS_APPROVED = 'approved';
	public const STATUS_REJECTED = 'rejected';

	// -----------------------------------------------------------------------
	// Public API — Review Requests
	// -----------------------------------------------------------------------

	/**
	 * Create a review request for a post.
	 *
	 * @param  int    $post_id     Post to review.
	 * @param  int    $reviewer_id WordPress user ID of the reviewer.
	 * @param  string $notes       Optional notes for the reviewer.
	 * @return int Review request ID (auto-incremented).
	 */
	public function create_review_request( int $post_id, int $reviewer_id, string $notes = '' ): int {
		$review_id = $this->next_id( self::OPT_REVIEW_COUNTER );
		$reviews   = $this->get_review_requests( $post_id );

		$reviews[] = [
			'id'          => $review_id,
			'post_id'     => $post_id,
			'reviewer_id' => $reviewer_id,
			'status'      => self::STATUS_PENDING,
			'notes'       => sanitize_textarea_field( $notes ),
			'created_at'  => time(),
			'updated_at'  => time(),
			'feedback'    => '',
		];

		update_post_meta( $post_id, self::META_REVIEWS, wp_json_encode( $reviews ) );

		/**
		 * Fires when a new review request is created.
		 *
		 * @param int    $review_id   Review ID.
		 * @param int    $post_id     Post ID.
		 * @param int    $reviewer_id Reviewer user ID.
		 */
		do_action( 'pearblog_review_requested', $review_id, $post_id, $reviewer_id );

		return $review_id;
	}

	/**
	 * Approve a review request.
	 *
	 * @param  int $review_id   Review ID (as returned by create_review_request).
	 * @param  int $reviewer_id User performing the approval.
	 * @return bool True on success, false if review not found.
	 */
	public function approve_content( int $review_id, int $reviewer_id ): bool {
		return $this->update_review_status( $review_id, $reviewer_id, self::STATUS_APPROVED, '' );
	}

	/**
	 * Reject a review request with mandatory feedback.
	 *
	 * @param  int    $review_id   Review ID.
	 * @param  int    $reviewer_id User performing the rejection.
	 * @param  string $feedback    Reason for rejection (required).
	 * @return bool True on success, false if review not found or feedback empty.
	 */
	public function reject_content( int $review_id, int $reviewer_id, string $feedback ): bool {
		if ( '' === trim( $feedback ) ) {
			return false;
		}
		return $this->update_review_status( $review_id, $reviewer_id, self::STATUS_REJECTED, $feedback );
	}

	/**
	 * Retrieve all review requests for a post.
	 *
	 * @param  int $post_id Post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_review_requests( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META_REVIEWS, true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Return all pending review requests across all posts for a given reviewer.
	 *
	 * When $reviewer_id is 0 all pending reviews across all reviewers are returned.
	 *
	 * @param  int $reviewer_id Optional reviewer filter (0 = all).
	 * @return array<int, array<string, mixed>>
	 */
	public function get_pending_reviews( int $reviewer_id = 0 ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta}
				 WHERE meta_key = %s",
				self::META_REVIEWS
			),
			ARRAY_A
		);

		$pending = [];
		foreach ( $rows as $row ) {
			$reviews = json_decode( $row['meta_value'] ?? '', true );
			if ( ! is_array( $reviews ) ) {
				continue;
			}
			foreach ( $reviews as $review ) {
				if ( $review['status'] !== self::STATUS_PENDING ) {
					continue;
				}
				if ( $reviewer_id > 0 && (int) $review['reviewer_id'] !== $reviewer_id ) {
					continue;
				}
				$pending[] = $review;
			}
		}

		usort( $pending, static fn( $a, $b ) => $b['created_at'] <=> $a['created_at'] );

		return $pending;
	}

	// -----------------------------------------------------------------------
	// Public API — Inline Comments
	// -----------------------------------------------------------------------

	/**
	 * Add an inline collaboration comment to a post.
	 *
	 * @param  int    $post_id   Post ID.
	 * @param  int    $user_id   Commenting user.
	 * @param  string $comment   Comment body.
	 * @param  int    $parent_id Parent comment ID for threaded replies (0 = root).
	 * @return int New comment ID.
	 */
	public function add_comment( int $post_id, int $user_id, string $comment, int $parent_id = 0 ): int {
		$comment_id = $this->next_id( self::OPT_COMMENT_COUNTER );
		$comments   = $this->get_comments( $post_id );

		$comments[] = [
			'id'         => $comment_id,
			'post_id'    => $post_id,
			'user_id'    => $user_id,
			'comment'    => sanitize_textarea_field( $comment ),
			'parent_id'  => $parent_id,
			'resolved'   => false,
			'created_at' => time(),
		];

		update_post_meta( $post_id, self::META_COMMENTS, wp_json_encode( $comments ) );

		/**
		 * Fires when a collaboration comment is added.
		 *
		 * @param int $comment_id New comment ID.
		 * @param int $post_id    Post ID.
		 * @param int $user_id    Commenting user ID.
		 */
		do_action( 'pearblog_collab_comment_added', $comment_id, $post_id, $user_id );

		return $comment_id;
	}

	/**
	 * Resolve (close) an inline comment.
	 *
	 * @param  int $post_id    Post ID.
	 * @param  int $comment_id Comment to resolve.
	 * @return bool True if found and updated.
	 */
	public function resolve_comment( int $post_id, int $comment_id ): bool {
		$comments = $this->get_comments( $post_id );
		$updated  = false;

		foreach ( $comments as &$c ) {
			if ( (int) $c['id'] === $comment_id ) {
				$c['resolved'] = true;
				$updated       = true;
				break;
			}
		}
		unset( $c );

		if ( $updated ) {
			update_post_meta( $post_id, self::META_COMMENTS, wp_json_encode( $comments ) );
		}

		return $updated;
	}

	/**
	 * Retrieve all collaboration comments for a post.
	 *
	 * @param  int  $post_id         Post ID.
	 * @param  bool $include_resolved Whether to include resolved comments.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_comments( int $post_id, bool $include_resolved = true ): array {
		$raw = get_post_meta( $post_id, self::META_COMMENTS, true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}
		$comments = json_decode( $raw, true );
		if ( ! is_array( $comments ) ) {
			return [];
		}
		if ( ! $include_resolved ) {
			$comments = array_values(
				array_filter( $comments, static fn( $c ) => ! $c['resolved'] )
			);
		}
		return $comments;
	}

	// -----------------------------------------------------------------------
	// Public API — Editorial Assignment
	// -----------------------------------------------------------------------

	/**
	 * Assign an editor to a post.
	 *
	 * @param  int $post_id Post ID.
	 * @param  int $user_id Editor user ID.
	 * @return bool Always true (write is always attempted).
	 */
	public function assign_editor( int $post_id, int $user_id ): bool {
		update_post_meta( $post_id, self::META_EDITOR, $user_id );

		/**
		 * Fires when an editor is assigned to a post.
		 *
		 * @param int $post_id Post ID.
		 * @param int $user_id Assigned editor user ID.
		 */
		do_action( 'pearblog_editor_assigned', $post_id, $user_id );

		return true;
	}

	/**
	 * Get the assigned editor for a post.
	 *
	 * @param  int $post_id Post ID.
	 * @return int|null User ID or null if unassigned.
	 */
	public function get_assigned_editor( int $post_id ): ?int {
		$raw = get_post_meta( $post_id, self::META_EDITOR, true );
		return $raw !== '' && $raw !== false ? (int) $raw : null;
	}

	// -----------------------------------------------------------------------
	// Public API — Version History
	// -----------------------------------------------------------------------

	/**
	 * Snapshot the current content of a post into version history.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  string $content Content body to snapshot.
	 * @param  int    $user_id User creating the snapshot.
	 * @param  string $label   Optional human-readable label.
	 * @return int Version number (1-based, monotonically increasing).
	 */
	public function snapshot_version( int $post_id, string $content, int $user_id, string $label = '' ): int {
		$versions = $this->get_content_history( $post_id );
		$version  = count( $versions ) + 1;

		$versions[] = [
			'version'    => $version,
			'user_id'    => $user_id,
			'label'      => sanitize_text_field( $label ),
			'excerpt'    => mb_substr( $content, 0, 500 ),
			'hash'       => md5( $content ),
			'created_at' => time(),
		];

		// Trim to rolling window.
		if ( count( $versions ) > self::MAX_VERSIONS ) {
			$versions = array_slice( $versions, -self::MAX_VERSIONS );
		}

		update_post_meta( $post_id, self::META_VERSIONS, wp_json_encode( $versions ) );

		return $version;
	}

	/**
	 * Retrieve version history for a post.
	 *
	 * @param  int $post_id Post ID.
	 * @return array<int, array<string, mixed>> Ordered oldest-first.
	 */
	public function get_content_history( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META_VERSIONS, true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	// -----------------------------------------------------------------------
	// Public API — Team Workload
	// -----------------------------------------------------------------------

	/**
	 * Return a workload summary for each editor: post count, pending reviews.
	 *
	 * @return array<int, array{user_id: int, assigned_posts: int, pending_reviews: int}>
	 */
	public function get_team_workload(): array {
		global $wpdb;

		// Count assigned posts per editor.
		$editor_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value AS user_id, COUNT(*) AS assigned_posts
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = %s
				 GROUP BY meta_value",
				self::META_EDITOR
			),
			ARRAY_A
		);

		$workload = [];
		foreach ( $editor_rows as $row ) {
			$uid = (int) $row['user_id'];
			$workload[ $uid ] = [
				'user_id'        => $uid,
				'assigned_posts' => (int) $row['assigned_posts'],
				'pending_reviews'=> 0,
			];
		}

		// Tally pending reviews per reviewer.
		$pending = $this->get_pending_reviews();
		foreach ( $pending as $review ) {
			$uid = (int) $review['reviewer_id'];
			if ( ! isset( $workload[ $uid ] ) ) {
				$workload[ $uid ] = [
					'user_id'        => $uid,
					'assigned_posts' => 0,
					'pending_reviews'=> 0,
				];
			}
			$workload[ $uid ]['pending_reviews']++;
		}

		return array_values( $workload );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Find a review request by ID across all posts in a given review list.
	 *
	 * @param  int $review_id  Review ID to locate.
	 * @return array{post_id: int, index: int}|null
	 */
	private function find_review( int $review_id ): ?array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				self::META_REVIEWS
			),
			ARRAY_A
		);

		foreach ( $rows as $row ) {
			$reviews = json_decode( $row['meta_value'] ?? '', true );
			if ( ! is_array( $reviews ) ) {
				continue;
			}
			foreach ( $reviews as $i => $review ) {
				if ( (int) $review['id'] === $review_id ) {
					return [ 'post_id' => (int) $row['post_id'], 'index' => $i ];
				}
			}
		}

		return null;
	}

	/**
	 * Update the status of a review request.
	 *
	 * @param  int    $review_id   Review ID.
	 * @param  int    $reviewer_id Reviewer performing the action.
	 * @param  string $status      New status.
	 * @param  string $feedback    Feedback text (for rejections).
	 * @return bool True on success.
	 */
	private function update_review_status(
		int $review_id,
		int $reviewer_id,
		string $status,
		string $feedback
	): bool {
		$location = $this->find_review( $review_id );
		if ( null === $location ) {
			return false;
		}

		$post_id = $location['post_id'];
		$index   = $location['index'];
		$reviews = $this->get_review_requests( $post_id );

		$reviews[ $index ]['status']      = $status;
		$reviews[ $index ]['reviewer_id'] = $reviewer_id;
		$reviews[ $index ]['feedback']    = sanitize_textarea_field( $feedback );
		$reviews[ $index ]['updated_at']  = time();

		update_post_meta( $post_id, self::META_REVIEWS, wp_json_encode( $reviews ) );

		$hook = self::STATUS_APPROVED === $status ? 'pearblog_content_approved' : 'pearblog_content_rejected';
		/**
		 * Fires when a review decision is recorded.
		 *
		 * @param int    $review_id   Review ID.
		 * @param int    $post_id     Post ID.
		 * @param int    $reviewer_id Reviewer user ID.
		 * @param string $feedback    Feedback text.
		 */
		do_action( $hook, $review_id, $post_id, $reviewer_id, $feedback );

		return true;
	}

	/**
	 * Generate the next auto-incremented ID for a counter option.
	 *
	 * @param  string $option_key WP option to increment.
	 * @return int New ID.
	 */
	private function next_id( string $option_key ): int {
		$current = (int) get_option( $option_key, 0 );
		$next    = $current + 1;
		update_option( $option_key, $next, false );
		return $next;
	}
}
