<?php
/**
 * Internal linker – automatically inserts contextual internal links into content.
 *
 * Strategy:
 *  1. Query published posts and extract their titles and slugs.
 *  2. For each candidate post, collect its KeywordCluster data (stored as
 *     post meta `pearblog_keyword_cluster`) to build a keyword ↔ post map.
 *  3. Scan the target content for occurrences of those keywords.
 *  4. Inject up to MAX_LINKS hyperlinks, preferring longer / more specific
 *     keywords over short ones, and skipping links already present.
 *  5. Never link the same keyword twice in one article, and never self-link.
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Injects contextual internal links into HTML content.
 */
class InternalLinker {

	/** Maximum number of internal links to insert per article. */
	private const MAX_LINKS = 5;

	/** Minimum keyword length to consider for linking (characters). */
	private const MIN_KEYWORD_LENGTH = 4;

	/** Meta key under which keyword-cluster data is stored on each post. */
	public const META_KEY_CLUSTER = 'pearblog_keyword_cluster';

	/**
	 * Scan $content for keywords belonging to published posts and insert links.
	 *
	 * @param string $content HTML article content.
	 * @param int    $post_id The ID of the post being processed (excluded from candidates).
	 * @return string         Content with internal links injected.
	 */
	public function apply( string $content, int $post_id ): string {
		$candidates = $this->build_candidate_map( $post_id );
		if ( empty( $candidates ) ) {
			return $content;
		}

		// Sort by keyword length descending so longer phrases are matched first.
		uksort( $candidates, static fn( $a, $b ) => mb_strlen( $b ) - mb_strlen( $a ) );

		$links_added    = 0;
		$linked_phrases = [];

		foreach ( $candidates as $keyword => $candidate ) {
			if ( $links_added >= self::MAX_LINKS ) {
				break;
			}

			// Skip if we already linked this phrase.
			if ( in_array( mb_strtolower( $keyword ), $linked_phrases, true ) ) {
				continue;
			}

			// Avoid inserting inside existing HTML tags / links.
			$pattern = '/(?<!["\'>])(?<!<a[^>]*>)(\b' . preg_quote( $keyword, '/' ) . '\b)(?![^<]*<\/a>)/iu';

			if ( ! preg_match( $pattern, $content ) ) {
				continue;
			}

			$link    = sprintf(
				'<a href="%s" title="%s">$1</a>',
				esc_url( $candidate['url'] ),
				esc_attr( $candidate['title'] )
			);
			$content = preg_replace( $pattern, $link, $content, 1 );

			$linked_phrases[] = mb_strtolower( $keyword );
			$links_added++;
		}

		/**
		 * Filter: pearblog_internal_links_applied
		 *
		 * @param string $content     Content after link injection.
		 * @param int    $post_id     Post being processed.
		 * @param int    $links_added Number of links actually inserted.
		 */
		return (string) apply_filters( 'pearblog_internal_links_applied', $content, $post_id, $links_added );
	}

	/**
	 * Backfill internal links for all published posts.
	 *
	 * Designed to be called from a WP-CLI command or admin action.
	 *
	 * @param int $batch_size Number of posts to process per call.
	 * @return int            Number of posts updated.
	 */
	public function backfill( int $batch_size = 20 ): int {
		$posts = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => $batch_size,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => '_pearblog_internal_links_applied',
					'compare' => 'NOT EXISTS',
				],
			],
		] );

		$updated = 0;
		foreach ( $posts as $post ) {
			$new_content = $this->apply( $post->post_content, $post->ID );
			if ( $new_content !== $post->post_content ) {
				wp_update_post( [
					'ID'           => $post->ID,
					'post_content' => $new_content,
				] );
				$updated++;
			}
			update_post_meta( $post->ID, '_pearblog_internal_links_applied', current_time( 'mysql' ) );
		}

		return $updated;
	}

	/**
	 * Store keyword cluster data on a post so it can be found by the linker.
	 *
	 * @param int    $post_id  Post ID.
	 * @param array  $cluster  Array of keywords (pillar + supporting).
	 */
	public static function store_cluster( int $post_id, array $cluster ): void {
		update_post_meta( $post_id, self::META_KEY_CLUSTER, $cluster );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Build a map of keyword → [url, title] from all published posts.
	 *
	 * @param int $exclude_id Post ID to exclude from candidates (the current post).
	 * @return array<string, array{url: string, title: string}>
	 */
	private function build_candidate_map( int $exclude_id ): array {
		$posts = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'post__not_in'   => [ $exclude_id ],
			'fields'         => 'ids',
		] );

		$map = [];

		foreach ( $posts as $pid ) {
			$url   = (string) get_permalink( $pid );
			$title = (string) get_the_title( $pid );

			// Always include the post title as a linkable keyword.
			if ( mb_strlen( $title ) >= self::MIN_KEYWORD_LENGTH ) {
				$map[ $title ] = [ 'url' => $url, 'title' => $title ];
			}

			// Also include stored keyword-cluster keywords.
			$cluster = get_post_meta( $pid, self::META_KEY_CLUSTER, true );
			if ( is_array( $cluster ) ) {
				foreach ( $cluster as $kw ) {
					$kw = trim( (string) $kw );
					if ( mb_strlen( $kw ) >= self::MIN_KEYWORD_LENGTH ) {
						$map[ $kw ] = [ 'url' => $url, 'title' => $title ];
					}
				}
			}
		}

		return $map;
	}
}
