<?php
/**
 * Template Name: Poradnik.PRO - Porownania
 * Description: Comparisons listing page (/porownania)
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
		/* ===== PAGE HERO (blue-purple gradient) ===== */
		.page-hero {
			background: linear-gradient(135deg, #dbeafe 0%, #ede9fe 100%);
			padding: 48px 0;
		}

		/* ===== FEATURED SECTION ===== */
		.featured-section {
			padding: 48px 0 0;
		}
		.featured-section-title {
			font-size: 20px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 20px;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.featured-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 20px;
			margin-bottom: 16px;
		}
		.featured-card {
			background: #fff;
			border: 2px solid var(--purple-primary);
			border-radius: var(--radius-lg);
			padding: 24px;
			position: relative;
			display: block;
			transition: box-shadow 0.2s, transform 0.2s;
		}
		.featured-card:hover {
			box-shadow: var(--shadow-lg);
			transform: translateY(-3px);
		}
		.featured-badge {
			position: absolute;
			top: -10px;
			left: 20px;
			background: var(--purple-primary);
			color: #fff;
			padding: 4px 12px;
			border-radius: 50px;
			font-size: 11px;
			font-weight: 700;
		}
		.featured-icon {
			font-size: 36px;
			margin-bottom: 12px;
			margin-top: 8px;
		}
		.featured-title {
			font-size: 16px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 8px;
		}
		.featured-desc {
			font-size: 13px;
			color: var(--gray-500);
			line-height: 1.5;
			margin-bottom: 14px;
		}
		.featured-stats {
			display: flex;
			align-items: center;
			gap: 12px;
		}
		.featured-stat {
			font-size: 11px;
			font-weight: 600;
			color: var(--gray-600);
			background: var(--gray-100);
			padding: 4px 10px;
			border-radius: 50px;
		}
		.featured-stat.green {
			background: #d1fae5;
			color: #059669;
		}

		/* ===== COMPARISONS GRID ===== */
		.comparisons-section {
			padding: 48px 0;
		}
		.section-title {
			font-size: 20px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 20px;
		}
		.comparisons-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 20px;
		}
		.comparison-card {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-lg);
			padding: 24px;
			display: block;
			transition: box-shadow 0.2s, transform 0.2s;
		}
		.comparison-card:hover {
			box-shadow: var(--shadow-md);
			transform: translateY(-2px);
		}
		.comparison-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 14px;
		}
		.comparison-icon {
			font-size: 32px;
		}
		.comparison-vs-badge {
			background: var(--orange-cta);
			color: #fff;
			width: 32px;
			height: 32px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 11px;
			font-weight: 800;
		}
		.comparison-title {
			font-size: 15px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 8px;
			line-height: 1.3;
		}
		.comparison-desc {
			font-size: 13px;
			color: var(--gray-500);
			line-height: 1.5;
			margin-bottom: 16px;
		}
		.comparison-meta {
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
		}
		.comparison-stat {
			font-size: 11px;
			font-weight: 600;
			color: var(--gray-600);
			background: var(--gray-100);
			padding: 4px 10px;
			border-radius: 50px;
		}
		.comparison-stat.purple {
			background: #ede9fe;
			color: var(--purple-primary);
		}
		.comparison-stat.green {
			background: #d1fae5;
			color: #059669;
		}
		.comparison-link {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			font-size: 13px;
			font-weight: 600;
			color: var(--purple-primary);
			margin-top: 14px;
			transition: gap 0.2s;
		}
		.comparison-card:hover .comparison-link {
			gap: 8px;
		}

		/* ===== RESPONSIVE ===== */
		@media (max-width: 768px) {
			.featured-grid { grid-template-columns: 1fr; }
			.comparisons-grid { grid-template-columns: 1fr; }
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'porownania' ); ?>

<!-- PAGE HERO -->
<section class="page-hero">
	<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
			<span class="sep">/</span>
			<span>Porownania</span>
		</div>
		<h1>Porownania</h1>
		<p>Porownuj produkty i uslugi obok siebie. Jasne roznice, realne koszty i konkretny werdykt — wiesz co wybrac i dlaczego.</p>
	</div>
</section>

