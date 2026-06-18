<?php
/**
 * Bayesian Optimizer – V9.0 F3: accelerates A/B test convergence.
 *
 * Implements a Thompson-Sampling multi-armed bandit algorithm on top of the
 * existing ABTestEngine.  Instead of splitting traffic 50/50 between variants
 * and waiting for statistical significance, the optimizer progressively
 * allocates more traffic to the currently winning variant (exploit) while
 * still exploring alternatives (explore).
 *
 * Model:
 *   Each variant is modelled as a Beta distribution:  Beta(α, β)
 *     α = successes (conversions / high-quality scores)
 *     β = failures  (non-conversions / low-quality scores)
 *
 *   On every routing decision, one sample is drawn from each variant's
 *   distribution; the variant with the highest sample wins the slot.
 *
 * Persistence:
 *   Arm state is stored in the WP option `pearblog_bayes_arms`
 *   as: { test_id: { variant_id: { alpha, beta } } }
 *
 * @package PearBlogEngine\Testing
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

/**
 * Thompson-Sampling Bayesian A/B optimizer.
 */
class BayesianOptimizer {

	/** WP option key for arm state storage. */
	public const OPTION_KEY = 'pearblog_bayes_arms';

	/** Minimum alpha / beta value (uninformed prior). */
	public const PRIOR_ALPHA = 1;
	public const PRIOR_BETA  = 1;

	// -----------------------------------------------------------------------
	// Arm management
	// -----------------------------------------------------------------------

	/**
	 * Register a new test and initialise prior distributions for each variant.
	 *
	 * @param string   $test_id
	 * @param string[] $variant_ids
	 * @return void
	 */
	public function register_test( string $test_id, array $variant_ids ): void {
		$arms               = $this->load_arms();
		$arms[ $test_id ] ??= [];

		foreach ( $variant_ids as $vid ) {
			if ( ! isset( $arms[ $test_id ][ $vid ] ) ) {
				$arms[ $test_id ][ $vid ] = [
					'alpha' => self::PRIOR_ALPHA,
					'beta'  => self::PRIOR_BETA,
				];
			}
		}

		$this->save_arms( $arms );
	}

	/**
	 * Record an observation for a variant.
	 *
	 * @param string $test_id
	 * @param string $variant_id
	 * @param bool   $success  True for conversion / high score, false otherwise.
	 * @return void
	 */
	public function record_observation( string $test_id, string $variant_id, bool $success ): void {
		$arms = $this->load_arms();

		$this->ensure_arm( $arms, $test_id, $variant_id );

		if ( $success ) {
			++$arms[ $test_id ][ $variant_id ]['alpha'];
		} else {
			++$arms[ $test_id ][ $variant_id ]['beta'];
		}

		$this->save_arms( $arms );
	}

	/**
	 * Select the best variant using Thompson Sampling.
	 *
	 * @param string $test_id
	 * @return string|null  Variant ID or null if test not found.
	 */
	public function select_variant( string $test_id ): ?string {
		$arms = $this->load_arms();

		if ( empty( $arms[ $test_id ] ) ) {
			return null;
		}

		$best_sample    = -1.0;
		$best_variant   = null;

		foreach ( $arms[ $test_id ] as $vid => $params ) {
			$sample = $this->beta_sample( (float) $params['alpha'], (float) $params['beta'] );
			if ( $sample > $best_sample ) {
				$best_sample  = $sample;
				$best_variant = $vid;
			}
		}

		return $best_variant;
	}

	/**
	 * Return the arm state for a test (for reporting).
	 *
	 * @param string $test_id
	 * @return array<string,array{alpha:int,beta:int,probability_best:float}>
	 */
	public function get_arm_state( string $test_id ): array {
		$arms = $this->load_arms();
		$test_arms = $arms[ $test_id ] ?? [];

		// Compute approximate probability of being best via Monte-Carlo (1000 draws).
		$wins = array_fill_keys( array_keys( $test_arms ), 0 );
		$draws = 1000;

		for ( $i = 0; $i < $draws; $i++ ) {
			$best_s  = -1.0;
			$best_v  = null;
			foreach ( $test_arms as $vid => $params ) {
				$s = $this->beta_sample( (float) $params['alpha'], (float) $params['beta'] );
				if ( $s > $best_s ) {
					$best_s = $s;
					$best_v = $vid;
				}
			}
			if ( null !== $best_v ) {
				$wins[ $best_v ]++;
			}
		}

		$result = [];
		foreach ( $test_arms as $vid => $params ) {
			$result[ $vid ] = [
				'alpha'            => (int) $params['alpha'],
				'beta'             => (int) $params['beta'],
				'probability_best' => round( $wins[ $vid ] / $draws, 4 ),
			];
		}

		return $result;
	}

