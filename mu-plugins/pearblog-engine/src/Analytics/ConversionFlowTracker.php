<?php
/**
 * Conversion Flow Tracker - User journey optimization
 *
 * Tracks user behavior through V3 landing pages:
 * - Page views
 * - Calculator interactions
 * - Form views and submissions
 * - CTA clicks
 * - Time on page
 * - Scroll depth
 *
 * @package PearBlogEngine\Analytics
 * @version 3.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * Conversion Flow Tracker
 *
 * Handles client-side and server-side conversion tracking.
 */
class ConversionFlowTracker {

	/**
	 * Initialize tracking hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Enqueue tracking script
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_tracking_script' ] );

		// Track server-side page view
		add_action( 'wp', [ __CLASS__, 'track_page_view' ] );
	}

	/**
	 * Enqueue tracking JavaScript.
	 *
	 * @return void
	 */
	public static function enqueue_tracking_script(): void {
		// Only load on V3 landing pages
		if ( ! self::is_v3_landing_page() ) {
			return;
		}

		wp_enqueue_script(
			'pearblog-v3-tracker',
			plugins_url( 'assets/js/v3-conversion-tracker.js', PEARBLOG_PLUGIN_FILE ),
			[ 'jquery' ],
			'3.0.0',
			true
		);

		// Localize with API endpoints and session data
		wp_localize_script(
			'pearblog-v3-tracker',
			'pearblogV3Tracker',
			[
				'apiUrl'       => rest_url( 'pearblog/v3/tracking/event' ),
				'sessionId'    => self::get_session_id(),
				'service'      => self::get_current_service(),
				'abVariant'    => self::get_ab_variant(),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'utmSource'    => $_GET['utm_source'] ?? '',
				'utmMedium'    => $_GET['utm_medium'] ?? '',
				'utmCampaign'  => $_GET['utm_campaign'] ?? '',
			]
		);
	}

	/**
	 * Track server-side page view.
	 *
	 * @return void
	 */
	public static function track_page_view(): void {
		if ( ! self::is_v3_landing_page() ) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'pearblog_conversion_events';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
			return;
		}

		$wpdb->insert(
			$table,
			[
				'session_id'   => self::get_session_id(),
				'event_type'   => 'page_view',
				'page_url'     => self::get_current_url(),
				'service'      => self::get_current_service(),
				'ab_variant'   => self::get_ab_variant(),
				'user_id'      => get_current_user_id() ?: null,
				'ip_address'   => self::get_client_ip(),
				'user_agent'   => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 ),
				'referrer'     => $_SERVER['HTTP_REFERER'] ?? null,
				'utm_source'   => $_GET['utm_source'] ?? null,
				'utm_medium'   => $_GET['utm_medium'] ?? null,
				'utm_campaign' => $_GET['utm_campaign'] ?? null,
				'created_at'   => current_time( 'mysql' ),
			],
			[
				'%s', '%s', '%s', '%s', '%s', '%d',
				'%s', '%s', '%s', '%s', '%s', '%s', '%s',
			]
		);
	}

	/**
	 * Get conversion funnel for session.
	 *
	 * @param string $session_id Session ID.
	 * @return array Funnel steps with timestamps.
	 */
	public static function get_session_funnel( string $session_id ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'pearblog_conversion_events';

		$events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, event_data, service, created_at
				FROM {$table}
				WHERE session_id = %s
				ORDER BY created_at ASC",
				$session_id
			),
			ARRAY_A
		);

		if ( empty( $events ) ) {
			return [];
		}

		// Build funnel stages
		$funnel = [
			'page_view'       => null,
			'calculator_use'  => null,
			'form_view'       => null,
			'form_submit'     => null,
			'converted'       => false,
		];

		foreach ( $events as $event ) {
			$event_type = $event['event_type'];

			if ( isset( $funnel[ $event_type ] ) && null === $funnel[ $event_type ] ) {
				$funnel[ $event_type ] = $event['created_at'];
			}

			if ( 'form_submit' === $event_type ) {
				$funnel['converted'] = true;
			}
		}

		return $funnel;
	}

	/**
	 * Calculate conversion rate for service.
	 *
	 * @param string $service Service slug.
	 * @param int    $days    Days to analyze (default: 30).
	 * @return array Conversion metrics:
	 *               - total_views: int
	 *               - calculator_uses: int
	 *               - form_views: int
	 *               - form_submits: int
	 *               - conversion_rate: float
	 */
	public static function get_conversion_metrics( string $service, int $days = 30 ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'pearblog_conversion_events';
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$metrics = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(DISTINCT CASE WHEN event_type = 'page_view' THEN session_id END) as total_views,
					COUNT(DISTINCT CASE WHEN event_type = 'calculator_use' THEN session_id END) as calculator_uses,
					COUNT(DISTINCT CASE WHEN event_type = 'form_view' THEN session_id END) as form_views,
					COUNT(DISTINCT CASE WHEN event_type = 'form_submit' THEN session_id END) as form_submits
				FROM {$table}
				WHERE service = %s AND created_at >= %s",
				$service,
				$cutoff
			),
			ARRAY_A
		);

		if ( ! $metrics || $metrics['total_views'] == 0 ) {
			return [
				'total_views'     => 0,
				'calculator_uses' => 0,
				'form_views'      => 0,
				'form_submits'    => 0,
				'conversion_rate' => 0.0,
			];
		}

		$conversion_rate = ( (int) $metrics['form_submits'] / (int) $metrics['total_views'] ) * 100;

		return [
			'total_views'     => (int) $metrics['total_views'],
			'calculator_uses' => (int) $metrics['calculator_uses'],
			'form_views'      => (int) $metrics['form_views'],
			'form_submits'    => (int) $metrics['form_submits'],
			'conversion_rate' => round( $conversion_rate, 2 ),
		];
	}

	/**
	 * Get drop-off points in funnel.
	 *
	 * @param string $service Service slug.
	 * @param int    $days    Days to analyze.
	 * @return array Drop-off percentages at each stage.
	 */
	public static function get_funnel_dropoff( string $service, int $days = 30 ): array {
		$metrics = self::get_conversion_metrics( $service, $days );

		if ( $metrics['total_views'] == 0 ) {
			return [];
		}

		$views = (int) $metrics['total_views'];

		return [
			'page_to_calculator' => [
				'reached'  => $metrics['calculator_uses'],
				'rate'     => round( ( $metrics['calculator_uses'] / $views ) * 100, 2 ),
				'dropoff'  => round( ( 1 - ( $metrics['calculator_uses'] / $views ) ) * 100, 2 ),
			],
			'calculator_to_form' => [
				'reached'  => $metrics['form_views'],
				'rate'     => $metrics['calculator_uses'] > 0
					? round( ( $metrics['form_views'] / $metrics['calculator_uses'] ) * 100, 2 )
					: 0,
				'dropoff'  => $metrics['calculator_uses'] > 0
					? round( ( 1 - ( $metrics['form_views'] / $metrics['calculator_uses'] ) ) * 100, 2 )
					: 0,
			],
			'form_to_submit' => [
				'reached'  => $metrics['form_submits'],
				'rate'     => $metrics['form_views'] > 0
					? round( ( $metrics['form_submits'] / $metrics['form_views'] ) * 100, 2 )
					: 0,
				'dropoff'  => $metrics['form_views'] > 0
					? round( ( 1 - ( $metrics['form_submits'] / $metrics['form_views'] ) ) * 100, 2 )
					: 0,
			],
		];
	}

	/**
	 * Check if current page is V3 landing page.
	 *
	 * @return bool True if V3 landing page.
	 */
	private static function is_v3_landing_page(): bool {
		// Check for V3 template or specific page meta
		return is_singular()
			&& (
				get_post_meta( get_the_ID(), '_pearblog_template_version', true ) === 'v3'
				|| strpos( get_permalink(), 'ile-kosztuje' ) !== false
			);
	}

	/**
	 * Get current service from URL or post meta.
	 *
	 * @return string Service slug.
	 */
	private static function get_current_service(): string {
		if ( is_singular() ) {
			$service = get_post_meta( get_the_ID(), '_pearblog_service', true );
			if ( $service ) {
				return $service;
			}

			// Parse from slug
			$slug = get_post_field( 'post_name', get_the_ID() );
			if ( preg_match( '/ile-kosztuje-([a-z0-9\-]+)/', $slug, $matches ) ) {
				return $matches[1];
			}
		}

		return 'unknown';
	}

	/**
	 * Get current URL.
	 *
	 * @return string Full URL.
	 */
	private static function get_current_url(): string {
		return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Get or create session ID.
	 *
	 * @return string Session ID.
	 */
	private static function get_session_id(): string {
		if ( isset( $_COOKIE['pearblog_session_id'] ) ) {
			return sanitize_text_field( $_COOKIE['pearblog_session_id'] );
		}

		$session_id = 'pb_' . uniqid( '', true );
		setcookie( 'pearblog_session_id', $session_id, time() + ( 30 * DAY_IN_SECONDS ), '/', '', is_ssl(), true );

		return $session_id;
	}

	/**
	 * Get A/B test variant for current session.
	 *
	 * @return string Variant name.
	 */
	private static function get_ab_variant(): string {
		// Check cookie or session storage
		if ( isset( $_COOKIE['pearblog_ab_variant'] ) ) {
			return sanitize_text_field( $_COOKIE['pearblog_ab_variant'] );
		}

		return 'control';
	}

	/**
	 * Get client IP address.
	 *
	 * @return string IP address.
	 */
	private static function get_client_ip(): string {
		$ip_keys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return 'unknown';
	}
}
