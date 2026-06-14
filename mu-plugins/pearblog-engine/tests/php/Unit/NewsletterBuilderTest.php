<?php

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Email\NewsletterBuilder;

/**
 * @covers \PearBlogEngine\Email\NewsletterBuilder
 */
class NewsletterBuilderTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_options']         = [];
		$GLOBALS['_cron_scheduled']  = [];
		$GLOBALS['_actions']         = [];
		$GLOBALS['_action_handlers'] = [];
		$GLOBALS['_current_user_can'] = false;
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant(): void {
		$this->assertSame( 'pearblog_newsletter_enabled', NewsletterBuilder::OPTION_ENABLED );
	}

	public function test_option_provider_constant(): void {
		$this->assertSame( 'pearblog_newsletter_provider', NewsletterBuilder::OPTION_PROVIDER );
	}

	public function test_option_api_key_constant(): void {
		$this->assertSame( 'pearblog_newsletter_api_key', NewsletterBuilder::OPTION_API_KEY );
	}

	public function test_option_list_id_constant(): void {
		$this->assertSame( 'pearblog_newsletter_list_id', NewsletterBuilder::OPTION_LIST_ID );
	}

	public function test_option_from_email_constant(): void {
		$this->assertSame( 'pearblog_newsletter_from_email', NewsletterBuilder::OPTION_FROM_EMAIL );
	}

	public function test_option_from_name_constant(): void {
		$this->assertSame( 'pearblog_newsletter_from_name', NewsletterBuilder::OPTION_FROM_NAME );
	}

	public function test_option_subject_constant(): void {
		$this->assertSame( 'pearblog_newsletter_subject', NewsletterBuilder::OPTION_SUBJECT );
	}

	public function test_option_articles_n_constant(): void {
		$this->assertSame( 'pearblog_newsletter_articles_n', NewsletterBuilder::OPTION_ARTICLES_N );
	}

	// -----------------------------------------------------------------------
	// send_weekly_newsletter() – disabled state
	// -----------------------------------------------------------------------

	public function test_send_weekly_newsletter_returns_disabled_when_not_enabled(): void {
		// OPTION_ENABLED is false by default.
		$builder = new NewsletterBuilder();
		$result  = $builder->send_weekly_newsletter();

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'none', $result['provider'] );
		$this->assertSame( 'disabled', $result['response'] );
	}

	public function test_send_weekly_newsletter_disabled_does_not_update_last_sent(): void {
		$builder = new NewsletterBuilder();
		$builder->send_weekly_newsletter();

		$this->assertFalse( get_option( 'pearblog_newsletter_last_sent', false ) );
	}

	public function test_send_weekly_newsletter_disabled_does_not_fire_action(): void {
		$fired   = false;
		add_action( 'pearblog_newsletter_sent', function () use ( &$fired ) {
			$fired = true;
		} );

		$builder = new NewsletterBuilder();
		$builder->send_weekly_newsletter();

		$this->assertFalse( $fired );
	}

	// -----------------------------------------------------------------------
	// maybe_schedule()
	// -----------------------------------------------------------------------

	public function test_maybe_schedule_does_nothing_when_disabled(): void {
		// OPTION_ENABLED is false by default.
		$builder = new NewsletterBuilder();
		$builder->maybe_schedule();

		$this->assertArrayNotHasKey( 'pearblog_newsletter_send', $GLOBALS['_cron_scheduled'] );
	}

	public function test_maybe_schedule_schedules_when_enabled_and_not_yet_scheduled(): void {
		update_option( NewsletterBuilder::OPTION_ENABLED, true );

		$builder = new NewsletterBuilder();
		$builder->maybe_schedule();

		$this->assertArrayHasKey( 'pearblog_newsletter_send', $GLOBALS['_cron_scheduled'] );
	}

	public function test_maybe_schedule_skips_when_already_scheduled(): void {
		update_option( NewsletterBuilder::OPTION_ENABLED, true );
		$GLOBALS['_cron_scheduled']['pearblog_newsletter_send'] = time() + 3600;

		$before  = $GLOBALS['_cron_scheduled']['pearblog_newsletter_send'];
		$builder = new NewsletterBuilder();
		$builder->maybe_schedule();

		// Pre-existing value must not change.
		$this->assertSame( $before, $GLOBALS['_cron_scheduled']['pearblog_newsletter_send'] );
	}

	// -----------------------------------------------------------------------
	// build_html() – structure checks (no posts, so articles section is empty)
	// -----------------------------------------------------------------------

	public function test_build_html_returns_string(): void {
		$builder = new NewsletterBuilder();
		$result  = $builder->build_html( 0 );

		$this->assertIsString( $result );
	}

	public function test_build_html_contains_doctype(): void {
		$builder = new NewsletterBuilder();
		$result  = $builder->build_html( 0 );

		$this->assertStringContainsString( '<!DOCTYPE html>', $result );
	}

	public function test_build_html_contains_site_name(): void {
		// get_bloginfo stub returns 'Test Blog' for any key.
		$builder = new NewsletterBuilder();
		$result  = $builder->build_html( 0 );

		$this->assertStringContainsString( 'Test Blog', $result );
	}

	public function test_build_html_contains_unsubscribe_placeholder(): void {
		$builder = new NewsletterBuilder();
		$result  = $builder->build_html( 0 );

		$this->assertStringContainsString( 'unsubscribe', $result );
	}

	public function test_build_html_contains_weekly_digest(): void {
		$builder = new NewsletterBuilder();
		$result  = $builder->build_html( 0 );

		$this->assertStringContainsString( 'Weekly Digest', $result );
	}

	public function test_build_html_contains_site_url(): void {
		$builder = new NewsletterBuilder();
		$result  = $builder->build_html( 0 );

		// get_site_url stub returns 'https://example.com'.
		$this->assertStringContainsString( 'example.com', $result );
	}

	// -----------------------------------------------------------------------
	// admin_permission()
	// -----------------------------------------------------------------------

	public function test_admin_permission_returns_false_when_no_capability(): void {
		$GLOBALS['_current_user_can'] = false;
		$builder = new NewsletterBuilder();
		$this->assertFalse( $builder->admin_permission() );
	}

	public function test_admin_permission_returns_true_when_manage_options(): void {
		$GLOBALS['_current_user_can'] = true;
		$builder = new NewsletterBuilder();
		$this->assertTrue( $builder->admin_permission() );
	}

	// -----------------------------------------------------------------------
	// rest_preview()
	// -----------------------------------------------------------------------

	public function test_rest_preview_returns_200_response(): void {
		$request  = new \WP_REST_Request( 'GET' );
		$builder  = new NewsletterBuilder();
		$response = $builder->rest_preview( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_rest_preview_returns_html_key(): void {
		$request  = new \WP_REST_Request( 'GET' );
		$builder  = new NewsletterBuilder();
		$response = $builder->rest_preview( $request );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'html', $data );
	}

	public function test_rest_preview_html_is_string(): void {
		$request  = new \WP_REST_Request( 'GET' );
		$builder  = new NewsletterBuilder();
		$response = $builder->rest_preview( $request );

		$data = $response->get_data();
		$this->assertIsString( $data['html'] );
	}
}
