<?php
/**
 * Push Notification Publisher – web push and OneSignal/FCM integration.
 *
 * Sends push notifications to subscribers when new articles are published.
 * Supports segmentation by category so subscribers only receive relevant
 * notifications.
 *
 * Supported providers:
 *  - OneSignal (via REST API)
 *  - Firebase Cloud Messaging (FCM v1 API)
 *
 * Configuration (WP options):
 *   pearblog_push_enabled        – (bool) enable push notifications
 *   pearblog_push_provider       – 'onesignal' | 'fcm'
 *   pearblog_push_onesignal_id   – OneSignal App ID
 *   pearblog_push_onesignal_key  – OneSignal REST API key
 *   pearblog_push_fcm_key        – FCM Server Key or OAuth bearer token
 *   pearblog_push_fcm_project    – Firebase project ID (for v1 API)
 *
 * @package PearBlogEngine\Social
 */

declare(strict_types=1);

namespace PearBlogEngine\Social;

/**
 * Dispatches web push notifications on article publication.
 */
class PushNotificationPublisher {

	/** WP option keys. */
	public const OPTION_ENABLED      = 'pearblog_push_enabled';
	public const OPTION_PROVIDER     = 'pearblog_push_provider';
	public const OPTION_OS_APP_ID    = 'pearblog_push_onesignal_id';
	public const OPTION_OS_API_KEY   = 'pearblog_push_onesignal_key';
	public const OPTION_FCM_KEY      = 'pearblog_push_fcm_key';
	public const OPTION_FCM_PROJECT  = 'pearblog_push_fcm_project';

	/** OneSignal API endpoints. */
	private const ONESIGNAL_NOTIFY_URL = 'https://onesignal.com/api/v1/notifications';

