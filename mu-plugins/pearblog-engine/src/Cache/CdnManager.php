<?php
/**
 * CDN Manager — automatically offloads AI-generated images to a CDN and
 * rewrites WordPress attachment URLs to point to the CDN origin.
 *
 * Two providers are supported (selected via `pearblog_cdn_provider` option):
 *   "bunnycdn"  — BunnyCDN Storage API + CDN pull/push zones
 *   "cloudflare"— Cloudflare Images API (direct upload)
 *
 * Workflow
 * ────────
 * 1. After an AI-generated image is saved as a WP attachment, call
 *    `offload_attachment(attachment_id)`.  The manager uploads the file to
 *    the configured CDN and stores the CDN URL in post-meta
 *    (`_pearblog_cdn_url`).
 * 2. The `wp_get_attachment_url` filter is hooked via `register()` so that
 *    any call to `wp_get_attachment_url()` for an offloaded attachment
 *    transparently returns the CDN URL instead of the local file URL.
 * 3. Optionally, the local file can be deleted after successful offload
 *    (`pearblog_cdn_delete_local` option) to reclaim disk space.
 *
 * Configuration WP options:
 *   pearblog_cdn_provider          – "bunnycdn" (default) | "cloudflare"
 *   pearblog_cdn_enabled           – bool master switch (default false)
 *   pearblog_cdn_delete_local      – bool, delete local file after upload (default false)
 *
 *   BunnyCDN:
 *     pearblog_cdn_bunny_api_key       – Storage Zone API key
 *     pearblog_cdn_bunny_zone_name     – Storage Zone name
 *     pearblog_cdn_bunny_region        – Storage region code, e.g. "de" (default "")
 *     pearblog_cdn_bunny_pull_zone_url – Pull zone base URL, e.g. "https://myzone.b-cdn.net"
 *
 *   Cloudflare Images:
 *     pearblog_cdn_cf_account_id  – Cloudflare account ID
 *     pearblog_cdn_cf_api_token   – Images API token
 *     pearblog_cdn_cf_delivery_url– Image delivery base URL
 *                                   e.g. "https://imagedelivery.net/<account-hash>"
 *
 * @package PearBlogEngine\Cache
 */

declare(strict_types=1);

namespace PearBlogEngine\Cache;

/**
 * Offloads AI-generated images to Cloudflare Images or BunnyCDN.
 */
class CdnManager {

	// -----------------------------------------------------------------------
	// Option keys
	// -----------------------------------------------------------------------

	public const OPTION_ENABLED          = 'pearblog_cdn_enabled';
	public const OPTION_PROVIDER         = 'pearblog_cdn_provider';
	public const OPTION_DELETE_LOCAL     = 'pearblog_cdn_delete_local';

	// BunnyCDN options.
	public const OPTION_BUNNY_API_KEY    = 'pearblog_cdn_bunny_api_key';
	public const OPTION_BUNNY_ZONE_NAME  = 'pearblog_cdn_bunny_zone_name';
	public const OPTION_BUNNY_REGION     = 'pearblog_cdn_bunny_region';
	public const OPTION_BUNNY_PULL_URL   = 'pearblog_cdn_bunny_pull_zone_url';

	// Cloudflare Images options.
	public const OPTION_CF_ACCOUNT_ID    = 'pearblog_cdn_cf_account_id';
	public const OPTION_CF_API_TOKEN     = 'pearblog_cdn_cf_api_token';
	public const OPTION_CF_DELIVERY_URL  = 'pearblog_cdn_cf_delivery_url';

	// -----------------------------------------------------------------------
	// Meta key
	// -----------------------------------------------------------------------

	/** Post meta key storing the CDN URL for an offloaded attachment. */
	public const META_CDN_URL  = '_pearblog_cdn_url';

	/** Post meta key recording the provider used for offload. */
	public const META_PROVIDER = '_pearblog_cdn_provider';

	// -----------------------------------------------------------------------
	// Provider constants
	// -----------------------------------------------------------------------

	public const PROVIDER_BUNNYCDN   = 'bunnycdn';
	public const PROVIDER_CLOUDFLARE = 'cloudflare';
	public const DEFAULT_PROVIDER    = self::PROVIDER_BUNNYCDN;

	// -----------------------------------------------------------------------
	// BunnyCDN API endpoints
	// -----------------------------------------------------------------------

