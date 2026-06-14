<?php
/**
 * Unit tests for AffiliateDiscovery.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Monetization\AffiliateDiscovery;

class AffiliateDiscoveryTest extends TestCase {

	private AffiliateDiscovery $discovery;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];
		$GLOBALS['_actions']   = [];
		$this->discovery = new AffiliateDiscovery();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant(): void {
		$this->assertSame( 'pearblog_affiliate_enabled', AffiliateDiscovery::OPTION_ENABLED );
	}

	public function test_meta_links_constant(): void {
		$this->assertSame( 'pearblog_affiliate_links', AffiliateDiscovery::META_LINKS );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_false_by_default(): void {
		$this->assertFalse( $this->discovery->is_enabled() );
	}

	public function test_is_enabled_true_when_option_set(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );

		$this->assertTrue( $this->discovery->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// discover_for_post — disabled
	// -----------------------------------------------------------------------

	public function test_discover_for_post_returns_empty_when_disabled(): void {
		$links = $this->discovery->discover_for_post( 1 );

		$this->assertSame( [], $links );
	}

	public function test_discover_for_post_returns_empty_when_post_not_found(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );
		$GLOBALS['_posts'] = [];

		$links = $this->discovery->discover_for_post( 9999 );

		$this->assertSame( [], $links );
	}

	// -----------------------------------------------------------------------
	// extract_product_keywords
	// -----------------------------------------------------------------------

	public function test_extract_product_keywords_returns_array(): void {
		$keywords = $this->discovery->extract_product_keywords( 'Samsung Galaxy Pro is a great product.' );

		$this->assertIsArray( $keywords );
	}

	public function test_extract_product_keywords_finds_capitalized_phrases(): void {
		$keywords = $this->discovery->extract_product_keywords( 'The Apple iPhone is a popular device. Sony Headphones are great.' );

		$this->assertNotEmpty( $keywords );
	}

	public function test_extract_product_keywords_returns_empty_for_generic_text(): void {
		$keywords = $this->discovery->extract_product_keywords( 'this is a simple text without any capitalized brand names or products' );

		$this->assertIsArray( $keywords );
	}

	public function test_extract_product_keywords_returns_max_15(): void {
		$text = str_repeat( 'Alpha Beta Gamma Delta Epsilon Zeta Eta Theta. ', 5 );

		$keywords = $this->discovery->extract_product_keywords( $text );

		$this->assertLessThanOrEqual( 15, count( $keywords ) );
	}

	// -----------------------------------------------------------------------
	// inject_affiliate_links — disabled
	// -----------------------------------------------------------------------

	public function test_inject_affiliate_links_returns_content_unchanged_when_disabled(): void {
		$content = '<p>Samsung Galaxy is the best phone.</p>';

		$result = $this->discovery->inject_affiliate_links( $content );

		$this->assertSame( $content, $result );
	}

	// -----------------------------------------------------------------------
	// inject_affiliate_links — enabled but no singular post
	// -----------------------------------------------------------------------

	public function test_inject_affiliate_links_returns_content_unchanged_when_not_singular(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );
		$GLOBALS['_is_singular'] = false;

		$content = '<p>Test content</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );

		$this->assertSame( $content, $result );
	}

	// -----------------------------------------------------------------------
	// Manual link mapping (via OPTION_MANUAL)
	// -----------------------------------------------------------------------

	public function test_manual_link_injection_when_keyword_found(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );

		$manual = [
			[ 'keyword' => 'Samsung Galaxy', 'url' => 'https://aff.example.com/samsung', 'label' => 'Samsung Galaxy' ],
		];
		update_option( AffiliateDiscovery::OPTION_MANUAL, wp_json_encode( $manual ) );

		// Simulate singular post context.
		$GLOBALS['_is_singular']    = true;
		$GLOBALS['_current_post_id'] = 1;

		$post = new \stdClass();
		$post->post_content = 'Samsung Galaxy is great. Buy it today.';
		$GLOBALS['_posts'][1] = $post;

		update_post_meta( 1, AffiliateDiscovery::META_LINKS, [
			[ 'keyword' => 'Samsung Galaxy', 'url' => 'https://aff.example.com/samsung', 'label' => 'Samsung Galaxy' ],
		] );

		$content = '<p>Samsung Galaxy is the best phone.</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );

		$this->assertStringContainsString( 'rel="nofollow sponsored"', $result );
	}

	// -----------------------------------------------------------------------
	// discover_for_post — enabled with manual links
	// -----------------------------------------------------------------------

	public function test_discover_for_post_returns_array_when_enabled(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );

		$post = new \stdClass();
		$post->post_content = 'Apple iPhone is great.';
		$GLOBALS['_posts'][1] = $post;

		$links = $this->discovery->discover_for_post( 1 );

		$this->assertIsArray( $links );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->discovery->register();
	}
}
