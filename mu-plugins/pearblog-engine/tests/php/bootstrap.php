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
		return 1;
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
	function do_action( string $hook, ...$args ): void {}
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
		return true;
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
		return 'https://example.com/post/' . (int) $post_id . '/';
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post_id = null ): string {
		$post = get_post( $post_id );
		return $post ? $post->post_title : '';
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( string $type ): string {
		return gmdate( 'Y-m-d H:i:s' );
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

// Reset global test state before each test.
$GLOBALS['_options']        = [];
$GLOBALS['_post_meta']      = [];
$GLOBALS['_transients']     = [];
$GLOBALS['_posts']          = [];
$GLOBALS['_post_list']      = [];
$GLOBALS['_actions']        = [];
$GLOBALS['_filters']        = [];
$GLOBALS['_cron_scheduled'] = [];
$GLOBALS['_mail_log']       = [];
$GLOBALS['_is_singular']    = false;
$GLOBALS['_is_admin']       = false;

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
		public int $found_posts = 0;
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
