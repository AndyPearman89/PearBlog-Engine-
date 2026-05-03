<?php
/**
 * AI Streaming Client – streams AI-generated content via Server-Sent Events.
 *
 * Provides a REST endpoint that proxies an OpenAI streaming request and
 * pipes tokens back to the browser as SSE events, enabling a live preview
 * in the WordPress admin.
 *
 * REST endpoint:
 *   GET /pearblog/v1/stream/generate?topic=<topic>&nonce=<nonce>
 *   Streams: text/event-stream
 *     data: {"token":"..."}
 *     data: {"done":true,"post_id":123}
 *
 * Configuration:
 *   Uses the same `pearblog_openai_api_key` option as AIClient.
 *   Only the OpenAI provider supports streaming in this implementation.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Streams AI content generation to the browser.
 */
class StreamingAIClient {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** OpenAI streaming chat endpoint. */
	private const OPENAI_STREAM_URL = 'https://api.openai.com/v1/chat/completions';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register REST endpoints.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the streaming REST route.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/stream/generate', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'handle_stream' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Streaming handler
	// -----------------------------------------------------------------------

	/**
	 * Handle a streaming generation request.
	 *
	 * Outputs SSE format directly and exits.
	 *
	 * @param \WP_REST_Request $request
	 * @return void
	 */
	public function handle_stream( \WP_REST_Request $request ): void {
		$topic  = sanitize_text_field( (string) $request->get_param( 'topic' ) );
		$api_key = (string) get_option( 'pearblog_openai_api_key', '' );

		if ( '' === $topic ) {
			wp_send_json_error( [ 'message' => 'Topic is required.' ], 400 );
			return;
		}

		if ( '' === $api_key ) {
			wp_send_json_error( [ 'message' => 'OpenAI API key not configured.' ], 400 );
			return;
		}

		// Set SSE headers.
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' ); // Disable nginx buffering.

		// Disable output buffering.
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}

		$this->stream_generation( $topic, $api_key );
		exit;
	}

	/**
	 * Stream token-by-token generation from OpenAI.
	 *
	 * @param string $topic   Article topic.
	 * @param string $api_key OpenAI API key.
	 */
	public function stream_generation( string $topic, string $api_key ): void {
		$model = (string) get_option( 'pearblog_ai_model', AIClient::DEFAULT_MODEL );

		$body = wp_json_encode( [
			'model'       => $model,
			'stream'      => true,
			'max_tokens'  => 2048,
			'messages'    => [
				[
					'role'    => 'system',
					'content' => 'You are an expert content writer. Write comprehensive, SEO-optimized articles.',
				],
				[
					'role'    => 'user',
					'content' => "Write a detailed article about: {$topic}",
				],
			],
		] );

		$ch = curl_init( self::OPENAI_STREAM_URL );
		if ( false === $ch ) {
			$this->send_sse_event( [ 'error' => 'Failed to initialize streaming.' ] );
			return;
		}

		$full_content = '';

		curl_setopt_array( $ch, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $body,
			CURLOPT_HTTPHEADER     => [
				'Authorization: Bearer ' . $api_key,
				'Content-Type: application/json',
			],
			CURLOPT_WRITEFUNCTION  => function ( $curl, $chunk ) use ( &$full_content ) {
				// SSE chunks from OpenAI: "data: {...}\n\n"
				$lines = explode( "\n", $chunk );
				foreach ( $lines as $line ) {
					$line = trim( $line );
					if ( '' === $line || 'data: [DONE]' === $line ) {
						continue;
					}

					if ( str_starts_with( $line, 'data: ' ) ) {
						$json  = substr( $line, 6 );
						$event = json_decode( $json, true );
						$token = $event['choices'][0]['delta']['content'] ?? '';

						if ( '' !== $token ) {
							$full_content .= $token;
							$this->send_sse_event( [ 'token' => $token ] );
						}
					}
				}

				// Flush output to client.
				if ( ob_get_level() > 0 ) {
					ob_flush();
				}
				flush();

				return strlen( $chunk );
			},
			CURLOPT_TIMEOUT        => 120,
			CURLOPT_RETURNTRANSFER => false,
		] );

		curl_exec( $ch );
		curl_close( $ch );

		// Signal completion.
		$this->send_sse_event( [ 'done' => true, 'word_count' => str_word_count( $full_content ) ] );
	}

	// -----------------------------------------------------------------------
	// Permission
	// -----------------------------------------------------------------------

	/**
	 * Permission callback – admins only.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Emit a single SSE event.
	 *
	 * @param array<string,mixed> $data Event payload.
	 */
	private function send_sse_event( array $data ): void {
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";
	}
}
