<?php
/**
 * Bayesian Optimizer — F3 (v9.0)
 *
 * Implements Bayesian A/B test evaluation using the Beta-Binomial conjugate
 * model. Replaces raw "higher average score wins" with a probabilistic
 * estimate of which variant is truly better, allowing early stopping and
 * avoiding the multiple-comparisons pitfall.
 *
 * Model:
 *   Each variant maintains a Beta(α, β) distribution over its true conversion
 *   rate (or quality-score rate). α = successes + 1, β = failures + 1 (uniform
 *   prior). The probability that variant A beats variant B is estimated via
 *   Monte Carlo sampling.
 *
 * Integration with ABTestEngine:
 *   $optimizer = new BayesianOptimizer();
 *   $result    = $optimizer->evaluate( $test );
 *   // $result = ['winner' => 'a'|'b'|null, 'prob_a_better' => float, 'confident' => bool]
 *
 * @package PearBlogEngine\Testing
 */

declare(strict_types=1);

namespace PearBlogEngine\Testing;

/**
 * Bayesian A/B test evaluator using Monte Carlo Beta sampling.
 */
class BayesianOptimizer {

	/** Minimum posterior probability to declare a winner. */
	public const WIN_THRESHOLD = 0.95;

	/** Number of Monte Carlo samples for probability estimation. */
	public const MC_SAMPLES = 10_000;

	/** Quality score above this fraction of maximum is counted as a "success". */
	public const QUALITY_SUCCESS_THRESHOLD = 0.7;

	/** Maximum possible quality score (matches QualityScorer::MAX_SCORE). */
	public const MAX_QUALITY_SCORE = 100;

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Evaluate an A/B test and return a winner (or null if inconclusive).
	 *
	 * @param array $test  A test record as stored by ABTestEngine.
	 * @return array{winner: string|null, prob_a_better: float, confident: bool, samples_a: int, samples_b: int}
	 */
	public function evaluate( array $test ): array {
		$va = $test['variants']['a'] ?? [ 'runs' => 0, 'total_score' => 0 ];
		$vb = $test['variants']['b'] ?? [ 'runs' => 0, 'total_score' => 0 ];

		$runs_a  = max( 0, (int) $va['runs'] );
		$runs_b  = max( 0, (int) $vb['runs'] );
		$score_a = max( 0.0, (float) ( $va['total_score'] ?? 0 ) );
		$score_b = max( 0.0, (float) ( $vb['total_score'] ?? 0 ) );

		// Derive successes/failures from quality scores.
		[ $alpha_a, $beta_a ] = $this->compute_beta_params( $score_a, $runs_a );
		[ $alpha_b, $beta_b ] = $this->compute_beta_params( $score_b, $runs_b );

		$prob_a_better = $this->prob_a_beats_b( $alpha_a, $beta_a, $alpha_b, $beta_b );

		$confident = $prob_a_better >= self::WIN_THRESHOLD || $prob_a_better <= ( 1 - self::WIN_THRESHOLD );
		$winner    = null;

		if ( $confident ) {
			$winner = $prob_a_better >= self::WIN_THRESHOLD ? 'a' : 'b';
		}

		return [
			'winner'        => $winner,
			'prob_a_better' => round( $prob_a_better, 4 ),
			'confident'     => $confident,
			'samples_a'     => $runs_a,
			'samples_b'     => $runs_b,
		];
	}

	/**
	 * Recommend which variant to allocate more traffic to (Thompson sampling).
	 *
	 * Returns 'a' or 'b' based on a single draw from each Beta distribution.
	 *
	 * @param array $test
	 * @return string 'a' | 'b'
	 */
	public function thompson_sample( array $test ): string {
		$va = $test['variants']['a'] ?? [ 'runs' => 0, 'total_score' => 0 ];
		$vb = $test['variants']['b'] ?? [ 'runs' => 0, 'total_score' => 0 ];

		[ $alpha_a, $beta_a ] = $this->compute_beta_params(
			(float) ( $va['total_score'] ?? 0 ),
			(int) $va['runs']
		);
		[ $alpha_b, $beta_b ] = $this->compute_beta_params(
			(float) ( $vb['total_score'] ?? 0 ),
			(int) $vb['runs']
		);

		$sample_a = $this->beta_sample( $alpha_a, $beta_a );
		$sample_b = $this->beta_sample( $alpha_b, $beta_b );

		return $sample_a >= $sample_b ? 'a' : 'b';
	}

	// -----------------------------------------------------------------------
	// Statistical helpers
	// -----------------------------------------------------------------------

