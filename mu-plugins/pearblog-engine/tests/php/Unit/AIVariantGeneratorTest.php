<?php
/**
 * Unit tests for AIVariantGenerator.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Testing\AIVariantGenerator;

class AIVariantGeneratorTest extends TestCase {

	// -----------------------------------------------------------------------
	// build_meta_prompt()
	// -----------------------------------------------------------------------

	public function test_build_meta_prompt_contains_topic(): void {
		$gen    = new AIVariantGenerator();
		$prompt = $gen->build_meta_prompt( 'Best hiking gear', 2 );

		$this->assertStringContainsString( 'Best hiking gear', $prompt );
	}

	public function test_build_meta_prompt_contains_count(): void {
		$gen    = new AIVariantGenerator();
		$prompt = $gen->build_meta_prompt( 'Travel tips', 3 );

		$this->assertStringContainsString( '3', $prompt );
	}

	// -----------------------------------------------------------------------
	// parse_variants()
	// -----------------------------------------------------------------------

	public function test_parse_variants_extracts_numbered_lines(): void {
		$gen  = new AIVariantGenerator();
		$raw  = "1. Focus on beginners.\n2. Focus on experts.\n3. Use bullet lists.";
		$vars = $gen->parse_variants( $raw, 3 );

		$this->assertCount( 3, $vars );
		$this->assertSame( 'Focus on beginners.', $vars['modifier_0'] );
		$this->assertSame( 'Focus on experts.', $vars['modifier_1'] );
		$this->assertSame( 'Use bullet lists.', $vars['modifier_2'] );
	}

	public function test_parse_variants_handles_parenthesis_numbering(): void {
		$gen  = new AIVariantGenerator();
		$raw  = "1) Write casually.\n2) Be formal.";
		$vars = $gen->parse_variants( $raw, 2 );

		$this->assertCount( 2, $vars );
		$this->assertSame( 'Write casually.', $vars['modifier_0'] );
	}

	public function test_parse_variants_pads_missing_lines_with_fallback(): void {
		$gen  = new AIVariantGenerator();
		$raw  = '1. Only one line.';
		$vars = $gen->parse_variants( $raw, 3 );

		$this->assertCount( 3, $vars );
		$this->assertStringContainsString( 'comprehensive', $vars['modifier_1'] );
	}

	public function test_parse_variants_returns_empty_on_blank_input(): void {
		$gen  = new AIVariantGenerator();
		$vars = $gen->parse_variants( '', 2 );

		$this->assertCount( 2, $vars );
		// Both should be fallbacks.
		foreach ( $vars as $v ) {
			$this->assertStringContainsString( 'comprehensive', $v );
		}
	}

	public function test_parse_variants_ignores_blank_lines(): void {
		$gen  = new AIVariantGenerator();
		$raw  = "\n\n1. First variant.\n\n2. Second variant.\n";
		$vars = $gen->parse_variants( $raw, 2 );

		$this->assertCount( 2, $vars );
		$this->assertSame( 'First variant.', $vars['modifier_0'] );
	}

	// -----------------------------------------------------------------------
	// generate_variants() — via stub subclass to avoid real AI calls
	// -----------------------------------------------------------------------

	public function test_generate_variants_returns_correct_count(): void {
		$gen  = $this->make_stubbed_generator( "1. Variant A.\n2. Variant B." );
		$vars = $gen->generate_variants( 'Topic', 2 );

		$this->assertCount( 2, $vars );
	}

	public function test_generate_variants_clamps_to_max(): void {
		$raw = implode( "\n", array_map( fn( $i ) => "{$i}. Modifier {$i}.", range( 1, 10 ) ) );
		$gen  = $this->make_stubbed_generator( $raw );
		$vars = $gen->generate_variants( 'Topic', 99 );

		$this->assertCount( AIVariantGenerator::MAX_VARIANTS, $vars );
	}

	public function test_generate_variants_clamps_minimum_to_one(): void {
		$gen  = $this->make_stubbed_generator( '1. Only one.' );
		$vars = $gen->generate_variants( 'Topic', 0 );

		$this->assertCount( 1, $vars );
	}

	public function test_generate_headline_variant_returns_string(): void {
		$gen      = $this->make_stubbed_generator( 'A Better Headline' );
		$headline = $gen->generate_headline_variant( 'Old Headline' );

		$this->assertIsString( $headline );
		$this->assertNotEmpty( $headline );
	}

	public function test_generate_headline_variant_falls_back_on_empty_ai_response(): void {
		$gen      = $this->make_stubbed_generator( '' );
		$headline = $gen->generate_headline_variant( 'Original' );

		$this->assertSame( 'Original', $headline );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function make_stubbed_generator( string $ai_response ): AIVariantGenerator {
		return new class( $ai_response ) extends AIVariantGenerator {
			public function __construct( private readonly string $response ) {
				// skip parent constructor – no AIClient needed.
			}

			protected function call_ai( string $prompt ): string {
				return $this->response;
			}
		};
	}
}
