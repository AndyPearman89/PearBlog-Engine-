<?php
/**
 * Template Name: Poradnik.PRO - Pytania i Odpowiedzi
 *
 * Q&A listing page for Poradnik.PRO. Displays community questions with
 * category filters, status indicators, and sidebar widgets.
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
		/* ===== PAGE-SPECIFIC: PYTANIA ===== */
		.page-hero--pytania {
			background: linear-gradient(135deg, #fff7ed 0%, #fef3c7 100%);
			padding: 48px 0 40px;
		}
		.page-hero--pytania .breadcrumb {
			margin-bottom: 16px;
		}
		.page-hero--pytania h1 {
			font-size: 36px;
			font-weight: 800;
			color: var(--gray-900);
			margin-bottom: 10px;
		}
		.page-hero--pytania .hero-subtitle {
			font-size: 16px;
			color: var(--gray-600);
			max-width: 600px;
			margin-bottom: 24px;
		}
		.btn-ask-question {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 14px 28px;
			border-radius: 50px;
			background: var(--orange-cta);
			color: #fff;
			font-size: 14px;
			font-weight: 700;
			transition: background 0.2s;
			box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
		}
		.btn-ask-question:hover {
			background: var(--orange-hover);
		}

		/* ===== FILTER CHIPS ===== */
		.filters-section {
			padding: 28px 0 0;
		}
		.filter-chips {
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
			margin-bottom: 32px;
		}
		.filter-chip {
			display: inline-flex;
			align-items: center;
			padding: 10px 20px;
			border-radius: 50px;
			border: 1px solid var(--gray-200);
			background: #fff;
			color: var(--gray-600);
			font-size: 13px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.2s;
		}
		.filter-chip:hover {
			border-color: var(--purple-primary);
			color: var(--purple-primary);
		}
		.filter-chip.active {
			background: var(--purple-primary);
			border-color: var(--purple-primary);
			color: #fff;
		}

		/* ===== CONTENT LAYOUT ===== */
		.pytania-grid {
			display: grid;
			grid-template-columns: 1fr 320px;
			gap: 32px;
			padding-bottom: 64px;
		}

		/* ===== QUESTION CARDS ===== */
		.questions-list {
			display: flex;
			flex-direction: column;
			gap: 16px;
		}
		.question-card {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-lg);
			padding: 24px;
			transition: box-shadow 0.2s, transform 0.2s;
		}
		.question-card:hover {
			box-shadow: var(--shadow-md);
			transform: translateY(-2px);
		}
		.question-header {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 16px;
			margin-bottom: 12px;
		}
		.question-badge {
			display: inline-flex;
			align-items: center;
			padding: 5px 12px;
			border-radius: 50px;
			font-size: 11px;
			font-weight: 700;
			letter-spacing: 0.02em;
		}
		.question-badge--prawo {
			background: #ede9fe;
			color: #7c3aed;
		}
		.question-badge--finanse {
			background: #dcfce7;
			color: #16a34a;
		}
		.question-badge--budownictwo {
			background: #fee2e2;
			color: #dc2626;
		}
		.question-badge--nieruchomosci {
			background: #fef3c7;
			color: #d97706;
		}
		.question-badge--zdrowie {
			background: #fce7f3;
			color: #db2777;
		}
		.question-badge--ubezpieczenia {
			background: #dbeafe;
			color: #2563eb;
		}
		.question-status {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 6px 12px;
			border-radius: 50px;
			font-size: 11px;
			font-weight: 700;
			flex-shrink: 0;
		}
		.question-status--answered {
			background: #dcfce7;
			color: #15803d;
		}
		.question-status--waiting {
			background: #fff7ed;
			color: #c2410c;
		}
		.question-status-dot {
			width: 8px;
			height: 8px;
			border-radius: 50%;
		}
		.question-status--answered .question-status-dot {
			background: #22c55e;
		}
		.question-status--waiting .question-status-dot {
			background: #f97316;
		}
		.question-title {
			font-size: 17px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 8px;
			line-height: 1.4;
			display: block;
		}
		.question-title:hover {
			color: var(--purple-primary);
		}
		.question-excerpt {
			font-size: 14px;
			color: var(--gray-500);
			line-height: 1.6;
			margin-bottom: 16px;
		}
		.question-meta {
			display: flex;
			align-items: center;
			gap: 16px;
			flex-wrap: wrap;
			padding-top: 14px;
			border-top: 1px solid var(--gray-100);
			font-size: 13px;
			color: var(--gray-400);
		}
		.question-meta-item {
			display: inline-flex;
			align-items: center;
			gap: 5px;
		}
		.question-meta-item strong {
			color: var(--gray-700);
			font-weight: 600;
		}
		.answers-count {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			font-weight: 700;
			color: var(--purple-primary);
		}

		/* ===== SIDEBAR ===== */
		.pytania-sidebar {
			display: flex;
			flex-direction: column;
			gap: 20px;
		}
		.sidebar-widget {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-lg);
			padding: 24px;
		}
		.sidebar-widget h3 {
			font-size: 16px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 16px;
		}
		.popular-questions-list {
			display: flex;
			flex-direction: column;
			gap: 12px;
		}
		.popular-question-item {
			display: flex;
			align-items: flex-start;
			gap: 10px;
			font-size: 13px;
			color: var(--gray-700);
			line-height: 1.4;
			padding-bottom: 12px;
			border-bottom: 1px solid var(--gray-100);
		}
		.popular-question-item:last-child {
			border-bottom: none;
			padding-bottom: 0;
		}
		.popular-question-num {
			flex-shrink: 0;
			width: 24px;
			height: 24px;
			border-radius: 50%;
			background: var(--gray-100);
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 11px;
			font-weight: 700;
			color: var(--gray-500);
		}
		.popular-question-item:hover {
			color: var(--purple-primary);
		}
		.expert-list {
			display: flex;
			flex-direction: column;
			gap: 14px;
		}
		.expert-item {
			display: flex;
			align-items: center;
			gap: 12px;
		}
		.expert-avatar {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			background: linear-gradient(135deg, var(--gray-200), var(--gray-300));
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 16px;
			color: var(--gray-500);
			flex-shrink: 0;
		}
		.expert-info {
			min-width: 0;
		}
		.expert-name {
			font-size: 13px;
			font-weight: 600;
			color: var(--gray-800);
		}
		.expert-specialty {
			font-size: 11px;
			color: var(--gray-400);
		}
		.expert-answers {
			margin-left: auto;
			font-size: 11px;
			font-weight: 700;
			color: var(--purple-primary);
			white-space: nowrap;
		}
		.sidebar-cta {
			background: linear-gradient(135deg, #1a0a3e, #4c1d95);
			border: none;
		}
		.sidebar-cta h3 {
			color: #fff;
		}
		.sidebar-cta p {
			font-size: 13px;
			color: rgba(255, 255, 255, 0.7);
			margin-bottom: 16px;
		}
		.sidebar-cta .btn-find-specialist {
			width: 100%;
			text-align: center;
			display: block;
			background: #fff;
			color: var(--purple-primary);
		}

		/* ===== RESPONSIVE ===== */
		@media (max-width: 1024px) {
			.pytania-grid {
				grid-template-columns: 1fr;
			}
			.pytania-sidebar {
				order: -1;
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 16px;
			}
			.sidebar-cta {
				grid-column: 1 / -1;
			}
		}
		@media (max-width: 768px) {
			.page-hero--pytania h1 {
				font-size: 28px;
			}
			.filter-chips {
				gap: 8px;
			}
			.filter-chip {
				padding: 8px 14px;
				font-size: 12px;
			}
			.pytania-sidebar {
				grid-template-columns: 1fr;
			}
			.question-header {
				flex-direction: column;
				gap: 10px;
			}
			.question-card {
				padding: 18px;
			}
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'pytania' ); ?>

<main>
	<!-- ===== PAGE HERO ===== -->
	<section class="page-hero--pytania">
		<div class="container">
			<nav class="breadcrumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
				<span class="sep">/</span>
				<span>Pytania i Odpowiedzi</span>
			</nav>
			<h1>Pytania i Odpowiedzi</h1>
			<p class="hero-subtitle">Zadaj pytanie spolecznosci ekspertow i uzyskaj sprawdzona odpowiedz. Pomagamy rozwiazywac codzienne problemy zwiazane z prawem, finansami, budownictwem i nieruchomosciami.</p>
			<a href="<?php echo esc_url( home_url( '/zadaj-pytanie/' ) ); ?>" class="btn-ask-question">Zadaj pytanie</a>
		</div>
	</section>

	<!-- ===== FILTER CHIPS ===== -->
	<section class="filters-section">
		<div class="container">
			<div class="filter-chips">
				<button class="filter-chip active" type="button">Wszystkie</button>
				<button class="filter-chip" type="button">Prawo</button>
				<button class="filter-chip" type="button">Finanse</button>
				<button class="filter-chip" type="button">Budownictwo</button>
				<button class="filter-chip" type="button">Nieruchomosci</button>
				<button class="filter-chip" type="button">Zdrowie</button>
			</div>
		</div>
	</section>

	<!-- ===== CONTENT GRID ===== -->
	<section class="container pytania-grid">
		<!-- Questions List -->
		<div class="questions-list">

			<!-- Question 1 -->
			<article class="question-card">
				<div class="question-header">
					<span class="question-badge question-badge--prawo">Prawo</span>
					<span class="question-status question-status--answered"><span class="question-status-dot"></span> Odpowiedziano</span>
				</div>
				<a href="<?php echo esc_url( home_url( '/pytania/wypowiedzenie-umowy-najmu/' ) ); ?>" class="question-title">Jak skutecznie wypowiedziec umowe najmu lokalu mieszkalnego?</a>
				<p class="question-excerpt">Wynajmuje mieszkanie na czas nieokreslony i chce wypowiedziec umowe. Najemca nie chce sie wyprowadzic. Jakie mam prawa jako wlasciciel i jakie sa ustawowe terminy wypowiedzenia?</p>
				<div class="question-meta">
					<span class="question-meta-item"><strong>Marek Wisniewski</strong></span>
					<span class="question-meta-item">12 czerwca 2026</span>
					<span class="question-meta-item answers-count">7 odpowiedzi</span>
				</div>
			</article>

			<!-- Question 2 -->
			<article class="question-card">
				<div class="question-header">
					<span class="question-badge question-badge--finanse">Finanse</span>
					<span class="question-status question-status--answered"><span class="question-status-dot"></span> Odpowiedziano</span>
				</div>
				<a href="<?php echo esc_url( home_url( '/pytania/kredyt-hipoteczny-stala-zmienna/' ) ); ?>" class="question-title">Stala czy zmienna stopa procentowa przy kredycie hipotecznym na 400 tys. zl?</a>
				<p class="question-excerpt">Planuje wziac kredyt na 25 lat. Oferty bankow roznia sie znaczaco. Czy w obecnej sytuacji gospodarczej lepiej wybrac stala stope na 5 lat, czy zmienna z nizsza rata poczatkowa?</p>
				<div class="question-meta">
					<span class="question-meta-item"><strong>Anna Kowalczyk</strong></span>
					<span class="question-meta-item">11 czerwca 2026</span>
					<span class="question-meta-item answers-count">12 odpowiedzi</span>
				</div>
			</article>

			<!-- Question 3 -->
			<article class="question-card">
				<div class="question-header">
					<span class="question-badge question-badge--budownictwo">Budownictwo</span>
					<span class="question-status question-status--waiting"><span class="question-status-dot"></span> Oczekuje</span>
				</div>
				<a href="<?php echo esc_url( home_url( '/pytania/pozwolenie-na-budowe-dzialka-rolna/' ) ); ?>" class="question-title">Czy moge uzyskac pozwolenie na budowe domu na dzialce rolnej klasy IV?</a>
				<p class="question-excerpt">Znalazlem atrakcyjna dzialke rolna klasy IV o powierzchni 30 arow. Chcialbym na niej wybudowac dom jednorodzinny. Jakie formalnosci trzeba zalatwic i ile trwa odrolnienie?</p>
				<div class="question-meta">
					<span class="question-meta-item"><strong>Piotr Zawadzki</strong></span>
					<span class="question-meta-item">10 czerwca 2026</span>
					<span class="question-meta-item answers-count">3 odpowiedzi</span>
				</div>
			</article>

			<!-- Question 4 -->
			<article class="question-card">
				<div class="question-header">
					<span class="question-badge question-badge--nieruchomosci">Nieruchomosci</span>
					<span class="question-status question-status--answered"><span class="question-status-dot"></span> Odpowiedziano</span>
				</div>
				<a href="<?php echo esc_url( home_url( '/pytania/wady-ukryte-mieszkanie-rynek-wtorny/' ) ); ?>" class="question-title">Kupilem mieszkanie z rynku wtornego i odkrylem wady ukryte - jakie mam roszczenia?</a>
				<p class="question-excerpt">Po dwoch miesiacach od zakupu okazalo sie, ze w mieszkaniu jest zagrzybienie ukryte pod panelami. Sprzedajacy nie informowal o tym problemie. Czy moge dochodzic obnizenia ceny lub odstapic od umowy?</p>
				<div class="question-meta">
					<span class="question-meta-item"><strong>Katarzyna Nowicka</strong></span>
					<span class="question-meta-item">9 czerwca 2026</span>
					<span class="question-meta-item answers-count">9 odpowiedzi</span>
				</div>
			</article>

			<!-- Question 5 -->
			<article class="question-card">
				<div class="question-header">
					<span class="question-badge question-badge--prawo">Prawo</span>
					<span class="question-status question-status--answered"><span class="question-status-dot"></span> Odpowiedziano</span>
				</div>
				<a href="<?php echo esc_url( home_url( '/pytania/dziedziczenie-ustawowe-a-testament/' ) ); ?>" class="question-title">Dziedziczenie ustawowe a testament - co jest wazniejsze i jak zabezpieczyc rodzine?</a>
				<p class="question-excerpt">Mam zone i dwoje dzieci z pierwszego malzenstwa. Chce, zeby mieszkanie przypadlo zonie, ale dzieci moglyby domagac sie zachowku. Jak najlepiej rozwiazac te kwestie prawnie?</p>
				<div class="question-meta">
					<span class="question-meta-item"><strong>Jan Kaczmarek</strong></span>
					<span class="question-meta-item">8 czerwca 2026</span>
					<span class="question-meta-item answers-count">6 odpowiedzi</span>
				</div>
			</article>

			<!-- Question 6 -->
			<article class="question-card">
				<div class="question-header">
					<span class="question-badge question-badge--budownictwo">Budownictwo</span>
					<span class="question-status question-status--answered"><span class="question-status-dot"></span> Odpowiedziano</span>
				</div>
				<a href="<?php echo esc_url( home_url( '/pytania/pompa-ciepla-dom-150m2/' ) ); ?>" class="question-title">Jaka pompa ciepla do domu 150 m2 z ogrzewaniem podlogowym - powietrzna czy gruntowa?</a>
				<p class="question-excerpt">Buduje dom jednorodzinny z bardzo dobra izolacja (U=0.15). Planuje ogrzewanie podlogowe w calym budynku. Rozwaham pompe powietrzna lub gruntowa. Jaka moc i jaki producent bedzie optymalny?</p>
				<div class="question-meta">
					<span class="question-meta-item"><strong>Tomasz Adamski</strong></span>
					<span class="question-meta-item">7 czerwca 2026</span>
					<span class="question-meta-item answers-count">11 odpowiedzi</span>
				</div>
			</article>

			<!-- Question 7 -->
			<article class="question-card">
				<div class="question-header">
					<span class="question-badge question-badge--finanse">Finanse</span>
					<span class="question-status question-status--waiting"><span class="question-status-dot"></span> Oczekuje</span>
				</div>
				<a href="<?php echo esc_url( home_url( '/pytania/dzialalnosc-nierejestrowana-limit/' ) ); ?>" class="question-title">Dzialalnosc nierejestrowana - jaki jest limit przychodow i jak prowadzic ewidencje?</a>
				<p class="question-excerpt">Chce legalnie sprzedawac rekodzielo w internecie. Ile moge zarobic bez rejestracji firmy w 2026 roku? Jak prawidlowo prowadzic ewidencje sprzedazy i kiedy musze zarejestrowac JDG?</p>
				<div class="question-meta">
					<span class="question-meta-item"><strong>Magdalena Szymanska</strong></span>
					<span class="question-meta-item">6 czerwca 2026</span>
					<span class="question-meta-item answers-count">4 odpowiedzi</span>
				</div>
			</article>

			<!-- Question 8 -->
			<article class="question-card">
				<div class="question-header">
					<span class="question-badge question-badge--nieruchomosci">Nieruchomosci</span>
					<span class="question-status question-status--waiting"><span class="question-status-dot"></span> Oczekuje</span>
				</div>
				<a href="<?php echo esc_url( home_url( '/pytania/podzial-dzialki-warunki-zabudowy/' ) ); ?>" class="question-title">Podzial dzialki budowlanej na dwie czesci - czy potrzebuje nowych warunkow zabudowy?</a>
				<p class="question-excerpt">Posiadam dzialke budowlana o powierzchni 1500 m2 z wydanymi warunkami zabudowy na jeden dom. Chce podzielic ja na dwie czesci i na kazdej postawic osobny budynek. Czy musza uzyskac nowa decyzje WZ?</p>
				<div class="question-meta">
					<span class="question-meta-item"><strong>Robert Wojciechowski</strong></span>
					<span class="question-meta-item">5 czerwca 2026</span>
					<span class="question-meta-item answers-count">2 odpowiedzi</span>
				</div>
			</article>

			<!-- Question 9 -->
			<article class="question-card">
				<div class="question-header">
					<span class="question-badge question-badge--zdrowie">Zdrowie</span>
					<span class="question-status question-status--answered"><span class="question-status-dot"></span> Odpowiedziano</span>
				</div>
				<a href="<?php echo esc_url( home_url( '/pytania/refundacja-lekow-nfz-2026/' ) ); ?>" class="question-title">Jak sprawdzic czy lek jest refundowany przez NFZ i jak uzyskac recepte na lek refundowany?</a>
				<p class="question-excerpt">Lekarz przepisal mi lek, ktory kosztuje 180 zl. Slyszalem, ze moze byc on refundowany. Gdzie sprawdzic liste lekow refundowanych i jak poprosic lekarza o recepte z niska odplatnoscia?</p>
				<div class="question-meta">
					<span class="question-meta-item"><strong>Ewa Jankowska</strong></span>
					<span class="question-meta-item">4 czerwca 2026</span>
					<span class="question-meta-item answers-count">5 odpowiedzi</span>
				</div>
			</article>

		</div>

		<!-- Sidebar -->
		<aside class="pytania-sidebar">

			<!-- Popularne pytania -->
			<div class="sidebar-widget">
				<h3>Popularne pytania</h3>
				<div class="popular-questions-list">
					<a href="<?php echo esc_url( home_url( '/pytania/alimenty-wysokosc-2026/' ) ); ?>" class="popular-question-item">
						<span class="popular-question-num">1</span>
						<span>Ile wynosza alimenty na jedno dziecko w 2026 roku?</span>
					</a>
					<a href="<?php echo esc_url( home_url( '/pytania/podatek-od-sprzedazy-mieszkania/' ) ); ?>" class="popular-question-item">
						<span class="popular-question-num">2</span>
						<span>Kiedy nie place podatku od sprzedazy mieszkania?</span>
					</a>
					<a href="<?php echo esc_url( home_url( '/pytania/kredyt-bez-wkladu-wlasnego/' ) ); ?>" class="popular-question-item">
						<span class="popular-question-num">3</span>
						<span>Czy mozna dostac kredyt hipoteczny bez wkladu wlasnego?</span>
					</a>
					<a href="<?php echo esc_url( home_url( '/pytania/odbiory-domu-jednorodzinnego/' ) ); ?>" class="popular-question-item">
						<span class="popular-question-num">4</span>
						<span>Jakie dokumenty sa potrzebne do odbioru domu?</span>
					</a>
					<a href="<?php echo esc_url( home_url( '/pytania/umowa-przedwstepna-zadatek/' ) ); ?>" class="popular-question-item">
						<span class="popular-question-num">5</span>
						<span>Roznica miedzy zadatkiem a zaliczka w umowie przedwstepnej</span>
					</a>
				</div>
			</div>

			<!-- Top eksperci -->
			<div class="sidebar-widget">
				<h3>Top eksperci</h3>
				<div class="expert-list">
					<div class="expert-item">
						<div class="expert-avatar">AK</div>
						<div class="expert-info">
							<div class="expert-name">adw. Anna Kowalska</div>
							<div class="expert-specialty">Prawo cywilne</div>
						</div>
						<span class="expert-answers">342 odp.</span>
					</div>
					<div class="expert-item">
						<div class="expert-avatar">PN</div>
						<div class="expert-info">
							<div class="expert-name">Piotr Nowak</div>
							<div class="expert-specialty">Doradztwo finansowe</div>
						</div>
						<span class="expert-answers">289 odp.</span>
					</div>
					<div class="expert-item">
						<div class="expert-avatar">MW</div>
						<div class="expert-info">
							<div class="expert-name">inz. Marek Wolski</div>
							<div class="expert-specialty">Budownictwo</div>
						</div>
						<span class="expert-answers">214 odp.</span>
					</div>
					<div class="expert-item">
						<div class="expert-avatar">KZ</div>
						<div class="expert-info">
							<div class="expert-name">Karolina Zawadzka</div>
							<div class="expert-specialty">Nieruchomosci</div>
						</div>
						<span class="expert-answers">198 odp.</span>
					</div>
					<div class="expert-item">
						<div class="expert-avatar">TL</div>
						<div class="expert-info">
							<div class="expert-name">dr Tomasz Lis</div>
							<div class="expert-specialty">Medycyna rodzinna</div>
						</div>
						<span class="expert-answers">176 odp.</span>
					</div>
				</div>
			</div>

			<!-- CTA -->
			<div class="sidebar-widget sidebar-cta">
				<h3>Potrzebujesz szybkiej odpowiedzi?</h3>
				<p>Skonsultuj swoja sprawe bezposrednio ze specjalista i otrzymaj indywidualna porade w 24 godziny.</p>
				<a href="<?php echo esc_url( home_url( '/specjalisci/' ) ); ?>" class="btn-find-specialist">Znajdz specjaliste</a>
			</div>

		</aside>
	</section>
</main>

<?php pp_pro_footer(); ?>

<?php wp_footer(); ?>
</body>
</html>
