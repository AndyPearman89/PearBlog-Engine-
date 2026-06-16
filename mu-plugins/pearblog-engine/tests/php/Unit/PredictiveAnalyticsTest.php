<?php
/**
 * Unit tests for PredictiveAnalytics.
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
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$this->pa = new PredictiveAnalytics();
	}

	// -----------------------------------------------------------------------
	// forecast()
	// -----------------------------------------------------------------------

	public function test_forecast_empty_series_returns_zero(): void {
		$result = $this->pa->forecast( [] );

		$this->assertSame( 0, $result['predicted'] );
		$this->assertSame( 'unknown', $result['trend'] );
		$this->assertSame( 0.0, $result['confidence'] );
	}

	public function test_forecast_growing_series_returns_growing_trend(): void {
		$result = $this->pa->forecast( [ 100, 120, 140, 160 ] );

		$this->assertSame( 'growing', $result['trend'] );
		$this->assertGreaterThan( 0, $result['predicted'] );
	}

	public function test_forecast_declining_series_returns_declining_trend(): void {
		$result = $this->pa->forecast( [ 200, 150, 100, 50 ] );

		$this->assertSame( 'declining', $result['trend'] );
	}

	public function test_forecast_stable_series_returns_stable_trend(): void {
		$result = $this->pa->forecast( [ 100, 100, 100, 100 ] );

		$this->assertSame( 'stable', $result['trend'] );
		$this->assertSame( 100, $result['predicted'] );
	}

	public function test_forecast_single_point(): void {
		$result = $this->pa->forecast( [ 50 ] );

		$this->assertSame( 50, $result['predicted'] );
		$this->assertSame( 0.0, $result['confidence'] );
	}

	public function test_forecast_confidence_is_between_zero_and_one(): void {
		$result = $this->pa->forecast( [ 80, 90, 85, 92, 88 ] );

		$this->assertGreaterThanOrEqual( 0.0, $result['confidence'] );
		$this->assertLessThanOrEqual( 1.0, $result['confidence'] );
	}

	public function test_forecast_uses_exponential_smoothing(): void {
		// With alpha=0.4, two identical values → same prediction.
		$result = $this->pa->forecast( [ 100, 100 ] );
		$this->assertSame( 100, $result['predicted'] );
	}

	// -----------------------------------------------------------------------
	// forecast_revenue()
	// -----------------------------------------------------------------------

	public function test_forecast_revenue_default_rpm(): void {
		$revenue = $this->pa->forecast_revenue( 1000 );
		// 1000/1000 * 1.5 = 1.5
		$this->assertSame( 1.5, $revenue );
	}

	public function test_forecast_revenue_custom_rpm(): void {
		$revenue = $this->pa->forecast_revenue( 5000, 2.0 );
		// 5000/1000 * 2.0 = 10.0
		$this->assertSame( 10.0, $revenue );
	}

	public function test_forecast_revenue_zero_views(): void {
		$this->assertSame( 0.0, $this->pa->forecast_revenue( 0 ) );
	}

	// -----------------------------------------------------------------------
	// Storage
	// -----------------------------------------------------------------------

	public function test_get_forecasts_returns_empty_array_initially(): void {
		$this->assertSame( [], $this->pa->get_forecasts() );
	}

	public function test_get_anomalies_returns_empty_array_initially(): void {
		$this->assertSame( [], $this->pa->get_anomalies() );
	}

	public function test_forecasts_are_persisted_after_update(): void {
		$data = [ 42 => [ 'predicted' => 100, 'trend' => 'growing', 'confidence' => 0.8 ] ];
		update_option( PredictiveAnalytics::OPTION_FORECASTS, $data );

		$this->assertSame( $data, $this->pa->get_forecasts() );
	}

	public function test_anomalies_are_persisted_after_update(): void {
		$data = [ [ 'post_id' => 7, 'drop_pct' => 35.0, 'detected_at' => '2026-06-14T00:00:00Z' ] ];
		update_option( PredictiveAnalytics::OPTION_ANOMALIES, $data );

		$this->assertSame( $data, $this->pa->get_anomalies() );
	}

	// -----------------------------------------------------------------------
	// Anomaly detection logic
	// -----------------------------------------------------------------------

	public function test_large_drop_would_be_detected(): void {
		$history = [ 1000, 500 ]; // 50% drop
		$prev    = $history[ count( $history ) - 2 ];
		$last    = end( $history );
		$drop    = ( $prev - $last ) / $prev;

		$this->assertGreaterThanOrEqual( PredictiveAnalytics::ANOMALY_DROP_THRESHOLD, $drop );
	}

	public function test_small_drop_would_not_be_detected(): void {
		$history = [ 1000, 950 ]; // 5% drop
		$prev    = $history[0];
		$last    = $history[1];
		$drop    = ( $prev - $last ) / $prev;

		$this->assertLessThan( PredictiveAnalytics::ANOMALY_DROP_THRESHOLD, $drop );
	}
}
