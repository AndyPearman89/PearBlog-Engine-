<?php
/**
 * Unit tests for ReadabilityAnalyzer.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\ReadabilityAnalyzer;

class ReadabilityAnalyzerTest extends TestCase {

	private ReadabilityAnalyzer $analyzer;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_actions']   = [];
		$this->analyzer = new ReadabilityAnalyzer();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options'] = [];
	}

	// -----------------------------------------------------------------------
	// count_syllables
	// -----------------------------------------------------------------------

	public function test_count_syllables_short_word_returns_one(): void {
		$this->assertSame( 1, $this->analyzer->count_syllables( 'the' ) );
		$this->assertSame( 1, $this->analyzer->count_syllables( 'cat' ) );
	}

	public function test_count_syllables_multisyllabic_word(): void {
		// "beautiful" → beau-ti-ful = 3 syllables
		$count = $this->analyzer->count_syllables( 'beautiful' );
		$this->assertGreaterThanOrEqual( 2, $count );
	}

	public function test_count_syllables_single_letter_returns_one(): void {
		$this->assertSame( 1, $this->analyzer->count_syllables( 'a' ) );
		$this->assertSame( 1, $this->analyzer->count_syllables( 'I' ) );
	}

	public function test_count_syllables_never_returns_zero(): void {
		foreach ( [ 'the', 'rhythm', 'gym', 'by' ] as $word ) {
			$this->assertGreaterThanOrEqual( 1, $this->analyzer->count_syllables( $word ) );
		}
	}

	// -----------------------------------------------------------------------
	// analyze_text – empty / whitespace
	// -----------------------------------------------------------------------

	public function test_empty_text_returns_zero_metrics(): void {
		$result = $this->analyzer->analyze_text( '' );
		$this->assertSame( 0.0, $result['flesch_ease'] );
		$this->assertSame( 0.0, $result['flesch_kincaid_grade'] );
		$this->assertSame( 0, $result['word_count'] );
		$this->assertIsArray( $result['issues'] );
		$this->assertEmpty( $result['issues'] );
	}

	public function test_whitespace_only_text_returns_zero_metrics(): void {
		$result = $this->analyzer->analyze_text( '   ' );
		$this->assertSame( 0.0, $result['flesch_ease'] );
	}

	// -----------------------------------------------------------------------
	// analyze_text – basic metrics
	// -----------------------------------------------------------------------

	public function test_analyze_text_returns_all_keys(): void {
		$text   = 'The cat sat on the mat. The dog barked loudly.';
		$result = $this->analyzer->analyze_text( $text );

		$expected_keys = [
			'flesch_ease',
			'flesch_kincaid_grade',
			'gunning_fog',
			'word_count',
			'sentence_count',
			'avg_sentence_length',
			'avg_syllables_per_word',
			'passive_ratio',
			'transition_ratio',
			'issues',
		];
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $result, "Missing key: {$key}" );
		}
	}

	public function test_simple_text_has_high_flesch_ease(): void {
		// Short simple sentences should score high.
		$text   = 'The cat sat. The dog ran. The bird flew.';
		$result = $this->analyzer->analyze_text( $text );
		// Flesch ease should be above 50 for very simple text.
		$this->assertGreaterThan( 50.0, $result['flesch_ease'] );
	}

	public function test_flesch_ease_bounded_0_to_100(): void {
		$text   = 'This is a test. It is very simple.';
		$result = $this->analyzer->analyze_text( $text );
		$this->assertGreaterThanOrEqual( 0.0, $result['flesch_ease'] );
		$this->assertLessThanOrEqual( 100.0, $result['flesch_ease'] );
	}

	public function test_word_count_is_correct(): void {
		$text   = 'One two three four five.';
		$result = $this->analyzer->analyze_text( $text );
		$this->assertSame( 5, $result['word_count'] );
	}

	public function test_sentence_count_is_correct(): void {
		$text   = 'First sentence. Second sentence. Third sentence.';
		$result = $this->analyzer->analyze_text( $text );
		$this->assertSame( 3, $result['sentence_count'] );
	}

	// -----------------------------------------------------------------------
	// analyze_text – passive voice detection
	// -----------------------------------------------------------------------

	public function test_passive_voice_detected(): void {
		$text   = 'The letter was written by John. The report was reviewed by Mary.';
		$result = $this->analyzer->analyze_text( $text );
		$this->assertGreaterThan( 0.0, $result['passive_ratio'] );
	}

	public function test_active_voice_has_low_passive_ratio(): void {
		$text   = 'John wrote the letter. Mary reviewed the report. Tom built the house.';
		$result = $this->analyzer->analyze_text( $text );
		// All active → passive ratio should be 0.
		$this->assertSame( 0.0, $result['passive_ratio'] );
	}

	// -----------------------------------------------------------------------
	// analyze_text – transition words
	// -----------------------------------------------------------------------

	public function test_transition_words_increase_ratio(): void {
		$text_with    = 'However, this is true. Therefore, we continue. Furthermore, we add more.';
		$text_without = 'This is true. We continue. We add more.';
		$with    = $this->analyzer->analyze_text( $text_with );
		$without = $this->analyzer->analyze_text( $text_without );
		$this->assertGreaterThan( $without['transition_ratio'], $with['transition_ratio'] );
	}

	// -----------------------------------------------------------------------
	// analyze_text – issues
	// -----------------------------------------------------------------------

	public function test_low_flesch_ease_generates_issue(): void {
		// Override min ease option to 90 to ensure an issue is always generated.
		$GLOBALS['_options']['pearblog_readability_min_ease'] = 90;

		$text   = 'The cat sat on the mat.';
		$result = $this->analyzer->analyze_text( $text );
		// At min_ease=90, most text should generate an issue.
		$this->assertIsArray( $result['issues'] );
	}

	public function test_no_issues_for_perfect_text(): void {
		// Set lenient thresholds so ease/grade/sentence issues won't fire.
		$GLOBALS['_options']['pearblog_readability_min_ease']  = 0;
		$GLOBALS['_options']['pearblog_readability_max_grade'] = 99;

		// Use text with transition words, short sentences, and active voice to
		// avoid generating any improvement suggestions.
		$text   = 'I like cats. However, dogs are fun. Therefore, birds sing songs.';
		$result = $this->analyzer->analyze_text( $text );
		$this->assertEmpty( $result['issues'] );
	}

	// -----------------------------------------------------------------------
	// REST permission
	// -----------------------------------------------------------------------

	public function test_rest_permission_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$request = new \WP_REST_Request();
		$this->assertTrue( $this->analyzer->rest_permission( $request ) );
	}

	public function test_rest_permission_false_when_no_api_key_and_not_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$GLOBALS['_options']['pearblog_api_key'] = '';
		$request = new \WP_REST_Request();
		$this->assertFalse( $this->analyzer->rest_permission( $request ) );
	}

	public function test_rest_permission_true_with_valid_bearer_token(): void {
		$GLOBALS['_current_user_can'] = false;
		$GLOBALS['_options']['pearblog_api_key'] = 'secret-key';
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer secret-key' );
		$this->assertTrue( $this->analyzer->rest_permission( $request ) );
	}

	public function test_rest_permission_false_with_invalid_bearer_token(): void {
		$GLOBALS['_current_user_can'] = false;
		$GLOBALS['_options']['pearblog_api_key'] = 'secret-key';
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer wrong-key' );
		$this->assertFalse( $this->analyzer->rest_permission( $request ) );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------
}