	/**
	 * Return the estimated conversion rate (mean of Beta distribution).
	 *
	 * @param string $test_id
	 * @param string $variant_id
	 * @return float  0.0 – 1.0
	 */
	public function expected_conversion_rate( string $test_id, string $variant_id ): float {
		$arms = $this->load_arms();
		$arm  = $arms[ $test_id ][ $variant_id ] ?? [
			'alpha' => self::PRIOR_ALPHA,
			'beta'  => self::PRIOR_BETA,
		];
		$alpha = (float) $arm['alpha'];
		$beta  = (float) $arm['beta'];

		return $alpha / ( $alpha + $beta );
	}

	// -----------------------------------------------------------------------
	// Internal helpers
	// -----------------------------------------------------------------------

	/**
	 * Sample from a Beta(alpha, beta) distribution using the Johnk method
	 * (approximation suitable for typical α/β ranges in A/B tests).
	 *
	 * @param float $alpha
	 * @param float $beta
	 * @return float  Sample in [0, 1].
	 */
	public function beta_sample( float $alpha, float $beta ): float {
		// Use the relation: Beta(α,β) = Gamma(α) / (Gamma(α) + Gamma(β))
		$g1 = $this->gamma_sample( $alpha );
		$g2 = $this->gamma_sample( $beta );

		$total = $g1 + $g2;
		if ( $total <= 0.0 ) {
			return 0.5;
		}
		return $g1 / $total;
	}

	/**
	 * Marsaglia-Tsang Gamma sampler for shape >= 1.
	 *
	 * @param float $shape (alpha / beta of Gamma dist, shape >= 1 after adjustment).
	 * @return float
	 */
	private function gamma_sample( float $shape ): float {
		if ( $shape < 1.0 ) {
			// Use the relation Gamma(s) = Gamma(s+1) * U^(1/s).
			return $this->gamma_sample( $shape + 1.0 ) * ( mt_rand() / mt_getrandmax() ) ** ( 1.0 / $shape );
		}
		$d = $shape - 1.0 / 3.0;
		$c = 1.0 / sqrt( 9.0 * $d );

		do {
			do {
				$x = $this->std_normal();
				$v = 1.0 + $c * $x;
			} while ( $v <= 0.0 );

			$v = $v ** 3;
			$u = mt_rand() / mt_getrandmax();

			if ( $u < 1.0 - 0.0331 * ( $x * $x ) ** 2 ) {
				return $d * $v;
			}
			if ( log( $u ) < 0.5 * $x * $x + $d * ( 1.0 - $v + log( $v ) ) ) {
				return $d * $v;
			}
		} while ( true );
	}

	/**
	 * Box-Muller standard-normal sample.
	 */
	private function std_normal(): float {
		static $has_spare = false;
		static $spare     = 0.0;

		if ( $has_spare ) {
			$has_spare = false;
			return $spare;
		}

		do {
			$u = mt_rand() / mt_getrandmax() * 2.0 - 1.0;
			$v = mt_rand() / mt_getrandmax() * 2.0 - 1.0;
			$s = $u * $u + $v * $v;
		} while ( $s >= 1.0 || $s === 0.0 );

		$mul       = sqrt( -2.0 * log( $s ) / $s );
		$spare     = $v * $mul;
		$has_spare = true;

		return $u * $mul;
	}

	/**
	 * @param array<string,array<string,array{alpha:int,beta:int}>> &$arms
	 */
	private function ensure_arm( array &$arms, string $test_id, string $variant_id ): void {
		$arms[ $test_id ]                ??= [];
		$arms[ $test_id ][ $variant_id ] ??= [
			'alpha' => self::PRIOR_ALPHA,
			'beta'  => self::PRIOR_BETA,
		];
	}

	/**
	 * @return array<string,array<string,array{alpha:int,beta:int}>>
	 */
	private function load_arms(): array {
		$raw = get_option( self::OPTION_KEY, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/**
	 * @param array<string,array<string,array{alpha:int,beta:int}>> $arms
	 */
	private function save_arms( array $arms ): void {
		update_option( self::OPTION_KEY, $arms );
	}
}
