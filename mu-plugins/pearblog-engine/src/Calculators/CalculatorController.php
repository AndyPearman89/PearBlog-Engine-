<?php
/**
 * Calculator Engine — REST API controller.
 *
 * Endpoints:
 *   GET  /pearblog/v1/calculators                         – list calculators
 *   GET  /pearblog/v1/calculators/{slug}                  – single calculator definition
 *   POST /pearblog/v1/calculators/{slug}/calculate         – run a calculation (public)
 *   POST /pearblog/v1/calculators          (manage_options) – upsert calculator
 *
 * @package PearBlogEngine\Calculators
 */

declare( strict_types=1 );

namespace PearBlogEngine\Calculators;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class CalculatorController {

	private const NAMESPACE = 'pearblog/v1';
	private const BASE      = 'calculators';

	private CalculatorEngine $engine;

	public function __construct( ?CalculatorEngine $engine = null ) {
		$this->engine = $engine ?? new CalculatorEngine();
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'list_calculators' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'category' => [ 'type' => 'string', 'default' => '' ],
						'per_page' => [ 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ],
						'offset'   => [ 'type' => 'integer', 'default' => 0,  'minimum' => 0 ],
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'upsert_calculator' ],
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/(?P<slug>[a-z0-9\-]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_calculator' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'slug' => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_title' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/(?P<slug>[a-z0-9\-]+)/calculate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'run_calculation' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'slug' => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_title' ],
				],
			]
		);
	}

	public function list_calculators( WP_REST_Request $request ): WP_REST_Response {
		$items = $this->engine->list_calculators(
			(string) $request->get_param( 'category' ),
			(int)    $request->get_param( 'per_page' ),
			(int)    $request->get_param( 'offset' )
		);

		return new WP_REST_Response( [ 'calculators' => $items, 'count' => count( $items ) ] );
	}

	public function get_calculator( WP_REST_Request $request ): WP_REST_Response {
		$calc = $this->engine->get_by_slug( (string) $request->get_param( 'slug' ) );

		if ( ! $calc ) {
			return new WP_REST_Response( [ 'error' => 'Not found' ], 404 );
		}

		return new WP_REST_Response( $calc );
	}

	public function run_calculation( WP_REST_Request $request ): WP_REST_Response {
		$slug = (string) $request->get_param( 'slug' );
		$calc = $this->engine->get_by_slug( $slug );

		if ( ! $calc ) {
			return new WP_REST_Response( [ 'error' => 'Calculator not found' ], 404 );
		}

		$inputs = $request->get_json_params()['inputs'] ?? [];

		if ( ! is_array( $inputs ) ) {
			return new WP_REST_Response( [ 'error' => 'inputs must be an object' ], 400 );
		}

		$result = $this->engine->calculate( $calc, $inputs );

		return new WP_REST_Response( $result->to_array() );
	}

	public function upsert_calculator( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();

		if ( empty( $body['slug'] ) || empty( $body['title'] ) ) {
			return new WP_REST_Response( [ 'error' => 'slug and title are required' ], 400 );
		}

		$id = $this->engine->upsert( $body );

		return new WP_REST_Response( [ 'id' => $id ], 201 );
	}
}
