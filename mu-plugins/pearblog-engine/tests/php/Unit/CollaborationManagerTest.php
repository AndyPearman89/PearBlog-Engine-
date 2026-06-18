<?php
/**
 * Unit tests for CollaborationManager (V9.0 F9).
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Pipeline\CollaborationManager;

class CollaborationManagerTest extends TestCase {

	private CollaborationManager $manager;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']        = [];
		$GLOBALS['_post_meta']      = [];
		$GLOBALS['_posts']          = [ 1 => [ 'post_status' => 'draft' ] ];
		$GLOBALS['_current_user_id'] = 10;
		$this->manager              = new CollaborationManager();
	}

	// -----------------------------------------------------------------------
	// get_stage / transition
	// -----------------------------------------------------------------------

	public function test_default_stage_is_draft(): void {
		$this->assertSame( 'draft', $this->manager->get_stage( 1 ) );
	}

	public function test_transition_changes_stage(): void {
		$this->manager->transition( 1, 'in_review', 10 );
		$this->assertSame( 'in_review', $this->manager->get_stage( 1 ) );
	}

	public function test_transition_returns_true_for_valid_stage(): void {
		$result = $this->manager->transition( 1, 'approved', 10 );
		$this->assertTrue( $result );
	}

	public function test_transition_returns_false_for_invalid_stage(): void {
		$result = $this->manager->transition( 1, 'invalid_stage', 10 );
		$this->assertFalse( $result );
	}

	public function test_transition_records_history_entry(): void {
		$this->manager->transition( 1, 'in_review', 10, 'needs eyes' );
		$history = $this->manager->get_history( 1 );
		$this->assertCount( 1, $history );
		$this->assertSame( 'draft', $history[0]['from'] );
		$this->assertSame( 'in_review', $history[0]['to'] );
		$this->assertSame( 10, $history[0]['actor_id'] );
		$this->assertSame( 'needs eyes', $history[0]['note'] );
	}

	public function test_multiple_transitions_build_history(): void {
		$this->manager->transition( 1, 'in_review', 10 );
		$this->manager->transition( 1, 'needs_revision', 20 );
		$this->manager->transition( 1, 'in_review', 10 );
		$history = $this->manager->get_history( 1 );
		$this->assertCount( 3, $history );
	}

	// -----------------------------------------------------------------------
	// assign_reviewer
	// -----------------------------------------------------------------------

	public function test_assign_reviewer_stores_meta(): void {
		$this->manager->assign_reviewer( 1, 42 );
		$reviewer = (int) get_post_meta( 1, CollaborationManager::META_REVIEWER, true );
		$this->assertSame( 42, $reviewer );
	}

	// -----------------------------------------------------------------------
	// add_comment / get_comments
	// -----------------------------------------------------------------------

	public function test_add_comment_returns_comment_with_id(): void {
		$comment = $this->manager->add_comment( 1, 10, 'Nice structure', 100 );
		$this->assertArrayHasKey( 'id', $comment );
		$this->assertNotEmpty( $comment['id'] );
	}

	public function test_add_comment_stores_text(): void {
		$this->manager->add_comment( 1, 10, 'Please expand intro' );
		$comments = $this->manager->get_comments( 1 );
		$this->assertCount( 1, $comments );
		$this->assertSame( 'Please expand intro', $comments[0]['text'] );
	}

	public function test_multiple_comments_accumulate(): void {
		$this->manager->add_comment( 1, 10, 'Comment A' );
		$this->manager->add_comment( 1, 20, 'Comment B' );
		$this->manager->add_comment( 1, 10, 'Comment C' );
		$this->assertCount( 3, $this->manager->get_comments( 1 ) );
	}

	public function test_get_comments_empty_for_new_post(): void {
		$this->assertSame( [], $this->manager->get_comments( 99 ) );
	}

	public function test_comment_stores_author_and_offset(): void {
		$comment = $this->manager->add_comment( 1, 7, 'Fix typo', 250 );
		$this->assertSame( 7, $comment['author_id'] );
		$this->assertSame( 250, $comment['offset'] );
	}

	// -----------------------------------------------------------------------
	// get_workload
	// -----------------------------------------------------------------------

	public function test_get_workload_returns_empty_when_no_reviewers(): void {
		$workload = $this->manager->get_workload();
		$this->assertSame( [], $workload );
	}

	// -----------------------------------------------------------------------
	// STAGES constant
	// -----------------------------------------------------------------------

	public function test_stages_contains_four_values(): void {
		$this->assertCount( 4, CollaborationManager::STAGES );
	}

	public function test_stages_contains_expected_names(): void {
		$this->assertContains( 'draft', CollaborationManager::STAGES );
		$this->assertContains( 'in_review', CollaborationManager::STAGES );
		$this->assertContains( 'needs_revision', CollaborationManager::STAGES );
		$this->assertContains( 'approved', CollaborationManager::STAGES );
	}

	// -----------------------------------------------------------------------
	// Permission callbacks
	// -----------------------------------------------------------------------

	public function test_perm_view_returns_true_when_logged_in(): void {
		$GLOBALS['_user_logged_in'] = true;
		$this->assertTrue( $this->manager->perm_view() );
	}

	public function test_perm_author_requires_edit_posts(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->manager->perm_author() );
	}

	public function test_perm_editor_requires_publish_posts(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->manager->perm_editor() );
	}

	public function test_perm_editor_false_without_capability(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->manager->perm_editor() );
	}
}
