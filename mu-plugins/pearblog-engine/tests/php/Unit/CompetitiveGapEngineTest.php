<?php
/**
 * Unit tests for CompetitiveGapEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\CompetitiveGapEngine;

class CompetitiveGapEngineTest extends TestCase {

	private CompetitiveGapEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_list'] = [];
		$GLOBALS['_posts']     = [];
		$this->engine = new CompetitiveGapEngine();
	}

	// -----------------------------------------------------------------------
	// Competitor topic management
	// -----------------------------------------------------------------------

	public function test_set_and_get_competitor_topics(): void {
		$this->engine->set_competitor_topics( [ 'SEO tips', 'Link building guide', 'Content marketing' ] );
		$topics = $this->engine->get_competitor_topics();

		$this->assertCount( 3, $topics );
		$this->assertContains( 'SEO tips', $topics );
	}

	public function test_get_competitor_topics_empty_by_default(): void {
		$this->assertSame( [], $this->engine->get_competitor_topics() );
	}

	public function test_set_topics_replaces_previous(): void {
		$this->engine->set_competitor_topics( [ 'Old Topic A', 'Old Topic B' ] );
		$this->engine->set_competitor_topics( [ 'New Topic' ] );
		$this->assertSame( [ 'New Topic' ], $this->engine->get_competitor_topics() );
	}

	public function test_add_competitor_topics_appends(): void {
		$this->engine->set_competitor_topics( [ 'Topic A' ] );
		$this->engine->add_competitor_topics( [ 'Topic B', 'Topic C' ] );
		$topics = $this->engine->get_competitor_topics();
		$this->assertCount( 3, $topics );
	}

	public function test_add_competitor_topics_deduplicates(): void {
		$this->engine->set_competitor_topics( [ 'Topic A' ] );
		$this->engine->add_competitor_topics( [ 'Topic A', 'Topic B' ] );
		$topics = $this->engine->get_competitor_topics();
		$this->assertCount( 2, $topics );
	}

	// -----------------------------------------------------------------------
	// tokenise
	// -----------------------------------------------------------------------

	public function test_tokenise_splits_into_lowercase_words(): void {
		$tokens = $this->engine->tokenise( 'Best SEO Tips for Beginners' );
		$this->assertArrayHasKey( 'best', $tokens );
		$this->assertArrayHasKey( 'tips', $tokens );
		$this->assertArrayHasKey( 'for', $tokens );
		$this->assertArrayHasKey( 'beginners', $tokens );
	}

	public function test_tokenise_skips_short_words(): void {
		$tokens = $this->engine->tokenise( 'Go to top' );
		$this->assertArrayNotHasKey( 'go', $tokens );
		$this->assertArrayNotHasKey( 'to', $tokens );
		$this->assertArrayHasKey( 'top', $tokens );
	}

	public function test_tokenise_empty_string(): void {
		$this->assertSame( [], $this->engine->tokenise( '' ) );
	}

	// -----------------------------------------------------------------------
	// jaccard_similarity
	// -----------------------------------------------------------------------

	public function test_jaccard_identical_sets(): void {
		$a = [ 'seo' => true, 'tips' => true ];
		$this->assertSame( 1.0, $this->engine->jaccard_similarity( $a, $a ) );
	}

	public function test_jaccard_disjoint_sets(): void {
		$a = [ 'seo' => true ];
		$b = [ 'food' => true ];
		$this->assertSame( 0.0, $this->engine->jaccard_similarity( $a, $b ) );
	}

	public function test_jaccard_partial_overlap(): void {
		$a   = [ 'seo' => true, 'tips' => true ];
		$b   = [ 'seo' => true, 'guide' => true ];
		$sim = $this->engine->jaccard_similarity( $a, $b );
		// |A∩B|=1, |A∪B|=3, similarity=1/3.
		$this->assertEqualsWithDelta( 1 / 3, $sim, 0.01 );
	}

	public function test_jaccard_both_empty(): void {
		$this->assertSame( 1.0, $this->engine->jaccard_similarity( [], [] ) );
	}

	// -----------------------------------------------------------------------
	// get_gap_topics — explicit inputs
	// -----------------------------------------------------------------------

	public function test_gap_when_nothing_published(): void {
		$competitor = [ 'Best Hiking Gear', 'Mountain Trail Guide' ];
		$published  = [];

		$gap = $this->engine->get_gap_topics( $competitor, $published );
		$this->assertCount( 2, $gap );
	}

	public function test_covered_topic_excluded_from_gap(): void {
		// Threshold default is 0.5; tokens "best hiking gear" and "best hiking gear review"
		// have high overlap, so should be considered covered.
		$competitor = [ 'Best Hiking Gear' ];
		$published  = [ 'Best Hiking Gear Review 2026' ];

		// |A|=3 tokens (best,hiking,gear), |B|=4 tokens (best,hiking,gear,review)
		// |A∩B|=3, |A∪B|=4 → similarity=0.75 > threshold 0.5 → covered.
		$gap = $this->engine->get_gap_topics( $competitor, $published );
		$this->assertNotContains( 'Best Hiking Gear', $gap );
	}

	public function test_dissimilar_topic_included_in_gap(): void {
		$competitor = [ 'Cryptocurrency Investing Guide' ];
		$published  = [ 'Best Hiking Gear Review' ];

		$gap = $this->engine->get_gap_topics( $competitor, $published );
		$this->assertContains( 'Cryptocurrency Investing Guide', $gap );
	}

	public function test_gap_sorted_lowest_similarity_first(): void {
		$competitor = [
			'Very Similar Title', // Will be similar to published title.
			'Totally Different Topic About Rockets',
		];
		$published = [ 'Very Similar Title Guide 2026' ];

		$gap = $this->engine->get_gap_topics( $competitor, $published );
		// "Totally Different Topic" should appear first (lowest similarity).
		if ( count( $gap ) > 1 ) {
			$this->assertStringContainsString( 'Totally Different', $gap[0] );
		}
		$this->assertTrue( count( $gap ) >= 1 );
	}

	public function test_custom_similarity_threshold(): void {
		update_option( CompetitiveGapEngine::OPTION_SIMILARITY_THRESH, 0.9 );

		// With a 0.9 threshold, even somewhat similar topics won't be considered covered.
		$competitor = [ 'SEO Guide for Beginners' ];
		$published  = [ 'SEO Tips for Beginners Blog' ];

		$gap = $this->engine->get_gap_topics( $competitor, $published );
		// Because threshold is very high, the topic should appear in the gap.
		$this->assertContains( 'SEO Guide for Beginners', $gap );
	}

	// -----------------------------------------------------------------------
	// get_top_gap_topics / max_inject
	// -----------------------------------------------------------------------

	public function test_get_top_gap_topics_respects_max(): void {
		update_option( CompetitiveGapEngine::OPTION_MAX_INJECT, 2 );

		$competitor = [
			'Topic Alpha', 'Topic Beta', 'Topic Gamma', 'Topic Delta',
		];

		$gap = $this->engine->get_top_gap_topics();
		// With no published posts we should get all gaps, but max 2 returned.
		$this->engine->set_competitor_topics( $competitor );
		$top = $this->engine->get_top_gap_topics();
		$this->assertLessThanOrEqual( 2, count( $top ) );
	}

	// -----------------------------------------------------------------------
	// enrich_prompt
	// -----------------------------------------------------------------------

	public function test_enrich_prompt_returns_unchanged_when_no_gaps(): void {
		// No competitor topics → no gaps → prompt unchanged.
		$prompt = 'Write an article about travel.';
		$this->assertSame( $prompt, $this->engine->enrich_prompt( $prompt ) );
	}

	public function test_enrich_prompt_appends_gap_block(): void {
		$this->engine->set_competitor_topics( [ 'Quantum Computing for Beginners' ] );
		$result = $this->engine->enrich_prompt( 'Write about technology.' );

		$this->assertStringContainsString( 'Competitive Content Gaps', $result );
		$this->assertStringContainsString( 'Quantum Computing for Beginners', $result );
	}

	public function test_enrich_prompt_original_text_preserved(): void {
		$this->engine->set_competitor_topics( [ 'AI in Healthcare 2026' ] );
		$original = 'Detailed prompt text here.';
		$result   = $this->engine->enrich_prompt( $original );

		$this->assertStringStartsWith( $original, $result );
	}
}
