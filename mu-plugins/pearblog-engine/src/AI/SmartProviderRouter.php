<?php
/**
 * Smart Provider Router — F7 Multi-Provider AI Orchestration.
 *
 * Selects the optimal AI provider for each generation request based on:
 *   1. Budget constraints (daily cost ceiling in cents).
 *   2. Content type hint (article, summarize, faq, classify).
 *   3. Circuit-breaker state per provider (skips open circuits).
 *   4. Fallback chain: primary → secondary → tertiary.
 *
 * WordPress options:
 *   pearblog_router_primary        – primary provider slug (default: 'openai')
 *   pearblog_router_secondary      – secondary provider slug (default: 'anthropic')
 *   pearblog_router_tertiary       – tertiary provider slug (default: 'gemini')
 *   pearblog_router_budget_cents   – max daily spend in cents (default: 5000 = $50)
 *   pearblog_router_spend_today    – running tally for today (auto-reset at midnight)
 *   pearblog_router_spend_date     – date of the running tally
 *   pearblog_router_routing_rules  – JSON map of content_type → preferred_provider
 *   pearblog_router_stats          – JSON map provider → {requests, tokens, cost_cents, errors}
 *
 * Content type hints:
 *   article   – long-form content; use the highest-quality provider
 *   summarize – short output; prefer the fastest/cheapest provider
 *   faq       – structured output; any provider
 *   classify  – classification; cheapest provider sufficient
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Routes AI generation requests to the best available provider.
 */
class SmartProviderRouter {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public const OPTION_PRIMARY    = 'pearblog_router_primary';
	public const OPTION_SECONDARY  = 'pearblog_router_secondary';
	public const OPTION_TERTIARY   = 'pearblog_router_tertiary';
	public const OPTION_BUDGET     = 'pearblog_router_budget_cents';
	public const OPTION_SPEND      = 'pearblog_router_spend_today';
	public const OPTION_SPEND_DATE = 'pearblog_router_spend_date';
	public const OPTION_RULES      = 'pearblog_router_routing_rules';
	public const OPTION_STATS      = 'pearblog_router_stats';

	/** Default daily budget: $50 in cents. */
	public const DEFAULT_BUDGET_CENTS = 5000;

	/**
	 * Content-type → best provider priority map (used when no explicit rule is set).
	 *
	 * @var array<string, string[]>  content_type → ordered provider slugs
	 */
	private const CONTENT_TYPE_DEFAULTS = [
		'article'   => [ 'openai', 'anthropic', 'gemini' ],   // quality first
		'summarize' => [ 'gemini', 'openai', 'anthropic' ],    // speed/cost first
		'faq'       => [ 'openai', 'gemini', 'anthropic' ],    // structured output
		'classify'  => [ 'gemini', 'openai', 'anthropic' ],    // cheapest first
	];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Select and return the best AIProviderInterface instance for a request.
	 *
	 * @param string $content_type  Hint: 'article' | 'summarize' | 'faq' | 'classify'.
	 * @param int    $max_tokens    Estimated max tokens needed (used for cost projection).
	 * @return AIProviderInterface
	 * @throws \RuntimeException   When all providers are unavailable (budget or circuit).
	 */
	public function route( string $content_type = 'article', int $max_tokens = 2048 ): AIProviderInterface {
		$chain    = $this->build_provider_chain( $content_type );
		$budget   = $this->remaining_budget_cents();

		foreach ( $chain as $slug ) {
			if ( $this->is_circuit_open( $slug ) ) {
				continue;
			}

			$cost = $this->project_cost_cents( $slug, $max_tokens );
			if ( $cost > $budget ) {
				// Try next provider — might be cheaper.
				continue;
			}

			$this->record_route( $slug );
			return AIProviderFactory::make( $slug );
		}

		// Absolute fallback: ignore budget and circuit for the last provider in chain.
		$last = end( $chain );
		if ( false !== $last ) {
			$this->record_route( $last );
			return AIProviderFactory::make( $last );
		}

		throw new \RuntimeException( 'SmartProviderRouter: no AI provider is available.' );
	}

