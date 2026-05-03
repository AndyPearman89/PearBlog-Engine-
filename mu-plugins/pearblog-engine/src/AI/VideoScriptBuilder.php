<?php
/**
 * Video Script Builder – converts articles into video scripts.
 *
 * Generates platform-optimized scripts for YouTube, TikTok and YouTube Shorts
 * by prompting the AI with a format-specific template.
 *
 * REST endpoint:
 *   POST /pearblog/v1/article/{post_id}/video-script
 *   Body: { "platform": "youtube|tiktok|shorts" }
 *
 * Stores the generated script in post meta `pearblog_video_script_{platform}`.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Converts published articles into video scripts.
 */
class VideoScriptBuilder {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Post meta key prefix. */
	private const META_PREFIX = 'pearblog_video_script_';

	/** Supported platforms. */
	public const PLATFORM_YOUTUBE = 'youtube';
	public const PLATFORM_TIKTOK  = 'tiktok';
	public const PLATFORM_SHORTS  = 'shorts';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the video script REST route.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/article/(?P<post_id>\d+)/video-script', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_generate' ],
			'permission_callback' => [ $this, 'editor_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/article/(?P<post_id>\d+)/video-script', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_scripts' ],
			'permission_callback' => [ $this, 'editor_permission' ],
		] );
	}

	// -----------------------------------------------------------------------
	// Core generation
	// -----------------------------------------------------------------------

	/**
	 * Generate a video script for a specific post and platform.
	 *
	 * @param int    $post_id  WordPress post ID.
	 * @param string $platform Target platform (youtube|tiktok|shorts).
	 * @return string Generated script.
	 * @throws \RuntimeException If post not found or AI fails.
	 */
	public function generate( int $post_id, string $platform = self::PLATFORM_YOUTUBE ): string {
		$post = get_post( $post_id );
		if ( ! $post ) {
			throw new \RuntimeException( "Post #{$post_id} not found." );
		}

		$content = wp_strip_all_tags( $post->post_content );
		$title   = $post->post_title;
		$prompt  = $this->build_prompt( $title, $content, $platform );

		$ai     = new AIClient();
		$script = $ai->generate( $prompt, 1500 );

		// Store the generated script.
		update_post_meta( $post_id, self::META_PREFIX . $platform, $script );
		update_post_meta( $post_id, self::META_PREFIX . 'generated_at', time() );

		/**
		 * Action: pearblog_video_script_generated
		 *
		 * @param int    $post_id  Post ID.
		 * @param string $platform Target platform.
		 * @param string $script   Generated script.
		 */
		do_action( 'pearblog_video_script_generated', $post_id, $platform, $script );

		return $script;
	}

	/**
	 * Get all stored video scripts for a post.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return array<string, string> Platform → script map.
	 */
	public function get_scripts( int $post_id ): array {
		$scripts = [];
		foreach ( [ self::PLATFORM_YOUTUBE, self::PLATFORM_TIKTOK, self::PLATFORM_SHORTS ] as $platform ) {
			$script = get_post_meta( $post_id, self::META_PREFIX . $platform, true );
			if ( ! empty( $script ) ) {
				$scripts[ $platform ] = $script;
			}
		}
		return $scripts;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Build a platform-specific generation prompt.
	 *
	 * @param string $title    Article title.
	 * @param string $content  Article content (plain text).
	 * @param string $platform Target platform.
	 * @return string Prompt string.
	 */
	private function build_prompt( string $title, string $content, string $platform ): string {
		$excerpt = substr( $content, 0, 1500 );

		$platform_instructions = match ( $platform ) {
			self::PLATFORM_TIKTOK => "
- Length: 60-90 seconds (150-200 words)
- Hook in first 3 seconds
- Use conversational, casual language
- Include 3-5 trending hashtags at the end
- Add [PAUSE] markers for natural breaks",

			self::PLATFORM_SHORTS => "
- Length: 30-60 seconds (75-150 words)
- Very strong hook in first 1-2 seconds
- One key takeaway only
- End with a clear call-to-action
- Vertical video format cues",

			default => "
- Length: 8-12 minutes (1200-1800 words)
- Include: [INTRO], [MAIN CONTENT] sections, [OUTRO]
- Hook viewers in the first 30 seconds
- Add chapter timestamps
- Include subscribe CTA
- End screen suggestions",
		};

		return "Convert this article into a {$platform} video script.

Article Title: {$title}

Article Content:
{$excerpt}

Platform-specific requirements:{$platform_instructions}

Format the script with:
- Speaker directions in [brackets]
- Scene descriptions in (parentheses)
- B-roll suggestions marked as B-ROLL:
- Clear paragraph breaks between sections";
	}

	// -----------------------------------------------------------------------
	// REST callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_generate( \WP_REST_Request $request ) {
		$post_id  = (int) $request->get_param( 'post_id' );
		$platform = (string) ( $request->get_param( 'platform' ) ?? self::PLATFORM_YOUTUBE );

		$valid_platforms = [ self::PLATFORM_YOUTUBE, self::PLATFORM_TIKTOK, self::PLATFORM_SHORTS ];
		if ( ! in_array( $platform, $valid_platforms, true ) ) {
			return new \WP_Error( 'invalid_platform', 'Invalid platform. Use: youtube, tiktok, or shorts.', [ 'status' => 400 ] );
		}

		try {
			$script = $this->generate( $post_id, $platform );
			return new \WP_REST_Response( [
				'post_id'  => $post_id,
				'platform' => $platform,
				'script'   => $script,
			], 200 );
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'generation_failed', $e->getMessage(), [ 'status' => 500 ] );
		}
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_scripts( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		return new \WP_REST_Response( [
			'post_id' => $post_id,
			'scripts' => $this->get_scripts( $post_id ),
		], 200 );
	}

	/**
	 * Permission callback – editors and above.
	 */
	public function editor_permission(): bool {
		return current_user_can( 'edit_posts' );
	}
}
