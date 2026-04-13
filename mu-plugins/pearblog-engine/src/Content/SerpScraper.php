<?php
/**
 * SERP Scraper — fetches competitor article titles and URLs for a given keyword.
 *
 * Supports two data sources, selected via the `pearblog_serp_provider` option:
 *   - "valueserp"  – Value SERP API (https://valueserp.com) – recommended (free tier available)
 *   - "serper"     – Serper.dev API (https://serper.dev) – alternative
 *
 * On success the results are cached as a WP transient so that repeated calls
 * for the same keyword don't burn API quota.
 *
 * Configuration WP options:
 *   pearblog_serp_provider        – "valueserp" (default) | "serper"
 *   pearblog_serp_api_key         – API key for the chosen provider
 *   pearblog_serp_results_count   – Max organic results to fetch (default 10)
 *   pearblog_serp_cache_ttl       – Transient TTL in seconds (default 86400 = 24 h)
 *   pearblog_serp_country         – ISO country code, e.g. "us" (default "us")
 *   pearblog_serp_language        – Language code, e.g. "en" (default "en")
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Retrieves top SERP results for a keyword using a configurable third-party API.
 */
class SerpScraper {

	// -----------------------------------------------------------------------
	// Option keys
	// -----------------------------------------------------------------------

	public const OPTION_PROVIDER       = 'pearblog_serp_provider';
	public const OPTION_API_KEY        = 'pearblog_serp_api_key';
	public const OPTION_RESULTS_COUNT  = 'pearblog_serp_results_count';
	public const OPTION_CACHE_TTL      = 'pearblog_serp_cache_ttl';
	public const OPTION_COUNTRY        = 'pearblog_serp_country';
	public const OPTION_LANGUAGE       = 'pearblog_serp_language';

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	public const DEFAULT_PROVIDER       = 'valueserp';
	public const DEFAULT_RESULTS_COUNT  = 10;
	public const DEFAULT_CACHE_TTL      = 86400;
	public const DEFAULT_COUNTRY        = 'us';
	public const DEFAULT_LANGUAGE       = 'en';

	// -----------------------------------------------------------------------
	// Provider API endpoints
	// -----------------------------------------------------------------------

	private const ENDPOINT_VALUESERP = 'https://api.valueserp.com/search';
	private const ENDPOINT_SERPER    = 'https://google.serper.dev/search';

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Check whether the scraper is configured (API key + provider set).
	 */
	public function is_configured(): bool {
		return '' !== $this->get_api_key();
	}

	/**
	 * Fetch top organic SERP results for the given keyword.
	 *
	 * @param string $keyword  Search query / keyword.
	 * @return array<int, array{title: string, url: string, snippet: string}>  Empty on error/unconfigured.
	 */
	public function fetch( string $keyword ): array {
		if ( '' === trim( $keyword ) || ! $this->is_configured() ) {
			return [];
		}

		$cache_key = $this->cache_key( $keyword );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (array) $cached;
		}

		$results = $this->call_provider( $keyword );

		if ( ! empty( $results ) ) {
			set_transient( $cache_key, $results, $this->get_cache_ttl() );
		}

