<?php
/**
 * Plugin Name: PearBlog Engine
 * Plugin URI:  https://github.com/AndyPearman89/PearBlog-Engine-
 * Description: PearBlog SaaS Engine v1 – multisite content operating system.
 *              Automatic content generation, SEO optimisation and monetisation.
 * Version:     1.0.0
 * Author:      Andy Pearman
 * License:     GPL-2.0-or-later
 * Text Domain: pearblog-engine
 *
 * @package PearBlogEngine
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PEARBLOG_ENGINE_VERSION', '6.0.0' );
define( 'PEARBLOG_ENGINE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PEARBLOG_ENGINE_URL', plugin_dir_url( __FILE__ ) );

// PSR-4 autoloader for src/ classes under the PearBlogEngine namespace.
spl_autoload_register( function ( string $class ): void {
	$prefix   = 'PearBlogEngine\\';
	$base_dir = PEARBLOG_ENGINE_DIR . 'src/';

	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative  = substr( $class, strlen( $prefix ) );
	$file      = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

// Bootstrap.
add_action( 'plugins_loaded', function (): void {
	\PearBlogEngine\Core\Plugin::get_instance()->boot();
} );
