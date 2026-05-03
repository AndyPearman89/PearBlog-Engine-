<?php
/**
 * Google Search Console Client – retrieves position, CTR and impressions data.
 *
 * Authenticates via a service-account JSON key stored in WP options.
 * Data is cached in transients to avoid hammering the API.
 *
 * Features:
 *  - Per-URL performance data (clicks, impressions, CTR, position)
 *  - Site-wide top queries report
 *  - Automatic "quick win" detection: URLs ranked 11–20 are flagged for
 *    content refresh, which fires `pearblog_gsc_quick_win_detected`.
 *  - Weekly cron refresh (`pearblog_gsc_refresh`).
 *
 * Configuration options:
 *   pearblog_gsc_site_url     – Verified property URL (e.g. "https://example.com/")
 *   pearblog_gsc_credentials  – Service-account JSON (raw string)
 *   pearblog_gsc_cache_ttl    – Cache TTL in seconds (default 86400)
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Wrapper around the Google Search Console Data API v1.
 */
class SearchConsoleClient {

	/** WP option keys. */
	public const OPTION_SITE_URL    = 'pearblog_gsc_site_url';
	public const OPTION_CREDENTIALS = 'pearblog_gsc_credentials';
	public const OPTION_CACHE_TTL   = 'pearblog_gsc_cache_ttl';

	/** Default cache TTL (24 hours). */
	public const DEFAULT_CACHE_TTL = 86400;

	/** GSC API base URL. */
	private const API_URL = 'https://searchconsole.googleapis.com/webmasters/v3/sites/%s/searchAnalytics/query';

	/** OAuth token endpoint. */
	private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

	/** Transient key prefix for cached reports. */
	private const TRANSIENT_PREFIX = 'pearblog_gsc_';

	/** Transient key for the access token. */
	public const TRANSIENT_TOKEN = 'pearblog_gsc_access_token';

	/** Position threshold for "quick win" detection (rank 11–20). */
	private const QUICK_WIN_MIN = 11.0;
	private const QUICK_WIN_MAX = 20.0;

	/** Cron hook. */
	private const CRON_HOOK = 'pearblog_gsc_refresh';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'run_refresh' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Schedule weekly cron if not already scheduled.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/gsc/performance', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_performance' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/gsc/quick-wins', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_quick_wins' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Configuration
	// -----------------------------------------------------------------------

	/**
	 * Whether the client is configured (site URL + credentials present).
	 */
	public function is_configured(): bool {
		return '' !== (string) get_option( self::OPTION_SITE_URL, '' )
			&& '' !== (string) get_option( self::OPTION_CREDENTIALS, '' );
	}

	/**
	 * Return cache TTL in seconds.
	 */
	public function get_cache_ttl(): int {
		return (int) get_option( self::OPTION_CACHE_TTL, self::DEFAULT_CACHE_TTL );
	}

	// -----------------------------------------------------------------------
	// Data retrieval
	// -----------------------------------------------------------------------

