<?php
/**
 * Webhook system – dispatches configurable outbound HTTP events.
 *
 * Supported events:
 *   pearblog.article_published   – triggered after ContentPipeline completes
 *   pearblog.pipeline_error      – triggered on pipeline failure
 *   pearblog.quality_scored      – triggered after QualityScorer runs
 *   pearblog.content_refreshed   – triggered after ContentRefreshEngine refreshes a post
 *
 * Webhook endpoints are stored in WordPress option `pearblog_webhooks` as a
 * native PHP array of { url, events[], secret } objects.
 *
 * Payloads are signed with HMAC-SHA256 using the configured secret and sent
 * in the `X-PearBlog-Signature` header.
 *
 * @package PearBlogEngine\Webhook
 */

declare(strict_types=1);

namespace PearBlogEngine\Webhook;

/**
 * Registers WordPress hooks and delivers outbound webhook events.
 */
class WebhookManager {

	private const OPTION_KEY = 'pearblog_webhooks';

	/**
	 * Attach WordPress action hooks (called from Plugin::boot).
	 */
	public function register(): void {
		add_action( 'pearblog_pipeline_completed', [ $this, 'on_article_published' ], 20, 3 );
		add_action( 'pearblog_quality_scored',     [ $this, 'on_quality_scored'    ], 10, 2 );
		add_action( 'pearblog_content_refreshed',  [ $this, 'on_content_refreshed' ], 10, 2 );

		// REST routes for CRUD on webhook endpoints.
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	// -----------------------------------------------------------------------
	// WordPress action hooks
	// -----------------------------------------------------------------------

	public function on_article_published( int $post_id, string $topic, $context ): void {
		$this->dispatch( 'pearblog.article_published', [
			'post_id' => $post_id,
			'topic'   => $topic,
			'url'     => get_permalink( $post_id ),
			'title'   => get_the_title( $post_id ),
		] );
	}

	public function on_quality_scored( int $post_id, float $score ): void {
		$this->dispatch( 'pearblog.quality_scored', [
			'post_id' => $post_id,
			'score'   => $score,
			'url'     => get_permalink( $post_id ),
		] );
	}

	public function on_content_refreshed( int $post_id, string $title ): void {
		$this->dispatch( 'pearblog.content_refreshed', [
			'post_id' => $post_id,
			'title'   => $title,
			'url'     => get_permalink( $post_id ),
		] );
	}

	/**
	 * Manually dispatch a pipeline-error event.
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context.
	 */
	public function dispatch_error( string $message, array $context = [] ): void {
		$this->dispatch( 'pearblog.pipeline_error', array_merge(
			[ 'message' => $message ],
			$context
		) );
	}

	// -----------------------------------------------------------------------
	// Dispatch engine
	// -----------------------------------------------------------------------

	/**
	 * Find all webhooks subscribed to $event and deliver the payload.
	 *
	 * @param string $event   Event name (e.g. pearblog.article_published).
	 * @param array  $payload Data to send as JSON body.
	 */
	public function dispatch( string $event, array $payload ): void {
		$hooks = $this->load_hooks();

		if ( empty( $hooks ) ) {
			return;
		}

		$body = wp_json_encode( array_merge( $payload, [
			'event'     => $event,
			'site_url'  => get_site_url(),
			'timestamp' => time(),
		] ) );

		foreach ( $hooks as $hook ) {
			if ( ! $this->hook_subscribes( $hook, $event ) ) {
				continue;
			}

			$url     = $hook['url']    ?? '';
			$secret  = $hook['secret'] ?? '';

			if ( '' === $url ) {
				continue;
			}

			$signature = '' !== $secret
				? 'sha256=' . hash_hmac( 'sha256', $body, $secret )
				: '';

			wp_remote_post( $url, [
				'timeout'  => 5,
				'blocking' => false, // Fire-and-forget.
				'headers'  => array_filter( [
					'Content-Type'           => 'application/json',
					'X-PearBlog-Event'       => $event,
					'X-PearBlog-Signature'   => $signature,
				] ),
				'body'     => $body,
			] );
		}
	}

	// -----------------------------------------------------------------------
	// REST API for CRUD on webhooks
	// -----------------------------------------------------------------------

	public function register_routes(): void {
		register_rest_route( 'pearblog/v1', '/webhooks', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'api_list' ],
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'api_create' ],
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
				'args'                => [
					'url'    => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'esc_url_raw' ],
					'events' => [ 'type' => 'array',  'required' => true, 'items' => [ 'type' => 'string' ] ],
					'secret' => [ 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
				],
			],
		] );

		register_rest_route( 'pearblog/v1', '/webhooks/(?P<id>[a-f0-9]{8})', [
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'api_delete' ],
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			],
		] );
	}

	public function api_list( \WP_REST_Request $req ): \WP_REST_Response {
		$hooks = $this->load_hooks();
		// Mask secrets in API response.
		foreach ( $hooks as &$hook ) {
			$hook['secret'] = '' !== $hook['secret'] ? '••••••' : '';
		}
		unset( $hook );
		return new \WP_REST_Response( $hooks, 200 );
	}

	public function api_create( \WP_REST_Request $req ): \WP_REST_Response {
		$hooks  = $this->load_hooks();
		$new_id = substr( md5( uniqid( '', true ) ), 0, 8 );

		$hooks[] = [
			'id'     => $new_id,
			'url'    => esc_url_raw( $req->get_param( 'url' ) ),
			'events' => array_map( 'sanitize_text_field', (array) $req->get_param( 'events' ) ),
			'secret' => sanitize_text_field( $req->get_param( 'secret' ) ),
		];

		$this->save_hooks( $hooks );

		return new \WP_REST_Response( [ 'id' => $new_id, 'success' => true ], 201 );
	}

	public function api_delete( \WP_REST_Request $req ): \WP_REST_Response {
		$id    = sanitize_text_field( $req->get_param( 'id' ) );
		$hooks = array_values( array_filter(
			$this->load_hooks(),
			fn( $h ) => ( $h['id'] ?? '' ) !== $id
		) );
		$this->save_hooks( $hooks );
		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	// -----------------------------------------------------------------------
	// Storage helpers
	// -----------------------------------------------------------------------

	/**
	 * @return array<array{id: string, url: string, events: string[], secret: string}>
	 */
	private function load_hooks(): array {
		$raw = get_option( self::OPTION_KEY, [] );
		return is_array( $raw ) ? $raw : [];
	}

	private function save_hooks( array $hooks ): void {
		update_option( self::OPTION_KEY, array_values( $hooks ) );
	}

	private function hook_subscribes( array $hook, string $event ): bool {
		$events = $hook['events'] ?? [];
		return in_array( '*', $events, true ) || in_array( $event, $events, true );
	}
}
