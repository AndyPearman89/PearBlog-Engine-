<?php
/**
 * Template Name: Poradnik.pro - Miasto
 * Description: Standalone city landing page for Poradnik.pro.
 */

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$fallbackCategories = [
    'prawo' => 'Prawo',
    'finanse' => 'Finanse',
    'nieruchomosci' => 'Nieruchomości',
    'budownictwo' => 'Budownictwo',
    'energia' => 'Energia',
    'zdrowie' => 'Zdrowie',
    'edukacja' => 'Edukacja',
    'motoryzacja' => 'Motoryzacja',
    'technologia' => 'Technologia',
    'dom-i-ogrod' => 'Dom i ogród',
];
$categories = $fallbackCategories;

$citySlug = 'warszawa';
$cityName = 'Warszawa';
$categorySlug = '';
$categoryName = '';

if (class_exists('PearBlog_Poradnik_Pro_Routing')) {
    $detectedCitySlug = PearBlog_Poradnik_Pro_Routing::get_current_city();
    if (!empty($detectedCitySlug)) {
        $citySlug = $detectedCitySlug;
    }

    $cityName = PearBlog_Poradnik_Pro_Routing::get_city_name($citySlug);
    if ($cityName === '') {
        $cityName = ucfirst(str_replace('-', ' ', $citySlug));
    }

    $detectedCategorySlug = PearBlog_Poradnik_Pro_Routing::get_current_category();
    if (!empty($detectedCategorySlug)) {
        $categorySlug = $detectedCategorySlug;
        $categoryName = PearBlog_Poradnik_Pro_Routing::get_category_name($categorySlug);
        if ($categoryName === '') {
            $categoryName = ucfirst(str_replace('-', ' ', $categorySlug));
        }
    }

    $categories = PearBlog_Poradnik_Pro_Routing::get_categories();
}

$cityCategoryLinks = [];
foreach ($categories as $slug => $name) {
    $cityCategoryLinks[] = [
        'name' => $name,
        'url' => '/' . rawurlencode($citySlug) . '/' . rawurlencode($slug) . '/',
    ];
}

$navItems = [
    ['label' => 'Poradniki', 'url' => '/poradniki/'],
    ['label' => 'Pytania', 'url' => '/pytania/'],
    ['label' => 'Rankingi', 'url' => '/rankingi/'],
    ['label' => 'Eksperci', 'url' => '/eksperci/'],
];

$stats = [
    ['value' => '1 240', 'label' => 'poradników'],
    ['value' => '850', 'label' => 'specjalistów'],
    ['value' => '320', 'label' => 'ekspertów'],
    ['value' => '28', 'label' => 'rankingów'],
];

$experts = [
    ['initials' => 'JK', 'name' => 'Jan Kowalski', 'specialty' => 'Doradca', 'rating' => '4.9', 'reviews' => '322'],
    ['initials' => 'AN', 'name' => 'Anna Nowak', 'specialty' => 'Prawnik', 'rating' => '4.8', 'reviews' => '425'],
    ['initials' => 'PZ', 'name' => 'Piotr Zieliński', 'specialty' => 'Prawnik', 'rating' => '4.7', 'reviews' => '278'],
];

