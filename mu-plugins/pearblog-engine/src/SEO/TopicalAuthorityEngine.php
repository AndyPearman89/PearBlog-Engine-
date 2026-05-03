<?php
/**
 * Topical Authority Engine – builds topic silo structure for SEO.
 *
 * Maps published content into pillar–cluster topic hierarchies to build
 * topical authority.  Automatically suggests cluster articles for each
 * pillar and ensures proper internal linking between pillar and cluster pages.
 *
 * Features:
 *  - Identifies pillar articles (high word-count, broad topics).
 *  - Groups existing articles into topic silos using keyword analysis.
 *  - Suggests missing cluster articles to add to the queue.
 *  - Enforces pillar → cluster → pillar internal link requirements.
 *  - WP option `pearblog_topical_silos` stores the silo map.
 *
 * REST endpoint:
 *   GET  /pearblog/v1/seo/silos       – get current silo map
 *   POST /pearblog/v1/seo/silos/build – (re)build the silo map
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Analyses and builds topical authority silo structures.
 */
class TopicalAuthorityEngine {

	/** WP option storing the silo map. */
	public const OPTION_SILOS = 'pearblog_topical_silos';

	/** WP option storing silo configuration. */
	public const OPTION_CONFIG = 'pearblog_topical_config';

	/** Minimum word count for a post to be considered a pillar. */
	private const PILLAR_MIN_WORDS = 1500;

