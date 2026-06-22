<?php
/**
 * Poradnik.PRO — Full Navigation Menu & Footer
 *
 * Provides hardcoded navigation menus and footer structure
 * for Poradnik.PRO platform. Used as fallback when WP menus
 * are not assigned, and as programmatic menu content for seeding.
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the full Poradnik.PRO primary navigation (fallback).
 * Called when no WP menu is assigned to 'primary' location.
 */
function pp_render_primary_nav_fallback() {
	$home = home_url( '/' );
	?>
	<ul class="pb-menu pp-main-menu">
		<li class="menu-item menu-item-has-children">
			<a href="<?php echo esc_url( $home . 'poradniki/' ); ?>">Poradniki</a>
			<ul class="sub-menu">
				<li><a href="<?php echo esc_url( $home . 'kategoria/prawo/' ); ?>">Prawo</a></li>
				<li><a href="<?php echo esc_url( $home . 'kategoria/finanse/' ); ?>">Finanse</a></li>
				<li><a href="<?php echo esc_url( $home . 'kategoria/nieruchomosci/' ); ?>">Nieruchomości</a></li>
				<li><a href="<?php echo esc_url( $home . 'kategoria/budownictwo/' ); ?>">Budownictwo</a></li>
				<li><a href="<?php echo esc_url( $home . 'kategoria/energia/' ); ?>">Energia</a></li>
				<li><a href="<?php echo esc_url( $home . 'kategoria/zdrowie/' ); ?>">Zdrowie</a></li>
				<li><a href="<?php echo esc_url( $home . 'kategoria/edukacja/' ); ?>">Edukacja</a></li>
				<li><a href="<?php echo esc_url( $home . 'kategoria/motoryzacja/' ); ?>">Motoryzacja</a></li>
				<li><a href="<?php echo esc_url( $home . 'kategoria/technologia/' ); ?>">Technologia</a></li>
				<li><a href="<?php echo esc_url( $home . 'kategoria/dom-i-ogrod/' ); ?>">Dom i ogród</a></li>
			</ul>
		</li>
		<li class="menu-item menu-item-has-children">
			<a href="<?php echo esc_url( $home . 'specjalisci/' ); ?>">Specjaliści</a>
			<ul class="sub-menu">
				<li><a href="<?php echo esc_url( $home . 'warszawa/specjalisci/' ); ?>">Warszawa</a></li>
				<li><a href="<?php echo esc_url( $home . 'krakow/specjalisci/' ); ?>">Kraków</a></li>
				<li><a href="<?php echo esc_url( $home . 'wroclaw/specjalisci/' ); ?>">Wrocław</a></li>
				<li><a href="<?php echo esc_url( $home . 'poznan/specjalisci/' ); ?>">Poznań</a></li>
				<li><a href="<?php echo esc_url( $home . 'gdansk/specjalisci/' ); ?>">Gdańsk</a></li>
				<li><a href="<?php echo esc_url( $home . 'katowice/specjalisci/' ); ?>">Katowice</a></li>
				<li><a href="<?php echo esc_url( $home . 'lodz/specjalisci/' ); ?>">Łódź</a></li>
				<li><a href="<?php echo esc_url( $home . 'szczecin/specjalisci/' ); ?>">Szczecin</a></li>
				<li><a href="<?php echo esc_url( $home . 'lublin/specjalisci/' ); ?>">Lublin</a></li>
				<li><a href="<?php echo esc_url( $home . 'bydgoszcz/specjalisci/' ); ?>">Bydgoszcz</a></li>
			</ul>
		</li>
		<li class="menu-item menu-item-has-children">
			<a href="<?php echo esc_url( $home . 'kalkulatory/' ); ?>">Narzędzia</a>
			<ul class="sub-menu">
				<li><a href="<?php echo esc_url( $home . 'kalkulatory/' ); ?>">Kalkulatory</a></li>
				<li><a href="<?php echo esc_url( $home . 'rankingi/' ); ?>">Rankingi</a></li>
				<li><a href="<?php echo esc_url( $home . 'porownania/' ); ?>">Porównania</a></li>
				<li><a href="<?php echo esc_url( $home . 'pytania/' ); ?>">Pytania i odpowiedzi</a></li>
			</ul>
		</li>
		<li class="menu-item">
			<a href="<?php echo esc_url( $home . 'ai-doradca/' ); ?>">AI Doradca</a>
		</li>
		<li class="menu-item">
			<a href="<?php echo esc_url( $home . 'cennik/' ); ?>">Cennik</a>
		</li>
		<li class="menu-item menu-item-cta">
			<a href="<?php echo esc_url( $home . 'dla-specjalistow/' ); ?>">Dla specjalistów</a>
		</li>
	</ul>
	<?php
}

