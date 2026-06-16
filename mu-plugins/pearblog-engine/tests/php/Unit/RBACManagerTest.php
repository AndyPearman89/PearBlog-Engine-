<?php
/**
 * Unit tests for RBACManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Security\RBACManager;

class RBACManagerTest extends TestCase {

	private RBACManager $rbac;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']       = [];
		$GLOBALS['_current_user_can'] = false;
		$GLOBALS['_wp_roles']      = [];
		$this->rbac                = new RBACManager();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_overrides_constant_value(): void {
		$this->assertSame( 'pearblog_rbac_overrides', RBACManager::OPTION_OVERRIDES );
	}

	public function test_capabilities_constant_is_array(): void {
		$this->assertIsArray( RBACManager::CAPABILITIES );
	}

	public function test_capabilities_has_eight_items(): void {
		$this->assertCount( 8, RBACManager::CAPABILITIES );
	}

	public function test_capabilities_contains_generate_content(): void {
		$this->assertContains( 'pearblog_generate_content', RBACManager::CAPABILITIES );
	}

	public function test_capabilities_contains_manage_queue(): void {
		$this->assertContains( 'pearblog_manage_queue', RBACManager::CAPABILITIES );
	}

	public function test_capabilities_contains_view_analytics(): void {
		$this->assertContains( 'pearblog_view_analytics', RBACManager::CAPABILITIES );
	}

	public function test_capabilities_contains_manage_monetization(): void {
		$this->assertContains( 'pearblog_manage_monetization', RBACManager::CAPABILITIES );
	}

	public function test_capabilities_contains_approve_content(): void {
		$this->assertContains( 'pearblog_approve_content', RBACManager::CAPABILITIES );
	}

	public function test_capabilities_contains_manage_settings(): void {
		$this->assertContains( 'pearblog_manage_settings', RBACManager::CAPABILITIES );
	}

	public function test_capabilities_contains_view_roi(): void {
		$this->assertContains( 'pearblog_view_roi', RBACManager::CAPABILITIES );
	}

	public function test_capabilities_contains_manage_billing(): void {
		$this->assertContains( 'pearblog_manage_billing', RBACManager::CAPABILITIES );
	}

	// -----------------------------------------------------------------------
	// assign_capabilities — safe when roles don't exist
	// -----------------------------------------------------------------------

	public function test_assign_capabilities_runs_without_error_when_no_roles(): void {
		// $GLOBALS['_wp_roles'] is empty → get_role returns null → method skips safely
		$this->rbac->assign_capabilities();
		$this->assertTrue( true ); // No exception thrown.
	}

	public function test_assign_capabilities_applies_caps_to_existing_role(): void {
		$role = new \WP_Role( 'editor' );
		$GLOBALS['_wp_roles']['editor'] = $role;

		$this->rbac->assign_capabilities();

		// Editor default: generate_content, manage_queue, view_analytics, approve_content, view_roi
		$this->assertTrue( $role->has_cap( 'pearblog_generate_content' ) );
		$this->assertTrue( $role->has_cap( 'pearblog_view_analytics' ) );
	}

	public function test_assign_capabilities_denies_admin_only_caps_to_author(): void {
		$role = new \WP_Role( 'author' );
		$GLOBALS['_wp_roles']['author'] = $role;

		$this->rbac->assign_capabilities();

		// Author does not get manage_billing or manage_settings by default.
		$this->assertFalse( $role->has_cap( 'pearblog_manage_billing' ) );
		$this->assertFalse( $role->has_cap( 'pearblog_manage_settings' ) );
	}

	// -----------------------------------------------------------------------
	// current_user_can — static helper
	// -----------------------------------------------------------------------

	public function test_current_user_can_returns_false_for_non_privileged_user(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( RBACManager::current_user_can( 'pearblog_generate_content' ) );
	}

	public function test_current_user_can_returns_true_for_admin_user(): void {
		// Simulating a user who passes current_user_can('manage_options').
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( RBACManager::current_user_can( 'pearblog_generate_content' ) );
	}
}
