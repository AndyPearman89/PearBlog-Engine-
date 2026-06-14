<?php
/**
 * Predictive Analytics — V9.0 F2
 *
 * Content performance forecasting, anomaly detection, trend analysis,
 * and revenue optimisation using lightweight statistical methods that
 * run without an external ML service.
 *
 * @package PearBlogEngine\Analytics
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * PredictiveAnalytics
 *
 * All computation is pure-PHP with no external dependencies so the class
 * can run inside a standard WordPress environment.
 */
class PredictiveAnalytics {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** Meta key for daily view counts stored as JSON. */
	private const META_DAILY_VIEWS = '_pearblog_daily_views';

	/** Meta key for revenue contribution stored as JSON. */
	private const META_DAILY_REVENUE = '_pearblog_daily_revenue';

	/** Option key: site-level daily revenue JSON. */
	private const OPT_SITE_REVENUE = 'pearblog_site_daily_revenue';

	/** Option key: cached anomaly snapshot. */
	private const OPT_ANOMALY_CACHE = 'pearblog_anomaly_cache';

	/** Minimum data points required for a meaningful forecast. */
	private const MIN_DATA_POINTS = 7;

	// -----------------------------------------------------------------------
	// Public API — Forecasting
	// -----------------------------------------------------------------------

	/**
	 * Forecast future performance for a single post using linear regression
	 * over historical daily view counts stored in post meta.
	 *
	 * @param  int $post_id Post ID to forecast.
	 * @param  int $days    Number of future days to project.
	 * @return array{
	 *     post_id: int,
	 *     historical_days: int,
	 *     forecast_days: int,
	 *     projected_views: int[],
	 *     trend: string,
	 *     confidence: float,
	 *     slope: float,
	 *     intercept: float,
	 * }
	 */
	public function forecast_performance( int $post_id, int $days = 30 ): array {
		$history = $this->get_daily_views( $post_id );

		if ( count( $history ) < self::MIN_DATA_POINTS ) {
			return $this->empty_forecast( $post_id, $days );
		}

		[ $slope, $intercept ] = $this->linear_regression( array_values( $history ) );

		$n         = count( $history );
		$projected = [];
		for ( $i = 1; $i <= $days; $i++ ) {
			$value       = (int) round( max( 0, $slope * ( $n + $i ) + $intercept ) );
			$projected[] = $value;
		}

		$confidence = $this->compute_r_squared( array_values( $history ), $slope, $intercept );

		return [
			'post_id'         => $post_id,
			'historical_days' => $n,
			'forecast_days'   => $days,
			'projected_views' => $projected,
			'trend'           => $slope > 0.5 ? 'rising' : ( $slope < -0.5 ? 'falling' : 'stable' ),
			'confidence'      => round( $confidence, 4 ),
			'slope'           => round( $slope, 4 ),
			'intercept'       => round( $intercept, 4 ),
		];
	}

	/**
	 * Forecast site-level revenue for the next N days.
	 *
	 * @param  int $days Number of future days to project.
	 * @return array{
	 *     forecast_days: int,
	 *     projected_revenue: float[],
	 *     total_projected: float,
	 *     trend: string,
	 *     confidence: float,
	 * }
	 */
	public function get_revenue_forecast( int $days = 90 ): array {
		$history = $this->get_site_revenue();

		if ( count( $history ) < self::MIN_DATA_POINTS ) {
			return [
				'forecast_days'    => $days,
				'projected_revenue'=> array_fill( 0, $days, 0.0 ),
				'total_projected'  => 0.0,
				'trend'            => 'unknown',
				'confidence'       => 0.0,
			];
		}

		[ $slope, $intercept ] = $this->linear_regression( array_values( $history ) );

		$n         = count( $history );
		$projected = [];
		for ( $i = 1; $i <= $days; $i++ ) {
			$projected[] = round( (float) max( 0, $slope * ( $n + $i ) + $intercept ), 2 );
		}

		$total      = array_sum( $projected );
		$confidence = $this->compute_r_squared( array_values( $history ), $slope, $intercept );

		return [
			'forecast_days'    => $days,
			'projected_revenue'=> $projected,
			'total_projected'  => round( $total, 2 ),
			'trend'            => $slope > 0 ? 'rising' : ( $slope < 0 ? 'falling' : 'stable' ),
			'confidence'       => round( $confidence, 4 ),
		];
	}

