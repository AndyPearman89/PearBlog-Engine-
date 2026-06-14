<?php
/**
 * Bayesian Optimizer – Thompson sampling multi-armed bandit for A/B tests.
 *
 * Part of the V9.0 F3: Smart A/B Testing Engine enhancement.
 *
 * Uses the Beta distribution conjugate prior for Bernoulli reward signals:
 *  - Each arm maintains an alpha (successes + 1) and beta (failures + 1) count.
 *  - Thompson sampling draws a random sample from each arm's Beta distribution
 *    and selects the arm with the highest draw — naturally balancing exploration
 *    and exploitation without a fixed ε parameter.
 *  - Convergence is declared when one arm's posterior probability of being the
 *    best exceeds a configurable credible-interval threshold (default 95%).
 *
 * This class is intentionally pure PHP with no WordPress dependencies so it
 * can be unit-tested without a WP environment.
 *
 * Usage:
 *   $opt = new BayesianOptimizer( ['a', 'b'] );
 *   $arm = $opt->select_arm();           // 'a' or 'b'
 *   $opt->update( 'a', true );           // arm 'a' got a conversion
 *   $opt->update( 'b', false );          // arm 'b' did not
 *   $probs = $opt->get_probabilities();  // ['a' => 0.72, 'b' => 0.28]
 *   $opt->is_converged();                // false (not yet)
 *
 * @package PearBlogEngine\Testing
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

/**
 * Thompson-sampling Bayesian optimizer for multi-armed bandit A/B tests.
 */
class BayesianOptimizer {

	/** Number of Monte Carlo samples used to estimate win probabilities. */
	public const MC_SAMPLES = 2000;

	/** Default credible-interval threshold for convergence (0–1). */
	public const DEFAULT_THRESHOLD = 0.95;

	/**
	 * Per-arm Beta distribution parameters.
	 * Each entry: [ 'alpha' => float, 'beta' => float ]
	 *
	 * @var array<string, array{alpha: float, beta: float}>
	 */
	private array $arms = [];

	/**
	 * Spare normal sample from the Box-Muller transform (instance-level state).
	 */
	private ?float $normal_spare = null;