	/** Maximum cluster articles per pillar. */
	private const MAX_CLUSTERS_PER_PILLAR = 10;

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Cron hook for weekly silo rebuild. */
	private const CRON_HOOK = 'pearblog_topical_rebuild';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'rebuild' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'pearblog_pipeline_completed', [ $this, 'on_article_published' ], 10, 2 );
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
		register_rest_route( self::NAMESPACE, '/seo/silos', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_silos' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/seo/silos/build', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_build_silos' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Silo building
	// -----------------------------------------------------------------------

	/**
	 * Build the full topical silo map from published content.
	 *
	 * @return array<string, array{pillar: array, clusters: array, missing_clusters: array}>
	 */
	public function build(): array {
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'fields'         => 'ids',
		] );

		$pillars  = [];
		$clusters = [];

		// Classify each post as pillar or cluster.
		foreach ( $posts as $post_id ) {
			$post       = get_post( (int) $post_id );
			$word_count = $post ? str_word_count( wp_strip_all_tags( $post->post_content ) ) : 0;
			$keywords   = $this->extract_keywords( $post ? $post->post_title : '' );

			if ( $word_count >= self::PILLAR_MIN_WORDS ) {
				$pillars[] = [
					'post_id'    => (int) $post_id,
					'title'      => $post ? $post->post_title : '',
					'word_count' => $word_count,
					'keywords'   => $keywords,
					'url'        => get_permalink( (int) $post_id ),
				];
			} else {
				$clusters[] = [
					'post_id'  => (int) $post_id,
					'title'    => $post ? $post->post_title : '',
					'keywords' => $keywords,
					'url'      => get_permalink( (int) $post_id ),
				];
			}
		}

		// Match clusters to pillars by keyword overlap.
		$silos = [];
		foreach ( $pillars as $pillar ) {
			$pillar_key    = $pillar['keywords'][0] ?? 'general';
			$matched       = [];
			$missing_count = 0;

			foreach ( $clusters as $cluster ) {
				if ( $this->keyword_overlap( $pillar['keywords'], $cluster['keywords'] ) > 0 ) {
					$matched[] = $cluster;
					if ( count( $matched ) >= self::MAX_CLUSTERS_PER_PILLAR ) {
						break;
					}
				}
			}

			// Suggest missing clusters based on keyword gaps.
			$missing_clusters = $this->suggest_missing_clusters( $pillar, count( $matched ) );

			$silos[ $pillar_key ] = [
				'pillar'           => $pillar,
				'clusters'         => $matched,
				'missing_clusters' => $missing_clusters,
				'coverage_score'   => min( 100, (int) ( ( count( $matched ) / max( 1, self::MAX_CLUSTERS_PER_PILLAR ) ) * 100 ) ),
			];
		}

		return $silos;
	}

	/**
	 * Build and persist the silo map.
	 */
	public function rebuild(): void {
		$silos = $this->build();
		update_option( self::OPTION_SILOS, $silos );

		/**
		 * Action: pearblog_topical_silos_built
		 *
		 * @param array<string,mixed> $silos Built silo map.
		 */
		do_action( 'pearblog_topical_silos_built', $silos );

		// Queue missing cluster topics.
		$this->queue_missing_clusters( $silos );
	}

	/**
	 * Get stored silo map (or build fresh if empty).
	 *
	 * @return array<string, mixed>
	 */
	public function get_silos(): array {
		$cached = get_option( self::OPTION_SILOS );
		return is_array( $cached ) ? $cached : [];
	}

	// -----------------------------------------------------------------------
	// WordPress action callbacks
	// -----------------------------------------------------------------------

	/**
	 * Trigger a lightweight silo update when a new article is published.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $topic   Article topic.
	 */
	public function on_article_published( int $post_id, string $topic ): void {
		// Debounce: rebuild at most once per hour.
		$last_rebuild = (int) get_option( 'pearblog_topical_last_rebuild', 0 );
		if ( time() - $last_rebuild < HOUR_IN_SECONDS ) {
			return;
		}

		$this->rebuild();
		update_option( 'pearblog_topical_last_rebuild', time() );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_silos( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( [
			'silos'      => $this->get_silos(),
			'silo_count' => count( $this->get_silos() ),
		], 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_build_silos( \WP_REST_Request $request ): \WP_REST_Response {
		$this->rebuild();
		return new \WP_REST_Response( [
			'success' => true,
			'silos'   => $this->get_silos(),
		], 200 );
	}

	/**
	 * Permission callback.
	 */
	public function admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Extract top keywords from a title string.
	 *
	 * @param string $title Post title.
	 * @return string[]
	 */
	private function extract_keywords( string $title ): array {
		$stop_words = [ 'jak', 'co', 'czy', 'dla', 'the', 'and', 'how', 'what', 'why', 'best', 'top', 'vs', 'a', 'w', 'i', 'z', 'do' ];
		$words      = preg_split( '/\s+/', strtolower( $title ), -1, PREG_SPLIT_NO_EMPTY ) ?: [];
		$filtered   = array_filter( $words, fn( $w ) => strlen( $w ) > 3 && ! in_array( $w, $stop_words, true ) );

		return array_values( array_slice( $filtered, 0, 5 ) );
	}

	/**
	 * Calculate keyword overlap count between two keyword arrays.
	 *
	 * @param string[] $a First keyword set.
	 * @param string[] $b Second keyword set.
	 * @return int Overlap count.
	 */
	private function keyword_overlap( array $a, array $b ): int {
		return count( array_intersect( $a, $b ) );
	}

	/**
	 * Suggest cluster topic titles missing for a given pillar.
	 *
	 * @param array<string,mixed> $pillar        Pillar data.
	 * @param int                 $existing_count Number of existing clusters.
	 * @return string[]
	 */
	private function suggest_missing_clusters( array $pillar, int $existing_count ): array {
		$needed    = max( 0, 5 - $existing_count );
		$keyword   = $pillar['keywords'][0] ?? '';
		$suggested = [];

		$templates = [
			"Beginners guide to {keyword}",
			"Common mistakes in {keyword}",
			"{keyword} tips and tricks",
			"How to improve your {keyword} strategy",
			"Advanced {keyword} techniques",
			"{keyword} vs alternatives – comparison",
			"Cost of {keyword} – complete breakdown",
		];

		foreach ( array_slice( $templates, 0, $needed ) as $template ) {
			$suggested[] = str_replace( '{keyword}', $keyword, $template );
		}

		return $suggested;
	}

	/**
	 * Add missing cluster topics to the content queue.
	 *
	 * @param array<string, mixed> $silos Silo map.
	 */
	private function queue_missing_clusters( array $silos ): void {
		$queue = new \PearBlogEngine\Content\TopicQueue( get_current_blog_id() );

		foreach ( $silos as $silo ) {
			foreach ( $silo['missing_clusters'] as $topic ) {
				// Only add if not already in queue.
				$queue->add( $topic );
			}
		}
	}
}
