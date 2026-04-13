<?php
/**
 * Unit tests for PipelineAuditLog.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Pipeline\PipelineAuditLog;

class PipelineAuditLogTest extends TestCase {

	/** @var PipelineAuditLog */
	private PipelineAuditLog $log;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_current_user_can'] = true; // grant manage_options in permission tests.
		$this->log = new PipelineAuditLog();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_current_user_can'] = false;
	}

	// -----------------------------------------------------------------------
	// append / count / clear
	// -----------------------------------------------------------------------

	public function test_append_returns_entry_with_expected_keys(): void {
		$entry = $this->log->append( 'test_event', PipelineAuditLog::LEVEL_INFO, [ 'foo' => 'bar' ] );

		$this->assertArrayHasKey( 'id', $entry );
		$this->assertArrayHasKey( 'timestamp', $entry );
		$this->assertArrayHasKey( 'event', $entry );
		$this->assertArrayHasKey( 'level', $entry );
		$this->assertArrayHasKey( 'context', $entry );
	}

	public function test_append_stores_correct_event_name(): void {
		$entry = $this->log->append( 'my_custom_event' );
		$this->assertSame( 'my_custom_event', $entry['event'] );
	}

	public function test_append_stores_correct_level(): void {
		$entry = $this->log->append( 'ev', PipelineAuditLog::LEVEL_ERROR );
		$this->assertSame( PipelineAuditLog::LEVEL_ERROR, $entry['level'] );
	}

	public function test_append_stores_context(): void {
		$ctx   = [ 'post_id' => 42, 'topic' => 'PHP tips' ];
		$entry = $this->log->append( 'ev', PipelineAuditLog::LEVEL_INFO, $ctx );
		$this->assertSame( $ctx, $entry['context'] );
	}

	public function test_count_increases_after_append(): void {
		$this->assertSame( 0, $this->log->count() );
		$this->log->append( 'ev1' );
		$this->assertSame( 1, $this->log->count() );
		$this->log->append( 'ev2' );
		$this->assertSame( 2, $this->log->count() );
	}

	public function test_clear_removes_all_events(): void {
		$this->log->append( 'ev1' );
		$this->log->append( 'ev2' );
		$this->log->clear();
		$this->assertSame( 0, $this->log->count() );
	}

	public function test_id_has_evt_prefix(): void {
		$entry = $this->log->append( 'ev' );
		$this->assertStringStartsWith( 'evt_', $entry['id'] );
	}

	public function test_id_is_unique_per_entry(): void {
		$a = $this->log->append( 'ev' );
		$b = $this->log->append( 'ev' );
		$this->assertNotSame( $a['id'], $b['id'] );
	}

	// -----------------------------------------------------------------------
	// get_events
	// -----------------------------------------------------------------------

	public function test_get_events_returns_newest_first(): void {
		$this->log->append( 'first' );
		$this->log->append( 'second' );
		$events = $this->log->get_events( 10 );
		$this->assertSame( 'second', $events[0]['event'] );
		$this->assertSame( 'first', $events[1]['event'] );
	}

	public function test_get_events_respects_limit(): void {
		for ( $i = 0; $i < 10; $i++ ) {
			$this->log->append( "ev{$i}" );
		}
		$this->assertCount( 3, $this->log->get_events( 3 ) );
	}

	public function test_get_events_filters_by_level(): void {
		$this->log->append( 'info_ev', PipelineAuditLog::LEVEL_INFO );
		$this->log->append( 'error_ev', PipelineAuditLog::LEVEL_ERROR );
		$this->log->append( 'warn_ev', PipelineAuditLog::LEVEL_WARNING );

		$errors = $this->log->get_events( 50, PipelineAuditLog::LEVEL_ERROR );
		$this->assertCount( 1, $errors );
		$this->assertSame( 'error_ev', $errors[0]['event'] );
	}

	public function test_get_events_filters_by_event_type(): void {
		$this->log->append( 'pipeline_started' );
		$this->log->append( 'pipeline_completed' );
		$this->log->append( 'pipeline_started' );

		$started = $this->log->get_events( 50, null, 'pipeline_started' );
		$this->assertCount( 2, $started );
	}

	public function test_get_events_combines_level_and_event_filter(): void {
		$this->log->append( 'pipeline_started', PipelineAuditLog::LEVEL_INFO );
		$this->log->append( 'cron_error', PipelineAuditLog::LEVEL_ERROR );
		$this->log->append( 'cron_error', PipelineAuditLog::LEVEL_WARNING );

		$result = $this->log->get_events( 50, PipelineAuditLog::LEVEL_ERROR, 'cron_error' );
		$this->assertCount( 1, $result );
	}

	public function test_get_all_events_returns_chronological_order(): void {
		$this->log->append( 'first' );
		$this->log->append( 'second' );
		$all = $this->log->get_all_events();
		$this->assertSame( 'first', $all[0]['event'] );
		$this->assertSame( 'second', $all[1]['event'] );
	}

	// -----------------------------------------------------------------------
	// Ring-buffer (MAX_ENTRIES enforcement)
	// -----------------------------------------------------------------------

	public function test_ring_buffer_enforces_max_entries(): void {
		// Append MAX_ENTRIES + 5 events to trigger truncation.
		$over = PipelineAuditLog::MAX_ENTRIES + 5;
		for ( $i = 0; $i < $over; $i++ ) {
			$this->log->append( "ev{$i}" );
		}
		$this->assertSame( PipelineAuditLog::MAX_ENTRIES, $this->log->count() );
	}

	public function test_ring_buffer_retains_newest_entries(): void {
		$over = PipelineAuditLog::MAX_ENTRIES + 3;
		for ( $i = 0; $i < $over; $i++ ) {
			$this->log->append( "ev{$i}" );
		}
		// After truncation the first retained event should be ev3 (0-indexed).
		$all = $this->log->get_all_events();
		$this->assertSame( 'ev3', $all[0]['event'] );
	}

	// -----------------------------------------------------------------------
	// Action callbacks
	// -----------------------------------------------------------------------

	public function test_on_pipeline_started_logs_correct_event(): void {
		$this->log->on_pipeline_started( 'Test Topic' );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'pipeline_started', $events[0]['event'] );
		$this->assertSame( 'Test Topic', $events[0]['context']['topic'] );
	}

	public function test_on_pipeline_completed_logs_post_id_and_topic(): void {
		$this->log->on_pipeline_completed( 123, 'My Topic' );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'pipeline_completed', $events[0]['event'] );
		$this->assertSame( 123, $events[0]['context']['post_id'] );
		$this->assertSame( 'My Topic', $events[0]['context']['topic'] );
	}

	public function test_on_duplicate_skipped_is_warning(): void {
		$this->log->on_duplicate_skipped( 'Duplicate Topic', [ 'similarity' => 0.95 ] );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'duplicate_skipped', $events[0]['event'] );
		$this->assertSame( PipelineAuditLog::LEVEL_WARNING, $events[0]['level'] );
	}

	public function test_on_cron_error_is_error_level(): void {
		$this->log->on_cron_error( 1, 'Pipeline exploded' );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'cron_error', $events[0]['event'] );
		$this->assertSame( PipelineAuditLog::LEVEL_ERROR, $events[0]['level'] );
		$this->assertSame( 'Pipeline exploded', $events[0]['context']['message'] );
	}

	public function test_on_seo_applied_stores_title(): void {
		$this->log->on_seo_applied( 10, 'SEO Title', 'Meta description here.' );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'seo_applied', $events[0]['event'] );
		$this->assertSame( 'SEO Title', $events[0]['context']['title'] );
	}

	public function test_on_quality_scored_stores_score(): void {
		$this->log->on_quality_scored( 10, 87.5 );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'quality_scored', $events[0]['event'] );
		$this->assertSame( 87.5, $events[0]['context']['score'] );
	}

	public function test_on_content_refreshed_stores_new_title(): void {
		$this->log->on_content_refreshed( 10, 'Refreshed Title' );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'content_refreshed', $events[0]['event'] );
		$this->assertSame( 'Refreshed Title', $events[0]['context']['new_title'] );
	}

	public function test_on_translation_created_stores_language(): void {
		$this->log->on_translation_created( 20, 10, 'fr' );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'translation_created', $events[0]['event'] );
		$this->assertSame( 'fr', $events[0]['context']['language'] );
		$this->assertSame( 10, $events[0]['context']['source_post_id'] );
	}

	public function test_on_social_published_stores_networks(): void {
		$this->log->on_social_published( 10, [ 'twitter' => true, 'facebook' => false ] );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'social_published', $events[0]['event'] );
		$this->assertContains( 'twitter', $events[0]['context']['networks'] );
	}

	public function test_on_cdn_offloaded_stores_provider(): void {
		$this->log->on_cdn_offloaded( 55, 'https://cdn.example.com/img.jpg', 'bunny' );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'cdn_offloaded', $events[0]['event'] );
		$this->assertSame( 'bunny', $events[0]['context']['provider'] );
	}

	public function test_on_bg_job_completed_stores_job_id(): void {
		$this->log->on_bg_job_completed( [ 'id' => 'job_abc', 'topic' => 'PHP' ] );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'bg_job_completed', $events[0]['event'] );
		$this->assertSame( 'job_abc', $events[0]['context']['job_id'] );
	}

	public function test_on_bg_job_failed_is_error_level(): void {
		$ex = new \RuntimeException( 'AI timeout' );
		$this->log->on_bg_job_failed( [ 'id' => 'job_xyz', 'topic' => 'PHP' ], $ex );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'bg_job_failed', $events[0]['event'] );
		$this->assertSame( PipelineAuditLog::LEVEL_ERROR, $events[0]['level'] );
		$this->assertSame( 'AI timeout', $events[0]['context']['error'] );
	}

	public function test_on_sla_breached_is_error_level(): void {
		$this->log->on_sla_breached( 'uptime_pct', 99.9, 98.1 );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'sla_breached', $events[0]['event'] );
		$this->assertSame( PipelineAuditLog::LEVEL_ERROR, $events[0]['level'] );
		$this->assertSame( 'uptime_pct', $events[0]['context']['metric'] );
	}

	public function test_on_abtest_winner_promoted_stores_winner(): void {
		$this->log->on_abtest_winner_promoted( 'ab_test1', 'a', 82.0, 79.5 );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'abtest_winner_promoted', $events[0]['event'] );
		$this->assertSame( 'a', $events[0]['context']['winner'] );
	}

	// -----------------------------------------------------------------------
	// REST: permission callback
	// -----------------------------------------------------------------------

	public function test_rest_permission_allows_manage_options(): void {
		// current_user_can stub returns true when 'manage_options' is passed.
		$request = new \WP_REST_Request();
		$this->assertTrue( $this->log->rest_permission( $request ) );
	}

	public function test_rest_permission_allows_valid_bearer_token(): void {
		update_option( 'pearblog_api_key', 'secret-token' );
		// $GLOBALS['_current_user_can'] is true (set in setUp), so this also tests
		// that a privileged user with a valid bearer token passes the check.
		$request = new \WP_REST_Request();
		$request->set_header( 'authorization', 'Bearer secret-token' );
		$this->assertTrue( $this->log->rest_permission( $request ) );
	}

	// -----------------------------------------------------------------------
	// REST: get events endpoint
	// -----------------------------------------------------------------------

	public function test_rest_get_events_returns_200(): void {
		$this->log->append( 'pipeline_started' );
		$request = new \WP_REST_Request();
		$request->set_param( 'limit', 10 );
		$response = $this->log->rest_get_events( $request );
		$this->assertSame( 200, $response->status );
		$this->assertArrayHasKey( 'events', $response->data );
		$this->assertArrayHasKey( 'total', $response->data );
	}

	public function test_rest_get_events_respects_limit_param(): void {
		for ( $i = 0; $i < 10; $i++ ) {
			$this->log->append( "ev{$i}" );
		}
		$request = new \WP_REST_Request();
		$request->set_param( 'limit', 3 );
		$response = $this->log->rest_get_events( $request );
		$this->assertCount( 3, $response->data['events'] );
	}

	public function test_rest_get_events_filters_by_level(): void {
		$this->log->append( 'info_ev', PipelineAuditLog::LEVEL_INFO );
		$this->log->append( 'error_ev', PipelineAuditLog::LEVEL_ERROR );
		$request = new \WP_REST_Request();
		$request->set_param( 'limit', 50 );
		$request->set_param( 'level', PipelineAuditLog::LEVEL_ERROR );
		$response = $this->log->rest_get_events( $request );
		$this->assertCount( 1, $response->data['events'] );
		$this->assertSame( 'error_ev', $response->data['events'][0]['event'] );
	}

	// -----------------------------------------------------------------------
	// REST: append endpoint
	// -----------------------------------------------------------------------

	public function test_rest_append_event_returns_201(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'event', 'custom_event' );
		$request->set_param( 'level', PipelineAuditLog::LEVEL_INFO );
		$request->set_param( 'context', [ 'key' => 'value' ] );
		$response = $this->log->rest_append_event( $request );
		$this->assertSame( 201, $response->status );
		$this->assertTrue( $response->data['appended'] );
	}

	public function test_rest_append_event_returns_400_when_event_empty(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'event', '' );
		$response = $this->log->rest_append_event( $request );
		$this->assertSame( 400, $response->status );
	}

	public function test_rest_append_event_persists_to_log(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'event', 'api_event' );
		$request->set_param( 'level', PipelineAuditLog::LEVEL_WARNING );
		$this->log->rest_append_event( $request );
		$this->assertSame( 1, $this->log->count() );
		$events = $this->log->get_events( 1 );
		$this->assertSame( 'api_event', $events[0]['event'] );
		$this->assertSame( PipelineAuditLog::LEVEL_WARNING, $events[0]['level'] );
	}
}
