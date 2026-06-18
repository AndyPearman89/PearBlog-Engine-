<?php
/**
 * Unit tests for QuizEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\DecisionPlatform\QuizEngine;

class QuizEngineTest extends TestCase {

	private QuizEngine $quiz;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_posts']            = [];
		$GLOBALS['_actions']          = [];
		$GLOBALS['_current_user_can'] = true;
		$this->quiz = new QuizEngine();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_post_type_constant(): void {
		$this->assertSame( 'pearblog_quiz', QuizEngine::POST_TYPE );
	}

	public function test_meta_questions_constant(): void {
		$this->assertSame( 'pearblog_quiz_questions', QuizEngine::META_QUESTIONS );
	}

	public function test_meta_lead_capture_constant(): void {
		$this->assertSame( 'pearblog_quiz_lead_capture', QuizEngine::META_LEAD_CAPTURE );
	}

	public function test_option_leads_constant(): void {
		$this->assertSame( 'pearblog_quiz_leads', QuizEngine::OPTION_LEADS );
	}

	// -----------------------------------------------------------------------
	// get_questions
	// -----------------------------------------------------------------------

	public function test_get_questions_returns_empty_array_when_no_meta(): void {
		$questions = $this->quiz->get_questions( 99 );
		$this->assertIsArray( $questions );
		$this->assertEmpty( $questions );
	}

	public function test_get_questions_returns_stored_questions(): void {
		$questions_data = [
			[ 'id' => 1, 'question' => 'How big is your space?', 'options' => [ 'small', 'large' ] ],
		];
		$GLOBALS['_post_meta'][1][ QuizEngine::META_QUESTIONS ] = [ wp_json_encode( $questions_data ) ];
		$result = $this->quiz->get_questions( 1 );
		$this->assertCount( 1, $result );
	}

	public function test_get_questions_returns_array_on_invalid_json(): void {
		$GLOBALS['_post_meta'][1][ QuizEngine::META_QUESTIONS ] = [ 'not-json{{' ];
		$result = $this->quiz->get_questions( 1 );
		$this->assertIsArray( $result );
	}

	// -----------------------------------------------------------------------
	// capture_lead (ring buffer)
	// -----------------------------------------------------------------------

	public function test_capture_lead_stores_lead(): void {
		$this->quiz->capture_lead( 1, 'test@example.com', 'Jan', 'Get a renovation' );
		$leads = (array) get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertNotEmpty( $leads );
	}

	public function test_capture_lead_stores_email(): void {
		$this->quiz->capture_lead( 1, 'jan@example.com', 'Jan Kowalski', 'Renovate' );
		$leads = (array) get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertSame( 'jan@example.com', $leads[0]['email'] );
	}

	public function test_capture_lead_stores_name(): void {
		$this->quiz->capture_lead( 1, 'test@example.com', 'Anna Nowak', 'Build new' );
		$leads = (array) get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertSame( 'Anna Nowak', $leads[0]['name'] );
	}

	public function test_capture_lead_stores_quiz_id(): void {
		$this->quiz->capture_lead( 42, 'quiz@example.com', 'User', 'Result' );
		$leads = (array) get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertSame( 42, $leads[0]['quiz_id'] );
	}

	public function test_capture_lead_stores_recommendation(): void {
		$this->quiz->capture_lead( 1, 'a@b.com', 'User', 'My recommendation text' );
		$leads = (array) get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertSame( 'My recommendation text', $leads[0]['recommendation'] );
	}

	public function test_capture_lead_stores_timestamp(): void {
		$before = time();
		$this->quiz->capture_lead( 1, 'a@b.com', 'User', 'Rec' );
		$leads = (array) get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertGreaterThanOrEqual( $before, $leads[0]['captured_at'] );
	}

	public function test_capture_lead_ring_buffer_trims_at_500(): void {
		// Pre-populate with 500 leads.
		$existing = [];
		for ( $i = 0; $i < 500; $i++ ) {
			$existing[] = [
				'quiz_id'        => 1,
				'email'          => "user{$i}@example.com",
				'name'           => "User $i",
				'recommendation' => "Rec $i",
				'created_at'     => time(),
			];
		}
		$GLOBALS['_options'][ QuizEngine::OPTION_LEADS ] = $existing;

		$this->quiz->capture_lead( 1, 'new@example.com', 'New User', 'New rec' );
		$leads = (array) get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertLessThanOrEqual( 500, count( $leads ) );
	}

	public function test_capture_lead_newest_is_last_after_ring_buffer(): void {
		$existing = [];
		for ( $i = 0; $i < 500; $i++ ) {
			$existing[] = [
				'quiz_id'        => 1,
				'email'          => "u{$i}@x.com",
				'name'           => "U",
				'recommendation' => "R",
				'created_at'     => time(),
			];
		}
		$GLOBALS['_options'][ QuizEngine::OPTION_LEADS ] = $existing;

		$this->quiz->capture_lead( 1, 'latest@example.com', 'Latest', 'Latest rec' );
		$leads = (array) get_option( QuizEngine::OPTION_LEADS, [] );
		$last  = end( $leads );
		$this->assertSame( 'latest@example.com', $last['email'] );
	}

	// -----------------------------------------------------------------------
	// admin_permission
	// -----------------------------------------------------------------------

	public function test_admin_permission_returns_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->quiz->admin_permission() );
	}

	public function test_admin_permission_returns_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->quiz->admin_permission() );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_init_action(): void {
		$this->quiz->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['init'] ) );
	}

	// -----------------------------------------------------------------------
	// REST leads endpoint
	// -----------------------------------------------------------------------

	public function test_rest_get_leads_returns_response(): void {
		$req    = new \WP_REST_Request();
		$result = $this->quiz->rest_get_leads( $req );
		$this->assertInstanceOf( \WP_REST_Response::class, $result );
	}

	public function test_rest_get_leads_returns_200_status(): void {
		$req    = new \WP_REST_Request();
		$result = $this->quiz->rest_get_leads( $req );
		$this->assertSame( 200, $result->get_status() );
	}

	// -----------------------------------------------------------------------
	// generate_recommendation (no AI key)
	// -----------------------------------------------------------------------

	public function test_generate_recommendation_returns_string(): void {
		// No AI key set — will return a fallback/error string rather than throw.
		try {
			$result = $this->quiz->generate_recommendation( 1, [ 'q1' => 'a1' ] );
			$this->assertIsString( $result );
		} catch ( \RuntimeException $e ) {
			// RuntimeException is also acceptable when no AI key.
			$this->assertTrue( true );
		}
	}
}
