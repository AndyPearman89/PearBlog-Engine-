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

	private AIVariantGenerator $gen;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_options']   = [];
		$GLOBALS['_posts']     = [];
		$this->gen             = new AIVariantGenerator();
	}

	// -----------------------------------------------------------------------
	// generate() — type validation
	// -----------------------------------------------------------------------

	public function test_generate_defaults_to_headline_for_unknown_type(): void {
		$result = $this->gen->generate( 1, 'unknown_type', 2, false );

		$this->assertSame( AIVariantGenerator::TYPE_HEADLINE, $result['type'] );
	}

	public function test_generate_returns_correct_structure(): void {
		$result = $this->gen->generate( 1, AIVariantGenerator::TYPE_HEADLINE, 2, false );

		$this->assertSame( 1, $result['post_id'] );
		$this->assertSame( AIVariantGenerator::TYPE_HEADLINE, $result['type'] );
		$this->assertArrayHasKey( 'original', $result );
		$this->assertArrayHasKey( 'variants', $result );
		$this->assertArrayHasKey( 'generated_at', $result );
		$this->assertArrayHasKey( 'source', $result );
		$this->assertIsArray( $result['variants'] );
	}

	// -----------------------------------------------------------------------
	// Headline variants
	// -----------------------------------------------------------------------

	public function test_generate_headline_count_respected(): void {
		$result = $this->gen->generate( 42, AIVariantGenerator::TYPE_HEADLINE, 3, false );

		$this->assertCount( 3, $result['variants'] );
	}

	public function test_generate_headline_count_capped_at_max(): void {
		$result = $this->gen->generate( 1, AIVariantGenerator::TYPE_HEADLINE, 999, false );

		$this->assertLessThanOrEqual( AIVariantGenerator::MAX_VARIANTS, count( $result['variants'] ) );
	}

	public function test_generate_headline_count_min_one(): void {
		$result = $this->gen->generate( 1, AIVariantGenerator::TYPE_HEADLINE, 0, false );

		$this->assertCount( 1, $result['variants'] );
	}

	public function test_headline_variants_are_strings(): void {
		$result = $this->gen->generate( 5, AIVariantGenerator::TYPE_HEADLINE, 5, false );

		foreach ( $result['variants'] as $v ) {
			$this->assertIsString( $v );
			$this->assertNotEmpty( $v );
		}
	}

	// -----------------------------------------------------------------------
	// CTA variants
	// -----------------------------------------------------------------------

	public function test_generate_cta_returns_pool_items(): void {
		$result = $this->gen->generate( 1, AIVariantGenerator::TYPE_CTA, 4, false );

		$this->assertCount( 4, $result['variants'] );
		foreach ( $result['variants'] as $v ) {
			$this->assertIsString( $v );
		}
	}

	public function test_generate_cta_original_is_read_more(): void {
		$result = $this->gen->generate( 1, AIVariantGenerator::TYPE_CTA, 1, false );

		$this->assertSame( 'Read More', $result['original'] );
	}

	// -----------------------------------------------------------------------
	// Meta variants
	// -----------------------------------------------------------------------

	public function test_generate_meta_appends_suffix(): void {
		$GLOBALS['_post_meta'][10]['_yoast_wpseo_metadesc'] = [ 'Best practices for SEO' ];
		$result = $this->gen->generate( 10, AIVariantGenerator::TYPE_META, 2, false );

		$this->assertCount( 2, $result['variants'] );
		foreach ( $result['variants'] as $v ) {
			$this->assertStringContainsString( 'Best practices for SEO', $v );
		}
	}

	// -----------------------------------------------------------------------
	// Intro variants
	// -----------------------------------------------------------------------

	public function test_generate_intro_returns_sentence_openers(): void {
		$result = $this->gen->generate( 1, AIVariantGenerator::TYPE_INTRO, 3, false );

		$this->assertCount( 3, $result['variants'] );
		foreach ( $result['variants'] as $v ) {
			$this->assertIsString( $v );
			$this->assertNotEmpty( $v );
		}
	}

	// -----------------------------------------------------------------------
	// generate_all()
	// -----------------------------------------------------------------------

	public function test_generate_all_covers_all_types(): void {
		$results = $this->gen->generate_all( 1, 2 );

		$this->assertArrayHasKey( AIVariantGenerator::TYPE_HEADLINE, $results );
		$this->assertArrayHasKey( AIVariantGenerator::TYPE_CTA, $results );
		$this->assertArrayHasKey( AIVariantGenerator::TYPE_META, $results );
		$this->assertArrayHasKey( AIVariantGenerator::TYPE_INTRO, $results );
	}

	// -----------------------------------------------------------------------
	// Cache
	// -----------------------------------------------------------------------

	public function test_cache_is_stored_and_returned(): void {
		// First call: populate cache.
		$first = $this->gen->generate( 99, AIVariantGenerator::TYPE_HEADLINE, 2, true );

		// Second call: should hit cache.
		$second = $this->gen->generate( 99, AIVariantGenerator::TYPE_HEADLINE, 2, true );

		$this->assertSame( $first['variants'], $second['variants'] );
	}

	public function test_cache_miss_when_more_variants_requested(): void {
		// Populate cache with 2 variants.
		$this->gen->generate( 99, AIVariantGenerator::TYPE_HEADLINE, 2, true );

		// Request 5 — should bypass cache (insufficient cached count) and regenerate.
		$result = $this->gen->generate( 99, AIVariantGenerator::TYPE_HEADLINE, 5, true );

		$this->assertCount( 5, $result['variants'] );
	}

	public function test_clear_cache_removes_cached_variants(): void {
		$this->gen->generate( 99, AIVariantGenerator::TYPE_HEADLINE, 2, true );
		$this->gen->clear_cache( 99, AIVariantGenerator::TYPE_HEADLINE );

		// Meta should be empty now.
		$raw = get_post_meta( 99, '_pearblog_ab_variants', true );
		$decoded = json_decode( (string) $raw, true );

		$this->assertFalse( isset( $decoded[ AIVariantGenerator::TYPE_HEADLINE ] ) );
	}

	public function test_clear_all_cache_removes_all_types(): void {
		$this->gen->generate_all( 77, 2 );
		$this->gen->clear_cache( 77 );

		$raw = get_post_meta( 77, '_pearblog_ab_variants', true );
		$this->assertSame( '', (string) $raw );
	}

	// -----------------------------------------------------------------------
	// AI enabled flag
	// -----------------------------------------------------------------------

	public function test_ai_disabled_by_default(): void {
		$this->assertFalse( $this->gen->is_ai_enabled() );
	}

	public function test_source_is_template_when_ai_disabled(): void {
		$result = $this->gen->generate( 1, AIVariantGenerator::TYPE_HEADLINE, 1, false );

		$this->assertSame( 'template', $result['source'] );
	}
}
