<?php
/**
 * Orphan Page Detector – identifies posts with no inbound internal links.
 *
 * Part of the V9.0 F8: Advanced SEO Automation Suite.
 *
 * An "orphan page" is a published post that receives zero internal links from
 * any other published post.  Orphan pages receive no link equity from the
 * site's internal link graph, typically rank poorly, and represent quick SEO
 * wins once linked.
 *
 * Features:
 *  - Scans all published posts for href patterns to build an inbound-link map.
 *  - Returns orphan pages sorted by content quality score (highest priority first).
 *  - Suggests up to N donor posts (posts that share topical overlap and could
 *    naturally link to an orphan) using keyword overlap analysis.
 *  - Provides link equity distribution summary across the site.
 *  - Results are cached in a transient to avoid expensive full-site scans on
 *    every request.
 *
 * REST endpoints:
 *   GET  /pearblog/v1/seo/orphans          – list orphan pages
 *   GET  /pearblog/v1/seo/orphans/report   – summary report + link equity
 *   POST /pearblog/v1/seo/orphans/suggest  – suggest donor posts for a given post_id
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Detects orphan pages and suggests internal linking opportunities.
 */
class OrphanPageDetector {

	/** Transient key for the inbound-link map cache. */
	public const TRANSIENT_LINK_MAP = 'pearblog_orphan_link_map';

	/** Transient TTL in seconds (12 hours). */
	private const LINK_MAP_TTL = 43200;

	/** Maximum donor suggestions per orphan. */
	public const MAX_DONOR_SUGGESTIONS = 5;

	/** Minimum keyword overlap score to qualify as a donor (0–1). */
	public const MIN_OVERLAP_SCORE = 0.1;

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Meta key for quality score set by QualityScorer. */
	private const META_QUALITY = '_pearblog_quality_score';

