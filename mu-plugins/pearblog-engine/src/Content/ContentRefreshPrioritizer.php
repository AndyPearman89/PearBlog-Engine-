<?php
/**
 * Content Refresh Prioritizer — F6 Smart Content Refresh Automation.
 *
 * Scores every published post for refresh urgency and returns a ranked list.
 * Used by ContentRefreshEngine to decide which posts to process first.
 *
 * Priority score formula (0–100):
 *   score = (age_score * 0.35) + (traffic_score * 0.25) + (quality_score * 0.25) + (trend_score * 0.15)
 *
 * Score components:
 *   age_score     – 0 (fresh) → 100 (ancient); based on days since last refresh
 *   traffic_score – 0 (low) → 100 (high); based on 30-day views (more views = higher priority)
 *   quality_score – 0 (perfect) → 100 (poor); inverted quality score (lower quality = higher urgency)
 *   trend_score   – 0 (growing) → 100 (declining); traffic trend penalty
 *
 * Meta keys read:
 *   _pearblog_refreshed_at     – last refresh timestamp (MySQL datetime)
 *   _pearblog_quality_score    – 0–100 quality score (from QualityScorer)
 *   _pearblog_traffic_trend    – 'growing' | 'stable' | 'declining'
 *   _pearblog_ga4_views_30d    – page views last 30 days
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Analytics\AnalyticsDashboard;

/**
 * Computes a priority score for each post and returns a ranked refresh queue.
 */
class ContentRefreshPrioritizer {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** Posts older than this many days without a refresh get the maximum age score. */
	public const MAX_AGE_DAYS = 365;

	/** Score weights (must sum to 1.0). */
	public const WEIGHT_AGE     = 0.35;
	public const WEIGHT_TRAFFIC = 0.25;
	public const WEIGHT_QUALITY = 0.25;
	public const WEIGHT_TREND   = 0.15;

	/** Traffic normaliser: this many views = 100 traffic score points. */
	public const TRAFFIC_NORMALIZER = 1000;

