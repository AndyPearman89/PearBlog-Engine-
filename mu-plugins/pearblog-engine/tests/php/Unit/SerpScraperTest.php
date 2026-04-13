<?php
/**
 * Unit tests for SerpScraper.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\SerpScraper;

class SerpScraperTest extends TestCase {

	private SerpScraper $scraper;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$this->scraper = new SerpScraper();
	}

	// -----------------------------------------------------------------------
	// is_configured
	// -----------------------------------------------------------------------

	public function test_not_configured_without_api_key(): void {
		$this->assertFalse( $this->scraper->is_configured() );
	}

	public function test_configured_when_api_key_set(): void {
		update_option( SerpScraper::OPTION_API_KEY, 'my-api-key' );
		$this->assertTrue( $this->scraper->is_configured() );
	}

	// -----------------------------------------------------------------------
	// Configuration accessors
	// -----------------------------------------------------------------------

	public function test_default_provider(): void {
		$this->assertSame( SerpScraper::DEFAULT_PROVIDER, $this->scraper->get_provider() );
	}

	public function test_custom_provider(): void {
		update_option( SerpScraper::OPTION_PROVIDER, 'serper' );
		$this->assertSame( 'serper', $this->scraper->get_provider() );
	}

	public function test_default_results_count(): void {
		$this->assertSame( SerpScraper::DEFAULT_RESULTS_COUNT, $this->scraper->get_results_count() );
	}

	public function test_custom_results_count(): void {
		update_option( SerpScraper::OPTION_RESULTS_COUNT, 5 );
		$this->assertSame( 5, $this->scraper->get_results_count() );
	}

	public function test_default_cache_ttl(): void {
		$this->assertSame( SerpScraper::DEFAULT_CACHE_TTL, $this->scraper->get_cache_ttl() );
	}

	public function test_default_country(): void {
		$this->assertSame( SerpScraper::DEFAULT_COUNTRY, $this->scraper->get_country() );
	}

	public function test_default_language(): void {
		$this->assertSame( SerpScraper::DEFAULT_LANGUAGE, $this->scraper->get_language() );
	}

	// -----------------------------------------------------------------------
	// fetch — not configured / empty keyword
	// -----------------------------------------------------------------------

	public function test_fetch_returns_empty_when_not_configured(): void {
		$this->assertSame( [], $this->scraper->fetch( 'best SEO tips' ) );
	}

	public function test_fetch_returns_empty_for_empty_keyword(): void {
		update_option( SerpScraper::OPTION_API_KEY, 'key' );
		$this->assertSame( [], $this->scraper->fetch( '' ) );
		$this->assertSame( [], $this->scraper->fetch( '   ' ) );
	}

	public function test_fetch_returns_cached_value(): void {
		update_option( SerpScraper::OPTION_API_KEY, 'key' );
		$keyword   = 'best SEO tips';
		$cache_key = $this->scraper->cache_key( $keyword );
		$GLOBALS['_transients'][ $cache_key ] = [
			[ 'title' => 'Cached Result', 'url' => 'https://example.com', 'snippet' => 'Cached.' ],
		];

		$results = $this->scraper->fetch( $keyword );
		$this->assertCount( 1, $results );
		$this->assertSame( 'Cached Result', $results[0]['title'] );
	}

	// -----------------------------------------------------------------------
	// parse_valueserp
	// -----------------------------------------------------------------------

	public function test_parse_valueserp_returns_title_url_snippet(): void {
		$data = [
			'organic_results' => [
				[
					'title'   => 'Best SEO Tips 2026',
					'link'    => 'https://example.com/seo-tips',
					'snippet' => 'Learn the best SEO tips.',
				],
				[
					'title'   => 'Advanced SEO Guide',
					'link'    => 'https://example.com/advanced-seo',
					'snippet' => 'Advanced techniques.',
				],
			],
		];

		$results = $this->scraper->parse_valueserp( $data );
		$this->assertCount( 2, $results );
		$this->assertSame( 'Best SEO Tips 2026', $results[0]['title'] );
		$this->assertSame( 'https://example.com/seo-tips', $results[0]['url'] );
		$this->assertSame( 'Learn the best SEO tips.', $results[0]['snippet'] );
	}

	public function test_parse_valueserp_skips_entries_missing_title_or_url(): void {
		$data = [
			'organic_results' => [
				[ 'title' => '', 'link' => 'https://example.com', 'snippet' => '' ],
				[ 'title' => 'Valid Title', 'link' => '', 'snippet' => '' ],
				[ 'title' => 'Good Result', 'link' => 'https://example.com/good', 'snippet' => 'Ok.' ],
			],
		];

		$results = $this->scraper->parse_valueserp( $data );
		$this->assertCount( 1, $results );
		$this->assertSame( 'Good Result', $results[0]['title'] );
	}

	public function test_parse_valueserp_respects_results_limit(): void {
		update_option( SerpScraper::OPTION_RESULTS_COUNT, 2 );
		$data = [
			'organic_results' => array_map( fn( $i ) => [
				'title'   => "Result {$i}",
				'link'    => "https://example.com/{$i}",
				'snippet' => "Snippet {$i}",
			], range( 1, 5 ) ),
		];

		$results = $this->scraper->parse_valueserp( $data );
		$this->assertCount( 2, $results );
	}

	public function test_parse_valueserp_empty_data(): void {
		$this->assertSame( [], $this->scraper->parse_valueserp( [] ) );
	}

	// -----------------------------------------------------------------------
	// parse_serper
	// -----------------------------------------------------------------------

	public function test_parse_serper_maps_organic_key(): void {
		$data = [
			'organic' => [
				[
					'title'   => 'Serper Result',
					'link'    => 'https://serper-example.com',
					'snippet' => 'From Serper.',
				],
			],
		];

		$results = $this->scraper->parse_serper( $data );
		$this->assertCount( 1, $results );
		$this->assertSame( 'Serper Result', $results[0]['title'] );
	}

	public function test_parse_serper_skips_entries_without_title_or_url(): void {
		$data = [
			'organic' => [
				[ 'title' => '', 'link' => 'https://example.com', 'snippet' => '' ],
				[ 'title' => 'Valid', 'link' => '', 'snippet' => '' ],
				[ 'title' => 'OK', 'link' => 'https://example.com/ok', 'snippet' => 'Snippet.' ],
			],
		];

		$results = $this->scraper->parse_serper( $data );
		$this->assertCount( 1, $results );
	}

	public function test_parse_serper_respects_results_limit(): void {
		update_option( SerpScraper::OPTION_RESULTS_COUNT, 1 );
		$data = [
			'organic' => [
				[ 'title' => 'A', 'link' => 'https://a.com', 'snippet' => '' ],
				[ 'title' => 'B', 'link' => 'https://b.com', 'snippet' => '' ],
			],
		];

		$results = $this->scraper->parse_serper( $data );
		$this->assertCount( 1, $results );
	}

	// -----------------------------------------------------------------------
	// fetch_titles / fetch_urls
	// -----------------------------------------------------------------------

	public function test_fetch_titles_returns_empty_when_not_configured(): void {
		$this->assertSame( [], $this->scraper->fetch_titles( 'some keyword' ) );
	}

	public function test_fetch_urls_returns_empty_when_not_configured(): void {
		$this->assertSame( [], $this->scraper->fetch_urls( 'some keyword' ) );
	}

	// -----------------------------------------------------------------------
	// cache_key
	// -----------------------------------------------------------------------

	public function test_cache_key_differs_per_keyword(): void {
		$key1 = $this->scraper->cache_key( 'keyword one' );
		$key2 = $this->scraper->cache_key( 'keyword two' );
		$this->assertNotSame( $key1, $key2 );
	}

	public function test_cache_key_consistent_for_same_keyword(): void {
		$this->assertSame(
			$this->scraper->cache_key( 'test keyword' ),
			$this->scraper->cache_key( 'test keyword' )
		);
	}

	public function test_cache_key_differs_per_provider(): void {
		update_option( SerpScraper::OPTION_PROVIDER, 'valueserp' );
		$key1 = $this->scraper->cache_key( 'test' );

		update_option( SerpScraper::OPTION_PROVIDER, 'serper' );
		$key2 = $this->scraper->cache_key( 'test' );

		$this->assertNotSame( $key1, $key2 );
	}
}
