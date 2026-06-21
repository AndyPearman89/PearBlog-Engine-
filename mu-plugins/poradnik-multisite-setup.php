<?php
/**
 * Poradnik.PRO Multisite Setup
 *
 * Enforces single theme (pearblog-theme) across the network and
 * defines the subpage structure for the poradnik.pro site.
 *
 * @package PearBlogEngine
 * @since   9.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ─────────────────────────────────────────────────────────
 * 1. Enforce single theme: pearblog-theme
 * ─────────────────────────────────────────────────────────
 *
 * Only pearblog-theme is allowed across the entire network.
 * All other themes are hidden from the admin panel.
 */
add_filter( 'allowed_themes', 'poradnik_enforce_single_theme' );
add_filter( 'site_allowed_themes', 'poradnik_enforce_single_theme' );
add_filter( 'network_allowed_themes', 'poradnik_enforce_single_theme' );

function poradnik_enforce_single_theme( $themes ) {
	return array(
		'pearblog-theme' => true,
	);
}

// Force-switch any site that doesn't use pearblog-theme.
add_action( 'setup_theme', function () {
	if ( is_multisite() && get_option( 'stylesheet' ) !== 'pearblog-theme' ) {
		update_option( 'stylesheet', 'pearblog-theme' );
		update_option( 'template', 'pearblog-theme' );
	}
} );

/**
 * ─────────────────────────────────────────────────────────
 * 2. Poradnik.PRO subpage structure
 * ─────────────────────────────────────────────────────────
 *
 * Defines the canonical page slugs for the poradnik.pro site.
 * Pages are auto-created on theme activation if they don't exist.
 */
function poradnik_pro_get_subpages() {
	return array(
		// Main sections
		'home'          => array(
			'title'    => 'Strona Główna',
			'template' => 'page-poradnik-pro-home.php',
		),
		'poradniki'     => array(
			'title'    => 'Poradniki',
			'template' => 'page-poradnik-pro-poradniki.php',
		),
		'kalkulatory'   => array(
			'title'    => 'Kalkulatory',
			'template' => 'page-poradnik-pro-kalkulatory.php',
		),
		'rankingi'      => array(
			'title'    => 'Rankingi',
			'template' => 'page-poradnik-pro-rankingi.php',
		),
		'porownania'    => array(
			'title'    => 'Porównania',
			'template' => 'page-poradnik-pro-porownania.php',
		),
		'faq'           => array(
			'title'    => 'FAQ',
			'template' => 'page-poradnik-pro-faq.php',
		),
		'eksperci'      => array(
			'title'    => 'Eksperci',
			'template' => 'page-poradnik-pro-eksperci.php',
		),
		'blog'          => array(
			'title'    => 'Blog',
			'template' => 'page-poradnik-pro-blog.php',
		),
		'kontakt'       => array(
			'title'    => 'Kontakt',
			'template' => 'page-poradnik-pro-kontakt.php',
		),
		'cennik'        => array(
			'title'    => 'Cennik',
			'template' => 'page-poradnik-pro-cennik.php',
		),
		'dla-specjalistow' => array(
			'title'    => 'Dla Specjalistów',
			'template' => 'page-poradnik-pro-dla-specjalistow.php',
		),
		'ai-doradca'    => array(
			'title'    => 'AI Doradca',
			'template' => 'page-poradnik-pro-ai-doradca.php',
		),
		'dashboard'     => array(
			'title'    => 'Dashboard',
			'template' => 'page-poradnik-pro-dashboard.php',
		),

		// Dynamic/single subpages (parent pages for clean URLs)
		'poradnik'      => array(
			'title'    => 'Poradnik',
			'template' => 'page-poradnik-pro-poradnik.php',
		),
		'kategoria'     => array(
			'title'    => 'Kategoria',
			'template' => 'page-poradnik-pro-kategoria.php',
		),
		'miasto'        => array(
			'title'    => 'Miasto',
			'template' => 'page-poradnik-pro-miasto.php',
		),
		'ranking'       => array(
			'title'    => 'Ranking',
			'template' => 'page-poradnik-pro-ranking-single.php',
		),
		'porownanie'    => array(
			'title'    => 'Porównanie',
			'template' => 'page-poradnik-pro-porownanie.php',
		),
		'kalkulator'    => array(
			'title'    => 'Kalkulator',
			'template' => 'page-poradnik-pro-kalkulator.php',
		),
		'pytania'       => array(
			'title'    => 'Pytania',
			'template' => 'page-poradnik-pro-pytania.php',
		),
		'specjalisci'   => array(
			'title'    => 'Specjaliści',
			'template' => 'page-poradnik-pro-specjalisci.php',
		),
	);
}

/**
 * Auto-create poradnik.pro subpages on theme activation.
 */
add_action( 'after_switch_theme', 'poradnik_pro_create_subpages' );

function poradnik_pro_create_subpages() {
	if ( ! is_multisite() ) {
		return;
	}

	$subpages = poradnik_pro_get_subpages();

	foreach ( $subpages as $slug => $config ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			// Ensure correct template is set.
			update_post_meta( $existing->ID, '_wp_page_template', $config['template'] );
			continue;
		}

		$page_id = wp_insert_post( array(
			'post_title'   => $config['title'],
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		) );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_wp_page_template', $config['template'] );
		}
	}

	// Set homepage to 'home' page.
	$home_page = get_page_by_path( 'home' );
	if ( $home_page ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_page->ID );
	}
}

/**
 * ─────────────────────────────────────────────────────────
 * 3. Remove non-poradnik themes from network
 * ─────────────────────────────────────────────────────────
 *
 * Hide theme install/search UI to prevent accidental installs.
 */
add_action( 'admin_menu', function () {
	if ( is_multisite() ) {
		remove_submenu_page( 'themes.php', 'theme-install.php' );
	}
} );

add_action( 'network_admin_menu', function () {
	remove_submenu_page( 'themes.php', 'theme-install.php' );
} );
