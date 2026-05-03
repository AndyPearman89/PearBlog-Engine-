<?php
/**
 * Tests for PromptOptimizer.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\AI\PromptOptimizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\AI\PromptOptimizer
 */
class PromptOptimizerTest extends TestCase {

	/** @var PromptOptimizer */
	private PromptOptimizer $optimizer;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->optimizer = new PromptOptimizer();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options'] = [];
		unset( $GLOBALS['_actions'] );
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_constants_are_defined(): void {
		$this->assertSame( 'pearblog_prompt_optimizer_data', PromptOptimizer::OPTION_DATA );
		$this->assertSame( 500, PromptOptimizer::MAX_DATA_POINTS );
		$this->assertSame( 10, PromptOptimizer::MIN_SAMPLES_REQUIRED );
		$this->assertIsArray( PromptOptimizer::MODIFIERS );
		$this->assertNotEmpty( PromptOptimizer::MODIFIERS );
	}

	public function test_all_modifier_keys_have_text(): void {
		foreach ( PromptOptimizer::MODIFIERS as $key => $text ) {
			$this->assertIsString( $key );
			$this->assertIsString( $text );
			$this->assertNotEmpty( $text );
		}
	}

	// -----------------------------------------------------------------------
	// get_data / reset
	// -----------------------------------------------------------------------

	public function test_get_data_returns_empty_array_initially(): void {
		$data = $this->optimizer->get_data();
		$this->assertSame( [], $data );
	}

	public function test_reset_clears_stored_data(): void {
		$this->optimizer->record( 1, 'detailed', 80 );
		$this->assertNotEmpty( $this->optimizer->get_data() );

		$this->optimizer->reset();
		$this->assertSame( [], $this->optimizer->get_data() );
	}

	// -----------------------------------------------------------------------
	// record
	// -----------------------------------------------------------------------

	public function test_record_appends_entry(): void {
		$this->optimizer->record( 42, 'detailed', 75 );
		$data = $this->optimizer->get_data();

		$this->assertCount( 1, $data );
		$this->assertSame( 42, $data[0]['post_id'] );
		$this->assertSame( 'detailed', $data[0]['modifier'] );
		$this->assertSame( 75, $data[0]['score'] );
		$this->assertIsInt( $data[0]['recorded_at'] );
	}

	public function test_record_multiple_entries(): void {
		$this->optimizer->record( 1, 'detailed', 70 );
		$this->optimizer->record( 2, 'conversational', 85 );
		$this->optimizer->record( 3, 'listicle', 60 );

		$this->assertCount( 3, $this->optimizer->get_data() );
	}

	public function test_record_ring_buffer_limits_entries(): void {
		// Record MAX_DATA_POINTS + 10 entries.
		for ( $i = 0; $i <= PromptOptimizer::MAX_DATA_POINTS + 9; $i++ ) {
			$this->optimizer->record( $i, 'detailed', 70 );
		}

		$data = $this->optimizer->get_data();
		$this->assertCount( PromptOptimizer::MAX_DATA_POINTS, $data );
	}

	public function test_ring_buffer_retains_newest_entries(): void {
		for ( $i = 0; $i < PromptOptimizer::MAX_DATA_POINTS + 5; $i++ ) {
			$this->optimizer->record( $i + 1000, 'detailed', $i );
		}

		$data  = $this->optimizer->get_data();
		$first = reset( $data );

		// The oldest entry should have been trimmed; first score should be 5.
		$this->assertSame( 5, $first['score'] );
	}

	// -----------------------------------------------------------------------
	// get_modifier_stats
	// -----------------------------------------------------------------------

	public function test_get_modifier_stats_returns_all_keys(): void {
		$stats = $this->optimizer->get_modifier_stats();

		foreach ( array_keys( PromptOptimizer::MODIFIERS ) as $key ) {
			$this->assertArrayHasKey( $key, $stats );
			$this->assertArrayHasKey( 'count', $stats[ $key ] );
			$this->assertArrayHasKey( 'mean', $stats[ $key ] );
			$this->assertArrayHasKey( 'min', $stats[ $key ] );
			$this->assertArrayHasKey( 'max', $stats[ $key ] );
		}
	}

	public function test_stats_are_zero_when_no_data(): void {
		$stats = $this->optimizer->get_modifier_stats();
		foreach ( $stats as $key => $stat ) {
			$this->assertSame( 0, $stat['count'] );
			$this->assertSame( 0.0, $stat['mean'] );
		}
	}

