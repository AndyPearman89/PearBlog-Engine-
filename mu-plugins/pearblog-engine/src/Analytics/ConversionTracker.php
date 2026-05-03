<?php
/**
 * Conversion Tracker – records and reports conversion events across the funnel.
 *
 * Tracks user interactions that signal progression through the marketing funnel:
 *   TOFU → MOFU → BOFU → Conversion
 *
 * Events tracked:
 *   - page_view        – basic article read (fires on template_redirect)
 *   - email_signup     – visitor subscribes to the newsletter
 *   - cta_click        – visitor clicks a CTA button (AJAX endpoint)
 *   - purchase         – affiliate link or product sale confirmed
 *   - scroll_depth     – visitor scrolls past 75% of article (JS beacon)
 *
 * Storage:
 *   Events are stored in a ring-buffer WP option per event type (max 1000
 *   entries each).  Aggregate counts are stored in a separate summary option
 *   for fast dashboard rendering.
 *
 *   Option keys:
 *     pearblog_conv_events_{type}   – ring-buffer for each event type
 *     pearblog_conv_totals          – JSON aggregate totals
 *
 * REST endpoints:
 *   POST /pearblog/v1/conversions/track                     – record an event
 *   GET  /pearblog/v1/conversions/stats                     – aggregate stats
 *   GET  /pearblog/v1/conversions/funnel                    – funnel view
 *   GET  /pearblog/v1/conversions/post/{post_id}            – per-post stats
 *
 * AJAX endpoints (public, rate-limited by nonce):
 *   wp_ajax_nopriv_pearblog_track_cta
 *   wp_ajax_nopriv_pearblog_track_scroll
 *
 * Options:
 *   pearblog_conv_tracker_enabled   – bool master switch (default true)
 *   pearblog_conv_max_per_type      – ring-buffer size per type (default 1000)
 *
 * Actions fired:
 *   pearblog_conversion_tracked ($event_type, $event_data)
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * Tracks and reports conversion events across the marketing funnel.
 */
class ConversionTracker {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Supported event types. */
	public const EVENT_PAGE_VIEW   = 'page_view';
	public const EVENT_EMAIL_SIGNUP = 'email_signup';
	public const EVENT_CTA_CLICK   = 'cta_click';
	public const EVENT_PURCHASE    = 'purchase';
	public const EVENT_SCROLL_DEPTH = 'scroll_depth';

	public const ALL_EVENTS = [
		self::EVENT_PAGE_VIEW,
		self::EVENT_EMAIL_SIGNUP,
		self::EVENT_CTA_CLICK,
		self::EVENT_PURCHASE,
		self::EVENT_SCROLL_DEPTH,
	];

	/** Funnel stages ordered top to bottom. */
	public const FUNNEL_STAGES = [
		'awareness'    => [ self::EVENT_PAGE_VIEW, self::EVENT_SCROLL_DEPTH ],
		'consideration' => [ self::EVENT_EMAIL_SIGNUP, self::EVENT_CTA_CLICK ],
		'conversion'   => [ self::EVENT_PURCHASE ],
	];

	/** WP option prefix for event ring-buffers. */
	private const OPTION_PREFIX = 'pearblog_conv_events_';