	/** Meta key for keyword cluster set by KeywordClusterEngine. */
	private const META_KEYWORDS = 'pearblog_keyword_cluster';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		// Bust cache whenever a post is saved / published.
		add_action( 'save_post', [ $this, 'bust_cache' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/seo/orphans', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_list_orphans' ],
			'permission_callback' => [ $this, 'admin_permission' ],
			'args'                => [
				'limit' => [
					'type'    => 'integer',
					'default' => 50,
					'minimum' => 1,
					'maximum' => 200,
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/seo/orphans/report', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_report' ],
			'permission_callback' => [ $this, 'admin_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/seo/orphans/suggest', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_suggest_donors' ],
			'permission_callback' => [ $this, 'admin_permission' ],
			'args'                => [
				'post_id' => [
					'required' => true,
					'type'     => 'integer',
					'minimum'  => 1,
				],
				'limit'   => [
					'type'    => 'integer',
					'default' => self::MAX_DONOR_SUGGESTIONS,
					'minimum' => 1,
					'maximum' => 20,
				],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// Core API
	// -----------------------------------------------------------------------

	/**
	 * Return all orphan pages (published posts with zero inbound internal links).
	 *
	 * @param array{limit?: int, post_type?: string} $options Optional filters.
	 * @return array<int, array{post_id: int, title: string, url: string, quality_score: float}>
	 */
	public function get_orphan_pages( array $options = [] ): array {
		$limit     = (int) ( $options['limit'] ?? 50 );
		$post_type = $options['post_type'] ?? 'post';

		$link_map  = $this->get_inbound_link_map( $post_type );
		$all_posts = $this->get_published_posts( $post_type );
		$orphans   = [];

		foreach ( $all_posts as $post ) {
			$post_id = (int) $post->ID;
			$inbound = $link_map[ $post_id ] ?? 0;
			if ( 0 === $inbound ) {
				$orphans[] = [
					'post_id'       => $post_id,
					'title'         => get_the_title( $post_id ),
					'url'           => get_permalink( $post_id ),
					'quality_score' => (float) ( get_post_meta( $post_id, self::META_QUALITY, true ) ?: 0.0 ),
					'inbound_links' => 0,
				];
			}
		}

		// Sort by quality score descending — highest-quality orphans get links first.
		usort( $orphans, static fn( $a, $b ) => $b['quality_score'] <=> $a['quality_score'] );

		return array_slice( $orphans, 0, $limit );
	}

	/**
	 * Build and return the inbound-link count map for all published posts.
	 *
	 * The map is an associative array of post_id → inbound link count.
	 * Results are cached for LINK_MAP_TTL seconds.
	 *
	 * @param string $post_type WordPress post type to scan.
	 * @return array<int, int>
	 */
	public function get_inbound_link_map( string $post_type = 'post' ): array {
		$cached = get_transient( self::TRANSIENT_LINK_MAP . '_' . $post_type );
		if ( false !== $cached ) {
			return $cached;
		}

		$posts  = $this->get_published_posts( $post_type );
		$map    = [];

		// Initialise every post with 0 inbound links.
		foreach ( $posts as $post ) {
			$map[ (int) $post->ID ] = 0;
		}

		// Build a URL → post_id lookup for fast matching.
		$url_to_id = [];
		foreach ( $posts as $post ) {
			$url = get_permalink( (int) $post->ID );
			if ( $url ) {
				$url_to_id[ rtrim( $url, '/' ) ] = (int) $post->ID;
			}
		}

		$home = rtrim( home_url(), '/' );

		// Scan each post's content for href attributes pointing to internal URLs.
		foreach ( $posts as $source_post ) {
			$content = $source_post->post_content ?? '';
			if ( ! $content ) {
				continue;
			}

			preg_match_all( '/href=["\']([^"\']+)["\']/', $content, $matches );
			$hrefs = $matches[1] ?? [];

			foreach ( $hrefs as $href ) {
				// Normalise: strip trailing slash, query string, fragment.
				$href = strtok( rtrim( $href, '/' ), '?' );
				$href = strtok( $href ?: '', '#' );

				if ( ! $href ) {
					continue;
				}

				// Handle absolute internal URLs.
				if ( str_starts_with( $href, $home ) ) {
					$clean = rtrim( $href, '/' );
					if ( isset( $url_to_id[ $clean ] ) ) {
						$target_id = $url_to_id[ $clean ];
						if ( $target_id !== (int) $source_post->ID ) {
							$map[ $target_id ] = ( $map[ $target_id ] ?? 0 ) + 1;
						}
					}
				}
			}
		}

		set_transient( self::TRANSIENT_LINK_MAP . '_' . $post_type, $map, self::LINK_MAP_TTL );

		return $map;
	}

	/**
	 * Return link equity distribution summary.
	 *
	 * Buckets posts by inbound link count:
	 *   0 links (orphan), 1–2, 3–5, 6–10, 11+.
	 *
	 * @param string $post_type Post type to analyse.
	 * @return array{buckets: array<string, int>, total_posts: int, orphan_count: int, orphan_pct: float}
	 */
	public function get_link_equity_distribution( string $post_type = 'post' ): array {
		$map        = $this->get_inbound_link_map( $post_type );
		$total      = count( $map );
		$buckets    = [ '0' => 0, '1-2' => 0, '3-5' => 0, '6-10' => 0, '11+' => 0 ];

		foreach ( $map as $count ) {
			if ( 0 === $count ) {
				$buckets['0']++;
			} elseif ( $count <= 2 ) {
				$buckets['1-2']++;
			} elseif ( $count <= 5 ) {
				$buckets['3-5']++;
			} elseif ( $count <= 10 ) {
				$buckets['6-10']++;
			} else {
				$buckets['11+']++;
			}
		}

		return [
			'buckets'     => $buckets,
			'total_posts' => $total,
			'orphan_count' => $buckets['0'],
			'orphan_pct'  => $total > 0 ? round( $buckets['0'] / $total * 100, 1 ) : 0.0,
		];
	}

	/**
	 * Suggest donor posts that could add an internal link to the given orphan.
	 *
	 * Scoring: Jaccard similarity on keyword sets, boosted by recent publication.
	 *
	 * @param int $post_id   Target (orphan) post ID.
	 * @param int $limit     Maximum number of suggestions.
	 * @return array<int, array{post_id: int, title: string, url: string, overlap_score: float}>
	 */
	public function suggest_links_for_orphan( int $post_id, int $limit = self::MAX_DONOR_SUGGESTIONS ): array {
		$orphan_keywords = $this->get_keywords( $post_id );
		if ( empty( $orphan_keywords ) ) {
			return [];
		}

		$posts       = $this->get_published_posts();
		$suggestions = [];

		foreach ( $posts as $post ) {
			$donor_id = (int) $post->ID;
			if ( $donor_id === $post_id ) {
				continue;
			}

			// Skip donors that already link to the orphan.
			$content = $post->post_content ?? '';
			$url     = get_permalink( $post_id );
			if ( $url && str_contains( $content, $url ) ) {
				continue;
			}

			$donor_keywords = $this->get_keywords( $donor_id );
			$overlap        = $this->jaccard_similarity( $orphan_keywords, $donor_keywords );

			if ( $overlap >= self::MIN_OVERLAP_SCORE ) {
				$suggestions[] = [
					'post_id'       => $donor_id,
					'title'         => get_the_title( $donor_id ),
					'url'           => get_permalink( $donor_id ),
					'overlap_score' => round( $overlap, 4 ),
				];
			}
		}

		usort( $suggestions, static fn( $a, $b ) => $b['overlap_score'] <=> $a['overlap_score'] );

		return array_slice( $suggestions, 0, $limit );
	}

	/**
	 * Return a full orphan report combining distribution and orphan list.
	 *
	 * @return array{distribution: array, orphans: array, generated_at: string}
	 */
	public function get_report(): array {
		return [
			'distribution' => $this->get_link_equity_distribution(),
			'orphans'      => $this->get_orphan_pages( [ 'limit' => 100 ] ),
			'generated_at' => gmdate( 'c' ),
		];
	}

	/**
	 * Bust the cached link map (called on post save).
	 */
	public function bust_cache(): void {
		delete_transient( self::TRANSIENT_LINK_MAP . '_post' );
		delete_transient( self::TRANSIENT_LINK_MAP . '_page' );
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * REST: list orphan pages.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_list_orphans( \WP_REST_Request $request ): \WP_REST_Response {
		$orphans = $this->get_orphan_pages( [ 'limit' => (int) $request->get_param( 'limit' ) ] );
		return new \WP_REST_Response( [ 'orphans' => $orphans, 'count' => count( $orphans ) ], 200 );
	}

	/**
	 * REST: return the full orphan report.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_report( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( $this->get_report(), 200 );
	}

	/**
	 * REST: suggest donor posts for a given post_id.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_suggest_donors( \WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );
		$limit   = (int) $request->get_param( 'limit' );

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return new \WP_Error( 'invalid_post', 'Post not found or not published.', [ 'status' => 404 ] );
		}

		$donors = $this->suggest_links_for_orphan( $post_id, $limit );
		return new \WP_REST_Response( [ 'post_id' => $post_id, 'donors' => $donors ], 200 );
	}

	/**
	 * Check manage_options capability.
	 *
	 * @return bool|\WP_Error
	 */
	public function admin_permission() {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Return all published posts (with content) for a given post type.
	 *
	 * @param string $post_type Post type.
	 * @return \WP_Post[]
	 */
	private function get_published_posts( string $post_type = 'post' ): array {
		return get_posts( [
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		] );
	}

	/**
	 * Return the keyword set for a post (from cluster meta or title tokens).
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	private function get_keywords( int $post_id ): array {
		$cluster = get_post_meta( $post_id, self::META_KEYWORDS, true );
		if ( is_array( $cluster ) && ! empty( $cluster['keywords'] ) ) {
			return array_map( 'strtolower', (array) $cluster['keywords'] );
		}
		// Fallback: tokenise the post title.
		$title = get_the_title( $post_id );
		return array_filter( array_map( 'strtolower', preg_split( '/\s+/', $title ) ?? [] ) );
	}

	/**
	 * Compute Jaccard similarity between two keyword sets.
	 *
	 * @param string[] $a First keyword set.
	 * @param string[] $b Second keyword set.
	 * @return float Similarity score in [0, 1].
	 */
	private function jaccard_similarity( array $a, array $b ): float {
		$a         = array_unique( $a );
		$b         = array_unique( $b );
		$intersect = count( array_intersect( $a, $b ) );
		$union     = count( array_unique( array_merge( $a, $b ) ) );
		return $union > 0 ? $intersect / $union : 0.0;
	}
}
