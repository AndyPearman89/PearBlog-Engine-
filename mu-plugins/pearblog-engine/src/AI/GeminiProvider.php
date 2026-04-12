<?php
/**
 * Google Gemini provider.
 *
 * Supports gemini-1.5-pro and gemini-1.5-flash via the Google AI Studio
 * generateContent REST API.
 *
 * API key is read from the `pearblog_gemini_api_key` WordPress option.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Google Gemini generateContent API provider.
 */
class GeminiProvider implements AIProviderInterface {

	private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';

	/**
	 * Supported Gemini models.
	 *
	 * Pricing (as of 2026-04-12, subject to change):
	 *  gemini-1.5-pro   : $3.50 / $10.50 per 1M tokens → 0.035   / 0.105  ¢ per 1k
	 *  gemini-1.5-flash : $0.075/ $0.30  per 1M tokens → 0.00075 / 0.003  ¢ per 1k
	 *
	 * @var array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>
	 */
	private const MODELS = [
		'gemini-1.5-pro' => [
			'label'                    => 'Gemini 1.5 Pro (best quality)',
			'max_tokens'               => 8192,
			'cost_per_1k_input_cents'  => 0.035,
			'cost_per_1k_output_cents' => 0.105,
		],
		'gemini-1.5-flash' => [
			'label'                    => 'Gemini 1.5 Flash (fast & cheap)',
			'max_tokens'               => 8192,
			'cost_per_1k_input_cents'  => 0.00075,
			'cost_per_1k_output_cents' => 0.003,
		],
	];

	/** @var string */
	private string $api_key;

	/** @var string */
	private string $model;

	/**
	 * @param string $api_key API key; if empty, read from WP option.
	 * @param string $model   Model slug; if empty, use default.
	 */
	public function __construct( string $api_key = '', string $model = '' ) {
		if ( '' === $api_key ) {
			$api_key = (string) get_option( self::get_api_key_option(), '' );
		}
		$this->api_key = $api_key;
		$this->model   = isset( self::MODELS[ $model ] ) ? $model : self::get_default_model();
	}

	// -----------------------------------------------------------------------
	// AIProviderInterface — instance
	// -----------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 */
	public function complete( string $prompt, int $max_tokens ): array {
		if ( '' === $this->api_key ) {
			throw new \RuntimeException( 'PearBlog Engine: Google Gemini API key is not configured.' );
		}

		// Gemini uses the API key as a query parameter.
		$url = self::API_BASE . rawurlencode( $this->model ) . ':generateContent?key=' . $this->api_key;

		$body = wp_json_encode( [
			'contents'        => [
				[ 'parts' => [ [ 'text' => $prompt ] ] ],
			],
			'generationConfig' => [
				'maxOutputTokens' => $max_tokens,
			],
		] );

		$response = wp_remote_post(
			$url,
			[
				'timeout' => 90,
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => $body,
			]
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException(
				'PearBlog Engine: HTTP request failed – ' . $response->get_error_message()
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$raw    = wp_remote_retrieve_body( $response );
		$data   = json_decode( $raw, true );

		if ( 429 === $status ) {
			throw new RateLimitException( 'Google Gemini rate limit hit (429).' );
		}

		if ( 200 !== $status || ! is_array( $data ) ) {
			$message = is_array( $data ) ? ( $data['error']['message'] ?? 'Unknown API error' ) : 'Unknown API error';
			throw new \RuntimeException( "PearBlog Engine: Gemini API error ({$status}) – {$message}" );
		}

		// Gemini response shape:
		// {"candidates":[{"content":{"parts":[{"text":"..."}]}}], "usageMetadata":{...}}
		$content = '';
		$parts   = $data['candidates'][0]['content']['parts'] ?? [];
		foreach ( $parts as $part ) {
			$content .= $part['text'] ?? '';
		}
		$content = trim( $content );

		$prompt_tokens     = (int) ( $data['usageMetadata']['promptTokenCount']     ?? 0 );
		$completion_tokens = (int) ( $data['usageMetadata']['candidatesTokenCount'] ?? 0 );

		return compact( 'content', 'prompt_tokens', 'completion_tokens' );
	}

	// -----------------------------------------------------------------------
	// AIProviderInterface — static metadata
	// -----------------------------------------------------------------------

	public static function get_slug(): string {
		return 'gemini';
	}

	public static function get_label(): string {
		return 'Google Gemini';
	}

	public static function get_api_key_option(): string {
		return 'pearblog_gemini_api_key';
	}

	public static function get_models(): array {
		return self::MODELS;
	}

	public static function get_default_model(): string {
		return 'gemini-1.5-pro';
	}
}
