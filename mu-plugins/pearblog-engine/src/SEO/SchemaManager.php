<?php
/**
 * Schema manager – outputs Schema.org structured data for each post.
 *
 * Generates JSON-LD blocks for:
 *  - Article (every post)
 *  - FAQPage (when FAQ questions are detected in content)
 *  - BreadcrumbList (home → category → post)
 *
 * Usage: call SchemaManager::output() inside wp_head or after the post template.
 * Plugin::boot() hooks this automatically.
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

use PearBlogEngine\SEO\InternalLinker;

/**
 * Generates and outputs Schema.org JSON-LD for WordPress posts.
 */
class SchemaManager {

	/**
	 * Attach wp_head hook (called from Plugin::boot).
	 */
	public function register(): void {
		add_action( 'wp_head', [ $this, 'output' ], 5 );
	}

	/**
	 * Echo all applicable Schema.org JSON-LD blocks for the current page.
	 */
	public function output(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		global $post;

		$schemas = array_filter( [
			$this->build_article_schema( $post ),
			$this->build_faq_schema( $post ),
			$this->build_breadcrumb_schema( $post ),
		] );

		foreach ( $schemas as $schema ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	// -----------------------------------------------------------------------
	// Schema builders
	// -----------------------------------------------------------------------

	/**
	 * Build an Article schema for a post.
	 */
	private function build_article_schema( \WP_Post $post ): array {
		$author    = get_the_author_meta( 'display_name', (int) $post->post_author );
		$image_id  = (int) get_post_thumbnail_id( $post->ID );
		$image_url = $image_id ? (string) wp_get_attachment_image_url( $image_id, 'large' ) : '';

		$schema = [
			'@context'         => 'https://schema.org',
			'@type'            => 'Article',
			'headline'         => get_the_title( $post->ID ),
			'description'      => (string) get_post_meta( $post->ID, 'pearblog_meta_description', true ),
			'url'              => (string) get_permalink( $post->ID ),
			'datePublished'    => get_the_date( 'c', $post->ID ),
			'dateModified'     => get_the_modified_date( 'c', $post->ID ),
			'author'           => [
				'@type' => 'Person',
				'name'  => $author ?: get_bloginfo( 'name' ),
			],
			'publisher'        => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'url'   => get_site_url(),
			],
			'inLanguage'       => get_bloginfo( 'language' ),
			'wordCount'        => $this->count_words( $post->post_content ),
		];

		if ( '' !== $image_url ) {
			$schema['image'] = [
				'@type' => 'ImageObject',
				'url'   => $image_url,
			];
		}

		// Add keywords from cluster meta if available.
		$cluster = get_post_meta( $post->ID, InternalLinker::META_KEY_CLUSTER, true );
		if ( is_array( $cluster ) && ! empty( $cluster ) ) {
			$schema['keywords'] = implode( ', ', array_map( 'sanitize_text_field', $cluster ) );
		}

		return $schema;
	}

	/**
	 * Build a FAQPage schema when the post content contains FAQ-style Q&A pairs.
	 *
	 * Detects patterns:
	 *  - Markdown: ### Question?\nAnswer text
	 *  - HTML:     <h3>Question?</h3><p>Answer</p>
	 */
	private function build_faq_schema( \WP_Post $post ): ?array {
		$pairs = $this->extract_faq_pairs( $post->post_content );
		if ( empty( $pairs ) ) {
			return null;
		}

		$entities = [];
		foreach ( $pairs as $pair ) {
			$entities[] = [
				'@type'          => 'Question',
				'name'           => $pair['question'],
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => $pair['answer'],
				],
			];
		}

		return [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		];
	}

	/**
	 * Build a BreadcrumbList schema: Home → Category → Post.
	 */
	private function build_breadcrumb_schema( \WP_Post $post ): array {
		$items = [
			[
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => __( 'Home', 'pearblog-engine' ),
				'item'     => get_home_url(),
			],
		];

		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			$cat    = $categories[0];
			$items[] = [
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => $cat->name,
				'item'     => (string) get_category_link( $cat->term_id ),
			];
			$items[] = [
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => get_the_title( $post->ID ),
				'item'     => (string) get_permalink( $post->ID ),
			];
		} else {
			$items[] = [
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => get_the_title( $post->ID ),
				'item'     => (string) get_permalink( $post->ID ),
			];
		}

		return [
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		];
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Extract FAQ question/answer pairs from HTML or Markdown content.
	 *
	 * @return array<array{question: string, answer: string}>
	 */
	private function extract_faq_pairs( string $content ): array {
		$pairs = [];

		// Look for an FAQ section (h2 or ##) and parse h3/### Q&A inside it.
		// Pattern: ### Question?\n<p>Answer</p> or Answer text
		preg_match_all(
			'/(?:<h3[^>]*>|###\s+)([^<\n]+\?)\s*(?:<\/h3>)?\s*(?:<p[^>]*>)?(.+?)(?:<\/p>|(?=\n###|\n##|$))/is',
			$content,
			$matches,
			PREG_SET_ORDER
		);

		foreach ( $matches as $match ) {
			$question = trim( wp_strip_all_tags( $match[1] ) );
			$answer   = trim( wp_strip_all_tags( $match[2] ) );

			if ( '' !== $question && mb_strlen( $answer ) >= 20 ) {
				$pairs[] = [
					'question' => $question,
					'answer'   => mb_substr( $answer, 0, 500 ), // Schema limit.
				];
			}
		}

		return array_slice( $pairs, 0, 10 ); // Max 10 FAQ items.
	}

	private function count_words( string $content ): int {
		return str_word_count( wp_strip_all_tags( $content ) );
	}
}
