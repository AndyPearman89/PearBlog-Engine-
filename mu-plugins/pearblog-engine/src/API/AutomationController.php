<?php
/**
 * Automation REST API controller.
 *
 * Exposes endpoints consumed by the external Python automation scripts
 * (automation_orchestrator.py, run_pipeline.py) so that GitHub Actions
 * workflows can drive the WordPress-side ContentPipeline remotely.
 *
 * Endpoints:
 *   POST /pearblog/v1/automation/create-content  – queue a topic + run pipeline
 *   POST /pearblog/v1/automation/process-content – trigger next pipeline cycle
 *   GET  /pearblog/v1/automation/status           – queue & pipeline health
 *
 * @package PearBlogEngine\API
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Pipeline\ContentPipeline;
use PearBlogEngine\Tenant\TenantContext;
use PearBlogEngine\API\RateLimiter;

/**
 * Registers and handles the automation REST API routes.
 */
class AutomationController {

	private const NAMESPACE = 'pearblog/v1';
	private const BASE      = 'automation';

	/**
	 * Register REST routes (called via rest_api_init).
	 */
	public function register_routes(): void {
		// POST /pearblog/v1/automation/create-content
		register_rest_route( self::NAMESPACE, self::BASE . '/create-content', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'create_content' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => $this->create_content_args(),
		] );

