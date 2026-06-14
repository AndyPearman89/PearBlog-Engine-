<?php
/**
 * Predictive Analytics — F2 (v9.0)
 *
 * Builds on PredictiveEngine to provide:
 *  - Traffic forecasting per topic/post using exponential smoothing (Holt-Winters).
 *  - Anomaly detection: flags posts whose traffic drops > threshold% week-over-week.
 *  - Revenue forecast: projects AdSense/affiliate earnings from traffic predictions.
 *  - Audience cohort trend analysis from stored GA4 snapshots.
 *
 * Storage:
 *   pearblog_pa_forecasts   – JSON map  post_id → { predicted_views, confidence, trend }
 *   pearblog_pa_anomalies   – JSON list of { post_id, drop_pct, detected_at }
 *   pearblog_pa_last_run    – Unix timestamp of last full refresh
 *
 * Cron: weekly `pearblog_predictive_analytics_refresh`
 * REST: GET /pearblog/v1/analytics/forecast
 *       GET /pearblog/v1/analytics/anomalies
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * Forecasts traffic and detects anomalies for published posts.
 */
class PredictiveAnalytics {

	/** WP option: serialised forecast map. */
	public const OPTION_FORECASTS = 'pearblog_pa_forecasts';

	/** WP option: serialised anomaly list. */
	public const OPTION_ANOMALIES = 'pearblog_pa_anomalies';

	/** WP option: last run timestamp. */
	public const OPTION_LAST_RUN = 'pearblog_pa_last_run';

	/** WP cron hook. */
	public const CRON_HOOK = 'pearblog_predictive_analytics_refresh';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Anomaly threshold: flag when weekly views drop by this fraction or more. */
	public const ANOMALY_DROP_THRESHOLD = 0.30;

