<?php
/**
 * Poradnik REST API
 *
 * REST API endpoints for Poradnik Engine V2.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class PoradnikAPI
 *
 * REST API controller for Poradnik operations.
 */
class PoradnikAPI extends WP_REST_Controller {
	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pearblog/v1';

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// POST /api/content/generate
		register_rest_route(
			$this->namespace,
			'/content/generate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_content' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'topic'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'category' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'city'     => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'intent'   => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => 'cost',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// POST /api/content/optimize
		register_rest_route(
			$this->namespace,
			'/content/optimize/(?P<article_id>\d+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'optimize_content' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'article_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
					'force'      => array(
						'required' => false,
						'type'     => 'boolean',
						'default'  => false,
					),
				),
			)
		);

		// GET /api/content/score
		register_rest_route(
			$this->namespace,
			'/content/score/(?P<article_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_score' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'article_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);

		// POST /api/content/publish
		register_rest_route(
			$this->namespace,
			'/content/publish/(?P<article_id>\d+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'publish_content' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'article_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);

		// POST /api/event
		register_rest_route(
			$this->namespace,
			'/event',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'track_event' ),
				'permission_callback' => '__return_true', // Public endpoint
				'args'                => array(
					'event_type' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'article_id' => array(
						'required' => false,
						'type'     => 'integer',
					),
					'post_id'    => array(
						'required' => false,
						'type'     => 'integer',
					),
				),
			)
		);

		// GET /api/articles/top
		register_rest_route(
			$this->namespace,
			'/articles/top',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_top_articles' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'category' => array(
						'required' => false,
						'type'     => 'string',
						'default'  => 'SCALE',
					),
					'limit'    => array(
						'required' => false,
						'type'     => 'integer',
						'default'  => 10,
					),
				),
			)
		);
	}

	/**
	 * Generate content endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function generate_content( WP_REST_Request $request ) {
		global $wpdb;

		$topic    = $request->get_param( 'topic' );
		$category = $request->get_param( 'category' );
		$city     = $request->get_param( 'city' );
		$intent   = $request->get_param( 'intent' );

		// Create article record
		$table_name = $wpdb->prefix . 'pearblog_articles';
		$result     = $wpdb->insert(
			$table_name,
			array(
				'topic'    => $topic,
				'city'     => $city,
				'service'  => $topic,
				'slug'     => sanitize_title( $topic . '-' . $city ),
				'status'   => 'draft',
				'variant'  => 'original',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $result ) {
			return new WP_Error( 'generation_failed', 'Failed to create article record', array( 'status' => 500 ) );
		}

		$article_id = $wpdb->insert_id;

		// Queue for generation
		$worker_manager = new WorkerManager();
		$worker_manager->dispatch( 'generate', array( 'article_id' => $article_id ) );

		return new WP_REST_Response(
			array(
				'success'    => true,
				'article_id' => $article_id,
				'status'     => 'queued',
				'message'    => 'Article queued for generation',
			),
			201
		);
	}

	/**
	 * Optimize content endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function optimize_content( WP_REST_Request $request ) {
		$article_id = (int) $request->get_param( 'article_id' );
		$force      = $request->get_param( 'force' );

		$ai_optimizer = new AIOptimizer();

		// Analyze article
		$optimizations = $ai_optimizer->analyze( $article_id );

		if ( empty( $optimizations ) ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'No optimizations needed',
				),
				200
			);
		}

		$applied = array();

		// Apply optimizations
		foreach ( $optimizations as $opt ) {
			if ( $force || $opt['priority'] === 'critical' ) {
				$result    = $ai_optimizer->optimize( $article_id, $opt['action'] );
				$applied[] = array(
					'action' => $opt['action'],
					'status' => is_wp_error( $result ) ? 'failed' : 'success',
				);
			}
		}

		return new WP_REST_Response(
			array(
				'success'           => true,
				'article_id'        => $article_id,
				'optimizations'     => $optimizations,
				'applied'           => $applied,
			),
			200
		);
	}

	/**
	 * Get score endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function get_score( WP_REST_Request $request ) {
		$article_id = (int) $request->get_param( 'article_id' );

		$scoring_engine = new ScoringEngine();
		$score_data     = $scoring_engine->calculate_score( $article_id );

		return new WP_REST_Response(
			array(
				'success'    => true,
				'article_id' => $article_id,
				'score'      => $score_data['total_score'],
				'category'   => $score_data['category'],
				'breakdown'  => array(
					'seo'        => $score_data['seo_score'],
					'engagement' => $score_data['engagement_score'],
					'ctr'        => $score_data['ctr_score'],
					'revenue'    => $score_data['revenue_score'],
				),
				'stats'      => $score_data['stats'],
			),
			200
		);
	}

	/**
	 * Publish content endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function publish_content( WP_REST_Request $request ) {
		global $wpdb;

		$article_id = (int) $request->get_param( 'article_id' );
		$table_name = $wpdb->prefix . 'pearblog_articles';

		$article = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $article_id ),
			ARRAY_A
		);

		if ( ! $article ) {
			return new WP_Error( 'article_not_found', 'Article not found', array( 'status' => 404 ) );
		}

		// Update post status
		wp_update_post(
			array(
				'ID'          => $article['post_id'],
				'post_status' => 'publish',
			)
		);

		// Update article status
		$wpdb->update(
			$table_name,
			array( 'status' => 'published' ),
			array( 'id' => $article_id ),
			array( '%s' ),
			array( '%d' )
		);

		return new WP_REST_Response(
			array(
				'success'    => true,
				'article_id' => $article_id,
				'post_id'    => $article['post_id'],
				'status'     => 'published',
			),
			200
		);
	}

	/**
	 * Track event endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function track_event( WP_REST_Request $request ) {
		$event_type = $request->get_param( 'event_type' );
		$article_id = $request->get_param( 'article_id' );
		$post_id    = $request->get_param( 'post_id' );

		$event_tracker = new EventTracker();
		$session_id    = EventTracker::get_session_id();

		$event_id = null;

		switch ( $event_type ) {
			case 'view':
				$event_id = $event_tracker->track_view( $post_id, $article_id, $session_id );
				break;

			case 'cta_click':
				$cta_text   = $request->get_param( 'cta_text' );
				$target_url = $request->get_param( 'target_url' );
				$event_id   = $event_tracker->track_cta_click( $post_id, $article_id, $session_id, $cta_text, $target_url );
				break;

			case 'scroll':
				$depth        = $request->get_param( 'depth' );
				$time_seconds = $request->get_param( 'time_seconds' );
				$event_id     = $event_tracker->track_scroll( $post_id, $article_id, $session_id, $depth, $time_seconds );
				break;

			case 'lead':
				$user_id    = $request->get_param( 'user_id' ) ?? 0;
				$lead_data  = $request->get_param( 'lead_data' ) ?? array();
				$event_id   = $event_tracker->track_lead( $post_id, $article_id, $session_id, $user_id, $lead_data );
				break;

			case 'revenue':
				$amount   = $request->get_param( 'amount' );
				$currency = $request->get_param( 'currency' ) ?? 'PLN';
				$event_id = $event_tracker->track_revenue( $post_id, $article_id, $session_id, $amount, $currency );
				break;
		}

		if ( ! $event_id ) {
			return new WP_Error( 'tracking_failed', 'Failed to track event', array( 'status' => 500 ) );
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'event_id' => $event_id,
			),
			201
		);
	}

	/**
	 * Get top articles endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_top_articles( WP_REST_Request $request ) {
		$category = $request->get_param( 'category' );
		$limit    = $request->get_param( 'limit' );

		$scoring_engine = new ScoringEngine();
		$articles       = $scoring_engine->get_articles_by_category( $category, $limit );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'category' => $category,
				'count'    => count( $articles ),
				'articles' => $articles,
			),
			200
		);
	}

	/**
	 * Check permission.
	 *
	 * @return bool True if user has permission.
	 */
	public function check_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}
