<?php
/**
 * AI client – sends prompts to OpenAI with retry logic and circuit breaker.
 *
 * v6 features:
 *   - Exponential backoff with jitter (MAX_RETRIES = 3)
 *   - Circuit breaker: after CIRCUIT_FAILURE_THRESHOLD consecutive failures
 *     the client refuses new requests for CIRCUIT_COOLDOWN_SECONDS seconds.
 *   - Cost tracking via the `pearblog_ai_cost_cents` option.
 *   - Pluggable via `pearblog_ai_request_args` and `pearblog_ai_response` filters.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Resilient OpenAI Chat Completions API wrapper.
 */
class AIClient {

	private const API_URL = 'https://api.openai.com/v1/chat/completions';
	private const MODEL   = 'gpt-4o-mini';

	/** Maximum retry attempts for transient failures. */
	private const MAX_RETRIES = 3;

	/** Base delay in seconds for exponential backoff (actual delay includes jitter). */
	private const RETRY_BASE_DELAY = 2;

	/** Number of consecutive failures before the circuit opens. */
	private const CIRCUIT_FAILURE_THRESHOLD = 5;

	/** Seconds to wait before the circuit half-opens (allows a single probe request). */
	private const CIRCUIT_COOLDOWN_SECONDS = 300;

	/** Approximate cost per 1 K input tokens (GPT-4o-mini) in cents. */
	private const COST_PER_1K_INPUT  = 0.015; // $0.00015/token → 0.015¢ per 1 K
	/** Approximate cost per 1 K output tokens (GPT-4o-mini) in cents. */
	private const COST_PER_1K_OUTPUT = 0.060; // $0.0006/token  → 0.06¢ per 1 K

	private string $api_key;

	public function __construct( string $api_key = '' ) {
		if ( '' === $api_key ) {
			$api_key = defined( 'PEARBLOG_OPENAI_API_KEY' )
				? PEARBLOG_OPENAI_API_KEY
				: (string) get_option( 'pearblog_openai_api_key', '' );
		}
		$this->api_key = $api_key;
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Send a prompt and return the AI-generated text.
	 *
	 * Retries transient failures with exponential back-off.  Respects the
	 * circuit breaker – throws immediately if the circuit is open.
	 *
	 * @param string $prompt     The full prompt to send.
	 * @param int    $max_tokens Maximum tokens in the response (default 2 048).
	 * @return string            Generated content.
	 * @throws \RuntimeException On permanent failures or open circuit.
	 */
	public function generate( string $prompt, int $max_tokens = 2048 ): string {
		if ( '' === $this->api_key ) {
			throw new \RuntimeException( 'PearBlog Engine: OpenAI API key is not configured.' );
		}

		$this->assert_circuit_closed();

		$last_exception = null;

		for ( $attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++ ) {
			if ( $attempt > 0 ) {
				$this->backoff_sleep( $attempt );
			}

			try {
				$result = $this->do_request( $prompt, $max_tokens );

				// Success → reset the failure counter.
				$this->record_success();

				return $result;
			} catch ( \RuntimeException $e ) {
				$last_exception = $e;

				// Only retry on transient (5xx / timeout) errors.
				if ( ! $this->is_retryable( $e ) ) {
					$this->record_failure();
					throw $e;
				}
			}
		}

		// All retries exhausted.
		$this->record_failure();
		throw $last_exception ?? new \RuntimeException( 'PearBlog Engine: AI request failed after retries.' );
	}

	/**
	 * Reset the circuit breaker manually (e.g. via WP-CLI or admin action).
	 */
	public static function reset_circuit(): void {
		delete_option( 'pearblog_circuit_failures' );
		delete_option( 'pearblog_circuit_opened_at' );
	}

	/**
	 * Return the cumulative AI cost tracked in cents.
	 */
	public static function total_cost_cents(): float {
		return (float) get_option( 'pearblog_ai_cost_cents', 0 );
	}

	// -----------------------------------------------------------------------
	// HTTP request
	// -----------------------------------------------------------------------

	/**
	 * Perform a single HTTP request to the OpenAI API.
	 *
	 * @return string Generated text.
	 * @throws \RuntimeException On HTTP or API error.
	 */
	private function do_request( string $prompt, int $max_tokens ): string {
		$body = wp_json_encode( [
			'model'      => self::MODEL,
			'messages'   => [
				[ 'role' => 'user', 'content' => $prompt ],
			],
			'max_tokens' => $max_tokens,
		] );

		/**
		 * Filter: pearblog_ai_request_args
		 *
		 * Customise the wp_remote_post arguments before the request is sent.
		 *
		 * @param array  $args   wp_remote_post args.
		 * @param string $prompt The original prompt text.
		 */
		$args = apply_filters( 'pearblog_ai_request_args', [
			'timeout' => 60,
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_key,
			],
			'body' => $body,
		], $prompt );

		$response = wp_remote_post( self::API_URL, $args );

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException(
				'PearBlog Engine: HTTP request failed – ' . $response->get_error_message(),
				0
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status || ! is_array( $data ) ) {
			$message = $data['error']['message'] ?? 'Unknown API error';
			throw new \RuntimeException(
				"PearBlog Engine: OpenAI API error ({$status}) – {$message}",
				$status
			);
		}

		$text = trim( $data['choices'][0]['message']['content'] ?? '' );

		// Track cost.
		$this->track_cost( $data );

		/**
		 * Filter: pearblog_ai_response
		 *
		 * Post-process the generated text before it is returned to callers.
		 *
		 * @param string $text   Generated text.
		 * @param array  $data   Full API response data.
		 * @param string $prompt Original prompt.
		 */
		return (string) apply_filters( 'pearblog_ai_response', $text, $data, $prompt );
	}

