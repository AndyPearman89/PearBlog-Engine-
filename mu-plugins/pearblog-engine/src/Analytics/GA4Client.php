<?php
/**
 * GA4 Client — fetches traffic and revenue data from the Google Analytics 4 Data API.
 *
 * Uses the GA4 Data API v1beta (runReport endpoint) authenticated via a
 * service-account JSON key stored in a WordPress option.
 *
 * Configuration options:
 *   pearblog_ga4_property_id     – GA4 property ID (e.g. "12345678")
 *   pearblog_ga4_credentials     – Service-account JSON key (raw JSON string)
 *   pearblog_ga4_cache_ttl       – Cache TTL in seconds (default 3600)
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

/**
 * Retrieves page-level and site-level traffic metrics from Google Analytics 4.
 */
class GA4Client {

	/** WP option keys. */
	public const OPTION_PROPERTY_ID  = 'pearblog_ga4_property_id';
	public const OPTION_CREDENTIALS  = 'pearblog_ga4_credentials';
	public const OPTION_CACHE_TTL    = 'pearblog_ga4_cache_ttl';

	/** Default cache TTL (1 hour). */
	public const DEFAULT_CACHE_TTL = 3600;

	/** GA4 Data API base URL. */
	private const API_URL = 'https://analyticsdata.googleapis.com/v1beta/properties/%s:runReport';

	/** OAuth token endpoint. */
	private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

	/** Access token transient key. */
	public const TRANSIENT_TOKEN = 'pearblog_ga4_access_token';

	// -----------------------------------------------------------------------
	// Configuration helpers
	// -----------------------------------------------------------------------

	/**
	 * Whether the client is configured (property ID + credentials present).
	 */
	public function is_configured(): bool {
		$pid   = (string) get_option( self::OPTION_PROPERTY_ID, '' );
		$creds = (string) get_option( self::OPTION_CREDENTIALS, '' );
		return '' !== $pid && '' !== $creds;
	}

	/**
	 * Get the configured GA4 property ID.
	 */
	public function get_property_id(): string {
		return (string) get_option( self::OPTION_PROPERTY_ID, '' );
	}

	/**
	 * Get cache TTL in seconds.
	 */
	public function get_cache_ttl(): int {
		return (int) get_option( self::OPTION_CACHE_TTL, self::DEFAULT_CACHE_TTL );
	}

	// -----------------------------------------------------------------------
	// Public data methods
	// -----------------------------------------------------------------------

	/**
	 * Get page views for a specific URL path over a date range.
	 *
	 * @param string $url_path   Relative URL path, e.g. "/my-post-slug/".
	 * @param string $start_date ISO date string, e.g. "2026-01-01" or "30daysAgo".
	 * @param string $end_date   ISO date string, e.g. "today".
	 * @return int               Total screen_page_views for the path.
	 */
	public function get_post_views( string $url_path, string $start_date = '30daysAgo', string $end_date = 'today' ): int {
		$cache_key = 'pearblog_ga4_views_' . md5( $url_path . $start_date . $end_date );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$report = $this->run_report(
			[ 'name' => 'pagePath' ],
			[ 'name' => 'screenPageViews' ],
			$start_date,
			$end_date,
			[ [ 'filter' => [ 'fieldName' => 'pagePath', 'stringFilter' => [ 'value' => $url_path, 'matchType' => 'EXACT' ] ] ] ]
		);

		$views = $this->extract_metric_total( $report, 'screenPageViews' );
		set_transient( $cache_key, $views, $this->get_cache_ttl() );
		return $views;
	}

	/**
	 * Get a ranked list of the top N posts by page views.
	 *
	 * @param int    $limit      Max posts to return.
	 * @param string $start_date
	 * @param string $end_date
	 * @return array<int, array{path: string, views: int}>  Sorted by views DESC.
	 */
	public function get_top_posts( int $limit = 10, string $start_date = '30daysAgo', string $end_date = 'today' ): array {
		$cache_key = 'pearblog_ga4_top_' . md5( $limit . $start_date . $end_date );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (array) $cached;
		}

		$report = $this->run_report(
			[ 'name' => 'pagePath' ],
			[ 'name' => 'screenPageViews' ],
			$start_date,
			$end_date,
			[],
			$limit
		);

