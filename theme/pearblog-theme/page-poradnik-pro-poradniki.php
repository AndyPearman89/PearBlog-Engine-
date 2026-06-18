<?php
/**
 * Template Name: Poradnik.PRO - Poradniki (Archive)
 * Description: Guides listing page (/poradniki)
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 */

defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/inc/poradnik-pro-shared.php';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<?php pp_pro_shared_styles(); ?>
	<style>
		/* ===== FILTER BAR ===== */
		.filter-bar {
			background: #fff;
			border-bottom: 1px solid var(--gray-200);
			padding: 16px 0;
		}
		.filter-inner {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
		}
		.filter-chip {
			padding: 8px 16px;
			border-radius: 50px;
			font-size: 13px;
			font-weight: 500;
			border: 1px solid var(--gray-200);
			color: var(--gray-600);
			background: #fff;
			transition: all 0.2s;
		}
		.filter-chip:hover { border-color: var(--purple-primary); color: var(--purple-primary); }
		.filter-chip.active {
			background: var(--purple-primary);
			color: #fff;
			border-color: var(--purple-primary);
		}

		/* ===== GUIDES GRID ===== */
		.guides-section { padding: 48px 0; }
		.guides-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 24px;
		}
		.guide-card {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-md);
			overflow: hidden;
			transition: box-shadow 0.2s, transform 0.2s;
		}
		.guide-card:hover {
			box-shadow: var(--shadow-md);
			transform: translateY(-2px);
		}
		.guide-thumb {
			height: 160px;
			background: linear-gradient(135deg, #ede9fe, #dbeafe);
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 48px;
		}
		.guide-body { padding: 20px; }
		.guide-category {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 50px;
			font-size: 11px;
			font-weight: 600;
			background: #f3e8ff;
			color: var(--purple-primary);
			margin-bottom: 10px;
		}
		.guide-title {
			font-size: 16px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 8px;
			line-height: 1.3;
		}
		.guide-excerpt {
			font-size: 13px;
			color: var(--gray-500);
			line-height: 1.5;
			margin-bottom: 16px;
		}
		.guide-meta {
			display: flex;
			align-items: center;
			justify-content: space-between;
			font-size: 12px;
			color: var(--gray-400);
		}
		.guide-reading-time {
			display: flex;
			align-items: center;
			gap: 4px;
		}

		/* ===== FEATURED GUIDE ===== */
		.featured-guide {
			background: #fff;
			border: 2px solid var(--purple-light);
			border-radius: var(--radius-lg);
			padding: 32px;
			margin-bottom: 40px;
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 32px;
			align-items: center;
		}
		.featured-guide-image {
			height: 240px;
			background: linear-gradient(135deg, #1a0a3e, #6c2bd9);
			border-radius: var(--radius-md);
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 64px;
		}
		.featured-guide-badge {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 6px 12px;
			border-radius: 50px;
			font-size: 11px;
			font-weight: 600;
			background: #fef3c7;
			color: #d97706;
			margin-bottom: 12px;
		}
		.featured-guide h2 {
			font-size: 24px;
			font-weight: 800;
			color: var(--gray-900);
			margin-bottom: 12px;
		}
		.featured-guide p {
			font-size: 14px;
			color: var(--gray-600);
			margin-bottom: 20px;
			line-height: 1.6;
		}
		.btn-read {
			display: inline-block;
			background: var(--purple-primary);
			color: #fff;
			padding: 12px 28px;
			border-radius: 50px;
			font-size: 14px;
			font-weight: 600;
			transition: background 0.2s;
		}
		.btn-read:hover { background: var(--purple-dark); }

		/* ===== PAGINATION ===== */
		.pagination {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			padding: 40px 0;
		}
		.page-btn {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 14px;
			font-weight: 500;
			color: var(--gray-600);
			border: 1px solid var(--gray-200);
			background: #fff;
			transition: all 0.2s;
		}
		.page-btn:hover { border-color: var(--purple-primary); color: var(--purple-primary); }
		.page-btn.active {
			background: var(--purple-primary);
			color: #fff;
			border-color: var(--purple-primary);
		}

		@media (max-width: 768px) {
			.guides-grid { grid-template-columns: 1fr; }
			.featured-guide { grid-template-columns: 1fr; }
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'poradniki' ); ?>

<!-- PAGE HERO -->
<section class="page-hero">
	<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
			<span class="sep">/</span>
			<span>Poradniki</span>
		</div>
		<h1>Poradniki</h1>
		<p>Zrozum temat, zanim wydasz pieniadze. Kazdy poradnik to kompletna wiedza prowadzaca do swiadomej decyzji.</p>
	</div>
</section>

<!-- FILTER BAR -->
<div class="filter-bar">
	<div class="container">
		<div class="filter-inner">
			<button class="filter-chip active">Wszystkie</button>
			<button class="filter-chip">Budownictwo</button>
			<button class="filter-chip">Prawo</button>
			<button class="filter-chip">Finanse</button>
			<button class="filter-chip">Nieruchomosci</button>
			<button class="filter-chip">Zdrowie</button>
			<button class="filter-chip">Motoryzacja</button>
			<button class="filter-chip">Technologia</button>
			<button class="filter-chip">Energia</button>
		</div>
	</div>
</div>

<!-- FEATURED GUIDE -->
<section class="guides-section">
	<div class="container">
		<div class="featured-guide">
			<div class="featured-guide-image">🏠</div>
			<div>
				<div class="featured-guide-badge">&#9733; Polecany poradnik</div>
				<h2>Koszt remontu lazienki &mdash; aktualne ceny 2026</h2>
				<p>Pelna analiza kosztow remontu lazienki: materialy, robocizna, ukryte wydatki. Dowiedz sie, ile naprawde zaplacisz i jak zaoszczedzic bez utraty jakosci.</p>
				<a href="<?php echo esc_url( home_url( '/poradnik/koszt-remontu-lazienki-2026/' ) ); ?>" class="btn-read">Czytaj poradnik</a>
			</div>
		</div>

		<!-- GUIDES GRID -->
		<div class="guides-grid">
			<a href="<?php echo esc_url( home_url( '/poradnik/jak-sprzedac-dzialke/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">🏗️</div>
				<div class="guide-body">
					<span class="guide-category">Budownictwo</span>
					<h3 class="guide-title">Jak sprzedac dzialke krok po kroku</h3>
					<p class="guide-excerpt">Kompletny przewodnik po sprzedazy dzialki &mdash; od wyceny po notariusza.</p>
					<div class="guide-meta">
						<span class="guide-reading-time">12 min</span>
						<span>2,4k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/kredyt-hipoteczny-przewodnik/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">💳</div>
				<div class="guide-body">
					<span class="guide-category">Finanse</span>
					<h3 class="guide-title">Kredyt hipoteczny &mdash; co musisz wiedziec</h3>
					<p class="guide-excerpt">Przewodnik po kredycie hipotecznym: zdolnosc, dokumenty, najlepsze oferty bankow w 2026.</p>
					<div class="guide-meta">
						<span class="guide-reading-time">18 min</span>
						<span>5,1k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/koszt-budowy-domu-2026/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">🔧</div>
				<div class="guide-body">
					<span class="guide-category">Budownictwo</span>
					<h3 class="guide-title">Koszt budowy domu za m&sup2; w 2026</h3>
					<p class="guide-excerpt">Aktualne koszty budowy domu na kazdym etapie &mdash; fundamenty, sciany, dach, wykonczenie.</p>
					<div class="guide-meta">
						<span class="guide-reading-time">15 min</span>
						<span>8,7k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/rozwod-procedura-koszty/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">⚖️</div>
				<div class="guide-body">
					<span class="guide-category">Prawo</span>
					<h3 class="guide-title">Rozwod &mdash; procedura i koszty 2026</h3>
					<p class="guide-excerpt">Jak wyglada procedura rozwodowa, ile kosztuje i jak sie przygotowac &mdash; kompletny poradnik.</p>
					<div class="guide-meta">
						<span class="guide-reading-time">14 min</span>
						<span>3,9k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/pompa-ciepla-poradnik/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">🌡️</div>
				<div class="guide-body">
					<span class="guide-category">Energia</span>
					<h3 class="guide-title">Pompa ciepla &mdash; wszystko co musisz wiedziec</h3>
					<p class="guide-excerpt">Rodzaje pomp ciepla, koszty instalacji, oszczednosci i dotacje &mdash; pelny przeglad.</p>
					<div class="guide-meta">
						<span class="guide-reading-time">20 min</span>
						<span>12k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/kupno-uzywane-auto/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">🚗</div>
				<div class="guide-body">
					<span class="guide-category">Motoryzacja</span>
					<h3 class="guide-title">Jak kupic uzywane auto bez ryzyka</h3>
					<p class="guide-excerpt">Na co zwrocic uwage, jakie dokumenty sprawdzic i jak nie dac sie oszukac.</p>
					<div class="guide-meta">
						<span class="guide-reading-time">10 min</span>
						<span>6,2k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/fotowoltaika-2026/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">☀️</div>
				<div class="guide-body">
					<span class="guide-category">Energia</span>
					<h3 class="guide-title">Fotowoltaika w 2026 &mdash; kompletny poradnik</h3>
					<p class="guide-excerpt">Net-billing, dobor mocy, koszty instalacji i realne oszczednosci. Czy panele nadal sie oplacaja?</p>
					<div class="guide-meta">
						<span class="guide-reading-time">22 min</span>
						<span>15k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/kupno-mieszkania-od-a-do-z/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">🏘️</div>
				<div class="guide-body">
					<span class="guide-category">Nieruchomosci</span>
					<h3 class="guide-title">Kupno mieszkania &mdash; od A do Z</h3>
					<p class="guide-excerpt">Rynek pierwotny vs wtorny, umowa deweloperska, kredyt, odbior techniczny &mdash; wszystko w jednym miejscu.</p>
					<div class="guide-meta">
						<span class="guide-reading-time">25 min</span>
						<span>9,8k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/implanty-zebowe/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">🦷</div>
				<div class="guide-body">
					<span class="guide-category">Zdrowie</span>
					<h3 class="guide-title">Implanty zebowe &mdash; koszt, rodzaje i opinie</h3>
					<p class="guide-excerpt">Ile kosztuje implant, jak wyglada zabieg i kiedy warto wybrac most zamiast implantu?</p>
					<div class="guide-meta">
						<span class="guide-reading-time">14 min</span>
						<span>7,3k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/internet-domowy-porownanie/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">📱</div>
				<div class="guide-body">
					<span class="guide-category">Technologia</span>
					<h3 class="guide-title">Internet domowy &mdash; porownanie technologii 2026</h3>
					<p class="guide-excerpt">Swiatlowod, LTE, 5G, kabel &mdash; ktora technologia najlepsza w Twojej lokalizacji?</p>
					<div class="guide-meta">
						<span class="guide-reading-time">11 min</span>
						<span>4,5k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/docieplenie-domu/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">🏡</div>
				<div class="guide-body">
					<span class="guide-category">Budownictwo</span>
					<h3 class="guide-title">Docieplenie domu &mdash; materialy i koszty</h3>
					<p class="guide-excerpt">Styropian vs welna, metoda lekka mokra, koszty za m&sup2; i dotacje na termomodernizacje.</p>
					<div class="guide-meta">
						<span class="guide-reading-time">16 min</span>
						<span>6,8k wyswietlen</span>
					</div>
				</div>
			</a>

			<a href="<?php echo esc_url( home_url( '/poradnik/alimenty-poradnik/' ) ); ?>" class="guide-card">
				<div class="guide-thumb">👶</div>
				<div class="guide-body">
					<span class="guide-category">Prawo</span>
					<h3 class="guide-title">Alimenty &mdash; ile, jak i kiedy?</h3>
					<p class="guide-excerpt">Zasady ustalania alimentow, tabelki orientacyjne, procedura sadowa i egzekucja.</p>
					<div class="guide-meta">
						<span class="guide-reading-time">13 min</span>
						<span>11k wyswietlen</span>
					</div>
				</div>
			</a>
		</div>

		<!-- PAGINATION -->
		<div class="pagination">
			<button class="page-btn active">1</button>
			<button class="page-btn">2</button>
			<button class="page-btn">3</button>
			<button class="page-btn">4</button>
			<button class="page-btn">&rarr;</button>
		</div>
	</div>
</section>

<?php pp_pro_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
