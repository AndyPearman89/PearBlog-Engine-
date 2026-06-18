<?php
/**
 * Smart Provider Router – V9.0 F7: cost/latency-aware AI provider selection.
 *
 * Augments the existing AIProviderFactory by adding runtime intelligence:
 *   - Routes requests to the cheapest provider that meets latency SLA
 *   - Implements automatic failover when a provider exceeds error threshold
 *   - Tracks per-provider cost, latency, and quality metrics
 *   - Supports budget caps (daily spend limit per provider)
 *
 * Usage:
 *   $router = new SmartProviderRouter();
 *   $provider = $router->select( 'long-form', 3000 ); // content_type, max_tokens
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Routes AI generation requests to the optimal provider.
 */
class SmartProviderRouter {

	/** WP option: per-provider stats (latency, errors, cost). */
	public const OPTION_STATS         = 'pearblog_provider_stats';

	/** WP option: daily budget caps per provider (USD). */
	public const OPTION_BUDGET_CAPS   = 'pearblog_provider_budget_caps';

	/** WP option: daily spend per provider (resets at midnight UTC). */
	public const OPTION_DAILY_SPEND   = 'pearblog_provider_daily_spend';

	/** WP option: provider circuit-breaker state. */
	public const OPTION_CIRCUIT_STATE = 'pearblog_provider_circuit_state';

	/** Error rate (%) above which a circuit is opened (provider disabled). */
	public const ERROR_THRESHOLD_PCT  = 30.0;

	/** Number of calls required before circuit evaluation. */
	public const MIN_CALLS_FOR_EVAL   = 10;

	/** Default latency SLA in milliseconds. */
	public const DEFAULT_SLA_MS       = 5000;

	// -----------------------------------------------------------------------
	// Selection
	// -----------------------------------------------------------------------

	/**
	 * Select the optimal provider for a generation request.
	 *
	 * @param string $content_type  'short-form' | 'long-form' | 'code' | 'image'
	 * @param int    $max_tokens    Approximate token budget for the request.
	 * @param int    $sla_ms        Maximum acceptable response latency in ms.
	 * @return string               Provider name: 'openai' | 'anthropic' | 'gemini'
	 */
	public function select( string $content_type, int $max_tokens = 1000, int $sla_ms = self::DEFAULT_SLA_MS ): string {
		$candidates = $this->get_available_providers();

		if ( empty( $candidates ) ) {
			return 'openai'; // Fallback to primary provider.
		}

		// Score each candidate.
		$scored = [];
		foreach ( $candidates as $name ) {
			$scored[ $name ] = $this->score_provider( $name, $content_type, $max_tokens, $sla_ms );
		}

		arsort( $scored );

		return (string) array_key_first( $scored );
	}

	/**
	 * Return providers not currently circuit-broken and within budget.
	 *
	 * @return string[]
	 */
	public function get_available_providers(): array {
		$all     = [ 'openai', 'anthropic', 'gemini' ];
		$circuit = $this->load_circuit_state();
		$spend   = $this->load_daily_spend();
		$caps    = $this->load_budget_caps();

		return array_filter( $all, function ( string $name ) use ( $circuit, $spend, $caps ): bool {
			// Circuit-breaker check.
			if ( ! empty( $circuit[ $name ]['open'] ) ) {
				return false;
			}
			// Budget cap check.
			$cap      = (float) ( $caps[ $name ] ?? PHP_FLOAT_MAX );
			$used     = (float) ( $spend[ $name ][ gmdate( 'Y-m-d' ) ] ?? 0.0 );
			return $used < $cap;
		} );
	}

	/**
	 * Score a provider (higher = better candidate).
	 *
	 * @param string $provider
	 * @param string $content_type
	 * @param int    $max_tokens
	 * @param int    $sla_ms
	 * @return float
	 */
	public function score_provider( string $provider, string $content_type, int $max_tokens, int $sla_ms ): float {
		$stats   = $this->get_provider_stats( $provider );
		$avg_lat = (float) ( $stats['avg_latency_ms'] ?? self::DEFAULT_SLA_MS );
		$quality = (float) ( $stats['avg_quality'] ?? 70.0 );
		$cost_pk = $this->cost_per_1k( $provider, $content_type ); // USD per 1K tokens.

		// Penalise providers exceeding SLA.
		if ( $avg_lat > $sla_ms ) {
			return -1.0;
		}

		// Normalise: favour low cost, high quality, low latency.
		$cost_score    = $cost_pk > 0 ? 1.0 / $cost_pk : 100.0;
		$quality_score = $quality / 100.0;
		$latency_score = 1.0 - min( 1.0, $avg_lat / $sla_ms );

		return ( $cost_score * 0.5 ) + ( $quality_score * 0.3 ) + ( $latency_score * 0.2 );
	}

