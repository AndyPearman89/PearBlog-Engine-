<?php
/**
 * Unit tests for AutopilotRunner.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\CLI\AutopilotRunner;

class AutopilotRunnerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_transients'] = [];
	}

	// -----------------------------------------------------------------------
	// Task list & resolve_tasks
	// -----------------------------------------------------------------------

	public function test_task_list_has_26_tasks(): void {
		$this->assertSame( 26, AutopilotRunner::get_task_count() );
	}

	public function test_resolve_all_returns_every_task(): void {
		$ids = AutopilotRunner::resolve_tasks( 'all' );
		$this->assertSame( AutopilotRunner::get_task_count(), count( $ids ) );
		$this->assertSame( '1.1', $ids[0] );
	}

	public function test_resolve_specific_tasks(): void {
		$ids = AutopilotRunner::resolve_tasks( '1.1,2.1,7.3' );
		$this->assertSame( [ '1.1', '2.1', '7.3' ], $ids );
	}

	public function test_resolve_ignores_invalid_ids(): void {
		$ids = AutopilotRunner::resolve_tasks( '1.1,99.9,2.1' );
		$this->assertSame( [ '1.1', '2.1' ], $ids );
	}

	public function test_resolve_empty_filter_returns_empty(): void {
		$ids = AutopilotRunner::resolve_tasks( '' );
		$this->assertSame( [], $ids );
	}

	public function test_resolve_all_is_case_insensitive(): void {
		$ids = AutopilotRunner::resolve_tasks( 'ALL' );
		$this->assertSame( AutopilotRunner::get_task_count(), count( $ids ) );
	}

	// -----------------------------------------------------------------------
	// Start
	// -----------------------------------------------------------------------

	public function test_start_sets_running_state(): void {
		$result = AutopilotRunner::start( 'enterprise', 'all' );

		$this->assertTrue( $result['success'] );
		$this->assertStringContainsString( 'enterprise', $result['message'] );

		$state = AutopilotRunner::get_state();
		$this->assertSame( AutopilotRunner::STATUS_RUNNING, $state['status'] );
		$this->assertSame( 'enterprise', $state['mode'] );
		$this->assertSame( '1.1', $state['current_task'] );
	}

	public function test_start_with_specific_tasks(): void {
		$result = AutopilotRunner::start( 'enterprise', '2.1,3.1' );

		$this->assertTrue( $result['success'] );

		$state = AutopilotRunner::get_state();
		$this->assertSame( [ '2.1', '3.1' ], $state['tasks'] );
		$this->assertSame( '2.1', $state['current_task'] );
	}

	public function test_start_rejects_invalid_mode(): void {
		$result = AutopilotRunner::start( 'turbo', 'all' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Invalid mode', $result['message'] );
	}

	public function test_start_rejects_when_already_running(): void {
		AutopilotRunner::start( 'enterprise', 'all' );

		$result = AutopilotRunner::start( 'enterprise', 'all' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'already running', $result['message'] );
	}

	public function test_start_rejects_empty_task_list(): void {
		$result = AutopilotRunner::start( 'enterprise', 'invalid_id' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'No valid tasks', $result['message'] );
	}

	public function test_start_records_start_time(): void {
		AutopilotRunner::start( 'enterprise', 'all' );

		$state = AutopilotRunner::get_state();
		$this->assertNotNull( $state['start_time'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $state['start_time'] );
	}

	// -----------------------------------------------------------------------
	// Pause / Resume
	// -----------------------------------------------------------------------

	public function test_pause_changes_status(): void {
		AutopilotRunner::start( 'enterprise', 'all' );

		$result = AutopilotRunner::pause();

		$this->assertTrue( $result['success'] );
		$this->assertSame( AutopilotRunner::STATUS_PAUSED, AutopilotRunner::get_state()['status'] );
	}

	public function test_pause_when_not_running_fails(): void {
		$result = AutopilotRunner::pause();

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'not running', $result['message'] );
	}

	public function test_resume_changes_status_back_to_running(): void {
		AutopilotRunner::start( 'enterprise', 'all' );
		AutopilotRunner::pause();

		$result = AutopilotRunner::resume();

		$this->assertTrue( $result['success'] );
		$this->assertSame( AutopilotRunner::STATUS_RUNNING, AutopilotRunner::get_state()['status'] );
	}

	public function test_resume_when_not_paused_fails(): void {
		$result = AutopilotRunner::resume();

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'not paused', $result['message'] );
	}

	public function test_pause_records_pause_time(): void {
		AutopilotRunner::start( 'enterprise', 'all' );
		AutopilotRunner::pause();

		$state = AutopilotRunner::get_state();
		$this->assertNotNull( $state['pause_time'] );
	}

	public function test_resume_clears_pause_time(): void {
		AutopilotRunner::start( 'enterprise', 'all' );
		AutopilotRunner::pause();
		AutopilotRunner::resume();

		$state = AutopilotRunner::get_state();
		$this->assertNull( $state['pause_time'] );
	}

	// -----------------------------------------------------------------------
	// Next
	// -----------------------------------------------------------------------

	public function test_next_advances_to_second_task(): void {
		AutopilotRunner::start( 'enterprise', '1.1,1.2,1.3' );

		$result = AutopilotRunner::next();

		$this->assertTrue( $result['success'] );

		$state = AutopilotRunner::get_state();
		$this->assertSame( '1.2', $state['current_task'] );
		$this->assertContains( '1.1', $state['completed'] );
	}

	public function test_next_completes_all_tasks(): void {
		AutopilotRunner::start( 'enterprise', '1.1,1.2' );

		AutopilotRunner::next(); // completes 1.1 → moves to 1.2
		$result = AutopilotRunner::next(); // completes 1.2 → all done

		$this->assertTrue( $result['success'] );
		$this->assertStringContainsString( 'All tasks completed', $result['message'] );

		$state = AutopilotRunner::get_state();
		$this->assertSame( AutopilotRunner::STATUS_IDLE, $state['status'] );
		$this->assertNull( $state['current_task'] );
	}

	public function test_next_when_idle_fails(): void {
		$result = AutopilotRunner::next();

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'not active', $result['message'] );
	}

	public function test_next_works_from_paused_state(): void {
		AutopilotRunner::start( 'enterprise', '1.1,1.2' );
		AutopilotRunner::pause();

		$result = AutopilotRunner::next();

		$this->assertTrue( $result['success'] );
		$this->assertSame( '1.2', AutopilotRunner::get_state()['current_task'] );
	}

	// -----------------------------------------------------------------------
	// Fail current
	// -----------------------------------------------------------------------

	public function test_fail_current_marks_and_advances(): void {
		AutopilotRunner::start( 'enterprise', '1.1,1.2,1.3' );

		$result = AutopilotRunner::fail_current();

		$this->assertTrue( $result['success'] );

		$state = AutopilotRunner::get_state();
		$this->assertContains( '1.1', $state['failed'] );
		$this->assertSame( '1.2', $state['current_task'] );
	}

	public function test_fail_when_no_current_task(): void {
		$result = AutopilotRunner::fail_current();

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'No current task', $result['message'] );
	}

	// -----------------------------------------------------------------------
	// Status summary
	// -----------------------------------------------------------------------

	public function test_status_summary_idle(): void {
		$summary = AutopilotRunner::get_status_summary();

		$this->assertSame( 'idle', $summary['status'] );
		$this->assertSame( 0, $summary['total'] );
		$this->assertSame( 0, $summary['completed'] );
		$this->assertSame( 0, $summary['failed'] );
	}

	public function test_status_summary_running(): void {
		AutopilotRunner::start( 'enterprise', '1.1,1.2,1.3' );

		$summary = AutopilotRunner::get_status_summary();

		$this->assertSame( 'running', $summary['status'] );
		$this->assertSame( 'enterprise', $summary['mode'] );
		$this->assertSame( '1.1', $summary['current_task'] );
		$this->assertSame( 3, $summary['total'] );
		$this->assertSame( 0, $summary['completed'] );
		$this->assertSame( 3, $summary['remaining'] );
		$this->assertSame( 0.0, $summary['progress_pct'] );
	}

	public function test_status_summary_with_progress(): void {
		AutopilotRunner::start( 'enterprise', '1.1,1.2,1.3' );
		AutopilotRunner::next();

		$summary = AutopilotRunner::get_status_summary();

		$this->assertSame( 1, $summary['completed'] );
		$this->assertSame( 2, $summary['remaining'] );
		$this->assertSame( 33.3, $summary['progress_pct'] );
	}

	// -----------------------------------------------------------------------
	// Reset
	// -----------------------------------------------------------------------

	public function test_reset_clears_state(): void {
		AutopilotRunner::start( 'enterprise', 'all' );
		AutopilotRunner::reset();

		$state = AutopilotRunner::get_state();
		$this->assertSame( 'idle', $state['status'] );
		$this->assertSame( [], $state['tasks'] );
	}

	// -----------------------------------------------------------------------
	// Standard mode
	// -----------------------------------------------------------------------

	public function test_start_standard_mode(): void {
		$result = AutopilotRunner::start( 'standard', 'all' );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'standard', AutopilotRunner::get_state()['mode'] );
	}

	// -----------------------------------------------------------------------
	// Edge cases
	// -----------------------------------------------------------------------

	public function test_get_state_returns_defaults_when_option_missing(): void {
		$state = AutopilotRunner::get_state();

		$this->assertSame( 'idle', $state['status'] );
		$this->assertSame( '', $state['mode'] );
		$this->assertNull( $state['current_task'] );
		$this->assertSame( [], $state['tasks'] );
	}

	public function test_get_state_handles_corrupt_option(): void {
		update_option( AutopilotRunner::STATE_OPTION, 'not_an_array' );

		$state = AutopilotRunner::get_state();
		$this->assertSame( 'idle', $state['status'] );
	}

	public function test_task_list_keys_are_consistent(): void {
		$tasks = AutopilotRunner::get_task_list();
		foreach ( $tasks as $id => $info ) {
			$this->assertArrayHasKey( 'phase', $info, "Task {$id} missing 'phase'" );
			$this->assertArrayHasKey( 'name', $info, "Task {$id} missing 'name'" );
			$this->assertArrayHasKey( 'priority', $info, "Task {$id} missing 'priority'" );
		}
	}
}
