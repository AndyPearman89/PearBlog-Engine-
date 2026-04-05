<?php
/**
 * PHPUnit bootstrap for PearBlog Engine unit tests.
 *
 * Provides WordPress function stubs so tests can run without loading
 * the full WordPress environment.
 *
 * @package PearBlogEngine\Tests
 */

declare(strict_types=1);

// Define WordPress constants used by the plugin.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wp/' );
}

if ( ! defined( 'PEARBLOG_ENGINE_DIR' ) ) {
	define( 'PEARBLOG_ENGINE_DIR', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'PEARBLOG_ENGINE_URL' ) ) {
	define( 'PEARBLOG_ENGINE_URL', 'https://example.com/wp-content/mu-plugins/pearblog-engine/' );
}

if ( ! defined( 'PEARBLOG_ENGINE_VERSION' ) ) {
	define( 'PEARBLOG_ENGINE_VERSION', '6.0.0' );
}

// ──── WordPress function stubs ────────────────────────────────────────────

/** @var array<string, mixed> In-memory option store for testing. */
$GLOBALS['_wp_test_options'] = [];

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $key, $default = false ) {
		return $GLOBALS['_wp_test_options'][ $key ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $key, $value, $autoload = null ): bool {
		$GLOBALS['_wp_test_options'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $key ): bool {
		unset( $GLOBALS['_wp_test_options'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_blog_option' ) ) {
	function get_blog_option( int $blog_id, string $key, $default = false ) {
		$store_key = "blog_{$blog_id}_{$key}";
		return $GLOBALS['_wp_test_options'][ $store_key ] ?? $default;
	}
}

if ( ! function_exists( 'update_blog_option' ) ) {
	function update_blog_option( int $blog_id, string $key, $value ): bool {
		$GLOBALS['_wp_test_options'][ "blog_{$blog_id}_{$key}" ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $text ): string {
		return strip_tags( $text );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( string $url ): string {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( int $post_id, string $key, $value ): bool {
		$GLOBALS['_wp_test_postmeta'][ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( int $post_id, string $key = '', bool $single = false ) {
		if ( '' === $key ) {
			return $GLOBALS['_wp_test_postmeta'][ $post_id ] ?? [];
		}
		$value = $GLOBALS['_wp_test_postmeta'][ $post_id ][ $key ] ?? null;
		return $single ? $value : ( null !== $value ? [ $value ] : [] );
	}
}

if ( ! function_exists( 'has_post_thumbnail' ) ) {
	function has_post_thumbnail( int $post_id ): bool {
		return ! empty( $GLOBALS['_wp_test_postmeta'][ $post_id ]['_thumbnail_id'] );
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = 0 ): string {
		$id = is_object( $post ) ? $post->ID : (int) $post;
		return "https://example.com/?p={$id}";
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( string $hook, ...$args ): void {
		// No-op in tests.
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook, $value, ...$args ) {
		return $value;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'human_time_diff' ) ) {
	function human_time_diff( int $from, int $to = 0 ): string {
		$to   = $to ?: time();
		$diff = abs( $to - $from );
		return $diff . ' seconds';
	}
}

// ──── Autoloader (PSR-4) ──────────────────────────────────────────────────

spl_autoload_register( function ( string $class ): void {
	$prefix   = 'PearBlogEngine\\';
	$base_dir = PEARBLOG_ENGINE_DIR . 'src/';

	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );
