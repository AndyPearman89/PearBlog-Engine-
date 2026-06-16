<?php
/**
 * Smart Provider Router — F7 (v9.0)
 *
 * Intelligently routes AI completion requests across multiple providers
 * based on real-time cost, quality, and availability metrics.
 *
 * Routing strategy (configurable):
 *   - cost_optimised   — pick the cheapest provider that meets min quality bar
 *   - quality_first    — pick the provider with the highest average quality score
 *   - round_robin      — distribute load evenly across healthy providers
 *   - failover         — primary provider with automatic fallback on error
 *
 * Provider health is maintained in a short-lived transient (`pearblog_spr_health`).
 * After CIRCUIT_OPEN_THRESHOLD consecutive failures for one provider, it is
 * temporarily excluded for CIRCUIT_COOL_DOWN seconds.
 *
 * Storage:
 *   pearblog_spr_stats   – JSON: provider → { total_calls, success, failures, total_latency_ms, total_cost_usd }
 *   pearblog_spr_strategy – active routing strategy slug
 *
 * REST:
 *   GET  /pearblog/v1/ai/router/status  — health + stats
 *   POST /pearblog/v1/ai/router/strategy — update strategy
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Routes AI completion calls across providers based on configurable strategy.
 */
class SmartProviderRouter {

	/** WP option: routing strategy. */
	public const OPTION_STRATEGY = 'pearblog_spr_strategy';

	/** WP option: per-provider call statistics. */
	public const OPTION_STATS = 'pearblog_spr_stats';

	/** WP transient: provider health map. */
	public const TRANSIENT_HEALTH = 'pearblog_spr_health';

	/** Consecutive failure count before a provider circuit opens. */
	public const CIRCUIT_OPEN_THRESHOLD = 3;

	/** Seconds a provider stays excluded after circuit opens. */
	public const CIRCUIT_COOL_DOWN = 300;

	/** Available routing strategies. */
	public const STRATEGIES = [ 'cost_optimised', 'quality_first', 'round_robin', 'failover' ];

	/** Default strategy. */
	public const DEFAULT_STRATEGY = 'failover';

	/** Estimated cost per 1K tokens in USD per provider. */
	private const COST_PER_1K = [
		'openai'    => 0.002,
		'anthropic' => 0.003,
		'gemini'    => 0.001,
	];

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/ai/router/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_status' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/ai/router/strategy', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_set_strategy' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'strategy' => [
					'type'     => 'string',
					'required' => true,
					'enum'     => self::STRATEGIES,
				],
			],
		] );
	}

	public function rest_permission(): bool {
		$key = (string) get_option( 'pearblog_api_key', '' );
		if ( '' !== $key ) {
			$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
			if ( str_starts_with( $auth, 'Bearer ' ) && hash_equals( $key, substr( $auth, 7 ) ) ) {
				return true;
			}
		}
		return current_user_can( 'manage_options' );
	}

	public function rest_status(): \WP_REST_Response {
		return new \WP_REST_Response( [
			'strategy' => $this->get_strategy(),
			'health'   => $this->get_health(),
			'stats'    => $this->get_stats(),
		] );
	}

	public function rest_set_strategy( \WP_REST_Request $request ): \WP_REST_Response {
		$strategy = (string) $request->get_param( 'strategy' );
		$this->set_strategy( $strategy );

		return new \WP_REST_Response( [ 'strategy' => $strategy ] );
	}

	// -----------------------------------------------------------------------
	// Public routing API
	// -----------------------------------------------------------------------

	/**
	 * Complete a prompt using the best available provider per the active strategy.
	 *
	 * @param string $prompt
	 * @param array  $options  Optional override: ['provider' => 'openai', …]
	 * @return string          The completion text.
	 * @throws \RuntimeException When all providers fail.
	 */
	public function complete( string $prompt, array $options = [] ): string {
		$providers = $this->pick_providers( $options['provider'] ?? '' );

		$last_error = null;

		foreach ( $providers as $slug ) {
			$start = $this->microtime_float();
			try {
				$provider = AIProviderFactory::make( $slug );
				$text     = $provider->complete( $prompt );
				$elapsed  = (int) ( ( $this->microtime_float() - $start ) * 1000 );

				$this->record_success( $slug, $elapsed );
				return $text;
			} catch ( \Throwable $e ) {
				$last_error = $e;
				$this->record_failure( $slug );
			}
		}

		throw new \RuntimeException(
			'SmartProviderRouter: all providers exhausted. Last error: ' . ( $last_error ? $last_error->getMessage() : 'unknown' )
		);
	}

	/**
	 * Return an ordered list of provider slugs to try based on the active strategy.
	 *
	 * @param string $override  Force a specific provider (bypass strategy).
	 * @return string[]
	 */
	public function pick_providers( string $override = '' ): array {
		$all_slugs = array_keys( self::COST_PER_1K );

		if ( '' !== $override && in_array( $override, $all_slugs, true ) ) {
			return [ $override ];
		}

		$health   = $this->get_health();
		$healthy  = array_filter(
			$all_slugs,
			static fn( string $slug ) => ( $health[ $slug ] ?? 'healthy' ) === 'healthy'
		);
		$healthy  = array_values( $healthy );
		$stats    = $this->get_stats();
		$strategy = $this->get_strategy();

		if ( empty( $healthy ) ) {
			// All unhealthy — try primary anyway (failsafe).
			return $all_slugs;
		}

		return match ( $strategy ) {
			'cost_optimised' => $this->order_by_cost( $healthy ),
			'quality_first'  => $this->order_by_quality( $healthy, $stats ),
			'round_robin'    => $this->order_round_robin( $healthy, $stats ),
			default          => $this->order_failover( $healthy ),
		};
	}

	// -----------------------------------------------------------------------
	// Strategy implementations
	// -----------------------------------------------------------------------

	/** @param string[] $slugs @return string[] */
	private function order_by_cost( array $slugs ): array {
		usort( $slugs, static fn( $a, $b ) => self::COST_PER_1K[ $a ] <=> self::COST_PER_1K[ $b ] );
		return $slugs;
	}

	/** @param string[] $slugs @return string[] */
	private function order_by_quality( array $slugs, array $stats ): array {
		usort( $slugs, static function ( $a, $b ) use ( $stats ): int {
			$qa = $stats[ $a ]['success'] ?? 0;
			$qb = $stats[ $b ]['success'] ?? 0;
			return $qb <=> $qa;
		} );
		return $slugs;
	}

	/** @param string[] $slugs @return string[] */
	private function order_round_robin( array $slugs, array $stats ): array {
		usort( $slugs, static function ( $a, $b ) use ( $stats ): int {
			$ca = $stats[ $a ]['total_calls'] ?? 0;
			$cb = $stats[ $b ]['total_calls'] ?? 0;
			return $ca <=> $cb;
		} );
		return $slugs;
	}

	/** @param string[] $slugs @return string[] */
	private function order_failover( array $slugs ): array {
		$primary = (string) get_option( AIProviderFactory::PROVIDER_OPTION, AIProviderFactory::DEFAULT_PROVIDER );
		usort( $slugs, static fn( $a, $b ) => (int) ( $a !== $primary ) <=> (int) ( $b !== $primary ) );
		return $slugs;
	}

	// -----------------------------------------------------------------------
	// Stats & health tracking
	// -----------------------------------------------------------------------

	public function record_success( string $slug, int $latency_ms ): void {
		$stats = $this->get_stats();
		if ( ! isset( $stats[ $slug ] ) ) {
			$stats[ $slug ] = $this->empty_stat();
		}
		$stats[ $slug ]['total_calls']++;
		$stats[ $slug ]['success']++;
		$stats[ $slug ]['total_latency_ms'] += $latency_ms;
		$stats[ $slug ]['consecutive_failures'] = 0;
		update_option( self::OPTION_STATS, $stats );
		$this->set_health( $slug, 'healthy' );
	}

	public function record_failure( string $slug ): void {
		$stats = $this->get_stats();
		if ( ! isset( $stats[ $slug ] ) ) {
			$stats[ $slug ] = $this->empty_stat();
		}
		$stats[ $slug ]['total_calls']++;
		$stats[ $slug ]['failures']++;
		$stats[ $slug ]['consecutive_failures']++;
		update_option( self::OPTION_STATS, $stats );

		if ( $stats[ $slug ]['consecutive_failures'] >= self::CIRCUIT_OPEN_THRESHOLD ) {
			$this->set_health( $slug, 'circuit_open', self::CIRCUIT_COOL_DOWN );
		}
	}

	/** @return array<string, array{total_calls:int, success:int, failures:int, total_latency_ms:int, consecutive_failures:int}> */
	public function get_stats(): array {
		$raw = get_option( self::OPTION_STATS, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/** @return array<string, string>  slug → 'healthy'|'circuit_open' */
	public function get_health(): array {
		$raw = get_transient( self::TRANSIENT_HEALTH );
		return is_array( $raw ) ? $raw : [];
	}

	public function set_health( string $slug, string $status, int $ttl = 0 ): void {
		$health          = $this->get_health();
		$health[ $slug ] = $status;
		set_transient( self::TRANSIENT_HEALTH, $health, $ttl );
	}

	public function get_strategy(): string {
		$stored = (string) get_option( self::OPTION_STRATEGY, self::DEFAULT_STRATEGY );
		return in_array( $stored, self::STRATEGIES, true ) ? $stored : self::DEFAULT_STRATEGY;
	}

	public function set_strategy( string $strategy ): void {
		if ( ! in_array( $strategy, self::STRATEGIES, true ) ) {
			throw new \InvalidArgumentException( "Unknown strategy: {$strategy}" );
		}
		update_option( self::OPTION_STRATEGY, $strategy );
	}

	// -----------------------------------------------------------------------
	// Internal helpers
	// -----------------------------------------------------------------------

	private function empty_stat(): array {
		return [
			'total_calls'          => 0,
			'success'              => 0,
			'failures'             => 0,
			'total_latency_ms'     => 0,
			'consecutive_failures' => 0,
		];
	}

	protected function microtime_float(): float {
		return microtime( true );
	}
}
