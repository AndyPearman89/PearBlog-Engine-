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
		$GLOBALS['_options'] = [];
		$GLOBALS['_roles']   = [];
		$this->rbac = new RBACManager();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_overrides_constant(): void {
		$this->assertSame( 'pearblog_rbac_overrides', RBACManager::OPTION_OVERRIDES );
	}

	public function test_capabilities_is_array(): void {
		$this->assertIsArray( RBACManager::CAPABILITIES );
	}

	public function test_capabilities_count(): void {
		$this->assertCount( 8, RBACManager::CAPABILITIES );
	}

	public function test_generate_content_capability_exists(): void {
		$this->assertContains( 'pearblog_generate_content', RBACManager::CAPABILITIES );
	}

	public function test_manage_billing_capability_exists(): void {
		$this->assertContains( 'pearblog_manage_billing', RBACManager::CAPABILITIES );
	}

	public function test_view_analytics_capability_exists(): void {
		$this->assertContains( 'pearblog_view_analytics', RBACManager::CAPABILITIES );
	}

	public function test_approve_content_capability_exists(): void {
		$this->assertContains( 'pearblog_approve_content', RBACManager::CAPABILITIES );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->rbac->register();
	}

	// -----------------------------------------------------------------------
	// assign_capabilities — no roles in test env
	// -----------------------------------------------------------------------

	public function test_assign_capabilities_handles_missing_roles_gracefully(): void {
		$this->expectNotToPerformAssertions();
		// All get_role() calls return null → should silently skip.
		$this->rbac->assign_capabilities();
	}

	// -----------------------------------------------------------------------
	// assign_capabilities — with mock roles
	// -----------------------------------------------------------------------

	public function test_assign_capabilities_adds_caps_to_administrator(): void {
		$adminRole = new class {
			public array $caps = [];
			public function add_cap( string $cap, bool $grant ): void {
				$this->caps[ $cap ] = $grant;
			}
		};

		$GLOBALS['_roles']['administrator'] = $adminRole;

		$this->rbac->assign_capabilities();

		$this->assertTrue( $adminRole->caps['pearblog_generate_content'] ?? false );
		$this->assertTrue( $adminRole->caps['pearblog_manage_billing'] ?? false );
	}

	public function test_assign_capabilities_editor_gets_generate_not_billing(): void {
		$editorRole = new class {
			public array $caps = [];
			public function add_cap( string $cap, bool $grant ): void {
				$this->caps[ $cap ] = $grant;
			}
		};

		$GLOBALS['_roles']['editor'] = $editorRole;

		$this->rbac->assign_capabilities();

		$this->assertTrue( $editorRole->caps['pearblog_generate_content'] ?? false );
		$this->assertFalse( $editorRole->caps['pearblog_manage_billing'] ?? true );
	}

	public function test_assign_capabilities_contributor_only_gets_analytics(): void {
		$contributorRole = new class {
			public array $caps = [];
			public function add_cap( string $cap, bool $grant ): void {
				$this->caps[ $cap ] = $grant;
			}
		};

		$GLOBALS['_roles']['contributor'] = $contributorRole;

		$this->rbac->assign_capabilities();

		$this->assertTrue( $contributorRole->caps['pearblog_view_analytics'] ?? false );
		$this->assertFalse( $contributorRole->caps['pearblog_generate_content'] ?? true );
	}

	// -----------------------------------------------------------------------
	// Option overrides
	// -----------------------------------------------------------------------

	public function test_overrides_respected_for_role(): void {
		// Give editor billing access via override.
		update_option( RBACManager::OPTION_OVERRIDES, [
			'editor' => [ 'pearblog_manage_billing', 'pearblog_view_analytics' ],
		] );

		$editorRole = new class {
			public array $caps = [];
			public function add_cap( string $cap, bool $grant ): void {
				$this->caps[ $cap ] = $grant;
			}
		};

		$GLOBALS['_roles']['editor'] = $editorRole;

		$this->rbac->assign_capabilities();

		$this->assertTrue( $editorRole->caps['pearblog_manage_billing'] ?? false );
	}
}
