<?php
/**
 * Plugin Name: PT24 AI Factory Loader
 * Description: Bootstraps PT24_Scale_Data and PT24_AI_Factory on the PT24 install.
 *              Runs REST, AJAX handlers and WP-Cron batch processor.
 * Version:     2.0.0
 * Author:      PearBlog Engine
 *
 * @package PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Guard: only on PT24 install.
$_pt24af_url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : (string) ( $_SERVER['HTTP_HOST'] ?? '' );
if ( false === stripos( $_pt24af_url, 'pt24' ) ) {
	unset( $_pt24af_url );
	return;
}
unset( $_pt24af_url );

// Scale data is required by landing-cpt.php routing — load early.
if ( ! class_exists( 'PT24_Scale_Data', false ) ) {
	$_pt24af_data = __DIR__ . '/pt24-scale-data.php';
	if ( file_exists( $_pt24af_data ) ) {
		require_once $_pt24af_data;
	}
	unset( $_pt24af_data );
}

// AI Factory engine.
if ( ! class_exists( 'PT24_AI_Factory', false ) ) {
	$_pt24af_factory = __DIR__ . '/pt24-ai-factory.php';
	if ( file_exists( $_pt24af_factory ) ) {
		require_once $_pt24af_factory;
	}
	unset( $_pt24af_factory );
}

// Register hooks — deferred to `plugins_loaded` so WP is fully initialised.
add_action( 'plugins_loaded', static function() {
	if ( class_exists( 'PT24_AI_Factory', false ) ) {
		PT24_AI_Factory::register();
	}
}, 20 );
