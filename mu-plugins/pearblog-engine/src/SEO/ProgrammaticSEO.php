<?php
/**
 * Programmatic SEO engine – automated SEO optimization for all posts.
 *
 * Handles:
 *  - Schema.org structured data (Article, BreadcrumbList, FAQ)
 *  - Open Graph & Twitter Card meta tags
 *  - Keyword density analysis
 *  - Auto meta-description generation
 *  - Bulk SEO audit for posts
 *  - Internal linking suggestions
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Programmatic SEO processor.
 */
class ProgrammaticSEO {

	/**
	 * Register WordPress hooks for front-end SEO output.
	 */
	public function register(): void {
		add_action( 'wp_head', [ $this, 'inject_schema_markup' ], 1 );
		add_action( 'wp_head', [ $this, 'inject_open_graph' ], 2 );
		add_action( 'wp_head', [ $this, 'inject_twitter_card' ], 3 );
		add_action( 'save_post', [ $this, 'auto_generate_meta' ], 20, 2 );
	}

	// -----------------------------------------------------------------------
	// Schema.org structured data
	// -----------------------------------------------------------------------

	/**
	 * Inject JSON-LD Schema.org markup into <head>.
	 */
	public function inject_schema_markup(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post = get_post();
		if ( ! $post ) {
			return;
		}

		$schemas = [];

		// Article schema.
		$schemas[] = $this->build_article_schema( $post );

		// BreadcrumbList schema.
		$schemas[] = $this->build_breadcrumb_schema( $post );

		// FAQ schema (if post contains FAQ block).
		$faq_schema = $this->build_faq_schema( $post );
		if ( null !== $faq_schema ) {
			$schemas[] = $faq_schema;
		}

		foreach ( $schemas as $schema ) {
			echo '<script type="application/ld+json">' . "\n";
			echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
			echo "\n</script>\n";
		}
	}

	/**
	 * Build Article structured data.
	 *
	 * @param \WP_Post $post WordPress post object.
	 * @return array Schema.org Article data.
	 */
	private function build_article_schema( \WP_Post $post ): array {
		$author     = get_the_author_meta( 'display_name', $post->post_author );
		$image_url  = (string) get_the_post_thumbnail_url( $post->ID, 'full' );
		$categories = wp_get_post_categories( $post->ID, [ 'fields' => 'names' ] );
		$keywords   = $this->extract_post_keywords( $post );

		$schema = [
			'@context'         => 'https://schema.org',
			'@type'            => 'Article',
			'headline'         => get_the_title( $post ),
			'description'      => $this->get_meta_description( $post ),
			'author'           => [
				'@type' => 'Person',
				'name'  => $author,
			],
			'publisher'        => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			],
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'mainEntityOfPage' => [
				'@type' => 'WebPage',
				'@id'   => get_permalink( $post ),
			],
			'url'              => get_permalink( $post ),
			'wordCount'        => str_word_count( wp_strip_all_tags( $post->post_content ) ),
			'inLanguage'       => get_option( 'pearblog_language', 'en' ),
		];

