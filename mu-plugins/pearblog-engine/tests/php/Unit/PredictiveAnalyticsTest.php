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
		$GLOBALS['_post_meta']   = [];
		$GLOBALS['_options']     = [];
		$this->pa = new PredictiveAnalytics();
	}

	// -----------------------------------------------------------------------
	// forecast_performance
	// -----------------------------------------------------------------------

	public function test_forecast_returns_empty_for_insufficient_data(): void {
		$result = $this->pa->forecast_performance( 1, 30 );

		$this->assertSame( 1, $result['post_id'] );
		$this->assertSame( 0, $result['historical_days'] );
		$this->assertSame( 30, $result['forecast_days'] );
		$this->assertSame( 'unknown', $result['trend'] );
		$this->assertSame( 0.0, $result['confidence'] );
		$this->assertCount( 30, $result['projected_views'] );
	}

	public function test_forecast_detects_rising_trend(): void {
		// Seed 14 days of linearly increasing views.
		$views = [];
		for ( $d = 0; $d < 14; $d++ ) {
			$date          = gmdate( 'Y-m-d', strtotime( "-{$d} days" ) );
			$views[ $date ] = ( 14 - $d ) * 10; // oldest day = smallest
		}
		ksort( $views );
		$GLOBALS['_post_meta'][42]['_pearblog_daily_views'] = [ wp_json_encode( $views ) ];

		$result = $this->pa->forecast_performance( 42, 7 );

		$this->assertSame( 'rising', $result['trend'] );
		$this->assertGreaterThan( 0.0, $result['slope'] );
		$this->assertGreaterThan( 0.8, $result['confidence'] );
		$this->assertCount( 7, $result['projected_views'] );
	}

	public function test_forecast_detects_falling_trend(): void {
		$views = [];
		for ( $d = 0; $d < 14; $d++ ) {
			$date          = gmdate( 'Y-m-d', strtotime( "-{$d} days" ) );
			$views[ $date ] = $d * 10; // oldest day = largest
		}
		ksort( $views );
		$GLOBALS['_post_meta'][43]['_pearblog_daily_views'] = [ wp_json_encode( $views ) ];

		$result = $this->pa->forecast_performance( 43, 7 );

		$this->assertSame( 'falling', $result['trend'] );
		$this->assertLessThan( 0.0, $result['slope'] );
	}

	public function test_forecast_stable_trend(): void {
		$views = [];
		for ( $d = 0; $d < 14; $d++ ) {
			$date          = gmdate( 'Y-m-d', strtotime( "-{$d} days" ) );
			$views[ $date ] = 100; // constant
		}
		ksort( $views );
		$GLOBALS['_post_meta'][44]['_pearblog_daily_views'] = [ wp_json_encode( $views ) ];

		$result = $this->pa->forecast_performance( 44, 5 );

		$this->assertSame( 'stable', $result['trend'] );
		$this->assertSame( 1.0, $result['confidence'] );
	}

	public function test_forecast_projected_views_non_negative(): void {
		$views = [];
		for ( $d = 0; $d < 10; $d++ ) {
			$date          = gmdate( 'Y-m-d', strtotime( "-{$d} days" ) );
			$views[ $date ] = max( 0, 5 - $d );
		}
		ksort( $views );
		$GLOBALS['_post_meta'][45]['_pearblog_daily_views'] = [ wp_json_encode( $views ) ];

		$result = $this->pa->forecast_performance( 45, 14 );

		foreach ( $result['projected_views'] as $v ) {
			$this->assertGreaterThanOrEqual( 0, $v );
		}
	}

	// -----------------------------------------------------------------------
	// get_revenue_forecast
	// -----------------------------------------------------------------------

	public function test_revenue_forecast_returns_zero_with_no_data(): void {
		$result = $this->pa->get_revenue_forecast( 30 );

		$this->assertSame( 30, $result['forecast_days'] );
		$this->assertSame( 0.0, $result['total_projected'] );
		$this->assertSame( 'unknown', $result['trend'] );
	}

	public function test_revenue_forecast_rising(): void {
		$rev = [];
		for ( $d = 0; $d < 10; $d++ ) {
			$date        = gmdate( 'Y-m-d', strtotime( "-{$d} days" ) );
			$rev[ $date ] = ( 10 - $d ) * 5.0;
		}
		ksort( $rev );
		$GLOBALS['_options']['pearblog_site_daily_revenue'] = wp_json_encode( $rev );

		$result = $this->pa->get_revenue_forecast( 7 );

		$this->assertSame( 'rising', $result['trend'] );
		$this->assertGreaterThan( 0.0, $result['total_projected'] );
		$this->assertCount( 7, $result['projected_revenue'] );
	}

	// -----------------------------------------------------------------------
	// detect_trends
	// -----------------------------------------------------------------------

	public function test_detect_trends_empty_returns_unknown(): void {
		$result = $this->pa->detect_trends( [] );
		$this->assertSame( 'unknown', $result['trend'] );
	}

	public function test_detect_trends_single_value(): void {
		$result = $this->pa->detect_trends( [ 100.0 ] );
		$this->assertSame( 'unknown', $result['trend'] );
	}

	public function test_detect_trends_rising(): void {
		$result = $this->pa->detect_trends( [ 10, 20, 30, 40, 50, 60, 70, 80 ] );
		$this->assertStringContainsString( 'uptrend', $result['trend'] );
		$this->assertSame( 'up', $result['direction'] );
		$this->assertGreaterThan( 0.0, $result['magnitude'] );
	}

	public function test_detect_trends_falling(): void {
		$result = $this->pa->detect_trends( [ 100, 80, 60, 40, 20, 10, 5, 1 ] );
		$this->assertStringContainsString( 'downtrend', $result['trend'] );
		$this->assertSame( 'down', $result['direction'] );
	}

	public function test_detect_trends_pct_change(): void {
		$result = $this->pa->detect_trends( [ 100.0, 200.0 ] );
		$this->assertSame( 100.0, $result['pct_change'] );
	}

	// -----------------------------------------------------------------------
	// get_anomalies
	// -----------------------------------------------------------------------

	public function test_anomalies_returns_empty_with_insufficient_data(): void {
		$result = $this->pa->get_anomalies( 99 );
		$this->assertSame( [], $result['anomalies'] );
		$this->assertSame( 0, $result['total_days'] );
	}

	public function test_anomalies_detects_spike(): void {
		$views = [];
		for ( $d = 0; $d < 14; $d++ ) {
			$date          = gmdate( 'Y-m-d', strtotime( "-{$d} days" ) );
			$views[ $date ] = 10; // constant baseline
		}
		// Insert a huge spike in the middle.
		$spike_date            = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$views[ $spike_date ] = 10000;
		ksort( $views );
		$GLOBALS['_post_meta'][50]['_pearblog_daily_views'] = [ wp_json_encode( $views ) ];

		$result = $this->pa->get_anomalies( 50 );

		$this->assertNotEmpty( $result['anomalies'] );
		$this->assertGreaterThan( 2.0, $result['anomalies'][0]['z_score'] );
	}

	public function test_anomalies_uniform_data_has_no_anomalies(): void {
		$views = [];
		for ( $d = 0; $d < 14; $d++ ) {
			$date          = gmdate( 'Y-m-d', strtotime( "-{$d} days" ) );
			$views[ $date ] = 50;
		}
		ksort( $views );
		$GLOBALS['_post_meta'][51]['_pearblog_daily_views'] = [ wp_json_encode( $views ) ];

		$result = $this->pa->get_anomalies( 51 );

		$this->assertEmpty( $result['anomalies'] );
	}

	// -----------------------------------------------------------------------
	// recommend_optimizations
	// -----------------------------------------------------------------------

	public function test_recommend_returns_low_data_message(): void {
		$result = $this->pa->recommend_optimizations( 200 );

		$this->assertLessThan( 100, $result['score'] );
		$this->assertNotEmpty( $result['recommendations'] );
		$this->assertStringContainsString( 'Insufficient', $result['recommendations'][0] );
	}

	public function test_recommend_score_bounded(): void {
		$views = [];
		for ( $d = 0; $d < 20; $d++ ) {
			$date          = gmdate( 'Y-m-d', strtotime( "-{$d} days" ) );
			$views[ $date ] = 500; // high traffic, stable
		}
		ksort( $views );
		$GLOBALS['_post_meta'][201]['_pearblog_daily_views'] = [ wp_json_encode( $views ) ];

		$result = $this->pa->recommend_optimizations( 201 );

		$this->assertGreaterThanOrEqual( 0, $result['score'] );
		$this->assertLessThanOrEqual( 100, $result['score'] );
	}

	public function test_recommend_penalises_low_quality_score(): void {
		$views = [];
		for ( $d = 0; $d < 14; $d++ ) {
			$date          = gmdate( 'Y-m-d', strtotime( "-{$d} days" ) );
			$views[ $date ] = 100;
		}
		ksort( $views );
		$GLOBALS['_post_meta'][202]['_pearblog_daily_views']      = [ wp_json_encode( $views ) ];
		$GLOBALS['_post_meta'][202]['_pearblog_quality_score']    = [ '0.4' ];

		$result_low = $this->pa->recommend_optimizations( 202 );

		$GLOBALS['_post_meta'][202]['_pearblog_quality_score']    = [ '0.9' ];
		$result_high = $this->pa->recommend_optimizations( 202 );

		$this->assertLessThanOrEqual( $result_high['score'], $result_low['score'] );
	}

	// -----------------------------------------------------------------------
	// record_daily_views / record_daily_revenue
	// -----------------------------------------------------------------------

	public function test_record_daily_views_stores_value(): void {
		$this->pa->record_daily_views( 300, 42 );

		// update_post_meta stub wraps the value in an array; index [0] is the JSON string.
		$raw     = $GLOBALS['_post_meta'][300]['_pearblog_daily_views'][0];
		$decoded = json_decode( $raw, true );

		$this->assertIsArray( $decoded );
		$this->assertContains( 42, $decoded );
	}

	public function test_record_daily_revenue_stores_value(): void {
		$this->pa->record_daily_revenue( 99.99 );

		$raw     = $GLOBALS['_options']['pearblog_site_daily_revenue'];
		$decoded = json_decode( $raw, true );

		$this->assertIsArray( $decoded );
		$this->assertContains( 99.99, $decoded );
	}
}
