<?php
/**
 * Multilingual Manager — generates translated article variants via AI and
 * integrates with WPML and Polylang for native multilingual WordPress workflows.
 *
 * Workflow
 * ────────
 * 1. A source post (any language) is passed to `translate_post()`.
 * 2. The manager builds a translation prompt and calls the configured AI provider.
 * 3. The translated content is saved as a new draft post.
 * 4. If WPML or Polylang is active, the new post is linked to the source via
 *    the appropriate plugin's API.
 * 5. The `pearblog_translation_created` action is fired so other extensions
 *    (SEO, internal linker, etc.) can process the new post.
 *
 * Supported multilingual plugins (both optional):
 *   - WPML  (function `wpml_set_element_language_details`)
 *   - Polylang  (function `pll_set_post_language` + `pll_save_post_translations`)
 *
 * Configuration WP options:
 *   pearblog_ml_target_languages  – JSON array of ISO language codes (e.g. ["de","fr","es"])
 *   pearblog_ml_post_status       – Status for translated posts: "draft" (default) | "publish"
 *   pearblog_ml_prompt_template   – System message template; {language} and {source} placeholders
 *   pearblog_ml_enabled           – bool, master on/off switch (default false)
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\AI\AIClient;

/**
 * Creates multilingual variants of existing posts via AI translation.
 */
class MultilingualManager {

	// -----------------------------------------------------------------------
	// Option keys
	// -----------------------------------------------------------------------

	public const OPTION_TARGET_LANGUAGES = 'pearblog_ml_target_languages';
	public const OPTION_POST_STATUS      = 'pearblog_ml_post_status';
	public const OPTION_PROMPT_TEMPLATE  = 'pearblog_ml_prompt_template';
	public const OPTION_ENABLED          = 'pearblog_ml_enabled';

	// -----------------------------------------------------------------------
	// Meta keys
	// -----------------------------------------------------------------------

	/** Meta key that records the source post ID on each translated post. */
	public const META_SOURCE_POST_ID = '_pearblog_ml_source_post_id';

	/** Meta key that records the target language on each translated post. */
	public const META_LANGUAGE       = '_pearblog_ml_language';

	// -----------------------------------------------------------------------
	// Action hook
	// -----------------------------------------------------------------------

	public const ACTION_CREATED = 'pearblog_translation_created';

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	public const DEFAULT_PROMPT_TEMPLATE = <<<'TMPL'
You are a professional translator. Translate the following WordPress article content into {language}.
Keep all HTML tags intact. Preserve headings, bullet points, and formatting.
Do NOT translate URLs, plugin shortcodes, or code snippets.
Return only the translated content — no preamble or explanation.

---
{source}
TMPL;

	/** @var AIClient */
	private AIClient $ai;

	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	// -----------------------------------------------------------------------
	// Configuration
	// -----------------------------------------------------------------------

	/**
	 * Whether the multilingual feature is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	/**
	 * Get the list of configured target language codes.
	 *
	 * @return string[]
	 */
	public function get_target_languages(): array {
		$raw     = get_option( self::OPTION_TARGET_LANGUAGES, '[]' );
		$decoded = json_decode( is_string( $raw ) ? $raw : '[]', true );
		return is_array( $decoded ) ? array_values( array_filter( array_map( 'strval', $decoded ) ) ) : [];
	}

	/**
	 * Set the list of target language codes.
	 *
	 * @param string[] $languages  ISO language codes, e.g. ["de","fr","es"].
	 */
	public function set_target_languages( array $languages ): void {
		$sanitized = array_values( array_filter( array_map( 'sanitize_text_field', $languages ) ) );
		update_option( self::OPTION_TARGET_LANGUAGES, wp_json_encode( $sanitized ) );
	}

	/**
	 * Get the post status for newly created translations.
	 */
	public function get_post_status(): string {
		$status = (string) get_option( self::OPTION_POST_STATUS, 'draft' );
		return in_array( $status, [ 'draft', 'publish', 'pending' ], true ) ? $status : 'draft';
	}

	/**
	 * Get the prompt template.
	 */
	public function get_prompt_template(): string {
		$tpl = (string) get_option( self::OPTION_PROMPT_TEMPLATE, '' );
		return '' !== $tpl ? $tpl : self::DEFAULT_PROMPT_TEMPLATE;
	}

	// -----------------------------------------------------------------------
	// Translation
	// -----------------------------------------------------------------------

