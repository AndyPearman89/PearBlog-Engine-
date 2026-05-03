<?php
/**
 * Predictive Analytics Engine – forecasts traffic potential for queued topics.
 *
 * Uses historical GA4 data to score queued topics by predicted traffic value.
 * Topics with similar keywords to high-performing existing articles receive
 * a higher predicted traffic score.
 *
 * The queue is automatically re-prioritized based on these scores.
 *
 * Features:
 *  - Keyword similarity matching between queued topics and top-performing articles.
 *  - Traffic score normalization (0–100).
 *  - Automatic queue re-prioritization weekly.
 *  - Per-topic confidence bands (sample size tracking).
 *
 * WP option:
 *   pearblog_predictive_scores – JSON-encoded topic → score map
 *
 * @package PearBlogEngine\Analytics
 */

declare(strict_types=1);

namespace PearBlogEngine\Analytics;

use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Predicts traffic potential for queued article topics.
 */
class PredictiveEngine {

	/** WP option key for stored scores. */
	public const OPTION_SCORES = 'pearblog_predictive_scores';

	/** WP option: last time the model was rebuilt. */
	public const OPTION_LAST_RUN = 'pearblog_predictive_last_run';

	/** Cron hook. */
	private const CRON_HOOK = 'pearblog_predictive_refresh';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'refresh' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Schedule weekly cron.
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
		register_rest_route( self::NAMESPACE, '/predictive/scores', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_scores' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/predictive/refresh', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_refresh' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Prediction logic
	// -----------------------------------------------------------------------

	/**
	 * Build traffic predictions for all queued topics.
	 *
	 * @return array<string, array{topic: string, score: float, confidence: string, similar_articles: int}>
	 */
	public function build_predictions(): array {
		$ga4        = new GA4Client();
		$context    = TenantContext::for_current_site();
		$queue      = new TopicQueue( $context->site_id );
		$topics     = $queue->all();

		// Build performance index from existing published posts.
		$performance_index = $this->build_performance_index( $ga4 );

		$scores = [];
		foreach ( $topics as $topic ) {
			$scores[ $topic ] = $this->predict_topic( $topic, $performance_index );
		}

		// Normalize scores to 0–100.
		$max_score = count( $scores ) > 0 ? max( array_column( $scores, 'raw_score' ) ) : 1.0;
		foreach ( $scores as &$data ) {
			$data['score'] = $max_score > 0 ? round( ( $data['raw_score'] / $max_score ) * 100, 1 ) : 0.0;
		}
		unset( $data );

		// Sort by score descending.
		uasort( $scores, fn( $a, $b ) => $b['score'] <=> $a['score'] );

		return $scores;
	}

	/**
	 * Refresh predictions and persist them.
	 */
	public function refresh(): void {
		$scores = $this->build_predictions();
		update_option( self::OPTION_SCORES, $scores );
		update_option( self::OPTION_LAST_RUN, time() );

		/**
		 * Action: pearblog_predictive_refreshed
		 *
		 * @param array<string,mixed> $scores Topic score map.
		 */
		do_action( 'pearblog_predictive_refreshed', $scores );
	}

	/**
	 * Get cached predictions.
	 *
	 * @return array<string, mixed>
	 */
	public function get_scores(): array {
		return (array) get_option( self::OPTION_SCORES, [] );
	}

	// -----------------------------------------------------------------------
	// Internal computation
	// -----------------------------------------------------------------------

	/**
	 * Build an index of existing article performance from GA4.
	 *
	 * @param GA4Client $ga4 GA4 client instance.
	 * @return array<int, array{title: string, keywords: array, views_30d: int}>
	 */
	private function build_performance_index( GA4Client $ga4 ): array {
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		] );

		$index = [];

		foreach ( $posts as $post_id ) {
			$post   = get_post( (int) $post_id );
			$title  = $post ? $post->post_title : '';
			$path   = parse_url( (string) get_permalink( (int) $post_id ), PHP_URL_PATH ) ?? '/';
			$views  = $ga4->is_configured() ? $ga4->get_page_views( $path, '30daysAgo', 'today' ) : 0;

			$index[] = [
				'post_id'   => (int) $post_id,
				'title'     => $title,
				'keywords'  => $this->extract_keywords( $title ),
				'views_30d' => $views,
			];
		}

		// Sort by views descending.
		usort( $index, fn( $a, $b ) => $b['views_30d'] <=> $a['views_30d'] );

		return $index;
	}

	/**
	 * Predict traffic score for a queued topic.
	 *
	 * @param string $topic            Topic string.
	 * @param array<int,array> $index  Performance index.
	 * @return array{topic: string, raw_score: float, score: float, confidence: string, similar_articles: int}
	 */
	private function predict_topic( string $topic, array $index ): array {
		$topic_keywords = $this->extract_keywords( $topic );
		$total_score    = 0.0;
		$similar_count  = 0;

		foreach ( $index as $article ) {
			$overlap = count( array_intersect( $topic_keywords, $article['keywords'] ) );
			if ( $overlap > 0 ) {
				// Weight: overlap * views, discounting by rank (top articles worth more).
				$weight      = $overlap / max( 1, count( $topic_keywords ) );
				$total_score += $weight * (float) $article['views_30d'];
				$similar_count++;
			}
		}

		$confidence = match ( true ) {
			$similar_count >= 5 => 'high',
			$similar_count >= 2 => 'medium',
			default             => 'low',
		};

		return [
			'topic'           => $topic,
			'raw_score'       => $total_score,
			'score'           => 0.0, // Filled after normalization.
			'confidence'      => $confidence,
			'similar_articles' => $similar_count,
		];
	}

	/**
	 * Extract keywords from a topic string.
	 *
	 * @param string $topic Topic string.
	 * @return string[]
	 */
	private function extract_keywords( string $topic ): array {
		$stop_words = [ 'jak', 'co', 'czy', 'dla', 'the', 'and', 'how', 'what', 'why', 'best', 'top', 'a', 'w', 'i', 'z', 'do', 'na', 'o' ];
		$words      = preg_split( '/\W+/', strtolower( $topic ), -1, PREG_SPLIT_NO_EMPTY ) ?: [];
		return array_values( array_filter( $words, fn( $w ) => strlen( $w ) > 3 && ! in_array( $w, $stop_words, true ) ) );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_scores( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( [
			'scores'   => $this->get_scores(),
			'last_run' => get_option( self::OPTION_LAST_RUN, 0 ),
		], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_refresh( \WP_REST_Request $request ): \WP_REST_Response {
		$this->refresh();
		return new \WP_REST_Response( [ 'success' => true, 'scores' => $this->get_scores() ], 200 );
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}
}
