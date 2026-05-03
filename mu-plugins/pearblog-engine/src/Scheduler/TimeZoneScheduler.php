<?php
/**
 * Timezone Scheduler – publishes articles at locally optimal times per timezone.
 *
 * PearBlog publishes articles at the best local time for the target audience
 * timezone, rather than the server timezone.  This increases organic traffic
 * by timing publication around peak reading hours in the target market.
 *
 * Algorithm:
 *   1. Reads `pearblog_target_timezone` option (e.g. "Europe/Warsaw").
 *   2. Looks up a configurable "golden hours" window per timezone group
 *      (North America, Europe, Asia-Pacific, Middle East, Latin America).
 *   3. Converts the next golden-hour slot to UTC and stores it as the
 *      WordPress scheduled publish time (post_date_gmt).
 *   4. Hooks into the `pearblog_pipeline_completed` action to reschedule
 *      newly generated articles on the fly if they were published immediately.
 *
 * Options:
 *   pearblog_target_timezone       – PHP timezone string (default: WP site timezone)
 *   pearblog_tz_golden_start       – hour (0-23, local) when golden window opens (default 8)
 *   pearblog_tz_golden_end         – hour (0-23, local) when golden window closes (default 11)
 *   pearblog_tz_scheduling_enabled – bool master switch (default false)
 *   pearblog_tz_spread_minutes     – spread consecutive articles by N minutes (default 30)
 *
 * REST endpoints:
 *   GET  /pearblog/v1/scheduler/slots         – next 7 days of optimal publish slots
 *   POST /pearblog/v1/scheduler/reschedule/{post_id} – reschedule a specific post
 *
 * WP-CLI:
 *   wp pearblog scheduler slots [--days=7]
 *   wp pearblog scheduler reschedule <post_id>
 *
 * @package PearBlogEngine\Scheduler
 */

declare(strict_types=1);

namespace PearBlogEngine\Scheduler;

/**
 * Schedules article publication at timezone-optimal times.
 */