	/**
	 * Fetch performance data for a specific URL.
	 *
	 * @param string $url        Full URL or relative path.
	 * @param string $start_date ISO date e.g. "2026-01-01" or "30daysAgo".
	 * @param string $end_date   ISO date e.g. "today".
	 * @return array{clicks:int, impressions:int, ctr:float, position:float}
	 */
	public function get_url_performance( string $url, string $start_date = '30daysAgo', string $end_date = 'today' ): array {
		if ( ! $this->is_configured() ) {
			return [ 'clicks' => 0, 'impressions' => 0, 'ctr' => 0.0, 'position' => 0.0 ];
		}

		$cache_key = self::TRANSIENT_PREFIX . 'url_' . md5( $url . $start_date . $end_date );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$token = $this->get_access_token();
		if ( '' === $token ) {
			return [ 'clicks' => 0, 'impressions' => 0, 'ctr' => 0.0, 'position' => 0.0 ];
		}

		$site_url = rawurlencode( (string) get_option( self::OPTION_SITE_URL ) );
		$api_url  = sprintf( self::API_URL, $site_url );

		$body = wp_json_encode( [
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => [ 'page' ],
			'dimensionFilterGroups' => [ [
				'filters' => [ [
					'dimension'  => 'page',
					'operator'   => 'equals',
					'expression' => $url,
				] ],
			] ],
		] );

		$response = wp_remote_post( $api_url, [
			'headers' => [
				'Authorization' => "Bearer {$token}",
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'clicks' => 0, 'impressions' => 0, 'ctr' => 0.0, 'position' => 0.0 ];
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$row  = $data['rows'][0] ?? null;

		$result = [
			'clicks'      => (int) ( $row['clicks']      ?? 0 ),
			'impressions' => (int) ( $row['impressions']  ?? 0 ),
			'ctr'         => (float) ( $row['ctr']        ?? 0.0 ),
			'position'    => (float) ( $row['position']   ?? 0.0 ),
		];

		set_transient( $cache_key, $result, $this->get_cache_ttl() );

		return $result;
	}

	/**
	 * Get top organic queries for the site.
	 *
	 * @param int    $limit      Max queries to return.
	 * @param string $start_date Date range start.
	 * @param string $end_date   Date range end.
	 * @return array<int, array{query: string, clicks: int, impressions: int, ctr: float, position: float}>
	 */
	public function get_top_queries( int $limit = 25, string $start_date = '30daysAgo', string $end_date = 'today' ): array {
		if ( ! $this->is_configured() ) {
			return [];
		}

		$cache_key = self::TRANSIENT_PREFIX . 'queries_' . md5( $limit . $start_date . $end_date );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$token = $this->get_access_token();
		if ( '' === $token ) {
			return [];
		}

		$site_url = rawurlencode( (string) get_option( self::OPTION_SITE_URL ) );
		$api_url  = sprintf( self::API_URL, $site_url );

		$body = wp_json_encode( [
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => [ 'query' ],
			'rowLimit'   => $limit,
			'orderBy'    => [ [ 'fieldName' => 'clicks', 'sortOrder' => 'DESCENDING' ] ],
		] );

		$response = wp_remote_post( $api_url, [
			'headers' => [
				'Authorization' => "Bearer {$token}",
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$data  = json_decode( wp_remote_retrieve_body( $response ), true );
		$rows  = $data['rows'] ?? [];
		$items = [];

		foreach ( $rows as $row ) {
			$items[] = [
				'query'       => $row['keys'][0] ?? '',
				'clicks'      => (int)   ( $row['clicks']      ?? 0 ),
				'impressions' => (int)   ( $row['impressions']  ?? 0 ),
				'ctr'         => (float) ( $row['ctr']          ?? 0.0 ),
				'position'    => (float) ( $row['position']     ?? 0.0 ),
			];
		}

		set_transient( $cache_key, $items, $this->get_cache_ttl() );

		return $items;
	}

	/**
	 * Detect "quick win" articles: ranked 11–20, candidates for content refresh.
	 *
	 * @return array<int, array{post_id: int, url: string, position: float, clicks: int, impressions: int}>
	 */
	public function detect_quick_wins(): array {
		if ( ! $this->is_configured() ) {
			return [];
		}

		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
		] );

		$quick_wins = [];

		foreach ( $posts as $post_id ) {
			$url  = get_permalink( (int) $post_id );
			$perf = $this->get_url_performance( (string) $url );

			if (
				$perf['position'] >= self::QUICK_WIN_MIN
				&& $perf['position'] <= self::QUICK_WIN_MAX
				&& $perf['impressions'] > 10
			) {
				$quick_wins[] = [
					'post_id'     => (int) $post_id,
					'url'         => $url,
					'position'    => $perf['position'],
					'clicks'      => $perf['clicks'],
					'impressions' => $perf['impressions'],
					'ctr'         => $perf['ctr'],
				];
			}
		}

		usort( $quick_wins, fn( $a, $b ) => $a['position'] <=> $b['position'] );

		return $quick_wins;
	}

	// -----------------------------------------------------------------------
	// Cron
	// -----------------------------------------------------------------------

	/**
	 * Weekly cron: detect quick wins and fire action hook.
	 */
	public function run_refresh(): void {
		$quick_wins = $this->detect_quick_wins();

		foreach ( $quick_wins as $win ) {
			/**
			 * Action: pearblog_gsc_quick_win_detected
			 *
			 * @param int   $post_id  Post ID.
			 * @param float $position Average SERP position.
			 */
			do_action( 'pearblog_gsc_quick_win_detected', $win['post_id'], $win['position'] );
		}

		update_option( 'pearblog_gsc_quick_wins', $quick_wins );
		update_option( 'pearblog_gsc_last_refresh', time() );

		/**
		 * Action: pearblog_gsc_refreshed
		 *
		 * @param array<int,array> $quick_wins Detected quick win articles.
		 */
		do_action( 'pearblog_gsc_refreshed', $quick_wins );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_performance( \WP_REST_Request $request ): \WP_REST_Response {
		$queries = $this->get_top_queries( 25 );
		return new \WP_REST_Response( [
			'configured'   => $this->is_configured(),
			'top_queries'  => $queries,
			'generated_at' => time(),
		], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_quick_wins( \WP_REST_Request $request ): \WP_REST_Response {
		$cached = get_option( 'pearblog_gsc_quick_wins', [] );
		return new \WP_REST_Response( [
			'count'      => count( $cached ),
			'quick_wins' => $cached,
			'last_refresh' => get_option( 'pearblog_gsc_last_refresh', 0 ),
		], 200 );
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// Authentication
	// -----------------------------------------------------------------------

	/**
	 * Return a valid OAuth 2.0 access token for the service account.
	 */
	private function get_access_token(): string {
		$cached = get_transient( self::TRANSIENT_TOKEN );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$creds_json = (string) get_option( self::OPTION_CREDENTIALS, '' );
		if ( '' === $creds_json ) {
			return '';
		}

		$creds = json_decode( $creds_json, true );
		if ( ! is_array( $creds ) ) {
			return '';
		}

		// Build JWT for service account.
		$now   = time();
		$claim = [
			'iss'   => $creds['client_email'] ?? '',
			'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
			'aud'   => self::TOKEN_URL,
			'iat'   => $now,
			'exp'   => $now + 3600,
		];

		$jwt   = $this->build_jwt( $claim, $creds['private_key'] ?? '' );
		if ( '' === $jwt ) {
			return '';
		}

		$response = wp_remote_post( self::TOKEN_URL, [
			'body' => [
				'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
				'assertion'  => $jwt,
			],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$data  = json_decode( wp_remote_retrieve_body( $response ), true );
		$token = (string) ( $data['access_token'] ?? '' );
		$ttl   = (int) ( $data['expires_in'] ?? 3600 ) - 60;

		if ( '' !== $token ) {
			set_transient( self::TRANSIENT_TOKEN, $token, $ttl );
		}

		return $token;
	}

	/**
	 * Build a signed JWT using RS256 for service-account auth.
	 *
	 * @param array<string,mixed> $claims JWT payload.
	 * @param string $private_key         PEM-encoded RSA private key.
	 * @return string Signed JWT or empty string on failure.
	 */
	private function build_jwt( array $claims, string $private_key ): string {
		if ( '' === $private_key || ! function_exists( 'openssl_sign' ) ) {
			return '';
		}

		$header  = base64_encode( (string) wp_json_encode( [ 'alg' => 'RS256', 'typ' => 'JWT' ] ) );
		$payload = base64_encode( (string) wp_json_encode( $claims ) );
		$message = $header . '.' . $payload;

		$key = openssl_pkey_get_private( $private_key );
		if ( false === $key ) {
			return '';
		}

		$signature = '';
		openssl_sign( $message, $signature, $key, OPENSSL_ALGO_SHA256 );

		return $message . '.' . base64_encode( $signature );
	}
}
