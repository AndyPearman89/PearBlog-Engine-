<?php
/**
 * Unit tests for TenantOnboardingController.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Tenant\TenantOnboardingController;

class TenantOnboardingControllerTest extends TestCase {

	private TenantOnboardingController $controller;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']           = [];
		$GLOBALS['_is_multisite']      = false;
		$GLOBALS['_current_blog_id']   = 1;
		$GLOBALS['_current_user_can']  = true;
		$GLOBALS['_wpmu_created_sites'] = [];
		unset( $GLOBALS['_wpmu_create_blog_result'] );
		$this->controller = new TenantOnboardingController();
	}

	// -----------------------------------------------------------------------
	// Single-site provisioning
	// -----------------------------------------------------------------------

	public function test_provision_returns_site_data_on_single_site(): void {
		$result = $this->controller->provision( [
			'domain'      => 'example.com',
			'title'       => 'Example Site',
			'industry'    => 'technology',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'starter',
			'admin_email' => 'admin@example.com',
		] );

		$this->assertIsArray( $result );
		$this->assertSame( 'example.com', $result['domain'] );
		$this->assertSame( 'Example Site', $result['title'] );
		$this->assertSame( 'starter', $result['plan'] );
		$this->assertSame( 'technology', $result['industry'] );
		$this->assertSame( 'en', $result['language'] );
	}

	public function test_provision_sets_correct_site_id_on_single_site(): void {
		$result = $this->controller->provision( [
			'domain'      => 'test.com',
			'title'       => 'Test',
			'industry'    => 'health',
			'tone'        => 'casual',
			'language'    => 'pl',
			'plan'        => 'pro',
			'admin_email' => 'a@b.com',
		] );

		$this->assertSame( 1, $result['site_id'] );
	}

	public function test_provision_uses_domain_as_title_when_title_empty(): void {
		$result = $this->controller->provision( [
			'domain'      => 'myblog.com',
			'title'       => '',
			'industry'    => 'tech',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'starter',
			'admin_email' => 'a@b.com',
		] );

		$this->assertSame( 'myblog.com', $result['title'] );
	}

	public function test_provision_stores_tenant_in_registry(): void {
		$this->controller->provision( [
			'domain'      => 'registry-test.com',
			'title'       => 'Registry Test',
			'industry'    => 'finance',
			'tone'        => 'formal',
			'language'    => 'de',
			'plan'        => 'enterprise',
			'admin_email' => 'a@b.com',
		] );

		$tenants = $this->controller->list_tenants();
		$found   = array_filter( $tenants, static fn( $t ) => $t['domain'] === 'registry-test.com' );
		$this->assertCount( 1, $found );
	}

	public function test_provision_applies_pearblog_options(): void {
		$this->controller->provision( [
			'domain'      => 'options-test.com',
			'title'       => 'Options Test',
			'industry'    => 'travel',
			'tone'        => 'casual',
			'language'    => 'es',
			'plan'        => 'pro',
			'admin_email' => 'a@b.com',
		] );

		$this->assertSame( 'travel', get_option( 'pearblog_industry' ) );
		$this->assertSame( 'casual', get_option( 'pearblog_tone' ) );
		$this->assertSame( 'es', get_option( 'pearblog_language' ) );
	}

	public function test_pro_plan_sets_publish_rate_3(): void {
		$this->controller->provision( [
			'domain'      => 'pro-plan.com',
			'title'       => 'Pro',
			'industry'    => 'tech',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'pro',
			'admin_email' => 'a@b.com',
		] );

		$this->assertSame( 3, get_option( 'pearblog_publish_rate' ) );
	}

	public function test_enterprise_plan_sets_publish_rate_10(): void {
		$this->controller->provision( [
			'domain'      => 'enterprise-plan.com',
			'title'       => 'Enterprise',
			'industry'    => 'finance',
			'tone'        => 'formal',
			'language'    => 'en',
			'plan'        => 'enterprise',
			'admin_email' => 'a@b.com',
		] );

		$this->assertSame( 10, get_option( 'pearblog_publish_rate' ) );
	}

	public function test_starter_plan_sets_publish_rate_1(): void {
		$this->controller->provision( [
			'domain'      => 'starter-plan.com',
			'title'       => 'Starter',
			'industry'    => 'tech',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'starter',
			'admin_email' => 'a@b.com',
		] );

		$this->assertSame( 1, get_option( 'pearblog_publish_rate' ) );
	}

	public function test_provision_returns_admin_url(): void {
		$result = $this->controller->provision( [
			'domain'      => 'url-test.com',
			'title'       => 'URL Test',
			'industry'    => 'tech',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'starter',
			'admin_email' => 'a@b.com',
		] );

		$this->assertArrayHasKey( 'admin_url', $result );
		$this->assertNotEmpty( $result['admin_url'] );
	}

	// -----------------------------------------------------------------------
	// Tenant list
	// -----------------------------------------------------------------------

	public function test_list_tenants_returns_empty_initially(): void {
		$this->assertSame( [], $this->controller->list_tenants() );
	}

	public function test_list_tenants_returns_all_provisioned(): void {
		$this->controller->provision( [
			'domain' => 'site-a.com', 'title' => 'A', 'industry' => 'tech',
			'tone' => 'professional', 'language' => 'en', 'plan' => 'starter', 'admin_email' => 'a@b.com',
		] );
		$this->controller->provision( [
			'domain' => 'site-b.com', 'title' => 'B', 'industry' => 'health',
			'tone' => 'casual', 'language' => 'pl', 'plan' => 'pro', 'admin_email' => 'b@b.com',
		] );

		$tenants = $this->controller->list_tenants();
		$this->assertCount( 2, $tenants );

		$domains = array_column( $tenants, 'domain' );
		$this->assertContains( 'site-a.com', $domains );
		$this->assertContains( 'site-b.com', $domains );
	}

	public function test_provisioning_same_domain_twice_updates_registry(): void {
		$this->controller->provision( [
			'domain' => 'dup.com', 'title' => 'First', 'industry' => 'tech',
			'tone' => 'professional', 'language' => 'en', 'plan' => 'starter', 'admin_email' => 'a@b.com',
		] );
		$this->controller->provision( [
			'domain' => 'dup.com', 'title' => 'Second', 'industry' => 'finance',
			'tone' => 'formal', 'language' => 'de', 'plan' => 'enterprise', 'admin_email' => 'b@b.com',
		] );

		$tenants = $this->controller->list_tenants();
		$dup     = array_filter( $tenants, static fn( $t ) => $t['domain'] === 'dup.com' );
		// Last write wins; only one entry.
		$this->assertCount( 1, $dup );
	}

	// -----------------------------------------------------------------------
	// Multisite provisioning
	// -----------------------------------------------------------------------

	public function test_provision_on_multisite_creates_subsite(): void {
		$GLOBALS['_is_multisite']        = true;
		$GLOBALS['_wpmu_next_blog_id']   = 5;
		$GLOBALS['_wp_network']          = (object) [ 'domain' => 'network.example.com' ];

		$result = $this->controller->provision( [
			'domain'      => 'sub.network.example.com',
			'title'       => 'Sub Site',
			'industry'    => 'tech',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'pro',
			'admin_email' => 'a@b.com',
		] );

		$this->assertSame( 5, $result['site_id'] );
		$this->assertCount( 1, $GLOBALS['_wpmu_created_sites'] );
	}

	public function test_provision_on_multisite_returns_wp_error_on_failure(): void {
		$GLOBALS['_is_multisite']              = true;
		$GLOBALS['_wpmu_create_blog_result']   = new \WP_Error( 'create_error', 'Creation failed' );
		$GLOBALS['_wp_network']                = (object) [ 'domain' => 'network.example.com' ];

		$result = $this->controller->provision( [
			'domain'      => 'bad.example.com',
			'title'       => 'Bad',
			'industry'    => 'tech',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'starter',
			'admin_email' => 'a@b.com',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}
}
