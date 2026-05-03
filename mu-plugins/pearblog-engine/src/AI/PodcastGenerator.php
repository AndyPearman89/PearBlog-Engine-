<?php
/**
 * Podcast Generator – article-to-audio pipeline.
 *
 * Converts any published WordPress article into:
 *   1. A conversational podcast script (via the configured AI provider).
 *   2. An MP3 audio file synthesised by ElevenLabs TTS.
 *   3. An HTML5 `<audio>` player block automatically prepended to the post
 *      content and served from the WordPress uploads directory.
 *
 * Options:
 *   pearblog_elevenlabs_api_key   – ElevenLabs API key (required for TTS).
 *   pearblog_elevenlabs_voice_id  – Voice ID to use (default: "21m00Tcm4TlvDq8ikWAM").
 *   pearblog_podcast_auto_insert  – bool; auto-insert player on publish.
 *
 * REST endpoints:
 *   POST /pearblog/v1/article/{id}/podcast   – generate & attach podcast
 *   GET  /pearblog/v1/article/{id}/podcast   – fetch podcast meta for article
 *
 * WP-CLI:
 *   wp pearblog podcast generate <post_id> [--voice=<voice_id>]
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Generates podcast scripts and audio files from article content.
 */
class PodcastGenerator {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Post meta key for the generated audio URL. */
	private const META_AUDIO_URL = 'pearblog_podcast_url';

	/** Post meta key for the raw podcast script. */
	private const META_SCRIPT = 'pearblog_podcast_script';

	/** ElevenLabs TTS endpoint. */
	private const ELEVENLABS_TTS_URL = 'https://api.elevenlabs.io/v1/text-to-speech/%s';

