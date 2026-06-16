<?php
/**
 * Unit tests for SecurityAuditor.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Security\SecurityAuditor;

// Define plugin directory constant so the constructor scan paths resolve.
if ( ! defined( 'PEARBLOG_ENGINE_DIR' ) ) {
	define( 'PEARBLOG_ENGINE_DIR', dirname( __DIR__, 3 ) . '/src/..' );
}

class SecurityAuditorTest extends TestCase {

	private SecurityAuditor $auditor;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$this->auditor       = new SecurityAuditor();
	}

	// -----------------------------------------------------------------------
	// get_results — before running audit
	// -----------------------------------------------------------------------

	public function test_get_results_returns_empty_array_before_audit(): void {
		$results = $this->auditor->get_results();

		$this->assertSame( [], $results );
	}

	// -----------------------------------------------------------------------
	// run_full_audit — structure
	// -----------------------------------------------------------------------

	public function test_run_full_audit_returns_array(): void {
		$results = $this->auditor->run_full_audit();

		$this->assertIsArray( $results );
	}

	public function test_run_full_audit_has_required_keys(): void {
		$results = $this->auditor->run_full_audit();

		$this->assertArrayHasKey( 'timestamp', $results );
		$this->assertArrayHasKey( 'auditor', $results );
		$this->assertArrayHasKey( 'owasp_version', $results );
		$this->assertArrayHasKey( 'checks', $results );
		$this->assertArrayHasKey( 'summary', $results );
	}

	public function test_run_full_audit_sets_owasp_version(): void {
		$results = $this->auditor->run_full_audit();

		$this->assertStringContainsString( 'OWASP Top 10', $results['owasp_version'] );
	}

	public function test_run_full_audit_sets_auditor_name(): void {
		$results = $this->auditor->run_full_audit();

		$this->assertNotEmpty( $results['auditor'] );
		$this->assertIsString( $results['auditor'] );
	}

	public function test_run_full_audit_checks_is_array(): void {
		$results = $this->auditor->run_full_audit();

		$this->assertIsArray( $results['checks'] );
	}

	public function test_run_full_audit_runs_10_owasp_checks(): void {
		$results = $this->auditor->run_full_audit();

		// Expect exactly 10 OWASP Top 10 checks.
		$this->assertCount( 10, $results['checks'] );
	}

	// -----------------------------------------------------------------------
	// run_full_audit — summary structure
	// -----------------------------------------------------------------------

	public function test_run_full_audit_summary_has_required_keys(): void {
		$results = $this->auditor->run_full_audit();
		$summary = $results['summary'];

		$this->assertArrayHasKey( 'total_checks', $summary );
		$this->assertArrayHasKey( 'passed', $summary );
		$this->assertArrayHasKey( 'failed', $summary );
		$this->assertArrayHasKey( 'warnings', $summary );
		$this->assertArrayHasKey( 'total_vulnerabilities', $summary );
		$this->assertArrayHasKey( 'risk_score', $summary );
		$this->assertArrayHasKey( 'overall_status', $summary );
	}

	public function test_run_full_audit_summary_total_checks_is_10(): void {
		$results = $this->auditor->run_full_audit();

		$this->assertSame( 10, $results['summary']['total_checks'] );
	}

	public function test_run_full_audit_summary_counts_are_non_negative(): void {
		$results = $this->auditor->run_full_audit();
		$summary = $results['summary'];

		$this->assertGreaterThanOrEqual( 0, $summary['passed'] );
		$this->assertGreaterThanOrEqual( 0, $summary['failed'] );
		$this->assertGreaterThanOrEqual( 0, $summary['warnings'] );
		$this->assertGreaterThanOrEqual( 0, $summary['total_vulnerabilities'] );
	}

	public function test_run_full_audit_risk_score_is_between_0_and_100(): void {
		$results    = $this->auditor->run_full_audit();
		$risk_score = $results['summary']['risk_score'];

		$this->assertGreaterThanOrEqual( 0, $risk_score );
		$this->assertLessThanOrEqual( 100, $risk_score );
	}

	public function test_run_full_audit_overall_status_is_valid_value(): void {
		$results = $this->auditor->run_full_audit();
		$status  = $results['summary']['overall_status'];

		$this->assertContains( $status, [ 'PASS', 'WARNING', 'CRITICAL' ] );
	}

	// -----------------------------------------------------------------------
	// run_full_audit — check structure
	// -----------------------------------------------------------------------

	public function test_each_check_has_name_key(): void {
		$results = $this->auditor->run_full_audit();

		foreach ( $results['checks'] as $check ) {
			$this->assertArrayHasKey( 'name', $check );
		}
	}

	public function test_each_check_has_status_key(): void {
		$results = $this->auditor->run_full_audit();

		foreach ( $results['checks'] as $check ) {
			$this->assertArrayHasKey( 'status', $check );
		}
	}

	public function test_each_check_status_is_valid(): void {
		$results       = $this->auditor->run_full_audit();
		$valid_statuses = [ 'PASS', 'FAIL', 'WARNING', 'INFO' ];

		foreach ( $results['checks'] as $check ) {
			$this->assertContains( $check['status'], $valid_statuses );
		}
	}

	// -----------------------------------------------------------------------
	// get_results — after audit
	// -----------------------------------------------------------------------

	public function test_get_results_returns_audit_results_after_run(): void {
		$this->auditor->run_full_audit();
		$results = $this->auditor->get_results();

		$this->assertNotEmpty( $results );
		$this->assertArrayHasKey( 'summary', $results );
	}

	public function test_get_results_matches_run_full_audit(): void {
		$from_run    = $this->auditor->run_full_audit();
		$from_getter = $this->auditor->get_results();

		$this->assertSame( $from_run['owasp_version'], $from_getter['owasp_version'] );
		$this->assertCount( count( $from_run['checks'] ), $from_getter['checks'] );
	}

	// -----------------------------------------------------------------------
	// export_json
	// -----------------------------------------------------------------------

	public function test_export_json_returns_empty_json_before_audit(): void {
		$json = $this->auditor->export_json();

		// Before running audit, results are empty.
		$this->assertJson( $json );
		$decoded = json_decode( $json, true );
		$this->assertSame( [], $decoded );
	}

	public function test_export_json_returns_valid_json_after_audit(): void {
		$this->auditor->run_full_audit();
		$json = $this->auditor->export_json();

		$this->assertJson( $json );
	}

	public function test_export_json_contains_summary_key(): void {
		$this->auditor->run_full_audit();
		$json    = $this->auditor->export_json();
		$decoded = json_decode( $json, true );

		$this->assertArrayHasKey( 'summary', $decoded );
	}

	public function test_export_json_contains_checks_key(): void {
		$this->auditor->run_full_audit();
		$json    = $this->auditor->export_json();
		$decoded = json_decode( $json, true );

		$this->assertArrayHasKey( 'checks', $decoded );
	}

	public function test_export_json_uses_pretty_print(): void {
		$this->auditor->run_full_audit();
		$json = $this->auditor->export_json();

		// Pretty-printed JSON contains newlines.
		$this->assertStringContainsString( "\n", $json );
	}

	// -----------------------------------------------------------------------
	// Idempotent — running audit twice gives consistent structure
	// -----------------------------------------------------------------------

	public function test_running_audit_twice_resets_checks(): void {
		$this->auditor->run_full_audit();
		$second_run = $this->auditor->run_full_audit();

		// Checks array should contain exactly 10 items each time.
		$this->assertCount( 10, $second_run['checks'] );
	}
}
