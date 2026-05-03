<?php
/**
 * Error Tracker – captures PHP errors/warnings and surfaces them in WP Admin.
 *
 * WordPress provides basic error logging but doesn't aggregate errors into a
 * queryable store.  This module:
 *
 *   - Installs a custom PHP error handler (set_error_handler) that intercepts
 *     E_WARNING, E_NOTICE, and E_USER_* errors during the WP request lifecycle.
 *   - Stores errors as a ring-buffer in a WP option (max 200 entries).
 *   - Exposes a REST endpoint for querying the error log.
 *   - Surfaces a WP Admin notice when critical errors exceed a threshold.
 *   - Provides a WP-CLI style `wp_remote_post` integration for forwarding
 *     errors to an external webhook (optional).
 *
 * Error entry format:
 *   {
 *     "type":    int,       // PHP error constant (E_WARNING etc.)
 *     "message": string,
 *     "file":    string,
 *     "line":    int,
 *     "at":      int        // Unix timestamp
 *   }
 *
 * Post meta keys: none (errors stored at site level, not per-post)
 *
 * Options:
 *   pearblog_error_tracker_enabled   – bool master switch (default true)
 *   pearblog_error_log               – JSON ring-buffer of captured errors
 *   pearblog_error_tracker_max       – max entries to retain (default 200)
 *   pearblog_error_webhook_url       – optional HTTP endpoint to forward errors
 *   pearblog_error_critical_threshold– admin notice threshold (default 5)
 *
 * REST endpoints:
 *   GET    /pearblog/v1/errors          – list captured errors (admin only)
 *   DELETE /pearblog/v1/errors          – clear the error log (admin only)
 *   GET    /pearblog/v1/errors/summary  – counts by error type (admin only)
 *
 * Actions fired:
 *   pearblog_error_captured ($entry)
 *
 * @package PearBlogEngine\Monitoring
 */

declare(strict_types=1);

namespace PearBlogEngine\Monitoring;

/**
 * Captures and stores PHP errors for the PearBlog admin.
 */
class ErrorTracker {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** WP option key for the ring-buffer. */
	public const OPTION_LOG = 'pearblog_error_log';

	/** PHP error types we care about. */
	private const TRACKED_TYPES = E_WARNING | E_NOTICE | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED;

