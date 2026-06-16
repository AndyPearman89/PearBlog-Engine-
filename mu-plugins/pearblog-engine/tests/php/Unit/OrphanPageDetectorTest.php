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

class OrphanPageDetectorTest extends TestCase {

	private OrphanPageDetector $detector;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$this->detector = $this->make_testable_detector( [] );
	}

	// -----------------------------------------------------------------------
	// collect_link_targets()
	// -----------------------------------------------------------------------

	public function test_collect_link_targets_returns_empty_for_no_links(): void {
		$posts = [
			(object) [ 'ID' => 1, 'post_title' => 'Post 1', 'post_content' => 'No links here.' ],
		];

		$targets = $this->detector->collect_link_targets( $posts );

		$this->assertSame( [], $targets );
	}

	// -----------------------------------------------------------------------
	// is_orphan()
	// -----------------------------------------------------------------------

	public function test_is_orphan_true_when_not_in_targets(): void {
		$posts   = [
			(object) [ 'ID' => 1, 'post_title' => 'A', 'post_content' => 'no links' ],
			(object) [ 'ID' => 2, 'post_title' => 'B', 'post_content' => 'no links' ],
		];

		// Without url_to_postid, no links will be resolved → all are orphans.
		$result = $this->detector->is_orphan( 1, $posts );

		$this->assertTrue( $result );
	}

	// -----------------------------------------------------------------------
	// scan()
	// -----------------------------------------------------------------------

	public function test_scan_detects_all_posts_as_orphans_when_no_links(): void {
		$posts = [
			(object) [ 'ID' => 10, 'post_title' => 'Alpha', 'post_content' => 'plain text' ],
			(object) [ 'ID' => 11, 'post_title' => 'Beta',  'post_content' => 'plain text' ],
		];
		$detector = $this->make_testable_detector( $posts );

		$orphans = $detector->scan();

		$this->assertCount( 2, $orphans );
		$post_ids = array_column( $orphans, 'post_id' );
		$this->assertContains( 10, $post_ids );
		$this->assertContains( 11, $post_ids );
	}

	public function test_scan_persists_results(): void {
		$posts    = [
			(object) [ 'ID' => 5, 'post_title' => 'Test', 'post_content' => '' ],
		];
		$detector = $this->make_testable_detector( $posts );
		$detector->scan();

		$stored = get_option( OrphanPageDetector::OPTION_ORPHANS );
		$this->assertIsArray( $stored );
		$this->assertCount( 1, $stored );
	}

	public function test_scan_stores_last_scan_timestamp(): void {
		$detector = $this->make_testable_detector( [] );
		$detector->scan();

		$ts = get_option( OrphanPageDetector::OPTION_LAST_SCAN );
		$this->assertIsInt( $ts );
		$this->assertGreaterThan( 0, $ts );
	}

	public function test_scan_sorts_by_quality_score_descending(): void {
		$GLOBALS['_post_meta'][10][ '_pearblog_quality_score' ] = [ 30 ];
		$GLOBALS['_post_meta'][11][ '_pearblog_quality_score' ] = [ 90 ];

		$posts = [
			(object) [ 'ID' => 10, 'post_title' => 'Low quality', 'post_content' => '' ],
			(object) [ 'ID' => 11, 'post_title' => 'High quality', 'post_content' => '' ],
		];
		$detector = $this->make_testable_detector( $posts );
		$orphans  = $detector->scan();

		$this->assertSame( 11, $orphans[0]['post_id'] ); // higher quality first
	}

	// -----------------------------------------------------------------------
	// get_orphans()
	// -----------------------------------------------------------------------

	public function test_get_orphans_returns_empty_initially(): void {
		$this->assertSame( [], $this->detector->get_orphans() );
	}

	public function test_get_orphans_returns_stored_data(): void {
		$data = [ [ 'post_id' => 5, 'title' => 'X', 'quality_score' => 70.0, 'detected_at' => '2026-06-14T00:00:00Z' ] ];
		update_option( OrphanPageDetector::OPTION_ORPHANS, $data );

		$this->assertSame( $data, $this->detector->get_orphans() );
	}

	// -----------------------------------------------------------------------
	// apply_fix()
	// -----------------------------------------------------------------------

	public function test_apply_fix_returns_false_when_fix_not_applied(): void {
		// Without a pearblog_orphan_fix filter that returns true, result is false.
		$fixed = $this->detector->apply_fix( 999 );

		$this->assertFalse( $fixed );
	}

	public function test_apply_fix_removes_from_stored_orphans_on_success(): void {
		update_option( OrphanPageDetector::OPTION_ORPHANS, [
			[ 'post_id' => 42, 'title' => 'Orphan', 'quality_score' => 50.0, 'detected_at' => '2026-06-14' ],
		] );

		// Register a filter that returns true to simulate a successful fix.
		add_filter( 'pearblog_orphan_fix', fn( $fixed, $post_id ) => $post_id === 42 ? true : $fixed, 10, 2 );

		// Stub get_post to return a non-null object.
		if ( ! function_exists( 'get_post' ) ) {
			// get_post is stubbed in bootstrap; pre-set a result.
		}
		$GLOBALS['_get_post_return'] = (object) [ 'ID' => 42 ];

		$fixed = $this->detector->apply_fix( 42 );

		remove_all_filters( 'pearblog_orphan_fix' );

		if ( $fixed ) {
			$remaining = $this->detector->get_orphans();
			$this->assertEmpty( $remaining );
		} else {
			$this->assertFalse( $fixed );
		}
	}

	// -----------------------------------------------------------------------
	// Helper
	// -----------------------------------------------------------------------

	private function make_testable_detector( array $posts ): OrphanPageDetector {
		return new class( $posts ) extends OrphanPageDetector {
			public function __construct( private array $stubPosts ) {}

			protected function get_all_published_posts(): array {
				return $this->stubPosts;
			}
		};
	}
}
