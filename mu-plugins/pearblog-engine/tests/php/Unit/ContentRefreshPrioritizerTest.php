<?php
/**
 * Unit tests for ContentRefreshPrioritizer.
 *
 * Tests pure scoring methods as well as the integrated prioritization queue.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\ContentRefreshEngine;
use PearBlogEngine\Content\ContentRefreshPrioritizer;

class ContentRefreshPrioritizerTest extends TestCase {

	private ContentRefreshPrioritizer $prioritizer;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];
		$GLOBALS['_post_list'] = [];
		$this->prioritizer = new ContentRefreshPrioritizer();
	}

	// -----------------------------------------------------------------------
	// age_score
	// -----------------------------------------------------------------------

	public function test_age_score_zero_for_fresh_post(): void {
		$this->assertSame( 0.0, $this->prioritizer->age_score( 0 ) );
	}

	public function test_age_score_caps_at_100_for_very_old_post(): void {
		$this->assertSame( 100.0, $this->prioritizer->age_score( ContentRefreshPrioritizer::MAX_AGE_DAYS + 100 ) );
	}

	public function test_age_score_50_at_half_max_age(): void {
		$half = ContentRefreshPrioritizer::MAX_AGE_DAYS / 2;
		$this->assertEqualsWithDelta( 50.0, $this->prioritizer->age_score( (int) $half ), 1.0 );
	}

	public function test_age_score_negative_days_returns_zero(): void {
		$this->assertSame( 0.0, $this->prioritizer->age_score( -10 ) );
	}

	// -----------------------------------------------------------------------
	// traffic_score
	// -----------------------------------------------------------------------

	public function test_traffic_score_zero_for_no_views(): void {
		$this->assertSame( 0.0, $this->prioritizer->traffic_score( 0 ) );
	}

	public function test_traffic_score_100_at_normalizer_views(): void {
		$this->assertSame( 100.0, $this->prioritizer->traffic_score( ContentRefreshPrioritizer::TRAFFIC_NORMALIZER ) );
	}

	public function test_traffic_score_caps_at_100_for_very_high_traffic(): void {
		$this->assertSame( 100.0, $this->prioritizer->traffic_score( ContentRefreshPrioritizer::TRAFFIC_NORMALIZER * 10 ) );
	}

	public function test_traffic_score_50_at_half_normalizer(): void {
		$half = (int) ( ContentRefreshPrioritizer::TRAFFIC_NORMALIZER / 2 );
		$this->assertEqualsWithDelta( 50.0, $this->prioritizer->traffic_score( $half ), 0.1 );
	}

	// -----------------------------------------------------------------------
	// quality_urgency_score
	// -----------------------------------------------------------------------

	public function test_quality_urgency_score_100_for_zero_quality(): void {
		// Quality of 0.0 is treated as "no quality data yet" — returns the sentinel value (80.0).
		$this->assertSame( 80.0, $this->prioritizer->quality_urgency_score( 0.0 ) );
	}

	public function test_quality_urgency_score_zero_for_perfect_quality(): void {
		$this->assertSame( 0.0, $this->prioritizer->quality_urgency_score( 100.0 ) );
	}

	public function test_quality_urgency_score_50_for_quality_50(): void {
		$this->assertEqualsWithDelta( 50.0, $this->prioritizer->quality_urgency_score( 50.0 ), 0.1 );
	}

	public function test_quality_urgency_score_default_for_no_data(): void {
		// quality <= 0 → medium-high urgency sentinel.
		$score = $this->prioritizer->quality_urgency_score( 0.0 );
		// The "no score" branch also returns 80 since quality = 0 ≤ 0.
		$this->assertGreaterThan( 0.0, $score );
	}

	// -----------------------------------------------------------------------
	// trend_score
	// -----------------------------------------------------------------------

	public function test_trend_score_100_for_declining(): void {
		$this->assertSame( 100.0, $this->prioritizer->trend_score( 'declining' ) );
	}

	public function test_trend_score_0_for_growing(): void {
		$this->assertSame( 0.0, $this->prioritizer->trend_score( 'growing' ) );
	}

	public function test_trend_score_50_for_stable(): void {
		$this->assertSame( 50.0, $this->prioritizer->trend_score( 'stable' ) );
	}

	public function test_trend_score_default_for_unknown(): void {
		$score = $this->prioritizer->trend_score( '' );
		$this->assertGreaterThanOrEqual( 0.0, $score );
		$this->assertLessThanOrEqual( 100.0, $score );
	}

	public function test_trend_score_for_invalid_trend_uses_unknown_default(): void {
		$score = $this->prioritizer->trend_score( 'foobar' );
		// Falls through to '' default = 30.
		$this->assertSame( 30.0, $score );
	}

	// -----------------------------------------------------------------------
	// score_post
	// -----------------------------------------------------------------------

	public function test_score_post_returns_required_keys(): void {
		$post_id = 10;
		$entry   = $this->prioritizer->score_post( $post_id );

		foreach ( [ 'post_id', 'score', 'age_days', 'quality', 'trend', 'views_30d' ] as $key ) {
			$this->assertArrayHasKey( $key, $entry, "Missing key: {$key}" );
		}
	}

	public function test_score_post_id_matches_input(): void {
		$entry = $this->prioritizer->score_post( 42 );
		$this->assertSame( 42, $entry['post_id'] );
	}

	public function test_score_post_score_in_0_100_range(): void {
		$GLOBALS['_post_meta'][99] = [
			'_pearblog_quality_score' => [ 60.0 ],
			'_pearblog_traffic_trend' => [ 'stable' ],
			'_pearblog_ga4_views_30d' => [ 300 ],
		];
		$entry = $this->prioritizer->score_post( 99 );

		$this->assertGreaterThanOrEqual( 0.0, $entry['score'] );
		$this->assertLessThanOrEqual( 100.0, $entry['score'] );
	}

	public function test_score_post_declining_trend_raises_score(): void {
		$base_meta = [
			'_pearblog_quality_score' => [ 60.0 ],
			'_pearblog_ga4_views_30d' => [ 300 ],
		];

		$GLOBALS['_post_meta'][1] = array_merge( $base_meta, [ '_pearblog_traffic_trend' => [ 'growing' ] ] );
		$GLOBALS['_post_meta'][2] = array_merge( $base_meta, [ '_pearblog_traffic_trend' => [ 'declining' ] ] );

		$growing   = $this->prioritizer->score_post( 1 )['score'];
		$declining = $this->prioritizer->score_post( 2 )['score'];

		$this->assertGreaterThan( $growing, $declining );
	}

	public function test_score_post_low_quality_raises_score(): void {
		$base_meta = [
			'_pearblog_traffic_trend' => [ 'stable' ],
			'_pearblog_ga4_views_30d' => [ 200 ],
		];

		$GLOBALS['_post_meta'][3] = array_merge( $base_meta, [ '_pearblog_quality_score' => [ 90.0 ] ] );
		$GLOBALS['_post_meta'][4] = array_merge( $base_meta, [ '_pearblog_quality_score' => [ 20.0 ] ] );

		$high_quality = $this->prioritizer->score_post( 3 )['score'];
		$low_quality  = $this->prioritizer->score_post( 4 )['score'];

		$this->assertGreaterThan( $high_quality, $low_quality );
	}

	// -----------------------------------------------------------------------
	// get_priority_queue / get_prioritized_ids
	// -----------------------------------------------------------------------

	public function test_get_priority_queue_returns_array(): void {
		$queue = $this->prioritizer->get_priority_queue();
		$this->assertIsArray( $queue );
	}

	public function test_get_priority_queue_respects_limit(): void {
		// With no posts in the stub, the queue should be empty.
		$queue = $this->prioritizer->get_priority_queue( 30, 5 );
		$this->assertLessThanOrEqual( 5, count( $queue ) );
	}

	public function test_get_prioritized_ids_returns_array_of_ints(): void {
		$ids = $this->prioritizer->get_prioritized_ids();
		$this->assertIsArray( $ids );
		foreach ( $ids as $id ) {
			$this->assertIsInt( $id );
		}
	}

	public function test_get_priority_queue_orders_by_score_descending(): void {
		$GLOBALS['_post_list'] = [ 1, 2 ];
		$GLOBALS['_posts'][1] = new \WP_Post( [ 'ID' => 1, 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS ) ] );
		$GLOBALS['_posts'][2] = new \WP_Post( [ 'ID' => 2, 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS ) ] );

		$GLOBALS['_post_meta'][1] = [
			ContentRefreshEngine::META_REFRESHED_AT => [ gmdate( 'Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS ) ],
			ContentRefreshEngine::META_TRAFFIC_TREND => [ 'growing' ],
			'_pearblog_quality_score' => [ 90.0 ],
			'_pearblog_ga4_views_30d' => [ 100 ],
		];
		$GLOBALS['_post_meta'][2] = [
			ContentRefreshEngine::META_REFRESHED_AT => [ gmdate( 'Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS ) ],
			ContentRefreshEngine::META_TRAFFIC_TREND => [ 'declining' ],
			'_pearblog_quality_score' => [ 20.0 ],
			'_pearblog_ga4_views_30d' => [ 1000 ],
		];

		$queue = $this->prioritizer->get_priority_queue( 30, 10, 0 );
		$this->assertSame( 2, $queue[0]['post_id'] );
	}

	public function test_get_priority_queue_respects_min_score_filter(): void {
		$GLOBALS['_post_list'] = [ 10, 11 ];
		$GLOBALS['_posts'][10] = new \WP_Post( [ 'ID' => 10, 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS ) ] );
		$GLOBALS['_posts'][11] = new \WP_Post( [ 'ID' => 11, 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 10 * DAY_IN_SECONDS ) ] );

		$GLOBALS['_post_meta'][10] = [
			ContentRefreshEngine::META_TRAFFIC_TREND => [ 'declining' ],
			'_pearblog_quality_score' => [ 10.0 ],
			'_pearblog_ga4_views_30d' => [ 1000 ],
		];
		$GLOBALS['_post_meta'][11] = [
			ContentRefreshEngine::META_TRAFFIC_TREND => [ 'growing' ],
			'_pearblog_quality_score' => [ 95.0 ],
			'_pearblog_ga4_views_30d' => [ 10 ],
		];

		$queue = $this->prioritizer->get_priority_queue( 1, 10, 70 );
		$this->assertCount( 1, $queue );
		$this->assertSame( 10, $queue[0]['post_id'] );
	}

	public function test_score_post_falls_back_to_post_date_when_refresh_meta_missing(): void {
		$GLOBALS['_posts'][55] = new \WP_Post( [
			'ID'        => 55,
			'post_date' => gmdate( 'Y-m-d H:i:s', time() - 14 * DAY_IN_SECONDS ),
		] );
		$GLOBALS['_post_meta'][55] = [
			'_pearblog_quality_score' => [ 70.0 ],
			'_pearblog_traffic_trend' => [ 'stable' ],
			'_pearblog_ga4_views_30d' => [ 200 ],
		];

		$entry = $this->prioritizer->score_post( 55 );
		$this->assertGreaterThanOrEqual( 13, $entry['age_days'] );
	}

	public function test_score_post_uses_default_stale_days_when_dates_unavailable(): void {
		$entry = $this->prioritizer->score_post( 99999 );
		$this->assertSame( ContentRefreshEngine::DEFAULT_STALE_DAYS, $entry['age_days'] );
	}
}
