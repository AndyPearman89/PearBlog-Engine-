<?php
/**
 * Unit tests for OrphanPageDetector.
 *
 * The bootstrap stubs provide:
 *   - get_posts()    → reads $GLOBALS['_post_list']
 *   - get_post()     → reads $GLOBALS['_posts'][id]
 *   - get_post_field() → reads property from get_post()
 *   - get_the_title() → reads post_title via get_post()
 *   - get_permalink() → 'https://example.com/post/{id}/'
 *   - get_post_meta() / update_post_meta() / delete_post_meta()
 *   - get_option() / update_option() / delete_option()
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\SEO\OrphanPageDetector;

class OrphanPageDetectorTest extends TestCase {

	private OrphanPageDetector $detector;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_options']   = [];
		$GLOBALS['_posts']     = [];
		$GLOBALS['_post_list'] = [];
		$this->detector        = new OrphanPageDetector();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Register a published post in the bootstrap stubs.
	 *
	 * @param  int    $id      Post ID.
	 * @param  string $title   Post title.
	 * @param  string $content Post content (HTML allowed).
	 * @param  string $type    Post type.
	 */
	private function make_post( int $id, string $title, string $content = '', string $type = 'post' ): void {
		$GLOBALS['_posts'][ $id ] = (object) [
			'ID'            => $id,
			'post_title'    => $title,
			'post_content'  => $content,
			'post_type'     => $type,
			'post_status'   => 'publish',
			'post_date_gmt' => '2026-01-01 00:00:00',
		];
		$GLOBALS['_post_list'][] = $id;
	}

	/**
	 * Return the permalink produced by the bootstrap stub for a given ID.
	 */
	private function permalink( int $id ): string {
		return 'https://example.com/post/' . $id . '/';
	}

	// -----------------------------------------------------------------------
	// scan() — basic detection
	// -----------------------------------------------------------------------

	public function test_no_posts_returns_empty_orphan_list(): void {
		$result = $this->detector->scan( true );

		$this->assertSame( [], $result['orphans'] );
		$this->assertSame( 0, $result['total_scanned'] );
		$this->assertSame( 0, $result['orphan_count'] );
	}

	public function test_single_post_with_no_inbound_links_is_orphan(): void {
		$this->make_post( 1, 'Lonely Post' );

		$result = $this->detector->scan( true );

		$this->assertContains( 1, $result['orphans'] );
		$this->assertSame( 1, $result['orphan_count'] );
	}

	public function test_post_linked_from_another_is_not_orphan(): void {
		$this->make_post( 1, 'Source Post', '<a href="' . $this->permalink( 2 ) . '">Link</a>' );
		$this->make_post( 2, 'Target Post' );

		$result = $this->detector->scan( true );

		$this->assertNotContains( 2, $result['orphans'] );
	}

	public function test_unlinked_post_is_orphan_when_other_is_linked(): void {
		$this->make_post( 1, 'Source', '<a href="' . $this->permalink( 2 ) . '">go</a>' );
		$this->make_post( 2, 'Target' );
		$this->make_post( 3, 'Orphan' );

		$result = $this->detector->scan( true );

		$this->assertContains( 3, $result['orphans'] );
		$this->assertNotContains( 2, $result['orphans'] );
	}

	public function test_total_scanned_counts_all_posts(): void {
		$this->make_post( 1, 'A' );
		$this->make_post( 2, 'B' );
		$this->make_post( 3, 'C' );

		$result = $this->detector->scan( true );

		$this->assertSame( 3, $result['total_scanned'] );
	}

	// -----------------------------------------------------------------------
	// scan() — cache
	// -----------------------------------------------------------------------

	public function test_scan_result_is_cached(): void {
		$this->make_post( 10, 'Post' );

		$first  = $this->detector->scan( true );
		$second = $this->detector->scan( false );

		$this->assertFalse( $first['cached'] );
		$this->assertTrue( $second['cached'] );
	}

	public function test_force_refresh_bypasses_cache(): void {
		$this->make_post( 10, 'Post' );
		$this->detector->scan( true ); // prime cache

		$result = $this->detector->scan( true ); // force refresh

		$this->assertFalse( $result['cached'] );
	}

	// -----------------------------------------------------------------------
	// mark_reviewed / unmark_reviewed
	// -----------------------------------------------------------------------

	public function test_reviewed_post_excluded_from_scan(): void {
		$this->make_post( 5, 'Reviewed' );
		$this->detector->mark_reviewed( 5 );

		$result = $this->detector->scan( true );

		$this->assertNotContains( 5, $result['orphans'] );
	}

	public function test_unmark_reviewed_re_exposes_post(): void {
		$this->make_post( 6, 'Reviewed' );
		$this->detector->mark_reviewed( 6 );
		$this->detector->unmark_reviewed( 6 );

		$result = $this->detector->scan( true );

		$this->assertContains( 6, $result['orphans'] );
	}

	// -----------------------------------------------------------------------
	// get_orphan_detail
	// -----------------------------------------------------------------------

	public function test_get_orphan_detail_returns_correct_structure(): void {
		$this->make_post( 20, 'Detail Post' );
		$detail = $this->detector->get_orphan_detail( 20 );

		$this->assertSame( 20, $detail['post_id'] );
		$this->assertSame( 'Detail Post', $detail['title'] );
		$this->assertArrayHasKey( 'url', $detail );
		$this->assertArrayHasKey( 'post_type', $detail );
		$this->assertArrayHasKey( 'inbound_count', $detail );
		$this->assertArrayHasKey( 'is_reviewed', $detail );
		$this->assertArrayHasKey( 'suggestions', $detail );
	}

	public function test_get_orphan_detail_inbound_count_zero_for_orphan(): void {
		$this->make_post( 21, 'Orphan' );

		$detail = $this->detector->get_orphan_detail( 21 );

		$this->assertSame( 0, $detail['inbound_count'] );
	}

	public function test_get_orphan_detail_inbound_count_positive_when_linked(): void {
		// Post 30 links to post 31.
		$this->make_post( 30, 'Source', '<a href="' . $this->permalink( 31 ) . '">link</a>' );
		$this->make_post( 31, 'Target' );

		$detail = $this->detector->get_orphan_detail( 31 );

		$this->assertGreaterThan( 0, $detail['inbound_count'] );
	}

	// -----------------------------------------------------------------------
	// generate_suggestions
	// -----------------------------------------------------------------------

	public function test_generate_suggestions_returns_related_posts(): void {
		$this->make_post( 40, 'PHP performance tips' );
		$this->make_post( 41, 'PHP optimization guide' );
		$this->make_post( 42, 'Cooking recipes' );
		$this->make_post( 43, 'PHP best practices' );

		$suggestions = $this->detector->generate_suggestions( 40 );

		// Posts 41 and 43 share "PHP" with post 40; cooking should not appear.
		$this->assertContains( 41, $suggestions );
		$this->assertContains( 43, $suggestions );
		$this->assertNotContains( 42, $suggestions );
	}

	public function test_generate_suggestions_persisted_to_meta(): void {
		$this->make_post( 50, 'SEO guide' );
		$this->make_post( 51, 'SEO tips' );

		$this->detector->generate_suggestions( 50 );

		$stored = $this->detector->get_suggestions( 50 );
		$this->assertNotEmpty( $stored );
	}

	public function test_get_suggestions_returns_empty_when_none_stored(): void {
		$this->make_post( 60, 'New Post' );

		$this->assertSame( [], $this->detector->get_suggestions( 60 ) );
	}

	public function test_suggestions_capped_at_max(): void {
		// Create many posts with overlapping words.
		for ( $i = 100; $i < 115; $i++ ) {
			$this->make_post( $i, "SEO content guide post $i" );
		}

		$suggestions = $this->detector->generate_suggestions( 100 );

		$this->assertLessThanOrEqual( OrphanPageDetector::SUGGESTION_COUNT, count( $suggestions ) );
	}

	// -----------------------------------------------------------------------
	// invalidate_cache
	// -----------------------------------------------------------------------

	public function test_invalidate_cache_forces_fresh_scan(): void {
		$this->make_post( 70, 'Cached' );
		$this->detector->scan( true ); // fill cache

		$this->detector->invalidate_cache();

		$result = $this->detector->scan( false );
		$this->assertFalse( $result['cached'] ); // cache was cleared
	}
}
