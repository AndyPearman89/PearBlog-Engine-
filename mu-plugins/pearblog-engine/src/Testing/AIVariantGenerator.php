<?php
/**
 * AI Variant Generator – auto-generates A/B test variant modifiers using AI.
 *
 * Part of the V9.0 F3: Smart A/B Testing Engine enhancement.
 *
 * This class uses the active AI provider to produce distinct content-strategy
 * modifiers for headline, SEO meta, and CTA variations so that ABTestEngine
 * tests can be seeded automatically without manual authoring.
 *
 * Variant types supported:
 *  - 'headline'  : Alternative H1 angles / emotional triggers.
 *  - 'seo_meta'  : Different title-tag / meta-description framings.
 *  - 'cta'       : Alternative call-to-action phrasings.
 *  - 'tone'      : Shifts in authorial tone (formal ↔ conversational ↔ expert).
 *
 * Usage:
 *   $gen      = new AIVariantGenerator();
 *   $variants = $gen->generate_variants( 'Best hiking boots', 'headline', 3 );
 *   // Returns [ 'modifier_a' => '...', 'modifier_b' => '...', ... ]
 *
 * @package PearBlogEngine\Testing
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

use PearBlogEngine\AI\AIProviderFactory;
use PearBlogEngine\AI\AIProviderInterface;

/**
 * Generates A/B test variant modifiers via the active AI provider.
 */
class AIVariantGenerator {

	/** Supported variant types. */
	public const VARIANT_TYPES = [ 'headline', 'seo_meta', 'cta', 'tone' ];

	/** Default number of variants to generate per call. */
	public const DEFAULT_VARIANT_COUNT = 2;

	/** Maximum allowed variants per call to keep prompts manageable. */
	public const MAX_VARIANT_COUNT = 5;

	/** Max tokens for the AI response. */
	private const MAX_TOKENS = 512;

	/** WP option caching generated variants (keyed by topic hash). */
	public const CACHE_OPTION = 'pearblog_ai_variant_cache';

	/** Cache TTL in seconds (6 hours). */
	private const CACHE_TTL = 21600;

	private AIProviderInterface $provider;

