<?php

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\DecisionPlatform\QuizEngine;

/**
 * @covers \PearBlogEngine\DecisionPlatform\QuizEngine
 */
class QuizEngineTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_options']         = [];
		$GLOBALS['_post_meta']       = [];
		$GLOBALS['_actions']         = [];
		$GLOBALS['_action_handlers'] = [];
		$GLOBALS['_current_user_can'] = false;
		$GLOBALS['_posts']           = [];
		$GLOBALS['_mail_log']        = [];
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
	// get_questions()
	// -----------------------------------------------------------------------

	public function test_get_questions_returns_empty_array_when_no_meta(): void {
		$engine = new QuizEngine();
		$result = $engine->get_questions( 1 );
		$this->assertSame( [], $result );
	}

	public function test_get_questions_returns_array_when_meta_is_array(): void {
		$questions = [
			[ 'question' => 'What is your budget?', 'answers' => [ 'Low', 'Medium', 'High' ] ],
			[ 'question' => 'Your age range?',       'answers' => [ '18-25', '26-40', '40+' ] ],
		];
		$GLOBALS['_post_meta'][10][QuizEngine::META_QUESTIONS] = [ $questions ];

		$engine = new QuizEngine();
		$result = $engine->get_questions( 10 );

		$this->assertCount( 2, $result );
		$this->assertSame( 'What is your budget?', $result[0]['question'] );
	}

	public function test_get_questions_decodes_json_string_meta(): void {
		$questions_json = json_encode( [
			[ 'question' => 'Preferred city?', 'answers' => [ 'Warsaw', 'Krakow' ] ],
		] );
		$GLOBALS['_post_meta'][11][QuizEngine::META_QUESTIONS] = [ $questions_json ];

		$engine = new QuizEngine();
		$result = $engine->get_questions( 11 );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Preferred city?', $result[0]['question'] );
	}

	public function test_get_questions_returns_empty_on_invalid_json(): void {
		$GLOBALS['_post_meta'][12][QuizEngine::META_QUESTIONS] = [ 'not valid json {{{' ];

		$engine = new QuizEngine();
		$result = $engine->get_questions( 12 );

		$this->assertSame( [], $result );
	}

	public function test_get_questions_returns_empty_on_empty_string_meta(): void {
		$GLOBALS['_post_meta'][13][QuizEngine::META_QUESTIONS] = [ '' ];

		$engine = new QuizEngine();
		$result = $engine->get_questions( 13 );

		$this->assertSame( [], $result );
	}

	// -----------------------------------------------------------------------
	// generate_recommendation() – early-return paths
	// -----------------------------------------------------------------------

	public function test_generate_recommendation_returns_default_message_when_no_questions(): void {
		// No post meta → get_questions returns [].
		$engine = new QuizEngine();
		$result = $engine->generate_recommendation( 20, [ '0' => 'Answer A' ] );

		$this->assertStringContainsString( 'Thank you', $result );
	}

	public function test_generate_recommendation_returns_default_message_when_no_answers(): void {
		$questions = [
			[ 'question' => 'Pick one?', 'answers' => [ 'A', 'B' ] ],
		];
		$GLOBALS['_post_meta'][21][QuizEngine::META_QUESTIONS] = [ $questions ];

		$engine = new QuizEngine();
		$result = $engine->generate_recommendation( 21, [] );

		$this->assertStringContainsString( 'Thank you', $result );
	}

	// -----------------------------------------------------------------------
	// capture_lead()
	// -----------------------------------------------------------------------

	public function test_capture_lead_stores_lead_in_option(): void {
		$engine = new QuizEngine();
		$engine->capture_lead( 30, 'user@example.com', 'Alice', 'Go for plan A.' );

		$leads = get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertCount( 1, $leads );
	}

	public function test_capture_lead_stores_correct_quiz_id(): void {
		$engine = new QuizEngine();
		$engine->capture_lead( 31, 'bob@example.com', 'Bob', 'Choose plan B.' );

		$leads = get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertSame( 31, $leads[0]['quiz_id'] );
	}

	public function test_capture_lead_stores_sanitized_email(): void {
		$engine = new QuizEngine();
		$engine->capture_lead( 32, 'carol@example.com', 'Carol', 'Recommendation.' );

		$leads = get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertSame( 'carol@example.com', $leads[0]['email'] );
	}

	public function test_capture_lead_stores_recommendation(): void {
		$engine = new QuizEngine();
		$engine->capture_lead( 33, 'dave@example.com', 'Dave', 'Select product X.' );

		$leads = get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertSame( 'Select product X.', $leads[0]['recommendation'] );
	}

	public function test_capture_lead_appends_to_existing_leads(): void {
		update_option( QuizEngine::OPTION_LEADS, [
			[ 'quiz_id' => 1, 'email' => 'prev@example.com', 'name' => 'Prev', 'recommendation' => 'Old.' ],
		] );

		$engine = new QuizEngine();
		$engine->capture_lead( 34, 'new@example.com', 'New', 'New rec.' );

		$leads = get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertCount( 2, $leads );
	}

	public function test_capture_lead_fires_action(): void {
		$fired = false;
		add_action( 'pearblog_quiz_lead_captured', function () use ( &$fired ) {
			$fired = true;
		} );

		$engine = new QuizEngine();
		$engine->capture_lead( 35, 'eve@example.com', 'Eve', 'Try option C.' );

		$this->assertTrue( $fired );
	}

	public function test_capture_lead_calls_wp_mail(): void {
		$engine = new QuizEngine();
		$engine->capture_lead( 36, 'frank@example.com', 'Frank', 'Rec text.' );

		$this->assertNotEmpty( $GLOBALS['_mail_log'] );
		$this->assertSame( 'frank@example.com', $GLOBALS['_mail_log'][0]['to'] );
	}

	public function test_capture_lead_ring_buffer_trims_to_max(): void {
		// Pre-fill with 500 leads.
		$leads = [];
		for ( $i = 0; $i < 500; $i++ ) {
			$leads[] = [
				'quiz_id'        => 1,
				'email'          => "user{$i}@example.com",
				'name'           => "User {$i}",
				'recommendation' => "Rec {$i}",
				'captured_at'    => time(),
			];
		}
		update_option( QuizEngine::OPTION_LEADS, $leads );

		$engine = new QuizEngine();
		$engine->capture_lead( 37, 'overflow@example.com', 'Overflow', 'Overflowed.' );

		$stored = get_option( QuizEngine::OPTION_LEADS, [] );
		$this->assertCount( 500, $stored );
	}

	public function test_capture_lead_ring_buffer_newest_lead_is_last(): void {
		$leads = [];
		for ( $i = 0; $i < 500; $i++ ) {
			$leads[] = [
				'quiz_id'        => 1,
				'email'          => "user{$i}@example.com",
				'name'           => "User {$i}",
				'recommendation' => "Rec {$i}",
				'captured_at'    => time(),
			];
		}
		update_option( QuizEngine::OPTION_LEADS, $leads );

		$engine = new QuizEngine();
		$engine->capture_lead( 38, 'newest@example.com', 'Newest', 'Latest rec.' );

		$stored = get_option( QuizEngine::OPTION_LEADS, [] );
		$last   = end( $stored );
		$this->assertSame( 'newest@example.com', $last['email'] );
	}

	// -----------------------------------------------------------------------
	// rest_get_leads()
	// -----------------------------------------------------------------------

	public function test_rest_get_leads_returns_empty_when_no_leads(): void {
		$request  = new \WP_REST_Request( 'GET' );
		$engine   = new QuizEngine();
		$response = $engine->rest_get_leads( $request );

		$data = $response->get_data();
		$this->assertSame( 0,  $data['count'] );
		$this->assertSame( [], $data['leads'] );
	}

	public function test_rest_get_leads_returns_count_of_stored_leads(): void {
		update_option( QuizEngine::OPTION_LEADS, [
			[ 'email' => 'a@example.com' ],
			[ 'email' => 'b@example.com' ],
			[ 'email' => 'c@example.com' ],
		] );

		$request  = new \WP_REST_Request( 'GET' );
		$engine   = new QuizEngine();
		$response = $engine->rest_get_leads( $request );

		$data = $response->get_data();
		$this->assertSame( 3, $data['count'] );
	}

	public function test_rest_get_leads_returns_200_status(): void {
		$request  = new \WP_REST_Request( 'GET' );
		$engine   = new QuizEngine();
		$response = $engine->rest_get_leads( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_rest_get_leads_caps_at_50_items(): void {
		$leads = [];
		for ( $i = 0; $i < 100; $i++ ) {
			$leads[] = [ 'email' => "u{$i}@example.com" ];
		}
		update_option( QuizEngine::OPTION_LEADS, $leads );

		$request  = new \WP_REST_Request( 'GET' );
		$engine   = new QuizEngine();
		$response = $engine->rest_get_leads( $request );

		$data = $response->get_data();
		$this->assertCount( 50, $data['leads'] );
	}

	// -----------------------------------------------------------------------
	// admin_permission()
	// -----------------------------------------------------------------------

	public function test_admin_permission_false_when_no_capability(): void {
		$GLOBALS['_current_user_can'] = false;
		$engine = new QuizEngine();
		$this->assertFalse( $engine->admin_permission() );
	}

	public function test_admin_permission_true_when_manage_options(): void {
		$GLOBALS['_current_user_can'] = true;
		$engine = new QuizEngine();
		$this->assertTrue( $engine->admin_permission() );
	}
}