		if ( '' !== $image_url ) {
			$schema['image'] = $image_url;
		}

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$schema['articleSection'] = implode( ', ', $categories );
		}

		if ( ! empty( $keywords ) ) {
			$schema['keywords'] = implode( ', ', $keywords );
		}

		return $schema;
	}

	/**
	 * Build BreadcrumbList structured data.
	 *
	 * @param \WP_Post $post WordPress post object.
	 * @return array Schema.org BreadcrumbList data.
	 */
	private function build_breadcrumb_schema( \WP_Post $post ): array {
		$items = [
			[
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => get_bloginfo( 'name' ),
				'item'     => home_url( '/' ),
			],
		];

		$categories = wp_get_post_categories( $post->ID, [ 'fields' => 'all' ] );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$cat       = $categories[0];
			$items[]   = [
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => $cat->name,
				'item'     => get_category_link( $cat->term_id ),
			];
		}

		$items[] = [
			'@type'    => 'ListItem',
			'position' => count( $items ) + 1,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		];

		return [
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		];
	}

	/**
	 * Build FAQ structured data from post content.
	 *
	 * Detects FAQ sections by looking for headings containing "FAQ" or
	 * "Pytania" followed by question/answer pairs.
	 *
	 * @param \WP_Post $post WordPress post object.
	 * @return array|null Schema.org FAQPage data, or null if no FAQ found.
	 */
	private function build_faq_schema( \WP_Post $post ): ?array {
		$content = $post->post_content;

		// Look for FAQ patterns: H2/H3 with "?" followed by a paragraph.
		if ( ! preg_match_all(
			'/<h[23][^>]*>\s*(.+?\?)\s*<\/h[23]>\s*<p>(.+?)<\/p>/is',
			$content,
			$matches,
			PREG_SET_ORDER
		) ) {
			return null;
		}

		if ( count( $matches ) < 2 ) {
			return null;
		}

		$faq_entries = [];
		foreach ( $matches as $match ) {
			$faq_entries[] = [
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $match[1] ),
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $match[2] ),
				],
			];
		}

		return [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $faq_entries,
		];
	}

	// -----------------------------------------------------------------------
	// Open Graph & Twitter Card
	// -----------------------------------------------------------------------

	/**
	 * Inject Open Graph meta tags.
	 */
	public function inject_open_graph(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post = get_post();
		if ( ! $post ) {
			return;
		}

		$title       = get_the_title( $post );
		$description = $this->get_meta_description( $post );
		$url         = get_permalink( $post );
		$image       = (string) get_the_post_thumbnail_url( $post->ID, 'full' );
		$site_name   = get_bloginfo( 'name' );
		$locale      = get_locale();

		echo '<meta property="og:type" content="article" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
		echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '" />' . "\n";
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $post ) ) . '" />' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', $post ) ) . '" />' . "\n";

		if ( '' !== $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
			echo '<meta property="og:image:width" content="1792" />' . "\n";
			echo '<meta property="og:image:height" content="1024" />' . "\n";
		}
	}

	/**
	 * Inject Twitter Card meta tags.
	 */
	public function inject_twitter_card(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post = get_post();
		if ( ! $post ) {
			return;
		}

		$title       = get_the_title( $post );
		$description = $this->get_meta_description( $post );
		$image       = (string) get_the_post_thumbnail_url( $post->ID, 'full' );

		echo '<meta name="twitter:card" content="' . ( '' !== $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";

		if ( '' !== $image ) {
			echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
		}
	}

	// -----------------------------------------------------------------------
	// Auto meta-description generation
	// -----------------------------------------------------------------------

	/**
	 * Auto-generate meta description on post save if missing.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function auto_generate_meta( int $post_id, \WP_Post $post ): void {
		if ( 'post' !== $post->post_type ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$existing = get_post_meta( $post_id, 'pearblog_meta_description', true );
		if ( ! empty( $existing ) ) {
			return;
		}

		$description = $this->generate_meta_description( $post->post_content );
		if ( '' !== $description ) {
			update_post_meta( $post_id, 'pearblog_meta_description', $description );
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $description );
			update_post_meta( $post_id, 'rank_math_description', $description );
		}
	}

	/**
	 * Generate a meta description from post content.
	 *
	 * @param string $content Post content.
	 * @return string Generated meta description (max 160 chars).
	 */
	public function generate_meta_description( string $content ): string {
		$text = wp_strip_all_tags( $content );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		if ( '' === $text ) {
			return '';
		}

		// Take the first meaningful sentence(s) up to ~155 characters.
		if ( mb_strlen( $text ) <= 155 ) {
			return $text;
		}

		$truncated = mb_substr( $text, 0, 155 );
		$last_space = mb_strrpos( $truncated, ' ' );
		if ( false !== $last_space && $last_space > 100 ) {
			$truncated = mb_substr( $truncated, 0, $last_space );
		}

		return $truncated . '…';
	}

	// -----------------------------------------------------------------------
	// Keyword density analysis
	// -----------------------------------------------------------------------

	/**
	 * Analyse keyword density in post content.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $keyword Target keyword.
	 * @return array{keyword: string, count: int, word_count: int, density: float, status: string}
	 */
	public function analyze_keyword_density( int $post_id, string $keyword ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return [
				'keyword'    => $keyword,
				'count'      => 0,
				'word_count' => 0,
				'density'    => 0.0,
				'status'     => 'error',
			];
		}

		$text       = mb_strtolower( wp_strip_all_tags( $post->post_content ) );
		$keyword_lc = mb_strtolower( $keyword );
		$word_count = str_word_count( $text );

		if ( 0 === $word_count ) {
			return [
				'keyword'    => $keyword,
				'count'      => 0,
				'word_count' => 0,
				'density'    => 0.0,
				'status'     => 'empty',
			];
		}

		$count   = mb_substr_count( $text, $keyword_lc );
		$density = ( $count * mb_strlen( $keyword_lc ) ) / mb_strlen( $text ) * 100;

		// Optimal density: 1-3%.
		if ( $density < 0.5 ) {
			$status = 'low';
		} elseif ( $density > 3.0 ) {
			$status = 'high';
		} else {
			$status = 'optimal';
		}

		return [
			'keyword'    => $keyword,
			'count'      => $count,
			'word_count' => $word_count,
			'density'    => round( $density, 2 ),
			'status'     => $status,
		];
	}

	// -----------------------------------------------------------------------
	// Bulk SEO audit
	// -----------------------------------------------------------------------

	/**
	 * Run a bulk SEO audit on published posts.
	 *
	 * @param int $limit Maximum number of posts to audit.
	 * @return array{total: int, issues: array<int, array>}
	 */
	public function bulk_audit( int $limit = 50 ): array {
		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$issues = [];

		foreach ( $posts as $post ) {
			$post_issues = [];

			// Check meta description.
			$meta_desc = get_post_meta( $post->ID, 'pearblog_meta_description', true );
			if ( empty( $meta_desc ) ) {
				$post_issues[] = 'missing_meta_description';
			} elseif ( mb_strlen( $meta_desc ) < 50 ) {
				$post_issues[] = 'short_meta_description';
			} elseif ( mb_strlen( $meta_desc ) > 160 ) {
				$post_issues[] = 'long_meta_description';
			}

			// Check title length.
			$title = get_the_title( $post );
			if ( mb_strlen( $title ) < 10 ) {
				$post_issues[] = 'short_title';
			} elseif ( mb_strlen( $title ) > 70 ) {
				$post_issues[] = 'long_title';
			}

			// Check featured image.
			if ( ! has_post_thumbnail( $post->ID ) ) {
				$post_issues[] = 'missing_featured_image';
			} else {
				$thumb_id = (int) get_post_thumbnail_id( $post->ID );
				$alt      = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
				if ( empty( $alt ) ) {
					$post_issues[] = 'missing_image_alt';
				}
			}

			// Check content length.
			$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
			if ( $word_count < 300 ) {
				$post_issues[] = 'thin_content';
			}

			// Check heading structure.
			if ( false === strpos( $post->post_content, '<h2' ) ) {
				$post_issues[] = 'missing_h2';
			}

			if ( ! empty( $post_issues ) ) {
				$issues[ $post->ID ] = [
					'title'  => $title,
					'url'    => get_permalink( $post ),
					'issues' => $post_issues,
				];
			}
		}

		return [
			'total'         => count( $posts ),
			'posts_audited' => count( $posts ),
			'issues_found'  => count( $issues ),
			'issues'        => $issues,
		];
	}

	// -----------------------------------------------------------------------
	// Internal linking suggestions
	// -----------------------------------------------------------------------

	/**
	 * Suggest internal links for a post based on keyword matching.
	 *
	 * @param int $post_id Post ID to find links for.
	 * @param int $limit   Maximum suggestions.
	 * @return array Array of suggested posts with relevance info.
	 */
	public function suggest_internal_links( int $post_id, int $limit = 5 ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return [];
		}

		$keywords = $this->extract_post_keywords( $post );
		if ( empty( $keywords ) ) {
			return [];
		}

		$other_posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'exclude'        => [ $post_id ],
		] );

		$scored = [];

		foreach ( $other_posts as $other ) {
			$other_text = mb_strtolower( $other->post_title . ' ' . wp_strip_all_tags( $other->post_content ) );
			$score      = 0;

			foreach ( $keywords as $kw ) {
				if ( false !== mb_strpos( $other_text, mb_strtolower( $kw ) ) ) {
					$score++;
				}
			}

			if ( $score > 0 ) {
				$scored[] = [
					'post_id'  => $other->ID,
					'title'    => get_the_title( $other ),
					'url'      => get_permalink( $other ),
					'score'    => $score,
					'keywords' => array_filter( $keywords, function ( $kw ) use ( $other_text ) {
						return false !== mb_strpos( $other_text, mb_strtolower( $kw ) );
					} ),
				];
			}
		}

		// Sort by score descending.
		usort( $scored, function ( $a, $b ) {
			return $b['score'] - $a['score'];
		} );

		return array_slice( $scored, 0, $limit );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Get meta description for a post (from stored meta or auto-generated).
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Meta description.
	 */
	private function get_meta_description( \WP_Post $post ): string {
		$desc = get_post_meta( $post->ID, 'pearblog_meta_description', true );
		if ( ! empty( $desc ) ) {
			return (string) $desc;
		}

		return $this->generate_meta_description( $post->post_content );
	}

	/**
	 * Extract significant keywords from a post (tags + title words).
	 *
	 * @param \WP_Post $post Post object.
	 * @return string[] Keywords.
	 */
	private function extract_post_keywords( \WP_Post $post ): array {
		$keywords = [];

		// Post tags.
		$tags = wp_get_post_tags( $post->ID, [ 'fields' => 'names' ] );
		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
			$keywords = array_merge( $keywords, $tags );
		}

		// Significant words from title (3+ chars, no stop words).
		$stop_words  = [ 'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'with', 'this', 'that', 'from', 'have', 'jak', 'nie', 'dla', 'czy', 'jest' ];
		$title_words = preg_split( '/\s+/', mb_strtolower( get_the_title( $post ) ) );
		foreach ( $title_words as $word ) {
			$word = preg_replace( '/[^\p{L}\p{N}]/u', '', $word );
			if ( mb_strlen( $word ) >= 3 && ! in_array( $word, $stop_words, true ) ) {
				$keywords[] = $word;
			}
		}

		return array_unique( $keywords );
	}
}
