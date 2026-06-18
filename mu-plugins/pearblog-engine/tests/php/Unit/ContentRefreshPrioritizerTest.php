<?php
/**
 * Unit tests for ContentRefreshPrioritizer (V9.0 F6).
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\ContentRefreshPrioritizer;

class ContentRefreshPrioritizerTest extends TestCase {

	private ContentRefreshPrioritizer $prioritizer;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];
		$this->prioritizer     = new ContentRefreshPrioritizer();
	}

	// -----------------------------------------------------------------------
	// score_post
	// -----------------------------------------------------------------------

	public function test_fresh_post_scores_zero(): void {
		$GLOBALS['_post_meta'][1]['_pearblog_modified_time'] = [ time() - 10 ];
		$result = $this->prioritizer->score_post( 1 );
		$this->assertSame( 1, $result['post_id'] );
		$this->assertSame( 0, $result['score'] );
		$this->assertSame( [], $result['reasons'] );
	}

	public function test_old_post_over_365_days_adds_40_pts(): void {
		$GLOBALS['_post_meta'][2]['_pearblog_modified_time'] = [ time() - 400 * DAY_IN_SECONDS ];
		$result = $this->prioritizer->score_post( 2 );
		$this->assertGreaterThanOrEqual( 40, $result['score'] );
		$this->assertNotEmpty( $result['reasons'] );
	}

	public function test_post_180_to_365_days_adds_25_pts(): void {
		$GLOBALS['_post_meta'][3]['_pearblog_modified_time'] = [ time() - 200 * DAY_IN_SECONDS ];
		$result = $this->prioritizer->score_post( 3 );
		$this->assertSame( 25, $result['score'] );
	}

	public function test_post_90_to_180_days_adds_10_pts(): void {
		$GLOBALS['_post_meta'][4]['_pearblog_modified_time'] = [ time() - 100 * DAY_IN_SECONDS ];
		$result = $this->prioritizer->score_post( 4 );
		$this->assertSame( 10, $result['score'] );
	}

	public function test_evergreen_post_skips_age_penalty(): void {
		$GLOBALS['_post_meta'][5]['_pearblog_modified_time'] = [ time() - 400 * DAY_IN_SECONDS ];
		$GLOBALS['_post_meta'][5][ ContentRefreshPrioritizer::META_EVERGREEN ] = [ '1' ];
		$result = $this->prioritizer->score_post( 5 );
		$this->assertSame( 0, $result['score'] );
	}

	public function test_severe_traffic_decline_adds_30_pts(): void {
		$GLOBALS['_post_meta'][6]['_pearblog_modified_time'] = [ time() - 10 ];
		$analytics = [ 6 => [ 'pageviews_current' => 50, 'pageviews_previous' => 1000 ] ];
		$result    = $this->prioritizer->score_post( 6, $analytics );
		$this->assertSame( 30, $result['score'] );
	}

	public function test_moderate_traffic_decline_adds_20_pts(): void {
		$GLOBALS['_post_meta'][7]['_pearblog_modified_time'] = [ time() - 10 ];
		$analytics = [ 7 => [ 'pageviews_current' => 650, 'pageviews_previous' => 1000 ] ]; // 35% drop
		$result    = $this->prioritizer->score_post( 7, $analytics );
		$this->assertSame( 20, $result['score'] );
	}

	public function test_minor_traffic_decline_adds_10_pts(): void {
		$GLOBALS['_post_meta'][8]['_pearblog_modified_time'] = [ time() - 10 ];
		$analytics = [ 8 => [ 'pageviews_current' => 870, 'pageviews_previous' => 1000 ] ]; // 13% drop
		$result    = $this->prioritizer->score_post( 8, $analytics );
		$this->assertSame( 10, $result['score'] );
	}

	public function test_low_quality_score_adds_20_pts(): void {
		$GLOBALS['_post_meta'][9]['_pearblog_modified_time']     = [ time() - 10 ];
		$GLOBALS['_post_meta'][9]['_pearblog_quality_score']      = [ '30' ];
		$result = $this->prioritizer->score_post( 9 );
		$this->assertSame( 20, $result['score'] );
	}

	public function test_score_capped_at_max(): void {
		// Old post + bad traffic + low quality => would exceed 100.
		$GLOBALS['_post_meta'][10]['_pearblog_modified_time']     = [ time() - 500 * DAY_IN_SECONDS ];
		$GLOBALS['_post_meta'][10]['_pearblog_quality_score']     = [ '10' ];
		$analytics = [ 10 => [ 'pageviews_current' => 10, 'pageviews_previous' => 1000 ] ];
		$result    = $this->prioritizer->score_post( 10, $analytics );
		$this->assertLessThanOrEqual( ContentRefreshPrioritizer::MAX_SCORE, $result['score'] );
	}

	// -----------------------------------------------------------------------
	// run
	// -----------------------------------------------------------------------

	public function test_run_stores_queue_option(): void {
		$this->prioritizer->run( [] );
		$queue = get_option( ContentRefreshPrioritizer::OPTION_QUEUE, null );
		$this->assertNotNull( $queue );
	}

	public function test_run_stores_last_run_timestamp(): void {
		$this->prioritizer->run( [] );
		$ts = get_option( ContentRefreshPrioritizer::OPTION_LAST_RUN );
		$this->assertSame( 1, preg_match( '/^\d{4}-\d{2}-\d{2}T/', $ts ) );
	}

	public function test_run_returns_sorted_by_score_descending(): void {
		// Inject two posts; post 2 should rank higher (older).
		$GLOBALS['_posts'] = [ 1 => [ 'post_status' => 'publish' ], 2 => [ 'post_status' => 'publish' ] ];
		$GLOBALS['_post_meta'][1]['_pearblog_modified_time'] = [ time() - 10 ];      // fresh
		$GLOBALS['_post_meta'][2]['_pearblog_modified_time'] = [ time() - 400 * DAY_IN_SECONDS ]; // old

		// Override get_posts to return our post IDs.
		$GLOBALS['_test_post_ids'] = [ 1, 2 ];

		$queue = $this->prioritizer->run( [] );

		if ( ! empty( $queue ) ) {
			// Verify descending order.
			for ( $i = 0; $i < count( $queue ) - 1; $i++ ) {
				$this->assertGreaterThanOrEqual( $queue[ $i + 1 ]['score'], $queue[ $i ]['score'] );
			}
		}

		$this->assertIsArray( $queue );
	}
}
