<?php
/**
 * Tests for ErrorTracker.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Monitoring\ErrorTracker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\Monitoring\ErrorTracker
 */
class ErrorTrackerTest extends TestCase {

	/** @var ErrorTracker */
	private ErrorTracker $tracker;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_current_user_can'] = false;
		$this->tracker = new ErrorTracker();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options'] = [];
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_log_constant(): void {
		$this->assertSame( 'pearblog_error_log', ErrorTracker::OPTION_LOG );
	}

	public function test_type_labels_map_php_error_constants(): void {
		$this->assertArrayHasKey( E_WARNING, ErrorTracker::TYPE_LABELS );
		$this->assertArrayHasKey( E_NOTICE, ErrorTracker::TYPE_LABELS );
		$this->assertArrayHasKey( E_USER_ERROR, ErrorTracker::TYPE_LABELS );
		$this->assertSame( 'Warning', ErrorTracker::TYPE_LABELS[ E_WARNING ] );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_returns_true_by_default(): void {
		$this->assertTrue( $this->tracker->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_disabled(): void {
		$GLOBALS['_options']['pearblog_error_tracker_enabled'] = false;
		$this->assertFalse( $this->tracker->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// get_log / clear_log / append_entry
	// -----------------------------------------------------------------------

	public function test_get_log_returns_empty_array_initially(): void {
		$this->assertSame( [], $this->tracker->get_log() );
	}

	public function test_get_log_returns_empty_for_non_array_option(): void {
		$GLOBALS['_options'][ ErrorTracker::OPTION_LOG ] = 'corrupted';
		$this->assertSame( [], $this->tracker->get_log() );
	}

	public function test_append_entry_stores_entry(): void {
		$entry = [ 'type' => E_WARNING, 'message' => 'Test warning', 'file' => 'pearblog-test.php', 'line' => 10, 'at' => time() ];
		$this->tracker->append_entry( $entry );
		$log = $this->tracker->get_log();
		$this->assertCount( 1, $log );
		$this->assertSame( E_WARNING, $log[0]['type'] );
	}

	public function test_append_entry_multiple_entries(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			$this->tracker->append_entry( [ 'type' => E_NOTICE, 'message' => "Notice #{$i}", 'file' => 'pearblog.php', 'line' => $i, 'at' => time() ] );
		}
		$this->assertCount( 5, $this->tracker->get_log() );
	}

	public function test_append_entry_enforces_ring_buffer(): void {
		$GLOBALS['_options']['pearblog_error_tracker_max'] = 3;

		for ( $i = 0; $i < 5; $i++ ) {
			$this->tracker->append_entry( [ 'type' => E_WARNING, 'message' => "Error {$i}", 'file' => 'pearblog.php', 'line' => $i, 'at' => $i ] );
		}

		$log = $this->tracker->get_log();
		$this->assertCount( 3, $log );
		// Should retain the last 3 entries.
		$this->assertSame( 'Error 2', $log[0]['message'] );
	}

	public function test_clear_log_empties_the_log(): void {
		$this->tracker->append_entry( [ 'type' => E_NOTICE, 'message' => 'test', 'file' => 'pearblog.php', 'line' => 1, 'at' => time() ] );
		$this->tracker->clear_log();
		$this->assertSame( [], $this->tracker->get_log() );
	}

	// -----------------------------------------------------------------------
	// handle_php_error
	// -----------------------------------------------------------------------

	public function test_handle_php_error_returns_false(): void {
		$result = $this->tracker->handle_php_error( E_WARNING, 'test error', 'pearblog-plugin.php', 42 );
		$this->assertFalse( $result );
	}

	public function test_handle_php_error_records_pearblog_files(): void {
		$this->tracker->handle_php_error( E_WARNING, 'test warning', 'pearblog-engine.php', 10 );
		$log = $this->tracker->get_log();
		$this->assertCount( 1, $log );
		$this->assertSame( E_WARNING, $log[0]['type'] );
		$this->assertSame( 'test warning', $log[0]['message'] );
	}

	public function test_handle_php_error_ignores_non_pearblog_files(): void {
		$this->tracker->handle_php_error( E_WARNING, 'unrelated warning', '/wp-includes/class-wp.php', 100 );
		$this->assertSame( [], $this->tracker->get_log() );
	}

	public function test_handle_php_error_fires_action(): void {
		$fired = false;
		$GLOBALS['_action_handlers']['pearblog_error_captured'] = function() use ( &$fired ) {
			$fired = true;
		};

		$this->tracker->handle_php_error( E_WARNING, 'hook test', 'pearblog-test.php', 1 );
		$this->assertTrue( $fired );
	}

	// -----------------------------------------------------------------------
	// get_summary
	// -----------------------------------------------------------------------

	public function test_get_summary_returns_empty_array_initially(): void {
		$this->assertSame( [], $this->tracker->get_summary() );
	}

	public function test_get_summary_groups_by_type(): void {
		$this->tracker->append_entry( [ 'type' => E_WARNING, 'message' => 'w1', 'file' => 'pearblog.php', 'line' => 1, 'at' => time() ] );
		$this->tracker->append_entry( [ 'type' => E_WARNING, 'message' => 'w2', 'file' => 'pearblog.php', 'line' => 2, 'at' => time() ] );
		$this->tracker->append_entry( [ 'type' => E_NOTICE, 'message' => 'n1', 'file' => 'pearblog.php', 'line' => 3, 'at' => time() ] );

		$summary = $this->tracker->get_summary();
		$this->assertSame( 2, $summary['Warning'] );
		$this->assertSame( 1, $summary['Notice'] );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	public function test_register_routes_adds_routes(): void {
		$GLOBALS['_rest_routes'] = [];
		$this->tracker->register_routes();
		$this->assertNotEmpty( $GLOBALS['_rest_routes'] );
	}

	public function test_rest_permission_requires_manage_options(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->tracker->rest_permission() );

		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->tracker->rest_permission() );
	}

	public function test_rest_list_returns_log_newest_first(): void {
		$this->tracker->append_entry( [ 'type' => E_WARNING, 'message' => 'first', 'file' => 'pb.php', 'line' => 1, 'at' => 1000 ] );
		$this->tracker->append_entry( [ 'type' => E_NOTICE,  'message' => 'second', 'file' => 'pb.php', 'line' => 2, 'at' => 2000 ] );

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/errors' );
		$response = $this->tracker->rest_list( $request );
		$data     = $response->get_data();

		$this->assertSame( 2, $data['total'] );
		// Newest first: 'second' should be first.
		$this->assertSame( 'second', $data['errors'][0]['message'] );
	}

	public function test_rest_clear_empties_log(): void {
		$this->tracker->append_entry( [ 'type' => E_NOTICE, 'message' => 'test', 'file' => 'pb.php', 'line' => 1, 'at' => time() ] );

		$request  = new \WP_REST_Request( 'DELETE', '/pearblog/v1/errors' );
		$response = $this->tracker->rest_clear( $request );

		$this->assertTrue( $response->get_data()['success'] );
		$this->assertSame( [], $this->tracker->get_log() );
	}

	public function test_rest_summary_returns_grouped_counts(): void {
		$this->tracker->append_entry( [ 'type' => E_USER_ERROR, 'message' => 'ue', 'file' => 'pb.php', 'line' => 1, 'at' => time() ] );

		$request  = new \WP_REST_Request( 'GET', '/pearblog/v1/errors/summary' );
		$response = $this->tracker->rest_summary( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'User Error', $data );
		$this->assertSame( 1, $data['User Error'] );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_hooks_when_enabled(): void {
		$GLOBALS['_options']['pearblog_error_tracker_enabled'] = true;
		$this->tracker->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}

	public function test_register_skips_hooks_when_disabled(): void {
		$GLOBALS['_options']['pearblog_error_tracker_enabled'] = false;
		$GLOBALS['_actions'] = [];
		$this->tracker->register();
		$this->assertEmpty( $GLOBALS['_actions'] );
	}
}