	/** Default voice (Rachel – English, narrative). */
	private const DEFAULT_VOICE_ID = '21m00Tcm4TlvDq8ikWAM';

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks and REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );

		if ( (bool) get_option( 'pearblog_podcast_auto_insert', false ) ) {
			add_action( 'publish_post', [ $this, 'auto_generate_on_publish' ], 20, 1 );
		}

		// Prepend audio player to post content when the meta is set.
		add_filter( 'the_content', [ $this, 'prepend_audio_player' ] );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/article/(?P<id>[\d]+)/podcast',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'rest_generate' ],
					'permission_callback' => [ $this, 'rest_permission' ],
					'args'                => [
						'id'       => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
						'voice_id' => [ 'required' => false, 'type' => 'string' ],
					],
				],
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'rest_get' ],
					'permission_callback' => [ $this, 'rest_permission' ],
					'args'                => [
						'id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
					],
				],
			]
		);
	}

	/**
	 * Permission callback – manage_options or Bearer token.
	 */
	public function rest_permission( \WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$stored = get_option( 'pearblog_api_key', '' );
		$header = $request->get_header( 'Authorization' ) ?? '';
		if ( str_starts_with( $header, 'Bearer ' ) ) {
			$token = trim( substr( $header, 7 ) );
			return hash_equals( $stored, $token );
		}
		return false;
	}

	/**
	 * REST POST /article/{id}/podcast – generate podcast.
	 */
	public function rest_generate( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id  = (int) $request->get_param( 'id' );
		$voice_id = (string) ( $request->get_param( 'voice_id' ) ?: get_option( 'pearblog_elevenlabs_voice_id', self::DEFAULT_VOICE_ID ) );

		$result = $this->generate( $post_id, $voice_id );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [ 'error' => $result->get_error_message() ], 422 );
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * REST GET /article/{id}/podcast – fetch podcast meta.
	 */
	public function rest_get( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id   = (int) $request->get_param( 'id' );
		$audio_url = get_post_meta( $post_id, self::META_AUDIO_URL, true );
		$script    = get_post_meta( $post_id, self::META_SCRIPT, true );

		if ( ! $audio_url ) {
			return new \WP_REST_Response( [ 'error' => 'No podcast generated for this article.' ], 404 );
		}

		return new \WP_REST_Response(
			[
				'post_id'   => $post_id,
				'audio_url' => $audio_url,
				'script'    => $script,
			],
			200
		);
	}

	// -----------------------------------------------------------------------
	// Core generation
	// -----------------------------------------------------------------------

	/**
	 * Generate a podcast script and audio file for a post.
	 *
	 * @param int    $post_id  WordPress post ID.
	 * @param string $voice_id ElevenLabs voice ID.
	 * @return array|\WP_Error  Result array or WP_Error.
	 */
	public function generate( int $post_id, string $voice_id = self::DEFAULT_VOICE_ID ): array|\WP_Error {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'invalid_post', "Post #{$post_id} not found." );
		}

		// Step 1 – build script via AI.
		$script = $this->build_script( $post );
		if ( is_wp_error( $script ) ) {
			return $script;
		}

		// Step 2 – synthesise audio via ElevenLabs.
		$audio_url = $this->synthesise_audio( $script, $post_id, $voice_id );
		if ( is_wp_error( $audio_url ) ) {
			return $audio_url;
		}

		// Step 3 – persist meta.
		update_post_meta( $post_id, self::META_SCRIPT, $script );
		update_post_meta( $post_id, self::META_AUDIO_URL, $audio_url );

		return [
			'post_id'   => $post_id,
			'audio_url' => $audio_url,
			'script'    => $script,
		];
	}

	// -----------------------------------------------------------------------
	// Auto-insert hook
	// -----------------------------------------------------------------------

	/**
	 * Automatically generate a podcast when a post is published.
	 *
	 * @param int $post_id WordPress post ID.
	 */
	public function auto_generate_on_publish( int $post_id ): void {
		// Only for standard posts, skip if already generated.
		if ( 'post' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( get_post_meta( $post_id, self::META_AUDIO_URL, true ) ) {
			return;
		}
		$this->generate( $post_id );
	}

	// -----------------------------------------------------------------------
	// Content filter – prepend audio player
	// -----------------------------------------------------------------------

	/**
	 * Prepend an HTML5 audio player before the post content.
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function prepend_audio_player( string $content ): string {
		if ( ! is_singular( 'post' ) || is_admin() ) {
			return $content;
		}

		$post_id   = get_the_ID();
		$audio_url = get_post_meta( $post_id, self::META_AUDIO_URL, true );

		if ( ! $audio_url ) {
			return $content;
		}

		$url    = esc_url( $audio_url );
		$label  = esc_html__( 'Listen to this article', 'pearblog-engine' );
		$player = <<<HTML
<div class="pearblog-podcast-player" style="margin:1.5em 0;padding:1em;background:#f8f8f8;border-radius:8px;display:flex;align-items:center;gap:1em;">
  <span style="font-weight:600;white-space:nowrap">{$label}</span>
  <audio controls preload="metadata" style="flex:1;min-width:0">
    <source src="{$url}" type="audio/mpeg">
  </audio>
</div>
HTML;

		return $player . $content;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Generate a conversational podcast script from post content using AI.
	 *
	 * @param \WP_Post $post WordPress post object.
	 * @return string|\WP_Error  Script text or WP_Error.
	 */
	private function build_script( \WP_Post $post ): string|\WP_Error {
		$api_key = get_option( 'pearblog_openai_api_key', '' );
		if ( ! $api_key ) {
			return new \WP_Error( 'no_api_key', 'OpenAI API key not configured.' );
		}

		$title   = wp_strip_all_tags( $post->post_title );
		$content = wp_strip_all_tags( $post->post_content );
		// Truncate to ~4 000 chars to stay within token budget.
		if ( strlen( $content ) > 4000 ) {
			$content = substr( $content, 0, 4000 ) . '…';
		}

		$prompt = <<<PROMPT
You are a professional podcast host. Convert the following article into a natural, conversational podcast script for a single speaker.

Guidelines:
- Start with a brief, engaging introduction.
- Present the key points naturally without reading lists verbatim.
- Use conversational transitions ("Now, let's look at...", "What's interesting here is...").
- End with a clear call to action / summary (60–90 seconds when read aloud).
- Do NOT include stage directions, timestamps, or metadata.
- Target length: 3–5 minutes when read at normal pace (~130 wpm).

Article title: {$title}

Article content:
{$content}
PROMPT;

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			[
				'timeout' => 60,
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				],
				'body' => wp_json_encode(
					[
						'model'       => 'gpt-4o-mini',
						'messages'    => [ [ 'role' => 'user', 'content' => $prompt ] ],
						'max_tokens'  => 1500,
						'temperature' => 0.7,
					]
				),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['choices'][0]['message']['content'] ?? '';

		if ( ! $text ) {
			return new \WP_Error( 'ai_empty', 'AI returned an empty script.' );
		}

		return trim( $text );
	}

	/**
	 * Synthesise audio via ElevenLabs and save to the uploads directory.
	 *
	 * @param string $script   Podcast script text.
	 * @param int    $post_id  WordPress post ID (used for filename).
	 * @param string $voice_id ElevenLabs voice ID.
	 * @return string|\WP_Error  Public URL of the saved MP3 or WP_Error.
	 */
	private function synthesise_audio( string $script, int $post_id, string $voice_id ): string|\WP_Error {
		$api_key = get_option( 'pearblog_elevenlabs_api_key', '' );
		if ( ! $api_key ) {
			// Graceful degradation – return empty string so meta is not set.
			return new \WP_Error( 'no_elevenlabs_key', 'ElevenLabs API key not configured. Script saved but no audio generated.' );
		}

		$url      = sprintf( self::ELEVENLABS_TTS_URL, rawurlencode( $voice_id ) );
		$response = wp_remote_post(
			$url,
			[
				'timeout' => 120,
				'headers' => [
					'xi-api-key'   => $api_key,
					'Content-Type' => 'application/json',
					'Accept'       => 'audio/mpeg',
				],
				'body' => wp_json_encode(
					[
						'text'           => $script,
						'model_id'       => 'eleven_monolingual_v1',
						'voice_settings' => [ 'stability' => 0.5, 'similarity_boost' => 0.75 ],
					]
				),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $status ) {
			$msg = wp_remote_retrieve_body( $response );
			return new \WP_Error( 'elevenlabs_error', "ElevenLabs API error {$status}: {$msg}" );
		}

		// Save audio file to uploads.
		$audio_data   = wp_remote_retrieve_body( $response );
		$upload_dir   = wp_upload_dir();
		$subdir       = '/pearblog-podcasts';
		$dir_path     = $upload_dir['basedir'] . $subdir;
		$dir_url      = $upload_dir['baseurl'] . $subdir;

		if ( ! wp_mkdir_p( $dir_path ) ) {
			return new \WP_Error( 'upload_dir', 'Could not create podcast upload directory.' );
		}

		$filename = "podcast-{$post_id}-" . time() . '.mp3';
		$file_path = $dir_path . '/' . $filename;

		if ( false === file_put_contents( $file_path, $audio_data ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			return new \WP_Error( 'file_write', 'Could not write audio file to disk.' );
		}

		return $dir_url . '/' . $filename;
	}
}
