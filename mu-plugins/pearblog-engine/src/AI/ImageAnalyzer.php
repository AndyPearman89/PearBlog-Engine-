<?php
/**
 * Image Analyzer – audits and analyses media library images.
 *
 * Provides:
 *  - Missing alt text detection
 *  - Missing featured image detection
 *  - AI-generated image tracking
 *  - Image dimension / optimization checks
 *  - Keyword-based image generation suggestions
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Image analysis and audit engine.
 */
class ImageAnalyzer {

	/**
	 * Run a full image audit across the media library and posts.
	 *
	 * @return array{
	 *   summary: array,
	 *   posts_without_images: array,
	 *   images_without_alt: array,
	 *   ai_generated_images: array,
	 *   oversized_images: array
	 * }
	 */
	public function full_audit(): array {
		return [
			'summary'              => $this->get_summary(),
			'posts_without_images' => $this->find_posts_without_featured_image(),
			'images_without_alt'   => $this->find_images_without_alt(),
			'ai_generated_images'  => $this->get_ai_generated_images(),
			'oversized_images'     => $this->find_oversized_images(),
		];
	}

	/**
	 * Get summary statistics of the image library.
	 *
	 * @return array{total_images: int, total_posts: int, posts_with_images: int, posts_without_images: int, ai_generated: int, missing_alt: int}
	 */
	public function get_summary(): array {
		$total_images = (int) wp_count_posts( 'attachment' )->inherit;

		$total_posts = (int) wp_count_posts( 'post' )->publish;

		// Count posts with featured images.
		$posts_with_thumb = $this->count_posts_with_thumbnails();

		// Count AI-generated images.
		$ai_count = $this->count_ai_generated();

		// Count images without alt text.
		$missing_alt = $this->count_missing_alt();

		return [
			'total_images'         => $total_images,
			'total_posts'          => $total_posts,
			'posts_with_images'    => $posts_with_thumb,
			'posts_without_images' => $total_posts - $posts_with_thumb,
			'ai_generated'         => $ai_count,
			'missing_alt'          => $missing_alt,
		];
	}

