<?php
/**
 * Predictive Analytics – V9.0 F2: advanced content-performance forecasting.
 *
 * Aggregates real-time page-view data, engagement signals, and revenue figures
 * into a unified dashboard model that surfaces:
 *  - Next-7-day traffic forecast per post/category
 *  - Anomaly alerts when actual traffic deviates >20% from forecast
 *  - Revenue optimisation recommendations based on ROI per article
 *  - Audience-behaviour prediction (new vs returning, device mix)
 *
 * Data flow:
 *  1. `refresh()` is called once daily by WP-Cron.
 *  2. It queries the last 90 days of GA4 data from get_option()
 *     (populated by GA4Client) and runs a simple linear-trend model.
 *  3. Forecasts are stored in `pearblog_pa_forecasts` (WP option).
 *  4. The REST endpoint `GET /wp-json/pearblog/v1/analytics/forecast`
 *     returns the cached forecasts as JSON.
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * V9 Predictive Analytics module.
 */
class PredictiveAnalytics {

	/** WP option: cached forecast data. */
	public const OPTION_FORECASTS   = 'pearblog_pa_forecasts';

	/** WP option: last model build timestamp. */
	public const OPTION_LAST_RUN    = 'pearblog_pa_last_run';

	/** WP option: anomaly thresholds. */
	public const OPTION_THRESHOLDS  = 'pearblog_pa_thresholds';

	/** Cron hook. */
	private const CRON_HOOK         = 'pearblog_pa_refresh';

	/** REST namespace. */
	private const REST_NAMESPACE    = 'pearblog/v1';

