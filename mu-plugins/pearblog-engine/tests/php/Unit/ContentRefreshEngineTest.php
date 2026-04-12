<?php
/**
 * Unit tests for ContentRefreshEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\ContentRefreshEngine;

class ContentRefreshEngineTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_post_meta']  = [];
	}

	public function test_constants_are_defined(): void {
		$this->assertSame( 'pearblog_content_refresh', ContentRefreshEngine::CRON_HOOK );
		$this->assertSame( 90, ContentRefreshEngine::DEFAULT_STALE_DAYS );
		$this->assertSame( 3, ContentRefreshEngine::DEFAULT_BATCH_SIZE );
	}

	public function test_meta_key_constants(): void {
		$this->assertSame( '_pearblog_refreshed_at', ContentRefreshEngine::META_REFRESHED_AT );
		$this->assertSame( '_pearblog_refresh_count', ContentRefreshEngine::META_REFRESH_COUNT );
		$this->assertSame( '_pearblog_traffic_trend', ContentRefreshEngine::META_TRAFFIC_TREND );
	}

	public function test_instantiation_without_dependencies(): void {
		$engine = new ContentRefreshEngine();
		$this->assertInstanceOf( ContentRefreshEngine::class, $engine );
	}

	public function test_register_attaches_hooks(): void {
		$engine = new ContentRefreshEngine();
		$engine->register();
		$this->assertTrue( (bool) has_action( ContentRefreshEngine::CRON_HOOK, [ $engine, 'run_batch' ] ) );
	}

	public function test_maybe_schedule_does_not_throw(): void {
		$engine = new ContentRefreshEngine();
		// wp_next_scheduled stub returns false → schedules event.
		$this->expectNotToPerformAssertions();
		$engine->maybe_schedule();
	}

	public function test_run_batch_returns_empty_array_when_no_stale_posts(): void {
		$engine = new ContentRefreshEngine();
		// WP stubs return empty query results.
		$result = $engine->run_batch();
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}
}
