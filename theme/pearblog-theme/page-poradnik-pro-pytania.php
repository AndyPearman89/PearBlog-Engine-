<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pytania i odpowiedzi – Poradnik.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #6c2bd9;
            --primary-dark: #4f1daa;
            --primary-soft: #ede9fe;
            --accent: #ef5a3c;
            --accent-dark: #d9482b;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-900: #0f172a;
            --shadow-sm: 0 8px 24px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 18px 40px rgba(15, 23, 42, 0.08);
            --radius-sm: 10px;
            --radius-md: 18px;
            --radius-lg: 24px;
            --max-width: 1180px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--gray-900);
            background: linear-gradient(180deg, #ffffff 0%, #f8f7ff 48%, #f8fafc 100%);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        button, select { font: inherit; }
        ul { list-style: none; }

        .container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 24px;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255,255,255,0.94);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(226,232,240,0.9);
        }

        .header-inner {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 800;
        }

        .logo-mark {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #9b6bff);
            color: var(--white);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .main-nav {
            display: flex;
            align-items: center;
            gap: 28px;
            color: var(--gray-600);
            font-size: 14px;
            font-weight: 500;
        }

        .main-nav a.active,
        .main-nav a:hover {
            color: var(--primary);
        }

        .btn-find-expert {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 999px;
            background: var(--primary);
            color: var(--white);
            font-size: 14px;
            font-weight: 700;
            transition: background 0.2s ease;
        }

        .btn-find-expert:hover { background: var(--primary-dark); }

        .hero {
            padding: 56px 0 28px;
        }

        .hero-panel {
            background: linear-gradient(135deg, #ffffff 0%, #f3efff 100%);
            border: 1px solid rgba(108,43,217,0.08);
            border-radius: 28px;
            padding: 38px;
            box-shadow: var(--shadow-sm);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 18px;
        }

        .hero h1 {
            font-size: clamp(32px, 5vw, 48px);
            line-height: 1.1;
            margin-bottom: 12px;
        }

        .hero p {
            max-width: 620px;
            color: var(--gray-600);
            font-size: 18px;
        }

        .filters-wrap {
            padding: 18px 0 42px;
        }

        .filters-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 20px 22px;
            box-shadow: var(--shadow-sm);
        }

        .filters-left {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .category-select {
            min-width: 210px;
            padding: 13px 16px;
            border: 1px solid var(--gray-200);
            border-radius: 999px;
            background: var(--white);
            color: var(--gray-700);
            outline: none;
        }

        .tabs {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
            border-radius: 999px;
            background: var(--gray-100);
        }

        .tab {
            padding: 10px 16px;
            border-radius: 999px;
            color: var(--gray-600);
            font-size: 14px;
            font-weight: 600;
            background: transparent;
        }

        .tab.active {
            background: var(--white);
            color: var(--primary);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        }

        .btn-ask {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 22px;
            border-radius: 999px;
            background: var(--accent);
            color: var(--white);
            font-weight: 700;
            box-shadow: 0 12px 24px rgba(239, 90, 60, 0.22);
        }

        .btn-ask:hover { background: var(--accent-dark); }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 28px;
            padding-bottom: 64px;
        }

        .questions-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .question-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .question-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 16px;
        }

        .question-main {
            display: flex;
            gap: 14px;
            min-width: 0;
        }

        .avatar {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
        }

        .question-copy {
            min-width: 0;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
        }

        .mini-meta {
            color: var(--gray-500);
            font-size: 13px;
        }

        .question-title {
            display: inline-block;
            font-size: clamp(20px, 2.4vw, 25px);
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 10px;
        }

        .question-title:hover { color: var(--primary); }

        .question-desc {
            color: var(--gray-600);
            font-size: 15px;
            max-width: 760px;
        }

        .answers-box {
            flex-shrink: 0;
            min-width: 124px;
            padding: 14px 16px;
            border-radius: 18px;
            background: #fff7ed;
            color: #c2410c;
            text-align: center;
        }

        .answers-box strong {
            display: block;
            font-size: 24px;
            line-height: 1;
            margin-bottom: 4px;
        }

        .answers-box span {
            font-size: 13px;
            font-weight: 600;
        }

        .question-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding-top: 16px;
            border-top: 1px solid var(--gray-100);
        }

        .footer-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            color: var(--gray-500);
            font-size: 13px;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--gray-100);
            color: var(--gray-700);
            font-size: 12px;
            font-weight: 700;
        }

        .status-chip.unanswered {
            background: #fef2f2;
            color: #dc2626;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .sidebar-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-card h2 {
            font-size: 18px;
            margin-bottom: 12px;
        }

        .sidebar-card p,
        .sidebar-card li {
            color: var(--gray-600);
            font-size: 14px;
        }

        .sidebar-card ul {
            display: grid;
            gap: 12px;
        }

        .sidebar-card li {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .count {
            color: var(--primary);
            font-weight: 700;
        }

        .cta-panel {
            background: linear-gradient(135deg, #19093d 0%, #6c2bd9 100%);
            color: var(--white);
        }

        .cta-panel p { color: rgba(255,255,255,0.76); }

        .cta-panel .btn-find-expert {
            margin-top: 18px;
            width: 100%;
            background: var(--white);
            color: var(--primary);
        }

        .site-footer {
            border-top: 1px solid var(--gray-200);
            background: var(--white);
            padding: 26px 0;
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            color: var(--gray-500);
            font-size: 13px;
        }

        .footer-links {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        @media (max-width: 1024px) {
            .content-grid { grid-template-columns: 1fr; }
            .sidebar { order: -1; }
        }

        @media (max-width: 860px) {
            .main-nav { display: none; }
            .hero-panel { padding: 28px; }
            .question-top { flex-direction: column; }
            .answers-box { width: 100%; }
        }

        @media (max-width: 640px) {
            .container { padding: 0 18px; }
            .header-inner { min-height: 68px; }
            .hero { padding-top: 32px; }
            .filters-bar { padding: 16px; }
            .filters-left,
            .tabs,
            .category-select,
            .btn-ask { width: 100%; }
            .tabs { justify-content: space-between; }
            .tab { flex: 1; text-align: center; }
            .question-card { padding: 18px; }
            .question-main { flex-direction: column; }
            .avatar { width: 42px; height: 42px; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo"><span class="logo-mark">P</span> Poradnik.pro</a>
                <nav class="main-nav">
                    <a href="/poradniki">Poradniki</a>
                    <a href="/porownania">Porównania</a>
                    <a href="/rankingi">Rankingi</a>
                    <a href="/eksperci">Eksperci</a>
                    <a href="/pytania" class="active">Pytania</a>
                </nav>
                <a href="/eksperci" class="btn-find-expert">Znajdź eksperta</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-panel">
                    <span class="eyebrow">Społeczność pytań i odpowiedzi</span>
                    <h1>Pytania i odpowiedzi</h1>
                    <p>Uzyskaj odpowiedź od eksperta i sprawdź, jak inni rozwiązują codzienne problemy związane z prawem, finansami, nieruchomościami i domem.</p>
                </div>
            </div>
        </section>

        <section class="filters-wrap">
            <div class="container">
                <div class="filters-bar">
                    <div class="filters-left">
                        <select class="category-select" aria-label="Wybierz kategorię">
                            <option selected>Nieruchomości</option>
                            <option>Prawo</option>
                            <option>Finanse</option>
                            <option>Dom i ogród</option>
                            <option>Ubezpieczenia</option>
                        </select>
                        <div class="tabs" aria-label="Filtry pytań">
                            <button class="tab active" type="button">Najnowsze</button>
                            <button class="tab" type="button">Popularne</button>
                            <button class="tab" type="button">Bez odpowiedzi</button>
                        </div>
                    </div>
                    <a href="/zadaj-pytanie" class="btn-ask">✍️ Zadaj pytanie</a>
                </div>
            </div>
        </section>

        <section class="container content-grid">
            <div class="questions-list">
                <article class="question-card">
                    <div class="question-top">
                        <div class="question-main">
                            <div class="avatar" aria-hidden="true"></div>
                            <div class="question-copy">
                                <div class="meta-row">
                                    <span class="tag">Nieruchomości</span>
                                    <span class="mini-meta">dodano 2 godz. temu</span>
                                </div>
                                <a href="#" class="question-title">Czy mogę budować dom na działce rolnej?</a>
                                <p class="question-desc">Interesuje mnie zakup działki klasy IV. Chcę sprawdzić, czy potrzebne będzie odrolnienie i jakie formalności trzeba załatwić przed rozpoczęciem budowy.</p>
                            </div>
                        </div>
                        <div class="answers-box"><strong>5</strong><span>odpowiedzi</span></div>
                    </div>
                    <div class="question-footer">
                        <div class="footer-meta">
                            <span>Anna • Warszawa</span>
                            <span>1,2 tys. wyświetleń</span>
                            <span>ostatnia odpowiedź 20 min temu</span>
                        </div>
                        <span class="status-chip">Zweryfikowany ekspert odpowiedział</span>
                    </div>
                </article>

                <article class="question-card">
                    <div class="question-top">
                        <div class="question-main">
                            <div class="avatar" aria-hidden="true"></div>
                            <div class="question-copy">
                                <div class="meta-row">
                                    <span class="tag">Finanse</span>
                                    <span class="mini-meta">dzisiaj</span>
                                </div>
                                <a href="#" class="question-title">Jak obliczyć ratę kredytu hipotecznego?</a>
                                <p class="question-desc">Szukam prostego sposobu na porównanie rat stałych i malejących przy kredycie na 450 tys. zł. Jakie koszty dodatkowe warto doliczyć do symulacji?</p>
                            </div>
                        </div>
                        <div class="answers-box"><strong>8</strong><span>odpowiedzi</span></div>
                    </div>
                    <div class="question-footer">
                        <div class="footer-meta">
                            <span>Michał • Kraków</span>
                            <span>2,8 tys. wyświetleń</span>
                            <span>ostatnia odpowiedź 1 godz. temu</span>
                        </div>
                        <span class="status-chip">Najczęściej zapisywane</span>
                    </div>
                </article>

                <article class="question-card">
                    <div class="question-top">
                        <div class="question-main">
                            <div class="avatar" aria-hidden="true"></div>
                            <div class="question-copy">
                                <div class="meta-row">
                                    <span class="tag">Podatki</span>
                                    <span class="mini-meta">wczoraj</span>
                                </div>
                                <a href="#" class="question-title">Jak rozliczyć działalność nierejestrowaną?</a>
                                <p class="question-desc">Chciałabym legalnie sprzedawać rękodzieło w internecie. Kiedy muszę wykazać przychód, jak prowadzić ewidencję i czy obowiązuje mnie kasa fiskalna?</p>
                            </div>
                        </div>
                        <div class="answers-box"><strong>3</strong><span>odpowiedzi</span></div>
                    </div>
                    <div class="question-footer">
                        <div class="footer-meta">
                            <span>Katarzyna • online</span>
                            <span>980 wyświetleń</span>
                            <span>odpowiedź doradcy podatkowego</span>
                        </div>
                        <span class="status-chip">Nowy komentarz</span>
                    </div>
                </article>

                <article class="question-card">
                    <div class="question-top">
                        <div class="question-main">
                            <div class="avatar" aria-hidden="true"></div>
                            <div class="question-copy">
                                <div class="meta-row">
                                    <span class="tag">Dom i ogród</span>
                                    <span class="mini-meta">2 dni temu</span>
                                </div>
                                <a href="#" class="question-title">Jaka pompa ciepła do domu 150m2?</a>
                                <p class="question-desc">Dom będzie dobrze ocieplony, ogrzewanie podłogowe w całym budynku. Zastanawiam się, czy lepiej wybrać pompę powietrzną czy gruntową i na jaką moc patrzeć.</p>
                            </div>
                        </div>
                        <div class="answers-box"><strong>6</strong><span>odpowiedzi</span></div>
                    </div>
                    <div class="question-footer">
                        <div class="footer-meta">
                            <span>Piotr • Wrocław</span>
                            <span>1,5 tys. wyświetleń</span>
                            <span>3 ekspertów w dyskusji</span>
                        </div>
                        <span class="status-chip">Porównanie rozwiązań</span>
                    </div>
                </article>

                <article class="question-card">
                    <div class="question-top">
                        <div class="question-main">
                            <div class="avatar" aria-hidden="true"></div>
                            <div class="question-copy">
                                <div class="meta-row">
                                    <span class="tag">Prawo</span>
                                    <span class="mini-meta">3 dni temu</span>
                                </div>
                                <a href="#" class="question-title">Czy dzierżawa wymaga aktu notarialnego?</a>
                                <p class="question-desc">Planuję wydzierżawić grunt na 15 lat. Czy umowa w zwykłej formie pisemnej wystarczy, czy dla bezpieczeństwa lepiej sporządzić akt notarialny?</p>
                            </div>
                        </div>
                        <div class="answers-box"><strong>0</strong><span>odpowiedzi</span></div>
                    </div>
                    <div class="question-footer">
                        <div class="footer-meta">
                            <span>Tomasz • Poznań</span>
                            <span>640 wyświetleń</span>
                            <span>oczekuje na odpowiedź prawnika</span>
                        </div>
                        <span class="status-chip unanswered">Bez odpowiedzi</span>
                    </div>
                </article>

                <article class="question-card">
                    <div class="question-top">
                        <div class="question-main">
                            <div class="avatar" aria-hidden="true"></div>
                            <div class="question-copy">
                                <div class="meta-row">
                                    <span class="tag">Ubezpieczenia</span>
                                    <span class="mini-meta">4 dni temu</span>
                                </div>
                                <a href="#" class="question-title">Jakie ubezpieczenie OC wybrać?</a>
                                <p class="question-desc">Mam pierwsze auto i zależy mi na możliwie szerokiej ochronie przy rozsądnej składce. Na które elementy polisy oraz rozszerzenia warto zwrócić uwagę?</p>
                            </div>
                        </div>
                        <div class="answers-box"><strong>4</strong><span>odpowiedzi</span></div>
                    </div>
                    <div class="question-footer">
                        <div class="footer-meta">
                            <span>Julia • Gdańsk</span>
                            <span>1,1 tys. wyświetleń</span>
                            <span>zaktualizowano dziś</span>
                        </div>
                        <span class="status-chip">Polecane przez społeczność</span>
                    </div>
                </article>
            </div>

            <aside class="sidebar">
                <div class="sidebar-card">
                    <h2>Popularne kategorie</h2>
                    <ul>
                        <li><span>Nieruchomości</span><span class="count">248</span></li>
                        <li><span>Prawo</span><span class="count">193</span></li>
                        <li><span>Finanse</span><span class="count">164</span></li>
                        <li><span>Dom i ogród</span><span class="count">121</span></li>
                        <li><span>Ubezpieczenia</span><span class="count">87</span></li>
                    </ul>
                </div>

                <div class="sidebar-card cta-panel">
                    <h2>Masz pilne pytanie?</h2>
                    <p>Skonsultuj sprawę bezpośrednio ze specjalistą i otrzymaj szybszą, dopasowaną odpowiedź.</p>
                    <a href="/eksperci" class="btn-find-expert">Znajdź eksperta</a>
                </div>
            </aside>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <span>© 2026 Poradnik.pro</span>
            <div class="footer-links">
                <a href="/polityka-prywatnosci">Polityka prywatności</a>
                <a href="/regulamin">Regulamin</a>
                <a href="/kontakt">Kontakt</a>
            </div>
        </div>
    </footer>
</body>
</html>
