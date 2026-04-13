<?php
/**
 * Unit tests for AnalyticsDashboard.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Analytics\AnalyticsDashboard;
use PearBlogEngine\Analytics\GA4Client;

class AnalyticsDashboardTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_posts']      = [];
		$GLOBALS['_post_list']  = [];
		$GLOBALS['_cron_scheduled'] = [];
	}

	// -----------------------------------------------------------------------
	// get_summary
	// -----------------------------------------------------------------------

	public function test_summary_returns_expected_keys(): void {
		$dash    = new AnalyticsDashboard();
		$summary = $dash->get_summary();

		$this->assertArrayHasKey( 'total_views_30d', $summary );
		$this->assertArrayHasKey( 'last_sync', $summary );
		$this->assertArrayHasKey( 'ga4_configured', $summary );
	}

	public function test_summary_ga4_not_configured_by_default(): void {
		$dash = new AnalyticsDashboard();
		$this->assertFalse( $dash->get_summary()['ga4_configured'] );
	}

	public function test_summary_total_views_zero_when_not_configured(): void {
		$dash = new AnalyticsDashboard();
		$this->assertSame( 0, $dash->get_summary()['total_views_30d'] );
	}

	public function test_summary_last_sync_is_never_initially(): void {
		$dash = new AnalyticsDashboard();
		$this->assertSame( 'never', $dash->get_summary()['last_sync'] );
	}

	public function test_summary_last_sync_reflects_option(): void {
		update_option( AnalyticsDashboard::OPTION_LAST_SYNC, '2026-04-01 12:00:00' );
		$dash = new AnalyticsDashboard();
		$this->assertSame( '2026-04-01 12:00:00', $dash->get_summary()['last_sync'] );
	}

	// -----------------------------------------------------------------------
	// sync_all_posts — not configured
	// -----------------------------------------------------------------------

	public function test_sync_all_posts_returns_zero_when_not_configured(): void {
		$dash = new AnalyticsDashboard();
		$this->assertSame( 0, $dash->sync_all_posts() );
	}

	// -----------------------------------------------------------------------
	// get_top_performing_posts
	// -----------------------------------------------------------------------

	public function test_get_top_performing_posts_empty_when_no_posts(): void {
		$dash = new AnalyticsDashboard();
		$this->assertSame( [], $dash->get_top_performing_posts() );
	}

	public function test_get_top_performing_posts_returns_correct_shape(): void {
		$post = new \WP_Post( [
			'ID'           => 50,
			'post_title'   => 'My Great Article',
			'post_content' => 'Content here.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][50]    = $post;
		$GLOBALS['_post_list']    = [50];
		$GLOBALS['_post_meta'][50][ AnalyticsDashboard::META_VIEWS_30D ] = [1200];
		$GLOBALS['_post_meta'][50]['_pearblog_quality_score']            = [85.0];

		$dash    = new AnalyticsDashboard();
		$results = $dash->get_top_performing_posts( 5 );

		$this->assertCount( 1, $results );
		$this->assertArrayHasKey( 'post_id', $results[0] );
		$this->assertArrayHasKey( 'title', $results[0] );
		$this->assertArrayHasKey( 'views_30d', $results[0] );
		$this->assertArrayHasKey( 'quality_score', $results[0] );
		$this->assertArrayHasKey( 'performance_score', $results[0] );
		$this->assertSame( 50, $results[0]['post_id'] );
	}

	public function test_performance_score_is_positive(): void {
		$post = new \WP_Post( [
			'ID'           => 51,
			'post_title'   => 'High Performance Post',
			'post_content' => 'Lots of great content.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][51]    = $post;
		$GLOBALS['_post_list']    = [51];
		$GLOBALS['_post_meta'][51][ AnalyticsDashboard::META_VIEWS_30D ] = [500];
		$GLOBALS['_post_meta'][51]['_pearblog_quality_score']            = [75.0];

		$dash    = new AnalyticsDashboard();
		$results = $dash->get_top_performing_posts( 5 );

		$this->assertGreaterThan( 0, $results[0]['performance_score'] );
	}

	// -----------------------------------------------------------------------
	// sync_post — stores meta
	// -----------------------------------------------------------------------

	public function test_sync_post_does_nothing_when_not_configured(): void {
		$dash = new AnalyticsDashboard();
		$dash->sync_post( 100 );

		// No meta should have been written.
		$this->assertEmpty( $GLOBALS['_post_meta'][100] ?? [] );
	}

	public function test_meta_keys_are_correct_constants(): void {
		$this->assertSame( '_pearblog_ga4_views_30d', AnalyticsDashboard::META_VIEWS_30D );
		$this->assertSame( '_pearblog_ga4_views_7d',  AnalyticsDashboard::META_VIEWS_7D );
		$this->assertSame( '_pearblog_ga4_updated_at', AnalyticsDashboard::META_UPDATED_AT );
	}
}
