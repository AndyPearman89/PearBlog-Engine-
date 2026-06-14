<?php
/**
 * PHPUnit bootstrap – sets up WordPress function stubs for unit testing.
 *
 * We test pure PHP logic without a full WordPress installation by stubbing
 * the handful of WordPress functions used in the classes under test.
 */

declare(strict_types=1);

// Stub WordPress global functions used in production code.
// This file is intentionally kept minimal – only stub what is actually needed.

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, $default = false ) {
		return $GLOBALS['_options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, $value ): bool {
		$GLOBALS['_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $option ): bool {
		unset( $GLOBALS['_options'][ $option ] );
		return true;
	}
}

if ( ! function_exists( 'get_blog_option' ) ) {
	function get_blog_option( int $blog_id, string $option, $default = false ) {
		return get_option( "{$blog_id}_{$option}", $default );
	}
}

if ( ! function_exists( 'update_blog_option' ) ) {
	function update_blog_option( int $blog_id, string $option, $value ): bool {
		return update_option( "{$blog_id}_{$option}", $value );
	}
}

if ( ! function_exists( 'get_current_blog_id' ) ) {
	function get_current_blog_id(): int {
		return $GLOBALS['_current_blog_id'] ?? 1;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( int $post_id, string $key, bool $single = false ) {
		$meta = $GLOBALS['_post_meta'][ $post_id ][ $key ] ?? [];
		return $single ? ( $meta[0] ?? '' ) : $meta;
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( int $post_id, string $key, $value ): bool {
		$GLOBALS['_post_meta'][ $post_id ][ $key ] = [ $value ];
		return true;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( int $post_id, string $key, $meta_value = '' ): bool {
		unset( $GLOBALS['_post_meta'][ $post_id ][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $key ) {
		return $GLOBALS['_transients'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $key, $value, int $expiration = 0 ): bool {
		$GLOBALS['_transients'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( string $key ): bool {
		unset( $GLOBALS['_transients'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( string $title ): string {
		return strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '-', $title ), '-' ) );
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( string $key = '' ): string {
		return 'Test Blog';
	}
}

if ( ! function_exists( 'get_site_url' ) ) {
	function get_site_url(): string {
		return 'https://example.com';
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( string $format, int $timestamp = 0 ): string {
		return gmdate( $format, $timestamp ?: time() );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( string $url ): string {
		return htmlspecialchars( filter_var( $url, FILTER_SANITIZE_URL ) ?: '', ENT_QUOTES );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $str ): string {
		return strip_tags( $str );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, int $options = 0 ): string {
		return (string) json_encode( $data, $options );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook, $value, ...$args ) {
		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( string $hook, ...$args ): void {
		foreach ( $GLOBALS['_actions'][ $hook ] ?? [] as $callback ) {
			$callback( ...$args );
		}
		if ( isset( $GLOBALS['_action_handlers'][ $hook ] ) ) {
			( $GLOBALS['_action_handlers'][ $hook ] )( ...$args );
		}
	}
}

// Hook system stubs.
if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$GLOBALS['_actions'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'has_action' ) ) {
	function has_action( string $hook, $callback = false ) {
		if ( $callback === false ) {
			return ! empty( $GLOBALS['_actions'][ $hook ] );
		}
		$callbacks = $GLOBALS['_actions'][ $hook ] ?? [];
		foreach ( $callbacks as $cb ) {
			if ( $cb === $callback || ( is_array( $cb ) && is_array( $callback ) && $cb[0] === $callback[0] && $cb[1] === $callback[1] ) ) {
				return 10;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$GLOBALS['_filters'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( string $hook, $callback = false ) {
		if ( $callback === false ) {
			return ! empty( $GLOBALS['_filters'][ $hook ] );
		}
		return in_array( $callback, $GLOBALS['_filters'][ $hook ] ?? [], true );
	}
}

// Cron stubs.
if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( string $hook, array $args = [] ) {
		return $GLOBALS['_cron_scheduled'][ $hook ] ?? false;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( int $timestamp, string $recurrence, string $hook, array $args = [] ): void {
		$GLOBALS['_cron_scheduled'][ $hook ] = $timestamp;
	}
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	function wp_clear_scheduled_hook( string $hook, array $args = [] ): int {
		unset( $GLOBALS['_cron_scheduled'][ $hook ] );
		return 1;
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	function wp_schedule_single_event( int $timestamp, string $hook, array $args = [] ): bool {
		// Only register if not already scheduled (mirrors WP behaviour).
		if ( ! isset( $GLOBALS['_cron_scheduled'][ $hook ] ) ) {
			$GLOBALS['_cron_scheduled'][ $hook ] = $timestamp;
		}
		return true;
	}
}

// Object Cache stubs (wp_cache_* API).
if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, string $group = '', bool $force = false, &$found = null ) {
		$store_key = "{$group}:{$key}";
		if ( array_key_exists( $store_key, $GLOBALS['_object_cache'] ?? [] ) ) {
			$found = true;
			return $GLOBALS['_object_cache'][ $store_key ];
		}
		$found = false;
		return false;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $data, string $group = '', int $expire = 0 ): bool {
		$GLOBALS['_object_cache'][ "{$group}:{$key}" ] = $data;
		return true;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( $key, string $group = '' ): bool {
		unset( $GLOBALS['_object_cache'][ "{$group}:{$key}" ] );
		return true;
	}
}

if ( ! function_exists( 'wp_cache_flush' ) ) {
	function wp_cache_flush(): bool {
		$GLOBALS['_object_cache'] = [];
		return true;
	}
}

if ( ! function_exists( 'wp_cache_flush_group' ) ) {
	function wp_cache_flush_group( string $group ): bool {
		foreach ( array_keys( $GLOBALS['_object_cache'] ?? [] ) as $store_key ) {
			if ( str_starts_with( $store_key, "{$group}:" ) ) {
				unset( $GLOBALS['_object_cache'][ $store_key ] );
			}
		}
		return true;
	}
}

if ( ! function_exists( 'wp_cache_add_global_groups' ) ) {
	function wp_cache_add_global_groups( array $groups ): void {
		$GLOBALS['_object_cache_global_groups'] = array_merge(
			$GLOBALS['_object_cache_global_groups'] ?? [],
			$groups
		);
	}
}

if ( ! function_exists( 'wp_using_ext_object_cache' ) ) {
	function wp_using_ext_object_cache( ?bool $using = null ): bool {
		if ( null !== $using ) {
			$GLOBALS['_using_ext_object_cache'] = $using;
		}
		return (bool) ( $GLOBALS['_using_ext_object_cache'] ?? false );
	}
}

// Query stubs.
if ( ! function_exists( 'is_singular' ) ) {
	function is_singular( $post_types = '' ): bool {
		return (bool) ( $GLOBALS['_is_singular'] ?? false );
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin(): bool {
		return (bool) ( $GLOBALS['_is_admin'] ?? false );
	}
}

// Translation stubs.
if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'sprintf' ) ) {
	// sprintf is a PHP built-in, no stub needed.
}

// REST / HTTP stubs.
if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( string $namespace, string $route, array $args = [], bool $override = false ): bool {
		$GLOBALS['_rest_routes'][] = [ 'namespace' => $namespace, 'route' => $route ];
		return true;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $capability ): bool {
		// Default to false so existing permission-denial tests keep passing.
		// Tests that need a privileged user can set $GLOBALS['_current_user_can'] = true.
		return (bool) ( $GLOBALS['_current_user_can'] ?? false );
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		const READABLE  = 'GET';
		const CREATABLE = 'POST';
		const EDITABLE  = 'POST, PUT, PATCH';
		const DELETABLE = 'DELETE';
		const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( string $url, array $args = [] ): array {
		return [ 'response' => [ 'code' => 200 ], 'body' => '' ];
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( string $url, array $args = [] ): array {
		return [ 'response' => [ 'code' => 200 ], 'body' => '' ];
	}
}

if ( ! function_exists( 'wp_remote_request' ) ) {
	function wp_remote_request( string $url, array $args = [] ): array {
		return $GLOBALS['_http_response'] ?? [ 'response' => [ 'code' => 200 ], 'body' => '' ];
	}
}

if ( ! function_exists( 'get_attached_file' ) ) {
	function get_attached_file( int $attachment_id, bool $unfiltered = false ) {
		return $GLOBALS['_attached_files'][ $attachment_id ] ?? false;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return $response['response']['code'] ?? 200;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return ( $thing instanceof \WP_Error );
	}
}

// Email stub.
if ( ! function_exists( 'wp_mail' ) ) {
	function wp_mail( $to, string $subject, string $message, $headers = '', $attachments = [] ): bool {
		$GLOBALS['_mail_log'][] = compact( 'to', 'subject', 'message' );
		return true;
	}
}

// Admin menu stubs.
if ( ! function_exists( 'add_menu_page' ) ) {
	function add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '', string $icon_url = '', ?int $position = null ): string {
		return 'toplevel_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'add_management_page' ) ) {
	function add_management_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '' ): string {
		return 'tools_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '' ): string {
		return $parent_slug . '_' . $menu_slug;
	}
}

// Settings stubs.
if ( ! function_exists( 'register_setting' ) ) {
	function register_setting( string $option_group, string $option_name, array $args = [] ): void {}
}

if ( ! function_exists( 'add_settings_section' ) ) {
	function add_settings_section( string $id, string $title, $callback, string $page ): void {}
}

if ( ! function_exists( 'add_settings_field' ) ) {
	function add_settings_field( string $id, string $title, $callback, string $page, string $section = 'default', array $args = [] ): void {}
}

// Script/style stubs.
if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all' ): void {}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( string $handle, string $src = '', array $deps = [], $ver = false, bool $in_footer = false ): void {}
}

if ( ! function_exists( 'wp_add_inline_style' ) ) {
	function wp_add_inline_style( string $handle, string $data ): bool {
		return true;
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( string $path = '', string $plugin = '' ): string {
		return 'https://example.com/wp-content/plugins/' . ltrim( $path, '/' );
	}
}

// Post query stubs.
if ( ! function_exists( 'get_categories' ) ) {
	function get_categories( array $args = [] ): array {
		return [];
	}
}

if ( ! function_exists( 'get_category' ) ) {
	function get_category( $category, string $output = 'OBJECT', string $filter = 'raw' ) {
		return null;
	}
}

if ( ! function_exists( 'get_the_excerpt' ) ) {
	function get_the_excerpt( $post = null ): string {
		return '';
	}
}

if ( ! function_exists( 'get_the_author_meta' ) ) {
	function get_the_author_meta( string $field, $user_id = false ): string {
		return '';
	}
}

if ( ! function_exists( 'get_avatar_url' ) ) {
	function get_avatar_url( $id_or_email, array $args = [] ): string {
		return 'https://example.com/avatar.jpg';
	}
}

if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
	function get_post_thumbnail_id( $post = null ): int {
		return 0;
	}
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	function wp_get_attachment_image_url( int $attachment_id, $size = 'thumbnail' ): string {
		return $attachment_id > 0 ? 'https://example.com/image-' . $attachment_id . '.jpg' : '';
	}
}

if ( ! function_exists( 'get_the_date' ) ) {
	function get_the_date( string $format = '', $post = null ): string {
		return gmdate( $format ?: 'Y-m-d', strtotime( '2026-01-15' ) );
	}
}

if ( ! function_exists( 'get_the_modified_date' ) ) {
	function get_the_modified_date( string $format = '', $post = null ): string {
		return gmdate( $format ?: 'Y-m-d', strtotime( '2026-01-16' ) );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( string $path = '' ): string {
		return 'https://example.com' . $path;
	}
}

if ( ! function_exists( 'get_home_url' ) ) {
	function get_home_url( ?int $blog_id = null, string $path = '', string $scheme = 'http' ): string {
		return 'https://example.com' . $path;
	}
}

if ( ! function_exists( 'get_primary_category' ) ) {
	function get_primary_category( int $post_id ) {
		return null;
	}
}

if ( ! function_exists( 'get_the_terms' ) ) {
	function get_the_terms( $post, string $taxonomy ) {
		return [];
	}
}

if ( ! function_exists( 'get_the_category' ) ) {
	function get_the_category( $post_id = false ): array {
		return [];
	}
}

if ( ! function_exists( 'term_description' ) ) {
	function term_description( int $term_id = 0, string $taxonomy = 'category' ): string {
		return '';
	}
}

if ( ! function_exists( 'get_post_meta_single' ) ) {
	// Just a convenience alias used by nothing, but keep it clean.
	function get_post_meta_single(): string { return ''; }
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( array $postarr, bool $wp_error = false, bool $fire_after_hooks = true ) {
		static $id = 1000;
		return ++$id;
	}
}

if ( ! function_exists( 'wp_update_post' ) ) {
	function wp_update_post( array $postarr, bool $wp_error = false, bool $fire_after_hooks = true ) {
		return $postarr['ID'] ?? 0;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $key ) );
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null ) {
		return $GLOBALS['_posts'][ (int) $post ] ?? null;
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( array $args = [] ): array {
		$all  = $GLOBALS['_post_list'] ?? [];
		$excl = array_map( 'intval', (array) ( $args['post__not_in'] ?? [] ) );
		if ( ! empty( $excl ) ) {
			$all = array_filter( $all, fn( $id ) => ! in_array( (int) $id, $excl, true ) );
		}
		return array_values( $all );
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post_id = null ): string {
		$id = ( $post_id instanceof WP_Post ) ? $post_id->ID : (int) $post_id;
		return 'https://example.com/post/' . $id . '/';
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post_id = null ): string {
		$post = get_post( $post_id );
		return $post ? $post->post_title : '';
	}
}

if ( ! function_exists( 'get_post_field' ) ) {
	function get_post_field( string $field, $post_id, string $context = 'raw' ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		return $post->$field ?? '';
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( string $type ): string {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return (object) [
			'ID'         => $GLOBALS['current_user']->ID ?? 0,
			'user_login' => $GLOBALS['current_user']->user_login ?? '',
			'roles'      => $GLOBALS['current_user']->roles ?? [],
		];
	}
}

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite(): bool {
		return $GLOBALS['_is_multisite'] ?? false;
	}
}

if ( ! function_exists( 'wp_get_theme' ) ) {
	function wp_get_theme() {
		return new class {
			public function get( string $header ): string {
				return 'Test Theme';
			}
		};
	}
}

if ( ! function_exists( 'gmdate' ) ) {
	function gmdate( string $format, ?int $timestamp = null ): string {
		return \gmdate( $format, $timestamp ?? time() );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ): string {
		return is_string( $str ) ? trim( strip_tags( $str ) ) : '';
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ): string {
		return is_string( $url ) ? $url : '';
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ): array {
		if ( is_object( $args ) ) {
			$parsed = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed = $args;
		} else {
			parse_str( (string) $args, $parsed );
		}
		return array_merge( $defaults, $parsed );
	}
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

// WordPress database result types
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}

// Reset global test state before each test.
$GLOBALS['_options']        = [];
$GLOBALS['_post_meta']      = [];
$GLOBALS['_transients']     = [];
$GLOBALS['_posts']          = [];
$GLOBALS['_post_list']      = [];
$GLOBALS['_actions']        = [];
$GLOBALS['_action_handlers'] = [];
$GLOBALS['_filters']        = [];
$GLOBALS['_cron_scheduled'] = [];
$GLOBALS['_mail_log']       = [];
$GLOBALS['_is_singular']    = false;
$GLOBALS['_is_admin']       = false;
$GLOBALS['_db_inserts']     = [];
$GLOBALS['_db_queries']     = [];
$GLOBALS['_db_results']     = [];
$GLOBALS['_db_affected_rows'] = 0;
$GLOBALS['_db_level_counts'] = [];
$GLOBALS['_db_channel_counts'] = [];
$GLOBALS['_is_multisite']   = false;
$GLOBALS['_current_blog_id'] = 1;
$GLOBALS['_rest_routes']    = [];
$GLOBALS['_rewrite_rules']  = [];

// WordPress class stubs.
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public int    $ID            = 0;
		public string $post_title    = '';
		public string $post_content  = '';
		public string $post_status   = 'publish';
		public int    $post_author   = 1;
		public string $post_date     = '';
		public string $post_modified = '';
		public string $post_name     = '';
		public string $post_excerpt  = '';
		public string $post_type     = 'post';

		public function __construct( array $data = [] ) {
			foreach ( $data as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Term' ) ) {
	class WP_Term {
		public int    $term_id = 0;
		public string $name    = '';
		public string $slug    = '';
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public int   $found_posts = 0;
		public array $posts       = [];
		public function __construct( array $args = [] ) {}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private string $code;
		private string $message;
		public function __construct( string $code = '', string $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
		public function get_error_code(): string { return $this->code; }
		public function get_error_message( string $code = '' ): string { return $this->message; }
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private array $params  = [];
		private array $headers = [];
		public function get_param( string $key ) { return $this->params[ $key ] ?? null; }
		public function set_param( string $key, $value ): void { $this->params[ $key ] = $value; }
		public function get_params(): array { return $this->params; }
		public function get_header( string $name ): ?string { return $this->headers[ strtolower( $name ) ] ?? null; }
		public function set_header( string $name, string $value ): void { $this->headers[ strtolower( $name ) ] = $value; }
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		public $data;
		public int   $status;
		public array $headers = [];
		public function __construct( $data = null, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}
		public function header( string $name, string $value, bool $replace = true ): void {
			// $replace matches the real WP_REST_Response::header() signature; always replace in the stub.
			$this->headers[ $name ] = $value;
		}
		public function get_headers(): array { return $this->headers; }
		public function get_data() { return $this->data; }
		public function get_status(): int { return $this->status; }
	}
}

// Mock wpdb class for database testing
if ( ! isset( $GLOBALS['wpdb'] ) ) {
	$GLOBALS['wpdb'] = new class {
		public string $prefix = 'wp_';
		public int $insert_id = 1;
		public string $last_error = '';

		public function get_charset_collate(): string {
			return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		}

		public function insert( string $table, array $data, array $format = [] ) {
			$GLOBALS['_db_inserts'][] = array_merge( [ '_table' => $table ], $data );
			return 1;
		}

		public function prepare( string $query, ...$args ) {
			// Handle array arguments (for bulk operations)
			if ( count( $args ) === 1 && is_array( $args[0] ) ) {
				$args = $args[0];
			}

			// Simple prepare that handles %s and %d placeholders
			$query = str_replace( '%s', "'%s'", $query );
			$query = str_replace( '%d', '%d', $query );

			return vsprintf( $query, $args );
		}

		public function query( string $query ) {
			$GLOBALS['_db_queries'][] = $query;

			// Track INSERT queries in _db_inserts for testing
			if ( stripos( $query, 'INSERT INTO' ) === 0 ) {
				$GLOBALS['_db_inserts'][] = [ '_query' => $query ];
			}

			return $GLOBALS['_db_affected_rows'] ?? 1;
		}

		public function get_results( string $query, string $output = OBJECT ) {
			// Support multiple sequential calls by checking query content
			if ( stripos( $query, 'GROUP BY level' ) !== false && ! empty( $GLOBALS['_db_level_counts'] ) ) {
				return $GLOBALS['_db_level_counts'];
			}
			if ( stripos( $query, 'GROUP BY channel' ) !== false && ! empty( $GLOBALS['_db_channel_counts'] ) ) {
				return $GLOBALS['_db_channel_counts'];
			}
			return $GLOBALS['_db_results'] ?? [];
		}

		public function get_var( string $query ) {
			if ( ! empty( $GLOBALS['_db_results'] ) ) {
				$first = $GLOBALS['_db_results'][0];
				return is_array( $first ) ? reset( $first ) : $first;
			}
			if ( ! empty( $GLOBALS['_db_level_counts'] ) ) {
				return $GLOBALS['_db_level_counts'];
			}
			if ( ! empty( $GLOBALS['_db_channel_counts'] ) ) {
				return $GLOBALS['_db_channel_counts'];
			}
			return null;
		}

		public function get_row( string $query, string $output = OBJECT ) {
			$row = $GLOBALS['_db_results'][0] ?? null;
			if ( null === $row ) {
				return null;
			}
			return $row;
		}

		public function esc_like( string $text ): string {
			return addcslashes( $text, '_%\\' );
		}
	};
}

// Define ABSPATH if not already defined
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

// dbDelta function stub
if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $queries ) {
		$GLOBALS['_db_queries'][] = $queries;
		return [ 'Created table wp_pearblog_logs' ];
	}
}

// ---------------------------------------------------------------------------
// v9.0 additional stubs
// ---------------------------------------------------------------------------

if ( ! function_exists( 'url_to_postid' ) ) {
	function url_to_postid( string $url ): int {
		return 0; // no resolution in unit tests
	}
}

if ( ! function_exists( 'remove_all_filters' ) ) {
	function remove_all_filters( string $hook, $priority = false ): bool {
		if ( isset( $GLOBALS['_filters'][ $hook ] ) ) {
			unset( $GLOBALS['_filters'][ $hook ] );
		}
		return true;
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( string $str ): string {
		return sanitize_text_field( $str );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int {
		return (int) ( $GLOBALS['_current_user_id'] ?? 0 );
	}
}

// ---------------------------------------------------------------------------
// v9.0 session 6 additional stubs
// ---------------------------------------------------------------------------

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( string $email ): string {
		return filter_var( $email, FILTER_SANITIZE_EMAIL ) ?: '';
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( string $path = '' ): string {
		return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'get_admin_url' ) ) {
	function get_admin_url( int $blog_id = 0, string $path = '' ): string {
		return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'get_user_by' ) ) {
	function get_user_by( string $field, $value ): object|false {
		$users = $GLOBALS['_wp_users'] ?? [];
		foreach ( $users as $user ) {
			if ( isset( $user->$field ) && $user->$field === $value ) {
				return $user;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'get_network' ) ) {
	function get_network(): ?object {
		return $GLOBALS['_wp_network'] ?? null;
	}
}

if ( ! function_exists( 'get_current_network_id' ) ) {
	function get_current_network_id(): int {
		return (int) ( $GLOBALS['_current_network_id'] ?? 1 );
	}
}

if ( ! function_exists( 'wpmu_create_blog' ) ) {
	function wpmu_create_blog( string $domain, string $path, string $title, int $user_id, array $options = [], int $network_id = 1 ): int|\WP_Error {
		$result = $GLOBALS['_wpmu_create_blog_result'] ?? null;
		if ( $result instanceof \WP_Error ) {
			return $result;
		}
		$blog_id = (int) ( $GLOBALS['_wpmu_next_blog_id'] ?? 2 );
		$GLOBALS['_wpmu_created_sites'][] = compact( 'domain', 'path', 'title', 'user_id', 'network_id' );
		return $blog_id;
	}
}

if ( ! function_exists( 'hash_equals' ) ) {
	// PHP built-in; stub only when missing (very old PHP).
	function hash_equals( string $known_string, string $user_string ): bool {
		return $known_string === $user_string;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( string $action = '-1' ): string {
		return 'test_nonce_' . md5( $action );
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( string $path = '', string $plugin = '' ): string {
		return 'https://example.com/wp-content/plugins/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( string $handle, string $src = '', array $deps = [], $ver = false, bool $in_footer = false ): void {}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( string $handle, string $name, array $data ): bool {
		return true;
	}
}

if ( ! function_exists( 'is_ssl' ) ) {
	function is_ssl(): bool {
		return false;
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular( $post_types = '' ): bool {
		return (bool) ( $GLOBALS['_is_singular'] ?? false );
	}
}

if ( ! function_exists( 'get_the_ID' ) ) {
	function get_the_ID(): int|false {
		return $GLOBALS['_current_post_id'] ?? false;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = 0 ): string|false {
		return $GLOBALS['_post_permalink'] ?? 'https://example.com/post/';
	}
}

if ( ! function_exists( 'get_post_field' ) ) {
	function get_post_field( string $field, $post_id = 0 ): string {
		return (string) ( $GLOBALS['_post_fields'][ $post_id ][ $field ] ?? '' );
	}
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
	define( 'MONTH_IN_SECONDS', 2592000 );
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( string $type ): string {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

// ---------------------------------------------------------------------------
// v9.0 session 9 additional stubs
// ---------------------------------------------------------------------------

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( $post = null ) {
		if ( $post instanceof WP_Post ) {
			return $post->post_type;
		}
		return $GLOBALS['_post_type'] ?? 'post';
	}
}

if ( ! function_exists( 'add_rewrite_rule' ) ) {
	function add_rewrite_rule( string $regex, string $redirect, string $after = 'bottom' ): void {
		$GLOBALS['_rewrite_rules'][] = [ $regex, $redirect, $after ];
	}
}

if ( ! function_exists( 'wp_count_posts' ) ) {
	function wp_count_posts( string $type = 'post' ) {
		$counts = $GLOBALS['_post_counts'][ $type ] ?? new \stdClass();
		if ( ! isset( $counts->publish ) ) {
			$counts->publish = 0;
		}
		return $counts;
	}
}

if ( ! function_exists( 'wp_hash' ) ) {
	function wp_hash( string $data, string $scheme = 'auth' ): string {
		return hash( 'sha256', $data . $scheme );
	}
}

if ( ! function_exists( 'get_post_time' ) ) {
	function get_post_time( string $format = 'U', bool $gmt = false, $post = null ) {
		return gmdate( $format, strtotime( '2026-01-15 12:00:00' ) );
	}
}

if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( string $text, int $num_words = 55, string $more = null ): string {
		$words = explode( ' ', $text );
		if ( count( $words ) <= $num_words ) {
			return $text;
		}
		return implode( ' ', array_slice( $words, 0, $num_words ) ) . ( $more ?? ' [&hellip;]' );
	}
}

if ( ! function_exists( 'get_the_tags' ) ) {
	function get_the_tags( int $post_id = 0 ) {
		return $GLOBALS['_post_tags'][ $post_id ] ?? [];
	}
}

// PSR-4 autoloader for src/ classes.
spl_autoload_register( function ( string $class ): void {
	$prefix   = 'PearBlogEngine\\';
	$base_dir = __DIR__ . '/../../src/';

	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );
