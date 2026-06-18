<?php
/**
 * Template Name: Poradnik.pro - Specjalisci
 *
 * Specialists directory page for Poradnik.pro. Uses the shared purple Inter-based
 * design system. Fully integrated with WordPress via wp_head()/wp_footer().
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/poradnik-pro-shared.php';

$pp_specialists = array(
	array(
		'initials'  => 'MK',
		'name'      => 'Mateusz Kowalczyk',
		'specialty' => 'Adwokat - prawo cywilne',
		'category'  => 'Prawo',
		'rating'    => '4.9',
		'reviews'   => 347,
		'location'  => 'Warszawa',
		'answers'   => 512,
		'slug'      => 'mateusz-kowalczyk',
	),
	array(
		'initials'  => 'AW',
		'name'      => 'Anna Wojciechowska',
		'specialty' => 'Doradca finansowy',
		'category'  => 'Finanse',
		'rating'    => '4.8',
		'reviews'   => 289,
		'location'  => 'Krakow',
		'answers'   => 431,
		'slug'      => 'anna-wojciechowska',
	),
	array(
		'initials'  => 'TZ',
		'name'      => 'Tomasz Zawadzki',
		'specialty' => 'Architekt budowlany',
		'category'  => 'Budownictwo',
		'rating'    => '4.9',
		'reviews'   => 198,
		'location'  => 'Wroclaw',
		'answers'   => 276,
		'slug'      => 'tomasz-zawadzki',
	),
	array(
		'initials'  => 'KN',
		'name'      => 'Katarzyna Nowicka',
		'specialty' => 'Posrednik nieruchomosci',
		'category'  => 'Nieruchomosci',
		'rating'    => '4.7',
		'reviews'   => 421,
		'location'  => 'Poznan',
		'answers'   => 389,
		'slug'      => 'katarzyna-nowicka',
	),
	array(
		'initials'  => 'PL',
		'name'      => 'Piotr Lewandowski',
		'specialty' => 'Lekarz internista',
		'category'  => 'Zdrowie',
		'rating'    => '4.9',
		'reviews'   => 562,
		'location'  => 'Gdansk',
		'answers'   => 648,
		'slug'      => 'piotr-lewandowski',
	),
	array(
		'initials'  => 'JM',
		'name'      => 'Jakub Mazurek',
		'specialty' => 'Programista Full Stack',
		'category'  => 'IT',
		'rating'    => '4.8',
		'reviews'   => 156,
		'location'  => 'Warszawa',
		'answers'   => 734,
		'slug'      => 'jakub-mazurek',
	),
	array(
		'initials'  => 'EK',
		'name'      => 'Elzbieta Kaminska',
		'specialty' => 'Radca prawny - prawo pracy',
		'category'  => 'Prawo',
		'rating'    => '4.8',
		'reviews'   => 312,
		'location'  => 'Lodz',
		'answers'   => 467,
		'slug'      => 'elzbieta-kaminska',
	),
	array(
		'initials'  => 'RD',
		'name'      => 'Robert Dabrowski',
		'specialty' => 'Doradca kredytowy',
		'category'  => 'Finanse',
		'rating'    => '4.7',
		'reviews'   => 245,
		'location'  => 'Katowice',
		'answers'   => 321,
		'slug'      => 'robert-dabrowski',
	),
	array(
		'initials'  => 'MS',
		'name'      => 'Magdalena Szymanska',
		'specialty' => 'Kierownik budowy',
		'category'  => 'Budownictwo',
		'rating'    => '4.6',
		'reviews'   => 178,
		'location'  => 'Szczecin',
		'answers'   => 203,
		'slug'      => 'magdalena-szymanska',
	),
	array(
		'initials'  => 'WP',
		'name'      => 'Wojciech Pawlak',
		'specialty' => 'Specjalista cyberbezpieczenstwa',
		'category'  => 'IT',
		'rating'    => '4.9',
		'reviews'   => 134,
		'location'  => 'Wroclaw',
		'answers'   => 289,
		'slug'      => 'wojciech-pawlak',
	),
	array(
		'initials'  => 'AG',
		'name'      => 'Agnieszka Grabowska',
		'specialty' => 'Dietetyk kliniczny',
		'category'  => 'Zdrowie',
		'rating'    => '4.8',
		'reviews'   => 387,
		'location'  => 'Krakow',
		'answers'   => 512,
		'slug'      => 'agnieszka-grabowska',
	),
	array(
		'initials'  => 'DW',
		'name'      => 'Dariusz Wisniewski',
		'specialty' => 'Rzeczoznawca majatkowy',
		'category'  => 'Nieruchomosci',
		'rating'    => '4.7',
		'reviews'   => 256,
		'location'  => 'Lublin',
		'answers'   => 198,
		'slug'      => 'dariusz-wisniewski',
	),
);

$pp_categories = array( 'Prawo', 'Finanse', 'Budownictwo', 'Nieruchomosci', 'Zdrowie', 'IT' );

$pp_avatar_colors = array(
	'Prawo'         => '#6c2bd9',
	'Finanse'       => '#3b82f6',
	'Budownictwo'   => '#f97316',
	'Nieruchomosci' => '#10b981',
	'Zdrowie'       => '#ef4444',
	'IT'            => '#8b5cf6',
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
    <?php wp_head(); ?>
    <?php pp_pro_shared_styles(); ?>
    <style>
        /* ===== PAGE HERO ===== */
        .page-hero {
            background: linear-gradient(135deg, #ede9fe 0%, #e0e7ff 100%);
            padding: 48px 0 56px;
        }
        .page-hero h1 {
            font-size: 36px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        .page-hero .subtitle {
            font-size: 16px;
            color: var(--gray-600);
            max-width: 560px;
            line-height: 1.6;
        }

        /* ===== SEARCH & FILTER SECTION ===== */
        .search-filter-section {
            padding: 32px 0 0;
        }
        .search-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        .search-input {
            flex: 1;
            height: 52px;
            padding: 0 20px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 15px;
            color: var(--gray-900);
            background: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-input:focus {
            border-color: var(--purple-primary);
            box-shadow: 0 0 0 3px rgba(108, 43, 217, 0.1);
        }
        .search-input::placeholder {
            color: var(--gray-400);
        }
        .search-btn {
            height: 52px;
            padding: 0 28px;
            background: var(--purple-primary);
            color: #fff;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .search-btn:hover {
            background: var(--purple-dark);
        }

        .filter-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }
        .filter-chip {
            display: inline-flex;
            align-items: center;
            height: 38px;
            padding: 0 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--gray-200);
            background: #fff;
            color: var(--gray-600);
            transition: all 0.2s;
        }
        .filter-chip:hover,
        .filter-chip.active {
            border-color: var(--purple-primary);
            background: #f3e8ff;
            color: var(--purple-primary);
        }

        /* ===== SPECIALISTS GRID ===== */
        .specialists-section {
            padding: 0 0 64px;
        }
        .specialists-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .specialist-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .specialist-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .avatar-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            color: #fff;
            flex-shrink: 0;
        }
        .card-info h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 2px;
        }
        .card-specialty {
            font-size: 13px;
            font-weight: 600;
            color: var(--purple-primary);
        }
        .card-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .card-rating {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        .card-rating .stars {
            color: #f59e0b;
            font-weight: 700;
        }
        .card-rating .rating-num {
            color: var(--gray-900);
            font-weight: 600;
        }
        .card-rating .reviews-count {
            color: var(--gray-400);
            font-size: 13px;
        }
        .card-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            color: var(--gray-500);
        }
        .card-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .card-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 44px;
            border-radius: 50px;
            border: 2px solid var(--purple-primary);
            color: var(--purple-primary);
            font-size: 14px;
            font-weight: 600;
            background: transparent;
            margin-top: auto;
            transition: all 0.2s;
        }
        .card-btn:hover {
            background: var(--purple-primary);
            color: #fff;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .specialists-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 640px) {
            .specialists-grid {
                grid-template-columns: 1fr;
            }
            .search-bar {
                flex-direction: column;
            }
            .page-hero {
                padding: 32px 0 40px;
            }
            .page-hero h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'specjalisci' ); ?>