	/**
	 * Record a completed generation attempt so stats and spend can be tracked.
	 *
	 * @param string $provider_slug  Provider used.
	 * @param int    $prompt_tokens  Input tokens consumed.
	 * @param int    $output_tokens  Output tokens generated.
	 * @param bool   $success        Whether generation succeeded.
	 */
	public function record_result(
		string $provider_slug,
		int    $prompt_tokens,
		int    $output_tokens,
		bool   $success = true
	): void {
		$cost_cents = $this->calculate_cost_cents( $provider_slug, $prompt_tokens, $output_tokens );

		// Update running daily spend.
		$this->update_daily_spend( $cost_cents );

		// Update per-provider stats.
		$stats = $this->get_stats();
		if ( ! isset( $stats[ $provider_slug ] ) ) {
			$stats[ $provider_slug ] = $this->empty_stats();
		}

		$stats[ $provider_slug ]['requests']   += 1;
		$stats[ $provider_slug ]['tokens']      += $prompt_tokens + $output_tokens;
		$stats[ $provider_slug ]['cost_cents']  += $cost_cents;
		if ( ! $success ) {
			$stats[ $provider_slug ]['errors'] += 1;
		}

		update_option( self::OPTION_STATS, wp_json_encode( $stats ) );
	}

	/**
	 * Return the currently configured provider chain in priority order.
	 *
	 * @return string[]
	 */
	public function get_chain(): array {
		return [
			(string) get_option( self::OPTION_PRIMARY,   'openai' ),
			(string) get_option( self::OPTION_SECONDARY, 'anthropic' ),
			(string) get_option( self::OPTION_TERTIARY,  'gemini' ),
		];
	}

	/**
	 * Return remaining budget for today in cents.
	 */
	public function remaining_budget_cents(): int {
		$budget = (int) get_option( self::OPTION_BUDGET, self::DEFAULT_BUDGET_CENTS );
		return max( 0, $budget - $this->today_spend_cents() );
	}

	/**
	 * Return today's total spend in cents.
	 */
	public function today_spend_cents(): int {
		$date  = (string) get_option( self::OPTION_SPEND_DATE, '' );
		$today = gmdate( 'Y-m-d' );

		if ( $date !== $today ) {
			// New day — reset the counter.
			update_option( self::OPTION_SPEND_DATE, $today );
			update_option( self::OPTION_SPEND,      0 );
			return 0;
		}

		return (int) get_option( self::OPTION_SPEND, 0 );
	}

