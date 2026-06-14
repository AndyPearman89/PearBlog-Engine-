<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poradniki – Poradnik.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1a1a2e;
            background: #f8f9fc;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; display: block; }
        button { cursor: pointer; border: none; font-family: inherit; }
        ul { list-style: none; }

        /* ===== VARIABLES ===== */
        :root {
            --purple-primary: #6c2bd9;
            --purple-dark: #1a0a3e;
            --purple-light: #8b5cf6;
            --orange-cta: #f97316;
            --orange-hover: #ea580c;
            --blue-accent: #3b82f6;
            --green-accent: #10b981;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
            --max-width: 1200px;
        }

        .container { max-width: var(--max-width); margin: 0 auto; padding: 0 24px; }

        /* ===== HEADER / NAV ===== */
        .site-header {
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            font-size: 20px;
            color: var(--gray-900);
        }
        .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
        }
        .main-nav { display: flex; align-items: center; gap: 28px; }
        .main-nav a {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-600);
            transition: color 0.2s;
        }
        .main-nav a:hover { color: var(--purple-primary); }
        .main-nav a.active { color: var(--purple-primary); font-weight: 600; }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .btn-find-specialist {
            background: var(--purple-primary);
            color: #fff;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-find-specialist:hover { background: var(--purple-dark); }

        /* ===== PAGE HERO ===== */
        .page-hero {
            background: linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%);
            padding: 48px 0;
        }
        .page-hero h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        .page-hero p {
            font-size: 16px;
            color: var(--gray-600);
            max-width: 560px;
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--gray-400);
            margin-bottom: 16px;
        }
        .breadcrumb a { color: var(--gray-500); transition: color 0.2s; }
        .breadcrumb a:hover { color: var(--purple-primary); }
        .breadcrumb .sep { color: var(--gray-300); }

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

        /* ===== FOOTER ===== */
        .site-footer {
            background: var(--gray-900);
            color: rgba(255,255,255,0.6);
            padding: 48px 0 24px;
        }
        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 12px;
        }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { color: rgba(255,255,255,0.5); transition: color 0.2s; }
        .footer-links a:hover { color: #fff; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .guides-grid { grid-template-columns: 1fr; }
            .featured-guide { grid-template-columns: 1fr; }
            .main-nav { display: none; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a href="/" class="logo">
                <div class="logo-icon">P</div>
                Poradnik.pro
            </a>
            <nav class="main-nav">
                <a href="/poradniki" class="active">Poradniki</a>
                <a href="/porownania">Porównania</a>
                <a href="/rankingi">Rankingi</a>
                <a href="/kalkulatory">Kalkulatory</a>
                <a href="/eksperci">Eksperci</a>
            </nav>
            <div class="header-actions">
                <a href="/dla-specjalistow" class="btn-find-specialist">Dla specjalistów</a>
            </div>
        </div>
    </div>
</header>

<!-- PAGE HERO -->
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Strona główna</a>
            <span class="sep">/</span>
            <span>Poradniki</span>
        </div>
        <h1>📘 Poradniki</h1>
        <p>Zrozum temat, zanim wydasz pieniądze. Każdy poradnik to kompletna wiedza prowadząca do świadomej decyzji.</p>
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
            <button class="filter-chip">Nieruchomości</button>
            <button class="filter-chip">Zdrowie</button>
            <button class="filter-chip">Motoryzacja</button>
            <button class="filter-chip">Technologia</button>
        </div>
    </div>
</div>

<!-- FEATURED GUIDE -->
<section class="guides-section">
    <div class="container">
        <div class="featured-guide">
            <div class="featured-guide-image">🏠</div>
            <div>
                <div class="featured-guide-badge">⭐ Polecany poradnik</div>
                <h2>Koszt remontu łazienki — aktualne ceny 2026</h2>
                <p>Pełna analiza kosztów remontu łazienki: materiały, robocizna, ukryte wydatki. Dowiedz się, ile naprawdę zapłacisz i jak zaoszczędzić bez utraty jakości.</p>
                <a href="#" class="btn-read">Czytaj poradnik</a>
            </div>
        </div>

        <!-- GUIDES GRID -->
        <div class="guides-grid">
            <a href="#" class="guide-card">
                <div class="guide-thumb">🏗️</div>
                <div class="guide-body">
                    <span class="guide-category">Budownictwo</span>
                    <h3 class="guide-title">Jak sprzedać działkę krok po kroku</h3>
                    <p class="guide-excerpt">Kompletny przewodnik po sprzedaży działki — od wyceny po notariusza.</p>
                    <div class="guide-meta">
                        <span class="guide-reading-time">📖 12 min</span>
                        <span>2,4k wyświetleń</span>
                    </div>
                </div>
            </a>

            <a href="#" class="guide-card">
                <div class="guide-thumb">💳</div>
                <div class="guide-body">
                    <span class="guide-category">Finanse</span>
                    <h3 class="guide-title">Kredyt hipoteczny — co musisz wiedzieć</h3>
                    <p class="guide-excerpt">Przewodnik po kredycie hipotecznym: zdolność, dokumenty, najlepsze oferty banków w 2026.</p>
                    <div class="guide-meta">
                        <span class="guide-reading-time">📖 18 min</span>
                        <span>5,1k wyświetleń</span>
                    </div>
                </div>
            </a>

            <a href="#" class="guide-card">
                <div class="guide-thumb">🔧</div>
                <div class="guide-body">
                    <span class="guide-category">Budownictwo</span>
                    <h3 class="guide-title">Koszt budowy domu za m² w 2026</h3>
                    <p class="guide-excerpt">Aktualne koszty budowy domu na każdym etapie — fundamenty, ściany, dach, wykończenie.</p>
                    <div class="guide-meta">
                        <span class="guide-reading-time">📖 15 min</span>
                        <span>8,7k wyświetleń</span>
                    </div>
                </div>
            </a>

            <a href="#" class="guide-card">
                <div class="guide-thumb">⚖️</div>
                <div class="guide-body">
                    <span class="guide-category">Prawo</span>
                    <h3 class="guide-title">Rozwód — procedura i koszty 2026</h3>
                    <p class="guide-excerpt">Jak wygląda procedura rozwodowa, ile kosztuje i jak się przygotować — kompletny poradnik.</p>
                    <div class="guide-meta">
                        <span class="guide-reading-time">📖 14 min</span>
                        <span>3,9k wyświetleń</span>
                    </div>
                </div>
            </a>

            <a href="#" class="guide-card">
                <div class="guide-thumb">🌡️</div>
                <div class="guide-body">
                    <span class="guide-category">Budownictwo</span>
                    <h3 class="guide-title">Pompa ciepła — wszystko co musisz wiedzieć</h3>
                    <p class="guide-excerpt">Rodzaje pomp ciepła, koszty instalacji, oszczędności i dotacje — pełny przegląd.</p>
                    <div class="guide-meta">
                        <span class="guide-reading-time">📖 20 min</span>
                        <span>12k wyświetleń</span>
                    </div>
                </div>
            </a>

            <a href="#" class="guide-card">
                <div class="guide-thumb">🚗</div>
                <div class="guide-body">
                    <span class="guide-category">Motoryzacja</span>
                    <h3 class="guide-title">Jak kupić używane auto bez ryzyka</h3>
                    <p class="guide-excerpt">Na co zwrócić uwagę, jakie dokumenty sprawdzić i jak nie dać się oszukać.</p>
                    <div class="guide-meta">
                        <span class="guide-reading-time">📖 10 min</span>
                        <span>6,2k wyświetleń</span>
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
            <button class="page-btn">→</button>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-bottom">
            <span>© 2026 Poradnik.pro. Wszelkie prawa zastrzeżone.</span>
            <div class="footer-links">
                <a href="#">Regulamin</a>
                <a href="#">Polityka prywatności</a>
                <a href="#">Kontakt</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
