<?php
/**
 * Tests for PoradnikCostTemplateBuilder.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Content\PoradnikCostTemplateBuilder;
use PearBlogEngine\Tenant\SiteProfile;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\Content\PoradnikCostTemplateBuilder
 */
class PoradnikCostTemplateBuilderTest extends TestCase {

	private PoradnikCostTemplateBuilder $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new PoradnikCostTemplateBuilder(
			new SiteProfile( 'home services', 'professional', 'adsense', 5, 'pl' )
		);
	}

	public function test_build_includes_cost_framework_sections_and_slugs(): void {
		$prompt = $this->builder->build(
			'remont łazienki',
			array(
				'city'      => 'Warszawa',
				'year'      => 2026,
				'service'   => 'remont-lazienki',
				'price_per' => 'm²',
			)
		);

		$this->assertStringContainsString( 'Template: ILE KOSZTUJE (Cost Analysis + Lead Generation)', $prompt );
		$this->assertStringContainsString( 'Topic: remont łazienki', $prompt );
		$this->assertStringContainsString( 'Location: Warszawa', $prompt );
		$this->assertStringContainsString( 'H1 FORMAT:', $prompt );
		$this->assertStringContainsString( 'Ile kosztuje remont łazienki w 2026?', $prompt );
		$this->assertStringContainsString( 'H2: "Ile kosztuje remont łazienki w innych miastach?"', $prompt );
		$this->assertStringContainsString( '/ile-kosztuje-remont-lazienki-warszawa', $prompt );
		$this->assertStringContainsString( 'SLUG: ile-kosztuje-remont-lazienki-warszawa-2026', $prompt );
		$this->assertStringContainsString( '[SCHEMA_FAQ]', $prompt );
		$this->assertStringContainsString( '[SCHEMA_HOWTO]', $prompt );
		$this->assertStringContainsString( '[CTA_CALCULATOR]', $prompt );
		$this->assertStringContainsString( '[LEAD_FORM]', $prompt );
	}
}