		$rows = $this->extract_rows( $report );
		set_transient( $cache_key, $rows, $this->get_cache_ttl() );
		return $rows;
	}

	/**
	 * Get total site-wide page views for the given date range.
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return int
	 */
	public function get_total_views( string $start_date = '30daysAgo', string $end_date = 'today' ): int {
		$cache_key = 'pearblog_ga4_total_' . md5( $start_date . $end_date );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$report = $this->run_report(
			[],
			[ 'name' => 'screenPageViews' ],
			$start_date,
			$end_date
		);

		$total = $this->extract_metric_total( $report, 'screenPageViews' );
		set_transient( $cache_key, $total, $this->get_cache_ttl() );
		return $total;
	}

	// -----------------------------------------------------------------------
	// GA4 API request
	// -----------------------------------------------------------------------

	/**
	 * Execute a runReport API call.
	 *
	 * @param array       $dimension   Single dimension spec or empty array.
	 * @param array       $metric      Single metric spec.
	 * @param string      $start_date
	 * @param string      $end_date
	 * @param array       $filters     dimensionFilter clauses.
	 * @param int         $limit       Row limit; 0 = no limit.
	 * @return array                   Decoded API response, or empty array on error.
	 */
	public function run_report(
		array  $dimension,
		array  $metric,
		string $start_date = '30daysAgo',
		string $end_date   = 'today',
		array  $filters    = [],
		int    $limit      = 0
	): array {
		if ( ! $this->is_configured() ) {
			return [];
		}

		$token = $this->get_access_token();
		if ( '' === $token ) {
			return [];
		}

		$body = [
			'dateRanges' => [
				[ 'startDate' => $start_date, 'endDate' => $end_date ],
			],
			'metrics' => [ $metric ],
			'orderBys' => [
				[ 'metric' => [ 'metricName' => $metric['name'] ], 'desc' => true ],
			],
		];

		if ( ! empty( $dimension ) ) {
			$body['dimensions'] = [ $dimension ];
		}

		if ( ! empty( $filters ) ) {
			$body['dimensionFilter'] = [
				'andGroup' => [ 'expressions' => $filters ],
			];
		}

		if ( $limit > 0 ) {
			$body['limit'] = $limit;
		}

		$url      = sprintf( self::API_URL, $this->get_property_id() );
		$response = wp_remote_post( $url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return [];
		}

		$body    = is_array( $response ) ? ( (string) ( $response['body'] ?? '' ) ) : '';
		$decoded = json_decode( $body, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	// -----------------------------------------------------------------------
	// OAuth2 service account token
	// -----------------------------------------------------------------------

	/**
	 * Get (or refresh) an OAuth2 access token via service account JWT.
	 *
	 * @return string  Access token, or empty string on failure.
	 */
	public function get_access_token(): string {
		$cached = get_transient( self::TRANSIENT_TOKEN );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$raw_creds = (string) get_option( self::OPTION_CREDENTIALS, '' );
		if ( '' === $raw_creds ) {
			return '';
		}

		$creds = json_decode( $raw_creds, true );
		if ( ! is_array( $creds ) ) {
			return '';
		}

		$jwt = $this->build_jwt( $creds );
		if ( '' === $jwt ) {
			return '';
		}

		$response = wp_remote_post( self::TOKEN_URL, [
			'body' => [
				'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
				'assertion'  => $jwt,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$body  = is_array( $response ) ? ( (string) ( $response['body'] ?? '' ) ) : '';
		$data  = json_decode( $body, true );
		$token = is_array( $data ) ? ( (string) ( $data['access_token'] ?? '' ) ) : '';

		if ( '' !== $token ) {
			$expires_in = (int) ( $data['expires_in'] ?? 3600 );
			set_transient( self::TRANSIENT_TOKEN, $token, $expires_in - 60 );
		}

		return $token;
	}

	/**
	 * Build a signed JWT for the service account.
	 *
	 * @param array $creds  Decoded service-account JSON.
	 * @return string       Base64url-encoded JWT, or empty string on failure.
	 */
	private function build_jwt( array $creds ): string {
		if ( empty( $creds['private_key'] ) || empty( $creds['client_email'] ) ) {
			return '';
		}

		$now = time();
		$header  = $this->base64url_encode( (string) wp_json_encode( [ 'alg' => 'RS256', 'typ' => 'JWT' ] ) );
		$payload = $this->base64url_encode( (string) wp_json_encode( [
			'iss'   => $creds['client_email'],
			'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
			'aud'   => self::TOKEN_URL,
			'iat'   => $now,
			'exp'   => $now + 3600,
		] ) );

		$signing_input = "{$header}.{$payload}";
		$signature     = '';

		if ( ! openssl_sign( $signing_input, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256 ) ) {
			return '';
		}

		return $signing_input . '.' . $this->base64url_encode( $signature );
	}

	/**
	 * URL-safe Base64 encoding (no padding).
	 */
	private function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	// -----------------------------------------------------------------------
	// Response parsing
	// -----------------------------------------------------------------------

	/**
	 * Sum values for a named metric across all rows.
	 *
	 * @param array  $report  Decoded runReport response.
	 * @param string $metric  Metric name.
	 * @return int
	 */
	public function extract_metric_total( array $report, string $metric ): int {
		$total     = 0;
		$metric_idx = $this->find_metric_index( $report, $metric );

		foreach ( $report['rows'] ?? [] as $row ) {
			$total += (int) ( $row['metricValues'][ $metric_idx ]['value'] ?? 0 );
		}

		return $total;
	}

	/**
	 * Extract dimension→metric rows as [ ['path'=>…, 'views'=>…], … ].
	 *
	 * @param array $report
	 * @return array<int, array{path: string, views: int}>
	 */
	public function extract_rows( array $report ): array {
		$rows       = [];
		$metric_idx = $this->find_metric_index( $report, 'screenPageViews' );

		foreach ( $report['rows'] ?? [] as $row ) {
			$path  = (string) ( $row['dimensionValues'][0]['value'] ?? '' );
			$views = (int) ( $row['metricValues'][ $metric_idx ]['value'] ?? 0 );
			if ( '' !== $path ) {
				$rows[] = [ 'path' => $path, 'views' => $views ];
			}
		}

		return $rows;
	}

	/**
	 * Find the index of a metric in the metricHeaders array.
	 */
	private function find_metric_index( array $report, string $name ): int {
		foreach ( $report['metricHeaders'] ?? [] as $idx => $header ) {
			if ( ( $header['name'] ?? '' ) === $name ) {
				return $idx;
			}
		}
		return 0;
	}
}
