<?php
/**
 * PearBlog Engine v8 Enterprise – Sample Configuration
 *
 * This is a fully-commented example showing ALL configurable constants.
 * Copy this file to wp-config-pearblog-v8.php and fill in your values,
 * or paste the relevant `define()` lines into your main wp-config.php.
 *
 * @package PearBlogEngine
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/* =====================================================
 * REQUIRED – Enterprise v8 Admin Dashboard
 * ===================================================== */

// Activates the 15-tab Enterprise admin panel.
// Accepted values: 'v8-enterprise' (full), 'v8', 'v7', 'v6'.
define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );

/* =====================================================
 * RECOMMENDED – AI & Content Engine
 * ===================================================== */

// OpenAI API key for AI content generation, auto-replies and strategy.
// Obtain from https://platform.openai.com/account/api-keys
define( 'PEARBLOG_OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY_HERE' );

/* =====================================================
 * MULTISITE / SaaS – Tenant Isolation
 * ===================================================== */

// Master secret used for HKDF key derivation in tenant isolation.
// Generate: openssl rand -hex 32
define( 'PEARBLOG_MASTER_SECRET', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );

/* =====================================================
 * MULTISITE – WordPress Network (poradnik.pro)
 * ===================================================== */

// Enable WordPress Multisite
define( 'WP_ALLOW_MULTISITE', true );
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false );
define( 'DOMAIN_CURRENT_SITE', 'poradnik.pro' );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );

/* =====================================================
 * LOGGING & DIAGNOSTICS
 * ===================================================== */

// Store log entries in the database (wp_pearblog_events).
// Useful for the Security & Audit tab but increases DB writes.
define( 'PEARBLOG_DATABASE_LOGGING', false );

/* =====================================================
 * DEVELOPMENT / STAGING ONLY
 * ===================================================== */

// Lower the admin capability to 'read' (allows any logged-in user).
// ⚠️  NEVER enable on production!
define( 'PEARBLOG_ADMIN_FORCE_ACCESS', false );

// WordPress debug mode (staging only).
define( 'WP_DEBUG',         true );
define( 'WP_DEBUG_LOG',     true );
define( 'WP_DEBUG_DISPLAY', false );
