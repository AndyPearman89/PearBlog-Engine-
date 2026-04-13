<?php
/**
 * Unit tests for PublishScheduler.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Scheduler\PublishScheduler;

/**
 * GA4Client stub — unconfigured by default.
 */
class SchedulerStubGA4Client {
	private bool $configured;
	private array $rows;

	public function __construct( bool $configured = false, array $rows = [] ) {
		$this->configured = $configured;
		$this->rows       = $rows;
	}

	public function is_configured(): bool { return $this->configured; }

	public function run_report( array $dims, array $mets, string $start, string $end ): array {
		return [ 'rows' => $this->rows ];
	}

	public function extract_rows( array $report ): array {
		return $this->rows;
	}
}

class PublishSchedulerTest extends TestCase {

	private function make_scheduler( bool $ga4_configured = false, array $ga4_rows = [] ): PublishScheduler {
		$ga4 = new SchedulerStubGA4Client( $ga4_configured, $ga4_rows );
		$s   = new PublishScheduler( null );
		// Inject stub via reflection.
		$ref = new \ReflectionClass( $s );
		$p   = $ref->getProperty( 'ga4' );
		$p->setAccessible( true );
		$p->setValue( $s, $ga4 );
		return $s;
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
	}

	// -----------------------------------------------------------------------
	// is_enabled / options
	// -----------------------------------------------------------------------

	public function test_disabled_by_default(): void {
		$s = $this->make_scheduler();
		$this->assertFalse( $s->is_enabled() );
	}

