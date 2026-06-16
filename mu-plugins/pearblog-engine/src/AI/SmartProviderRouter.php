<?php
/**
 * Smart Provider Router — V9.0 F7
 *
 * Intelligent routing layer that automatically selects the best AI provider
 * (OpenAI, Anthropic, Gemini) based on content type, cost optimisation,
 * real-time availability, and historical performance scores. Provides
 * transparent failover so that callers never need to handle provider-specific
 * exceptions.
 *
 * @package PearBlogEngine\AI
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Routes AI completion requests to the optimal provider.
 */
class SmartProviderRouter {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** Supported content-type hints for routing decisions. */
	public const CONTENT_LONG_FORM   = 'long_form';
	public const CONTENT_SHORT_FORM  = 'short_form';
	public const CONTENT_CODE        = 'code';
	public const CONTENT_CREATIVE    = 'creative';
	public const CONTENT_FACTUAL     = 'factual';
	public const CONTENT_TRANSLATION = 'translation';

	/** Option key: global router enable flag. */
	public const OPT_ENABLED = 'pearblog_smart_router_enabled';

	/** Option key: JSON performance stats per provider. */
	public const OPT_STATS = 'pearblog_smart_router_stats';

	/** Option key: cost budget (USD cents) per day. */
	public const OPT_DAILY_BUDGET_CENTS = 'pearblog_smart_router_daily_budget';

	/** Option key: today's accumulated cost (USD cents). */
	public const OPT_TODAY_COST = 'pearblog_smart_router_today_cost';

	/** Option key: date string of today's cost accumulation. */
	public const OPT_TODAY_DATE = 'pearblog_smart_router_today_date';

	/** Minimum success-rate threshold; providers below this are sidelined. */
	public const MIN_SUCCESS_RATE = 0.80;

	/**
	 * Default routing preference per content type:
	 * content_type => ordered list of provider slugs (best first).
	 *
	 * @var array<string, string[]>
	 */
	private const ROUTING_MAP = [
		self::CONTENT_LONG_FORM   => [ 'anthropic', 'openai', 'gemini' ],
		self::CONTENT_SHORT_FORM  => [ 'openai',    'gemini', 'anthropic' ],
		self::CONTENT_CODE        => [ 'openai',    'anthropic', 'gemini' ],
		self::CONTENT_CREATIVE    => [ 'anthropic', 'openai', 'gemini' ],
		self::CONTENT_FACTUAL     => [ 'gemini',    'openai', 'anthropic' ],
		self::CONTENT_TRANSLATION => [ 'gemini',    'openai', 'anthropic' ],
	];

	/** Cost weights per provider (lower is cheaper). Relative scale only. */
	private const COST_WEIGHT = [
		'openai'    => 1.0,
		'anthropic' => 1.2,
		'gemini'    => 0.7,
	];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Route a completion request to the best available provider and return its result.
	 *
	 * @param  string $prompt       Prompt text.
	 * @param  int    $max_tokens   Maximum output tokens.
	 * @param  string $content_type One of the CONTENT_* constants.
	 * @param  bool   $cost_aware   When true, factor in cost optimisation.
	 * @return array{
	 *     content: string,
	 *     provider: string,
	 *     prompt_tokens: int,
	 *     completion_tokens: int,
	 *     cost_cents: float,
	 *     fallback_used: bool,
	 * }
	 * @throws \RuntimeException When no provider is available.
	 */
	public function route(
		string $prompt,
		int $max_tokens     = 1000,
		string $content_type = self::CONTENT_SHORT_FORM,
		bool $cost_aware    = true
	): array {
		$ordered  = $this->get_ordered_providers( $content_type, $cost_aware );
		$last_exc = null;
		$fallback = false;

		foreach ( $ordered as $idx => $slug ) {
			try {
				$provider = AIProviderFactory::make( $slug );
				$raw      = $provider->complete( $prompt, $max_tokens );

				$cost = $this->estimate_cost_cents(
					$slug,
					$raw['prompt_tokens']     ?? 0,
					$raw['completion_tokens'] ?? 0
				);

				$this->record_success( $slug, $raw['prompt_tokens'] ?? 0, $raw['completion_tokens'] ?? 0, $cost );
				$this->accumulate_daily_cost( $cost );

				return [
					'content'           => $raw['content']            ?? '',
					'provider'          => $slug,
					'prompt_tokens'     => $raw['prompt_tokens']      ?? 0,
					'completion_tokens' => $raw['completion_tokens']  ?? 0,
					'cost_cents'        => $cost,
					'fallback_used'     => $idx > 0 || $fallback,
				];
			} catch ( \Throwable $e ) {
				$this->record_failure( $slug );
				$last_exc = $e;
				$fallback = true;
			}
		}

		throw new \RuntimeException(
			'SmartProviderRouter: all providers failed. Last error: ' . ( $last_exc?->getMessage() ?? 'unknown' )
		);
	}

