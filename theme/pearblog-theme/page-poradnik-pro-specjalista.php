<?php
/**
 * Template Name: Poradnik.PRO - Profil Specjalisty
 * Description: Expert profile page (/specjalista/{slug})
 * @package PearBlog
 */
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/inc/poradnik-pro-shared.php';

$expert_name = get_the_title() ?: 'Mec. Anna Kowalska';
$reviews     = array(
	array( 'Profesjonalna i rzetelna pomoc. Polecam każdemu kto potrzebuje porady prawnej.', 'Marcin W.', '5/5', '3 dni temu' ),
	array( 'Szybka i konkretna odpowiedź. Sprawa spadkowa rozwiązana bez problemów.', 'Katarzyna M.', '5/5', '1 tydzień temu' ),
	array( 'Bardzo pomocna, wyjaśniła wszystkie zawiłości prawne prostym językiem.', 'Tomasz K.', '5/5', '2 tygodnie temu' ),
);
$specs       = array( 'Prawo cywilne', 'Prawo spadkowe', 'Prawo rodzinne', 'Umowy', 'Nieruchomości', 'Odszkodowania' );
$articles    = array(
	array(
		'title' => 'Jak napisać testament — krok po kroku',
		'meta'  => '12 min czytania · 2 450 wyświetleń',
		'url'   => home_url( '/poradnik/jak-napisac-testament/' ),
	),
	array(
		'title' => 'Spadek — co musisz wiedzieć',
		'meta'  => '10 min czytania · 1 890 wyświetleń',
		'url'   => home_url( '/poradnik/spadek-poradnik/' ),
	),
);
$answers     = array(
	array( 'Czy mogę odziedziczyć długi po rodzicach?', '#' ),
	array( 'Jak wypisać się z testamentu?', '#' ),
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
        body {
            background: linear-gradient(180deg, #ffffff 0%, var(--gray-50) 100%);
            color: var(--gray-900);
        }

        .profile-page {
            min-height: 100vh;
        }

        .profile-main {
            padding: 40px 0 72px;
        }

        .profile-layout {
            max-width: 880px;
            margin: 0 auto;
        }

        .profile-card,
        .content-card,
        .contact-card,
        .lead-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
        }

        .profile-card {
            padding: 32px;
            margin-bottom: 32px;
        }

        .profile-head {
            display: flex;
            align-items: flex-start;
            gap: 24px;
        }

        .profile-avatar {
            width: 96px;
            height: 96px;
            border-radius: 28px;
            background: rgba(108, 43, 217, 0.1);
            color: var(--purple-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .profile-title-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 6px;
        }

        .profile-title-row h1 {
            font-size: 34px;
            line-height: 1.15;
            font-weight: 800;
            color: var(--gray-900);
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
            font-size: 12px;
            font-weight: 700;
        }

        .profile-subtitle {
            font-size: 15px;
            color: var(--gray-500);
        }

        .stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-top: 20px;
        }

        .stat-item {
            font-size: 14px;
            color: var(--gray-500);
        }

        .stat-item strong {
            color: var(--gray-900);
            font-size: 16px;
            font-weight: 700;
        }

        .stat-item .rating-value {
            color: var(--yellow-accent);
            font-size: 24px;
            line-height: 1;
        }

        .section-block {
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 16px;
        }

        .review-list,
        .link-list {
            display: grid;
            gap: 16px;
        }

        .review-card,
        .link-card {
            padding: 22px 24px;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        }

        .review-card:hover,
        .link-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: rgba(108, 43, 217, 0.2);
        }

        .review-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 10px;
        }

        .review-score {
            color: var(--yellow-accent);
            font-weight: 700;
        }

        .review-card p {
            font-size: 15px;
            line-height: 1.7;
            color: var(--gray-700);
        }

        .review-author {
            margin-top: 12px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-500);
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 0 16px;
            border-radius: 999px;
            border: 1px solid var(--gray-200);
            background: var(--gray-50);
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .about-card,
        .contact-card,
        .lead-card {
            padding: 28px;
        }

        .about-card p {
            font-size: 15px;
            line-height: 1.8;
            color: var(--gray-600);
        }

        .about-card p + p {
            margin-top: 12px;
        }

        .link-card {
            display: block;
            color: inherit;
            text-decoration: none;
        }

        .link-card:hover .link-title {
            color: var(--purple-primary);
        }

        .link-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-900);
            transition: color 0.2s;
        }

        .link-card p,
        .link-meta {
            margin-top: 6px;
            font-size: 13px;
            color: var(--gray-500);
            line-height: 1.6;
        }

        .contact-card {
            background: linear-gradient(180deg, #faf7ff 0%, #ffffff 100%);
        }

        .contact-form {
            display: grid;
            gap: 18px;
        }

        .form-field label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-field input,
        .form-field textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            background: #fff;
            color: var(--gray-900);
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            resize: vertical;
        }

        .form-field input:focus,
        .form-field textarea:focus {
            outline: none;
            border-color: var(--purple-primary);
            box-shadow: 0 0 0 3px rgba(108, 43, 217, 0.12);
        }

        .form-field input::placeholder,
        .form-field textarea::placeholder {
            color: var(--gray-400);
        }

        .primary-button,
        .lead-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 13px 24px;
            border-radius: 999px;
            background: var(--purple-primary);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.2s, transform 0.2s;
        }

        .primary-button:hover,
        .lead-button:hover {
            background: var(--purple-dark);
            transform: translateY(-1px);
        }

        .lead-card {
            text-align: center;
            background: linear-gradient(135deg, rgba(108, 43, 217, 0.12) 0%, rgba(139, 92, 246, 0.12) 100%);
        }

        .lead-card h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .lead-card p {
            font-size: 15px;
            color: var(--gray-600);
            margin-bottom: 18px;
        }

        @media (max-width: 768px) {
            .profile-main {
                padding: 32px 0 56px;
            }

            .profile-card,
            .about-card,
            .contact-card,
            .lead-card,
            .review-card,
            .link-card {
                padding: 22px;
            }

            .profile-head {
                flex-direction: column;
            }

            .profile-title-row h1 {
                font-size: 28px;
            }

            .section-title,
            .lead-card h2 {
                font-size: 22px;
            }

            .stats-row {
                gap: 16px;
            }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); pp_pro_header( 'specjalisci' ); ?>
