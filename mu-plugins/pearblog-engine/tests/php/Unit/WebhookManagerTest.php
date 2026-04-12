<?php
/**
 * Unit tests for WebhookManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Webhook\WebhookManager;

class WebhookManagerTest extends TestCase {

	private WebhookManager $manager;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$this->manager = new WebhookManager();
	}

	public function test_instantiation(): void {
		$this->assertInstanceOf( WebhookManager::class, $this->manager );
	}

	public function test_register_attaches_hooks(): void {
		$this->manager->register();
		$this->assertTrue( (bool) has_action( 'pearblog_pipeline_completed', [ $this->manager, 'on_article_published' ] ) );
		$this->assertTrue( (bool) has_action( 'pearblog_quality_scored', [ $this->manager, 'on_quality_scored' ] ) );
		$this->assertTrue( (bool) has_action( 'pearblog_content_refreshed', [ $this->manager, 'on_content_refreshed' ] ) );
	}

	public function test_dispatch_does_not_throw_when_no_webhooks_configured(): void {
		$this->expectNotToPerformAssertions();
		$this->manager->dispatch( 'pearblog.article_published', [ 'post_id' => 1 ] );
	}

	public function test_dispatch_error_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->manager->dispatch_error( 'Pipeline failed', [ 'topic' => 'test' ] );
	}

	public function test_dispatch_with_webhook_registered_calls_endpoint(): void {
		// Register a webhook endpoint.
		$webhooks = [
			[ 'url' => 'https://example.com/webhook', 'events' => [ 'pearblog.article_published' ], 'secret' => 'mysecret' ],
		];
		$GLOBALS['_options']['pearblog_webhooks'] = serialize( $webhooks );

		// Dispatch should not throw (HTTP call stubbed).
		$this->expectNotToPerformAssertions();
		$this->manager->dispatch( 'pearblog.article_published', [ 'post_id' => 1 ] );
	}

	public function test_on_article_published_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->manager->on_article_published( 1, 'test topic', null );
	}

	public function test_on_quality_scored_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->manager->on_quality_scored( 1, 85.5 );
	}

	public function test_on_content_refreshed_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->manager->on_content_refreshed( 1, 'Refreshed Article' );
	}
}
