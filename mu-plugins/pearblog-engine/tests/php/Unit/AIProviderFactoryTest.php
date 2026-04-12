<?php
/**
 * Unit tests for AIProviderFactory and the three providers.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\AIProviderFactory;
use PearBlogEngine\AI\AIProviderInterface;
use PearBlogEngine\AI\OpenAIProvider;
use PearBlogEngine\AI\AnthropicProvider;
use PearBlogEngine\AI\GeminiProvider;
use PearBlogEngine\AI\RateLimitException;

class AIProviderFactoryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
	}

	// -----------------------------------------------------------------------
	// Factory — provider resolution
	// -----------------------------------------------------------------------

	public function test_make_returns_openai_provider_by_default(): void {
		$provider = AIProviderFactory::make();
		$this->assertInstanceOf( OpenAIProvider::class, $provider );
	}

	public function test_make_returns_openai_provider_explicitly(): void {
		$provider = AIProviderFactory::make( 'openai' );
		$this->assertInstanceOf( OpenAIProvider::class, $provider );
	}

	public function test_make_returns_anthropic_provider(): void {
		$provider = AIProviderFactory::make( 'anthropic' );
		$this->assertInstanceOf( AnthropicProvider::class, $provider );
	}

	public function test_make_returns_gemini_provider(): void {
		$provider = AIProviderFactory::make( 'gemini' );
		$this->assertInstanceOf( GeminiProvider::class, $provider );
	}

	public function test_make_throws_for_unknown_provider(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessageMatches( '/Unknown AI provider/i' );
		AIProviderFactory::make( 'nonexistent' );
	}

	public function test_make_reads_active_provider_from_option(): void {
		update_option( AIProviderFactory::PROVIDER_OPTION, 'anthropic' );
		$provider = AIProviderFactory::make();
		$this->assertInstanceOf( AnthropicProvider::class, $provider );
	}

	public function test_make_falls_back_to_default_for_invalid_option(): void {
		update_option( AIProviderFactory::PROVIDER_OPTION, 'flux-ultra-fake' );
		$provider = AIProviderFactory::make();
		$this->assertInstanceOf( OpenAIProvider::class, $provider );
	}

	// -----------------------------------------------------------------------
	// Factory — metadata helpers
	// -----------------------------------------------------------------------

	public function test_get_all_providers_has_three_entries(): void {
		$providers = AIProviderFactory::get_all_providers();
		$this->assertArrayHasKey( 'openai',    $providers );
		$this->assertArrayHasKey( 'anthropic', $providers );
		$this->assertArrayHasKey( 'gemini',    $providers );
		$this->assertCount( 3, $providers );
	}

	public function test_get_all_models_has_entries_for_every_provider(): void {
		$all = AIProviderFactory::get_all_models();
		$this->assertArrayHasKey( 'openai',    $all );
		$this->assertArrayHasKey( 'anthropic', $all );
		$this->assertArrayHasKey( 'gemini',    $all );
	}

	public function test_get_active_provider_models_returns_openai_by_default(): void {
		$models = AIProviderFactory::get_active_provider_models();
		$this->assertArrayHasKey( 'gpt-4o',      $models );
		$this->assertArrayHasKey( 'gpt-4o-mini', $models );
	}

	public function test_get_active_provider_models_returns_anthropic_when_set(): void {
		update_option( AIProviderFactory::PROVIDER_OPTION, 'anthropic' );
		$models = AIProviderFactory::get_active_provider_models();
		$this->assertArrayHasKey( 'claude-3-5-sonnet-20241022', $models );
		$this->assertArrayHasKey( 'claude-3-haiku-20240307',    $models );
	}

	public function test_get_active_provider_models_returns_gemini_when_set(): void {
		update_option( AIProviderFactory::PROVIDER_OPTION, 'gemini' );
		$models = AIProviderFactory::get_active_provider_models();
		$this->assertArrayHasKey( 'gemini-1.5-pro',   $models );
		$this->assertArrayHasKey( 'gemini-1.5-flash', $models );
	}

	public function test_get_active_provider_default_model_changes_with_provider(): void {
		update_option( AIProviderFactory::PROVIDER_OPTION, 'openai' );
		$this->assertSame( 'gpt-4o-mini', AIProviderFactory::get_active_provider_default_model() );

		update_option( AIProviderFactory::PROVIDER_OPTION, 'anthropic' );
		$this->assertSame( 'claude-3-5-sonnet-20241022', AIProviderFactory::get_active_provider_default_model() );

		update_option( AIProviderFactory::PROVIDER_OPTION, 'gemini' );
		$this->assertSame( 'gemini-1.5-pro', AIProviderFactory::get_active_provider_default_model() );
	}

	public function test_get_active_api_key_option_changes_with_provider(): void {
		update_option( AIProviderFactory::PROVIDER_OPTION, 'openai' );
		$this->assertSame( 'pearblog_openai_api_key', AIProviderFactory::get_active_api_key_option() );

		update_option( AIProviderFactory::PROVIDER_OPTION, 'anthropic' );
		$this->assertSame( 'pearblog_anthropic_api_key', AIProviderFactory::get_active_api_key_option() );

		update_option( AIProviderFactory::PROVIDER_OPTION, 'gemini' );
		$this->assertSame( 'pearblog_gemini_api_key', AIProviderFactory::get_active_api_key_option() );
	}

	// -----------------------------------------------------------------------
	// Provider interface contract — OpenAI
	// -----------------------------------------------------------------------

	public function test_openai_implements_interface(): void {
		$this->assertInstanceOf( AIProviderInterface::class, new OpenAIProvider( 'sk-test' ) );
	}

	public function test_openai_slug_and_label(): void {
		$this->assertSame( 'openai', OpenAIProvider::get_slug() );
		$this->assertNotEmpty( OpenAIProvider::get_label() );
	}

	public function test_openai_has_four_models(): void {
		$models = OpenAIProvider::get_models();
		$this->assertCount( 4, $models );
		$this->assertArrayHasKey( 'gpt-4o',        $models );
		$this->assertArrayHasKey( 'gpt-4o-mini',   $models );
		$this->assertArrayHasKey( 'gpt-4-turbo',   $models );
		$this->assertArrayHasKey( 'gpt-3.5-turbo', $models );
	}

	public function test_openai_default_model_is_gpt4o_mini(): void {
		$this->assertSame( 'gpt-4o-mini', OpenAIProvider::get_default_model() );
	}

	public function test_openai_complete_throws_without_api_key(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/API key is not configured/i' );
		( new OpenAIProvider( '' ) )->complete( 'test', 10 );
	}

	// -----------------------------------------------------------------------
	// Provider interface contract — Anthropic
	// -----------------------------------------------------------------------

	public function test_anthropic_implements_interface(): void {
		$this->assertInstanceOf( AIProviderInterface::class, new AnthropicProvider( 'sk-ant-test' ) );
	}

	public function test_anthropic_slug_and_label(): void {
		$this->assertSame( 'anthropic', AnthropicProvider::get_slug() );
		$this->assertNotEmpty( AnthropicProvider::get_label() );
	}

	public function test_anthropic_has_two_models(): void {
		$models = AnthropicProvider::get_models();
		$this->assertCount( 2, $models );
		$this->assertArrayHasKey( 'claude-3-5-sonnet-20241022', $models );
		$this->assertArrayHasKey( 'claude-3-haiku-20240307',    $models );
	}

	public function test_anthropic_default_model(): void {
		$this->assertSame( 'claude-3-5-sonnet-20241022', AnthropicProvider::get_default_model() );
	}

	public function test_anthropic_complete_throws_without_api_key(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/API key is not configured/i' );
		( new AnthropicProvider( '' ) )->complete( 'test', 10 );
	}

	public function test_anthropic_model_costs_reasonable(): void {
		$models = AnthropicProvider::get_models();
		foreach ( $models as $slug => $meta ) {
			$this->assertGreaterThan( 0, $meta['cost_per_1k_input_cents'],  "Input cost for {$slug} must be positive" );
			$this->assertGreaterThan( 0, $meta['cost_per_1k_output_cents'], "Output cost for {$slug} must be positive" );
		}
	}

	// -----------------------------------------------------------------------
	// Provider interface contract — Gemini
	// -----------------------------------------------------------------------

	public function test_gemini_implements_interface(): void {
		$this->assertInstanceOf( AIProviderInterface::class, new GeminiProvider( 'AIza-test' ) );
	}

	public function test_gemini_slug_and_label(): void {
		$this->assertSame( 'gemini', GeminiProvider::get_slug() );
		$this->assertNotEmpty( GeminiProvider::get_label() );
	}

	public function test_gemini_has_two_models(): void {
		$models = GeminiProvider::get_models();
		$this->assertCount( 2, $models );
		$this->assertArrayHasKey( 'gemini-1.5-pro',   $models );
		$this->assertArrayHasKey( 'gemini-1.5-flash', $models );
	}

	public function test_gemini_default_model(): void {
		$this->assertSame( 'gemini-1.5-pro', GeminiProvider::get_default_model() );
	}

	public function test_gemini_complete_throws_without_api_key(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/API key is not configured/i' );
		( new GeminiProvider( '' ) )->complete( 'test', 10 );
	}

	public function test_gemini_model_costs_reasonable(): void {
		$models = GeminiProvider::get_models();
		foreach ( $models as $slug => $meta ) {
			$this->assertGreaterThan( 0, $meta['cost_per_1k_input_cents'],  "Input cost for {$slug} must be positive" );
			$this->assertGreaterThan( 0, $meta['cost_per_1k_output_cents'], "Output cost for {$slug} must be positive" );
		}
	}

	// -----------------------------------------------------------------------
	// Each model has the required metadata keys
	// -----------------------------------------------------------------------

	public function test_all_model_entries_have_required_keys(): void {
		foreach ( AIProviderFactory::get_all_models() as $provider_slug => $models ) {
			foreach ( $models as $model_slug => $meta ) {
				$label = "{$provider_slug}/{$model_slug}";
				$this->assertArrayHasKey( 'label',                    $meta, "{$label} missing 'label'" );
				$this->assertArrayHasKey( 'max_tokens',               $meta, "{$label} missing 'max_tokens'" );
				$this->assertArrayHasKey( 'cost_per_1k_input_cents',  $meta, "{$label} missing input cost" );
				$this->assertArrayHasKey( 'cost_per_1k_output_cents', $meta, "{$label} missing output cost" );
				$this->assertGreaterThan( 0, $meta['max_tokens'],               "{$label} max_tokens must be positive" );
				$this->assertGreaterThanOrEqual( 0, $meta['cost_per_1k_input_cents'],  "{$label} input cost cannot be negative" );
				$this->assertGreaterThanOrEqual( 0, $meta['cost_per_1k_output_cents'], "{$label} output cost cannot be negative" );
			}
		}
	}

	// -----------------------------------------------------------------------
	// RateLimitException is usable from the AI namespace
	// -----------------------------------------------------------------------

	public function test_rate_limit_exception_is_throwable(): void {
		$this->expectException( RateLimitException::class );
		throw new RateLimitException( 'test' );
	}

	public function test_rate_limit_exception_extends_runtime_exception(): void {
		$e = new RateLimitException( 'test' );
		$this->assertInstanceOf( \RuntimeException::class, $e );
	}
}
