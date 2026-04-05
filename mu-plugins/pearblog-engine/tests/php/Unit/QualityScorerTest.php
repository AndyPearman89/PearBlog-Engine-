<?php
/**
 * Unit tests for QualityScorer.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\QualityScorer;

/**
 * Additional stub: get_the_title needs to work with a WP_Post object in context.
 */

class QualityScorerTest extends TestCase {

	private QualityScorer $scorer;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_posts']     = [];
		$GLOBALS['_post_meta'] = [];
		$this->scorer = new QualityScorer();
	}

	public function test_scores_post_and_stores_meta(): void {
		$post_id = 10;
		$this->seed_post( $post_id, $this->rich_content(), 'Great Article Title' );

		$scores = $this->scorer->score( $post_id );

		$this->assertArrayHasKey( 'composite', $scores );
		$this->assertGreaterThan( 0, $scores['composite'] );
		$this->assertLessThanOrEqual( 100, $scores['composite'] );

		// Should also have persisted to post meta.
		$stored = $GLOBALS['_post_meta'][ $post_id ][ QualityScorer::META_QUALITY_SCORE ][0] ?? null;
		$this->assertNotNull( $stored );
	}

	public function test_returns_zero_for_missing_post(): void {
		$scores = $this->scorer->score( 9999 );
		$this->assertSame( 0.0, $scores['composite'] );
	}

	public function test_readability_score_is_between_0_and_100(): void {
		$post_id = 11;
		$this->seed_post( $post_id, $this->rich_content(), 'Title' );
		$scores = $this->scorer->score( $post_id );

		$this->assertGreaterThanOrEqual( 0, $scores['readability'] );
		$this->assertLessThanOrEqual( 100, $scores['readability'] );
	}

	public function test_heading_score_rewards_h2_and_h3(): void {
		$post_id  = 12;
		$content  = "<h2>Section 1</h2><p>Content</p><h3>Subsection</h3><p>More content.</p>"
			. str_repeat( '<h2>Another Section</h2><p>Text.</p>', 3 );
		$this->seed_post( $post_id, $content, 'Heading Article' );

		$scores = $this->scorer->score( $post_id );
		$this->assertGreaterThanOrEqual( 80, $scores['heading_score'] );
	}

	public function test_short_content_gets_low_word_count(): void {
		$post_id = 13;
		$this->seed_post( $post_id, 'Short content here.', 'Short' );
		$scores = $this->scorer->score( $post_id );

		$this->assertLessThan( 10, $scores['word_count'] );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function rich_content(): string {
		return "<h2>Introduction</h2>\n" .
			str_repeat( "<p>This is a sentence about travel and destinations. Visitors enjoy the scenery. Hotels are affordable.</p>\n", 30 ) .
			"<h2>Main Points</h2>\n" .
			str_repeat( "<p>Another paragraph with useful travel tips and local recommendations for tourists.</p>\n", 20 ) .
			"<h3>Subsection</h3>\n" .
			str_repeat( "<p>Detail content with relevant information about the topic under discussion.</p>\n", 10 ) .
			"<h2>Conclusion</h2>\n" .
			str_repeat( "<p>Final thoughts on the subject with helpful conclusions for readers.</p>\n", 10 );
	}

	private function seed_post( int $id, string $content, string $title = 'Test Post' ): void {
		$post               = new \WP_Post();
		$post->ID           = $id;
		$post->post_title   = $title;
		$post->post_content = $content;
		$post->post_status  = 'publish';
		$GLOBALS['_posts'][ $id ] = $post;
	}
}
