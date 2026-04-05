<?php
/**
 * Unit tests for TopicQueue.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Content\TopicQueue;
use PHPUnit\Framework\TestCase;

class TopicQueueTest extends TestCase {

	private TopicQueue $queue;

	protected function setUp(): void {
		$GLOBALS['_wp_test_options'] = [];
		$this->queue = new TopicQueue( 1 );
	}

	public function test_empty_queue(): void {
		$this->assertSame( 0, $this->queue->count() );
		$this->assertNull( $this->queue->pop() );
		$this->assertNull( $this->queue->peek() );
		$this->assertSame( [], $this->queue->all() );
	}

	public function test_push_and_pop(): void {
		$this->queue->push( 'Topic A', 'Topic B', 'Topic C' );

		$this->assertSame( 3, $this->queue->count() );
		$this->assertSame( 'Topic A', $this->queue->pop() );
		$this->assertSame( 2, $this->queue->count() );
		$this->assertSame( 'Topic B', $this->queue->pop() );
		$this->assertSame( 1, $this->queue->count() );
	}

	public function test_peek_does_not_remove(): void {
		$this->queue->push( 'Topic X' );

		$this->assertSame( 'Topic X', $this->queue->peek() );
		$this->assertSame( 1, $this->queue->count() );
	}

	public function test_clear(): void {
		$this->queue->push( 'A', 'B', 'C' );
		$this->queue->clear();

		$this->assertSame( 0, $this->queue->count() );
		$this->assertNull( $this->queue->pop() );
	}

	public function test_fifo_order(): void {
		$this->queue->push( 'First', 'Second', 'Third' );

		$this->assertSame( 'First', $this->queue->pop() );
		$this->assertSame( 'Second', $this->queue->pop() );
		$this->assertSame( 'Third', $this->queue->pop() );
	}

	public function test_empty_strings_are_ignored(): void {
		$this->queue->push( '', '  ', 'Valid Topic', '' );

		$this->assertSame( 1, $this->queue->count() );
		$this->assertSame( 'Valid Topic', $this->queue->pop() );
	}

	public function test_all_returns_array_values(): void {
		$this->queue->push( 'A', 'B' );
		$this->queue->pop(); // Remove 'A'

		$all = $this->queue->all();

		$this->assertSame( [ 'B' ], $all );
		// Ensure keys are re-indexed (0-based).
		$this->assertArrayHasKey( 0, $all );
	}

	public function test_separate_sites_have_separate_queues(): void {
		$queue_site1 = new TopicQueue( 1 );
		$queue_site2 = new TopicQueue( 2 );

		$queue_site1->push( 'Site 1 Topic' );
		$queue_site2->push( 'Site 2 Topic' );

		$this->assertSame( 'Site 1 Topic', $queue_site1->pop() );
		$this->assertSame( 'Site 2 Topic', $queue_site2->pop() );
	}
}
