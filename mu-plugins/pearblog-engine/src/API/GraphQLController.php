<?php
/**
 * GraphQL Controller — lightweight GraphQL endpoint for PearBlog Engine data.
 *
 * Provides two integration paths:
 *   1. WPGraphQL extension — if the WPGraphQL plugin is active, registers
 *      PearBlog types and fields on the existing GraphQL schema.
 *   2. Standalone REST endpoint — always registers `GET /pearblog/v1/graphql`
 *      with a minimal built-in query resolver for environments without WPGraphQL.
 *
 * Supported queries (both paths):
 *   - `queue`         – returns the current topic queue (array of strings)
 *   - `stats`         – pipeline stats (articles today, total, success rate)
 *   - `topPosts`      – top N posts by quality score (id, title, score, views)
 *   - `health`        – system health summary (api_ok, circuit_open, queue_size)
 *
 * Authentication:
 *   Same bearer-token mechanism as the REST AutomationController:
 *   Authorization: Bearer <pearblog_api_key>  or  manage_options capability.
 *
 * @package PearBlogEngine\API
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

use PearBlogEngine\Content\QualityScorer;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Analytics\AnalyticsDashboard;

/**
 * Registers and resolves PearBlog GraphQL queries.
 */
class GraphQLController {

	/** REST namespace + route for standalone endpoint. */
	public const REST_NAMESPACE = 'pearblog/v1';
	public const REST_ROUTE     = '/graphql';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );
		add_action( 'graphql_register_types', [ $this, 'register_graphql_types' ] );
	}

	/**
	 * Register the standalone REST-based GraphQL endpoint.
	 */
	public function register_rest_route(): void {
		register_rest_route( self::REST_NAMESPACE, self::REST_ROUTE, [
			'methods'             => 'GET,POST',
			'callback'            => [ $this, 'handle_request' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );
	}

	/**
	 * Register PearBlog types/fields with WPGraphQL (if active).
	 */
	public function register_graphql_types(): void {
		if ( ! function_exists( 'register_graphql_object_type' ) ) {
			return;
		}

		// PearBlogStats type.
		register_graphql_object_type( 'PearBlogStats', [
			'description' => 'PearBlog Engine pipeline statistics',
			'fields'      => [
				'articlesTotal'  => [ 'type' => 'Int', 'description' => 'Total published AI articles' ],
				'articlesToday'  => [ 'type' => 'Int', 'description' => 'Articles published today' ],
				'successRate'    => [ 'type' => 'Float', 'description' => 'Pipeline success rate 0–100' ],
				'queueSize'      => [ 'type' => 'Int', 'description' => 'Topics awaiting generation' ],
				'aiCostCents'    => [ 'type' => 'Int', 'description' => 'Accumulated AI cost in USD cents' ],
			],
		] );

		// PearBlogPost type.
		register_graphql_object_type( 'PearBlogPost', [
			'description' => 'AI-generated post with quality metrics',
			'fields'      => [
				'postId'        => [ 'type' => 'Int',    'description' => 'WordPress post ID' ],
				'title'         => [ 'type' => 'String', 'description' => 'Post title' ],
				'qualityScore'  => [ 'type' => 'Float',  'description' => 'Composite quality score 0–100' ],
				'views30d'      => [ 'type' => 'Int',    'description' => 'GA4 page views last 30 days' ],
				'performScore'  => [ 'type' => 'Float',  'description' => 'Blended performance score' ],
			],
		] );

		// PearBlogHealth type.
		register_graphql_object_type( 'PearBlogHealth', [
			'description' => 'System health summary',
			'fields'      => [
				'apiConfigured'  => [ 'type' => 'Boolean', 'description' => 'OpenAI API key is set' ],
				'circuitOpen'    => [ 'type' => 'Boolean', 'description' => 'AI circuit breaker is open' ],
				'queueSize'      => [ 'type' => 'Int',     'description' => 'Topics in queue' ],
				'lastPipelineRun'=> [ 'type' => 'String',  'description' => 'Timestamp of last pipeline run' ],
			],
		] );

		// Root query fields.
		register_graphql_field( 'RootQuery', 'pearBlogStats', [
			'type'        => 'PearBlogStats',
			'description' => 'PearBlog Engine pipeline statistics',
			'resolve'     => fn() => $this->resolve_stats(),
		] );

		register_graphql_field( 'RootQuery', 'pearBlogTopPosts', [
			'type'        => [ 'list_of' => 'PearBlogPost' ],
			'description' => 'Top AI-generated posts by performance score',
			'args'        => [ 'limit' => [ 'type' => 'Int', 'defaultValue' => 10 ] ],
			'resolve'     => fn( $root, array $args ) => $this->resolve_top_posts( $args['limit'] ?? 10 ),
		] );

		register_graphql_field( 'RootQuery', 'pearBlogHealth', [
			'type'        => 'PearBlogHealth',
			'description' => 'PearBlog Engine system health',
			'resolve'     => fn() => $this->resolve_health(),
		] );

		register_graphql_field( 'RootQuery', 'pearBlogQueue', [
			'type'        => [ 'list_of' => 'String' ],
			'description' => 'Topics waiting in the content queue',
			'resolve'     => fn() => $this->resolve_queue(),
		] );
	}

	// -----------------------------------------------------------------------
	// REST endpoint handler
	// -----------------------------------------------------------------------

	/**
	 * Handle an incoming GraphQL-style REST request.
	 *
	 * Accepts either:
	 *   - `?query=queue` as a GET param
	 *   - JSON body `{"query": "stats"}` as POST
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handle_request( \WP_REST_Request $request ): \WP_REST_Response {
		$query  = (string) ( $request->get_param( 'query' ) ?? '' );
		$args   = (array)  ( $request->get_param( 'args' )  ?? [] );

		if ( '' === $query ) {
			return new \WP_REST_Response( [
				'errors' => [ [ 'message' => 'Missing required parameter: query' ] ],
			], 400 );
		}

		$data = $this->resolve( $query, $args );

		if ( null === $data ) {
			return new \WP_REST_Response( [
				'errors' => [ [ 'message' => "Unknown query: {$query}" ] ],
			], 400 );
		}

		return new \WP_REST_Response( [ 'data' => [ $query => $data ] ], 200 );
	}

	/**
	 * Permission callback: bearer token or manage_options.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function check_permission( \WP_REST_Request $request ): bool {
		if ( function_exists( '\current_user_can' ) && \current_user_can( 'manage_options' ) ) {
			return true;
		}

		$api_key = (string) get_option( 'pearblog_api_key', '' );
		if ( '' === $api_key ) {
			return false;
		}

		$auth  = (string) ( $request->get_header( 'authorization' ) ?? '' );
		$token = '';
		if ( str_starts_with( strtolower( $auth ), 'bearer ' ) ) {
			$token = substr( $auth, 7 );
		}

		return hash_equals( $api_key, $token );
	}

	// -----------------------------------------------------------------------
	// Resolvers
	// -----------------------------------------------------------------------

	/**
	 * Dispatch a query string to the correct resolver.
	 *
	 * @param string $query
	 * @param array  $args
	 * @return mixed|null  Null if unknown query.
	 */
	public function resolve( string $query, array $args = [] ) {
		switch ( $query ) {
			case 'queue':
				return $this->resolve_queue();
			case 'stats':
				return $this->resolve_stats();
			case 'topPosts':
				return $this->resolve_top_posts( (int) ( $args['limit'] ?? 10 ) );
			case 'health':
				return $this->resolve_health();
			default:
				return null;
		}
	}

	/**
	 * Resolve the `queue` query: list of pending topic strings.
	 *
	 * @return string[]
	 */
	public function resolve_queue(): array {
		$queue = new TopicQueue( get_current_blog_id() );
		return $queue->all();
	}

	/**
	 * Resolve the `stats` query.
	 *
	 * @return array{articlesTotal: int, articlesToday: int, successRate: float, queueSize: int, aiCostCents: int}
	 */
	public function resolve_stats(): array {
		$perf_raw   = get_option( 'pearblog_perf_metrics', '{}' );
		$perf       = json_decode( is_string( $perf_raw ) ? $perf_raw : '{}', true );
		$perf       = is_array( $perf ) ? $perf : [];

		$total      = (int)   ( $perf['pipeline_runs'] ?? 0 );
		$ok         = (int)   ( $perf['pipeline_ok']   ?? 0 );
		$rate       = $total > 0 ? round( ( $ok / $total ) * 100, 1 ) : 100.0;

		$queue      = new TopicQueue( get_current_blog_id() );
		$today      = (int) get_option( 'pearblog_posts_today', 0 );

		return [
			'articlesTotal' => $total,
			'articlesToday' => $today,
			'successRate'   => $rate,
			'queueSize'     => count( $queue->all() ),
			'aiCostCents'   => (int) get_option( 'pearblog_ai_cost_cents', 0 ),
		];
	}

	/**
	 * Resolve the `topPosts` query.
	 *
	 * @param int $limit
	 * @return array<int, array{postId: int, title: string, qualityScore: float, views30d: int, performScore: float}>
	 */
	public function resolve_top_posts( int $limit = 10 ): array {
		$dashboard = new AnalyticsDashboard();
		$raw       = $dashboard->get_top_performing_posts( $limit );

		return array_map( fn( $p ) => [
			'postId'       => $p['post_id'],
			'title'        => $p['title'],
			'qualityScore' => $p['quality_score'],
			'views30d'     => $p['views_30d'],
			'performScore' => $p['performance_score'],
		], $raw );
	}

	/**
	 * Resolve the `health` query.
	 *
	 * @return array{apiConfigured: bool, circuitOpen: bool, queueSize: int, lastPipelineRun: string}
	 */
	public function resolve_health(): array {
		$api_key   = (string) get_option( 'pearblog_openai_api_key', '' );
		$circuit   = (array)  get_option( 'pearblog_circuit_breaker', [] );
		$queue     = new TopicQueue( get_current_blog_id() );

		return [
			'apiConfigured'   => '' !== $api_key,
			'circuitOpen'     => (bool) ( $circuit['open'] ?? false ),
			'queueSize'       => count( $queue->all() ),
			'lastPipelineRun' => (string) get_option( 'pearblog_last_pipeline_run', 'never' ),
		];
	}
}
