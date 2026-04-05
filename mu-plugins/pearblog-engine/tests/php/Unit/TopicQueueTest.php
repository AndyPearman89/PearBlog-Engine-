<?php
/**
 * Unit tests for TopicQueue.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\TopicQueue;

class TopicQueueTest extends TestCase {

	private TopicQueue $queue;

	protected function setUp(): void {
		parent::setUp();
		// Reset the WP option store before each test.
		$GLOBALS['_options'] = [];
		$this->queue = new TopicQueue( 1 );
	}

	public function test_new_queue_is_empty(): void {
		$this->assertSame( 0, $this->queue->count() );
		$this->assertSame( [], $this->queue->all() );
	}

	public function test_push_single_topic(): void {
		$this->queue->push( 'Topic A' );
		$this->assertSame( 1, $this->queue->count() );
		$this->assertSame( 'Topic A', $this->queue->peek() );
	}

	public function test_push_multiple_topics_in_order(): void {
		$this->queue->push( 'Topic A', 'Topic B', 'Topic C' );
		$this->assertSame( [ 'Topic A', 'Topic B', 'Topic C' ], $this->queue->all() );
	}

	public function test_pop_removes_and_returns_first_topic(): void {
		$this->queue->push( 'First', 'Second' );

		$topic = $this->queue->pop();

		$this->assertSame( 'First', $topic );
		$this->assertSame( 1, $this->queue->count() );
	}

	public function test_pop_empty_queue_returns_null(): void {
		$this->assertNull( $this->queue->pop() );
	}

	public function test_peek_does_not_remove_topic(): void {
		$this->queue->push( 'Peek Topic' );

		$this->queue->peek();

		$this->assertSame( 1, $this->queue->count() );
	}

	public function test_clear_empties_queue(): void {
		$this->queue->push( 'A', 'B', 'C' );
		$this->queue->clear();

		$this->assertSame( 0, $this->queue->count() );
	}

	public function test_push_trims_whitespace(): void {
		$this->queue->push( '  My Topic  ' );
		$this->assertSame( 'My Topic', $this->queue->peek() );
	}

	public function test_push_ignores_empty_strings(): void {
		$this->queue->push( '', '   ', 'Valid Topic' );
		$this->assertSame( 1, $this->queue->count() );
	}

	public function test_fifo_order_is_preserved(): void {
		$topics = [ 'First', 'Second', 'Third', 'Fourth' ];
		$this->queue->push( ...$topics );

		$popped = [];
		while ( $this->queue->count() > 0 ) {
			$popped[] = $this->queue->pop();
		}

		$this->assertSame( $topics, $popped );
	}

	public function test_multiple_sites_have_isolated_queues(): void {
		$queue1 = new TopicQueue( 1 );
		$queue2 = new TopicQueue( 2 );

		$queue1->push( 'Site 1 Topic' );

		$this->assertSame( 1, $queue1->count() );
		$this->assertSame( 0, $queue2->count() );
	}
}