/**
 * Render the full Poradnik.PRO footer content.
 * Multi-column layout with links, categories, cities, and info.
 */
function pp_render_full_footer() {
	$home      = home_url( '/' );
	$site_name = get_bloginfo( 'name' );
	$site_desc = get_bloginfo( 'description' );
	$year      = gmdate( 'Y' );
	?>
	<footer class="pp-footer" role="contentinfo">
		<div class="pb-container">

			<!-- Footer top: 4-column grid -->
			<div class="pp-footer-grid">

				<!-- Column 1: Brand + description -->
				<div class="pp-footer-col pp-footer-brand">
					<a href="<?php echo esc_url( $home ); ?>" class="pp-footer-logo" rel="home">
						<?php echo pearblog_get_logo(); ?>
					</a>
					<p class="pp-footer-desc">
						<?php echo $site_desc ? esc_html( $site_desc ) : 'Profesjonalne porady od ekspertów. Prawo, finanse, budownictwo, zdrowie i wiele więcej.'; ?>
					</p>
					<div class="pp-footer-social">
						<a href="https://facebook.com/PoradnikPRO" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
							<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
						</a>
						<a href="https://twitter.com/PoradnikPRO" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)">
							<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
						</a>
						<a href="https://linkedin.com/company/poradnik-pro" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
							<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
						</a>
					</div>
				</div>

				<!-- Column 2: Kategorie -->
				<div class="pp-footer-col">
					<h3 class="pp-footer-heading">Kategorie</h3>
					<ul class="pp-footer-links">
						<li><a href="<?php echo esc_url( $home . 'kategoria/prawo/' ); ?>">Prawo</a></li>
						<li><a href="<?php echo esc_url( $home . 'kategoria/finanse/' ); ?>">Finanse</a></li>
						<li><a href="<?php echo esc_url( $home . 'kategoria/nieruchomosci/' ); ?>">Nieruchomości</a></li>
						<li><a href="<?php echo esc_url( $home . 'kategoria/budownictwo/' ); ?>">Budownictwo</a></li>
						<li><a href="<?php echo esc_url( $home . 'kategoria/energia/' ); ?>">Energia i OZE</a></li>
						<li><a href="<?php echo esc_url( $home . 'kategoria/zdrowie/' ); ?>">Zdrowie</a></li>
						<li><a href="<?php echo esc_url( $home . 'kategoria/motoryzacja/' ); ?>">Motoryzacja</a></li>
						<li><a href="<?php echo esc_url( $home . 'kategoria/technologia/' ); ?>">Technologia</a></li>
						<li><a href="<?php echo esc_url( $home . 'kategoria/dom-i-ogrod/' ); ?>">Dom i ogród</a></li>
						<li><a href="<?php echo esc_url( $home . 'kategoria/edukacja/' ); ?>">Edukacja</a></li>
					</ul>
				</div>

				<!-- Column 3: Narzędzia & Usługi -->
				<div class="pp-footer-col">
					<h3 class="pp-footer-heading">Narzędzia</h3>
					<ul class="pp-footer-links">
						<li><a href="<?php echo esc_url( $home . 'kalkulatory/' ); ?>">Kalkulatory</a></li>
						<li><a href="<?php echo esc_url( $home . 'rankingi/' ); ?>">Rankingi</a></li>
						<li><a href="<?php echo esc_url( $home . 'porownania/' ); ?>">Porównania</a></li>
						<li><a href="<?php echo esc_url( $home . 'pytania/' ); ?>">Pytania i odpowiedzi</a></li>
						<li><a href="<?php echo esc_url( $home . 'ai-doradca/' ); ?>">AI Doradca</a></li>
						<li><a href="<?php echo esc_url( $home . 'specjalisci/' ); ?>">Specjaliści</a></li>
						<li><a href="<?php echo esc_url( $home . 'cennik/' ); ?>">Cennik usług</a></li>
					</ul>
				</div>

				<!-- Column 4: Informacje -->
				<div class="pp-footer-col">
					<h3 class="pp-footer-heading">Informacje</h3>
					<ul class="pp-footer-links">
						<li><a href="<?php echo esc_url( $home . 'dla-specjalistow/' ); ?>">Dla specjalistów</a></li>
						<li><a href="<?php echo esc_url( $home . 'faq/' ); ?>">FAQ</a></li>
						<li><a href="<?php echo esc_url( $home . 'kontakt/' ); ?>">Kontakt</a></li>
						<li><a href="<?php echo esc_url( $home . 'blog/' ); ?>">Blog</a></li>
						<li><a href="<?php echo esc_url( $home . 'polityka-prywatnosci/' ); ?>">Polityka prywatności</a></li>
						<li><a href="<?php echo esc_url( $home . 'regulamin/' ); ?>">Regulamin</a></li>
						<li><a href="<?php echo esc_url( $home . 'panel/' ); ?>">Moje konto</a></li>
					</ul>
				</div>

			</div>

			<!-- Footer middle: Miasta (optional SEO links) -->
			<div class="pp-footer-cities">
				<h3 class="pp-footer-heading">Specjaliści w miastach</h3>
				<div class="pp-footer-city-links">
					<a href="<?php echo esc_url( $home . 'warszawa/specjalisci/' ); ?>">Warszawa</a>
					<a href="<?php echo esc_url( $home . 'krakow/specjalisci/' ); ?>">Kraków</a>
					<a href="<?php echo esc_url( $home . 'wroclaw/specjalisci/' ); ?>">Wrocław</a>
					<a href="<?php echo esc_url( $home . 'poznan/specjalisci/' ); ?>">Poznań</a>
					<a href="<?php echo esc_url( $home . 'gdansk/specjalisci/' ); ?>">Gdańsk</a>
					<a href="<?php echo esc_url( $home . 'katowice/specjalisci/' ); ?>">Katowice</a>
					<a href="<?php echo esc_url( $home . 'lodz/specjalisci/' ); ?>">Łódź</a>
					<a href="<?php echo esc_url( $home . 'szczecin/specjalisci/' ); ?>">Szczecin</a>
					<a href="<?php echo esc_url( $home . 'lublin/specjalisci/' ); ?>">Lublin</a>
					<a href="<?php echo esc_url( $home . 'bydgoszcz/specjalisci/' ); ?>">Bydgoszcz</a>
				</div>
			</div>

			<!-- Footer bottom: copyright -->
			<div class="pp-footer-bottom">
				<p class="pp-footer-copyright">
					&copy; <?php echo esc_html( $year ); ?>
					<a href="<?php echo esc_url( $home ); ?>"><?php echo esc_html( $site_name ?: 'Poradnik.PRO' ); ?></a>
					— Wszelkie prawa zastrzeżone.
				</p>
				<p class="pp-footer-powered">
					Powered by <a href="https://pearblog.pro" target="_blank" rel="noopener">PearBlog Engine</a>
				</p>
			</div>

		</div>
	</footer>
	<?php
}

