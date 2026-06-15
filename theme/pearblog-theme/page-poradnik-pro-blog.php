<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog i Aktualności – Poradnik.pro</title>
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

        /* ===== HEADER ===== */
        .site-header { background: #fff; border-bottom: 1px solid var(--gray-200); position: sticky; top: 0; z-index: 100; }
        .header-inner { display: flex; align-items: center; justify-content: space-between; height: 64px; }
        .logo { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 20px; color: var(--gray-900); }
        .logo-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--purple-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; }
        .main-nav { display: flex; gap: 28px; }
        .main-nav a { font-size: 14px; font-weight: 500; color: var(--gray-600); transition: color 0.2s; }
        .main-nav a:hover, .main-nav a.active { color: var(--purple-primary); }
        .header-actions { display: flex; align-items: center; gap: 12px; }
        .btn-find-specialist { background: var(--purple-primary); color: #fff; padding: 10px 20px; border-radius: 50px; font-size: 13px; font-weight: 600; transition: background 0.2s; }
        .btn-find-specialist:hover { background: var(--purple-dark); }

        /* ===== PAGE HERO ===== */
        .page-hero { background: linear-gradient(135deg, #ede9fe 0%, #dbeafe 100%); padding: 48px 0; }
        .page-hero h1 { font-size: 32px; font-weight: 800; color: var(--gray-900); margin-bottom: 8px; }
        .page-hero p { font-size: 16px; color: var(--gray-600); max-width: 560px; }
        .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-400); margin-bottom: 16px; }
        .breadcrumb a { color: var(--gray-500); transition: color 0.2s; }
        .breadcrumb a:hover { color: var(--purple-primary); }
        .breadcrumb .sep { color: var(--gray-300); }

        /* ===== BLOG LAYOUT ===== */
        .blog-layout { display: grid; grid-template-columns: 1fr 320px; gap: 40px; padding: 48px 0; align-items: start; }

        /* ===== FEATURED POST ===== */
        .featured-post {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 32px;
            transition: box-shadow 0.2s;
        }
        .featured-post:hover { box-shadow: var(--shadow-md); }
        .featured-post-image {
            height: 240px;
            background: linear-gradient(135deg, #6c2bd9, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            position: relative;
        }
        .featured-label {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--orange-cta);
            color: #fff;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
        }
        .featured-post-body { padding: 28px; }
        .featured-post-body .post-category { display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 600; background: #f3e8ff; color: var(--purple-primary); margin-bottom: 12px; }
        .featured-post-body h2 { font-size: 22px; font-weight: 700; color: var(--gray-900); margin-bottom: 10px; }
        .featured-post-body p { font-size: 14px; color: var(--gray-600); line-height: 1.6; margin-bottom: 16px; }
        .post-meta { display: flex; align-items: center; gap: 16px; font-size: 12px; color: var(--gray-400); }

        /* ===== POST CARDS ===== */
        .posts-grid { display: flex; flex-direction: column; gap: 20px; }
        .post-card {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 20px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .post-card:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
        .post-card-image {
            height: 100%;
            min-height: 140px;
            background: linear-gradient(135deg, #ede9fe, #dbeafe);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }
        .post-card-body { padding: 20px 20px 20px 0; display: flex; flex-direction: column; justify-content: center; }
        .post-card-body .post-category { display: inline-block; padding: 3px 8px; border-radius: 50px; font-size: 10px; font-weight: 600; background: #dbeafe; color: var(--blue-accent); margin-bottom: 8px; width: fit-content; }
        .post-card-body h3 { font-size: 16px; font-weight: 600; color: var(--gray-900); margin-bottom: 6px; line-height: 1.3; }
        .post-card-body p { font-size: 13px; color: var(--gray-500); margin-bottom: 10px; }

        /* ===== SIDEBAR ===== */
        .blog-sidebar { position: sticky; top: 88px; }
        .sidebar-widget {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px;
            margin-bottom: 20px;
        }
        .sidebar-widget h4 { font-size: 14px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; }

        .category-list { display: flex; flex-direction: column; gap: 10px; }
        .category-link { display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: var(--gray-600); padding: 8px 12px; border-radius: var(--radius-sm); transition: background 0.2s; }
        .category-link:hover { background: var(--gray-50); color: var(--purple-primary); }
        .category-count { background: var(--gray-100); padding: 2px 8px; border-radius: 50px; font-size: 11px; font-weight: 600; color: var(--gray-500); }

        .popular-posts { display: flex; flex-direction: column; gap: 14px; }
        .popular-post { display: flex; gap: 12px; align-items: center; }
        .popular-post-num { width: 28px; height: 28px; border-radius: 50%; background: var(--purple-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .popular-post-title { font-size: 13px; color: var(--gray-700); font-weight: 500; line-height: 1.3; }

        .newsletter-widget { background: linear-gradient(135deg, #1a0a3e, #6c2bd9); border: none; }
        .newsletter-widget h4 { color: #fff; }
        .newsletter-widget p { font-size: 13px; color: rgba(255,255,255,0.7); margin-bottom: 14px; }
        .newsletter-input { width: 100%; padding: 10px 14px; border: 1px solid rgba(255,255,255,0.2); border-radius: var(--radius-sm); background: rgba(255,255,255,0.1); color: #fff; font-size: 13px; margin-bottom: 10px; }
        .newsletter-input::placeholder { color: rgba(255,255,255,0.5); }
        .newsletter-btn { width: 100%; padding: 10px; border-radius: var(--radius-sm); background: var(--orange-cta); color: #fff; font-size: 13px; font-weight: 600; transition: background 0.2s; }
        .newsletter-btn:hover { background: var(--orange-hover); }

        /* ===== PAGINATION ===== */
        .pagination { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 0 0; }
        .page-btn { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500; color: var(--gray-600); border: 1px solid var(--gray-200); background: #fff; transition: all 0.2s; }
        .page-btn:hover { border-color: var(--purple-primary); color: var(--purple-primary); }
        .page-btn.active { background: var(--purple-primary); color: #fff; border-color: var(--purple-primary); }

        /* ===== FOOTER ===== */
        .site-footer { background: var(--gray-900); color: rgba(255,255,255,0.6); padding: 48px 0 24px; }
        .footer-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { color: rgba(255,255,255,0.5); transition: color 0.2s; }
        .footer-links a:hover { color: #fff; }

        @media (max-width: 900px) {
            .blog-layout { grid-template-columns: 1fr; }
            .blog-sidebar { position: static; }
            .post-card { grid-template-columns: 1fr; }
            .post-card-image { min-height: 160px; }
            .post-card-body { padding: 16px; }
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
            <span>Blog</span>
        </div>
        <h1>📰 Blog i Aktualności</h1>
        <p>Bądź na bieżąco z rynkiem usług, zmianami w prawie i nowymi trendami. Eksperci piszą, Ty zyskujesz wiedzę.</p>
    </div>
</section>

<!-- BLOG LAYOUT -->
<div class="container">
    <div class="blog-layout">
        <!-- MAIN CONTENT -->
        <main>
            <!-- FEATURED POST -->
            <a href="#" class="featured-post">
                <div class="featured-post-image">
                    <span class="featured-label">🔥 Wyróżniony</span>
                    📊
                </div>
                <div class="featured-post-body">
                    <span class="post-category">Rynek usług</span>
                    <h2>Ceny remontów w 2026 — raport kwartalny Q2</h2>
                    <p>Analiza aktualnych cen usług remontowych w 16 największych miastach Polski. Sprawdź, jak zmieniły się stawki glazurników, hydraulików i elektryków w porównaniu z Q1.</p>
                    <div class="post-meta">
                        <span>📅 10 czerwca 2026</span>
                        <span>📖 8 min czytania</span>
                        <span>💬 24 komentarze</span>
                    </div>
                </div>
            </a>

            <!-- POSTS GRID -->
            <div class="posts-grid">
                <a href="#" class="post-card">
                    <div class="post-card-image">⚖️</div>
                    <div class="post-card-body">
                        <span class="post-category">Prawo</span>
                        <h3>Nowe przepisy o rękojmi 2026 — co się zmienia dla konsumentów?</h3>
                        <p>Od lipca 2026 obowiązują nowe zasady reklamacji usług. Sprawdź swoje prawa.</p>
                        <div class="post-meta">
                            <span>📅 8 czerwca 2026</span>
                            <span>📖 5 min</span>
                        </div>
                    </div>
                </a>

                <a href="#" class="post-card">
                    <div class="post-card-image">🌡️</div>
                    <div class="post-card-body">
                        <span class="post-category">Energia</span>
                        <h3>Dotacja na pompę ciepła 2026 — nowe zasady programu Czyste Powietrze</h3>
                        <p>Rząd podniósł limity dofinansowania. Ile możesz zyskać i jak złożyć wniosek?</p>
                        <div class="post-meta">
                            <span>📅 5 czerwca 2026</span>
                            <span>📖 6 min</span>
                        </div>
                    </div>
                </a>

                <a href="#" class="post-card">
                    <div class="post-card-image">💳</div>
                    <div class="post-card-body">
                        <span class="post-category">Finanse</span>
                        <h3>Stopy procentowe w dół — co to oznacza dla Twojego kredytu?</h3>
                        <p>RPP obniżyła stopy o 0,25 pp. Ile zaoszczędzisz na racie i czy warto refinansować?</p>
                        <div class="post-meta">
                            <span>📅 3 czerwca 2026</span>
                            <span>📖 4 min</span>
                        </div>
                    </div>
                </a>

                <a href="#" class="post-card">
                    <div class="post-card-image">🏠</div>
                    <div class="post-card-body">
                        <span class="post-category">Nieruchomości</span>
                        <h3>Rynek nieruchomości Q2 2026 — ceny mieszkań stabilizacja czy wzrost?</h3>
                        <p>Analiza cen transakcyjnych w 10 miastach. Czy to dobry moment na zakup?</p>
                        <div class="post-meta">
                            <span>📅 1 czerwca 2026</span>
                            <span>📖 7 min</span>
                        </div>
                    </div>
                </a>

                <a href="#" class="post-card">
                    <div class="post-card-image">🚗</div>
                    <div class="post-card-body">
                        <span class="post-category">Motoryzacja</span>
                        <h3>Ubezpieczenie OC 2026 — ranking najtańszych ofert + porady</h3>
                        <p>Porównaliśmy 12 ubezpieczycieli. Kto oferuje najlepszą cenę za OC w Twojej grupie wiekowej?</p>
                        <div class="post-meta">
                            <span>📅 28 maja 2026</span>
                            <span>📖 5 min</span>
                        </div>
                    </div>
                </a>

                <a href="#" class="post-card">
                    <div class="post-card-image">☀️</div>
                    <div class="post-card-body">
                        <span class="post-category">Energia</span>
                        <h3>Net-billing 2026 — czy fotowoltaika nadal się opłaca? Nowa kalkulacja</h3>
                        <p>Zaktualizowane wyliczenia zwrotu inwestycji w panele po zmianach taryfowych.</p>
                        <div class="post-meta">
                            <span>📅 25 maja 2026</span>
                            <span>📖 6 min</span>
                        </div>
                    </div>
                </a>

                <a href="#" class="post-card">
                    <div class="post-card-image">👷</div>
                    <div class="post-card-body">
                        <span class="post-category">Poradnik.pro</span>
                        <h3>Jak wybrać ekipę remontową — 10 red flags, na które musisz uważać</h3>
                        <p>Doświadczenie 50 000 użytkowników w jednym artykule. Te sygnały oznaczają kłopoty.</p>
                        <div class="post-meta">
                            <span>📅 22 maja 2026</span>
                            <span>📖 9 min</span>
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
        </main>

        <!-- SIDEBAR -->
        <aside class="blog-sidebar">
            <div class="sidebar-widget">
                <h4>📂 Kategorie</h4>
                <div class="category-list">
                    <a href="#" class="category-link">
                        <span>Budownictwo i remont</span>
                        <span class="category-count">34</span>
                    </a>
                    <a href="#" class="category-link">
                        <span>Energia i OZE</span>
                        <span class="category-count">21</span>
                    </a>
                    <a href="#" class="category-link">
                        <span>Prawo i przepisy</span>
                        <span class="category-count">18</span>
                    </a>
                    <a href="#" class="category-link">
                        <span>Finanse osobiste</span>
                        <span class="category-count">15</span>
                    </a>
                    <a href="#" class="category-link">
                        <span>Nieruchomości</span>
                        <span class="category-count">12</span>
                    </a>
                    <a href="#" class="category-link">
                        <span>Motoryzacja</span>
                        <span class="category-count">9</span>
                    </a>
                    <a href="#" class="category-link">
                        <span>Zdrowie</span>
                        <span class="category-count">7</span>
                    </a>
                </div>
            </div>

            <div class="sidebar-widget">
                <h4>🔥 Popularne</h4>
                <div class="popular-posts">
                    <a href="#" class="popular-post">
                        <span class="popular-post-num">1</span>
                        <span class="popular-post-title">Koszt remontu łazienki 2026</span>
                    </a>
                    <a href="#" class="popular-post">
                        <span class="popular-post-num">2</span>
                        <span class="popular-post-title">Pompa ciepła vs gaz — co się opłaca?</span>
                    </a>
                    <a href="#" class="popular-post">
                        <span class="popular-post-num">3</span>
                        <span class="popular-post-title">Kredyt hipoteczny — poradnik 2026</span>
                    </a>
                    <a href="#" class="popular-post">
                        <span class="popular-post-num">4</span>
                        <span class="popular-post-title">Jak sprzedać działkę krok po kroku</span>
                    </a>
                    <a href="#" class="popular-post">
                        <span class="popular-post-num">5</span>
                        <span class="popular-post-title">Fotowoltaika — czy nadal się opłaca?</span>
                    </a>
                </div>
            </div>

            <div class="sidebar-widget newsletter-widget">
                <h4>📬 Newsletter</h4>
                <p>Otrzymuj najważniejsze porady i aktualności prosto na email — raz w tygodniu.</p>
                <input type="email" class="newsletter-input" placeholder="Twój adres email" aria-label="Adres email">
                <button class="newsletter-btn">Zapisz się za darmo</button>
            </div>
        </aside>
    </div>
</div>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-bottom">
            <span>&copy; 2026 Poradnik.pro. Wszelkie prawa zastrzeżone.</span>
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
