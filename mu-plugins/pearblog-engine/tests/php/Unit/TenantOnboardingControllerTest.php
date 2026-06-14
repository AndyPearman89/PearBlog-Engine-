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

	private TenantOnboardingController $ctrl;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_actions']    = [];
		$GLOBALS['_is_multisite'] = false;
		$this->ctrl = new TenantOnboardingController();
	}

	// -----------------------------------------------------------------------
	// provision — single-site mode
	// -----------------------------------------------------------------------

	public function test_provision_returns_array_on_success(): void {
		$result = $this->ctrl->provision( [
			'domain'      => 'example.com',
			'title'       => 'Example Site',
			'industry'    => 'technology',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'starter',
			'admin_email' => 'admin@example.com',
		] );

		$this->assertIsArray( $result );
	}

	public function test_provision_returns_domain_in_result(): void {
		$result = $this->ctrl->provision( [
			'domain'      => 'myblog.io',
			'title'       => 'My Blog',
			'industry'    => 'general',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'pro',
			'admin_email' => 'admin@myblog.io',
		] );

		$this->assertSame( 'myblog.io', $result['domain'] );
	}

	public function test_provision_returns_title_in_result(): void {
		$result = $this->ctrl->provision( [
			'domain'      => 'site.com',
			'title'       => 'Awesome Site',
			'industry'    => 'health',
			'tone'        => 'conversational',
			'language'    => 'pl',
			'plan'        => 'enterprise',
			'admin_email' => 'admin@site.com',
		] );

		$this->assertSame( 'Awesome Site', $result['title'] );
	}

	public function test_provision_uses_domain_as_title_when_empty(): void {
		$result = $this->ctrl->provision( [
			'domain'      => 'domain-as-title.com',
			'title'       => '',
			'industry'    => 'general',
			'tone'        => 'professional',
			'language'    => 'en',
			'plan'        => 'starter',
			'admin_email' => 'a@b.com',
		] );

		$this->assertSame( 'domain-as-title.com', $result['title'] );
	}

	public function test_provision_returns_plan_in_result(): void {
		$result = $this->ctrl->provision( [
			'domain'   => 'prosite.com',
			'industry' => 'general',
			'plan'     => 'pro',
		] );

		$this->assertSame( 'pro', $result['plan'] );
	}

	public function test_provision_returns_industry_in_result(): void {
		$result = $this->ctrl->provision( [
			'domain'   => 'techsite.com',
			'industry' => 'technology',
			'plan'     => 'starter',
		] );

		$this->assertSame( 'technology', $result['industry'] );
	}

	public function test_provision_returns_language_in_result(): void {
		$result = $this->ctrl->provision( [
			'domain'   => 'plsite.com',
			'industry' => 'health',
			'language' => 'pl',
			'plan'     => 'starter',
		] );

		$this->assertSame( 'pl', $result['language'] );
	}

	public function test_provision_returns_site_id(): void {
		$result = $this->ctrl->provision( [
			'domain'   => 'mysite.com',
			'industry' => 'general',
			'plan'     => 'starter',
		] );

		$this->assertArrayHasKey( 'site_id', $result );
		$this->assertIsInt( $result['site_id'] );
	}

	public function test_provision_returns_admin_url(): void {
		$result = $this->ctrl->provision( [
			'domain'   => 'mysite.com',
			'industry' => 'general',
			'plan'     => 'starter',
		] );

		$this->assertArrayHasKey( 'admin_url', $result );
		$this->assertIsString( $result['admin_url'] );
	}

	// -----------------------------------------------------------------------
	// provision — stores in tenant registry
	// -----------------------------------------------------------------------

	public function test_provision_stores_tenant_in_registry(): void {
		$this->ctrl->provision( [
			'domain'   => 'registry-test.com',
			'industry' => 'tech',
			'plan'     => 'pro',
		] );

		$registry = get_option( 'pearblog_tenant_registry', [] );
		$this->assertNotEmpty( $registry );
		$this->assertCount( 1, $registry );
	}

	public function test_provision_stores_provisioned_timestamp(): void {
		$before = time();
		$this->ctrl->provision( [
			'domain'   => 'timestamp-test.com',
			'industry' => 'general',
			'plan'     => 'starter',
		] );
		$after = time();

		$registry = get_option( 'pearblog_tenant_registry', [] );
		$entry    = reset( $registry ); // Get first (and only) entry regardless of key.

		$this->assertNotEmpty( $entry );
		$this->assertGreaterThanOrEqual( $before, $entry['provisioned'] );
		$this->assertLessThanOrEqual( $after, $entry['provisioned'] );
	}

	// -----------------------------------------------------------------------
	// provision — applies plan-based publish rates
	// -----------------------------------------------------------------------

	public function test_provision_applies_starter_publish_rate(): void {
		$this->ctrl->provision( [
			'domain'   => 'starter.com',
			'industry' => 'general',
			'plan'     => 'starter',
		] );

		$this->assertSame( 1, get_option( 'pearblog_publish_rate' ) );
	}

	public function test_provision_applies_pro_publish_rate(): void {
		$this->ctrl->provision( [
			'domain'   => 'pro.com',
			'industry' => 'general',
			'plan'     => 'pro',
		] );

		$this->assertSame( 3, get_option( 'pearblog_publish_rate' ) );
	}

	public function test_provision_applies_enterprise_publish_rate(): void {
		$this->ctrl->provision( [
			'domain'   => 'enterprise.com',
			'industry' => 'general',
			'plan'     => 'enterprise',
		] );

		$this->assertSame( 10, get_option( 'pearblog_publish_rate' ) );
	}

	// -----------------------------------------------------------------------
	// provision — default options applied
	// -----------------------------------------------------------------------

	public function test_provision_enables_circuit_breaker(): void {
		$this->ctrl->provision( [
			'domain'   => 'cb.com',
			'industry' => 'tech',
			'plan'     => 'starter',
		] );

		$this->assertTrue( (bool) get_option( 'pearblog_circuit_breaker_enabled' ) );
	}

	public function test_provision_sets_homepage_version(): void {
		$this->ctrl->provision( [
			'domain'   => 'hv.com',
			'industry' => 'general',
			'plan'     => 'starter',
		] );

		$this->assertSame( 'v7', get_option( 'pearblog_homepage_version' ) );
	}

	// -----------------------------------------------------------------------
	// list_tenants
	// -----------------------------------------------------------------------

	public function test_list_tenants_returns_empty_array_initially(): void {
		$this->assertSame( [], $this->ctrl->list_tenants() );
	}

	public function test_list_tenants_returns_array(): void {
		$this->ctrl->provision( [ 'domain' => 'a.com', 'industry' => 'tech', 'plan' => 'starter' ] );

		$this->assertIsArray( $this->ctrl->list_tenants() );
	}

	public function test_list_tenants_returns_one_entry_after_one_provision(): void {
		$this->ctrl->provision( [ 'domain' => 'one.com', 'industry' => 'tech', 'plan' => 'starter' ] );

		$this->assertCount( 1, $this->ctrl->list_tenants() );
	}

	public function test_list_tenants_accumulates_multiple_provisions(): void {
		$this->ctrl->provision( [ 'domain' => 'first.com', 'industry' => 'tech', 'plan' => 'starter' ] );
		$this->ctrl->provision( [ 'domain' => 'second.com', 'industry' => 'health', 'plan' => 'pro' ] );

		$this->assertCount( 2, $this->ctrl->list_tenants() );
	}

	public function test_list_tenants_contains_domain(): void {
		$this->ctrl->provision( [ 'domain' => 'listed.com', 'industry' => 'tech', 'plan' => 'starter' ] );

		$domains = array_column( $this->ctrl->list_tenants(), 'domain' );
		$this->assertContains( 'listed.com', $domains );
	}

	public function test_list_tenants_returns_sequential_array(): void {
		$this->ctrl->provision( [ 'domain' => 'sequential.com', 'industry' => 'tech', 'plan' => 'pro' ] );

		$list = $this->ctrl->list_tenants();
		$this->assertSame( [ 0 ], array_keys( $list ) );
	}
}
