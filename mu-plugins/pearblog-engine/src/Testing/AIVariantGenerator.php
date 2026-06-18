<?php
/**
 * AI Variant Generator – V9.0 F3: auto-generates A/B test variants using AI.
 *
 * Integrates with the existing ABTestEngine to produce headline, CTA, and
 * SEO-title variants without manual copywriting.  Each variant is generated
 * via the active AI provider (OpenAI / Anthropic / Gemini) using a
 * structured prompt, then stored alongside the test record.
 *
 * Usage:
 *   $gen = new AIVariantGenerator();
 *   $variants = $gen->generate( 'post_title', 'Best Laptops 2026', 3 );
 *   // Returns ['Best Laptops Under $1K 2026', 'Top 10 Laptops …', …]
 *
 * @package PearBlogEngine\Testing
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

/**
 * Generates content variants for A/B tests using the active AI provider.
 */
class AIVariantGenerator {

	/** WP option: name of the AI model to use for variant generation. */
	public const OPTION_MODEL = 'pearblog_variant_gen_model';

	/** Default model used when option is not set. */
	public const DEFAULT_MODEL = 'gpt-4o-mini';

	/** Maximum variants the generator will produce per call. */
	public const MAX_VARIANTS = 10;

	/** @var callable(string,string):string Pluggable AI call. */
	private $ai_caller;

	/**
	 * @param callable|null $ai_caller  Optional override for AI call (testing).
	 *                                   Signature: fn(model, prompt): string
	 */
	public function __construct( ?callable $ai_caller = null ) {
		$this->ai_caller = $ai_caller ?? [ $this, 'default_ai_call' ];
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Generate $count alternative variants of $original_text for $field_type.
	 *
	 * @param string $field_type    'post_title' | 'cta_text' | 'meta_description'
	 * @param string $original_text The original copy to improve/vary.
	 * @param int    $count         Number of variants to generate (1–MAX_VARIANTS).
	 * @return string[]             Array of variant strings.
	 */
	public function generate( string $field_type, string $original_text, int $count = 3 ): array {
		$count = max( 1, min( self::MAX_VARIANTS, $count ) );
		$model = get_option( self::OPTION_MODEL, self::DEFAULT_MODEL );
		$prompt = $this->build_prompt( $field_type, $original_text, $count );

		$raw = ( $this->ai_caller )( $model, $prompt );

		return $this->parse_variants( $raw, $count );
	}

	/**
	 * Build a structured prompt for variant generation.
	 *
	 * @param string $field_type
	 * @param string $original
	 * @param int    $count
	 * @return string
	 */
	public function build_prompt( string $field_type, string $original, int $count ): string {
		$labels = [
			'post_title'       => 'article headline',
			'cta_text'         => 'call-to-action button label',
			'meta_description' => 'SEO meta description',
		];
		$label = $labels[ $field_type ] ?? $field_type;

		return sprintf(
			"Generate exactly %d alternative %s variants of the following text.\n" .
			"Each variant must be on its own line, numbered with a period (e.g. \"1. ...\").\n" .
			"Keep each variant concise and persuasive.\n\nOriginal: %s",
			$count,
			$label,
			$original
		);
	}

	/**
	 * Parse AI response into a clean array of variant strings.
	 *
	 * @param string $raw
	 * @param int    $count Expected number of variants.
	 * @return string[]
	 */
	public function parse_variants( string $raw, int $count ): array {
		$lines    = preg_split( '/\r?\n/', trim( $raw ) ) ?: [];
		$variants = [];

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			// Strip leading "1. ", "2. ", "- ", "* " etc.
			$cleaned = preg_replace( '/^[\d]+\.\s*|^[-*]\s*/', '', $line );
			if ( null !== $cleaned && '' !== $cleaned ) {
				$variants[] = $cleaned;
			}
		}

		// Trim to requested count.
		return array_slice( $variants, 0, $count );
	}

	// -----------------------------------------------------------------------
	// Internal helpers
	// -----------------------------------------------------------------------

	/**
	 * Default AI call via wp_remote_post to the OpenAI completions endpoint.
	 * In production this should be replaced by the project's AIClient.
	 *
	 * @param string $model
	 * @param string $prompt
	 * @return string
	 */
	protected function default_ai_call( string $model, string $prompt ): string {
		$api_key = get_option( 'pearblog_openai_api_key', '' );
		if ( empty( $api_key ) ) {
			return '';
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( [
					'model'    => $model,
					'messages' => [
						[ 'role' => 'user', 'content' => $prompt ],
					],
					'max_tokens' => 500,
				] ),
				'timeout' => 20,
			]
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['choices'][0]['message']['content'] ?? '';
	}
}
