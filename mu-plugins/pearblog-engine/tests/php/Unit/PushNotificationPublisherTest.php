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
		$this->publisher       = new PushNotificationPublisher();
	}

	// -----------------------------------------------------------------------
	// Option constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant_value(): void {
		$this->assertSame( 'pearblog_push_enabled', PushNotificationPublisher::OPTION_ENABLED );
	}

	public function test_option_provider_constant_value(): void {
		$this->assertSame( 'pearblog_push_provider', PushNotificationPublisher::OPTION_PROVIDER );
	}

	public function test_option_onesignal_app_id_constant_value(): void {
		$this->assertSame( 'pearblog_push_onesignal_id', PushNotificationPublisher::OPTION_OS_APP_ID );
	}

	public function test_option_onesignal_api_key_constant_value(): void {
		$this->assertSame( 'pearblog_push_onesignal_key', PushNotificationPublisher::OPTION_OS_API_KEY );
	}

	public function test_option_fcm_key_constant_value(): void {
		$this->assertSame( 'pearblog_push_fcm_key', PushNotificationPublisher::OPTION_FCM_KEY );
	}

	// -----------------------------------------------------------------------
	// is_enabled — disabled paths
	// -----------------------------------------------------------------------

	public function test_is_enabled_returns_false_by_default(): void {
		$this->assertFalse( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_enabled_but_no_onesignal_config(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		// No app ID or API key set.
		$this->assertFalse( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_onesignal_missing_api_key(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'onesignal' );
		update_option( PushNotificationPublisher::OPTION_OS_APP_ID, 'app-id-123' );
		// No API key → still disabled.
		$this->assertFalse( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_onesignal_missing_app_id(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'onesignal' );
		update_option( PushNotificationPublisher::OPTION_OS_API_KEY, 'api-key-abc' );
		// No app ID → still disabled.
		$this->assertFalse( $this->publisher->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// is_enabled — enabled paths
	// -----------------------------------------------------------------------

	public function test_is_enabled_returns_true_for_fully_configured_onesignal(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'onesignal' );
		update_option( PushNotificationPublisher::OPTION_OS_APP_ID, 'app-id-123' );
		update_option( PushNotificationPublisher::OPTION_OS_API_KEY, 'rest-api-key-xyz' );
		$this->assertTrue( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_returns_false_for_fcm_without_key(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'fcm' );
		// No FCM key → disabled.
		$this->assertFalse( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_returns_true_for_fully_configured_fcm(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'fcm' );
		update_option( PushNotificationPublisher::OPTION_FCM_KEY, 'fcm-server-key-abc' );
		$this->assertTrue( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_returns_false_for_unknown_provider(): void {
		update_option( PushNotificationPublisher::OPTION_ENABLED, true );
		update_option( PushNotificationPublisher::OPTION_PROVIDER, 'unknown_provider' );
		$this->assertFalse( $this->publisher->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// notify — disabled path
	// -----------------------------------------------------------------------

	public function test_notify_returns_disabled_when_not_enabled(): void {
		$post             = new \WP_Post();
		$post->post_title   = 'Test Article';
		$post->post_content = 'Article body.';
		$post->post_excerpt = '';
		$post->post_type    = 'post';

		$result = $this->publisher->notify( 1, $post );

		$this->assertSame( 'none', $result['provider'] );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'disabled', $result['response'] );
	}
}
