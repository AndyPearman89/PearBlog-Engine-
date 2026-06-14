<?php
/**
 * Orphan Page Detector — V9.0 F8
 *
 * Identifies published posts/pages that receive no internal links from other
 * published content, making them invisible to both crawlers and readers.
 * Provides scan results, link-suggestion hints, and remediation tracking.
 *
 * All computation is pure-PHP using WordPress meta/post APIs (stubbed for
 * unit testing).
 *
 * @package PearBlogEngine\SEO
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Detects and manages orphaned pages.
 */
class OrphanPageDetector {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** Option key: last-scan timestamp (Unix). */
	public const OPT_LAST_SCAN = 'pearblog_orphan_last_scan';

	/** Option key: cached orphan post IDs (JSON array). */
	public const OPT_ORPHAN_CACHE = 'pearblog_orphan_cache';

	/** Meta key: set to '1' on posts that have been manually reviewed/fixed. */
	public const META_REVIEWED = '_pearblog_orphan_reviewed';

	/** Meta key: JSON list of suggested linking post IDs. */
	public const META_SUGGESTIONS = '_pearblog_orphan_suggestions';

	/** Number of link-suggestion candidates to return per orphan. */
	public const SUGGESTION_COUNT = 5;

	/** Post types considered for orphan detection. */
	private const SCAN_POST_TYPES = [ 'post', 'page' ];

	// -----------------------------------------------------------------------
	// Public API — Detection
	// -----------------------------------------------------------------------

	/**
	 * Run a full orphan scan across all published content.
	 *
	 * @param  bool $force_refresh Bypass the cached result and re-scan.
	 * @return array{
	 *     orphans: int[],
	 *     total_scanned: int,
	 *     orphan_count: int,
	 *     scanned_at: string,
	 *     cached: bool,
	 * }
	 */
	public function scan( bool $force_refresh = false ): array {
		if ( ! $force_refresh ) {
			$cached = $this->get_cached_scan();
			if ( null !== $cached ) {
				return $cached;
			}
		}

		$all_ids     = $this->get_all_published_ids();
		$linked_ids  = $this->get_internally_linked_ids( $all_ids );
		$orphan_ids  = array_values( array_diff( $all_ids, $linked_ids ) );

		// Exclude posts the editor has already reviewed/fixed.
		$orphan_ids = array_values( array_filter(
			$orphan_ids,
			fn( int $id ): bool => ! $this->is_reviewed( $id )
		) );

		$result = [
			'orphans'       => $orphan_ids,
			'total_scanned' => count( $all_ids ),
			'orphan_count'  => count( $orphan_ids ),
			'scanned_at'    => gmdate( 'c' ),
			'cached'        => false,
		];

		$this->save_scan_cache( $result );
		return $result;
	}

	/**
	 * Return detailed info for a single orphan post.
	 *
	 * @param  int $post_id Post ID.
	 * @return array{
	 *     post_id: int,
	 *     title: string,
	 *     url: string,
	 *     post_type: string,
	 *     published_at: string,
	 *     inbound_count: int,
	 *     is_reviewed: bool,
	 *     suggestions: int[],
	 * }
	 */
	public function get_orphan_detail( int $post_id ): array {
		$all_ids      = $this->get_all_published_ids();
		$inbound      = $this->count_inbound_links( $post_id, $all_ids );

		return [
			'post_id'      => $post_id,
			'title'        => (string) get_the_title( $post_id ),
			'url'          => (string) get_permalink( $post_id ),
			'post_type'    => (string) get_post_field( 'post_type', $post_id ),
			'published_at' => (string) get_post_field( 'post_date_gmt', $post_id ),
			'inbound_count' => $inbound,
			'is_reviewed'  => $this->is_reviewed( $post_id ),
			'suggestions'  => $this->get_suggestions( $post_id ),
		];
	}

	// -----------------------------------------------------------------------
	// Public API — Remediation
	// -----------------------------------------------------------------------

	/**
	 * Mark a post as reviewed/fixed so it is excluded from future scans.
	 *
	 * @param  int $post_id Post ID to mark.
	 */
	public function mark_reviewed( int $post_id ): void {
		update_post_meta( $post_id, self::META_REVIEWED, '1' );
		$this->invalidate_cache();
	}

	/**
	 * Clear the reviewed flag, re-exposing the post to future scans.
	 *
	 * @param  int $post_id Post ID.
	 */
	public function unmark_reviewed( int $post_id ): void {
		delete_post_meta( $post_id, self::META_REVIEWED );
		$this->invalidate_cache();
	}

	/**
	 * Generate and persist linking suggestions for an orphan post.
	 *
	 * Finds published posts whose titles share the most words with the orphan's
	 * title, returning up to SUGGESTION_COUNT candidates.
	 *
	 * @param  int $post_id Orphan post ID.
	 * @return int[] Suggested post IDs.
	 */
	public function generate_suggestions( int $post_id ): array {
		$all_ids     = $this->get_all_published_ids();
		$candidates  = array_diff( $all_ids, [ $post_id ] );
		$orphan_title = strtolower( (string) get_the_title( $post_id ) );
		$orphan_words = $this->title_words( $orphan_title );

		$scores = [];
		foreach ( $candidates as $cid ) {
			$cwords          = $this->title_words( strtolower( (string) get_the_title( $cid ) ) );
			$common          = count( array_intersect( $orphan_words, $cwords ) );
			if ( $common > 0 ) {
				$scores[ $cid ] = $common;
			}
		}

		arsort( $scores );
		$suggestions = array_slice( array_keys( $scores ), 0, self::SUGGESTION_COUNT );
		update_post_meta( $post_id, self::META_SUGGESTIONS, wp_json_encode( $suggestions ) );
		return $suggestions;
	}

