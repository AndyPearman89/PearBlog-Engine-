<?php
/**
 * Unit tests for EmailDigest.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Email\EmailDigest;

class EmailDigestTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_mail_log']   = [];
		$GLOBALS['_post_list']  = [];
		$GLOBALS['_posts']      = [];
	}

	public function test_cron_hook_constant(): void {
		$this->assertSame( 'pearblog_email_digest', EmailDigest::CRON_HOOK );
	}

	public function test_instantiation(): void {
		$digest = new EmailDigest();
		$this->assertInstanceOf( EmailDigest::class, $digest );
	}

	public function test_register_attaches_cron_hook(): void {
		$digest = new EmailDigest();
		$digest->register();
		$this->assertTrue( (bool) has_action( EmailDigest::CRON_HOOK, [ $digest, 'send' ] ) );
	}

	public function test_maybe_schedule_does_not_throw(): void {
		$digest = new EmailDigest();
		$this->expectNotToPerformAssertions();
		$digest->maybe_schedule();
	}

	public function test_send_returns_false_when_no_recent_posts(): void {
		// WP query stub returns no posts.
		$digest = new EmailDigest();
		$result = $digest->send( 7 );
		$this->assertFalse( $result );
	}

	public function test_send_accepts_custom_days_parameter(): void {
		$digest = new EmailDigest();
		// Both 7 and 30 should work without throwing.
		$this->assertIsBool( $digest->send( 30 ) );
	}
}
