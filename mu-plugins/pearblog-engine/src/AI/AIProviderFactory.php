<?php
/**
 * Factory that instantiates the correct AI provider.
 *
 * Usage:
 *   $provider = AIProviderFactory::make();                         // active provider from options
 *   $provider = AIProviderFactory::make('anthropic');              // explicit provider
 *   $provider = AIProviderFactory::make('openai', 'sk-…', 'gpt-4o'); // with key + model overrides
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Creates AIProviderInterface instances based on the active settings.
 */
class AIProviderFactory {

	/** WordPress option key that stores the active provider slug. */
	public const PROVIDER_OPTION = 'pearblog_ai_provider';

	/** Default provider when the option is absent or invalid. */
	public const DEFAULT_PROVIDER = 'openai';

	/**
	 * Registered provider class map.
	 *
	 * @var array<string, class-string<AIProviderInterface>>
	 */
	private const PROVIDERS = [
		'openai'    => OpenAIProvider::class,
		'anthropic' => AnthropicProvider::class,
		'gemini'    => GeminiProvider::class,
	];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Build and return the requested provider instance.
	 *
	 * @param string $provider_slug Slug from PROVIDERS keys; empty = read from WP option.
	 * @param string $api_key       Optional API key override; empty = each provider reads its own option.
	 * @param string $model         Optional model override; empty = each provider reads the shared model option.
	 * @return AIProviderInterface
	 * @throws \InvalidArgumentException For unknown provider slugs.
	 */
	public static function make(
		string $provider_slug = '',
		string $api_key       = '',
		string $model         = ''
	): AIProviderInterface {
		if ( '' === $provider_slug ) {
			$provider_slug = self::get_active_provider_slug();
		}

		if ( ! isset( self::PROVIDERS[ $provider_slug ] ) ) {
			throw new \InvalidArgumentException(
				"PearBlog Engine: Unknown AI provider '{$provider_slug}'."
			);
		}

		$class = self::PROVIDERS[ $provider_slug ];
		return new $class( $api_key, $model );
	}

	/**
	 * Return the active provider slug (reads WP option, falls back to default).
	 */
	public static function get_active_provider_slug(): string {
		$stored = (string) get_option( self::PROVIDER_OPTION, self::DEFAULT_PROVIDER );
		return isset( self::PROVIDERS[ $stored ] ) ? $stored : self::DEFAULT_PROVIDER;
	}

	/**
	 * Return a map of all registered providers: slug → label.
	 *
	 * @return array<string, string>
	 */
	public static function get_all_providers(): array {
		$result = [];
		foreach ( self::PROVIDERS as $slug => $class ) {
			$result[ $slug ] = $class::get_label();
		}
		return $result;
	}

	/**
	 * Return all models for all registered providers, grouped by provider slug.
	 *
	 * @return array<string, array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>>
	 */
	public static function get_all_models(): array {
		$result = [];
		foreach ( self::PROVIDERS as $slug => $class ) {
			$result[ $slug ] = $class::get_models();
		}
		return $result;
	}

	/**
	 * Return the models for the currently active provider.
	 *
	 * @return array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>
	 */
	public static function get_active_provider_models(): array {
		$slug  = self::get_active_provider_slug();
		$class = self::PROVIDERS[ $slug ];
		return $class::get_models();
	}

	/**
	 * Return the default model slug for the currently active provider.
	 */
	public static function get_active_provider_default_model(): string {
		$slug  = self::get_active_provider_slug();
		$class = self::PROVIDERS[ $slug ];
		return $class::get_default_model();
	}

	/**
	 * Return the WordPress option key for the active provider's API key.
	 */
	public static function get_active_api_key_option(): string {
		$slug  = self::get_active_provider_slug();
		$class = self::PROVIDERS[ $slug ];
		return $class::get_api_key_option();
	}
}
