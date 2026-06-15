<?php
/**
 * PearBlog MU loader.
 *
 * WordPress autoloads only PHP files directly in wp-content/mu-plugins.
 * This loader boots the plugin entrypoint from the pearblog-engine subdirectory.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pearblog_engine_main = __DIR__ . '/pearblog-engine/pearblog-engine.php';

if ( file_exists( $pearblog_engine_main ) ) {
	require_once $pearblog_engine_main;
}