	public function test_enabled_when_option_set(): void {
		update_option( PublishScheduler::OPTION_ENABLED, true );
		$s = $this->make_scheduler();
		$this->assertTrue( $s->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// normalise
	// -----------------------------------------------------------------------

	public function test_normalise_all_zeros_returns_zeros(): void {
		$s      = $this->make_scheduler();
		$result = $s->normalise( [ 0.0, 0.0, 0.0 ] );
		$this->assertSame( [ 0.0, 0.0, 0.0 ], $result );
	}

	public function test_normalise_max_becomes_100(): void {
		$s      = $this->make_scheduler();
		$result = $s->normalise( [ 10.0, 20.0, 5.0 ] );
		$this->assertSame( 100.0, $result[1] );
	}

	public function test_normalise_proportional_scaling(): void {
		$s      = $this->make_scheduler();
		$result = $s->normalise( [ 0.0, 50.0, 100.0 ] );
		$this->assertSame( 0.0, $result[0] );
		$this->assertSame( 50.0, $result[1] );
		$this->assertSame( 100.0, $result[2] );
	}

	// -----------------------------------------------------------------------
	// aggregate_engagement
	// -----------------------------------------------------------------------

	public function test_aggregate_empty_report_returns_all_zeros(): void {
		$s                     = $this->make_scheduler();
		[ $hours, $dow ]       = $s->aggregate_engagement( [] );
		$this->assertCount( 24, $hours );
		$this->assertCount( 7, $dow );
		$this->assertSame( 0.0, array_sum( $hours ) );
		$this->assertSame( 0.0, array_sum( $dow ) );
	}

	public function test_aggregate_accumulates_sessions_by_hour(): void {
		$s = $this->make_scheduler(
			true,
			[
				// [ hour, dow, sessions, engagedSessions ]
				[ '10', '2', '100', '60' ],
				[ '10', '3', '50',  '30' ],
				[ '14', '2', '200', '150' ],
			]
		);
		[ $hours ] = $s->aggregate_engagement( [] );
		// Hour 10 should accumulate 60 + 30 = 90 engaged sessions.
		$this->assertSame( 90.0, $hours[10] );
		// Hour 14 should be 150.
		$this->assertSame( 150.0, $hours[14] );
	}

	public function test_aggregate_accumulates_sessions_by_dow(): void {
		$s = $this->make_scheduler(
			true,
			[
				[ '10', '2', '100', '60' ],
				[ '14', '2', '200', '150' ],
				[ '10', '3', '50',  '30' ],
			]
		);
		[ , $dow ] = $s->aggregate_engagement( [] );
		// Dow 2 (Tue): 60 + 150 = 210.
		$this->assertSame( 210.0, $dow[2] );
		// Dow 3 (Wed): 30.
		$this->assertSame( 30.0, $dow[3] );
	}

	public function test_aggregate_falls_back_to_sessions_when_no_engaged(): void {
		$s = $this->make_scheduler(
			true,
			[
				[ '8', '1', '75', '0' ],
			]
		);
		[ $hours ] = $s->aggregate_engagement( [] );
		$this->assertSame( 75.0, $hours[8] );
	}

	public function test_aggregate_ignores_invalid_hour(): void {
		$s = $this->make_scheduler(
			true,
			[
				[ '99', '1', '100', '80' ],
			]
		);
		[ $hours ] = $s->aggregate_engagement( [] );
		$this->assertSame( 0.0, array_sum( $hours ) );
	}

	// -----------------------------------------------------------------------
	// find_optimal_slot
	// -----------------------------------------------------------------------

	public function test_find_optimal_slot_returns_highest_combined_score(): void {
		$s = $this->make_scheduler();

		$hour_scores = array_fill( 0, 24, 0.0 );
		$dow_scores  = array_fill( 0, 7,  0.0 );

		$hour_scores[10] = 100.0; // Best hour.
		$dow_scores[2]   = 100.0; // Best day (Tuesday).

		[ $hour, $dow ] = $s->find_optimal_slot( $hour_scores, $dow_scores );
		$this->assertSame( 10, $hour );
		$this->assertSame( 2, $dow );
	}

	public function test_find_optimal_slot_falls_back_when_all_zero(): void {
		update_option( PublishScheduler::OPTION_FALLBACK_HOUR, 9 );
		update_option( PublishScheduler::OPTION_FALLBACK_DOW, 1 );
		$s = $this->make_scheduler();

		$hour_scores = array_fill( 0, 24, 0.0 );
		$dow_scores  = array_fill( 0, 7,  0.0 );

		[ $hour, $dow ] = $s->find_optimal_slot( $hour_scores, $dow_scores );
		$this->assertSame( 9, $hour );
		$this->assertSame( 1, $dow );
	}

	// -----------------------------------------------------------------------
	// compute_analysis
	// -----------------------------------------------------------------------

	public function test_compute_analysis_returns_expected_keys(): void {
		$s        = $this->make_scheduler();
		$analysis = $s->compute_analysis();
		$this->assertArrayHasKey( 'hour_scores', $analysis );
		$this->assertArrayHasKey( 'dow_scores', $analysis );
		$this->assertArrayHasKey( 'optimal_hour', $analysis );
		$this->assertArrayHasKey( 'optimal_dow', $analysis );
	}

	public function test_compute_analysis_hour_scores_has_24_items(): void {
		$s        = $this->make_scheduler();
		$analysis = $s->compute_analysis();
		$this->assertCount( 24, $analysis['hour_scores'] );
	}

	public function test_compute_analysis_dow_scores_has_7_items(): void {
		$s        = $this->make_scheduler();
		$analysis = $s->compute_analysis();
		$this->assertCount( 7, $analysis['dow_scores'] );
	}

	public function test_compute_analysis_uses_fallback_when_ga4_not_configured(): void {
		update_option( PublishScheduler::OPTION_FALLBACK_HOUR, 14 );
		update_option( PublishScheduler::OPTION_FALLBACK_DOW,  4 );
		$s        = $this->make_scheduler( false );
		$analysis = $s->compute_analysis();
		$this->assertSame( 14, $analysis['optimal_hour'] );
		$this->assertSame( 4,  $analysis['optimal_dow'] );
	}

	public function test_compute_analysis_with_ga4_data(): void {
		$rows = [
			[ '10', '2', '100', '80' ], // Tue 10am: best slot
			[ '14', '3', '50',  '30' ],
		];
		$s        = $this->make_scheduler( true, $rows );
		$analysis = $s->compute_analysis();
		$this->assertSame( 10, $analysis['optimal_hour'] );
		$this->assertSame( 2,  $analysis['optimal_dow'] );
	}

	// -----------------------------------------------------------------------
	// analyse (persistence)
	// -----------------------------------------------------------------------

	public function test_analyse_persists_to_option(): void {
		$s = $this->make_scheduler();
		$s->analyse();
		$raw = get_option( PublishScheduler::OPTION_ANALYSIS, '' );
		$this->assertNotEmpty( $raw );
		$decoded = json_decode( $raw, true );
		$this->assertArrayHasKey( 'optimal_hour', $decoded );
	}

	public function test_get_analysis_returns_cached(): void {
		$s = $this->make_scheduler();
		$s->analyse();
		// Second call should use cache.
		$analysis = $s->get_analysis();
		$this->assertArrayHasKey( 'optimal_hour', $analysis );
	}

	// -----------------------------------------------------------------------
	// next_occurrence
	// -----------------------------------------------------------------------

	public function test_next_occurrence_same_day_future_hour(): void {
		// Wednesday 9am, target = Wednesday 14:00 → same day, 14:00.
		$tz  = new \DateTimeZone( 'UTC' );
		$now = new \DateTimeImmutable( '2026-04-15 09:00:00', $tz ); // Wednesday (dow=3)
		$s   = $this->make_scheduler();

		$next = $s->next_occurrence( $now, 3, 14 );
		$this->assertSame( '2026-04-15 14:00:00', $next->format( 'Y-m-d H:i:s' ) );
	}

	public function test_next_occurrence_same_day_past_hour_advances_week(): void {
		// Wednesday 15:00, target = Wednesday 10:00 → next Wednesday.
		$tz  = new \DateTimeZone( 'UTC' );
		$now = new \DateTimeImmutable( '2026-04-15 15:00:00', $tz ); // Wednesday
		$s   = $this->make_scheduler();

		$next = $s->next_occurrence( $now, 3, 10 );
		$this->assertSame( '2026-04-22 10:00:00', $next->format( 'Y-m-d H:i:s' ) );
	}

	public function test_next_occurrence_different_day_forward(): void {
		// Wednesday 09:00, target = Friday (dow=5) 10:00 → this coming Friday.
		$tz  = new \DateTimeZone( 'UTC' );
		$now = new \DateTimeImmutable( '2026-04-15 09:00:00', $tz ); // Wednesday
		$s   = $this->make_scheduler();

		$next = $s->next_occurrence( $now, 5, 10 );
		$this->assertSame( '2026-04-17 10:00:00', $next->format( 'Y-m-d H:i:s' ) );
	}

	public function test_next_occurrence_different_day_backward_wraps(): void {
		// Wednesday 09:00, target = Monday (dow=1) 10:00 → next Monday.
		$tz  = new \DateTimeZone( 'UTC' );
		$now = new \DateTimeImmutable( '2026-04-15 09:00:00', $tz ); // Wednesday
		$s   = $this->make_scheduler();

		$next = $s->next_occurrence( $now, 1, 10 );
		$this->assertSame( '2026-04-20 10:00:00', $next->format( 'Y-m-d H:i:s' ) );
	}

	// -----------------------------------------------------------------------
	// get_optimal_slot / get_optimal_publish_time
	// -----------------------------------------------------------------------

	public function test_get_optimal_slot_returns_two_ints(): void {
		$s = $this->make_scheduler();
		[ $hour, $dow ] = $s->get_optimal_slot();
		$this->assertIsInt( $hour );
		$this->assertIsInt( $dow );
		$this->assertGreaterThanOrEqual( 0, $hour );
		$this->assertLessThan( 24, $hour );
		$this->assertGreaterThanOrEqual( 0, $dow );
		$this->assertLessThan( 7, $dow );
	}

	public function test_get_optimal_publish_time_returns_future_datetime(): void {
		$s    = $this->make_scheduler();
		$time = $s->get_optimal_publish_time();
		$this->assertInstanceOf( \DateTimeImmutable::class, $time );
		$this->assertGreaterThan( time(), $time->getTimestamp() );
	}
}
