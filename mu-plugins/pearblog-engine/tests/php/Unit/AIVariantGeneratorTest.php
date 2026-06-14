<?php
/**
 * Unit tests for AIVariantGenerator.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\AIProviderInterface;
use PearBlogEngine\Testing\AIVariantGenerator;

class AIVariantGeneratorTest extends TestCase {

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Build a stub AIProviderInterface that returns the given response text.
	 */
	private function make_provider( string $response ): AIProviderInterface {
		$stub = $this->createMock( AIProviderInterface::class );
		$stub->method( 'complete' )->willReturn( [
			'content'           => $response,
			'prompt_tokens'     => 50,
			'completion_tokens' => 80,
		] );
		return $stub;
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_transients'] = [];
	}

	// -----------------------------------------------------------------------
	// VARIANT_TYPES constant
	// -----------------------------------------------------------------------

	public function test_variant_types_contains_expected_values(): void {
		$this->assertContains( 'headline', AIVariantGenerator::VARIANT_TYPES );
		$this->assertContains( 'seo_meta', AIVariantGenerator::VARIANT_TYPES );
		$this->assertContains( 'cta', AIVariantGenerator::VARIANT_TYPES );
		$this->assertContains( 'tone', AIVariantGenerator::VARIANT_TYPES );
	}

	// -----------------------------------------------------------------------
	// generate_variants – happy path
	// -----------------------------------------------------------------------

	public function test_generate_variants_returns_two_modifiers_by_default(): void {
		$provider = $this->make_provider( "1. Focus on beginners.\n2. Focus on experts." );
		$gen      = new AIVariantGenerator( $provider );

		$variants = $gen->generate_variants( 'Best hiking boots', 'headline' );

		$this->assertArrayHasKey( 'modifier_a', $variants );
		$this->assertArrayHasKey( 'modifier_b', $variants );
		$this->assertCount( 2, $variants );
	}

	public function test_generate_variants_strips_numbering_from_lines(): void {
		$provider = $this->make_provider( "1. Focus on beginners.\n2. Focus on experts." );
		$gen      = new AIVariantGenerator( $provider );

		$variants = $gen->generate_variants( 'Topic', 'headline' );

		$this->assertSame( 'Focus on beginners.', $variants['modifier_a'] );
		$this->assertSame( 'Focus on experts.', $variants['modifier_b'] );
	}

	public function test_generate_variants_supports_three_variants(): void {
		$provider = $this->make_provider( "1. A\n2. B\n3. C" );
		$gen      = new AIVariantGenerator( $provider );

		$variants = $gen->generate_variants( 'Topic', 'tone', 3 );

		$this->assertArrayHasKey( 'modifier_a', $variants );
		$this->assertArrayHasKey( 'modifier_b', $variants );
		$this->assertArrayHasKey( 'modifier_c', $variants );
		$this->assertCount( 3, $variants );
	}

	public function test_generate_variants_clamps_count_to_max(): void {
		$lines    = implode( "\n", array_map( fn( $i ) => "{$i}. Variant {$i}", range( 1, 10 ) ) );
		$provider = $this->make_provider( $lines );
		$gen      = new AIVariantGenerator( $provider );

		$variants = $gen->generate_variants( 'Topic', 'headline', 99 );

		$this->assertLessThanOrEqual( AIVariantGenerator::MAX_VARIANT_COUNT, count( $variants ) );
	}

	public function test_generate_variants_clamps_count_to_minimum_two(): void {
		$provider = $this->make_provider( "1. A\n2. B" );
		$gen      = new AIVariantGenerator( $provider );

		$variants = $gen->generate_variants( 'Topic', 'cta', 1 );

		$this->assertCount( 2, $variants );
	}

	// -----------------------------------------------------------------------
	// generate_variants – type validation
	// -----------------------------------------------------------------------

	public function test_generate_variants_throws_for_unknown_type(): void {
		$gen = new AIVariantGenerator( $this->make_provider( '' ) );

		$this->expectException( \InvalidArgumentException::class );
		$gen->generate_variants( 'Topic', 'invalid_type' );
	}

	// -----------------------------------------------------------------------
	// Convenience wrappers
	// -----------------------------------------------------------------------

	public function test_generate_headline_variants_delegates_to_generate_variants(): void {
		$provider = $this->make_provider( "1. Beginner focus.\n2. Expert focus." );
		$gen      = new AIVariantGenerator( $provider );

		$variants = $gen->generate_headline_variants( 'Best Laptop 2026' );

		$this->assertCount( 2, $variants );
		$this->assertArrayHasKey( 'modifier_a', $variants );
	}

	public function test_generate_seo_variants_combines_title_and_description(): void {
		$provider = $this->make_provider( "1. SEO angle A.\n2. SEO angle B." );
		$gen      = new AIVariantGenerator( $provider );

		$variants = $gen->generate_seo_variants( 'Best Coffee Machines', 'Find the best value' );

		$this->assertCount( 2, $variants );
	}

	public function test_generate_cta_variants_returns_two_variants(): void {
		$provider = $this->make_provider( "1. Buy now.\n2. Learn more." );
		$gen      = new AIVariantGenerator( $provider );

		$variants = $gen->generate_cta_variants( 'Subscribe today' );

		$this->assertCount( 2, $variants );
	}

	// -----------------------------------------------------------------------
	// build_prompt
	// -----------------------------------------------------------------------

	public function test_build_prompt_contains_topic(): void {
		$gen    = new AIVariantGenerator( $this->make_provider( '' ) );
		$prompt = $gen->build_prompt( 'headline', 'Best Hiking Boots', 2 );

		$this->assertStringContainsString( 'Best Hiking Boots', $prompt );
	}

	public function test_build_prompt_contains_count(): void {
		$gen    = new AIVariantGenerator( $this->make_provider( '' ) );
		$prompt = $gen->build_prompt( 'seo_meta', 'Topic', 3 );

		$this->assertStringContainsString( '3', $prompt );
	}

	public function test_build_prompt_varies_by_type(): void {
		$gen = new AIVariantGenerator( $this->make_provider( '' ) );

		$prompts = array_map(
			fn( $type ) => $gen->build_prompt( $type, 'Topic', 2 ),
			AIVariantGenerator::VARIANT_TYPES
		);

		// Each prompt should be unique (different framing per type).
		$this->assertCount( count( array_unique( $prompts ) ), $prompts );
	}

	// -----------------------------------------------------------------------
	// Caching
	// -----------------------------------------------------------------------

	public function test_generate_variants_uses_transient_cache(): void {
		$provider = $this->make_provider( "1. Cached A.\n2. Cached B." );
		$gen      = new AIVariantGenerator( $provider );

		$first  = $gen->generate_variants( 'Same topic', 'headline' );
		$second = $gen->generate_variants( 'Same topic', 'headline' );

		$this->assertSame( $first, $second );
	}
}
