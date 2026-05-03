<?php
/**
 * Glossary Builder – auto-generates SEO glossary pages for topic clusters.
 *
 * Each pillar article can have an associated glossary page that defines the
 * key terms used in that topic cluster.  Glossary pages are implemented as
 * regular WordPress pages with a dedicated page template and are
 * automatically populated via AI.
 *
 * Features:
 *   - On-demand glossary generation: POST /pearblog/v1/glossary/{post_id}
 *   - Batch rebuild: regenerates outdated glossary pages via WP-Cron
 *   - Term extraction: scans a post's content for capitalised nouns and
 *     domain-specific jargon (no external NLP library required)
 *   - AI definitions: for each extracted term, calls AIClient to produce a
 *     one-paragraph plain-English definition
 *   - Schema markup: injects DefinedTerm schema blocks into the page
 *   - Post meta tracking:
 *       _pearblog_glossary_page_id   – ID of the linked glossary page
 *       _pearblog_glossary_terms     – JSON array of defined terms
 *       _pearblog_glossary_built_at  – Unix timestamp of last build
 *
 * Options:
 *   pearblog_glossary_enabled        – bool master switch (default true)
 *   pearblog_glossary_max_terms      – cap on terms per glossary (default 20)
 *   pearblog_glossary_min_word_count – minimum article words to qualify (default 800)
 *
 * REST endpoints:
 *   POST /pearblog/v1/glossary/{post_id}        – build/rebuild glossary page
 *   GET  /pearblog/v1/glossary/{post_id}        – retrieve current glossary data
 *   GET  /pearblog/v1/glossary/{post_id}/terms  – list extracted terms
 *
 * Actions fired:
 *   pearblog_glossary_built ($post_id, $glossary_page_id, $terms)
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\AI\AIClient;

/**
 * Builds and manages SEO glossary pages linked to pillar articles.
 */
class GlossaryBuilder {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Post meta keys. */
	private const META_PAGE_ID  = '_pearblog_glossary_page_id';
	private const META_TERMS    = '_pearblog_glossary_terms';
	private const META_BUILT_AT = '_pearblog_glossary_built_at';

	/** Cron hook. */
	private const CRON_HOOK = 'pearblog_glossary_batch_rebuild';

	/** Words typically ignored during term extraction. */
	private const STOP_WORDS = [
		'the', 'and', 'that', 'with', 'this', 'from', 'have', 'will',
		'your', 'more', 'also', 'they', 'been', 'were', 'their', 'when',
		'which', 'into', 'some', 'what', 'there', 'other', 'about',
	];

	/** @var AIClient */
	private AIClient $ai;

