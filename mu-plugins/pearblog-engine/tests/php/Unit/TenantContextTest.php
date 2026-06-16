<?php
/**
 * Tests for TenantContext.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Tenant\SiteProfile;
use PearBlogEngine\Tenant\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\Tenant\TenantContext
 * @covers \PearBlogEngine\Tenant\SiteProfile
 */
class TenantContextTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$GLOBALS['_is_multisite'] = true;
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options'] = [];
	}

	// -----------------------------------------------------------------------
	// SiteProfile
	// -----------------------------------------------------------------------

	public function test_site_profile_stores_all_fields(): void {
		$profile = new SiteProfile(
			industry:     'tech',
			tone:         'formal',
			monetization: 'adsense',
			publish_rate: 3,
			language:     'en',
		);

		$this->assertSame( 'tech', $profile->industry );
		$this->assertSame( 'formal', $profile->tone );
		$this->assertSame( 'adsense', $profile->monetization );
		$this->assertSame( 3, $profile->publish_rate );
		$this->assertSame( 'en', $profile->language );
	}

	public function test_site_profile_default_values(): void {
		// The profile enforces int for publish_rate.
		$profile = new SiteProfile(
			industry:     'general',
			tone:         'neutral',
			monetization: 'adsense',
			publish_rate: 1,
			language:     'en',
		);
		$this->assertIsInt( $profile->publish_rate );
	}

	// -----------------------------------------------------------------------
	// TenantContext – constructor
	// -----------------------------------------------------------------------

	public function test_tenant_context_stores_site_id_domain_profile(): void {
		$profile = new SiteProfile(
			industry:     'food',
			tone:         'casual',
			monetization: 'affiliate',
			publish_rate: 2,
			language:     'pl',
		);

		$ctx = new TenantContext( 1, 'https://example.com', $profile );

		$this->assertSame( 1, $ctx->site_id );
		$this->assertSame( 'https://example.com', $ctx->domain );
		$this->assertSame( $profile, $ctx->profile );
	}

	public function test_site_id_is_readonly(): void {
		$profile = new SiteProfile(
			industry:     'general',
			tone:         'neutral',
			monetization: 'adsense',
			publish_rate: 1,
			language:     'en',
		);
		$ctx = new TenantContext( 5, 'https://site5.com', $profile );

		$this->assertSame( 5, $ctx->site_id );

		// Readonly – verify via reflection that property is readonly.
		$ref = new \ReflectionProperty( TenantContext::class, 'site_id' );
		$this->assertTrue( $ref->isReadOnly() );
	}

	// -----------------------------------------------------------------------
	// TenantContext::for_site
	// -----------------------------------------------------------------------

	public function test_for_site_builds_context_from_options(): void {
		// Bootstrap defines get_blog_option as get_option("1_{key}"), so we use prefixed keys.
		$GLOBALS['_options']['1_pearblog_industry']     = 'travel';
		$GLOBALS['_options']['1_pearblog_tone']         = 'informal';
		$GLOBALS['_options']['1_pearblog_monetization'] = 'affiliate';
		$GLOBALS['_options']['1_pearblog_publish_rate'] = '4';
		$GLOBALS['_options']['1_pearblog_language']     = 'de';

		$ctx = TenantContext::for_site( 1 );

		$this->assertInstanceOf( TenantContext::class, $ctx );
		$this->assertSame( 'travel', $ctx->profile->industry );
		$this->assertSame( 'informal', $ctx->profile->tone );
		$this->assertSame( 'affiliate', $ctx->profile->monetization );
		$this->assertSame( 4, $ctx->profile->publish_rate );
		$this->assertSame( 'de', $ctx->profile->language );
	}

	public function test_for_site_uses_default_values_when_options_missing(): void {
		$ctx = TenantContext::for_site( 1 );

		$this->assertSame( 'general', $ctx->profile->industry );
		$this->assertSame( 'neutral', $ctx->profile->tone );
		$this->assertSame( 'adsense', $ctx->profile->monetization );
		$this->assertSame( 1, $ctx->profile->publish_rate );
		$this->assertSame( 'en', $ctx->profile->language );
	}

	public function test_for_site_returns_tenant_context_instance(): void {
		$ctx = TenantContext::for_site( 1 );
		$this->assertInstanceOf( TenantContext::class, $ctx );
	}

	public function test_for_site_site_id_is_set(): void {
		$ctx = TenantContext::for_site( 1 );
		$this->assertSame( 1, $ctx->site_id );
	}

	public function test_for_site_domain_is_string(): void {
		$ctx = TenantContext::for_site( 1 );
		$this->assertIsString( $ctx->domain );
	}

	// -----------------------------------------------------------------------
	// SiteProfile – publish_rate coercion
	// -----------------------------------------------------------------------

	public function test_for_site_coerces_publish_rate_to_int(): void {
		$GLOBALS['_options']['1_pearblog_publish_rate'] = '7';
		$ctx = TenantContext::for_site( 1 );
		$this->assertIsInt( $ctx->profile->publish_rate );
		$this->assertSame( 7, $ctx->profile->publish_rate );
	}

	public function test_for_site_publish_rate_defaults_to_one(): void {
		// No option set → default 1.
		$ctx = TenantContext::for_site( 1 );
		$this->assertSame( 1, $ctx->profile->publish_rate );
	}
}