/**
 * Register the fallback callback for primary menu.
 * Hooked into the wp_nav_menu fallback_cb for 'primary' location.
 */
function pp_nav_menu_fallback_primary() {
	pp_render_primary_nav_fallback();
}

/**
 * Register additional menu locations for Poradnik.PRO.
 */
function pp_register_nav_menus() {
	register_nav_menus( array(
		'primary'         => __( 'Menu główne', 'pearblog-theme' ),
		'footer'          => __( 'Menu stopki', 'pearblog-theme' ),
		'footer-legal'    => __( 'Stopka — linki prawne', 'pearblog-theme' ),
		'mobile'          => __( 'Menu mobilne', 'pearblog-theme' ),
	) );
}
add_action( 'after_setup_theme', 'pp_register_nav_menus', 20 );

/**
 * Seed WP navigation menus programmatically.
 * Call via WP-CLI: wp eval-file theme/pearblog-theme/inc/poradnik-pro-navigation.php --seed-menus
 * Or use pp_seed_navigation_menus() from code.
 */
function pp_seed_navigation_menus() {
	$home = home_url( '/' );

	// --- Primary menu ---
	$menu_name  = 'Poradnik.PRO — Menu główne';
	$menu_exists = wp_get_nav_menu_object( $menu_name );

	if ( ! $menu_exists ) {
		$menu_id = wp_create_nav_menu( $menu_name );

		// Top-level items
		$poradniki_id = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => 'Poradniki',
			'menu-item-url'    => $home . 'poradniki/',
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		) );

		// Category sub-items
		$categories = array(
			'Prawo'          => 'kategoria/prawo/',
			'Finanse'        => 'kategoria/finanse/',
			'Nieruchomości'  => 'kategoria/nieruchomosci/',
			'Budownictwo'    => 'kategoria/budownictwo/',
			'Energia'        => 'kategoria/energia/',
			'Zdrowie'        => 'kategoria/zdrowie/',
			'Edukacja'       => 'kategoria/edukacja/',
			'Motoryzacja'    => 'kategoria/motoryzacja/',
			'Technologia'    => 'kategoria/technologia/',
			'Dom i ogród'    => 'kategoria/dom-i-ogrod/',
		);
		foreach ( $categories as $title => $path ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'     => $title,
				'menu-item-url'       => $home . $path,
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'custom',
				'menu-item-parent-id' => $poradniki_id,
			) );
		}

		// Specjaliści
		$spec_id = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => 'Specjaliści',
			'menu-item-url'    => $home . 'specjalisci/',
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		) );

		$cities = array(
			'Warszawa'  => 'warszawa/specjalisci/',
			'Kraków'    => 'krakow/specjalisci/',
			'Wrocław'   => 'wroclaw/specjalisci/',
			'Poznań'    => 'poznan/specjalisci/',
			'Gdańsk'    => 'gdansk/specjalisci/',
			'Katowice'  => 'katowice/specjalisci/',
			'Łódź'      => 'lodz/specjalisci/',
			'Szczecin'  => 'szczecin/specjalisci/',
			'Lublin'    => 'lublin/specjalisci/',
			'Bydgoszcz' => 'bydgoszcz/specjalisci/',
		);
		foreach ( $cities as $title => $path ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'     => $title,
				'menu-item-url'       => $home . $path,
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'custom',
				'menu-item-parent-id' => $spec_id,
			) );
		}

		// Narzędzia
		$tools_id = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => 'Narzędzia',
			'menu-item-url'    => $home . 'kalkulatory/',
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		) );

		$tools = array(
			'Kalkulatory'         => 'kalkulatory/',
			'Rankingi'            => 'rankingi/',
			'Porównania'          => 'porownania/',
			'Pytania i odpowiedzi' => 'pytania/',
		);
		foreach ( $tools as $title => $path ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'     => $title,
				'menu-item-url'       => $home . $path,
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'custom',
				'menu-item-parent-id' => $tools_id,
			) );
		}

		// Standalone items
		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => 'AI Doradca',
			'menu-item-url'    => $home . 'ai-doradca/',
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		) );

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => 'Cennik',
			'menu-item-url'    => $home . 'cennik/',
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		) );

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'   => 'Dla specjalistów',
			'menu-item-url'     => $home . 'dla-specjalistow/',
			'menu-item-status'  => 'publish',
			'menu-item-type'    => 'custom',
			'menu-item-classes' => 'menu-item-cta',
		) );

		// Assign to theme location
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		$locations['primary'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	// --- Footer menu ---
	$footer_name   = 'Poradnik.PRO — Stopka';
	$footer_exists = wp_get_nav_menu_object( $footer_name );

	if ( ! $footer_exists ) {
		$footer_id = wp_create_nav_menu( $footer_name );

		$footer_items = array(
			'Poradniki'         => 'poradniki/',
			'Specjaliści'       => 'specjalisci/',
			'Kalkulatory'       => 'kalkulatory/',
			'Rankingi'          => 'rankingi/',
			'AI Doradca'        => 'ai-doradca/',
			'Cennik'            => 'cennik/',
			'Dla specjalistów'  => 'dla-specjalistow/',
			'FAQ'               => 'faq/',
			'Kontakt'           => 'kontakt/',
			'Blog'              => 'blog/',
			'Polityka prywatności' => 'polityka-prywatnosci/',
			'Regulamin'         => 'regulamin/',
		);

		foreach ( $footer_items as $title => $path ) {
			wp_update_nav_menu_item( $footer_id, 0, array(
				'menu-item-title'  => $title,
				'menu-item-url'    => $home . $path,
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			) );
		}

		// Assign to theme location
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		$locations['footer'] = $footer_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	return true;
}

// Allow CLI seeding: wp eval 'pp_seed_navigation_menus();'
if ( defined( 'WP_CLI' ) && WP_CLI && in_array( '--seed-menus', $GLOBALS['argv'] ?? array(), true ) ) {
	pp_seed_navigation_menus();
	WP_CLI::success( 'Navigation menus seeded successfully.' );
}
