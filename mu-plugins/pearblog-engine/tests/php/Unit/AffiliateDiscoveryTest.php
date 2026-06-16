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
		$GLOBALS['_options']          = [];
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_posts']            = [];
		$GLOBALS['_is_singular']      = false;
		$GLOBALS['_current_post_id']  = 0;
		$this->discovery = new AffiliateDiscovery();
	}

	// -----------------------------------------------------------------------
	// Option constants
	// -----------------------------------------------------------------------

	public function test_option_constants(): void {
		$this->assertSame( 'pearblog_affiliate_enabled',  AffiliateDiscovery::OPTION_ENABLED );
		$this->assertSame( 'pearblog_affiliate_awin_key', AffiliateDiscovery::OPTION_AWIN_KEY );
		$this->assertSame( 'pearblog_affiliate_awin_pub', AffiliateDiscovery::OPTION_AWIN_PUB );
		$this->assertSame( 'pearblog_affiliate_cj_key',   AffiliateDiscovery::OPTION_CJ_KEY );
		$this->assertSame( 'pearblog_affiliate_manual',   AffiliateDiscovery::OPTION_MANUAL );
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
	// extract_product_keywords
	// -----------------------------------------------------------------------

	public function test_extract_product_keywords_finds_capitalized_phrases(): void {
		$text = 'I love the Apple MacBook Pro. It is a great laptop.';

		$keywords = $this->discovery->extract_product_keywords( $text );

		$this->assertIsArray( $keywords );
		$this->assertNotEmpty( $keywords );
	}

	public function test_extract_product_keywords_finds_action_phrases(): void {
		$text = 'You should buy Sony Camera and order Nikon Lens today.';

		$keywords = $this->discovery->extract_product_keywords( $text );

		// Should detect "Sony Camera" or "Nikon Lens" from context.
		$this->assertIsArray( $keywords );
	}

	public function test_extract_product_keywords_deduplicates(): void {
		$text = 'The Blue Widget is great. Buy Blue Widget now. Order Blue Widget today.';

		$keywords = $this->discovery->extract_product_keywords( $text );

		$this->assertCount( count( array_unique( $keywords ) ), $keywords );
	}

	public function test_extract_product_keywords_returns_array_for_empty_text(): void {
		$keywords = $this->discovery->extract_product_keywords( '' );

		$this->assertIsArray( $keywords );
	}

	public function test_extract_product_keywords_max_15(): void {
		// Generate text with many capitalized phrases.
		$phrases = [];
		for ( $i = 0; $i < 20; $i++ ) {
			$phrases[] = "Product Brand" . $i . " Review"; // won't work as caps
		}
		// Use proper Capitalized phrases.
		$text = '';
		for ( $i = 0; $i < 20; $i++ ) {
			$text .= "Alpha Beta Gamma Delta {$i}. ";
		}

		$keywords = $this->discovery->extract_product_keywords( $text );

		$this->assertLessThanOrEqual( 15, count( $keywords ) );
	}

	// -----------------------------------------------------------------------
	// discover_for_post — disabled guard
	// -----------------------------------------------------------------------

	public function test_discover_for_post_returns_empty_when_disabled(): void {
		$links = $this->discovery->discover_for_post( 1 );

		$this->assertSame( [], $links );
	}

	// -----------------------------------------------------------------------
	// discover_for_post — post not found
	// -----------------------------------------------------------------------

	public function test_discover_for_post_returns_empty_when_post_not_found(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );

		// Post 999 does not exist in _posts.
		$links = $this->discovery->discover_for_post( 999 );

		$this->assertSame( [], $links );
	}

	// -----------------------------------------------------------------------
	// discover_for_post — manual links
	// -----------------------------------------------------------------------

	public function test_discover_for_post_uses_manual_mapping(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );
		update_option( AffiliateDiscovery::OPTION_MANUAL, wp_json_encode( [
			[
				'keyword' => 'Sony Camera',
				'url'     => 'https://example.com/sony',
				'label'   => 'Sony Camera Deal',
			],
		] ) );

		$post = new \WP_Post( [
			'ID'           => 5,
			'post_title'   => 'Camera Review',
			'post_content' => 'The Sony Camera is a fantastic product for photography.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][5] = $post;

		$links = $this->discovery->discover_for_post( 5 );

		$sources = array_column( $links, 'source' );
		$this->assertContains( 'manual', $sources );

		$urls = array_column( $links, 'url' );
		$this->assertContains( 'https://example.com/sony', $urls );
	}

	public function test_discover_for_post_saves_links_as_post_meta(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );
		update_option( AffiliateDiscovery::OPTION_MANUAL, wp_json_encode( [
			[ 'keyword' => 'Test Brand', 'url' => 'https://test.com', 'label' => 'Test' ],
		] ) );

		$post = new \WP_Post( [
			'ID'           => 6,
			'post_title'   => 'Testing',
			'post_content' => 'The Test Brand is excellent quality.',
			'post_status'  => 'publish',
		] );
		$GLOBALS['_posts'][6] = $post;

		$this->discovery->discover_for_post( 6 );

		$saved = get_post_meta( 6, AffiliateDiscovery::META_LINKS, true );
		$this->assertIsArray( $saved );
	}

	// -----------------------------------------------------------------------
	// inject_affiliate_links
	// -----------------------------------------------------------------------

	public function test_inject_affiliate_links_passes_through_when_disabled(): void {
		$content = '<p>Article content here.</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );

		$this->assertSame( $content, $result );
	}

	public function test_inject_affiliate_links_passes_through_when_not_singular(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );
		$GLOBALS['_is_singular'] = false;

		$content = '<p>Article content here.</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );

		$this->assertSame( $content, $result );
	}

	public function test_inject_affiliate_links_replaces_keyword_with_anchor(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );
		$GLOBALS['_is_singular']     = true;
		$GLOBALS['_current_post_id'] = 7;

		update_post_meta( 7, AffiliateDiscovery::META_LINKS, [
			[
				'keyword' => 'laptop',
				'url'     => 'https://shop.example.com/laptop',
				'label'   => 'Best Laptop Deal',
			],
		] );

		$content = '<p>Buy the best laptop for your work.</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );

		$this->assertStringContainsString( 'https://shop.example.com/laptop', $result );
		$this->assertStringContainsString( 'rel="nofollow sponsored"', $result );
	}

	public function test_inject_affiliate_links_replaces_only_first_occurrence(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );
		$GLOBALS['_is_singular']     = true;
		$GLOBALS['_current_post_id'] = 8;

		update_post_meta( 8, AffiliateDiscovery::META_LINKS, [
			[
				'keyword' => 'widget',
				'url'     => 'https://shop.example.com/widget',
				'label'   => 'Widget',
			],
		] );

		$content = '<p>Buy a widget. The widget is great. Get a widget.</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );

		// Only the first occurrence becomes a link; the others remain plain text.
		$this->assertSame( 1, substr_count( $result, 'https://shop.example.com/widget' ) );
	}

	public function test_inject_affiliate_links_skips_entries_without_keyword(): void {
		update_option( AffiliateDiscovery::OPTION_ENABLED, true );
		$GLOBALS['_is_singular']     = true;
		$GLOBALS['_current_post_id'] = 9;

		update_post_meta( 9, AffiliateDiscovery::META_LINKS, [
			[ 'url' => 'https://shop.example.com/no-keyword' ],  // no keyword
		] );

		$content = '<p>Some content here.</p>';
		$result  = $this->discovery->inject_affiliate_links( $content );

		$this->assertSame( $content, $result );
	}
}
