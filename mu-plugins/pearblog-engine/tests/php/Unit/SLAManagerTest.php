<?php
/**
 * Unit tests for SLAManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Monitoring\SLAManager;

class SLAManagerTest extends TestCase {

	private SLAManager $sla;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']  = [];
		$GLOBALS['_mail_log'] = [];
		$GLOBALS['_cron_scheduled'] = [];
		$this->sla = new SLAManager();
	}

	// -----------------------------------------------------------------------
	// get_targets / set_targets
	// -----------------------------------------------------------------------

	public function test_default_targets_match_constants(): void {
		$targets = $this->sla->get_targets();
		$this->assertSame( SLAManager::DEFAULTS[SLAManager::METRIC_UPTIME], $targets[SLAManager::METRIC_UPTIME] );
		$this->assertSame( SLAManager::DEFAULTS[SLAManager::METRIC_PIPELINE_SUCCESS], $targets[SLAManager::METRIC_PIPELINE_SUCCESS] );
	}

	public function test_set_targets_overrides_specific_keys(): void {
		$this->sla->set_targets( [ SLAManager::METRIC_UPTIME => 95.0 ] );
		// JSON round-trip may return int 95 from 95.0; use assertEquals for loose comparison.
		$this->assertEquals( 95.0, $this->sla->get_target( SLAManager::METRIC_UPTIME ) );
		// JSON round-trip may return int; use assertEquals for loose comparison.
		$this->assertEquals( SLAManager::DEFAULTS[SLAManager::METRIC_PIPELINE_SUCCESS], $this->sla->get_target( SLAManager::METRIC_PIPELINE_SUCCESS ) );
	}

	public function test_unknown_metric_stripped_from_targets(): void {
		$this->sla->set_targets( [ 'invalid_metric' => 50.0 ] );
		$targets = $this->sla->get_targets();
		$this->assertArrayNotHasKey( 'invalid_metric', $targets );
	}

	public function test_get_target_unknown_key_returns_zero(): void {
		$this->assertSame( 0, $this->sla->get_target( 'totally_unknown' ) );
	}

	// -----------------------------------------------------------------------
	// is_breached
	// -----------------------------------------------------------------------

	public function test_uptime_breached_when_below_target(): void {
		$this->assertTrue( $this->sla->is_breached( SLAManager::METRIC_UPTIME, 99.9, 99.0 ) );
	}

	public function test_uptime_not_breached_when_at_target(): void {
		$this->assertFalse( $this->sla->is_breached( SLAManager::METRIC_UPTIME, 99.9, 99.9 ) );
	}

	public function test_uptime_not_breached_when_above_target(): void {
		$this->assertFalse( $this->sla->is_breached( SLAManager::METRIC_UPTIME, 99.9, 100.0 ) );
	}

	public function test_response_time_breached_when_above_target(): void {
		$this->assertTrue( $this->sla->is_breached( SLAManager::METRIC_API_RESPONSE_MS, 2000, 2500 ) );
	}

	public function test_response_time_not_breached_when_below_target(): void {
		$this->assertFalse( $this->sla->is_breached( SLAManager::METRIC_API_RESPONSE_MS, 2000, 1800 ) );
	}

	public function test_cost_breached_when_above_target(): void {
		$this->assertTrue( $this->sla->is_breached( SLAManager::METRIC_COST_PER_ARTICLE, 10, 15 ) );
	}

	// -----------------------------------------------------------------------
	// evaluate
	// -----------------------------------------------------------------------

	public function test_evaluate_returns_status_per_metric(): void {
		$metrics = [
			SLAManager::METRIC_UPTIME           => 99.95,
			SLAManager::METRIC_PIPELINE_SUCCESS => 99.8,
			SLAManager::METRIC_API_RESPONSE_MS  => 800,
			SLAManager::METRIC_COST_PER_ARTICLE => 8,
		];

		$status = $this->sla->evaluate( $metrics );

		$this->assertArrayHasKey( SLAManager::METRIC_UPTIME, $status );
		$this->assertFalse( $status[SLAManager::METRIC_UPTIME]['breached'] );
		$this->assertFalse( $status[SLAManager::METRIC_PIPELINE_SUCCESS]['breached'] );
	}

	public function test_evaluate_marks_breach(): void {
		$this->sla->set_targets( [ SLAManager::METRIC_UPTIME => 99.9 ] );

		$metrics = [
			SLAManager::METRIC_UPTIME           => 98.0,
			SLAManager::METRIC_PIPELINE_SUCCESS => 99.9,
			SLAManager::METRIC_API_RESPONSE_MS  => 500,
			SLAManager::METRIC_COST_PER_ARTICLE => 5,
		];

		$status = $this->sla->evaluate( $metrics );
		$this->assertTrue( $status[SLAManager::METRIC_UPTIME]['breached'] );
	}

	public function test_evaluate_persists_status(): void {
		$this->sla->evaluate( [
			SLAManager::METRIC_UPTIME           => 100.0,
			SLAManager::METRIC_PIPELINE_SUCCESS => 100.0,
			SLAManager::METRIC_API_RESPONSE_MS  => 100,
			SLAManager::METRIC_COST_PER_ARTICLE => 5,
		] );

		$status = $this->sla->get_status();
		$this->assertNotNull( $status );
		$this->assertArrayHasKey( 'evaluated_at', $status );
		$this->assertArrayHasKey( 'metrics', $status );
	}

	public function test_get_status_returns_null_when_never_evaluated(): void {
		$this->assertNull( $this->sla->get_status() );
	}

	// -----------------------------------------------------------------------
	// generate_monthly_report
	// -----------------------------------------------------------------------

	public function test_monthly_report_contains_expected_fields(): void {
		$metrics = [
			SLAManager::METRIC_UPTIME           => 99.99,
			SLAManager::METRIC_PIPELINE_SUCCESS => 99.5,
			SLAManager::METRIC_API_RESPONSE_MS  => 750,
			SLAManager::METRIC_COST_PER_ARTICLE => 8,
		];

		$report = $this->sla->generate_monthly_report( $metrics );

		$this->assertArrayHasKey( 'month', $report );
		$this->assertArrayHasKey( 'status', $report );
		$this->assertArrayHasKey( 'breaches', $report );
		$this->assertArrayHasKey( 'sla_met', $report );
		$this->assertTrue( $report['sla_met'] );
		$this->assertSame( [], $report['breaches'] );
	}

	public function test_monthly_report_records_breach(): void {
		$this->sla->set_targets( [ SLAManager::METRIC_UPTIME => 99.9 ] );

		$metrics = [
			SLAManager::METRIC_UPTIME           => 95.0,
			SLAManager::METRIC_PIPELINE_SUCCESS => 99.9,
			SLAManager::METRIC_API_RESPONSE_MS  => 500,
			SLAManager::METRIC_COST_PER_ARTICLE => 5,
		];

		$report = $this->sla->generate_monthly_report( $metrics );
		$this->assertFalse( $report['sla_met'] );
		$this->assertContains( SLAManager::METRIC_UPTIME, $report['breaches'] );
	}

	public function test_monthly_report_stored_in_history(): void {
		$metrics = [
			SLAManager::METRIC_UPTIME           => 100.0,
			SLAManager::METRIC_PIPELINE_SUCCESS => 100.0,
			SLAManager::METRIC_API_RESPONSE_MS  => 400,
			SLAManager::METRIC_COST_PER_ARTICLE => 7,
		];

		$report  = $this->sla->generate_monthly_report( $metrics );
		$history = $this->sla->get_history();

		$this->assertArrayHasKey( $report['month'], $history );
	}

	public function test_monthly_report_sends_email_when_configured(): void {
		update_option( SLAManager::OPTION_REPORT_EMAIL, 'admin@example.com' );

		$this->sla->generate_monthly_report( [
			SLAManager::METRIC_UPTIME           => 100.0,
			SLAManager::METRIC_PIPELINE_SUCCESS => 100.0,
			SLAManager::METRIC_API_RESPONSE_MS  => 300,
			SLAManager::METRIC_COST_PER_ARTICLE => 6,
		] );

		$this->assertCount( 1, $GLOBALS['_mail_log'] );
		$this->assertSame( 'admin@example.com', $GLOBALS['_mail_log'][0]['to'] );
		$this->assertStringContainsString( 'SLA Report', $GLOBALS['_mail_log'][0]['subject'] );
	}

	public function test_no_email_when_report_email_not_configured(): void {
		$this->sla->generate_monthly_report( [
			SLAManager::METRIC_UPTIME           => 100.0,
			SLAManager::METRIC_PIPELINE_SUCCESS => 100.0,
			SLAManager::METRIC_API_RESPONSE_MS  => 300,
			SLAManager::METRIC_COST_PER_ARTICLE => 6,
		] );

		$this->assertSame( [], $GLOBALS['_mail_log'] );
	}

	// -----------------------------------------------------------------------
	// get_history
	// -----------------------------------------------------------------------

	public function test_get_history_returns_empty_initially(): void {
		$this->assertSame( [], $this->sla->get_history() );
	}
}
