<?php
/**
 * Health Controller – REST API endpoint for system health checks.
 *
 * Provides a GET /pearblog/v1/health endpoint that returns:
 *   - Queue size and status
 *   - Pipeline last-run timestamp
 *   - Circuit breaker state
 *   - AI cost tracking
 *   - System info (PHP version, WP version, plugin version)
 *
 * Designed for uptime monitoring tools (UptimeRobot, Pingdom, GitHub Actions).
 *
 * @package PearBlogEngine\Monitoring
 */

declare(strict_types=1);

namespace PearBlogEngine\Monitoring;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\Content\TopicQueue;

/**
 * Registers the /pearblog/v1/health REST route.
 */
class HealthController {

	private const NAMESPACE = 'pearblog/v1';

	/**
	 * Register REST routes (call on rest_api_init).
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/health', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'handle' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );
	}

	/**
	 * Permission check: allow authenticated admins or requests with a valid API key.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return bool
	 */
	public function check_permission( \WP_REST_Request $request ): bool {
		// Admin users always allowed.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// API key auth (same as AutomationController).
		$api_key = (string) get_option( 'pearblog_api_key', '' );
		if ( '' !== $api_key ) {
			$provided = $request->get_header( 'X-PearBlog-Key' )
			         ?? $request->get_param( 'api_key' )
			         ?? '';
			return hash_equals( $api_key, (string) $provided );
		}

		return false;
	}

	/**
	 * Handle the health check request.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$site_id = get_current_blog_id();
		$queue   = new TopicQueue( $site_id );

		// Circuit breaker state.
		$circuit_failures = (int) get_option( 'pearblog_circuit_failures', 0 );
		$circuit_opened   = (int) get_option( 'pearblog_circuit_opened_at', 0 );
		$circuit_status   = 'closed';

		if ( $circuit_failures >= 5 ) {
			$elapsed = time() - $circuit_opened;
			$circuit_status = $elapsed < 300 ? 'open' : 'half-open';
		}

		// Pipeline timing.
		$last_run = (int) get_option( 'pearblog_last_pipeline_run', 0 );

		// Determine overall health.
		$status = 'healthy';
		$issues = [];

		if ( 'open' === $circuit_status ) {
			$status   = 'degraded';
			$issues[] = 'Circuit breaker is open — AI requests are blocked.';
		}

		if ( $queue->count() === 0 ) {
			$issues[] = 'Topic queue is empty — no articles will be generated.';
		}

		if ( $last_run > 0 && ( time() - $last_run ) > 7200 ) {
			$issues[] = 'Pipeline has not run in over 2 hours.';
		}

		if ( ! empty( $issues ) && 'healthy' === $status ) {
			$status = 'warning';
		}

		$data = [
			'status'    => $status,
			'version'   => defined( 'PEARBLOG_ENGINE_VERSION' ) ? PEARBLOG_ENGINE_VERSION : 'unknown',
			'timestamp' => gmdate( 'c' ),
			'queue'     => [
				'size'      => $queue->count(),
				'next_topic' => $queue->peek(),
			],
			'pipeline'  => [
				'last_run'      => $last_run > 0 ? gmdate( 'c', $last_run ) : null,
				'last_run_ago'  => $last_run > 0 ? human_time_diff( $last_run ) . ' ago' : 'never',
			],
			'circuit_breaker' => [
				'status'   => $circuit_status,
				'failures' => $circuit_failures,
			],
			'ai_cost' => [
				'total_cents' => round( AIClient::total_cost_cents(), 2 ),
			],
			'system'  => [
				'php_version' => PHP_VERSION,
				'wp_version'  => get_bloginfo( 'version' ),
				'multisite'   => is_multisite(),
				'site_id'     => $site_id,
			],
			'issues'  => $issues,
		];

		$http_status = 'degraded' === $status ? 503 : 200;

		return new \WP_REST_Response( $data, $http_status );
	}
}
