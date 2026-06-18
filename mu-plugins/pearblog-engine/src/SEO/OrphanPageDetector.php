<?php
/**
 * Orphan Page Detector – V9.0 F8: identifies pages with no internal links.
 *
 * A page is considered "orphaned" when no other published post or page on the
 * same site links to it.  Orphan pages receive little link equity and are
 * typically poorly indexed by search engines.
 *
 * This module:
 *   1. Scans all published posts for outbound internal links.
 *   2. Compares the complete URL set against the linked-to set.
 *   3. Marks orphans in WP option `pearblog_orphan_pages`.
 *   4. Exposes results via REST GET /wp-json/pearblog/v1/seo/orphan-pages.
 *   5. Fires action `pearblog_orphan_detected` for each newly found orphan.
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Detects published posts/pages that have no internal inbound links.
 */
class OrphanPageDetector {

	/** WP option: serialised orphan report. */
	public const OPTION_ORPHANS  = 'pearblog_orphan_pages';

	/** WP option: last scan timestamp. */
	public const OPTION_LAST_RUN = 'pearblog_orphan_last_run';

	/** Cron hook. */
	private const CRON_HOOK      = 'pearblog_orphan_scan';

	/** REST namespace. */
	private const REST_NAMESPACE = 'pearblog/v1';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'scan' ] );
		add_action( 'init', [ $this, 'maybe_schedule' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Schedule weekly scan if not already scheduled.
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
			'/seo/orphan-pages',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_get_orphans' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			]
		);
	}

	// -----------------------------------------------------------------------
	// Scanning
	// -----------------------------------------------------------------------

	/**
	 * Scan all published posts/pages for orphans.
	 *
	 * @param string[]|null $post_permalinks Optional: inject set of all post URLs (for testing).
	 * @param string[]|null $linked_urls     Optional: inject set of all inbound-linked URLs (for testing).
	 * @return array<int,array{post_id:int,permalink:string,age_days:int}> Orphan list.
	 */
	public function scan( ?array $post_permalinks = null, ?array $linked_urls = null ): array {
		$post_types = apply_filters( 'pearblog_orphan_post_types', [ 'post', 'page' ] );

		// Build map: permalink → post_id.
		$all_posts   = get_posts( [
			'post_status'    => 'publish',
			'post_type'      => $post_types,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );

		$permalink_map = [];
		foreach ( $all_posts as $id ) {
			$url = get_permalink( (int) $id );
			if ( $url ) {
				$permalink_map[ $this->normalise_url( $url ) ] = (int) $id;
			}
		}

		if ( null !== $post_permalinks ) {
			$permalink_map = [];
			foreach ( $post_permalinks as $url ) {
				$permalink_map[ $this->normalise_url( $url ) ] = 0;
			}
		}

		// Collect all internally linked URLs from post content.
		if ( null === $linked_urls ) {
			$linked_urls = $this->collect_linked_urls( $all_posts );
		}

		$linked_set = [];
		foreach ( $linked_urls as $url ) {
			$linked_set[ $this->normalise_url( $url ) ] = true;
		}

		// Compute orphans.
		$previous_orphans = $this->load_orphans();
		$orphans          = [];

		foreach ( $permalink_map as $norm_url => $post_id ) {
			if ( isset( $linked_set[ $norm_url ] ) ) {
				continue;
			}

			$age = 0;
			if ( $post_id > 0 ) {
				$modified = get_post_modified_time( 'U', true, $post_id );
				$age      = $modified ? (int) floor( ( time() - (int) $modified ) / DAY_IN_SECONDS ) : 0;
			}

			$orphans[] = [
				'post_id'   => $post_id,
				'permalink' => $norm_url,
				'age_days'  => $age,
			];

			// Fire action for newly detected orphans.
			$was_orphan = array_filter( $previous_orphans, static fn( $o ) => $o['post_id'] === $post_id );
			if ( $post_id > 0 && empty( $was_orphan ) ) {
				do_action( 'pearblog_orphan_detected', $post_id );
			}
		}

		update_option( self::OPTION_ORPHANS, $orphans );
		update_option( self::OPTION_LAST_RUN, gmdate( 'Y-m-d\TH:i:s\Z' ) );

		return $orphans;
	}

	/**
	 * Extract all internal hrefs from post content.
	 *
	 * @param int[] $post_ids
	 * @return string[]
	 */
	public function collect_linked_urls( array $post_ids ): array {
		$home = trailingslashit( home_url() );
		$urls = [];

		foreach ( $post_ids as $id ) {
			$content = get_post_field( 'post_content', (int) $id );
			if ( ! $content ) {
				continue;
			}

			preg_match_all(
				'/<a\s[^>]*href=["\'](' . preg_quote( $home, '/' ) . '[^"\']*)["\'][^>]*>/i',
				$content,
				$matches
			);

			foreach ( $matches[1] ?? [] as $url ) {
				$urls[] = $url;
			}
		}

		return array_unique( $urls );
	}

	/**
	 * Normalise a URL for comparison (strip trailing slash, query string, fragment).
	 *
	 * @param string $url
	 * @return string
	 */
	public function normalise_url( string $url ): string {
		$url = strtok( $url, '?' );
		$url = strtok( (string) $url, '#' );
		return rtrim( (string) $url, '/' );
	}

	// -----------------------------------------------------------------------
	// REST handler
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_orphans( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response( [
			'orphans'  => $this->load_orphans(),
			'last_run' => get_option( self::OPTION_LAST_RUN, null ),
		], 200 );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * @return array<int,array{post_id:int,permalink:string,age_days:int}>
	 */
	private function load_orphans(): array {
		$raw = get_option( self::OPTION_ORPHANS, [] );
		return is_array( $raw ) ? $raw : [];
	}
}