	/**
	 * Retrieve previously generated suggestions for a post.
	 *
	 * @param  int $post_id Post ID.
	 * @return int[]
	 */
	public function get_suggestions( int $post_id ): array {
		$raw     = (string) get_post_meta( $post_id, self::META_SUGGESTIONS, true );
		$decoded = $raw !== '' ? json_decode( $raw, true ) : null;
		return is_array( $decoded ) ? array_map( 'intval', $decoded ) : [];
	}

	/**
	 * Invalidate the orphan scan cache, forcing a fresh scan on next call.
	 */
	public function invalidate_cache(): void {
		delete_option( self::OPT_ORPHAN_CACHE );
		delete_option( self::OPT_LAST_SCAN );
	}

	// -----------------------------------------------------------------------
	// Internal helpers — Link analysis
	// -----------------------------------------------------------------------

	/**
	 * Return all published post IDs across the configured post types.
	 *
	 * @return int[]
	 */
	private function get_all_published_ids(): array {
		$raw = get_posts( [
			'post_type'      => self::SCAN_POST_TYPES,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );
		return array_map( 'intval', is_array( $raw ) ? $raw : [] );
	}

	/**
	 * Return post IDs that appear at least once as an href in another post's content.
	 *
	 * @param  int[] $all_ids All published post IDs.
	 * @return int[] IDs that are linked to.
	 */
	private function get_internally_linked_ids( array $all_ids ): array {
		$linked = [];

		foreach ( $all_ids as $source_id ) {
			$content = (string) get_post_field( 'post_content', $source_id );
			if ( '' === $content ) {
				continue;
			}

			// Extract href attribute values from <a> tags.
			preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches );
			$hrefs = $matches[1] ?? [];

			foreach ( $hrefs as $href ) {
				// Try to resolve the URL to a post ID.
				$target_id = $this->url_to_post_id( $href );
				if ( $target_id > 0 && $target_id !== $source_id ) {
					$linked[ $target_id ] = true;
				}
			}
		}

		return array_keys( $linked );
	}

	/**
	 * Count how many posts link to a given target post.
	 *
	 * @param  int   $target_id Target post ID.
	 * @param  int[] $all_ids   All published post IDs (scope of search).
	 * @return int
	 */
	private function count_inbound_links( int $target_id, array $all_ids ): int {
		$target_url = (string) get_permalink( $target_id );
		$count      = 0;

		foreach ( $all_ids as $source_id ) {
			if ( $source_id === $target_id ) {
				continue;
			}
			$content = (string) get_post_field( 'post_content', $source_id );
			if ( $content !== '' && str_contains( $content, $target_url ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Map a URL string to a WordPress post ID (0 if not matched).
	 *
	 * Uses `url_to_postid()` when available; falls back to searching _posts global.
	 *
	 * @param  string $url URL to resolve.
	 * @return int Post ID or 0.
	 */
	private function url_to_post_id( string $url ): int {
		if ( function_exists( 'url_to_postid' ) ) {
			return (int) url_to_postid( $url );
		}

		// Test-environment fallback: search _posts global.
		$posts = $GLOBALS['_posts'] ?? [];
		foreach ( $posts as $post ) {
			if ( isset( $post->guid ) && $post->guid === $url ) {
				return (int) $post->ID;
			}
		}

		return 0;
	}

	// -----------------------------------------------------------------------
	// Internal helpers — Misc
	// -----------------------------------------------------------------------

	/**
	 * Check whether a post has been marked as reviewed.
	 *
	 * @param  int $post_id Post ID.
	 * @return bool
	 */
	private function is_reviewed( int $post_id ): bool {
		return '1' === (string) get_post_meta( $post_id, self::META_REVIEWED, true );
	}

	/**
	 * Split a title string into an array of meaningful words (stop-words removed).
	 *
	 * @param  string $title Lowercase title.
	 * @return string[]
	 */
	private function title_words( string $title ): array {
		$stop  = [ 'the', 'a', 'an', 'is', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 'but', 'with', 'how', 'what', 'why', 'when', 'where' ];
		$words = preg_split( '/\W+/', $title, -1, PREG_SPLIT_NO_EMPTY ) ?: [];
		return array_values( array_diff( $words, $stop ) );
	}

	// -----------------------------------------------------------------------
	// Cache helpers
	// -----------------------------------------------------------------------

	/**
	 * @return array|null Cached scan result or null if absent/expired.
	 */
	private function get_cached_scan(): ?array {
		$raw = (string) get_option( self::OPT_ORPHAN_CACHE, '' );
		if ( '' === $raw ) {
			return null;
		}
		$result = json_decode( $raw, true );
		if ( ! is_array( $result ) ) {
			return null;
		}
		$result['cached'] = true;
		return $result;
	}

	private function save_scan_cache( array $result ): void {
		update_option( self::OPT_ORPHAN_CACHE, wp_json_encode( $result ) );
		update_option( self::OPT_LAST_SCAN, time() );
	}
}
