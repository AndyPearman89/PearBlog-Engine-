<?php
/**
 * Unit tests for ContentRefreshPrioritizer.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\ContentRefreshPrioritizer;

class ContentRefreshPrioritizerTest extends TestCase {

	private ContentRefreshPrioritizer $crp;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];

		$this->crp = new class extends ContentRefreshPrioritizer {
			protected function get_candidate_posts(): array {
				return [ 1, 2, 3 ];
			}
		};
	}

	// -----------------------------------------------------------------------
	// score_post()
	// -----------------------------------------------------------------------

	public function test_declining_trend_gives_max_trend_pts(): void {
		$GLOBALS['_post_meta'][1][ '_pearblog_traffic_trend' ] = [ 'declining' ];

		$score = $this->crp->score_post( 1 );

		$this->assertSame( 40, $score['factors']['trend_pts'] );
	}

	public function test_growing_trend_gives_zero_trend_pts(): void {
		$GLOBALS['_post_meta'][1][ '_pearblog_traffic_trend' ] = [ 'growing' ];

		$score = $this->crp->score_post( 1 );

		$this->assertSame( 0, $score['factors']['trend_pts'] );
	}

	public function test_unknown_trend_gives_default_trend_pts(): void {
		// No meta set.
		$score = $this->crp->score_post( 1 );

		$this->assertSame( 15, $score['factors']['trend_pts'] );
	}

	public function test_never_refreshed_gives_max_age_pts(): void {
		// No _pearblog_refreshed_at meta.
		$score = $this->crp->score_post( 1 );

		$this->assertSame( 30, $score['factors']['age_pts'] );
	}

	public function test_recently_refreshed_gives_low_age_pts(): void {
		$GLOBALS['_post_meta'][1][ '_pearblog_refreshed_at' ] = [ gmdate( 'Y-m-d H:i:s' ) ];

		$score = $this->crp->score_post( 1 );

		$this->assertLessThan( 5, $score['factors']['age_pts'] );
	}

	public function test_low_quality_gives_quality_pts(): void {
		$GLOBALS['_post_meta'][1][ '_pearblog_quality_score' ] = [ 40 ]; // below floor of 60

		$score = $this->crp->score_post( 1 );

		$this->assertGreaterThan( 0, $score['factors']['quality_pts'] );
	}

	public function test_high_quality_gives_zero_quality_pts(): void {
		$GLOBALS['_post_meta'][1][ '_pearblog_quality_score' ] = [ 90 ];

		$score = $this->crp->score_post( 1 );

		$this->assertSame( 0, $score['factors']['quality_pts'] );
	}

	public function test_view_decay_gives_decay_pts(): void {
		$GLOBALS['_post_meta'][1][ '_pearblog_weekly_views' ] = [ [ 1000, 700 ] ]; // 30% drop

		$score = $this->crp->score_post( 1 );

		$this->assertSame( 10, $score['factors']['decay_pts'] );
	}

	public function test_score_capped_at_100(): void {
		// Maximise all factors.
		$GLOBALS['_post_meta'][1][ '_pearblog_traffic_trend' ] = [ 'declining' ];
		$GLOBALS['_post_meta'][1][ '_pearblog_quality_score' ] = [ 10 ];
		$GLOBALS['_post_meta'][1][ '_pearblog_weekly_views'  ] = [ [ 500, 100 ] ];

		$score = $this->crp->score_post( 1 );

		$this->assertLessThanOrEqual( 100, $score['score'] );
	}

	public function test_score_result_has_updated_at_key(): void {
		$score = $this->crp->score_post( 1 );

		$this->assertArrayHasKey( 'updated_at', $score );
	}

	// -----------------------------------------------------------------------
	// rescore_all()
	// -----------------------------------------------------------------------

	public function test_rescore_all_returns_count_of_posts(): void {
		$count = $this->crp->rescore_all();

		$this->assertSame( 3, $count );
	}

	public function test_rescore_all_persists_scores(): void {
		$this->crp->rescore_all();

		$stored = get_option( ContentRefreshPrioritizer::OPTION_SCORES );

		$this->assertIsArray( $stored );
		$this->assertCount( 3, $stored );
	}

	// -----------------------------------------------------------------------
	// get_ranked_queue()
	// -----------------------------------------------------------------------

	public function test_get_ranked_queue_returns_empty_initially(): void {
		$queue = $this->crp->get_ranked_queue();

		$this->assertSame( [], $queue );
	}

	public function test_get_ranked_queue_returns_posts_sorted_by_score(): void {
		update_option( ContentRefreshPrioritizer::OPTION_SCORES, [
			10 => [ 'score' => 80, 'factors' => [] ],
			20 => [ 'score' => 30, 'factors' => [] ],
			30 => [ 'score' => 95, 'factors' => [] ],
		] );

		$queue = $this->crp->get_ranked_queue( 3 );

		$this->assertSame( 30, $queue[0]['post_id'] );
		$this->assertSame( 10, $queue[1]['post_id'] );
		$this->assertSame( 20, $queue[2]['post_id'] );
	}

	public function test_get_ranked_queue_respects_limit(): void {
		update_option( ContentRefreshPrioritizer::OPTION_SCORES, [
			1 => [ 'score' => 90, 'factors' => [] ],
			2 => [ 'score' => 80, 'factors' => [] ],
			3 => [ 'score' => 70, 'factors' => [] ],
		] );

		$queue = $this->crp->get_ranked_queue( 2 );

		$this->assertCount( 2, $queue );
	}
}
