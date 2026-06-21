<?php
/**
 * Template Name: Poradnik.PRO - Blog
 * Description: Blog i aktualności Poradnik.PRO.
 *
 * @package PearBlog
 */

defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/inc/poradnik-pro-shared.php';

$pp_blog_featured = array(
	'category' => 'Energia',
	'title'    => 'Jak wybrać pompę ciepła w 2026 roku — kompletny poradnik',
	'excerpt'  => 'Sprawdź, jakie parametry mają dziś największy wpływ na koszt ogrzewania, poziom dotacji i komfort użytkowania. Omawiamy dobór mocy, typy urządzeń i najczęstsze błędy inwestorów.',
	'date'     => '18 czerwca 2026',
	'read'     => '8 min',
	'icon'     => '♨️',
);

$pp_blog_articles = array(
	array(
		'category' => 'Prawo',
		'title'    => 'Nowe przepisy budowlane 2026 — co się zmienia?',
		'excerpt'  => 'Najważniejsze zmiany dla inwestorów, właścicieli domów i ekip wykonawczych: formalności, dokumentacja i terminy.',
		'date'     => '15 czerwca 2026',
		'read'     => '5 min',
		'icon'     => '⚖️',
	),
	array(
		'category' => 'Finanse',
		'title'    => 'Kredyt hipoteczny czy gotówkowy — porównanie',
		'excerpt'  => 'Które rozwiązanie lepiej sprawdza się przy remoncie, zakupie działki lub sfinansowaniu wkładu własnego?',
		'date'     => '12 czerwca 2026',
		'read'     => '6 min',
		'icon'     => '💳',
	),
	array(
		'category' => 'Energia',
		'title'    => 'Fotowoltaika — czy wciąż się opłaca?',
		'excerpt'  => 'Aktualne koszty instalacji, ceny energii i okres zwrotu dla domu jednorodzinnego w realiach 2026 roku.',
		'date'     => '9 czerwca 2026',
		'read'     => '7 min',
		'icon'     => '☀️',
	),
	array(
		'category' => 'Budownictwo',
		'title'    => 'Remont łazienki — koszty i planowanie',
		'excerpt'  => 'Od harmonogramu prac po wybór materiałów: ile dziś kosztuje remont łazienki i jak ograniczyć ryzyko opóźnień.',
		'date'     => '5 czerwca 2026',
		'read'     => '4 min',
		'icon'     => '🛁',
	),
);

