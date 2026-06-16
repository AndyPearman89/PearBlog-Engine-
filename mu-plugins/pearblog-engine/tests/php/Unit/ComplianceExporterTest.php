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
		$GLOBALS['_actions']   = [];
		$this->exporter = new ComplianceExporter();
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->exporter->register();
	}

	// -----------------------------------------------------------------------
	// build_report
	// -----------------------------------------------------------------------

	public function test_build_report_returns_array(): void {
		$report = $this->exporter->build_report();

		$this->assertIsArray( $report );
	}

	public function test_build_report_has_required_keys(): void {
		$report = $this->exporter->build_report();

		$this->assertArrayHasKey( 'report_id', $report );
		$this->assertArrayHasKey( 'generated_at', $report );
		$this->assertArrayHasKey( 'site_url', $report );
		$this->assertArrayHasKey( 'period_days', $report );
		$this->assertArrayHasKey( 'total_events', $report );
		$this->assertArrayHasKey( 'events_by_level', $report );
		$this->assertArrayHasKey( 'events_by_type', $report );
		$this->assertArrayHasKey( 'data_retention', $report );
		$this->assertArrayHasKey( 'events', $report );
	}

	public function test_build_report_id_contains_today_date(): void {
		$report = $this->exporter->build_report();

		$this->assertStringContainsString( date( 'Y-m-d' ), $report['report_id'] );
	}

	public function test_build_report_period_days_defaults_to_30(): void {
		$report = $this->exporter->build_report();

		$this->assertSame( 30, $report['period_days'] );
	}

	public function test_build_report_period_days_can_be_set(): void {
		$report = $this->exporter->build_report( 7 );

		$this->assertSame( 7, $report['period_days'] );
	}

	public function test_build_report_caps_days_at_max(): void {
		$report = $this->exporter->build_report( 9999 );

		$this->assertLessThanOrEqual( 365, $report['period_days'] );
	}

	public function test_build_report_events_is_array(): void {
		$report = $this->exporter->build_report();

		$this->assertIsArray( $report['events'] );
	}

	public function test_build_report_data_retention_has_policy(): void {
		$report = $this->exporter->build_report();

		$this->assertArrayHasKey( 'policy', $report['data_retention'] );
		$this->assertIsString( $report['data_retention']['policy'] );
	}

	// -----------------------------------------------------------------------
	// to_csv
	// -----------------------------------------------------------------------

	public function test_to_csv_returns_string(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );

		$this->assertIsString( $csv );
	}

	public function test_to_csv_contains_header_row(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );

		$this->assertStringContainsString( 'Event ID', $csv );
		$this->assertStringContainsString( 'Timestamp', $csv );
		$this->assertStringContainsString( 'Event Type', $csv );
	}

	public function test_to_csv_starts_with_utf8_bom(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );

		$this->assertStringStartsWith( "\xEF\xBB\xBF", $csv );
	}

	public function test_to_csv_contains_report_id(): void {
		$report = $this->exporter->build_report();
		$csv    = $this->exporter->to_csv( $report );

		$this->assertStringContainsString( $report['report_id'], $csv );
	}

	// -----------------------------------------------------------------------
	// rest_export
	// -----------------------------------------------------------------------

	public function test_rest_export_returns_200_json(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturnMap( [
			[ 'format', 'json' ],
			[ 'days',   30 ],
		] );

		$response = $this->exporter->rest_export( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_rest_export_returns_csv_wrapped_when_format_csv(): void {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_param' )->willReturnMap( [
			[ 'format', 'csv' ],
			[ 'days',   30 ],
		] );

		$data = $this->exporter->rest_export( $request )->get_data();

		$this->assertSame( 'csv', $data['format'] );
		$this->assertArrayHasKey( 'content', $data );
		$this->assertArrayHasKey( 'filename', $data );
	}

	// -----------------------------------------------------------------------
	// admin_permission
	// -----------------------------------------------------------------------

	public function test_admin_permission_returns_bool(): void {
		$this->assertIsBool( $this->exporter->admin_permission() );
	}
}
