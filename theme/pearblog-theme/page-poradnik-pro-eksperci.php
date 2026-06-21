<?php
/**
 * Template Name: Poradnik.PRO - Eksperci
 * Description: Lista zweryfikowanych ekspertów Poradnik.PRO.
 *
 * @package PearBlog
 */

defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/inc/poradnik-pro-shared.php';

$pp_expert_categories = array(
	'Wszystkie specjalizacje',
	'Prawo',
	'Budownictwo',
	'Finanse',
	'Architektura',
	'Nieruchomości',
	'Instalacje',
);

$pp_experts = array(
	array(
		'initial'        => 'A',
		'name'           => 'Mec. Anna Kowalska',
		'specialization' => 'Prawo cywilne, spadkowe',
		'city'           => 'Warszawa',
		'rating'         => '4.9',
		'reviews'        => 128,
		'category'       => 'Prawo',
		'profile_url'    => home_url( '/specjalista/anna-kowalska/' ),
	),
	array(
		'initial'        => 'P',
		'name'           => 'Inż. Piotr Nowak',
		'specialization' => 'Budownictwo, remonty',
		'city'           => 'Kraków',
		'rating'         => '4.8',
		'reviews'        => 96,
		'category'       => 'Budownictwo',
		'profile_url'    => home_url( '/specjalista/piotr-nowak/' ),
	),
	array(
		'initial'        => 'M',
		'name'           => 'Dr Magdalena Wiśniewska',
		'specialization' => 'Finanse, kredyty',
		'city'           => 'Poznań',
		'rating'         => '4.7',
		'reviews'        => 84,
		'category'       => 'Finanse',
		'profile_url'    => home_url( '/specjalista/magdalena-wisniewska/' ),
	),
	array(
		'initial'        => 'T',
		'name'           => 'Arch. Tomasz Zieliński',
		'specialization' => 'Architektura, projekty',
		'city'           => 'Wrocław',
		'rating'         => '4.9',
		'reviews'        => 112,
		'category'       => 'Architektura',
		'profile_url'    => home_url( '/specjalista/tomasz-zielinski/' ),
	),
	array(
		'initial'        => 'K',
		'name'           => 'Mgr Karolina Dąbrowska',
		'specialization' => 'Nieruchomości, wyceny',
		'city'           => 'Gdańsk',
		'rating'         => '4.6',
		'reviews'        => 73,
		'category'       => 'Nieruchomości',
		'profile_url'    => home_url( '/specjalista/karolina-dabrowska/' ),
	),
	array(
		'initial'        => 'M',
		'name'           => 'Inż. Marek Kowalczyk',
		'specialization' => 'Instalacje, ogrzewanie',
		'city'           => 'Katowice',
		'rating'         => '4.8',
		'reviews'        => 101,
		'category'       => 'Instalacje',
		'profile_url'    => home_url( '/specjalista/marek-kowalczyk/' ),
	),
);
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
		.experts-search {
			background: #fff;
			border-bottom: 1px solid var(--gray-200);
			padding: 20px 0;
		}
		.search-inner {
			display: grid;
			grid-template-columns: minmax(0, 1.6fr) repeat(2, minmax(180px, 1fr)) auto;
			gap: 12px;
		}
		.search-input,
		.search-select {
			width: 100%;
			padding: 13px 18px;
			border: 1px solid var(--gray-200);
			border-radius: 999px;
			font: inherit;
			color: var(--gray-700);
			background: #fff;
		}
		.search-input:focus,
		.search-select:focus {
			outline: none;
			border-color: var(--purple-primary);
			box-shadow: 0 0 0 3px rgba(108, 43, 217, 0.12);
		}
		.search-input::placeholder {
			color: var(--gray-400);
		}
		.btn-search-experts {
			padding: 13px 28px;
			border-radius: 999px;
			background: var(--orange-cta);
			color: #fff;
			font-size: 14px;
			font-weight: 700;
			transition: background 0.2s ease;
		}
		.btn-search-experts:hover {
			background: var(--orange-hover);
		}
		.category-filter {
			padding: 18px 0 0;
		}
		.category-filter-label {
			display: block;
			font-size: 13px;
			font-weight: 700;
			color: var(--gray-600);
			margin-bottom: 12px;
		}
		.category-filter-list {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
		}
		.category-chip {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 9px 16px;
			border-radius: 999px;
			border: 1px solid var(--gray-200);
			background: #fff;
			font-size: 13px;
			font-weight: 600;
			color: var(--gray-600);
			transition: all 0.2s ease;
		}
		.category-chip:hover {
			border-color: var(--purple-primary);
			color: var(--purple-primary);
		}
		.category-chip.active {
			background: var(--purple-primary);
			border-color: var(--purple-primary);
			color: #fff;
		}
		.experts-section {
			padding: 48px 0 64px;
		}
		.experts-grid {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 24px;
		}
		.expert-card {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-lg);
			padding: 28px;
			box-shadow: var(--shadow-sm);
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}
		.expert-card:hover {
			transform: translateY(-3px);
			box-shadow: var(--shadow-md);
		}
		.expert-top {
			display: flex;
			align-items: center;
			gap: 16px;
			margin-bottom: 18px;
		}
		.expert-avatar {
			width: 68px;
			height: 68px;
			border-radius: 50%;
			background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
			color: #fff;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			font-size: 28px;
			font-weight: 800;
			flex-shrink: 0;
		}
		.expert-category {
			display: inline-flex;
			padding: 5px 10px;
			border-radius: 999px;
			background: rgba(59, 130, 246, 0.1);
			color: var(--blue-accent);
			font-size: 11px;
			font-weight: 700;
			margin-bottom: 10px;
		}
		.expert-name {
			font-size: 19px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 6px;
		}
		.expert-specialization {
			font-size: 14px;
			color: var(--purple-primary);
			font-weight: 600;
			margin-bottom: 14px;
		}
		.expert-meta {
			display: grid;
			gap: 10px;
			margin-bottom: 22px;
		}
		.expert-meta-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			font-size: 14px;
			color: var(--gray-600);
		}
		.expert-meta-item strong {
			color: var(--gray-900);
		}
		.expert-rating {
			color: var(--yellow-accent);
			font-weight: 700;
		}
		.btn-profile {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 100%;
			padding: 12px 20px;
			border-radius: 999px;
			background: var(--purple-primary);
			color: #fff;
			font-size: 14px;
			font-weight: 700;
			transition: background 0.2s ease;
		}
		.btn-profile:hover {
			background: var(--purple-dark);
		}
		@media (max-width: 1024px) {
			.search-inner {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
			.experts-grid {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
		}
		@media (max-width: 768px) {
			.search-inner,
			.experts-grid {
				grid-template-columns: 1fr;
			}
			.expert-top {
				align-items: flex-start;
			}
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'poradnik-pro-eksperci-page' ); ?>>
<?php wp_body_open(); pp_pro_header( 'specjalisci' ); ?>
<section class="page-hero">
	<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona główna</a>
			<span class="sep">/</span>
			<span>Eksperci</span>
		</div>
		<h1>Znajdź sprawdzonego eksperta</h1>
		<p>Ponad 500 zweryfikowanych specjalistów z całej Polski. Wybierz dziedzinę i znajdź pomoc.</p>
	</div>
</section>
<section class="experts-search">
	<div class="container">
		<div class="search-inner">
			<input class="search-input" type="text" placeholder="Wpisz specjalizację, nazwisko lub miasto">
			<select class="search-select" aria-label="Dziedzina">
				<option>Wybierz dziedzinę</option>
				<option>Prawo</option>
				<option>Budownictwo</option>
				<option>Finanse</option>
				<option>Architektura</option>
				<option>Nieruchomości</option>
				<option>Instalacje</option>
			</select>
			<select class="search-select" aria-label="Miasto">
				<option>Cała Polska</option>
				<option>Warszawa</option>
				<option>Kraków</option>
				<option>Poznań</option>
				<option>Wrocław</option>
				<option>Gdańsk</option>
				<option>Katowice</option>
			</select>
			<button class="btn-search-experts" type="button">Szukaj eksperta</button>
		</div>
		<div class="category-filter">
			<span class="category-filter-label">Popularne kategorie</span>
			<div class="category-filter-list">
				<?php foreach ( $pp_expert_categories as $index => $category ) : ?>
					<a class="category-chip<?php echo 0 === $index ? ' active' : ''; ?>" href="#">
						<?php echo esc_html( $category ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
<section class="experts-section">
	<div class="container">
		<div class="experts-grid">
			<?php foreach ( $pp_experts as $expert ) : ?>
				<article class="expert-card">
					<div class="expert-top">
						<div class="expert-avatar"><?php echo esc_html( $expert['initial'] ); ?></div>
						<div>
							<span class="expert-category"><?php echo esc_html( $expert['category'] ); ?></span>
							<h2 class="expert-name"><?php echo esc_html( $expert['name'] ); ?></h2>
							<p class="expert-specialization"><?php echo esc_html( $expert['specialization'] ); ?></p>
						</div>
					</div>
					<div class="expert-meta">
						<div class="expert-meta-item">
							<span>Miasto</span>
							<strong><?php echo esc_html( $expert['city'] ); ?></strong>
						</div>
						<div class="expert-meta-item">
							<span>Ocena</span>
							<strong class="expert-rating">★<?php echo esc_html( $expert['rating'] ); ?></strong>
						</div>
						<div class="expert-meta-item">
							<span>Opinie</span>
							<strong><?php echo esc_html( number_format_i18n( $expert['reviews'] ) ); ?></strong>
						</div>
					</div>
					<a class="btn-profile" href="<?php echo esc_url( $expert['profile_url'] ); ?>">Zobacz profil</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php pp_pro_footer(); wp_footer(); ?>
</body>
</html>
