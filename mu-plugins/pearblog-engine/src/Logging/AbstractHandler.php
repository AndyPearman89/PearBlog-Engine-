<?php
/**
 * Abstract Log Handler
 *
 * Base class for all log handlers. Handlers are responsible for writing
 * log records to specific destinations (file, database, external service, etc.).
 *
 * @package PearBlogEngine\Logging
 */

declare(strict_types=1);

namespace PearBlogEngine\Logging;

/**
 * Abstract Handler for processing log records
 */
abstract class AbstractHandler {

	/** @var string Minimum log level this handler will process */
	protected string $min_level;

	/** @var array List of processors to apply to records */
	protected array $processors = [];

	/** @var bool Whether this handler is enabled */
	protected bool $enabled = true;

	/**
	 * Log level severity mapping
	 */
	protected const LEVELS = [
		'DEBUG'     => 100,
		'INFO'      => 200,
		'NOTICE'    => 250,
		'WARNING'   => 300,
		'ERROR'     => 400,
		'CRITICAL'  => 500,
		'ALERT'     => 550,
		'EMERGENCY' => 600,
	];

	/**
	 * Constructor
	 *
	 * @param string $min_level Minimum level to handle
	 */
	public function __construct( string $min_level = 'DEBUG' ) {
		$this->min_level = $min_level;
	}

	/**
	 * Handle a log record
	 *
	 * @param array $record Log record array
	 * @return bool Whether the record was handled
	 */
	public function handle( array $record ): bool {
		if ( ! $this->enabled ) {
			return false;
		}

		if ( ! $this->is_handling( $record ) ) {
			return false;
		}

		// Apply processors
		foreach ( $this->processors as $processor ) {
			$record = $processor->process( $record );
		}

		return $this->write( $record );
	}

	/**
	 * Check if this handler should handle the record
	 *
	 * @param array $record Log record
	 * @return bool
	 */
	protected function is_handling( array $record ): bool {
		$level          = $record['level'] ?? 'DEBUG';
		$record_level   = self::LEVELS[ $level ] ?? 0;
		$min_level_num  = self::LEVELS[ $this->min_level ] ?? 0;

		return $record_level >= $min_level_num;
	}

	/**
	 * Write the record (must be implemented by subclasses)
	 *
	 * @param array $record Processed log record
	 * @return bool Success status
	 */
	abstract protected function write( array $record ): bool;

	/**
	 * Add a processor to this handler
	 *
	 * @param ProcessorInterface $processor
	 * @return self
	 */
	public function add_processor( ProcessorInterface $processor ): self {
		$this->processors[] = $processor;
		return $this;
	}

	/**
	 * Enable this handler
	 */
	public function enable(): void {
		$this->enabled = true;
	}

	/**
	 * Disable this handler
	 */
	public function disable(): void {
		$this->enabled = false;
	}

	/**
	 * Check if handler is enabled
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}
}