	/**
	 * @param string[] $arm_labels Unique identifiers for each arm (e.g. ['a', 'b']).
	 * @throws \InvalidArgumentException If fewer than 2 arms are supplied.
	 */
	public function __construct( array $arm_labels ) {
		if ( count( $arm_labels ) < 2 ) {
			throw new \InvalidArgumentException( 'BayesianOptimizer requires at least 2 arms.' );
		}
		foreach ( $arm_labels as $label ) {
			// Uniform Beta(1,1) prior — no initial preference.
			$this->arms[ (string) $label ] = [ 'alpha' => 1.0, 'beta' => 1.0 ];
		}
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Select the arm to show next via Thompson sampling.
	 *
	 * Draws one sample from each arm's Beta(α, β) posterior and returns the
	 * label of the arm with the highest draw.
	 *
	 * @return string Arm label.
	 */
	public function select_arm(): string {
		$best_arm   = '';
		$best_draw  = -1.0;

		foreach ( $this->arms as $label => $params ) {
			$draw = $this->beta_sample( $params['alpha'], $params['beta'] );
			if ( $draw > $best_draw ) {
				$best_draw = $draw;
				$best_arm  = $label;
			}
		}

		return $best_arm;
	}

	/**
	 * Update an arm's posterior after observing a reward signal.
	 *
	 * @param string $arm    Arm label.
	 * @param bool   $reward True = conversion / success; false = no conversion.
	 * @throws \InvalidArgumentException For unknown arm labels.
	 */
	public function update( string $arm, bool $reward ): void {
		if ( ! isset( $this->arms[ $arm ] ) ) {
			throw new \InvalidArgumentException( "Unknown arm label: '{$arm}'." );
		}
		if ( $reward ) {
			$this->arms[ $arm ]['alpha'] += 1.0;
		} else {
			$this->arms[ $arm ]['beta'] += 1.0;
		}
	}

	/**
	 * Estimate each arm's probability of being the best arm via Monte Carlo.
	 *
	 * Runs MC_SAMPLES Thompson draws and counts how often each arm wins.
	 *
	 * @return array<string, float> Map of arm label → win probability (sums to ~1.0).
	 */
	public function get_probabilities(): array {
		$wins = array_fill_keys( array_keys( $this->arms ), 0 );

		for ( $i = 0; $i < self::MC_SAMPLES; $i++ ) {
			$best_arm  = '';
			$best_draw = -1.0;
			foreach ( $this->arms as $label => $params ) {
				$draw = $this->beta_sample( $params['alpha'], $params['beta'] );
				if ( $draw > $best_draw ) {
					$best_draw = $draw;
					$best_arm  = $label;
				}
			}
			$wins[ $best_arm ]++;
		}

		$probs = [];
		foreach ( $wins as $label => $count ) {
			$probs[ $label ] = round( $count / self::MC_SAMPLES, 4 );
		}

		return $probs;
	}

	/**
	 * Return the highest single-arm win probability across all arms.
	 *
	 * Useful for displaying overall confidence of the current leading arm.
	 *
	 * @return float Win probability of the leading arm (0–1).
	 */
	public function get_confidence(): float {
		$probs = $this->get_probabilities();
		return max( $probs );
	}

	/**
	 * Check whether the optimizer has converged.
	 *
	 * Convergence is declared when one arm's estimated win probability exceeds
	 * $threshold (default 95%).
	 *
	 * @param float $threshold Credible-interval threshold (0–1).
	 * @return bool
	 */
	public function is_converged( float $threshold = self::DEFAULT_THRESHOLD ): bool {
		return $this->get_confidence() >= $threshold;
	}

	/**
	 * Return the arm label currently estimated as the winner.
	 *
	 * @return string Arm label with highest win probability.
	 */
	public function get_leading_arm(): string {
		$probs = $this->get_probabilities();
		arsort( $probs );
		return (string) array_key_first( $probs );
	}

	/**
	 * Return the raw Beta distribution parameters for all arms.
	 *
	 * @return array<string, array{alpha: float, beta: float}>
	 */
	public function get_arm_params(): array {
		return $this->arms;
	}

	/**
	 * Return the total number of observations recorded across all arms.
	 *
	 * Each arm starts at alpha=1, beta=1 (uniform prior), so observations
	 * are the combined increments above the initial values of 2 per arm.
	 *
	 * @return int
	 */
	public function get_total_observations(): int {
		$total = 0;
		foreach ( $this->arms as $params ) {
			// alpha + beta - 2 (subtract the 1+1 prior).
			$total += (int) ( $params['alpha'] + $params['beta'] - 2 );
		}
		return $total;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Draw one sample from a Beta(alpha, beta) distribution using the
	 * Johnk method (ratio of two Gamma random variables via the log-trick).
	 *
	 * PHP has no native Beta sampler, so we implement it via:
	 *   X ~ Gamma(α, 1),  Y ~ Gamma(β, 1),  Z = X / (X + Y) ~ Beta(α, β)
	 *
	 * Gamma(k, 1) is approximated with the Marsaglia & Tsang "squeeze" method
	 * for k >= 1 and the boost method for k < 1.
	 *
	 * @param float $alpha α parameter (> 0).
	 * @param float $beta  β parameter (> 0).
	 * @return float       Sample in [0, 1].
	 */
	private function beta_sample( float $alpha, float $beta ): float {
		$x = $this->gamma_sample( $alpha );
		$y = $this->gamma_sample( $beta );
		$s = $x + $y;
		return $s > 0 ? $x / $s : 0.5;
	}

	/**
	 * Draw one sample from Gamma(shape, 1) using Marsaglia & Tsang (2000).
	 *
	 * @param float $shape Shape parameter k > 0.
	 * @return float
	 */
	private function gamma_sample( float $shape ): float {
		if ( $shape < 1.0 ) {
			// Boost method: Gamma(k) = Gamma(k+1) * U^(1/k).
			return $this->gamma_sample( $shape + 1.0 ) * ( mt_rand() / mt_getrandmax() ) ** ( 1.0 / $shape );
		}

		$d = $shape - 1.0 / 3.0;
		$c = 1.0 / sqrt( 9.0 * $d );

		while ( true ) {
			do {
				$x = $this->normal_sample();
				$v = 1.0 + $c * $x;
			} while ( $v <= 0 );

			$v = $v ** 3;
			$u = mt_rand() / mt_getrandmax();

			if ( $u < 1.0 - 0.0331 * ( $x ** 2 ) ** 2 ) {
				return $d * $v;
			}
			if ( log( $u ) < 0.5 * $x * $x + $d * ( 1.0 - $v + log( $v ) ) ) {
				return $d * $v;
			}
		}
	}

	/**
	 * Draw one sample from N(0,1) using the Box-Muller transform.
	 *
	 * @return float
	 */
	private function normal_sample(): float {
		if ( null !== $this->normal_spare ) {
			$spare              = $this->normal_spare;
			$this->normal_spare = null;
			return $spare;
		}

		$u = mt_rand() / mt_getrandmax();
		$v = mt_rand() / mt_getrandmax();

		$mag = sqrt( -2.0 * log( max( $u, 1e-10 ) ) );

		$this->normal_spare = $mag * cos( 2.0 * M_PI * $v );

		return $mag * sin( 2.0 * M_PI * $v );
	}
}
