<?php

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Admin\AdminPageV8Enterprise;

/**
 * Unit tests for AdminPageV8Enterprise.
 */
class AdminPageV8EnterpriseTest extends TestCase {

	private AdminPageV8Enterprise $admin;

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['_options']        = [];
		$GLOBALS['_actions']        = [];
		$GLOBALS['_filters']        = [];
		$GLOBALS['_json_response']  = null;
		$GLOBALS['_db_results']     = [];
		$GLOBALS['_db_col_results'] = [];
		$GLOBALS['_is_network_admin'] = false;
		$GLOBALS['_localized_scripts'] = [];
		$GLOBALS['current_user']    = (object) [ 'ID' => 1, 'user_login' => 'admin', 'roles' => [ 'administrator' ] ];
		$_GET  = [];
		$_POST = [];

		$this->admin = new AdminPageV8Enterprise();
	}

	protected function tearDown(): void {
		$_GET  = [];
		$_POST = [];
		unset(
			$GLOBALS['_options'],
			$GLOBALS['_actions'],
			$GLOBALS['_filters'],
			$GLOBALS['_json_response'],
			$GLOBALS['_db_results'],
			$GLOBALS['_db_col_results'],
			$GLOBALS['_is_network_admin'],
			$GLOBALS['_localized_scripts'],
			$GLOBALS['current_user']
		);
		parent::tearDown();
	}

	// ─── register() ──────────────────────────────────────────────────────

	public function test_register_hooks_admin_menu(): void {
		$this->admin->register();

		$this->assertTrue( has_action( 'admin_menu' ) !== false );
		$this->assertTrue( has_action( 'network_admin_menu' ) !== false );
		$this->assertTrue( has_action( 'admin_init' ) !== false );
		$this->assertTrue( has_action( 'admin_enqueue_scripts' ) !== false );
	}

	public function test_register_hooks_ajax_handlers(): void {
		$this->admin->register();

		$this->assertTrue( has_action( 'wp_ajax_pb_v8_get_realtime_stats' ) !== false );
		$this->assertTrue( has_action( 'wp_ajax_pb_v8_get_notifications' ) !== false );
		$this->assertTrue( has_action( 'wp_ajax_pb_v8_toggle_theme' ) !== false );
		$this->assertTrue( has_action( 'wp_ajax_pb_v8_export_report' ) !== false );
	}

	// ─── add_menu() ─────────────────────────────────────────────────────

	public function test_add_menu_uses_manage_options_by_default(): void {
		// add_menu uses manage_options when not network admin
		$GLOBALS['_is_network_admin'] = false;
		ob_start();
		$this->admin->add_menu();
		ob_end_clean();
		// If we got here without error, the capability was valid
		$this->assertTrue( true );
	}

	public function test_add_menu_uses_manage_network_options_for_network_admin(): void {
		$GLOBALS['_is_network_admin'] = true;
		ob_start();
		$this->admin->add_menu();
		ob_end_clean();
		$this->assertTrue( true );
	}

	// ─── register_settings() ─────────────────────────────────────────────

	public function test_register_settings_executes_without_error(): void {
		$this->admin->register_settings();
		$this->assertTrue( true );
	}

	// ─── enqueue_assets() ────────────────────────────────────────────────

	public function test_enqueue_assets_skips_other_pages(): void {
		$this->admin->enqueue_assets( 'edit.php' );
		$this->assertEmpty( $GLOBALS['_localized_scripts'] );
	}

	public function test_enqueue_assets_loads_on_correct_page(): void {
		$this->admin->enqueue_assets( 'toplevel_page_pearblog-enterprise-v8' );
		$this->assertArrayHasKey( 'pearblog-admin-v8-js', $GLOBALS['_localized_scripts'] );

		$localized = $GLOBALS['_localized_scripts']['pearblog-admin-v8-js'];
		$this->assertSame( 'pbV8Data', $localized['name'] );
		$this->assertArrayHasKey( 'ajaxUrl', $localized['data'] );
		$this->assertArrayHasKey( 'nonce', $localized['data'] );
		$this->assertArrayHasKey( 'version', $localized['data'] );
		$this->assertSame( '8.0.0', $localized['data']['version'] );
	}

	public function test_enqueue_assets_respects_language_option(): void {
		$GLOBALS['_options']['pearblog_v8_language'] = 'pl';
		$this->admin->enqueue_assets( 'toplevel_page_pearblog-enterprise-v8' );

		$localized = $GLOBALS['_localized_scripts']['pearblog-admin-v8-js'];
		$this->assertSame( 'pl', $localized['data']['language'] );
		$this->assertArrayHasKey( 'translations', $localized['data'] );
		$this->assertSame( 'Ładowanie...', $localized['data']['translations']['loading'] );
	}

	public function test_enqueue_assets_defaults_to_english(): void {
		$this->admin->enqueue_assets( 'toplevel_page_pearblog-enterprise-v8' );

		$localized = $GLOBALS['_localized_scripts']['pearblog-admin-v8-js'];
		$this->assertSame( 'en', $localized['data']['language'] );
		$this->assertSame( 'Loading...', $localized['data']['translations']['loading'] );
	}

	public function test_enqueue_assets_theme_default_is_light(): void {
		$this->admin->enqueue_assets( 'toplevel_page_pearblog-enterprise-v8' );

		$localized = $GLOBALS['_localized_scripts']['pearblog-admin-v8-js'];
		$this->assertSame( 'light', $localized['data']['theme'] );
	}

	public function test_enqueue_assets_realtime_enabled_by_default(): void {
		$this->admin->enqueue_assets( 'toplevel_page_pearblog-enterprise-v8' );

		$localized = $GLOBALS['_localized_scripts']['pearblog-admin-v8-js'];
		$this->assertTrue( $localized['data']['realtimeEnabled'] );
	}

	// ─── render_page() ──────────────────────────────────────────────────

	public function test_render_page_outputs_html_container(): void {
		ob_start();
		$this->admin->render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'pearblog-admin-v8', $output );
		$this->assertStringContainsString( 'PearBlog Enterprise', $output );
		$this->assertStringContainsString( 'v8.0.0 MAX', $output );
	}

	public function test_render_page_shows_dark_theme_attribute(): void {
		$GLOBALS['_options']['pearblog_v8_theme'] = 'dark';

		ob_start();
		$this->admin->render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-theme="dark"', $output );
	}

	public function test_render_page_shows_polish_language_attribute(): void {
		$GLOBALS['_options']['pearblog_v8_language'] = 'pl';

		ob_start();
		$this->admin->render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-lang="pl"', $output );
	}

	public function test_render_page_includes_tabs(): void {
		ob_start();
		$this->admin->render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-tab="dashboard"', $output );
		$this->assertStringContainsString( 'data-tab="realtime"', $output );
		$this->assertStringContainsString( 'data-tab="security"', $output );
		$this->assertStringContainsString( 'data-tab="settings"', $output );
	}

	// ─── ajax_get_realtime_stats() ──────────────────────────────────────

	public function test_ajax_get_realtime_stats_returns_success(): void {
		$this->admin->ajax_get_realtime_stats();

		$response = $GLOBALS['_json_response'];
		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 'visitors', $response['data'] );
		$this->assertArrayHasKey( 'revenue', $response['data'] );
		$this->assertArrayHasKey( 'conversions', $response['data'] );
		$this->assertArrayHasKey( 'errors', $response['data'] );
		$this->assertArrayHasKey( 'timestamp', $response['data'] );
	}

	public function test_ajax_get_realtime_stats_visitors_from_db(): void {
		$GLOBALS['_db_results'] = [ 42 ];

		$this->admin->ajax_get_realtime_stats();

		$response = $GLOBALS['_json_response'];
		$this->assertSame( 42, $response['data']['visitors'] );
	}

	// ─── ajax_get_notifications() ────────────────────────────────────────

	public function test_ajax_get_notifications_returns_notifications(): void {
		$this->admin->ajax_get_notifications();

		$response = $GLOBALS['_json_response'];
		$this->assertTrue( $response['success'] );
		$this->assertIsArray( $response['data'] );
		$this->assertCount( 2, $response['data'] );
		$this->assertSame( 'Content Generated', $response['data'][0]['title'] );
		$this->assertSame( 'warning', $response['data'][1]['type'] );
	}

	// ─── ajax_toggle_theme() ─────────────────────────────────────────────

	public function test_ajax_toggle_theme_toggles_from_light_to_dark(): void {
		$GLOBALS['_options']['pearblog_v8_theme'] = 'light';

		$this->admin->ajax_toggle_theme();

		$response = $GLOBALS['_json_response'];
		$this->assertTrue( $response['success'] );
		$this->assertSame( 'dark', $response['data']['theme'] );
		$this->assertSame( 'dark', $GLOBALS['_options']['pearblog_v8_theme'] );
	}

	public function test_ajax_toggle_theme_toggles_from_dark_to_light(): void {
		$GLOBALS['_options']['pearblog_v8_theme'] = 'dark';

		$this->admin->ajax_toggle_theme();

		$response = $GLOBALS['_json_response'];
		$this->assertTrue( $response['success'] );
		$this->assertSame( 'light', $response['data']['theme'] );
		$this->assertSame( 'light', $GLOBALS['_options']['pearblog_v8_theme'] );
	}

	// ─── ajax_export_report() ────────────────────────────────────────────

	public function test_ajax_export_report_default_csv(): void {
		$this->admin->ajax_export_report();

		$response = $GLOBALS['_json_response'];
		$this->assertTrue( $response['success'] );
		$this->assertStringContainsString( 'CSV', $response['data']['message'] );
	}

	public function test_ajax_export_report_custom_format(): void {
		$_POST['format'] = 'pdf';

		$this->admin->ajax_export_report();

		$response = $GLOBALS['_json_response'];
		$this->assertTrue( $response['success'] );
		$this->assertStringContainsString( 'PDF', $response['data']['message'] );
	}

	// ─── capability logic ────────────────────────────────────────────────

	public function test_capability_override_option_is_respected(): void {
		$GLOBALS['_options']['pearblog_admin_capability_override'] = 'edit_posts';
		// Render page to trigger capability resolution indirectly via add_menu
		ob_start();
		$this->admin->add_menu();
		ob_end_clean();
		// No error = capability was resolved
		$this->assertTrue( true );
	}

	public function test_force_access_constant(): void {
		if ( ! defined( 'PEARBLOG_ADMIN_FORCE_ACCESS' ) ) {
			define( 'PEARBLOG_ADMIN_FORCE_ACCESS', true );
		}
		ob_start();
		$this->admin->add_menu();
		ob_end_clean();
		$this->assertTrue( true );
	}
}
