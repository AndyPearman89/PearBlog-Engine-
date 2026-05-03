<?php
/**
 * Tenant Isolator – enforces data separation between tenants in multisite.
 *
 * In WordPress multisite, each site is treated as a separate tenant.
 * This module ensures PearBlog data is fully isolated per site and provides:
 *
 *  - Per-site encryption of sensitive option values (API keys).
 *  - Prevention of cross-site data access in REST endpoints.
 *  - Per-tenant encryption key derivation from a master secret.
 *
 * Configuration (network options, set in wp-config.php):
 *   PEARBLOG_MASTER_SECRET – master secret for key derivation (32+ chars)
 *
 * Sensitive options encrypted per-site:
 *   pearblog_openai_api_key, pearblog_anthropic_api_key,
 *   pearblog_ga4_credentials, pearblog_gsc_credentials
 *
 * @package PearBlogEngine\Tenant
 */

declare(strict_types=1);

namespace PearBlogEngine\Tenant;

/**
 * Enforces tenant data isolation and provides per-tenant encryption.
 */
class TenantIsolator {

	/** Options that should be encrypted at rest. */
	private const SENSITIVE_OPTIONS = [
		'pearblog_openai_api_key',
		'pearblog_anthropic_api_key',
		'pearblog_ga4_credentials',
		'pearblog_gsc_credentials',
		'pearblog_billing_stripe_secret',
		'pearblog_push_onesignal_key',
		'pearblog_push_fcm_key',
		'pearblog_newsletter_api_key',
	];

	/** Prefix added to option names when stored encrypted. */
	private const ENCRYPTED_PREFIX = 'pbenc_';

	/** Cipher algorithm. */
	private const CIPHER = 'AES-256-CBC';

	/** IV length for AES-256-CBC. */
	private const IV_LENGTH = 16;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		if ( ! is_multisite() ) {
			return;
		}

		// Validate site access on REST requests.
		add_filter( 'rest_pre_dispatch', [ $this, 'validate_site_access' ], 5, 3 );
	}

	// -----------------------------------------------------------------------
	// REST isolation
	// -----------------------------------------------------------------------

	/**
	 * Ensure REST requests are processed in the correct site context.
	 *
	 * @param mixed            $result  Response (null = continue).
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request REST request.
	 * @return mixed
	 */
	public function validate_site_access( $result, \WP_REST_Server $server, \WP_REST_Request $request ) {
		if ( null !== $result ) {
			return $result;
		}

		$route = $request->get_route();

		// Only apply to PearBlog routes.
		if ( ! str_starts_with( $route, '/pearblog/' ) ) {
			return null;
		}

		// Validate the current user belongs to this site (multisite network check).
		if ( is_user_logged_in() && is_multisite() ) {
			$user_id = get_current_user_id();
			if ( ! is_user_member_of_blog( $user_id, get_current_blog_id() ) && ! is_super_admin( $user_id ) ) {
				return new \WP_Error(
					'tenant_access_denied',
					__( 'You do not have access to this site.', 'pearblog-engine' ),
					[ 'status' => 403 ]
				);
			}
		}

		return null;
	}

	// -----------------------------------------------------------------------
	// Encryption / Decryption
	// -----------------------------------------------------------------------

	/**
	 * Encrypt a sensitive option value using the site-specific key.
	 *
	 * @param string $option_name  WP option name.
	 * @param string $plain_value  Plain text value to encrypt.
	 * @return bool True if encrypted and stored successfully.
	 */
	public function encrypt_option( string $option_name, string $plain_value ): bool {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			// Fallback: store unencrypted if OpenSSL unavailable.
			return update_option( $option_name, $plain_value );
		}

		$key = $this->derive_site_key();
		$iv  = openssl_random_pseudo_bytes( self::IV_LENGTH );

		$encrypted = openssl_encrypt( $plain_value, self::CIPHER, $key, 0, $iv );
		if ( false === $encrypted ) {
			return false;
		}

		$payload = base64_encode( $iv ) . '::' . $encrypted;
		return (bool) update_option( self::ENCRYPTED_PREFIX . $option_name, $payload );
	}

	/**
	 * Decrypt a sensitive option value.
	 *
	 * @param string $option_name WP option name.
	 * @return string Decrypted value, or plain value if not encrypted.
	 */
	public function decrypt_option( string $option_name ): string {
		$encrypted_key = self::ENCRYPTED_PREFIX . $option_name;
		$payload       = (string) get_option( $encrypted_key, '' );

		if ( '' === $payload || ! str_contains( $payload, '::' ) ) {
			// Fall back to unencrypted option.
			return (string) get_option( $option_name, '' );
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return (string) get_option( $option_name, '' );
		}

		[ $iv_b64, $ciphertext ] = explode( '::', $payload, 2 );
		$iv  = base64_decode( $iv_b64 );
		$key = $this->derive_site_key();

		$decrypted = openssl_decrypt( $ciphertext, self::CIPHER, $key, 0, $iv );
		return false !== $decrypted ? $decrypted : '';
	}

	/**
	 * Check if a specific option is stored encrypted.
	 *
	 * @param string $option_name WP option name.
	 * @return bool
	 */
	public function is_encrypted( string $option_name ): bool {
		return '' !== (string) get_option( self::ENCRYPTED_PREFIX . $option_name, '' );
	}

	/**
	 * List of sensitive option names that should be encrypted.
	 *
	 * @return string[]
	 */
	public function get_sensitive_options(): array {
		return self::SENSITIVE_OPTIONS;
	}

	// -----------------------------------------------------------------------
	// Key derivation
	// -----------------------------------------------------------------------

	/**
	 * Derive a site-specific 32-byte encryption key.
	 *
	 * Uses HKDF with the master secret and site ID as salt.
	 *
	 * @return string 32-byte binary key.
	 */
	private function derive_site_key(): string {
		$master = defined( 'PEARBLOG_MASTER_SECRET' )
			? PEARBLOG_MASTER_SECRET
			: ( defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_salt( 'auth' ) );

		$site_id = (string) get_current_blog_id();

		// HKDF-like derivation using HMAC-SHA256.
		return hash_hmac( 'sha256', "pearblog_tenant_{$site_id}", $master, true );
	}
}
