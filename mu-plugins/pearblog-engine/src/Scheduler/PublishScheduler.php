<?php
/**
 * Publish Scheduler — calculates the optimal time to publish new posts
 * based on historical Google Analytics 4 engagement data.
 *
 * Algorithm:
 *   1. Fetch GA4 page-engagement data (sessions × engaged sessions) for the
 *      past `pearblog_ps_lookback_days` days.
 *   2. Aggregate engagement per hour-of-day (0–23) and per day-of-week (0 Sun … 6 Sat).
 *   3. Normalise both vectors to 0–100 and combine: hour weight 60%, day weight 40%.
 *   4. The optimal slot is the (hour, dow) pair with the highest combined score.
 *   5. `get_optimal_publish_time()` returns the next calendar occurrence of that slot
 *      in the site's local timezone.
 *   6. `schedule_post($post_id)` calls `wp_schedule_single_event()` (future publish)
 *      with the optimal timestamp and updates the post status to 'future'.
 *
 * When GA4 is not configured the scheduler falls back to a sensible default
 * (Tuesday 10:00 am site-local time).
 *
 * Configuration WP options:
 *   pearblog_ps_enabled          – bool, master switch (default false)
 *   pearblog_ps_lookback_days    – int, GA4 analysis window (default 90)
 *   pearblog_ps_analysis         – JSON, cached engagement analysis
 *   pearblog_ps_fallback_hour    – int 0–23, default hour when GA4 is unavailable (default 10)
 *   pearblog_ps_fallback_dow     – int 0–6, default day-of-week 0=Sun…6=Sat (default 2 = Tuesday)
 *
 * Cron hook: pearblog_publish_schedule_refresh (weekly)
 * Action hook: pearblog_post_scheduled ($post_id, $timestamp)
 *
 * @package PearBlogEngine\Scheduler
 */

declare(strict_types=1);

namespace PearBlogEngine\Scheduler;

use PearBlogEngine\Analytics\GA4Client;

/**
 * Determines the best time to publish and reschedules posts accordingly.
 */
class PublishScheduler {

	// -----------------------------------------------------------------------
	// Option keys
	// -----------------------------------------------------------------------

	public const OPTION_ENABLED       = 'pearblog_ps_enabled';
	public const OPTION_LOOKBACK_DAYS = 'pearblog_ps_lookback_days';
	public const OPTION_ANALYSIS      = 'pearblog_ps_analysis';
	public const OPTION_FALLBACK_HOUR = 'pearblog_ps_fallback_hour';
	public const OPTION_FALLBACK_DOW  = 'pearblog_ps_fallback_dow';

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	public const DEFAULT_LOOKBACK_DAYS = 90;
	public const DEFAULT_FALLBACK_HOUR = 10;   // 10 am
	public const DEFAULT_FALLBACK_DOW  = 2;    // Tuesday

	// -----------------------------------------------------------------------
	// Hooks
	// -----------------------------------------------------------------------

	/** WP cron hook for weekly analysis refresh. */
	public const CRON_HOOK = 'pearblog_publish_schedule_refresh';

	/** Action hook fired after scheduling a post. */
	public const ACTION_SCHEDULED = 'pearblog_post_scheduled';

	// -----------------------------------------------------------------------
	// Internal constants
	// -----------------------------------------------------------------------

	/** Number of hours in a day. */
	private const HOURS_IN_DAY = 24;

	/** Number of days in a week. */
	private const DAYS_IN_WEEK = 7;

	/** Weight of the hour signal (vs day-of-week) when computing combined score. */
	private const HOUR_WEIGHT = 0.6;

	/** Weight of the day-of-week signal. */
	private const DOW_WEIGHT = 0.4;

	// -----------------------------------------------------------------------
	// Dependencies
	// -----------------------------------------------------------------------

	/** @var GA4Client */
	private $ga4;

	public function __construct( ?GA4Client $ga4 = null ) {
		$this->ga4 = $ga4 ?? new GA4Client();
	}

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register the weekly cron refresh.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'analyse' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Whether the smart scheduler is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	/**
	 * Analyse GA4 engagement data and persist the result.
	 *
	 * @return array{hour_scores: float[], dow_scores: float[], optimal_hour: int, optimal_dow: int}
	 */
	public function analyse(): array {
		$analysis = $this->compute_analysis();
		update_option( self::OPTION_ANALYSIS, wp_json_encode( $analysis ) );
		return $analysis;
	}

