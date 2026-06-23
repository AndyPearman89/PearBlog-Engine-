<?php
/**
 * Plugin Name: PT24 Google Places Loader
 * Description: Bootstraps PT24_Places_Seeder on the PT24 install.
 * Version:     1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$_url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : (string) ( $_SERVER['HTTP_HOST'] ?? '' );
if ( false === stripos( $_url, 'pt24' ) ) { unset( $_url ); return; }
unset( $_url );

if ( ! class_exists( 'PT24_Places_Seeder', false ) ) {
	$_f = __DIR__ . '/pt24-places-seeder.php';
	if ( file_exists( $_f ) ) require_once $_f;
	unset( $_f );
}

add_action( 'plugins_loaded', static function() {
	if ( class_exists( 'PT24_Places_Seeder', false ) ) {
		PT24_Places_Seeder::register();
	}
}, 25 );
