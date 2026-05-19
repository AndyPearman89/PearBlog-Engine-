<?php
/**
 * Ranking Service
 *
 * Orchestration layer that combines:
 *   - DecisionPlatform\RankingEngine (AI generation + WP CPT persistence)
 *   - RankingScoreCalculator (weighted scoring)
 *   - SponsorEngine (sponsored placement injection)
 *   - Transient caching
 *
 * @package PearBlogEngine\Rankings
 */

declare(strict_types=1);

namespace PearBlogEngine\Rankings;

use PearBlogEngine\Core\EventBus;
use PearBlogEngine\Core\RankingUpdatedEvent;
use PearBlogEngine\DecisionPlatform\RankingEngine as BaseRankingEngine;

/**
 * RankingService
 *
 * Call site:
 *   $service = new RankingService();
 *   $items   = $service->get_ranked_list('mechanik', 'warszawa', 10);
 */
class RankingService {

	private const CACHE_PREFIX = 'pearblog_ranking_';
	private const CACHE_TTL    = 3600; // 1 hour

	private BaseRankingEngine      $generator;
	private RankingScoreCalculator $scorer;
	private SponsorEngine          $sponsor;

	public function __construct(
		?BaseRankingEngine      $generator = null,
		?RankingScoreCalculator $scorer    = null,
		?SponsorEngine          $sponsor   = null
	) {
		$this->generator = $generator ?? new BaseRankingEngine();
		$this->scorer    = $scorer    ?? new RankingScoreCalculator();
		$this->sponsor   = $sponsor   ?? new SponsorEngine();
	}

	// -----------------------------------------------------------------------
	// Primary API
	// -----------------------------------------------------------------------

	/**
	 * Return a fully scored, sponsor-ordered ranking list.
	 *
	 * Results are cached in WP transients keyed by category + city + limit.
	 *
	 * @param string $category   Vertical / service slug.
	 * @param string $city       City slug.
	 * @param int    $limit      Max items to return.
	 * @param bool   $force      Bypass cache.
	 * @return array<array<string, mixed>>
	 */
	public function get_ranked_list(
		string $category,
		string $city,
		int    $limit = 10,
		bool   $force = false
	): array {
		$cache_key = $this->cache_key( $category, $city, $limit );

		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		// Fetch raw specialists for category + city from WP CPT.
		$entries = $this->fetch_specialist_entries( $category, $city, $limit * 2 );

		// Score each entry.
		$entries = $this->score_entries( $entries );

		// Sort by total score descending before sponsor injection.
		usort( $entries, fn( $a, $b ) => $b['_score']['total'] <=> $a['_score']['total'] );

		// Cap to limit.
		$entries = array_slice( $entries, 0, $limit );

		// Inject sponsored placements (reorders top positions).
		$entries = $this->sponsor->apply( $entries, $category, $city );

		// Add position numbers.
		foreach ( $entries as $i => &$entry ) {
			$entry['position'] = $i + 1;
		}
		unset( $entry );

		set_transient( $cache_key, $entries, self::CACHE_TTL );

		return $entries;
	}

	/**
	 * Recalculate and persist the score for a single specialist in a ranking.
	 *
	 * Fires a RankingUpdatedEvent so listeners can invalidate caches.
	 *
	 * @param int    $specialist_id
	 * @param string $category
	 * @param string $city
	 * @return RankingScore
	 */
	public function recalculate_score( int $specialist_id, string $category, string $city ): RankingScore {
		$metrics = $this->load_metrics( $specialist_id );
		$score   = $this->scorer->calculate( $metrics );

		// Persist score to post meta.
		update_post_meta( $specialist_id, '_pearblog_ranking_score', $score->total );
		update_post_meta( $specialist_id, '_pearblog_score_breakdown', $score->to_array() );
		update_post_meta( $specialist_id, '_pearblog_score_updated_at', time() );

		// Invalidate cache.
		$this->invalidate_cache( $category, $city );

		// Fire event.
		EventBus::dispatch( new RankingUpdatedEvent( $specialist_id, $score->total ) );

		return $score;
	}

	/**
	 * Invalidate all transients for a category/city combination.
	 *
	 * @param string $category
	 * @param string $city
	 */
	public function invalidate_cache( string $category, string $city ): void {
		foreach ( [ 5, 10, 20 ] as $limit ) {
			delete_transient( $this->cache_key( $category, $city, $limit ) );
		}
	}

