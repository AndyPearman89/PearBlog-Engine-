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

		// Override the global wpdb mock with one that supports get_row.
		$GLOBALS['wpdb'] = new class {
			public string $prefix     = 'wp_';
			public string $last_error = '';

			public function prepare( string $query, ...$args ): string {
				return $query; // Simplified – we only care about return values in tests.
			}

			/** @param  string $output  ARRAY_A or OBJECT. */
			public function get_row( string $query, string $output = 'OBJECT' ) {
				$row = $GLOBALS['_db_row'] ?? null;
				if ( null === $row ) {
					return null;
				}
				return $output === 'OBJECT' ? (object) $row : $row;
			}

			public function get_results( string $query, string $output = 'OBJECT' ): array {
				$rows = $GLOBALS['_db_results'] ?? [];
				if ( $output === 'ARRAY_A' ) {
					return array_map( fn( $r ) => (array) $r, $rows );
				}
				return array_map( fn( $r ) => is_array( $r ) ? (object) $r : $r, $rows );
			}

			public function get_var( string $query ) {
				return $GLOBALS['_db_var'] ?? null;
			}

			public function insert( string $table, array $data, array $format = [] ): int {
				$GLOBALS['_db_inserts'][] = array_merge( [ '_table' => $table ], $data );
				return 1;
			}
		};

		$GLOBALS['_db_row']     = null;
		$GLOBALS['_db_results'] = [];
		$GLOBALS['_db_var']     = null;
		$GLOBALS['_db_inserts'] = [];
		$GLOBALS['_options']    = [];
	}

	// -----------------------------------------------------------------------
	// get_conversion_metrics — zero views
	// -----------------------------------------------------------------------

	public function test_get_conversion_metrics_returns_zeros_when_no_data(): void {
		$GLOBALS['_db_row'] = null; // No rows returned.

		$metrics = ConversionFlowTracker::get_conversion_metrics( 'plumbing' );

		$this->assertSame( 0, $metrics['total_views'] );
		$this->assertSame( 0, $metrics['calculator_uses'] );
		$this->assertSame( 0, $metrics['form_views'] );
		$this->assertSame( 0, $metrics['form_submits'] );
		$this->assertSame( 0.0, $metrics['conversion_rate'] );
	}

	public function test_get_conversion_metrics_returns_expected_keys(): void {
		$metrics = ConversionFlowTracker::get_conversion_metrics( 'service' );

		$this->assertArrayHasKey( 'total_views', $metrics );
		$this->assertArrayHasKey( 'calculator_uses', $metrics );
		$this->assertArrayHasKey( 'form_views', $metrics );
		$this->assertArrayHasKey( 'form_submits', $metrics );
		$this->assertArrayHasKey( 'conversion_rate', $metrics );
	}

	// -----------------------------------------------------------------------
	// get_conversion_metrics — with data
	// -----------------------------------------------------------------------

	public function test_get_conversion_metrics_calculates_conversion_rate(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '100',
			'calculator_uses' => '60',
			'form_views'      => '40',
			'form_submits'    => '10',
		];

		$metrics = ConversionFlowTracker::get_conversion_metrics( 'plumbing' );

		$this->assertSame( 100, $metrics['total_views'] );
		$this->assertSame( 10, $metrics['form_submits'] );
		$this->assertEqualsWithDelta( 10.0, $metrics['conversion_rate'], 0.01 );
	}

	public function test_get_conversion_metrics_rounds_conversion_rate_to_two_decimals(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '3',
			'calculator_uses' => '2',
			'form_views'      => '1',
			'form_submits'    => '1',
		];

		$metrics = ConversionFlowTracker::get_conversion_metrics( 'test-service' );

		// 1/3 * 100 = 33.33...
		$this->assertEqualsWithDelta( 33.33, $metrics['conversion_rate'], 0.01 );
	}

	public function test_get_conversion_metrics_returns_zero_rate_when_total_views_zero(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '0',
			'calculator_uses' => '0',
			'form_views'      => '0',
			'form_submits'    => '0',
		];

		$metrics = ConversionFlowTracker::get_conversion_metrics( 'empty-service' );

		$this->assertSame( 0.0, $metrics['conversion_rate'] );
	}

	// -----------------------------------------------------------------------
	// get_funnel_dropoff — empty service
	// -----------------------------------------------------------------------

	public function test_get_funnel_dropoff_returns_empty_when_no_views(): void {
		$GLOBALS['_db_row'] = null;

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'no-traffic' );

		$this->assertSame( [], $dropoff );
	}

	// -----------------------------------------------------------------------
	// get_funnel_dropoff — with data
	// -----------------------------------------------------------------------

	public function test_get_funnel_dropoff_returns_three_stages(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '100',
			'calculator_uses' => '50',
			'form_views'      => '30',
			'form_submits'    => '10',
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'plumbing' );

		$this->assertArrayHasKey( 'page_to_calculator', $dropoff );
		$this->assertArrayHasKey( 'calculator_to_form', $dropoff );
		$this->assertArrayHasKey( 'form_to_submit', $dropoff );
	}

	public function test_get_funnel_dropoff_page_to_calculator_rate(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '100',
			'calculator_uses' => '50',
			'form_views'      => '30',
			'form_submits'    => '10',
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'plumbing' );

		$this->assertEqualsWithDelta( 50.0, $dropoff['page_to_calculator']['rate'], 0.01 );
		$this->assertSame( 50, $dropoff['page_to_calculator']['reached'] );
	}

	public function test_get_funnel_dropoff_page_to_calculator_dropoff_rate(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '100',
			'calculator_uses' => '50',
			'form_views'      => '30',
			'form_submits'    => '10',
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'plumbing' );

		$this->assertEqualsWithDelta( 50.0, $dropoff['page_to_calculator']['dropoff'], 0.01 );
	}

	public function test_get_funnel_dropoff_calculator_to_form_rate(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '100',
			'calculator_uses' => '50',
			'form_views'      => '30',
			'form_submits'    => '10',
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'plumbing' );

		$this->assertEqualsWithDelta( 60.0, $dropoff['calculator_to_form']['rate'], 0.01 );
	}

	public function test_get_funnel_dropoff_form_to_submit_rate(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '100',
			'calculator_uses' => '50',
			'form_views'      => '30',
			'form_submits'    => '10',
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'plumbing' );

		$this->assertEqualsWithDelta( 33.33, $dropoff['form_to_submit']['rate'], 0.01 );
	}

	public function test_get_funnel_dropoff_calculator_to_form_zero_when_no_calculator_uses(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '100',
			'calculator_uses' => '0',
			'form_views'      => '10',
			'form_submits'    => '5',
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'direct-form' );

		$this->assertEqualsWithDelta( 0.0, $dropoff['calculator_to_form']['rate'], 0.001 );
		$this->assertEqualsWithDelta( 0.0, $dropoff['calculator_to_form']['dropoff'], 0.001 );
	}

	public function test_get_funnel_dropoff_form_to_submit_zero_when_no_form_views(): void {
		$GLOBALS['_db_row'] = [
			'total_views'     => '100',
			'calculator_uses' => '60',
			'form_views'      => '0',
			'form_submits'    => '5',
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'no-form' );

		$this->assertEqualsWithDelta( 0.0, $dropoff['form_to_submit']['rate'], 0.001 );
	}

	// -----------------------------------------------------------------------
	// get_funnel_dropoff — full conversion (no dropoff)
	// -----------------------------------------------------------------------

	public function test_get_funnel_dropoff_full_conversion_has_zero_dropoff(): void {
		// All 100 visitors complete every stage.
		$GLOBALS['_db_row'] = [
			'total_views'     => '100',
			'calculator_uses' => '100',
			'form_views'      => '100',
			'form_submits'    => '100',
		];

		$dropoff = ConversionFlowTracker::get_funnel_dropoff( 'perfect' );

		$this->assertSame( 0.0, $dropoff['page_to_calculator']['dropoff'] );
		$this->assertSame( 0.0, $dropoff['calculator_to_form']['dropoff'] );
		$this->assertSame( 0.0, $dropoff['form_to_submit']['dropoff'] );
	}

	// -----------------------------------------------------------------------
	// get_session_funnel — empty session
	// -----------------------------------------------------------------------

	public function test_get_session_funnel_returns_empty_array_for_unknown_session(): void {
		$GLOBALS['_db_results'] = [];

		$funnel = ConversionFlowTracker::get_session_funnel( 'unknown-session-id' );

		$this->assertSame( [], $funnel );
	}

	// -----------------------------------------------------------------------
	// get_session_funnel — with events
	// -----------------------------------------------------------------------

	public function test_get_session_funnel_returns_expected_keys(): void {
		$GLOBALS['_db_results'] = [
			[ 'event_type' => 'page_view', 'event_data' => null, 'service' => 'plumbing', 'created_at' => '2026-06-01 10:00:00' ],
		];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session-abc' );

		$this->assertArrayHasKey( 'page_view', $funnel );
		$this->assertArrayHasKey( 'calculator_use', $funnel );
		$this->assertArrayHasKey( 'form_view', $funnel );
		$this->assertArrayHasKey( 'form_submit', $funnel );
		$this->assertArrayHasKey( 'converted', $funnel );
	}

	public function test_get_session_funnel_marks_page_view_timestamp(): void {
		$ts = '2026-06-01 10:00:00';
		$GLOBALS['_db_results'] = [
			[ 'event_type' => 'page_view', 'event_data' => null, 'service' => 'plumbing', 'created_at' => $ts ],
		];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session-xyz' );

		$this->assertSame( $ts, $funnel['page_view'] );
	}

	public function test_get_session_funnel_not_converted_without_form_submit(): void {
		$GLOBALS['_db_results'] = [
			[ 'event_type' => 'page_view', 'event_data' => null, 'service' => 'plumbing', 'created_at' => '2026-06-01 10:00:00' ],
			[ 'event_type' => 'calculator_use', 'event_data' => null, 'service' => 'plumbing', 'created_at' => '2026-06-01 10:01:00' ],
		];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session-no-convert' );

		$this->assertFalse( $funnel['converted'] );
	}

	public function test_get_session_funnel_converted_with_form_submit(): void {
		$GLOBALS['_db_results'] = [
			[ 'event_type' => 'page_view', 'event_data' => null, 'service' => 'plumbing', 'created_at' => '2026-06-01 10:00:00' ],
			[ 'event_type' => 'form_submit', 'event_data' => null, 'service' => 'plumbing', 'created_at' => '2026-06-01 10:03:00' ],
		];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session-converted' );

		$this->assertTrue( $funnel['converted'] );
	}

	public function test_get_session_funnel_records_only_first_occurrence_of_each_stage(): void {
		$ts1 = '2026-06-01 10:00:00';
		$ts2 = '2026-06-01 10:05:00';
		$GLOBALS['_db_results'] = [
			[ 'event_type' => 'page_view', 'event_data' => null, 'service' => 's', 'created_at' => $ts1 ],
			[ 'event_type' => 'page_view', 'event_data' => null, 'service' => 's', 'created_at' => $ts2 ],
		];

		$funnel = ConversionFlowTracker::get_session_funnel( 'session-multi' );

		// Only first page_view timestamp should be stored.
		$this->assertSame( $ts1, $funnel['page_view'] );
	}
}
