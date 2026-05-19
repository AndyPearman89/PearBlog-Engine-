<?php
/**
 * Local Hub REST API Controller
 *
 * Endpoints:
 *   GET  /pearblog/v1/local/hubs                      — list all vertical hubs
 *   GET  /pearblog/v1/local/hubs/{vertical}           — hub config + city pages
 *   POST /pearblog/v1/local/hubs/{vertical}/generate  — generate pages for cities (admin)
 *
 * @package PearBlogEngine\Local
 */

declare(strict_types=1);

namespace PearBlogEngine\Local;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * LocalHubController.
 */
class LocalHubController extends WP_REST_Controller {

	protected $namespace = 'pearblog/v1';
	protected $rest_base = 'local';

	private LocalHubManager $manager;

	public function __construct( ?LocalHubManager $manager = null ) {
		$this->manager = $manager ?? new LocalHubManager();
	}

	public function register_routes(): void {
		// GET /local/hubs
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/hubs', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'list_hubs' ],
			'permission_callback' => '__return_true',
		] );

		// GET /local/hubs/{vertical}
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/hubs/(?P<vertical>[a-z0-9_-]+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_hub' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'vertical' => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ],
			],
		] );

		// POST /local/hubs/{vertical}/generate
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/hubs/(?P<vertical>[a-z0-9_-]+)/generate', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'generate_pages' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'vertical' => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ],
				'cities'   => [ 'required' => true, 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// Callbacks
	// -----------------------------------------------------------------------

	/** GET /local/hubs */
	public function list_hubs( $request ): WP_REST_Response {
		$hubs = $this->manager->all_hubs();
		return new WP_REST_Response( [
			'success' => true,
			'count'   => count( $hubs ),
			'hubs'    => array_values( $hubs ),
		] );
	}

	/** GET /local/hubs/{vertical} */
	public function get_hub( $request ): WP_REST_Response|WP_Error {
		$vertical = (string) $request->get_param( 'vertical' );
		$hub      = $this->manager->get_hub( $vertical );

		if ( ! $hub ) {
			return new WP_Error( 'hub_not_found', 'Hub not found.', [ 'status' => 404 ] );
		}

		// Include generated pages count.
		$pages = get_posts( [
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [ [ 'key' => 'pearblog_hub_vertical', 'value' => $vertical ] ],
			'fields'         => 'ids',
		] );

		$hub['generated_pages'] = count( $pages );

		return new WP_REST_Response( [ 'success' => true, 'hub' => $hub ] );
	}

	/** POST /local/hubs/{vertical}/generate */
	public function generate_pages( $request ): WP_REST_Response|WP_Error {
		$vertical = (string) $request->get_param( 'vertical' );
		$cities   = (array) $request->get_param( 'cities' );
		$cities   = array_map( 'sanitize_text_field', $cities );

		if ( ! $this->manager->get_hub( $vertical ) ) {
			return new WP_Error( 'hub_not_found', 'Hub not found.', [ 'status' => 404 ] );
		}

		$results = $this->manager->generate_hub_pages( $vertical, $cities );

		return new WP_REST_Response( [
			'success'   => true,
			'generated' => count( $results ),
			'results'   => $results,
		], 201 );
	}

	// -----------------------------------------------------------------------
	// Permissions
	// -----------------------------------------------------------------------

	/** @param WP_REST_Request $request */
	public function check_admin_permission( $request ): bool {
		return current_user_can( 'manage_options' );
	}
}
