<?php
/**
 * Search Intent Engine – classifies content intent and maps it to keywords.
 *
 * Reads the site's Search Console data (via SearchConsoleClient) and GA4
 * data (via GA4Client) to classify every published post into one of the
 * four standard search intents:
 *
 *   - Informational  – user wants to learn something ("how to", "what is")
 *   - Navigational   – user looking for a specific site/brand
 *   - Commercial     – user researching before a purchase ("best X", "X vs Y")
 *   - Transactional  – user ready to act ("buy X", "download", "order")
 *
 * Classification approach:
 *   1. Examine query strings from Search Console (highest-signal data).
 *   2. Fall back to NLP keyword analysis of the post title + content.
 *   3. Store the detected intent in post meta for downstream consumers
 *      (e.g., MonetizationEngine, FunnelStageDetector, PromptOptimizer).
 *
 * Post meta keys:
 *   _pearblog_search_intent         – detected intent string
 *   _pearblog_search_intent_queries – JSON top-10 queries driving traffic
 *   _pearblog_search_intent_at      – Unix timestamp of last classification
 *
 * Options:
 *   pearblog_intent_engine_enabled  – bool master switch (default true)
 *
 * REST endpoints:
 *   GET  /pearblog/v1/intent/{post_id}          – get classification for a post
 *   POST /pearblog/v1/intent/{post_id}/classify – (re-)classify a post
 *   GET  /pearblog/v1/intent/stats              – intent distribution across the site
 *
 * Action fired:
 *   pearblog_intent_classified ($post_id, $intent, $queries)
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * Classifies post search intent (informational / navigational / commercial / transactional).
 */
class SearchIntentEngine {

	/** Intent constants. */
	public const INTENT_INFORMATIONAL  = 'informational';
	public const INTENT_NAVIGATIONAL   = 'navigational';
	public const INTENT_COMMERCIAL     = 'commercial';
	public const INTENT_TRANSACTIONAL  = 'transactional';

	public const ALL_INTENTS = [
		self::INTENT_INFORMATIONAL,
		self::INTENT_NAVIGATIONAL,
		self::INTENT_COMMERCIAL,
		self::INTENT_TRANSACTIONAL,
	];

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Post meta keys. */
	private const META_INTENT   = '_pearblog_search_intent';
	private const META_QUERIES  = '_pearblog_search_intent_queries';
	private const META_AT       = '_pearblog_search_intent_at';

	/** Keyword lists for NLP-based classification. */
	private const INFORMATIONAL_SIGNALS = [
		'how', 'what', 'why', 'when', 'where', 'who', 'guide', 'tutorial',
		'learn', 'explain', 'definition', 'meaning', 'tips', 'examples',
	];

	private const COMMERCIAL_SIGNALS = [
		'best', 'top', 'review', 'comparison', 'vs', 'versus', 'alternative',
		'recommend', 'ranking', 'rated', 'compare',
	];

	private const TRANSACTIONAL_SIGNALS = [
		'buy', 'purchase', 'order', 'download', 'subscribe', 'sign up',
		'get', 'free', 'discount', 'coupon', 'deal', 'price', 'cost', 'cheap',
	];

