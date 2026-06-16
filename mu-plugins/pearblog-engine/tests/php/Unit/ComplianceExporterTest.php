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
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$this->exporter        = new ComplianceExporter();
	}

	// -----------------------------------------------------------------------
	// build_report — return type and required keys
	// -----------------------------------------------------------------------

	public function test_build_report_returns_array(): void {
		$report = $this->exporter->build_report();
		$this->assertIsArray( $report );
	}

	public function test_build_report_has_report_id_key(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'report_id', $report );
	}

	public function test_build_report_has_generated_at_key(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'generated_at', $report );
	}

	public function test_build_report_has_period_days_key(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'period_days', $report );
	}

	public function test_build_report_has_total_events_key(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'total_events', $report );
	}

	public function test_build_report_has_data_retention_key(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'data_retention', $report );
	}

	public function test_build_report_has_events_key(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'events', $report );
	}

	// -----------------------------------------------------------------------
	// build_report — field values
	// -----------------------------------------------------------------------

	public function test_build_report_id_starts_with_pearblog_compliance(): void {
		$report = $this->exporter->build_report();
		$this->assertStringStartsWith( 'pearblog-compliance-', $report['report_id'] );
	}

	public function test_build_report_total_events_is_zero_for_empty_log(): void {
		$report = $this->exporter->build_report();
		$this->assertSame( 0, $report['total_events'] );
	}

	public function test_build_report_period_days_defaults_to_30(): void {
		$report = $this->exporter->build_report();
		$this->assertSame( 30, $report['period_days'] );
	}

	public function test_build_report_period_days_respects_custom_value(): void {
		$report = $this->exporter->build_report( 90 );
		$this->assertSame( 90, $report['period_days'] );
	}

	public function test_build_report_caps_period_days_at_365(): void {
		$report = $this->exporter->build_report( 999 );
		$this->assertSame( 365, $report['period_days'] );
	}

	public function test_build_report_data_retention_has_audit_log_max_entries(): void {
		$report = $this->exporter->build_report();
		$this->assertArrayHasKey( 'audit_log_max_entries', $report['data_retention'] );
	}

	// -----------------------------------------------------------------------
	// to_csv — output format
	// -----------------------------------------------------------------------

	public function test_to_csv_returns_string(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );
		$this->assertIsString( $csv );
	}

	public function test_to_csv_starts_with_utf8_bom(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );
		$this->assertStringStartsWith( "\xEF\xBB\xBF", $csv );
	}

	public function test_to_csv_contains_header_comment(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );
		$this->assertStringContainsString( 'PearBlog Engine Compliance Report', $csv );
	}

	public function test_to_csv_contains_column_headers(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );
		$this->assertStringContainsString( 'Event ID', $csv );
		$this->assertStringContainsString( 'Timestamp', $csv );
		$this->assertStringContainsString( 'Level', $csv );
	}

	public function test_to_csv_contains_report_id_metadata(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );
		$this->assertStringContainsString( $report['report_id'], $csv );
	}
}
