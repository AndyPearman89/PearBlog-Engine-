<?php
/**
 * Content Moderator – checks AI-generated content against OpenAI Moderation API.
 *
 * Before publication, scans content for harmful, hateful, or violating material
 * using the OpenAI Moderation endpoint.  If content fails moderation it is
 * blocked and logged; an admin alert is dispatched.
 *
 * Configuration (WP options):
 *   pearblog_moderation_enabled   – (bool) enable content moderation
 *   pearblog_moderation_action    – 'block' | 'flag' (default: 'block')
 *
 * Uses the same `pearblog_openai_api_key` option as AIClient.
 *
 * Post meta:
 *   pearblog_moderation_result    – JSON: {flagged, categories, scores}
 *
 * @package PearBlogEngine\Security
 */

declare(strict_types=1);

namespace PearBlogEngine\Security;

/**
 * Scans content through OpenAI Moderation API.
 */
class ContentModerator {

	/** WP option keys. */
	public const OPTION_ENABLED = 'pearblog_moderation_enabled';
	public const OPTION_ACTION  = 'pearblog_moderation_action';

	/** Post meta key for moderation results. */
	public const META_RESULT = 'pearblog_moderation_result';

	/** OpenAI Moderation API endpoint. */
	private const API_URL = 'https://api.openai.com/v1/moderations';

	/** Max text length to send for moderation (API limit). */
	private const MAX_TEXT_LENGTH = 8000;

	// -----------------------------------------------------------------------

	/**
	 * Whether moderation is enabled and configured.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false )
			&& '' !== (string) get_option( 'pearblog_openai_api_key', '' );
	}

	/**
	 * Check content and optionally block it if it violates policy.
	 *
	 * @param int    $post_id WordPress post ID.
	 * @param string $content Article content to check.
	 * @return array{flagged: bool, action: string, categories: array<string,bool>, scores: array<string,float>}
	 */
	public function check( int $post_id, string $content ): array {
		if ( ! $this->is_enabled() ) {
			return [ 'flagged' => false, 'action' => 'none', 'categories' => [], 'scores' => [] ];
		}

		$text   = wp_strip_all_tags( $content );
		$text   = substr( $text, 0, self::MAX_TEXT_LENGTH );
		$result = $this->call_moderation_api( $text );

		// Persist result.
		update_post_meta( $post_id, self::META_RESULT, $result );

		if ( $result['flagged'] ) {
			$action = (string) get_option( self::OPTION_ACTION, 'block' );
			$result['action'] = $action;

			$flagged_cats = array_keys( array_filter( $result['categories'] ) );

			error_log( sprintf(
				'PearBlog Engine: Content moderation flagged post #%d – categories: %s',
				$post_id,
				implode( ', ', $flagged_cats )
			) );

			/**
			 * Action: pearblog_content_moderation_flagged
			 *
			 * @param int    $post_id       Post ID.
			 * @param string $action        'block' or 'flag'.
			 * @param array  $categories    Flagged categories.
			 */
			do_action( 'pearblog_content_moderation_flagged', $post_id, $action, $flagged_cats );

			if ( 'block' === $action ) {
				// Revert post to draft to prevent publication.
				wp_update_post( [
					'ID'          => $post_id,
					'post_status' => 'draft',
				] );
			}
		} else {
			$result['action'] = 'pass';
		}

		return $result;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Call the OpenAI Moderation API.
	 *
	 * @param string $text Text to check.
	 * @return array{flagged: bool, categories: array<string,bool>, scores: array<string,float>}
	 */
	private function call_moderation_api( string $text ): array {
		$api_key = (string) get_option( 'pearblog_openai_api_key', '' );
		$default = [ 'flagged' => false, 'categories' => [], 'scores' => [] ];

		if ( '' === $api_key ) {
			return $default;
		}

		$response = wp_remote_post( self::API_URL, [
			'headers' => [
				'Authorization' => "Bearer {$api_key}",
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [ 'input' => $text ] ),
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'PearBlog Engine: Moderation API error – ' . $response->get_error_message() );
			return $default;
		}

		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		$result = $data['results'][0] ?? null;

		if ( ! is_array( $result ) ) {
			return $default;
		}

		return [
			'flagged'    => (bool) ( $result['flagged'] ?? false ),
			'categories' => (array) ( $result['categories'] ?? [] ),
			'scores'     => (array) ( $result['category_scores'] ?? [] ),
		];
	}
}