	/** Traffic trend multipliers (declining = highest urgency). */
	private const TREND_SCORES = [
		'declining' => 100,
		'stable'    => 50,
		'growing'   => 0,
		''          => 30,   // unknown trend
	];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Return a list of post IDs ranked by refresh urgency (highest first).
	 *
	 * @param int $stale_days   Minimum days since last refresh to qualify.
	 * @param int $limit        Maximum number of posts to return.
	 * @param int $min_score    Minimum priority score to include (0–100).
	 * @return array<int, array{post_id: int, score: float, age_days: int, quality: float, trend: string, views_30d: int}>
	 */
	public function get_priority_queue(
		int $stale_days = ContentRefreshEngine::DEFAULT_STALE_DAYS,
		int $limit      = 50,
		int $min_score  = 20
	): array {
		$cutoff   = gmdate( 'Y-m-d H:i:s', time() - ( $stale_days * DAY_IN_SECONDS ) );
		$post_ids = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => max( 200, $limit * 4 ), // fetch more; filter by score
			'fields'         => 'ids',
			'date_query'     => [ [ 'before' => $cutoff, 'column' => 'post_date', 'inclusive' => true ] ],
		] );

		$scored = [];
		foreach ( $post_ids as $post_id ) {
			$entry = $this->score_post( (int) $post_id, $stale_days );
			if ( $entry['score'] >= $min_score ) {
				$scored[] = $entry;
			}
		}

		// Sort: highest score first.
		usort( $scored, fn( $a, $b ) => $b['score'] <=> $a['score'] );

		return array_slice( $scored, 0, $limit );
	}

	/**
	 * Compute the priority score for a single post.
	 *
	 * @param int $post_id
	 * @param int $stale_days  Used to calibrate the age score ceiling.
	 * @return array{post_id: int, score: float, age_days: int, quality: float, trend: string, views_30d: int}
	 */
	public function score_post( int $post_id, int $stale_days = ContentRefreshEngine::DEFAULT_STALE_DAYS ): array {
		$refreshed_at = (string) get_post_meta( $post_id, ContentRefreshEngine::META_REFRESHED_AT, true );
		$quality      = (float)  get_post_meta( $post_id, QualityScorer::META_QUALITY_SCORE, true );
		$trend        = (string) get_post_meta( $post_id, ContentRefreshEngine::META_TRAFFIC_TREND, true );
		$views_30d    = (int)    get_post_meta( $post_id, AnalyticsDashboard::META_VIEWS_30D, true );

		// Age in days since last refresh (or post date if never refreshed).
		$age_days = $this->compute_age_days( $post_id, $refreshed_at );

		$age_score     = $this->age_score( $age_days );
		$traffic_score = $this->traffic_score( $views_30d );
		$quality_score = $this->quality_urgency_score( $quality );
		$trend_score   = $this->trend_score( $trend );

		$score = round(
			( $age_score     * self::WEIGHT_AGE     ) +
			( $traffic_score * self::WEIGHT_TRAFFIC ) +
			( $quality_score * self::WEIGHT_QUALITY ) +
			( $trend_score   * self::WEIGHT_TREND   ),
			2
		);

		return [
			'post_id'   => $post_id,
			'score'     => $score,
			'age_days'  => $age_days,
			'quality'   => $quality,
			'trend'     => $trend ?: 'unknown',
			'views_30d' => $views_30d,
		];
	}

	/**
	 * Return the top N post IDs sorted by priority (for direct use in ContentRefreshEngine).
	 *
	 * @param int $stale_days
	 * @param int $batch_size
	 * @return int[]
	 */
	public function get_prioritized_ids( int $stale_days = ContentRefreshEngine::DEFAULT_STALE_DAYS, int $batch_size = ContentRefreshEngine::DEFAULT_BATCH_SIZE ): array {
		$queue = $this->get_priority_queue( $stale_days, $batch_size );
		return array_column( $queue, 'post_id' );
	}

	// -----------------------------------------------------------------------
	// Score component calculations
	// -----------------------------------------------------------------------

	/**
	 * Age score: 0 (just refreshed) → 100 (very old).
	 */
	public function age_score( int $age_days ): float {
		if ( $age_days <= 0 ) {
			return 0.0;
		}
		return min( 100.0, round( ( $age_days / self::MAX_AGE_DAYS ) * 100, 2 ) );
	}

	/**
	 * Traffic score: 0 (no traffic) → 100 (high traffic).
	 *
	 * High-traffic posts are prioritised: refreshing them has the most SEO impact.
	 */
	public function traffic_score( int $views_30d ): float {
		if ( $views_30d <= 0 ) {
			return 0.0;
		}
		return min( 100.0, round( ( $views_30d / self::TRAFFIC_NORMALIZER ) * 100, 2 ) );
	}

	/**
	 * Quality urgency score: 0 (high quality) → 100 (low quality).
	 *
	 * Lower quality = higher urgency to refresh.
	 */
	public function quality_urgency_score( float $quality ): float {
		if ( $quality <= 0.0 ) {
			return 80.0; // No score yet — treat as medium-high urgency.
		}
		// Invert: quality 100 → score 0; quality 0 → score 100.
		return round( max( 0.0, 100.0 - $quality ), 2 );
	}

	/**
	 * Trend score: declining → 100; growing → 0.
	 */
	public function trend_score( string $trend ): float {
		return (float) ( self::TREND_SCORES[ $trend ] ?? self::TREND_SCORES[''] );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function compute_age_days( int $post_id, string $refreshed_at ): int {
		if ( '' !== $refreshed_at ) {
			$ts = strtotime( $refreshed_at );
			if ( false !== $ts ) {
				return (int) floor( ( time() - $ts ) / DAY_IN_SECONDS );
			}
		}

		// Fall back to post date.
		$post_date = get_post_field( 'post_date', $post_id );
		if ( '' !== (string) $post_date ) {
			$ts = strtotime( (string) $post_date );
			if ( false !== $ts ) {
				return (int) floor( ( time() - $ts ) / DAY_IN_SECONDS );
			}
		}

		return ContentRefreshEngine::DEFAULT_STALE_DAYS;
	}
}
