<?php
/**
 * Unit tests for AIClient circuit breaker, cost tracking, and model selection.
 *
 * These tests exercise only the circuit-breaker state management logic and the
 * new model-configuration API. HTTP calls are not made during unit tests.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\AIClient;

class AIClientTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		// Ensure circuit starts closed and model option is cleared.
		AIClient::reset_circuit();
		delete_option( AIClient::MODEL_OPTION );
	}

	// -----------------------------------------------------------------------
	// Circuit breaker
	// -----------------------------------------------------------------------

	public function test_circuit_starts_closed(): void {
		$this->assertFalse( AIClient::is_circuit_open() );
	}

	public function test_reset_circuit_closes_open_circuit(): void {
		// Force circuit open by writing state directly.
		update_option( 'pearblog_ai_circuit_state', [
			'failures'    => 10,
			'open'        => true,
			'retry_after' => time() + 300,
		] );

		$this->assertTrue( AIClient::is_circuit_open() );

		AIClient::reset_circuit();

		$this->assertFalse( AIClient::is_circuit_open() );
	}

	public function test_circuit_auto_closes_after_cooldown(): void {
		// Set retry_after in the past.
		update_option( 'pearblog_ai_circuit_state', [
			'failures'    => 10,
			'open'        => true,
			'retry_after' => time() - 1, // Already expired.
		] );

		// Should auto-close (transition to half-open).
		$this->assertFalse( AIClient::is_circuit_open() );
	}

	// -----------------------------------------------------------------------
	// Cost tracking
	// -----------------------------------------------------------------------

	public function test_cost_starts_at_zero_after_reset(): void {
		AIClient::reset_cost();
		$this->assertSame( 0.0, AIClient::get_total_cost_cents() );
	}

	public function test_cost_accumulates(): void {
		AIClient::reset_cost();

		update_option( 'pearblog_ai_cost_cents', 5.0 );
		update_option( 'pearblog_ai_cost_cents', (float) get_option( 'pearblog_ai_cost_cents', 0 ) + 3.2 );

		$this->assertEqualsWithDelta( 8.2, AIClient::get_total_cost_cents(), 0.001 );
	}

	// -----------------------------------------------------------------------
	// API key validation
	// -----------------------------------------------------------------------

	public function test_missing_api_key_throws_runtime_exception(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/API key is not configured/i' );

		// Inject an OpenAIProvider with an empty key so the check fires immediately.
		$provider = new \PearBlogEngine\AI\OpenAIProvider( '' );
		$client   = new AIClient( '', '', $provider );
		$client->generate( 'test prompt' );
	}

	// -----------------------------------------------------------------------
	// Model selection — get_model() / get_available_models() (OpenAI default)
	// -----------------------------------------------------------------------

	public function test_default_model_is_gpt4o_mini_when_option_not_set(): void {
		// Default provider is OpenAI; default model is gpt-4o-mini.
		$this->assertSame( AIClient::DEFAULT_MODEL, AIClient::get_model() );
	}

	public function test_get_model_returns_stored_option_when_valid(): void {
		update_option( AIClient::MODEL_OPTION, 'gpt-4o' );
		$this->assertSame( 'gpt-4o', AIClient::get_model() );
	}

	public function test_get_model_falls_back_to_default_for_invalid_slug(): void {
		update_option( AIClient::MODEL_OPTION, 'gpt-99-ultra-fake' );
		$this->assertSame( AIClient::DEFAULT_MODEL, AIClient::get_model() );
	}

	public function test_get_available_models_returns_openai_models_by_default(): void {
		$models = AIClient::get_available_models();

		$this->assertArrayHasKey( 'gpt-4o',        $models );
		$this->assertArrayHasKey( 'gpt-4o-mini',   $models );
		$this->assertArrayHasKey( 'gpt-4-turbo',   $models );
		$this->assertArrayHasKey( 'gpt-3.5-turbo', $models );
		$this->assertCount( 4, $models );
	}

	public function test_get_available_models_returns_anthropic_models_when_provider_set(): void {
		update_option( \PearBlogEngine\AI\AIProviderFactory::PROVIDER_OPTION, 'anthropic' );
		$models = AIClient::get_available_models();
		$this->assertArrayHasKey( 'claude-3-5-sonnet-20241022', $models );
	}

	public function test_get_available_models_returns_gemini_models_when_provider_set(): void {
		update_option( \PearBlogEngine\AI\AIProviderFactory::PROVIDER_OPTION, 'gemini' );
		$models = AIClient::get_available_models();
		$this->assertArrayHasKey( 'gemini-1.5-pro', $models );
	}

	public function test_each_model_has_required_keys(): void {
		foreach ( AIClient::get_available_models() as $slug => $meta ) {
			$this->assertArrayHasKey( 'label',                    $meta, "Model {$slug} missing 'label'" );
			$this->assertArrayHasKey( 'max_tokens',               $meta, "Model {$slug} missing 'max_tokens'" );
			$this->assertArrayHasKey( 'cost_per_1k_input_cents',  $meta, "Model {$slug} missing input cost" );
			$this->assertArrayHasKey( 'cost_per_1k_output_cents', $meta, "Model {$slug} missing output cost" );
		}
	}

	public function test_gpt4o_is_more_expensive_than_gpt4o_mini(): void {
		$models = AIClient::get_available_models();
		$this->assertGreaterThan(
			$models['gpt-4o-mini']['cost_per_1k_input_cents'],
			$models['gpt-4o']['cost_per_1k_input_cents']
		);
	}

	public function test_default_model_is_present_in_available_models(): void {
		$this->assertArrayHasKey( AIClient::DEFAULT_MODEL, AIClient::get_available_models() );
	}

	// -----------------------------------------------------------------------
	// estimate_cost_cents()
	// -----------------------------------------------------------------------

	public function test_estimate_cost_cents_returns_zero_for_zero_tokens(): void {
		$this->assertSame( 0.0, AIClient::estimate_cost_cents( 0, 'gpt-4o-mini' ) );
	}

	public function test_estimate_cost_cents_gpt4o_mini_1000_tokens(): void {
		// gpt-4o-mini: input=0.0015, output=0.006, blended=0.0015*0.4+0.006*0.6=0.0042
		$expected = 0.0042;
		$actual   = AIClient::estimate_cost_cents( 1000, 'gpt-4o-mini' );
		$this->assertEqualsWithDelta( $expected, $actual, 0.00001 );
	}

	public function test_estimate_cost_cents_gpt4o_1000_tokens(): void {
		// gpt-4o: input=0.025, output=0.100, blended=0.025*0.4+0.100*0.6=0.07
		$expected = 0.07;
		$actual   = AIClient::estimate_cost_cents( 1000, 'gpt-4o' );
		$this->assertEqualsWithDelta( $expected, $actual, 0.00001 );
	}

	public function test_estimate_cost_cents_uses_active_model_when_no_slug_given(): void {
		// Both models must be from OpenAI (active provider) for this comparison.
		update_option( \PearBlogEngine\AI\AIProviderFactory::PROVIDER_OPTION, 'openai' );
		update_option( AIClient::MODEL_OPTION, 'gpt-4o-mini' );
		$cost_mini = AIClient::estimate_cost_cents( 1000 );

		update_option( AIClient::MODEL_OPTION, 'gpt-4o' );
		$cost_4o = AIClient::estimate_cost_cents( 1000 );

		$this->assertGreaterThan( $cost_mini, $cost_4o );
	}

	public function test_estimate_cost_cents_falls_back_for_invalid_model(): void {
		// Should not throw; falls back to the active/default model.
		$cost = AIClient::estimate_cost_cents( 1000, 'nonexistent-model' );
		$this->assertGreaterThan( 0.0, $cost );
	}

	// -----------------------------------------------------------------------
	// Constructor — provider injection
	// -----------------------------------------------------------------------

	public function test_constructor_accepts_model_override(): void {
		$client = new AIClient( 'sk-test', 'gpt-4o' );
		$this->assertInstanceOf( AIClient::class, $client );
	}

	public function test_constructor_accepts_provider_injection(): void {
		$provider = new \PearBlogEngine\AI\OpenAIProvider( 'sk-test', 'gpt-4o' );
		$client   = new AIClient( '', '', $provider );
		$this->assertInstanceOf( AIClient::class, $client );
	}

	public function test_generate_uses_injected_provider(): void {
		// Stub provider returns controlled content without HTTP.
		$stub = new class implements \PearBlogEngine\AI\AIProviderInterface {
			public function complete( string $prompt, int $max_tokens ): array {
				return [ 'content' => 'stub output', 'prompt_tokens' => 10, 'completion_tokens' => 20 ];
			}
			public static function get_slug(): string          { return 'stub'; }
			public static function get_label(): string         { return 'Stub'; }
			public static function get_api_key_option(): string { return 'pearblog_stub_api_key'; }
			public static function get_models(): array         { return []; }
			public static function get_default_model(): string { return ''; }
		};

		$client = new AIClient( '', '', $stub );
		$result = $client->generate( 'hello' );
		$this->assertSame( 'stub output', $result );
	}
}
