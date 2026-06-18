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
		$GLOBALS['_is_singular_post'] = false;
		$this->discovery = new AffiliateDiscovery();
	}

	// -----------------------------------------------------------------------
	// Option / meta constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant(): void {
		$this->assertSame( 'pearblog_affiliate_enabled', AffiliateDiscovery::OPTION_ENABLED );
	}

	public function test_option_awin_key_constant(): void {
		$this->assertSame( 'pearblog_affiliate_awin_key', AffiliateDiscovery::OPTION_AWIN_KEY );
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
		$GLOBALS['_options'][ AffiliateDiscovery::OPTION_ENABLED ] = true;
		$this->assertTrue( $this->discovery->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// extract_product_keywords
	// -----------------------------------------------------------------------

	public function test_extract_product_keywords_returns_empty_for_empty_text(): void {
		$keywords = $this->discovery->extract_product_keywords( '' );
		$this->assertSame( [], $keywords );
	}

	public function test_extract_product_keywords_finds_capitalized_phrases(): void {
		$keywords = $this->discovery->extract_product_keywords( 'Buy Samsung Galaxy smartphone today.' );
		$this->assertNotEmpty( $keywords );
	}

	public function test_extract_product_keywords_finds_buy_pattern(): void {
		$keywords = $this->discovery->extract_product_keywords( 'You should buy laptop for work.' );
		$this->assertNotEmpty( $keywords );
	}

	public function test_extract_product_keywords_deduplicates(): void {
		$keywords = $this->discovery->extract_product_keywords( 'Samsung Galaxy Samsung Galaxy Samsung Galaxy' );
		$unique = array_unique( $keywords );
		$this->assertCount( count( $unique ), $keywords );
	}

	public function test_extract_product_keywords_limits_to_15(): void {
		// Generate text with many product names.
		$text = '';
		for ( $i = 0; $i < 20; $i++ ) {
			$text .= "Buy Product Number{$i} today. ";
		}
		$keywords = $this->discovery->extract_product_keywords( $text );
		$this->assertLessThanOrEqual( 15, count( $keywords ) );
	}

	public function test_extract_product_keywords_filters_short_keywords(): void {
		// Short words (≤3 chars) should be excluded.
		$keywords = $this->discovery->extract_product_keywords( 'Buy Abc today.' );
		foreach ( $keywords as $keyword ) {
			$this->assertGreaterThan( 3, strlen( $keyword ) );
		}
	}

	// -----------------------------------------------------------------------
	// inject_affiliate_links
	// -----------------------------------------------------------------------

	public function test_inject_affiliate_links_returns_content_unchanged_when_disabled(): void {
		$content = '<p>Buy Samsung Galaxy now.</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );
		$this->assertSame( $content, $result );
	}

	public function test_inject_affiliate_links_skips_when_not_singular_post(): void {
		$GLOBALS['_options'][ AffiliateDiscovery::OPTION_ENABLED ] = true;
		$GLOBALS['_is_singular'] = false;
		$content = '<p>Buy Samsung Galaxy now.</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );
		$this->assertSame( $content, $result );
	}

	public function test_inject_affiliate_links_injects_link_for_matched_keyword(): void {
		$GLOBALS['_options'][ AffiliateDiscovery::OPTION_ENABLED ] = true;
		$GLOBALS['_is_singular'] = true;
		$GLOBALS['_current_post_id'] = 1;
		// Set up affiliate links in post meta.
		$GLOBALS['_post_meta'][1][ AffiliateDiscovery::META_LINKS ] = [
			[
				[
					'keyword' => 'Samsung',
					'url'     => 'https://awin.com/123',
					'label'   => 'Samsung',
					'source'  => 'manual',
				],
			],
		];
		$content = '<p>Buy Samsung smartphone today.</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );
		$this->assertStringContainsString( 'awin.com', $result );
	}

	// -----------------------------------------------------------------------
	// discover_for_post
	// -----------------------------------------------------------------------

	public function test_discover_for_post_returns_empty_when_disabled(): void {
		$links = $this->discovery->discover_for_post( 1 );
		$this->assertSame( [], $links );
	}

	public function test_discover_for_post_returns_empty_when_post_not_found(): void {
		$GLOBALS['_options'][ AffiliateDiscovery::OPTION_ENABLED ] = true;
		// No post in globals.
		$links = $this->discovery->discover_for_post( 9999 );
		$this->assertSame( [], $links );
	}

	public function test_discover_for_post_uses_manual_links(): void {
		$GLOBALS['_options'][ AffiliateDiscovery::OPTION_ENABLED ] = true;
		$GLOBALS['_options'][ AffiliateDiscovery::OPTION_MANUAL ] = json_encode( [
			[ 'keyword' => 'Apple', 'url' => 'https://partner.example.com/apple', 'label' => 'Apple' ],
		] );
		$post                = new \stdClass();
		$post->ID            = 10;
		$post->post_content  = 'Apple MacBook Pro is a great laptop. Apple makes it. Buy Apple today.';
		$GLOBALS['_posts'][10] = $post;
		$links = $this->discovery->discover_for_post( 10 );
		$this->assertNotEmpty( $links );
		$this->assertSame( 'manual', $links[0]['source'] );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_content_filter(): void {
		$this->discovery->register();
		$this->assertTrue( isset( $GLOBALS['_filters']['the_content'] ) );
	}

	public function test_register_adds_pipeline_action(): void {
		$this->discovery->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['pearblog_pipeline_completed'] ) );
	}
}
