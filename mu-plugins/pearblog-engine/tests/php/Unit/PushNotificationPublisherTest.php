<?php
/**
 * Unit tests for PushNotificationPublisher.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Social\PushNotificationPublisher;

class PushNotificationPublisherTest extends TestCase {

	private PushNotificationPublisher $publisher;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_actions']   = [];
		$this->publisher = new PushNotificationPublisher();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant(): void {
		$this->assertSame( 'pearblog_push_enabled', PushNotificationPublisher::OPTION_ENABLED );
	}

	public function test_option_provider_constant(): void {
		$this->assertSame( 'pearblog_push_provider', PushNotificationPublisher::OPTION_PROVIDER );
	}

	public function test_option_os_app_id_constant(): void {
		$this->assertSame( 'pearblog_push_onesignal_id', PushNotificationPublisher::OPTION_OS_APP_ID );
	}

	public function test_option_fcm_key_constant(): void {
		$this->assertSame( 'pearblog_push_fcm_key', PushNotificationPublisher::OPTION_FCM_KEY );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_false_by_default(): void {
		$this->assertFalse( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_false_when_enabled_but_no_onesignal_keys(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'onesignal' );

		$this->assertFalse( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_true_when_onesignal_configured(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'onesignal' );
		update_option( PushNotificationPublisher::OPTION_OS_APP_ID, 'app-id-123' );
		update_option( PushNotificationPublisher::OPTION_OS_API_KEY, 'api-key-abc' );

		$this->assertTrue( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_false_when_fcm_has_no_key(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'fcm' );

		$this->assertFalse( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_true_when_fcm_key_set(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'fcm' );
		update_option( PushNotificationPublisher::OPTION_FCM_KEY, 'fcm-server-key' );

		$this->assertTrue( $this->publisher->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// notify — disabled
	// -----------------------------------------------------------------------

	public function test_notify_returns_empty_result_when_disabled(): void {
		$post             = new \WP_Post( [
			'post_title'   => 'Test Article',
			'post_content' => '<p>Content here.</p>',
			'post_excerpt' => '',
			'post_type'    => 'post',
		] );

		$result = $this->publisher->notify( 1, $post );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'provider', $result );
		$this->assertSame( 'none', $result['provider'] );
	}

	// -----------------------------------------------------------------------
	// on_publish — skip for non-post types
	// -----------------------------------------------------------------------

	public function test_on_publish_skips_non_post_types(): void {
		$this->expectNotToPerformAssertions();

		$post             = new \WP_Post( [
			'post_type'    => 'page',
			'post_title'   => 'A Page',
			'post_content' => '<p>Content.</p>',
			'post_excerpt' => '',
		] );

		$this->publisher->on_publish( 1, $post );
	}

	public function test_on_publish_skips_when_already_notified(): void {
		$this->expectNotToPerformAssertions();

		$post             = new \WP_Post( [
			'post_type'    => 'post',
			'post_title'   => 'Title',
			'post_content' => '<p>Content.</p>',
			'post_excerpt' => '',
		] );

		// Mark as already notified.
		update_post_meta( 1, 'pearblog_push_notified', time() );

		$this->publisher->on_publish( 1, $post );
	}

	// -----------------------------------------------------------------------
	// notify — enabled with onesignal (wp_remote_post stubbed)
	// -----------------------------------------------------------------------

	public function test_notify_returns_result_with_provider_when_onesignal(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'onesignal' );
		update_option( PushNotificationPublisher::OPTION_OS_APP_ID, 'app-id-123' );
		update_option( PushNotificationPublisher::OPTION_OS_API_KEY, 'api-key-abc' );

		$post             = new \WP_Post( [
			'ID'           => 1,
			'post_title'   => 'New Article',
			'post_content' => '<p>Amazing content about something interesting.</p>',
			'post_excerpt' => '',
			'post_type'    => 'post',
		] );

		$result = $this->publisher->notify( 1, $post );

		$this->assertSame( 'onesignal', $result['provider'] );
		$this->assertArrayHasKey( 'success', $result );
	}

	public function test_notify_stores_result_in_post_meta(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'onesignal' );
		update_option( PushNotificationPublisher::OPTION_OS_APP_ID, 'app-id' );
		update_option( PushNotificationPublisher::OPTION_OS_API_KEY, 'api-key' );

		$post             = new \WP_Post( [
			'ID'           => 2,
			'post_title'   => 'Another Article',
			'post_content' => '<p>Content.</p>',
			'post_excerpt' => '',
			'post_type'    => 'post',
		] );

		$this->publisher->notify( 2, $post );

		$notified = get_post_meta( 2, 'pearblog_push_notified', true );
		$this->assertGreaterThan( 0, $notified );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_does_not_throw(): void {
		$this->expectNotToPerformAssertions();
		$this->publisher->register();
	}
}