$pp_blog_categories = array(
	array( 'name' => 'Energia', 'count' => 24 ),
	array( 'name' => 'Budownictwo', 'count' => 19 ),
	array( 'name' => 'Finanse', 'count' => 17 ),
	array( 'name' => 'Prawo', 'count' => 15 ),
	array( 'name' => 'Nieruchomości', 'count' => 12 ),
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
		.blog-nav-strip {
			background: #fff;
			border-bottom: 1px solid var(--gray-200);
		}
		.blog-nav-inline {
			display: flex;
			align-items: center;
			gap: 20px;
			height: 52px;
		}
		.blog-nav-link {
			position: relative;
			display: inline-flex;
			align-items: center;
			height: 100%;
			font-size: 14px;
			font-weight: 600;
			color: var(--purple-primary);
		}
		.blog-nav-link::after {
			content: '';
			position: absolute;
			left: 0;
			right: 0;
			bottom: 0;
			height: 3px;
			border-radius: 999px 999px 0 0;
			background: var(--purple-primary);
		}
		.blog-layout {
			display: grid;
			grid-template-columns: minmax(0, 1.85fr) minmax(280px, 0.85fr);
			gap: 36px;
			padding: 48px 0 64px;
			align-items: start;
		}
		.featured-post,
		.post-card,
		.sidebar-widget {
			background: #fff;
			border: 1px solid var(--gray-200);
			border-radius: var(--radius-lg);
			box-shadow: var(--shadow-sm);
		}
		.featured-post {
			overflow: hidden;
			margin-bottom: 28px;
		}
		.featured-media {
			display: flex;
			align-items: center;
			justify-content: center;
			height: 260px;
			background: linear-gradient(135deg, var(--purple-primary), var(--blue-accent));
			color: #fff;
			font-size: 72px;
			position: relative;
		}
		.featured-label {
			position: absolute;
			top: 18px;
			left: 18px;
			padding: 6px 12px;
			border-radius: 999px;
			background: var(--orange-cta);
			color: #fff;
			font-size: 11px;
			font-weight: 800;
			letter-spacing: 0.02em;
		}
		.featured-body {
			padding: 28px;
		}
		.post-category {
			display: inline-flex;
			padding: 5px 10px;
			border-radius: 999px;
			background: rgba(108, 43, 217, 0.1);
			color: var(--purple-primary);
			font-size: 11px;
			font-weight: 700;
			margin-bottom: 14px;
		}
		.featured-body h2 {
			font-size: 28px;
			line-height: 1.2;
			font-weight: 800;
			color: var(--gray-900);
			margin-bottom: 12px;
		}
		.featured-body p,
		.post-card-body p,
		.sidebar-widget p {
			color: var(--gray-600);
			line-height: 1.65;
		}
		.post-meta {
			display: flex;
			flex-wrap: wrap;
			gap: 16px;
			margin-top: 16px;
			font-size: 13px;
			color: var(--gray-500);
		}
		.posts-list {
			display: grid;
			gap: 18px;
		}
		.post-card {
			display: grid;
			grid-template-columns: 180px minmax(0, 1fr);
			overflow: hidden;
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}
		.post-card:hover,
		.featured-post:hover {
			transform: translateY(-2px);
			box-shadow: var(--shadow-md);
		}
		.post-card-media {
			display: flex;
			align-items: center;
			justify-content: center;
			min-height: 160px;
			background: linear-gradient(135deg, #ede9fe, #dbeafe);
			font-size: 42px;
		}
		.post-card-body {
			padding: 22px;
		}
		.post-card-body h3 {
			font-size: 20px;
			line-height: 1.3;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 10px;
		}
		.blog-sidebar {
			position: sticky;
			top: 88px;
			display: grid;
			gap: 20px;
		}
		.sidebar-widget {
			padding: 24px;
		}
		.sidebar-widget h3 {
			font-size: 18px;
			font-weight: 700;
			color: var(--gray-900);
			margin-bottom: 16px;
		}
		.category-list {
			display: grid;
			gap: 10px;
		}
		.category-link {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 11px 14px;
			border-radius: var(--radius-md);
			background: var(--gray-50);
			font-size: 14px;
			font-weight: 600;
			color: var(--gray-700);
			transition: background 0.2s ease, color 0.2s ease;
		}
		.category-link:hover {
			background: rgba(108, 43, 217, 0.1);
			color: var(--purple-primary);
		}
		.category-count {
			padding: 3px 8px;
			border-radius: 999px;
			background: #fff;
			font-size: 12px;
			color: var(--gray-500);
		}
		.newsletter-widget {
			background: linear-gradient(135deg, var(--purple-dark), var(--purple-primary));
			border: none;
			color: #fff;
		}
		.newsletter-widget h3,
		.newsletter-widget p {
			color: #fff;
		}
		.newsletter-form {
			display: grid;
			gap: 10px;
			margin-top: 18px;
		}
		.newsletter-input {
			width: 100%;
			padding: 13px 14px;
			border: 1px solid rgba(255, 255, 255, 0.18);
			border-radius: var(--radius-md);
			background: rgba(255, 255, 255, 0.12);
			color: #fff;
			font: inherit;
		}
		.newsletter-input::placeholder {
			color: rgba(255, 255, 255, 0.72);
		}
		.newsletter-input:focus {
			outline: none;
			border-color: rgba(255, 255, 255, 0.45);
		}
		.newsletter-btn {
			padding: 13px 16px;
			border-radius: var(--radius-md);
			background: var(--orange-cta);
			color: #fff;
			font-size: 14px;
			font-weight: 700;
			transition: background 0.2s ease;
		}
		.newsletter-btn:hover {
			background: var(--orange-hover);
		}
		@media (max-width: 960px) {
			.blog-layout {
				grid-template-columns: 1fr;
			}
			.blog-sidebar {
				position: static;
			}
		}
		@media (max-width: 768px) {
			.post-card {
				grid-template-columns: 1fr;
			}
			.featured-media {
				height: 220px;
				font-size: 60px;
			}
			.featured-body h2 {
				font-size: 24px;
			}
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'poradnik-pro-blog-page' ); ?>>
<?php wp_body_open(); pp_pro_header( '' ); ?>
<div class="blog-nav-strip">
	<div class="container">
		<div class="blog-nav-inline">
			<a class="blog-nav-link" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a>
		</div>
	</div>
</div>
<section class="page-hero">
	<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona główna</a>
			<span class="sep">/</span>
			<span>Blog</span>
		</div>
		<h1>Blog i aktualności Poradnik.PRO</h1>
		<p>Praktyczne analizy ekspertów, aktualne przepisy i wskazówki, które pomagają lepiej planować inwestycje, finanse i decyzje prawne.</p>
	</div>
</section>
<div class="container">
	<div class="blog-layout">
		<main>
			<article class="featured-post">
				<div class="featured-media">
					<span class="featured-label">Wyróżniony</span>
					<?php echo esc_html( $pp_blog_featured['icon'] ); ?>
				</div>
				<div class="featured-body">
					<span class="post-category"><?php echo esc_html( $pp_blog_featured['category'] ); ?></span>
					<h2><?php echo esc_html( $pp_blog_featured['title'] ); ?></h2>
					<p><?php echo esc_html( $pp_blog_featured['excerpt'] ); ?></p>
					<div class="post-meta">
						<span><?php echo esc_html( $pp_blog_featured['date'] ); ?></span>
						<span><?php echo esc_html( $pp_blog_featured['read'] ); ?> czytania</span>
						<span><?php echo esc_html( $pp_blog_featured['category'] ); ?></span>
					</div>
				</div>
			</article>
			<div class="posts-list">
				<?php foreach ( $pp_blog_articles as $article ) : ?>
					<article class="post-card">
						<div class="post-card-media"><?php echo esc_html( $article['icon'] ); ?></div>
						<div class="post-card-body">
							<span class="post-category"><?php echo esc_html( $article['category'] ); ?></span>
							<h3><?php echo esc_html( $article['title'] ); ?></h3>
							<p><?php echo esc_html( $article['excerpt'] ); ?></p>
							<div class="post-meta">
								<span><?php echo esc_html( $article['date'] ); ?></span>
								<span><?php echo esc_html( $article['read'] ); ?> czytania</span>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</main>
		<aside class="blog-sidebar">
			<section class="sidebar-widget">
				<h3>Popularne kategorie</h3>
				<div class="category-list">
					<?php foreach ( $pp_blog_categories as $category ) : ?>
						<a class="category-link" href="#">
							<span><?php echo esc_html( $category['name'] ); ?></span>
							<span class="category-count"><?php echo esc_html( number_format_i18n( $category['count'] ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
			<section class="sidebar-widget newsletter-widget">
				<h3>Newsletter</h3>
				<p>Raz w tygodniu wysyłamy wybór najważniejszych poradników, zmian w przepisach i analiz rynkowych.</p>
				<form class="newsletter-form">
					<input class="newsletter-input" type="email" placeholder="Twój adres e-mail" aria-label="Twój adres e-mail">
					<button class="newsletter-btn" type="submit">Zapisz mnie</button>
				</form>
			</section>
		</aside>
	</div>
</div>
<?php pp_pro_footer(); wp_footer(); ?>
</body>
</html>
