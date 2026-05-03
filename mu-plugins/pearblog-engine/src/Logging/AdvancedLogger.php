<?php
/**
 * Advanced PSR-3 Compliant Logger
 *
 * Full-featured logging system with:
 * - PSR-3 interface compliance
 * - Multiple handlers (file, database, external services)
 * - Processors for context enrichment
 * - Performance metrics tracking
 * - Error tracking integration
 * - Structured logging
 * - Log aggregation and search
 *
 * Usage:
 * ```php
 * $logger = AdvancedLogger::get_instance();
 * $logger->info('User logged in', ['user_id' => 123]);
 * $logger->error('API call failed', ['exception' => $e, 'endpoint' => '/api/generate']);
 * ```
 *
 * @package PearBlogEngine\Logging
 */

declare(strict_types=1);

namespace PearBlogEngine\Logging;

/**
 * Advanced Logger with PSR-3 compliance
 */
class AdvancedLogger implements LoggerInterface {

	/** @var self|null Singleton instance */
	private static ?self $instance = null;

	/** @var array List of handlers */
	private array $handlers = [];

	/** @var array Global processors applied to all records */
	private array $processors = [];

	/** @var string Channel name for this logger */
	private string $channel;

	/** @var array Performance metrics */
	private array $metrics = [
		'logs_written'   => 0,
		'errors_logged'  => 0,
		'start_time'     => 0,
		'total_duration' => 0,
	];

	/**
	 * Constructor
	 *
	 * @param string $channel Channel name (e.g., 'pipeline', 'ai', 'security')
	 */
	public function __construct( string $channel = 'pearblog' ) {
		$this->channel             = $channel;
		$this->metrics['start_time'] = microtime( true );
	}

	/**
	 * Get or create singleton instance
	 *
	 * @param string $channel Optional channel name
	 * @return self
	 */
	public static function get_instance( string $channel = 'pearblog' ): self {
		if ( null === self::$instance ) {
			self::$instance = new self( $channel );
			self::$instance->setup_default_handlers();
		}

		return self::$instance;
	}

	/**
	 * Setup default handlers and processors
	 */
	private function setup_default_handlers(): void {
		// Use existing Logger for backward compatibility
		$legacy_handler = new LegacyLoggerHandler( 'DEBUG' );
		$this->add_handler( $legacy_handler );

		// Add database handler for persistent logs (INFO and above)
		if ( defined( 'PEARBLOG_DATABASE_LOGGING' ) && PEARBLOG_DATABASE_LOGGING ) {
			$db_handler = new DatabaseHandler( 'INFO', 10 );
			$this->add_handler( $db_handler );
		}

		// Add processors
		$this->add_processor( new MemoryUsageProcessor() );
		$this->add_processor( new RequestContextProcessor() );
		$this->add_processor( new WordPressContextProcessor() );
	}

	/**
	 * Add a handler
	 *
	 * @param AbstractHandler $handler
	 * @return self
	 */
	public function add_handler( AbstractHandler $handler ): self {
		$this->handlers[] = $handler;
		return $this;
	}