	public function __construct( AIProviderInterface $provider = null ) {
		$this->provider = $provider ?? AIProviderFactory::make();
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Generate $count variant modifiers for the given topic and type.
	 *
	 * Returns an array like:
	 *   [
	 *     'modifier_a' => 'Focus on long-term durability and expert reviews.',
	 *     'modifier_b' => 'Focus on beginner friendliness and value for money.',
	 *   ]
	 * For count > 2, additional keys are 'modifier_c', 'modifier_d', …
	 *
	 * Results are cached in a WP transient to avoid redundant AI calls.
	 *
	 * @param string $topic        Article topic or title.
	 * @param string $variant_type One of self::VARIANT_TYPES.
	 * @param int    $count        Number of distinct variants (2–5).
	 * @return array<string, string>
	 * @throws \InvalidArgumentException For unsupported variant types.
	 */
	public function generate_variants(
		string $topic,
		string $variant_type = 'headline',
		int    $count        = self::DEFAULT_VARIANT_COUNT
	): array {
		if ( ! in_array( $variant_type, self::VARIANT_TYPES, true ) ) {
			throw new \InvalidArgumentException(
				"Unsupported variant type '{$variant_type}'. Allowed: " . implode( ', ', self::VARIANT_TYPES )
			);
		}

		$count = max( 2, min( self::MAX_VARIANT_COUNT, $count ) );

		$cache_key = $this->cache_key( $topic, $variant_type, $count );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$raw      = $this->call_ai( $topic, $variant_type, $count );
		$variants = $this->parse_variants( $raw, $count );

		set_transient( $cache_key, $variants, self::CACHE_TTL );

		return $variants;
	}

	/**
	 * Generate headline variants and return them as labelled modifier strings.
	 *
	 * @param string $title Current article title.
	 * @param int    $count Number of variants (2–5).
	 * @return array<string, string>
	 */
	public function generate_headline_variants( string $title, int $count = self::DEFAULT_VARIANT_COUNT ): array {
		return $this->generate_variants( $title, 'headline', $count );
	}

	/**
	 * Generate SEO meta variants (title tag + meta description angles).
	 *
	 * @param string $title       Current article title.
	 * @param string $description Current meta description (may be empty).
	 * @param int    $count       Number of variants (2–5).
	 * @return array<string, string>
	 */
	public function generate_seo_variants(
		string $title,
		string $description = '',
		int    $count       = self::DEFAULT_VARIANT_COUNT
	): array {
		$topic = $description ? "{$title}: {$description}" : $title;
		return $this->generate_variants( $topic, 'seo_meta', $count );
	}

	/**
	 * Generate CTA phrasing variants.
	 *
	 * @param string $current_cta Current CTA text.
	 * @param int    $count       Number of variants (2–5).
	 * @return array<string, string>
	 */
	public function generate_cta_variants( string $current_cta, int $count = self::DEFAULT_VARIANT_COUNT ): array {
		return $this->generate_variants( $current_cta, 'cta', $count );
	}

	/**
	 * Return the prompt instructions for a given variant type.
	 *
	 * @param string $variant_type Variant type slug.
	 * @param string $topic        Topic / context string.
	 * @param int    $count        Number of variants requested.
	 * @return string
	 */
	public function build_prompt( string $variant_type, string $topic, int $count ): string {
		$instructions = [
			'headline' => "Generate {$count} distinct prompt modifiers (each one sentence) that shift the editorial angle "
				. "for an article about: \"{$topic}\". Each modifier should emphasise a different reader benefit or "
				. "emotional trigger (e.g. beginner-friendly, expert deep-dive, cost-saving, aspirational). "
				. "Output exactly {$count} lines numbered 1. 2. etc. Nothing else.",

			'seo_meta' => "Generate {$count} distinct prompt modifiers (each one sentence) that produce different "
				. "SEO title and meta-description angles for: \"{$topic}\". Each modifier should target a "
				. "different search intent or keyword cluster. "
				. "Output exactly {$count} numbered lines. Nothing else.",

			'cta'      => "Generate {$count} distinct prompt modifiers (each one sentence) that rewrite the "
				. "call-to-action strategy for an article about: \"{$topic}\". Each modifier should suggest a "
				. "different CTA framing (urgency, value-lead, social-proof, question, command). "
				. "Output exactly {$count} numbered lines. Nothing else.",

			'tone'     => "Generate {$count} distinct prompt modifiers (each one sentence) that set a different "
				. "authorial tone for an article about: \"{$topic}\". Options include: formal/academic, "
				. "conversational/friendly, expert/authoritative, storytelling/narrative, data-driven. "
				. "Output exactly {$count} numbered lines. Nothing else.",
		];

		return $instructions[ $variant_type ] ?? '';
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Call the AI provider and return the raw text response.
	 */
	private function call_ai( string $topic, string $variant_type, int $count ): string {
		$prompt   = $this->build_prompt( $variant_type, $topic, $count );
		$response = $this->provider->complete( $prompt, self::MAX_TOKENS );
		return $response['content'] ?? '';
	}

	/**
	 * Parse the numbered list returned by the AI into a labelled modifier array.
	 *
	 * @param string $raw   Raw AI response text.
	 * @param int    $count Expected number of variants.
	 * @return array<string, string>
	 */
	private function parse_variants( string $raw, int $count ): array {
		$lines    = preg_split( '/\r?\n/', trim( $raw ) );
		$labels   = range( 'a', 'z' );
		$variants = [];

		$index = 0;
		foreach ( $lines as $line ) {
			if ( $index >= $count ) {
				break;
			}
			// Strip leading numbering like "1." or "1)" from the AI response.
			$cleaned = preg_replace( '/^\s*\d+[.)]\s*/', '', $line );
			$cleaned = trim( $cleaned );
			if ( '' === $cleaned ) {
				continue;
			}
			$variants[ 'modifier_' . $labels[ $index ] ] = $cleaned;
			$index++;
		}

		// Pad with fallback values if AI returned fewer lines than expected.
		while ( $index < $count ) {
			$variants[ 'modifier_' . $labels[ $index ] ] = "Variant " . strtoupper( $labels[ $index ] ) . " for: {$raw}";
			$index++;
		}

		return $variants;
	}

	/**
	 * Build a deterministic transient key for a given topic / type / count combination.
	 */
	private function cache_key( string $topic, string $variant_type, int $count ): string {
		return 'pb_aivg_' . substr( md5( $topic . $variant_type . $count ), 0, 12 );
	}
}
