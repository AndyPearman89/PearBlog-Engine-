<?php
/**
 * Bayesian Optimizer — V9.0 F3
 *
 * Implements a lightweight multi-armed bandit (Thompson Sampling) algorithm
 * for A/B test traffic allocation and winner selection. Uses Beta distribution
 * sampling over per-variant conversion statistics to determine the optimal
 * traffic split and identify statistically significant winners without
 * requiring a fixed sample size.
 *
 * All computation is pure-PHP; no external ML dependencies required.
 *
 * @package PearBlogEngine\Testing
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

/**
 * Bayesian optimizer for A/B test traffic allocation.
 */
class BayesianOptimizer {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** Minimum conversion rate confidence threshold (0–1) to declare a winner. */
	public const WIN_PROBABILITY_THRESHOLD = 0.95;

	/** Minimum total impressions before declaring a winner. */
	public const MIN_IMPRESSIONS = 100;

	/** Default Beta prior alpha (successes + 1). */
	public const PRIOR_ALPHA = 1;

	/** Default Beta prior beta (failures + 1). */
	public const PRIOR_BETA = 1;

	/** Number of Monte-Carlo samples for win-probability estimation. */
	public const MC_SAMPLES = 5000;

	/** Meta key prefix for test statistics (JSON per test-id). */
	private const META_STATS = '_pearblog_ab_stats';

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Record an impression for a variant.
	 *
	 * @param  int    $post_id    Post ID running the test.
	 * @param  string $test_id    Test identifier (e.g. 'headline_test_1').
	 * @param  string $variant_id Variant identifier (e.g. 'A', 'B', 'control').
	 */
	public function record_impression( int $post_id, string $test_id, string $variant_id ): void {
		$stats = $this->load_stats( $post_id, $test_id );
		$stats[ $variant_id ]['impressions'] = ( $stats[ $variant_id ]['impressions'] ?? 0 ) + 1;
		$this->save_stats( $post_id, $test_id, $stats );
	}

	/**
	 * Record a conversion for a variant.
	 *
	 * @param  int    $post_id    Post ID.
	 * @param  string $test_id    Test identifier.
	 * @param  string $variant_id Variant identifier.
	 */
	public function record_conversion( int $post_id, string $test_id, string $variant_id ): void {
		$stats = $this->load_stats( $post_id, $test_id );
		$stats[ $variant_id ]['conversions'] = ( $stats[ $variant_id ]['conversions'] ?? 0 ) + 1;
		$this->save_stats( $post_id, $test_id, $stats );
	}

	/**
	 * Select which variant to serve based on Thompson Sampling.
	 *
	 * Returns the variant_id with the highest sampled Beta value.
	 *
	 * @param  int      $post_id  Post ID.
	 * @param  string   $test_id  Test identifier.
	 * @param  string[] $variants Ordered list of variant IDs.
	 * @return string  The selected variant ID.
	 */
	public function select_variant( int $post_id, string $test_id, array $variants ): string {
		if ( empty( $variants ) ) {
			return '';
		}

		$stats     = $this->load_stats( $post_id, $test_id );
		$best      = '';
		$bestScore = -1.0;

		foreach ( $variants as $variant_id ) {
			$alpha = self::PRIOR_ALPHA + ( $stats[ $variant_id ]['conversions'] ?? 0 );
			$beta  = self::PRIOR_BETA  + max( 0,
				( $stats[ $variant_id ]['impressions'] ?? 0 ) -
				( $stats[ $variant_id ]['conversions'] ?? 0 )
			);
			$score = $this->sample_beta( $alpha, $beta );
			if ( $score > $bestScore ) {
				$bestScore = $score;
				$best      = $variant_id;
			}
		}

		return $best !== '' ? $best : $variants[0];
	}

	/**
	 * Estimate win probabilities for all variants via Monte-Carlo simulation.
	 *
	 * @param  int      $post_id  Post ID.
	 * @param  string   $test_id  Test identifier.
	 * @param  string[] $variants Variant IDs to compare.
	 * @return array<string, float> variant_id → probability of being the best (0–1).
	 */
	public function win_probabilities( int $post_id, string $test_id, array $variants ): array {
		if ( empty( $variants ) ) {
			return [];
		}

		$stats = $this->load_stats( $post_id, $test_id );
		$wins  = array_fill_keys( $variants, 0 );

		for ( $s = 0; $s < self::MC_SAMPLES; $s++ ) {
			$bestVariant = '';
			$bestSample  = -1.0;

			foreach ( $variants as $variant_id ) {
				$alpha  = self::PRIOR_ALPHA + ( $stats[ $variant_id ]['conversions'] ?? 0 );
				$beta   = self::PRIOR_BETA  + max( 0,
					( $stats[ $variant_id ]['impressions'] ?? 0 ) -
					( $stats[ $variant_id ]['conversions'] ?? 0 )
				);
				$sample = $this->sample_beta( $alpha, $beta );
				if ( $sample > $bestSample ) {
					$bestSample  = $sample;
					$bestVariant = $variant_id;
				}
			}

			if ( $bestVariant !== '' ) {
				$wins[ $bestVariant ]++;
			}
		}

		$probs = [];
		foreach ( $variants as $variant_id ) {
			$probs[ $variant_id ] = round( $wins[ $variant_id ] / self::MC_SAMPLES, 4 );
		}
		return $probs;
	}

