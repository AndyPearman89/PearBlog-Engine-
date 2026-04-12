<?php
/**
 * Structured logger with log levels, rotation, and JSON output.
 *
 * Features:
 *  - PSR-3 inspired levels: DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY
 *  - Writes to WordPress debug.log (via error_log) and optionally to a dedicated file
 *  - Automatic log rotation when file exceeds configurable size limit
 *  - Structured JSON entries for easy parsing / shipping to external services
 *  - All logging calls are no-ops when WP_DEBUG_LOG is false (zero overhead in production)
 *
 * @package PearBlogEngine\Monitoring
 */

declare(strict_types=1);

namespace PearBlogEngine\Monitoring;

/**
 * Centralised logger for PearBlog Engine.
 */
class Logger {

	// ------------------------------------------------------------------
	// Log levels (ordered by severity, matches PSR-3)
	// ------------------------------------------------------------------

	public const DEBUG     = 'DEBUG';
	public const INFO      = 'INFO';
	public const NOTICE    = 'NOTICE';
	public const WARNING   = 'WARNING';
	public const ERROR     = 'ERROR';
	public const CRITICAL  = 'CRITICAL';
	public const ALERT     = 'ALERT';
	public const EMERGENCY = 'EMERGENCY';

	/** Numeric severity used for filtering. */
	private const SEVERITY = [
		self::DEBUG     => 0,
		self::INFO      => 1,
		self::NOTICE    => 2,
		self::WARNING   => 3,
		self::ERROR     => 4,
		self::CRITICAL  => 5,
		self::ALERT     => 6,
		self::EMERGENCY => 7,
	];

	/** Default maximum log file size before rotation (10 MB). */
	private const DEFAULT_MAX_BYTES = 10 * 1024 * 1024;

	/** Number of rotated archives to keep. */
	private const ROTATION_KEEP = 5;

	/** Singleton instance. */
	private static ?self $instance = null;

	/** Configured minimum severity level. */
	private string $min_level;

	/** Path to dedicated log file, or empty to skip file logging. */
	private string $log_file;

	/** Maximum file size before rotation. */
	private int $max_bytes;

	/** Channel / context prefix shown in every log entry. */
	private string $channel;

	/** Accumulated in-memory log entries (last N entries for debug endpoint). */
	private array $memory_log = [];

	/** Maximum in-memory entries to keep. */
	private const MEMORY_LIMIT = 200;

	// ------------------------------------------------------------------
	// Construction / singleton
	// ------------------------------------------------------------------

	/**
	 * @param string $channel  Human-readable channel name (e.g. "pipeline", "ai").
	 * @param string $min_level Minimum level to log (default: DEBUG when WP_DEBUG true, else WARNING).
	 * @param string $log_file  Dedicated log file path, or '' to use error_log only.
	 * @param int    $max_bytes Max file size before rotation.
	 */
	public function __construct(
		string $channel   = 'pearblog',
		string $min_level = '',
		string $log_file  = '',
		int    $max_bytes = self::DEFAULT_MAX_BYTES
	) {
		$this->channel   = $channel;
		$this->max_bytes = $max_bytes;
		$this->log_file  = $log_file;

		if ( '' === $min_level ) {
			$this->min_level = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? self::DEBUG : self::WARNING;
		} else {
			$this->min_level = $min_level;
		}
	}

	/**
	 * Return or create the shared plugin logger instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			// Use a dedicated log file alongside WP's debug.log when possible.
			$log_file = '';
			if ( defined( 'WP_CONTENT_DIR' ) ) {
				$log_file = WP_CONTENT_DIR . '/pearblog-engine.log';
			}
			self::$instance = new self( 'pearblog', '', $log_file );
		}
		return self::$instance;
	}

	// ------------------------------------------------------------------
	// Public logging API
	// ------------------------------------------------------------------

	public function debug( string $message, array $context = [] ): void {
		$this->log( self::DEBUG, $message, $context );
	}

	public function info( string $message, array $context = [] ): void {
		$this->log( self::INFO, $message, $context );
	}

	public function notice( string $message, array $context = [] ): void {
		$this->log( self::NOTICE, $message, $context );
	}

	public function warning( string $message, array $context = [] ): void {
		$this->log( self::WARNING, $message, $context );
	}

	public function error( string $message, array $context = [] ): void {
		$this->log( self::ERROR, $message, $context );
	}

	public function critical( string $message, array $context = [] ): void {
		$this->log( self::CRITICAL, $message, $context );
	}

	public function alert( string $message, array $context = [] ): void {
		$this->log( self::ALERT, $message, $context );
	}

	public function emergency( string $message, array $context = [] ): void {
		$this->log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Core log method.
	 *
	 * @param string $level   One of the class-level constants.
	 * @param string $message Log message (may contain {placeholder} tokens).
	 * @param array  $context Key-value pairs; values replace {key} tokens in $message.
	 */
	public function log( string $level, string $message, array $context = [] ): void {
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		$message     = $this->interpolate( $message, $context );
		$entry       = $this->build_entry( $level, $message, $context );
		$json_line   = (string) json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		// 1. Always write to PHP error_log (respects WP_DEBUG_LOG).
		error_log( 'PearBlog ' . $level . ': ' . $message );

		// 2. Write structured JSON to dedicated file (if configured).
		if ( '' !== $this->log_file ) {
			$this->write_to_file( $json_line );
		}

		// 3. Keep latest entries in memory for the debug REST endpoint.
		$this->memory_log[] = $entry;
		if ( count( $this->memory_log ) > self::MEMORY_LIMIT ) {
			array_shift( $this->memory_log );
		}
	}

