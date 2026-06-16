<?php
/**
 * Unit tests for CollaborationManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\CollaborationManager;

class CollaborationManagerTest extends TestCase {

	private CollaborationManager $cm;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_post_meta']       = [];
		$GLOBALS['_options']         = [];
		$GLOBALS['_db_results']      = [];
		$GLOBALS['_current_user_id'] = 1;
		$this->cm = new CollaborationManager();
	}

	// -----------------------------------------------------------------------
	// create_review_request
	// -----------------------------------------------------------------------

	public function test_create_review_request_returns_positive_id(): void {
		$id = $this->cm->create_review_request( 10, 5 );
		$this->assertGreaterThan( 0, $id );
	}

	public function test_create_review_request_increments_id(): void {
		$id1 = $this->cm->create_review_request( 10, 5 );
		$id2 = $this->cm->create_review_request( 10, 5 );
		$this->assertGreaterThan( $id1, $id2 );
	}

	public function test_create_review_request_stores_pending_status(): void {
		$id      = $this->cm->create_review_request( 10, 5, 'Please check grammar.' );
		$reviews = $this->cm->get_review_requests( 10 );

		$this->assertCount( 1, $reviews );
		$this->assertSame( CollaborationManager::STATUS_PENDING, $reviews[0]['status'] );
		$this->assertSame( 5, $reviews[0]['reviewer_id'] );
		$this->assertSame( 'Please check grammar.', $reviews[0]['notes'] );
	}

	public function test_create_review_request_multiple_per_post(): void {
		$this->cm->create_review_request( 10, 5 );
		$this->cm->create_review_request( 10, 6 );

		$reviews = $this->cm->get_review_requests( 10 );
		$this->assertCount( 2, $reviews );
	}

	// -----------------------------------------------------------------------
	// approve_content
	// -----------------------------------------------------------------------

	public function test_approve_updates_status(): void {
		$review_id = $this->cm->create_review_request( 20, 7 );

		// Seed the DB stub so find_review can locate it.
		// update_post_meta wraps in [value]; use [0] to get the raw JSON string.
		$raw = $GLOBALS['_post_meta'][20]['_pearblog_review_requests'][0];
		$GLOBALS['_db_results'] = [
			[ 'post_id' => 20, 'meta_value' => $raw ],
		];

		$ok = $this->cm->approve_content( $review_id, 7 );
		$this->assertTrue( $ok );

		$reviews = $this->cm->get_review_requests( 20 );
		$this->assertSame( CollaborationManager::STATUS_APPROVED, $reviews[0]['status'] );
	}

	public function test_approve_returns_false_for_unknown_review(): void {
		$GLOBALS['_db_results'] = [];
		$ok = $this->cm->approve_content( 9999, 1 );
		$this->assertFalse( $ok );
	}

	// -----------------------------------------------------------------------
	// reject_content
	// -----------------------------------------------------------------------

	public function test_reject_requires_feedback(): void {
		$review_id = $this->cm->create_review_request( 30, 8 );
		$ok        = $this->cm->reject_content( $review_id, 8, '' );
		$this->assertFalse( $ok );
	}

	public function test_reject_updates_status_and_feedback(): void {
		$review_id = $this->cm->create_review_request( 31, 9 );
		$raw       = $GLOBALS['_post_meta'][31]['_pearblog_review_requests'][0];
		$GLOBALS['_db_results'] = [
			[ 'post_id' => 31, 'meta_value' => $raw ],
		];

		$ok = $this->cm->reject_content( $review_id, 9, 'Factual errors on paragraph 3.' );
		$this->assertTrue( $ok );

		$reviews = $this->cm->get_review_requests( 31 );
		$this->assertSame( CollaborationManager::STATUS_REJECTED, $reviews[0]['status'] );
		$this->assertSame( 'Factual errors on paragraph 3.', $reviews[0]['feedback'] );
	}

	// -----------------------------------------------------------------------
	// get_pending_reviews
	// -----------------------------------------------------------------------

	public function test_get_pending_reviews_empty_when_no_data(): void {
		$GLOBALS['_db_results'] = [];
		$pending = $this->cm->get_pending_reviews();
		$this->assertEmpty( $pending );
	}

	public function test_get_pending_reviews_returns_only_pending(): void {
		// Create two reviews: approve first, leave second pending.
		$r1 = $this->cm->create_review_request( 40, 10 );
		$r2 = $this->cm->create_review_request( 40, 11 );

		$raw = $GLOBALS['_post_meta'][40]['_pearblog_review_requests'][0];
		$GLOBALS['_db_results'] = [
			[ 'post_id' => 40, 'meta_value' => $raw ],
		];

		$this->cm->approve_content( $r1, 10 );

		// Refresh after approval.
		$GLOBALS['_db_results'][0]['meta_value'] = $GLOBALS['_post_meta'][40]['_pearblog_review_requests'][0];

		$pending = $this->cm->get_pending_reviews();
		$this->assertCount( 1, $pending );
		$this->assertSame( CollaborationManager::STATUS_PENDING, $pending[0]['status'] );
	}

	public function test_get_pending_reviews_filters_by_reviewer(): void {
		$this->cm->create_review_request( 41, 20 );
		$this->cm->create_review_request( 41, 21 );

		$raw = $GLOBALS['_post_meta'][41]['_pearblog_review_requests'][0];
		$GLOBALS['_db_results'] = [
			[ 'post_id' => 41, 'meta_value' => $raw ],
		];

		$for_20 = $this->cm->get_pending_reviews( 20 );
		$this->assertCount( 1, $for_20 );
		$this->assertSame( 20, $for_20[0]['reviewer_id'] );
	}

	// -----------------------------------------------------------------------
	// add_comment / get_comments
	// -----------------------------------------------------------------------

	public function test_add_comment_returns_positive_id(): void {
		$id = $this->cm->add_comment( 50, 1, 'Great intro paragraph.' );
		$this->assertGreaterThan( 0, $id );
	}

	public function test_add_comment_stored_correctly(): void {
		$this->cm->add_comment( 50, 1, 'Fix the heading.' );
		$comments = $this->cm->get_comments( 50 );

		$this->assertCount( 1, $comments );
		$this->assertSame( 'Fix the heading.', $comments[0]['comment'] );
		$this->assertFalse( $comments[0]['resolved'] );
	}

	public function test_add_comment_threaded_reply(): void {
		$parent = $this->cm->add_comment( 50, 1, 'Parent comment.' );
		$this->cm->add_comment( 50, 2, 'Reply.', $parent );

		$comments = $this->cm->get_comments( 50 );
		$this->assertSame( $parent, $comments[1]['parent_id'] );
	}

	public function test_resolve_comment(): void {
		$id = $this->cm->add_comment( 60, 1, 'This needs a citation.' );
		$ok = $this->cm->resolve_comment( 60, $id );

		$this->assertTrue( $ok );

		$open = $this->cm->get_comments( 60, false );
		$this->assertEmpty( $open );
	}

	public function test_resolve_comment_returns_false_for_unknown(): void {
		$ok = $this->cm->resolve_comment( 61, 9999 );
		$this->assertFalse( $ok );
	}

	// -----------------------------------------------------------------------
	// assign_editor
	// -----------------------------------------------------------------------

	public function test_assign_editor_stores_user_id(): void {
		$this->cm->assign_editor( 70, 15 );
		$this->assertSame( 15, $this->cm->get_assigned_editor( 70 ) );
	}

	public function test_get_assigned_editor_returns_null_when_unset(): void {
		$this->assertNull( $this->cm->get_assigned_editor( 71 ) );
	}

	public function test_assign_editor_can_be_reassigned(): void {
		$this->cm->assign_editor( 72, 3 );
		$this->cm->assign_editor( 72, 7 );
		$this->assertSame( 7, $this->cm->get_assigned_editor( 72 ) );
	}

	// -----------------------------------------------------------------------
	// snapshot_version / get_content_history
	// -----------------------------------------------------------------------

	public function test_snapshot_version_returns_version_number(): void {
		$v = $this->cm->snapshot_version( 80, 'Hello world content', 1, 'Initial draft' );
		$this->assertSame( 1, $v );
	}

	public function test_snapshot_version_increments(): void {
		$v1 = $this->cm->snapshot_version( 80, 'Version one', 1 );
		$v2 = $this->cm->snapshot_version( 80, 'Version two', 1 );
		$this->assertSame( $v1 + 1, $v2 );
	}

	public function test_snapshot_stores_hash(): void {
		$content = 'Some content to hash.';
		$this->cm->snapshot_version( 81, $content, 1 );
		$history = $this->cm->get_content_history( 81 );

		$this->assertSame( md5( $content ), $history[0]['hash'] );
	}

	public function test_get_content_history_empty_by_default(): void {
		$history = $this->cm->get_content_history( 999 );
		$this->assertSame( [], $history );
	}

	public function test_snapshot_stores_label(): void {
		$this->cm->snapshot_version( 82, 'Content', 1, 'Pre-review snapshot' );
		$history = $this->cm->get_content_history( 82 );
		$this->assertSame( 'Pre-review snapshot', $history[0]['label'] );
	}
}
