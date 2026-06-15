<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Porównania – Poradnik.pro</title>
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

        /* ===== HEADER ===== */
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
        .main-nav a { font-size: 14px; font-weight: 500; color: var(--gray-600); transition: color 0.2s; }
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
            background: linear-gradient(135deg, #dbeafe 0%, #ede9fe 100%);
            padding: 48px 0;
        }
        .page-hero h1 { font-size: 32px; font-weight: 800; color: var(--gray-900); margin-bottom: 8px; }
        .page-hero p { font-size: 16px; color: var(--gray-600); max-width: 560px; }
        .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-400); margin-bottom: 16px; }
        .breadcrumb a { color: var(--gray-500); transition: color 0.2s; }
        .breadcrumb a:hover { color: var(--purple-primary); }
        .breadcrumb .sep { color: var(--gray-300); }

        /* ===== COMPARISON CARDS ===== */
        .comparisons-section { padding: 48px 0; }
        .comparisons-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .comparison-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 28px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .comparison-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .comparison-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            background: #dbeafe;
            color: var(--blue-accent);
            margin-bottom: 14px;
        }
        .comparison-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 10px;
        }
        .comparison-desc {
            font-size: 13px;
            color: var(--gray-500);
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .comparison-vs {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        .vs-option {
            flex: 1;
            background: var(--gray-50);
            border-radius: var(--radius-sm);
            padding: 14px;
            text-align: center;
        }
        .vs-option-icon { font-size: 28px; margin-bottom: 6px; }
        .vs-option-label { font-size: 13px; font-weight: 600; color: var(--gray-700); }
        .vs-divider {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--orange-cta);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
            flex-shrink: 0;
        }
        .comparison-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .comparison-tag {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            background: var(--gray-100);
            color: var(--gray-600);
        }
        .comparison-tag.green { background: #d1fae5; color: #059669; }

        /* ===== VERDICT BOX ===== */
        .verdict-section {
            background: #fff;
            border: 2px solid var(--green-accent);
            border-radius: var(--radius-lg);
            padding: 32px;
            margin-bottom: 40px;
        }
        .verdict-section h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .verdict-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .verdict-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--gray-700);
        }
        .verdict-check { color: var(--green-accent); font-weight: 700; }

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
        .page-btn.active { background: var(--purple-primary); color: #fff; border-color: var(--purple-primary); }

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

        @media (max-width: 768px) {
            .comparisons-grid { grid-template-columns: 1fr; }
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
                <a href="/poradniki">Poradniki</a>
                <a href="/porownania" class="active">Porównania</a>
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
            <span>Porównania</span>
        </div>
        <h1>🆚 Porównania</h1>
        <p>Najważniejszy moment: wybór. Jasne różnice, realne koszty, konkretny werdykt — wiesz co wybrać i dlaczego.</p>
    </div>
</section>

<!-- VERDICT BOX -->
<section class="comparisons-section">
    <div class="container">
        <div class="verdict-section">
            <h2>✔ Jak działają nasze porównania</h2>
            <div class="verdict-list">
                <div class="verdict-item"><span class="verdict-check">✓</span> Jasne różnice między opcjami</div>
                <div class="verdict-item"><span class="verdict-check">✓</span> Realne koszty i oszczędności</div>
                <div class="verdict-item"><span class="verdict-check">✓</span> Konkretny werdykt — wiemy co polecamy</div>
                <div class="verdict-item"><span class="verdict-check">✓</span> Dopasowani wykonawcy na końcu</div>
            </div>
        </div>

        <!-- COMPARISONS GRID -->
        <div class="comparisons-grid">
            <a href="#" class="comparison-card">
                <span class="comparison-badge">🔥 Popularne</span>
                <h3 class="comparison-title">Pompa ciepła vs Gaz</h3>
                <p class="comparison-desc">Co się bardziej opłaca w 2026? Porównanie kosztów instalacji, eksploatacji i zwrotu inwestycji.</p>
                <div class="comparison-vs">
                    <div class="vs-option">
                        <div class="vs-option-icon">🌡️</div>
                        <div class="vs-option-label">Pompa ciepła</div>
                    </div>
                    <div class="vs-divider">VS</div>
                    <div class="vs-option">
                        <div class="vs-option-icon">🔥</div>
                        <div class="vs-option-label">Ogrzewanie gazowe</div>
                    </div>
                </div>
                <div class="comparison-tags">
                    <span class="comparison-tag">Ogrzewanie</span>
                    <span class="comparison-tag green">Werdykt dostępny</span>
                </div>
            </a>

            <a href="#" class="comparison-card">
                <span class="comparison-badge">⚡ Trending</span>
                <h3 class="comparison-title">Fotowoltaika vs Prąd z sieci</h3>
                <p class="comparison-desc">Czy panele fotowoltaiczne wciąż się opłacają? Nowe zasady net-billingu 2026.</p>
                <div class="comparison-vs">
                    <div class="vs-option">
                        <div class="vs-option-icon">☀️</div>
                        <div class="vs-option-label">Fotowoltaika</div>
                    </div>
                    <div class="vs-divider">VS</div>
                    <div class="vs-option">
                        <div class="vs-option-icon">⚡</div>
                        <div class="vs-option-label">Prąd z sieci</div>
                    </div>
                </div>
                <div class="comparison-tags">
                    <span class="comparison-tag">Energia</span>
                    <span class="comparison-tag green">Werdykt dostępny</span>
                </div>
            </a>

            <a href="#" class="comparison-card">
                <span class="comparison-badge">🏠 Dom</span>
                <h3 class="comparison-title">Remont vs Wykończenie pod klucz</h3>
                <p class="comparison-desc">Samodzielna organizacja remontu czy firma pod klucz? Porównanie kosztów, czasu i stresu.</p>
                <div class="comparison-vs">
                    <div class="vs-option">
                        <div class="vs-option-icon">🔨</div>
                        <div class="vs-option-label">Samodzielny remont</div>
                    </div>
                    <div class="vs-divider">VS</div>
                    <div class="vs-option">
                        <div class="vs-option-icon">🏗️</div>
                        <div class="vs-option-label">Pod klucz</div>
                    </div>
                </div>
                <div class="comparison-tags">
                    <span class="comparison-tag">Remont</span>
                    <span class="comparison-tag green">Werdykt dostępny</span>
                </div>
            </a>

            <a href="#" class="comparison-card">
                <span class="comparison-badge">💰 Finanse</span>
                <h3 class="comparison-title">Kredyt stały vs Kredyt zmienny</h3>
                <p class="comparison-desc">Stała czy zmienna stopa procentowa? Który kredyt hipoteczny wybrać w 2026.</p>
                <div class="comparison-vs">
                    <div class="vs-option">
                        <div class="vs-option-icon">📊</div>
                        <div class="vs-option-label">Oprocentowanie stałe</div>
                    </div>
                    <div class="vs-divider">VS</div>
                    <div class="vs-option">
                        <div class="vs-option-icon">📈</div>
                        <div class="vs-option-label">Oprocentowanie zmienne</div>
                    </div>
                </div>
                <div class="comparison-tags">
                    <span class="comparison-tag">Kredyt</span>
                    <span class="comparison-tag green">Werdykt dostępny</span>
                </div>
            </a>

            <a href="#" class="comparison-card">
                <span class="comparison-badge">🏠 Dom</span>
                <h3 class="comparison-title">Docieplenie styropian vs Wełna mineralna</h3>
                <p class="comparison-desc">Który materiał izolacyjny wybrać? Porównanie cen, parametrów i trwałości.</p>
                <div class="comparison-vs">
                    <div class="vs-option">
                        <div class="vs-option-icon">⬜</div>
                        <div class="vs-option-label">Styropian EPS</div>
                    </div>
                    <div class="vs-divider">VS</div>
                    <div class="vs-option">
                        <div class="vs-option-icon">🟨</div>
                        <div class="vs-option-label">Wełna mineralna</div>
                    </div>
                </div>
                <div class="comparison-tags">
                    <span class="comparison-tag">Termomodernizacja</span>
                    <span class="comparison-tag green">Werdykt dostępny</span>
                </div>
            </a>

            <a href="#" class="comparison-card">
                <span class="comparison-badge">🚗 Auto</span>
                <h3 class="comparison-title">Auto elektryczne vs Hybryda plug-in</h3>
                <p class="comparison-desc">Koszty eksploatacji, zasięg i wygoda — które rozwiązanie bardziej się opłaca w Polsce?</p>
                <div class="comparison-vs">
                    <div class="vs-option">
                        <div class="vs-option-icon">🔋</div>
                        <div class="vs-option-label">100% Elektryczny</div>
                    </div>
                    <div class="vs-divider">VS</div>
                    <div class="vs-option">
                        <div class="vs-option-icon">⛽</div>
                        <div class="vs-option-label">Hybryda PHEV</div>
                    </div>
                </div>
                <div class="comparison-tags">
                    <span class="comparison-tag">Motoryzacja</span>
                    <span class="comparison-tag green">Werdykt dostępny</span>
                </div>
            </a>

            <a href="#" class="comparison-card">
                <span class="comparison-badge">🦷 Zdrowie</span>
                <h3 class="comparison-title">Implant vs Most protetyczny</h3>
                <p class="comparison-desc">Trwałość, komfort i cena — co wybrać przy uzupełnianiu brakujących zębów?</p>
                <div class="comparison-vs">
                    <div class="vs-option">
                        <div class="vs-option-icon">🔩</div>
                        <div class="vs-option-label">Implant</div>
                    </div>
                    <div class="vs-divider">VS</div>
                    <div class="vs-option">
                        <div class="vs-option-icon">🦷</div>
                        <div class="vs-option-label">Most protetyczny</div>
                    </div>
                </div>
                <div class="comparison-tags">
                    <span class="comparison-tag">Stomatologia</span>
                    <span class="comparison-tag green">Werdykt dostępny</span>
                </div>
            </a>
        </div>

        <!-- PAGINATION -->
        <div class="pagination">
            <button class="page-btn active">1</button>
            <button class="page-btn">2</button>
            <button class="page-btn">3</button>
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
