<?php
/**
 * Specialists REST API Controller
 *
 * Endpoints:
 *   GET  /pearblog/v1/specialists                  — search specialists
 *   GET  /pearblog/v1/specialists/{id}             — get single profile
 *   POST /pearblog/v1/specialists                  — create specialist (admin)
 *   PUT  /pearblog/v1/specialists/{id}             — update specialist (admin)
 *   GET  /pearblog/v1/specialists/{id}/reviews     — get reviews
 *   POST /pearblog/v1/specialists/{id}/reviews     — submit review (public)
 *   POST /pearblog/v1/specialists/{id}/verify      — trigger verification (admin)
 *
 * @package PearBlogEngine\Specialists
 */

declare(strict_types=1);

namespace PearBlogEngine\Specialists;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Specialists REST API Controller.
 */
class SpecialistsController extends WP_REST_Controller {

	protected $namespace = 'pearblog/v1';
	protected $rest_base = 'specialists';

	private SpecialistProfile  $profiles;
	private ReviewSystem       $reviews;
	private BadgeEngine        $badges;
	private VerificationEngine $verification;

	public function __construct(
		?SpecialistProfile  $profiles      = null,
		?ReviewSystem       $reviews       = null,
		?BadgeEngine        $badges        = null,
		?VerificationEngine $verification  = null
	) {
		$this->profiles     = $profiles     ?? new SpecialistProfile();
		$this->reviews      = $reviews      ?? new ReviewSystem();
		$this->badges       = $badges       ?? new BadgeEngine();
		$this->verification = $verification ?? new VerificationEngine();
	}

