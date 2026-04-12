<?php
/**
 * Contract for AI text-generation providers.
 *
 * Every provider (OpenAI, Anthropic, Google Gemini, …) must implement this
 * interface so that AIClient and AIProviderFactory can treat them uniformly.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * AI provider contract.
 */
interface AIProviderInterface {

	// -----------------------------------------------------------------------
	// Instance API
	// -----------------------------------------------------------------------

	/**
	 * Send a single prompt to the provider and return structured usage data.
	 *
	 * The implementation is responsible for making the HTTP request only — no
	 * retry logic, circuit breaker, or cost tracking (all handled by AIClient).
	 *
	 * @param string $prompt     Full prompt text.
	 * @param int    $max_tokens Maximum tokens in the response.
	 * @return array{content: string, prompt_tokens: int, completion_tokens: int}
	 * @throws RateLimitException  When the provider returns a rate-limit signal.
	 * @throws \RuntimeException   On HTTP errors or unexpected API responses.
	 */
	public function complete( string $prompt, int $max_tokens ): array;

	// -----------------------------------------------------------------------
	// Static metadata (usable without instantiation)
	// -----------------------------------------------------------------------

	/**
	 * Provider slug, used as the `pearblog_ai_provider` option value.
	 * Examples: 'openai', 'anthropic', 'gemini'.
	 */
	public static function get_slug(): string;

	/**
	 * Human-readable provider name shown in the admin UI.
	 * Examples: 'OpenAI', 'Anthropic Claude', 'Google Gemini'.
	 */
	public static function get_label(): string;

	/**
	 * WordPress option key that stores this provider's API key.
	 */
	public static function get_api_key_option(): string;

	/**
	 * Model metadata map for this provider.
	 *
	 * Keys are model slugs (e.g. 'gpt-4o', 'claude-3-5-sonnet-20241022').
	 * Values must have at minimum:
	 *   label                    – human-readable name
	 *   max_tokens               – maximum supported output tokens
	 *   cost_per_1k_input_cents  – USD cents per 1 000 input tokens
	 *   cost_per_1k_output_cents – USD cents per 1 000 output tokens
	 *
	 * @return array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>
	 */
	public static function get_models(): array;

	/**
	 * Default model slug for this provider (used when the stored option is
	 * absent or does not match any key in get_models()).
	 */
	public static function get_default_model(): string;
}
