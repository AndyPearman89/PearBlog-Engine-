<?php
/**
 * AI Variant Generator — F3 (v9.0)
 *
 * Generates A/B test headline and CTA variants using the active AI provider.
 * Works alongside ABTestEngine: creates multiple prompt variations for a topic
 * so that the engine can split-test them automatically.
 *
 * Usage:
 *   $gen      = new AIVariantGenerator();
 *   $variants = $gen->generate_variants( 'Best hiking gear 2026', 3 );
 *   // Returns: [ 'modifier_0' => '...', 'modifier_1' => '...', 'modifier_2' => '...' ]
 *
 * The variants are prompt modifiers (instructions) that the ABTestEngine
 * appends to the base prompt via the `pearblog_prompt` filter.
 *
 * @package PearBlogEngine\Testing
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

use PearBlogEngine\AI\AIClient;

/**
 * Uses AI to generate diverse prompt modifiers for A/B test variants.
 */
class AIVariantGenerator {

	/** Maximum number of variants that may be generated in one call. */
	public const MAX_VARIANTS = 5;

	/** Default number of variants when not specified. */
	public const DEFAULT_VARIANTS = 2;

	/** @var AIClient */
	private AIClient $ai;

	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Generate $count distinct prompt modifier variants for the given topic.
	 *
	 * Each modifier is a short instruction string (e.g. "Focus on beginners.
	 * Use bullet lists. Emphasise cost savings.") intended to steer the AI
	 * content generator in a distinct direction.
	 *
	 * @param string $topic  The article topic.
	 * @param int    $count  Number of variants to generate (1–MAX_VARIANTS).
	 * @return array<string, string>  Map of variant key → modifier string.
	 */
	public function generate_variants( string $topic, int $count = self::DEFAULT_VARIANTS ): array {
		$count = max( 1, min( $count, self::MAX_VARIANTS ) );

		$prompt = $this->build_meta_prompt( $topic, $count );
		$raw    = $this->call_ai( $prompt );

		return $this->parse_variants( $raw, $count );
	}

	/**
	 * Generate a single headline variant for a post.
	 *
	 * @param string $original_headline Existing H1 title.
	 * @return string Alternative headline suggestion.
	 */
	public function generate_headline_variant( string $original_headline ): string {
		$prompt = sprintf(
			'Write one alternative SEO-optimised headline for the following article title. ' .
			'Return only the headline, no explanation.\n\nOriginal: %s',
			$original_headline
		);

		$result = $this->call_ai( $prompt );
		$result = trim( $result );

		return '' !== $result ? $result : $original_headline;
	}

	// -----------------------------------------------------------------------
	// Internal helpers
	// -----------------------------------------------------------------------

	/**
	 * Build the meta-prompt that instructs the AI to produce $count modifiers.
	 */
	public function build_meta_prompt( string $topic, int $count ): string {
		return sprintf(
			'You are a content strategy expert. For the article topic "%s", ' .
			'generate exactly %d distinct writing-style modifiers. ' .
			'Each modifier is a 1–3 sentence instruction that steers an AI content writer ' .
			'in a unique direction (e.g. tone, audience, angle, structure). ' .
			'Return ONLY a numbered list (1. ... 2. ... etc.). No extra text.',
			$topic,
			$count
		);
	}

	/**
	 * Parse the numbered list returned by the AI into an associative array.
	 *
	 * @param string $raw    Raw AI response.
	 * @param int    $count  Expected number of variants.
	 * @return array<string, string>
	 */
	public function parse_variants( string $raw, int $count ): array {
		$lines    = preg_split( '/\r?\n/', trim( $raw ) );
		$variants = [];
		$idx      = 0;

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			// Strip leading "1. " / "1) " numbering.
			$text = preg_replace( '/^\d+[\.\)]\s*/', '', $line );

			if ( '' !== $text ) {
				$variants[ 'modifier_' . $idx ] = $text;
				$idx++;
			}

			if ( $idx >= $count ) {
				break;
			}
		}

		// Pad with fallbacks if AI returned fewer lines than requested.
		while ( $idx < $count ) {
			$variants[ 'modifier_' . $idx ] = 'Write a comprehensive, well-structured article with clear headings.';
			$idx++;
		}

		return $variants;
	}

	/**
	 * Call the AI client and return the raw text response.
	 * Overridable in tests.
	 */
	protected function call_ai( string $prompt ): string {
		try {
			return $this->ai->complete( $prompt );
		} catch ( \Throwable $e ) {
			return '';
		}
	}
}
