<?php
/**
 * Poradnik.PRO Shared Components
 *
 * Reusable header, footer, and style blocks for all Poradnik.PRO templates.
 * Ensures design consistency across all subpages matching the home page look.
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output shared CSS variables and base styles for Poradnik.PRO pages.
 */
function pp_pro_shared_styles() {
	?>
	<style>
		/* ===== RESET & BASE ===== */
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
			color: #1a1a2e;
			background: #f8f9fc;
			line-height: 1.5;
			-webkit-font-smoothing: antialiased;
		}
		a { text-decoration: none; color: inherit; }
		img { max-width: 100%; height: auto; display: block; }
		button { cursor: pointer; border: none; font-family: inherit; }
		ul { list-style: none; }

		/* ===== VARIABLES ===== */
		:root {
			--purple-primary: #6c2bd9;
			--purple-dark: #1a0a3e;
			--purple-light: #8b5cf6;
			--orange-cta: #f97316;
			--orange-hover: #ea580c;
			--blue-accent: #3b82f6;
			--green-accent: #10b981;
			--yellow-accent: #f59e0b;
			--gray-50: #f8fafc;
			--gray-100: #f1f5f9;
			--gray-200: #e2e8f0;
			--gray-300: #cbd5e1;
			--gray-400: #94a3b8;
			--gray-500: #64748b;
			--gray-600: #475569;
			--gray-700: #334155;
			--gray-800: #1e293b;
			--gray-900: #0f172a;
			--radius-sm: 8px;
			--radius-md: 12px;
			--radius-lg: 16px;
			--radius-xl: 24px;
			--shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
			--shadow-md: 0 4px 12px rgba(0,0,0,0.08);
			--shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
			--max-width: 1200px;
		}

		.container { max-width: var(--max-width); margin: 0 auto; padding: 0 24px; }

		/* ===== HEADER / NAV ===== */
		.site-header {
			background: #fff;
			border-bottom: 1px solid var(--gray-200);
			position: sticky;
			top: 0;
			z-index: 100;
		}
		.header-inner {
			display: flex;
			align-items: center;
			justify-content: space-between;
			height: 64px;
		}
		.logo {
			display: flex;
			align-items: center;
			gap: 8px;
			font-weight: 800;
			font-size: 20px;
			color: var(--gray-900);
		}
		.logo-icon {
			width: 32px;
			height: 32px;
			background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
			border-radius: 8px;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #fff;
			font-weight: 700;
			font-size: 16px;
		}
		.main-nav { display: flex; align-items: center; gap: 28px; }
		.main-nav a {
			font-size: 14px;
			font-weight: 500;
			color: var(--gray-600);
			transition: color 0.2s;
		}
		.main-nav a:hover { color: var(--purple-primary); }
		.main-nav a.active { color: var(--purple-primary); font-weight: 600; }
		.header-actions { display: flex; align-items: center; gap: 16px; }
		.btn-search-icon {
			background: none;
			font-size: 18px;
			color: var(--gray-600);
		}
		.btn-find-specialist {
			background: var(--purple-primary);
			color: #fff;
			padding: 10px 20px;
			border-radius: 50px;
			font-size: 13px;
			font-weight: 600;
			transition: background 0.2s;
		}
		.btn-find-specialist:hover { background: var(--purple-dark); }

		/* ===== BREADCRUMB ===== */
		.breadcrumb {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: 13px;
			color: var(--gray-400);
			margin-bottom: 16px;
		}
		.breadcrumb a { color: var(--gray-500); transition: color 0.2s; }
		.breadcrumb a:hover { color: var(--purple-primary); }
		.breadcrumb .sep { color: var(--gray-300); }

		/* ===== PAGE HERO ===== */
		.page-hero {
			background: linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%);
			padding: 48px 0;
		}
		.page-hero h1 {
			font-size: 32px;
			font-weight: 800;
			color: var(--gray-900);
			margin-bottom: 8px;
		}
		.page-hero p {
			font-size: 16px;
			color: var(--gray-600);
			max-width: 560px;
		}

		/* ===== FOOTER ===== */
		.site-footer {
			background: #fff;
			border-top: 1px solid var(--gray-200);
			padding: 40px 0 24px;
		}
		.footer-grid {
			display: grid;
			grid-template-columns: repeat(5, 1fr);
			gap: 32px;
			margin-bottom: 32px;
		}
		.footer-col h4 {
			font-size: 13px;
			font-weight: 700;
			color: var(--gray-800);
			margin-bottom: 12px;
		}
		.footer-col a {
			display: block;
			font-size: 12px;
			color: var(--gray-500);
			margin-bottom: 8px;
			transition: color 0.2s;
		}
		.footer-col a:hover { color: var(--purple-primary); }
		.footer-bottom {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding-top: 24px;
			border-top: 1px solid var(--gray-200);
			font-size: 12px;
			color: var(--gray-400);
		}
		.footer-links { display: flex; gap: 20px; }
		.footer-links a { color: var(--gray-500); transition: color 0.2s; }
		.footer-links a:hover { color: var(--purple-primary); }

		/* ===== RESPONSIVE ===== */
		@media (max-width: 768px) {
			.main-nav { display: none; }
			.footer-grid { grid-template-columns: repeat(2, 1fr); }
		}
	</style>
	<?php
}

