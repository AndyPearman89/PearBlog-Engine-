<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eksperci – Poradnik.pro</title>
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
        .page-hero { background: linear-gradient(135deg, #ede9fe 0%, #f3e8ff 100%); padding: 48px 0; }
        .page-hero h1 { font-size: 32px; font-weight: 800; color: var(--gray-900); margin-bottom: 8px; }
        .page-hero p { font-size: 16px; color: var(--gray-600); max-width: 560px; }
        .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-400); margin-bottom: 16px; }
        .breadcrumb a { color: var(--gray-500); }
        .breadcrumb a:hover { color: var(--purple-primary); }
        .breadcrumb .sep { color: var(--gray-300); }

        /* ===== SEARCH ===== */
        .experts-search {
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 0;
        }
        .search-inner {
            display: flex;
            gap: 12px;
        }
        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid var(--gray-200);
            border-radius: 50px;
            font-size: 14px;
            color: var(--gray-700);
            outline: none;
            transition: border-color 0.2s;
        }
        .search-input:focus { border-color: var(--purple-primary); }
        .search-input::placeholder { color: var(--gray-400); }
        .search-select {
            padding: 12px 20px;
            border: 1px solid var(--gray-200);
            border-radius: 50px;
            font-size: 14px;
            color: var(--gray-600);
            background: #fff;
            min-width: 180px;
            outline: none;
        }
        .btn-search-experts {
            background: var(--orange-cta);
            color: #fff;
            padding: 12px 28px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-search-experts:hover { background: var(--orange-hover); }

        /* ===== EXPERTS GRID ===== */
        .experts-section { padding: 48px 0; }
        .experts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .expert-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 28px;
            text-align: center;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .expert-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .expert-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--gray-200);
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: var(--gray-500);
        }
        .expert-verified {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 600;
            background: #d1fae5;
            color: #059669;
            margin-bottom: 10px;
        }
        .expert-name { font-size: 16px; font-weight: 700; color: var(--gray-900); margin-bottom: 4px; }
        .expert-specialty { font-size: 13px; color: var(--purple-primary); font-weight: 500; margin-bottom: 8px; }
        .expert-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            color: var(--gray-600);
            margin-bottom: 6px;
        }
        .expert-stars { color: var(--yellow-accent); }
        .expert-location { font-size: 12px; color: var(--gray-400); margin-bottom: 16px; }
        .expert-tags { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-bottom: 16px; }
        .expert-tag { padding: 4px 10px; border-radius: 50px; font-size: 11px; background: var(--gray-100); color: var(--gray-600); }
        .btn-contact {
            display: inline-block;
            background: var(--purple-primary);
            color: #fff;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
            width: 100%;
        }
        .btn-contact:hover { background: var(--purple-dark); }

        /* ===== CTA SECTION ===== */
        .cta-section {
            background: linear-gradient(135deg, #1a0a3e, #6c2bd9);
            border-radius: var(--radius-lg);
            padding: 48px;
            text-align: center;
            margin: 40px 0;
        }
        .cta-section h2 { color: #fff; font-size: 24px; font-weight: 800; margin-bottom: 12px; }
        .cta-section p { color: rgba(255,255,255,0.7); font-size: 15px; margin-bottom: 24px; }
        .btn-cta {
            display: inline-block;
            background: var(--orange-cta);
            color: #fff;
            padding: 14px 36px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 700;
            transition: background 0.2s;
        }
        .btn-cta:hover { background: var(--orange-hover); }

        /* ===== FOOTER ===== */
        .site-footer { background: var(--gray-900); color: rgba(255,255,255,0.6); padding: 48px 0 24px; }
        .footer-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { color: rgba(255,255,255,0.5); transition: color 0.2s; }
        .footer-links a:hover { color: #fff; }

        @media (max-width: 768px) {
            .experts-grid { grid-template-columns: 1fr; }
            .search-inner { flex-direction: column; }
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
                <a href="/rankingi">Rankingi</a>
                <a href="/kalkulatory">Kalkulatory</a>
                <a href="/eksperci" class="active">Eksperci</a>
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
            <span>Eksperci</span>
        </div>
        <h1>🧑‍💼 Eksperci</h1>
        <p>Decyzja to dopiero początek — teraz wykonanie. Profile specjalistów, opinie użytkowników, szybkie zapytania.</p>
    </div>
</section>

<!-- SEARCH -->
<div class="experts-search">
    <div class="container">
        <div class="search-inner">
            <input type="text" class="search-input" placeholder="Szukaj specjalisty, np. prawnik Katowice..." aria-label="Szukaj specjalisty">
            <select class="search-select" aria-label="Kategoria">
                <option>Wszystkie kategorie</option>
                <option>Prawo</option>
                <option>Budownictwo</option>
                <option>Finanse</option>
                <option>Nieruchomości</option>
                <option>Zdrowie</option>
            </select>
            <button class="btn-search-experts">🔍 Szukaj</button>
        </div>
    </div>
</div>

<!-- EXPERTS GRID -->
<section class="experts-section">
    <div class="container">
        <div class="experts-grid">
            <div class="expert-card">
                <div class="expert-avatar">👨‍⚖️</div>
                <span class="expert-verified">✓ Zweryfikowany</span>
                <h3 class="expert-name">mec. Jan Nowak</h3>
                <p class="expert-specialty">Prawnik — Prawo rodzinne</p>
                <div class="expert-rating">
                    <span class="expert-stars">★★★★★</span>
                    <span>4.9 (87 opinii)</span>
                </div>
                <p class="expert-location">📍 Katowice</p>
                <div class="expert-tags">
                    <span class="expert-tag">Rozwód</span>
                    <span class="expert-tag">Alimenty</span>
                    <span class="expert-tag">Opieka</span>
                </div>
                <a href="#" class="btn-contact">Wyślij zapytanie</a>
            </div>

            <div class="expert-card">
                <div class="expert-avatar">👷</div>
                <span class="expert-verified">✓ Zweryfikowany</span>
                <h3 class="expert-name">Marek Kowalski</h3>
                <p class="expert-specialty">Firma remontowa</p>
                <div class="expert-rating">
                    <span class="expert-stars">★★★★★</span>
                    <span>4.8 (142 opinie)</span>
                </div>
                <p class="expert-location">📍 Katowice</p>
                <div class="expert-tags">
                    <span class="expert-tag">Remont</span>
                    <span class="expert-tag">Łazienka</span>
                    <span class="expert-tag">Kuchnia</span>
                </div>
                <a href="#" class="btn-contact">Wyślij zapytanie</a>
            </div>

            <div class="expert-card">
                <div class="expert-avatar">👨‍💼</div>
                <span class="expert-verified">✓ Zweryfikowany</span>
                <h3 class="expert-name">Piotr Wiśniewski</h3>
                <p class="expert-specialty">Doradca finansowy</p>
                <div class="expert-rating">
                    <span class="expert-stars">★★★★★</span>
                    <span>4.9 (63 opinie)</span>
                </div>
                <p class="expert-location">📍 Warszawa</p>
                <div class="expert-tags">
                    <span class="expert-tag">Kredyt</span>
                    <span class="expert-tag">Inwestycje</span>
                    <span class="expert-tag">Podatki</span>
                </div>
                <a href="#" class="btn-contact">Wyślij zapytanie</a>
            </div>

            <div class="expert-card">
                <div class="expert-avatar">👩‍🔧</div>
                <span class="expert-verified">✓ Zweryfikowany</span>
                <h3 class="expert-name">Anna Zielińska</h3>
                <p class="expert-specialty">Instalator pomp ciepła</p>
                <div class="expert-rating">
                    <span class="expert-stars">★★★★☆</span>
                    <span>4.7 (45 opinii)</span>
                </div>
                <p class="expert-location">📍 Kraków</p>
                <div class="expert-tags">
                    <span class="expert-tag">Pompa ciepła</span>
                    <span class="expert-tag">OZE</span>
                </div>
                <a href="#" class="btn-contact">Wyślij zapytanie</a>
            </div>

            <div class="expert-card">
                <div class="expert-avatar">👨‍⚕️</div>
                <span class="expert-verified">✓ Zweryfikowany</span>
                <h3 class="expert-name">dr Tomasz Baran</h3>
                <p class="expert-specialty">Stomatolog</p>
                <div class="expert-rating">
                    <span class="expert-stars">★★★★★</span>
                    <span>5.0 (112 opinii)</span>
                </div>
                <p class="expert-location">📍 Wrocław</p>
                <div class="expert-tags">
                    <span class="expert-tag">Implanty</span>
                    <span class="expert-tag">Ortodoncja</span>
                </div>
                <a href="#" class="btn-contact">Wyślij zapytanie</a>
            </div>

            <div class="expert-card">
                <div class="expert-avatar">👩‍💻</div>
                <span class="expert-verified">✓ Zweryfikowany</span>
                <h3 class="expert-name">Katarzyna Maj</h3>
                <p class="expert-specialty">Architekt wnętrz</p>
                <div class="expert-rating">
                    <span class="expert-stars">★★★★★</span>
                    <span>4.8 (78 opinii)</span>
                </div>
                <p class="expert-location">📍 Gdańsk</p>
                <div class="expert-tags">
                    <span class="expert-tag">Projekt</span>
                    <span class="expert-tag">Wnętrza</span>
                    <span class="expert-tag">3D</span>
                </div>
                <a href="#" class="btn-contact">Wyślij zapytanie</a>
            </div>
        </div>

        <!-- CTA -->
        <div class="cta-section">
            <h2>Jeden formularz → kilka ofert → wybór</h2>
            <p>Opisz swoje potrzeby, a odpowiedni specjaliści skontaktują się z Tobą</p>
            <a href="#" class="btn-cta">Wyślij zapytanie do ekspertów</a>
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
