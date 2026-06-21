<?php
/**
 * Plugin Name: PearBlog Engine
 * Plugin URI:  https://github.com/AndyPearman89/PearBlog-Engine-
 * Description: PearBlog SaaS Engine v1 – multisite content operating system.
 *              Automatic content generation, SEO optimisation and monetisation.
 * Version:     9.0.0
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

define( 'PEARBLOG_ENGINE_VERSION', '8.0.0' );
define( 'PEARBLOG_ENGINE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PEARBLOG_ENGINE_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'PEARBLOG_PLUGIN_FILE' ) ) {
	define( 'PEARBLOG_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'PEARBLOG_PLUGIN_DIR' ) ) {
	define( 'PEARBLOG_PLUGIN_DIR', PEARBLOG_ENGINE_DIR );
}

// Enable full Enterprise V8 admin dashboard
if ( ! defined( 'PEARBLOG_ADMIN_VERSION' ) ) {
	define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
}

// PSR-4 autoloader for src/ classes. Handles both the canonical
// PearBlogEngine\ namespace and the legacy PearBlog\ namespace used by some
// dormant subsystems (LeadAI, Poradnik), both rooted at src/.
spl_autoload_register( function ( string $class ): void {
	$base_dir = PEARBLOG_ENGINE_DIR . 'src/';

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

// Bootstrap.
add_action( 'plugins_loaded', function (): void {
	\PearBlogEngine\Core\Plugin::get_instance()->boot();
} );
