<?php
/**
 * Internal linker – injects contextual internal links into article content.
 *
 * Scans existing published posts for keyword overlap with the new article
 * and inserts internal links at relevant points to improve SEO and site
 * navigation.
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Adds internal links to content based on keyword matching with existing posts.
 */
class InternalLinker {

	/** Maximum number of internal links to inject per article. */
	private const MAX_LINKS = 5;

	/** Minimum word count before a link is considered. */
	private const MIN_WORDS_BETWEEN_LINKS = 150;

	/**
	 * Inject internal links into article content.
	 *
	 * @param int    $post_id Current post ID (excluded from candidates).
	 * @param string $content Article HTML content.
	 * @return string Content with internal links injected.
	 */
	public function apply( int $post_id, string $content ): string {
		$candidates = $this->find_link_candidates( $post_id, $content );

		if ( empty( $candidates ) ) {
			return $content;
		}

		$links_added = 0;

		foreach ( $candidates as $candidate ) {
			if ( $links_added >= self::MAX_LINKS ) {
				break;
			}

			$linked_content = $this->inject_link( $content, $candidate );

			if ( $linked_content !== $content ) {
				$content = $linked_content;
				$links_added++;
			}
		}

		if ( $links_added > 0 ) {
			update_post_meta( $post_id, '_pearblog_internal_links_count', $links_added );

			/**
			 * Action: pearblog_internal_links_added
			 *
			 * @param int $post_id     Post ID.
			 * @param int $links_added Number of links injected.
			 */
			do_action( 'pearblog_internal_links_added', $post_id, $links_added );
		}

		return $content;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Find published posts whose titles overlap with the content.
	 *
	 * Returns an array of candidate arrays sorted by relevance (keyword hits).
	 *
	 * @param int    $exclude_id Post ID to exclude.
	 * @param string $content    Article content.
	 * @return array<int, array{post_id: int, title: string, url: string, keywords: string[], hits: int}>
	 */
	private function find_link_candidates( int $exclude_id, string $content ): array {
		$text = mb_strtolower( wp_strip_all_tags( $content ) );

		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'exclude'        => [ $exclude_id ],
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		] );

		if ( empty( $posts ) ) {
			return [];
		}

		$candidates = [];

		foreach ( $posts as $candidate_id ) {
			$title = get_the_title( $candidate_id );
			$url   = get_permalink( $candidate_id );

			if ( empty( $title ) || empty( $url ) ) {
				continue;
			}

			// Extract meaningful keywords from the candidate title (3+ chars).
			$keywords = $this->extract_keywords( $title );
			$hits     = 0;

			foreach ( $keywords as $kw ) {
				if ( false !== mb_strpos( $text, mb_strtolower( $kw ) ) ) {
					$hits++;
				}
			}

			if ( $hits > 0 ) {
				$candidates[] = [
					'post_id'  => $candidate_id,
					'title'    => $title,
					'url'      => $url,
					'keywords' => $keywords,
					'hits'     => $hits,
				];
			}
		}

		// Sort by hits descending.
		usort( $candidates, static fn( array $a, array $b ): int => $b['hits'] <=> $a['hits'] );

		return $candidates;
	}

	/**
	 * Extract meaningful keywords from a post title.
	 *
	 * Strips stop words and returns words of 3+ characters.
	 *
	 * @param string $title Post title.
	 * @return string[] Keywords.
	 */
	private function extract_keywords( string $title ): array {
		$stop_words = [
			'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to',
			'for', 'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were',
			'be', 'been', 'has', 'have', 'had', 'do', 'does', 'did', 'will',
			'would', 'could', 'should', 'may', 'might', 'can', 'shall',
			'this', 'that', 'these', 'those', 'it', 'its', 'not', 'no',
			'jak', 'co', 'gdzie', 'czy', 'nie', 'na', 'do', 'za', 'po',
			'się', 'jest', 'są', 'był', 'była', 'były', 'ten', 'ta', 'to',
		];

		$words = preg_split( '/[\s,\-–—]+/u', mb_strtolower( $title ) );
		if ( false === $words ) {
			return [];
		}

		return array_values( array_filter(
			$words,
			static fn( string $w ): bool => mb_strlen( $w ) >= 3 && ! in_array( $w, $stop_words, true )
		) );
	}

	/**
	 * Inject a single internal link into content.
	 *
	 * Finds the first natural occurrence of a candidate keyword in the text
	 * (outside of existing links and headings) and wraps it in a link.
	 *
	 * @param string $content   Current content.
	 * @param array  $candidate Link candidate data.
	 * @return string Modified content (unchanged if no suitable insertion point found).
	 */
	private function inject_link( string $content, array $candidate ): string {
		// Find the best keyword to link.
		$link_keyword = '';
		foreach ( $candidate['keywords'] as $kw ) {
			if ( false !== mb_stripos( $content, $kw ) ) {
				$link_keyword = $kw;
				break;
			}
		}

		if ( '' === $link_keyword ) {
			return $content;
		}

		// Regex explanation:
		//   (?<!<a[^>]*>)     — Negative lookbehind: not already inside <a> tag text.
		//   (?<![\/\w])       — Not preceded by word char or / (avoids partial matches).
		//   (keyword)         — Capture the keyword (case-insensitive via /iu flags).
		//   (?![^<]*<\/a>)    — Negative lookahead: not already inside an anchor element.
		//   (?![^<]*<\/h[1-6]>) — Negative lookahead: not inside a heading element.
		$pattern = '/(?<!<a[^>]*>)(?<![\/\w])(' . preg_quote( $link_keyword, '/' ) . ')(?![^<]*<\/a>)(?![^<]*<\/h[1-6]>)/iu';

		$link_html = sprintf(
			'<a href="%s" title="%s" class="pearblog-internal-link">%s</a>',
			esc_url( $candidate['url'] ),
			esc_attr( $candidate['title'] ),
			'$1'
		);

		// Replace only the first occurrence.
		$result = preg_replace( $pattern, $link_html, $content, 1, $count );

		return $count > 0 ? $result : $content;
	}
}