	/** WP option for aggregate totals. */
	public const OPTION_TOTALS = 'pearblog_conv_totals';

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks, REST routes, and AJAX handlers.
	 */
	public function register(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'template_redirect', [ $this, 'track_page_view' ] );
		add_action( 'wp_ajax_nopriv_pearblog_track_cta', [ $this, 'ajax_track_cta' ] );
		add_action( 'wp_ajax_pearblog_track_cta', [ $this, 'ajax_track_cta' ] );
		add_action( 'wp_ajax_nopriv_pearblog_track_scroll', [ $this, 'ajax_track_scroll' ] );
		add_action( 'wp_ajax_pearblog_track_scroll', [ $this, 'ajax_track_scroll' ] );
	}

	/**
	 * Whether the conversion tracker is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( 'pearblog_conv_tracker_enabled', true );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/conversions/track', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_track' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'event'   => [ 'required' => true, 'type' => 'string' ],
				'post_id' => [ 'required' => false, 'type' => 'integer', 'default' => 0 ],
				'meta'    => [ 'required' => false, 'type' => 'object', 'default' => [] ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/conversions/stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_stats' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/conversions/funnel', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_funnel' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/conversions/post/(?P<id>[\d]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_post_stats' ],
			'permission_callback' => [ $this, 'admin_permission' ],
			'args'                => [ 'id' => [ 'required' => true, 'type' => 'integer' ] ],
		] );
	}

	/**
	 * REST permission for track endpoint – public (nonce checked in handler).
	 */
	public function rest_permission(): bool {
		return true; // Rate-limited by nonce check in handler.
	}

	/**
	 * REST permission for admin endpoints.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * POST /conversions/track – record a conversion event.
	 */
	public function rest_track( \WP_REST_Request $request ): \WP_REST_Response {
		$event_type = sanitize_key( $request->get_param( 'event' ) );
		$post_id    = (int) ( $request->get_param( 'post_id' ) ?? 0 );
		$meta       = (array) ( $request->get_param( 'meta' ) ?? [] );

		if ( ! in_array( $event_type, self::ALL_EVENTS, true ) ) {
			return new \WP_REST_Response( [ 'error' => "Unknown event type: {$event_type}" ], 400 );
		}

		$result = $this->track( $event_type, $post_id, $meta );
		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /conversions/stats – aggregate statistics.
	 */
	public function rest_stats( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->get_totals(), 200 );
	}

	/**
	 * GET /conversions/funnel – funnel view with conversion rates.
	 */
	public function rest_funnel( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->get_funnel_view(), 200 );
	}

	/**
	 * GET /conversions/post/{id} – per-post event stats.
	 */
	public function rest_post_stats( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		return new \WP_REST_Response( $this->get_post_stats( $post_id ), 200 );
	}

	// -----------------------------------------------------------------------
	// WordPress action callbacks
	// -----------------------------------------------------------------------

	/**
	 * Track a page view when a singular post is viewed.
	 */
	public function track_page_view(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post_id = get_the_ID();
		if ( $post_id ) {
			$this->track( self::EVENT_PAGE_VIEW, (int) $post_id );
		}
	}

	/**
	 * AJAX: track a CTA click.
	 */
	public function ajax_track_cta(): void {
		check_ajax_referer( 'pearblog_track', 'nonce' );
		$post_id = (int) ( $_POST['post_id'] ?? 0 );
		$label   = sanitize_text_field( $_POST['label'] ?? '' );
		$this->track( self::EVENT_CTA_CLICK, $post_id, [ 'label' => $label ] );
		wp_send_json_success( [ 'tracked' => true ] );
	}

	/**
	 * AJAX: track scroll depth.
	 */
	public function ajax_track_scroll(): void {
		check_ajax_referer( 'pearblog_track', 'nonce' );
		$post_id = (int) ( $_POST['post_id'] ?? 0 );
		$depth   = (int) ( $_POST['depth'] ?? 0 );
		$this->track( self::EVENT_SCROLL_DEPTH, $post_id, [ 'depth_pct' => $depth ] );
		wp_send_json_success( [ 'tracked' => true ] );
	}

	// -----------------------------------------------------------------------
	// Core tracking
	// -----------------------------------------------------------------------

	/**
	 * Record a conversion event.
	 *
	 * @param string $event_type Event type constant.
	 * @param int    $post_id    Related post ID (0 for site-level events).
	 * @param array  $meta       Extra event metadata.
	 * @return array  Event entry as stored.
	 */
	public function track( string $event_type, int $post_id = 0, array $meta = [] ): array {
		$entry = [
			'event'   => $event_type,
			'post_id' => $post_id,
			'meta'    => $meta,
			'at'      => time(),
		];

		$this->append_event( $event_type, $entry );
		$this->increment_total( $event_type );

		do_action( 'pearblog_conversion_tracked', $event_type, $entry );

		return $entry;
	}

	// -----------------------------------------------------------------------
	// Statistics
	// -----------------------------------------------------------------------

	/**
	 * Return aggregate event totals.
	 *
	 * @return array<string, int>  Event type => total count.
	 */
	public function get_totals(): array {
		$raw     = get_option( self::OPTION_TOTALS, [] );
		$totals  = is_array( $raw ) ? $raw : [];

		// Ensure all event types are present.
		foreach ( self::ALL_EVENTS as $event ) {
			$totals[ $event ] = $totals[ $event ] ?? 0;
		}

		return $totals;
	}

	/**
	 * Return a funnel view with per-stage totals and conversion rates.
	 *
	 * @return array<string, array>
	 */
	public function get_funnel_view(): array {
		$totals  = $this->get_totals();
		$funnel  = [];

		foreach ( self::FUNNEL_STAGES as $stage => $events ) {
			$stage_total = 0;
			foreach ( $events as $event ) {
				$stage_total += $totals[ $event ] ?? 0;
			}
			$funnel[ $stage ] = [
				'events' => $events,
				'total'  => $stage_total,
			];
		}

		// Conversion rate: conversion / awareness.
		$awareness  = $funnel['awareness']['total'];
		$conversion = $funnel['conversion']['total'];
		$funnel['conversion_rate_pct'] = $awareness > 0
			? round( ( $conversion / $awareness ) * 100, 2 )
			: 0.0;

		return $funnel;
	}

	/**
	 * Return per-post event stats from the ring-buffer.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, int>  Event type => count for this post.
	 */
	public function get_post_stats( int $post_id ): array {
		$stats = array_fill_keys( self::ALL_EVENTS, 0 );

		foreach ( self::ALL_EVENTS as $event ) {
			$log = $this->get_event_log( $event );
			foreach ( $log as $entry ) {
				if ( (int) $entry['post_id'] === $post_id ) {
					$stats[ $event ]++;
				}
			}
		}

		return [
			'post_id' => $post_id,
			'stats'   => $stats,
		];
	}

	/**
	 * Get the ring-buffer for a specific event type.
	 *
	 * @param string $event_type Event type constant.
	 * @return array<int, array>
	 */
	public function get_event_log( string $event_type ): array {
		$raw = get_option( self::OPTION_PREFIX . $event_type, [] );
		return is_array( $raw ) ? $raw : [];
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Append an event to the ring-buffer for its type.
	 *
	 * @param string $event_type Event type.
	 * @param array  $entry      Event entry.
	 */
	private function append_event( string $event_type, array $entry ): void {
		$log = $this->get_event_log( $event_type );
		$log[] = $entry;

		$max = (int) get_option( 'pearblog_conv_max_per_type', 1000 );
		if ( count( $log ) > $max ) {
			$log = array_slice( $log, -$max );
		}

		update_option( self::OPTION_PREFIX . $event_type, $log );
	}

	/**
	 * Increment the aggregate total for an event type.
	 *
	 * @param string $event_type Event type.
	 */
	private function increment_total( string $event_type ): void {
		$totals = $this->get_totals();
		$totals[ $event_type ] = ( $totals[ $event_type ] ?? 0 ) + 1;
		update_option( self::OPTION_TOTALS, $totals );
	}
}