class TimeZoneScheduler {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Number of pre-scheduled slots to look ahead. */
	private const LOOKAHEAD_DAYS = 14;

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks and REST routes.
	 */
	public function register(): void {
		if ( ! (bool) get_option( 'pearblog_tz_scheduling_enabled', false ) ) {
			return;
		}

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'pearblog_pipeline_completed', [ $this, 'reschedule_article' ], 30, 1 );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/scheduler/slots', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_slots' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'days' => [ 'required' => false, 'type' => 'integer', 'minimum' => 1, 'maximum' => 30, 'default' => 7 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/scheduler/reschedule/(?P<id>[\d]+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_reschedule' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
			],
		] );
	}

	/**
	 * Permission – manage_options or API key.
	 */
	public function rest_permission( \WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$stored = get_option( 'pearblog_api_key', '' );
		if ( '' === $stored ) {
			return false;
		}
		$header = $request->get_header( 'Authorization' ) ?? '';
		if ( str_starts_with( $header, 'Bearer ' ) ) {
			return hash_equals( $stored, trim( substr( $header, 7 ) ) );
		}
		return false;
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * GET /scheduler/slots – next N days of optimal publish slots.
	 */
	public function rest_slots( \WP_REST_Request $request ): \WP_REST_Response {
		$days  = (int) $request->get_param( 'days' );
		$slots = $this->get_upcoming_slots( $days );
		return new \WP_REST_Response( $slots, 200 );
	}

	/**
	 * POST /scheduler/reschedule/{id} – reschedule a post.
	 */
	public function rest_reschedule( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$result  = $this->reschedule_post( $post_id );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [ 'error' => $result->get_error_message() ], 422 );
		}

		return new \WP_REST_Response( $result, 200 );
	}

	// -----------------------------------------------------------------------
	// Core scheduling logic
	// -----------------------------------------------------------------------

	/**
	 * Reschedule a freshly published post to the next golden-hour slot.
	 *
	 * Called via `pearblog_pipeline_completed` action.
	 *
	 * @param int $post_id WordPress post ID.
	 */
	public function reschedule_article( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}
		$this->reschedule_post( $post_id );
	}

	/**
	 * Reschedule a specific post to the next optimal UTC slot.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return array|\WP_Error  Updated schedule info or WP_Error.
	 */
	public function reschedule_post( int $post_id ): array|\WP_Error {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'invalid_post', "Post #{$post_id} not found." );
		}

		$next_slot = $this->next_available_slot();

		if ( ! $next_slot ) {
			return new \WP_Error( 'no_slot', 'Could not compute a next publish slot.' );
		}

		// Move post to 'future' with the new date.
		$post_date     = $next_slot->format( 'Y-m-d H:i:s' );
		$post_date_gmt = ( clone $next_slot )->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );

		wp_update_post( [
			'ID'            => $post_id,
			'post_status'   => 'future',
			'post_date'     => $post_date,
			'post_date_gmt' => $post_date_gmt,
		] );

		wp_schedule_single_event( $next_slot->getTimestamp(), 'publish_future_post', [ $post_id ] );

		do_action( 'pearblog_post_rescheduled', $post_id, $next_slot->getTimestamp() );

		return [
			'post_id'       => $post_id,
			'scheduled_utc' => $post_date_gmt,
			'scheduled_local' => $post_date,
			'timezone'      => $this->get_target_timezone(),
		];
	}

	/**
	 * Return an array of upcoming golden-hour slots for the next N days.
	 *
	 * @param int $days Number of days to look ahead.
	 * @return array  Array of slot info arrays.
	 */
	public function get_upcoming_slots( int $days = 7 ): array {
		$tz           = new \DateTimeZone( $this->get_target_timezone() );
		$golden_start = (int) get_option( 'pearblog_tz_golden_start', 8 );
		$golden_end   = (int) get_option( 'pearblog_tz_golden_end', 11 );
		$spread       = (int) get_option( 'pearblog_tz_spread_minutes', 30 );
		$slots        = [];
		$now          = new \DateTimeImmutable( 'now', $tz );

		for ( $day = 0; $day < $days; $day++ ) {
			$date    = $now->modify( "+{$day} days" );
			$current = $date->setTime( $golden_start, 0, 0 );
			$end     = $date->setTime( $golden_end, 0, 0 );

			while ( $current <= $end ) {
				if ( $current > $now ) {
					$utc = $current->setTimezone( new \DateTimeZone( 'UTC' ) );
					$slots[] = [
						'local_datetime' => $current->format( 'Y-m-d H:i:s' ),
						'utc_datetime'   => $utc->format( 'Y-m-d H:i:s' ),
						'timezone'       => $this->get_target_timezone(),
					];
				}
				$current = $current->modify( "+{$spread} minutes" );
			}
		}

		return $slots;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Return the next available golden-hour slot as a DateTimeImmutable (local tz).
	 *
	 * @return \DateTimeImmutable|null
	 */
	private function next_available_slot(): ?\DateTimeImmutable {
		$slots = $this->get_upcoming_slots( self::LOOKAHEAD_DAYS );
		if ( empty( $slots ) ) {
			return null;
		}

		// Check which slots are already taken by scheduled posts.
		$taken = $this->get_taken_slots();

		foreach ( $slots as $slot ) {
			if ( ! in_array( $slot['utc_datetime'], $taken, true ) ) {
				$tz = new \DateTimeZone( $this->get_target_timezone() );
				return new \DateTimeImmutable( $slot['local_datetime'], $tz );
			}
		}

		// If all computed slots are taken, return the first one anyway.
		$tz = new \DateTimeZone( $this->get_target_timezone() );
		return new \DateTimeImmutable( $slots[0]['local_datetime'], $tz );
	}

	/**
	 * Return a list of UTC datetimes already claimed by 'future' posts.
	 *
	 * @return string[]  Array of 'Y-m-d H:i:s' UTC strings.
	 */
	private function get_taken_slots(): array {
		global $wpdb;
		$rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT post_date_gmt FROM {$wpdb->posts}
				 WHERE post_status = 'future' AND post_type = 'post'
				   AND post_date_gmt >= %s
				 ORDER BY post_date_gmt ASC
				 LIMIT 100",
				gmdate( 'Y-m-d H:i:s' )
			)
		);
		return $rows ?: [];
	}

	/**
	 * Get the configured target timezone string.
	 *
	 * @return string  PHP timezone string.
	 */
	private function get_target_timezone(): string {
		$tz = get_option( 'pearblog_target_timezone', '' );
		if ( ! $tz ) {
			// Fall back to WordPress site timezone.
			$tz = get_option( 'timezone_string', 'UTC' );
		}
		return $tz ?: 'UTC';
	}
}