	// -----------------------------------------------------------------------
	// Circuit breaker
	// -----------------------------------------------------------------------

	/**
	 * @throws \RuntimeException If the circuit breaker is open.
	 */
	private function assert_circuit_closed(): void {
		$failures  = (int) get_option( 'pearblog_circuit_failures', 0 );
		$opened_at = (int) get_option( 'pearblog_circuit_opened_at', 0 );

		if ( $failures < self::CIRCUIT_FAILURE_THRESHOLD ) {
			return; // Closed.
		}

		$elapsed = time() - $opened_at;

		if ( $elapsed < self::CIRCUIT_COOLDOWN_SECONDS ) {
			throw new \RuntimeException( sprintf(
				'PearBlog Engine: Circuit breaker OPEN — %d consecutive failures. Cooldown ends in %d s.',
				$failures,
				self::CIRCUIT_COOLDOWN_SECONDS - $elapsed
			) );
		}

		// Half-open: cooldown expired → let one request through.
	}

	private function record_failure(): void {
		$failures = (int) get_option( 'pearblog_circuit_failures', 0 ) + 1;
		update_option( 'pearblog_circuit_failures', $failures, false );

		if ( $failures >= self::CIRCUIT_FAILURE_THRESHOLD ) {
			update_option( 'pearblog_circuit_opened_at', time(), false );

			error_log( sprintf(
				'PearBlog Engine: Circuit breaker OPENED after %d consecutive failures.',
				$failures
			) );
		}
	}

	private function record_success(): void {
		$failures = (int) get_option( 'pearblog_circuit_failures', 0 );
		if ( $failures > 0 ) {
			update_option( 'pearblog_circuit_failures', 0, false );
			delete_option( 'pearblog_circuit_opened_at' );
		}
	}

	// -----------------------------------------------------------------------
	// Retry helpers
	// -----------------------------------------------------------------------

	/**
	 * Determine whether the exception represents a transient error.
	 */
	private function is_retryable( \RuntimeException $e ): bool {
		$code = $e->getCode();

		// Retry on: 0 (timeout/connection error), 429 (rate-limit), 5xx.
		return 0 === $code || 429 === $code || ( $code >= 500 && $code < 600 );
	}

	/**
	 * Sleep with exponential backoff + jitter.
	 */
	private function backoff_sleep( int $attempt ): void {
		$base  = self::RETRY_BASE_DELAY * ( 2 ** ( $attempt - 1 ) );
		$delay = $base + random_int( 0, (int) ( $base * 0.5 ) );
		sleep( $delay );
	}

	// -----------------------------------------------------------------------
	// Cost tracking
	// -----------------------------------------------------------------------

	/**
	 * Increment the cumulative cost option based on token usage in the
	 * API response.
	 */
	private function track_cost( array $data ): void {
		$usage = $data['usage'] ?? null;
		if ( ! is_array( $usage ) ) {
			return;
		}

		$input_tokens  = (int) ( $usage['prompt_tokens']     ?? 0 );
		$output_tokens = (int) ( $usage['completion_tokens'] ?? 0 );

		$cost_cents = ( $input_tokens / 1000 ) * self::COST_PER_1K_INPUT
		            + ( $output_tokens / 1000 ) * self::COST_PER_1K_OUTPUT;

		if ( $cost_cents > 0 ) {
			$current = (float) get_option( 'pearblog_ai_cost_cents', 0 );
			update_option( 'pearblog_ai_cost_cents', $current + $cost_cents, false );
		}
	}
}
