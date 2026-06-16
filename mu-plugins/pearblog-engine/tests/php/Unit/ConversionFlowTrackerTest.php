<?php
/**
 * Unit tests for ConversionFlowTracker.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Analytics\ConversionFlowTracker;

class ConversionFlowTrackerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// Reset global state.
		$GLOBALS['_options']          = [];
		$GLOBALS['_is_singular']      = false;
		$GLOBALS['_current_post_id']  = false;
		$GLOBALS['_post_permalink']   = 'https://example.com/post/';
		$GLOBALS['_post_fields']      = [];
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_db_results']       = [];
		$GLOBALS['_db_affected_rows'] = 1;
		$_COOKIE                      = [];
		$_SERVER['HTTP_HOST']         = 'example.com';
		$_SERVER['REQUEST_URI']       = '/test/';
		$_SERVER['REMOTE_ADDR']       = '127.0.0.1';
		unset( $_SERVER['HTTP_USER_AGENT'] );
		unset( $_SERVER['HTTP_REFERER'] );
		$_GET = [];
	}

	// -----------------------------------------------------------------------
	// get_session_funnel
	// -----------------------------------------------------------------------

	public function test_get_session_funnel_returns_empty_for_no_events(): void {
		$GLOBALS['_db_results'] = [];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session_abc' );
		$this->assertSame( [], $funnel );
	}

	public function test_get_session_funnel_marks_converted_on_form_submit(): void {
		$GLOBALS['_db_results'] = [
			[ 'event_type' => 'page_view',   'event_data' => null, 'service' => 'roof', 'created_at' => '2026-01-01 10:00:00' ],
			[ 'event_type' => 'form_view',   'event_data' => null, 'service' => 'roof', 'created_at' => '2026-01-01 10:01:00' ],
			[ 'event_type' => 'form_submit', 'event_data' => null, 'service' => 'roof', 'created_at' => '2026-01-01 10:02:00' ],
		];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session_def' );
		$this->assertTrue( $funnel['converted'] );
	}

	public function test_get_session_funnel_not_converted_without_form_submit(): void {
		$GLOBALS['_db_results'] = [
			[ 'event_type' => 'page_view', 'event_data' => null, 'service' => 'roof', 'created_at' => '2026-01-01 10:00:00' ],
			[ 'event_type' => 'form_view', 'event_data' => null, 'service' => 'roof', 'created_at' => '2026-01-01 10:01:00' ],
		];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session_ghi' );
		$this->assertFalse( $funnel['converted'] );
	}

	public function test_get_session_funnel_records_timestamps(): void {
		$GLOBALS['_db_results'] = [
			[ 'event_type' => 'page_view',      'event_data' => null, 'service' => 'roof', 'created_at' => '2026-01-01 10:00:00' ],
			[ 'event_type' => 'calculator_use', 'event_data' => null, 'service' => 'roof', 'created_at' => '2026-01-01 10:01:00' ],
		];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session_jkl' );
		$this->assertSame( '2026-01-01 10:00:00', $funnel['page_view'] );
		$this->assertSame( '2026-01-01 10:01:00', $funnel['calculator_use'] );
		$this->assertNull( $funnel['form_view'] );
	}

	public function test_get_session_funnel_only_records_first_occurrence(): void {
		$GLOBALS['_db_results'] = [
			[ 'event_type' => 'page_view', 'event_data' => null, 'service' => 'roof', 'created_at' => '2026-01-01 10:00:00' ],
			[ 'event_type' => 'page_view', 'event_data' => null, 'service' => 'roof', 'created_at' => '2026-01-01 10:05:00' ],
		];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session_mno' );
		// First timestamp wins.
		$this->assertSame( '2026-01-01 10:00:00', $funnel['page_view'] );
	}

	// -----------------------------------------------------------------------
	// get_conversion_metrics
	// -----------------------------------------------------------------------

	public function test_get_conversion_metrics_returns_zeros_for_no_data(): void {
		$GLOBALS['_db_results'] = [ null ]; // get_row returns null-ish

		$metrics = ConversionFlowTracker::get_conversion_metrics( 'roof-repair', 30 );
		$this->assertSame( 0, $metrics['total_views'] );
		$this->assertSame( 0.0, $metrics['conversion_rate'] );
	}

	public function test_get_conversion_metrics_calculates_rate(): void {
		$GLOBALS['_db_results'] = [
			[
				'total_views'     => '100',
				'calculator_uses' => '40',
				'form_views'      => '20',
				'form_submits'    => '10',
			],
		];

		$metrics = ConversionFlowTracker::get_conversion_metrics( 'roof-repair', 30 );
		$this->assertSame( 100, $metrics['total_views'] );
		$this->assertSame( 40, $metrics['calculator_uses'] );
		$this->assertSame( 20, $metrics['form_views'] );
		$this->assertSame( 10, $metrics['form_submits'] );
		$this->assertSame( 10.0, $metrics['conversion_rate'] );
	}

	public function test_get_conversion_metrics_rounds_rate(): void {
		$GLOBALS['_db_results'] = [
			[
				'total_views'     => '3',
				'calculator_uses' => '1',
				'form_views'      => '1',
				'form_submits'    => '1',
			],
		];

		$metrics = ConversionFlowTracker::get_conversion_metrics( 'service', 7 );
		$this->assertSame( round( 100 / 3, 2 ), $metrics['conversion_rate'] );
	}

	// -----------------------------------------------------------------------
	// get_funnel_dropoff
	// -----------------------------------------------------------------------

	public function test_get_funnel_dropoff_returns_empty_for_zero_views(): void {
		$GLOBALS['_db_results'] = [ null ];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'service', 30 );
		$this->assertSame( [], $dropoff );
	}

	public function test_get_funnel_dropoff_has_three_stages(): void {
		$GLOBALS['_db_results'] = [
			[
				'total_views'     => '100',
				'calculator_uses' => '50',
				'form_views'      => '30',
				'form_submits'    => '10',
			],
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'service', 30 );
		$this->assertArrayHasKey( 'page_to_calculator', $dropoff );
		$this->assertArrayHasKey( 'calculator_to_form', $dropoff );
		$this->assertArrayHasKey( 'form_to_submit', $dropoff );
	}

	public function test_get_funnel_dropoff_calculates_rates(): void {
		$GLOBALS['_db_results'] = [
			[
				'total_views'     => '100',
				'calculator_uses' => '50',
				'form_views'      => '25',
				'form_submits'    => '10',
			],
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'service', 30 );
		$this->assertSame( 50.0, $dropoff['page_to_calculator']['rate'] );
		$this->assertSame( 50.0, $dropoff['page_to_calculator']['dropoff'] );
		$this->assertSame( 50.0, $dropoff['calculator_to_form']['rate'] );
		$this->assertSame( 40.0, $dropoff['form_to_submit']['rate'] );
	}

	public function test_get_funnel_dropoff_handles_zero_calculator_uses(): void {
		$GLOBALS['_db_results'] = [
			[
				'total_views'     => '10',
				'calculator_uses' => '0',
				'form_views'      => '0',
				'form_submits'    => '0',
			],
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'service', 30 );
		$this->assertEquals( 0, $dropoff['calculator_to_form']['rate'] );
		$this->assertEquals( 0, $dropoff['calculator_to_form']['dropoff'] );
	}

	public function test_get_funnel_dropoff_handles_zero_form_views(): void {
		$GLOBALS['_db_results'] = [
			[
				'total_views'     => '50',
				'calculator_uses' => '20',
				'form_views'      => '0',
				'form_submits'    => '0',
			],
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'service', 30 );
		$this->assertEquals( 0, $dropoff['form_to_submit']['rate'] );
		$this->assertEquals( 0, $dropoff['form_to_submit']['dropoff'] );
	}
}