	/**
	 * Compute Beta distribution parameters from quality data.
	 *
	 * "Success" = article scored above QUALITY_SUCCESS_THRESHOLD * MAX_QUALITY_SCORE.
	 *
	 * @param float $total_score Sum of quality scores across all runs.
	 * @param int   $runs        Number of articles generated for this variant.
	 * @return array{float, float} [alpha, beta]
	 */
	public function compute_beta_params( float $total_score, int $runs ): array {
		if ( $runs === 0 ) {
			return [ 1.0, 1.0 ]; // uniform prior
		}

		$threshold  = self::QUALITY_SUCCESS_THRESHOLD * self::MAX_QUALITY_SCORE;
		$avg        = $total_score / $runs;

		// Map average quality score to a success ratio, then adjust by soft threshold.
		$adj_successes = max( 0, (int) round( $runs * ( $avg / self::MAX_QUALITY_SCORE ) ) );

		return [
			(float) max( 1, $adj_successes + 1 ),
			(float) max( 1, $runs - $adj_successes + 1 ),
		];
	}

	/**
	 * Estimate P(A > B) via Monte Carlo integration over Beta distributions.
	 *
	 * @param float $alpha_a
	 * @param float $beta_a
	 * @param float $alpha_b
	 * @param float $beta_b
	 * @return float Probability in [0, 1].
	 */
	public function prob_a_beats_b( float $alpha_a, float $beta_a, float $alpha_b, float $beta_b ): float {
		$wins = 0;
		$n    = self::MC_SAMPLES;

		for ( $i = 0; $i < $n; $i++ ) {
			if ( $this->beta_sample( $alpha_a, $beta_a ) > $this->beta_sample( $alpha_b, $beta_b ) ) {
				$wins++;
			}
		}

		return $wins / $n;
	}

	/**
	 * Draw a single sample from a Beta(α, β) distribution using the
	 * Johnk method (two gamma variates approach simplified via uniform draws).
	 *
	 * For small integer parameters this approximation via the ratio of two
	 * Gamma samples is used. We approximate Gamma(k, 1) as -ln(U1 * U2 * … * Uk)
	 * for integer k, which is exact for integer shape parameters.
	 *
	 * @param float $alpha Shape parameter α > 0.
	 * @param float $beta  Shape parameter β > 0.
	 * @return float Sample in (0, 1).
	 */
	public function beta_sample( float $alpha, float $beta ): float {
		$g_a = $this->gamma_sample( $alpha );
		$g_b = $this->gamma_sample( $beta );
		$sum = $g_a + $g_b;
		return $sum > 0.0 ? $g_a / $sum : 0.5;
	}

	/**
	 * Approximate Gamma(shape, 1) sample via the Marsaglia-Tsang method.
	 *
	 * For shape < 1 we use the relation: Gamma(shape) = Gamma(shape+1) * U^(1/shape).
	 *
	 * @param float $shape α > 0
	 * @return float
	 */
	public function gamma_sample( float $shape ): float {
		if ( $shape < 1.0 ) {
			// Gamma(a) = Gamma(a+1) * U^(1/a)
			$u = $this->uniform();
			return $this->gamma_sample( $shape + 1.0 ) * pow( $u, 1.0 / $shape );
		}

		$d = $shape - 1.0 / 3.0;
		$c = 1.0 / sqrt( 9.0 * $d );

		while ( true ) {
			do {
				$x = $this->normal_sample();
				$v = 1.0 + $c * $x;
			} while ( $v <= 0.0 );

			$v = $v * $v * $v;
			$u = $this->uniform();

			if ( $u < 1.0 - 0.0331 * ( $x * $x ) * ( $x * $x ) ) {
				return $d * $v;
			}
			if ( log( $u ) < 0.5 * $x * $x + $d * ( 1.0 - $v + log( $v ) ) ) {
				return $d * $v;
			}
		}
	}

	/**
	 * Standard normal sample via Box-Muller transform.
	 */
	protected function normal_sample(): float {
		static $spare = null;
		if ( $spare !== null ) {
			$s     = $spare;
			$spare = null;
			return $s;
		}
		do {
			$u = $this->uniform() * 2.0 - 1.0;
			$v = $this->uniform() * 2.0 - 1.0;
			$s = $u * $u + $v * $v;
		} while ( $s >= 1.0 || $s === 0.0 );

		$mul   = sqrt( -2.0 * log( $s ) / $s );
		$spare = $v * $mul;
		return $u * $mul;
	}

	/**
	 * Uniform(0, 1) random draw — overridable in tests for determinism.
	 */
	protected function uniform(): float {
		return mt_rand() / mt_getrandmax();
	}
}
