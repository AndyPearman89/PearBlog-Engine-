<?php
/**
 * Plugin Name: Poradnik.pro Branding Fix
 * Description: Forces the correct site identity (title/tagline) for poradnik.pro,
 *              overriding any leftover "PT24.PRO" values copied from another install.
 * Version:     1.0.0
 *
 * Deployed as a must-use plugin so the branding is corrected without direct
 * database access (FTP-only environment). It performs a one-time self-heal:
 * the correct values are written to the database once, after which the admin
 * can freely edit them again.
 *
 * @package PearBlogEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical branding for poradnik.pro.
 */
if ( ! defined( 'PORADNIK_SITE_NAME' ) ) {
	define( 'PORADNIK_SITE_NAME', 'Poradnik.pro' );
}
if ( ! defined( 'PORADNIK_SITE_TAGLINE' ) ) {
	define( 'PORADNIK_SITE_TAGLINE', 'Praktyczne poradniki: remont, budowa, auto, finanse i dom' );
}

/**
 * One-time self-heal: persist the correct title/tagline to the database.
 *
 * Guarded by the `poradnik_branding_synced` flag so it runs only once and does
 * not fight future intentional edits made from the admin panel.
 */
add_action( 'init', static function () {
	if ( get_option( 'poradnik_branding_synced' ) === '1' ) {
		return;
	}

	if ( get_option( 'blogname' ) !== PORADNIK_SITE_NAME ) {
		update_option( 'blogname', PORADNIK_SITE_NAME );
	}
	if ( get_option( 'blogdescription' ) !== PORADNIK_SITE_TAGLINE ) {
		update_option( 'blogdescription', PORADNIK_SITE_TAGLINE );
	}

	update_option( 'poradnik_branding_synced', '1' );
} );
