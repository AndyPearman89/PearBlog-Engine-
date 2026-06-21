<?php
/**
 * PearBlog Engine v8 Enterprise – WordPress Configuration Snippet
 *
 * Copy this file's contents into your wp-config.php (before the line
 * "That's all, stop editing!") or require it directly:
 *
 *     require_once __DIR__ . '/wp-content/mu-plugins/pearblog-engine/config/wp-config-pearblog-v8.php';
 *
 * Every constant uses a guard (`defined()`) so values set earlier in
 * wp-config.php or by the hosting environment always win.
 *
 * @package PearBlogEngine
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return; // Loaded outside WordPress – skip silently.
}

/* ───────────────────────────────────────────────────────
 * 1. Admin Version – selects the v8 Enterprise dashboard
 * ─────────────────────────────────────────────────────── */
if ( ! defined( 'PEARBLOG_ADMIN_VERSION' ) ) {
	define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
}

/* ───────────────────────────────────────────────────────
 * 2. AI / OpenAI Integration
 * ─────────────────────────────────────────────────────── */
if ( ! defined( 'PEARBLOG_OPENAI_API_KEY' ) ) {
	// Set your OpenAI key here or via the Settings Enterprise tab.
	// define( 'PEARBLOG_OPENAI_API_KEY', 'sk-...' );
}

/* ───────────────────────────────────────────────────────
 * 3. Security – Multi-tenant Master Secret
 * ─────────────────────────────────────────────────────── */
if ( ! defined( 'PEARBLOG_MASTER_SECRET' ) ) {
	// Required for multisite/SaaS tenant isolation (HKDF key derivation).
	// Generate a unique 64-char hex string: `openssl rand -hex 32`
	// define( 'PEARBLOG_MASTER_SECRET', '' );
}

/* ───────────────────────────────────────────────────────
 * 4. Logging
 * ─────────────────────────────────────────────────────── */
if ( ! defined( 'PEARBLOG_DATABASE_LOGGING' ) ) {
	define( 'PEARBLOG_DATABASE_LOGGING', false );
}

/* ───────────────────────────────────────────────────────
 * 5. Admin Access Override (development / staging only)
 * ─────────────────────────────────────────────────────── */
if ( ! defined( 'PEARBLOG_ADMIN_FORCE_ACCESS' ) ) {
	// When true, the Enterprise dashboard requires only `read`
	// capability instead of `manage_options`. NEVER enable in production.
	define( 'PEARBLOG_ADMIN_FORCE_ACCESS', false );
}

/* ───────────────────────────────────────────────────────
 * 6. WordPress Debug (recommended for staging)
 * ─────────────────────────────────────────────────────── */
// Uncomment the lines below on staging/development environments:
// define( 'WP_DEBUG',         true );
// define( 'WP_DEBUG_LOG',     true );
// define( 'WP_DEBUG_DISPLAY', false );
