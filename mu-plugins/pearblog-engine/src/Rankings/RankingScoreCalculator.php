<?php
/**
 * Ranking Score Calculator
 *
 * Computes a weighted trust/quality score for ranking entries.
 * Factors: review rating, review count, response rate, recency,
 * verification level, and an optional premium boost.
 *
 * @package PearBlogEngine\Rankings
 */

declare(strict_types=1);

namespace PearBlogEngine\Rankings;

/**
 * Immutable result of a score calculation.
 */
final class RankingScore {

	public function __construct(
		public readonly float  $total,        // 0–100
		public readonly float  $review_score, // 0–40
		public readonly float  $trust_score,  // 0–30
		public readonly float  $activity_score, // 0–20
		public readonly float  $premium_boost,  // 0–10
		public readonly array  $breakdown     // human-readable labels
	) {}

	/** @return array<string, mixed> */
	public function to_array(): array {
		return [
			'total'          => round( $this->total, 2 ),
			'review_score'   => round( $this->review_score, 2 ),
			'trust_score'    => round( $this->trust_score, 2 ),
			'activity_score' => round( $this->activity_score, 2 ),
			'premium_boost'  => round( $this->premium_boost, 2 ),
			'breakdown'      => $this->breakdown,
		];
	}
}

/**
 * Calculates a composite ranking score from specialist metrics.
 *
 * Scoring model (max 100 pts):
 *   ┌─────────────────────────────────────┬────────┐
 *   │ Component                           │ Max pts│
 *   ├─────────────────────────────────────┼────────┤
 *   │ Review rating   (avg × 8)           │  40    │
 *   │ Review volume   (log-normalised)    │  +10   │  (part of review_score)
 *   │ Verification level                  │  20    │  (part of trust_score)
 *   │ Response rate   (0–100 %)           │  10    │  (part of trust_score)
 *   │ Activity recency (< 30 days = full) │  20    │
 *   │ Premium subscription boost          │  10    │
 *   └─────────────────────────────────────┴────────┘
 */
class RankingScoreCalculator {

	/**
	 * Calculate score from raw metrics.
	 *
	 * @param array{
	 *   avg_rating:         float,
	 *   review_count:       int,
	 *   response_rate:      float,
	 *   verification_level: string,
	 *   last_active_days:   int,
	 *   is_premium:         bool,
	 *   is_sponsored:       bool,
	 * } $metrics
	 *
	 * @return RankingScore
	 */
	public function calculate( array $metrics ): RankingScore {
		$breakdown = [];

		// ── Review component (max 50) ──────────────────────────────────────
		$avg     = max( 0.0, min( 5.0, (float) ( $metrics['avg_rating'] ?? 0 ) ) );
		$count   = max( 0, (int) ( $metrics['review_count'] ?? 0 ) );
		$rat_pts = $avg * 8.0;                                  // 0–40
		$vol_pts = $count > 0 ? min( 10.0, log( $count + 1, 2 ) * 2.0 ) : 0.0; // 0–10
		$review_score = $rat_pts + $vol_pts;
		$breakdown[]  = "Rating {$avg}/5 → {$rat_pts}pts; {$count} reviews → {$vol_pts}pts";

		// ── Trust component (max 30) ───────────────────────────────────────
		$level     = $metrics['verification_level'] ?? 'none';
		$verif_pts = match ( $level ) {
			'gold'   => 20.0,
			'silver' => 14.0,
			'bronze' => 8.0,
			default  => 0.0,
		};
		$resp_rate = max( 0.0, min( 100.0, (float) ( $metrics['response_rate'] ?? 0 ) ) );
		$resp_pts  = $resp_rate * 0.1;                          // 0–10
		$trust_score = $verif_pts + $resp_pts;
		$breakdown[] = "Verification [{$level}] → {$verif_pts}pts; response rate {$resp_rate}% → {$resp_pts}pts";

		// ── Activity component (max 20) ────────────────────────────────────
		$days          = max( 0, (int) ( $metrics['last_active_days'] ?? 999 ) );
		$activity_score = match ( true ) {
			$days <= 7   => 20.0,
			$days <= 30  => 16.0,
			$days <= 90  => 10.0,
			$days <= 365 => 5.0,
			default      => 0.0,
		};
		$breakdown[] = "Last active {$days} days ago → {$activity_score}pts";

		// ── Premium boost (max 10) ─────────────────────────────────────────
		$premium_boost = 0.0;
		if ( ! empty( $metrics['is_premium'] ) ) {
			$premium_boost += 7.0;
		}
		if ( ! empty( $metrics['is_sponsored'] ) ) {
			$premium_boost += 3.0;
		}
		$breakdown[] = "Premium/sponsored boost → {$premium_boost}pts";

		$total = min( 100.0, $review_score + $trust_score + $activity_score + $premium_boost );

		return new RankingScore( $total, $review_score, $trust_score, $activity_score, $premium_boost, $breakdown );
	}
}
