<?php
/**
 * PearBlog Health Route Fix.
 *
 * Registers the /pearblog/v1/health REST endpoint independently so it remains
 * available even if the main MU-plugin bootstrap is not refreshed yet.
 *
 * @package PearBlogEngine
 */

declare(strict_types=1);

add_action( 'rest_api_init', static function (): void {
	register_rest_route(
		'pearblog/v1',
		'/health',
		[
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => static function ( \WP_REST_Request $request ): \WP_REST_Response {
				$api_key_set = '' !== (string) get_option( 'pearblog_openai_api_key', '' );
				$last_run_ts  = (int) get_option( 'pearblog_last_pipeline_run', 0 );
				$queue_state  = get_option( 'pearblog_topic_queue', [] );
				$queue_size   = is_array( $queue_state ) ? count( $queue_state ) : 0;

				$overall = $api_key_set ? 'ok' : 'degraded';

				return new \WP_REST_Response(
					[
						'overall'     => $overall,
						'status'      => $overall,
						'timestamp'   => current_time( 'mysql' ),
						'api_key_set' => $api_key_set,
						'queue_size'  => $queue_size,
						'last_run'    => $last_run_ts > 0 ? gmdate( 'c', $last_run_ts ) : null,
					],
					200
				);
			},
			'permission_callback' => static function ( \WP_REST_Request $request ): bool {
				$secret = (string) get_option( 'pearblog_health_secret', '' );

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
			},
		]
	);
} );
