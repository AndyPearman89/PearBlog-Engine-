<?php
/**
 * OpenAI Chat Completions provider.
 *
 * Supports gpt-4o, gpt-4o-mini, gpt-4-turbo, and gpt-3.5-turbo.
 * API key is read from the `pearblog_openai_api_key` WordPress option or the
 * PEARBLOG_OPENAI_API_KEY wp-config constant.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * OpenAI Chat Completions API provider.
 */
class OpenAIProvider implements AIProviderInterface {

	private const API_URL = 'https://api.openai.com/v1/chat/completions';

	/**
	 * Supported OpenAI models.
	 *
	 * Pricing (as of 2026-04-12, subject to change):
	 *  gpt-4o        : $2.50 / $10.00  per 1M tokens → 0.025  / 0.100  ¢ per 1k
	 *  gpt-4o-mini   : $0.15 / $0.60   per 1M tokens → 0.0015 / 0.006  ¢ per 1k
	 *  gpt-4-turbo   : $10   / $30     per 1M tokens → 0.1    / 0.3    ¢ per 1k
	 *  gpt-3.5-turbo : $0.50 / $1.50   per 1M tokens → 0.005  / 0.015  ¢ per 1k
	 *
	 * @var array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>
	 */
	private const MODELS = [
		'gpt-4o' => [
			'label'                    => 'GPT-4o (best quality)',
			'max_tokens'               => 4096,
			'cost_per_1k_input_cents'  => 0.025,
			'cost_per_1k_output_cents' => 0.100,
		],
		'gpt-4o-mini' => [
			'label'                    => 'GPT-4o mini (fast & cheap)',
			'max_tokens'               => 4096,
			'cost_per_1k_input_cents'  => 0.0015,
			'cost_per_1k_output_cents' => 0.006,
		],
		'gpt-4-turbo' => [
			'label'                    => 'GPT-4 Turbo (high quality)',
			'max_tokens'               => 4096,
			'cost_per_1k_input_cents'  => 0.100,
			'cost_per_1k_output_cents' => 0.300,
		],
		'gpt-3.5-turbo' => [
			'label'                    => 'GPT-3.5 Turbo (lowest cost)',
			'max_tokens'               => 4096,
			'cost_per_1k_input_cents'  => 0.005,
			'cost_per_1k_output_cents' => 0.015,
		],
	];

	/** @var string */
	private string $api_key;

	/** @var string */
	private string $model;

	/**
	 * @param string $api_key API key; if empty, read from WP option / constant.
	 * @param string $model   Model slug; if empty, use default.
	 */
	public function __construct( string $api_key = '', string $model = '' ) {
		if ( '' === $api_key ) {
			$api_key = defined( 'PEARBLOG_OPENAI_API_KEY' )
				? PEARBLOG_OPENAI_API_KEY
				: (string) get_option( self::get_api_key_option(), '' );
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
			throw new \RuntimeException( 'PearBlog Engine: OpenAI API key is not configured.' );
		}

		$body = wp_json_encode( [
			'model'      => $this->model,
			'messages'   => [
				[ 'role' => 'user', 'content' => $prompt ],
			],
			'max_tokens' => $max_tokens,
		] );

		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => 90,
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

		$status = (int) wp_remote_retrieve_response_code( $response );
		$raw    = wp_remote_retrieve_body( $response );
		$data   = json_decode( $raw, true );

		if ( 429 === $status ) {
			throw new RateLimitException( 'OpenAI rate limit hit (429).' );
		}

		if ( 200 !== $status || ! is_array( $data ) ) {
			$message = is_array( $data ) ? ( $data['error']['message'] ?? 'Unknown API error' ) : 'Unknown API error';
			throw new \RuntimeException( "PearBlog Engine: OpenAI API error ({$status}) – {$message}" );
		}

		$content           = trim( $data['choices'][0]['message']['content'] ?? '' );
		$prompt_tokens     = (int) ( $data['usage']['prompt_tokens']     ?? 0 );
		$completion_tokens = (int) ( $data['usage']['completion_tokens'] ?? 0 );

		return compact( 'content', 'prompt_tokens', 'completion_tokens' );
	}

	// -----------------------------------------------------------------------
	// AIProviderInterface — static metadata
	// -----------------------------------------------------------------------

	public static function get_slug(): string {
		return 'openai';
	}

	public static function get_label(): string {
		return 'OpenAI';
	}

	public static function get_api_key_option(): string {
		return 'pearblog_openai_api_key';
	}

	public static function get_models(): array {
		return self::MODELS;
	}

	public static function get_default_model(): string {
		return 'gpt-4o-mini';
	}
}
