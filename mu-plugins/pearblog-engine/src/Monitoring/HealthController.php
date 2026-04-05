<?php
/**
 * Health controller – exposes a system health-check REST endpoint.
 *
 * Endpoint: GET /pearblog/v1/health
 *
 * Access: requires `manage_options` or a shared secret provided via
 * `X-PearBlog-Health-Secret` header or `health_secret` query param.
 *
 * Returns a JSON object with status indicators for each sub-system:
 *  - api_key         – Whether the OpenAI key is configured.
 *  - openai          – Whether a lightweight test request succeeds (cached 5 min).
 *  - circuit_breaker – Current circuit-breaker state.
 *  - queue           – Number of topics waiting.
 *  - last_run        – Timestamp of the most recently published article.
 *  - ai_cost_usd     – Cumulative estimated API spend.
 *  - overall         – "ok" | "degraded" | "down"
 *
 * @package PearBlogEngine\Monitoring
 */

declare(strict_types=1);

namespace PearBlogEngine\Monitoring;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\Content\TopicQueue;

/**
 * Registers and handles the health-check REST route.
 */
class HealthController {

	private const NAMESPACE           = 'pearblog/v1';
	private const OPENAI_CHECK_TRANSIENT = 'pb_health_openai_check';
	private const HEALTH_SECRET_OPTION   = 'pearblog_health_secret';

	/**
	 * Register REST route (called via rest_api_init).
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/health', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_health' ],
			'permission_callback' => fn( \WP_REST_Request $request ) => $this->authorize_request( $request ),
		] );
	}

	/**
	 * Build and return the health-check response.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public function get_health( \WP_REST_Request $request ): \WP_REST_Response {
		$checks = [];

		// 1. API key configured.
		$api_key_set       = '' !== (string) get_option( 'pearblog_openai_api_key', '' );
		$checks['api_key'] = [
			'status'  => $api_key_set ? 'ok' : 'error',
			'detail'  => $api_key_set ? 'configured' : 'not configured',
		];

		// 2. Circuit breaker.
		$circuit_open              = AIClient::is_circuit_open();
		$checks['circuit_breaker'] = [
			'status' => $circuit_open ? 'error' : 'ok',
			'detail' => $circuit_open ? 'OPEN – AI calls blocked' : 'closed',
		];

		// 3. OpenAI connectivity (cached 5 min so we don't spam the API).
		$openai_check = get_transient( self::OPENAI_CHECK_TRANSIENT );
		if ( false === $openai_check ) {
			$openai_check = $this->check_openai_connectivity();
			set_transient( self::OPENAI_CHECK_TRANSIENT, $openai_check, 5 * MINUTE_IN_SECONDS );
		}
		$checks['openai'] = $openai_check;

		// 4. Topic queue.
		$site_id       = get_current_blog_id();
		$queue         = new TopicQueue( $site_id );
		$queue_length  = $queue->count();
		$checks['queue'] = [
			'status' => 'ok',
			'detail' => "{$queue_length} topics waiting",
			'count'  => $queue_length,
		];

		// 5. Last successful pipeline run.
		$last_run_ts = (int) get_option( 'pearblog_last_pipeline_run', 0 );
		$last_run    = $last_run_ts > 0 ? gmdate( 'Y-m-d H:i:s', $last_run_ts ) : 'never';
		$hours_since = $last_run_ts > 0 ? round( ( time() - $last_run_ts ) / HOUR_IN_SECONDS, 1 ) : null;

		$last_run_status = 'ok';
		if ( null === $hours_since || $hours_since > 48 ) {
			$last_run_status = 'warning';
		}

		$checks['last_run'] = [
			'status'      => $last_run_status,
			'detail'      => $last_run,
			'hours_since' => $hours_since,
		];

		// 6. Cost tracking.
		$cost_cents     = AIClient::get_total_cost_cents();
		$checks['ai_cost'] = [
			'status'    => 'ok',
			'usd_cents' => round( $cost_cents, 4 ),
			'usd'       => round( $cost_cents / 100, 4 ),
		];

		// 7. Articles published today.
		$today_count              = $this->count_articles_today();
		$checks['articles_today'] = [
			'status' => 'ok',
			'count'  => $today_count,
		];

		// Derive overall status.
		$overall = 'ok';
		foreach ( $checks as $check ) {
			if ( isset( $check['status'] ) ) {
				if ( 'error' === $check['status'] ) {
					$overall = 'down';
					break;
				}
				if ( 'warning' === $check['status'] && 'ok' === $overall ) {
					$overall = 'degraded';
				}
			}
		}

		$http_code = match ( $overall ) {
			'down'     => 503,
			'degraded' => 200, // 200 so uptime monitors don't falsely alert on degraded.
			default    => 200,
		};

		return new \WP_REST_Response(
			array_merge( [ 'overall' => $overall, 'timestamp' => current_time( 'mysql' ) ], $checks ),
			$http_code
		);
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Send a minimal test call to the OpenAI models endpoint (not a completion,
	 * just a list-models call to verify auth).
	 */
	private function check_openai_connectivity(): array {
		$api_key = (string) get_option( 'pearblog_openai_api_key', '' );
		if ( '' === $api_key ) {
			return [ 'status' => 'error', 'detail' => 'API key not configured' ];
		}

		$response = wp_remote_get( 'https://api.openai.com/v1/models', [
			'timeout' => 10,
			'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'status' => 'error', 'detail' => $response->get_error_message() ];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 === $code ) {
			return [ 'status' => 'ok', 'detail' => 'reachable' ];
		}
		if ( 401 === $code ) {
			return [ 'status' => 'error', 'detail' => 'invalid API key (401)' ];
		}
		if ( 429 === $code ) {
			return [ 'status' => 'warning', 'detail' => 'rate limited (429)' ];
		}

		return [ 'status' => 'warning', 'detail' => "HTTP {$code}" ];
	}

	/**
	 * Count published posts created today (in site local time).
	 */
	private function count_articles_today(): int {
		$today = current_time( 'Y-m-d' );

		$count = (int) ( new \WP_Query( [
			'post_status'    => 'publish',
			'date_query'     => [ [ 'after' => $today . ' 00:00:00', 'column' => 'post_date' ] ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		] ) )->found_posts;

		return $count;
	}

	/**
	 * Require either an auth secret (header/query) or manage_options capability.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	private function authorize_request( \WP_REST_Request $request ): bool {
		$secret = (string) get_option( self::HEALTH_SECRET_OPTION, '' );

		if ( '' !== $secret ) {
			$provided = (string) $request->get_header( 'x-pearblog-health-secret' );
			if ( '' === $provided ) {
				$provided = (string) $request->get_param( 'health_secret' );
			}

			if ( '' !== $provided && hash_equals( $secret, $provided ) ) {
				return true;
			}
		}

		return current_user_can( 'manage_options' );
	}
}