	/**
	 * Return a test summary: stats + win probabilities + winner (if any).
	 *
	 * @param  int      $post_id  Post ID.
	 * @param  string   $test_id  Test identifier.
	 * @param  string[] $variants Variant IDs.
	 * @return array{
	 *     test_id: string,
	 *     variants: array<string, array{impressions: int, conversions: int, rate: float}>,
	 *     win_probabilities: array<string, float>,
	 *     winner: string|null,
	 *     total_impressions: int,
	 *     ready: bool,
	 * }
	 */
	public function summary( int $post_id, string $test_id, array $variants ): array {
		$stats     = $this->load_stats( $post_id, $test_id );
		$totalImpr = 0;

		$variantSummary = [];
		foreach ( $variants as $v ) {
			$impr   = $stats[ $v ]['impressions'] ?? 0;
			$conv   = $stats[ $v ]['conversions'] ?? 0;
			$rate   = $impr > 0 ? round( $conv / $impr, 4 ) : 0.0;
			$totalImpr += $impr;
			$variantSummary[ $v ] = [
				'impressions' => $impr,
				'conversions' => $conv,
				'rate'        => $rate,
			];
		}

		$ready  = $totalImpr >= self::MIN_IMPRESSIONS;
		$probs  = $ready ? $this->win_probabilities( $post_id, $test_id, $variants ) : array_fill_keys( $variants, 0.0 );
		$winner = null;

		if ( $ready ) {
			foreach ( $probs as $variant_id => $prob ) {
				if ( $prob >= self::WIN_PROBABILITY_THRESHOLD ) {
					$winner = $variant_id;
					break;
				}
			}
		}

		return [
			'test_id'           => $test_id,
			'variants'          => $variantSummary,
			'win_probabilities' => $probs,
			'winner'            => $winner,
			'total_impressions' => $totalImpr,
			'ready'             => $ready,
		];
	}

	/**
	 * Reset all statistics for a test.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  string $test_id Test identifier.
	 */
	public function reset( int $post_id, string $test_id ): void {
		$all = $this->load_all( $post_id );
		unset( $all[ $test_id ] );
		update_post_meta( $post_id, self::META_STATS, wp_json_encode( $all ) );
	}

	// -----------------------------------------------------------------------
	// Math helpers
	// -----------------------------------------------------------------------

	/**
	 * Sample from a Beta(α, β) distribution using the Johnk method.
	 *
	 * @param  float $alpha Alpha shape parameter (>0).
	 * @param  float $beta  Beta shape parameter (>0).
	 * @return float Sample in [0, 1].
	 */
	public function sample_beta( float $alpha, float $beta ): float {
		// Use ratio of Gamma samples: Gamma(α) / (Gamma(α) + Gamma(β)).
		$ga = $this->sample_gamma( $alpha );
		$gb = $this->sample_gamma( $beta );
		$sum = $ga + $gb;
		return $sum > 0.0 ? $ga / $sum : 0.5;
	}

	/**
	 * Sample from a Gamma(k, 1) distribution using Marsaglia–Tsang's method.
	 *
	 * @param  float $k Shape parameter (>0).
	 * @return float Non-negative sample.
	 */
	private function sample_gamma( float $k ): float {
		if ( $k < 1.0 ) {
			// Boost small k using the Ahrens–Dieter transform.
			return $this->sample_gamma( $k + 1.0 ) * ( mt_rand() / mt_getrandmax() ) ** ( 1.0 / $k );
		}

		$d = $k - 1.0 / 3.0;
		$c = 1.0 / sqrt( 9.0 * $d );

		while ( true ) {
			do {
				$x = $this->normal();
				$v = 1.0 + $c * $x;
			} while ( $v <= 0.0 );

			$v3 = $v ** 3;
			$u  = mt_rand() / mt_getrandmax();

			if ( $u < 1.0 - 0.0331 * ( $x ** 2 ) ** 2 ) {
				return $d * $v3;
			}
			if ( log( $u ) < 0.5 * $x * $x + $d * ( 1.0 - $v3 + log( $v3 ) ) ) {
				return $d * $v3;
			}
		}
	}

	/**
	 * Box–Muller standard normal sample.
	 */
	private function normal(): float {
		static $spare   = null;
		static $hasSpare = false;

		if ( $hasSpare ) {
			$hasSpare = false;
			return $spare ?? 0.0;
		}

		$u = $v = $s = 0.0;
		do {
			$u = mt_rand() / mt_getrandmax() * 2.0 - 1.0;
			$v = mt_rand() / mt_getrandmax() * 2.0 - 1.0;
			$s = $u * $u + $v * $v;
		} while ( $s >= 1.0 || $s === 0.0 );

		$mul     = sqrt( -2.0 * log( $s ) / $s );
		$spare   = $v * $mul;
		$hasSpare = true;
		return $u * $mul;
	}

	// -----------------------------------------------------------------------
	// Persistence helpers
	// -----------------------------------------------------------------------

	/**
	 * @return array<string, array<string, int>>
	 */
	private function load_all( int $post_id ): array {
		$raw     = (string) get_post_meta( $post_id, self::META_STATS, true );
		$decoded = $raw !== '' ? json_decode( $raw, true ) : null;
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * @param  int    $post_id Post ID.
	 * @param  string $test_id Test identifier.
	 * @return array<string, array<string, int>>
	 */
	private function load_stats( int $post_id, string $test_id ): array {
		$all = $this->load_all( $post_id );
		return $all[ $test_id ] ?? [];
	}

	private function save_stats( int $post_id, string $test_id, array $stats ): void {
		$all             = $this->load_all( $post_id );
		$all[ $test_id ] = $stats;
		update_post_meta( $post_id, self::META_STATS, wp_json_encode( $all ) );
	}
}