<!-- POPULARNE POROWNANIA (FEATURED) -->
<section class="featured-section">
	<div class="container">
		<div class="featured-section-title">Popularne porownania</div>
		<div class="featured-grid">
			<a href="<?php echo esc_url( home_url( '/porownanie/pompa-ciepla-vs-kociol-gazowy/' ) ); ?>" class="featured-card">
				<span class="featured-badge">Popularne</span>
				<div class="featured-icon">🌡️</div>
				<div class="featured-title">Pompa ciepla vs kociol gazowy</div>
				<div class="featured-desc">Co sie bardziej oplaca w 2026? Porownanie kosztow instalacji, eksploatacji i zwrotu z inwestycji.</div>
				<div class="featured-stats">
					<span class="featured-stat">6 kryteriow</span>
					<span class="featured-stat green">Werdykt dostepny</span>
				</div>
			</a>
			<a href="<?php echo esc_url( home_url( '/porownanie/kredyt-staly-vs-zmienny/' ) ); ?>" class="featured-card">
				<span class="featured-badge">Trending</span>
				<div class="featured-icon">💰</div>
				<div class="featured-title">Kredyt hipoteczny: stale vs zmienne oprocentowanie</div>
				<div class="featured-desc">Ktora opcja lepsza przy obecnych stopach procentowych? Analiza kosztow na 20 i 30 lat.</div>
				<div class="featured-stats">
					<span class="featured-stat">5 kryteriow</span>
					<span class="featured-stat green">Werdykt dostepny</span>
				</div>
			</a>
			<a href="<?php echo esc_url( home_url( '/porownanie/styropian-vs-welna-mineralna/' ) ); ?>" class="featured-card">
				<span class="featured-badge">Nowe</span>
				<div class="featured-icon">🏠</div>
				<div class="featured-title">Styropian vs welna mineralna</div>
				<div class="featured-desc">Ktory material izolacyjny wybrac? Porownanie cen, parametrow termicznych i trwalosci.</div>
				<div class="featured-stats">
					<span class="featured-stat">7 kryteriow</span>
					<span class="featured-stat green">Werdykt dostepny</span>
				</div>
			</a>
		</div>
	</div>
</section>

