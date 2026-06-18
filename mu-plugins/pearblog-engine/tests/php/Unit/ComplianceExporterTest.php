<?php
/**
 * Unit tests for ComplianceExporter.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Security\ComplianceExporter;

class ComplianceExporterTest extends TestCase {

	private ComplianceExporter $exporter;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_current_user_can'] = true;
		$this->exporter = new ComplianceExporter();
	}

	// -----------------------------------------------------------------------
	// build_report
	// -----------------------------------------------------------------------

	public function test_build_report_returns_array(): void {
		$report = $this->exporter->build_report();
		$this->assertIsArray( $report );
	}

	public function test_build_report_has_report_id(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'report_id', $report );
		$this->assertStringStartsWith( 'pearblog-compliance-', $report['report_id'] );
	}

	public function test_build_report_has_generated_at(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'generated_at', $report );
		$this->assertNotEmpty( $report['generated_at'] );
	}

	public function test_build_report_has_site_url(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'site_url', $report );
		$this->assertSame( 'https://example.com', $report['site_url'] );
	}

	public function test_build_report_has_period_days(): void {
		$report = $this->exporter->build_report( 30 );
		$this->assertSame( 30, $report['period_days'] );
	}

	public function test_build_report_caps_days_at_365(): void {
		$report = $this->exporter->build_report( 500 );
		$this->assertSame( 365, $report['period_days'] );
	}

	public function test_build_report_has_data_retention(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'data_retention', $report );
		$this->assertArrayHasKey( 'audit_log_max_entries', $report['data_retention'] );
	}

	public function test_build_report_has_events_array(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'events', $report );
		$this->assertIsArray( $report['events'] );
	}

	public function test_build_report_has_total_events(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'total_events', $report );
	}

	public function test_build_report_has_events_by_level(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'events_by_level', $report );
	}

	public function test_build_report_has_events_by_type(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'events_by_type', $report );
	}

	public function test_build_report_period_from_is_before_period_to(): void {
		$report = $this->exporter->build_report( 7 );
		$from   = strtotime( $report['period_from'] );
		$to     = strtotime( $report['period_to'] );
		$this->assertLessThan( $to, $from );
	}

	// -----------------------------------------------------------------------
	// to_csv
	// -----------------------------------------------------------------------

	public function test_to_csv_contains_bom(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );
		$this->assertStringStartsWith( "\xEF\xBB\xBF", $csv );
	}

	public function test_to_csv_contains_report_id(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );
		$this->assertStringContainsString( $report['report_id'], $csv );
	}

	public function test_to_csv_returns_string(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );
		$this->assertIsString( $csv );
	}

	public function test_to_csv_contains_header_comment(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );
		$this->assertStringContainsString( 'PearBlog Engine Compliance Report', $csv );
	}

	// -----------------------------------------------------------------------
	// REST + permissions
	// -----------------------------------------------------------------------

	public function test_admin_permission_returns_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->exporter->admin_permission() );
	}

	public function test_admin_permission_returns_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->exporter->admin_permission() );
	}

	public function test_rest_export_returns_200(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'format', 'json' );
		$req->set_param( 'days', '30' );
		$result = $this->exporter->rest_export( $req );
		$this->assertSame( 200, $result->get_status() );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_rest_api_init_action(): void {
		$this->exporter->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}
}
