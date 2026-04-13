<?php
/**
 * Unit tests for GA4Client.
 *
 * All HTTP calls are intercepted by the wp_remote_post / wp_remote_get stubs
 * in bootstrap.php; we extend those via $GLOBALS['_ga4_response'] so individual
 * tests can return specific API payloads.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Analytics\GA4Client;

/**
 * Override wp_remote_post in the global namespace so GA4Client tests can
 * inject custom API responses without modifying production code.
 */

class GA4ClientTest extends TestCase {

	private GA4Client $client;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_ga4_response'] = null; // Custom response override.
		$this->client = new GA4Client();
	}

	// -----------------------------------------------------------------------
	// is_configured
	// -----------------------------------------------------------------------

	public function test_not_configured_when_no_options(): void {
		$this->assertFalse( $this->client->is_configured() );
	}

	public function test_not_configured_when_missing_credentials(): void {
		update_option( GA4Client::OPTION_PROPERTY_ID, '12345678' );
		$this->assertFalse( $this->client->is_configured() );
	}

	public function test_not_configured_when_missing_property_id(): void {
		update_option( GA4Client::OPTION_CREDENTIALS, '{"private_key":"x","client_email":"e@e.com"}' );
		$this->assertFalse( $this->client->is_configured() );
	}

	public function test_configured_when_both_options_set(): void {
		update_option( GA4Client::OPTION_PROPERTY_ID, '12345678' );
		update_option( GA4Client::OPTION_CREDENTIALS, '{"private_key":"x","client_email":"e@e.com"}' );
		$this->assertTrue( $this->client->is_configured() );
	}

	// -----------------------------------------------------------------------
	// get_property_id / get_cache_ttl
	// -----------------------------------------------------------------------

	public function test_get_property_id(): void {
		update_option( GA4Client::OPTION_PROPERTY_ID, '987654321' );
		$this->assertSame( '987654321', $this->client->get_property_id() );
	}

	public function test_default_cache_ttl(): void {
		$this->assertSame( GA4Client::DEFAULT_CACHE_TTL, $this->client->get_cache_ttl() );
	}

	public function test_custom_cache_ttl(): void {
		update_option( GA4Client::OPTION_CACHE_TTL, 7200 );
		$this->assertSame( 7200, $this->client->get_cache_ttl() );
	}

	// -----------------------------------------------------------------------
	// run_report — not configured returns []
	// -----------------------------------------------------------------------

	public function test_run_report_returns_empty_when_not_configured(): void {
		$result = $this->client->run_report( [ 'name' => 'pagePath' ], [ 'name' => 'screenPageViews' ] );
		$this->assertSame( [], $result );
	}

	// -----------------------------------------------------------------------
	// extract_metric_total
	// -----------------------------------------------------------------------

	public function test_extract_metric_total_sums_rows(): void {
		$report = [
			'metricHeaders' => [ [ 'name' => 'screenPageViews', 'type' => 'TYPE_INTEGER' ] ],
			'rows' => [
				[ 'dimensionValues' => [ [ 'value' => '/post-1/' ] ], 'metricValues' => [ [ 'value' => '150' ] ] ],
				[ 'dimensionValues' => [ [ 'value' => '/post-2/' ] ], 'metricValues' => [ [ 'value' => '75' ] ] ],
			],
		];

		$total = $this->client->extract_metric_total( $report, 'screenPageViews' );
		$this->assertSame( 225, $total );
	}

	public function test_extract_metric_total_empty_report(): void {
		$this->assertSame( 0, $this->client->extract_metric_total( [], 'screenPageViews' ) );
	}

	public function test_extract_metric_total_no_rows(): void {
		$report = [
			'metricHeaders' => [ [ 'name' => 'screenPageViews', 'type' => 'TYPE_INTEGER' ] ],
		];
		$this->assertSame( 0, $this->client->extract_metric_total( $report, 'screenPageViews' ) );
	}

	// -----------------------------------------------------------------------
	// extract_rows
	// -----------------------------------------------------------------------

	public function test_extract_rows_returns_path_views_pairs(): void {
		$report = [
			'metricHeaders' => [ [ 'name' => 'screenPageViews', 'type' => 'TYPE_INTEGER' ] ],
			'rows' => [
				[ 'dimensionValues' => [ [ 'value' => '/article-one/' ] ], 'metricValues' => [ [ 'value' => '500' ] ] ],
				[ 'dimensionValues' => [ [ 'value' => '/article-two/' ] ], 'metricValues' => [ [ 'value' => '300' ] ] ],
			],
		];

		$rows = $this->client->extract_rows( $report );
		$this->assertCount( 2, $rows );
		$this->assertSame( '/article-one/', $rows[0]['path'] );
		$this->assertSame( 500, $rows[0]['views'] );
		$this->assertSame( '/article-two/', $rows[1]['path'] );
	}

	public function test_extract_rows_skips_empty_path(): void {
		$report = [
			'metricHeaders' => [ [ 'name' => 'screenPageViews', 'type' => 'TYPE_INTEGER' ] ],
			'rows' => [
				[ 'dimensionValues' => [ [ 'value' => '' ] ], 'metricValues' => [ [ 'value' => '100' ] ] ],
				[ 'dimensionValues' => [ [ 'value' => '/valid/' ] ], 'metricValues' => [ [ 'value' => '200' ] ] ],
			],
		];

		$rows = $this->client->extract_rows( $report );
		$this->assertCount( 1, $rows );
		$this->assertSame( '/valid/', $rows[0]['path'] );
	}

	// -----------------------------------------------------------------------
	// get_post_views — cached path
	// -----------------------------------------------------------------------

	public function test_get_post_views_returns_zero_when_not_configured(): void {
		$views = $this->client->get_post_views( '/my-article/' );
		$this->assertSame( 0, $views );
	}

	public function test_get_post_views_returns_cached_value(): void {
		$cache_key = 'pearblog_ga4_views_' . md5( '/my-post/30daysAgotoday' );
		$GLOBALS['_transients'][ $cache_key ] = 999;

		// Even without API config, cached value is returned.
		$views = $this->client->get_post_views( '/my-post/' );
		$this->assertSame( 999, $views );
	}

	// -----------------------------------------------------------------------
	// get_access_token — cached
	// -----------------------------------------------------------------------

	public function test_get_access_token_returns_cached(): void {
		$GLOBALS['_transients'][ GA4Client::TRANSIENT_TOKEN ] = 'cached-token-xyz';
		$this->assertSame( 'cached-token-xyz', $this->client->get_access_token() );
	}

	public function test_get_access_token_empty_when_no_credentials(): void {
		$this->assertSame( '', $this->client->get_access_token() );
	}
}
