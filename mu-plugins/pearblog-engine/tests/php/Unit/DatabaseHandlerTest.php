<?php
/**
 * Unit tests for DatabaseHandler
 *
 * Tests database logging, buffering, querying, and pruning functionality.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Logging\DatabaseHandler;

/**
 * Test DatabaseHandler class
 */
class DatabaseHandlerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// Reset global database state - Don't override the bootstrap $wpdb
		$GLOBALS['_db_inserts'] = [];
		$GLOBALS['_db_queries'] = [];
		$GLOBALS['_db_results'] = [];
		$GLOBALS['_db_affected_rows'] = 0;
		$GLOBALS['_db_level_counts'] = [];
		$GLOBALS['_db_channel_counts'] = [];
	}

	/**
	 * Test handler instantiation
	 */
	public function test_handler_instantiation(): void {
		$handler = new DatabaseHandler();
		$this->assertInstanceOf( DatabaseHandler::class, $handler );
	}

	/**
	 * Test handler with custom min level
	 */
	public function test_handler_with_custom_min_level(): void {
		$handler = new DatabaseHandler( 'ERROR' );
		$this->assertInstanceOf( DatabaseHandler::class, $handler );
	}

	/**
	 * Test immediate write when buffer size is 0
	 */
	public function test_immediate_write_when_buffer_zero(): void {
		$handler = new DatabaseHandler( 'INFO', 0 );

		$record = [
			'timestamp' => '2026-05-03 15:00:00',
			'level'     => 'INFO',
			'channel'   => 'test',
			'message'   => 'Test message',
			'context'   => [ 'key' => 'value' ],
			'extra'     => [],
		];

		$result = $handler->handle( $record );

		$this->assertTrue( $result );
		$this->assertCount( 1, $GLOBALS['_db_inserts'] );
	}

	/**
	 * Test buffered writes
	 */
	public function test_buffered_writes(): void {
		$handler = new DatabaseHandler( 'INFO', 3 );

		$record = [
			'timestamp' => '2026-05-03 15:00:00',
			'level'     => 'INFO',
			'channel'   => 'test',
			'message'   => 'Message 1',
			'context'   => [],
			'extra'     => [],
		];

		// Write 2 records - should be buffered
		$handler->handle( $record );
		$handler->handle( $record );

		$this->assertCount( 0, $GLOBALS['_db_inserts'] ); // Not flushed yet

		// Write 3rd record - should trigger flush
		$handler->handle( $record );

		$this->assertCount( 1, $GLOBALS['_db_inserts'] ); // Flushed
	}

	/**
	 * Test manual flush
	 */
	public function test_manual_flush(): void {
		$handler = new DatabaseHandler( 'INFO', 10 );

		$record = [
			'timestamp' => '2026-05-03 15:00:00',
			'level'     => 'INFO',
			'channel'   => 'test',
			'message'   => 'Test message',
			'context'   => [],
			'extra'     => [],
		];

		$handler->handle( $record );
		$handler->handle( $record );

		$this->assertCount( 0, $GLOBALS['_db_inserts'] );

		$handler->flush();

		$this->assertCount( 1, $GLOBALS['_db_inserts'] );
	}

	/**
	 * Test level filtering
	 */
	public function test_level_filtering(): void {
		$handler = new DatabaseHandler( 'ERROR', 0 );

		$info_record = [
			'timestamp' => '2026-05-03 15:00:00',
			'level'     => 'INFO',
			'channel'   => 'test',
			'message'   => 'Info message',
			'context'   => [],
			'extra'     => [],
		];

		$error_record = [
			'timestamp' => '2026-05-03 15:00:00',
			'level'     => 'ERROR',
			'channel'   => 'test',
			'message'   => 'Error message',
			'context'   => [],
			'extra'     => [],
		];

		$handler->handle( $info_record );
		$this->assertCount( 0, $GLOBALS['_db_inserts'] ); // Filtered out

		$handler->handle( $error_record );
		$this->assertCount( 1, $GLOBALS['_db_inserts'] ); // Written
	}

	/**
	 * Test query logs method
	 */
	public function test_query_logs(): void {
		$handler = new DatabaseHandler();

		// Set up mock data
		$GLOBALS['_db_results'] = [
			[
				'id'         => 1,
				'timestamp'  => '2026-05-03 15:00:00',
				'level'      => 'ERROR',
				'channel'    => 'test',
				'message'    => 'Test error',
				'context'    => '{}',
				'extra'      => '{}',
				'created_at' => '2026-05-03 15:00:00',
			],
		];

		$logs = $handler->query_logs( [
			'level' => 'ERROR',
			'limit' => 10,
		] );

		$this->assertIsArray( $logs );
		$this->assertCount( 1, $logs );
		$this->assertSame( 'ERROR', $logs[0]['level'] );
	}

	/**
	 * Test prune logs
	 */
	public function test_prune_logs(): void {
		$handler = new DatabaseHandler();

		$GLOBALS['_db_affected_rows'] = 5;

		$deleted = $handler->prune_logs( 30 );

		$this->assertSame( 5, $deleted );
		$this->assertCount( 1, $GLOBALS['_db_queries'] );
	}

	/**
	 * Test get stats
	 */
	public function test_get_stats(): void {
		$handler = new DatabaseHandler();

		// Mock total count
		$GLOBALS['_db_results'] = [
			[ 'count' => 100 ],
		];

		// Mock level counts
		$GLOBALS['_db_level_counts'] = [
			[ 'level' => 'ERROR', 'count' => 10 ],
			[ 'level' => 'INFO', 'count' => 90 ],
		];

		// Mock channel counts
		$GLOBALS['_db_channel_counts'] = [
			[ 'channel' => 'pipeline', 'count' => 50 ],
			[ 'channel' => 'ai', 'count' => 50 ],
		];

		$stats = $handler->get_stats();

		$this->assertArrayHasKey( 'total', $stats );
		$this->assertArrayHasKey( 'by_level', $stats );
		$this->assertArrayHasKey( 'by_channel', $stats );
	}

	/**
	 * Test handler can be disabled
	 */
	public function test_handler_can_be_disabled(): void {
		$handler = new DatabaseHandler( 'INFO', 0 );

		$record = [
			'timestamp' => '2026-05-03 15:00:00',
			'level'     => 'INFO',
			'channel'   => 'test',
			'message'   => 'Test message',
			'context'   => [],
			'extra'     => [],
		];

		$handler->disable();
		$result = $handler->handle( $record );

		$this->assertFalse( $result );
		$this->assertCount( 0, $GLOBALS['_db_inserts'] );
	}

	/**
	 * Test handler can be enabled
	 */
	public function test_handler_can_be_enabled(): void {
		$handler = new DatabaseHandler( 'INFO', 0 );
		$handler->disable();

		$this->assertFalse( $handler->is_enabled() );

		$handler->enable();

		$this->assertTrue( $handler->is_enabled() );
	}

	/**
	 * Test JSON encoding of context
	 */
	public function test_json_encoding_of_context(): void {
		$handler = new DatabaseHandler( 'INFO', 0 );

		$record = [
			'timestamp' => '2026-05-03 15:00:00',
			'level'     => 'INFO',
			'channel'   => 'test',
			'message'   => 'Test message',
			'context'   => [ 'user_id' => 123, 'action' => 'login' ],
			'extra'     => [ 'memory' => '128MB' ],
		];

		$handler->handle( $record );

		$insert = $GLOBALS['_db_inserts'][0];
		$this->assertArrayHasKey( 'context', $insert );
		$this->assertIsString( $insert['context'] );

		$decoded = json_decode( $insert['context'], true );
		$this->assertSame( 123, $decoded['user_id'] );
	}
}