	/**
	 * Translate a single post into all configured target languages.
	 *
	 * @param int       $source_post_id
	 * @param string[]  $languages   Override languages; empty = use option.
	 * @return array<string, int|null>  Map of language_code → new post ID (null on failure).
	 */
	public function translate_post( int $source_post_id, array $languages = [] ): array {
		if ( ! $this->is_enabled() ) {
			return [];
		}

		if ( empty( $languages ) ) {
			$languages = $this->get_target_languages();
		}

		$results = [];
		foreach ( $languages as $lang ) {
			$results[ $lang ] = $this->translate_to_language( $source_post_id, $lang );
		}

		return $results;
	}

	/**
	 * Translate a post into a single target language.
	 *
	 * @param int    $source_post_id
	 * @param string $language        ISO language code.
	 * @return int|null               New post ID, or null on failure.
	 */
	public function translate_to_language( int $source_post_id, string $language ): ?int {
		$source_content = $this->get_post_content( $source_post_id );
		$source_title   = get_the_title( $source_post_id );

		if ( '' === $source_content ) {
			return null;
		}

		$translated_content = $this->call_ai( $source_content, $language );
		if ( '' === $translated_content ) {
			return null;
		}

		$translated_title = $this->call_ai( $source_title, $language );
		if ( '' === $translated_title ) {
			$translated_title = $source_title . " [{$language}]";
		}

		$new_post_id = $this->create_translation_post(
			$translated_title,
			$translated_content,
			$source_post_id,
			$language
		);

		if ( null === $new_post_id ) {
			return null;
		}

		$this->link_to_source( $source_post_id, $new_post_id, $language );

		/**
		 * Fires after a translated post variant is created.
		 *
		 * @param int    $new_post_id     The newly created post ID.
		 * @param int    $source_post_id  The original source post ID.
		 * @param string $language        ISO language code of the translation.
		 */
		do_action( self::ACTION_CREATED, $new_post_id, $source_post_id, $language );

		return $new_post_id;
	}

	// -----------------------------------------------------------------------
	// AI call
	// -----------------------------------------------------------------------

	/**
	 * Translate text to a target language via the AI client.
	 *
	 * @param string $text
	 * @param string $language
	 * @return string  Translated text, or empty string on failure.
	 */
	public function call_ai( string $text, string $language ): string {
		$prompt = str_replace(
			[ '{language}', '{source}' ],
			[ $language, $text ],
			$this->get_prompt_template()
		);

		$response = $this->ai->generate( $prompt );

		return is_string( $response ) ? trim( $response ) : '';
	}

	// -----------------------------------------------------------------------
	// WordPress post helpers
	// -----------------------------------------------------------------------

	/**
	 * Retrieve post content by ID (raw content, no filters).
	 */
	private function get_post_content( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		return is_object( $post ) ? (string) ( $post->post_content ?? '' ) : '';
	}

	/**
	 * Create a new WP draft post for the translation.
	 *
	 * @param string $title
	 * @param string $content
	 * @param int    $source_id
	 * @param string $language
	 * @return int|null  New post ID, or null on failure.
	 */
	private function create_translation_post(
		string $title,
		string $content,
		int    $source_id,
		string $language
	): ?int {
		$post_arr = [
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => $content,
			'post_status'  => $this->get_post_status(),
			'post_type'    => 'post',
		];

		$new_id = wp_insert_post( $post_arr, true );

		if ( is_wp_error( $new_id ) || $new_id <= 0 ) {
			return null;
		}

		update_post_meta( $new_id, self::META_SOURCE_POST_ID, $source_id );
		update_post_meta( $new_id, self::META_LANGUAGE, sanitize_text_field( $language ) );

		return $new_id;
	}

	/**
	 * Link the translated post to the source via WPML or Polylang if active.
	 *
	 * @param int    $source_id
	 * @param int    $new_id
	 * @param string $language
	 */
	private function link_to_source( int $source_id, int $new_id, string $language ): void {
		// WPML integration.
		if ( function_exists( 'wpml_set_element_language_details' ) ) {
			\wpml_set_element_language_details( [
				'element_id'           => $new_id,
				'element_type'         => 'post_post',
				'trid'                 => apply_filters( 'wpml_element_trid', null, $source_id, 'post_post' ),
				'language_code'        => $language,
				'source_language_code' => null,
			] );
			return;
		}

		// Polylang integration.
		if ( function_exists( 'pll_set_post_language' ) ) {
			\pll_set_post_language( $new_id, $language );

			$source_lang = function_exists( 'pll_get_post_language' )
				? \pll_get_post_language( $source_id )
				: '';

			if ( '' !== $source_lang ) {
				$translations = function_exists( 'pll_get_post_translations' )
					? \pll_get_post_translations( $source_id )
					: [];
				$translations[ $language ]     = $new_id;
				$translations[ $source_lang ]  = $source_id;
				\pll_save_post_translations( $translations );
			}
		}
	}
}