	public function test_stats_calculate_mean_correctly(): void {
		$this->optimizer->record( 1, 'detailed', 70 );
		$this->optimizer->record( 2, 'detailed', 80 );
		$this->optimizer->record( 3, 'detailed', 90 );

		$stats = $this->optimizer->get_modifier_stats();
		$this->assertSame( 3, $stats['detailed']['count'] );
		$this->assertSame( 80.0, $stats['detailed']['mean'] );
		$this->assertSame( 70, $stats['detailed']['min'] );
		$this->assertSame( 90, $stats['detailed']['max'] );
	}

	public function test_stats_separate_modifier_buckets(): void {
		$this->optimizer->record( 1, 'detailed', 80 );
		$this->optimizer->record( 2, 'conversational', 60 );

		$stats = $this->optimizer->get_modifier_stats();
		$this->assertSame( 1, $stats['detailed']['count'] );
		$this->assertSame( 1, $stats['conversational']['count'] );
		$this->assertSame( 0, $stats['listicle']['count'] );
	}

	// -----------------------------------------------------------------------
	// get_best_modifier
	// -----------------------------------------------------------------------

	public function test_get_best_modifier_returns_detailed_by_default(): void {
		$best = $this->optimizer->get_best_modifier();
		$this->assertSame( 'detailed', $best );
	}

	public function test_get_best_modifier_requires_min_samples(): void {
		// Record fewer than MIN_SAMPLES_REQUIRED for each modifier.
		for ( $i = 0; $i < PromptOptimizer::MIN_SAMPLES_REQUIRED - 1; $i++ ) {
			$this->optimizer->record( $i, 'conversational', 99 );
		}

		// Still not enough – should fall back to 'detailed'.
		$best = $this->optimizer->get_best_modifier();
		$this->assertSame( 'detailed', $best );
	}

	public function test_get_best_modifier_picks_highest_mean(): void {
		// Give 'listicle' enough samples with high scores.
		for ( $i = 0; $i < PromptOptimizer::MIN_SAMPLES_REQUIRED; $i++ ) {
			$this->optimizer->record( $i, 'listicle', 95 );
		}

		$best = $this->optimizer->get_best_modifier();
		$this->assertSame( 'listicle', $best );
	}

	// -----------------------------------------------------------------------
	// get_best_modifier_text
	// -----------------------------------------------------------------------

	public function test_get_best_modifier_text_returns_string(): void {
		$text = $this->optimizer->get_best_modifier_text();
		$this->assertIsString( $text );
		$this->assertNotEmpty( $text );
	}

	public function test_get_best_modifier_text_matches_modifier(): void {
		for ( $i = 0; $i < PromptOptimizer::MIN_SAMPLES_REQUIRED; $i++ ) {
			$this->optimizer->record( $i, 'expert', 90 );
		}
		$best_key  = $this->optimizer->get_best_modifier();
		$best_text = $this->optimizer->get_best_modifier_text();
		$this->assertSame( PromptOptimizer::MODIFIERS[ $best_key ], $best_text );
	}

	// -----------------------------------------------------------------------
	// select_modifier
	// -----------------------------------------------------------------------

	public function test_select_modifier_returns_valid_key(): void {
		$key = $this->optimizer->select_modifier();
		$this->assertArrayHasKey( $key, PromptOptimizer::MODIFIERS );
	}

	public function test_select_modifier_returns_string(): void {
		$this->assertIsString( $this->optimizer->select_modifier() );
	}

	// -----------------------------------------------------------------------
	// register / on_quality_scored
	// -----------------------------------------------------------------------

	public function test_register_adds_hook(): void {
		$this->optimizer->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['pearblog_quality_scored'] ) );
	}

	public function test_on_quality_scored_ignores_missing_meta(): void {
		$GLOBALS['_post_meta'] = [];
		$this->optimizer->on_quality_scored( 99, 80 );
		$this->assertSame( [], $this->optimizer->get_data() );
	}

	public function test_on_quality_scored_records_with_meta(): void {
		$GLOBALS['_post_meta'] = [ 99 => [ 'pearblog_prompt_modifier' => ['listicle'] ] ];
		$this->optimizer->on_quality_scored( 99, 85 );
		$data = $this->optimizer->get_data();
		$this->assertCount( 1, $data );
		$this->assertSame( 'listicle', $data[0]['modifier'] );
		$this->assertSame( 85, $data[0]['score'] );
	}
}
