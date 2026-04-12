<?php
/**
 * AI client – sends prompts to OpenAI and returns generated content.
 *
 * Features:
 *  - Configurable model (gpt-4o, gpt-4o-mini, gpt-4-turbo, gpt-3.5-turbo).
 *    Active model is stored in the `pearblog_ai_model` option.
 *  - Exponential backoff with jitter on rate-limit (429) responses.
 *  - Circuit breaker: after N consecutive failures the client refuses
 *    further calls for a configurable cooldown period.
 *  - Per-article cost tracking stored in a WordPress option.
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
 * Thin wrapper around the OpenAI Chat Completions API with resilience features.
 */
class AIClient {

	private const API_URL = 'https://api.openai.com/v1/chat/completions';

	/**
	 * Fallback model used when the option is not set or is invalid.
	 */
	public const DEFAULT_MODEL = 'gpt-4o-mini';

	/**
	 * Option key that stores the currently selected model slug.
	 */
	public const MODEL_OPTION = 'pearblog_ai_model';

	/**
	 * Supported models with metadata.
	 *
	 * cost_per_1k_input_cents  – estimated USD cents per 1 000 input tokens.
	 * cost_per_1k_output_cents – estimated USD cents per 1 000 output tokens.
	 * max_tokens               – maximum supported output tokens.
	 * label                    – human-readable label for the admin UI.
	 *
	 * Pricing sources (as of 2026-04-12, subject to change):
	 *  gpt-4o        : $2.50/$10.00 per 1M tokens → 0.025/0.100 cents per 1k
	 *  gpt-4o-mini   : $0.15/$0.60  per 1M tokens → 0.0015/0.006 cents per 1k
	 *  gpt-4-turbo   : $10/$30      per 1M tokens → 0.1/0.3 cents per 1k
	 *  gpt-3.5-turbo : $0.50/$1.50  per 1M tokens → 0.005/0.015 cents per 1k
	 *
	 * @var array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>
	 */
	public const MODELS = [
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

	/** Maximum retry attempts on rate-limit (429) responses. */
	private const MAX_RETRIES = 3;

	/** Base delay in seconds for exponential backoff. */
	private const BASE_DELAY_SECONDS = 2;

	/** Number of consecutive failures before the circuit opens. */
	private const CIRCUIT_FAILURE_THRESHOLD = 5;

	/** Seconds to keep the circuit open (cooldown period). */
	private const CIRCUIT_COOLDOWN_SECONDS = 300; // 5 minutes

	/** WordPress option key for circuit-breaker state. */
	private const CB_STATE_OPTION = 'pearblog_ai_circuit_state';

	/** WordPress option key for cumulative API cost tracking (USD cents). */
	private const COST_OPTION = 'pearblog_ai_cost_cents';

	/** @var string */
	private string $api_key;

	/** @var string The resolved model slug for this instance. */
	private string $model;

	public function __construct( string $api_key = '', string $model = '' ) {
		if ( '' === $api_key ) {
			$api_key = defined( 'PEARBLOG_OPENAI_API_KEY' )
				? PEARBLOG_OPENAI_API_KEY
				: (string) get_option( 'pearblog_openai_api_key', '' );
		}
		$this->api_key = $api_key;
		$this->model   = '' !== $model ? $model : self::get_model();
	}

	// -----------------------------------------------------------------------
	// Model helpers (static, usable without an instance)
	// -----------------------------------------------------------------------

	/**
	 * Return the currently active model slug (reads the WP option, falls back
	 * to DEFAULT_MODEL if unset or invalid).
	 */
	public static function get_model(): string {
		$stored = (string) get_option( self::MODEL_OPTION, self::DEFAULT_MODEL );
		return isset( self::MODELS[ $stored ] ) ? $stored : self::DEFAULT_MODEL;
	}

	/**
	 * Return the full metadata map for every supported model.
	 *
	 * @return array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>
	 */
	public static function get_available_models(): array {
		return self::MODELS;
	}

	/**
	 * Calculate estimated cost in USD cents for a given number of tokens.
	 *
	 * Uses the configured model's blended average (input + output split assumed
	 * 40 % input / 60 % output, matching typical chat-completion patterns).
	 *
	 * @param int    $total_tokens Total tokens (input + output combined).
	 * @param string $model        Model slug; defaults to the active model.
	 * @return float               Estimated cost in USD cents.
	 */
	public static function estimate_cost_cents( int $total_tokens, string $model = '' ): float {
		if ( '' === $model || ! isset( self::MODELS[ $model ] ) ) {
			$model = self::get_model();
		}

		$meta       = self::MODELS[ $model ];
		$input_rate  = $meta['cost_per_1k_input_cents'];
		$output_rate = $meta['cost_per_1k_output_cents'];

		// Blended: 40 % input, 60 % output.
		$blended_rate = ( $input_rate * 0.4 ) + ( $output_rate * 0.6 );
		return ( $total_tokens / 1000.0 ) * $blended_rate;
	}

	/**
	 * Send a prompt and return the AI-generated text.
	 *
	 * Retries on 429 (rate limit) up to MAX_RETRIES times with exponential
	 * backoff + jitter.  Respects the circuit breaker – throws immediately
	 * when the circuit is open.
	 *
	 * @param string $prompt        The full prompt to send.
	 * @param int    $max_tokens    Maximum tokens in the response (default 2 048).
	 * @return string               Generated content, or empty string on failure.
	 * @throws \RuntimeException    When the request fails, circuit is open, or API returns an error.
	 */
	public function generate( string $prompt, int $max_tokens = 2048 ): string {
		if ( '' === $this->api_key ) {
			throw new \RuntimeException( 'PearBlog Engine: OpenAI API key is not configured.' );
		}

		$this->assert_circuit_closed();

		$last_exception = null;

		for ( $attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++ ) {
			try {
				$result = $this->do_request( $prompt, $max_tokens );
				$this->record_success();
				return $result;
			} catch ( RateLimitException $e ) {
				$last_exception = $e;
				if ( $attempt < self::MAX_RETRIES ) {
					$this->backoff( $attempt );
					continue;
				}
				// Exhausted retries – record failure and rethrow.
				$this->record_failure();
				throw new \RuntimeException(
					'PearBlog Engine: OpenAI rate limit exceeded after ' . self::MAX_RETRIES . ' retries.',
					429,
					$e
				);
			} catch ( \Throwable $e ) {
				$this->record_failure();
				throw $e;
			}
		}

		// Should not be reachable, but satisfies static analysis.
		$this->record_failure();
		throw $last_exception ?? new \RuntimeException( 'PearBlog Engine: AI generation failed.' );
	}

	// -----------------------------------------------------------------------
	// Cost tracking
	// -----------------------------------------------------------------------

	/**
	 * Return total estimated API spend in USD cents since tracking began.
	 *
	 * @return float USD cents.
	 */
	public static function get_total_cost_cents(): float {
		return (float) get_option( self::COST_OPTION, 0 );
	}

	/**
	 * Reset the cumulative cost counter to zero.
	 */
	public static function reset_cost(): void {
		update_option( self::COST_OPTION, 0 );
	}

	// -----------------------------------------------------------------------
	// Circuit breaker helpers (public for testing / admin reset)
	// -----------------------------------------------------------------------

	/**
	 * Return whether the circuit breaker is currently open (blocking calls).
	 */
	public static function is_circuit_open(): bool {
		$state = self::get_circuit_state();
		if ( $state['open'] && time() >= $state['retry_after'] ) {
			// Cooldown elapsed – transition to half-open by resetting.
			self::reset_circuit();
			return false;
		}
		return $state['open'];
	}

	/**
	 * Manually reset the circuit breaker (e.g. from an admin action).
	 */
	public static function reset_circuit(): void {
		update_option( self::CB_STATE_OPTION, [ 'failures' => 0, 'open' => false, 'retry_after' => 0 ] );
	}

	// -----------------------------------------------------------------------
	// Private implementation
	// -----------------------------------------------------------------------

	/**
	 * Perform a single HTTP request to the OpenAI API.
	 *
	 * @throws RateLimitException  On HTTP 429.
	 * @throws \RuntimeException   On any other error.
	 */
	private function do_request( string $prompt, int $max_tokens ): string {
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

		$content = trim( $data['choices'][0]['message']['content'] ?? '' );

		// Track token usage using per-model cost rates.
		$prompt_tokens     = (int) ( $data['usage']['prompt_tokens']     ?? 0 );
		$completion_tokens = (int) ( $data['usage']['completion_tokens'] ?? 0 );
		$total_tokens      = (int) ( $data['usage']['total_tokens']      ?? ( $prompt_tokens + $completion_tokens ) );

		if ( $total_tokens > 0 ) {
			$meta       = self::MODELS[ $this->model ] ?? self::MODELS[ self::DEFAULT_MODEL ];
			$cost_cents = ( $prompt_tokens / 1000.0 ) * $meta['cost_per_1k_input_cents']
			            + ( $completion_tokens / 1000.0 ) * $meta['cost_per_1k_output_cents'];
			$existing   = (float) get_option( self::COST_OPTION, 0 );
			update_option( self::COST_OPTION, $existing + $cost_cents );
		}

		return $content;
	}

	/**
	 * Sleep using exponential backoff with full jitter.
	 *
	 * @param int $attempt Zero-based attempt index.
	 */
	private function backoff( int $attempt ): void {
		$max_delay = self::BASE_DELAY_SECONDS * ( 2 ** $attempt );
		$delay     = random_int( 1, max( 1, (int) $max_delay ) );
		error_log( "PearBlog Engine: rate limited – retrying in {$delay}s (attempt " . ( $attempt + 1 ) . ').' );
		sleep( $delay );
	}

	/**
	 * Throw if the circuit breaker is open.
	 */
	private function assert_circuit_closed(): void {
		if ( self::is_circuit_open() ) {
			$state = self::get_circuit_state();
			$eta   = max( 0, $state['retry_after'] - time() );
			throw new \RuntimeException(
				"PearBlog Engine: AI circuit breaker is OPEN. Retry in {$eta}s."
			);
		}
	}

	/**
	 * Record a successful API call (resets consecutive failure count).
	 */
	private function record_success(): void {
		$state              = self::get_circuit_state();
		$state['failures']  = 0;
		$state['open']      = false;
		$state['retry_after'] = 0;
		update_option( self::CB_STATE_OPTION, $state );
	}

	/**
	 * Record a failed API call.  Opens the circuit after too many failures.
	 */
	private function record_failure(): void {
		$state = self::get_circuit_state();
		$state['failures']++;

		if ( $state['failures'] >= self::CIRCUIT_FAILURE_THRESHOLD ) {
			$state['open']        = true;
			$state['retry_after'] = time() + self::CIRCUIT_COOLDOWN_SECONDS;
			error_log( sprintf(
				'PearBlog Engine: AI circuit breaker OPENED after %d failures. Will retry at %s.',
				$state['failures'],
				gmdate( 'Y-m-d H:i:s', $state['retry_after'] )
			) );
		}

		update_option( self::CB_STATE_OPTION, $state );
	}

	/**
	 * Read circuit-breaker state from the database.
	 *
	 * @return array{failures: int, open: bool, retry_after: int}
	 */
	private static function get_circuit_state(): array {
		$default = [ 'failures' => 0, 'open' => false, 'retry_after' => 0 ];
		$stored  = get_option( self::CB_STATE_OPTION, $default );
		return is_array( $stored ) ? array_merge( $default, $stored ) : $default;
	}
}

/**
 * Internal exception used to signal HTTP 429 rate-limit responses.
 *
 * @internal
 */
class RateLimitException extends \RuntimeException {}