<!-- ALL COMPARISONS GRID -->
<section class="comparisons-section">
	<div class="container">
		<div class="section-title">Wszystkie porownania</div>
		<div class="comparisons-grid">

			<!-- 1. Pompa ciepla vs kociol gazowy -->
			<a href="<?php echo esc_url( home_url( '/porownanie/pompa-ciepla-vs-kociol-gazowy/' ) ); ?>" class="comparison-card">
				<div class="comparison-header">
					<span class="comparison-icon">🌡️</span>
					<span class="comparison-vs-badge">VS</span>
				</div>
				<h3 class="comparison-title">Pompa ciepla vs kociol gazowy</h3>
				<p class="comparison-desc">Porownanie kosztow ogrzewania, instalacji i eksploatacji. Sprawdz co sie bardziej oplaca.</p>
				<div class="comparison-meta">
					<span class="comparison-stat">Ogrzewanie</span>
					<span class="comparison-stat purple">6 kryteriow</span>
					<span class="comparison-stat green">Werdykt</span>
				</div>
				<span class="comparison-link">Zobacz porownanie &rarr;</span>
			</a>

			<!-- 2. Kredyt hipoteczny: stale vs zmienne -->
			<a href="<?php echo esc_url( home_url( '/porownanie/kredyt-staly-vs-zmienny/' ) ); ?>" class="comparison-card">
				<div class="comparison-header">
					<span class="comparison-icon">💰</span>
					<span class="comparison-vs-badge">VS</span>
				</div>
				<h3 class="comparison-title">Kredyt hipoteczny: stale vs zmienne oprocentowanie</h3>
				<p class="comparison-desc">Stala czy zmienna stopa procentowa? Ktory kredyt hipoteczny wybrac w obecnych warunkach.</p>
				<div class="comparison-meta">
					<span class="comparison-stat">Finanse</span>
					<span class="comparison-stat purple">5 kryteriow</span>
					<span class="comparison-stat green">Werdykt</span>
				</div>
				<span class="comparison-link">Zobacz porownanie &rarr;</span>
			</a>

			<!-- 3. Styropian vs welna mineralna -->
			<a href="<?php echo esc_url( home_url( '/porownanie/styropian-vs-welna-mineralna/' ) ); ?>" class="comparison-card">
				<div class="comparison-header">
					<span class="comparison-icon">🧱</span>
					<span class="comparison-vs-badge">VS</span>
				</div>
				<h3 class="comparison-title">Styropian vs welna mineralna</h3>
				<p class="comparison-desc">Ktory material izolacyjny wybrac do ocieplenia domu? Parametry, ceny i trwalosc.</p>
				<div class="comparison-meta">
					<span class="comparison-stat">Termomodernizacja</span>
					<span class="comparison-stat purple">7 kryteriow</span>
					<span class="comparison-stat green">Werdykt</span>
				</div>
				<span class="comparison-link">Zobacz porownanie &rarr;</span>
			</a>

			<!-- 4. Fotowoltaika vs kolektory sloneczne -->
			<a href="<?php echo esc_url( home_url( '/porownanie/fotowoltaika-vs-kolektory-sloneczne/' ) ); ?>" class="comparison-card">
				<div class="comparison-header">
					<span class="comparison-icon">☀️</span>
					<span class="comparison-vs-badge">VS</span>
				</div>
				<h3 class="comparison-title">Fotowoltaika vs kolektory sloneczne</h3>
				<p class="comparison-desc">Panele fotowoltaiczne czy kolektory? Roznice w zastosowaniu, kosztach i zwrocie inwestycji.</p>
				<div class="comparison-meta">
					<span class="comparison-stat">Energia</span>
					<span class="comparison-stat purple">5 kryteriow</span>
					<span class="comparison-stat green">Werdykt</span>
				</div>
				<span class="comparison-link">Zobacz porownanie &rarr;</span>
			</a>

			<!-- 5. Mieszkanie z rynku pierwotnego vs wtornego -->
			<a href="<?php echo esc_url( home_url( '/porownanie/rynek-pierwotny-vs-wtorny/' ) ); ?>" class="comparison-card">
				<div class="comparison-header">
					<span class="comparison-icon">🏢</span>
					<span class="comparison-vs-badge">VS</span>
				</div>
				<h3 class="comparison-title">Mieszkanie z rynku pierwotnego vs wtornego</h3>
				<p class="comparison-desc">Nowe mieszkanie od dewelopera czy z rynku wtornego? Porownanie cen, stanu i kosztow dodatkowych.</p>
				<div class="comparison-meta">
					<span class="comparison-stat">Nieruchomosci</span>
					<span class="comparison-stat purple">6 kryteriow</span>
					<span class="comparison-stat green">Werdykt</span>
				</div>
				<span class="comparison-link">Zobacz porownanie &rarr;</span>
			</a>

			<!-- 6. Internet swiatlowodowy vs 5G -->
			<a href="<?php echo esc_url( home_url( '/porownanie/swiatlowod-vs-5g/' ) ); ?>" class="comparison-card">
				<div class="comparison-header">
					<span class="comparison-icon">🌐</span>
					<span class="comparison-vs-badge">VS</span>
				</div>
				<h3 class="comparison-title">Internet swiatlowodowy vs 5G</h3>
				<p class="comparison-desc">Ktora technologia zapewni szybszy i stabilniejszy internet? Porownanie predkosci, cen i dostepnosci.</p>
				<div class="comparison-meta">
					<span class="comparison-stat">Technologia</span>
					<span class="comparison-stat purple">4 kryteria</span>
					<span class="comparison-stat green">Werdykt</span>
				</div>
				<span class="comparison-link">Zobacz porownanie &rarr;</span>
			</a>

			<!-- 7. Leasing vs kredyt na auto -->
			<a href="<?php echo esc_url( home_url( '/porownanie/leasing-vs-kredyt-samochodowy/' ) ); ?>" class="comparison-card">
				<div class="comparison-header">
					<span class="comparison-icon">🚗</span>
					<span class="comparison-vs-badge">VS</span>
				</div>
				<h3 class="comparison-title">Leasing vs kredyt na auto</h3>
				<p class="comparison-desc">Leasing operacyjny czy kredyt samochodowy? Porownanie kosztow, podatkow i elastycznosci.</p>
				<div class="comparison-meta">
					<span class="comparison-stat">Motoryzacja</span>
					<span class="comparison-stat purple">5 kryteriow</span>
					<span class="comparison-stat green">Werdykt</span>
				</div>
				<span class="comparison-link">Zobacz porownanie &rarr;</span>
			</a>

			<!-- 8. Implanty vs mosty zebowe -->
			<a href="<?php echo esc_url( home_url( '/porownanie/implanty-vs-mosty-zebowe/' ) ); ?>" class="comparison-card">
				<div class="comparison-header">
					<span class="comparison-icon">🦷</span>
					<span class="comparison-vs-badge">VS</span>
				</div>
				<h3 class="comparison-title">Implanty vs mosty zebowe</h3>
				<p class="comparison-desc">Trwalosc, komfort i cena — co wybrac przy uzupelnianiu brakujacych zebow?</p>
				<div class="comparison-meta">
					<span class="comparison-stat">Stomatologia</span>
					<span class="comparison-stat purple">4 kryteria</span>
					<span class="comparison-stat green">Werdykt</span>
				</div>
				<span class="comparison-link">Zobacz porownanie &rarr;</span>
			</a>

			<!-- 9. OC online vs agent -->
			<a href="<?php echo esc_url( home_url( '/porownanie/oc-online-vs-agent/' ) ); ?>" class="comparison-card">
				<div class="comparison-header">
					<span class="comparison-icon">🛡️</span>
					<span class="comparison-vs-badge">VS</span>
				</div>
				<h3 class="comparison-title">OC online vs agent ubezpieczeniowy</h3>
				<p class="comparison-desc">Kupic OC przez internet czy u agenta? Porownanie cen, obslugi i zakresu ochrony.</p>
				<div class="comparison-meta">
					<span class="comparison-stat">Ubezpieczenia</span>
					<span class="comparison-stat purple">4 kryteria</span>
					<span class="comparison-stat green">Werdykt</span>
				</div>
				<span class="comparison-link">Zobacz porownanie &rarr;</span>
			</a>

		</div>
	</div>
</section>

<?php pp_pro_footer(); ?>

<?php wp_footer(); ?>
</body>
</html>
