<?php
/**
 * Permission Manager — role-based access control and audit logging.
 *
 * Controls which WordPress roles may trigger, pause, or configure the
 * content pipeline, and records every sensitive action to an audit log
 * stored as a WordPress option.
 *
 * Configuration:
 *   pearblog_perm_trigger_roles  – JSON array of WP roles that can trigger the pipeline
 *   pearblog_perm_pause_roles    – JSON array of WP roles that can pause the pipeline
 *   pearblog_perm_settings_roles – JSON array of WP roles that can edit settings
 *   pearblog_audit_log           – JSON-encoded ring buffer of audit entries
 *
 * @package PearBlogEngine\API
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

/**
 * Handles capability checks and writes entries to the audit log.
 */
class PermissionManager {

	/** Option keys. */
	public const OPTION_TRIGGER_ROLES  = 'pearblog_perm_trigger_roles';
	public const OPTION_PAUSE_ROLES    = 'pearblog_perm_pause_roles';
	public const OPTION_SETTINGS_ROLES = 'pearblog_perm_settings_roles';
	public const OPTION_AUDIT_LOG      = 'pearblog_audit_log';

	/** Maximum audit log entries to retain. */
	public const AUDIT_MAX = 500;

	/** Permission action constants. */
	public const ACTION_TRIGGER  = 'trigger';
	public const ACTION_PAUSE    = 'pause';
	public const ACTION_SETTINGS = 'settings';

	/** Default roles allowed for each action. */
	private const DEFAULTS = [
		self::ACTION_TRIGGER  => [ 'administrator', 'editor' ],
		self::ACTION_PAUSE    => [ 'administrator', 'editor' ],
		self::ACTION_SETTINGS => [ 'administrator' ],
	];

	// -----------------------------------------------------------------------
	// Permission checks
	// -----------------------------------------------------------------------

	/**
	 * Check whether a given WP user role is allowed to perform an action.
	 *
	 * @param string $action  One of ACTION_TRIGGER, ACTION_PAUSE, ACTION_SETTINGS.
	 * @param string $role    WP role slug (e.g. "editor").
	 * @return bool
	 */
	public function role_can( string $action, string $role ): bool {
		$allowed = $this->get_allowed_roles( $action );
		return in_array( $role, $allowed, true );
	}

	/**
	 * Check whether the current WP user can perform an action.
	 *
	 * Falls back to current_user_can() for manage_options (admins always pass).
	 *
	 * @param string $action
	 * @return bool
	 */
	public function current_user_can( string $action ): bool {
		// Admins always have access.
		if ( function_exists( '\current_user_can' ) && \current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Inspect the current user's roles.
		if ( function_exists( '\wp_get_current_user' ) ) {
			$user  = \wp_get_current_user();
			$roles = (array) ( $user->roles ?? [] );
			foreach ( $roles as $role ) {
				if ( $this->role_can( $action, $role ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the list of roles allowed for a given action.
	 *
	 * @param string $action
	 * @return string[]
	 */
	public function get_allowed_roles( string $action ): array {
		$option_map = [
			self::ACTION_TRIGGER  => self::OPTION_TRIGGER_ROLES,
			self::ACTION_PAUSE    => self::OPTION_PAUSE_ROLES,
			self::ACTION_SETTINGS => self::OPTION_SETTINGS_ROLES,
		];

		$option = $option_map[ $action ] ?? '';
		if ( '' === $option ) {
			return [];
		}

		$raw     = get_option( $option, '' );
		$decoded = $raw !== '' ? json_decode( $raw, true ) : null;

		if ( ! is_array( $decoded ) ) {
			return self::DEFAULTS[ $action ] ?? [];
		}

		return array_values( array_filter( array_map( 'strval', $decoded ) ) );
	}

	/**
	 * Set allowed roles for an action.
	 *
	 * @param string   $action
	 * @param string[] $roles
	 */
	public function set_allowed_roles( string $action, array $roles ): void {
		$option_map = [
			self::ACTION_TRIGGER  => self::OPTION_TRIGGER_ROLES,
			self::ACTION_PAUSE    => self::OPTION_PAUSE_ROLES,
			self::ACTION_SETTINGS => self::OPTION_SETTINGS_ROLES,
		];

		$option = $option_map[ $action ] ?? '';
		if ( '' === $option ) {
			return;
		}

		$sanitized = array_values( array_unique( array_filter( array_map( 'sanitize_key', $roles ) ) ) );
		update_option( $option, wp_json_encode( $sanitized ) );
	}

	// -----------------------------------------------------------------------
	// Audit log
	// -----------------------------------------------------------------------

	/**
	 * Append an entry to the audit log.
	 *
	 * @param string $actor    Who performed the action (username, "cron", "cli").
	 * @param string $action   Short action label (e.g. "trigger_pipeline").
	 * @param string $context  Additional context string.
	 * @param bool   $success  Whether the action succeeded.
	 */
	public function log( string $actor, string $action, string $context = '', bool $success = true ): void {
		$history = $this->get_audit_log();

		$history[] = [
			'ts'      => gmdate( 'Y-m-d H:i:s' ),
			'actor'   => sanitize_text_field( $actor ),
			'action'  => sanitize_text_field( $action ),
			'context' => sanitize_text_field( $context ),
			'success' => $success,
		];

		// Trim to max size (keep newest).
		if ( count( $history ) > self::AUDIT_MAX ) {
			$history = array_slice( $history, -self::AUDIT_MAX );
		}

		update_option( self::OPTION_AUDIT_LOG, wp_json_encode( $history ) );
	}

	/**
	 * Retrieve the audit log entries (newest last).
	 *
	 * @param int $limit  Maximum entries to return; 0 = all.
	 * @return array<int, array{ts: string, actor: string, action: string, context: string, success: bool}>
	 */
	public function get_audit_log( int $limit = 0 ): array {
		$raw     = get_option( self::OPTION_AUDIT_LOG, '[]' );
		$decoded = json_decode( is_string( $raw ) ? $raw : '[]', true );
		$log     = is_array( $decoded ) ? $decoded : [];

		if ( $limit > 0 ) {
			return array_slice( $log, -$limit );
		}

		return $log;
	}

	/**
	 * Clear the audit log.
	 */
	public function clear_audit_log(): void {
		update_option( self::OPTION_AUDIT_LOG, '[]' );
	}
}