	/**
	 * Return the provider ranking for a given content type.
	 *
	 * @param  string $content_type One of the CONTENT_* constants.
	 * @param  bool   $cost_aware   Factor cost into ranking.
	 * @return string[] Ordered provider slugs (best first).
	 */
	public function get_ordered_providers( string $content_type, bool $cost_aware = true ): array {
		$base  = self::ROUTING_MAP[ $content_type ] ?? self::ROUTING_MAP[ self::CONTENT_SHORT_FORM ];
		$stats = $this->load_stats();

		// Score each provider: base position + success rate bonus − cost penalty.
		$scored = [];
		foreach ( $base as $pos => $slug ) {
			$s           = $stats[ $slug ] ?? [];
			$total       = ( $s['successes'] ?? 0 ) + ( $s['failures'] ?? 0 );
			$success_rate = $total > 0 ? ( $s['successes'] ?? 0 ) / $total : 1.0;

			// Sideline providers below the minimum success rate.
			if ( $total > 5 && $success_rate < self::MIN_SUCCESS_RATE ) {
				continue;
			}

			$score  = ( count( $base ) - $pos ) * 10;        // base position weight
			$score += $success_rate * 5;                       // reliability bonus
			if ( $cost_aware ) {
				$score -= self::COST_WEIGHT[ $slug ] ?? 1.0;  // cost penalty
			}

			$scored[ $slug ] = $score;
		}

		if ( empty( $scored ) ) {
			// Fallback: return original order without sidelining.
			return $base;
		}

		arsort( $scored );
		return array_keys( $scored );
	}

	/**
	 * Return raw performance statistics for all providers.
	 *
	 * @return array<string, array{successes: int, failures: int, total_tokens: int, total_cost_cents: float, avg_response_ms: float}>
	 */
	public function get_stats(): array {
		return $this->load_stats();
	}

	/**
	 * Reset performance statistics.
	 */
	public function reset_stats(): void {
		update_option( self::OPT_STATS, wp_json_encode( [] ) );
	}

	/**
	 * Return today's accumulated cost in USD cents.
	 */
	public function get_today_cost(): float {
		$this->maybe_reset_daily_cost();
		return (float) get_option( self::OPT_TODAY_COST, 0.0 );
	}

	/**
	 * Return the daily budget in USD cents.
	 */
	public function get_daily_budget(): float {
		return (float) get_option( self::OPT_DAILY_BUDGET_CENTS, 500.0 );
	}

	/**
	 * Check whether the daily budget has been exhausted.
	 */
	public function is_budget_exhausted(): bool {
		$budget = $this->get_daily_budget();
		return $budget > 0.0 && $this->get_today_cost() >= $budget;
	}

	/**
	 * Estimate the cost in USD cents for a completed request.
	 *
	 * Uses fixed per-1k-token rates for each provider.
	 *
	 * @param  string $slug             Provider slug.
	 * @param  int    $prompt_tokens    Input token count.
	 * @param  int    $completion_tokens Output token count.
	 * @return float Cost in USD cents.
	 */
	public function estimate_cost_cents( string $slug, int $prompt_tokens, int $completion_tokens ): float {
		// Rates in USD cents per 1 000 tokens (input / output).
		$rates = [
			'openai'    => [ 'in' => 0.5,  'out' => 1.5  ],
			'anthropic' => [ 'in' => 0.8,  'out' => 2.4  ],
			'gemini'    => [ 'in' => 0.35, 'out' => 1.05 ],
		];

		$r = $rates[ $slug ] ?? [ 'in' => 1.0, 'out' => 2.0 ];
		return round(
			( $prompt_tokens / 1000 ) * $r['in'] + ( $completion_tokens / 1000 ) * $r['out'],
			4
		);
	}

	// -----------------------------------------------------------------------
	// Stats persistence
	// -----------------------------------------------------------------------

	/**
	 * @return array<string, array>
	 */
	private function load_stats(): array {
		$raw     = (string) get_option( self::OPT_STATS, '' );
		$decoded = $raw !== '' ? json_decode( $raw, true ) : null;
		return is_array( $decoded ) ? $decoded : [];
	}

	private function record_success( string $slug, int $prompt_tokens, int $completion_tokens, float $cost_cents ): void {
		$stats = $this->load_stats();
		$s     = $stats[ $slug ] ?? [ 'successes' => 0, 'failures' => 0, 'total_tokens' => 0, 'total_cost_cents' => 0.0 ];

		$s['successes']        = ( $s['successes'] ?? 0 ) + 1;
		$s['total_tokens']     = ( $s['total_tokens'] ?? 0 ) + $prompt_tokens + $completion_tokens;
		$s['total_cost_cents'] = round( ( $s['total_cost_cents'] ?? 0.0 ) + $cost_cents, 4 );
		$stats[ $slug ]        = $s;

		update_option( self::OPT_STATS, wp_json_encode( $stats ) );
	}

	private function record_failure( string $slug ): void {
		$stats              = $this->load_stats();
		$s                  = $stats[ $slug ] ?? [ 'successes' => 0, 'failures' => 0, 'total_tokens' => 0, 'total_cost_cents' => 0.0 ];
		$s['failures']      = ( $s['failures'] ?? 0 ) + 1;
		$stats[ $slug ]     = $s;
		update_option( self::OPT_STATS, wp_json_encode( $stats ) );
	}

	// -----------------------------------------------------------------------
	// Daily budget helpers
	// -----------------------------------------------------------------------

	private function accumulate_daily_cost( float $cost_cents ): void {
		$this->maybe_reset_daily_cost();
		$current = (float) get_option( self::OPT_TODAY_COST, 0.0 );
		update_option( self::OPT_TODAY_COST, round( $current + $cost_cents, 4 ) );
	}

	private function maybe_reset_daily_cost(): void {
		$today  = gmdate( 'Y-m-d' );
		$stored = (string) get_option( self::OPT_TODAY_DATE, '' );
		if ( $stored !== $today ) {
			update_option( self::OPT_TODAY_DATE, $today );
			update_option( self::OPT_TODAY_COST, 0.0 );
		}
	}
}
