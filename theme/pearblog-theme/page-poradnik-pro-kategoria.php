<?php
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$fallbackCities = [
    'warszawa' => 'Warszawa',
    'krakow' => 'Kraków',
    'wroclaw' => 'Wrocław',
    'poznan' => 'Poznań',
    'gdansk' => 'Gdańsk',
    'katowice' => 'Katowice',
    'lodz' => 'Łódź',
    'szczecin' => 'Szczecin',
    'lublin' => 'Lublin',
    'bydgoszcz' => 'Bydgoszcz',
];
$cities = $fallbackCities;

$categorySlug = 'prawo';
$categoryName = 'Prawo';

if (class_exists('PearBlog_Poradnik_Pro_Routing')) {
    $detectedCategorySlug = PearBlog_Poradnik_Pro_Routing::get_current_category();
    if (!empty($detectedCategorySlug)) {
        $categorySlug = $detectedCategorySlug;
    }

    $categoryName = PearBlog_Poradnik_Pro_Routing::get_category_name($categorySlug);
    if ($categoryName === '') {
        $categoryName = ucfirst(str_replace('-', ' ', $categorySlug));
    }
    $cities = PearBlog_Poradnik_Pro_Routing::get_cities();
}

$cityCategoryLinks = [];
foreach ($cities as $citySlug => $cityName) {
    $cityCategoryLinks[] = [
        'name' => $cityName,
        'url' => '/' . rawurlencode($citySlug) . '/' . rawurlencode($categorySlug) . '/',
    ];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $escape($categoryName); ?> – Poradnik.pro</title>
    <meta name="description" content="Kategoria <?php echo $escape($categoryName); ?> na Poradnik.pro: praktyczne poradniki, pytania i odpowiedzi ekspertów, rankingi i najlepsi specjaliści.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple-primary: #6c2bd9;
            --purple-dark: #1a0a3e;
            --purple-light: #8b5cf6;
            --orange-cta: #f97316;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --max-width: 1200px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--gray-900);
            background: var(--gray-50);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        ul { list-style: none; }

        .container {
            width: min(100%, var(--max-width));
            margin: 0 auto;
            padding: 0 24px;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--gray-200);
        }

        .header-inner {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--gray-900);
            flex-shrink: 0;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            font-weight: 800;
        }

        .main-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            flex: 1;
            flex-wrap: wrap;
        }

        .main-nav a {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--gray-600);
            transition: color 0.2s ease;
        }

        .main-nav a:hover,
        .main-nav a.active { color: var(--purple-primary); }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 20px;
            border-radius: 999px;
            background: var(--purple-primary);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: var(--shadow-sm);
        }

        .hero {
            background:
                radial-gradient(circle at top right, rgba(139,92,246,0.28), transparent 35%),
                linear-gradient(135deg, #14082f 0%, var(--purple-dark) 45%, #2d1065 100%);
            color: #fff;
            padding: 80px 0 40px;
        }

        .hero-content {
            max-width: 760px;
        }

        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.16);
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.82);
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .hero h1 {
            font-size: clamp(2.8rem, 7vw, 4.5rem);
            line-height: 0.98;
            margin-bottom: 20px;
            letter-spacing: -0.04em;
        }

        .hero p {
            max-width: 620px;
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: rgba(255,255,255,0.78);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-top: 40px;
        }

        .stat-card {
            padding: 22px;
            border-radius: var(--radius-lg);
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(8px);
        }

        .stat-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .stat-label {
            color: rgba(255,255,255,0.68);
            font-size: 0.95rem;
        }

        main {
            padding: 40px 0 72px;
        }

        .content-stack {
            display: grid;
            gap: 28px;
        }

        .section-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--shadow-sm);
        }

        .section-header {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .section-header h2 {
            font-size: clamp(1.5rem, 3vw, 2rem);
            line-height: 1.1;
            color: var(--gray-900);
        }

        .section-header p {
            color: var(--gray-500);
            font-size: 0.98rem;
            max-width: 560px;
        }

        .list-grid {
            display: grid;
            gap: 14px;
        }

        .list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 20px;
            border-radius: var(--radius-md);
            background: var(--gray-50);
            border: 1px solid var(--gray-100);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .list-item:hover {
            transform: translateY(-1px);
            border-color: rgba(108,43,217,0.2);
            box-shadow: var(--shadow-sm);
        }

        .list-copy {
            min-width: 0;
        }

        .list-kicker {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
            color: var(--purple-primary);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .list-title {
            font-size: 1.06rem;
            font-weight: 700;
            color: var(--gray-800);
        }

        .list-meta {
            flex-shrink: 0;
            text-align: right;
            color: var(--gray-500);
            font-size: 0.92rem;
            font-weight: 600;
        }

        .experts-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .expert-card {
            background: linear-gradient(180deg, #ffffff 0%, #faf7ff 100%);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .expert-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .expert-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--gray-200);
            color: var(--gray-700);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .expert-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .expert-specialty {
            color: var(--gray-500);
            font-size: 0.92rem;
        }

        .expert-rating {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-600);
            font-size: 0.92rem;
        }

        .rating-stars {
            color: var(--orange-cta);
            font-weight: 700;
        }

        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .tag-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 18px;
            border-radius: 999px;
            background: #f4edff;
            color: var(--purple-primary);
            border: 1px solid rgba(108,43,217,0.14);
            font-size: 0.95rem;
            font-weight: 700;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .tag-link:hover {
            background: #ede4ff;
            transform: translateY(-1px);
        }

        .calc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .calc-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 20px;
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--gray-200);
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .calc-card:hover {
            box-shadow: 0 6px 20px rgba(108,43,217,0.10);
            transform: translateY(-2px);
        }

        .calc-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #f0e8ff;
            color: var(--purple-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .calc-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .lead-cta-section {
            margin-top: 32px;
            padding: 48px 32px;
            background: linear-gradient(135deg, var(--purple-primary), #4c1d95);
            border-radius: 24px;
            text-align: center;
            color: #fff;
        }

        .lead-cta-section h2 {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .lead-cta-section p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 24px;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        .lead-cta-btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 10px;
            background: var(--orange-cta);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            transition: background 0.2s;
        }

        .lead-cta-btn:hover { background: #ea580c; }

        .footer-note {
            padding-top: 12px;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.92rem;
        }

        @media (max-width: 1024px) {
            .header-inner {
                flex-wrap: wrap;
                justify-content: center;
                padding: 16px 0;
            }

            .main-nav {
                order: 3;
                width: 100%;
                justify-content: center;
            }

            .stats-row,
            .experts-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .container { padding: 0 18px; }

            .hero { padding: 56px 0 32px; }

            .section-card {
                padding: 24px 20px;
                border-radius: 20px;
            }

            .section-header,
            .list-item,
            .expert-rating {
                display: grid;
                gap: 12px;
            }

            .list-meta {
                text-align: left;
            }

            .stats-row,
            .experts-row {
                grid-template-columns: 1fr;
            }

            .main-nav {
                gap: 14px 18px;
            }
        }

        @media (max-width: 560px) {
            .logo { width: 100%; justify-content: center; }
            .btn-primary { width: 100%; }
            .header-inner { gap: 16px; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo" aria-label="Poradnik.pro">
                    <span class="logo-icon">P</span>
                    <span>Poradnik.pro</span>
                </a>
                <nav class="main-nav" aria-label="Główna nawigacja">
                    <a href="/poradniki" class="active">Poradniki</a>
                    <a href="/rankingi">Rankingi</a>
                    <a href="/kalkulatory">Kalkulatory</a>
                    <a href="/pytania-i-odpowiedzi">Pytania i Odpowiedzi</a>
                    <a href="/specjalisci">Specjaliści</a>
                    <a href="/kontakt">Kontakt</a>
                </nav>
                <a href="/specjalisci" class="btn-primary">Znajdź eksperta</a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-label">Kategoria / <?php echo $escape($categoryName); ?></div>
                <h1><?php echo $escape($categoryName); ?></h1>
                <p>Praktyczna wiedza, odpowiedzi ekspertów i najlepsi specjaliści w jednym miejscu.</p>
            </div>
            <div class="stats-row" aria-label="Statystyki kategorii <?php echo $escape($categoryName); ?>">
                <div class="stat-card">
                    <span class="stat-value">2 430</span>
                    <span class="stat-label">poradników</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">1 250</span>
                    <span class="stat-label">specjalistów</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">320</span>
                    <span class="stat-label">opinii</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">18</span>
                    <span class="stat-label">kalkulatorów</span>
                </div>
            </div>
        </div>
    </section>

    <main>
        <div class="container">
            <div class="content-stack">
                <section class="section-card" aria-labelledby="popularne-poradniki">
                    <div class="section-header">
                        <div>
                            <h2 id="popularne-poradniki">Popularne poradniki</h2>
                        </div>
                        <p>Najczęściej czytane materiały z kategorii <?php echo $escape($categoryName); ?> — praktyczne poradniki, odpowiedzi ekspertów i rankingi.</p>
                    </div>
                    <div class="list-grid">
                        <a href="#" class="list-item">
                            <div class="list-copy">
                                <span class="list-kicker">Poradnik</span>
                                <div class="list-title">Jak najtaniej przeprowadzić sprawę o spadek?</div>
                            </div>
                            <div class="list-meta">12 450 wyświetleń</div>
                        </a>
                        <a href="#" class="list-item">
                            <div class="list-copy">
                                <span class="list-kicker">Poradnik</span>
                                <div class="list-title">Rozwód bez orzekania o winie — ile trwa i ile kosztuje?</div>
                            </div>
                            <div class="list-meta">10 980 wyświetleń</div>
                        </a>
                        <a href="#" class="list-item">
                            <div class="list-copy">
                                <span class="list-kicker">Poradnik</span>
                                <div class="list-title">Wzór umowy najmu mieszkania 2026 — co musi zawierać?</div>
                            </div>
                            <div class="list-meta">9 340 wyświetleń</div>
                        </a>
                        <a href="#" class="list-item">
                            <div class="list-copy">
                                <span class="list-kicker">Poradnik</span>
                                <div class="list-title">Jak odzyskać dług bez sądu? Skuteczne kroki dla wierzyciela</div>
                            </div>
                            <div class="list-meta">8 615 wyświetleń</div>
                        </a>
                        <a href="#" class="list-item">
                            <div class="list-copy">
                                <span class="list-kicker">Poradnik</span>
                                <div class="list-title">Testament własnoręczny — najczęstsze błędy i jak ich uniknąć</div>
                            </div>
                            <div class="list-meta">7 820 wyświetleń</div>
                        </a>
                    </div>
                </section>

                <section class="section-card" aria-labelledby="najnowsze-pytania">
                    <div class="section-header">
                        <div>
                            <h2 id="najnowsze-pytania">Najnowsze pytania</h2>
                        </div>
                        <p>Aktualne problemy użytkowników, na które odpowiadają prawnicy i doradcy z Poradnik.pro.</p>
                    </div>
                    <div class="list-grid">
                        <a href="#" class="list-item">
                            <div class="list-copy">
                                <span class="list-kicker">Pytanie użytkownika</span>
                                <div class="list-title">Czy mogę wypowiedzieć umowę najmu okazjonalnego przed terminem?</div>
                            </div>
                            <div class="list-meta">14 odpowiedzi</div>
                        </a>
                        <a href="#" class="list-item">
                            <div class="list-copy">
                                <span class="list-kicker">Pytanie użytkownika</span>
                                <div class="list-title">Jak podzielić majątek po rozwodzie, jeśli mieszkanie jest na kredyt?</div>
                            </div>
                            <div class="list-meta">11 odpowiedzi</div>
                        </a>
                        <a href="#" class="list-item">
                            <div class="list-copy">
                                <span class="list-kicker">Pytanie użytkownika</span>
                                <div class="list-title">Czy zachowek przysługuje wnukom po śmierci dziadka?</div>
                            </div>
                            <div class="list-meta">9 odpowiedzi</div>
                        </a>
                        <a href="#" class="list-item">
                            <div class="list-copy">
                                <span class="list-kicker">Pytanie użytkownika</span>
                                <div class="list-title">Jak napisać skuteczne wezwanie do zapłaty dla kontrahenta?</div>
                            </div>
                            <div class="list-meta">7 odpowiedzi</div>
                        </a>
                    </div>
                </section>

                <section class="section-card" aria-labelledby="najlepsi-eksperci">
                    <div class="section-header">
                        <div>
                            <h2 id="najlepsi-eksperci">Najlepsi eksperci</h2>
                        </div>
                        <p>Sprawdzeni specjaliści z wysokimi ocenami i doświadczeniem w sprawach cywilnych, rodzinnych i spadkowych.</p>
                    </div>
                    <div class="experts-row">
                        <a href="#" class="expert-card">
                            <div class="expert-top">
                                <div class="expert-avatar">AK</div>
                                <div>
                                    <div class="expert-name">Anna Kowalska</div>
                                    <div class="expert-specialty">Prawo rodzinne</div>
                                </div>
                            </div>
                            <div class="expert-rating">
                                <span class="rating-stars">★ 4.9</span>
                                <span>126 opinii</span>
                            </div>
                        </a>
                        <a href="#" class="expert-card">
                            <div class="expert-top">
                                <div class="expert-avatar">MN</div>
                                <div>
                                    <div class="expert-name">Michał Nowak</div>
                                    <div class="expert-specialty">Prawo spadkowe</div>
                                </div>
                            </div>
                            <div class="expert-rating">
                                <span class="rating-stars">★ 4.9</span>
                                <span>98 opinii</span>
                            </div>
                        </a>
                        <a href="#" class="expert-card">
                            <div class="expert-top">
                                <div class="expert-avatar">JP</div>
                                <div>
                                    <div class="expert-name">Joanna Piotrowska</div>
                                    <div class="expert-specialty">Prawo nieruchomości</div>
                                </div>
                            </div>
                            <div class="expert-rating">
                                <span class="rating-stars">★ 4.9</span>
                                <span>114 opinii</span>
                            </div>
                        </a>
                        <a href="#" class="expert-card">
                            <div class="expert-top">
                                <div class="expert-avatar">TB</div>
                                <div>
                                    <div class="expert-name">Tomasz Bąk</div>
                                    <div class="expert-specialty">Prawo pracy</div>
                                </div>
                            </div>
                            <div class="expert-rating">
                                <span class="rating-stars">★ 4.9</span>
                                <span>87 opinii</span>
                            </div>
                        </a>
                    </div>
                </section>

                <section class="section-card" aria-labelledby="popularne-rankingi">
                    <div class="section-header">
                        <div>
                            <h2 id="popularne-rankingi">Najpopularniejsze rankingi</h2>
                        </div>
                        <p>Szybkie skróty do najlepiej ocenianych kancelarii i usług prawnych w największych miastach.</p>
                    </div>
                    <div class="tag-list">
                        <a href="#" class="tag-link">Ranking kancelarii rozwodowych Warszawa</a>
                        <a href="#" class="tag-link">Najlepsi adwokaci od spadków Kraków</a>
                        <a href="#" class="tag-link">Ranking radców prawnych Wrocław</a>
                        <a href="#" class="tag-link">Kancelarie od prawa pracy Poznań</a>
                        <a href="#" class="tag-link">Najlepsze kancelarie nieruchomości Gdańsk</a>
                        <a href="#" class="tag-link">Adwokaci od odszkodowań Łódź</a>
                    </div>
                </section>

                <section class="section-card" aria-labelledby="miasta-w-kategorii">
                    <div class="section-header">
                        <div>
                            <h2 id="miasta-w-kategorii">Miasta w kategorii <?php echo $escape($categoryName); ?></h2>
                        </div>
                        <p>Przejdź do landingów tej kategorii w konkretnych miastach.</p>
                    </div>
                    <div class="tag-list">
                        <?php foreach ($cityCategoryLinks as $cityCategoryLink) : ?>
                            <a href="<?php echo $escape($cityCategoryLink['url']); ?>" class="tag-link"><?php echo $escape($cityCategoryLink['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section-card">
                    <div class="section-header">
                        <h2 id="kalkulatory">Kalkulatory</h2>
                    </div>
                    <p>Oblicz koszty i zaplanuj działania z pomocą naszych narzędzi.</p>
                    <div class="calc-grid">
                        <a href="/kalkulator/alimenty" class="calc-card">
                            <div class="calc-icon">🧮</div>
                            <div class="calc-name">Kalkulator alimentów</div>
                        </a>
                        <a href="/kalkulator/odszkodowanie" class="calc-card">
                            <div class="calc-icon">💰</div>
                            <div class="calc-name">Kalkulator odszkodowania</div>
                        </a>
                        <a href="/kalkulator/koszty-sadowe" class="calc-card">
                            <div class="calc-icon">⚖️</div>
                            <div class="calc-name">Kalkulator kosztów sądowych</div>
                        </a>
                        <a href="/kalkulator/podatek-od-spadku" class="calc-card">
                            <div class="calc-icon">📋</div>
                            <div class="calc-name">Kalkulator podatku od spadku</div>
                        </a>
                    </div>
                </section>

                <section class="lead-cta-section">
                    <h2>Potrzebujesz porady prawnej?</h2>
                    <p>Opisz swoją sytuację i otrzymaj spersonalizowaną odpowiedź od zweryfikowanego eksperta w ciągu 24h.</p>
                    <a href="/dla-specjalistow" class="lead-cta-btn">Wyślij zapytanie →</a>
                </section>
            </div>
            <div class="footer-note">Szablon kategorii Poradnik.pro zaprojektowany w układzie zgodnym z wireframe dla platformy Poradnik.pro.</div>
        </div>
    </main>
</body>
</html>