	private const BUNNY_STORAGE_BASE = 'https://storage.bunnycdn.com';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_filter( 'wp_get_attachment_url', [ $this, 'filter_attachment_url' ], 10, 2 );
	}

	// -----------------------------------------------------------------------
	// Main public API
	// -----------------------------------------------------------------------

	/**
	 * Offload a WP attachment to the CDN.
	 *
	 * @param int $attachment_id  WP post ID of the attachment.
	 * @return string|null        CDN URL on success, null on failure/disabled.
	 */
	public function offload_attachment( int $attachment_id ): ?string {
		if ( ! $this->is_enabled() ) {
			return null;
		}

		// Skip if already offloaded.
		$existing = get_post_meta( $attachment_id, self::META_CDN_URL, true );
		if ( '' !== (string) $existing ) {
			return (string) $existing;
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( (string) $file_path ) ) {
			return null;
		}

		$cdn_url = $this->upload_to_provider( (string) $file_path, $attachment_id );

		if ( null === $cdn_url ) {
			return null;
		}

		update_post_meta( $attachment_id, self::META_CDN_URL, $cdn_url );
		update_post_meta( $attachment_id, self::META_PROVIDER, $this->get_provider() );

		if ( $this->should_delete_local() ) {
			@unlink( (string) $file_path );
		}

		/**
		 * Fires when an attachment is successfully offloaded to the CDN.
		 *
		 * @param int    $attachment_id
		 * @param string $cdn_url
		 * @param string $provider
		 */
		do_action( 'pearblog_cdn_offloaded', $attachment_id, $cdn_url, $this->get_provider() );

		return $cdn_url;
	}

	/**
	 * WordPress filter: return CDN URL instead of local URL for offloaded attachments.
	 *
	 * @param string $url           Original attachment URL.
	 * @param int    $attachment_id Attachment post ID.
	 * @return string
	 */
	public function filter_attachment_url( string $url, int $attachment_id ): string {
		$cdn_url = (string) get_post_meta( $attachment_id, self::META_CDN_URL, true );
		return '' !== $cdn_url ? $cdn_url : $url;
	}

	/**
	 * Remove a previously offloaded attachment from the CDN.
	 *
	 * @param int $attachment_id
	 * @return bool
	 */
	public function remove_from_cdn( int $attachment_id ): bool {
		$cdn_url  = (string) get_post_meta( $attachment_id, self::META_CDN_URL, true );
		$provider = (string) get_post_meta( $attachment_id, self::META_PROVIDER, true );

		if ( '' === $cdn_url ) {
			return false;
		}

		$success = false;

		if ( self::PROVIDER_BUNNYCDN === $provider ) {
			$success = $this->bunny_delete( $cdn_url );
		} elseif ( self::PROVIDER_CLOUDFLARE === $provider ) {
			$success = $this->cloudflare_delete( $cdn_url );
		}

		if ( $success ) {
			delete_post_meta( $attachment_id, self::META_CDN_URL );
			delete_post_meta( $attachment_id, self::META_PROVIDER );
		}

		return $success;
	}

	// -----------------------------------------------------------------------
	// Configuration accessors
	// -----------------------------------------------------------------------

	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	public function get_provider(): string {
		return (string) get_option( self::OPTION_PROVIDER, self::DEFAULT_PROVIDER );
	}

	public function should_delete_local(): bool {
		return (bool) get_option( self::OPTION_DELETE_LOCAL, false );
	}

	// -----------------------------------------------------------------------
	// Provider dispatch
	// -----------------------------------------------------------------------

	/**
	 * Upload a file to the configured provider.
	 *
	 * @param string $file_path      Absolute path to the local file.
	 * @param int    $attachment_id
	 * @return string|null           CDN URL, or null on failure.
	 */
	public function upload_to_provider( string $file_path, int $attachment_id ): ?string {
		return match ( $this->get_provider() ) {
			self::PROVIDER_CLOUDFLARE => $this->cloudflare_upload( $file_path, $attachment_id ),
			default                   => $this->bunny_upload( $file_path, $attachment_id ),
		};
	}

	// -----------------------------------------------------------------------
	// BunnyCDN implementation
	// -----------------------------------------------------------------------

	/**
	 * Upload a file to BunnyCDN Storage.
	 *
	 * @return string|null  CDN pull-zone URL or null on failure.
	 */
	private function bunny_upload( string $file_path, int $attachment_id ): ?string {
		$api_key   = (string) get_option( self::OPTION_BUNNY_API_KEY, '' );
		$zone_name = (string) get_option( self::OPTION_BUNNY_ZONE_NAME, '' );
		$region    = (string) get_option( self::OPTION_BUNNY_REGION, '' );
		$pull_url  = rtrim( (string) get_option( self::OPTION_BUNNY_PULL_URL, '' ), '/' );

		if ( '' === $api_key || '' === $zone_name || '' === $pull_url ) {
			return null;
		}

		$filename     = basename( $file_path );
		$storage_host = '' !== $region
			? "https://{$region}.storage.bunnycdn.com"
			: self::BUNNY_STORAGE_BASE;

		$endpoint = "{$storage_host}/{$zone_name}/pearblog/{$filename}";
		$body     = $this->read_file( $file_path );

		if ( null === $body ) {
			return null;
		}

		$response = wp_remote_request( $endpoint, [
			'method'  => 'PUT',
			'headers' => [
				'AccessKey'    => $api_key,
				'Content-Type' => 'application/octet-stream',
			],
			'body'    => $body,
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return null;
		}

		return "{$pull_url}/pearblog/{$filename}";
	}

	/**
	 * Delete a file from BunnyCDN Storage.
	 *
	 * @param string $cdn_url  The CDN URL of the file.
	 * @return bool
	 */
	private function bunny_delete( string $cdn_url ): bool {
		$api_key   = (string) get_option( self::OPTION_BUNNY_API_KEY, '' );
		$zone_name = (string) get_option( self::OPTION_BUNNY_ZONE_NAME, '' );
		$region    = (string) get_option( self::OPTION_BUNNY_REGION, '' );

		if ( '' === $api_key || '' === $zone_name ) {
			return false;
		}

		$filename     = basename( (string) parse_url( $cdn_url, PHP_URL_PATH ) );
		$storage_host = '' !== $region
			? "https://{$region}.storage.bunnycdn.com"
			: self::BUNNY_STORAGE_BASE;

		$endpoint = "{$storage_host}/{$zone_name}/pearblog/{$filename}";

		$response = wp_remote_request( $endpoint, [
			'method'  => 'DELETE',
			'headers' => [ 'AccessKey' => $api_key ],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 300;
	}

	// -----------------------------------------------------------------------
	// Cloudflare Images implementation
	// -----------------------------------------------------------------------

	/**
	 * Upload a file to Cloudflare Images.
	 *
	 * @return string|null  Cloudflare delivery URL or null on failure.
	 */
	private function cloudflare_upload( string $file_path, int $attachment_id ): ?string {
		$account_id   = (string) get_option( self::OPTION_CF_ACCOUNT_ID, '' );
		$api_token    = (string) get_option( self::OPTION_CF_API_TOKEN, '' );
		$delivery_url = rtrim( (string) get_option( self::OPTION_CF_DELIVERY_URL, '' ), '/' );

		if ( '' === $account_id || '' === $api_token || '' === $delivery_url ) {
			return null;
		}

		$endpoint = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/images/v1";
		$filename  = basename( $file_path );
		$body      = $this->read_file( $file_path );

		if ( null === $body ) {
			return null;
		}

		// Cloudflare Images expects multipart/form-data.
		$boundary = '----PearBlogCDN' . md5( (string) time() );
		$multipart = "--{$boundary}\r\n"
			. "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n"
			. "Content-Type: application/octet-stream\r\n\r\n"
			. $body . "\r\n"
			. "--{$boundary}--\r\n";

		$response = wp_remote_post( $endpoint, [
			'headers' => [
				'Authorization' => "Bearer {$api_token}",
				'Content-Type'  => "multipart/form-data; boundary={$boundary}",
			],
			'body'    => $multipart,
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return null;
		}

		$body_str = is_array( $response ) ? ( (string) ( $response['body'] ?? '' ) ) : '';
		$data     = json_decode( $body_str, true );

		if ( ! isset( $data['result']['id'] ) ) {
			return null;
		}

		$cf_id = (string) $data['result']['id'];
		return "{$delivery_url}/{$cf_id}/public";
	}

	/**
	 * Delete an image from Cloudflare Images.
	 *
	 * @param string $cdn_url  The CDN delivery URL (contains the CF image ID).
	 * @return bool
	 */
	private function cloudflare_delete( string $cdn_url ): bool {
		$account_id = (string) get_option( self::OPTION_CF_ACCOUNT_ID, '' );
		$api_token  = (string) get_option( self::OPTION_CF_API_TOKEN, '' );

		if ( '' === $account_id || '' === $api_token ) {
			return false;
		}

		// Extract image ID from URL: .../delivery_url/<image_id>/public
		$parts    = explode( '/', rtrim( $cdn_url, '/' ) );
		$cf_id    = $parts[ count( $parts ) - 2 ] ?? '';

		if ( '' === $cf_id ) {
			return false;
		}

		$endpoint = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/images/v1/{$cf_id}";

		$response = wp_remote_request( $endpoint, [
			'method'  => 'DELETE',
			'headers' => [ 'Authorization' => "Bearer {$api_token}" ],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 300;
	}

	// -----------------------------------------------------------------------
	// File helper
	// -----------------------------------------------------------------------

	/**
	 * Read file contents. Returns null if the file is unreadable.
	 */
	private function read_file( string $path ): ?string {
		if ( ! is_readable( $path ) ) {
			return null;
		}
		$content = file_get_contents( $path );
		return false !== $content ? $content : null;
	}
}
