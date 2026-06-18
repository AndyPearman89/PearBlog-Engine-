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
		$GLOBALS['_did_action'][ $hook ] = ( $GLOBALS['_did_action'][ $hook ] ?? 0 ) + 1;
		foreach ( $GLOBALS['_actions'][ $hook ] ?? [] as $callback ) {
			$callback( ...$args );
		}
		// Single-handler spy used by several tests.
		if ( isset( $GLOBALS['_action_handlers'][ $hook ] ) && is_callable( $GLOBALS['_action_handlers'][ $hook ] ) ) {
			$GLOBALS['_action_handlers'][ $hook ]( ...$args );
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

if ( ! function_exists( 'get_the_ID' ) ) {
	function get_the_ID(): int {
		return (int) ( $GLOBALS['_current_post_id'] ?? 0 );
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
		$GLOBALS['_rest_routes'][] = [
			'namespace' => $namespace,
			'route'     => $route,
			'args'      => $args,
		];
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

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ): string {
		if ( is_array( $response ) && isset( $response['body'] ) ) {
			return (string) $response['body'];
		}
		return $GLOBALS['_remote_body'] ?? '';
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( string $email ): string {
		return filter_var( $email, FILTER_SANITIZE_EMAIL ) ?: '';
	}
}

if ( ! function_exists( 'register_post_type' ) ) {
	function register_post_type( string $post_type, array $args = [] ): void {
		$GLOBALS['_registered_post_types'][ $post_type ] = $args;
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	function add_shortcode( string $tag, $callback ): void {
		$GLOBALS['_shortcodes'][ $tag ] = $callback;
	}
}

if ( ! function_exists( 'get_the_post_thumbnail_url' ) ) {
	function get_the_post_thumbnail_url( $post_id = null, $size = 'post-thumbnail' ): string {
		$id = is_object( $post_id ) ? ( $post_id->ID ?? 0 ) : (int) $post_id;
		return $GLOBALS['_thumbnail_urls'][ $id ] ?? '';
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( string $data ): string {
		return $data;
	}
}

if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box( string $id, string $title, $callback, $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null ): void {
		$GLOBALS['_meta_boxes'][] = compact( 'id', 'title', 'context' );
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( string $action = '', string $name = '_wpnonce', bool $referer = true, bool $echo = true ): string {
		return '';
	}
}

if ( ! function_exists( 'check_admin_referer' ) ) {
	function check_admin_referer( string $action = '-1', string $query_arg = '_wpnonce' ): bool {
		return true;
	}
}

if ( ! function_exists( 'wp_get_post_categories' ) ) {
	function wp_get_post_categories( int $post_id, array $args = [] ): array {
		return $GLOBALS['_post_categories'][ $post_id ] ?? [];
	}
}

if ( ! function_exists( 'get_query_var' ) ) {
	function get_query_var( string $var, $default = '' ) {
		return $GLOBALS['_query_vars'][ $var ] ?? $default;
	}
}

if ( ! function_exists( 'get_queried_object' ) ) {
	function get_queried_object() {
		return $GLOBALS['_queried_object'] ?? null;
	}
}

if ( ! function_exists( 'status_header' ) ) {
	function status_header( int $code, string $description = '' ): void {}
}

if ( ! function_exists( 'nocache_headers' ) ) {
	function nocache_headers(): void {}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $key, $value = '', string $url = '' ): string {
		if ( is_array( $key ) ) {
			return $url . '?' . http_build_query( $key );
		}
		return $url . '?' . urlencode( $key ) . '=' . urlencode( (string) $value );
	}
}

if ( ! function_exists( 'get_role' ) ) {
	function get_role( string $role ) {
		$roles = $GLOBALS['_roles'] ?? [];
		return $roles[ $role ] ?? null;
	}
}

if ( ! class_exists( 'WP_Role' ) ) {
	class WP_Role {
		public string $name;
		public array  $capabilities;
		public function __construct( string $role, array $capabilities = [] ) {
			$this->name         = $role;
			$this->capabilities = $capabilities;
		}
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
		$id = is_object( $post_id ) ? ( $post_id->ID ?? 0 ) : (int) $post_id;
		return 'https://example.com/post/' . (int) $id . '/';
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
$GLOBALS['_filters']        = [];
$GLOBALS['_cron_scheduled'] = [];
$GLOBALS['_mail_log']       = [];
$GLOBALS['_is_singular']    = false;
$GLOBALS['_rewrite_rules']  = [];
$GLOBALS['_rest_routes']    = [];
$GLOBALS['_action_handlers'] = [];
$GLOBALS['_did_action']     = [];
$GLOBALS['_is_admin']       = false;
$GLOBALS['_db_inserts']     = [];
$GLOBALS['_db_queries']     = [];
$GLOBALS['_db_results']     = [];
$GLOBALS['_db_affected_rows'] = 0;
$GLOBALS['_db_level_counts'] = [];
$GLOBALS['_db_channel_counts'] = [];
$GLOBALS['_is_multisite']   = false;
$GLOBALS['_current_blog_id'] = 1;

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

if ( ! function_exists( 'rest_ensure_response' ) ) {
	function rest_ensure_response( $response ) {
		if ( $response instanceof \WP_REST_Response ) {
			return $response;
		}
		return new \WP_REST_Response( $response, 200 );
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

		public function esc_like( string $text ): string {
			return addcslashes( $text, '_%\\' );
		}
	};
}

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( $post = null ) {
		$id  = is_object( $post ) ? ( $post->ID ?? 0 ) : (int) $post;
		$obj = $GLOBALS['_posts'][ $id ] ?? null;
		if ( $obj && isset( $obj->post_type ) ) {
			return $obj->post_type;
		}
		return $GLOBALS['_post_type'] ?? 'post';
	}
}

if ( ! function_exists( 'wp_count_posts' ) ) {
	function wp_count_posts( string $type = 'post', string $perm = '' ) {
		$publish = $GLOBALS['_published_post_count']
			?? count( $GLOBALS['_post_list'] ?? [] );
		return (object) [ 'publish' => (int) $publish, 'draft' => 0, 'pending' => 0 ];
	}
}

if ( ! function_exists( 'add_rewrite_rule' ) ) {
	function add_rewrite_rule( string $regex, string $query, string $after = 'bottom' ): void {
		$GLOBALS['_rewrite_rules'][] = [ $regex, $query, $after ];
	}
}

if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( string $text, int $num_words = 55, ?string $more = null ): string {
		$more = $more ?? '&hellip;';
		$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );
		$words = $text === '' ? [] : explode( ' ', $text );
		if ( count( $words ) <= $num_words ) {
			return $text;
		}
		return implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $text, bool $remove_breaks = false ): string {
		$text = strip_tags( $text );
		return trim( $text );
	}
}

if ( ! function_exists( 'wp_hash' ) ) {
	function wp_hash( string $data, string $scheme = 'auth' ): string {
		return hash_hmac( 'sha256', $data, $scheme . '_salt_key_here' );
	}
}

if ( ! function_exists( 'get_the_tags' ) ) {
	function get_the_tags( $post_id = 0 ) {
		return $GLOBALS['_post_tags'][ (int) $post_id ] ?? false;
	}
}

if ( ! function_exists( 'get_post_time' ) ) {
	function get_post_time( string $format = 'U', bool $gmt = false, $post = null ) {
		$ts = is_object( $post ) && isset( $post->post_date_gmt )
			? strtotime( (string) $post->post_date_gmt )
			: time();
		return gmdate( $format, $ts ?: time() );
	}
}

// Define ABSPATH if not already defined
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

// Provide a no-op wp-admin/includes/upgrade.php so production code that does
// `require_once ABSPATH . 'wp-admin/includes/upgrade.php'` before dbDelta()
// does not fatal in the unit-test environment.
$pearblog_upgrade_stub = ABSPATH . 'wp-admin/includes/upgrade.php';
if ( ! file_exists( $pearblog_upgrade_stub ) ) {
	@mkdir( dirname( $pearblog_upgrade_stub ), 0777, true );
	@file_put_contents( $pearblog_upgrade_stub, "<?php\n// Test stub for dbDelta(); real implementation provided in bootstrap.\n" );
}

// dbDelta function stub
if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $queries ) {
		$GLOBALS['_db_queries'][] = $queries;
		return [ 'Created table wp_pearblog_logs' ];
	}
}

