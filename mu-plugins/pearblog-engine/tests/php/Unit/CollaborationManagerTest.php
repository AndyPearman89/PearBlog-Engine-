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

	private CollaborationManager $mgr;
	private const POST = 100;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$this->mgr = new CollaborationManager();
	}

	// -----------------------------------------------------------------------
	// Status
	// -----------------------------------------------------------------------

	public function test_get_status_returns_pending_by_default(): void {
		$this->assertSame( 'pending', $this->mgr->get_status( self::POST ) );
	}

	public function test_set_and_get_status(): void {
		$this->mgr->set_status( self::POST, 'in_review' );
		$this->assertSame( 'in_review', $this->mgr->get_status( self::POST ) );
	}

	public function test_set_status_all_valid_statuses(): void {
		foreach ( CollaborationManager::STATUSES as $status ) {
			$this->mgr->set_status( self::POST, $status );
			$this->assertSame( $status, $this->mgr->get_status( self::POST ) );
		}
	}

	public function test_set_status_invalid_throws_exception(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->mgr->set_status( self::POST, 'flying' );
	}

	// -----------------------------------------------------------------------
	// Reviewers
	// -----------------------------------------------------------------------

	public function test_get_reviewers_returns_empty_initially(): void {
		$this->assertSame( [], $this->mgr->get_reviewers( self::POST ) );
	}

	public function test_assign_reviewers_persists_ids(): void {
		$this->mgr->assign_reviewers( self::POST, [ 5, 10, 15 ] );
		$this->assertSame( [ 5, 10, 15 ], $this->mgr->get_reviewers( self::POST ) );
	}

	public function test_assign_reviewers_deduplicates(): void {
		$this->mgr->assign_reviewers( self::POST, [ 3, 3, 7 ] );
		$this->assertCount( 2, $this->mgr->get_reviewers( self::POST ) );
	}

	// -----------------------------------------------------------------------
	// Comments
	// -----------------------------------------------------------------------

	public function test_get_comments_returns_empty_initially(): void {
		$this->assertSame( [], $this->mgr->get_comments( self::POST ) );
	}

	public function test_add_comment_returns_comment_with_id(): void {
		$comment = $this->mgr->add_comment( self::POST, 'Great intro!' );

		$this->assertArrayHasKey( 'id', $comment );
		$this->assertSame( 'Great intro!', $comment['text'] );
		$this->assertFalse( $comment['resolved'] );
	}

	public function test_add_multiple_comments(): void {
		$this->mgr->add_comment( self::POST, 'First.' );
		$this->mgr->add_comment( self::POST, 'Second.' );

		$this->assertCount( 2, $this->mgr->get_comments( self::POST ) );
	}

	public function test_delete_comment_removes_it(): void {
		$c = $this->mgr->add_comment( self::POST, 'To delete.' );
		$deleted = $this->mgr->delete_comment( self::POST, $c['id'] );

		$this->assertTrue( $deleted );
		$this->assertSame( [], $this->mgr->get_comments( self::POST ) );
	}

	public function test_delete_nonexistent_comment_returns_false(): void {
		$deleted = $this->mgr->delete_comment( self::POST, 'nope' );
		$this->assertFalse( $deleted );
	}

	// -----------------------------------------------------------------------
	// History
	// -----------------------------------------------------------------------

	public function test_history_records_status_change(): void {
		$this->mgr->set_status( self::POST, 'in_review' );
		$history = $this->mgr->get_history( self::POST );

		$this->assertNotEmpty( $history );
		$this->assertSame( 'status_changed', $history[0]['action'] );
	}

	public function test_history_records_reviewer_assignment(): void {
		$this->mgr->assign_reviewers( self::POST, [ 1, 2 ] );
		$history = $this->mgr->get_history( self::POST );

		$actions = array_column( $history, 'action' );
		$this->assertContains( 'reviewers_assigned', $actions );
	}

	public function test_history_records_review_submission(): void {
		$this->mgr->submit_review( self::POST, 'approved', 'Looks good.' );
		$history = $this->mgr->get_history( self::POST );

		$actions = array_column( $history, 'action' );
		$this->assertContains( 'review_submitted', $actions );
		$this->assertContains( 'status_changed', $actions );
	}

	// -----------------------------------------------------------------------
	// submit_review()
	// -----------------------------------------------------------------------

	public function test_submit_review_updates_status(): void {
		$this->mgr->submit_review( self::POST, 'changes_requested' );
		$this->assertSame( 'changes_requested', $this->mgr->get_status( self::POST ) );
	}

	// -----------------------------------------------------------------------
	// get_state()
	// -----------------------------------------------------------------------

	public function test_get_state_returns_expected_keys(): void {
		$state = $this->mgr->get_state( self::POST );

		$this->assertArrayHasKey( 'post_id', $state );
		$this->assertArrayHasKey( 'status', $state );
		$this->assertArrayHasKey( 'reviewers', $state );
		$this->assertArrayHasKey( 'comments', $state );
		$this->assertArrayHasKey( 'history', $state );
	}

	// -----------------------------------------------------------------------
	// on_pipeline_completed()
	// -----------------------------------------------------------------------

	public function test_on_pipeline_completed_sets_pending_for_new_post(): void {
		$this->mgr->on_pipeline_completed( self::POST );
		$this->assertSame( 'pending', $this->mgr->get_status( self::POST ) );
	}

	public function test_on_pipeline_completed_does_not_overwrite_existing_status(): void {
		$this->mgr->set_status( self::POST, 'approved' );
		$this->mgr->on_pipeline_completed( self::POST );
		$this->assertSame( 'approved', $this->mgr->get_status( self::POST ) );
	}
}