	/** FCM API endpoint (v1). */
	private const FCM_API_URL = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'publish_post', [ $this, 'on_publish' ], 20, 2 );
	}

	// -----------------------------------------------------------------------
	// Core methods
	// -----------------------------------------------------------------------

	/**
	 * Whether push notifications are enabled and configured.
	 */
	public function is_enabled(): bool {
		if ( ! (bool) get_option( self::OPTION_ENABLED, false ) ) {
			return false;
		}

		$provider = (string) get_option( self::OPTION_PROVIDER, 'onesignal' );

		return match ( $provider ) {
			'onesignal' => '' !== (string) get_option( self::OPTION_OS_APP_ID )
			             && '' !== (string) get_option( self::OPTION_OS_API_KEY ),
			'fcm'       => '' !== (string) get_option( self::OPTION_FCM_KEY ),
			default     => false,
		};
	}

	/**
	 * Send a push notification for a published article.
	 *
	 * @param int      $post_id  WordPress post ID.
	 * @param \WP_Post $post     Post object.
	 * @return array{provider: string, success: bool, response: mixed}
	 */
	public function notify( int $post_id, \WP_Post $post ): array {
		if ( ! $this->is_enabled() ) {
			return [ 'provider' => 'none', 'success' => false, 'response' => 'disabled' ];
		}

		// Get post categories for segmentation.
		$categories = wp_get_post_categories( $post_id, [ 'fields' => 'names' ] );
		$excerpt    = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 20 );
		$image_url  = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: '';

		$provider = (string) get_option( self::OPTION_PROVIDER, 'onesignal' );

		$result = match ( $provider ) {
			'fcm'   => $this->send_via_fcm( $post->post_title, $excerpt, get_permalink( $post_id ), $image_url, $categories ),
			default => $this->send_via_onesignal( $post->post_title, $excerpt, get_permalink( $post_id ), $image_url, $categories ),
		};

		// Store notification history.
		update_post_meta( $post_id, 'pearblog_push_notified', time() );
		update_post_meta( $post_id, 'pearblog_push_result', $result );

		/**
		 * Action: pearblog_push_sent
		 *
		 * @param int   $post_id WordPress post ID.
		 * @param array $result  Notification result.
		 */
		do_action( 'pearblog_push_sent', $post_id, $result );

		return $result;
	}

	// -----------------------------------------------------------------------
	// WordPress action callback
	// -----------------------------------------------------------------------

	/**
	 * Trigger notification when a post transitions to 'publish'.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function on_publish( int $post_id, \WP_Post $post ): void {
		// Only send for regular posts, not pages, CPTs etc.
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Prevent duplicate notifications.
		if ( get_post_meta( $post_id, 'pearblog_push_notified', true ) ) {
			return;
		}

		$this->notify( $post_id, $post );
	}

	// -----------------------------------------------------------------------
	// OneSignal implementation
	// -----------------------------------------------------------------------

	/**
	 * Send notification via OneSignal REST API.
	 *
	 * @param string   $title      Notification title.
	 * @param string   $body       Notification body.
	 * @param string   $url        Target URL.
	 * @param string   $image_url  Large image URL.
	 * @param string[] $categories Post categories for segment targeting.
	 * @return array{provider: string, success: bool, response: mixed}
	 */
	private function send_via_onesignal(
		string $title,
		string $body,
		string $url,
		string $image_url,
		array $categories
	): array {
		$app_id  = (string) get_option( self::OPTION_OS_APP_ID );
		$api_key = (string) get_option( self::OPTION_OS_API_KEY );

		$payload = [
			'app_id'            => $app_id,
			'headings'          => [ 'en' => $title ],
			'contents'          => [ 'en' => $body ],
			'url'               => $url,
			'included_segments' => [ 'All' ],
		];

		if ( '' !== $image_url ) {
			$payload['big_picture']     = $image_url;
			$payload['large_icon']      = $image_url;
		}

		// Category-based segmentation (if OneSignal tags are configured).
		if ( ! empty( $categories ) ) {
			$payload['filters'] = [];
			foreach ( $categories as $cat ) {
				$payload['filters'][] = [ 'field' => 'tag', 'key' => 'category', 'relation' => '=', 'value' => strtolower( $cat ) ];
				$payload['filters'][] = [ 'operator' => 'OR' ];
			}
			// Remove trailing OR operator.
			if ( ! empty( $payload['filters'] ) ) {
				array_pop( $payload['filters'] );
			}
		}

		$response = wp_remote_post( self::ONESIGNAL_NOTIFY_URL, [
			'headers' => [
				'Authorization' => 'Basic ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $payload ),
			'timeout' => 15,
		] );

		$success = ! is_wp_error( $response )
			&& in_array( wp_remote_retrieve_response_code( $response ), [ 200, 201 ], true );

		return [
			'provider' => 'onesignal',
			'success'  => $success,
			'response' => is_wp_error( $response ) ? $response->get_error_message() : json_decode( wp_remote_retrieve_body( $response ), true ),
		];
	}

	// -----------------------------------------------------------------------
	// FCM implementation
	// -----------------------------------------------------------------------

	/**
	 * Send notification via Firebase Cloud Messaging v1 API.
	 *
	 * @param string   $title
	 * @param string   $body
	 * @param string   $url
	 * @param string   $image_url
	 * @param string[] $categories
	 * @return array{provider: string, success: bool, response: mixed}
	 */
	private function send_via_fcm(
		string $title,
		string $body,
		string $url,
		string $image_url,
		array $categories
	): array {
		$fcm_key = (string) get_option( self::OPTION_FCM_KEY );
		$project = (string) get_option( self::OPTION_FCM_PROJECT );

		if ( '' === $project ) {
			return [ 'provider' => 'fcm', 'success' => false, 'response' => 'missing_project_id' ];
		}

		$api_url = sprintf( self::FCM_API_URL, $project );

		$payload = [
			'message' => [
				'topic'        => 'all_subscribers',
				'notification' => [
					'title' => $title,
					'body'  => $body,
					'image' => $image_url ?: null,
				],
				'webpush' => [
					'fcm_options' => [ 'link' => $url ],
				],
			],
		];

		$response = wp_remote_post( $api_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $fcm_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $payload ),
			'timeout' => 15,
		] );

		$success = ! is_wp_error( $response )
			&& 200 === wp_remote_retrieve_response_code( $response );

		return [
			'provider' => 'fcm',
			'success'  => $success,
			'response' => is_wp_error( $response ) ? $response->get_error_message() : json_decode( wp_remote_retrieve_body( $response ), true ),
		];
	}
}
