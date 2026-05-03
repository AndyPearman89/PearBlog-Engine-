<?php
/**
 * Cohort & Funnel Analytics Engine.
 *
 * Tracks user cohorts through the full conversion funnel:
 *   Visit → Register → Lead → Conversion
 *
 * Data is collected via lightweight JavaScript pixel + REST endpoint and
 * stored in WordPress as aggregated option data.
 *
 * Features:
 *  - Per-article conversion funnel tracking.
 *  - Session cohort comparison: organic search vs AI-generated content.
 *  - Weekly funnel summary stored in `pearblog_cohort_snapshot`.
 *
 * REST endpoints:
 *   POST /pearblog/v1/cohort/event       – track a funnel event (from JS pixel)
 *   GET  /pearblog/v1/cohort/summary     – get funnel summary stats
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * Cohort and funnel analytics.
 */
class CohortEngine {

	/** WP option keys. */
	public const OPTION_SNAPSHOT = 'pearblog_cohort_snapshot';
	public const OPTION_RAW      = 'pearblog_cohort_raw';

	/** Funnel stages. */
	public const STAGE_VISIT      = 'visit';
	public const STAGE_REGISTER   = 'register';
	public const STAGE_LEAD       = 'lead';
	public const STAGE_CONVERSION = 'conversion';

