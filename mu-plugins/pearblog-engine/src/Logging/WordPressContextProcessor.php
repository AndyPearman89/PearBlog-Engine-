<?php
/**
 * WordPress Context Processor
 *
 * Adds WordPress-specific context to log records:
 * - Current user ID and username
 * - Site ID (multisite)
 * - Current theme
 * - WordPress version
 * - PHP version
 *
 * @package PearBlogEngine\Logging
 */

declare(strict_types=1);

namespace PearBlogEngine\Logging;

/**
 * Adds WordPress environment context to log records
 */
class WordPressContextProcessor implements ProcessorInterface {

	/** @var bool Whether to include user information */
	private bool $include_user;

	/** @var bool Whether to include environment info */
	private bool $include_environment;

	/**
	 * Constructor
	 *
	 * @param bool $include_user        Include current user info
	 * @param bool $include_environment Include WP/PHP versions
	 */
	public function __construct( bool $include_user = true, bool $include_environment = false ) {
		$this->include_user        = $include_user;
		$this->include_environment = $include_environment;
	}

	/**
	 * Process the log record
	 *
	 * @param array $record Log record
	 * @return array Modified record
	 */
	public function process( array $record ): array {
		$record['extra'] = $record['extra'] ?? [];
		$record['extra']['wordpress'] = [];

		// Current user
		if ( $this->include_user ) {
			$user = wp_get_current_user();
			if ( $user->ID > 0 ) {
				$record['extra']['wordpress']['user'] = [
					'id'       => $user->ID,
					'username' => $user->user_login,
					'role'     => ! empty( $user->roles ) ? $user->roles[0] : 'none',
				];
			}
		}

		// Multisite info
		if ( is_multisite() ) {
			$record['extra']['wordpress']['site_id'] = get_current_blog_id();
		}

		// Environment
		if ( $this->include_environment ) {
			global $wp_version;

			$record['extra']['wordpress']['environment'] = [
				'wp_version'  => $wp_version ?? 'unknown',
				'php_version' => PHP_VERSION,
				'theme'       => wp_get_theme()->get( 'Name' ),
				'is_debug'    => defined( 'WP_DEBUG' ) && WP_DEBUG,
			];
		}

		return $record;
	}
}
