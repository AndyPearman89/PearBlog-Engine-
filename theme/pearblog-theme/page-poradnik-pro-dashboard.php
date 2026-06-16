<?php
/**
 * Template Name: Poradnik.pro - Expert Dashboard
 * Template Post Type: page
 */
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podsumowanie eksperta – Poradnik.pro</title>
    <meta name="description" content="Dashboard eksperta Poradnik.pro z podsumowaniem leadów, odpowiedzi i aktywności.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple: #6c2bd9;
            --purple-dark: #4f1daa;
            --purple-soft: #f3edff;
            --green: #16a34a;
            --green-soft: #dcfce7;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-900: #0f172a;
            --white: #ffffff;
            --shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            --shadow-soft: 0 10px 24px rgba(15, 23, 42, 0.06);
            --radius-lg: 24px;
            --radius-md: 18px;
            --radius-sm: 12px;
            --max-width: 1200px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(180deg, #ffffff 0%, #f7f4ff 45%, #f8fafc 100%);
            color: var(--gray-900);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
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
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(226,232,240,0.95);
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
            gap: 10px;
            font-size: 20px;
            font-weight: 800;
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--purple), #9b6bff);
            color: var(--white);
            font-weight: 800;
        }

        .main-nav {
            display: flex;
            align-items: center;
            gap: 28px;
            color: var(--gray-600);
            font-size: 14px;
            font-weight: 600;
        }

        .main-nav a {
            position: relative;
            padding: 28px 0 24px;
            transition: color 0.2s ease;
        }

        .main-nav a:hover,
        .main-nav a.active {
            color: var(--purple);
        }

        .main-nav a.active::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 3px;
            border-radius: 999px;
            background: var(--purple);
        }

        .btn-find-expert {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 20px;
            border-radius: 999px;
            background: var(--purple);
            color: var(--white);
            font-size: 14px;
            font-weight: 700;
            box-shadow: var(--shadow-soft);
            transition: background 0.2s ease;
        }

        .btn-find-expert:hover { background: var(--purple-dark); }

        .dashboard-page {
            padding: 32px 0 56px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card,
        .panel,
        .activity-card,
        .metric-card {
            background: var(--white);
            border: 1px solid rgba(226,232,240,0.9);
            box-shadow: var(--shadow-soft);
        }

        .stat-card {
            border-radius: var(--radius-md);
            padding: 22px;
        }

        .stat-label {
            display: block;
            color: var(--gray-500);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            line-height: 1;
            font-weight: 800;
            color: var(--gray-900);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: minmax(260px, 0.72fr) minmax(0, 2.28fr);
            gap: 24px;
            align-items: start;
        }

        #sidebar-toggle {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .mobile-sidebar-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-bottom: 18px;
            padding: 14px 18px;
            border-radius: 16px;
            background: var(--white);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-soft);
            color: var(--purple);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .panel {
            border-radius: var(--radius-lg);
            padding: 24px;
        }

        .sidebar-panel {
            position: sticky;
            top: 98px;
        }

        .profile-block {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 24px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--gray-200);
        }

        .avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e5e7eb, #cbd5e1);
            color: var(--gray-600);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-block small {
            display: block;
            color: var(--gray-500);
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .profile-block strong {
            display: block;
            font-size: 22px;
            line-height: 1.2;
        }

        .sidebar-nav {
            display: grid;
            gap: 10px;
            margin-bottom: 28px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            min-height: 46px;
            padding: 0 16px;
            border-radius: 14px;
            color: var(--gray-600);
            font-size: 14px;
            font-weight: 600;
            border-left: 4px solid transparent;
            background: transparent;
            transition: all 0.2s ease;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: var(--purple);
            background: var(--purple-soft);
            border-left-color: var(--purple);
        }

        .sidebar-section-title,
        .content-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .sidebar-section-title h2,
        .content-section-title h2 {
            font-size: 18px;
            line-height: 1.2;
        }

        .sidebar-section-title a,
        .content-section-title a {
            color: var(--purple);
            font-size: 13px;
            font-weight: 700;
        }

        .mini-list {
            display: grid;
            gap: 12px;
        }

        .mini-item {
            padding: 14px 16px;
            border-radius: 14px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
        }

        .mini-item strong,
        .activity-card strong {
            display: block;
            font-size: 15px;
            line-height: 1.35;
            margin-bottom: 6px;
        }

        .mini-item span,
        .activity-card time,
        .activity-card p {
            color: var(--gray-500);
            font-size: 13px;
        }

        .main-stack {
            display: grid;
            gap: 24px;
        }

        .main-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .main-panel-header h1 {
            font-size: 28px;
            line-height: 1.1;
            margin-bottom: 6px;
        }

        .main-panel-header p {
            color: var(--gray-500);
            font-size: 15px;
        }

        .activity-list {
            display: grid;
            gap: 16px;
        }

        .activity-card {
            border-radius: var(--radius-md);
            padding: 20px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .activity-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: 0 12px;
            border-radius: 999px;
            background: var(--green-soft);
            color: var(--green);
            font-size: 12px;
            font-weight: 700;
        }

        .activity-card time {
            white-space: nowrap;
        }

        .stats-section {
            padding-top: 8px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .metric-card {
            border-radius: var(--radius-md);
            padding: 22px;
        }

        .metric-card .top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .metric-card .top-row span {
            color: var(--gray-500);
            font-size: 13px;
            font-weight: 600;
        }

        .metric-card .value {
            font-size: 30px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 10px;
        }

        .metric-change {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--green);
            font-size: 14px;
            font-weight: 700;
        }

        .metric-change::before {
            content: '↗';
            font-size: 14px;
        }

        .site-footer {
            padding-top: 16px;
            color: var(--gray-500);
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .stats-grid,
            .metrics-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .sidebar-panel {
                position: static;
            }
        }

        @media (max-width: 820px) {
            .main-nav { display: none; }
            .header-inner { min-height: 68px; }
            .mobile-sidebar-toggle { display: inline-flex; }
            .sidebar-panel { display: none; }
            #sidebar-toggle:checked ~ .dashboard-grid .sidebar-panel { display: block; }
            .dashboard-grid { gap: 18px; }
            .activity-card,
            .main-panel-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 640px) {
            .container { padding: 0 16px; }
            .dashboard-page { padding: 24px 0 40px; }
            .stats-grid,
            .metrics-grid { grid-template-columns: 1fr; }
            .stat-card,
            .panel,
            .activity-card,
            .metric-card { padding: 18px; }
            .main-panel-header h1 { font-size: 24px; }
            .profile-block strong { font-size: 20px; }
            .btn-find-expert { padding: 0 16px; font-size: 13px; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a class="logo" href="#" aria-label="Poradnik.pro">
                <span class="logo-mark">P</span>
                <span>Poradnik.pro</span>
            </a>

            <nav class="main-nav" aria-label="Główna nawigacja">
                <a href="#">Poradniki</a>
                <a href="#">Pytania</a>
                <a href="#">Publikacje</a>
                <a class="active" href="#">Podsumowanie</a>
            </nav>

            <a class="btn-find-expert" href="#">Znajdź eksperta</a>
        </div>
    </div>
</header>

<main class="dashboard-page">
    <div class="container">
        <section class="stats-grid" aria-label="Najważniejsze statystyki">
            <article class="stat-card">
                <span class="stat-label">Leady</span>
                <div class="stat-value">128</div>
            </article>
            <article class="stat-card">
                <span class="stat-label">Odpowiedzi</span>
                <div class="stat-value">54</div>
            </article>
            <article class="stat-card">
                <span class="stat-label">Artykuły</span>
                <div class="stat-value">24</div>
            </article>
            <article class="stat-card">
                <span class="stat-label">Średnia ocena</span>
                <div class="stat-value">4.9</div>
            </article>
        </section>

        <input type="checkbox" id="sidebar-toggle" aria-hidden="true">
        <label for="sidebar-toggle" class="mobile-sidebar-toggle">Pokaż / ukryj panel boczny</label>

        <section class="dashboard-grid">
            <aside class="panel sidebar-panel" aria-label="Panel eksperta">
                <div class="profile-block">
                    <div class="avatar" aria-hidden="true">JK</div>
                    <div>
                        <small>Profil eksperta</small>
                        <strong>Jan Kowalski</strong>
                    </div>
                </div>

                <nav class="sidebar-nav" aria-label="Nawigacja konta">
                    <a class="active" href="#">Statystyki</a>
                    <a href="#">Profil</a>
                    <a href="#">Wiadomości</a>
                    <a href="#">Leady</a>
                    <a href="#">Odpowiedzi</a>
                </nav>

                <div class="sidebar-section-title">
                    <h2>Ostatnie leady</h2>
                    <a href="#">Zaloguj wszystko</a>
                </div>

                <div class="mini-list">
                    <article class="mini-item">
                        <strong>Zapytanie o kredyt hipoteczny</strong>
                        <span>Dzisiaj, 10:15</span>
                    </article>
                    <article class="mini-item">
                        <strong>Pytanie o sprzedaż działek</strong>
                        <span>Wczoraj, 16:40</span>
                    </article>
                    <article class="mini-item">
                        <strong>Doradztwo inwestycyjne</strong>
                        <span>12 maja, 09:05</span>
                    </article>
                </div>
            </aside>

            <div class="main-stack">
                <section class="panel">
                    <div class="main-panel-header">
                        <div>
                            <h1>Podsumowanie eksperta</h1>
                            <p>Przegląd najnowszych leadów, aktywności i wyników profilu w jednym miejscu.</p>
                        </div>
                    </div>

                    <div class="content-section-title">
                        <h2>Ostatnia aktywność</h2>
                        <a href="#">Zobacz wszystkie</a>
                    </div>

                    <div class="activity-list">
                        <article class="activity-card">
                            <div>
                                <div class="activity-meta">
                                    <strong>Zapytanie o kredyt hipoteczny</strong>
                                    <span class="status-badge">Nowy</span>
                                </div>
                                <p>Nowy lead od klienta poszukującego wsparcia przy finansowaniu zakupu mieszkania.</p>
                            </div>
                            <time datetime="2025-05-14T10:15">Dzisiaj, 10:15</time>
                        </article>

                        <article class="activity-card">
                            <div>
                                <div class="activity-meta">
                                    <strong>Pytanie o sprzedaż działek</strong>
                                    <span class="status-badge">Nowy</span>
                                </div>
                                <p>Klient pyta o strategię wyceny i przygotowanie oferty sprzedaży działki budowlanej.</p>
                            </div>
                            <time datetime="2025-05-13T16:40">Wczoraj, 16:40</time>
                        </article>

                        <article class="activity-card">
                            <div>
                                <div class="activity-meta">
                                    <strong>Doradztwo inwestycyjne</strong>
                                    <span class="status-badge">Nowy</span>
                                </div>
                                <p>Zapytanie dotyczące budowy portfela inwestycyjnego i analizy ryzyka dla nowego klienta.</p>
                            </div>
                            <time datetime="2025-05-12T09:05">12 maja, 09:05</time>
                        </article>

                        <article class="activity-card">
                            <div>
                                <div class="activity-meta">
                                    <strong>Wycena</strong>
                                    <span class="status-badge">Nowy</span>
                                </div>
                                <p>Prośba o szybką wycenę nieruchomości wraz z rekomendacją dalszych działań.</p>
                            </div>
                            <time datetime="2025-05-10T13:20">10 maja, 13:20</time>
                        </article>
                    </div>
                </section>

                <section class="panel stats-section">
                    <div class="content-section-title">
                        <h2>Statystyki (30 dni)</h2>
                    </div>

                    <div class="metrics-grid">
                        <article class="metric-card">
                            <div class="top-row">
                                <span>Leady</span>
                            </div>
                            <div class="value">128</div>
                            <div class="metric-change">+12%</div>
                        </article>

                        <article class="metric-card">
                            <div class="top-row">
                                <span>Wyświetlenia profilu</span>
                            </div>
                            <div class="value">1 240</div>
                            <div class="metric-change">+8%</div>
                        </article>

                        <article class="metric-card">
                            <div class="top-row">
                                <span>Odpowiedzi</span>
                            </div>
                            <div class="value">54</div>
                            <div class="metric-change">+5%</div>
                        </article>
                    </div>
                </section>
            </div>
        </section>

        <footer class="site-footer">
            <p>Poradnik.pro · Dashboard eksperta</p>
        </footer>
    </div>
</main>

</body>
</html>
