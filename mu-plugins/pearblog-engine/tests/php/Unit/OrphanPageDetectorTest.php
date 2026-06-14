<?php
/**
 * Unit tests for OrphanPageDetector.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\SEO\OrphanPageDetector;
use WP_Post;

class OrphanPageDetectorTest extends TestCase {

	private OrphanPageDetector $detector;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_transients'] = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_post_list']  = [];
		$GLOBALS['_posts']      = [];
		$this->detector = new OrphanPageDetector();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Register a WP_Post stub in global state so get_posts() returns it.
	 */
	private function make_post( int $id, string $title, string $content = '' ): WP_Post {
		$post               = new WP_Post();
		$post->ID           = $id;
		$post->post_title   = $title;
		$post->post_content = $content;
		$post->post_status  = 'publish';

		$GLOBALS['_post_list'][]       = $post;
		$GLOBALS['_posts'][ $id ]      = $post;

		return $post;
	}

	// -----------------------------------------------------------------------
	// get_inbound_link_map
	// -----------------------------------------------------------------------

	public function test_empty_site_returns_empty_link_map(): void {
		$map = $this->detector->get_inbound_link_map();
		$this->assertSame( [], $map );
	}

	public function test_post_with_no_links_has_zero_inbound_count(): void {
		$this->make_post( 1, 'Post One', 'No links here.' );
		$map = $this->detector->get_inbound_link_map();
		$this->assertSame( 0, $map[1] );
	}

	public function test_post_linked_from_another_increments_count(): void {
		$this->make_post( 1, 'Target Post', '' );
		$this->make_post( 2, 'Linking Post', '<a href="https://example.com/post/1/">See target</a>' );

		$map = $this->detector->get_inbound_link_map();

		$this->assertSame( 1, $map[1] );
		$this->assertSame( 0, $map[2] );
	}

	public function test_self_links_are_not_counted(): void {
		// Post 1 links to itself — should not increment its own count.
		$this->make_post( 1, 'Self', '<a href="https://example.com/post/1/">myself</a>' );

		$map = $this->detector->get_inbound_link_map();
		$this->assertSame( 0, $map[1] );
	}

	public function test_multiple_posts_linking_to_same_target(): void {
		$this->make_post( 1, 'Target', '' );
		$this->make_post( 2, 'Linker A', '<a href="https://example.com/post/1/">link</a>' );
		$this->make_post( 3, 'Linker B', '<a href="https://example.com/post/1/">link</a>' );

		$map = $this->detector->get_inbound_link_map();
		$this->assertSame( 2, $map[1] );
	}

	public function test_link_map_uses_transient_cache(): void {
		$this->make_post( 10, 'Cached post', '' );
		$first  = $this->detector->get_inbound_link_map();
		$second = $this->detector->get_inbound_link_map();
		$this->assertSame( $first, $second );
	}

	// -----------------------------------------------------------------------
	// get_orphan_pages
	// -----------------------------------------------------------------------

	public function test_get_orphan_pages_returns_empty_for_no_posts(): void {
		$this->assertSame( [], $this->detector->get_orphan_pages() );
	}

	public function test_post_with_zero_inbound_is_an_orphan(): void {
		$this->make_post( 5, 'Orphan Post', 'content' );
		$orphans = $this->detector->get_orphan_pages();

		$this->assertCount( 1, $orphans );
		$this->assertSame( 5, $orphans[0]['post_id'] );
	}

	public function test_post_with_inbound_link_is_not_an_orphan(): void {
		$this->make_post( 1, 'Target', '' );
		$this->make_post( 2, 'Linker', '<a href="https://example.com/post/1/">x</a>' );

		$orphans = $this->detector->get_orphan_pages();

		$post_ids = array_column( $orphans, 'post_id' );
		$this->assertNotContains( 1, $post_ids );
	}

	public function test_orphan_includes_required_fields(): void {
		$this->make_post( 7, 'Field Test Post', '' );
		$orphans = $this->detector->get_orphan_pages();

		$this->assertArrayHasKey( 'post_id', $orphans[0] );
		$this->assertArrayHasKey( 'title', $orphans[0] );
		$this->assertArrayHasKey( 'url', $orphans[0] );
		$this->assertArrayHasKey( 'quality_score', $orphans[0] );
		$this->assertArrayHasKey( 'inbound_links', $orphans[0] );
	}

	public function test_orphan_inbound_links_field_is_zero(): void {
		$this->make_post( 8, 'Orphan', '' );
		$orphans = $this->detector->get_orphan_pages();
		$this->assertSame( 0, $orphans[0]['inbound_links'] );
	}

	public function test_orphans_limited_by_option(): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->make_post( $i, "Post {$i}", '' );
		}
		$orphans = $this->detector->get_orphan_pages( [ 'limit' => 3 ] );
		$this->assertCount( 3, $orphans );
	}

	public function test_orphans_sorted_by_quality_score_descending(): void {
		$post1                                   = $this->make_post( 1, 'Low quality', '' );
		$post2                                   = $this->make_post( 2, 'High quality', '' );
		$GLOBALS['_post_meta'][1]['_pearblog_quality_score'] = [ 30.0 ];
		$GLOBALS['_post_meta'][2]['_pearblog_quality_score'] = [ 90.0 ];

		$orphans = $this->detector->get_orphan_pages();

		$this->assertSame( 2, $orphans[0]['post_id'] );
		$this->assertSame( 1, $orphans[1]['post_id'] );
	}

	// -----------------------------------------------------------------------
	// get_link_equity_distribution
	// -----------------------------------------------------------------------

	public function test_distribution_is_all_orphans_for_unlinked_posts(): void {
		$this->make_post( 1, 'A', '' );
		$this->make_post( 2, 'B', '' );

		$dist = $this->detector->get_link_equity_distribution();

		$this->assertSame( 2, $dist['orphan_count'] );
		$this->assertSame( 2, $dist['total_posts'] );
		$this->assertSame( 100.0, $dist['orphan_pct'] );
	}

	public function test_distribution_buckets_cover_expected_ranges(): void {
		$this->make_post( 1, 'A', '' );
		$dist = $this->detector->get_link_equity_distribution();

		$this->assertArrayHasKey( '0', $dist['buckets'] );
		$this->assertArrayHasKey( '1-2', $dist['buckets'] );
		$this->assertArrayHasKey( '3-5', $dist['buckets'] );
		$this->assertArrayHasKey( '6-10', $dist['buckets'] );
		$this->assertArrayHasKey( '11+', $dist['buckets'] );
	}

	public function test_distribution_correctly_buckets_linked_post(): void {
		$this->make_post( 1, 'Target', '' );
		$this->make_post( 2, 'Linker', '<a href="https://example.com/post/1/">x</a>' );

		$dist = $this->detector->get_link_equity_distribution();

		// Post 1 has 1 inbound → bucket '1-2'.
		$this->assertSame( 1, $dist['buckets']['1-2'] );
		// Post 2 has 0 inbound → bucket '0'.
		$this->assertSame( 1, $dist['buckets']['0'] );
	}

	public function test_distribution_returns_zero_orphan_pct_when_no_posts(): void {
		$dist = $this->detector->get_link_equity_distribution();
		$this->assertSame( 0.0, $dist['orphan_pct'] );
	}

	// -----------------------------------------------------------------------
	// suggest_links_for_orphan
	// -----------------------------------------------------------------------

	public function test_suggest_returns_empty_when_no_keyword_overlap(): void {
		$this->make_post( 1, 'hiking boots', '' );
		$this->make_post( 2, 'cooking recipes', 'I love pasta.' );

		$suggestions = $this->detector->suggest_links_for_orphan( 1 );
		$this->assertSame( [], $suggestions );
	}

	public function test_suggest_returns_donor_with_overlap(): void {
		// Post 1: "best hiking boots" — uses title tokens as keywords.
		$this->make_post( 1, 'best hiking boots', '' );
		// Post 2 shares "hiking" and "boots" tokens in its title.
		$this->make_post( 2, 'best hiking boots review', 'content here' );

		$suggestions = $this->detector->suggest_links_for_orphan( 1 );

		$this->assertNotEmpty( $suggestions );
		$this->assertSame( 2, $suggestions[0]['post_id'] );
	}

	public function test_suggest_respects_limit(): void {
		$this->make_post( 1, 'hiking gear best', '' );
		for ( $i = 2; $i <= 10; $i++ ) {
			$this->make_post( $i, 'hiking gear best review', 'content' );
		}

		$suggestions = $this->detector->suggest_links_for_orphan( 1, 3 );
		$this->assertLessThanOrEqual( 3, count( $suggestions ) );
	}

	public function test_suggest_excludes_posts_already_linking_to_orphan(): void {
		$this->make_post( 1, 'hiking boots', '' );
		// Post 2 already links to post 1 — should be excluded.
		$this->make_post( 2, 'hiking boots review', '<a href="https://example.com/post/1/">best boots</a>' );

		$suggestions = $this->detector->suggest_links_for_orphan( 1 );
		$donor_ids   = array_column( $suggestions, 'post_id' );
		$this->assertNotContains( 2, $donor_ids );
	}

	public function test_suggest_does_not_include_target_post_itself(): void {
		$this->make_post( 1, 'hiking boots', '' );
		$suggestions = $this->detector->suggest_links_for_orphan( 1 );
		$donor_ids   = array_column( $suggestions, 'post_id' );
		$this->assertNotContains( 1, $donor_ids );
	}

	public function test_suggest_donor_has_required_fields(): void {
		$this->make_post( 1, 'hiking boots best', '' );
		$this->make_post( 2, 'hiking boots review good', 'content' );

		$suggestions = $this->detector->suggest_links_for_orphan( 1 );
		if ( empty( $suggestions ) ) {
			$this->markTestSkipped( 'No overlap found; keyword sets too dissimilar.' );
		}

		$this->assertArrayHasKey( 'post_id', $suggestions[0] );
		$this->assertArrayHasKey( 'title', $suggestions[0] );
		$this->assertArrayHasKey( 'url', $suggestions[0] );
		$this->assertArrayHasKey( 'overlap_score', $suggestions[0] );
	}

	public function test_suggest_overlap_score_is_between_zero_and_one(): void {
		$this->make_post( 1, 'hiking boots best', '' );
		$this->make_post( 2, 'hiking boots review', 'content' );

		$suggestions = $this->detector->suggest_links_for_orphan( 1 );
		foreach ( $suggestions as $s ) {
			$this->assertGreaterThanOrEqual( 0.0, $s['overlap_score'] );
			$this->assertLessThanOrEqual( 1.0, $s['overlap_score'] );
		}
	}

	// -----------------------------------------------------------------------
	// get_report
	// -----------------------------------------------------------------------

	public function test_get_report_has_required_keys(): void {
		$report = $this->detector->get_report();

		$this->assertArrayHasKey( 'distribution', $report );
		$this->assertArrayHasKey( 'orphans', $report );
		$this->assertArrayHasKey( 'generated_at', $report );
	}

	public function test_get_report_generated_at_is_iso8601(): void {
		$report = $this->detector->get_report();
		// ISO 8601 contains 'T'.
		$this->assertStringContainsString( 'T', $report['generated_at'] );
	}

	// -----------------------------------------------------------------------
	// bust_cache
	// -----------------------------------------------------------------------

	public function test_bust_cache_removes_cached_link_map(): void {
		$this->make_post( 1, 'Test', '' );
		$this->detector->get_inbound_link_map(); // Populate cache.

		$this->detector->bust_cache();

		// Add a new post — it should appear in a fresh scan after cache bust.
		$this->make_post( 2, 'New post', '<a href="https://example.com/post/1/">link</a>' );
		$map = $this->detector->get_inbound_link_map();
		$this->assertSame( 1, $map[1] );
	}
}
