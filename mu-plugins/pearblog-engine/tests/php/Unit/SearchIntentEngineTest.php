<?php
/**
 * Unit tests for SearchIntentEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Analytics\SearchIntentEngine;

class SearchIntentEngineTest extends TestCase {

	private SearchIntentEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [ 'pearblog_intent_engine_enabled' => true ];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_actions']   = [];
		$GLOBALS['_posts']     = [];
		$GLOBALS['_post_list'] = [];
		$this->engine = new SearchIntentEngine();
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_defaults_to_true(): void {
		$GLOBALS['_options'] = [];
		$e = new SearchIntentEngine();
		$this->assertTrue( $e->is_enabled() );
	}

	public function test_is_enabled_respects_option(): void {
		$GLOBALS['_options']['pearblog_intent_engine_enabled'] = false;
		$e = new SearchIntentEngine();
		$this->assertFalse( $e->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// register()
	// -----------------------------------------------------------------------

	public function test_register_adds_rest_api_init_action(): void {
		$this->engine->register();
		$this->assertNotEmpty( $GLOBALS['_actions']['rest_api_init'] ?? [] );
	}

	public function test_register_disabled_skips_hooks(): void {
		$GLOBALS['_options']['pearblog_intent_engine_enabled'] = false;
		$e = new SearchIntentEngine();
		$e->register();
		$this->assertEmpty( $GLOBALS['_actions']['rest_api_init'] ?? [] );
	}

	// -----------------------------------------------------------------------
	// classify_text
	// -----------------------------------------------------------------------

	public function test_informational_intent_for_how_to_text(): void {
		$text   = 'how to learn programming from scratch guide tutorial';
		$intent = $this->engine->classify_text( $text );
		$this->assertSame( SearchIntentEngine::INTENT_INFORMATIONAL, $intent );
	}

	public function test_commercial_intent_for_best_review_text(): void {
		$text   = 'best laptop review comparison top rated 2024 vs alternative';
		$intent = $this->engine->classify_text( $text );
		$this->assertSame( SearchIntentEngine::INTENT_COMMERCIAL, $intent );
	}

	public function test_transactional_intent_for_buy_text(): void {
		$text   = 'buy discount coupon price cheap order now download subscribe';
		$intent = $this->engine->classify_text( $text );
		$this->assertSame( SearchIntentEngine::INTENT_TRANSACTIONAL, $intent );
	}

	public function test_navigational_intent_for_login_text(): void {
		$text   = 'login sign in official website homepage contact';
		$intent = $this->engine->classify_text( $text );
		$this->assertSame( SearchIntentEngine::INTENT_NAVIGATIONAL, $intent );
	}

	public function test_empty_text_defaults_to_informational(): void {
		$intent = $this->engine->classify_text( '' );
		$this->assertSame( SearchIntentEngine::INTENT_INFORMATIONAL, $intent );
	}

	public function test_result_is_always_a_valid_intent(): void {
		foreach ( [ 'random gibberish', 'xyz 123', '' ] as $text ) {
			$intent = $this->engine->classify_text( $text );
			$this->assertContains( $intent, SearchIntentEngine::ALL_INTENTS );
		}
	}

	// -----------------------------------------------------------------------
	// classify_post
	// -----------------------------------------------------------------------

	public function test_classify_post_returns_valid_intent(): void {
		$post = $this->make_post( 1, 'How to build a website guide tutorial from scratch' );
		$result = $this->engine->classify_post( $post );

		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['post_id'] );
		$this->assertContains( $result['intent'], SearchIntentEngine::ALL_INTENTS );
	}

	public function test_classify_post_persists_intent_in_meta(): void {
		$post = $this->make_post( 2, 'Best laptop buy order discount price' );
		$this->engine->classify_post( $post );

		$stored = get_post_meta( 2, '_pearblog_search_intent', true );
		$this->assertNotEmpty( $stored );
		$this->assertContains( $stored, SearchIntentEngine::ALL_INTENTS );
	}

	public function test_classify_post_stores_timestamp(): void {
		$before = time();
		$post   = $this->make_post( 3, 'What is machine learning' );
		$this->engine->classify_post( $post );
		$after = time();

		$ts = (int) get_post_meta( 3, '_pearblog_search_intent_at', true );
		$this->assertGreaterThanOrEqual( $before, $ts );
		$this->assertLessThanOrEqual( $after + 1, $ts );
	}

	// -----------------------------------------------------------------------
	// get_intent_stats
	// -----------------------------------------------------------------------

	public function test_get_intent_stats_returns_all_intents_as_keys(): void {
		$stats = $this->engine->get_intent_stats();
		foreach ( SearchIntentEngine::ALL_INTENTS as $intent ) {
			$this->assertArrayHasKey( $intent, $stats );
		}
	}

	public function test_get_intent_stats_increments_on_classify(): void {
		$post_ids = [ 10, 11, 12 ];
		$GLOBALS['_post_list'] = $post_ids;

		foreach ( $post_ids as $id ) {
			$post   = $this->make_post( $id, 'how to learn something guide tutorial' );
			$GLOBALS['_posts'][ $id ] = $post;
			update_post_meta( $id, '_pearblog_search_intent', SearchIntentEngine::INTENT_INFORMATIONAL );
		}

		$stats = $this->engine->get_intent_stats();
		$this->assertGreaterThanOrEqual( 3, $stats[ SearchIntentEngine::INTENT_INFORMATIONAL ] );
	}

	// -----------------------------------------------------------------------
	// REST permission
	// -----------------------------------------------------------------------

	public function test_rest_permission_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->engine->rest_permission() );
	}

	public function test_rest_permission_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->engine->rest_permission() );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	public function test_rest_get_returns_404_when_not_classified(): void {
		$GLOBALS['_current_user_can'] = true;
		$request = new \WP_REST_Request();
		$request->set_param( 'id', 999 );
		$response = $this->engine->rest_get( $request );
		$this->assertSame( 404, $response->status );
	}

	public function test_rest_get_returns_200_when_classified(): void {
		$GLOBALS['_current_user_can'] = true;

		update_post_meta( 50, '_pearblog_search_intent', SearchIntentEngine::INTENT_COMMERCIAL );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 50 );
		$response = $this->engine->rest_get( $request );
		$this->assertSame( 200, $response->status );
		$this->assertSame( SearchIntentEngine::INTENT_COMMERCIAL, $response->data['intent'] );
	}

	public function test_rest_classify_returns_404_for_missing_post(): void {
		$GLOBALS['_current_user_can'] = true;
		$request = new \WP_REST_Request();
		$request->set_param( 'id', 9999 );
		$response = $this->engine->rest_classify( $request );
		$this->assertSame( 404, $response->status );
	}

	public function test_rest_classify_returns_200_for_existing_post(): void {
		$GLOBALS['_current_user_can'] = true;
		$post = $this->make_post( 20, 'best laptop review comparison' );
		$GLOBALS['_posts'][20] = $post;

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 20 );
		$response = $this->engine->rest_classify( $request );
		$this->assertSame( 200, $response->status );
	}

	public function test_rest_stats_returns_200(): void {
		$GLOBALS['_current_user_can'] = true;
		$request  = new \WP_REST_Request();
		$response = $this->engine->rest_stats( $request );
		$this->assertSame( 200, $response->status );
		$this->assertIsArray( $response->data );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function make_post( int $id, string $content = '' ): \WP_Post {
		return new \WP_Post( [
			'ID'           => $id,
			'post_title'   => "Test post {$id}",
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_author'  => 1,
		] );
	}
}
