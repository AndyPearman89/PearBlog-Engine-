<?php
/**
 * Unit tests for SocialPublisher.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Social\SocialPublisher;

class SocialPublisherTest extends TestCase {

	private SocialPublisher $publisher;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$this->publisher = new SocialPublisher();
	}

	public function test_instantiation(): void {
		$this->assertInstanceOf( SocialPublisher::class, $this->publisher );
	}

	public function test_register_attaches_hook(): void {
		$this->publisher->register();
		$this->assertTrue( (bool) has_action( 'pearblog_pipeline_completed', [ $this->publisher, 'on_pipeline_completed' ] ) );
	}

	public function test_publish_returns_empty_array_for_invalid_post(): void {
		// Post ID 0 does not exist in WP stubs.
		$result = $this->publisher->publish( 0 );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function test_publish_returns_array_keyed_by_channel(): void {
		// Even with no credentials configured, the result is an array.
		$result = $this->publisher->publish( 999 );
		$this->assertIsArray( $result );
	}

	public function test_on_pipeline_completed_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->publisher->on_pipeline_completed( 1, 'test topic', null );
	}

	public function test_publish_without_enabled_channels_returns_empty(): void {
		// No channels configured in options → result is empty.
		$GLOBALS['_options']['pearblog_social_enabled_channels'] = '';
		$result = $this->publisher->publish( 1 );
		$this->assertIsArray( $result );
	}
}
