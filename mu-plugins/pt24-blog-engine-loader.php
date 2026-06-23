<?php
/**
 * Plugin Name: PT24 Blog Engine Loader
 * Description: Bootstraps PT24_Blog_Engine on the PT24 install only.
 * Version:     1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$_url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : (string) ( $_SERVER['HTTP_HOST'] ?? '' );
if ( false === stripos( $_url, 'pt24' ) ) { unset( $_url ); return; }
unset( $_url );

if ( ! class_exists( 'PT24_Blog_Engine', false ) ) {
	$_f = __DIR__ . '/pt24-blog-engine.php';
	if ( file_exists( $_f ) ) require_once $_f;
	unset( $_f );
}

add_action( 'plugins_loaded', static function() {
	if ( class_exists( 'PT24_Blog_Engine', false ) ) {
		PT24_Blog_Engine::register();
	}
}, 30 );