<div class="profile-page">
    <main class="profile-main">
        <div class="container">
            <div class="profile-layout">
                <section class="profile-card section-block">
                    <div class="profile-head">
                        <div class="profile-avatar"><?php echo esc_html( mb_substr( $expert_name, 0, 1 ) ); ?></div>
                        <div class="profile-content">
                            <div class="profile-title-row">
                                <h1><?php echo esc_html( $expert_name ); ?></h1>
                                <span class="verified-badge">Zweryfikowany</span>
                            </div>
                            <p class="profile-subtitle">Prawo cywilne · Prawo spadkowe · Warszawa</p>
                            <div class="stats-row" aria-label="Statystyki specjalisty">
                                <div class="stat-item"><strong class="rating-value">★ 4.9</strong> / 5</div>
                                <div class="stat-item"><strong>312</strong> odpowiedzi</div>
                                <div class="stat-item"><strong>98%</strong> poleca</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section-block">
                    <h2 class="section-title">Opinie (48)</h2>
                    <div class="review-list">
                        <?php foreach ( $reviews as $review ) : ?>
                            <article class="content-card review-card">
                                <div class="review-meta">
                                    <span class="review-score"><?php echo esc_html( $review[2] ); ?></span>
                                    <span>·</span>
                                    <span><?php echo esc_html( $review[3] ); ?></span>
                                </div>
                                <p>„<?php echo esc_html( $review[0] ); ?>”</p>
                                <div class="review-author">— <?php echo esc_html( $review[1] ); ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section-block">
                    <h2 class="section-title">Specjalizacje</h2>
                    <div class="chips">
                        <?php foreach ( $specs as $spec ) : ?>
                            <span class="chip"><?php echo esc_html( $spec ); ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section-block">
                    <h2 class="section-title">O ekspercie</h2>
                    <div class="content-card about-card">
                        <p>Adwokat z 15-letnim doświadczeniem. Specjalizuję się w prawie cywilnym, spadkowym i rodzinnym. Ukończyłam Wydział Prawa i Administracji Uniwersytetu Warszawskiego. Członek Okręgowej Rady Adwokackiej w Warszawie.</p>
                        <p>Na Poradnik.PRO udzielam porad prawnych od 2024 roku. Odpowiedziałam na ponad 300 pytań użytkowników z oceną 4.9/5.</p>
                    </div>
                </section>

                <section class="section-block">
                    <h2 class="section-title">Artykuły</h2>
                    <div class="link-list">
                        <?php foreach ( $articles as $article ) : ?>
                            <a href="<?php echo esc_url( $article['url'] ); ?>" class="content-card link-card">
                                <h3 class="link-title"><?php echo esc_html( $article['title'] ); ?></h3>
                                <div class="link-meta"><?php echo esc_html( $article['meta'] ); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section-block">
                    <h2 class="section-title">Ostatnie odpowiedzi</h2>
                    <div class="link-list">
                        <?php foreach ( $answers as $answer ) : ?>
                            <a href="<?php echo esc_url( $answer[1] ); ?>" class="content-card link-card">
                                <p>Odpowiedź na:</p>
                                <h3 class="link-title"><?php echo esc_html( $answer[0] ); ?></h3>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section-block">
                    <div class="contact-card">
                        <h2 class="section-title">Wyślij wiadomość</h2>
                        <form class="contact-form">
                            <div class="form-field">
                                <label for="temat">Temat</label>
                                <input type="text" id="temat" name="temat" required placeholder="Czego dotyczy Twoje pytanie?">
                            </div>
                            <div class="form-field">
                                <label for="wiadomosc">Wiadomość</label>
                                <textarea id="wiadomosc" name="wiadomosc" required rows="4" placeholder="Opisz swoją sytuację…"></textarea>
                            </div>
                            <div>
                                <button type="submit" class="primary-button">Wyślij wiadomość</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="section-block">
                    <div class="lead-card">
                        <h2>Potrzebujesz pilnej porady?</h2>
                        <p>Zadaj pytanie publicznie — eksperci odpowiedzą w ciągu 24h.</p>
                        <a href="<?php echo esc_url( home_url( '/zadaj-pytanie/' ) ); ?>" class="lead-button">Zadaj pytanie →</a>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>
<?php pp_pro_footer(); wp_footer(); ?>
</body>
</html>
