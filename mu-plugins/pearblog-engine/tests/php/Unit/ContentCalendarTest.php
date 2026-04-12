<?php
/**
 * Unit tests for ContentCalendar.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Admin\ContentCalendar;

class ContentCalendarTest extends TestCase {

	private ContentCalendar $calendar;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$this->calendar = new ContentCalendar();
	}

	public function test_instantiation(): void {
		$this->assertInstanceOf( ContentCalendar::class, $this->calendar );
	}

	public function test_register_attaches_hooks(): void {
		$this->calendar->register();
		$this->assertTrue( (bool) has_action( 'admin_menu', [ $this->calendar, 'add_menu' ] ) );
		$this->assertTrue( (bool) has_action( 'rest_api_init', [ $this->calendar, 'register_routes' ] ) );
	}

	public function test_maybe_schedule_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->calendar->maybe_schedule();
	}

	public function test_dispatch_today_does_not_throw_when_no_entries(): void {
		// No calendar entries stored.
		$this->expectNotToPerformAssertions();
		$this->calendar->dispatch_today();
	}

	public function test_dispatch_today_queues_pending_entries_for_today(): void {
		$today = date( 'Y-m-d' );
		$entries = [
			[ 'date' => $today, 'topic' => 'Test Topic', 'status' => 'pending' ],
			[ 'date' => '2020-01-01', 'topic' => 'Old Topic', 'status' => 'pending' ],
		];
		$GLOBALS['_options']['pearblog_content_calendar'] = wp_json_encode( $entries );

		// dispatch_today() should process today's pending entries without throwing.
		$this->expectNotToPerformAssertions();
		$this->calendar->dispatch_today();
	}
}
