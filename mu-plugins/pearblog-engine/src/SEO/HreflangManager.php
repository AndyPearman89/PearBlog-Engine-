<?php
/**
 * Hreflang Manager – International SEO via hreflang tags & per-language sitemaps.
 *
 * Automatically generates:
 *   - hreflang <link> tags in <head> for multilingual sites.
 *   - A per-language XML sitemap at /pearblog-sitemap-{lang}.xml
 *   - Optional automatic translation of articles via DeepL API.
 *
 * Options:
 *   pearblog_hreflang_languages   – array of active language codes (e.g. ['en','pl','de']).
 *   pearblog_hreflang_default     – default language code (e.g. 'en').
 *   pearblog_deepl_api_key        – DeepL API key for auto-translation.
 *   pearblog_hreflang_auto_trans  – bool; auto-translate new posts on publish.
 *
 * Post meta:
 *   pearblog_translation_{lang}   – post ID of translated post in that language.
 *
 * REST endpoints:
 *   GET  /pearblog/v1/hreflang/{post_id}          – hreflang map for a post.
 *   POST /pearblog/v1/hreflang/{post_id}/translate – trigger translation to target lang.
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Manages hreflang annotations and per-language sitemaps.
 */
class HreflangManager {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Post meta prefix for translation IDs. */
	private const META_TRANSLATION = 'pearblog_translation_';

	/** Post meta key for this post's own language code. */
	private const META_LANG = 'pearblog_post_language';

	/** DeepL API endpoint (free tier). */
	private const DEEPL_URL = 'https://api-free.deepl.com/v2/translate';

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register all hooks and REST routes.
	 */
	public function register(): void {
		add_action( 'wp_head', [ $this, 'output_hreflang_tags' ], 5 );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'init', [ $this, 'register_sitemap_rewrite' ] );
		add_action( 'template_redirect', [ $this, 'maybe_serve_sitemap' ] );

