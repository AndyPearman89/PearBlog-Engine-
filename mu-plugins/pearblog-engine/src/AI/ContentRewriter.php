<?php
/**
 * Content Rewriter – AI-powered article refresh and rewrite engine.
 *
 * Rewrites existing published articles using the configured AI provider to
 * produce improved or repurposed versions.  Key features:
 *
 *   - Full rewrite mode: generates a fresh article on the same topic.
 *   - Refresh mode: preserves the structure but modernises facts, stats,
 *     and examples (calls PromptOptimizer to improve the prompt first).
 *   - Section-level rewrite: rewrites a specific H2/H3 section without
 *     touching the rest of the article.
 *   - Changelog tracking: stores a revision log in post meta so editors
 *     can see what changed and when.
 *   - Batch rewrite queue: allows `wp pearblog rewrite batch --days=30`
 *     to queue articles older than N days for background refresh.
 *
 * Post meta keys:
 *   _pearblog_rewrite_log  – JSON-encoded array of rewrite events
 *   _pearblog_last_rewrite – Unix timestamp of the last AI rewrite
 *
 * Options:
 *   pearblog_rewrite_enabled       – bool master switch (default true)
 *   pearblog_rewrite_model         – AI model override (default: site AI model)
 *   pearblog_rewrite_max_age_days  – articles older than N days are candidates
 *                                    for batch refresh (default 90)
 *
 * REST endpoints:
 *   POST /pearblog/v1/rewrite/{post_id}          – full or refresh rewrite
 *   POST /pearblog/v1/rewrite/{post_id}/section  – section-level rewrite
 *   GET  /pearblog/v1/rewrite/{post_id}/log      – revision history
 *
 * Actions fired:
 *   pearblog_post_rewritten ($post_id, $mode, $result)
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * AI-powered article rewriter.
 */
class ContentRewriter {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Post meta keys. */
	private const META_LOG        = '_pearblog_rewrite_log';
	private const META_LAST       = '_pearblog_last_rewrite';

	/** Rewrite modes. */
	public const MODE_FULL    = 'full';
	public const MODE_REFRESH = 'refresh';
	public const MODE_SECTION = 'section';

	/** @var AIClient */
	private AIClient $ai;

