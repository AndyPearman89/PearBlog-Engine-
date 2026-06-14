<?php
/**
 * Content Refresh Prioritizer — F6 (v9.0)
 *
 * Extends ContentRefreshEngine with smart queue prioritization:
 * instead of refreshing posts by age alone, this class computes a composite
 * priority score per post and returns a ranked list so the refresh cron
 * targets the highest-value content first.
 *
 * Priority Score formula (0–100):
 *   - Traffic trend signal  (40 pts) – 'declining' > 'stable' > 'growing'
 *   - Age since last refresh (30 pts) – older = higher score
 *   - Quality score deficit  (20 pts) – lower quality = higher urgency
 *   - Pageview decay         (10 pts) – significant recent drop
 *
 * Storage:
 *   pearblog_crp_scores   – JSON map post_id → { score, factors, updated_at }
 *
 * REST:
 *   GET /pearblog/v1/content/refresh-queue  — returns ranked list
 *   POST /pearblog/v1/content/refresh-queue/prioritize — trigger manual re-score
 *
 * CLI:
 *   wp pearblog v9 refresh-score   (implemented in V9Command)
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Ranks posts by refresh urgency using a multi-factor scoring model.
 */
class ContentRefreshPrioritizer {

	/** WP option storing the last computed scores. */
	public const OPTION_SCORES = 'pearblog_crp_scores';

	/** WP cron hook for weekly re-scoring. */
	public const CRON_HOOK = 'pearblog_crp_refresh';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Days after which age contributes full 30 pts. */
	public const MAX_AGE_DAYS = 180;

	/** Quality score floor: posts below this get full quality-urgency points. */
	public const QUALITY_FLOOR = 60;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'rescore_all' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/content/refresh-queue', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_queue' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'limit' => [ 'default' => 20, 'type' => 'integer', 'minimum' => 1, 'maximum' => 100 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/content/refresh-queue/prioritize', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_prioritize' ],
			'permission_callback' => [ $this, 'rest_permission' ],
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

	public function rest_queue( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = (int) $request->get_param( 'limit' );
		return new \WP_REST_Response( [
			'queue' => $this->get_ranked_queue( $limit ),
		] );
	}

	public function rest_prioritize( \WP_REST_Request $request ): \WP_REST_Response {
		$count = $this->rescore_all();
		return new \WP_REST_Response( [
			'rescored' => $count,
		] );
	}

	// -----------------------------------------------------------------------
	// Scoring
	// -----------------------------------------------------------------------

	/**
	 * Re-score all published posts and persist results.
	 *
	 * @return int Number of posts scored.
	 */
	public function rescore_all(): int {
		$posts  = $this->get_candidate_posts();
		$scores = [];

		foreach ( $posts as $post_id ) {
			$scores[ $post_id ] = $this->score_post( $post_id );
		}

		update_option( self::OPTION_SCORES, $scores );
		do_action( 'pearblog_crp_rescored', $scores );

		return count( $scores );
	}

	/**
	 * Compute priority score for a single post.
	 *
	 * @param int $post_id
	 * @return array{score:int, factors:array, updated_at:string}
	 */
	public function score_post( int $post_id ): array {
		$trend         = (string) get_post_meta( $post_id, '_pearblog_traffic_trend', true );
		$refreshed_at  = (string) get_post_meta( $post_id, '_pearblog_refreshed_at', true );
		$quality       = (float) get_post_meta( $post_id, '_pearblog_quality_score', true );
		$weekly_views  = (array) get_post_meta( $post_id, '_pearblog_weekly_views', true );

		// --- Traffic trend (40 pts) ---
		$trend_pts = match ( $trend ) {
			'declining' => 40,
			'stable'    => 20,
			'growing'   => 0,
			default     => 15, // unknown
		};

		// --- Age since last refresh (30 pts) ---
		$age_pts = 0;
		if ( '' !== $refreshed_at ) {
			$days_ago = (int) floor( ( time() - strtotime( $refreshed_at ) ) / 86400 );
			$age_pts  = (int) min( 30, (int) round( $days_ago / self::MAX_AGE_DAYS * 30 ) );
		} else {
			$age_pts = 30; // never refreshed = maximum urgency
		}

		// --- Quality score deficit (20 pts) ---
		$quality_pts = 0;
		if ( $quality > 0 ) {
			$quality_pts = $quality < self::QUALITY_FLOOR
				? (int) round( ( 1 - $quality / self::QUALITY_FLOOR ) * 20 )
				: 0;
		} else {
			$quality_pts = 10; // no score recorded
		}

		// --- Pageview decay (10 pts) ---
		$decay_pts = 0;
		if ( is_array( $weekly_views ) && count( $weekly_views ) >= 2 ) {
			$views = array_values( $weekly_views );
			$last  = (int) end( $views );
			$prev  = (int) prev( $views );
			if ( $prev > 0 && ( $prev - $last ) / $prev > 0.20 ) {
				$decay_pts = 10;
			}
		}

		$total = $trend_pts + $age_pts + $quality_pts + $decay_pts;

		return [
			'score'   => min( 100, $total ),
			'factors' => compact( 'trend_pts', 'age_pts', 'quality_pts', 'decay_pts' ),
			'updated_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		];
	}

	/**
	 * Return posts ranked by descending priority score.
	 *
	 * @param int $limit
	 * @return array<int, array{post_id:int, score:int, factors:array}>
	 */
	public function get_ranked_queue( int $limit = 20 ): array {
		$scores = get_option( self::OPTION_SCORES, [] );
		if ( ! is_array( $scores ) ) {
			return [];
		}

		arsort( $scores );
		$top = array_slice( $scores, 0, $limit, true );

		$result = [];
		foreach ( $top as $post_id => $data ) {
			$result[] = [
				'post_id' => (int) $post_id,
				'score'   => (int) ( $data['score'] ?? 0 ),
				'factors' => $data['factors'] ?? [],
			];
		}

		return $result;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * @return int[]
	 */
	protected function get_candidate_posts(): array {
		if ( ! function_exists( 'get_posts' ) ) {
			return [];
		}
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1000,
			'fields'         => 'ids',
		] );
		return is_array( $posts ) ? array_map( 'intval', $posts ) : [];
	}
}