	// -----------------------------------------------------------------------
	// Data access helpers
	// -----------------------------------------------------------------------

	/**
	 * Fetch specialist post IDs with their stored meta from the CPT.
	 *
	 * @param string $category
	 * @param string $city
	 * @param int    $limit
	 * @return array<array<string, mixed>>
	 */
	private function fetch_specialist_entries( string $category, string $city, int $limit ): array {
		$args = [
			'post_type'      => 'pearblog_expert',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => '_pearblog_ranking_score',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		];

		$meta_query = [];
		if ( $category !== '' ) {
			$meta_query[] = [
				'key'     => 'pearblog_expert_category',
				'value'   => $category,
				'compare' => '=',
			];
		}
		if ( $city !== '' ) {
			$meta_query[] = [
				'key'     => 'pearblog_expert_location',
				'value'   => $city,
				'compare' => 'LIKE',
			];
		}
		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$posts   = get_posts( $args );
		$entries = [];

		foreach ( $posts as $post ) {
			$entries[] = [
				'specialist_id'  => $post->ID,
				'name'           => $post->post_title,
				'slug'           => $post->post_name,
				'category'       => get_post_meta( $post->ID, 'pearblog_expert_category', true ),
				'city'           => get_post_meta( $post->ID, 'pearblog_expert_location', true ),
				'avatar'         => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: '',
				'description'    => wp_trim_words( $post->post_content, 20 ),
				'phone'          => get_post_meta( $post->ID, 'pearblog_expert_phone', true ),
				'website'        => get_post_meta( $post->ID, 'pearblog_expert_website', true ),
				'avg_rating'     => (float) get_post_meta( $post->ID, '_pearblog_avg_rating', true ),
				'review_count'   => (int) get_post_meta( $post->ID, '_pearblog_review_count', true ),
				'is_premium'     => (bool) get_post_meta( $post->ID, '_pearblog_is_premium', true ),
				'is_sponsored'   => false, // Set by SponsorEngine::apply()
				'badges'         => get_post_meta( $post->ID, '_pearblog_badges', true ) ?: [],
				'verification'   => get_post_meta( $post->ID, '_pearblog_verification_level', true ) ?: 'none',
				'response_time'  => (string) get_post_meta( $post->ID, '_pearblog_response_time', true ),
				'url'            => get_permalink( $post->ID ),
			];
		}

		return $entries;
	}

	/**
	 * Load scoring metrics for a specialist from post meta.
	 *
	 * @param int $specialist_id
	 * @return array<string, mixed>
	 */
	private function load_metrics( int $specialist_id ): array {
		$last_modified = get_post_modified_time( 'U', true, $specialist_id );
		$days_active   = $last_modified
			? (int) floor( ( time() - (int) $last_modified ) / DAY_IN_SECONDS )
			: 999;

		return [
			'avg_rating'         => (float) get_post_meta( $specialist_id, '_pearblog_avg_rating', true ),
			'review_count'       => (int) get_post_meta( $specialist_id, '_pearblog_review_count', true ),
			'response_rate'      => (float) get_post_meta( $specialist_id, '_pearblog_response_rate', true ),
			'verification_level' => (string) get_post_meta( $specialist_id, '_pearblog_verification_level', true ),
			'last_active_days'   => $days_active,
			'is_premium'         => (bool) get_post_meta( $specialist_id, '_pearblog_is_premium', true ),
			'is_sponsored'       => false,
		];
	}

	/**
	 * Score a list of entries in-place.
	 *
	 * @param array<array<string, mixed>> $entries
	 * @return array<array<string, mixed>>
	 */
	private function score_entries( array $entries ): array {
		foreach ( $entries as &$entry ) {
			$metrics          = [
				'avg_rating'         => $entry['avg_rating'] ?? 0,
				'review_count'       => $entry['review_count'] ?? 0,
				'response_rate'      => 80.0, // default until response tracking available
				'verification_level' => $entry['verification'] ?? 'none',
				'last_active_days'   => 14,   // default
				'is_premium'         => $entry['is_premium'] ?? false,
				'is_sponsored'       => false,
			];
			$entry['_score']  = $this->scorer->calculate( $metrics )->to_array();
		}
		unset( $entry );
		return $entries;
	}

	private function cache_key( string $category, string $city, int $limit ): string {
		return self::CACHE_PREFIX . sanitize_key( $category ) . '_' . sanitize_key( $city ) . '_' . $limit;
	}
}
