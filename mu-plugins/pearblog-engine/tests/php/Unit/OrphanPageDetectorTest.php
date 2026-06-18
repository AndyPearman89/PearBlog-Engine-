<?php
/**
 * Unit tests for OrphanPageDetector (V9.0 F8).
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
		$GLOBALS['_home_url']  = 'https://example.com';
		$this->detector        = new OrphanPageDetector();
	}

	// -----------------------------------------------------------------------
	// normalise_url
	// -----------------------------------------------------------------------

	public function test_normalise_strips_trailing_slash(): void {
		$this->assertSame( 'https://example.com/page', $this->detector->normalise_url( 'https://example.com/page/' ) );
	}

	public function test_normalise_strips_query_string(): void {
		$this->assertSame( 'https://example.com/page', $this->detector->normalise_url( 'https://example.com/page?utm=test' ) );
	}

	public function test_normalise_strips_fragment(): void {
		$this->assertSame( 'https://example.com/page', $this->detector->normalise_url( 'https://example.com/page#section' ) );
	}

	public function test_normalise_strips_trailing_slash_and_query(): void {
		$this->assertSame( 'https://example.com/page', $this->detector->normalise_url( 'https://example.com/page/?q=1' ) );
	}

	// -----------------------------------------------------------------------
	// scan (injected data)
	// -----------------------------------------------------------------------

	public function test_all_pages_linked_returns_empty_orphan_list(): void {
		$permalinks = [ 'https://example.com/a', 'https://example.com/b' ];
		$linked     = [ 'https://example.com/a', 'https://example.com/b' ];
		$orphans    = $this->detector->scan( $permalinks, $linked );
		$this->assertSame( [], $orphans );
	}

	public function test_unlinked_page_is_reported_as_orphan(): void {
		$permalinks = [ 'https://example.com/a', 'https://example.com/b' ];
		$linked     = [ 'https://example.com/a' ]; // /b not linked.
		$orphans    = $this->detector->scan( $permalinks, $linked );
		$this->assertCount( 1, $orphans );
		$this->assertSame( 'https://example.com/b', $orphans[0]['permalink'] );
	}

	public function test_scan_normalises_urls_before_comparison(): void {
		$permalinks = [ 'https://example.com/page/' ];
		$linked     = [ 'https://example.com/page' ]; // no trailing slash.
		$orphans    = $this->detector->scan( $permalinks, $linked );
		$this->assertSame( [], $orphans );
	}

	public function test_scan_stores_orphans_in_option(): void {
		$permalinks = [ 'https://example.com/orphan' ];
		$linked     = [];
		$this->detector->scan( $permalinks, $linked );
		$stored = get_option( OrphanPageDetector::OPTION_ORPHANS, null );
		$this->assertNotNull( $stored );
		$this->assertCount( 1, $stored );
	}

	public function test_scan_stores_last_run_timestamp(): void {
		$this->detector->scan( [], [] );
		$ts = get_option( OrphanPageDetector::OPTION_LAST_RUN );
		$this->assertSame( 1, preg_match( '/^\d{4}-\d{2}-\d{2}T/', $ts ) );
	}

	public function test_multiple_unlinked_pages_all_reported(): void {
		$permalinks = [ 'https://example.com/a', 'https://example.com/b', 'https://example.com/c' ];
		$linked     = [];
		$orphans    = $this->detector->scan( $permalinks, $linked );
		$this->assertCount( 3, $orphans );
	}

	// -----------------------------------------------------------------------
	// collect_linked_urls
	// -----------------------------------------------------------------------

	public function test_collect_linked_urls_returns_empty_for_no_posts(): void {
		$urls = $this->detector->collect_linked_urls( [] );
		$this->assertSame( [], $urls );
	}

	// -----------------------------------------------------------------------
	// REST handler
	// -----------------------------------------------------------------------

	public function test_rest_get_orphans_returns_stored_data(): void {
		update_option( OrphanPageDetector::OPTION_ORPHANS, [
			[ 'post_id' => 0, 'permalink' => 'https://example.com/orphan', 'age_days' => 90 ],
		] );
		$req  = new \WP_REST_Request();
		$resp = $this->detector->rest_get_orphans( $req );
		$data = $resp->get_data();
		$this->assertCount( 1, $data['orphans'] );
	}

	public function test_rest_get_orphans_returns_empty_by_default(): void {
		$req  = new \WP_REST_Request();
		$resp = $this->detector->rest_get_orphans( $req );
		$data = $resp->get_data();
		$this->assertSame( [], $data['orphans'] );
	}
}
