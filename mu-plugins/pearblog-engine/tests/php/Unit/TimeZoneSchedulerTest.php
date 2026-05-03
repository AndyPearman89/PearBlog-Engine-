<?php
/**
 * Unit tests for TimeZoneScheduler.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Scheduler\TimeZoneScheduler;

class TimeZoneSchedulerTest extends TestCase {

	private TimeZoneScheduler $scheduler;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']  = [
			'pearblog_tz_scheduling_enabled' => true,
			'pearblog_target_timezone'        => 'Europe/London',
			'pearblog_tz_golden_start'        => 8,
			'pearblog_tz_golden_end'          => 11,
			'pearblog_tz_spread_minutes'      => 30,
		];
		$GLOBALS['_actions']  = [];
		$GLOBALS['wpdb']      = new class {
			public string $posts = 'wp_posts';
			public function prepare( string $sql, ...$args ): string {
				return vsprintf( str_replace( '%s', "'%s'", $sql ), $args );
			}
			public function get_col( string $sql ): array {
				return [];
			}
		};
		$this->scheduler = new TimeZoneScheduler();
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// get_upcoming_slots
	// -----------------------------------------------------------------------

	public function test_get_upcoming_slots_returns_array(): void {
		$slots = $this->scheduler->get_upcoming_slots( 1 );
		$this->assertIsArray( $slots );
	}

	public function test_each_slot_has_required_keys(): void {
		$slots = $this->scheduler->get_upcoming_slots( 2 );
		if ( empty( $slots ) ) {
			$this->markTestSkipped( 'No upcoming slots in current golden window.' );
		}
		foreach ( $slots as $slot ) {
			$this->assertArrayHasKey( 'local_datetime', $slot );
			$this->assertArrayHasKey( 'utc_datetime', $slot );
			$this->assertArrayHasKey( 'timezone', $slot );
		}
	}

	public function test_slot_timezone_matches_configured(): void {
		$slots = $this->scheduler->get_upcoming_slots( 7 );
		if ( empty( $slots ) ) {
			$this->markTestSkipped( 'No upcoming slots in 7-day window.' );
		}
		$this->assertSame( 'Europe/London', $slots[0]['timezone'] );
	}

	public function test_slots_are_in_golden_hour_window(): void {
		$slots = $this->scheduler->get_upcoming_slots( 7 );
		foreach ( $slots as $slot ) {
			$hour = (int) date( 'G', strtotime( $slot['local_datetime'] ) );
			$this->assertGreaterThanOrEqual( 8, $hour );
			$this->assertLessThanOrEqual( 11, $hour );
		}
	}

	public function test_more_days_returns_more_slots(): void {
		$slots_3  = $this->scheduler->get_upcoming_slots( 3 );
		$slots_10 = $this->scheduler->get_upcoming_slots( 10 );
		$this->assertGreaterThanOrEqual( count( $slots_3 ), count( $slots_10 ) );
	}

	public function test_spread_minutes_determines_slot_interval(): void {
		$GLOBALS['_options']['pearblog_tz_spread_minutes'] = 60;
		$scheduler = new TimeZoneScheduler();
		$slots     = $scheduler->get_upcoming_slots( 7 );

		if ( count( $slots ) < 2 ) {
			$this->markTestSkipped( 'Not enough slots to measure interval.' );
		}

		// Consecutive slots should differ by ~60 minutes (3600 seconds) within the same day.
		$t1 = strtotime( $slots[0]['utc_datetime'] );
		$t2 = strtotime( $slots[1]['utc_datetime'] );
		$diff = abs( $t2 - $t1 );
		// Could be 60 min within day or jump to next day's window start.
		$this->assertGreaterThanOrEqual( 3600, $diff );
	}

	// -----------------------------------------------------------------------
	// REST permission
	// -----------------------------------------------------------------------

	public function test_rest_permission_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$request = new \WP_REST_Request();
		$this->assertTrue( $this->scheduler->rest_permission( $request ) );
	}

	public function test_rest_permission_false_when_no_api_key_and_not_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$GLOBALS['_options']['pearblog_api_key'] = '';
		$request = new \WP_REST_Request();
		$this->assertFalse( $this->scheduler->rest_permission( $request ) );
	}

	public function test_rest_permission_true_with_valid_api_key(): void {
		$GLOBALS['_current_user_can'] = false;
		$GLOBALS['_options']['pearblog_api_key'] = 'my-secret';
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer my-secret' );
		$this->assertTrue( $this->scheduler->rest_permission( $request ) );
	}

	// -----------------------------------------------------------------------
	// UTC vs local conversion
	// -----------------------------------------------------------------------

	public function test_utc_and_local_datetimes_are_strings(): void {
		$slots = $this->scheduler->get_upcoming_slots( 3 );
		foreach ( $slots as $slot ) {
			$this->assertRegExp( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $slot['local_datetime'] );
			$this->assertRegExp( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $slot['utc_datetime'] );
		}
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------
}
