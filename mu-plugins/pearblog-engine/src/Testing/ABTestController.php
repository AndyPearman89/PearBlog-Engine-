<?php
/**
 * A/B Test REST Controller — exposes ABTestEngine via /pearblog/v1/abtests.
 *
 * Endpoints:
 *   GET    /abtests                – list all tests
 *   POST   /abtests                – create a test
 *   GET    /abtests/{id}           – get a specific test
 *   DELETE /abtests/{id}           – delete a test
 *   POST   /abtests/{id}/promote   – force-promote the winner of a test
 *   GET    /abtests/{id}/results   – get statistical results summary
 *   POST   /abtests/promote-all    – promote all mature tests (cron action)
 *
 * Authentication: manage_options capability or Bearer API key.
 *
 * @package PearBlogEngine\Testing
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

/**
 * Registers REST routes for the A/B Testing Framework.
 */
class ABTestController {

	public const REST_NAMESPACE = 'pearblog/v1';
	public const REST_BASE      = '/abtests';

	private ABTestEngine $engine;

	public function __construct( ?ABTestEngine $engine = null ) {
		$this->engine = $engine ?? new ABTestEngine();
	}

	// -------------------------------------------------------------------------
	// Registration
	// -------------------------------------------------------------------------

	public function register_routes(): void {
		// Collection endpoints.
		register_rest_route( self::REST_NAMESPACE, self::REST_BASE, [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'list_tests' ],
				'permission_callback' => [ $this, 'check_permission' ],
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_test' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => $this->create_args(),
			],
		] );

		// Single-test endpoints.
		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/(?P<id>[a-z0-9_]+)', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_test' ],
				'permission_callback' => [ $this, 'check_permission' ],
			],
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_test' ],
				'permission_callback' => [ $this, 'check_permission' ],
			],
		] );

		// Promote single test.
		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/(?P<id>[a-z0-9_]+)/promote', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'promote_test' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		// Statistical results.
		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/(?P<id>[a-z0-9_]+)/results', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_results' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		// Promote all mature tests.
		register_rest_route( self::REST_NAMESPACE, self::REST_BASE . '/promote-all', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'promote_all' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * GET /abtests — return all tests with computed summary fields.
	 */
	public function list_tests( \WP_REST_Request $request ): \WP_REST_Response {
		$tests  = $this->engine->list_tests();
		$output = [];

		foreach ( $tests as $test_id => $test ) {
			$output[] = $this->format_test( $test_id, $test );
		}

		return new \WP_REST_Response( [
			'count' => count( $output ),
			'tests' => $output,
		], 200 );
	}

	/**
	 * POST /abtests — create a new A/B test.
	 */
	public function create_test( \WP_REST_Request $request ): \WP_REST_Response {
		$topic      = sanitize_text_field( (string) $request->get_param( 'topic' ) );
		$modifier_a = sanitize_textarea_field( (string) $request->get_param( 'modifier_a' ) );
		$modifier_b = sanitize_textarea_field( (string) $request->get_param( 'modifier_b' ) );

		if ( '' === $topic || '' === $modifier_a || '' === $modifier_b ) {
			return new \WP_REST_Response( [ 'error' => 'topic, modifier_a, and modifier_b are required.' ], 400 );
		}

		$test_id = $this->engine->create_test( $topic, $modifier_a, $modifier_b );
		$test    = $this->engine->get_test( $test_id );

		return new \WP_REST_Response( $this->format_test( $test_id, $test ), 201 );
	}

	/**
	 * GET /abtests/{id} — return a single test.
	 */
	public function get_test( \WP_REST_Request $request ): \WP_REST_Response {
		$test_id = (string) $request->get_param( 'id' );
		$test    = $this->engine->get_test( $test_id );

		if ( null === $test ) {
			return new \WP_REST_Response( [ 'error' => 'Test not found.' ], 404 );
		}

		return new \WP_REST_Response( $this->format_test( $test_id, $test ), 200 );
	}

	/**
	 * DELETE /abtests/{id} — delete a test.
	 */
	public function delete_test( \WP_REST_Request $request ): \WP_REST_Response {
		$test_id = (string) $request->get_param( 'id' );
		$deleted = $this->engine->delete_test( $test_id );

		if ( ! $deleted ) {
			return new \WP_REST_Response( [ 'error' => 'Test not found.' ], 404 );
		}

		return new \WP_REST_Response( [ 'deleted' => true, 'test_id' => $test_id ], 200 );
	}

	/**
	 * POST /abtests/{id}/promote — force-elect the winner of a specific test.
	 */
	public function promote_test( \WP_REST_Request $request ): \WP_REST_Response {
		$test_id = (string) $request->get_param( 'id' );
		$test    = $this->engine->get_test( $test_id );

		if ( null === $test ) {
			return new \WP_REST_Response( [ 'error' => 'Test not found.' ], 404 );
		}

		$winner = $this->engine->promote_winner( $test_id );

		if ( null === $winner ) {
			return new \WP_REST_Response( [
				'promoted' => false,
				'message'  => sprintf(
					'Not enough data yet (need %d articles per variant).',
					ABTestEngine::MIN_ARTICLES_PER_VARIANT
				),
			], 200 );
		}

		return new \WP_REST_Response( [
			'promoted' => true,
			'winner'   => $winner,
			'avg_a'    => $this->engine->get_average_score( $test_id, 'a' ),
			'avg_b'    => $this->engine->get_average_score( $test_id, 'b' ),
		], 200 );
	}

	/**
	 * GET /abtests/{id}/results — statistical summary for a test.
	 */
	public function get_results( \WP_REST_Request $request ): \WP_REST_Response {
		$test_id = (string) $request->get_param( 'id' );
		$test    = $this->engine->get_test( $test_id );

		if ( null === $test ) {
			return new \WP_REST_Response( [ 'error' => 'Test not found.' ], 404 );
		}

		$scores_a = $test['variants']['a']['scores'] ?? [];
		$scores_b = $test['variants']['b']['scores'] ?? [];
		$avg_a    = $this->engine->get_average_score( $test_id, 'a' );
		$avg_b    = $this->engine->get_average_score( $test_id, 'b' );

		$relative_lift = ( $avg_a > 0 )
			? round( ( ( $avg_b - $avg_a ) / $avg_a ) * 100, 2 )
			: 0.0;

		return new \WP_REST_Response( [
			'test_id'           => $test_id,
			'topic'             => $test['topic'],
			'winner'            => $test['winner'],
			'status'            => null !== $test['winner'] ? 'completed' : 'running',
			'variant_a'         => [
				'articles'      => count( $scores_a ),
				'avg_quality'   => $avg_a,
				'scores'        => $scores_a,
				'modifier'      => $test['modifier_a'],
			],
			'variant_b'         => [
				'articles'      => count( $scores_b ),
				'avg_quality'   => $avg_b,
				'scores'        => $scores_b,
				'modifier'      => $test['modifier_b'],
			],
			'relative_lift_pct' => $relative_lift,
			'leading_variant'   => ( $avg_b > $avg_a ) ? 'b' : ( $avg_a > $avg_b ? 'a' : 'tie' ),
			'mature_in_days'    => max( 0, ABTestEngine::PROMOTION_DAYS - (int) floor( ( time() - $test['created_at'] ) / DAY_IN_SECONDS ) ),
		], 200 );
	}

	/**
	 * POST /abtests/promote-all — promote all tests that are past promotion threshold.
	 */
	public function promote_all( \WP_REST_Request $request ): \WP_REST_Response {
		$results = $this->engine->promote_mature_tests();
		$promoted = array_filter( $results, fn( $v ) => null !== $v );

		return new \WP_REST_Response( [
			'evaluated' => count( $results ),
			'promoted'  => count( $promoted ),
			'results'   => $results,
		], 200 );
	}

	// -------------------------------------------------------------------------
	// Permission
	// -------------------------------------------------------------------------

	public function check_permission( \WP_REST_Request $request ): bool {
		if ( function_exists( '\current_user_can' ) && \current_user_can( 'manage_options' ) ) {
			return true;
		}

		$api_key = (string) get_option( 'pearblog_api_key', '' );
		if ( '' === $api_key ) {
			return false;
		}

		$auth  = (string) ( $request->get_header( 'authorization' ) ?? '' );
		$token = str_starts_with( strtolower( $auth ), 'bearer ' ) ? substr( $auth, 7 ) : '';

		return hash_equals( $api_key, $token );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Format a test record for REST output.
	 */
	private function format_test( string $test_id, array $test ): array {
		$avg_a = $this->engine->get_average_score( $test_id, 'a' );
		$avg_b = $this->engine->get_average_score( $test_id, 'b' );

		return [
			'id'             => $test_id,
			'topic'          => $test['topic'],
			'status'         => null !== $test['winner'] ? 'completed' : 'running',
			'winner'         => $test['winner'],
			'created_at'     => gmdate( 'Y-m-d H:i:s', $test['created_at'] ),
			'promoted_at'    => $test['promoted_at'] ? gmdate( 'Y-m-d H:i:s', $test['promoted_at'] ) : null,
			'runs_a'         => $test['variants']['a']['runs'],
			'runs_b'         => $test['variants']['b']['runs'],
			'avg_quality_a'  => $avg_a,
			'avg_quality_b'  => $avg_b,
			'leading'        => ( $avg_b > $avg_a ) ? 'b' : ( $avg_a > $avg_b ? 'a' : 'tie' ),
			'modifier_a'     => $test['modifier_a'],
			'modifier_b'     => $test['modifier_b'],
		];
	}

	/**
	 * Args schema for test creation.
	 */
	private function create_args(): array {
		return [
			'topic'      => [
				'required'    => true,
				'type'        => 'string',
				'description' => 'Topic to split-test (exact match to article topic).',
			],
			'modifier_a' => [
				'required'    => true,
				'type'        => 'string',
				'description' => 'Additional prompt instructions appended for variant A.',
			],
			'modifier_b' => [
				'required'    => true,
				'type'        => 'string',
				'description' => 'Additional prompt instructions appended for variant B.',
			],
		];
	}
}