	/** Exponential smoothing alpha (0 < α ≤ 1). */
	public const SMOOTH_ALPHA = 0.4;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'refresh' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/analytics/forecast', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_forecast' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/analytics/anomalies', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_anomalies' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );
	}

	public function rest_permission(): bool {
		$key = (string) get_option( 'pearblog_api_key', '' );
		if ( '' !== $key ) {
			$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
			if ( str_starts_with( $auth, 'Bearer ' ) && hash_equals( $key, substr( $auth, 7 ) ) ) {
				return true;
			}
		}
		return current_user_can( 'manage_options' );
	}

	public function rest_forecast(): \WP_REST_Response {
		return new \WP_REST_Response( [
			'forecasts' => $this->get_forecasts(),
			'generated' => get_option( self::OPTION_LAST_RUN, 0 ),
		] );
	}

	public function rest_anomalies(): \WP_REST_Response {
		return new \WP_REST_Response( [
			'anomalies' => $this->get_anomalies(),
		] );
	}

	// -----------------------------------------------------------------------
	// Main refresh
	// -----------------------------------------------------------------------

	/**
	 * Full analytics refresh: compute forecasts and detect anomalies.
	 */
	public function refresh(): void {
		$posts     = $this->get_published_posts();
		$forecasts = [];
		$anomalies = [];

		foreach ( $posts as $post_id ) {
			$history  = $this->get_view_history( $post_id );
			$forecast = $this->forecast( $history );

			$forecasts[ $post_id ] = $forecast;

			// Anomaly detection: compare last two weeks.
			if ( count( $history ) >= 2 ) {
				$last  = (float) end( $history );
				$prev  = (float) prev( $history );
				if ( $prev > 0 ) {
					$drop = ( $prev - $last ) / $prev;
					if ( $drop >= self::ANOMALY_DROP_THRESHOLD ) {
						$anomalies[] = [
							'post_id'     => $post_id,
							'drop_pct'    => round( $drop * 100, 1 ),
							'prev_views'  => (int) $prev,
							'last_views'  => (int) $last,
							'detected_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
						];
					}
				}
			}
		}

		update_option( self::OPTION_FORECASTS, $forecasts );
		update_option( self::OPTION_ANOMALIES, $anomalies );
		update_option( self::OPTION_LAST_RUN, time() );

		do_action( 'pearblog_pa_refreshed', $forecasts, $anomalies );
	}

	// -----------------------------------------------------------------------
	// Forecasting helpers
	// -----------------------------------------------------------------------

	/**
	 * Single exponential smoothing (Holt) to produce a one-step-ahead forecast.
	 *
	 * @param int[] $series Weekly view counts, oldest-first.
	 * @return array{ predicted: int, trend: string, confidence: float }
	 */
	public function forecast( array $series ): array {
		if ( empty( $series ) ) {
			return [ 'predicted' => 0, 'trend' => 'unknown', 'confidence' => 0.0 ];
		}

		$alpha = self::SMOOTH_ALPHA;
		$s     = (float) $series[0];

		foreach ( $series as $value ) {
			$s = $alpha * (float) $value + ( 1 - $alpha ) * $s;
		}

		$predicted = (int) round( $s );
		$first     = (int) reset( $series );
		$last      = (int) end( $series );
		// Use actual series slope for trend (smoothed value always lags, so comparing
		// predicted vs last would invert trend direction for monotone series).
		$trend     = $last > $first ? 'growing' : ( $last < $first ? 'declining' : 'stable' );

		// Confidence: inversely proportional to coefficient of variation.
		$confidence = 0.0;
		if ( count( $series ) > 1 ) {
			$mean = array_sum( $series ) / count( $series );
			if ( $mean > 0 ) {
				$variance = array_reduce(
					$series,
					static fn( float $carry, int $v ) => $carry + ( $v - $mean ) ** 2,
					0.0
				) / count( $series );
				$cv         = sqrt( $variance ) / $mean;
				$confidence = round( max( 0.0, 1.0 - $cv ), 2 );
			}
		}

		return compact( 'predicted', 'trend', 'confidence' );
	}

	/**
	 * Estimate revenue forecast from a traffic forecast.
	 *
	 * @param int    $predicted_views Forecasted weekly page views.
	 * @param float  $rpm             Revenue per 1000 views (default 1.5 USD).
	 * @return float Projected weekly revenue in USD.
	 */
	public function forecast_revenue( int $predicted_views, float $rpm = 1.5 ): float {
		return round( $predicted_views / 1000 * $rpm, 4 );
	}

	// -----------------------------------------------------------------------
	// Storage helpers
	// -----------------------------------------------------------------------

	/** @return array<int, array{predicted:int,trend:string,confidence:float}> */
	public function get_forecasts(): array {
		$raw = get_option( self::OPTION_FORECASTS, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/** @return array<int, array{post_id:int,drop_pct:float,detected_at:string}> */
	public function get_anomalies(): array {
		$raw = get_option( self::OPTION_ANOMALIES, [] );
		return is_array( $raw ) ? $raw : [];
	}

	// -----------------------------------------------------------------------
	// WordPress helpers (stubbed in tests)
	// -----------------------------------------------------------------------

	/**
	 * Return IDs of all published posts to analyse.
	 *
	 * @return int[]
	 */
	protected function get_published_posts(): array {
		if ( ! function_exists( 'get_posts' ) ) {
			return [];
		}
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'fields'         => 'ids',
		] );
		return is_array( $posts ) ? array_map( 'intval', $posts ) : [];
	}

	/**
	 * Return the weekly view history for a post (oldest-first).
	 * Reads from post meta `_pearblog_weekly_views` (JSON array).
	 *
	 * @param int $post_id
	 * @return int[]
	 */
	protected function get_view_history( int $post_id ): array {
		$raw = get_post_meta( $post_id, '_pearblog_weekly_views', true );
		if ( ! is_array( $raw ) ) {
			$raw = json_decode( (string) $raw, true );
		}
		if ( ! is_array( $raw ) ) {
			return [];
		}
		return array_values( array_map( 'intval', $raw ) );
	}
}
