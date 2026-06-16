<?php
/**
 * Unit tests for CohortEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Analytics\CohortEngine;

class CohortEngineTest extends TestCase {

	private CohortEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->engine        = new CohortEngine();
	}

	// -----------------------------------------------------------------------
	// record_event
	// -----------------------------------------------------------------------

	public function test_record_event_stores_event_in_raw_data(): void {
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 1 );

		$raw = get_option( CohortEngine::OPTION_RAW, [] );
		$this->assertCount( 1, $raw );
		$this->assertSame( CohortEngine::STAGE_VISIT, $raw[0]['stage'] );
		$this->assertSame( 1, $raw[0]['post_id'] );
	}

	public function test_record_event_defaults_source_to_direct(): void {
		$this->engine->record_event( CohortEngine::STAGE_LEAD, 5 );

		$raw = get_option( CohortEngine::OPTION_RAW, [] );
		$this->assertSame( 'direct', $raw[0]['source'] );
	}

	public function test_record_event_stores_custom_source(): void {
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 10, 'organic' );

		$raw = get_option( CohortEngine::OPTION_RAW, [] );
		$this->assertSame( 'organic', $raw[0]['source'] );
	}

	public function test_record_event_stores_timestamp(): void {
		$before = time();
		$this->engine->record_event( CohortEngine::STAGE_CONVERSION, 1 );
		$after = time();

		$raw = get_option( CohortEngine::OPTION_RAW, [] );
		$this->assertGreaterThanOrEqual( $before, $raw[0]['timestamp'] );
		$this->assertLessThanOrEqual( $after, $raw[0]['timestamp'] );
	}

	public function test_record_event_stores_meta(): void {
		$meta = [ 'device' => 'mobile', 'country' => 'PL' ];
		$this->engine->record_event( CohortEngine::STAGE_REGISTER, 2, 'direct', $meta );

		$raw = get_option( CohortEngine::OPTION_RAW, [] );
		$this->assertSame( $meta, $raw[0]['meta'] );
	}

	public function test_record_event_appends_multiple_events(): void {
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 1 );
		$this->engine->record_event( CohortEngine::STAGE_REGISTER, 1 );
		$this->engine->record_event( CohortEngine::STAGE_LEAD, 1 );

		$raw = get_option( CohortEngine::OPTION_RAW, [] );
		$this->assertCount( 3, $raw );
	}

	// -----------------------------------------------------------------------
	// compute_snapshot — empty data
	// -----------------------------------------------------------------------

	public function test_compute_snapshot_returns_zeros_for_empty_data(): void {
		$snapshot = $this->engine->compute_snapshot();

		$this->assertSame( 0, $snapshot['total_events'] );
		$this->assertSame( 0, $snapshot['by_stage'][ CohortEngine::STAGE_VISIT ] );
		$this->assertSame( 0, $snapshot['by_stage'][ CohortEngine::STAGE_CONVERSION ] );
	}

	public function test_compute_snapshot_has_expected_keys(): void {
		$snapshot = $this->engine->compute_snapshot();

		$this->assertArrayHasKey( 'generated_at', $snapshot );
		$this->assertArrayHasKey( 'total_events', $snapshot );
		$this->assertArrayHasKey( 'by_stage', $snapshot );
		$this->assertArrayHasKey( 'by_source', $snapshot );
		$this->assertArrayHasKey( 'funnel_rates_pct', $snapshot );
	}

	// -----------------------------------------------------------------------
	// compute_snapshot — with data
	// -----------------------------------------------------------------------

	public function test_compute_snapshot_counts_events_by_stage(): void {
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 1 );
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 2 );
		$this->engine->record_event( CohortEngine::STAGE_REGISTER, 1 );

		$snapshot = $this->engine->compute_snapshot();

		$this->assertSame( 2, $snapshot['by_stage'][ CohortEngine::STAGE_VISIT ] );
		$this->assertSame( 1, $snapshot['by_stage'][ CohortEngine::STAGE_REGISTER ] );
		$this->assertSame( 0, $snapshot['by_stage'][ CohortEngine::STAGE_LEAD ] );
	}

	public function test_compute_snapshot_counts_by_source(): void {
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 1, 'organic' );
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 2, 'organic' );
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 3, 'direct' );

		$snapshot = $this->engine->compute_snapshot();

		$this->assertSame( 2, $snapshot['by_source']['organic'][ CohortEngine::STAGE_VISIT ] );
		$this->assertSame( 1, $snapshot['by_source']['direct'][ CohortEngine::STAGE_VISIT ] );
	}

	public function test_compute_snapshot_total_events(): void {
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 1 );
		$this->engine->record_event( CohortEngine::STAGE_REGISTER, 1 );
		$this->engine->record_event( CohortEngine::STAGE_LEAD, 1 );
		$this->engine->record_event( CohortEngine::STAGE_CONVERSION, 1 );

		$snapshot = $this->engine->compute_snapshot();

		$this->assertSame( 4, $snapshot['total_events'] );
	}

	// -----------------------------------------------------------------------
	// compute_snapshot — funnel conversion rates
	// -----------------------------------------------------------------------

	public function test_compute_snapshot_calculates_funnel_rates(): void {
		// 100 visits → 50 register → 25 lead → 10 conversion
		for ( $i = 0; $i < 100; $i++ ) {
			$this->engine->record_event( CohortEngine::STAGE_VISIT, $i );
		}
		for ( $i = 0; $i < 50; $i++ ) {
			$this->engine->record_event( CohortEngine::STAGE_REGISTER, $i );
		}
		for ( $i = 0; $i < 25; $i++ ) {
			$this->engine->record_event( CohortEngine::STAGE_LEAD, $i );
		}
		for ( $i = 0; $i < 10; $i++ ) {
			$this->engine->record_event( CohortEngine::STAGE_CONVERSION, $i );
		}

		$snapshot = $this->engine->compute_snapshot();

		$this->assertSame( 50.0, $snapshot['funnel_rates_pct']['visit_to_register'] );
		$this->assertSame( 50.0, $snapshot['funnel_rates_pct']['register_to_lead'] );
		$this->assertSame( 40.0, $snapshot['funnel_rates_pct']['lead_to_conversion'] );
	}

	public function test_compute_snapshot_funnel_rate_is_zero_when_no_visit(): void {
		$this->engine->record_event( CohortEngine::STAGE_CONVERSION, 1 );

		$snapshot = $this->engine->compute_snapshot();

		$this->assertSame( 0.0, $snapshot['funnel_rates_pct']['visit_to_register'] );
	}

	// -----------------------------------------------------------------------
	// refresh_snapshot
	// -----------------------------------------------------------------------

	public function test_refresh_snapshot_persists_snapshot_to_option(): void {
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 1 );
		$this->engine->refresh_snapshot();

		$stored = get_option( CohortEngine::OPTION_SNAPSHOT, null );
		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( 'by_stage', $stored );
	}

	public function test_refresh_snapshot_matches_compute_snapshot(): void {
		$this->engine->record_event( CohortEngine::STAGE_VISIT, 1 );
		$this->engine->record_event( CohortEngine::STAGE_REGISTER, 1 );

		$computed  = $this->engine->compute_snapshot();
		$this->engine->refresh_snapshot();
		$persisted = get_option( CohortEngine::OPTION_SNAPSHOT, [] );

		$this->assertSame( $computed['total_events'], $persisted['total_events'] );
		$this->assertSame( $computed['by_stage'], $persisted['by_stage'] );
	}

	// -----------------------------------------------------------------------
	// Stage constants
	// -----------------------------------------------------------------------

	public function test_stage_constants_are_distinct_strings(): void {
		$stages = [
			CohortEngine::STAGE_VISIT,
			CohortEngine::STAGE_REGISTER,
			CohortEngine::STAGE_LEAD,
			CohortEngine::STAGE_CONVERSION,
		];

		$this->assertCount( 4, array_unique( $stages ) );
	}
}
