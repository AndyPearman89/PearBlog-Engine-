<?php
/**
 * Unit tests for ContentROIEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Analytics\ContentROIEngine;

class ContentROIEngineTest extends TestCase {

	private ContentROIEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']  = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_post_list'] = [];
		$this->engine          = new ContentROIEngine();
	}

	// -----------------------------------------------------------------------
	// compute_article_roi — basic structure
	// -----------------------------------------------------------------------

	public function test_compute_article_roi_returns_expected_keys(): void {
		$roi = $this->engine->compute_article_roi( 1 );

		$required_keys = [
			'post_id', 'title', 'url',
			'sessions_30d', 'cost_cents', 'cost_usd',
			'revenue_cents', 'revenue_usd',
			'roi_cents', 'roi_pct', 'rpm_cents',
			'break_even_sessions', 'is_profitable', 'revenue_by_source',
		];

		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $roi, "Missing key: {$key}" );
		}
	}

	public function test_compute_article_roi_returns_correct_post_id(): void {
		$roi = $this->engine->compute_article_roi( 42 );

		$this->assertSame( 42, $roi['post_id'] );
	}

	// -----------------------------------------------------------------------
	// compute_article_roi — no cost/revenue
	// -----------------------------------------------------------------------

	public function test_compute_article_roi_is_profitable_when_zero_cost_and_zero_revenue(): void {
		$roi = $this->engine->compute_article_roi( 1 );

		// Zero cost, zero revenue: roi_cents = 0, is_profitable = true (0 >= 0).
		$this->assertSame( 0.0, $roi['cost_cents'] );
		$this->assertSame( 0.0, $roi['revenue_cents'] );
		$this->assertTrue( $roi['is_profitable'] );
	}

	// -----------------------------------------------------------------------
	// compute_article_roi — with cost
	// -----------------------------------------------------------------------

	public function test_compute_article_roi_uses_post_meta_for_cost(): void {
		update_post_meta( 5, 'pearblog_generation_cost_cents', 200 );

		$roi = $this->engine->compute_article_roi( 5 );

		$this->assertSame( 200.0, $roi['cost_cents'] );
		$this->assertEqualsWithDelta( 2.0, $roi['cost_usd'], 0.001 );
	}

	public function test_compute_article_roi_is_not_profitable_with_cost_and_no_revenue(): void {
		update_post_meta( 5, 'pearblog_generation_cost_cents', 500 );

		$roi = $this->engine->compute_article_roi( 5 );

		$this->assertFalse( $roi['is_profitable'] );
		$this->assertSame( -500.0, $roi['roi_cents'] );
	}

	// -----------------------------------------------------------------------
	// compute_article_roi — with revenue
	// -----------------------------------------------------------------------

	public function test_compute_article_roi_is_profitable_when_revenue_exceeds_cost(): void {
		update_post_meta( 10, 'pearblog_generation_cost_cents', 100 );
		// Set revenue via the RevenueTracker option (option key: pearblog_revenue_{post_id}).
		update_option( 'pearblog_revenue_10', [
			'total_cents'   => 500.0,
			'totals'        => [ 'adsense' => 500.0 ],
			'events'        => [],
			'ai_cost_cents' => 0.0,
			'roi_cents'     => 400.0,
			'last_updated'  => time(),
		] );

		$roi = $this->engine->compute_article_roi( 10 );

		$this->assertTrue( $roi['is_profitable'] );
		$this->assertSame( 400.0, $roi['roi_cents'] );
	}

	public function test_compute_article_roi_roi_pct_is_zero_when_no_cost(): void {
		$roi = $this->engine->compute_article_roi( 3 );

		$this->assertSame( 0.0, $roi['roi_pct'] );
	}

	public function test_compute_article_roi_calculates_roi_pct(): void {
		update_post_meta( 7, 'pearblog_generation_cost_cents', 100 );
		update_option( 'pearblog_revenue_7', [
			'total_cents'  => 200.0,
			'totals'       => [],
			'events'       => [],
			'ai_cost_cents'=> 0.0,
			'roi_cents'    => 100.0,
			'last_updated' => time(),
		] );

		$roi = $this->engine->compute_article_roi( 7 );

		$this->assertSame( 100.0, $roi['roi_pct'] );
	}

	// -----------------------------------------------------------------------
	// compute_article_roi — break-even sessions
	// -----------------------------------------------------------------------

	public function test_compute_article_roi_break_even_is_zero_when_no_cost(): void {
		$roi = $this->engine->compute_article_roi( 1 );

		$this->assertSame( 0, $roi['break_even_sessions'] );
	}

	public function test_compute_article_roi_break_even_is_zero_when_no_sessions(): void {
		update_post_meta( 11, 'pearblog_generation_cost_cents', 300 );

		$roi = $this->engine->compute_article_roi( 11 );

		// rpm = 0 (no sessions), so break_even = 0.
		$this->assertSame( 0, $roi['break_even_sessions'] );
	}

	// -----------------------------------------------------------------------
	// build_snapshot — empty post list
	// -----------------------------------------------------------------------

	public function test_build_snapshot_returns_expected_structure(): void {
		$snapshot = $this->engine->build_snapshot();

		$required_keys = [
			'generated_at', 'articles_analysed',
			'total_revenue_cents', 'total_revenue_usd',
			'total_cost_cents', 'total_cost_usd',
			'net_roi_cents', 'net_roi_pct',
			'profitable_articles', 'articles',
		];

		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $snapshot, "Missing key: {$key}" );
		}
	}

	public function test_build_snapshot_is_empty_when_no_posts(): void {
		$snapshot = $this->engine->build_snapshot();

		$this->assertSame( 0, $snapshot['articles_analysed'] );
		$this->assertSame( 0.0, $snapshot['total_revenue_cents'] );
		$this->assertSame( 0.0, $snapshot['total_cost_cents'] );
		$this->assertSame( [], $snapshot['articles'] );
	}

	// -----------------------------------------------------------------------
	// build_snapshot — with posts
	// -----------------------------------------------------------------------

	public function test_build_snapshot_counts_articles_correctly(): void {
		$GLOBALS['_post_list'] = [ 1, 2, 3 ];

		$snapshot = $this->engine->build_snapshot();

		$this->assertSame( 3, $snapshot['articles_analysed'] );
	}

	public function test_build_snapshot_aggregates_costs(): void {
		$GLOBALS['_post_list'] = [ 20, 21 ];
		update_post_meta( 20, 'pearblog_generation_cost_cents', 100 );
		update_post_meta( 21, 'pearblog_generation_cost_cents', 150 );

		$snapshot = $this->engine->build_snapshot();

		$this->assertSame( 250.0, $snapshot['total_cost_cents'] );
	}

	public function test_build_snapshot_counts_profitable_articles(): void {
		$GLOBALS['_post_list'] = [ 30, 31 ];
		// Post 30: no cost, no revenue → profitable (0 >= 0)
		// Post 31: cost 200, no revenue → not profitable
		update_post_meta( 31, 'pearblog_generation_cost_cents', 200 );

		$snapshot = $this->engine->build_snapshot();

		$this->assertSame( 1, $snapshot['profitable_articles'] );
	}

	// -----------------------------------------------------------------------
	// refresh / get_snapshot
	// -----------------------------------------------------------------------

	public function test_refresh_persists_snapshot_to_option(): void {
		$this->engine->refresh();

		$stored = get_option( ContentROIEngine::OPTION_SNAPSHOT );
		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( 'articles_analysed', $stored );
	}

	public function test_get_snapshot_returns_cached_snapshot(): void {
		$cached = [
			'generated_at'         => time(),
			'articles_analysed'    => 5,
			'total_revenue_cents'  => 1000.0,
			'total_revenue_usd'    => 10.0,
			'total_cost_cents'     => 200.0,
			'total_cost_usd'       => 2.0,
			'net_roi_cents'        => 800.0,
			'net_roi_pct'          => 400.0,
			'profitable_articles'  => 4,
			'articles'             => [],
		];
		update_option( ContentROIEngine::OPTION_SNAPSHOT, $cached );

		$snapshot = $this->engine->get_snapshot();

		$this->assertSame( 5, $snapshot['articles_analysed'] );
		$this->assertSame( 1000.0, $snapshot['total_revenue_cents'] );
	}

	public function test_get_snapshot_builds_fresh_when_no_cache(): void {
		// No cached snapshot — should call build_snapshot(20).
		$snapshot = $this->engine->get_snapshot();

		$this->assertIsArray( $snapshot );
		$this->assertArrayHasKey( 'articles_analysed', $snapshot );
	}
}