	/**
	 * Find published posts that have no featured image.
	 *
	 * @param int $limit Maximum results.
	 * @return array Array of post data without featured images.
	 */
	public function find_posts_without_featured_image( int $limit = 50 ): array {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_date
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
			WHERE p.post_type = 'post'
			AND p.post_status = 'publish'
			AND (pm.meta_value IS NULL OR pm.meta_value = '')
			ORDER BY p.post_date DESC
			LIMIT %d",
			$limit
		) );

		$posts = [];
		foreach ( $results as $row ) {
			$posts[] = [
				'post_id'    => (int) $row->ID,
				'title'      => $row->post_title,
				'date'       => $row->post_date,
				'edit_url'   => get_edit_post_link( (int) $row->ID, 'raw' ),
				'keywords'   => $this->extract_keywords_from_title( $row->post_title ),
			];
		}

		return $posts;
	}

	/**
	 * Find images in the media library that have no alt text.
	 *
	 * @param int $limit Maximum results.
	 * @return array Array of image data without alt text.
	 */
	public function find_images_without_alt( int $limit = 50 ): array {
		$attachments = get_posts( [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => $limit,
			'post_status'    => 'inherit',
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'   => '_wp_attachment_image_alt',
					'value' => '',
				],
			],
		] );

		$images = [];
		foreach ( $attachments as $att ) {
			$images[] = [
				'attachment_id' => $att->ID,
				'title'         => $att->post_title,
				'url'           => wp_get_attachment_url( $att->ID ),
				'thumbnail'     => wp_get_attachment_image_url( $att->ID, 'thumbnail' ),
				'file'          => get_attached_file( $att->ID ),
				'ai_generated'  => (bool) get_post_meta( $att->ID, '_pearblog_ai_generated', true ),
			];
		}

		return $images;
	}

	/**
	 * Get all AI-generated images in the media library.
	 *
	 * @param int $limit Maximum results.
	 * @return array Array of AI-generated image data.
	 */
	public function get_ai_generated_images( int $limit = 50 ): array {
		$attachments = get_posts( [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => $limit,
			'post_status'    => 'inherit',
			'meta_key'       => '_pearblog_ai_generated',
			'meta_value'     => '1',
		] );

		$images = [];
		foreach ( $attachments as $att ) {
			$images[] = [
				'attachment_id'   => $att->ID,
				'title'           => $att->post_title,
				'url'             => wp_get_attachment_url( $att->ID ),
				'thumbnail'       => wp_get_attachment_image_url( $att->ID, 'thumbnail' ),
				'source'          => get_post_meta( $att->ID, '_pearblog_image_source', true ),
				'generation_date' => get_post_meta( $att->ID, '_pearblog_generation_date', true ),
				'width'           => get_post_meta( $att->ID, '_pearblog_original_width', true ),
				'height'          => get_post_meta( $att->ID, '_pearblog_original_height', true ),
				'alt'             => get_post_meta( $att->ID, '_wp_attachment_image_alt', true ),
			];
		}

		return $images;
	}

	/**
	 * Find images that exceed recommended dimensions.
	 *
	 * @param int $max_width  Maximum recommended width.
	 * @param int $max_height Maximum recommended height.
	 * @param int $limit      Maximum results.
	 * @return array Array of oversized image data.
	 */
	public function find_oversized_images( int $max_width = 2560, int $max_height = 1440, int $limit = 50 ): array {
		$attachments = get_posts( [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => $limit,
			'post_status'    => 'inherit',
		] );

		$oversized = [];
		foreach ( $attachments as $att ) {
			$metadata = wp_get_attachment_metadata( $att->ID );
			if ( ! is_array( $metadata ) ) {
				continue;
			}

			$width  = (int) ( $metadata['width'] ?? 0 );
			$height = (int) ( $metadata['height'] ?? 0 );

			if ( $width > $max_width || $height > $max_height ) {
				$oversized[] = [
					'attachment_id' => $att->ID,
					'title'         => $att->post_title,
					'url'           => wp_get_attachment_url( $att->ID ),
					'width'         => $width,
					'height'        => $height,
					'filesize'      => isset( $metadata['filesize'] ) ? (int) $metadata['filesize'] : 0,
				];
			}
		}

		return $oversized;
	}

	/**
	 * Generate image suggestions based on post keywords.
	 *
	 * For posts without featured images, suggests keywords to use
	 * for image generation based on the post title and content.
	 *
	 * @param int $post_id Post ID.
	 * @return array{post_id: int, title: string, keywords: string[], suggested_prompt: string}
	 */
	public function suggest_image_keywords( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return [
				'post_id'          => $post_id,
				'title'            => '',
				'keywords'         => [],
				'suggested_prompt' => '',
			];
		}

		$keywords = $this->extract_keywords_from_title( $post->post_title );

		// Also extract from content.
		$content_keywords = $this->extract_keywords_from_content( $post->post_content );
		$keywords         = array_unique( array_merge( $keywords, $content_keywords ) );
		$keywords         = array_slice( $keywords, 0, 10 );

		$style = get_option( 'pearblog_image_style', 'photorealistic' );
		$style_templates = [
			'photorealistic' => 'A stunning, high-quality photograph of',
			'illustration'   => 'A beautiful digital illustration of',
			'artistic'       => 'An artistic, painterly representation of',
			'minimal'        => 'A clean, minimalist photograph of',
		];

		$template = $style_templates[ $style ] ?? $style_templates['photorealistic'];
		$prompt   = sprintf(
			'%s %s. Professional photography, vibrant colors, excellent composition, no text or watermarks.',
			$template,
			implode( ', ', array_slice( $keywords, 0, 5 ) )
		);

		return [
			'post_id'          => $post_id,
			'title'            => $post->post_title,
			'keywords'         => $keywords,
			'suggested_prompt' => $prompt,
		];
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Count published posts that have a featured image set.
	 *
	 * @return int Count.
	 */
	private function count_posts_with_thumbnails(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type = 'post'
			AND p.post_status = 'publish'
			AND pm.meta_key = '_thumbnail_id'
			AND pm.meta_value != ''"
		);
	}

	/**
	 * Count AI-generated images in the media library.
	 *
	 * @return int Count.
	 */
	private function count_ai_generated(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type = 'attachment'
			AND p.post_status = 'inherit'
			AND pm.meta_key = '_pearblog_ai_generated'
			AND pm.meta_value = '1'"
		);
	}

	/**
	 * Count images without alt text.
	 *
	 * @return int Count.
	 */
	private function count_missing_alt(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
			WHERE p.post_type = 'attachment'
			AND p.post_mime_type LIKE 'image/%'
			AND p.post_status = 'inherit'
			AND (pm.meta_value IS NULL OR pm.meta_value = '')"
		);
	}

	/**
	 * Extract meaningful keywords from a post title.
	 *
	 * @param string $title Post title.
	 * @return string[] Keywords.
	 */
	private function extract_keywords_from_title( string $title ): array {
		$stop_words = [
			'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'with', 'this', 'that', 'from', 'have',
			'jak', 'nie', 'dla', 'czy', 'jest', 'ten', 'lub', 'oraz', 'ale', 'przy', 'przez', 'się', 'pod',
		];

		$words = preg_split( '/[\s\-–—:,;.!?]+/', mb_strtolower( $title ) );
		$words = array_filter( $words, function ( $word ) use ( $stop_words ) {
			$word = preg_replace( '/[^\p{L}\p{N}]/u', '', $word );
			return mb_strlen( $word ) >= 3 && ! in_array( $word, $stop_words, true );
		} );

		return array_values( $words );
	}

	/**
	 * Extract keywords from post content using frequency analysis.
	 *
	 * @param string $content Post content.
	 * @param int    $top_n   Number of top keywords to return.
	 * @return string[] Keywords.
	 */
	private function extract_keywords_from_content( string $content, int $top_n = 10 ): array {
		$text = mb_strtolower( wp_strip_all_tags( $content ) );

		$stop_words = [
			'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'with', 'this', 'that', 'from', 'have',
			'jak', 'nie', 'dla', 'czy', 'jest', 'ten', 'lub', 'oraz', 'ale', 'przy', 'przez', 'się', 'pod',
			'was', 'will', 'been', 'has', 'had', 'would', 'could', 'should', 'also', 'more', 'about', 'into',
			'być', 'który', 'która', 'które', 'tego', 'tej', 'aby', 'już', 'może', 'można',
		];

		$words = preg_split( '/[\s\-–—:,;.!?\(\)\[\]"\']+/', $text );
		$words = array_filter( $words, function ( $word ) use ( $stop_words ) {
			$word = preg_replace( '/[^\p{L}\p{N}]/u', '', $word );
			return mb_strlen( $word ) >= 3 && ! in_array( $word, $stop_words, true );
		} );

		// Count frequency.
		$freq = array_count_values( $words );
		arsort( $freq );

		return array_slice( array_keys( $freq ), 0, $top_n );
	}
}
