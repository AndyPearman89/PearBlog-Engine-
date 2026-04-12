<?php
/**
 * Unit tests for Logger.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Monitoring\Logger;

class LoggerTest extends TestCase {

	private Logger $logger;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		// Create a logger with empty log file path so no file I/O occurs.
		$this->logger = new Logger( 'test', Logger::DEBUG, '' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->logger->clear_memory();
	}

	public function test_level_constants_are_defined(): void {
		$this->assertSame( 'DEBUG', Logger::DEBUG );
		$this->assertSame( 'INFO', Logger::INFO );
		$this->assertSame( 'WARNING', Logger::WARNING );
		$this->assertSame( 'ERROR', Logger::ERROR );
		$this->assertSame( 'CRITICAL', Logger::CRITICAL );
	}

	public function test_info_message_appears_in_memory_log(): void {
		$this->logger->info( 'Hello world' );
		$recent = $this->logger->get_recent();
		$this->assertCount( 1, $recent );
		$this->assertSame( 'Hello world', $recent[0]['message'] );
		$this->assertSame( Logger::INFO, $recent[0]['level'] );
	}

	public function test_debug_message_appears_when_min_level_is_debug(): void {
		$this->logger->debug( 'Debug message' );
		$recent = $this->logger->get_recent();
		$this->assertCount( 1, $recent );
		$this->assertSame( Logger::DEBUG, $recent[0]['level'] );
	}

	public function test_debug_message_suppressed_when_min_level_is_warning(): void {
		$logger = new Logger( 'test', Logger::WARNING, '' );
		$logger->debug( 'This should not appear' );
		$this->assertCount( 0, $logger->get_recent() );
	}

	public function test_multiple_levels_all_stored(): void {
		$this->logger->info( 'Info' );
		$this->logger->warning( 'Warning' );
		$this->logger->error( 'Error' );
		$this->assertCount( 3, $this->logger->get_recent() );
	}

	public function test_filter_by_min_level(): void {
		$this->logger->info( 'Info' );
		$this->logger->warning( 'Warning' );
		$this->logger->error( 'Error' );

		$warnings_and_above = $this->logger->get_recent( Logger::WARNING );
		$this->assertCount( 2, $warnings_and_above );
		foreach ( $warnings_and_above as $entry ) {
			$this->assertContains( $entry['level'], [ Logger::WARNING, Logger::ERROR ] );
		}
	}

	public function test_placeholder_interpolation(): void {
		$this->logger->info( 'User {user} logged in from {ip}', [ 'user' => 'alice', 'ip' => '127.0.0.1' ] );
		$recent = $this->logger->get_recent();
		$this->assertSame( 'User alice logged in from 127.0.0.1', $recent[0]['message'] );
	}

	public function test_context_stored_in_entry(): void {
		$this->logger->error( 'Error occurred', [ 'code' => '500', 'url' => '/api' ] );
		$recent = $this->logger->get_recent();
		$this->assertArrayHasKey( 'context', $recent[0] );
		$this->assertSame( '500', $recent[0]['context']['code'] );
	}

	public function test_clear_memory_empties_log(): void {
		$this->logger->info( 'Message' );
		$this->assertCount( 1, $this->logger->get_recent() );

		$this->logger->clear_memory();
		$this->assertCount( 0, $this->logger->get_recent() );
	}

	public function test_entry_has_required_keys(): void {
		$this->logger->warning( 'Test' );
		$entry = $this->logger->get_recent()[0];

		$this->assertArrayHasKey( 'timestamp', $entry );
		$this->assertArrayHasKey( 'level', $entry );
		$this->assertArrayHasKey( 'channel', $entry );
		$this->assertArrayHasKey( 'message', $entry );
		$this->assertArrayHasKey( 'context', $entry );
	}

	public function test_log_file_size_returns_zero_when_no_file(): void {
		$this->assertSame( 0, $this->logger->log_file_size() );
	}

	public function test_singleton_returns_same_instance(): void {
		$a = Logger::get_instance();
		$b = Logger::get_instance();
		$this->assertSame( $a, $b );
	}
}
