<?php
/**
 * Request Context Processor
 *
 * Adds HTTP request information to log records:
 * - Request method (GET, POST, etc.)
 * - Request URI
 * - Client IP address
 * - User agent
 * - Referer
 *
 * @package PearBlogEngine\Logging
 */

declare(strict_types=1);

namespace PearBlogEngine\Logging;

/**
 * Adds HTTP request context to log records
 */
class RequestContextProcessor implements ProcessorInterface {

	/** @var bool Whether to include user agent */
	private bool $include_user_agent;

	/** @var bool Whether to include referer */
	private bool $include_referer;

	/**
	 * Constructor
	 *
	 * @param bool $include_user_agent Include user agent string
	 * @param bool $include_referer    Include HTTP referer
	 */
	public function __construct( bool $include_user_agent = true, bool $include_referer = false ) {
		$this->include_user_agent = $include_user_agent;
		$this->include_referer    = $include_referer;
	}

	/**
	 * Process the log record
	 *
	 * @param array $record Log record
	 * @return array Modified record
	 */
	public function process( array $record ): array {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			return $record; // Not an HTTP request
		}

		$record['extra'] = $record['extra'] ?? [];

		$record['extra']['request'] = [
			'method' => sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ),
			'uri'    => sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ),
			'ip'     => $this->get_client_ip(),
		];

		if ( $this->include_user_agent && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$record['extra']['request']['user_agent'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		if ( $this->include_referer && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$record['extra']['request']['referer'] = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}

		return $record;
	}

	/**
	 * Get client IP address (handles proxies)
	 *
	 * @return string Client IP
	 */
	private function get_client_ip(): string {
		$ip_keys = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR',
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

				// Handle comma-separated IPs (X-Forwarded-For can have multiple)
				if ( false !== strpos( $ip, ',' ) ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}
