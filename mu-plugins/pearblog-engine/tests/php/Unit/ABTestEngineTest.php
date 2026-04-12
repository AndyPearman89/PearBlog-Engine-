<?php
/**
 * Unit tests for ABTestEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Testing\ABTestEngine;

class ABTestEngineTest extends TestCase {

	private ABTestEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_transients'] = [];
		$this->engine = new ABTestEngine();
	}

	// -----------------------------------------------------------------------
	// create_test / get_test / list_tests / delete_test
	// -----------------------------------------------------------------------

	public function test_create_test_returns_string_id(): void {
		$id = $this->engine->create_test( 'Best Hiking Gear', 'Focus on beginners.', 'Focus on experts.' );

		$this->assertIsString( $id );
		$this->assertStringStartsWith( 'ab_', $id );
	}

	public function test_created_test_is_retrievable(): void {
		$id   = $this->engine->create_test( 'Travel Tips', 'Budget travel.', 'Luxury travel.' );
		$test = $this->engine->get_test( $id );

		$this->assertNotNull( $test );
		$this->assertSame( $id, $test['id'] );
		$this->assertSame( 'Travel Tips', $test['topic'] );
		$this->assertSame( 'Budget travel.', $test['modifier_a'] );
		$this->assertSame( 'Luxury travel.', $test['modifier_b'] );
		$this->assertNull( $test['winner'] );
		$this->assertNull( $test['promoted_at'] );
		$this->assertSame( 0, $test['variants']['a']['runs'] );
		$this->assertSame( 0, $test['variants']['b']['runs'] );
	}

	public function test_get_test_returns_null_for_unknown_id(): void {
		$this->assertNull( $this->engine->get_test( 'ab_unknown' ) );
	}

	public function test_list_tests_returns_all_tests(): void {
		$id1 = $this->engine->create_test( 'Topic A', 'Ma', 'Mb' );
		$id2 = $this->engine->create_test( 'Topic B', 'Mc', 'Md' );

		$all = $this->engine->list_tests();

		$this->assertArrayHasKey( $id1, $all );
		$this->assertArrayHasKey( $id2, $all );
	}

	public function test_delete_test_removes_it(): void {
		$id = $this->engine->create_test( 'To Delete', 'A', 'B' );
		$this->assertTrue( $this->engine->delete_test( $id ) );
		$this->assertNull( $this->engine->get_test( $id ) );
	}

	public function test_delete_nonexistent_test_returns_false(): void {
		$this->assertFalse( $this->engine->delete_test( 'ab_nope' ) );
	}

	// -----------------------------------------------------------------------
	// get_next_variant (round-robin)
	// -----------------------------------------------------------------------

	public function test_first_variant_is_a(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );
		$this->assertSame( 'a', $this->engine->get_next_variant( $id ) );
	}

	public function test_variant_balances_runs(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		// Record one run for A.
		$this->engine->record_article( $id, 'a', 1, 70.0 );

		// Now B has fewer runs, so the next variant should be b.
		$this->assertSame( 'b', $this->engine->get_next_variant( $id ) );
	}

	public function test_variant_returns_a_when_tied(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		// Equal runs: both at 1.
		$this->engine->record_article( $id, 'a', 1, 70.0 );
		$this->engine->record_article( $id, 'b', 2, 65.0 );

		$this->assertSame( 'a', $this->engine->get_next_variant( $id ) );
	}

	// -----------------------------------------------------------------------
	// record_article / get_average_score
	// -----------------------------------------------------------------------

	public function test_record_article_increments_runs(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		$this->engine->record_article( $id, 'a', 10, 80.0 );
		$this->engine->record_article( $id, 'a', 11, 60.0 );

		$test = $this->engine->get_test( $id );
		$this->assertSame( 2, $test['variants']['a']['runs'] );
		$this->assertContains( 10, $test['variants']['a']['post_ids'] );
		$this->assertContains( 11, $test['variants']['a']['post_ids'] );
	}

	public function test_get_average_score_correct(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		$this->engine->record_article( $id, 'a', 10, 80.0 );
		$this->engine->record_article( $id, 'a', 11, 60.0 );

		$this->assertEqualsWithDelta( 70.0, $this->engine->get_average_score( $id, 'a' ), 0.001 );
	}

	public function test_get_average_score_returns_zero_with_no_data(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );
		$this->assertSame( 0.0, $this->engine->get_average_score( $id, 'a' ) );
	}

	public function test_get_average_score_returns_zero_for_unknown_test(): void {
		$this->assertSame( 0.0, $this->engine->get_average_score( 'ab_nope', 'a' ) );
	}

	public function test_record_article_ignores_invalid_variant(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );
		$this->engine->record_article( $id, 'c', 99, 50.0 ); // 'c' is invalid.

		$test = $this->engine->get_test( $id );
		$this->assertSame( 0, $test['variants']['a']['runs'] );
		$this->assertSame( 0, $test['variants']['b']['runs'] );
	}

	// -----------------------------------------------------------------------
	// promote_winner
	// -----------------------------------------------------------------------

	public function test_promote_winner_elects_b_when_b_is_better(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		$this->engine->record_article( $id, 'a', 1, 60.0 );
		$this->engine->record_article( $id, 'a', 2, 62.0 );
		$this->engine->record_article( $id, 'b', 3, 80.0 );
		$this->engine->record_article( $id, 'b', 4, 84.0 );

		$winner = $this->engine->promote_winner( $id );

		$this->assertSame( 'b', $winner );

		$test = $this->engine->get_test( $id );
		$this->assertSame( 'b', $test['winner'] );
		$this->assertNotNull( $test['promoted_at'] );
	}

	public function test_promote_winner_elects_a_when_a_is_better(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		$this->engine->record_article( $id, 'a', 1, 90.0 );
		$this->engine->record_article( $id, 'a', 2, 88.0 );
		$this->engine->record_article( $id, 'b', 3, 70.0 );
		$this->engine->record_article( $id, 'b', 4, 72.0 );

		$this->assertSame( 'a', $this->engine->promote_winner( $id ) );
	}

	public function test_promote_winner_returns_null_with_insufficient_data(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		// Only one article per variant – below MIN_ARTICLES_PER_VARIANT (2).
		$this->engine->record_article( $id, 'a', 1, 80.0 );
		$this->engine->record_article( $id, 'b', 2, 70.0 );

		$this->assertNull( $this->engine->promote_winner( $id ) );
	}

	public function test_promote_winner_returns_existing_winner_if_already_promoted(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		$this->engine->record_article( $id, 'a', 1, 90.0 );
		$this->engine->record_article( $id, 'a', 2, 88.0 );
		$this->engine->record_article( $id, 'b', 3, 70.0 );
		$this->engine->record_article( $id, 'b', 4, 72.0 );

		$first  = $this->engine->promote_winner( $id );
		$second = $this->engine->promote_winner( $id );

		$this->assertSame( $first, $second );
	}

	public function test_promote_winner_returns_null_for_unknown_test(): void {
		$this->assertNull( $this->engine->promote_winner( 'ab_nope' ) );
	}

	// -----------------------------------------------------------------------
	// promote_mature_tests
	// -----------------------------------------------------------------------

	public function test_promote_mature_tests_promotes_old_tests(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		// Back-date created_at by 8 days.
		$tests         = $GLOBALS['_options'][ ABTestEngine::OPTION_KEY ];
		$tests[ $id ]['created_at'] -= 8 * DAY_IN_SECONDS;
		$GLOBALS['_options'][ ABTestEngine::OPTION_KEY ] = $tests;

		$this->engine->record_article( $id, 'a', 1, 80.0 );
		$this->engine->record_article( $id, 'a', 2, 82.0 );
		$this->engine->record_article( $id, 'b', 3, 70.0 );
		$this->engine->record_article( $id, 'b', 4, 72.0 );

		$results = $this->engine->promote_mature_tests();

		$this->assertArrayHasKey( $id, $results );
		$this->assertSame( 'a', $results[ $id ] );
	}

	public function test_promote_mature_tests_skips_young_tests(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		// Ensure enough data but test is only 1 day old.
		$this->engine->record_article( $id, 'a', 1, 80.0 );
		$this->engine->record_article( $id, 'a', 2, 82.0 );
		$this->engine->record_article( $id, 'b', 3, 70.0 );
		$this->engine->record_article( $id, 'b', 4, 72.0 );

		$results = $this->engine->promote_mature_tests();

		$this->assertArrayNotHasKey( $id, $results );
	}

	public function test_promote_mature_tests_skips_already_promoted(): void {
		$id = $this->engine->create_test( 'Topic', 'A', 'B' );

		// Enough data.
		$this->engine->record_article( $id, 'a', 1, 80.0 );
		$this->engine->record_article( $id, 'a', 2, 82.0 );
		$this->engine->record_article( $id, 'b', 3, 70.0 );
		$this->engine->record_article( $id, 'b', 4, 72.0 );

		// Back-date and manually promote first.
		$tests         = $GLOBALS['_options'][ ABTestEngine::OPTION_KEY ];
		$tests[ $id ]['created_at']  -= 8 * DAY_IN_SECONDS;
		$tests[ $id ]['winner']       = 'a';
		$tests[ $id ]['promoted_at']  = time();
		$GLOBALS['_options'][ ABTestEngine::OPTION_KEY ] = $tests;

		$results = $this->engine->promote_mature_tests();

		$this->assertArrayNotHasKey( $id, $results );
	}

	// -----------------------------------------------------------------------
	// get_winning_modifier
	// -----------------------------------------------------------------------

	public function test_get_winning_modifier_returns_correct_modifier(): void {
		$id = $this->engine->create_test( 'Topic', 'Mod-A', 'Mod-B' );

		$this->engine->record_article( $id, 'a', 1, 90.0 );
		$this->engine->record_article( $id, 'a', 2, 88.0 );
		$this->engine->record_article( $id, 'b', 3, 70.0 );
		$this->engine->record_article( $id, 'b', 4, 72.0 );
		$this->engine->promote_winner( $id );

		$this->assertSame( 'Mod-A', $this->engine->get_winning_modifier( $id ) );
	}

	public function test_get_winning_modifier_returns_null_before_promotion(): void {
		$id = $this->engine->create_test( 'Topic', 'Mod-A', 'Mod-B' );
		$this->assertNull( $this->engine->get_winning_modifier( $id ) );
	}

	// -----------------------------------------------------------------------
	// get_active_test_for_topic
	// -----------------------------------------------------------------------

	public function test_get_active_test_for_topic_returns_matching_test(): void {
		$id = $this->engine->create_test( 'Best Hiking Gear', 'A', 'B' );

		$found = $this->engine->get_active_test_for_topic( 'Best Hiking Gear' );

		$this->assertNotNull( $found );
		$this->assertSame( $id, $found['id'] );
	}

	public function test_get_active_test_for_topic_is_case_insensitive(): void {
		$id = $this->engine->create_test( 'Best Hiking Gear', 'A', 'B' );

		$found = $this->engine->get_active_test_for_topic( 'best hiking gear' );

		$this->assertNotNull( $found );
		$this->assertSame( $id, $found['id'] );
	}

	public function test_get_active_test_for_topic_skips_promoted_tests(): void {
		$id = $this->engine->create_test( 'Promoted Topic', 'A', 'B' );

		// Force promote.
		$this->engine->record_article( $id, 'a', 1, 90.0 );
		$this->engine->record_article( $id, 'a', 2, 88.0 );
		$this->engine->record_article( $id, 'b', 3, 70.0 );
		$this->engine->record_article( $id, 'b', 4, 72.0 );
		$this->engine->promote_winner( $id );

		$this->assertNull( $this->engine->get_active_test_for_topic( 'Promoted Topic' ) );
	}

	public function test_get_active_test_for_topic_returns_null_when_no_match(): void {
		$this->assertNull( $this->engine->get_active_test_for_topic( 'Nonexistent Topic' ) );
	}

	// -----------------------------------------------------------------------
	// apply_variant_modifier (filter callback)
	// -----------------------------------------------------------------------

	public function test_apply_variant_modifier_appends_modifier_to_prompt(): void {
		$id = $this->engine->create_test( 'AI Topic', 'Focus on beginners.', 'Focus on experts.' );

		$original = 'Write an article about AI Topic.';
		$modified = $this->engine->apply_variant_modifier( $original, 'AI Topic' );

		$this->assertStringContainsString( $original, $modified );
		$this->assertTrue(
			str_contains( $modified, 'Focus on beginners.' ) ||
			str_contains( $modified, 'Focus on experts.' )
		);
	}

	public function test_apply_variant_modifier_stores_transient(): void {
		$id = $this->engine->create_test( 'Transient Topic', 'MA', 'MB' );

		$this->engine->apply_variant_modifier( 'Original prompt.', 'Transient Topic' );

		$run = get_transient( 'pearblog_abtest_run_' . md5( 'Transient Topic' ) );
		$this->assertIsArray( $run );
		$this->assertSame( $id, $run['test_id'] );
		$this->assertContains( $run['variant'], [ 'a', 'b' ] );
	}

	public function test_apply_variant_modifier_returns_prompt_unchanged_when_no_test(): void {
		$prompt   = 'Write an article.';
		$modified = $this->engine->apply_variant_modifier( $prompt, 'No Test Topic' );

		$this->assertSame( $prompt, $modified );
	}

	// -----------------------------------------------------------------------
	// on_pipeline_completed (action callback)
	// -----------------------------------------------------------------------

	public function test_on_pipeline_completed_records_score(): void {
		$id = $this->engine->create_test( 'Pipeline Topic', 'A', 'B' );

		// Simulate the prompt filter storing the transient.
		set_transient( 'pearblog_abtest_run_' . md5( 'Pipeline Topic' ), [
			'test_id' => $id,
			'variant' => 'a',
		], HOUR_IN_SECONDS );

		// Seed quality score in post meta.
		$post_id = 42;
		$GLOBALS['_post_meta'][ $post_id ][ \PearBlogEngine\Content\QualityScorer::META_QUALITY_SCORE ] = [ 75.0 ];

		$this->engine->on_pipeline_completed( $post_id, 'Pipeline Topic' );

		$test = $this->engine->get_test( $id );
		$this->assertSame( 1, $test['variants']['a']['runs'] );
		$this->assertContains( 75.0, $test['variants']['a']['scores'] );
	}

	public function test_on_pipeline_completed_deletes_transient(): void {
		$id  = $this->engine->create_test( 'Clean Up Topic', 'A', 'B' );
		$key = 'pearblog_abtest_run_' . md5( 'Clean Up Topic' );

		set_transient( $key, [ 'test_id' => $id, 'variant' => 'b' ], HOUR_IN_SECONDS );
		$GLOBALS['_post_meta'][5][ \PearBlogEngine\Content\QualityScorer::META_QUALITY_SCORE ] = [ 65.0 ];

		$this->engine->on_pipeline_completed( 5, 'Clean Up Topic' );

		$this->assertFalse( get_transient( $key ) );
	}

	public function test_on_pipeline_completed_is_no_op_without_transient(): void {
		$id = $this->engine->create_test( 'No Transient', 'A', 'B' );

		// No transient set – should not throw or record anything.
		$this->engine->on_pipeline_completed( 99, 'No Transient' );

		$test = $this->engine->get_test( $id );
		$this->assertSame( 0, $test['variants']['a']['runs'] );
		$this->assertSame( 0, $test['variants']['b']['runs'] );
	}
}
