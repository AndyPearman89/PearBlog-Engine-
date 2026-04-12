<?php
/**
 * Unit tests for PerformanceDashboard.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Monitoring\PerformanceDashboard;

class PerformanceDashboardTest extends TestCase {

	private PerformanceDashboard $dashboard;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$this->dashboard = new PerformanceDashboard();
		$this->dashboard->reset_metrics();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->dashboard->reset_metrics();
	}

	public function test_starts_with_empty_records(): void {
		$this->assertCount( 0, $this->dashboard->get_all_records() );
	}

	public function test_record_pipeline_run_stores_a_record(): void {
		$this->dashboard->record_pipeline_run( 1, 'Test Topic', 2.5 );
		$records = $this->dashboard->get_all_records();
		$this->assertCount( 1, $records );
	}

	public function test_record_pipeline_run_contains_expected_keys(): void {
		$this->dashboard->record_pipeline_run( 42, 'My Topic', 5.0 );
		$record = $this->dashboard->get_all_records()[0];

		$this->assertArrayHasKey( 'ts', $record );
		$this->assertArrayHasKey( 'type', $record );
		$this->assertArrayHasKey( 'post_id', $record );
		$this->assertArrayHasKey( 'topic', $record );
		$this->assertArrayHasKey( 'duration', $record );
		$this->assertSame( 'success', $record['type'] );
		$this->assertSame( 42, $record['post_id'] );
		$this->assertSame( 'My Topic', $record['topic'] );
		$this->assertEqualsWithDelta( 5.0, $record['duration'], 0.001 );
	}

	public function test_record_pipeline_error_stores_error_record(): void {
		$this->dashboard->record_pipeline_error( 1, 'Something failed' );
		$records = $this->dashboard->get_all_records();
		$this->assertCount( 1, $records );
		$this->assertSame( 'error', $records[0]['type'] );
	}

	public function test_get_summary_reflects_recorded_data(): void {
		$this->dashboard->record_pipeline_run( 1, 'Topic A', 3.0 );
		$this->dashboard->record_pipeline_run( 2, 'Topic B', 7.0 );
		$this->dashboard->record_pipeline_error( 1, 'Oops' );

		$summary = $this->dashboard->get_summary();

		$this->assertSame( 3, $summary['total_runs'] );
		$this->assertSame( 2, $summary['successes'] );
		$this->assertSame( 1, $summary['errors'] );
		$this->assertEqualsWithDelta( 33.3, $summary['error_rate_pct'], 0.5 );
	}

	public function test_get_recent_runs_returns_newest_first(): void {
		$this->dashboard->record_pipeline_run( 1, 'Topic A', 1.0 );
		$this->dashboard->record_pipeline_run( 2, 'Topic B', 2.0 );

		$recent = $this->dashboard->get_recent_runs( 5 );
		$this->assertSame( 'Topic B', $recent[0]['topic'] );
		$this->assertSame( 'Topic A', $recent[1]['topic'] );
	}

	public function test_get_recent_runs_respects_limit(): void {
		for ( $i = 1; $i <= 10; $i++ ) {
			$this->dashboard->record_pipeline_run( $i, "Topic {$i}", 1.0 );
		}

		$recent = $this->dashboard->get_recent_runs( 3 );
		$this->assertCount( 3, $recent );
	}

	public function test_reset_metrics_empties_all_data(): void {
		$this->dashboard->record_pipeline_run( 1, 'Topic', 1.0 );
		$this->dashboard->reset_metrics();
		$this->assertCount( 0, $this->dashboard->get_all_records() );
	}

	public function test_thresholds_return_array_with_expected_keys(): void {
		$thresholds = $this->dashboard->get_thresholds();
		$this->assertArrayHasKey( 'pipeline_duration_sec', $thresholds );
		$this->assertArrayHasKey( 'memory_peak_mb', $thresholds );
		$this->assertArrayHasKey( 'error_rate_pct', $thresholds );
		$this->assertArrayHasKey( 'cost_per_article_usd', $thresholds );
	}

	public function test_topic_truncated_to_100_chars(): void {
		$long_topic = str_repeat( 'a', 200 );
		$this->dashboard->record_pipeline_run( 1, $long_topic, 1.0 );
		$record = $this->dashboard->get_all_records()[0];
		$this->assertLessThanOrEqual( 100, mb_strlen( $record['topic'] ) );
	}

	public function test_ring_buffer_caps_at_200_records(): void {
		for ( $i = 1; $i <= 210; $i++ ) {
			$this->dashboard->record_pipeline_run( $i, "Topic {$i}", 0.1 );
		}
		$this->assertLessThanOrEqual( 200, count( $this->dashboard->get_all_records() ) );
	}

	// -----------------------------------------------------------------------
	// get_csv_rows()
	// -----------------------------------------------------------------------

	public function test_get_csv_rows_returns_header_as_first_row(): void {
		$rows = $this->dashboard->get_csv_rows();

		$this->assertNotEmpty( $rows );
		$this->assertSame( 'timestamp', $rows[0][0] );
		$this->assertSame( 'type',      $rows[0][1] );
		$this->assertSame( 'post_id',   $rows[0][2] );
		$this->assertSame( 'topic',     $rows[0][3] );
	}

	public function test_get_csv_rows_includes_recorded_run(): void {
		$this->dashboard->record_pipeline_run( 7, 'CSV Topic', 1.5 );
		$rows = $this->dashboard->get_csv_rows();

		// rows[0] = header, rows[1] = the run.
		$this->assertCount( 2, $rows );
		$this->assertSame( 'success',   $rows[1][1] );
		$this->assertSame( '7',         $rows[1][2] );
		$this->assertSame( 'CSV Topic', $rows[1][3] );
	}

	public function test_get_csv_rows_empty_when_no_runs(): void {
		$rows = $this->dashboard->get_csv_rows();

		// Only the header row should be present.
		$this->assertCount( 1, $rows );
		$this->assertSame( 'timestamp', $rows[0][0] );
	}

	public function test_get_csv_rows_respects_limit(): void {
		for ( $i = 1; $i <= 10; $i++ ) {
			$this->dashboard->record_pipeline_run( $i, "T{$i}", 0.5 );
		}

		$rows = $this->dashboard->get_csv_rows( 3 );
		// 1 header + 3 data rows.
		$this->assertCount( 4, $rows );
	}
}
