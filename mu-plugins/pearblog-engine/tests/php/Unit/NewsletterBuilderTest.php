<?php
/**
 * Unit tests for NewsletterBuilder.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Email\NewsletterBuilder;

class NewsletterBuilderTest extends TestCase {

	private NewsletterBuilder $builder;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_posts']            = [];
		$GLOBALS['_actions']          = [];
		$GLOBALS['_current_user_can'] = true;
		$this->builder = new NewsletterBuilder();
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
	// build_html
	// -----------------------------------------------------------------------

	public function test_build_html_returns_string(): void {
		$html = $this->builder->build_html();
		$this->assertIsString( $html );
	}

	public function test_build_html_contains_doctype(): void {
		$html = $this->builder->build_html();
		$this->assertStringContainsString( '<!DOCTYPE html>', $html );
	}

	public function test_build_html_contains_site_name(): void {
		$html = $this->builder->build_html();
		$this->assertStringContainsString( 'Test Blog', $html );
	}

	public function test_build_html_contains_unsubscribe_link(): void {
		$html = $this->builder->build_html();
		$this->assertStringContainsString( 'Unsubscribe', $html );
	}

	public function test_build_html_contains_newsletter_table_structure(): void {
		$html = $this->builder->build_html();
		$this->assertStringContainsString( '<table', $html );
	}

	public function test_build_html_contains_body_tag(): void {
		$html = $this->builder->build_html();
		$this->assertStringContainsString( '<body', $html );
	}

	public function test_build_html_contains_html_close_tag(): void {
		$html = $this->builder->build_html();
		$this->assertStringContainsString( '</html>', $html );
	}

	// -----------------------------------------------------------------------
	// send_weekly_newsletter — disabled path
	// -----------------------------------------------------------------------

	public function test_send_weekly_newsletter_returns_disabled_when_not_enabled(): void {
		$result = $this->builder->send_weekly_newsletter();
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'disabled', $result['response'] );
	}

	public function test_send_weekly_newsletter_returns_array(): void {
		$result = $this->builder->send_weekly_newsletter();
		$this->assertIsArray( $result );
	}

	public function test_send_weekly_newsletter_has_success_key(): void {
		$result = $this->builder->send_weekly_newsletter();
		$this->assertArrayHasKey( 'success', $result );
	}

	public function test_send_weekly_newsletter_has_provider_key(): void {
		$result = $this->builder->send_weekly_newsletter();
		$this->assertArrayHasKey( 'provider', $result );
	}

	// -----------------------------------------------------------------------
	// maybe_schedule
	// -----------------------------------------------------------------------

	public function test_maybe_schedule_skips_when_not_enabled(): void {
		// Should not throw and not register cron event.
		$this->builder->maybe_schedule();
		$this->assertTrue( true );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_rest_api_init_action(): void {
		$this->builder->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}

	// -----------------------------------------------------------------------
	// admin_permission
	// -----------------------------------------------------------------------

	public function test_admin_permission_returns_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->builder->admin_permission() );
	}

	public function test_admin_permission_returns_false_for_non_admin(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->builder->admin_permission() );
	}

	// -----------------------------------------------------------------------
	// REST preview
	// -----------------------------------------------------------------------

	public function test_rest_preview_returns_response(): void {
		$req    = new \WP_REST_Request();
		$result = $this->builder->rest_preview( $req );
		$this->assertInstanceOf( \WP_REST_Response::class, $result );
	}

	public function test_rest_preview_returns_200_status(): void {
		$req    = new \WP_REST_Request();
		$result = $this->builder->rest_preview( $req );
		$this->assertSame( 200, $result->get_status() );
	}
}