	// -----------------------------------------------------------------------
	// Public API — Trend Detection
	// -----------------------------------------------------------------------

	/**
	 * Detect trends across a set of metric values.
	 *
	 * @param  float[] $metrics Ordered time-series data points.
	 * @return array{
	 *     trend: string,
	 *     direction: string,
	 *     magnitude: float,
	 *     slope: float,
	 *     pct_change: float,
	 * }
	 */
	public function detect_trends( array $metrics ): array {
		if ( count( $metrics ) < 2 ) {
			return [
				'trend'     => 'unknown',
				'direction' => 'flat',
				'magnitude' => 0.0,
				'slope'     => 0.0,
				'pct_change'=> 0.0,
			];
		}

		[ $slope ] = $this->linear_regression( array_values( $metrics ) );

		$first     = (float) reset( $metrics );
		$last      = (float) end( $metrics );
		$pct       = $first !== 0.0 ? ( ( $last - $first ) / abs( $first ) ) * 100 : 0.0;

		if ( abs( $slope ) < 0.5 ) {
			$trend = 'stable';
		} elseif ( $slope > 5 ) {
			$trend = 'strong_uptrend';
		} elseif ( $slope > 0 ) {
			$trend = 'uptrend';
		} elseif ( $slope < -5 ) {
			$trend = 'strong_downtrend';
		} else {
			$trend = 'downtrend';
		}

		return [
			'trend'     => $trend,
			'direction' => $slope > 0 ? 'up' : ( $slope < 0 ? 'down' : 'flat' ),
			'magnitude' => abs( round( $slope, 4 ) ),
			'slope'     => round( $slope, 4 ),
			'pct_change'=> round( $pct, 2 ),
		];
	}

	// -----------------------------------------------------------------------
	// Public API — Anomaly Detection
	// -----------------------------------------------------------------------

	/**
	 * Detect anomalies in a post's daily view counts using z-score method.
	 *
	 * @param  int    $post_id   Post ID to analyse.
	 * @param  string $metric    Metric name (informational, defaults to 'views').
	 * @param  float  $threshold Z-score threshold above which a value is anomalous.
	 * @return array{
	 *     post_id: int,
	 *     metric: string,
	 *     anomalies: array<int, array{day: int, value: float, z_score: float}>,
	 *     total_days: int,
	 *     mean: float,
	 *     std_dev: float,
	 * }
	 */
	public function get_anomalies( int $post_id, string $metric = 'views', float $threshold = 2.0 ): array {
		$data = array_values( $this->get_daily_views( $post_id ) );

		if ( count( $data ) < self::MIN_DATA_POINTS ) {
			return [
				'post_id'   => $post_id,
				'metric'    => $metric,
				'anomalies' => [],
				'total_days'=> 0,
				'mean'      => 0.0,
				'std_dev'   => 0.0,
			];
		}

		$mean    = array_sum( $data ) / count( $data );
		$std_dev = $this->std_dev( $data, $mean );
		$anomalies = [];

		foreach ( $data as $i => $value ) {
			if ( $std_dev > 0 ) {
				$z = abs( ( (float) $value - $mean ) / $std_dev );
				if ( $z >= $threshold ) {
					$anomalies[] = [
						'day'    => $i,
						'value'  => (float) $value,
						'z_score'=> round( $z, 4 ),
					];
				}
			}
		}

		return [
			'post_id'   => $post_id,
			'metric'    => $metric,
			'anomalies' => $anomalies,
			'total_days'=> count( $data ),
			'mean'      => round( $mean, 2 ),
			'std_dev'   => round( $std_dev, 2 ),
		];
	}