	/**
	 * Constructor.
	 *
	 * @param AIClient|null $ai AI client instance. Defaults to new AIClient.
	 */
	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register hooks and REST routes.
	 */
	public function register(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Whether the rewriter is globally enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) get_option( 'pearblog_rewrite_enabled', true );
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/rewrite/(?P<id>[\d]+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_rewrite' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id'   => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
				'mode' => [
					'required' => false,
					'type'     => 'string',
					'default'  => self::MODE_REFRESH,
					'enum'     => [ self::MODE_FULL, self::MODE_REFRESH ],
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/rewrite/(?P<id>[\d]+)/section', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_rewrite_section' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id'      => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
				'heading' => [ 'required' => true, 'type' => 'string', 'minLength' => 1 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/rewrite/(?P<id>[\d]+)/log', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_log' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
			],
		] );
	}

	/**
	 * Permission – manage_options or valid API key.
	 */
	public function rest_permission( \WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$stored = get_option( 'pearblog_api_key', '' );
		if ( '' === $stored ) {
			return false;
		}
		$header = $request->get_header( 'Authorization' ) ?? '';
		if ( str_starts_with( $header, 'Bearer ' ) ) {
			return hash_equals( $stored, trim( substr( $header, 7 ) ) );
		}
		return false;
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * POST /rewrite/{id} – full or refresh rewrite.
	 */
	public function rest_rewrite( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$mode    = (string) ( $request->get_param( 'mode' ) ?? self::MODE_REFRESH );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_REST_Response( [ 'error' => "Post #{$post_id} not found." ], 404 );
		}

		$result = self::MODE_FULL === $mode
			? $this->full_rewrite( $post )
			: $this->refresh_rewrite( $post );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [ 'error' => $result->get_error_message() ], 500 );
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * POST /rewrite/{id}/section – section-level rewrite.
	 */
	public function rest_rewrite_section( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$heading = sanitize_text_field( $request->get_param( 'heading' ) );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_REST_Response( [ 'error' => "Post #{$post_id} not found." ], 404 );
		}

		$result = $this->rewrite_section( $post, $heading );
		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [ 'error' => $result->get_error_message() ], 500 );
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /rewrite/{id}/log – return rewrite log.
	 */
	public function rest_get_log( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		return new \WP_REST_Response( $this->get_log( $post_id ), 200 );
	}

	// -----------------------------------------------------------------------
	// Core rewrite logic
	// -----------------------------------------------------------------------

	/**
	 * Generate a completely fresh article on the same topic.
	 *
	 * @param \WP_Post $post Original post.
	 * @return array|\WP_Error  Result array or WP_Error.
	 */
	public function full_rewrite( \WP_Post $post ): array|\WP_Error {
		$topic  = $post->post_title;
		$prompt = "Write a completely fresh, comprehensive article on the topic: \"{$topic}\". "
		        . "Do not reference the previous version. Target the same audience. "
		        . "Output only the article HTML content.";

		$new_content = $this->ai->generate( $prompt );

		if ( ! $new_content ) {
			return new \WP_Error( 'ai_failed', 'AI generation returned empty content.' );
		}

		return $this->apply_rewrite( $post, $new_content, self::MODE_FULL );
	}

	/**
	 * Refresh an existing article: update facts, stats, examples.
	 *
	 * @param \WP_Post $post Original post.
	 * @return array|\WP_Error  Result array or WP_Error.
	 */
	public function refresh_rewrite( \WP_Post $post ): array|\WP_Error {
		$old_content = wp_strip_all_tags( $post->post_content );
		$prompt      = "You are an expert editor. Refresh the following article by updating "
		             . "outdated statistics, examples, and facts. Keep the structure and "
		             . "overall narrative intact. Improve clarity where possible. "
		             . "Output only the updated article HTML.\n\n"
		             . "ARTICLE:\n{$old_content}";

		$new_content = $this->ai->generate( $prompt );

		if ( ! $new_content ) {
			return new \WP_Error( 'ai_failed', 'AI generation returned empty content.' );
		}

		return $this->apply_rewrite( $post, $new_content, self::MODE_REFRESH );
	}

	/**
	 * Rewrite a specific section (identified by heading text).
	 *
	 * @param \WP_Post $post    Original post.
	 * @param string   $heading Heading text that identifies the section.
	 * @return array|\WP_Error
	 */
	public function rewrite_section( \WP_Post $post, string $heading ): array|\WP_Error {
		$content   = $post->post_content;
		$section   = $this->extract_section( $content, $heading );

		if ( null === $section ) {
			return new \WP_Error( 'section_not_found', "Section with heading \"{$heading}\" not found." );
		}

		$prompt      = "Rewrite the following section of an article for clarity and freshness. "
		             . "Keep the heading. Output only the improved HTML section.\n\n"
		             . "SECTION:\n{$section}";

		$new_section = $this->ai->generate( $prompt );
		if ( ! $new_section ) {
			return new \WP_Error( 'ai_failed', 'AI generation returned empty content.' );
		}

		$new_content = str_replace( $section, $new_section, $content );

		return $this->apply_rewrite( $post, $new_content, self::MODE_SECTION );
	}

	/**
	 * Apply rewritten content to the post and log the event.
	 *
	 * @param \WP_Post $post        Original post.
	 * @param string   $new_content New HTML content.
	 * @param string   $mode        Rewrite mode constant.
	 * @return array  Result summary.
	 */
	public function apply_rewrite( \WP_Post $post, string $new_content, string $mode ): array {
		wp_update_post( [
			'ID'           => $post->ID,
			'post_content' => $new_content,
		] );

		$entry = [
			'mode'        => $mode,
			'timestamp'   => time(),
			'word_count'  => str_word_count( wp_strip_all_tags( $new_content ) ),
			'prev_length' => strlen( $post->post_content ),
			'new_length'  => strlen( $new_content ),
		];

		$this->append_log( $post->ID, $entry );
		update_post_meta( $post->ID, self::META_LAST, time() );

		do_action( 'pearblog_post_rewritten', $post->ID, $mode, $entry );

		return [
			'post_id'  => $post->ID,
			'mode'     => $mode,
			'success'  => true,
			'entry'    => $entry,
		];
	}

	// -----------------------------------------------------------------------
	// Batch queue
	// -----------------------------------------------------------------------

	/**
	 * Return post IDs that are older than the configured max-age and haven't
	 * been rewritten recently.
	 *
	 * @param int $limit Maximum number of candidates to return.
	 * @return int[]  Array of WordPress post IDs.
	 */
	public function get_batch_candidates( int $limit = 10 ): array {
		$max_age = (int) get_option( 'pearblog_rewrite_max_age_days', 90 );
		$cutoff  = gmdate( 'Y-m-d H:i:s', strtotime( "-{$max_age} days" ) );

		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'date_query'     => [ [ 'before' => $cutoff, 'inclusive' => true ] ],
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => self::META_LAST,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => self::META_LAST,
					'value'   => strtotime( "-{$max_age} days" ),
					'compare' => '<',
					'type'    => 'NUMERIC',
				],
			],
			'fields' => 'ids',
		] );

		return array_map( 'intval', $posts ?: [] );
	}

	// -----------------------------------------------------------------------
	// Revision log
	// -----------------------------------------------------------------------

	/**
	 * Return the full rewrite log for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array  Ordered list of log entries (newest first).
	 */
	public function get_log( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META_LOG, true );
		$log = $raw ? (array) json_decode( $raw, true ) : [];
		return array_reverse( $log );
	}

	/**
	 * Append a new entry to the post's rewrite log.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $entry   Log entry.
	 */
	private function append_log( int $post_id, array $entry ): void {
		$raw = get_post_meta( $post_id, self::META_LOG, true );
		$log = $raw ? (array) json_decode( $raw, true ) : [];
		$log[] = $entry;
		// Keep last 50 entries.
		if ( count( $log ) > 50 ) {
			$log = array_slice( $log, -50 );
		}
		update_post_meta( $post_id, self::META_LOG, wp_json_encode( $log ) );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Extract a content section between the given heading and the next heading
	 * of the same or higher level.
	 *
	 * @param string $content HTML content.
	 * @param string $heading Heading text to look for.
	 * @return string|null  HTML of the section, or null if not found.
	 */
	private function extract_section( string $content, string $heading ): ?string {
		$safe    = preg_quote( $heading, '/' );
		// Match <h2>...heading...</h2> or <h3>...heading...</h3> and everything until the next heading.
		$pattern = '/(<h[2-6][^>]*>.*?' . $safe . '.*?<\/h[2-6]>)(.*?)(?=<h[2-6]|$)/si';
		if ( preg_match( $pattern, $content, $matches ) ) {
			return $matches[1] . $matches[2];
		}
		return null;
	}
}
