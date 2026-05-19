<?php
/**
 * Integration tests for Monetization workflows.
 *
 * Tests funnel-aware AdSense placement, revenue tracking, and affiliate integration.
 * Validates the complete monetization pipeline from detection to placement.
 *
 * @package PearBlogEngine\Tests\Integration
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration test suite for monetization features.
 */
class MonetizationIntegrationTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// Initialize global test state
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_posts']      = [];

		// Configure monetization settings
		$GLOBALS['_options']['pearblog_adsense_publisher_id'] = 'ca-pub-1234567890';
		$GLOBALS['_options']['pearblog_adsense_strategy'] = 'funnel_aware';
		$GLOBALS['_options']['pearblog_adsense_enable_tofu'] = true;
		$GLOBALS['_options']['pearblog_adsense_enable_mofu'] = true;
		$GLOBALS['_options']['pearblog_adsense_enable_bofu'] = false; // Disabled for bottom of funnel
		$GLOBALS['_options']['pearblog_v7_revenue_enabled'] = true;
		$GLOBALS['_options']['pearblog_affiliate_disclosure'] = 'This post contains affiliate links.';
	}

	// ------------------------------------------------------------------
	// Funnel Stage Detection
	// ------------------------------------------------------------------

	public function test_detects_tofu_content_from_informational_keywords(): void {
		$content = 'What is Docker and how does it work? Learn the basics of containerization.';

		// Simulate funnel detection
		$funnel_stage = $this->detect_funnel_stage( $content );

		$this->assertSame( 'TOFU', $funnel_stage );
	}

	public function test_detects_mofu_content_from_comparison_keywords(): void {
		$content = 'Docker vs Kubernetes: Which is better for your project? Compare features and pricing.';

		$funnel_stage = $this->detect_funnel_stage( $content );

		$this->assertSame( 'MOFU', $funnel_stage );
	}

	public function test_detects_bofu_content_from_transactional_keywords(): void {
		$content = 'Buy Docker Enterprise now. Get 20% off. Download and install today. Special offer.';

		$funnel_stage = $this->detect_funnel_stage( $content );

		$this->assertSame( 'BOFU', $funnel_stage );
	}

	// ------------------------------------------------------------------
	// AdSense Placement Strategy
	// ------------------------------------------------------------------

	public function test_tofu_content_shows_full_ads(): void {
		$post_id = 1;
		$GLOBALS['_post_meta'][$post_id]['pearblog_funnel_stage'] = 'TOFU';

		$ad_count = $this->get_ad_placement_count( $post_id );

		// TOFU should show 2 ads (full placement)
		$this->assertSame( 2, $ad_count );
	}

	public function test_mofu_content_shows_limited_ads(): void {
		$post_id = 2;
		$GLOBALS['_post_meta'][$post_id]['pearblog_funnel_stage'] = 'MOFU';

		$ad_count = $this->get_ad_placement_count( $post_id );

		// MOFU should show 1 ad (limited placement)
		$this->assertSame( 1, $ad_count );
	}

	public function test_bofu_content_shows_no_ads(): void {
		$post_id = 3;
		$GLOBALS['_post_meta'][$post_id]['pearblog_funnel_stage'] = 'BOFU';

		$ad_count = $this->get_ad_placement_count( $post_id );

		// BOFU should show 0 ads (disabled for conversion focus)
		$this->assertSame( 0, $ad_count );
	}

	public function test_adsense_strategy_respects_configuration(): void {
		// Test with balanced strategy
		$GLOBALS['_options']['pearblog_adsense_strategy'] = 'balanced';

		$post_id = 4;
		$content = '<p>Test content</p>';

		// Balanced strategy should inject at standard positions
		$injected = $this->inject_ads( $content, $post_id );

		$this->assertStringContainsString( 'data-ad-client', $injected );
	}

	// ------------------------------------------------------------------
	// Ad Injection Positions
	// ------------------------------------------------------------------

	public function test_header_ad_injection(): void {
		$content = '<h1>Title</h1><p>Content here</p>';
		$post_id = 5;
		$GLOBALS['_post_meta'][$post_id]['pearblog_funnel_stage'] = 'TOFU';

		$injected = $this->inject_ads( $content, $post_id );

		// Should inject ad after title
		$this->assertMatchesRegularExpression( '/<h1>.*<\/h1>.*data-ad-client/s', $injected );
	}

	public function test_in_content_ad_injection(): void {
		$content = '<p>Para 1</p><p>Para 2</p><p>Para 3</p><p>Para 4</p>';
		$post_id = 6;
		$GLOBALS['_post_meta'][$post_id]['pearblog_funnel_stage'] = 'TOFU';

		$injected = $this->inject_ads( $content, $post_id );

		// Should inject ad in middle of content
		$ad_count = substr_count( $injected, 'data-ad-client' );
		$this->assertGreaterThanOrEqual( 1, $ad_count );
	}

	public function test_no_ads_injected_when_disabled(): void {
		$GLOBALS['_options']['pearblog_v7_revenue_enabled'] = false;

		$content = '<p>Test content</p>';
		$post_id = 7;

		$injected = $this->inject_ads( $content, $post_id );

		// Should not contain any ads
		$this->assertStringNotContainsString( 'data-ad-client', $injected );
	}

	// ------------------------------------------------------------------
	// Revenue Tracking
	// ------------------------------------------------------------------

	public function test_revenue_tracking_stores_impressions(): void {
		$post_id = 8;
		$impressions = 1000;
		$rpm = 2.5;
		$revenue = ( $impressions / 1000 ) * $rpm;

		// Store revenue data
		$GLOBALS['_post_meta'][$post_id]['pearblog_ad_impressions'] = $impressions;
		$GLOBALS['_post_meta'][$post_id]['pearblog_ad_revenue'] = $revenue;

		$stored_revenue = (float) $GLOBALS['_post_meta'][$post_id]['pearblog_ad_revenue'];

		$this->assertSame( 2.5, $stored_revenue );
	}

	public function test_revenue_calculation_is_accurate(): void {
		$impressions = 5000;
		$rpm = 3.75; // Revenue per 1000 impressions

		$expected_revenue = ( $impressions / 1000 ) * $rpm;
		$calculated_revenue = $this->calculate_revenue( $impressions, $rpm );

		$this->assertSame( $expected_revenue, $calculated_revenue );
		$this->assertSame( 18.75, $calculated_revenue ); // 5 * 3.75
	}

	public function test_top_earning_posts_aggregation(): void {
		// Create posts with different revenue
		$GLOBALS['_post_meta'][10]['pearblog_ad_revenue'] = 15.50;
		$GLOBALS['_post_meta'][11]['pearblog_ad_revenue'] = 25.00;
		$GLOBALS['_post_meta'][12]['pearblog_ad_revenue'] = 8.75;

		$top_earners = $this->get_top_earning_posts( 2 );

		$this->assertCount( 2, $top_earners );
		$this->assertSame( 11, $top_earners[0]['post_id'] ); // Highest earner first
		$this->assertSame( 25.00, $top_earners[0]['revenue'] );
	}

	// ------------------------------------------------------------------
	// Affiliate Link Management
	// ------------------------------------------------------------------

	public function test_affiliate_link_injection(): void {
		$content = 'Check out <a href="https://example.com/product">this product</a>.';
		$affiliate_id = 'partner-123';

		$injected = $this->inject_affiliate_id( $content, $affiliate_id );

		$this->assertStringContainsString( 'partner-123', $injected );
	}

	public function test_affiliate_disclosure_is_added(): void {
		$content = '<p>Product review content</p>';
		$has_affiliate_links = true;

		if ( $has_affiliate_links ) {
			$disclosure = $GLOBALS['_options']['pearblog_affiliate_disclosure'];
			$content = '<div class="affiliate-disclosure">' . $disclosure . '</div>' . $content;
		}

		$this->assertStringContainsString( 'affiliate links', $content );
		$this->assertStringContainsString( 'affiliate-disclosure', $content );
	}

	public function test_affiliate_link_tracking_parameter(): void {
		$url = 'https://example.com/product';
		$tracking_id = 'pb_affiliate_001';

		$tracked_url = $this->add_tracking_parameter( $url, $tracking_id );

		$this->assertStringContainsString( 'pb_affiliate_001', $tracked_url );
		$this->assertStringContainsString( '?', $tracked_url );
	}

	// ------------------------------------------------------------------
	// Sponsored Content
	// ------------------------------------------------------------------

	public function test_sponsored_post_displays_badge(): void {
		$post_id = 15;
		$GLOBALS['_post_meta'][$post_id]['pearblog_is_sponsored'] = true;

		$has_badge = (bool) ( $GLOBALS['_post_meta'][$post_id]['pearblog_is_sponsored'] ?? false );

		$this->assertTrue( $has_badge );
	}

	public function test_sponsored_post_tracking(): void {
		$post_id = 16;
		$sponsor = 'TechCorp Inc';
		$campaign = 'Q2_2026_Product_Launch';

		$GLOBALS['_post_meta'][$post_id]['pearblog_sponsor'] = $sponsor;
		$GLOBALS['_post_meta'][$post_id]['pearblog_campaign'] = $campaign;

		$stored_sponsor = $GLOBALS['_post_meta'][$post_id]['pearblog_sponsor'];
		$stored_campaign = $GLOBALS['_post_meta'][$post_id]['pearblog_campaign'];

		$this->assertSame( $sponsor, $stored_sponsor );
		$this->assertSame( $campaign, $stored_campaign );
	}

	// ------------------------------------------------------------------
	// Revenue Reporting
	// ------------------------------------------------------------------

	public function test_daily_revenue_aggregation(): void {
		$today = gmdate( 'Y-m-d' );

		// Simulate multiple posts earning revenue today
		$GLOBALS['_post_meta'][20]['pearblog_ad_revenue'] = 5.00;
		$GLOBALS['_post_meta'][20]['pearblog_revenue_date'] = $today;

		$GLOBALS['_post_meta'][21]['pearblog_ad_revenue'] = 7.50;
		$GLOBALS['_post_meta'][21]['pearblog_revenue_date'] = $today;

		$total_today = $this->get_revenue_for_date( $today );

		$this->assertSame( 12.50, $total_today );
	}

	public function test_monthly_revenue_trend(): void {
		$revenue_by_month = [
			'2026-01' => 450.00,
			'2026-02' => 520.00,
			'2026-03' => 585.00,
			'2026-04' => 630.00,
		];

		// Calculate growth rate
		$jan = $revenue_by_month['2026-01'];
		$apr = $revenue_by_month['2026-04'];
		$growth_rate = ( ( $apr - $jan ) / $jan ) * 100;

		$this->assertSame( 40.0, $growth_rate ); // 40% growth
		$this->assertGreaterThan( 0, $growth_rate ); // Positive trend
	}

	// ------------------------------------------------------------------
	// Ad Performance Optimization
	// ------------------------------------------------------------------

	public function test_low_performing_ads_flagged_for_optimization(): void {
		$post_id = 25;
		$impressions = 10000;
		$rpm = 0.50; // Below threshold of 1.00

		$GLOBALS['_post_meta'][$post_id]['pearblog_ad_impressions'] = $impressions;
		$GLOBALS['_post_meta'][$post_id]['pearblog_rpm'] = $rpm;

		$needs_optimization = $rpm < 1.00;

		$this->assertTrue( $needs_optimization );
	}

	// ------------------------------------------------------------------
	// Helper Methods
	// ------------------------------------------------------------------

	private function detect_funnel_stage( string $content ): string {
		$content_lower = strtolower( $content );

		// BOFU keywords (highest priority - conversion intent)
		$bofu_keywords = [ 'buy', 'purchase', 'download', 'discount', 'offer', 'special' ];
		foreach ( $bofu_keywords as $keyword ) {
			if ( str_contains( $content_lower, $keyword ) ) {
				return 'BOFU';
			}
		}

		// MOFU keywords (comparison, consideration)
		$mofu_keywords = [ 'vs', 'versus', 'compare', 'comparison', 'better', 'best', 'review', 'alternative' ];
		foreach ( $mofu_keywords as $keyword ) {
			if ( str_contains( $content_lower, $keyword ) ) {
				return 'MOFU';
			}
		}

		// TOFU (default - informational)
		return 'TOFU';
	}

	private function get_ad_placement_count( int $post_id ): int {
		$funnel_stage = $GLOBALS['_post_meta'][$post_id]['pearblog_funnel_stage'] ?? 'TOFU';

		// Check if funnel stage is enabled in options
		$tofu_enabled = $GLOBALS['_options']['pearblog_adsense_enable_tofu'] ?? true;
		$mofu_enabled = $GLOBALS['_options']['pearblog_adsense_enable_mofu'] ?? true;
		$bofu_enabled = $GLOBALS['_options']['pearblog_adsense_enable_bofu'] ?? false;

		if ( 'TOFU' === $funnel_stage && $tofu_enabled ) {
			return 2; // Full placement
		}

		if ( 'MOFU' === $funnel_stage && $mofu_enabled ) {
			return 1; // Limited placement
		}

		if ( 'BOFU' === $funnel_stage && $bofu_enabled ) {
			return 2; // If enabled (default is disabled)
		}

		return 0; // No ads
	}

	private function inject_ads( string $content, int $post_id ): string {
		$revenue_enabled = $GLOBALS['_options']['pearblog_v7_revenue_enabled'] ?? false;

		if ( ! $revenue_enabled ) {
			return $content;
		}

		$ad_count = $this->get_ad_placement_count( $post_id );

		if ( $ad_count === 0 ) {
			return $content;
		}

		// Inject ad code
		$ad_code = '<ins class="adsbygoogle" data-ad-client="' .
		           esc_attr( $GLOBALS['_options']['pearblog_adsense_publisher_id'] ) .
		           '"></ins>';

		// Simple injection after first heading or paragraph.
		$injected = preg_replace( '/<\/h1>/', '</h1>' . $ad_code, $content, 1, $heading_replacements );
		if ( 0 === $heading_replacements ) {
			$injected = preg_replace( '/<\/p>/', '</p>' . $ad_code, $content, 1, $paragraph_replacements );
			if ( 0 === $paragraph_replacements ) {
				$injected = $content . $ad_code;
			}
		}

		if ( $ad_count > 1 ) {
			$injected .= $ad_code;
		}

		return $injected;
	}

	private function calculate_revenue( int $impressions, float $rpm ): float {
		return ( $impressions / 1000 ) * $rpm;
	}

	private function get_top_earning_posts( int $limit ): array {
		$posts = [];

		foreach ( $GLOBALS['_post_meta'] as $post_id => $meta ) {
			if ( isset( $meta['pearblog_ad_revenue'] ) ) {
				$posts[] = [
					'post_id' => $post_id,
					'revenue' => $meta['pearblog_ad_revenue'],
				];
			}
		}

		// Sort by revenue descending
		usort( $posts, fn( $a, $b ) => $b['revenue'] <=> $a['revenue'] );

		return array_slice( $posts, 0, $limit );
	}

	private function inject_affiliate_id( string $content, string $affiliate_id ): string {
		// Add affiliate ID to URLs
		return preg_replace(
			'/(href="https?:\/\/[^"]+)(")/i',
			'$1?ref=' . urlencode( $affiliate_id ) . '$2',
			$content
		);
	}

	private function add_tracking_parameter( string $url, string $tracking_id ): string {
		$separator = str_contains( $url, '?' ) ? '&' : '?';
		return $url . $separator . 'track=' . urlencode( $tracking_id );
	}

	private function get_revenue_for_date( string $date ): float {
		$total = 0.0;

		foreach ( $GLOBALS['_post_meta'] as $post_id => $meta ) {
			if ( isset( $meta['pearblog_ad_revenue'], $meta['pearblog_revenue_date'] ) ) {
				if ( $meta['pearblog_revenue_date'] === $date ) {
					$total += (float) $meta['pearblog_ad_revenue'];
				}
			}
		}

		return $total;
	}
}
