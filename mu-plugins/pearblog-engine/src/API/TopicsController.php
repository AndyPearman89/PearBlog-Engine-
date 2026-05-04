<?php
/**
 * Topics REST API Controller
 *
 * Provides REST endpoints for managing SEO topics programmatically.
 * Part of the core architecture specification.
 *
 * Endpoints:
 * - GET /pearblog/v1/topics - List all topics
 * - GET /pearblog/v1/topics/{id} - Get single topic
 * - POST /pearblog/v1/topics - Create new topic
 * - PUT /pearblog/v1/topics/{id} - Update topic
 * - DELETE /pearblog/v1/topics/{id} - Delete topic
 *
 * @package PearBlogEngine\API
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

use PearBlogEngine\Content\TopicCPT;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Topics API controller.
 */
class TopicsController extends WP_REST_Controller {

	/**
	 * Namespace for API routes.
	 */
	protected $namespace = 'pearblog/v1';

	/**
	 * Resource name.
	 */
	protected $rest_base = 'topics';

	/**
	 * Register API routes.
	 */
	public function register_routes(): void {
		// GET /topics - List topics
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
			]
		);

		// GET /topics/{id} - Get single topic
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => [
						'id' => [
							'description' => __( 'Topic ID.', 'pearblog-engine' ),
							'type'        => 'integer',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => [
						'id' => [
							'description' => __( 'Topic ID.', 'pearblog-engine' ),
							'type'        => 'integer',
						],
					],
				],
			]
		);
	}

	/**
	 * Check permissions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_permission( $request ) {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Get all topics.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$per_page = $request->get_param( 'per_page' ) ?: 20;
		$page     = $request->get_param( 'page' ) ?: 1;
		$status   = $request->get_param( 'status' );
		$intent   = $request->get_param( 'intent_type' );

		$args = [
			'post_type'      => TopicCPT::POST_TYPE,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
		];

		if ( $status ) {
			$args['meta_query'] = [
				[
					'key'   => '_pb_topic_status',
					'value' => sanitize_text_field( $status ),
				],
			];
		}

		if ( $intent ) {
			$args['tax_query'] = [
				[
					'taxonomy' => TopicCPT::TAXONOMY_INTENT,
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $intent ),
				],
			];
		}

		$query = new \WP_Query( $args );

		$topics = [];
		foreach ( $query->posts as $post ) {
			$topics[] = TopicCPT::get_topic_data( $post->ID );
		}

		return new WP_REST_Response( [
			'topics' => $topics,
			'total'  => $query->found_posts,
			'pages'  => $query->max_num_pages,
		], 200 );
	}

	/**
	 * Get single topic.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$topic = TopicCPT::get_topic_data( $id );

		if ( ! $topic ) {
			return new WP_Error( 'rest_topic_not_found', __( 'Topic not found.', 'pearblog-engine' ), [ 'status' => 404 ] );
		}

		return new WP_REST_Response( $topic, 200 );
	}

	/**
	 * Create new topic.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$params = $request->get_params();

		$result = TopicCPT::create_topic( [
			'title'       => $params['title'] ?? '',
			'keyword'     => $params['keyword'] ?? '',
			'intent_type' => $params['intent_type'] ?? 'info',
			'city'        => $params['city'] ?? '',
			'service'     => $params['service'] ?? '',
			'content'     => $params['content'] ?? '',
		] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$topic = TopicCPT::get_topic_data( $result );

		return new WP_REST_Response( $topic, 201 );
	}

	/**
	 * Update topic.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$id     = (int) $request->get_param( 'id' );
		$params = $request->get_params();

		$post = get_post( $id );

		if ( ! $post || $post->post_type !== TopicCPT::POST_TYPE ) {
			return new WP_Error( 'rest_topic_not_found', __( 'Topic not found.', 'pearblog-engine' ), [ 'status' => 404 ] );
		}

		// Update post.
		if ( isset( $params['title'] ) ) {
			wp_update_post( [
				'ID'         => $id,
				'post_title' => sanitize_text_field( $params['title'] ),
			] );
		}

		if ( isset( $params['content'] ) ) {
			wp_update_post( [
				'ID'           => $id,
				'post_content' => wp_kses_post( $params['content'] ),
			] );
		}

		// Update meta.
		if ( isset( $params['keyword'] ) ) {
			update_post_meta( $id, '_pb_topic_keyword', sanitize_text_field( $params['keyword'] ) );
		}

		if ( isset( $params['city'] ) ) {
			update_post_meta( $id, '_pb_topic_city', sanitize_text_field( $params['city'] ) );
		}

		if ( isset( $params['service'] ) ) {
			update_post_meta( $id, '_pb_topic_service', sanitize_text_field( $params['service'] ) );
		}

		if ( isset( $params['status'] ) ) {
			update_post_meta( $id, '_pb_topic_status', sanitize_text_field( $params['status'] ) );
		}

		// Update taxonomy.
		if ( isset( $params['intent_type'] ) ) {
			wp_set_post_terms( $id, [ $params['intent_type'] ], TopicCPT::TAXONOMY_INTENT );
		}

		$topic = TopicCPT::get_topic_data( $id );

		return new WP_REST_Response( $topic, 200 );
	}

	/**
	 * Delete topic.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$post = get_post( $id );

		if ( ! $post || $post->post_type !== TopicCPT::POST_TYPE ) {
			return new WP_Error( 'rest_topic_not_found', __( 'Topic not found.', 'pearblog-engine' ), [ 'status' => 404 ] );
		}

		$topic = TopicCPT::get_topic_data( $id );

		$result = wp_delete_post( $id, true );

		if ( ! $result ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Cannot delete topic.', 'pearblog-engine' ), [ 'status' => 500 ] );
		}

		return new WP_REST_Response( [
			'deleted' => true,
			'topic'   => $topic,
		], 200 );
	}

	/**
	 * Get collection parameters.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return [
			'page'        => [
				'description'       => __( 'Current page of the collection.', 'pearblog-engine' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			],
			'per_page'    => [
				'description'       => __( 'Maximum number of items to return.', 'pearblog-engine' ),
				'type'              => 'integer',
				'default'           => 20,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			],
			'status'      => [
				'description'       => __( 'Filter by topic status.', 'pearblog-engine' ),
				'type'              => 'string',
				'enum'              => [ 'pending', 'queued', 'generated', 'published' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'intent_type' => [
				'description'       => __( 'Filter by intent type.', 'pearblog-engine' ),
				'type'              => 'string',
				'enum'              => [ 'info', 'commercial', 'local' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}
}
