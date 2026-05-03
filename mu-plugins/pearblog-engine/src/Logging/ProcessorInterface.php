<?php
/**
 * Processor Interface
 *
 * Processors add extra information to log records before they are handled.
 * They can add context like memory usage, request ID, user info, etc.
 *
 * @package PearBlogEngine\Logging
 */

declare(strict_types=1);

namespace PearBlogEngine\Logging;

/**
 * Processor Interface
 */
interface ProcessorInterface {

	/**
	 * Process a log record and return the modified record
	 *
	 * @param array $record Original log record
	 * @return array Modified log record
	 */
	public function process( array $record ): array;
}
