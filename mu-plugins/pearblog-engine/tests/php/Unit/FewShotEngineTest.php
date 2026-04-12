<?php
/**
 * Unit tests for FewShotEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\FewShotEngine;
use PearBlogEngine\Content\QualityScorer;

class FewShotEngineTest extends TestCase {

	private FewShotEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_posts']      = [];
		$GLOBALS['_post_list']  = [];
		$this->engine = new FewShotEngine();
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_enabled_by_default(): void {
		$this->assertTrue( $this->engine->is_enabled() );
	}

	public function test_disabled_when_option_false(): void {
		update_option( FewShotEngine::OPTION_ENABLED, false );
		$this->assertFalse( $this->engine->is_enabled() );
	}

	public function test_disabled_when_option_string_zero(): void {
		update_option( FewShotEngine::OPTION_ENABLED, '0' );
		$this->assertFalse( $this->engine->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// get_examples
	// -----------------------------------------------------------------------

	public function test_get_examples_returns_empty_when_no_posts(): void {
		$this->assertSame( [], $this->engine->get_examples() );
	}

	public function test_get_examples_returns_posts_with_sufficient_score(): void {
		$post = new \WP_Post( [
			'ID'           => 101,
			'post_title'   => 'Amazing Travel Guide',
			'post_content' => 'This is a wonderful article about travelling the world with great tips and advice.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][101]    = $post;
		$GLOBALS['_post_list']     = [101];
		$GLOBALS['_post_meta'][101][QualityScorer::META_QUALITY_SCORE] = [80.0];

		$examples = $this->engine->get_examples();
		$this->assertCount( 1, $examples );
		$this->assertSame( 101, $examples[0]['post_id'] );
		$this->assertSame( 'Amazing Travel Guide', $examples[0]['title'] );
		$this->assertSame( 80.0, $examples[0]['score'] );
		$this->assertNotEmpty( $examples[0]['excerpt'] );
	}

	public function test_get_examples_respects_max_posts(): void {
		update_option( FewShotEngine::OPTION_MAX_POSTS, 2 );

		for ( $i = 1; $i <= 5; $i++ ) {
			$post = new \WP_Post( [
				'ID'           => $i,
				'post_title'   => "Post {$i}",
				'post_content' => str_repeat( "Word{$i} ", 100 ),
				'post_status'  => 'publish',
			] );
			$GLOBALS['_posts'][ $i ] = $post;
			$GLOBALS['_post_list'][] = $i;
			$GLOBALS['_post_meta'][ $i ][ QualityScorer::META_QUALITY_SCORE ] = [ 75.0 ];
		}

		$examples = $this->engine->get_examples();
		$this->assertLessThanOrEqual( 2, count( $examples ) );
	}

	public function test_excerpt_is_truncated_to_configured_length(): void {
		update_option( FewShotEngine::OPTION_EXCERPT_LEN, 50 );

		$post = new \WP_Post( [
			'ID'           => 200,
			'post_title'   => 'Long Article',
			'post_content' => str_repeat( 'x ', 500 ),
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][200]    = $post;
		$GLOBALS['_post_list']     = [200];
		$GLOBALS['_post_meta'][200][QualityScorer::META_QUALITY_SCORE] = [80.0];

		$examples = $this->engine->get_examples();
		// Excerpt should not exceed configured length + 1 (ellipsis char).
		$this->assertLessThanOrEqual( 51, mb_strlen( $examples[0]['excerpt'] ) );
	}

	// -----------------------------------------------------------------------
	// enrich_prompt
	// -----------------------------------------------------------------------

	public function test_enrich_prompt_returns_unchanged_when_disabled(): void {
		update_option( FewShotEngine::OPTION_ENABLED, false );
		$prompt = 'Write an article.';
		$this->assertSame( $prompt, $this->engine->enrich_prompt( $prompt ) );
	}

	public function test_enrich_prompt_returns_unchanged_when_no_examples(): void {
		$prompt = 'Write an article.';
		$this->assertSame( $prompt, $this->engine->enrich_prompt( $prompt ) );
	}

	public function test_enrich_prompt_appends_example_block(): void {
		$post = new \WP_Post( [
			'ID'           => 300,
			'post_title'   => 'Great Guide',
			'post_content' => 'This is excellent content that covers everything.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][300]    = $post;
		$GLOBALS['_post_list']     = [300];
		$GLOBALS['_post_meta'][300][QualityScorer::META_QUALITY_SCORE] = [85.0];

		$prompt  = 'Write an article about travel.';
		$result  = $this->engine->enrich_prompt( $prompt );

		$this->assertStringContainsString( $prompt, $result );
		$this->assertStringContainsString( 'Writing Style Examples', $result );
		$this->assertStringContainsString( 'Great Guide', $result );
	}

	public function test_enrich_prompt_includes_score_in_block(): void {
		$post = new \WP_Post( [
			'ID'           => 301,
			'post_title'   => 'SEO Guide',
			'post_content' => 'Helpful content about search engine optimisation strategies.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][301]    = $post;
		$GLOBALS['_post_list']     = [301];
		$GLOBALS['_post_meta'][301][QualityScorer::META_QUALITY_SCORE] = [90.5];

		$result = $this->engine->enrich_prompt( 'Write about SEO.' );
		$this->assertStringContainsString( '90.5', $result );
	}
}
