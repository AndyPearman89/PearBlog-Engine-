<?php
/**
 * Legacy Logger Handler
 *
 * Bridges the new advanced logging system with the existing
 * PearBlogEngine\Monitoring\Logger for backward compatibility.
 *
 * @package PearBlogEngine\Logging
 */

declare(strict_types=1);

namespace PearBlogEngine\Logging;

use PearBlogEngine\Monitoring\Logger;

/**
 * Handler that delegates to the legacy Logger
 */
class LegacyLoggerHandler extends AbstractHandler {

	/** @var Logger Legacy logger instance */
	private Logger $legacy_logger;

	/**
	 * Constructor
	 *
	 * @param string $min_level Minimum log level
	 */
	public function __construct( string $min_level = 'DEBUG' ) {
		parent::__construct( $min_level );
		$this->legacy_logger = Logger::get_instance();
	}

	/**
	 * Write to legacy logger
	 *
	 * @param array $record Log record
	 * @return bool Success
	 */
	protected function write( array $record ): bool {
		$level   = $record['level'] ?? 'INFO';
		$message = $record['message'] ?? '';
		$context = $record['context'] ?? [];

		// Merge extra context from processors
		if ( ! empty( $record['extra'] ) ) {
			$context['extra'] = $record['extra'];
		}

		// Add exception details to context
		if ( ! empty( $record['exception'] ) ) {
			$context['exception'] = $record['exception'];
		}

		// Call appropriate method on legacy logger
		switch ( $level ) {
			case 'DEBUG':
				$this->legacy_logger->debug( $message, $context );
				break;
			case 'INFO':
				$this->legacy_logger->info( $message, $context );
				break;
			case 'NOTICE':
				$this->legacy_logger->notice( $message, $context );
				break;
			case 'WARNING':
				$this->legacy_logger->warning( $message, $context );
				break;
			case 'ERROR':
				$this->legacy_logger->error( $message, $context );
				break;
			case 'CRITICAL':
				$this->legacy_logger->critical( $message, $context );
				break;
			case 'ALERT':
				$this->legacy_logger->alert( $message, $context );
				break;
			case 'EMERGENCY':
				$this->legacy_logger->emergency( $message, $context );
				break;
			default:
				$this->legacy_logger->log( $level, $message, $context );
		}

		return true;
	}
}
