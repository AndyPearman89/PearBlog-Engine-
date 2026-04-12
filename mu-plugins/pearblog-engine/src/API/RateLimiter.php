<?php
/**
 * API rate limiter using WordPress transients.
 *
 * Tracks per-client request counts within a rolling window and exposes
 * helpers to check limits and obtain data for standard rate-limit headers.
 *
 * Usage in a controller:
 *
 *   $limiter   = new RateLimiter();
 *   $client_id = $limiter->get_client_id( $request );
 *   $rate      = $limiter->check( $client_id, 'create_content', RateLimiter::LIMIT_PIPELINE );
 *
 *   if ( ! $rate['allowed'] ) {
 *       return $limiter->too_many_requests( $rate );
 *   }
 *
 *   $response = new \WP_REST_Response( $data, 200 );
 *   $limiter->add_headers( $response, $rate );
 *   return $response;
 *
 * @package PearBlogEngine\API
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

/**
 * Tracks per-client request counts and enforces configured rate limits.
 *
 * Each window is rolling: the transient storing the request count expires
 * after WINDOW_SECONDS.  A companion WordPress option records the window
 * reset timestamp so the `X-RateLimit-Reset` header can be populated.
 */
class RateLimiter {

	/** Rolling window length in seconds. */
	public const WINDOW_SECONDS = 60;

	/** Maximum requests per window for read (GET) endpoints. */
	public const LIMIT_READ = 120;

	/** Maximum requests per window for write (POST/DELETE) endpoints. */
	public const LIMIT_WRITE = 30;

	/** Maximum requests per window for the pipeline-trigger endpoint. */
	public const LIMIT_PIPELINE = 5;

	/** Transient key prefix for per-client counters. */
	private const TRANSIENT_PREFIX = 'pb_rl_';

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Check whether the client may make another request and record the attempt.
	 *
	 * Returns an array describing the current window state:
	 *   - allowed    (bool)  – true when the request is within the limit
	 *   - remaining  (int)   – requests remaining in this window
	 *   - limit      (int)   – the configured limit
	 *   - reset      (int)   – Unix timestamp when the current window resets
	 *
	 * @param string $client_id Unique identifier for the requesting client.
	 * @param string $endpoint  Short identifier for the endpoint (e.g. 'create_content').
	 * @param int    $limit     Maximum requests allowed per window.
	 * @return array{allowed: bool, remaining: int, limit: int, reset: int}
	 */
	public function check( string $client_id, string $endpoint, int $limit ): array {
		$transient_key = self::TRANSIENT_PREFIX . substr( md5( $client_id . $endpoint ), 0, 16 );
		$reset_key     = $transient_key . '_reset';

		$reset_ts = (int) get_option( $reset_key, 0 );
		$now      = time();

		// Start a new window when none exists or the previous one has expired.
		if ( 0 === $reset_ts || $now >= $reset_ts ) {
			$reset_ts = $now + self::WINDOW_SECONDS;
			update_option( $reset_key, $reset_ts );
			delete_transient( $transient_key );
		}

		$count = (int) get_transient( $transient_key ) + 1;
		set_transient( $transient_key, $count, self::WINDOW_SECONDS );

		return [
			'allowed'   => $count <= $limit,
			'remaining' => max( 0, $limit - $count ),
			'limit'     => $limit,
			'reset'     => $reset_ts,
		];
	}

	/**
	 * Derive a stable client identifier from a REST request.
	 *
	 * Prefers a hashed Bearer token so different API keys each receive their
	 * own quota.  Falls back to the client IP address.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return string
	 */
	public function get_client_id( \WP_REST_Request $request ): string {
		$auth = (string) $request->get_header( 'Authorization' );
		if ( str_starts_with( $auth, 'Bearer ' ) ) {
			return 'bearer_' . substr( md5( substr( $auth, 7 ) ), 0, 16 );
		}

		// Fall back to IP address.  When REMOTE_ADDR is absent (e.g. CLI),
		// use a constant sentinel so that CLI-invoked requests share one
		// bucket rather than each getting an anonymous unlimited quota.
		$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? 'cli' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return 'ip_' . $ip;
	}

	/**
	 * Add standard rate-limit response headers to a REST response.
	 *
	 * Sets X-RateLimit-Limit, X-RateLimit-Remaining, and X-RateLimit-Reset.
	 *
	 * @param \WP_REST_Response                                          $response REST response to annotate.
	 * @param array{allowed: bool, remaining: int, limit: int, reset: int} $rate     Result from check().
	 */
	public function add_headers( \WP_REST_Response $response, array $rate ): void {
		$response->header( 'X-RateLimit-Limit',     (string) $rate['limit'] );
		$response->header( 'X-RateLimit-Remaining', (string) $rate['remaining'] );
		$response->header( 'X-RateLimit-Reset',     (string) $rate['reset'] );
	}

	/**
	 * Build a 429 WP_Error response for requests that exceed the limit.
	 *
	 * Includes a Retry-After value equal to the seconds remaining in the window.
	 *
	 * @param array{allowed: bool, remaining: int, limit: int, reset: int} $rate Result from check().
	 * @return \WP_Error
	 */
	public function too_many_requests( array $rate ): \WP_Error {
		$retry_after = max( 1, $rate['reset'] - time() );

		return new \WP_Error(
			'rate_limit_exceeded',
			sprintf(
				/* translators: %d: seconds to wait before retrying */
				__( 'Too many requests. Please wait %d seconds.', 'pearblog-engine' ),
				$retry_after
			),
			[
				'status'      => 429,
				'retry_after' => $retry_after,
			]
		);
	}
}
