<?php
/**
 * AI client – sends prompts to OpenAI and returns generated content.
 *
 * Features:
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
	private const MODEL   = 'gpt-4o-mini';

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

	// Approximate cost per 1 000 tokens for gpt-4o-mini (input + output avg).
	private const COST_PER_1K_TOKENS_CENTS = 0.015; // $0.00015 / token = $0.15 / 1k

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
			'model'      => self::MODEL,
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

		// Track token usage and estimated cost.
		$total_tokens = (int) ( $data['usage']['total_tokens'] ?? 0 );
		if ( $total_tokens > 0 ) {
			$cost_cents = ( $total_tokens / 1000 ) * self::COST_PER_1K_TOKENS_CENTS;
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