$tabs = [
    'poradniki' => [
        'label' => 'Poradniki',
        'items' => [
            'Jak sprzedać mieszkanie w Warszawie?',
            'Kredyt hipoteczny Warszawa – poradnik',
            'Zakup mieszkania w stolicy krok po kroku',
        ],
    ],
    'pytania' => [
        'label' => 'Pytania',
        'items' => [
            'Zdolność kredytowa',
            'Najlepsze dzielnice do zamieszkania w Warszawie?',
            'Jak wybrać eksperta do spraw nieruchomości?',
        ],
    ],
    'rankingi' => [
        'label' => 'Rankingi',
        'items' => [
            'Kalkulator OC',
            'Ranking doradców kredytowych w Warszawie',
            'Ranking kancelarii prawnych w Warszawie',
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $escape($cityName); ?> – Poradnik.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --purple: #6c2bd9;
            --purple-dark: #1a0a3e;
            --purple-deep: #0f0626;
            --purple-mid: #2d1b69;
            --purple-soft: #f3edff;
            --green: #16a34a;
            --green-dark: #15803d;
            --white: #ffffff;
            --text: #140f23;
            --muted: #6a6480;
            --border: rgba(108, 43, 217, 0.12);
            --shadow: 0 18px 45px rgba(15, 6, 38, 0.12);
            --radius-lg: 24px;
            --radius-md: 18px;
            --radius-sm: 14px;
            --max-width: 1200px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text);
            background: #f7f5fc;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        button { font: inherit; cursor: pointer; }

        .container {
            width: min(var(--max-width), calc(100% - 32px));
            margin: 0 auto;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(20, 15, 35, 0.08);
        }

        .header-inner {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text);
            white-space: nowrap;
        }

        .logo-mark {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            background: linear-gradient(135deg, var(--purple), #8b5cf6);
            box-shadow: 0 10px 24px rgba(108, 43, 217, 0.25);
        }

        .main-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 28px;
            flex: 1;
        }

        .main-nav a {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--muted);
            transition: color 0.2s ease;
        }

        .main-nav a:hover,
        .main-nav a:focus-visible { color: var(--purple); }

        .header-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 22px;
            border-radius: 999px;
            background: var(--purple);
            color: var(--white);
            font-weight: 700;
            box-shadow: 0 12px 24px rgba(108, 43, 217, 0.24);
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .header-cta:hover,
        .header-cta:focus-visible {
            background: #5a21b6;
            transform: translateY(-1px);
        }

        .hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0f0626, #1a0a3e, #2d1b69);
            color: var(--white);
            padding: 88px 0 72px;
        }

        .hero::before,
        .hero::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            filter: blur(10px);
            opacity: 0.35;
        }

        .hero::before {
            width: 360px;
            height: 360px;
            top: -120px;
            right: -60px;
            background: rgba(139, 92, 246, 0.45);
        }

        .hero::after {
            width: 280px;
            height: 280px;
            bottom: -120px;
            left: -60px;
            background: rgba(59, 130, 246, 0.25);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 36px;
        }

        .hero-copy {
            max-width: 720px;
        }

        .eyebrow {
            display: inline-flex;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.14);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 18px;
        }

        .hero h1 {
            margin: 0 0 14px;
            font-size: clamp(2.8rem, 6vw, 4.75rem);
            line-height: 0.98;
            letter-spacing: -0.04em;
        }

        .hero p {
            margin: 0;
            max-width: 620px;
            font-size: clamp(1.05rem, 2vw, 1.3rem);
            color: rgba(255, 255, 255, 0.8);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .stat-card {
            padding: 24px;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .stat-value {
            display: block;
            font-size: clamp(1.8rem, 3vw, 2.6rem);
            font-weight: 800;
            line-height: 1;
            margin-bottom: 10px;
        }

        .stat-label {
            display: block;
            color: rgba(255, 255, 255, 0.76);
            font-size: 0.95rem;
        }

        .section {
            padding: 72px 0;
        }

        .section-light { background: var(--white); }
        .section-muted { background: #f7f5fc; }

        .section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 32px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.4rem);
            line-height: 1.05;
        }

        .section-heading p {
            margin: 0;
            max-width: 520px;
            color: var(--muted);
        }

        .experts-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .expert-card {
            padding: 28px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            background: linear-gradient(180deg, #ffffff 0%, #fbf9ff 100%);
            box-shadow: var(--shadow);
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
            flex: 0 0 68px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 800;
            color: var(--purple);
            background: linear-gradient(135deg, rgba(108, 43, 217, 0.15), rgba(139, 92, 246, 0.3));
            border: 1px solid rgba(108, 43, 217, 0.12);
        }

        .expert-name {
            margin: 0 0 4px;
            font-size: 1.15rem;
        }

        .expert-specialty {
            margin: 0;
            color: var(--purple);
            font-weight: 700;
        }

        .expert-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 18px 0 22px;
            padding-top: 18px;
            border-top: 1px solid rgba(20, 15, 35, 0.08);
            color: var(--muted);
            font-size: 0.95rem;
        }

        .expert-rating {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: var(--text);
        }

        .stars {
            color: #f5b301;
            letter-spacing: 0.06em;
        }

        .ask-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            border-radius: 999px;
            background: var(--green);
            color: var(--white);
            font-weight: 700;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .ask-button:hover,
        .ask-button:focus-visible {
            background: var(--green-dark);
            transform: translateY(-1px);
        }

        .tabs-shell {
            border-radius: calc(var(--radius-lg) + 4px);
            border: 1px solid var(--border);
            background: var(--white);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .tabs-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px;
            background: #fcfbff;
            border-bottom: 1px solid rgba(20, 15, 35, 0.08);
        }

        .tab-button {
            border: 1px solid rgba(108, 43, 217, 0.15);
            background: var(--white);
            color: var(--muted);
            padding: 12px 18px;
            border-radius: 999px;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .tab-button.active,
        .tab-button:hover,
        .tab-button:focus-visible {
            color: var(--white);
            background: var(--purple);
            border-color: var(--purple);
            box-shadow: 0 10px 24px rgba(108, 43, 217, 0.2);
        }

        .tab-panel {
            display: none;
            padding: 28px 28px 32px;
        }

        .tab-panel.active { display: block; }

        .popular-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 18px;
        }

        .popular-item a {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: var(--text);
            font-size: 1.05rem;
            font-weight: 600;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .popular-item a::before {
            content: '•';
            color: var(--purple);
            font-size: 1.5rem;
            line-height: 1;
        }

        .popular-item a:hover,
        .popular-item a:focus-visible {
            color: var(--purple);
            transform: translateX(4px);
        }

        @media (max-width: 980px) {
            .header-inner {
                min-height: 72px;
                flex-wrap: wrap;
                padding: 14px 0;
            }

            .main-nav {
                order: 3;
                width: 100%;
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .stats-grid,
            .experts-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .hero {
                padding: 72px 0 56px;
            }

            .section {
                padding: 56px 0;
            }

            .stats-grid,
            .experts-row {
                grid-template-columns: 1fr;
            }

            .section-heading {
                flex-direction: column;
                align-items: flex-start;
            }

            .tabs-nav {
                flex-direction: column;
                align-items: stretch;
            }

            .tab-button,
            .header-cta {
                width: 100%;
                justify-content: center;
            }

            .expert-meta {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    <?php if (function_exists('wp_head')) { wp_head(); } ?>
</head>
<body>
<?php if (function_exists('wp_body_open')) { wp_body_open(); } ?>
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a class="logo" href="/">
                <span class="logo-mark">P</span>
                <span>Poradnik.pro</span>
            </a>
            <nav class="main-nav" aria-label="Główna nawigacja">
                <?php foreach ($navItems as $navItem) : ?>
                    <a href="<?php echo $escape($navItem['url']); ?>"><?php echo $escape($navItem['label']); ?></a>
                <?php endforeach; ?>
            </nav>
            <a class="header-cta" href="/eksperci/">Znajdź eksperta</a>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-copy">
                    <span class="eyebrow">Miasto / <?php echo $escape($cityName); ?><?php if (!empty($categoryName)) : ?> / <?php echo $escape($categoryName); ?><?php endif; ?></span>
                    <h1><?php echo $escape($cityName); ?></h1>
                    <p>Polecani eksperci i odpowiedzi dla Ciebie<?php if (!empty($categoryName)) : ?> w kategorii <?php echo $escape($categoryName); ?><?php endif; ?></p>
                </div>
                <div class="stats-grid" aria-label="Statystyki <?php echo $escape($cityName); ?>">
                    <?php foreach ($stats as $stat) : ?>
                        <div class="stat-card">
                            <span class="stat-value"><?php echo $escape($stat['value']); ?></span>
                            <span class="stat-label"><?php echo $escape($stat['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-light">
        <div class="container">
            <div class="section-heading">
                <div>
                    <h2>Polecani eksperci w <?php echo $escape($cityName); ?></h2>
                </div>
                <p>Skontaktuj się ze sprawdzonymi specjalistami i zadaj pytanie bezpośrednio do osoby, która zna lokalny rynek.</p>
            </div>

            <div class="experts-row">
                <?php foreach ($experts as $expert) : ?>
                    <article class="expert-card">
                        <div class="expert-top">
                            <div class="expert-avatar" aria-hidden="true"><?php echo $escape($expert['initials']); ?></div>
                            <div>
                                <h3 class="expert-name"><?php echo $escape($expert['name']); ?></h3>
                                <p class="expert-specialty"><?php echo $escape($expert['specialty']); ?></p>
                            </div>
                        </div>
                        <div class="expert-meta">
                            <div class="expert-rating">
                                <span class="stars" aria-hidden="true">★★★★★</span>
                                <span><?php echo $escape($expert['rating']); ?></span>
                            </div>
                            <span><?php echo $escape($expert['reviews']); ?> opinii</span>
                        </div>
                        <a class="ask-button" href="/pytania/">Zadaj pytanie</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section-muted">
        <div class="container">
            <div class="section-heading">
                <div>
                    <h2>Popularne w <?php echo $escape($cityName); ?></h2>
                </div>
                <p>Najczęściej wyszukiwane poradniki, pytania i rankingi związane z życiem, finansami i nieruchomościami w stolicy.</p>
            </div>

            <div class="tabs-shell">
                <div class="tabs-nav" role="tablist" aria-label="Popularne treści">
                    <?php $isFirstTab = true; ?>
                    <?php foreach ($tabs as $tabKey => $tab) : ?>
                        <button
                            class="tab-button<?php echo $isFirstTab ? ' active' : ''; ?>"
                            type="button"
                            role="tab"
                            aria-selected="<?php echo $isFirstTab ? 'true' : 'false'; ?>"
                            aria-controls="tab-panel-<?php echo $escape($tabKey); ?>"
                            id="tab-<?php echo $escape($tabKey); ?>"
                            data-tab-target="tab-panel-<?php echo $escape($tabKey); ?>"
                        >
                            <?php echo $escape($tab['label']); ?>
                        </button>
                        <?php $isFirstTab = false; ?>
                    <?php endforeach; ?>
                </div>

                <?php $isFirstPanel = true; ?>
                <?php foreach ($tabs as $tabKey => $tab) : ?>
                    <div
                        class="tab-panel<?php echo $isFirstPanel ? ' active' : ''; ?>"
                        id="tab-panel-<?php echo $escape($tabKey); ?>"
                        role="tabpanel"
                        aria-labelledby="tab-<?php echo $escape($tabKey); ?>"
                    >
                        <ul class="popular-list">
                            <?php foreach ($tab['items'] as $item) : ?>
                                <li class="popular-item">
                                    <a href="#"><?php echo $escape($item); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php $isFirstPanel = false; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section-light">
        <div class="container">
            <div class="section-heading">
                <div>
                    <h2>Kategorie w <?php echo $escape($cityName); ?></h2>
                </div>
                <p>Szybkie przejścia do landingów kategorii dla tego miasta.</p>
            </div>
            <ul class="popular-list">
                <?php foreach ($cityCategoryLinks as $categoryLink) : ?>
                    <li class="popular-item">
                        <a href="<?php echo $escape($categoryLink['url']); ?>"><?php echo $escape($categoryLink['name']); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
</main>

<script>
    (function () {
        var buttons = document.querySelectorAll('[data-tab-target]');
        var panels = document.querySelectorAll('.tab-panel');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var targetId = button.getAttribute('data-tab-target');

                buttons.forEach(function (item) {
                    item.classList.remove('active');
                    item.setAttribute('aria-selected', 'false');
                });

                panels.forEach(function (panel) {
                    panel.classList.remove('active');
                });

                button.classList.add('active');
                button.setAttribute('aria-selected', 'true');

                var targetPanel = document.getElementById(targetId);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
            });
        });
    }());
</script>
<?php if (function_exists('wp_footer')) { wp_footer(); } ?>
</body>
</html>
