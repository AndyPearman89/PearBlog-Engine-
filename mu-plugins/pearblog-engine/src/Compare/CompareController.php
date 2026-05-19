<?php
/**
 * Compare Engine — REST API controller.
 *
 * Endpoints:
 *   GET  /pearblog/v1/compare                        – list comparisons
 *   GET  /pearblog/v1/compare/{slug}                 – single comparison with items & AI verdict
 *   GET  /pearblog/v1/compare/{slug}/pros-cons        – pros / cons grouped by item
 *   POST /pearblog/v1/compare           (manage_options) – upsert comparison
 *
 * @package PearBlogEngine\Compare
 */

declare( strict_types=1 );

namespace PearBlogEngine\Compare;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class CompareController {

	private const NAMESPACE = 'pearblog/v1';
	private const BASE      = 'compare';

	private CompareEngine $engine;

	public function __construct( ?CompareEngine $engine = null ) {
		$this->engine = $engine ?? new CompareEngine();
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'list_comparisons' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'category' => [ 'type' => 'string', 'default' => '' ],
						'per_page' => [ 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ],
						'offset'   => [ 'type' => 'integer', 'default' => 0,  'minimum' => 0 ],
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'upsert_comparison' ],
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/(?P<slug>[a-z0-9\-]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_comparison' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'slug' => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_title' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/(?P<slug>[a-z0-9\-]+)/pros-cons',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_pros_cons' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'slug' => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_title' ],
				],
			]
		);
	}

	public function list_comparisons( WP_REST_Request $request ): WP_REST_Response {
		$items = $this->engine->list_comparisons(
			(string) $request->get_param( 'category' ),
			(int)    $request->get_param( 'per_page' ),
			(int)    $request->get_param( 'offset' )
		);

		return new WP_REST_Response( [ 'comparisons' => $items, 'count' => count( $items ) ] );
	}

	public function get_comparison( WP_REST_Request $request ): WP_REST_Response {
		$slug       = (string) $request->get_param( 'slug' );
		$comparison = $this->engine->get_by_slug( $slug );

		if ( ! $comparison ) {
			return new WP_REST_Response( [ 'error' => 'Not found' ], 404 );
		}

		return new WP_REST_Response( $comparison );
	}

	public function get_pros_cons( WP_REST_Request $request ): WP_REST_Response {
		$slug       = (string) $request->get_param( 'slug' );
		$comparison = $this->engine->get_by_slug( $slug );

		if ( ! $comparison ) {
			return new WP_REST_Response( [ 'error' => 'Not found' ], 404 );
		}

		$pros_cons = $this->engine->get_pros_cons( (int) $comparison['id'] );

		return new WP_REST_Response( $pros_cons );
	}

	public function upsert_comparison( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();

		if ( empty( $body['slug'] ) || empty( $body['title'] ) ) {
			return new WP_REST_Response( [ 'error' => 'slug and title are required' ], 400 );
		}

		$id = $this->engine->upsert( $body );

		return new WP_REST_Response( [ 'id' => $id ], 201 );
	}
}