	/**
	 * Add a processor
	 *
	 * @param ProcessorInterface $processor
	 * @return self
	 */
	public function add_processor( ProcessorInterface $processor ): self {
		$this->processors[] = $processor;
		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function emergency( string $message, array $context = [] ): void {
		$this->log( 'EMERGENCY', $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert( string $message, array $context = [] ): void {
		$this->log( 'ALERT', $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical( string $message, array $context = [] ): void {
		$this->log( 'CRITICAL', $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function error( string $message, array $context = [] ): void {
		$this->log( 'ERROR', $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning( string $message, array $context = [] ): void {
		$this->log( 'WARNING', $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice( string $message, array $context = [] ): void {
		$this->log( 'NOTICE', $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function info( string $message, array $context = [] ): void {
		$this->log( 'INFO', $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug( string $message, array $context = [] ): void {
		$this->log( 'DEBUG', $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function log( $level, string $message, array $context = [] ): void {
		$start = microtime( true );

		// Normalize level to string
		$level = strtoupper( (string) $level );

		// Build record
		$record = $this->build_record( $level, $message, $context );

		// Apply global processors
		foreach ( $this->processors as $processor ) {
			$record = $processor->process( $record );
		}

		// Handle with all handlers
		foreach ( $this->handlers as $handler ) {
			$handler->handle( $record );
		}

		// Update metrics
		$this->update_metrics( $level, microtime( true ) - $start );
	}

	/**
	 * Build a log record array
	 *
	 * @param string $level   Log level
	 * @param string $message Message template
	 * @param array  $context Context data
	 * @return array Log record
	 */
	private function build_record( string $level, string $message, array $context ): array {
		// Interpolate message
		$message = $this->interpolate( $message, $context );

		// Extract exception if present
		$exception = null;
		if ( isset( $context['exception'] ) && $context['exception'] instanceof \Throwable ) {
			$exception = $this->normalize_exception( $context['exception'] );
			unset( $context['exception'] );
		}

		return [
			'timestamp' => gmdate( 'Y-m-d H:i:s' ),
			'level'     => $level,
			'channel'   => $this->channel,
			'message'   => $message,
			'context'   => $context,
			'exception' => $exception,
			'extra'     => [], // Filled by processors
		];
	}

	/**
	 * Interpolate context values into message placeholders
	 *
	 * @param string $message Message template
	 * @param array  $context Context data
	 * @return string Interpolated message
	 */
	private function interpolate( string $message, array $context ): string {
		$replacements = [];

		foreach ( $context as $key => $value ) {
			if ( is_null( $value ) || is_scalar( $value ) || ( is_object( $value ) && method_exists( $value, '__toString' ) ) ) {
				$replacements[ '{' . $key . '}' ] = (string) $value;
			} elseif ( is_array( $value ) ) {
				$replacements[ '{' . $key . '}' ] = json_encode( $value );
			}
		}

		return strtr( $message, $replacements );
	}

	/**
	 * Normalize exception to array
	 *
	 * @param \Throwable $exception Exception to normalize
	 * @return array Normalized exception data
	 */
	private function normalize_exception( \Throwable $exception ): array {
		$data = [
			'class'   => get_class( $exception ),
			'message' => $exception->getMessage(),
			'code'    => $exception->getCode(),
			'file'    => $exception->getFile(),
			'line'    => $exception->getLine(),
			'trace'   => [],
		];

		// Add stack trace (limit to 10 frames to avoid huge logs)
		$trace = $exception->getTrace();
		$data['trace'] = array_slice( $trace, 0, 10 );

		// Add previous exception if present
		if ( $exception->getPrevious() ) {
			$data['previous'] = $this->normalize_exception( $exception->getPrevious() );
		}

		return $data;
	}

	/**
	 * Update performance metrics
	 *
	 * @param string $level    Log level
	 * @param float  $duration Duration in seconds
	 */
	private function update_metrics( string $level, float $duration ): void {
		$this->metrics['logs_written']++;
		$this->metrics['total_duration'] += $duration;

		if ( in_array( $level, [ 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' ], true ) ) {
			$this->metrics['errors_logged']++;
		}
	}

	/**
	 * Get performance metrics
	 *
	 * @return array Metrics
	 */
	public function get_metrics(): array {
		$runtime = microtime( true ) - $this->metrics['start_time'];

		return [
			'logs_written'         => $this->metrics['logs_written'],
			'errors_logged'        => $this->metrics['errors_logged'],
			'average_duration_ms'  => $this->metrics['logs_written'] > 0
				? ( $this->metrics['total_duration'] / $this->metrics['logs_written'] ) * 1000
				: 0,
			'runtime_seconds'      => $runtime,
			'logs_per_second'      => $runtime > 0 ? $this->metrics['logs_written'] / $runtime : 0,
		];
	}

	/**
	 * Create a child logger with a different channel
	 *
	 * @param string $channel Channel name
	 * @return self
	 */
	public function with_channel( string $channel ): self {
		$logger = new self( $channel );

		// Share handlers and processors
		$logger->handlers   = $this->handlers;
		$logger->processors = $this->processors;

		return $logger;
	}

	/**
	 * Get all handlers
	 *
	 * @return array
	 */
	public function get_handlers(): array {
		return $this->handlers;
	}

	/**
	 * Get database handler (if present)
	 *
	 * @return DatabaseHandler|null
	 */
	public function get_database_handler(): ?DatabaseHandler {
		foreach ( $this->handlers as $handler ) {
			if ( $handler instanceof DatabaseHandler ) {
				return $handler;
			}
		}

		return null;
	}
}
