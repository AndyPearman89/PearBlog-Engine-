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
	// Constructor & readonly properties
	// -----------------------------------------------------------------------

	public function test_constructor_sets_industry(): void {
		$profile = new SiteProfile( 'technology', 'professional', 'adsense', 3, 'en' );

		$this->assertSame( 'technology', $profile->industry );
	}

	public function test_constructor_sets_tone(): void {
		$profile = new SiteProfile( 'health', 'conversational', 'affiliate', 1, 'pl' );

		$this->assertSame( 'conversational', $profile->tone );
	}

	public function test_constructor_sets_monetization(): void {
		$profile = new SiteProfile( 'finance', 'professional', 'saas', 5, 'en' );

		$this->assertSame( 'saas', $profile->monetization );
	}

	public function test_constructor_sets_publish_rate(): void {
		$profile = new SiteProfile( 'travel', 'casual', 'adsense', 7, 'de' );

		$this->assertSame( 7, $profile->publish_rate );
	}

	public function test_constructor_sets_language(): void {
		$profile = new SiteProfile( 'tech', 'formal', 'affiliate', 2, 'pl' );

		$this->assertSame( 'pl', $profile->language );
	}

	// -----------------------------------------------------------------------
	// Properties are readonly (immutable value object)
	// -----------------------------------------------------------------------

	public function test_properties_are_readonly(): void {
		$profile = new SiteProfile( 'tech', 'professional', 'adsense', 3, 'en' );
		$reflection = new \ReflectionClass( $profile );

		foreach ( [ 'industry', 'tone', 'monetization', 'publish_rate', 'language' ] as $prop ) {
			$this->assertTrue(
				$reflection->getProperty( $prop )->isReadOnly(),
				"Property {$prop} should be readonly"
			);
		}
	}

	// -----------------------------------------------------------------------
	// summary()
	// -----------------------------------------------------------------------

	public function test_summary_contains_industry(): void {
		$profile = new SiteProfile( 'technology', 'professional', 'adsense', 3, 'en' );

		$this->assertStringContainsString( 'technology', $profile->summary() );
	}

	public function test_summary_contains_tone(): void {
		$profile = new SiteProfile( 'health', 'conversational', 'affiliate', 1, 'pl' );

		$this->assertStringContainsString( 'conversational', $profile->summary() );
	}

	public function test_summary_contains_monetization(): void {
		$profile = new SiteProfile( 'finance', 'professional', 'saas', 5, 'en' );

		$this->assertStringContainsString( 'saas', $profile->summary() );
	}

	public function test_summary_contains_publish_rate(): void {
		$profile = new SiteProfile( 'travel', 'casual', 'adsense', 7, 'de' );

		$this->assertStringContainsString( '7', $profile->summary() );
	}

	public function test_summary_contains_language(): void {
		$profile = new SiteProfile( 'food', 'friendly', 'affiliate', 2, 'pl' );

		$this->assertStringContainsString( 'pl', $profile->summary() );
	}

	public function test_summary_returns_string(): void {
		$profile = new SiteProfile( 'tech', 'professional', 'adsense', 3, 'en' );

		$this->assertIsString( $profile->summary() );
	}

	public function test_summary_is_non_empty(): void {
		$profile = new SiteProfile( 'tech', 'professional', 'adsense', 3, 'en' );

		$this->assertNotEmpty( $profile->summary() );
	}

	// -----------------------------------------------------------------------
	// Distinct instances are independent
	// -----------------------------------------------------------------------

	public function test_two_profiles_are_independent(): void {
		$a = new SiteProfile( 'tech', 'professional', 'adsense', 3, 'en' );
		$b = new SiteProfile( 'health', 'casual', 'affiliate', 1, 'pl' );

		$this->assertNotSame( $a->industry, $b->industry );
		$this->assertNotSame( $a->language, $b->language );
	}
}
