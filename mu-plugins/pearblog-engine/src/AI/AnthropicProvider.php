<?php
/**
 * Anthropic Claude provider.
 *
 * Supports claude-3-5-sonnet-20241022 and claude-3-haiku-20240307 via the
 * Anthropic Messages API (api.anthropic.com/v1/messages).
 *
 * API key is read from the `pearblog_anthropic_api_key` WordPress option.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Anthropic Claude Messages API provider.
 */
class AnthropicProvider implements AIProviderInterface {

	private const API_URL          = 'https://api.anthropic.com/v1/messages';
	private const API_VERSION      = '2023-06-01';

	/**
	 * Supported Anthropic models.
	 *
	 * Pricing (as of 2026-04-12, subject to change):
	 *  claude-3-5-sonnet-20241022 : $3   / $15   per 1M tokens → 0.030  / 0.150  ¢ per 1k
	 *  claude-3-haiku-20240307    : $0.25/ $1.25  per 1M tokens → 0.0025 / 0.0125 ¢ per 1k
	 *
	 * @var array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>
	 */
	private const MODELS = [
		'claude-3-5-sonnet-20241022' => [
			'label'                    => 'Claude 3.5 Sonnet (best quality)',
			'max_tokens'               => 8192,
			'cost_per_1k_input_cents'  => 0.030,
			'cost_per_1k_output_cents' => 0.150,
		],
		'claude-3-haiku-20240307' => [
			'label'                    => 'Claude 3 Haiku (fast & cheap)',
			'max_tokens'               => 4096,
			'cost_per_1k_input_cents'  => 0.0025,
			'cost_per_1k_output_cents' => 0.0125,
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
			throw new \RuntimeException( 'PearBlog Engine: Anthropic API key is not configured.' );
		}

		$body = wp_json_encode( [
			'model'      => $this->model,
			'max_tokens' => $max_tokens,
			'messages'   => [
				[ 'role' => 'user', 'content' => $prompt ],
			],
		] );

		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => 90,
				'headers' => [
					'Content-Type'       => 'application/json',
					'x-api-key'          => $this->api_key,
					'anthropic-version'  => self::API_VERSION,
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

		// Anthropic uses 429 for rate limits and 529 for overload (treat as rate limit).
		if ( 429 === $status || 529 === $status ) {
			throw new RateLimitException( "Anthropic rate limit / overload ({$status})." );
		}

		if ( 200 !== $status || ! is_array( $data ) ) {
			$message = is_array( $data ) ? ( $data['error']['message'] ?? 'Unknown API error' ) : 'Unknown API error';
			throw new \RuntimeException( "PearBlog Engine: Anthropic API error ({$status}) – {$message}" );
		}

		// Anthropic response shape: {"content": [{"type": "text", "text": "..."}], "usage": {...}}
		$content = '';
		if ( is_array( $data['content'] ?? null ) ) {
			foreach ( $data['content'] as $block ) {
				if ( 'text' === ( $block['type'] ?? '' ) ) {
					$content .= $block['text'] ?? '';
				}
			}
		}
		$content = trim( $content );

		$prompt_tokens     = (int) ( $data['usage']['input_tokens']  ?? 0 );
		$completion_tokens = (int) ( $data['usage']['output_tokens'] ?? 0 );

		return compact( 'content', 'prompt_tokens', 'completion_tokens' );
	}

	// -----------------------------------------------------------------------
	// AIProviderInterface — static metadata
	// -----------------------------------------------------------------------

	public static function get_slug(): string {
		return 'anthropic';
	}

	public static function get_label(): string {
		return 'Anthropic Claude';
	}

	public static function get_api_key_option(): string {
		return 'pearblog_anthropic_api_key';
	}

	public static function get_models(): array {
		return self::MODELS;
	}

	public static function get_default_model(): string {
		return 'claude-3-5-sonnet-20241022';
	}
}