	// -----------------------------------------------------------------------
	// Observation recording
	// -----------------------------------------------------------------------

	/**
	 * Record the result of a generation call.
	 *
	 * @param string $provider
	 * @param int    $latency_ms
	 * @param bool   $success
	 * @param float  $quality_score 0–100
	 * @param float  $cost_usd
	 * @return void
	 */
	public function record_call(
		string $provider,
		int $latency_ms,
		bool $success,
		float $quality_score = 70.0,
		float $cost_usd = 0.0
	): void {
		$stats = $this->load_stats();

		$p = $stats[ $provider ] ?? [
			'calls'          => 0,
			'errors'         => 0,
			'total_latency'  => 0,
			'total_quality'  => 0.0,
		];

		$p['calls']++;
		if ( ! $success ) {
			$p['errors']++;
		}
		$p['total_latency'] += $latency_ms;
		$p['total_quality'] += $quality_score;

		$p['avg_latency_ms'] = (int) round( $p['total_latency'] / $p['calls'] );
		$p['avg_quality']    = round( $p['total_quality'] / $p['calls'], 2 );
		$p['error_rate_pct'] = round( $p['errors'] / $p['calls'] * 100, 2 );

		$stats[ $provider ] = $p;
		update_option( self::OPTION_STATS, $stats );

		// Update daily spend.
		$this->record_spend( $provider, $cost_usd );

		// Evaluate circuit breaker.
		$this->evaluate_circuit( $provider, $p );
	}

	// -----------------------------------------------------------------------
	// Circuit breaker
	// -----------------------------------------------------------------------

	/**
	 * Open or close a provider's circuit based on its error rate.
	 *
	 * @param string $provider
	 * @param array{calls:int,errors:int,error_rate_pct:float} $stats
	 */
	private function evaluate_circuit( string $provider, array $stats ): void {
		if ( $stats['calls'] < self::MIN_CALLS_FOR_EVAL ) {
			return;
		}

		$circuit = $this->load_circuit_state();

		if ( $stats['error_rate_pct'] > self::ERROR_THRESHOLD_PCT ) {
			$circuit[ $provider ]['open']       = true;
			$circuit[ $provider ]['opened_at']  = time();
		} else {
			$circuit[ $provider ]['open']        = false;
		}

		update_option( self::OPTION_CIRCUIT_STATE, $circuit );
	}

	// -----------------------------------------------------------------------
	// Stats & helpers
	// -----------------------------------------------------------------------

	/**
	 * @param string $provider
	 * @return array{calls:int,errors:int,avg_latency_ms:int,avg_quality:float,error_rate_pct:float}
	 */
	public function get_provider_stats( string $provider ): array {
		$stats = $this->load_stats();
		return $stats[ $provider ] ?? [];
	}

	/**
	 * Cost per 1,000 tokens in USD (approximate current pricing).
	 *
	 * @param string $provider
	 * @param string $content_type
	 * @return float
	 */
	public function cost_per_1k( string $provider, string $content_type ): float {
		$table = [
			'openai'    => [ 'default' => 0.0020, 'long-form' => 0.0060 ],
			'anthropic' => [ 'default' => 0.0025, 'long-form' => 0.0080 ],
			'gemini'    => [ 'default' => 0.0005, 'long-form' => 0.0020 ],
		];

		$provider_costs = $table[ $provider ] ?? [];
		return (float) ( $provider_costs[ $content_type ] ?? $provider_costs['default'] ?? 0.002 );
	}

	/**
	 * Record per-provider daily spend.
	 *
	 * @param string $provider
	 * @param float  $amount_usd
	 */
	private function record_spend( string $provider, float $amount_usd ): void {
		if ( $amount_usd <= 0.0 ) {
			return;
		}
		$spend  = $this->load_daily_spend();
		$today  = gmdate( 'Y-m-d' );
		$spend[ $provider ][ $today ] = ( (float) ( $spend[ $provider ][ $today ] ?? 0.0 ) ) + $amount_usd;
		update_option( self::OPTION_DAILY_SPEND, $spend );
	}

	/** @return array<string,array<string,float>> */
	private function load_daily_spend(): array {
		$raw = get_option( self::OPTION_DAILY_SPEND, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/** @return array<string,array{calls:int,errors:int,avg_latency_ms:int,avg_quality:float,error_rate_pct:float}> */
	private function load_stats(): array {
		$raw = get_option( self::OPTION_STATS, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/** @return array<string,array{open:bool,opened_at?:int}> */
	private function load_circuit_state(): array {
		$raw = get_option( self::OPTION_CIRCUIT_STATE, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/** @return array<string,float> */
	private function load_budget_caps(): array {
		$raw = get_option( self::OPTION_BUDGET_CAPS, [] );
		return is_array( $raw ) ? $raw : [];
	}
}
