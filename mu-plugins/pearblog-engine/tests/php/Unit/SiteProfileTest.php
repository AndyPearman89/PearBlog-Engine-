<?php
/**
 * Unit tests for SiteProfile.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Tenant\SiteProfile;

class SiteProfileTest extends TestCase {

	// -----------------------------------------------------------------------
	// Construction
	// -----------------------------------------------------------------------

	public function test_constructor_sets_all_properties(): void {
		$profile = new SiteProfile(
			industry: 'technology',
			tone: 'professional',
			monetization: 'adsense',
			publish_rate: 3,
			language: 'en',
		);

		$this->assertSame( 'technology', $profile->industry );
		$this->assertSame( 'professional', $profile->tone );
		$this->assertSame( 'adsense', $profile->monetization );
		$this->assertSame( 3, $profile->publish_rate );
		$this->assertSame( 'en', $profile->language );
	}

	public function test_properties_are_readonly(): void {
		$profile = new SiteProfile(
			industry: 'health',
			tone: 'conversational',
			monetization: 'affiliate',
			publish_rate: 5,
			language: 'pl',
		);

		$this->expectException( \Error::class );
		$profile->industry = 'finance'; // @phpstan-ignore-line
	}

	// -----------------------------------------------------------------------
	// Summary
	// -----------------------------------------------------------------------

	public function test_summary_contains_industry(): void {
		$profile = new SiteProfile( 'finance', 'formal', 'saas', 1, 'de' );
		$this->assertStringContainsString( 'finance', $profile->summary() );
	}

	public function test_summary_contains_tone(): void {
		$profile = new SiteProfile( 'tech', 'conversational', 'adsense', 2, 'en' );
		$this->assertStringContainsString( 'conversational', $profile->summary() );
	}

	public function test_summary_contains_monetization(): void {
		$profile = new SiteProfile( 'health', 'professional', 'affiliate', 1, 'en' );
		$this->assertStringContainsString( 'affiliate', $profile->summary() );
	}

	public function test_summary_contains_publish_rate(): void {
		$profile = new SiteProfile( 'tech', 'professional', 'saas', 7, 'en' );
		$this->assertStringContainsString( '7', $profile->summary() );
	}

	public function test_summary_contains_language(): void {
		$profile = new SiteProfile( 'legal', 'formal', 'adsense', 1, 'fr' );
		$this->assertStringContainsString( 'fr', $profile->summary() );
	}

	public function test_summary_returns_string(): void {
		$profile = new SiteProfile( 'travel', 'casual', 'affiliate', 2, 'es' );
		$this->assertIsString( $profile->summary() );
		$this->assertNotEmpty( $profile->summary() );
	}

	// -----------------------------------------------------------------------
	// Edge cases
	// -----------------------------------------------------------------------

	public function test_zero_publish_rate_is_valid(): void {
		$profile = new SiteProfile( 'tech', 'professional', 'adsense', 0, 'en' );
		$this->assertSame( 0, $profile->publish_rate );
		$this->assertStringContainsString( '0', $profile->summary() );
	}

	public function test_empty_language_is_stored(): void {
		$profile = new SiteProfile( 'tech', 'professional', 'adsense', 1, '' );
		$this->assertSame( '', $profile->language );
	}

	public function test_long_industry_string_is_stored(): void {
		$long_industry = str_repeat( 'a', 200 );
		$profile       = new SiteProfile( $long_industry, 'professional', 'adsense', 1, 'en' );
		$this->assertSame( $long_industry, $profile->industry );
	}

	public function test_summary_is_idempotent(): void {
		$profile = new SiteProfile( 'tech', 'professional', 'saas', 3, 'en' );
		$this->assertSame( $profile->summary(), $profile->summary() );
	}
}
