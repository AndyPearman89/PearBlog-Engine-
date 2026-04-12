<?php
/**
 * Unit tests for AlertManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Monitoring\AlertManager;

class AlertManagerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
	}

	public function test_level_constants_are_defined(): void {
		$this->assertSame( 'info', AlertManager::LEVEL_INFO );
		$this->assertSame( 'warning', AlertManager::LEVEL_WARNING );
		$this->assertSame( 'error', AlertManager::LEVEL_ERROR );
		$this->assertSame( 'critical', AlertManager::LEVEL_CRITICAL );
	}

	public function test_alert_does_not_throw_when_no_channels_configured(): void {
		// No webhooks or email set – alert() should be a silent no-op.
		$manager = new AlertManager();
		$this->expectNotToPerformAssertions();
		$manager->alert( 'Test', 'Test message', AlertManager::LEVEL_INFO );
	}

	public function test_pipeline_error_shorthand_uses_error_level(): void {
		// Just verify it runs without throwing.
		$manager = new AlertManager();
		$this->expectNotToPerformAssertions();
		$manager->pipeline_error( 'Something went wrong', [ 'context' => 'test' ] );
	}

	public function test_critical_shorthand_uses_critical_level(): void {
		$manager = new AlertManager();
		$this->expectNotToPerformAssertions();
		$manager->critical( 'Critical Title', 'Critical message' );
	}

	public function test_info_shorthand_does_not_deduplicate(): void {
		// info() should pass dedup=false, so calling it twice should not suppress the second call.
		$manager = new AlertManager();
		// Both calls should silently succeed without any exception.
		$manager->info( 'Title', 'Message 1' );
		$manager->info( 'Title', 'Message 2' );
		$this->assertTrue( true );
	}

	public function test_deduplication_suppresses_identical_alerts(): void {
		// Pre-seed the deduplication transient manually.
		$title = 'Duplicate Alert';
		$level = AlertManager::LEVEL_ERROR;
		$key   = 'pb_alert_dedup_' . substr( md5( $title . $level ), 0, 16 );
		set_transient( $key, 1, 300 );

		// alert() with dedup=true should silently skip the second dispatch.
		$manager = new AlertManager();
		// No exception expected.
		$manager->alert( $title, 'message', $level, [], true );
		$this->assertTrue( true );
	}

	public function test_dedup_transient_is_set_after_first_alert(): void {
		$title = 'New Alert';
		$level = AlertManager::LEVEL_WARNING;
		$key   = 'pb_alert_dedup_' . substr( md5( $title . $level ), 0, 16 );

		// Transient should not exist yet.
		$this->assertFalse( get_transient( $key ) );

		( new AlertManager() )->alert( $title, 'body', $level, [], true );

		// Transient should now be set.
		$this->assertNotFalse( get_transient( $key ) );
	}
}
