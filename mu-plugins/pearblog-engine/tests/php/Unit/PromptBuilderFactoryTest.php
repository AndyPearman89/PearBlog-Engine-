<?php
/**
 * Unit tests for PromptBuilderFactory (industry auto-detection).
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\PromptBuilderFactory;
use PearBlogEngine\Content\PromptBuilder;
use PearBlogEngine\Content\EcommercePromptBuilder;
use PearBlogEngine\Content\TechPromptBuilder;
use PearBlogEngine\Content\HealthPromptBuilder;
use PearBlogEngine\Content\FinancePromptBuilder;
use PearBlogEngine\Content\FoodPromptBuilder;
use PearBlogEngine\Content\TravelPromptBuilder;
use PearBlogEngine\Tenant\SiteProfile;

class PromptBuilderFactoryTest extends TestCase {

	private function profile( string $industry ): SiteProfile {
		return new SiteProfile(
			industry:     $industry,
			tone:         'neutral',
			monetization: 'adsense',
			publish_rate: 1,
			language:     'en'
		);
	}

	public function test_travel_industry_returns_travel_builder(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'travel & tourism' ) );
		$this->assertInstanceOf( TravelPromptBuilder::class, $builder );
	}

	public function test_ecommerce_industry_returns_ecommerce_builder(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'ecommerce product reviews' ) );
		$this->assertInstanceOf( EcommercePromptBuilder::class, $builder );
	}

	public function test_technology_industry_returns_tech_builder(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'technology & software' ) );
		$this->assertInstanceOf( TechPromptBuilder::class, $builder );
	}

	public function test_health_industry_returns_health_builder(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'health and wellness' ) );
		$this->assertInstanceOf( HealthPromptBuilder::class, $builder );
	}

	public function test_finance_industry_returns_finance_builder(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'personal finance & investing' ) );
		$this->assertInstanceOf( FinancePromptBuilder::class, $builder );
	}

	public function test_food_industry_returns_food_builder(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'food and recipes' ) );
		$this->assertInstanceOf( FoodPromptBuilder::class, $builder );
	}

	public function test_unknown_industry_returns_base_prompt_builder(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'unknown niche xyz' ) );
		$this->assertInstanceOf( PromptBuilder::class, $builder );
	}

	public function test_ecommerce_builder_builds_non_empty_prompt(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'ecommerce' ) );
		$prompt  = $builder->build( 'Best laptop under $1000' );
		$this->assertNotEmpty( $prompt );
		$this->assertStringContainsString( 'Best laptop under $1000', $prompt );
	}

	public function test_tech_builder_builds_non_empty_prompt(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'technology saas' ) );
		$prompt  = $builder->build( 'How to use Docker' );
		$this->assertNotEmpty( $prompt );
		$this->assertStringContainsString( 'How to use Docker', $prompt );
	}

	public function test_health_builder_includes_disclaimer_instruction(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'health fitness' ) );
		$prompt  = $builder->build( 'Benefits of vitamin D' );
		$this->assertStringContainsString( 'healthcare professional', $prompt );
	}

	public function test_finance_builder_includes_disclaimer_instruction(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'finance investing' ) );
		$prompt  = $builder->build( 'How to invest in ETFs' );
		$this->assertStringContainsString( 'investment advice', $prompt );
	}

	public function test_food_builder_includes_recipe_instructions(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'food recipe cooking' ) );
		$prompt  = $builder->build( 'Chocolate chip cookies' );
		$this->assertStringContainsString( 'Ingredients', $prompt );
	}

	public function test_case_insensitive_industry_matching(): void {
		$builder = PromptBuilderFactory::create( $this->profile( 'TECHNOLOGY Software' ) );
		$this->assertInstanceOf( TechPromptBuilder::class, $builder );
	}
}
