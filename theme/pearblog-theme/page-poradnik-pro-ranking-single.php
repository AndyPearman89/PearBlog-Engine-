<?php
/**
 * Template Name: Poradnik.pro - Ranking detail
 *
 * Standalone ranking detail page template for Poradnik.pro.
 */
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Najlepsze konta osobiste 2026 | Poradnik.pro</title>
    <meta name="description" content="Ranking kont osobistych, które wyróżniają się brakiem opłat, funkcjonalnością i dostępnością.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --purple: #6c2bd9;
            --purple-dark: #4f1ca8;
            --purple-soft: #f4ecff;
            --text: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --bg: #f8fafc;
            --card: #ffffff;
            --success: #0f9f6e;
            --shadow: 0 14px 38px rgba(17, 24, 39, 0.08);
            --radius-lg: 24px;
            --radius-md: 18px;
            --radius-sm: 12px;
            --max-width: 1180px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        button { border: 0; background: none; font: inherit; cursor: pointer; }
        .container { width: min(var(--max-width), calc(100% - 32px)); margin: 0 auto; }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
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
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(135deg, var(--purple), #8b5cf6);
            box-shadow: 0 10px 24px rgba(108, 43, 217, 0.24);
            font-weight: 800;
        }
        .main-nav {
            display: flex;
            align-items: center;
            gap: 28px;
            color: var(--muted);
            font-size: 0.95rem;
            font-weight: 500;
        }
        .main-nav a:hover,
        .main-nav a.active { color: var(--purple); }
        .header-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 999px;
            background: var(--purple);
            color: #fff;
            font-size: 0.92rem;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(108, 43, 217, 0.22);
        }
        .header-cta:hover { background: var(--purple-dark); }

        .page-shell { padding: 28px 0 80px; }
        .hero-card {
            background: linear-gradient(180deg, #ffffff 0%, #faf7ff 100%);
            border: 1px solid #efe5ff;
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: var(--shadow);
        }
        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
            color: var(--muted);
            font-size: 0.9rem;
        }
        .breadcrumb a:hover,
        .breadcrumb span:last-child { color: var(--purple); }
        .hero-card h1 {
            font-size: clamp(2.15rem, 4vw, 3.4rem);
            line-height: 1.08;
            letter-spacing: -0.03em;
            margin-bottom: 12px;
        }
        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            color: var(--muted);
            font-size: 0.95rem;
            margin-bottom: 16px;
        }
        .hero-meta span + span::before {
            content: '•';
            margin-right: 10px;
        }
        .hero-description {
            max-width: 760px;
            color: #374151;
            font-size: 1.05rem;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 28px 0 32px;
        }
        .tab {
            padding: 11px 18px;
            border-radius: 999px;
            border: 1px solid #ddd6fe;
            background: #fff;
            color: var(--purple);
            font-weight: 700;
            font-size: 0.92rem;
        }
        .tab.active {
            background: var(--purple);
            border-color: var(--purple);
            color: #fff;
        }

        .ranking-card,
        .info-section,
        .faq-section {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }
        .ranking-card { overflow: hidden; }
        .section-head {
            padding: 24px 28px 0;
        }
        .section-head h2 {
            font-size: 1.35rem;
            margin-bottom: 6px;
        }
        .section-head p {
            color: var(--muted);
            font-size: 0.96rem;
        }
        .table-scroll {
            overflow-x: auto;
            padding: 24px 0 8px;
        }
        table {
            width: 100%;
            min-width: 820px;
            border-collapse: collapse;
        }
        th,
        td {
            padding: 18px 28px;
            text-align: left;
            border-top: 1px solid var(--line);
            vertical-align: middle;
        }
        th {
            color: var(--muted);
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #fcfbff;
            border-top: 0;
        }
        tbody tr:hover { background: #fcfbff; }
        .rank-number {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--purple-soft);
            color: var(--purple);
            font-weight: 800;
        }
        .bank-name {
            font-weight: 700;
            color: var(--text);
        }
        .price-free {
            color: var(--success);
            font-weight: 700;
        }
        .stars {
            color: #f59e0b;
            letter-spacing: 0.08em;
            font-size: 1rem;
        }
        .score {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--purple);
        }
        .check-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 999px;
            background: var(--purple);
            color: #fff;
            font-size: 0.92rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .check-btn:hover { background: var(--purple-dark); }

        .info-section,
        .faq-section {
            margin-top: 32px;
            padding: 28px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-top: 22px;
        }
        .info-box {
            padding: 22px;
            border-radius: var(--radius-md);
            background: #faf7ff;
            border: 1px solid #efe5ff;
        }
        .info-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            background: #fff;
            color: var(--purple);
            box-shadow: 0 6px 16px rgba(108, 43, 217, 0.08);
            font-size: 1.3rem;
        }
        .info-box h3 {
            font-size: 1rem;
            margin-bottom: 8px;
        }
        .info-box p {
            color: var(--muted);
            font-size: 0.92rem;
        }

        .faq-list {
            display: grid;
            gap: 14px;
            margin-top: 22px;
        }
        .faq-item {
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: #fff;
            overflow: hidden;
        }
        .faq-item summary {
            list-style: none;
            cursor: pointer;
            padding: 20px 22px;
            font-weight: 700;
            position: relative;
        }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item summary::after {
            content: '+';
            position: absolute;
            right: 22px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--purple);
            font-size: 1.2rem;
        }
        .faq-item[open] summary::after { content: '−'; }
        .faq-answer {
            padding: 0 22px 20px;
            color: var(--muted);
        }

        .compare-section,
        .experts-section,
        .related-section {
            margin-top: 32px;
            padding: 28px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .compare-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 0.92rem;
        }

        .compare-table th,
        .compare-table td {
            padding: 14px 16px;
            text-align: center;
            border-bottom: 1px solid var(--line);
        }

        .compare-table th { background: #faf7ff; font-weight: 700; }
        .compare-table td:first-child { text-align: left; font-weight: 500; }
        .compare-table tr:last-child td { border-bottom: none; }

        .experts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 18px;
        }

        .expert-card {
            text-align: center;
            padding: 22px 16px;
            border-radius: var(--radius-md);
            border: 1px solid var(--line);
            background: #faf7ff;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .expert-card:hover {
            box-shadow: 0 6px 20px rgba(108,43,217,0.10);
            transform: translateY(-2px);
        }

        .expert-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple), #9b6bff);
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
        }

        .expert-name { font-weight: 700; font-size: 0.95rem; margin-bottom: 3px; }
        .expert-role { font-size: 0.85rem; color: var(--muted); margin-bottom: 8px; }
        .expert-link {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 8px;
            background: var(--purple);
            color: #fff;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .expert-link:hover { background: var(--purple-dark); }

        .related-list {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .related-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            border-radius: var(--radius-md);
            border: 1px solid var(--line);
            background: #fff;
            transition: border-color 0.2s, transform 0.2s;
        }

        .related-item:hover {
            border-color: var(--purple);
            transform: translateY(-1px);
        }

        .related-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f0e8ff;
            color: var(--purple);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .related-text { font-size: 0.92rem; font-weight: 500; flex: 1; }
        .related-arrow { color: var(--muted); font-size: 1.1rem; }

        @media (max-width: 960px) {
            .header-inner { flex-wrap: wrap; padding: 14px 0; }
            .main-nav { order: 3; width: 100%; justify-content: center; gap: 18px; }
            .info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 680px) {
            .container { width: min(var(--max-width), calc(100% - 24px)); }
            .main-nav { display: none; }
            .header-inner { min-height: 70px; }
            .hero-card,
            .info-section,
            .faq-section { padding: 22px; }
            .section-head { padding: 22px 22px 0; }
            th,
            td { padding: 16px 18px; }
            .info-grid { grid-template-columns: 1fr; }
            .tabs { gap: 10px; }
            .tab { width: 100%; text-align: center; }
            .hero-meta span + span::before { margin-right: 8px; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo"><span class="logo-mark">P</span>Poradnik.pro</a>
                <nav class="main-nav" aria-label="Główna nawigacja">
                    <a href="/poradniki">Poradniki</a>
                    <a href="/porownania">Porównania</a>
                    <a href="/rankingi" class="active">Rankingi</a>
                    <a href="/kalkulatory">Kalkulatory</a>
                    <a href="/eksperci">Eksperci</a>
                </nav>
                <a href="/eksperci" class="header-cta">Znajdź eksperta</a>
            </div>
        </div>
    </header>

    <main class="page-shell">
        <div class="container">
            <section class="hero-card">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="/">Strona główna</a>
                    <span>&gt;</span>
                    <a href="/rankingi">Rankingi</a>
                    <span>&gt;</span>
                    <a href="/rankingi/finanse">Finanse</a>
                    <span>&gt;</span>
                    <span>Najlepsze konta osobiste 2026</span>
                </nav>
                <h1>Najlepsze konta osobiste 2026</h1>
                <div class="hero-meta">
                    <span>Zaktualizowano: 12.06.2026</span>
                    <span>12 546 odpowiedzi</span>
                </div>
                <p class="hero-description">Ranking kont osobistych, które wyróżniają się brakiem opłat, funkcjonalnością i dostępnością.</p>
            </section>

            <div class="tabs" aria-label="Filtry rankingu">
                <button class="tab active" type="button">Wszystkie</button>
                <button class="tab" type="button">Darmowe</button>
                <button class="tab" type="button">Dla młodych</button>
                <button class="tab" type="button">Z kartą</button>
            </div>

            <section class="ranking-card" aria-labelledby="ranking-heading">
                <div class="section-head">
                    <h2 id="ranking-heading">Ranking kont osobistych</h2>
                    <p>Porównanie najciekawszych ofert bankowych według kosztów, jakości aplikacji oraz ogólnej oceny użytkowników.</p>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Bank</th>
                                <th>Opłaty</th>
                                <th>Karta</th>
                                <th>Aplikacja</th>
                                <th>Ocena</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="rank-number">1</span></td>
                                <td class="bank-name">mBank</td>
                                <td class="price-free">0 zł</td>
                                <td class="price-free">0 zł</td>
                                <td class="stars">★★★★★</td>
                                <td class="score">4.9</td>
                                <td><a href="#" class="check-btn">Sprawdź</a></td>
                            </tr>
                            <tr>
                                <td><span class="rank-number">2</span></td>
                                <td class="bank-name">ING Bank Śląski</td>
                                <td class="price-free">0 zł</td>
                                <td class="price-free">0 zł</td>
                                <td class="stars">★★★★☆</td>
                                <td class="score">4.8</td>
                                <td><a href="#" class="check-btn">Sprawdź</a></td>
                            </tr>
                            <tr>
                                <td><span class="rank-number">3</span></td>
                                <td class="bank-name">Santander Bank Polska</td>
                                <td class="price-free">0 zł</td>
                                <td class="price-free">0 zł</td>
                                <td class="stars">★★★★☆</td>
                                <td class="score">4.7</td>
                                <td><a href="#" class="check-btn">Sprawdź</a></td>
                            </tr>
                            <tr>
                                <td><span class="rank-number">4</span></td>
                                <td class="bank-name">PKO BP</td>
                                <td class="price-free">0 zł</td>
                                <td class="price-free">0 zł</td>
                                <td class="stars">★★★★☆</td>
                                <td class="score">4.6</td>
                                <td><a href="#" class="check-btn">Sprawdź</a></td>
                            </tr>
                            <tr>
                                <td><span class="rank-number">5</span></td>
                                <td class="bank-name">Bank Millennium</td>
                                <td class="price-free">0 zł</td>
                                <td class="price-free">0 zł</td>
                                <td class="stars">★★★★☆</td>
                                <td class="score">4.6</td>
                                <td><a href="#" class="check-btn">Sprawdź</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="info-section" aria-labelledby="info-heading">
                <h2 id="info-heading">Jak przygotowujemy ranking?</h2>
                <div class="info-grid">
                    <article class="info-box">
                        <div class="info-icon">📊</div>
                        <h3>Jak powstają rankingi?</h3>
                        <p>Analizujemy opłaty, warunki promocji, dostępność usług i doświadczenie klientów, aby porównać konta w jednolity sposób.</p>
                    </article>
                    <article class="info-box">
                        <div class="info-icon">🔬</div>
                        <h3>Niezależne testy</h3>
                        <p>Każda oferta jest oceniana niezależnie od reklam i partnerstw, z naciskiem na realne korzyści dla użytkownika.</p>
                    </article>
                    <article class="info-box">
                        <div class="info-icon">🗓️</div>
                        <h3>Aktualizowany co miesiąc</h3>
                        <p>Zmiany w tabelach opłat, aplikacjach i promocjach sprawdzamy regularnie, by ranking nie tracił aktualności.</p>
                    </article>
                    <article class="info-box">
                        <div class="info-icon">✅</div>
                        <h3>Ponad 60 kryteriów</h3>
                        <p>Oceniamy m.in. koszty prowadzenia rachunku, kartę, bankowość mobilną, BLIK, dostęp do bankomatów i ofertę dodatkową.</p>
                    </article>
                </div>
            </section>

            <section class="faq-section" aria-labelledby="faq-heading">
                <h2 id="faq-heading">Najczęściej zadawane pytania</h2>
                <div class="faq-list">
                    <details class="faq-item" open>
                        <summary>Które konto osobiste jest najlepsze w 2026 roku?</summary>
                        <div class="faq-answer">
                            <p>W tym zestawieniu najwyżej ocenione jest konto w mBanku dzięki zerowym opłatom, dopracowanej aplikacji i szerokiej dostępności usług online.</p>
                        </div>
                    </details>
                    <details class="faq-item">
                        <summary>Na co zwrócić uwagę przy wyborze konta?</summary>
                        <div class="faq-answer">
                            <p>Najważniejsze są opłaty za prowadzenie rachunku i kartę, liczba darmowych bankomatów, jakość aplikacji mobilnej oraz warunki dodatkowych usług.</p>
                        </div>
                    </details>
                    <details class="faq-item">
                        <summary>Czy darmowe konto zawsze oznacza brak kosztów?</summary>
                        <div class="faq-answer">
                            <p>Nie zawsze. Część banków uzależnia brak opłat od aktywności, wpływu wynagrodzenia lub wykonania określonej liczby transakcji kartą.</p>
                        </div>
                    </details>
                    <details class="faq-item">
                        <summary>Jak często ranking jest aktualizowany?</summary>
                        <div class="faq-answer">
                            <p>Ranking jest odświeżany co miesiąc oraz po istotnych zmianach w tabelach opłat, regulaminach promocji i funkcjach aplikacji mobilnych.</p>
                        </div>
                    </details>
                </div>
            </section>

            <section class="compare-section" aria-labelledby="compare-heading">
                <h2 id="compare-heading">Porównanie wybranych ofert</h2>
                <div style="overflow-x:auto;">
                    <table class="compare-table">
                        <thead>
                            <tr>
                                <th>Cecha</th>
                                <th>mBank eKonto</th>
                                <th>ING Konto Direct</th>
                                <th>PKO BP iKonto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Opłata za prowadzenie</td>
                                <td>0 zł</td>
                                <td>0 zł</td>
                                <td>0 zł (z wpływem)</td>
                            </tr>
                            <tr>
                                <td>Karta do konta</td>
                                <td>0 zł (5 transakcji)</td>
                                <td>0 zł (1 transakcja)</td>
                                <td>7 zł/mies.</td>
                            </tr>
                            <tr>
                                <td>Wypłaty z bankomatów</td>
                                <td>Bez limitu własne</td>
                                <td>1 darmowa/mies. obce</td>
                                <td>Własne: 0 zł</td>
                            </tr>
                            <tr>
                                <td>Aplikacja mobilna</td>
                                <td>★★★★★</td>
                                <td>★★★★☆</td>
                                <td>★★★★☆</td>
                            </tr>
                            <tr>
                                <td>BLIK</td>
                                <td>Tak</td>
                                <td>Tak</td>
                                <td>Tak</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="experts-section" aria-labelledby="experts-heading">
                <h2 id="experts-heading">Polecani eksperci</h2>
                <div class="experts-grid">
                    <div class="expert-card">
                        <div class="expert-avatar">MK</div>
                        <div class="expert-name">Michał Kowalski</div>
                        <div class="expert-role">Doradca finansowy</div>
                        <a href="#" class="expert-link">Zobacz profil</a>
                    </div>
                    <div class="expert-card">
                        <div class="expert-avatar">AZ</div>
                        <div class="expert-name">Anna Zawadzka</div>
                        <div class="expert-role">Analityk bankowy</div>
                        <a href="#" class="expert-link">Zobacz profil</a>
                    </div>
                    <div class="expert-card">
                        <div class="expert-avatar">PW</div>
                        <div class="expert-name">Paweł Wiśniewski</div>
                        <div class="expert-role">Ekspert kredytowy</div>
                        <a href="#" class="expert-link">Zobacz profil</a>
                    </div>
                </div>
            </section>

            <section class="related-section" aria-labelledby="related-heading">
                <h2 id="related-heading">Powiązane rankingi</h2>
                <div class="related-list">
                    <a href="/ranking/najlepsze-konta-oszczednosciowe" class="related-item">
                        <div class="related-icon">📊</div>
                        <div class="related-text">Ranking kont oszczędnościowych 2026</div>
                        <span class="related-arrow">›</span>
                    </a>
                    <a href="/ranking/najlepsze-kredyty-hipoteczne" class="related-item">
                        <div class="related-icon">🏠</div>
                        <div class="related-text">Ranking kredytów hipotecznych</div>
                        <span class="related-arrow">›</span>
                    </a>
                    <a href="/ranking/najlepsze-karty-kredytowe" class="related-item">
                        <div class="related-icon">💳</div>
                        <div class="related-text">Ranking kart kredytowych</div>
                        <span class="related-arrow">›</span>
                    </a>
                    <a href="/ranking/najlepsze-lokaty" class="related-item">
                        <div class="related-icon">💰</div>
                        <div class="related-text">Ranking lokat bankowych</div>
                        <span class="related-arrow">›</span>
                    </a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
