<?php
/**
 * Template Name: Poradnik.PRO - Profil Eksperta
 * Description: Expert profile page with reviews and specialties
 * @package PearBlog
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
        .profile-hero {
            background: #f3f4f6;
            padding: 40px 0 28px;
            border-bottom: 1px solid var(--gray-200);
        }

        .profile-hero .breadcrumb {
            margin-bottom: 24px;
            color: var(--gray-500);
        }

        .profile-card {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 28px;
            align-items: center;
        }

        .avatar-lg {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dbe1ea, #cbd5e1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-500);
            font-size: 56px;
            font-weight: 700;
        }

        .profile-meta h1 {
            font-size: 40px;
            line-height: 1.1;
            margin-bottom: 10px;
        }

        .role-row,
        .meta-row,
        .cta-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .role-row {
            margin-bottom: 14px;
            color: var(--gray-700);
            font-size: 18px;
            font-weight: 600;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 12px;
            border-radius: 999px;
            background: rgba(108, 43, 217, 0.12);
            color: var(--purple-primary);
            font-size: 12px;
            font-weight: 700;
        }

        .meta-row {
            margin-bottom: 22px;
            color: var(--gray-600);
            font-size: 15px;
        }

        .rating-stars {
            color: var(--yellow-accent);
            letter-spacing: 1px;
        }

        .meta-divider {
            color: var(--gray-300);
        }

        .cta-row {
            gap: 14px;
        }

        .btn-primary,
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 22px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--purple-primary);
            color: #fff;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background: var(--purple-dark);
        }

        .btn-secondary {
            color: var(--purple-primary);
            border: 1px solid var(--purple-primary);
            background: #fff;
        }

        .btn-secondary:hover {
            background: #f7f1ff;
        }

        .tabs-bar {
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
        }

        .tabs-nav {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 0;
        }

        .tab-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            min-height: 64px;
            padding: 0 18px;
            color: var(--gray-500);
            font-size: 15px;
            font-weight: 600;
            white-space: nowrap;
        }

        .tab-link.active {
            color: var(--purple-primary);
        }

        .tab-link.active::after {
            content: '';
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 0;
            height: 3px;
            border-radius: 999px;
            background: var(--purple-primary);
        }

        .page-section {
            padding: 40px 0;
        }

        .about-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.65fr) minmax(300px, 0.95fr);
            gap: 28px;
            align-items: start;
        }

        .card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            padding: 32px;
            box-shadow: var(--shadow-sm);
        }

        .card h2,
        .card h3 {
            margin-bottom: 18px;
            font-size: 24px;
            line-height: 1.2;
        }

        .about-copy {
            color: var(--gray-700);
            font-size: 16px;
            margin-bottom: 24px;
        }

        .specialties-title {
            margin-bottom: 14px;
            font-size: 17px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .specialties-list {
            padding-left: 20px;
            list-style: disc;
        }

        .specialties-list li {
            margin-bottom: 12px;
            color: var(--gray-700);
        }

        .specialties-list li::marker {
            color: var(--purple-primary);
        }

        .stats-list {
            display: grid;
            gap: 14px;
        }

        .stat-box {
            padding: 18px 20px;
            border-radius: var(--radius-lg);
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
        }

        .stat-label {
            display: block;
            margin-bottom: 4px;
            color: var(--gray-500);
            font-size: 13px;
            font-weight: 600;
        }

        .stat-value {
            color: var(--gray-900);
            font-size: 24px;
            font-weight: 800;
            line-height: 1.1;
        }

        .reviews-section-title {
            margin-bottom: 24px;
            font-size: 30px;
            font-weight: 800;
        }

        .reviews-summary {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 28px;
            margin-bottom: 28px;
            align-items: stretch;
        }

        .rating-overview {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .rating-overview .score {
            font-size: 64px;
            font-weight: 800;
            line-height: 1;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .rating-overview .stars {
            color: var(--yellow-accent);
            font-size: 22px;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .rating-overview .caption {
            color: var(--gray-500);
            font-size: 14px;
            font-weight: 500;
        }

        .review-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .review-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .review-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .reviewer {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-sm {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e5e7eb, #cbd5e1);
            color: var(--gray-600);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .reviewer strong {
            display: block;
            font-size: 15px;
        }

        .review-date {
            color: var(--gray-500);
            font-size: 12px;
        }

        .review-rating {
            color: var(--yellow-accent);
            font-size: 14px;
            white-space: nowrap;
        }

        .review-card p {
            color: var(--gray-700);
            font-size: 15px;
        }

        @media (max-width: 960px) {
            .profile-card,
            .about-grid,
            .reviews-summary,
            .review-grid {
                grid-template-columns: 1fr;
            }

            .profile-card {
                text-align: center;
            }

            .avatar-lg {
                margin: 0 auto;
            }

            .role-row,
            .meta-row,
            .cta-row {
                justify-content: center;
            }

            .profile-meta h1 {
                font-size: 34px;
            }
        }

        @media (max-width: 640px) {
            .profile-hero {
                padding-top: 28px;
            }

            .profile-meta h1 {
                font-size: 30px;
            }

            .role-row {
                font-size: 16px;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
            }

            .cta-row {
                flex-direction: column;
            }

            .card {
                padding: 24px;
            }

            .reviews-section-title {
                font-size: 26px;
            }

            .rating-overview .score {
                font-size: 52px;
            }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); pp_pro_header( 'specjalisci' ); ?>

<section class="profile-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona główna</a>
            <span class="sep">/</span>
            <a href="<?php echo esc_url( home_url( '/specjalisci/' ) ); ?>">Specjaliści</a>
            <span class="sep">/</span>
            <span>Jan Kowalski</span>
        </div>

        <div class="profile-card">
            <div class="avatar-lg">JK</div>
            <div class="profile-meta">
                <h1>Jan Kowalski</h1>
                <div class="role-row">
                    <span>Doradca nieruchomości</span>
                    <span class="badge">Zweryfikowany</span>
                    <span class="badge">Poradnik</span>
                </div>
                <div class="meta-row">
                    <span><strong>4.9</strong></span>
                    <span class="rating-stars">★★★★★</span>
                    <span>(532 opinie)</span>
                    <span class="meta-divider">•</span>
                    <span>Warszawa</span>
                </div>
                <div class="cta-row">
                    <a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>" class="btn-secondary">Zapytaj</a>
                    <a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>" class="btn-primary">Wyślij wiadomość</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="tabs-bar">
    <div class="container">
        <nav class="tabs-nav" aria-label="Sekcje profilu eksperta">
            <a href="#o-mnie" class="tab-link active">O mnie</a>
            <a href="#specjalizacja" class="tab-link">Specjalizacja</a>
            <a href="#opinie" class="tab-link">Opinie (532)</a>
            <a href="#odpowiedzi" class="tab-link">Odpowiedzi (136)</a>
            <a href="#artykuly" class="tab-link">Artykuły (24)</a>
        </nav>
    </div>
</section>

<main>
    <section class="page-section" id="o-mnie">
        <div class="container">
            <div class="about-grid">
                <article class="card">
                    <h2>O mnie</h2>
                    <p class="about-copy">Od 10 lat pomagam w sprzedaży, zakupie i wycenie nieruchomości. Ponad 1000 zadowolonych klientów.</p>
                    <div id="specjalizacja">
                        <div class="specialties-title">Specjalizuję się w:</div>
                        <ul class="specialties-list">
                            <li>sprzedaż działek budowlanych</li>
                            <li>wycena nieruchomości</li>
                            <li>doradztwo inwestycyjne</li>
                            <li>analiza dokumentów</li>
                        </ul>
                    </div>
                </article>

                <aside class="card">
                    <h3>Doświadczenie</h3>
                    <div class="stats-list">
                        <div class="stat-box">
                            <span class="stat-label">Staż na rynku</span>
                            <span class="stat-value">10+ lat</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Przetworzone transakcje</span>
                            <span class="stat-value">1200+</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Średni czas odpowiedzi</span>
                            <span class="stat-value">2h</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section class="page-section" id="opinie">
        <div class="container">
            <h2 class="reviews-section-title">Opinie klientów</h2>
            <div class="reviews-summary">
                <div class="card rating-overview">
                    <div class="score">4.9</div>
                    <div class="stars">★★★★★</div>
                    <div class="caption">Na podstawie 532 opinii</div>
                </div>
                <div class="review-grid">
                    <article class="review-card">
                        <div class="review-head">
                            <div class="reviewer">
                                <div class="avatar-sm">AK</div>
                                <div>
                                    <strong>Anna Kurek</strong>
                                    <div class="review-date">12 maja 2026</div>
                                </div>
                            </div>
                            <div class="review-rating">★★★★★</div>
                        </div>
                        <p>Pełen profesjonalizm i bardzo konkretne wskazówki. Dzięki pomocy pana Jana zamknęliśmy sprzedaż mieszkania szybciej, niż zakładaliśmy.</p>
                    </article>

                    <article class="review-card">
                        <div class="review-head">
                            <div class="reviewer">
                                <div class="avatar-sm">MP</div>
                                <div>
                                    <strong>Michał Pakuła</strong>
                                    <div class="review-date">28 kwietnia 2026</div>
                                </div>
                            </div>
                            <div class="review-rating">★★★★★</div>
                        </div>
                        <p>Świetna analiza dokumentów i szybka odpowiedź na każde pytanie. Czułem się bezpiecznie przy zakupie działki inwestycyjnej.</p>
                    </article>

                    <article class="review-card">
                        <div class="review-head">
                            <div class="reviewer">
                                <div class="avatar-sm">ES</div>
                                <div>
                                    <strong>Emilia Sowa</strong>
                                    <div class="review-date">3 kwietnia 2026</div>
                                </div>
                            </div>
                            <div class="review-rating">★★★★★</div>
                        </div>
                        <p>Doceniam transparentność, dobrą komunikację i realne wsparcie przy wycenie. Bardzo merytoryczna i spokojna współpraca.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>
</main>

<?php pp_pro_footer(); wp_footer(); ?>
</body>
</html>
