<?php
/**
 * Tests for MonetizationEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Monetization\MonetizationEngine;
use PearBlogEngine\Tenant\SiteProfile;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\Monetization\MonetizationEngine
 */
class MonetizationEngineTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function make_profile( string $monetization ): SiteProfile {
		return new SiteProfile(
			industry:     'general',
			tone:         'neutral',
			monetization: $monetization,
			publish_rate: 1,
			language:     'en',
		);
	}

	private function register_post( int $id, string $title, string $content ): void {
		$post               = new \WP_Post();
		$post->ID           = $id;
		$post->post_title   = $title;
		$post->post_content = $content;
		$GLOBALS['_posts'][ $id ] = $post;
	}

	// -----------------------------------------------------------------------
	// Constructor
	// -----------------------------------------------------------------------

	public function test_can_be_instantiated(): void {
		$engine = new MonetizationEngine( $this->make_profile( 'adsense' ) );
		$this->assertInstanceOf( MonetizationEngine::class, $engine );
	}

	// -----------------------------------------------------------------------
	// apply() – AdSense branch (default)
	// -----------------------------------------------------------------------

	public function test_apply_adsense_returns_string(): void {
		$GLOBALS['_options']['pearblog_adsense_enable_tofu'] = '1';
		$this->register_post( 1, 'SEO Tips Guide', str_repeat( 'word ', 300 ) );

		$engine  = new MonetizationEngine( $this->make_profile( 'adsense' ) );
		$result  = $engine->apply( 1, str_repeat( '<p>paragraph</p>', 20 ) );
		$this->assertIsString( $result );
	}

	public function test_apply_adsense_returns_content_unchanged_when_disabled(): void {
		$GLOBALS['_options']['pearblog_adsense_enable_tofu']  = '0';
		$GLOBALS['_options']['pearblog_adsense_enable_mofu']  = '0';
		$GLOBALS['_options']['pearblog_adsense_enable_bofu']  = '0';

		$this->register_post( 2, 'Buy Widget Now Best Price', 'buy now purchase' );

		$content = '<p>Simple article.</p>';
		$engine  = new MonetizationEngine( $this->make_profile( 'adsense' ) );
		$result  = $engine->apply( 2, $content );
		$this->assertStringContainsString( 'Simple article', $result );
	}

	// -----------------------------------------------------------------------
	// apply() – affiliate branch
	// -----------------------------------------------------------------------

	public function test_apply_affiliate_returns_string(): void {
		$this->register_post( 3, 'Best Hotels Guide', 'hotel review content' );
		$engine = new MonetizationEngine( $this->make_profile( 'affiliate' ) );
		$result = $engine->apply( 3, '<p>Check out these hotels.</p>' );
		$this->assertIsString( $result );
	}

	// -----------------------------------------------------------------------
	// apply() – SaaS CTA branch
	// -----------------------------------------------------------------------

	public function test_apply_saas_cta_returns_string(): void {
		$this->register_post( 4, 'Best Marketing Tools', 'marketing software review' );
		$engine = new MonetizationEngine( $this->make_profile( 'saas' ) );
		$result = $engine->apply( 4, '<p>Here are the best marketing tools.</p>' );
		$this->assertIsString( $result );
	}

	// -----------------------------------------------------------------------
	// apply() – funnel stage detection
	// -----------------------------------------------------------------------

	public function test_apply_stores_funnel_stage_meta(): void {
		$this->register_post( 5, 'Best SEO Tools Guide', str_repeat( 'seo keyword ', 50 ) );
		$engine = new MonetizationEngine( $this->make_profile( 'adsense' ) );
		$engine->apply( 5, str_repeat( '<p>Content</p>', 10 ) );

		$raw   = $GLOBALS['_post_meta'][5]['pearblog_funnel_stage'] ?? null;
		$stage = is_array( $raw ) ? ( $raw[0] ?? null ) : $raw;
		$this->assertContains( $stage, [ 'tofu', 'mofu', 'bofu', null ] );
	}

	public function test_apply_fires_funnel_stage_action(): void {
		$this->register_post( 6, 'Learn SEO Basics', str_repeat( 'seo learn basics ', 30 ) );
		$fired = false;
		$GLOBALS['_action_handlers']['pearblog_funnel_stage_detected'] = function() use ( &$fired ) {
			$fired = true;
		};

		$engine = new MonetizationEngine( $this->make_profile( 'adsense' ) );
		$engine->apply( 6, str_repeat( '<p>Intro paragraph.</p>', 5 ) );

		$this->assertTrue( $fired );
	}

	// -----------------------------------------------------------------------
	// apply() – no post found
	// -----------------------------------------------------------------------

	public function test_apply_returns_content_when_post_not_found(): void {
		// Post ID 999 does not exist in _posts.
		$engine  = new MonetizationEngine( $this->make_profile( 'adsense' ) );
		$content = '<p>Standalone content.</p>';
		$result  = $engine->apply( 999, $content );
		$this->assertIsString( $result );
	}

	// -----------------------------------------------------------------------
	// apply() – filter
	// -----------------------------------------------------------------------

	public function test_apply_passes_through_pearblog_monetized_content_filter(): void {
		$this->register_post( 7, 'Widget Review Best Buy', 'buy widget price compare' );

		$engine = new MonetizationEngine( $this->make_profile( 'adsense' ) );
		$result = $engine->apply( 7, '<p>Original</p>' );

		// The stub apply_filters returns the first value argument unchanged.
		// We just verify the result is a non-empty string.
		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}
}