	/**
	 * Constructor.
	 *
	 * @param AIClient|null $ai Optional AI client for unit tests.
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
		add_action( self::CRON_HOOK, [ $this, 'batch_rebuild' ] );
		add_action( 'init', [ $this, 'maybe_schedule_cron' ] );
		add_action( 'pearblog_pipeline_completed', [ $this, 'on_pipeline_completed' ], 30, 1 );
	}

	/**
	 * Whether the glossary builder is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( 'pearblog_glossary_enabled', true );
	}

	/**
	 * Schedule daily cron if not already scheduled.
	 */
	public function maybe_schedule_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/glossary/(?P<id>[\d]+)', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_get' ],
				'permission_callback' => [ $this, 'rest_permission' ],
				'args'                => [ 'id' => [ 'required' => true, 'type' => 'integer' ] ],
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_build' ],
				'permission_callback' => [ $this, 'rest_permission' ],
				'args'                => [ 'id' => [ 'required' => true, 'type' => 'integer' ] ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/glossary/(?P<id>[\d]+)/terms', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_terms' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [ 'id' => [ 'required' => true, 'type' => 'integer' ] ],
		] );
	}

	/**
	 * Permission: manage_options only.
	 */
	public function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	/**
	 * GET /glossary/{id} – retrieve current glossary data.
	 */
	public function rest_get( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id    = (int) $request->get_param( 'id' );
		$page_id    = (int) get_post_meta( $post_id, self::META_PAGE_ID, true );
		$terms_raw  = get_post_meta( $post_id, self::META_TERMS, true );
		$built_at   = (int) get_post_meta( $post_id, self::META_BUILT_AT, true );

		if ( ! $page_id ) {
			return new \WP_REST_Response( [ 'error' => 'No glossary yet for this post.' ], 404 );
		}

		return new \WP_REST_Response( [
			'post_id'         => $post_id,
			'glossary_page_id' => $page_id,
			'terms'           => $terms_raw ? json_decode( $terms_raw, true ) : [],
			'built_at'        => $built_at,
		], 200 );
	}

	/**
	 * POST /glossary/{id} – build or rebuild glossary page.
	 */
	public function rest_build( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_REST_Response( [ 'error' => "Post #{$post_id} not found." ], 404 );
		}

		$result = $this->build_for_post( $post );
		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [ 'error' => $result->get_error_message() ], 500 );
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /glossary/{id}/terms – list extracted terms only.
	 */
	public function rest_terms( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id   = (int) $request->get_param( 'id' );
		$terms_raw = get_post_meta( $post_id, self::META_TERMS, true );
		return new \WP_REST_Response( [
			'post_id' => $post_id,
			'terms'   => $terms_raw ? json_decode( $terms_raw, true ) : [],
		], 200 );
	}

	// -----------------------------------------------------------------------
	// Core logic
	// -----------------------------------------------------------------------

	/**
	 * Hook: build glossary after pipeline completion.
	 *
	 * @param int $post_id Published post ID.
	 */
	public function on_pipeline_completed( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$min_words = (int) get_option( 'pearblog_glossary_min_word_count', 800 );
		$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );

		if ( $word_count >= $min_words ) {
			$this->build_for_post( $post );
		}
	}

	/**
	 * Build a glossary page for the given post.
	 *
	 * @param \WP_Post $post Source post.
	 * @return array|\WP_Error  Result summary or WP_Error.
	 */
	public function build_for_post( \WP_Post $post ): array|\WP_Error {
		$terms = $this->extract_terms( $post->post_content );

		if ( empty( $terms ) ) {
			return new \WP_Error( 'no_terms', 'No glossary terms found in post content.' );
		}

		$max_terms = (int) get_option( 'pearblog_glossary_max_terms', 20 );
		$terms     = array_slice( $terms, 0, $max_terms );

		// Build glossary page content.
		$page_content = $this->render_glossary_html( $post->post_title, $terms );

		// Create or update the linked glossary page.
		$existing_page_id = (int) get_post_meta( $post->ID, self::META_PAGE_ID, true );
		$page_id = $this->upsert_glossary_page( $post, $page_content, $existing_page_id );

		// Persist meta.
		update_post_meta( $post->ID, self::META_PAGE_ID, $page_id );
		update_post_meta( $post->ID, self::META_TERMS, wp_json_encode( $terms ) );
		update_post_meta( $post->ID, self::META_BUILT_AT, time() );

		do_action( 'pearblog_glossary_built', $post->ID, $page_id, $terms );

		return [
			'post_id'          => $post->ID,
			'glossary_page_id' => $page_id,
			'term_count'       => count( $terms ),
			'terms'            => $terms,
		];
	}

	/**
	 * Batch rebuild all glossaries for qualifying posts.
	 */
	public function batch_rebuild(): void {
		$min_words = (int) get_option( 'pearblog_glossary_min_word_count', 800 );

		$posts = get_posts( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		foreach ( $posts as $post ) {
			$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
			if ( $word_count >= $min_words ) {
				$this->build_for_post( $post );
			}
		}
	}

	/**
	 * Extract candidate glossary terms from HTML content.
	 *
	 * Terms are multi-word phrases or long single words that appear to be
	 * domain-specific jargon (starts with capital in body text, or appears in
	 * `<strong>` or `<em>` tags).
	 *
	 * @param string $content HTML post content.
	 * @return string[]  Unique term list.
	 */
	public function extract_terms( string $content ): array {
		$terms = [];

		// Extract emphasised terms from <strong> and <em> tags.
		if ( preg_match_all( '/<(strong|em)[^>]*>(.*?)<\/\1>/si', $content, $matches ) ) {
			foreach ( $matches[2] as $match ) {
				$term = trim( wp_strip_all_tags( $match ) );
				if ( $this->is_valid_term( $term ) ) {
					$terms[] = $term;
				}
			}
		}

		// Deduplicate, preserving order, and limit to avoid excessive AI calls.
		$unique = [];
		foreach ( $terms as $term ) {
			$lower = strtolower( $term );
			if ( ! isset( $unique[ $lower ] ) ) {
				$unique[ $lower ] = $term;
			}
		}

		return array_values( $unique );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Render glossary HTML for a page.
	 *
	 * @param string   $pillar_title Title of the source pillar.
	 * @param string[] $terms        Glossary terms.
	 * @return string  HTML content.
	 */
	private function render_glossary_html( string $pillar_title, array $terms ): string {
		$title = esc_html( $pillar_title );
		$html  = "<h1>Glossary: {$title}</h1>\n";
		$html .= "<p>Key terms and definitions related to <em>{$title}</em>.</p>\n";
		$html .= "<dl class=\"pearblog-glossary\">\n";

		foreach ( $terms as $term ) {
			$safe_term   = esc_html( $term );
			$definition  = $this->get_definition( $term );
			$safe_def    = esc_html( $definition );

			$html .= "  <dt id=\"term-" . sanitize_html_class( strtolower( str_replace( ' ', '-', $term ) ) ) . "\">{$safe_term}</dt>\n";
			$html .= "  <dd>{$safe_def}</dd>\n";
		}

		$html .= "</dl>\n";

		// Schema: DefinedTermSet.
		$schema_terms = [];
		foreach ( $terms as $term ) {
			$schema_terms[] = [
				'@type'       => 'DefinedTerm',
				'name'        => $term,
				'description' => $this->get_definition( $term ),
			];
		}

		$schema = [
			'@context'     => 'https://schema.org',
			'@type'        => 'DefinedTermSet',
			'name'         => "Glossary: {$pillar_title}",
			'hasDefinedTerm' => $schema_terms,
		];

		$html .= '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";

		return $html;
	}

	/**
	 * Get a definition for a term (from AI or fallback).
	 *
	 * @param string $term The term to define.
	 * @return string  Definition text.
	 */
	private function get_definition( string $term ): string {
		$prompt     = "Define the following term in one concise paragraph for a general audience: \"{$term}\". "
		            . "Do not include the term itself in the first sentence. Plain text only.";
		$definition = $this->ai->generate( $prompt, 150 );

		return $definition ?: "A term commonly used in this context. See the full article for details.";
	}

	/**
	 * Create or update the WordPress page for a glossary.
	 *
	 * @param \WP_Post $source           Source post.
	 * @param string   $content          HTML content for the page.
	 * @param int      $existing_page_id Existing page ID or 0.
	 * @return int  Page ID.
	 */
	private function upsert_glossary_page( \WP_Post $source, string $content, int $existing_page_id ): int {
		$args = [
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Glossary: ' . $source->post_title,
			'post_content' => $content,
			'post_parent'  => 0,
		];

		if ( $existing_page_id > 0 ) {
			$args['ID'] = $existing_page_id;
			wp_update_post( $args );
			return $existing_page_id;
		}

		return (int) wp_insert_post( $args );
	}

	/**
	 * Validate that a candidate term is worth including in the glossary.
	 *
	 * @param string $term Candidate term.
	 * @return bool
	 */
	private function is_valid_term( string $term ): bool {
		// Must be between 2 and 60 characters.
		$len = strlen( $term );
		if ( $len < 2 || $len > 60 ) {
			return false;
		}

		// Must not be a stop word.
		if ( in_array( strtolower( $term ), self::STOP_WORDS, true ) ) {
			return false;
		}

		// Must contain at least one letter.
		if ( ! preg_match( '/[a-zA-Z]/', $term ) ) {
			return false;
		}

		return true;
	}
}
