<?php
/**
 * Unit tests for PredictiveAnalytics (V9.0 F2).
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Analytics\PredictiveAnalytics;

class PredictiveAnalyticsTest extends TestCase {

	private PredictiveAnalytics $pa;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->pa            = new PredictiveAnalytics();
	}

	// -----------------------------------------------------------------------
	// build_forecasts
	// -----------------------------------------------------------------------

	public function test_build_forecasts_returns_empty_for_insufficient_history(): void {
		$result = $this->pa->build_forecasts( [] );
		$this->assertSame( [], $result );
	}

	public function test_build_forecasts_returns_empty_for_five_days(): void {
		$history = $this->make_history( 5, 1000 );
		$this->assertSame( [], $this->pa->build_forecasts( $history ) );
	}

	public function test_build_forecasts_returns_seven_days_for_valid_history(): void {
		$history = $this->make_history( 30, 500 );
		$result  = $this->pa->build_forecasts( $history );
		$this->assertCount( 7, $result );
	}

	public function test_build_forecasts_keys_are_iso_dates(): void {
		$history = $this->make_history( 30, 300 );
		$result  = $this->pa->build_forecasts( $history );
		foreach ( array_keys( $result ) as $date ) {
			$this->assertSame( 1, preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) );
		}
	}

	public function test_build_forecasts_values_are_non_negative(): void {
		$history = $this->make_history( 15, 200 );
		$result  = $this->pa->build_forecasts( $history );
		foreach ( $result as $value ) {
			$this->assertGreaterThanOrEqual( 0, $value );
		}
	}

	public function test_flat_line_produces_constant_forecast(): void {
		$history = $this->make_history( 14, 1000 );
		$result  = $this->pa->build_forecasts( $history );
		foreach ( $result as $value ) {
			$this->assertSame( 1000, $value );
		}
	}

	public function test_growing_trend_produces_ascending_forecast(): void {
		$history = [];
		$base    = strtotime( '2026-01-01' );
		for ( $i = 0; $i < 30; $i++ ) {
			$history[] = [
				'date'      => gmdate( 'Y-m-d', $base + $i * DAY_IN_SECONDS ),
				'pageviews' => 100 + $i * 10, // +10 per day.
			];
		}
		$result = $this->pa->build_forecasts( $history );
		$values = array_values( $result );
		$this->assertGreaterThan( $values[0], $values[6] );
	}

	// -----------------------------------------------------------------------
	// refresh
	// -----------------------------------------------------------------------

	public function test_refresh_stores_forecasts_in_option(): void {
		$history = $this->make_history( 20, 400 );
		$this->pa->refresh( $history );
		$stored = get_option( PredictiveAnalytics::OPTION_FORECASTS, [] );
		$this->assertNotEmpty( $stored );
	}

	public function test_refresh_stores_last_run_timestamp(): void {
		$history = $this->make_history( 10, 300 );
		$this->pa->refresh( $history );
		$ts = get_option( PredictiveAnalytics::OPTION_LAST_RUN );
		$this->assertNotFalse( $ts );
		$this->assertSame( 1, preg_match( '/^\d{4}-\d{2}-\d{2}T/', $ts ) );
	}

	public function test_refresh_returns_empty_array_if_no_data(): void {
		$result = $this->pa->refresh( [] );
		$this->assertSame( [], $result );
	}

	// -----------------------------------------------------------------------
	// detect_anomalies
	// -----------------------------------------------------------------------

	public function test_no_anomalies_when_actual_matches_forecast(): void {
		$forecast = [ '2026-06-01' => 1000, '2026-06-02' => 1000 ];
		$actual   = [ '2026-06-01' => 1000, '2026-06-02' => 1000 ];
		$this->assertSame( [], $this->pa->detect_anomalies( $actual, $forecast ) );
	}

	public function test_anomaly_detected_when_deviation_exceeds_threshold(): void {
		$forecast  = [ '2026-06-01' => 1000 ];
		$actual    = [ '2026-06-01' => 500 ]; // 50% drop.
		$anomalies = $this->pa->detect_anomalies( $actual, $forecast, 20.0 );
		$this->assertCount( 1, $anomalies );
		$this->assertSame( '2026-06-01', $anomalies[0]['date'] );
		$this->assertSame( 50.0, $anomalies[0]['deviation_pct'] );
	}

	public function test_no_anomaly_when_deviation_below_threshold(): void {
		$forecast  = [ '2026-06-01' => 1000 ];
		$actual    = [ '2026-06-01' => 950 ]; // 5% drop.
		$anomalies = $this->pa->detect_anomalies( $actual, $forecast, 20.0 );
		$this->assertSame( [], $anomalies );
	}

	public function test_anomaly_skips_missing_forecast_dates(): void {
		$forecast  = [];
		$actual    = [ '2026-06-01' => 100 ];
		$anomalies = $this->pa->detect_anomalies( $actual, $forecast );
		$this->assertSame( [], $anomalies );
	}

	// -----------------------------------------------------------------------
	// revenue_recommendations
	// -----------------------------------------------------------------------

	public function test_revenue_recommendations_empty_for_empty_input(): void {
		$this->assertSame( [], $this->pa->revenue_recommendations( [] ) );
	}

	public function test_revenue_recommendations_returns_sorted_by_roi(): void {
		$data = [
			[ 'post_id' => 1, 'pageviews' => 100, 'revenue' => 10.0 ],
			[ 'post_id' => 2, 'pageviews' => 100, 'revenue' => 1.0 ],
			[ 'post_id' => 3, 'pageviews' => 100, 'revenue' => 5.0 ],
		];
		$recs = $this->pa->revenue_recommendations( $data );
		$this->assertCount( 3, $recs );
		// Highest ROI first.
		$this->assertSame( 1, $recs[0]['post_id'] );
	}

	public function test_revenue_recommendation_high_roi_gets_increase(): void {
		$data = [
			[ 'post_id' => 1, 'pageviews' => 100, 'revenue' => 100.0 ],
			[ 'post_id' => 2, 'pageviews' => 100, 'revenue' => 1.0 ],
		];
		$recs = $this->pa->revenue_recommendations( $data );
		$high = array_filter( $recs, static fn( $r ) => $r['post_id'] === 1 );
		$high = array_values( $high );
		$this->assertSame( 'increase-promotion', $high[0]['recommendation'] );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * @return array<int,array{date:string,pageviews:int}>
	 */
	private function make_history( int $days, int $pageviews ): array {
		$history = [];
		$base    = strtotime( '2026-01-01' );
		for ( $i = 0; $i < $days; $i++ ) {
			$history[] = [
				'date'      => gmdate( 'Y-m-d', $base + $i * DAY_IN_SECONDS ),
				'pageviews' => $pageviews,
			];
		}
		return $history;
	}
}
