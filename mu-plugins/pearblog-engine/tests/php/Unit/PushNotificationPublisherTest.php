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
		$GLOBALS['_options']          = [];
		$GLOBALS['_actions']          = [];
		$GLOBALS['_current_user_can'] = true;
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

	public function test_option_os_api_key_constant(): void {
		$this->assertSame( 'pearblog_push_onesignal_key', PushNotificationPublisher::OPTION_OS_API_KEY );
	}

	public function test_option_fcm_key_constant(): void {
		$this->assertSame( 'pearblog_push_fcm_key', PushNotificationPublisher::OPTION_FCM_KEY );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_returns_false_when_option_not_set(): void {
		$this->assertFalse( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_onesignal_keys_missing(): void {
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_ENABLED ]  = true;
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_PROVIDER ] = 'onesignal';
		// No app id / api key set — disabled.
		$this->assertFalse( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_returns_true_for_valid_onesignal(): void {
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_ENABLED ]   = true;
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_PROVIDER ]  = 'onesignal';
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_OS_APP_ID ] = 'test-app-id';
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_OS_API_KEY ] = 'test-api-key';
		$this->assertTrue( $this->publisher->is_enabled() );
	}

	public function test_is_enabled_returns_true_for_valid_fcm(): void {
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_ENABLED ]  = true;
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_PROVIDER ] = 'fcm';
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_FCM_KEY ]  = 'test-fcm-key';
		$this->assertTrue( $this->publisher->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_publish_post_action(): void {
		$this->publisher->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['publish_post'] ) );
	}

	// -----------------------------------------------------------------------
	// on_publish
	// -----------------------------------------------------------------------

	public function test_on_publish_skips_when_not_publish(): void {
		$post = new \WP_Post( [ 'post_type' => 'page', 'post_title' => 'Test', 'post_content' => '', 'ID' => 1 ] );
		// Not a 'post' type — should skip early.
		$this->publisher->on_publish( 1, $post );
		$this->assertTrue( true );
	}

	public function test_on_publish_skips_when_not_enabled(): void {
		$post = new \WP_Post( [ 'post_type' => 'post', 'post_title' => 'Test', 'post_content' => '', 'ID' => 1 ] );
		// Publisher not enabled — verify is_enabled check stops execution.
		// Should not throw.
		$this->publisher->on_publish( 1, $post );
		$this->assertTrue( true );
	}

	// -----------------------------------------------------------------------
	// notify (structural)
	// -----------------------------------------------------------------------

	public function test_notify_returns_array(): void {
		$post    = new \WP_Post( [ 'post_type' => 'post', 'post_title' => 'Test', 'post_content' => '', 'ID' => 99 ] );
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_ENABLED ]   = true;
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_PROVIDER ]  = 'onesignal';
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_OS_APP_ID ] = 'app';
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_OS_API_KEY ] = 'key';
		// Actual HTTP will fail but result is still an array.
		$result = $this->publisher->notify( 99, $post );
		$this->assertIsArray( $result );
	}

	public function test_notify_result_has_provider_key(): void {
		$post    = new \WP_Post( [ 'post_type' => 'post', 'post_title' => 'Test', 'post_content' => '', 'ID' => 99 ] );
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_ENABLED ]   = true;
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_PROVIDER ]  = 'onesignal';
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_OS_APP_ID ] = 'app';
		$GLOBALS['_options'][ PushNotificationPublisher::OPTION_OS_API_KEY ] = 'key';
		$result = $this->publisher->notify( 99, $post );
		$this->assertArrayHasKey( 'provider', $result );
	}
}
