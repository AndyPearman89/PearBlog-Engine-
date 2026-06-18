<?php
/**
 * Unit tests for AIVariantGenerator (V9.0 F3).
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Testing\AIVariantGenerator;

class AIVariantGeneratorTest extends TestCase {

	// -----------------------------------------------------------------------
	// build_prompt
	// -----------------------------------------------------------------------

	public function test_build_prompt_contains_original_text(): void {
		$gen    = new AIVariantGenerator();
		$prompt = $gen->build_prompt( 'post_title', 'Best Laptops 2026', 3 );
		$this->assertStringContainsString( 'Best Laptops 2026', $prompt );
	}

	public function test_build_prompt_requests_correct_count(): void {
		$gen    = new AIVariantGenerator();
		$prompt = $gen->build_prompt( 'post_title', 'My Title', 5 );
		$this->assertStringContainsString( '5', $prompt );
	}

	public function test_build_prompt_uses_article_headline_for_post_title(): void {
		$gen    = new AIVariantGenerator();
		$prompt = $gen->build_prompt( 'post_title', 'Test', 1 );
		$this->assertStringContainsString( 'headline', $prompt );
	}

	public function test_build_prompt_uses_cta_label(): void {
		$gen    = new AIVariantGenerator();
		$prompt = $gen->build_prompt( 'cta_text', 'Sign Up Now', 2 );
		$this->assertStringContainsString( 'call-to-action', $prompt );
	}

	public function test_build_prompt_uses_meta_description_label(): void {
		$gen    = new AIVariantGenerator();
		$prompt = $gen->build_prompt( 'meta_description', 'About us page.', 2 );
		$this->assertStringContainsString( 'meta description', $prompt );
	}

	public function test_build_prompt_handles_unknown_field_type(): void {
		$gen    = new AIVariantGenerator();
		$prompt = $gen->build_prompt( 'unknown_field', 'Value', 1 );
		$this->assertStringContainsString( 'Value', $prompt );
	}

	// -----------------------------------------------------------------------
	// parse_variants
	// -----------------------------------------------------------------------

	public function test_parse_variants_strips_numbered_prefixes(): void {
		$gen  = new AIVariantGenerator();
		$raw  = "1. First variant\n2. Second variant\n3. Third variant";
		$vars = $gen->parse_variants( $raw, 3 );
		$this->assertCount( 3, $vars );
		$this->assertSame( 'First variant', $vars[0] );
		$this->assertSame( 'Second variant', $vars[1] );
		$this->assertSame( 'Third variant', $vars[2] );
	}

	public function test_parse_variants_strips_bullet_prefixes(): void {
		$gen  = new AIVariantGenerator();
		$raw  = "- Option A\n- Option B";
		$vars = $gen->parse_variants( $raw, 2 );
		$this->assertCount( 2, $vars );
		$this->assertSame( 'Option A', $vars[0] );
	}

	public function test_parse_variants_skips_empty_lines(): void {
		$gen  = new AIVariantGenerator();
		$raw  = "1. First\n\n\n2. Second";
		$vars = $gen->parse_variants( $raw, 2 );
		$this->assertCount( 2, $vars );
	}

	public function test_parse_variants_trims_to_count(): void {
		$gen  = new AIVariantGenerator();
		$raw  = "1. A\n2. B\n3. C\n4. D\n5. E";
		$vars = $gen->parse_variants( $raw, 3 );
		$this->assertCount( 3, $vars );
	}

	public function test_parse_variants_returns_empty_for_empty_raw(): void {
		$gen  = new AIVariantGenerator();
		$vars = $gen->parse_variants( '', 3 );
		$this->assertSame( [], $vars );
	}

	// -----------------------------------------------------------------------
	// generate (using injected caller)
	// -----------------------------------------------------------------------

	public function test_generate_calls_injected_ai_caller(): void {
		$called = false;
		$gen    = new AIVariantGenerator( static function ( string $model, string $prompt ) use ( &$called ): string {
			$called = true;
			return "1. Var A\n2. Var B\n3. Var C";
		} );
		$vars = $gen->generate( 'post_title', 'Test Title', 3 );
		$this->assertTrue( $called );
		$this->assertCount( 3, $vars );
	}

	public function test_generate_clamps_count_to_max(): void {
		$gen  = new AIVariantGenerator( static function ( string $m, string $p ): string {
			return implode( "\n", array_map( static fn( int $i ) => "{$i}. Var {$i}", range( 1, 10 ) ) );
		} );
		$vars = $gen->generate( 'post_title', 'Title', AIVariantGenerator::MAX_VARIANTS + 5 );
		$this->assertCount( AIVariantGenerator::MAX_VARIANTS, $vars );
	}

	public function test_generate_returns_empty_when_ai_returns_empty(): void {
		$gen  = new AIVariantGenerator( static fn() => '' );
		$vars = $gen->generate( 'post_title', 'Any Title', 3 );
		$this->assertSame( [], $vars );
	}

	public function test_generate_minimum_count_is_one(): void {
		$gen  = new AIVariantGenerator( static fn() => '1. Only one' );
		$vars = $gen->generate( 'post_title', 'Title', -5 );
		$this->assertCount( 1, $vars );
	}

	public function test_generate_passes_model_option_to_caller(): void {
		$GLOBALS['_options'][ AIVariantGenerator::OPTION_MODEL ] = 'gpt-4o';
		$capturedModel = '';
		$gen = new AIVariantGenerator( static function ( string $model, string $p ) use ( &$capturedModel ): string {
			$capturedModel = $model;
			return '1. Var';
		} );
		$gen->generate( 'post_title', 'Title', 1 );
		$this->assertSame( 'gpt-4o', $capturedModel );
	}
}
