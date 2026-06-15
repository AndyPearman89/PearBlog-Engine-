<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Czy mogę budować dom na działce rolnej? – Poradnik.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #6c2bd9;
            --primary-dark: #5520ae;
            --primary-soft: #f3ebff;
            --text: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --bg: #f8fafc;
            --card: #ffffff;
            --shadow: 0 18px 40px rgba(17, 24, 39, 0.08);
            --radius-lg: 24px;
            --radius-md: 18px;
            --radius-sm: 12px;
            --container: 1180px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            background: linear-gradient(180deg, #ffffff 0%, #faf7ff 45%, #f8fafc 100%);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        ul { list-style: none; }

        .container {
            max-width: var(--container);
            margin: 0 auto;
            padding: 0 24px;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.9);
        }

        .header-inner {
            min-height: 76px;
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
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #9b6bff);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
        }

        .main-nav {
            display: flex;
            align-items: center;
            gap: 28px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
        }

        .main-nav a:hover,
        .main-nav a.active { color: var(--primary); }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 999px;
            background: var(--primary);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            transition: background 0.2s ease;
        }

        .btn-primary:hover { background: var(--primary-dark); }

        .page-shell {
            padding: 28px 0 72px;
        }

        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 28px;
            color: var(--muted);
            font-size: 13px;
        }

        .breadcrumb .current { color: var(--text); font-weight: 600; }
        .breadcrumb .sep { color: #c4b5fd; }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.85fr) minmax(280px, 1fr);
            gap: 28px;
            align-items: start;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .question-card {
            padding: 36px;
            margin-bottom: 24px;
        }

        .question-title {
            font-size: clamp(30px, 5vw, 42px);
            line-height: 1.12;
            margin-bottom: 16px;
        }

        .question-body {
            max-width: 760px;
            color: var(--muted);
            font-size: 18px;
            margin-bottom: 24px;
        }

        .question-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tag {
            padding: 10px 14px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }

        .answers-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
            padding: 12px 18px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 15px;
            font-weight: 800;
        }

        .answers-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 0 4px;
        }

        .answers-header h2 {
            font-size: 24px;
            font-weight: 800;
        }

        .sort-chip {
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            background: #fff;
        }

        .answers-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .answer-card {
            padding: 28px;
        }

        .answer-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #d1d5db;
            flex: 0 0 52px;
        }

        .answer-author {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .answer-role {
            color: var(--muted);
            font-size: 13px;
        }

        .answer-text {
            color: #374151;
            font-size: 15px;
            margin-bottom: 18px;
        }

        .answer-text p { margin-bottom: 12px; }

        .answer-points {
            margin: 0 0 8px 20px;
        }

        .answer-points li {
            list-style: disc;
            margin-bottom: 10px;
        }

        .answer-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
            color: var(--muted);
            font-size: 13px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
        }

        .answer-helpful {
            color: var(--primary);
            font-weight: 700;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 18px;
            position: sticky;
            top: 96px;
        }

        .sidebar-card {
            padding: 24px;
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .category-pill {
            display: inline-flex;
            align-items: center;
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .subcategory-list {
            display: grid;
            gap: 12px;
        }

        .subcategory-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #374151;
            font-size: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .subcategory-list li:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .subcategory-list span:last-child {
            color: var(--primary);
            font-weight: 700;
        }

        .expert-list {
            display: grid;
            gap: 14px;
        }

        .expert-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            border: 1px solid #f1f5f9;
            border-radius: var(--radius-md);
            background: #fcfcff;
        }

        .expert-info {
            min-width: 0;
            flex: 1;
        }

        .expert-name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .expert-role,
        .expert-count {
            font-size: 12px;
            color: var(--muted);
        }

        .expert-link {
            color: var(--primary);
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .cta-card {
            padding: 28px;
            background: linear-gradient(135deg, #4c1d95 0%, #6c2bd9 52%, #8b5cf6 100%);
            color: #fff;
            border: none;
        }

        .cta-card p {
            color: rgba(255,255,255,0.82);
            margin: 12px 0 20px;
            font-size: 14px;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 14px 18px;
            border-radius: 999px;
            background: #fff;
            color: var(--primary);
            font-size: 14px;
            font-weight: 800;
        }

        @media (max-width: 980px) {
            .content-grid { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .main-nav { display: none; }
        }

        @media (max-width: 640px) {
            .container { padding: 0 16px; }
            .header-inner { min-height: 70px; }
            .question-card,
            .answer-card,
            .sidebar-card,
            .cta-card { padding: 22px; }
            .question-body { font-size: 16px; }
            .answers-header {
                align-items: flex-start;
                flex-direction: column;
            }
            .question-meta { align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <a href="#" class="logo" aria-label="Poradnik.pro">
                    <span class="logo-mark">P</span>
                    <span>Poradnik.pro</span>
                </a>
                <nav class="main-nav" aria-label="Główna nawigacja">
                    <a href="#">Kategorie</a>
                    <a href="#" class="active">Pytania i odpowiedzi</a>
                    <a href="#">Eksperci</a>
                    <a href="#">Poradniki</a>
                </nav>
                <a href="#" class="btn-primary">Znajdź eksperta</a>
            </div>
        </div>
    </header>

    <main class="page-shell">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="#">Strona główna</a>
                <span class="sep">&gt;</span>
                <a href="#">Pytania i odpowiedzi</a>
                <span class="sep">&gt;</span>
                <span class="current">Czy mogę budować dom na działce rolnej?</span>
            </nav>

            <div class="content-grid">
                <section>
                    <article class="card question-card">
                        <h1 class="question-title">Czy mogę budować dom na działce rolnej?</h1>
                        <p class="question-body">Czy mogę budować dom na działce rolnej bez przekształcania jej na budowlaną?</p>
                        <div class="question-meta">
                            <div class="tags" aria-label="Tagi pytania">
                                <span class="tag">działka rolna</span>
                                <span class="tag">Budowa domu</span>
                                <span class="tag">prawo</span>
                            </div>
                            <div class="answers-count">3 Odpowiedzi</div>
                        </div>
                    </article>

                    <div class="answers-header">
                        <h2>Odpowiedzi (3)</h2>
                        <div class="sort-chip">Sortuj: Najlepsze</div>
                    </div>

                    <div class="answers-list">
                        <article class="card answer-card">
                            <div class="answer-top">
                                <div class="avatar" aria-hidden="true"></div>
                                <div>
                                    <div class="answer-author">Jan Kowalski</div>
                                    <div class="answer-role">Doradca nieruchomości</div>
                                </div>
                            </div>
                            <div class="answer-text">
                                <p>Tak, ale tylko w określonych przypadkach. Kluczowe jest przeznaczenie gruntu oraz to, czy inwestycja spełnia wymagania planistyczne i formalne.</p>
                                <ul class="answer-points">
                                    <li>działka ma status rolnej; zabudowany (R/M)</li>
                                    <li>działka posiada dojazd o warunkach zabudowy</li>
                                    <li>działka ma wymagane przeznaczenie w MPZP</li>
                                </ul>
                            </div>
                            <div class="answer-footer">
                                <span>12.05.2026</span>
                                <span class="answer-helpful">Pomocna odpowiedź (68)</span>
                            </div>
                        </article>

                        <article class="card answer-card">
                            <div class="answer-top">
                                <div class="avatar" aria-hidden="true"></div>
                                <div>
                                    <div class="answer-author">Anna Nowak</div>
                                    <div class="answer-role">Prawnik budowlany</div>
                                </div>
                            </div>
                            <div class="answer-text">
                                <p>Jeżeli grunt nie został odrolniony, urząd zwykle sprawdzi, czy budowa domu jednorodzinnego mieści się w dopuszczalnym sposobie użytkowania terenu.</p>
                                <ul class="answer-points">
                                    <li>należy zweryfikować klasę gruntu i lokalny plan zagospodarowania</li>
                                    <li>w razie braku MPZP trzeba uzyskać decyzję o warunkach zabudowy</li>
                                    <li>przy gruntach wyższej klasy może być konieczna zgoda na wyłączenie z produkcji rolnej</li>
                                </ul>
                            </div>
                            <div class="answer-footer">
                                <span>12.05.2026</span>
                                <span class="answer-helpful">Pomocna odpowiedź (54)</span>
                            </div>
                        </article>

                        <article class="card answer-card">
                            <div class="answer-top">
                                <div class="avatar" aria-hidden="true"></div>
                                <div>
                                    <div class="answer-author">Piotr Zieliński</div>
                                    <div class="answer-role">Ekspert ds. inwestycji</div>
                                </div>
                            </div>
                            <div class="answer-text">
                                <p>Przed zakupem lub rozpoczęciem budowy warto zlecić analizę formalną działki. To pozwoli sprawdzić ryzyko odrolnienia i czas potrzebny na uzyskanie zgód.</p>
                                <ul class="answer-points">
                                    <li>sprawdź księgę wieczystą i dostęp do drogi publicznej</li>
                                    <li>potwierdź, czy są media lub możliwość ich doprowadzenia</li>
                                    <li>porównaj zapisy ewidencji z zapisami MPZP albo decyzją WZ</li>
                                </ul>
                            </div>
                            <div class="answer-footer">
                                <span>12.05.2026</span>
                                <span class="answer-helpful">Pomocna odpowiedź (41)</span>
                            </div>
                        </article>
                    </div>
                </section>

                <aside class="sidebar" aria-label="Sidebar">
                    <section class="card sidebar-card">
                        <h2 class="sidebar-title">Kategoria</h2>
                        <div class="category-pill">Nieruchomości</div>
                        <ul class="subcategory-list">
                            <li><span>Prawo</span><span>›</span></li>
                            <li><span>Finanse</span><span>›</span></li>
                            <li><span>Motoryzacja</span><span>›</span></li>
                            <li><span>Biznes</span><span>›</span></li>
                            <li><span>Zdrowie</span><span>›</span></li>
                            <li><span>Technologia</span><span>›</span></li>
                        </ul>
                    </section>

                    <section class="card sidebar-card">
                        <h2 class="sidebar-title">Najlepsi eksperci</h2>
                        <div class="expert-list">
                            <article class="expert-card">
                                <div class="avatar" aria-hidden="true"></div>
                                <div class="expert-info">
                                    <div class="expert-name">Marta Wiśniewska</div>
                                    <div class="expert-role">Radca prawny</div>
                                    <div class="expert-count">214 odpowiedzi</div>
                                </div>
                                <a href="#" class="expert-link">Odpowiedzi</a>
                            </article>
                            <article class="expert-card">
                                <div class="avatar" aria-hidden="true"></div>
                                <div class="expert-info">
                                    <div class="expert-name">Tomasz Lewandowski</div>
                                    <div class="expert-role">Doradca kredytowy</div>
                                    <div class="expert-count">189 odpowiedzi</div>
                                </div>
                                <a href="#" class="expert-link">Odpowiedzi</a>
                            </article>
                            <article class="expert-card">
                                <div class="avatar" aria-hidden="true"></div>
                                <div class="expert-info">
                                    <div class="expert-name">Karolina Maj</div>
                                    <div class="expert-role">Ekspert nieruchomości</div>
                                    <div class="expert-count">163 odpowiedzi</div>
                                </div>
                                <a href="#" class="expert-link">Odpowiedzi</a>
                            </article>
                        </div>
                    </section>

                    <section class="card cta-card">
                        <h2 class="sidebar-title">Potrzebujesz indywidualnej porady?</h2>
                        <p>Opisz swoją sytuację i otrzymaj odpowiedź dopasowaną do Twojego przypadku od sprawdzonego eksperta.</p>
                        <a href="#" class="cta-button">Wyślij zapytanie</a>
                    </section>
                </aside>
            </div>
        </div>
    </main>
</body>
</html>