	/** Human-readable labels for error types. */
	public const TYPE_LABELS = [
		E_ERROR         => 'Fatal',
		E_WARNING       => 'Warning',
		E_NOTICE        => 'Notice',
		E_USER_ERROR    => 'User Error',
		E_USER_WARNING  => 'User Warning',
		E_USER_NOTICE   => 'User Notice',
		E_DEPRECATED    => 'Deprecated',
		E_USER_DEPRECATED => 'User Deprecated',
	];

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks and install error handler.
	 */
	public function register(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'admin_notices', [ $this, 'maybe_show_admin_notice' ] );
		add_action( 'init', [ $this, 'install_error_handler' ] );
	}

	/**
	 * Whether the error tracker is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( 'pearblog_error_tracker_enabled', true );
	}

	/**
	 * Install the custom PHP error handler.
	 *
	 * We use a non-capturing handler that stores errors and then delegates to
	 * the previous handler (returns false) so WordPress logging still works.
	 */
	public function install_error_handler(): void {
		set_error_handler( [ $this, 'handle_php_error' ], self::TRACKED_TYPES );
	}

	// -----------------------------------------------------------------------
	// Error handler
	// -----------------------------------------------------------------------

	/**
	 * Custom PHP error handler callback.
	 *
	 * @param int    $errno   Error number.
	 * @param string $errstr  Error message.
	 * @param string $errfile File where the error occurred.
	 * @param int    $errline Line number.
	 * @return bool  Always false so default handler also runs.
	 */
	public function handle_php_error( int $errno, string $errstr, string $errfile = '', int $errline = 0 ): bool {
		// Honour the @ (silence) operator.
		if ( 0 === error_reporting() ) {
			return false;
		}

		// Only track PearBlog-related errors to avoid log spam.
		if ( '' !== $errfile && ! str_contains( $errfile, 'pearblog' ) && ! str_contains( $errfile, 'PearBlog' ) ) {
			return false;
		}

		$entry = [
			'type'    => $errno,
			'message' => $errstr,
			'file'    => $errfile,
			'line'    => $errline,
			'at'      => time(),
		];

		$this->append_entry( $entry );

		do_action( 'pearblog_error_captured', $entry );

		// Optionally forward to webhook.
		$webhook = (string) get_option( 'pearblog_error_webhook_url', '' );
		if ( '' !== $webhook ) {
			$this->forward_to_webhook( $entry, $webhook );
		}

		return false; // Let the default handler also run.
	}

	// -----------------------------------------------------------------------
	// Admin notice
	// -----------------------------------------------------------------------

	/**
	 * Display an admin notice when recent error count exceeds threshold.
	 */
	public function maybe_show_admin_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$threshold = (int) get_option( 'pearblog_error_critical_threshold', 5 );
		$log       = $this->get_log();

		// Count recent errors (last hour).
		$recent = array_filter( $log, fn( $e ) => ( time() - (int) $e['at'] ) < HOUR_IN_SECONDS );

		if ( count( $recent ) >= $threshold ) {
			$count = count( $recent );
			echo '<div class="notice notice-error"><p>';
			echo '<strong>PearBlog Error Tracker:</strong> ';
			echo esc_html( "{$count} errors captured in the last hour. " );
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=pearblog#errors' ) ) . '">View error log →</a>';
			echo '</p></div>';
		}
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/errors', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_list' ],
				'permission_callback' => [ $this, 'rest_permission' ],
			],
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'rest_clear' ],
				'permission_callback' => [ $this, 'rest_permission' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/errors/summary', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_summary' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );
	}

	/**
	 * Permission: manage_options.
	 */
	public function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /errors – return captured errors (newest first).
	 */
	public function rest_list( \WP_REST_Request $request ): \WP_REST_Response {
		$log = array_reverse( $this->get_log() );
		return new \WP_REST_Response( [ 'errors' => $log, 'total' => count( $log ) ], 200 );
	}

	/**
	 * DELETE /errors – clear the error log.
	 */
	public function rest_clear( \WP_REST_Request $request ): \WP_REST_Response {
		$this->clear_log();
		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * GET /errors/summary – counts grouped by error type.
	 */
	public function rest_summary( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->get_summary(), 200 );
	}

	// -----------------------------------------------------------------------
	// Log management
	// -----------------------------------------------------------------------

	/**
	 * Return the current error log.
	 *
	 * @return array<int, array>
	 */
	public function get_log(): array {
		$raw = get_option( self::OPTION_LOG, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/**
	 * Append a new entry to the ring-buffer.
	 *
	 * @param array $entry Error entry.
	 */
	public function append_entry( array $entry ): void {
		$log = $this->get_log();
		$log[] = $entry;

		$max = (int) get_option( 'pearblog_error_tracker_max', 200 );
		if ( count( $log ) > $max ) {
			$log = array_slice( $log, -$max );
		}

		update_option( self::OPTION_LOG, $log );
	}

	/**
	 * Clear the error log.
	 */
	public function clear_log(): void {
		update_option( self::OPTION_LOG, [] );
	}

	/**
	 * Return per-type error counts.
	 *
	 * @return array<string, int>  Label => count.
	 */
	public function get_summary(): array {
		$summary = [];

		foreach ( $this->get_log() as $entry ) {
			$type  = (int) $entry['type'];
			$label = self::TYPE_LABELS[ $type ] ?? "Error({$type})";
			$summary[ $label ] = ( $summary[ $label ] ?? 0 ) + 1;
		}

		return $summary;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Forward an error entry to an external webhook (fire and forget).
	 *
	 * @param array  $entry   Error entry.
	 * @param string $webhook Webhook URL.
	 */
	private function forward_to_webhook( array $entry, string $webhook ): void {
		wp_remote_post( $webhook, [
			'timeout'  => 3,
			'blocking' => false,
			'body'     => wp_json_encode( $entry ),
			'headers'  => [ 'Content-Type' => 'application/json' ],
		] );
	}
}
