<?php
/**
 * Rankings REST API Controller
 *
 * Endpoints:
 *   GET  /pearblog/v1/rankings                  — paginated list of ranking CPT posts
 *   GET  /pearblog/v1/rankings/{category}       — ranked list for a category
 *   GET  /pearblog/v1/rankings/{category}/{city} — ranked list for category + city
 *   POST /pearblog/v1/rankings/recalculate      — trigger score recalculation (auth required)
 *
 * @package PearBlogEngine\Rankings
 */

declare(strict_types=1);

namespace PearBlogEngine\Rankings;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Rankings REST API Controller.
 *
 * Uses pearblog/v1 namespace, consistent with existing controllers.
 */
class RankingsController extends WP_REST_Controller {

	protected $namespace = 'pearblog/v1';
	protected $rest_base = 'rankings';

	private RankingService $service;

	public function __construct( ?RankingService $service = null ) {
		$this->service = $service ?? new RankingService();
	}

	/**
	 * Register all routes.
	 */
	public function register_routes(): void {
		// GET /pearblog/v1/rankings
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_items' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'category' => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
					'default'           => '',
				],
				'city' => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
					'default'           => '',
				],
				'limit' => [
					'type'    => 'integer',
					'default' => 10,
					'minimum' => 1,
					'maximum' => 50,
				],
			],
		] );

		// GET /pearblog/v1/rankings/{category}
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<category>[a-z0-9_-]+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_by_category' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'category' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'limit' => [
					'type'    => 'integer',
					'default' => 10,
					'minimum' => 1,
					'maximum' => 50,
				],
			],
		] );

		// GET /pearblog/v1/rankings/{category}/{city}
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<category>[a-z0-9_-]+)/(?P<city>[a-z0-9_-]+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_by_category_city' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'category' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'city' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'limit' => [
					'type'    => 'integer',
					'default' => 10,
					'minimum' => 1,
					'maximum' => 50,
				],
			],
		] );

		// POST /pearblog/v1/rankings/recalculate
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/recalculate', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'recalculate' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'specialist_id' => [
					'required' => true,
					'type'     => 'integer',
					'minimum'  => 1,
				],
				'category' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'city' => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
					'default'           => '',
				],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// Callbacks
	// -----------------------------------------------------------------------

	/**
	 * GET /pearblog/v1/rankings — list with optional filter params.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$category = (string) $request->get_param( 'category' );
		$city     = (string) $request->get_param( 'city' );
		$limit    = (int) $request->get_param( 'limit' );

		$items = $this->service->get_ranked_list( $category, $city, $limit );

		return new WP_REST_Response( [
			'success'  => true,
			'category' => $category,
			'city'     => $city,
			'count'    => count( $items ),
			'items'    => $items,
		] );
	}

	/**
	 * GET /pearblog/v1/rankings/{category}
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_by_category( $request ): WP_REST_Response {
		$category = (string) $request->get_param( 'category' );
		$limit    = (int) $request->get_param( 'limit' );
		$items    = $this->service->get_ranked_list( $category, '', $limit );

		return new WP_REST_Response( [
			'success'  => true,
			'category' => $category,
			'city'     => '',
			'count'    => count( $items ),
			'items'    => $items,
		] );
	}

	/**
	 * GET /pearblog/v1/rankings/{category}/{city}
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_by_category_city( $request ): WP_REST_Response {
		$category = (string) $request->get_param( 'category' );
		$city     = (string) $request->get_param( 'city' );
		$limit    = (int) $request->get_param( 'limit' );
		$items    = $this->service->get_ranked_list( $category, $city, $limit );

		return new WP_REST_Response( [
			'success'  => true,
			'category' => $category,
			'city'     => $city,
			'count'    => count( $items ),
			'items'    => $items,
		] );
	}

	/**
	 * POST /pearblog/v1/rankings/recalculate
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function recalculate( $request ): WP_REST_Response|WP_Error {
		$specialist_id = (int) $request->get_param( 'specialist_id' );
		$category      = (string) $request->get_param( 'category' );
		$city          = (string) $request->get_param( 'city' );

		if ( ! get_post( $specialist_id ) ) {
			return new WP_Error( 'not_found', 'Specialist not found.', [ 'status' => 404 ] );
		}

		$score = $this->service->recalculate_score( $specialist_id, $category, $city );

		return new WP_REST_Response( [
			'success'       => true,
			'specialist_id' => $specialist_id,
			'score'         => $score->to_array(),
		] );
	}

	// -----------------------------------------------------------------------
	// Permissions
	// -----------------------------------------------------------------------

	/**
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function check_admin_permission( $request ): bool {
		return current_user_can( 'manage_options' );
	}
}