	/**
	 * Register all routes.
	 */
	public function register_routes(): void {
		// GET /specialists  POST /specialists
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => '__return_true',
				'args'                => $this->search_args(),
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'check_admin_permission' ],
				'args'                => $this->profile_args(),
			],
		] );

		// GET /specialists/{id}  PUT /specialists/{id}
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => '__return_true',
				'args'                => [ 'id' => [ 'required' => true, 'type' => 'integer' ] ],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'check_admin_permission' ],
				'args'                => $this->profile_args( required: false ),
			],
		] );

		// GET /specialists/{id}/reviews  POST /specialists/{id}/reviews
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/reviews', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_reviews' ],
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'submit_review' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'author_name'  => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
					'author_email' => [ 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_email' ],
					'rating'       => [ 'required' => true, 'type' => 'integer', 'minimum' => 1, 'maximum' => 5 ],
					'title'        => [ 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
					'body'         => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ],
				],
			],
		] );

		// GET /specialists/{id}/badges
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/badges', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_badges' ],
			'permission_callback' => '__return_true',
		] );

		// POST /specialists/{id}/verify
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/verify', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'request_verification' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'target_level' => [
					'required' => true,
					'type'     => 'string',
					'enum'     => [ 'bronze', 'silver', 'gold' ],
				],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// Callbacks
	// -----------------------------------------------------------------------

	/** GET /specialists */
	public function get_items( $request ): WP_REST_Response {
		$filters = [
			'category'           => $request->get_param( 'category' ) ?? '',
			'city'               => $request->get_param( 'city' ) ?? '',
			'verification_level' => $request->get_param( 'verification_level' ) ?? '',
			'limit'              => (int) ( $request->get_param( 'limit' ) ?? 20 ),
			'offset'             => (int) ( $request->get_param( 'offset' ) ?? 0 ),
			'order_by'           => $request->get_param( 'order_by' ) ?? 'ranking_score',
		];

		$items = $this->profiles->search( $filters );

		return new WP_REST_Response( [
			'success' => true,
			'count'   => count( $items ),
			'items'   => $items,
		] );
	}

	/** GET /specialists/{id} */
	public function get_item( $request ): WP_REST_Response|WP_Error {
		$id      = (int) $request->get_param( 'id' );
		$profile = $this->profiles->find( $id );

		if ( ! $profile ) {
			return new WP_Error( 'not_found', 'Specialist not found.', [ 'status' => 404 ] );
		}

		$profile['badges']  = $this->badges->get_badges( $id );
		$profile['reviews'] = $this->reviews->for_specialist( $id, 5 );

		return new WP_REST_Response( [ 'success' => true, 'item' => $profile ] );
	}

	/** POST /specialists */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$id = $this->profiles->create( $request->get_params() );

		if ( null === $id ) {
			return new WP_Error( 'create_failed', 'Could not create specialist.', [ 'status' => 500 ] );
		}

		return new WP_REST_Response( [ 'success' => true, 'id' => $id ], 201 );
	}

	/** PUT /specialists/{id} */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );

		if ( ! $this->profiles->find( $id ) ) {
			return new WP_Error( 'not_found', 'Specialist not found.', [ 'status' => 404 ] );
		}

		$ok = $this->profiles->update( $id, $request->get_params() );

		return new WP_REST_Response( [ 'success' => $ok ] );
	}

	/** GET /specialists/{id}/reviews */
	public function get_reviews( $request ): WP_REST_Response {
		$id    = (int) $request->get_param( 'id' );
		$items = $this->reviews->for_specialist( $id );
		return new WP_REST_Response( [ 'success' => true, 'count' => count( $items ), 'items' => $items ] );
	}

	/** POST /specialists/{id}/reviews */
	public function submit_review( $request ): WP_REST_Response|WP_Error {
		$data               = $request->get_params();
		$data['specialist_id'] = (int) $request->get_param( 'id' );

		$review_id = $this->reviews->submit( $data );

		if ( null === $review_id ) {
			return new WP_Error( 'submit_failed', 'Review could not be submitted. Rate-limit or invalid specialist.', [ 'status' => 429 ] );
		}

		return new WP_REST_Response( [ 'success' => true, 'review_id' => $review_id, 'message' => 'Review submitted and awaiting moderation.' ], 201 );
	}

	/** GET /specialists/{id}/badges */
	public function get_badges( $request ): WP_REST_Response {
		$id    = (int) $request->get_param( 'id' );
		$items = $this->badges->get_badges( $id );
		return new WP_REST_Response( [ 'success' => true, 'items' => $items ] );
	}

	/** POST /specialists/{id}/verify */
	public function request_verification( $request ): WP_REST_Response {
		$id    = (int) $request->get_param( 'id' );
		$level = (string) $request->get_param( 'target_level' );
		$ok    = $this->verification->request( $id, $level );
		return new WP_REST_Response( [ 'success' => $ok ] );
	}

	// -----------------------------------------------------------------------
	// Permissions
	// -----------------------------------------------------------------------

	/** @param WP_REST_Request $request */
	public function check_admin_permission( $request ): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// Argument definitions
	// -----------------------------------------------------------------------

	/** @return array<string, array<string, mixed>> */
	private function search_args(): array {
		return [
			'category'           => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'default' => '' ],
			'city'               => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'verification_level' => [ 'type' => 'string', 'enum' => [ '', 'none', 'bronze', 'silver', 'gold' ], 'default' => '' ],
			'order_by'           => [ 'type' => 'string', 'enum' => [ 'ranking_score', 'avg_rating', 'review_count', 'created_at' ], 'default' => 'ranking_score' ],
			'limit'              => [ 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ],
			'offset'             => [ 'type' => 'integer', 'default' => 0, 'minimum' => 0 ],
		];
	}

	/**
	 * @param bool $required  Whether fields are required.
	 * @return array<string, array<string, mixed>>
	 */
	private function profile_args( bool $required = true ): array {
		return [
			'name'     => [ 'required' => $required, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
			'category' => [ 'required' => $required, 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ],
			'city'     => [ 'required' => $required, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
			'phone'    => [ 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
			'email'    => [ 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_email' ],
			'website'  => [ 'required' => false, 'type' => 'string', 'format' => 'uri' ],
			'bio'      => [ 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ],
		];
	}
}