		return $results;
	}

	/**
	 * Fetch SERP data and return only the titles (useful for CompetitiveGapEngine).
	 *
	 * @param string $keyword
	 * @return string[]
	 */
	public function fetch_titles( string $keyword ): array {
		return array_column( $this->fetch( $keyword ), 'title' );
	}

	/**
	 * Fetch SERP data and return only the URLs.
	 *
	 * @param string $keyword
	 * @return string[]
	 */
	public function fetch_urls( string $keyword ): array {
		return array_column( $this->fetch( $keyword ), 'url' );
	}

	// -----------------------------------------------------------------------
	// Configuration accessors
	// -----------------------------------------------------------------------

	public function get_provider(): string {
		return (string) get_option( self::OPTION_PROVIDER, self::DEFAULT_PROVIDER );
	}

	public function get_api_key(): string {
		return (string) get_option( self::OPTION_API_KEY, '' );
	}

	public function get_results_count(): int {
		return (int) get_option( self::OPTION_RESULTS_COUNT, self::DEFAULT_RESULTS_COUNT );
	}

	public function get_cache_ttl(): int {
		return (int) get_option( self::OPTION_CACHE_TTL, self::DEFAULT_CACHE_TTL );
	}

	public function get_country(): string {
		return (string) get_option( self::OPTION_COUNTRY, self::DEFAULT_COUNTRY );
	}

	public function get_language(): string {
		return (string) get_option( self::OPTION_LANGUAGE, self::DEFAULT_LANGUAGE );
	}

	// -----------------------------------------------------------------------
	// Provider dispatch
	// -----------------------------------------------------------------------

	/**
	 * Call the configured SERP provider API.
	 *
	 * @param string $keyword
	 * @return array<int, array{title: string, url: string, snippet: string}>
	 */
	public function call_provider( string $keyword ): array {
		return match ( $this->get_provider() ) {
			'serper'    => $this->call_serper( $keyword ),
			default     => $this->call_valueserp( $keyword ),
		};
	}

	// -----------------------------------------------------------------------
	// Value SERP
	// -----------------------------------------------------------------------

	/**
	 * @return array<int, array{title: string, url: string, snippet: string}>
	 */
	private function call_valueserp( string $keyword ): array {
		$url = add_query_arg( [
			'api_key'  => $this->get_api_key(),
			'q'        => rawurlencode( $keyword ),
			'gl'       => $this->get_country(),
			'hl'       => $this->get_language(),
			'num'      => $this->get_results_count(),
			'output'   => 'json',
		], self::ENDPOINT_VALUESERP );

		$response = wp_remote_get( $url, [ 'timeout' => 15 ] );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return [];
		}

		$body = is_array( $response ) ? ( (string) ( $response['body'] ?? '' ) ) : '';
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return [];
		}

		return $this->parse_valueserp( $data );
	}

	/**
	 * @param array $data  Decoded Value SERP JSON.
	 * @return array<int, array{title: string, url: string, snippet: string}>
	 */
	public function parse_valueserp( array $data ): array {
		$results = [];
		$limit   = $this->get_results_count();

		foreach ( $data['organic_results'] ?? [] as $row ) {
			if ( count( $results ) >= $limit ) {
				break;
			}
			$title   = (string) ( $row['title']   ?? '' );
			$url     = (string) ( $row['link']    ?? '' );
			$snippet = (string) ( $row['snippet'] ?? '' );

			if ( '' !== $title && '' !== $url ) {
				$results[] = compact( 'title', 'url', 'snippet' );
			}
		}

		return $results;
	}

	// -----------------------------------------------------------------------
	// Serper.dev
	// -----------------------------------------------------------------------

	/**
	 * @return array<int, array{title: string, url: string, snippet: string}>
	 */
	private function call_serper( string $keyword ): array {
		$response = wp_remote_post( self::ENDPOINT_SERPER, [
			'headers' => [
				'X-API-KEY'    => $this->get_api_key(),
				'Content-Type' => 'application/json',
			],
			'body'    => (string) wp_json_encode( [
				'q'  => $keyword,
				'gl' => $this->get_country(),
				'hl' => $this->get_language(),
				'num'=> $this->get_results_count(),
			] ),
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return [];
		}

		$body = is_array( $response ) ? ( (string) ( $response['body'] ?? '' ) ) : '';
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return [];
		}

		return $this->parse_serper( $data );
	}

	/**
	 * @param array $data  Decoded Serper JSON.
	 * @return array<int, array{title: string, url: string, snippet: string}>
	 */
	public function parse_serper( array $data ): array {
		$results = [];
		$limit   = $this->get_results_count();

		foreach ( $data['organic'] ?? [] as $row ) {
			if ( count( $results ) >= $limit ) {
				break;
			}
			$title   = (string) ( $row['title']   ?? '' );
			$url     = (string) ( $row['link']    ?? '' );
			$snippet = (string) ( $row['snippet'] ?? '' );

			if ( '' !== $title && '' !== $url ) {
				$results[] = compact( 'title', 'url', 'snippet' );
			}
		}

		return $results;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Transient cache key for a keyword.
	 */
	public function cache_key( string $keyword ): string {
		return 'pearblog_serp_' . md5( $this->get_provider() . $keyword );
	}
}
