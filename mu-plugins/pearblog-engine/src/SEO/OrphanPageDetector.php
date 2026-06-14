<?php
/**
 * Orphan Page Detector — F8 (v9.0)
 *
 * Identifies "orphan pages" — published posts that have zero internal links
 * pointing to them from other published posts — and provides tools to fix them.
 *
 * Detection logic:
 *   1. Collect all inter-post internal links by scanning post content.
 *   2. Find posts that appear in zero link targets.
 *   3. Score each orphan by its quality score (high quality orphans = priority).
 *
 * Remediation:
 *   - suggest_links(): asks InternalLinker to find anchor placements in other posts.
 *   - apply_fix(): inserts a contextual link into the most relevant nearby post.
 *
 * Storage:
 *   pearblog_orphans   – JSON list of { post_id, title, quality_score, detected_at }
 *   pearblog_orphans_last_scan – Unix timestamp
 *
 * REST:
 *   GET  /pearblog/v1/seo/orphans        – list detected orphan pages
 *   POST /pearblog/v1/seo/orphans/scan   – trigger a full re-scan
 *   POST /pearblog/v1/seo/orphans/{id}/fix – apply auto-fix for one orphan
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Detects orphan pages and orchestrates link remediation.
 */
class OrphanPageDetector {

	/** WP option: persisted orphan list. */
	public const OPTION_ORPHANS   = 'pearblog_orphans';
	public const OPTION_LAST_SCAN = 'pearblog_orphans_last_scan';

	/** WP cron hook. */
	public const CRON_HOOK = 'pearblog_orphan_scan';

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'scan' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/seo/orphans', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_list' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/seo/orphans/scan', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_scan' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/seo/orphans/(?P<id>\d+)/fix', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_fix' ],
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

	public function rest_list(): \WP_REST_Response {
		return new \WP_REST_Response( [
			'orphans'   => $this->get_orphans(),
			'last_scan' => get_option( self::OPTION_LAST_SCAN, null ),
		] );
	}

	public function rest_scan(): \WP_REST_Response {
		$count = count( $this->scan() );
		return new \WP_REST_Response( [ 'orphans_found' => $count ] );
	}

	public function rest_fix( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['id'];
		$fixed   = $this->apply_fix( $post_id );

		return new \WP_REST_Response( [ 'fixed' => $fixed, 'post_id' => $post_id ] );
	}

	// -----------------------------------------------------------------------
	// Detection
	// -----------------------------------------------------------------------

	/**
	 * Perform a full orphan scan and persist results.
	 *
	 * @return array<int, array{post_id:int, title:string, quality_score:float, detected_at:string}>
	 */
	public function scan(): array {
		$posts        = $this->get_all_published_posts();
		$link_targets = $this->collect_link_targets( $posts );
		$orphans      = [];

		foreach ( $posts as $post ) {
			$id = (int) $post->ID;
			if ( in_array( $id, $link_targets, true ) ) {
				continue;
			}

			$quality = (float) get_post_meta( $id, '_pearblog_quality_score', true );

			$orphans[] = [
				'post_id'       => $id,
				'title'         => $post->post_title,
				'quality_score' => $quality,
				'detected_at'   => gmdate( 'Y-m-d\TH:i:s\Z' ),
			];
		}

		// Sort by quality_score descending so high-value orphans appear first.
		usort( $orphans, static fn( $a, $b ) => $b['quality_score'] <=> $a['quality_score'] );

		update_option( self::OPTION_ORPHANS, $orphans );
		update_option( self::OPTION_LAST_SCAN, time() );

		do_action( 'pearblog_orphans_detected', $orphans );

		return $orphans;
	}

	/**
	 * Check if a given post ID is an orphan (not linked to by any other post).
	 *
	 * @param int   $post_id
	 * @param array $posts   Pre-fetched post objects (optional optimisation).
	 * @return bool
	 */
	public function is_orphan( int $post_id, array $posts = [] ): bool {
		if ( empty( $posts ) ) {
			$posts = $this->get_all_published_posts();
		}
		$targets = $this->collect_link_targets( $posts );
		return ! in_array( $post_id, $targets, true );
	}

	/**
	 * Collect all internal link target post IDs from a list of post objects.
	 *
	 * @param \WP_Post[]|object[] $posts
	 * @return int[]
	 */
	public function collect_link_targets( array $posts ): array {
		$targets  = [];
		$home_url = function_exists( 'home_url' ) ? home_url( '/' ) : '/';

		foreach ( $posts as $post ) {
			$content = $post->post_content ?? '';
			preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches );

			foreach ( $matches[1] as $href ) {
				// Resolve to post ID if it's an internal URL.
				if ( function_exists( 'url_to_postid' ) ) {
					$target_id = url_to_postid( $href );
					if ( $target_id > 0 && $target_id !== (int) $post->ID ) {
						$targets[] = $target_id;
					}
				}
			}
		}

		return array_unique( array_map( 'intval', $targets ) );
	}

	// -----------------------------------------------------------------------
	// Remediation
	// -----------------------------------------------------------------------

	/**
	 * Attempt to fix an orphan by adding a link to it from the most relevant post.
	 *
	 * This fires the `pearblog_orphan_fix` action with the post ID so that
	 * InternalLinker or a custom handler can perform the actual link insertion.
	 *
	 * @param int $post_id  The orphan post to fix.
	 * @return bool         True if a fix was applied.
	 */
	public function apply_fix( int $post_id ): bool {
		if ( ! function_exists( 'get_post' ) || ! get_post( $post_id ) ) {
			return false;
		}

		$fixed = (bool) apply_filters( 'pearblog_orphan_fix', false, $post_id );
		do_action( 'pearblog_orphan_fixed', $post_id, $fixed );

		if ( $fixed ) {
			// Remove from stored orphan list.
			$orphans  = $this->get_orphans();
			$orphans  = array_filter( $orphans, static fn( $o ) => (int) $o['post_id'] !== $post_id );
			update_option( self::OPTION_ORPHANS, array_values( $orphans ) );
		}

		return $fixed;
	}

	// -----------------------------------------------------------------------
	// Storage
	// -----------------------------------------------------------------------

	/** @return array<int, array{post_id:int, title:string, quality_score:float, detected_at:string}> */
	public function get_orphans(): array {
		$raw = get_option( self::OPTION_ORPHANS, [] );
		return is_array( $raw ) ? $raw : [];
	}

	// -----------------------------------------------------------------------
	// WordPress helpers (overridable in tests)
	// -----------------------------------------------------------------------

	/** @return \WP_Post[]|object[] */
	protected function get_all_published_posts(): array {
		if ( ! function_exists( 'get_posts' ) ) {
			return [];
		}
		return get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 2000,
			'no_found_rows'  => true,
		] ) ?: [];
	}
}