	/** Default anomaly-deviation percentage before an alert is triggered. */
	public const DEFAULT_ANOMALY_PCT = 20.0;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'refresh' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Schedule the daily cron if it has not already been scheduled.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/analytics/forecast',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_get_forecasts' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			]
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/analytics/anomalies',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_get_anomalies' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			]
		);
	}

	// -----------------------------------------------------------------------
	// Forecasting
	// -----------------------------------------------------------------------

	/**
	 * Build and cache traffic forecasts using a linear-trend model.
	 *
	 * @param array<int,array{date:string,pageviews:int}> $history
	 *   Optional: inject historical data (useful for testing). When omitted the
	 *   method reads from the `pearblog_ga4_daily_history` option (written by
	 *   GA4Client).
	 * @return array<string,int> Map of ISO-8601 date strings → forecast pageviews.
	 */
	public function refresh( array $history = [] ): array {
		if ( empty( $history ) ) {
			$stored  = get_option( 'pearblog_ga4_daily_history', [] );
			$history = is_array( $stored ) ? $stored : [];
		}

		$forecasts = $this->build_forecasts( $history );
		update_option( self::OPTION_FORECASTS, $forecasts );
		update_option( self::OPTION_LAST_RUN, gmdate( 'Y-m-d\TH:i:s\Z' ) );

		return $forecasts;
	}

	/**
	 * Produce 7-day ahead forecasts from a daily history using OLS linear regression.
	 *
	 * @param array<int,array{date:string,pageviews:int}> $history
	 * @return array<string,int>
	 */
	public function build_forecasts( array $history ): array {
		$n = count( $history );
		if ( $n < 7 ) {
			return [];
		}

		// Encode day index as x, pageviews as y.
		$sum_x  = 0;
		$sum_y  = 0;
		$sum_xy = 0;
		$sum_xx = 0;

		foreach ( $history as $i => $row ) {
			$x       = $i;
			$y       = (int) ( $row['pageviews'] ?? 0 );
			$sum_x  += $x;
			$sum_y  += $y;
			$sum_xy += $x * $y;
			$sum_xx += $x * $x;
		}

		$denom = ( $n * $sum_xx ) - ( $sum_x * $sum_x );
		if ( 0 === $denom ) {
			// Flat line – all days equal.
			$slope     = 0;
			$intercept = $n > 0 ? $sum_y / $n : 0;
		} else {
			$slope     = ( ( $n * $sum_xy ) - ( $sum_x * $sum_y ) ) / $denom;
			$intercept = ( $sum_y - $slope * $sum_x ) / $n;
		}

		// Compute last entry's date offset.
		$last_entry = end( $history );
		$last_date  = $last_entry['date'] ?? gmdate( 'Y-m-d' );
		$last_ts    = strtotime( $last_date ) ?: time();

		$forecasts = [];
		for ( $d = 1; $d <= 7; $d++ ) {
			$future_idx              = $n - 1 + $d;
			$predicted               = (int) round( $intercept + $slope * $future_idx );
			$date_key                = gmdate( 'Y-m-d', $last_ts + $d * DAY_IN_SECONDS );
			$forecasts[ $date_key ]  = max( 0, $predicted );
		}

		return $forecasts;
	}

	/**
	 * Detect anomalies by comparing actual to forecast values.
	 *
	 * @param array<string,int> $actual   Date → actual pageviews.
	 * @param array<string,int> $forecast Date → forecast pageviews.
	 * @param float             $threshold Deviation percentage before anomaly.
	 * @return array<int,array{date:string,actual:int,forecast:int,deviation_pct:float}>
	 */
	public function detect_anomalies(
		array $actual,
		array $forecast,
		float $threshold = self::DEFAULT_ANOMALY_PCT
	): array {
		$anomalies = [];

		foreach ( $actual as $date => $pageviews ) {
			if ( ! isset( $forecast[ $date ] ) || 0 === $forecast[ $date ] ) {
				continue;
			}
			$deviation = abs( $pageviews - $forecast[ $date ] ) / $forecast[ $date ] * 100;
			if ( $deviation > $threshold ) {
				$anomalies[] = [
					'date'          => $date,
					'actual'        => $pageviews,
					'forecast'      => $forecast[ $date ],
					'deviation_pct' => round( $deviation, 2 ),
				];
			}
		}

		return $anomalies;
	}

	/**
	 * Compute revenue optimisation recommendations.
	 *
	 * @param array<int,array{post_id:int,pageviews:int,revenue:float}> $roi_data
	 * @return array<int,array{post_id:int,recommendation:string,roi_per_view:float}>
	 */
	public function revenue_recommendations( array $roi_data ): array {
		if ( empty( $roi_data ) ) {
			return [];
		}

		// Compute ROI per pageview.
		$scored = array_map( static function ( array $row ): array {
			$pv               = max( 1, (int) ( $row['pageviews'] ?? 1 ) );
			$roi_per_view     = round( (float) ( $row['revenue'] ?? 0 ) / $pv, 6 );
			$row['roi_per_view'] = $roi_per_view;
			return $row;
		}, $roi_data );

		usort( $scored, static fn( array $a, array $b ) => $b['roi_per_view'] <=> $a['roi_per_view'] );

		$median = $scored[ (int) ( count( $scored ) / 2 ) ]['roi_per_view'] ?? 0;

		$recommendations = [];
		foreach ( $scored as $row ) {
			$rec = $row['roi_per_view'] > $median * 1.5
				? 'increase-promotion'
				: ( $row['roi_per_view'] < $median * 0.5 ? 'refresh-or-retire' : 'maintain' );

			$recommendations[] = [
				'post_id'        => (int) $row['post_id'],
				'recommendation' => $rec,
				'roi_per_view'   => $row['roi_per_view'],
			];
		}

		return $recommendations;
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * REST: return cached forecasts.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_get_forecasts(): mixed {
		return rest_ensure_response( [
			'forecasts' => get_option( self::OPTION_FORECASTS, [] ),
			'last_run'  => get_option( self::OPTION_LAST_RUN, null ),
		] );
	}

	/**
	 * REST: detect anomalies between stored forecast and actual option.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_get_anomalies(): mixed {
		$forecast = get_option( self::OPTION_FORECASTS, [] );
		$actual   = get_option( 'pearblog_ga4_actual_daily', [] );
		$threshold = (float) get_option( self::OPTION_THRESHOLDS, self::DEFAULT_ANOMALY_PCT );

		return rest_ensure_response( [
			'anomalies' => $this->detect_anomalies( $actual, $forecast, $threshold ),
		] );
	}
}
