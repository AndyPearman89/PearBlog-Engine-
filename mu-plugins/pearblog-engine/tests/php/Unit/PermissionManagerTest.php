<?php
/**
 * Unit tests for PermissionManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\API\PermissionManager;

class PermissionManagerTest extends TestCase {

	private PermissionManager $mgr;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->mgr = new PermissionManager();
	}

	// -----------------------------------------------------------------------
	// role_can — default roles
	// -----------------------------------------------------------------------

	public function test_administrator_can_trigger_by_default(): void {
		$this->assertTrue( $this->mgr->role_can( PermissionManager::ACTION_TRIGGER, 'administrator' ) );
	}

	public function test_editor_can_trigger_by_default(): void {
		$this->assertTrue( $this->mgr->role_can( PermissionManager::ACTION_TRIGGER, 'editor' ) );
	}

	public function test_subscriber_cannot_trigger_by_default(): void {
		$this->assertFalse( $this->mgr->role_can( PermissionManager::ACTION_TRIGGER, 'subscriber' ) );
	}

	public function test_only_administrator_can_change_settings_by_default(): void {
		$this->assertTrue( $this->mgr->role_can( PermissionManager::ACTION_SETTINGS, 'administrator' ) );
		$this->assertFalse( $this->mgr->role_can( PermissionManager::ACTION_SETTINGS, 'editor' ) );
	}

	// -----------------------------------------------------------------------
	// set_allowed_roles / get_allowed_roles
	// -----------------------------------------------------------------------

	public function test_set_and_get_allowed_roles(): void {
		$this->mgr->set_allowed_roles( PermissionManager::ACTION_TRIGGER, [ 'administrator', 'shop_manager' ] );
		$roles = $this->mgr->get_allowed_roles( PermissionManager::ACTION_TRIGGER );
		$this->assertContains( 'shop_manager', $roles );
		$this->assertContains( 'administrator', $roles );
	}

	public function test_set_roles_deduplicates(): void {
		$this->mgr->set_allowed_roles( PermissionManager::ACTION_PAUSE, [ 'editor', 'editor', 'administrator' ] );
		$roles = $this->mgr->get_allowed_roles( PermissionManager::ACTION_PAUSE );
		$this->assertCount( 2, array_unique( $roles ) );
	}

	public function test_unknown_action_returns_empty_roles(): void {
		$this->assertSame( [], $this->mgr->get_allowed_roles( 'nonexistent_action' ) );
	}

	public function test_role_can_respects_custom_roles(): void {
		$this->mgr->set_allowed_roles( PermissionManager::ACTION_TRIGGER, [ 'author' ] );
		$this->assertTrue( $this->mgr->role_can( PermissionManager::ACTION_TRIGGER, 'author' ) );
		$this->assertFalse( $this->mgr->role_can( PermissionManager::ACTION_TRIGGER, 'editor' ) );
	}

	// -----------------------------------------------------------------------
	// Audit log
	// -----------------------------------------------------------------------

	public function test_log_creates_entry(): void {
		$this->mgr->log( 'admin', 'trigger_pipeline', 'via CLI', true );
		$log = $this->mgr->get_audit_log();
		$this->assertCount( 1, $log );
		$this->assertSame( 'admin', $log[0]['actor'] );
		$this->assertSame( 'trigger_pipeline', $log[0]['action'] );
		$this->assertSame( 'via CLI', $log[0]['context'] );
		$this->assertTrue( $log[0]['success'] );
	}

	public function test_log_records_failure(): void {
		$this->mgr->log( 'cron', 'trigger_pipeline', 'auth failed', false );
		$log = $this->mgr->get_audit_log();
		$this->assertFalse( $log[0]['success'] );
	}

	public function test_multiple_log_entries_are_ordered_oldest_first(): void {
		$this->mgr->log( 'user1', 'action_a', '' );
		$this->mgr->log( 'user2', 'action_b', '' );
		$log = $this->mgr->get_audit_log();
		$this->assertSame( 'action_a', $log[0]['action'] );
		$this->assertSame( 'action_b', $log[1]['action'] );
	}

	public function test_log_trims_to_max(): void {
		for ( $i = 0; $i < PermissionManager::AUDIT_MAX + 10; $i++ ) {
			$this->mgr->log( 'user', "action_{$i}", '' );
		}
		$log = $this->mgr->get_audit_log();
		$this->assertLessThanOrEqual( PermissionManager::AUDIT_MAX, count( $log ) );
	}

	public function test_get_audit_log_with_limit(): void {
		for ( $i = 0; $i < 20; $i++ ) {
			$this->mgr->log( 'user', "action_{$i}", '' );
		}
		$limited = $this->mgr->get_audit_log( 5 );
		$this->assertCount( 5, $limited );
	}

	public function test_clear_audit_log(): void {
		$this->mgr->log( 'user', 'some_action', '' );
		$this->mgr->clear_audit_log();
		$this->assertSame( [], $this->mgr->get_audit_log() );
	}

	public function test_empty_log_returns_empty_array(): void {
		$this->assertSame( [], $this->mgr->get_audit_log() );
	}
}
