<?php
/**
 * Memory Usage Processor
 *
 * Adds current memory usage information to log records.
 * Useful for tracking memory leaks and performance issues.
 *
 * @package PearBlogEngine\Logging
 */

declare(strict_types=1);

namespace PearBlogEngine\Logging;

/**
 * Adds memory usage to log records
 */
class MemoryUsageProcessor implements ProcessorInterface {

	/** @var bool Whether to include peak memory */
	private bool $include_peak;

	/** @var bool Whether to format values as human-readable */
	private bool $format_readable;

	/**
	 * Constructor
	 *
	 * @param bool $include_peak    Include peak memory usage
	 * @param bool $format_readable Format as human-readable (e.g., "12.5 MB")
	 */
	public function __construct( bool $include_peak = true, bool $format_readable = true ) {
		$this->include_peak    = $include_peak;
		$this->format_readable = $format_readable;
	}

	/**
	 * Process the log record
	 *
	 * @param array $record Log record
	 * @return array Modified record
	 */
	public function process( array $record ): array {
		$record['extra'] = $record['extra'] ?? [];

		$current = memory_get_usage( true );
		$peak    = memory_get_peak_usage( true );

		if ( $this->format_readable ) {
			$record['extra']['memory_usage'] = $this->format_bytes( $current );

			if ( $this->include_peak ) {
				$record['extra']['memory_peak'] = $this->format_bytes( $peak );
			}
		} else {
			$record['extra']['memory_usage'] = $current;

			if ( $this->include_peak ) {
				$record['extra']['memory_peak'] = $peak;
			}
		}

		return $record;
	}

	/**
	 * Format bytes as human-readable string
	 *
	 * @param int $bytes Number of bytes
	 * @return string Formatted string
	 */
	private function format_bytes( int $bytes ): string {
		$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];

		for ( $i = 0; $bytes > 1024 && $i < count( $units ) - 1; $i++ ) {
			$bytes /= 1024;
		}

		return round( $bytes, 2 ) . ' ' . $units[ $i ];
	}
}
