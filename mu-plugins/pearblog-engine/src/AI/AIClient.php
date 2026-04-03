<?php
/**
 * AI client – sends prompts to OpenAI and returns generated content.
 *
 * The API key is read from the WordPress option `pearblog_openai_api_key`
 * (set via the PearBlog admin page or wp-config.php constant
 * PEARBLOG_OPENAI_API_KEY).
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Thin wrapper around the OpenAI Chat Completions API.
 */
class AIClient {

	private const API_URL = 'https://api.openai.com/v1/chat/completions';
	private const MODEL   = 'gpt-4o-mini';

	/** @var string */
	private string $api_key;

	public function __construct( string $api_key = '' ) {
		if ( '' === $api_key ) {
			$api_key = defined( 'PEARBLOG_OPENAI_API_KEY' )
				? PEARBLOG_OPENAI_API_KEY
				: (string) get_option( 'pearblog_openai_api_key', '' );
		}
		$this->api_key = $api_key;
	}

	/**
	 * Send a prompt and return the AI-generated text.
	 *
	 * @param string $prompt        The full prompt to send.
	 * @param int    $max_tokens    Maximum tokens in the response (default 2 048).
	 * @return string               Generated content, or empty string on failure.
	 * @throws \RuntimeException    When the HTTP request fails or the API returns an error.
	 */
	public function generate( string $prompt, int $max_tokens = 2048 ): string {
		if ( '' === $this->api_key ) {
			throw new \RuntimeException( 'PearBlog Engine: OpenAI API key is not configured.' );
		}

		$body = wp_json_encode( [
			'model'      => self::MODEL,
			'messages'   => [
				[ 'role' => 'user', 'content' => $prompt ],
			],
			'max_tokens' => $max_tokens,
		] );

		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => 60,
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				],
				'body'    => $body,
			]
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException(
				'PearBlog Engine: HTTP request failed – ' . $response->get_error_message()
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status || ! is_array( $data ) ) {
			$message = $data['error']['message'] ?? 'Unknown API error';
			throw new \RuntimeException( "PearBlog Engine: OpenAI API error ({$status}) – {$message}" );
		}

		return trim( $data['choices'][0]['message']['content'] ?? '' );
	}
}
