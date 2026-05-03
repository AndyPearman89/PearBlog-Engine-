<?php
/**
 * Unit tests for AdvancedLogger
 *
 * Tests PSR-3 compliance, handler registration, processor application,
 * and performance metrics tracking.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Logging\AdvancedLogger;
use PearBlogEngine\Logging\AbstractHandler;
use PearBlogEngine\Logging\ProcessorInterface;

/**
 * Test AdvancedLogger class
 */
class AdvancedLoggerTest extends TestCase {

	/**
	 * Test logger instantiation with default channel
	 */
	public function test_logger_instantiation_with_default_channel(): void {
		$logger = new AdvancedLogger();
		$this->assertInstanceOf( AdvancedLogger::class, $logger );
	}

	/**
	 * Test logger instantiation with custom channel
	 */
	public function test_logger_instantiation_with_custom_channel(): void {
		$logger = new AdvancedLogger( 'test-channel' );
		$this->assertInstanceOf( AdvancedLogger::class, $logger );
	}

	/**
	 * Test singleton instance creation
	 */
	public function test_get_instance_returns_singleton(): void {
		$logger1 = AdvancedLogger::get_instance();
		$logger2 = AdvancedLogger::get_instance();

		$this->assertSame( $logger1, $logger2 );
	}

	/**
	 * Test adding handlers
	 */
	public function test_add_handler(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();

		$result = $logger->add_handler( $handler );

		$this->assertSame( $logger, $result ); // Fluent interface
		$this->assertContains( $handler, $logger->get_handlers() );
	}

	/**
	 * Test adding processors
	 */
	public function test_add_processor(): void {
		$logger = new AdvancedLogger();
		$processor = new TestProcessor();

		$result = $logger->add_processor( $processor );

		$this->assertSame( $logger, $result ); // Fluent interface
	}

	/**
	 * Test debug logging
	 */
	public function test_debug_logging(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->debug( 'Debug message', [ 'key' => 'value' ] );

		$this->assertCount( 1, $handler->records );
		$this->assertSame( 'DEBUG', $handler->records[0]['level'] );
		$this->assertSame( 'Debug message', $handler->records[0]['message'] );
	}

	/**
	 * Test info logging
	 */
	public function test_info_logging(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->info( 'Info message' );

		$this->assertCount( 1, $handler->records );
		$this->assertSame( 'INFO', $handler->records[0]['level'] );
	}

	/**
	 * Test notice logging
	 */
	public function test_notice_logging(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->notice( 'Notice message' );

		$this->assertCount( 1, $handler->records );
		$this->assertSame( 'NOTICE', $handler->records[0]['level'] );
	}

	/**
	 * Test warning logging
	 */
	public function test_warning_logging(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->warning( 'Warning message' );

		$this->assertCount( 1, $handler->records );
		$this->assertSame( 'WARNING', $handler->records[0]['level'] );
	}

	/**
	 * Test error logging
	 */
	public function test_error_logging(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->error( 'Error message' );

		$this->assertCount( 1, $handler->records );
		$this->assertSame( 'ERROR', $handler->records[0]['level'] );
	}

	/**
	 * Test critical logging
	 */
	public function test_critical_logging(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->critical( 'Critical message' );

		$this->assertCount( 1, $handler->records );
		$this->assertSame( 'CRITICAL', $handler->records[0]['level'] );
	}

	/**
	 * Test alert logging
	 */
	public function test_alert_logging(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->alert( 'Alert message' );

		$this->assertCount( 1, $handler->records );
		$this->assertSame( 'ALERT', $handler->records[0]['level'] );
	}

	/**
	 * Test emergency logging
	 */
	public function test_emergency_logging(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->emergency( 'Emergency message' );

		$this->assertCount( 1, $handler->records );
		$this->assertSame( 'EMERGENCY', $handler->records[0]['level'] );
	}

	/**
	 * Test message interpolation with placeholders
	 */
	public function test_message_interpolation(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->info( 'User {user} logged in from {ip}', [
			'user' => 'john',
			'ip'   => '192.168.1.1',
		] );

		$this->assertSame( 'User john logged in from 192.168.1.1', $handler->records[0]['message'] );
	}

