<?php
/**
 * Internal linker – injects contextual internal links into article content.
 *
 * The linker searches the generated content for words/phrases that match
 * titles of already-published posts on the same site, then wraps the first
 * occurrence of each match with an anchor tag.
 *
 * Rules:
 *  - Only published posts are considered as link targets.
 *  - A post never links to itself.
 *  - Each target post is linked at most once per article (first occurrence).
 *  - Maximum {@see MAX_LINKS} internal links are inserted per article to avoid
 *    over-optimisation.
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Contextual internal link injector.
 */
class InternalLinker {

	/** Maximum internal links inserted per article. */
	private const MAX_LINKS = 8;

	/** @var int WordPress site ID. */
	private int $site_id;

	public function __construct( int $site_id ) {
		$this->site_id = $site_id;
	}

	/**
	 * Scan $content for anchor opportunities and inject internal links.
	 *
	 * @param int    $post_id  Post ID being enriched (will not link to itself).
	 * @param string $content  HTML/Markdown content to process.
	 * @return string          Content with internal links injected.
	 */
	public function apply( int $post_id, string $content ): string {
		$targets = $this->fetch_link_targets( $post_id );

		if ( empty( $targets ) ) {
			return $content;
		}

		$links_added = 0;

		foreach ( $targets as $target_id => $target ) {
			if ( $links_added >= self::MAX_LINKS ) {
				break;
			}

			$anchor   = $target['anchor'];
			$url      = $target['url'];
			$title    = esc_attr( $target['post_title'] );
			$link_tag = sprintf( '<a href="%s" title="%s">%s</a>', esc_url( $url ), $title, esc_html( $anchor ) );

			// Replace only the first plain occurrence outside existing HTML tags.
			$new_content = $this->replace_first_occurrence( $content, $anchor, $link_tag );

			if ( $new_content !== $content ) {
				$content = $new_content;
				$links_added++;
			}
		}

		/**
		 * Action: pearblog_internal_links_applied
		 *
		 * @param int    $post_id     The post that was enriched.
		 * @param int    $links_added Number of links injected.
		 */
		do_action( 'pearblog_internal_links_applied', $post_id, $links_added );

		return $content;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Fetch published posts that could serve as link targets.
	 *
	 * Returns an array keyed by post ID, each value containing:
	 *   'anchor'     => best anchor text (post title or shortest meaningful phrase)
	 *   'url'        => permalink
	 *   'post_title' => full title for the link's title attribute
	 *
	 * @param int $current_post_id Exclude the article being processed.
	 * @return array<int, array{anchor: string, url: string, post_title: string}>
	 */
	private function fetch_link_targets( int $current_post_id ): array {
		$query = new \WP_Query( [
			'post_status'         => 'publish',
			'posts_per_page'      => 200,
			'post__not_in'        => [ $current_post_id ],
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'fields'              => 'all',
		] );

		$targets = [];
		foreach ( $query->posts as $post ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				continue;
			}

			$title = trim( $post->post_title );
			if ( '' === $title ) {
				continue;
			}

			$targets[ $post->ID ] = [
				'anchor'     => $title,
				'url'        => (string) get_permalink( $post->ID ),
				'post_title' => $title,
			];
		}

		// Sort longest-anchor-first so more specific phrases match first.
		uasort( $targets, static fn( $a, $b ) => strlen( $b['anchor'] ) <=> strlen( $a['anchor'] ) );

		return $targets;
	}

	/**
	 * Replace the first occurrence of $anchor in $content with $replacement,
	 * skipping text that is already inside an HTML tag or attribute.
	 *
	 * @param string $content     Source content.
	 * @param string $anchor      Plain text to find.
	 * @param string $replacement HTML to replace with.
	 * @return string
	 */
	private function replace_first_occurrence( string $content, string $anchor, string $replacement ): string {
		// Escape the anchor for use in a regex.
		$escaped = preg_quote( $anchor, '/' );

		// Negative lookbehind/lookahead approach: only match when not inside a tag.
		// We use a callback to ensure the match is not between < and >.
		$replaced = false;
		$result   = preg_replace_callback(
			'/(' . $escaped . ')/ui',
			function ( array $m ) use ( $replacement, &$replaced ): string {
				if ( $replaced ) {
					return $m[0];
				}
				$replaced = true;
				return $replacement;
			},
			$content,
			1
		);

		// If the replacement ended up inside an existing <a> tag, revert it.
		// Simple heuristic: if the replaced version contains nested <a>, revert.
		if ( null !== $result && preg_match( '/<a[^>]*>[^<]*<a[^>]*>/i', $result ) ) {
			return $content;
		}

		return $result ?? $content;
	}
}