	// -----------------------------------------------------------------------
	// Public API — Recommendations
	// -----------------------------------------------------------------------

	/**
	 * Generate data-driven optimisation recommendations for a post.
	 *
	 * Recommendations are derived from statistical signals rather than an
	 * AI call so that they are available offline and at zero cost.
	 *
	 * @param  int $post_id Post to analyse.
	 * @return array{
	 *     post_id: int,
	 *     score: int,
	 *     recommendations: string[],
	 *     signals: array<string, mixed>,
	 * }
	 */
	public function recommend_optimizations( int $post_id ): array {
		$views   = array_values( $this->get_daily_views( $post_id ) );
		$score   = 100;
		$recs    = [];
		$signals = [];

		if ( count( $views ) < self::MIN_DATA_POINTS ) {
			$recs[]    = 'Insufficient traffic data — publish and allow at least 7 days of data to accumulate.';
			$signals['data_points'] = count( $views );
			return [
				'post_id'        => $post_id,
				'score'          => 50,
				'recommendations'=> $recs,
				'signals'        => $signals,
			];
		}

		$trend_data = $this->detect_trends( $views );
		$anomalies  = $this->get_anomalies( $post_id );
		$mean       = array_sum( $views ) / count( $views );

		$signals['trend']           = $trend_data['trend'];
		$signals['pct_change']      = $trend_data['pct_change'];
		$signals['mean_daily_views']= round( $mean, 1 );
		$signals['anomaly_count']   = count( $anomalies['anomalies'] );

		if ( in_array( $trend_data['trend'], [ 'downtrend', 'strong_downtrend' ], true ) ) {
			$score -= 20;
			$recs[] = 'Traffic is declining — consider refreshing the content, updating the title, or improving internal links.';
		}

		if ( $trend_data['trend'] === 'strong_uptrend' ) {
			$recs[] = 'Content is gaining momentum — consider promoting it on social media to amplify growth.';
		}

		if ( $mean < 10 ) {
			$score -= 15;
			$recs[] = 'Average daily views are very low — improve SEO meta, add internal links, or promote via email.';
		} elseif ( $mean > 500 ) {
			$recs[] = 'High-traffic post — ensure it is monetised and has strong CTAs.';
		}

		if ( count( $anomalies['anomalies'] ) > 0 ) {
			$recs[] = 'Traffic spikes detected — investigate referral sources and create follow-up content to capture the audience.';
		}

		$quality = (float) get_post_meta( $post_id, '_pearblog_quality_score', true );
		if ( $quality > 0 && $quality < 0.6 ) {
			$score -= 10;
			$recs[] = 'Quality score is below 60 % — revise headings, add FAQs, and improve readability.';
		}
		if ( $quality > 0 ) {
			$signals['quality_score'] = $quality;
		}

		if ( empty( $recs ) ) {
			$recs[] = 'Content is performing well — monitor weekly and refresh quarterly.';
		}

		return [
			'post_id'        => $post_id,
			'score'          => max( 0, min( 100, $score ) ),
			'recommendations'=> $recs,
			'signals'        => $signals,
		];
	}

	/**
	 * Record a daily view count for a post (called from pipeline / cron).
	 *
	 * @param int $post_id Post ID.
	 * @param int $views   View count for today.
	 */
	public function record_daily_views( int $post_id, int $views ): void {
		$data               = $this->get_daily_views( $post_id );
		$today              = gmdate( 'Y-m-d' );
		$data[ $today ]     = $views;
		// Keep rolling 365-day window.
		if ( count( $data ) > 365 ) {
			$data = array_slice( $data, -365, 365, true );
		}
		update_post_meta( $post_id, self::META_DAILY_VIEWS, wp_json_encode( $data ) );
	}