<main>
    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
                <span class="sep">/</span>
                <span>Specjalisci</span>
            </nav>
            <h1>Specjalisci</h1>
            <p class="subtitle">Znajdz zweryfikowanych ekspertow z roznych dziedzin. Porownaj oceny, przeczytaj opinie i wybierz najlepszego specjaliste dla siebie.</p>
        </div>
    </section>

    <!-- Search & Filter -->
    <section class="search-filter-section">
        <div class="container">
            <div class="search-bar">
                <input class="search-input" type="text" placeholder="Szukaj specjalisty po imieniu, specjalizacji lub lokalizacji..." aria-label="Szukaj specjalisty">
                <button class="search-btn" type="button">Szukaj</button>
            </div>
            <div class="filter-chips" role="group" aria-label="Filtruj wedlug kategorii">
                <button class="filter-chip active" type="button">Wszystkie</button>
                <?php foreach ( $pp_categories as $cat ) : ?>
                    <button class="filter-chip" type="button"><?php echo esc_html( $cat ); ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Specialists Grid -->
    <section class="specialists-section">
        <div class="container">
            <div class="specialists-grid">
                <?php foreach ( $pp_specialists as $specialist ) :
                    $avatar_bg = isset( $pp_avatar_colors[ $specialist['category'] ] ) ? $pp_avatar_colors[ $specialist['category'] ] : '#6c2bd9';
                    $profile_url = esc_url( home_url( '/specjalista/' . $specialist['slug'] . '/' ) );
                    $stars_full = floor( (float) $specialist['rating'] );
                    $star_icons = str_repeat( "\xe2\x98\x85", $stars_full );
                ?>
                <article class="specialist-card">
                    <div class="card-header">
                        <div class="avatar-circle" style="background: <?php echo esc_attr( $avatar_bg ); ?>;">
                            <?php echo esc_html( $specialist['initials'] ); ?>
                        </div>
                        <div class="card-info">
                            <h3><?php echo esc_html( $specialist['name'] ); ?></h3>
                            <span class="card-specialty"><?php echo esc_html( $specialist['specialty'] ); ?></span>
                        </div>
                    </div>
                    <div class="card-details">
                        <div class="card-rating">
                            <span class="stars"><?php echo $star_icons; ?></span>
                            <span class="rating-num"><?php echo esc_html( $specialist['rating'] ); ?></span>
                            <span class="reviews-count">(<?php echo esc_html( $specialist['reviews'] ); ?> opinii)</span>
                        </div>
                        <div class="card-meta">
                            <span><?php echo esc_html( $specialist['location'] ); ?></span>
                            <span><?php echo esc_html( $specialist['answers'] ); ?> odpowiedzi</span>
                        </div>
                    </div>
                    <a class="card-btn" href="<?php echo $profile_url; ?>">Zobacz profil</a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php pp_pro_footer(); ?>

<?php wp_footer(); ?>
</body>
</html>
