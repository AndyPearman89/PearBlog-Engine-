<?php
/**
 * Unit tests for MemoryUsageProcessor
 *
 * Tests memory usage tracking and formatting.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Logging\MemoryUsageProcessor;

/**
 * Test MemoryUsageProcessor
 */
class MemoryUsageProcessorTest extends TestCase {

	/**
	 * Test processor adds memory usage to record
	 */
	public function test_adds_memory_usage_to_record(): void {
		$processor = new MemoryUsageProcessor();

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'extra', $processed );
		$this->assertArrayHasKey( 'memory_usage', $processed['extra'] );
	}

	/**
	 * Test processor adds peak memory when enabled
	 */
	public function test_adds_peak_memory_when_enabled(): void {
		$processor = new MemoryUsageProcessor( true );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'memory_peak', $processed['extra'] );
	}

	/**
	 * Test processor does not add peak memory when disabled
	 */
	public function test_does_not_add_peak_memory_when_disabled(): void {
		$processor = new MemoryUsageProcessor( false );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayNotHasKey( 'memory_peak', $processed['extra'] );
	}

	/**
	 * Test memory is formatted as human-readable by default
	 */
	public function test_formats_memory_as_human_readable(): void {
		$processor = new MemoryUsageProcessor( true, true );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertIsString( $processed['extra']['memory_usage'] );
		$this->assertMatchesRegularExpression( '/\d+(\.\d+)?\s+(B|KB|MB|GB)/', $processed['extra']['memory_usage'] );
	}

	/**
	 * Test memory is raw bytes when formatting disabled
	 */
	public function test_returns_raw_bytes_when_formatting_disabled(): void {
		$processor = new MemoryUsageProcessor( true, false );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertIsInt( $processed['extra']['memory_usage'] );
	}
}