		// POST /pearblog/v1/automation/process-content
		register_rest_route( self::NAMESPACE, self::BASE . '/process-content', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'process_content' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'action' => [
					'type'              => 'string',
					'default'           => 'process_content',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'seo_enabled' => [
					'type'    => 'boolean',
					'default' => true,
				],
			],
		] );

		// GET /pearblog/v1/automation/status
		register_rest_route( self::NAMESPACE, self::BASE . '/status', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_status' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Permission check
	// -----------------------------------------------------------------------

	/**
	 * Verify that the request carries a valid Bearer token matching the
	 * stored API key, or that the current user has manage_options capability.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return bool|\WP_Error
	 */
	public function check_permission( \WP_REST_Request $request ) {
		// Allow admin users (standard WP auth / cookie / application-password).
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Validate Bearer token against stored API key.
		$stored_key = (string) get_option( 'pearblog_api_key', '' );

		if ( '' === $stored_key ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'API key not configured. Set the pearblog_api_key option.', 'pearblog-engine' ),
				[ 'status' => 403 ]
			);
		}

		$auth_header = $request->get_header( 'Authorization' );
		if ( empty( $auth_header ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Missing Authorization header.', 'pearblog-engine' ),
				[ 'status' => 401 ]
			);
		}

		// Accept "Bearer <token>".
		if ( 0 !== strncasecmp( $auth_header, 'Bearer ', 7 ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Authorization header must use Bearer scheme.', 'pearblog-engine' ),
				[ 'status' => 401 ]
			);
		}

		$token = substr( $auth_header, 7 );

		if ( ! hash_equals( $stored_key, $token ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Invalid API key.', 'pearblog-engine' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	// -----------------------------------------------------------------------
	// Endpoint: create-content
	// -----------------------------------------------------------------------

	/**
	 * Handle POST /automation/create-content.
	 *
	 * Pushes the topic to the queue and immediately runs the pipeline for it.
	 * Called by automation_orchestrator.py with a full content brief.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_content( \WP_REST_Request $request ) {
		$limiter   = new RateLimiter();
		$client_id = $limiter->get_client_id( $request );
		$rate      = $limiter->check( $client_id, 'create_content', RateLimiter::LIMIT_PIPELINE );

		if ( ! $rate['allowed'] ) {
			return $limiter->too_many_requests( $rate );
		}

		$keyword  = (string) $request->get_param( 'keyword' );
		$title    = (string) $request->get_param( 'title' );
		$topic    = '' !== $title ? $title : $keyword;

		if ( '' === $topic ) {
			return new \WP_Error(
				'missing_topic',
				__( 'A keyword or title is required.', 'pearblog-engine' ),
				[ 'status' => 400 ]
			);
		}

		$site_id = get_current_blog_id();
		$queue   = new TopicQueue( $site_id );

		// Push topic to the front of the queue so it is processed next.
		$existing = $queue->all();
		$queue->clear();
		$queue->push( $topic );
		$queue->push( ...$existing );

		// Store the content brief as a transient so the pipeline can use it.
		$brief_data = [
			'keyword'           => $keyword,
			'title'             => $title,
			'headings'          => $request->get_param( 'headings' ) ?? [],
			'target_word_count' => (int) ( $request->get_param( 'target_word_count' ) ?? 2000 ),
			'keywords'          => $request->get_param( 'keywords' ) ?? [],
			'priority'          => (int) ( $request->get_param( 'priority' ) ?? 5 ),
			'serp_analysis'     => $request->get_param( 'serp_analysis' ) ?? [],
		];
		set_transient( 'pearblog_brief_' . hash( 'sha256', $topic ), $brief_data, DAY_IN_SECONDS );

		// Run the pipeline immediately.
		$context  = TenantContext::for_site( $site_id );
		$pipeline = new ContentPipeline( $context );

		try {
			$result = $pipeline->run();

			if ( null === $result ) {
				return new \WP_Error(
					'pipeline_empty',
					__( 'Pipeline ran but the queue was empty (race condition).', 'pearblog-engine' ),
					[ 'status' => 500 ]
				);
			}

			$response = new \WP_REST_Response( [
				'success' => true,
				'message' => __( 'Content created and published.', 'pearblog-engine' ),
				'post_id' => $result['post_id'],
				'topic'   => $result['topic'],
				'status'  => $result['status'],
				'url'     => get_permalink( $result['post_id'] ),
			], 201 );
			$limiter->add_headers( $response, $rate );
			return $response;

		} catch ( \Throwable $e ) {
			error_log( 'PearBlog Engine API: create-content failed – ' . $e->getMessage() );

			return new \WP_Error(
				'pipeline_failed',
				__( 'Content pipeline failed.', 'pearblog-engine' ),
				[ 'status' => 500 ]
			);
		}
	}

	// -----------------------------------------------------------------------
	// Endpoint: process-content
	// -----------------------------------------------------------------------

	/**
	 * Handle POST /automation/process-content.
	 *
	 * Triggers the next pipeline cycle (pops from queue), used by
	 * run_pipeline.py / content-pipeline.yml GitHub Action.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function process_content( \WP_REST_Request $request ) {
		$limiter   = new RateLimiter();
		$client_id = $limiter->get_client_id( $request );
		$rate      = $limiter->check( $client_id, 'process_content', RateLimiter::LIMIT_WRITE );

		if ( ! $rate['allowed'] ) {
			return $limiter->too_many_requests( $rate );
		}

		$site_id  = get_current_blog_id();
		$context  = TenantContext::for_site( $site_id );
		$pipeline = new ContentPipeline( $context );

		$publish_rate        = $context->profile->publish_rate;
		$articles_to_publish = ( $publish_rate > 0 ) ? $publish_rate : 1;
		$results             = [];

		for ( $i = 0; $i < $articles_to_publish; $i++ ) {
			try {
				$result = $pipeline->run();
				if ( null === $result ) {
					break; // Queue exhausted.
				}
				$results[] = $result;
			} catch ( \Throwable $e ) {
				error_log( 'PearBlog Engine API: process-content cycle failed – ' . $e->getMessage() );
				$results[] = [
					'topic'  => 'unknown',
					'status' => 'failed',
					'error'  => $e->getMessage(),
				];
			}
		}

		if ( empty( $results ) ) {
			$response = new \WP_REST_Response( [
				'success'  => true,
				'message'  => __( 'No topics in queue – nothing to process.', 'pearblog-engine' ),
				'articles' => [],
			], 200 );
			$limiter->add_headers( $response, $rate );
			return $response;
		}

		$response = new \WP_REST_Response( [
			'success'  => true,
			'message'  => sprintf(
				/* translators: %d: number of articles processed */
				__( '%d article(s) processed.', 'pearblog-engine' ),
				count( $results )
			),
			'articles' => $results,
		], 200 );
		$limiter->add_headers( $response, $rate );
		return $response;
	}

	// -----------------------------------------------------------------------
	// Endpoint: status
	// -----------------------------------------------------------------------

	/**
	 * Handle GET /automation/status.
	 *
	 * Returns queue length, next topic, last pipeline result, and site profile.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_status( \WP_REST_Request $request ) {
		$limiter   = new RateLimiter();
		$client_id = $limiter->get_client_id( $request );
		$rate      = $limiter->check( $client_id, 'get_status', RateLimiter::LIMIT_READ );

		if ( ! $rate['allowed'] ) {
			return $limiter->too_many_requests( $rate );
		}

		$site_id = get_current_blog_id();
		$queue   = new TopicQueue( $site_id );
		$context = TenantContext::for_site( $site_id );

		$response = new \WP_REST_Response( [
			'site_id'       => $site_id,
			'queue_length'  => $queue->count(),
			'next_topic'    => $queue->peek(),
			'profile'       => [
				'industry'      => $context->profile->industry,
				'tone'          => $context->profile->tone,
				'monetization'  => $context->profile->monetization,
				'publish_rate'  => $context->profile->publish_rate,
				'language'      => $context->profile->language,
			],
			'cron_scheduled' => (bool) wp_next_scheduled( 'pearblog_run_pipeline' ),
			'timestamp'      => current_time( 'mysql' ),
		], 200 );
		$limiter->add_headers( $response, $rate );
		return $response;
	}

	// -----------------------------------------------------------------------
	// Argument schemas
	// -----------------------------------------------------------------------

	/**
	 * Argument definitions for the create-content endpoint.
	 *
	 * @return array
	 */
	private function create_content_args(): array {
		return [
			'keyword' => [
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			],
			'title' => [
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			],
			'headings' => [
				'type'    => 'array',
				'default' => [],
				'items'   => [ 'type' => 'string' ],
			],
			'target_word_count' => [
				'type'              => 'integer',
				'default'           => 2000,
				'sanitize_callback' => 'absint',
			],
			'keywords' => [
				'type'    => 'array',
				'default' => [],
				'items'   => [ 'type' => 'string' ],
			],
			'priority' => [
				'type'              => 'integer',
				'default'           => 5,
				'sanitize_callback' => 'absint',
			],
			'serp_analysis' => [
				'type'    => 'object',
				'default' => [],
			],
		];
	}
}
