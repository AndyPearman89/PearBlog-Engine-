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

	private RBACManager $mgr;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']        = [];
		$GLOBALS['_current_user_can'] = null;
		unset( $GLOBALS['_current_user_can'] );

		// Register the four default roles in the stub.
		foreach ( [ 'administrator', 'editor', 'author', 'contributor' ] as $role ) {
			$GLOBALS['_roles'][ $role ] = new \WP_Role( $role );
		}

		$this->mgr = new RBACManager();
	}

	// -----------------------------------------------------------------------
	// assign_capabilities – defaults
	// -----------------------------------------------------------------------

	public function test_administrator_receives_all_capabilities_by_default(): void {
		$this->mgr->assign_capabilities();

		$role = $GLOBALS['_roles']['administrator'];
		foreach ( RBACManager::CAPABILITIES as $cap ) {
			$this->assertTrue( $role->has_cap( $cap ), "Administrator should have {$cap}" );
		}
	}

	public function test_editor_receives_subset_of_capabilities_by_default(): void {
		$this->mgr->assign_capabilities();

		$role            = $GLOBALS['_roles']['editor'];
		$expected_true   = [ 'pearblog_generate_content', 'pearblog_manage_queue', 'pearblog_view_analytics', 'pearblog_approve_content', 'pearblog_view_roi' ];
		$expected_false  = [ 'pearblog_manage_monetization', 'pearblog_manage_settings', 'pearblog_manage_billing' ];

		foreach ( $expected_true as $cap ) {
			$this->assertTrue( $role->has_cap( $cap ), "Editor should have {$cap}" );
		}
		foreach ( $expected_false as $cap ) {
			$this->assertFalse( $role->has_cap( $cap ), "Editor should NOT have {$cap}" );
		}
	}

	public function test_author_only_gets_generate_content_and_view_analytics(): void {
		$this->mgr->assign_capabilities();

		$role = $GLOBALS['_roles']['author'];
		$this->assertTrue( $role->has_cap( 'pearblog_generate_content' ) );
		$this->assertTrue( $role->has_cap( 'pearblog_view_analytics' ) );
		$this->assertFalse( $role->has_cap( 'pearblog_manage_settings' ) );
		$this->assertFalse( $role->has_cap( 'pearblog_manage_billing' ) );
	}

	public function test_contributor_only_gets_view_analytics(): void {
		$this->mgr->assign_capabilities();

		$role = $GLOBALS['_roles']['contributor'];
		$this->assertTrue( $role->has_cap( 'pearblog_view_analytics' ) );
		$this->assertFalse( $role->has_cap( 'pearblog_generate_content' ) );
		$this->assertFalse( $role->has_cap( 'pearblog_manage_settings' ) );
	}

	// -----------------------------------------------------------------------
	// assign_capabilities – overrides
	// -----------------------------------------------------------------------

	public function test_overrides_replace_default_assignments(): void {
		// Give contributor generate_content via override, remove view_analytics.
		update_option( RBACManager::OPTION_OVERRIDES, [
			'contributor' => [ 'pearblog_generate_content' ],
		] );

		$this->mgr->assign_capabilities();

		$role = $GLOBALS['_roles']['contributor'];
		$this->assertTrue( $role->has_cap( 'pearblog_generate_content' ) );
		$this->assertFalse( $role->has_cap( 'pearblog_view_analytics' ) );
	}

	public function test_overrides_do_not_affect_unmentioned_roles(): void {
		update_option( RBACManager::OPTION_OVERRIDES, [
			'contributor' => [ 'pearblog_generate_content' ],
		] );

		$this->mgr->assign_capabilities();

		// Editor defaults should remain intact.
		$editor = $GLOBALS['_roles']['editor'];
		$this->assertTrue( $editor->has_cap( 'pearblog_generate_content' ) );
		$this->assertTrue( $editor->has_cap( 'pearblog_view_analytics' ) );
	}

	public function test_missing_role_is_skipped_gracefully(): void {
		// Remove 'author' from the stub registry.
		unset( $GLOBALS['_roles']['author'] );

		// Should not throw; remaining roles still receive capabilities.
		$this->mgr->assign_capabilities();

		$admin = $GLOBALS['_roles']['administrator'];
		$this->assertTrue( $admin->has_cap( 'pearblog_manage_settings' ) );
	}

	// -----------------------------------------------------------------------
	// current_user_can
	// -----------------------------------------------------------------------

	public function test_manage_options_user_can_any_capability(): void {
		$GLOBALS['_current_user_can'] = true;

		foreach ( RBACManager::CAPABILITIES as $cap ) {
			$this->assertTrue(
				RBACManager::current_user_can( $cap ),
				"manage_options user should pass {$cap}"
			);
		}
	}

	public function test_non_privileged_user_cannot_access_capability(): void {
		$GLOBALS['_current_user_can'] = false;

		$this->assertFalse( RBACManager::current_user_can( 'pearblog_manage_settings' ) );
	}

	// -----------------------------------------------------------------------
	// CAPABILITIES constant
	// -----------------------------------------------------------------------

	public function test_capabilities_list_is_not_empty(): void {
		$this->assertNotEmpty( RBACManager::CAPABILITIES );
	}

	public function test_all_capabilities_have_pearblog_prefix(): void {
		foreach ( RBACManager::CAPABILITIES as $cap ) {
			$this->assertStringStartsWith( 'pearblog_', $cap, "{$cap} should start with 'pearblog_'" );
		}
	}
}
