<?php
/**
 * AI client – orchestrates text generation across pluggable AI providers.
 *
 * Features:
 *  - Multi-provider support: OpenAI, Anthropic Claude, Google Gemini.
 *    Active provider stored in `pearblog_ai_provider` option.
 *  - Configurable model per provider; stored in `pearblog_ai_model` option.
 *  - Exponential backoff with jitter on rate-limit responses.
 *  - Circuit breaker: after N consecutive failures the client refuses
 *    further calls for a configurable cooldown period.
 *  - Per-article cost tracking stored in a WordPress option.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Provider-agnostic AI client with resilience features.
 *
 * The underlying HTTP call is delegated to an AIProviderInterface instance
 * created by AIProviderFactory.  All retry, circuit-breaker, and cost-tracking
 * logic lives here, keeping providers thin and testable.
 */
class AIClient {

/**
 * Fallback model used when the option is not set or is invalid.
 * Points to the OpenAI default for backward compatibility.
 */
public const DEFAULT_MODEL = 'gpt-4o-mini';

/**
 * Option key that stores the currently selected model slug.
 */
public const MODEL_OPTION = 'pearblog_ai_model';

/**
 * OpenAI models map — kept for backward compatibility with code that
 * accesses AIClient::MODELS directly.
 *
 * @deprecated Use AIProviderFactory::get_all_models() or
 *             AIProviderFactory::get_active_provider_models() instead.
 *
 * @var array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>
 */
public const MODELS = [
'gpt-4o' => [
'label'                    => 'GPT-4o (best quality)',
'max_tokens'               => 4096,
'cost_per_1k_input_cents'  => 0.025,
'cost_per_1k_output_cents' => 0.100,
],
'gpt-4o-mini' => [
'label'                    => 'GPT-4o mini (fast & cheap)',
'max_tokens'               => 4096,
'cost_per_1k_input_cents'  => 0.0015,
'cost_per_1k_output_cents' => 0.006,
],
'gpt-4-turbo' => [
'label'                    => 'GPT-4 Turbo (high quality)',
'max_tokens'               => 4096,
'cost_per_1k_input_cents'  => 0.100,
'cost_per_1k_output_cents' => 0.300,
],
'gpt-3.5-turbo' => [
'label'                    => 'GPT-3.5 Turbo (lowest cost)',
'max_tokens'               => 4096,
'cost_per_1k_input_cents'  => 0.005,
'cost_per_1k_output_cents' => 0.015,
],
];

/** Maximum retry attempts on rate-limit responses. */
private const MAX_RETRIES = 3;

/** Base delay in seconds for exponential backoff. */
private const BASE_DELAY_SECONDS = 2;

/** Number of consecutive failures before the circuit opens. */
private const CIRCUIT_FAILURE_THRESHOLD = 5;

/** Seconds to keep the circuit open (cooldown period). */
private const CIRCUIT_COOLDOWN_SECONDS = 300;

/** WordPress option key for circuit-breaker state. */
private const CB_STATE_OPTION = 'pearblog_ai_circuit_state';

/** WordPress option key for cumulative API cost tracking (USD cents). */
private const COST_OPTION = 'pearblog_ai_cost_cents';

/** @var AIProviderInterface */
private AIProviderInterface $provider;

/**
 * @param string                  $api_key  API key override; if empty each provider reads its own option.
 * @param string                  $model    Model slug override; if empty reads from WP option.
 * @param AIProviderInterface|null $provider Provider override (useful for testing).
 */
public function __construct(
string $api_key = '',
string $model   = '',
?AIProviderInterface $provider = null
) {
if ( null !== $provider ) {
$this->provider = $provider;
} else {
$this->provider = AIProviderFactory::make( '', $api_key, $model );
}
}

// -----------------------------------------------------------------------
// Model / provider helpers (static, usable without an instance)
// -----------------------------------------------------------------------

/**
 * Return the currently active model slug for the active provider.
 * Validates against the active provider's model list; falls back to that
 * provider's default when the stored value is absent or invalid.
 */
public static function get_model(): string {
$stored   = (string) get_option( self::MODEL_OPTION, '' );
$models   = AIProviderFactory::get_active_provider_models();
return isset( $models[ $stored ] ) ? $stored : AIProviderFactory::get_active_provider_default_model();
}

/**
 * Return the model metadata map for the currently active provider.
 *
 * @return array<string, array{label: string, max_tokens: int, cost_per_1k_input_cents: float, cost_per_1k_output_cents: float}>
 */
public static function get_available_models(): array {
return AIProviderFactory::get_active_provider_models();
}

/**
 * Calculate estimated cost in USD cents for a given number of tokens.
 *
 * @param int    $total_tokens Total tokens (input + output combined).
 * @param string $model        Model slug; defaults to the active model.
 * @return float               Estimated cost in USD cents.
 */
public static function estimate_cost_cents( int $total_tokens, string $model = '' ): float {
// Check all providers' model lists.
if ( '' === $model ) {
$model = self::get_model();
}

$meta = null;
foreach ( AIProviderFactory::get_all_models() as $provider_models ) {
if ( isset( $provider_models[ $model ] ) ) {
$meta = $provider_models[ $model ];
break;
}
}

if ( null === $meta ) {
// Unknown model — fall back to active model.
$fallback = self::get_model();
foreach ( AIProviderFactory::get_all_models() as $provider_models ) {
if ( isset( $provider_models[ $fallback ] ) ) {
$meta = $provider_models[ $fallback ];
break;
}
}
}

if ( null === $meta ) {
return 0.0;
}

// Blended: 40 % input, 60 % output.
$blended_rate = ( $meta['cost_per_1k_input_cents'] * 0.4 ) + ( $meta['cost_per_1k_output_cents'] * 0.6 );
return ( $total_tokens / 1000.0 ) * $blended_rate;
}

/**
 * Send a prompt and return the AI-generated text.
 *
 * @param string $prompt     The full prompt to send.
 * @param int    $max_tokens Maximum tokens in the response (default 2 048).
 * @return string            Generated content.
 * @throws \RuntimeException When the request fails or the circuit is open.
 */
public function generate( string $prompt, int $max_tokens = 2048 ): string {
$this->assert_circuit_closed();

$last_exception = null;

for ( $attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++ ) {
try {
$result = $this->do_request( $prompt, $max_tokens );
$this->record_success();
return $result;
} catch ( RateLimitException $e ) {
$last_exception = $e;
if ( $attempt < self::MAX_RETRIES ) {
$this->backoff( $attempt );
continue;
}
$this->record_failure();
throw new \RuntimeException(
'PearBlog Engine: AI rate limit exceeded after ' . self::MAX_RETRIES . ' retries.',
429,
$e
);
} catch ( \Throwable $e ) {
$this->record_failure();
throw $e;
}
}

$this->record_failure();
throw $last_exception ?? new \RuntimeException( 'PearBlog Engine: AI generation failed.' );
}

// -----------------------------------------------------------------------
// Cost tracking
// -----------------------------------------------------------------------

/**
 * Return total estimated API spend in USD cents since tracking began.
 */
public static function get_total_cost_cents(): float {
return (float) get_option( self::COST_OPTION, 0 );
}

/**
 * Reset the cumulative cost counter to zero.
 */
public static function reset_cost(): void {
update_option( self::COST_OPTION, 0 );
}

// -----------------------------------------------------------------------
// Circuit breaker helpers (public for testing / admin reset)
// -----------------------------------------------------------------------

/**
 * Return whether the circuit breaker is currently open (blocking calls).
 */
public static function is_circuit_open(): bool {
$state = self::get_circuit_state();
if ( $state['open'] && time() >= $state['retry_after'] ) {
self::reset_circuit();
return false;
}
return $state['open'];
}

/**
 * Manually reset the circuit breaker (e.g. from an admin action).
 */
public static function reset_circuit(): void {
update_option( self::CB_STATE_OPTION, [ 'failures' => 0, 'open' => false, 'retry_after' => 0 ] );
}

// -----------------------------------------------------------------------
// Private implementation
// -----------------------------------------------------------------------

/**
 * Delegate to the provider, then record cost.
 *
 * @throws RateLimitException  Bubbled from the provider.
 * @throws \RuntimeException   Bubbled from the provider.
 */
private function do_request( string $prompt, int $max_tokens ): string {
$result = $this->provider->complete( $prompt, $max_tokens );

$prompt_tokens     = (int) ( $result['prompt_tokens']     ?? 0 );
$completion_tokens = (int) ( $result['completion_tokens'] ?? 0 );

if ( $prompt_tokens > 0 || $completion_tokens > 0 ) {
$this->record_cost( $prompt_tokens, $completion_tokens );
}

return (string) ( $result['content'] ?? '' );
}

/**
 * Accumulate token costs using the active provider's model rates.
 */
private function record_cost( int $prompt_tokens, int $completion_tokens ): void {
$model_slug = self::get_model();
$meta       = null;

foreach ( AIProviderFactory::get_all_models() as $provider_models ) {
if ( isset( $provider_models[ $model_slug ] ) ) {
$meta = $provider_models[ $model_slug ];
break;
}
}

if ( null === $meta ) {
return;
}

$cost_cents = ( $prompt_tokens / 1000.0 ) * $meta['cost_per_1k_input_cents']
            + ( $completion_tokens / 1000.0 ) * $meta['cost_per_1k_output_cents'];
$existing   = (float) get_option( self::COST_OPTION, 0 );
update_option( self::COST_OPTION, $existing + $cost_cents );
}

/**
 * Sleep using exponential backoff with full jitter.
 */
private function backoff( int $attempt ): void {
$max_delay = self::BASE_DELAY_SECONDS * ( 2 ** $attempt );
$delay     = random_int( 1, max( 1, (int) $max_delay ) );
error_log( "PearBlog Engine: rate limited – retrying in {$delay}s (attempt " . ( $attempt + 1 ) . ').' );
sleep( $delay );
}

/**
 * Throw if the circuit breaker is open.
 */
private function assert_circuit_closed(): void {
if ( self::is_circuit_open() ) {
$state = self::get_circuit_state();
$eta   = max( 0, $state['retry_after'] - time() );
throw new \RuntimeException(
"PearBlog Engine: AI circuit breaker is OPEN. Retry in {$eta}s."
);
}
}

/**
 * Record a successful API call (resets consecutive failure count).
 */
private function record_success(): void {
$state              = self::get_circuit_state();
$state['failures']  = 0;
$state['open']      = false;
$state['retry_after'] = 0;
update_option( self::CB_STATE_OPTION, $state );
}

/**
 * Record a failed API call.  Opens the circuit after too many failures.
 */
private function record_failure(): void {
$state = self::get_circuit_state();
$state['failures']++;

if ( $state['failures'] >= self::CIRCUIT_FAILURE_THRESHOLD ) {
$state['open']        = true;
$state['retry_after'] = time() + self::CIRCUIT_COOLDOWN_SECONDS;
error_log( sprintf(
'PearBlog Engine: AI circuit breaker OPENED after %d failures. Will retry at %s.',
$state['failures'],
gmdate( 'Y-m-d H:i:s', $state['retry_after'] )
) );
}

update_option( self::CB_STATE_OPTION, $state );
}

/**
 * Read circuit-breaker state from the database.
 *
 * @return array{failures: int, open: bool, retry_after: int}
 */
private static function get_circuit_state(): array {
$default = [ 'failures' => 0, 'open' => false, 'retry_after' => 0 ];
$stored  = get_option( self::CB_STATE_OPTION, $default );
return is_array( $stored ) ? array_merge( $default, $stored ) : $default;
}
}
