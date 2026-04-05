<?php
/**
 * Social publisher – auto-posts new articles to Twitter/X, Facebook, and LinkedIn.
 *
 * Triggered by `pearblog_pipeline_completed` action (after a post is published).
 *
 * Configuration (stored as WordPress options):
 *   pearblog_social_twitter_api_key        – Twitter API Key (v2)
 *   pearblog_social_twitter_api_secret     – Twitter API Secret
 *   pearblog_social_twitter_access_token   – Twitter Access Token
 *   pearblog_social_twitter_access_secret  – Twitter Access Token Secret
 *   pearblog_social_facebook_page_token    – Facebook Page Access Token
 *   pearblog_social_facebook_page_id       – Facebook Page ID
 *   pearblog_social_linkedin_access_token  – LinkedIn Access Token
 *   pearblog_social_linkedin_author_urn    – LinkedIn person/org URN
 *   pearblog_social_enabled_channels       – Comma-separated: twitter,facebook,linkedin
 *
 * @package PearBlogEngine\Social
 */

declare(strict_types=1);

namespace PearBlogEngine\Social;

/**
 * Posts published article summaries to configured social media channels.
 */
class SocialPublisher {

	/**
	 * Attach WordPress hooks (called from Plugin::boot).
	 */
	public function register(): void {
		add_action( 'pearblog_pipeline_completed', [ $this, 'on_pipeline_completed' ], 10, 3 );
	}

	/**
	 * Callback for pearblog_pipeline_completed action.
	 *
	 * @param int    $post_id WordPress post ID.
	 * @param string $topic   Original topic.
	 * @param mixed  $context TenantContext.
	 */
	public function on_pipeline_completed( int $post_id, string $topic, $context ): void {
		// Small delay to allow WordPress to fully commit the post.
		$this->publish( $post_id );
	}

	/**
	 * Push a post to all enabled social channels.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return array<string, bool>  Channel → success map.
	 */
	public function publish( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return [];
		}

		$channels = $this->enabled_channels();
		$url      = (string) get_permalink( $post_id );
		$title    = get_the_title( $post_id );
		$desc     = (string) get_post_meta( $post_id, 'pearblog_meta_description', true );
		$hashtags = $this->generate_hashtags( $post );

		$results  = [];

		if ( in_array( 'twitter', $channels, true ) ) {
			$text              = $this->truncate( "{$title} {$url} {$hashtags}", 280 );
			$results['twitter'] = $this->post_twitter( $text );
		}

		if ( in_array( 'facebook', $channels, true ) ) {
			$results['facebook'] = $this->post_facebook( $title, $desc, $url );
		}

		if ( in_array( 'linkedin', $channels, true ) ) {
			$results['linkedin'] = $this->post_linkedin( $title, $desc, $url );
		}

		// Log result.
		foreach ( $results as $channel => $success ) {
			error_log( sprintf(
				'PearBlog SocialPublisher: %s post %d to %s.',
				$success ? 'Published' : 'Failed to publish',
				$post_id,
				$channel
			) );
		}

		/**
		 * Action: pearblog_social_published
		 *
		 * @param int   $post_id Post ID.
		 * @param array $results Channel → success map.
		 */
		do_action( 'pearblog_social_published', $post_id, $results );