/**
 * Output the Poradnik.PRO site header.
 *
 * @param string $active_page Slug of the currently active navigation item.
 */
function pp_pro_header( $active_page = '' ) {
	$home = esc_url( home_url( '/' ) );
	$nav_items = array(
		'poradniki'   => array( 'label' => 'Poradniki', 'url' => '/poradniki/' ),
		'porownania'  => array( 'label' => 'Porownania', 'url' => '/porownania/' ),
		'rankingi'    => array( 'label' => 'Rankingi', 'url' => '/rankingi/' ),
		'kalkulatory' => array( 'label' => 'Kalkulatory', 'url' => '/kalkulatory/' ),
		'pytania'     => array( 'label' => 'Pytania i Odpowiedzi', 'url' => '/pytania/' ),
		'specjalisci' => array( 'label' => 'Specjalisci', 'url' => '/specjalisci/' ),
	);
	?>
	<header class="site-header">
		<div class="container header-inner">
			<a href="<?php echo $home; ?>" class="logo">
				<div class="logo-icon">P</div>
				Poradnik.pro
			</a>
			<nav class="main-nav">
				<?php foreach ( $nav_items as $slug => $item ) : ?>
					<a href="<?php echo esc_url( home_url( $item['url'] ) ); ?>"<?php echo $active_page === $slug ? ' class="active"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="header-actions">
				<a href="<?php echo esc_url( home_url( '/specjalisci/' ) ); ?>" class="btn-find-specialist">Znajdz specjaliste</a>
			</div>
		</div>
	</header>
	<?php
}

/**
 * Output the Poradnik.PRO site footer.
 */
function pp_pro_footer() {
	$home = esc_url( home_url( '/' ) );
	?>
	<footer class="site-footer">
		<div class="container">
			<div class="footer-grid">
				<div class="footer-col">
					<h4>Poradniki</h4>
					<a href="<?php echo esc_url( home_url( '/kategoria/prawo/' ) ); ?>">Prawo</a>
					<a href="<?php echo esc_url( home_url( '/kategoria/finanse/' ) ); ?>">Finanse</a>
					<a href="<?php echo esc_url( home_url( '/kategoria/nieruchomosci/' ) ); ?>">Nieruchomosci</a>
					<a href="<?php echo esc_url( home_url( '/kategoria/budownictwo/' ) ); ?>">Budownictwo</a>
					<a href="<?php echo esc_url( home_url( '/kategoria/motoryzacja/' ) ); ?>">Motoryzacja</a>
				</div>
				<div class="footer-col">
					<h4>Rankingi</h4>
					<a href="<?php echo esc_url( home_url( '/ranking/kredyty/' ) ); ?>">Kredyty</a>
					<a href="<?php echo esc_url( home_url( '/ranking/konta-bankowe/' ) ); ?>">Konta bankowe</a>
					<a href="<?php echo esc_url( home_url( '/ranking/ubezpieczenia/' ) ); ?>">Ubezpieczenia</a>
					<a href="<?php echo esc_url( home_url( '/ranking/pompy-ciepla/' ) ); ?>">Pompy ciepla</a>
					<a href="<?php echo esc_url( home_url( '/ranking/programy-ksiegowe/' ) ); ?>">Programy ksiegowe</a>
				</div>
				<div class="footer-col">
					<h4>Kalkulatory</h4>
					<a href="<?php echo esc_url( home_url( '/kalkulator/kredyt-hipoteczny/' ) ); ?>">Kredyt hipoteczny</a>
					<a href="<?php echo esc_url( home_url( '/kalkulator/zdolnosc-kredytowa/' ) ); ?>">Zdolnosc kredytowa</a>
					<a href="<?php echo esc_url( home_url( '/kalkulator/koszt-budowy/' ) ); ?>">Koszt budowy</a>
					<a href="<?php echo esc_url( home_url( '/kalkulator/oc/' ) ); ?>">Kalkulator OC</a>
					<a href="<?php echo esc_url( home_url( '/kalkulator/wynagrodzenia/' ) ); ?>">Wynagrodzenia</a>
				</div>
				<div class="footer-col">
					<h4>Dla specjalistow</h4>
					<a href="<?php echo esc_url( home_url( '/dla-specjalistow/' ) ); ?>">Dolacz jako ekspert</a>
					<a href="<?php echo esc_url( home_url( '/panel/' ) ); ?>">Panel specjalisty</a>
					<a href="<?php echo esc_url( home_url( '/cennik/' ) ); ?>">Cennik</a>
					<a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">FAQ</a>
				</div>
				<div class="footer-col">
					<h4>O nas</h4>
					<a href="<?php echo $home; ?>">O Poradnik.pro</a>
					<a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Kontakt</a>
					<a href="<?php echo esc_url( home_url( '/regulamin/' ) ); ?>">Regulamin</a>
					<a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>">Polityka prywatnosci</a>
				</div>
			</div>
			<div class="footer-bottom">
				<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Poradnik.pro. Wszelkie prawa zastrzezone.</span>
				<div class="footer-links">
					<a href="<?php echo esc_url( home_url( '/regulamin/' ) ); ?>">Regulamin</a>
					<a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>">Polityka prywatnosci</a>
					<a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Kontakt</a>
				</div>
			</div>
		</div>
	</footer>
	<?php
}