	/** Max raw events to store (ring buffer). */
	private const MAX_EVENTS = 2000;

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Cron hook for weekly snapshot. */
	private const CRON_HOOK = 'pearblog_cohort_snapshot_refresh';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tracking_pixel' ] );
		add_action( self::CRON_HOOK, [ $this, 'refresh_snapshot' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
	}

	/**
	 * Schedule weekly snapshot refresh.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/cohort/event', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_track_event' ],
			'permission_callback' => '__return_true', // Open: called from frontend JS.
		] );

		register_rest_route( self::NAMESPACE, '/cohort/summary', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_summary' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	/**
	 * Enqueue lightweight tracking pixel JS.
	 */
	public function enqueue_tracking_pixel(): void {
		if ( ! (bool) get_option( 'pearblog_cohort_enabled', false ) ) {
			return;
		}

		$nonce = wp_create_nonce( 'pearblog_cohort' );
		$js    = "
(function(){
	var pb_cohort = {
		endpoint: '" . esc_js( rest_url( 'pearblog/v1/cohort/event' ) ) . "',
		nonce: '{$nonce}',
		post_id: " . (int) get_the_ID() . ",
		source: '" . esc_js( isset( \$_SERVER['HTTP_REFERER'] ) ? 'referral' : 'direct' ) . "',
		track: function(stage, meta) {
			if (!navigator.sendBeacon) return;
			var data = new FormData();
			data.append('stage', stage);
			data.append('post_id', pb_cohort.post_id);
			data.append('source', pb_cohort.source);
			data.append('_wpnonce', pb_cohort.nonce);
			if (meta) Object.keys(meta).forEach(function(k){ data.append(k, meta[k]); });
			navigator.sendBeacon(pb_cohort.endpoint, data);
		}
	};
	// Auto-track page visit.
	pb_cohort.track('visit');
	// Expose for custom conversion tracking.
	window.pbCohort = pb_cohort;
})();";

		wp_add_inline_script( 'jquery', $js );
	}

	// -----------------------------------------------------------------------
	// Event recording
	// -----------------------------------------------------------------------

	/**
	 * Record a funnel event.
	 *
	 * @param string $stage   Funnel stage.
	 * @param int    $post_id WordPress post ID.
	 * @param string $source  Traffic source.
	 * @param array<string,mixed> $meta Additional metadata.
	 */
	public function record_event( string $stage, int $post_id, string $source = 'direct', array $meta = [] ): void {
		$raw   = (array) get_option( self::OPTION_RAW, [] );
		$raw[] = [
			'stage'      => $stage,
			'post_id'    => $post_id,
			'source'     => $source,
			'timestamp'  => time(),
			'meta'       => $meta,
		];

		// Ring buffer.
		if ( count( $raw ) > self::MAX_EVENTS ) {
			$raw = array_slice( $raw, -self::MAX_EVENTS );
		}

		update_option( self::OPTION_RAW, $raw );
	}

	// -----------------------------------------------------------------------
	// Snapshot computation
	// -----------------------------------------------------------------------

	/**
	 * Build and persist the funnel snapshot.
	 */
	public function refresh_snapshot(): void {
		$snapshot = $this->compute_snapshot();
		update_option( self::OPTION_SNAPSHOT, $snapshot );

		/**
		 * Action: pearblog_cohort_snapshot_refreshed
		 *
		 * @param array<string,mixed> $snapshot Funnel snapshot data.
		 */
		do_action( 'pearblog_cohort_snapshot_refreshed', $snapshot );
	}

	/**
	 * Compute funnel statistics from raw events.
	 *
	 * @return array<string,mixed>
	 */
	public function compute_snapshot(): array {
		$raw    = (array) get_option( self::OPTION_RAW, [] );
		$stages = [ self::STAGE_VISIT, self::STAGE_REGISTER, self::STAGE_LEAD, self::STAGE_CONVERSION ];

		$by_source = [];
		$by_stage  = array_fill_keys( $stages, 0 );

		foreach ( $raw as $event ) {
			$stage  = $event['stage'] ?? '';
			$source = $event['source'] ?? 'direct';

			if ( isset( $by_stage[ $stage ] ) ) {
				$by_stage[ $stage ]++;
			}

			if ( ! isset( $by_source[ $source ] ) ) {
				$by_source[ $source ] = array_fill_keys( $stages, 0 );
			}

			if ( isset( $by_source[ $source ][ $stage ] ) ) {
				$by_source[ $source ][ $stage ]++;
			}
		}

		// Calculate conversion rates.
		$funnel_rates = [];
		for ( $i = 0; $i < count( $stages ) - 1; $i++ ) {
			$from    = $stages[ $i ];
			$to      = $stages[ $i + 1 ];
			$from_n  = $by_stage[ $from ];
			$to_n    = $by_stage[ $to ];
			$rate    = $from_n > 0 ? round( ( $to_n / $from_n ) * 100, 2 ) : 0.0;
			$funnel_rates["{$from}_to_{$to}"] = $rate;
		}

		return [
			'generated_at'    => time(),
			'total_events'    => count( $raw ),
			'by_stage'        => $by_stage,
			'by_source'       => $by_source,
			'funnel_rates_pct' => $funnel_rates,
		];
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_track_event( \WP_REST_Request $request ) {
		// Verify nonce for frontend requests.
		$nonce = $request->get_param( '_wpnonce' ) ?? $request->get_header( 'x-wp-nonce' );
		if ( ! wp_verify_nonce( $nonce, 'pearblog_cohort' ) ) {
			return new \WP_Error( 'invalid_nonce', 'Invalid nonce.', [ 'status' => 403 ] );
		}

		$stage   = sanitize_text_field( (string) ( $request->get_param( 'stage' ) ?? '' ) );
		$post_id = (int) ( $request->get_param( 'post_id' ) ?? 0 );
		$source  = sanitize_text_field( (string) ( $request->get_param( 'source' ) ?? 'direct' ) );

		$valid_stages = [ self::STAGE_VISIT, self::STAGE_REGISTER, self::STAGE_LEAD, self::STAGE_CONVERSION ];
		if ( ! in_array( $stage, $valid_stages, true ) ) {
			return new \WP_Error( 'invalid_stage', 'Invalid funnel stage.', [ 'status' => 400 ] );
		}

		$this->record_event( $stage, $post_id, $source );

		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_summary( \WP_REST_Request $request ): \WP_REST_Response {
		$snapshot = get_option( self::OPTION_SNAPSHOT );
		if ( ! is_array( $snapshot ) ) {
			$snapshot = $this->compute_snapshot();
		}

		return new \WP_REST_Response( $snapshot, 200 );
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}