		return $results;
	}

	// -----------------------------------------------------------------------
	// Channel implementations
	// -----------------------------------------------------------------------

	private function post_twitter( string $text ): bool {
		$api_key        = (string) get_option( 'pearblog_social_twitter_api_key', '' );
		$api_secret     = (string) get_option( 'pearblog_social_twitter_api_secret', '' );
		$access_token   = (string) get_option( 'pearblog_social_twitter_access_token', '' );
		$access_secret  = (string) get_option( 'pearblog_social_twitter_access_secret', '' );

		if ( '' === $api_key || '' === $access_token ) {
			return false;
		}

		$endpoint = 'https://api.twitter.com/2/tweets';
		$body     = wp_json_encode( [ 'text' => $text ] );

		// Build OAuth 1.0a authorization header.
		$auth_header = $this->build_oauth_header( 'POST', $endpoint, $api_key, $api_secret, $access_token, $access_secret );

		$response = wp_remote_post( $endpoint, [
			'timeout' => 15,
			'headers' => [
				'Authorization' => $auth_header,
				'Content-Type'  => 'application/json',
			],
			'body' => $body,
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return 201 === $code;
	}

	private function post_facebook( string $title, string $desc, string $url ): bool {
		$page_token = (string) get_option( 'pearblog_social_facebook_page_token', '' );
		$page_id    = (string) get_option( 'pearblog_social_facebook_page_id', '' );

		if ( '' === $page_token || '' === $page_id ) {
			return false;
		}

		$message  = "{$title}\n\n{$desc}\n\n{$url}";
		$endpoint = "https://graph.facebook.com/v19.0/{$page_id}/feed";

		$response = wp_remote_post( $endpoint, [
			'timeout' => 15,
			'body'    => [
				'message'      => $message,
				'link'         => $url,
				'access_token' => $page_token,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return 200 === $code;
	}

	private function post_linkedin( string $title, string $desc, string $url ): bool {
		$access_token = (string) get_option( 'pearblog_social_linkedin_access_token', '' );
		$author_urn   = (string) get_option( 'pearblog_social_linkedin_author_urn', '' );

		if ( '' === $access_token || '' === $author_urn ) {
			return false;
		}

		$payload = [
			'author'          => $author_urn,
			'lifecycleState'  => 'PUBLISHED',
			'specificContent' => [
				'com.linkedin.ugc.ShareContent' => [
					'shareCommentary' => [
						'text' => "{$title}\n\n{$desc}\n\n{$url}",
					],
					'shareMediaCategory' => 'ARTICLE',
					'media' => [
						[
							'status'      => 'READY',
							'description' => [ 'text' => $desc ],
							'originalUrl' => $url,
							'title'       => [ 'text' => $title ],
						],
					],
				],
			],
			'visibility' => [
				'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
			],
		];

		$response = wp_remote_post( 'https://api.linkedin.com/v2/ugcPosts', [
			'timeout' => 15,
			'headers' => [
				'Authorization'   => 'Bearer ' . $access_token,
				'Content-Type'    => 'application/json',
				'X-Restli-Protocol-Version' => '2.0.0',
			],
			'body' => wp_json_encode( $payload ),
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return 201 === $code;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Return list of enabled social channels.
	 *
	 * @return string[]
	 */
	private function enabled_channels(): array {
		$raw = (string) get_option( 'pearblog_social_enabled_channels', '' );
		if ( '' === $raw ) {
			return [];
		}
		return array_map( 'trim', explode( ',', $raw ) );
	}

	/**
	 * Generate 2-3 hashtags from post categories and tags.
	 */
	private function generate_hashtags( \WP_Post $post ): string {
		$terms    = get_the_terms( $post->ID, 'category' ) ?: [];
		$tags     = get_the_terms( $post->ID, 'post_tag' ) ?: [];
		$all_terms = array_merge( (array) $terms, (array) $tags );

		$hashtags = [];
		foreach ( array_slice( $all_terms, 0, 3 ) as $term ) {
			if ( $term instanceof \WP_Term ) {
				$slug       = str_replace( '-', '', ucwords( $term->slug, '-' ) );
				$hashtags[] = '#' . $slug;
			}
		}

		return implode( ' ', $hashtags );
	}

	private function truncate( string $text, int $max ): string {
		if ( mb_strlen( $text ) <= $max ) {
			return $text;
		}
		return mb_substr( $text, 0, $max - 1 ) . '…';
	}

	/**
	 * Build an OAuth 1.0a Authorization header for Twitter API v2.
	 */
	private function build_oauth_header(
		string $method,
		string $url,
		string $api_key,
		string $api_secret,
		string $access_token,
		string $access_secret
	): string {
		$nonce     = bin2hex( random_bytes( 16 ) );
		$timestamp = (string) time();

		$params = [
			'oauth_consumer_key'     => $api_key,
			'oauth_nonce'            => $nonce,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp'        => $timestamp,
			'oauth_token'            => $access_token,
			'oauth_version'          => '1.0',
		];

		ksort( $params );
		$param_string = implode( '&', array_map(
			fn( $k, $v ) => rawurlencode( $k ) . '=' . rawurlencode( $v ),
			array_keys( $params ),
			$params
		) );

		$base_string = strtoupper( $method ) . '&' . rawurlencode( $url ) . '&' . rawurlencode( $param_string );
		$signing_key = rawurlencode( $api_secret ) . '&' . rawurlencode( $access_secret );
		$signature   = base64_encode( hash_hmac( 'sha1', $base_string, $signing_key, true ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$params['oauth_signature'] = $signature;

		$header_parts = [];
		foreach ( $params as $key => $value ) {
			$header_parts[] = rawurlencode( $key ) . '="' . rawurlencode( $value ) . '"';
		}

		return 'OAuth ' . implode( ', ', $header_parts );
	}
}