	/**
	 * Test exception normalization
	 */
	public function test_exception_normalization(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$exception = new \RuntimeException( 'Test exception', 500 );
		$logger->error( 'An error occurred', [ 'exception' => $exception ] );

		$this->assertCount( 1, $handler->records );
		$this->assertArrayHasKey( 'exception', $handler->records[0] );
		$this->assertIsArray( $handler->records[0]['exception'] );
		$this->assertSame( 'RuntimeException', $handler->records[0]['exception']['class'] );
		$this->assertSame( 'Test exception', $handler->records[0]['exception']['message'] );
		$this->assertSame( 500, $handler->records[0]['exception']['code'] );
	}

	/**
	 * Test processor application
	 */
	public function test_processor_application(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$processor = new TestProcessor();

		$logger->add_handler( $handler );
		$logger->add_processor( $processor );

		$logger->info( 'Test message' );

		$this->assertCount( 1, $handler->records );
		$this->assertArrayHasKey( 'extra', $handler->records[0] );
		$this->assertArrayHasKey( 'processed', $handler->records[0]['extra'] );
		$this->assertTrue( $handler->records[0]['extra']['processed'] );
	}

	/**
	 * Test multiple handlers receive the same log
	 */
	public function test_multiple_handlers(): void {
		$logger = new AdvancedLogger();
		$handler1 = new TestHandler();
		$handler2 = new TestHandler();

		$logger->add_handler( $handler1 );
		$logger->add_handler( $handler2 );

		$logger->info( 'Test message' );

		$this->assertCount( 1, $handler1->records );
		$this->assertCount( 1, $handler2->records );
		$this->assertSame( $handler1->records[0]['message'], $handler2->records[0]['message'] );
	}

	/**
	 * Test metrics tracking
	 */
	public function test_metrics_tracking(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->info( 'Message 1' );
		$logger->error( 'Message 2' );
		$logger->debug( 'Message 3' );

		$metrics = $logger->get_metrics();

		$this->assertArrayHasKey( 'logs_written', $metrics );
		$this->assertArrayHasKey( 'errors_logged', $metrics );
		$this->assertSame( 3, $metrics['logs_written'] );
		$this->assertSame( 1, $metrics['errors_logged'] );
	}

	/**
	 * Test child logger with different channel
	 */
	public function test_with_channel_creates_child_logger(): void {
		$logger = new AdvancedLogger( 'parent' );
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$child = $logger->with_channel( 'child' );
		$child->info( 'Child message' );

		$this->assertCount( 1, $handler->records );
		$this->assertSame( 'child', $handler->records[0]['channel'] );
	}

	/**
	 * Test child logger shares handlers with parent
	 */
	public function test_child_logger_shares_handlers(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$child = $logger->with_channel( 'child' );
		$child->info( 'Child message' );

		// Message should appear in parent's handler
		$this->assertCount( 1, $handler->records );
	}

	/**
	 * Test log record structure
	 */
	public function test_log_record_structure(): void {
		$logger = new AdvancedLogger();
		$handler = new TestHandler();
		$logger->add_handler( $handler );

		$logger->info( 'Test message', [ 'key' => 'value' ] );

		$record = $handler->records[0];

		$this->assertArrayHasKey( 'timestamp', $record );
		$this->assertArrayHasKey( 'level', $record );
		$this->assertArrayHasKey( 'channel', $record );
		$this->assertArrayHasKey( 'message', $record );
		$this->assertArrayHasKey( 'context', $record );
		$this->assertArrayHasKey( 'extra', $record );
	}
}

/**
 * Test handler for unit testing
 */
class TestHandler extends AbstractHandler {
	/** @var array Recorded log entries */
	public array $records = [];

	protected function write( array $record ): bool {
		$this->records[] = $record;
		return true;
	}
}

/**
 * Test processor for unit testing
 */
class TestProcessor implements ProcessorInterface {
	public function process( array $record ): array {
		$record['extra'] = $record['extra'] ?? [];
		$record['extra']['processed'] = true;
		return $record;
	}
}