		if ( (bool) get_option( 'pearblog_hreflang_auto_trans', false ) ) {
			add_action( 'publish_post', [ $this, 'auto_translate_on_publish' ], 30, 1 );
		}
	}

	// -----------------------------------------------------------------------
	// Hreflang output
	// -----------------------------------------------------------------------

	/**
	 * Output hreflang <link rel="alternate"> tags in <head>.
	 */
	public function output_hreflang_tags(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post_id = get_the_ID();
		$map     = $this->build_hreflang_map( $post_id );

		if ( empty( $map ) ) {
			return;
		}

		foreach ( $map as $lang => $url ) {
			printf(
				'<link rel="alternate" hreflang="%s" href="%s">' . "\n",
				esc_attr( $lang ),
				esc_url( $url )
			);
		}

		// x-default points to the default language version.
		$default_lang = get_option( 'pearblog_hreflang_default', 'en' );
		if ( isset( $map[ $default_lang ] ) ) {
			printf(
				'<link rel="alternate" hreflang="x-default" href="%s">' . "\n",
				esc_url( $map[ $default_lang ] )
			);
		}
	}

	/**
	 * Build a language → URL map for a post.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return array<string, string>  Language code → permalink.
	 */
	public function build_hreflang_map( int $post_id ): array {
		$languages = (array) get_option( 'pearblog_hreflang_languages', [] );
		if ( empty( $languages ) ) {
			return [];
		}

		$map  = [];
		$post = get_post( $post_id );
		if ( ! $post ) {
			return $map;
		}

		// Language of this post.
		$this_lang = get_post_meta( $post_id, self::META_LANG, true ) ?: get_option( 'pearblog_hreflang_default', 'en' );
		$map[ $this_lang ] = get_permalink( $post_id ) ?: '';

		// Translations.
		foreach ( $languages as $lang ) {
			if ( $lang === $this_lang ) {
				continue;
			}
			$trans_id = (int) get_post_meta( $post_id, self::META_TRANSLATION . $lang, true );
			if ( $trans_id > 0 ) {
				$map[ $lang ] = get_permalink( $trans_id ) ?: '';
			}
		}

		return array_filter( $map );
	}

	// -----------------------------------------------------------------------
	// Per-language XML sitemap
	// -----------------------------------------------------------------------

	/**
	 * Register rewrite rule for /pearblog-sitemap-{lang}.xml.
	 */
	public function register_sitemap_rewrite(): void {
		add_rewrite_rule(
			'^pearblog-sitemap-([a-z]{2,5})\.xml$',
			'index.php?pearblog_hreflang_sitemap=$matches[1]',
			'top'
		);
		add_filter( 'query_vars', static function ( array $vars ): array {
			$vars[] = 'pearblog_hreflang_sitemap';
			return $vars;
		} );
	}

	/**
	 * Serve the XML sitemap when the rewrite rule matches.
	 */
	public function maybe_serve_sitemap(): void {
		$lang = get_query_var( 'pearblog_hreflang_sitemap' );
		if ( ! $lang ) {
			return;
		}

		$this->serve_sitemap( sanitize_key( $lang ) );
		exit;
	}

	/**
	 * Output a simple XML sitemap for the given language.
	 *
	 * @param string $lang Language code.
	 */
	private function serve_sitemap( string $lang ): void {
		// Collect posts in this language (either native or translated).
		$default_lang = get_option( 'pearblog_hreflang_default', 'en' );
		$urls         = [];

		if ( $lang === $default_lang ) {
			// All posts without a language meta (treated as default).
			$query = new \WP_Query( [
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'meta_query'     => [
					'relation' => 'OR',
					[
						'key'     => self::META_LANG,
						'value'   => $lang,
						'compare' => '=',
					],
					[
						'key'     => self::META_LANG,
						'compare' => 'NOT EXISTS',
					],
				],
			] );
			foreach ( $query->posts as $p ) {
				$urls[] = [
					'loc'     => get_permalink( $p ),
					'lastmod' => get_post_modified_time( 'Y-m-d', false, $p ),
				];
			}
		} else {
			// Posts that have a translation in this language.
			$query = new \WP_Query( [
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'meta_key'       => self::META_TRANSLATION . $lang,
				'meta_value'     => '',
				'meta_compare'   => '!=',
			] );
			foreach ( $query->posts as $p ) {
				$trans_id = (int) get_post_meta( $p->ID, self::META_TRANSLATION . $lang, true );
				if ( $trans_id > 0 ) {
					$urls[] = [
						'loc'     => get_permalink( $trans_id ),
						'lastmod' => get_post_modified_time( 'Y-m-d', false, $trans_id ),
					];
				}
			}
		}

		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( $urls as $entry ) {
			echo '  <url>' . "\n";
			echo '    <loc>' . esc_url( $entry['loc'] ) . '</loc>' . "\n";
			echo '    <lastmod>' . esc_html( $entry['lastmod'] ) . '</lastmod>' . "\n";
			echo '  </url>' . "\n";
		}
		echo '</urlset>' . "\n";
	}

	// -----------------------------------------------------------------------
	// REST routes
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/hreflang/(?P<id>[\d]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_map' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/hreflang/(?P<id>[\d]+)/translate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_translate' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'id'          => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
				'target_lang' => [ 'required' => true, 'type' => 'string' ],
			],
		] );
	}

	/**
	 * Permission – manage_options.
	 */
	public function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /hreflang/{id} – return hreflang map.
	 */
	public function rest_map( \WP_REST_Request $request ): \WP_REST_Response {
		$map = $this->build_hreflang_map( (int) $request->get_param( 'id' ) );
		return new \WP_REST_Response( $map, 200 );
	}

	/**
	 * POST /hreflang/{id}/translate – trigger DeepL translation.
	 */
	public function rest_translate( \WP_REST_Request $request ): \WP_REST_Response {
		$source_id   = (int) $request->get_param( 'id' );
		$target_lang = strtoupper( sanitize_key( $request->get_param( 'target_lang' ) ) );

		$result = $this->translate_post( $source_id, $target_lang );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [ 'error' => $result->get_error_message() ], 422 );
		}

		return new \WP_REST_Response( $result, 200 );
	}

	// -----------------------------------------------------------------------
	// Auto-translate on publish
	// -----------------------------------------------------------------------

	/**
	 * Auto-translate a post to all configured languages when it is first published.
	 *
	 * @param int $post_id WordPress post ID.
	 */
	public function auto_translate_on_publish( int $post_id ): void {
		if ( 'post' !== get_post_type( $post_id ) ) {
			return;
		}

		$languages    = (array) get_option( 'pearblog_hreflang_languages', [] );
		$default_lang = strtoupper( get_option( 'pearblog_hreflang_default', 'en' ) );

		foreach ( $languages as $lang ) {
			$lang_upper = strtoupper( $lang );
			if ( $lang_upper === $default_lang ) {
				continue;
			}
			// Skip if translation already exists.
			if ( get_post_meta( $post_id, self::META_TRANSLATION . strtolower( $lang ), true ) ) {
				continue;
			}
			$this->translate_post( $post_id, $lang_upper );
		}
	}

	// -----------------------------------------------------------------------
	// Translation core
	// -----------------------------------------------------------------------

	/**
	 * Translate a post to a target language via DeepL and create a new post.
	 *
	 * @param int    $source_id   Source post ID.
	 * @param string $target_lang Target language code (e.g. 'DE', 'PL').
	 * @return array|\WP_Error    Result array or WP_Error.
	 */
	public function translate_post( int $source_id, string $target_lang ): array|\WP_Error {
		$source = get_post( $source_id );
		if ( ! $source ) {
			return new \WP_Error( 'invalid_post', "Post #{$source_id} not found." );
		}

		$api_key = get_option( 'pearblog_deepl_api_key', '' );
		if ( ! $api_key ) {
			return new \WP_Error( 'no_deepl_key', 'DeepL API key not configured.' );
		}

		$lang_lower  = strtolower( $target_lang );
		$source_lang = strtoupper( get_option( 'pearblog_hreflang_default', 'en' ) );

		// Translate title and content.
		$translated_title   = $this->deepl_translate( $source->post_title, $target_lang, $source_lang, $api_key );
		$translated_content = $this->deepl_translate( $source->post_content, $target_lang, $source_lang, $api_key );

		if ( is_wp_error( $translated_title ) ) {
			return $translated_title;
		}
		if ( is_wp_error( $translated_content ) ) {
			return $translated_content;
		}

		// Check if a translated post already exists.
		$existing_id = (int) get_post_meta( $source_id, self::META_TRANSLATION . $lang_lower, true );

		if ( $existing_id > 0 && get_post( $existing_id ) ) {
			// Update existing translation.
			wp_update_post( [
				'ID'           => $existing_id,
				'post_title'   => $translated_title,
				'post_content' => $translated_content,
			] );
			$trans_id = $existing_id;
		} else {
			// Create new translated post (draft so editor can review).
			$trans_id = wp_insert_post( [
				'post_title'   => $translated_title,
				'post_content' => $translated_content,
				'post_status'  => 'draft',
				'post_type'    => 'post',
				'post_author'  => $source->post_author,
			] );

			if ( is_wp_error( $trans_id ) ) {
				return $trans_id;
			}

			update_post_meta( (int) $trans_id, self::META_LANG, $lang_lower );
			update_post_meta( $source_id, self::META_TRANSLATION . $lang_lower, $trans_id );
		}

		return [
			'source_id'   => $source_id,
			'trans_id'    => $trans_id,
			'target_lang' => $lang_lower,
			'title'       => $translated_title,
			'edit_url'    => get_edit_post_link( (int) $trans_id, 'raw' ),
		];
	}

	// -----------------------------------------------------------------------
	// DeepL helper
	// -----------------------------------------------------------------------

	/**
	 * Translate a single text string via the DeepL API.
	 *
	 * @param string $text        Text to translate.
	 * @param string $target_lang Target language code (e.g. 'DE').
	 * @param string $source_lang Source language code (e.g. 'EN').
	 * @param string $api_key     DeepL API key.
	 * @return string|\WP_Error   Translated text or WP_Error.
	 */
	private function deepl_translate( string $text, string $target_lang, string $source_lang, string $api_key ): string|\WP_Error {
		if ( '' === trim( $text ) ) {
			return $text;
		}

		$response = wp_remote_post(
			self::DEEPL_URL,
			[
				'timeout' => 30,
				'headers' => [
					'Authorization' => 'DeepL-Auth-Key ' . $api_key,
					'Content-Type'  => 'application/json',
				],
				'body' => wp_json_encode( [
					'text'        => [ $text ],
					'target_lang' => $target_lang,
					'source_lang' => $source_lang,
					'tag_handling' => 'html',
				] ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $status ) {
			return new \WP_Error( 'deepl_error', "DeepL API error {$status}: " . wp_remote_retrieve_body( $response ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['translations'][0]['text'] ?? new \WP_Error( 'deepl_empty', 'DeepL returned empty translation.' );
	}
}