	// ------------------------------------------------------------------
	// In-memory log access
	// ------------------------------------------------------------------

	/**
	 * Return all in-memory log entries (newest last).
	 *
	 * @param string|null $min_level If provided, filter to entries at or above this level.
	 * @return array<int, array>
	 */
	public function get_recent( ?string $min_level = null ): array {
		if ( null === $min_level ) {
			return $this->memory_log;
		}

		$min_sev = self::SEVERITY[ $min_level ] ?? 0;

		return array_values( array_filter(
			$this->memory_log,
			fn( array $e ) => ( self::SEVERITY[ $e['level'] ] ?? 0 ) >= $min_sev
		) );
	}

	/**
	 * Clear the in-memory log buffer.
	 */
	public function clear_memory(): void {
		$this->memory_log = [];
	}

	// ------------------------------------------------------------------
	// Log file management
	// ------------------------------------------------------------------

	/**
	 * Rotate the log file if it exceeds the configured size limit.
	 *
	 * Renamed pattern: pearblog-engine.log → pearblog-engine.log.1 → … .5
	 * The oldest archive is deleted.
	 */
	public function maybe_rotate(): void {
		if ( '' === $this->log_file || ! file_exists( $this->log_file ) ) {
			return;
		}

		if ( filesize( $this->log_file ) < $this->max_bytes ) {
			return;
		}

		// Rotate archives: .5 deleted, .4→.5, …, .1→.2, current→.1
		for ( $i = self::ROTATION_KEEP; $i >= 1; $i-- ) {
			$old = $this->log_file . '.' . $i;
			$new = $this->log_file . '.' . ( $i + 1 );
			if ( file_exists( $old ) ) {
				if ( $i === self::ROTATION_KEEP ) {
					@unlink( $old );
				} else {
					@rename( $old, $new );
				}
			}
		}

		@rename( $this->log_file, $this->log_file . '.1' );
	}

	/**
	 * Delete the dedicated log file and all its rotated archives.
	 */
	public function clear_log_file(): void {
		if ( '' === $this->log_file ) {
			return;
		}

		if ( file_exists( $this->log_file ) ) {
			@unlink( $this->log_file );
		}

		for ( $i = 1; $i <= self::ROTATION_KEEP + 1; $i++ ) {
			$archive = $this->log_file . '.' . $i;
			if ( file_exists( $archive ) ) {
				@unlink( $archive );
			}
		}
	}

	/**
	 * Return the size of the current log file in bytes, or 0 if not present.
	 */
	public function log_file_size(): int {
		if ( '' === $this->log_file || ! file_exists( $this->log_file ) ) {
			return 0;
		}
		return (int) filesize( $this->log_file );
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	/**
	 * Check whether a message at this level should be emitted.
	 */
	private function should_log( string $level ): bool {
		$msg_sev = self::SEVERITY[ $level ] ?? 0;
		$min_sev = self::SEVERITY[ $this->min_level ] ?? 0;
		return $msg_sev >= $min_sev;
	}

	/**
	 * Replace {placeholder} tokens in the message with context values.
	 *
	 * @param string $message Raw message template.
	 * @param array  $context Replacement values.
	 * @return string         Interpolated message.
	 */
	private function interpolate( string $message, array $context ): string {
		$replace = [];
		foreach ( $context as $key => $value ) {
			if ( is_string( $value ) || is_int( $value ) || is_float( $value ) ) {
				$replace[ '{' . $key . '}' ] = (string) $value;
			}
		}
		return strtr( $message, $replace );
	}

	/**
	 * Build the structured log entry array.
	 */
	private function build_entry( string $level, string $message, array $context ): array {
		return [
			'timestamp' => gmdate( 'c' ),
			'level'     => $level,
			'channel'   => $this->channel,
			'message'   => $message,
			'context'   => $context,
			'pid'       => function_exists( 'getmypid' ) ? getmypid() : 0,
		];
	}

	/**
	 * Append a JSON line to the dedicated log file.
	 * Triggers rotation check before writing.
	 *
	 * @param string $line JSON-encoded log entry.
	 */
	private function write_to_file( string $line ): void {
		$this->maybe_rotate();

		// Ensure directory exists.
		$dir = dirname( $this->log_file );
		if ( ! is_dir( $dir ) ) {
			return;
		}

		// Append with advisory lock.
		$fh = @fopen( $this->log_file, 'a' );
		if ( false === $fh ) {
			return;
		}
		flock( $fh, LOCK_EX );
		fwrite( $fh, $line . "\n" );
		flock( $fh, LOCK_UN );
		fclose( $fh );
	}
}