	/**
	 * Return the cached engagement analysis, refreshing if absent.
	 *
	 * @return array{hour_scores: float[], dow_scores: float[], optimal_hour: int, optimal_dow: int}
	 */
	public function get_analysis(): array {
		$raw = get_option( self::OPTION_ANALYSIS, '' );
		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) && isset( $decoded['optimal_hour'] ) ) {
				return $decoded;
			}
		}
		return $this->analyse();
	}

	/**
	 * Return the next \DateTimeImmutable occurrence of the optimal publish slot
	 * in the site's local timezone.
	 *
	 * If the optimal slot for the current day has already passed today, the
	 * returned time is in the NEXT occurrence of that day-of-week.
	 *
	 * @return \DateTimeImmutable
	 */
	public function get_optimal_publish_time(): \DateTimeImmutable {
		[ $hour, $dow ] = $this->get_optimal_slot();

		$tz   = $this->get_site_timezone();
		$now  = new \DateTimeImmutable( 'now', $tz );
		$next = $this->next_occurrence( $now, $dow, $hour );

		return $next;
	}

	/**
	 * Schedule a post to publish at the next optimal time.
	 *
	 * Sets the post status to 'future' and schedules a `publish_future_post`
	 * single cron event at the optimal timestamp.
	 *
	 * @param int $post_id WP post ID.
	 * @return bool        True on success, false when the post does not exist.
	 */
	public function schedule_post( int $post_id ): bool {
		if ( ! $this->post_exists( $post_id ) ) {
			return false;
		}

		$publish_time = $this->get_optimal_publish_time();
		$timestamp    = $publish_time->getTimestamp();

		// Update post date to the scheduled time (WP uses local time in the DB).
		$tz       = $this->get_site_timezone();
		$gmt_time = ( new \DateTimeImmutable( '@' . $timestamp ) )
			->setTimezone( new \DateTimeZone( 'UTC' ) )
			->format( 'Y-m-d H:i:s' );
		$local_time = $publish_time->setTimezone( $tz )->format( 'Y-m-d H:i:s' );

		wp_update_post( [
			'ID'            => $post_id,
			'post_status'   => 'future',
			'post_date'     => $local_time,
			'post_date_gmt' => $gmt_time,
		] );

		wp_schedule_single_event( $timestamp, 'publish_future_post', [ $post_id ] );

		do_action( self::ACTION_SCHEDULED, $post_id, $timestamp );

		return true;
	}

	/**
	 * Return the optimal (hour, day-of-week) pair.
	 *
	 * @return array{int, int}  [hour (0–23), dow (0 Sun … 6 Sat)]
	 */
	public function get_optimal_slot(): array {
		$analysis = $this->get_analysis();
		return [ (int) $analysis['optimal_hour'], (int) $analysis['optimal_dow'] ];
	}

	// -----------------------------------------------------------------------
	// Analysis engine
	// -----------------------------------------------------------------------

	/**
	 * Fetch GA4 data, aggregate by hour/dow, and return the scored analysis.
	 *
	 * Falls back to static defaults when GA4 is not configured.
	 *
	 * @return array{hour_scores: float[], dow_scores: float[], optimal_hour: int, optimal_dow: int}
	 */
	public function compute_analysis(): array {
		$hour_raw = array_fill( 0, self::HOURS_IN_DAY, 0.0 );
		$dow_raw  = array_fill( 0, self::DAYS_IN_WEEK, 0.0 );

		if ( $this->ga4->is_configured() ) {
			$days   = (int) get_option( self::OPTION_LOOKBACK_DAYS, self::DEFAULT_LOOKBACK_DAYS );
			$report = $this->fetch_ga4_hourly_data( $days );
			[ $hour_raw, $dow_raw ] = $this->aggregate_engagement( $report );
		}

		$hour_scores = $this->normalise( $hour_raw );
		$dow_scores  = $this->normalise( $dow_raw );

		[ $optimal_hour, $optimal_dow ] = $this->find_optimal_slot( $hour_scores, $dow_scores );

		return [
			'hour_scores'  => $hour_scores,
			'dow_scores'   => $dow_scores,
			'optimal_hour' => $optimal_hour,
			'optimal_dow'  => $optimal_dow,
		];
	}

	/**
	 * Aggregate a GA4 report into per-hour and per-DoW engagement totals.
	 *
	 * GA4 report rows expected: [ hour (string "0"–"23"), dayOfWeek, sessions, engagedSessions ]
	 *
	 * @param array $report Raw GA4 report array (may be empty).
	 * @return array{float[], float[]}  [hour_raw, dow_raw] each indexed 0…N-1.
	 */
	public function aggregate_engagement( array $report ): array {
		$hour_raw = array_fill( 0, self::HOURS_IN_DAY, 0.0 );
		$dow_raw  = array_fill( 0, self::DAYS_IN_WEEK, 0.0 );

		$rows = $this->ga4->extract_rows( $report );

		foreach ( $rows as $row ) {
			// Row structure: [hour, dayOfWeek, sessions, engagedSessions]
			if ( count( $row ) < 4 ) {
				continue;
			}

			$hour     = (int) $row[0];
			$dow      = (int) $row[1];
			$sessions = (float) $row[2];
			$engaged  = (float) $row[3];

			if ( $hour < 0 || $hour >= self::HOURS_IN_DAY ) {
				continue;
			}
			if ( $dow < 0 || $dow >= self::DAYS_IN_WEEK ) {
				continue;
			}

			// Engagement signal: prefer engaged sessions; fall back to raw sessions.
			$signal = $engaged > 0.0 ? $engaged : $sessions;

			$hour_raw[ $hour ] += $signal;
			$dow_raw[ $dow ]   += $signal;
		}

		return [ $hour_raw, $dow_raw ];
	}

	/**
	 * Find the (hour, dow) combination with the highest combined normalised score.
	 *
	 * @param float[] $hour_scores Normalised 0–100 per hour (24 items).
	 * @param float[] $dow_scores  Normalised 0–100 per dow (7 items).
	 * @return array{int, int}     [best_hour, best_dow]
	 */
	public function find_optimal_slot( array $hour_scores, array $dow_scores ): array {
		$fallback_hour = (int) get_option( self::OPTION_FALLBACK_HOUR, self::DEFAULT_FALLBACK_HOUR );
		$fallback_dow  = (int) get_option( self::OPTION_FALLBACK_DOW,  self::DEFAULT_FALLBACK_DOW );

		// If there is no engagement signal at all, return the configured fallback.
		if ( array_sum( $hour_scores ) <= 0.0 || array_sum( $dow_scores ) <= 0.0 ) {
			return [ $fallback_hour, $fallback_dow ];
		}

		$best_hour  = $fallback_hour;
		$best_dow   = $fallback_dow;
		$best_score = -1.0;

		foreach ( $hour_scores as $h => $hs ) {
			foreach ( $dow_scores as $d => $ds ) {
				$combined = self::HOUR_WEIGHT * $hs + self::DOW_WEIGHT * $ds;
				if ( $combined > $best_score ) {
					$best_score = $combined;
					$best_hour  = $h;
					$best_dow   = $d;
				}
			}
		}

		return [ $best_hour, $best_dow ];
	}

	/**
	 * Normalise an array of floats to a 0–100 scale.
	 *
	 * @param float[] $values
	 * @return float[]
	 */
	public function normalise( array $values ): array {
		$max = max( $values );
		if ( $max <= 0.0 ) {
			return array_fill( 0, count( $values ), 0.0 );
		}
		return array_map( fn( float $v ): float => round( ( $v / $max ) * 100.0, 2 ), $values );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Fetch hourly + day-of-week engagement data from GA4.
	 *
	 * @param int $days Lookback window.
	 * @return array    Raw GA4 report.
	 */
	private function fetch_ga4_hourly_data( int $days ): array {
		try {
			return $this->ga4->run_report(
				[ 'hour', 'dayOfWeek' ],
				[ 'sessions', 'engagedSessions' ],
				"{$days}daysAgo",
				'today'
			);
		} catch ( \Throwable $e ) {
			return [];
		}
	}

	/**
	 * Return the site's configured timezone as a \DateTimeZone.
	 *
	 * @return \DateTimeZone
	 */
	private function get_site_timezone(): \DateTimeZone {
		$tz_string = (string) get_option( 'timezone_string', 'UTC' );
		if ( '' === $tz_string ) {
			// Fallback: construct from UTC offset.
			$offset    = (float) get_option( 'gmt_offset', 0 );
			$sign      = $offset >= 0 ? '+' : '-';
			$abs       = abs( $offset );
			$hours     = (int) $abs;
			$minutes   = (int) round( ( $abs - $hours ) * 60 );
			$tz_string = sprintf( '%s%02d:%02d', $sign, $hours, $minutes );
		}
		try {
			return new \DateTimeZone( $tz_string );
		} catch ( \Exception $e ) {
			return new \DateTimeZone( 'UTC' );
		}
	}

	/**
	 * Calculate the next \DateTimeImmutable occurrence of a given day-of-week and hour.
	 *
	 * If today IS the target dow AND the target hour is still in the future today,
	 * we return today at that hour.  Otherwise we advance to the next occurrence.
	 *
	 * @param \DateTimeImmutable $now Current datetime in site-local timezone.
	 * @param int                $dow 0 = Sunday … 6 = Saturday.
	 * @param int                $hour Hour of day (0–23).
	 * @return \DateTimeImmutable
	 */
	public function next_occurrence( \DateTimeImmutable $now, int $dow, int $hour ): \DateTimeImmutable {
		// Candidate = today at the target hour.
		$candidate = $now->setTime( $hour, 0, 0 );

		$current_dow = (int) $now->format( 'w' ); // 0 = Sun … 6 = Sat.
		$days_ahead  = ( $dow - $current_dow + 7 ) % 7;

		if ( $days_ahead > 0 ) {
			$candidate = $candidate->modify( "+{$days_ahead} days" );
		} elseif ( $candidate <= $now ) {
			// Same dow but hour has passed — advance by one week.
			$candidate = $candidate->modify( '+7 days' );
		}

		return $candidate;
	}

	/**
	 * Check whether a post with the given ID exists.
	 *
	 * @param int $post_id
	 * @return bool
	 */
	private function post_exists( int $post_id ): bool {
		return $post_id > 0 && '' !== (string) get_post_field( 'post_status', $post_id );
	}
}
