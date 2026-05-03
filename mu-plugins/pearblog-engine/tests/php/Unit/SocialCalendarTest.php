<?php
/**
 * Unit tests for SocialCalendar.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Social\SocialCalendar;

class SocialCalendarTest extends TestCase {

	private SocialCalendar $calendar;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']  = [
			'pearblog_social_calendar_enabled'  => true,
			'pearblog_social_queue'             => [],
		];
		$GLOBALS['_actions']  = [];
		$GLOBALS['_cron_scheduled'] = [];
		// Provide a WP_Post for get_post().
		$post = new \WP_Post( [
			'ID'           => 1,
			'post_title'   => 'Test Post',
			'post_content' => 'This is test content for the social calendar.',
			'post_excerpt' => '',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		] );
		$GLOBALS['_posts'] = [ 1 => $post, 42 => $post, 5 => $post ];
		$this->calendar = new SocialCalendar();
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// register() hooks
	// -----------------------------------------------------------------------

	public function test_register_adds_publish_post_action(): void {
		$this->calendar->register();
		$this->assertNotEmpty( $GLOBALS['_actions']['publish_post'] ?? [] );
	}

	public function test_register_disabled_skips_hooks(): void {
		$GLOBALS['_options']['pearblog_social_calendar_enabled'] = false;
		$cal = new SocialCalendar();
		$cal->register();
		$this->assertArrayNotHasKey( 'publish_post', $GLOBALS['_actions'] );
	}

	// -----------------------------------------------------------------------
	// queue_social_posts_on_publish
	// -----------------------------------------------------------------------

	public function test_queue_adds_entries_for_default_platforms(): void {
		// Stub get_post_type.
		$GLOBALS['_post_type'] = 'post';

		$post_id = 42;
		$this->calendar->queue_social_posts_on_publish( $post_id );

		$queue = get_option( 'pearblog_social_queue', [] );
		$this->assertNotEmpty( $queue );

		// Default schedule has 4 platforms.
		$platforms = array_column( array_values( $queue ), 'platform' );
		$this->assertContains( 'twitter', $platforms );
		$this->assertContains( 'linkedin', $platforms );
	}

	public function test_queue_entries_have_required_fields(): void {
		$GLOBALS['_post_type'] = 'post';
		$this->calendar->queue_social_posts_on_publish( 1 );

		$queue = get_option( 'pearblog_social_queue', [] );
		foreach ( $queue as $entry ) {
			$this->assertArrayHasKey( 'entry_id', $entry );
			$this->assertArrayHasKey( 'post_id', $entry );
			$this->assertArrayHasKey( 'platform', $entry );
			$this->assertArrayHasKey( 'due_at', $entry );
			$this->assertArrayHasKey( 'copy', $entry );
			$this->assertArrayHasKey( 'status', $entry );
		}
	}

	public function test_queue_skips_non_post_post_type(): void {
		$GLOBALS['_post_type'] = 'page';
		$this->calendar->queue_social_posts_on_publish( 99 );
		$queue = get_option( 'pearblog_social_queue', [] );
		$this->assertEmpty( $queue );
	}

	public function test_twitter_entry_has_immediate_due_at(): void {
		$GLOBALS['_post_type'] = 'post';
		$before = time();
		$this->calendar->queue_social_posts_on_publish( 1 );
		$after = time();

		$queue    = get_option( 'pearblog_social_queue', [] );
		$twitter  = array_values( array_filter( $queue, fn( $e ) => 'twitter' === $e['platform'] ) );
		$this->assertCount( 1, $twitter );
		// Twitter delay is 0 minutes → due_at should be approximately now.
		$this->assertGreaterThanOrEqual( $before, $twitter[0]['due_at'] );
		$this->assertLessThanOrEqual( $after + 5, $twitter[0]['due_at'] );
	}

	public function test_linkedin_due_at_is_after_twitter(): void {
		$GLOBALS['_post_type'] = 'post';
		$this->calendar->queue_social_posts_on_publish( 1 );

		$queue    = get_option( 'pearblog_social_queue', [] );
		$twitter  = array_values( array_filter( $queue, fn( $e ) => 'twitter' === $e['platform'] ) );
		$linkedin = array_values( array_filter( $queue, fn( $e ) => 'linkedin' === $e['platform'] ) );

		$this->assertNotEmpty( $linkedin );
		$this->assertGreaterThan( $twitter[0]['due_at'], $linkedin[0]['due_at'] );
	}

	public function test_all_new_entries_have_pending_status(): void {
		$GLOBALS['_post_type'] = 'post';
		$this->calendar->queue_social_posts_on_publish( 1 );

		$queue = get_option( 'pearblog_social_queue', [] );
		foreach ( $queue as $entry ) {
			$this->assertSame( 'pending', $entry['status'] );
		}
	}

	// -----------------------------------------------------------------------
	// dispatch_due_entries
	// -----------------------------------------------------------------------

	public function test_dispatch_marks_due_entries_as_sent(): void {
		// Inject an already-due entry directly.
		$queue = [
			'sc_1_twitter' => [
				'entry_id' => 'sc_1_twitter',
				'post_id'  => 1,
				'platform' => 'twitter',
				'due_at'   => time() - 100,
				'copy'     => 'Test tweet',
				'status'   => 'pending',
				'created_at' => time() - 200,
			],
		];
		update_option( 'pearblog_social_queue', $queue );

		$this->calendar->dispatch_due_entries();

		$updated = get_option( 'pearblog_social_queue', [] );
		// Entry should be marked sent or pruned.
		if ( isset( $updated['sc_1_twitter'] ) ) {
			$this->assertSame( 'sent', $updated['sc_1_twitter']['status'] );
		} else {
			// Entry was pruned (also valid).
			$this->assertArrayNotHasKey( 'sc_1_twitter', $updated );
		}
	}

	public function test_dispatch_skips_future_entries(): void {
		$queue = [
			'sc_1_facebook' => [
				'entry_id' => 'sc_1_facebook',
				'post_id'  => 1,
				'platform' => 'facebook',
				'due_at'   => time() + 3600, // 1 hour in future
				'copy'     => 'Test fb post',
				'status'   => 'pending',
				'created_at' => time(),
			],
		];
		update_option( 'pearblog_social_queue', $queue );

		$this->calendar->dispatch_due_entries();

		$updated = get_option( 'pearblog_social_queue', [] );
		$this->assertArrayHasKey( 'sc_1_facebook', $updated );
		$this->assertSame( 'pending', $updated['sc_1_facebook']['status'] );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	public function test_rest_list_returns_response(): void {
		$GLOBALS['_current_user_can'] = true;
		$request  = new \WP_REST_Request();
		$response = $this->calendar->rest_list( $request );
		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->status );
	}

	public function test_rest_cancel_returns_404_for_missing_entry(): void {
		$GLOBALS['_current_user_can'] = true;
		$request  = new \WP_REST_Request();
		$request->set_param( 'entry_id', 'nonexistent' );
		$response = $this->calendar->rest_cancel( $request );
		$this->assertSame( 404, $response->status );
	}

	public function test_rest_cancel_removes_entry(): void {
		$GLOBALS['_current_user_can'] = true;
		update_option( 'pearblog_social_queue', [
			'sc_5_twitter' => [
				'entry_id' => 'sc_5_twitter',
				'post_id'  => 5,
				'platform' => 'twitter',
				'due_at'   => time() + 3600,
				'copy'     => 'hello',
				'status'   => 'pending',
				'created_at' => time(),
			],
		] );

		$request  = new \WP_REST_Request();
		$request->set_param( 'entry_id', 'sc_5_twitter' );
		$response = $this->calendar->rest_cancel( $request );
		$this->assertSame( 200, $response->status );

		$queue = get_option( 'pearblog_social_queue', [] );
		$this->assertArrayNotHasKey( 'sc_5_twitter', $queue );
	}

	public function test_rest_permission_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->calendar->rest_permission() );
	}

	public function test_rest_permission_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->calendar->rest_permission() );
	}

	// No helper needed – using WP_REST_Request directly above.
}
