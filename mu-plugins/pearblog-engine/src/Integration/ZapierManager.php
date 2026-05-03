<?php
/**
 * Zapier / Make.com Integration Manager.
 *
 * Exposes PearBlog Engine events as Zapier-compatible webhook trigger events
 * and provides action endpoints so Zapier/Make can call back into the engine.
 *
 * Trigger events (fired as outgoing webhooks):
 *   article.published    – payload: {post_id, title, url, quality_score}
 *   lead.captured        – payload: {lead_id, category, email, phone}
 *   quality.failed       – payload: {post_id, title, score, threshold}
 *
 * Action REST endpoints (incoming from Zapier):
 *   POST /pearblog/v1/zapier/topic/add       – add topic to queue
 *   POST /pearblog/v1/zapier/pipeline/trigger – trigger pipeline for one topic
 *
 * Configuration (WP options):
 *   pearblog_zapier_webhook_url   – outgoing webhook URL (Zapier/Make)
 *   pearblog_zapier_secret        – shared secret for verifying incoming calls
 *   pearblog_zapier_events        – comma-separated list of enabled events
 *
 * @package PearBlogEngine\Integration
 */

declare(strict_types=1);

namespace PearBlogEngine\Integration;

use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Bridges PearBlog Engine events with Zapier and Make.com.
 */
class ZapierManager {

	/** WP option keys. */
	public const OPTION_WEBHOOK_URL = 'pearblog_zapier_webhook_url';
	public const OPTION_SECRET      = 'pearblog_zapier_secret';
	public const OPTION_EVENTS      = 'pearblog_zapier_events';

	/** Default enabled events. */
	private const DEFAULT_EVENTS = [ 'article.published', 'quality.failed' ];

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'pearblog_pipeline_completed', [ $this, 'on_article_published' ], 10, 2 );
		add_action( 'pearblog_quality_scored', [ $this, 'on_quality_scored' ], 10, 2 );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/zapier/topic/add', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_add_topic' ],
			'permission_callback' => [ $this, 'zapier_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/zapier/pipeline/trigger', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_trigger_pipeline' ],
			'permission_callback' => [ $this, 'zapier_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Outgoing webhooks
	// -----------------------------------------------------------------------

	/**
	 * Fire `article.published` event on pipeline completion.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $topic   Article topic.
	 */
	public function on_article_published( int $post_id, string $topic ): void {
		if ( ! $this->is_event_enabled( 'article.published' ) ) {
			return;
		}

		$quality_score = (int) get_post_meta( $post_id, 'pearblog_quality_score', true );

		$this->dispatch( 'article.published', [
			'post_id'       => $post_id,
			'title'         => get_the_title( $post_id ),
			'url'           => get_permalink( $post_id ),
			'topic'         => $topic,
			'quality_score' => $quality_score,
			'published_at'  => gmdate( 'c' ),
		] );
	}

	/**
	 * Fire `quality.failed` event when quality score is below threshold.
	 *
	 * @param int $post_id      Post ID.
	 * @param int $quality_score Quality score (0–100).
	 */
	public function on_quality_scored( int $post_id, int $quality_score ): void {
		$threshold = (int) get_option( 'pearblog_quality_threshold', 60 );

		if ( ! $this->is_event_enabled( 'quality.failed' ) ) {
			return;
		}

		if ( $quality_score >= $threshold ) {
			return;
		}

		$this->dispatch( 'quality.failed', [
			'post_id'       => $post_id,
			'title'         => get_the_title( $post_id ),
			'url'           => get_permalink( $post_id ),
			'score'         => $quality_score,
			'threshold'     => $threshold,
			'flagged_at'    => gmdate( 'c' ),
		] );
	}

	/**
	 * Send event payload to the configured webhook URL.
	 *
	 * @param string              $event   Event name.
	 * @param array<string,mixed> $payload Event payload.
	 */
	public function dispatch( string $event, array $payload ): void {
		$webhook_url = (string) get_option( self::OPTION_WEBHOOK_URL, '' );
		if ( '' === $webhook_url ) {
			return;
		}

		$body = wp_json_encode( array_merge( $payload, [
			'event'     => $event,
			'site_url'  => get_site_url(),
			'timestamp' => time(),
		] ) );

		// Sign the payload with HMAC-SHA256.
		$secret    = (string) get_option( self::OPTION_SECRET, '' );
		$signature = '' !== $secret ? hash_hmac( 'sha256', (string) $body, $secret ) : '';

		$headers = [
			'Content-Type'       => 'application/json',
			'X-PearBlog-Event'   => $event,
		];

		if ( '' !== $signature ) {
			$headers['X-PearBlog-Signature'] = $signature;
		}

		$response = wp_remote_post( $webhook_url, [
			'headers' => $headers,
			'body'    => $body,
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			error_log( "PearBlog Zapier: Failed to dispatch '{$event}' – " . $response->get_error_message() );
		}
	}

	// -----------------------------------------------------------------------
	// Incoming action endpoints (from Zapier)
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_add_topic( \WP_REST_Request $request ) {
		$topic = sanitize_text_field( (string) $request->get_param( 'topic' ) );
		if ( '' === $topic ) {
			return new \WP_Error( 'missing_topic', 'Topic is required.', [ 'status' => 400 ] );
		}

		$context = TenantContext::for_current_site();
		$queue   = new TopicQueue( $context->site_id );
		$queue->add( $topic );

		return new \WP_REST_Response( [
			'success' => true,
			'topic'   => $topic,
			'message' => 'Topic added to queue.',
		], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_trigger_pipeline( \WP_REST_Request $request ) {
		$topic = sanitize_text_field( (string) $request->get_param( 'topic' ) );

		$context  = TenantContext::for_current_site();
		$pipeline = new \PearBlogEngine\Pipeline\ContentPipeline( $context );

		// If topic provided, pre-add it.
		if ( '' !== $topic ) {
			( new TopicQueue( $context->site_id ) )->add( $topic );
		}

		try {
			$result = $pipeline->run();
			return new \WP_REST_Response( [ 'success' => true, 'result' => $result ], 200 );
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'pipeline_failed', $e->getMessage(), [ 'status' => 500 ] );
		}
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Check if a specific event type is enabled.
	 *
	 * @param string $event Event name.
	 * @return bool
	 */
	private function is_event_enabled( string $event ): bool {
		$raw     = (string) get_option( self::OPTION_EVENTS, implode( ',', self::DEFAULT_EVENTS ) );
		$enabled = array_map( 'trim', explode( ',', $raw ) );
		return in_array( $event, $enabled, true );
	}

	/**
	 * Permission callback using shared secret or manage_options.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function zapier_permission( \WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$secret = (string) get_option( self::OPTION_SECRET, '' );
		if ( '' === $secret ) {
			return false;
		}

		$provided = $request->get_header( 'x-pearblog-secret' )
			?? (string) ( $request->get_param( 'secret' ) ?? '' );

		return hash_equals( $secret, (string) $provided );
	}
}