	/**
	 * Record site-level daily revenue.
	 *
	 * @param float $revenue Revenue in default currency for today.
	 */
	public function record_daily_revenue( float $revenue ): void {
		$data               = $this->get_site_revenue();
		$today              = gmdate( 'Y-m-d' );
		$data[ $today ]     = $revenue;
		if ( count( $data ) > 365 ) {
			$data = array_slice( $data, -365, 365, true );
		}
		update_option( self::OPT_SITE_REVENUE, wp_json_encode( $data ) );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Retrieve stored daily view counts for a post.
	 *
	 * @param  int $post_id Post ID.
	 * @return float[] Associative array keyed by date string.
	 */
	private function get_daily_views( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META_DAILY_VIEWS, true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Retrieve stored site-level daily revenue.
	 *
	 * @return float[]
	 */
	private function get_site_revenue(): array {
		$raw     = get_option( self::OPT_SITE_REVENUE, '' );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Ordinary least-squares linear regression.
	 *
	 * @param  float[] $y Ordered data points (x = 0, 1, 2 …).
	 * @return array{float, float} [slope, intercept]
	 */
	private function linear_regression( array $y ): array {
		$n     = count( $y );
		$sum_x = ( $n * ( $n - 1 ) ) / 2;           // 0 + 1 + … + (n-1)
		$sum_x_squared = ( $n * ( $n - 1 ) * ( 2 * $n - 1 ) ) / 6;
		$sum_y = array_sum( $y );
		$sum_xy= 0.0;
		foreach ( $y as $i => $v ) {
			$sum_xy += $i * (float) $v;
		}
		$denom    = ( $n * $sum_x_squared - $sum_x ** 2 );
		if ( $denom === 0 ) {
			return [ 0.0, $sum_y / $n ];
		}
		$slope     = ( $n * $sum_xy - $sum_x * $sum_y ) / $denom;
		$intercept = ( $sum_y - $slope * $sum_x ) / $n;
		return [ $slope, $intercept ];
	}

	/**
	 * Compute R² (coefficient of determination).
	 *
	 * @param  float[] $actual    Actual values.
	 * @param  float   $slope     Regression slope.
	 * @param  float   $intercept Regression intercept.
	 * @return float R² in [0, 1].
	 */
	private function compute_r_squared( array $actual, float $slope, float $intercept ): float {
		$mean   = array_sum( $actual ) / count( $actual );
		$ss_tot = 0.0;
		$ss_res = 0.0;
		foreach ( $actual as $i => $v ) {
			$predicted = $slope * $i + $intercept;
			$ss_res   += ( (float) $v - $predicted ) ** 2;
			$ss_tot   += ( (float) $v - $mean ) ** 2;
		}
		if ( $ss_tot === 0.0 ) {
			return 1.0;
		}
		return max( 0.0, 1.0 - $ss_res / $ss_tot );
	}

	/**
	 * Population standard deviation.
	 *
	 * @param  float[] $data Data points.
	 * @param  float   $mean Pre-computed mean.
	 * @return float
	 */
	private function std_dev( array $data, float $mean ): float {
		$variance = 0.0;
		foreach ( $data as $v ) {
			$variance += ( (float) $v - $mean ) ** 2;
		}
		return sqrt( $variance / count( $data ) );
	}

	/**
	 * Return an empty forecast result.
	 *
	 * @param  int $post_id Post ID.
	 * @param  int $days    Forecast horizon.
	 * @return array<string, mixed>
	 */
	private function empty_forecast( int $post_id, int $days ): array {
		return [
			'post_id'         => $post_id,
			'historical_days' => 0,
			'forecast_days'   => $days,
			'projected_views' => array_fill( 0, $days, 0 ),
			'trend'           => 'unknown',
			'confidence'      => 0.0,
			'slope'           => 0.0,
			'intercept'       => 0.0,
		];
	}
}
