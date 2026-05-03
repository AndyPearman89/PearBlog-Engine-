<?php
/**
 * Decision Platform API Controller
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * REST API Controller for Decision Platform
 */
class DecisionPlatformAPI {

	/** @var DecisionAssistant */
	private DecisionAssistant $assistant;

	/** @var LeadGenerator */
	private LeadGenerator $lead_generator;

	/** @var Calculator|null */
	private ?Calculator $calculator = null;

	public function __construct() {
		$this->assistant = new DecisionAssistant();
		$this->lead_generator = new LeadGenerator();
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes(): void {
		// Decision Assistant
		register_rest_route( 'pearblog/v1', '/decision/recommend', [
			'methods' => 'POST',
			'callback' => [ $this, 'get_recommendation' ],
			'permission_callback' => '__return_true',
			'args' => [
				'need' => [
					'required' => true,
					'type' => 'string',
				],
				'budget' => [
					'type' => 'number',
					'default' => null,
				],
				'location' => [
					'type' => 'string',
					'default' => null,
				],
			],
		] );

		// Lead Generation
		register_rest_route( 'pearblog/v1', '/lead/submit', [
			'methods' => 'POST',
			'callback' => [ $this, 'submit_lead' ],
			'permission_callback' => '__return_true',
			'args' => [
				'name' => [
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'email' => [
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_email',
				],
				'phone' => [
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'city' => [
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'message' => [
					'type' => 'string',
					'default' => '',
					'sanitize_callback' => 'sanitize_textarea_field',
				],
				'category' => [
					'type' => 'string',
					'default' => '',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		// Calculator
		register_rest_route( 'pearblog/v1', '/calculator/(?P<id>\d+)/calculate', [
			'methods' => 'POST',
			'callback' => [ $this, 'calculate' ],
			'permission_callback' => '__return_true',
			'args' => [
				'id' => [
					'required' => true,
					'type' => 'integer',
				],
				'values' => [
					'required' => true,
					'type' => 'object',
				],
			],
		] );

		// Comparison
		register_rest_route( 'pearblog/v1', '/comparison/(?P<slug>[a-zA-Z0-9-]+)', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_comparison' ],
			'permission_callback' => '__return_true',
		] );

		// Ranking
		register_rest_route( 'pearblog/v1', '/ranking/(?P<slug>[a-zA-Z0-9-]+)', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_ranking' ],
			'permission_callback' => '__return_true',
		] );

		// Experts search
		register_rest_route( 'pearblog/v1', '/experts/search', [
			'methods' => 'GET',
			'callback' => [ $this, 'search_experts' ],
			'permission_callback' => '__return_true',
			'args' => [
				'category' => [
					'type' => 'string',
					'default' => '',
				],
				'city' => [
					'type' => 'string',
					'default' => '',
				],
				'verified_only' => [
					'type' => 'boolean',
					'default' => false,
				],
			],
		] );

		// Offers search
		register_rest_route( 'pearblog/v1', '/offers/search', [
			'methods' => 'GET',
			'callback' => [ $this, 'search_offers' ],
			'permission_callback' => '__return_true',
			'args' => [
				'category' => [
					'type' => 'string',
					'default' => '',
				],
				'city' => [
					'type' => 'string',
					'default' => '',
				],
			],
		] );
	}

	/**
	 * Get recommendation endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_recommendation( \WP_REST_Request $request ): \WP_REST_Response {
		$input = [
			'need' => $request->get_param( 'need' ),
			'budget' => $request->get_param( 'budget' ),
			'location' => $request->get_param( 'location' ),
		];

		try {
			$recommendation = $this->assistant->get_recommendation( $input );

			return new \WP_REST_Response( [
				'success' => true,
				'data' => $recommendation,
			], 200 );
		} catch ( \Exception $e ) {
			return new \WP_REST_Response( [
				'success' => false,
				'error' => $e->getMessage(),
			], 500 );
		}
	}

	/**
	 * Submit lead endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function submit_lead( \WP_REST_Request $request ): \WP_REST_Response {
		$data = [
			'name' => $request->get_param( 'name' ),
			'email' => $request->get_param( 'email' ),
			'phone' => $request->get_param( 'phone' ),
			'city' => $request->get_param( 'city' ),
			'message' => $request->get_param( 'message' ),
			'category' => $request->get_param( 'category' ),
			'source_url' => $request->get_header( 'referer' ) ?? '',
		];

		try {
			$lead_id = $this->lead_generator->submit_lead( $data );

			// Match experts
			$experts = $this->lead_generator->match_experts( $lead_id );

			return new \WP_REST_Response( [
				'success' => true,
				'lead_id' => $lead_id,
				'matched_experts' => count( $experts ),
				'message' => 'Dziękujemy! Skontaktujemy się wkrótce.',
			], 201 );
		} catch ( \Exception $e ) {
			return new \WP_REST_Response( [
				'success' => false,
				'error' => $e->getMessage(),
			], 400 );
		}
	}

	/**
	 * Calculate endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function calculate( \WP_REST_Request $request ): \WP_REST_Response {
		$calculator_id = $request->get_param( 'id' );
		$values = $request->get_param( 'values' );

		$post = get_post( $calculator_id );
		if ( ! $post || 'pearblog_calculator' !== $post->post_type ) {
			return new \WP_REST_Response( [
				'success' => false,
				'error' => 'Calculator not found',
			], 404 );
		}

		$calculator = Calculator::from_post( $post );
		$result = $calculator->calculate( $values );

		if ( null === $result ) {
			return new \WP_REST_Response( [
				'success' => false,
				'error' => 'Calculation error',
			], 400 );
		}

		return new \WP_REST_Response( [
			'success' => true,
			'result' => $result,
			'formatted' => $calculator->format_result( $result ),
		], 200 );
	}

	/**
	 * Get comparison endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_comparison( \WP_REST_Request $request ): \WP_REST_Response {
		$engine = new ComparisonEngine();
		$comparison = $engine->find_by_slug( $request->get_param( 'slug' ) );

		if ( ! $comparison ) {
			return new \WP_REST_Response( [
				'success' => false,
				'error' => 'Comparison not found',
			], 404 );
		}

		return new \WP_REST_Response( [
			'success' => true,
			'data' => [
				'id' => $comparison->id,
				'title' => $comparison->title,
				'items' => $comparison->items,
				'criteria' => $comparison->criteria,
				'winner' => $comparison->winner,
			],
		], 200 );
	}

	/**
	 * Get ranking endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_ranking( \WP_REST_Request $request ): \WP_REST_Response {
		$engine = new RankingEngine();
		$ranking = $engine->find_by_slug( $request->get_param( 'slug' ) );

		if ( ! $ranking ) {
			return new \WP_REST_Response( [
				'success' => false,
				'error' => 'Ranking not found',
			], 404 );
		}

		return new \WP_REST_Response( [
			'success' => true,
			'data' => [
				'id' => $ranking->id,
				'title' => $ranking->title,
				'category' => $ranking->category,
				'location' => $ranking->location,
				'items' => $ranking->items,
			],
		], 200 );
	}

	/**
	 * Search experts endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function search_experts( \WP_REST_Request $request ): \WP_REST_Response {
		$args = [
			'post_type' => 'pearblog_expert',
			'post_status' => 'publish',
			'posts_per_page' => 20,
		];

		$meta_query = [ 'relation' => 'AND' ];

		if ( $request->get_param( 'category' ) ) {
			$meta_query[] = [
				'key' => 'pearblog_expert_category',
				'value' => $request->get_param( 'category' ),
				'compare' => '=',
			];
		}

		if ( $request->get_param( 'city' ) ) {
			$meta_query[] = [
				'key' => 'pearblog_expert_location',
				'value' => $request->get_param( 'city' ),
				'compare' => 'LIKE',
			];
		}

		if ( $request->get_param( 'verified_only' ) ) {
			$meta_query[] = [
				'key' => 'pearblog_expert_verified',
				'value' => '1',
				'compare' => '=',
			];
		}

		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query;
		}

		$posts = get_posts( $args );
		$experts = array_map( function( $post ) {
			$expert = Expert::from_post( $post );
			return [
				'id' => $expert->id,
				'name' => $expert->name,
				'category' => $expert->category,
				'location' => $expert->location,
				'rating' => $expert->rating,
				'review_count' => $expert->review_count,
				'verified' => $expert->verified,
				'premium' => $expert->premium,
			];
		}, $posts );

		return new \WP_REST_Response( [
			'success' => true,
			'data' => $experts,
			'count' => count( $experts ),
		], 200 );
	}

	/**
	 * Search offers endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function search_offers( \WP_REST_Request $request ): \WP_REST_Response {
		$args = [
			'post_type' => 'pearblog_offer',
			'post_status' => 'publish',
			'posts_per_page' => 20,
		];

		$meta_query = [ 'relation' => 'AND' ];

		if ( $request->get_param( 'category' ) ) {
			$meta_query[] = [
				'key' => 'pearblog_offer_category',
				'value' => $request->get_param( 'category' ),
				'compare' => '=',
			];
		}

		if ( $request->get_param( 'city' ) ) {
			$meta_query[] = [
				'key' => 'pearblog_offer_location',
				'value' => $request->get_param( 'city' ),
				'compare' => 'LIKE',
			];
		}

		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query;
		}

		$posts = get_posts( $args );
		$offers = array_map( function( $post ) {
			$offer = Offer::from_post( $post );
			return [
				'id' => $offer->id,
				'title' => $offer->title,
				'location' => $offer->location,
				'category' => $offer->category,
				'price_range' => $offer->price_range,
				'featured' => $offer->featured,
			];
		}, $posts );

		return new \WP_REST_Response( [
			'success' => true,
			'data' => $offers,
			'count' => count( $offers ),
		], 200 );
	}
}
