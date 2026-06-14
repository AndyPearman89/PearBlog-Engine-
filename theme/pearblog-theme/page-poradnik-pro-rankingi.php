<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rankingi – Poradnik.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1a1a2e;
            background: #f8f9fc;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        button { cursor: pointer; border: none; font-family: inherit; }
        ul { list-style: none; }

        :root {
            --purple-primary: #6c2bd9;
            --purple-dark: #1a0a3e;
            --purple-light: #8b5cf6;
            --orange-cta: #f97316;
            --orange-hover: #ea580c;
            --blue-accent: #3b82f6;
            --green-accent: #10b981;
            --yellow-accent: #f59e0b;
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
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --max-width: 1200px;
        }

        .container { max-width: var(--max-width); margin: 0 auto; padding: 0 24px; }

        /* ===== HEADER ===== */
        .site-header { background: #fff; border-bottom: 1px solid var(--gray-200); position: sticky; top: 0; z-index: 100; }
        .header-inner { display: flex; align-items: center; justify-content: space-between; height: 64px; }
        .logo { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 20px; color: var(--gray-900); }
        .logo-icon { width: 32px; height: 32px; background: linear-gradient(135deg, var(--purple-primary), var(--purple-light)); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 16px; }
        .main-nav { display: flex; align-items: center; gap: 28px; }
        .main-nav a { font-size: 14px; font-weight: 500; color: var(--gray-600); transition: color 0.2s; }
        .main-nav a:hover { color: var(--purple-primary); }
        .main-nav a.active { color: var(--purple-primary); font-weight: 600; }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .btn-find-specialist { background: var(--purple-primary); color: #fff; padding: 10px 20px; border-radius: 50px; font-size: 13px; font-weight: 600; transition: background 0.2s; }
        .btn-find-specialist:hover { background: var(--purple-dark); }

        /* ===== PAGE HERO ===== */
        .page-hero { background: linear-gradient(135deg, #fef3c7 0%, #ffedd5 100%); padding: 48px 0; }
        .page-hero h1 { font-size: 32px; font-weight: 800; color: var(--gray-900); margin-bottom: 8px; }
        .page-hero p { font-size: 16px; color: var(--gray-600); max-width: 560px; }
        .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-400); margin-bottom: 16px; }
        .breadcrumb a { color: var(--gray-500); }
        .breadcrumb a:hover { color: var(--purple-primary); }
        .breadcrumb .sep { color: var(--gray-300); }

        /* ===== RANKINGS ===== */
        .rankings-section { padding: 48px 0; }
        .rankings-list { display: flex; flex-direction: column; gap: 20px; }

        .ranking-item {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 24px;
            display: grid;
            grid-template-columns: 60px 1fr auto;
            gap: 20px;
            align-items: center;
            transition: box-shadow 0.2s;
        }
        .ranking-item:hover { box-shadow: var(--shadow-md); }
        .ranking-item.gold { border-color: #fbbf24; border-width: 2px; }
        .ranking-item.silver { border-color: #94a3b8; border-width: 2px; }
        .ranking-item.bronze { border-color: #d97706; border-width: 2px; }

        .ranking-position {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
        }
        .ranking-position.gold { background: #fef3c7; color: #d97706; }
        .ranking-position.silver { background: #f1f5f9; color: #64748b; }
        .ranking-position.bronze { background: #ffedd5; color: #ea580c; }
        .ranking-position.default { background: var(--gray-100); color: var(--gray-600); }

        .ranking-info h3 { font-size: 16px; font-weight: 700; color: var(--gray-900); margin-bottom: 4px; }
        .ranking-meta { display: flex; align-items: center; gap: 16px; font-size: 13px; color: var(--gray-500); }
        .ranking-stars { color: var(--yellow-accent); }
        .ranking-reviews { color: var(--gray-400); }
        .ranking-location { display: flex; align-items: center; gap: 4px; }

        .ranking-score {
            text-align: center;
        }
        .ranking-score-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--green-accent);
        }
        .ranking-score-label {
            font-size: 11px;
            color: var(--gray-400);
        }

        /* ===== CATEGORY RANKINGS ===== */
        .category-rankings {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .category-ranking-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px;
            transition: box-shadow 0.2s;
        }
        .category-ranking-card:hover { box-shadow: var(--shadow-md); }
        .category-ranking-icon { font-size: 32px; margin-bottom: 12px; }
        .category-ranking-title { font-size: 15px; font-weight: 700; color: var(--gray-900); margin-bottom: 6px; }
        .category-ranking-count { font-size: 12px; color: var(--gray-500); margin-bottom: 12px; }
        .category-ranking-top {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .top-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }
        .top-item-name { color: var(--gray-700); font-weight: 500; }
        .top-item-score { color: var(--green-accent); font-weight: 700; }

        /* ===== FOOTER ===== */
        .site-footer { background: var(--gray-900); color: rgba(255,255,255,0.6); padding: 48px 0 24px; }
        .footer-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { color: rgba(255,255,255,0.5); transition: color 0.2s; }
        .footer-links a:hover { color: #fff; }

        @media (max-width: 768px) {
            .category-rankings { grid-template-columns: 1fr; }
            .ranking-item { grid-template-columns: 48px 1fr; }
            .ranking-score { display: none; }
            .main-nav { display: none; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a href="/" class="logo"><div class="logo-icon">P</div> Poradnik.pro</a>
            <nav class="main-nav">
                <a href="/poradniki">Poradniki</a>
                <a href="/porownania">Porównania</a>
                <a href="/rankingi" class="active">Rankingi</a>
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
            <span>Rankingi</span>
        </div>
        <h1>🏆 Rankingi</h1>
        <p>Nie szukasz — wybierasz sprawdzonych. Opinie, oceny, ranking jakości — minimalizujesz ryzyko.</p>
    </div>
</section>

<!-- CATEGORY RANKINGS -->
<section class="rankings-section">
    <div class="container">
        <div class="category-rankings">
            <a href="#" class="category-ranking-card">
                <div class="category-ranking-icon">🏗️</div>
                <h3 class="category-ranking-title">Firmy remontowe</h3>
                <p class="category-ranking-count">127 firm w rankingu</p>
                <div class="category-ranking-top">
                    <div class="top-item"><span class="top-item-name">1. RemontPro Katowice</span><span class="top-item-score">9.7</span></div>
                    <div class="top-item"><span class="top-item-name">2. Budmax Warszawa</span><span class="top-item-score">9.5</span></div>
                    <div class="top-item"><span class="top-item-name">3. Eko-Dom Kraków</span><span class="top-item-score">9.3</span></div>
                </div>
            </a>

            <a href="#" class="category-ranking-card">
                <div class="category-ranking-icon">⚖️</div>
                <h3 class="category-ranking-title">Prawnicy rozwodowi</h3>
                <p class="category-ranking-count">84 kancelarie</p>
                <div class="category-ranking-top">
                    <div class="top-item"><span class="top-item-name">1. Kancelaria Nowak</span><span class="top-item-score">9.8</span></div>
                    <div class="top-item"><span class="top-item-name">2. Adw. Kowalski</span><span class="top-item-score">9.4</span></div>
                    <div class="top-item"><span class="top-item-name">3. Prawo&Partner</span><span class="top-item-score">9.2</span></div>
                </div>
            </a>

            <a href="#" class="category-ranking-card">
                <div class="category-ranking-icon">🌡️</div>
                <h3 class="category-ranking-title">Instalatorzy pomp ciepła</h3>
                <p class="category-ranking-count">93 firmy</p>
                <div class="category-ranking-top">
                    <div class="top-item"><span class="top-item-name">1. EcoHeat Śląsk</span><span class="top-item-score">9.6</span></div>
                    <div class="top-item"><span class="top-item-name">2. TermoInstal</span><span class="top-item-score">9.4</span></div>
                    <div class="top-item"><span class="top-item-name">3. GreenPump</span><span class="top-item-score">9.1</span></div>
                </div>
            </a>
        </div>

        <!-- TOP RANKING LIST -->
        <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">🏆 Najlepsze firmy remontowe — Katowice</h2>
        <div class="rankings-list">
            <div class="ranking-item gold">
                <div class="ranking-position gold">1</div>
                <div class="ranking-info">
                    <h3>RemontPro Katowice</h3>
                    <div class="ranking-meta">
                        <span class="ranking-stars">★★★★★</span>
                        <span class="ranking-reviews">142 opinie</span>
                        <span class="ranking-location">📍 Katowice</span>
                    </div>
                </div>
                <div class="ranking-score">
                    <div class="ranking-score-value">9.7</div>
                    <div class="ranking-score-label">/ 10</div>
                </div>
            </div>

            <div class="ranking-item silver">
                <div class="ranking-position silver">2</div>
                <div class="ranking-info">
                    <h3>Budmax Wykończenia</h3>
                    <div class="ranking-meta">
                        <span class="ranking-stars">★★★★★</span>
                        <span class="ranking-reviews">98 opinii</span>
                        <span class="ranking-location">📍 Katowice</span>
                    </div>
                </div>
                <div class="ranking-score">
                    <div class="ranking-score-value">9.5</div>
                    <div class="ranking-score-label">/ 10</div>
                </div>
            </div>

            <div class="ranking-item bronze">
                <div class="ranking-position bronze">3</div>
                <div class="ranking-info">
                    <h3>Eko-Dom Remonty</h3>
                    <div class="ranking-meta">
                        <span class="ranking-stars">★★★★☆</span>
                        <span class="ranking-reviews">76 opinii</span>
                        <span class="ranking-location">📍 Katowice</span>
                    </div>
                </div>
                <div class="ranking-score">
                    <div class="ranking-score-value">9.3</div>
                    <div class="ranking-score-label">/ 10</div>
                </div>
            </div>

            <div class="ranking-item">
                <div class="ranking-position default">4</div>
                <div class="ranking-info">
                    <h3>Solidne Wnętrza</h3>
                    <div class="ranking-meta">
                        <span class="ranking-stars">★★★★☆</span>
                        <span class="ranking-reviews">64 opinie</span>
                        <span class="ranking-location">📍 Katowice</span>
                    </div>
                </div>
                <div class="ranking-score">
                    <div class="ranking-score-value">9.0</div>
                    <div class="ranking-score-label">/ 10</div>
                </div>
            </div>

            <div class="ranking-item">
                <div class="ranking-position default">5</div>
                <div class="ranking-info">
                    <h3>Artisan Remont</h3>
                    <div class="ranking-meta">
                        <span class="ranking-stars">★★★★☆</span>
                        <span class="ranking-reviews">51 opinii</span>
                        <span class="ranking-location">📍 Katowice</span>
                    </div>
                </div>
                <div class="ranking-score">
                    <div class="ranking-score-value">8.8</div>
                    <div class="ranking-score-label">/ 10</div>
                </div>
            </div>
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