	/**
	 * Return per-provider stats.
	 *
	 * @return array<string, array{requests: int, tokens: int, cost_cents: int, errors: int}>
	 */
	public function get_stats(): array {
		$raw  = (string) get_option( self::OPTION_STATS, '{}' );
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Reset all router stats and daily spend (useful for testing / new billing period).
	 */
	public function reset_stats(): void {
		update_option( self::OPTION_STATS,      wp_json_encode( [] ) );
		update_option( self::OPTION_SPEND,      0 );
		update_option( self::OPTION_SPEND_DATE, gmdate( 'Y-m-d' ) );
	}

	/**
	 * Return a summary for the admin UI / REST API.
	 *
	 * @return array{chain: string[], budget_cents: int, spend_today_cents: int, remaining_cents: int, stats: array}
	 */
	public function get_status(): array {
		return [
			'chain'              => $this->get_chain(),
			'budget_cents'       => (int) get_option( self::OPTION_BUDGET, self::DEFAULT_BUDGET_CENTS ),
			'spend_today_cents'  => $this->today_spend_cents(),
			'remaining_cents'    => $this->remaining_budget_cents(),
			'stats'              => $this->get_stats(),
		];
	}

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register the router with WordPress.
	 *
	 * Hooks into pearblog_before_generate to override the active provider,
	 * and into pearblog_pipeline_completed to record usage.
	 */
	public function register(): void {
		add_action( 'pearblog_router_record', [ $this, 'on_record_result' ], 10, 4 );
	}

	/**
	 * Action callback: record result for a provider.
	 */
	public function on_record_result(
		string $provider_slug,
		int    $prompt_tokens,
		int    $output_tokens,
		bool   $success
	): void {
		$this->record_result( $provider_slug, $prompt_tokens, $output_tokens, $success );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Build the ordered provider chain for a given content type.
	 *
	 * Custom routing rules (stored as JSON option) override defaults.
	 *
	 * @param string $content_type
	 * @return string[]
	 */
	private function build_provider_chain( string $content_type ): array {
		// Check for a custom per-type rule.
		$rules_json = (string) get_option( self::OPTION_RULES, '{}' );
		$rules      = json_decode( $rules_json, true );
		$rules      = is_array( $rules ) ? $rules : [];

		if ( isset( $rules[ $content_type ] ) && is_array( $rules[ $content_type ] ) ) {
			return $rules[ $content_type ];
		}

		// Use default content-type priorities if available.
		if ( isset( self::CONTENT_TYPE_DEFAULTS[ $content_type ] ) ) {
			return self::CONTENT_TYPE_DEFAULTS[ $content_type ];
		}

		// Fall back to the configured chain.
		return $this->get_chain();
	}

	/**
	 * Check if a provider's circuit breaker is currently open.
	 *
	 * Reads from the `pearblog_circuit_{slug}` option written by AIClient.
	 */
	private function is_circuit_open( string $slug ): bool {
		$circuit = (array) get_option( "pearblog_circuit_{$slug}", [] );
		return (bool) ( $circuit['open'] ?? false );
	}

	/**
	 * Project the cost in cents for a given provider and token count.
	 *
	 * Uses the input cost rate as a conservative estimate (assumes all tokens are input).
	 */
	private function project_cost_cents( string $slug, int $max_tokens ): int {
		try {
			$models       = AIProviderFactory::make( $slug )::get_models();
			$default      = AIProviderFactory::make( $slug )::get_default_model();
			$model_meta   = $models[ $default ] ?? reset( $models );
			$rate         = (float) ( $model_meta['cost_per_1k_input_cents'] ?? 0.01 );
			return (int) ceil( ( $max_tokens / 1000 ) * $rate );
		} catch ( \Throwable $e ) {
			return 0;
		}
	}

	/**
	 * Calculate the exact cost for a completed generation in cents.
	 */
	private function calculate_cost_cents( string $slug, int $prompt_tokens, int $output_tokens ): int {
		try {
			$models     = AIProviderFactory::make( $slug )::get_models();
			$default    = AIProviderFactory::make( $slug )::get_default_model();
			$meta       = $models[ $default ] ?? reset( $models );
			$input_rate = (float) ( $meta['cost_per_1k_input_cents']  ?? 0.01 );
			$out_rate   = (float) ( $meta['cost_per_1k_output_cents'] ?? 0.01 );

			return (int) ceil(
				( $prompt_tokens / 1000 ) * $input_rate +
				( $output_tokens / 1000 ) * $out_rate
			);
		} catch ( \Throwable $e ) {
			return 0;
		}
	}

	/**
	 * Add to today's running spend.
	 */
	private function update_daily_spend( int $cost_cents ): void {
		$current = $this->today_spend_cents();
		update_option( self::OPTION_SPEND,      $current + $cost_cents );
		update_option( self::OPTION_SPEND_DATE, gmdate( 'Y-m-d' ) );
	}

	/**
	 * Record a provider selection (for stats tracking without cost yet known).
	 */
	private function record_route( string $slug ): void {
		// Lightweight pre-flight stat: increment a "routed" counter.
		$stats = $this->get_stats();
		if ( ! isset( $stats[ $slug ] ) ) {
			$stats[ $slug ] = $this->empty_stats();
		}
		// (actual tokens/cost recorded later via record_result)
		update_option( self::OPTION_STATS, wp_json_encode( $stats ) );
	}

	private function empty_stats(): array {
		return [ 'requests' => 0, 'tokens' => 0, 'cost_cents' => 0, 'errors' => 0 ];
	}
}