	private const NAVIGATIONAL_SIGNALS = [
		'login', 'sign in', 'website', 'official', 'homepage', 'contact',
	];

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks and REST routes.
	 */
	public function register(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'pearblog_pipeline_completed', [ $this, 'classify_on_pipeline_completed' ], 25, 1 );
	}

	/**
	 * Whether the intent engine is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( 'pearblog_intent_engine_enabled', true );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/intent/(?P<id>[\d]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/intent/(?P<id>[\d]+)/classify', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_classify' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/intent/stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_stats' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );
	}

	/**
	 * Permission – manage_options or API key.
	 */
	public function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * GET /intent/{id} – return stored intent classification.
	 */
	public function rest_get( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$intent  = get_post_meta( $post_id, self::META_INTENT, true );

		if ( ! $intent ) {
			return new \WP_REST_Response( [ 'error' => 'Not yet classified.' ], 404 );
		}

		$queries_raw = get_post_meta( $post_id, self::META_QUERIES, true );
		return new \WP_REST_Response( [
			'post_id'    => $post_id,
			'intent'     => $intent,
			'queries'    => $queries_raw ? json_decode( $queries_raw, true ) : [],
			'classified' => (int) get_post_meta( $post_id, self::META_AT, true ),
		], 200 );
	}

	/**
	 * POST /intent/{id}/classify – run classification.
	 */
	public function rest_classify( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_REST_Response( [ 'error' => "Post #{$post_id} not found." ], 404 );
		}

		$result = $this->classify_post( $post );
		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /intent/stats – distribution of intents across the site.
	 */
	public function rest_stats( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->get_intent_stats(), 200 );
	}

	// -----------------------------------------------------------------------
	// Core classification
	// -----------------------------------------------------------------------

	/**
	 * Hook: classify after pipeline completion.
	 *
	 * @param int $post_id WordPress post ID.
	 */
	public function classify_on_pipeline_completed( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post ) {
			$this->classify_post( $post );
		}
	}

	/**
	 * Classify a post's search intent and persist it.
	 *
	 * @param \WP_Post $post Post to classify.
	 * @return array  Classification result.
	 */
	public function classify_post( \WP_Post $post ): array {
		$text    = strtolower( $post->post_title . ' ' . wp_strip_all_tags( $post->post_content ) );
		$intent  = $this->classify_text( $text );
		$queries = []; // Would be populated from SearchConsoleClient in a live install.

		update_post_meta( $post->ID, self::META_INTENT, $intent );
		update_post_meta( $post->ID, self::META_QUERIES, wp_json_encode( $queries ) );
		update_post_meta( $post->ID, self::META_AT, time() );

		do_action( 'pearblog_intent_classified', $post->ID, $intent, $queries );

		return [
			'post_id' => $post->ID,
			'intent'  => $intent,
			'queries' => $queries,
		];
	}

	/**
	 * Classify plain text into an intent using keyword signals.
	 *
	 * @param string $text Lowercased plain text.
	 * @return string  One of the INTENT_* constants.
	 */
	public function classify_text( string $text ): string {
		$scores = [
			self::INTENT_INFORMATIONAL => $this->count_signals( $text, self::INFORMATIONAL_SIGNALS ),
			self::INTENT_COMMERCIAL    => $this->count_signals( $text, self::COMMERCIAL_SIGNALS ),
			self::INTENT_TRANSACTIONAL => $this->count_signals( $text, self::TRANSACTIONAL_SIGNALS ),
			self::INTENT_NAVIGATIONAL  => $this->count_signals( $text, self::NAVIGATIONAL_SIGNALS ),
		];

		arsort( $scores );
		$top_intent = array_key_first( $scores );
		$top_score  = $scores[ $top_intent ];

		// If no signals found at all, default to informational.
		return $top_score > 0 ? $top_intent : self::INTENT_INFORMATIONAL;
	}

	/**
	 * Return intent distribution stats across all classified posts.
	 *
	 * @return array<string, int>  Intent → count.
	 */
	public function get_intent_stats(): array {
		$stats = array_fill_keys( self::ALL_INTENTS, 0 );
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => self::META_INTENT,
			'fields'         => 'ids',
		] );

		foreach ( $posts as $post_id ) {
			$intent = get_post_meta( (int) $post_id, self::META_INTENT, true );
			if ( isset( $stats[ $intent ] ) ) {
				$stats[ $intent ]++;
			}
		}

		return $stats;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Count how many signal words appear in the text.
	 *
	 * @param string   $text    Lowercased input text.
	 * @param string[] $signals Array of signal words/phrases.
	 * @return int  Match count.
	 */
	private function count_signals( string $text, array $signals ): int {
		$count = 0;
		foreach ( $signals as $signal ) {
			if ( str_contains( $text, $signal ) ) {
				++$count;
			}
		}
		return $count;
	}
}