// V9.0 stubs.
if ( ! function_exists( 'get_post_modified_time' ) ) {
	function get_post_modified_time( string $format = 'U', bool $gmt = false, $post = null ) {
		$id      = is_numeric( $post ) ? (int) $post : 0;
		$stored  = $GLOBALS['_post_meta'][ $id ]['_pearblog_modified_time'][0] ?? null;
		$ts      = $stored ?? ( time() - 200 * DAY_IN_SECONDS );
		if ( 'U' === $format ) {
			return (int) $ts;
		}
		return gmdate( $format, (int) $ts );
	}
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4(): string {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff )
		);
	}
}

if ( ! function_exists( 'wp_publish_post' ) ) {
	function wp_publish_post( int $post_id ): int {
		if ( isset( $GLOBALS['_posts'][ $post_id ] ) ) {
			$GLOBALS['_posts'][ $post_id ]['post_status'] = 'publish';
		}
		return $post_id;
	}
}

if ( ! function_exists( 'wp_trash_post' ) ) {
	function wp_trash_post( int $post_id ): void {
		if ( isset( $GLOBALS['_posts'][ $post_id ] ) ) {
			$GLOBALS['_posts'][ $post_id ]['post_status'] = 'trash';
		}
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( string $value ): string {
		return rtrim( $value, '/' ) . '/';
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in(): bool {
		return $GLOBALS['_user_logged_in'] ?? true;
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int {
		return $GLOBALS['_current_user_id'] ?? 1;
	}
}

if ( ! function_exists( 'get_userdata' ) ) {
	function get_userdata( int $user_id ) {
		$users = $GLOBALS['_users'] ?? [];
		if ( ! isset( $users[ $user_id ] ) ) {
			return false;
		}
		return (object) $users[ $user_id ];
	}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
	function shortcode_atts( array $defaults, array $atts, string $shortcode = '' ): array {
		$out = [];
		foreach ( $defaults as $key => $default ) {
			$out[ $key ] = array_key_exists( $key, $atts ) ? $atts[ $key ] : $default;
		}
		return $out;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( string $action = '' ): string {
		return 'test_nonce_' . md5( $action );
	}
}

if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, bool $echo = true ): string {
		$result = $selected == $current ? ' selected="selected"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, bool $echo = true ): string {
		$result = $checked == $current ? ' checked="checked"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

// PSR-4 autoloader for src/ classes.
spl_autoload_register( function ( string $class ): void {
	$base_dir = __DIR__ . '/../../src/';

	foreach ( [ 'PearBlogEngine\\', 'PearBlog\\' ] as $prefix ) {
		if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
			continue;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
		return;
	}
} );
