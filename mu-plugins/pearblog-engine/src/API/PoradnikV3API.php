<?php
/**
 * Poradnik V3 REST API - Calculator and pricing endpoints
 *
 * REST API endpoints for:
 * - Calculator submissions
 * - Live pricing data
 * - Conversion tracking
 * - A/B test variant assignment
 *
 * @package PearBlogEngine\API
 * @version 3.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

use PearBlogEngine\Content\SmartCalculatorEngine;
use PearBlogEngine\Content\LivePricingDataLayer;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Poradnik V3 API Controller
 */
class PoradnikV3API extends WP_REST_Controller {

	/**
	 * API namespace.
	 */
	private const NAMESPACE = 'pearblog/v3';

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Calculator submission
		register_rest_route(
			self::NAMESPACE,
			'/calculator/calculate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'calculate' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'service' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'metraz' => [
						'required'          => true,
						'type'              => 'number',
						'minimum'           => 10,
						'maximum'           => 1000,
					],
					'standard' => [
						'type'              => 'string',
						'enum'              => [ 'podstawowy', 'sredni', 'premium' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					'lokalizacja' => [
						'type'              => 'string',
						'enum'              => [ 'miasto', 'przedmiescia', 'wies' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					'typ' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// Get live pricing
		register_rest_route(
			self::NAMESPACE,
			'/pricing/(?P<service>[a-z0-9\-]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_pricing' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'service' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'standard' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'lokalizacja' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'days' => [
						'type'    => 'integer',
						'minimum' => 7,
						'maximum' => 365,
						'default' => 90,
					],
				],
			]
		);

		// Get pricing trend
		register_rest_route(
			self::NAMESPACE,
			'/pricing/(?P<service>[a-z0-9\-]+)/trend',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_pricing_trend' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'service' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'days' => [
						'type'    => 'integer',
						'minimum' => 7,
						'maximum' => 90,
						'default' => 30,
					],
				],
			]
		);

		// Track conversion event
		register_rest_route(
			self::NAMESPACE,
			'/tracking/event',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'track_event' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'event_type' => [
						'required'          => true,
						'type'              => 'string',
						'enum'              => [ 'page_view', 'calculator_use', 'form_view', 'form_submit', 'cta_click' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					'service' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'event_data' => [
						'type' => 'object',
					],
				],
			]
		);

		// Get A/B test variant
		register_rest_route(
			self::NAMESPACE,
			'/abtest/variant',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_ab_variant' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'test_name' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'service' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Calculate cost endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function calculate( WP_REST_Request $request ) {
		$service = $request->get_param( 'service' );
		$inputs = [
			'metraz'      => $request->get_param( 'metraz' ),
			'standard'    => $request->get_param( 'standard' ) ?? 'sredni',
			'lokalizacja' => $request->get_param( 'lokalizacja' ) ?? 'przedmiescia',
			'typ'         => $request->get_param( 'typ' ) ?? '',
		];

		$result = SmartCalculatorEngine::calculate( $service, $inputs );

		if ( ! $result ) {
			return new WP_Error(
				'calculation_failed',
				'Nie udało się obliczyć kosztu. Sprawdź wprowadzone dane.',
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $result,
			],
			200
		);
	}

	/**
	 * Get pricing data endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function get_pricing( WP_REST_Request $request ) {
		$service = $request->get_param( 'service' );
		$filters = [
			'standard'    => $request->get_param( 'standard' ),
			'lokalizacja' => $request->get_param( 'lokalizacja' ),
			'days'        => $request->get_param( 'days' ) ?? 90,
		];

		$pricing = LivePricingDataLayer::get_live_pricing( $service, $filters );

		if ( ! $pricing ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Brak wystarczających danych cenowych dla tego serwisu.',
				],
				200
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $pricing,
			],
			200
		);
	}

	/**
	 * Get pricing trend endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function get_pricing_trend( WP_REST_Request $request ) {
		$service = $request->get_param( 'service' );
		$days = $request->get_param( 'days' ) ?? 30;

		$trend = LivePricingDataLayer::get_pricing_trend( $service, $days );

		if ( ! $trend ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Brak wystarczających danych do analizy trendu.',
				],
				200
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $trend,
			],
			200
		);
	}

	/**
	 * Track conversion event endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function track_event( WP_REST_Request $request ) {
		global $wpdb;

		$event_type = $request->get_param( 'event_type' );
		$service = $request->get_param( 'service' );
		$event_data = $request->get_param( 'event_data' );

		$session_id = $this->get_session_id();

		$table = $wpdb->prefix . 'pearblog_conversion_events';

		$inserted = $wpdb->insert(
			$table,
			[
				'session_id'   => $session_id,
				'event_type'   => $event_type,
				'event_data'   => $event_data ? wp_json_encode( $event_data ) : null,
				'page_url'     => $request->get_param( 'page_url' ) ?? $_SERVER['HTTP_REFERER'] ?? '',
				'service'      => $service,
				'ab_variant'   => $request->get_param( 'ab_variant' ),
				'user_id'      => get_current_user_id() ?: null,
				'ip_address'   => $this->get_client_ip(),
				'user_agent'   => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 ),
				'referrer'     => $_SERVER['HTTP_REFERER'] ?? null,
				'utm_source'   => $request->get_param( 'utm_source' ),
				'utm_medium'   => $request->get_param( 'utm_medium' ),
				'utm_campaign' => $request->get_param( 'utm_campaign' ),
				'created_at'   => current_time( 'mysql' ),
			],
			[
				'%s', '%s', '%s', '%s', '%s', '%s', '%d',
				'%s', '%s', '%s', '%s', '%s', '%s', '%s',
			]
		);

		return new WP_REST_Response(
			[
				'success' => (bool) $inserted,
				'message' => $inserted ? 'Event tracked successfully.' : 'Failed to track event.',
			],
			200
		);
	}

	/**
	 * Get A/B test variant endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_ab_variant( WP_REST_Request $request ) {
		$test_name = $request->get_param( 'test_name' );
		$service = $request->get_param( 'service' );

		$variant = $this->assign_ab_variant( $test_name, $service );

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => [
					'test_name'    => $test_name,
					'variant_name' => $variant['variant_name'],
					'config'       => $variant['config'],
				],
			],
			200
		);
	}

	/**
	 * Assign A/B test variant to session.
	 *
	 * @param string $test_name Test name.
	 * @param string $service   Service slug.
	 * @return array Variant data.
	 */
	private function assign_ab_variant( string $test_name, string $service = '' ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'pearblog_ab_test_variants';

		// Get active variants for this test
		$where = [ 'test_name = %s', 'is_active = 1' ];
		$params = [ $test_name ];

		if ( $service ) {
			$where[] = '(service = %s OR service IS NULL)';
			$params[] = $service;
		}

		$where_clause = implode( ' AND ', $where );

		$variants = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_clause} ORDER BY traffic_allocation DESC",
				...$params
			),
			ARRAY_A
		);

		if ( empty( $variants ) ) {
			// No variants found - return default
			return [
				'variant_name' => 'control',
				'config'       => [],
			];
		}

		// Weighted random selection based on traffic_allocation
		$session_id = $this->get_session_id();
		$hash = crc32( $session_id . $test_name );
		$random = abs( $hash % 100 );

		$cumulative = 0;
		foreach ( $variants as $variant ) {
			$cumulative += (int) $variant['traffic_allocation'];
			if ( $random < $cumulative ) {
				// Increment views
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$table} SET views = views + 1 WHERE id = %d",
					$variant['id']
				) );

				return [
					'variant_name' => $variant['variant_name'],
					'config'       => json_decode( $variant['config'] ?? '{}', true ),
				];
			}
		}

		// Fallback to first variant
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$table} SET views = views + 1 WHERE id = %d",
			$variants[0]['id']
		) );

		return [
			'variant_name' => $variants[0]['variant_name'],
			'config'       => json_decode( $variants[0]['config'] ?? '{}', true ),
		];
	}

	/**
	 * Get or create session ID.
	 *
	 * @return string Session ID.
	 */
	private function get_session_id(): string {
		if ( isset( $_COOKIE['pearblog_session_id'] ) ) {
			return sanitize_text_field( $_COOKIE['pearblog_session_id'] );
		}

		$session_id = 'pb_' . uniqid( '', true );
		setcookie( 'pearblog_session_id', $session_id, time() + ( 30 * DAY_IN_SECONDS ), '/', '', is_ssl(), true );

		return $session_id;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string IP address.
	 */
	private function get_client_ip(): string {
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
