<?php
/**
 * Content Refresh Prioritizer – V9.0 F6: smart staleness scoring and queue.
 *
 * Analyses all published posts and assigns a refresh-urgency score (0–100)
 * based on:
 *   1. Content age (older → higher score)
 *   2. Traffic decline vs. previous 30 days (>30% drop → +20 pts)
 *   3. Keyword ranking change (detected via Search Console option)
 *   4. Evergreen classification (evergreen content scores lower)
 *
 * Output:
 *   - `pearblog_refresh_queue` WP option: sorted list of {post_id, score, reason}
 *   - REST GET /wp-json/pearblog/v1/refresh-queue
 *   - REST POST /wp-json/pearblog/v1/refresh-queue/{id}/trigger
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Scores and prioritises content that needs refreshing.
 */
class ContentRefreshPrioritizer {

	/** WP option key for the prioritised refresh queue. */
	public const OPTION_QUEUE    = 'pearblog_refresh_queue';

	/** WP option: last scoring run timestamp. */
	public const OPTION_LAST_RUN = 'pearblog_refresh_prioritizer_last_run';

	/** Cron hook. */
	private const CRON_HOOK      = 'pearblog_refresh_prioritize';

	/** REST namespace. */
	private const REST_NAMESPACE = 'pearblog/v1';

	/** Post meta: marks a post as evergreen (skips urgency age penalty). */
	public const META_EVERGREEN  = '_pearblog_evergreen';

	/** Maximum urgency score. */
	public const MAX_SCORE       = 100;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'run' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Schedule weekly cron if not already scheduled.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/refresh-queue',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_get_queue' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/refresh-queue/(?P<id>[\d]+)/trigger',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_trigger_refresh' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			]
		);
	}

	// -----------------------------------------------------------------------
	// Scoring
	// -----------------------------------------------------------------------

	/**
	 * Score all published posts and update the refresh queue option.
	 *
	 * @param array<int,array{post_id:int,pageviews_current:int,pageviews_previous:int}>|null $analytics
	 *   Optional: inject analytics for testing.
	 * @return array<int,array{post_id:int,score:int,reasons:string[]}>
	 */
	public function run( ?array $analytics = null ): array {
		$posts = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );

		$analytics_map = [];
		if ( null !== $analytics ) {
			foreach ( $analytics as $row ) {
				$analytics_map[ $row['post_id'] ] = $row;
			}
		}

		$scored = [];
		foreach ( $posts as $post_id ) {
			$result    = $this->score_post( (int) $post_id, $analytics_map );
			$scored[]  = $result;
		}

		usort( $scored, static fn( $a, $b ) => $b['score'] <=> $a['score'] );

		update_option( self::OPTION_QUEUE, $scored );
		update_option( self::OPTION_LAST_RUN, gmdate( 'Y-m-d\TH:i:s\Z' ) );

		return $scored;
	}

	/**
	 * Compute refresh urgency score for a single post.
	 *
	 * @param int $post_id
	 * @param array<int,array{pageviews_current:int,pageviews_previous:int}> $analytics_map
	 * @return array{post_id:int,score:int,reasons:string[]}
	 */
	public function score_post( int $post_id, array $analytics_map = [] ): array {
		$score   = 0;
		$reasons = [];

		// --- Age penalty ---
		$modified    = get_post_modified_time( 'U', true, $post_id );
		$age_days    = $modified ? (int) floor( ( time() - (int) $modified ) / DAY_IN_SECONDS ) : 0;
		$is_evergreen = (bool) get_post_meta( $post_id, self::META_EVERGREEN, true );

		if ( ! $is_evergreen ) {
			if ( $age_days > 365 ) {
				$score  += 40;
				$reasons[] = "content_age:{$age_days}d";
			} elseif ( $age_days > 180 ) {
				$score  += 25;
				$reasons[] = "content_age:{$age_days}d";
			} elseif ( $age_days > 90 ) {
				$score  += 10;
				$reasons[] = "content_age:{$age_days}d";
			}
		}

		// --- Traffic decline ---
		if ( isset( $analytics_map[ $post_id ] ) ) {
			$pv_cur  = max( 1, (int) $analytics_map[ $post_id ]['pageviews_current'] );
			$pv_prev = max( 1, (int) $analytics_map[ $post_id ]['pageviews_previous'] );
			$decline = ( $pv_prev - $pv_cur ) / $pv_prev;

			if ( $decline > 0.5 ) {
				$score  += 30;
				$reasons[] = 'traffic_decline:>50%';
			} elseif ( $decline > 0.3 ) {
				$score  += 20;
				$reasons[] = 'traffic_decline:>30%';
			} elseif ( $decline > 0.1 ) {
				$score  += 10;
				$reasons[] = 'traffic_decline:>10%';
			}
		}

		// --- Quality score (low quality = needs refresh) ---
		$quality = (float) get_post_meta( $post_id, '_pearblog_quality_score', true );
		if ( $quality > 0 && $quality < 50 ) {
			$score  += 20;
			$reasons[] = "low_quality:{$quality}";
		}

		return [
			'post_id' => $post_id,
			'score'   => min( self::MAX_SCORE, $score ),
			'reasons' => $reasons,
		];
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_queue( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( [
			'queue'    => get_option( self::OPTION_QUEUE, [] ),
			'last_run' => get_option( self::OPTION_LAST_RUN, null ),
		], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_trigger_refresh( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$post_id = (int) $request->get_param( 'id' );

		if ( ! get_post( $post_id ) ) {
			return new \WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
		}

		do_action( 'pearblog_refresh_post', $post_id );

		return new \WP_REST_Response( [ 'triggered' => true, 'post_id' => $post_id ], 200 );
	}
}
