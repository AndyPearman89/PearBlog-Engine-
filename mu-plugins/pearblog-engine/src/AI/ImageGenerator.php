<?php
/**
 * Image Generator – creates AI-generated images using DALL-E 3.
 *
 * Generates featured images for articles based on title/topic.
 * Integrates with WordPress media library.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * DALL-E 3 image generation client.
 */
class ImageGenerator {

	private const API_URL = 'https://api.openai.com/v1/images/generations';
	private const MODEL   = 'dall-e-3';

	/** @var string */
	private string $api_key;

	/** @var bool */
	private bool $enabled;

	public function __construct( string $api_key = '', bool $enabled = true ) {
		if ( '' === $api_key ) {
			$api_key = defined( 'PEARBLOG_OPENAI_API_KEY' )
				? PEARBLOG_OPENAI_API_KEY
				: (string) get_option( 'pearblog_openai_api_key', '' );
		}
		$this->api_key = $api_key;
		$this->enabled = $enabled && (bool) get_option( 'pearblog_enable_image_generation', true );
	}

	/**
	 * Generate an image based on article topic/title.
	 *
	 * @param string $topic     Article topic or title.
	 * @param string $style     Image style hint (optional).
	 * @return string|null      Generated image URL, or null if disabled/failed.
	 * @throws \RuntimeException On API errors (when enabled).
	 */
	public function generate( string $topic, string $style = '' ): ?string {
		if ( ! $this->enabled ) {
			return null;
		}

		if ( '' === $this->api_key ) {
			error_log( 'PearBlog Engine: OpenAI API key not configured for image generation.' );
			return null;
		}

		$prompt = $this->build_prompt( $topic, $style );

		$body = wp_json_encode( [
			'model'   => self::MODEL,
			'prompt'  => $prompt,
			'n'       => 1,
			'size'    => '1792x1024', // DALL-E 3 landscape format
			'quality' => 'standard',  // or 'hd' for higher quality
		] );

		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => 120, // Image generation takes longer
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				],
				'body'    => $body,
			]
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'PearBlog Engine: Image generation HTTP error – ' . $response->get_error_message() );
			return null;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status || ! is_array( $data ) ) {
			$message = $data['error']['message'] ?? 'Unknown API error';
			error_log( "PearBlog Engine: DALL-E API error ({$status}) – {$message}" );
			return null;
		}

		return $data['data'][0]['url'] ?? null;
	}

	/**
	 * Generate image and attach it to a WordPress post as featured image.
	 *
	 * @param int    $post_id Post ID to attach the image to.
	 * @param string $topic   Article topic/title for image generation.
	 * @param string $style   Optional style hint.
	 * @return int|null       Attachment ID, or null on failure.
	 */
	public function generate_and_attach( int $post_id, string $topic, string $style = '' ): ?int {
		$image_url = $this->generate( $topic, $style );

		if ( null === $image_url ) {
			return null;
		}

		// Download the image to WordPress media library.
		$attachment_id = $this->download_to_media_library( $image_url, $topic );

		if ( null === $attachment_id ) {
			return null;
		}

		// Set as featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Build a DALL-E prompt optimized for travel/blog featured images.
	 *
	 * @param string $topic Article topic.
	 * @param string $style Optional style override.
	 * @return string       Optimized DALL-E prompt.
	 */
	private function build_prompt( string $topic, string $style ): string {
		// Default style for travel/blog content.
		$default_style = get_option( 'pearblog_image_style', 'photorealistic' );
		$style_used    = $style ?: $default_style;

		$style_templates = [
			'photorealistic' => 'A stunning, high-quality photograph of',
			'illustration'   => 'A beautiful digital illustration of',
			'artistic'       => 'An artistic, painterly representation of',
			'minimal'        => 'A clean, minimalist photograph of',
		];

		$template = $style_templates[ $style_used ] ?? $style_templates['photorealistic'];

		// Build prompt.
		$prompt = sprintf(
			'%s %s. Professional photography, vibrant colors, excellent composition, no text or watermarks.',
			$template,
			$topic
		);

		/**
		 * Filter: pearblog_image_prompt
		 *
		 * Customize the DALL-E image generation prompt.
		 *
		 * @param string $prompt Generated prompt.
		 * @param string $topic  Article topic.
		 * @param string $style  Style used.
		 */
		return apply_filters( 'pearblog_image_prompt', $prompt, $topic, $style_used );
	}

	/**
	 * Download image from URL and add to WordPress media library.
	 *
	 * @param string $url   Image URL.
	 * @param string $title Image title/alt text.
	 * @return int|null     Attachment ID, or null on failure.
	 */
	private function download_to_media_library( string $url, string $title ): ?int {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download to temp file.
		$temp_file = download_url( $url );

		if ( is_wp_error( $temp_file ) ) {
			error_log( 'PearBlog Engine: Failed to download image – ' . $temp_file->get_error_message() );
			return null;
		}

		// Prepare file array for media_handle_sideload.
		$file_array = [
			'name'     => sanitize_file_name( $title ) . '.png',
			'tmp_name' => $temp_file,
		];

		// Insert into media library.
		$attachment_id = media_handle_sideload( $file_array, 0, $title );

		// Clean up temp file if still exists.
		if ( file_exists( $temp_file ) ) {
			@unlink( $temp_file );
		}

		if ( is_wp_error( $attachment_id ) ) {
			error_log( 'PearBlog Engine: Failed to add image to media library – ' . $attachment_id->get_error_message() );
			return null;
		}

		// Set alt text for SEO and accessibility.
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title );

		// Set canonical image description for Open Graph and Schema.org
		update_post_meta( $attachment_id, '_pearblog_canonical_description', $title );

		// Mark as AI-generated for tracking
		update_post_meta( $attachment_id, '_pearblog_ai_generated', true );
		update_post_meta( $attachment_id, '_pearblog_generation_date', current_time( 'timestamp' ) );
		update_post_meta( $attachment_id, '_pearblog_image_source', 'dall-e-3' );

		// DALL-E 3 standard size is 1792x1024
		update_post_meta( $attachment_id, '_pearblog_original_width', 1792 );
		update_post_meta( $attachment_id, '_pearblog_original_height', 1024 );

		return $attachment_id;
	}
}
