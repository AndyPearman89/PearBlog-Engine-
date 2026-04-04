<?php
/**
 * SEO engine – extracts and applies SEO metadata to a WordPress post.
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Parses AI-generated content for SEO signals and stores them as post meta.
 *
 * Expected content format (produced by PromptBuilder):
 *
 *   META: <meta description text>
 *
 *   # <H1 title>
 *
 *   <body…>
 */
class SEOEngine {

	/**
	 * Parse the raw AI content, extract the meta description and title, and
	 * apply them to the given post.
	 *
	 * @param int    $post_id    WordPress post ID.
	 * @param string $content    Raw AI-generated content.
	 * @return array{title: string, meta_description: string, content: string}
	 *              Parsed data that was applied to the post.
	 */
	public function apply( int $post_id, string $content ): array {
		$meta_description = $this->extract_meta_description( $content );
		$title            = $this->extract_title( $content );
		$body             = $this->strip_directives( $content );

		// Store SEO fields as post meta (compatible with Yoast / RankMath).
		if ( '' !== $meta_description ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
			update_post_meta( $post_id, 'rank_math_description', $meta_description );
			update_post_meta( $post_id, 'pearblog_meta_description', $meta_description );
		}

		if ( '' !== $title ) {
			update_post_meta( $post_id, '_yoast_wpseo_title', $title );
			update_post_meta( $post_id, 'rank_math_title', $title );
		}

		// Store canonical URL
		$canonical_url = $this->canonical_url( $post_id );
		update_post_meta( $post_id, '_pearblog_canonical_url', $canonical_url );

		/**
		 * Action: pearblog_seo_applied
		 *
		 * Fires after SEO metadata has been written to a post.
		 *
		 * @param int    $post_id          Post ID.
		 * @param string $title            Extracted H1 title.
		 * @param string $meta_description Extracted meta description.
		 */
		do_action( 'pearblog_seo_applied', $post_id, $title, $meta_description );

		return compact( 'title', 'meta_description', 'content' => $body );
	}

	/**
	 * Generate and return a canonical URL for a post.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return string Canonical URL.
	 */
	public function canonical_url( int $post_id ): string {
		return (string) get_permalink( $post_id );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function extract_meta_description( string $content ): string {
		if ( preg_match( '/^META:\s*(.+)$/mi', $content, $matches ) ) {
			return trim( $matches[1] );
		}
		return '';
	}

	private function extract_title( string $content ): string {
		// Match markdown H1 ( # Title ) or HTML <h1>Title</h1>.
		if ( preg_match( '/^#\s+(.+)$/mi', $content, $matches ) ) {
			return trim( $matches[1] );
		}
		if ( preg_match( '/<h1[^>]*>(.+?)<\/h1>/is', $content, $matches ) ) {
			return trim( wp_strip_all_tags( $matches[1] ) );
		}
		return '';
	}

	private function strip_directives( string $content ): string {
		// Remove the META: line from the body that will be stored as post content.
		return trim( preg_replace( '/^META:\s*.+\n?/mi', '', $content ) );
	}
}
