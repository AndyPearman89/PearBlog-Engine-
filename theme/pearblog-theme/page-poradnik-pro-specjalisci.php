<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specjaliści | Poradnik.pro</title>
    <meta name="description" content="Znajdź najlepszego eksperta dla siebie na Poradnik.pro.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple: #6c2bd9;
            --purple-dark: #571fc0;
            --purple-soft: #f4edff;
            --text: #18181b;
            --muted: #6b7280;
            --border: #e5e7eb;
            --bg: #f6f7fb;
            --white: #ffffff;
            --shadow: 0 16px 32px rgba(24, 24, 27, 0.08);
            --radius: 12px;
            --container: 1200px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        button, input, select { font: inherit; }
        button { cursor: pointer; }

        .container {
            width: min(var(--container), calc(100% - 32px));
            margin: 0 auto;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.9);
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
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--purple), #8d58ec);
            color: var(--white);
            font-weight: 800;
        }

        .main-nav {
            display: flex;
            align-items: center;
            gap: 28px;
            flex-wrap: wrap;
        }

        .main-nav a {
            color: var(--muted);
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .main-nav a:hover,
        .main-nav a.active { color: var(--purple); }

        .header-cta,
        .profile-link,
        .page-arrow,
        .page-number {
            transition: all 0.2s ease;
        }

        .header-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 22px;
            border-radius: 999px;
            background: var(--purple);
            color: var(--white);
            font-size: 0.95rem;
            font-weight: 700;
            border: 0;
        }

        .header-cta:hover { background: var(--purple-dark); }

        .hero {
            padding: 56px 0 28px;
        }

        .hero h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.1;
            margin-bottom: 12px;
        }

        .hero p {
            max-width: 560px;
            color: var(--muted);
            font-size: 1.05rem;
        }

        .search-panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            margin: 18px 0 18px;
        }

        .search-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 240px;
            gap: 16px;
        }

        .search-input,
        .search-select {
            width: 100%;
            min-height: 56px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--white);
            padding: 0 18px;
            color: var(--text);
            outline: none;
        }

        .search-input:focus,
        .search-select:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 4px rgba(108, 43, 217, 0.12);
        }

        .search-input::placeholder { color: #9ca3af; }

        .filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .filter-tab {
            min-height: 42px;
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--muted);
            font-weight: 600;
        }

        .filter-tab.active,
        .filter-tab:hover {
            border-color: rgba(108, 43, 217, 0.2);
            background: var(--purple-soft);
            color: var(--purple);
        }

        .specialists-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .specialist-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .specialist-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
        }

        .card-top {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .card-meta h2 {
            font-size: 1.15rem;
            margin-bottom: 4px;
        }

        .specialty {
            color: var(--purple);
            font-weight: 600;
            font-size: 0.96rem;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .stars {
            color: #f4b400;
            letter-spacing: 0.03em;
            font-weight: 700;
        }

        .profile-link {
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            border-radius: 999px;
            border: 2px solid var(--purple);
            color: var(--purple);
            background: transparent;
            font-weight: 700;
        }

        .profile-link:hover {
            background: var(--purple);
            color: var(--white);
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 36px 0 56px;
        }

        .page-arrow,
        .page-number,
        .page-ellipsis {
            min-width: 42px;
            min-height: 42px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .page-arrow,
        .page-number {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--muted);
        }

        .page-number.active,
        .page-number:hover,
        .page-arrow:hover {
            border-color: var(--purple);
            color: var(--purple);
            background: var(--purple-soft);
        }

        .page-number.active {
            background: var(--purple);
            color: var(--white);
        }

        @media (max-width: 1100px) {
            .specialists-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 840px) {
            .header-inner {
                flex-wrap: wrap;
                justify-content: center;
                padding: 16px 0;
            }

            .main-nav {
                justify-content: center;
                gap: 18px;
            }

            .search-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .hero { padding-top: 40px; }
            .specialists-grid { grid-template-columns: 1fr; }
            .search-panel { padding: 18px; border-radius: 16px; }
            .specialist-card { padding: 22px; }
            .card-top { align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <a class="logo" href="/">
                    <span class="logo-mark">P</span>
                    <span>Poradnik.pro</span>
                </a>

                <nav class="main-nav" aria-label="Główna nawigacja">
                    <a href="/poradniki">Poradniki</a>
                    <a href="/rankingi">Rankingi</a>
                    <a href="/kalkulatory">Kalkulatory</a>
                    <a href="/specjalisci" class="active">Specjaliści</a>
                </nav>

                <a class="header-cta" href="/specjalisci">Znajdź eksperta</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <h1>Specjaliści</h1>
                <p>Znajdź najlepszego eksperta dla siebie</p>
            </div>
        </section>

        <section class="container">
            <div class="search-panel">
                <div class="search-row">
                    <input class="search-input" type="text" placeholder="Czego szukasz?" aria-label="Czego szukasz?">
                    <select class="search-select" aria-label="Wszystkie kategorie">
                        <option>Wszystkie kategorie</option>
                        <option>Nieruchomości</option>
                        <option>Prawo</option>
                        <option>Finanse</option>
                        <option>Księgowość</option>
                    </select>
                </div>
            </div>

            <div class="filters" aria-label="Filtry specjalistów">
                <button class="filter-tab active" type="button">Branża</button>
                <button class="filter-tab" type="button">Lokalizacja</button>
                <button class="filter-tab" type="button">Ocena</button>
                <button class="filter-tab" type="button">Online</button>
                <button class="filter-tab" type="button">Zweryfikowani</button>
                <button class="filter-tab" type="button">Więcej</button>
            </div>

            <div class="specialists-grid">
                <article class="specialist-card">
                    <div class="card-top">
                        <div class="avatar">JK</div>
                        <div class="card-meta">
                            <h2>Jan Kowalski</h2>
                            <p class="specialty">Doradca nieruchomości</p>
                        </div>
                    </div>
                    <div class="rating-row"><span class="stars">★ 4.9</span><span>322 opinie</span></div>
                    <a class="profile-link" href="/specjalisci/jan-kowalski">Zobacz profil</a>
                </article>

                <article class="specialist-card">
                    <div class="card-top">
                        <div class="avatar">AN</div>
                        <div class="card-meta">
                            <h2>Anna Nowak</h2>
                            <p class="specialty">Prawnik</p>
                        </div>
                    </div>
                    <div class="rating-row"><span class="stars">★ 4.8</span><span>425 opinie</span></div>
                    <a class="profile-link" href="/specjalisci/anna-nowak">Zobacz profil</a>
                </article>

                <article class="specialist-card">
                    <div class="card-top">
                        <div class="avatar">PZ</div>
                        <div class="card-meta">
                            <h2>Piotr Zieliński</h2>
                            <p class="specialty">Doradca kredytowy</p>
                        </div>
                    </div>
                    <div class="rating-row"><span class="stars">★ 4.7</span><span>278 opinie</span></div>
                    <a class="profile-link" href="/specjalisci/piotr-zielinski">Zobacz profil</a>
                </article>

                <article class="specialist-card">
                    <div class="card-top">
                        <div class="avatar">KW</div>
                        <div class="card-meta">
                            <h2>Karolina Wiśniewska</h2>
                            <p class="specialty">Księgowa</p>
                        </div>
                    </div>
                    <div class="rating-row"><span class="stars">★ 4.8</span><span>196 opinie</span></div>
                    <a class="profile-link" href="/specjalisci/karolina-wisniewska">Zobacz profil</a>
                </article>

                <article class="specialist-card">
                    <div class="card-top">
                        <div class="avatar">KW</div>
                        <div class="card-meta">
                            <h2>Karolina Wiśniewska</h2>
                            <p class="specialty">Specjalistka ds. podatków</p>
                        </div>
                    </div>
                    <div class="rating-row"><span class="stars">★ 4.9</span><span>241 opinii</span></div>
                    <a class="profile-link" href="/specjalisci/karolina-wisniewska-podatki">Zobacz profil</a>
                </article>

                <article class="specialist-card">
                    <div class="card-top">
                        <div class="avatar">TN</div>
                        <div class="card-meta">
                            <h2>Tomasz Nowak</h2>
                            <p class="specialty">Doradca biznesowy</p>
                        </div>
                    </div>
                    <div class="rating-row"><span class="stars">★ 4.8</span><span>214 opinii</span></div>
                    <a class="profile-link" href="/specjalisci/tomasz-nowak">Zobacz profil</a>
                </article>

                <article class="specialist-card">
                    <div class="card-top">
                        <div class="avatar">ML</div>
                        <div class="card-meta">
                            <h2>Michał Lewandowski</h2>
                            <p class="specialty">Konsultant finansowy</p>
                        </div>
                    </div>
                    <div class="rating-row"><span class="stars">★ 4.7</span><span>189 opinii</span></div>
                    <a class="profile-link" href="/specjalisci/michal-lewandowski">Zobacz profil</a>
                </article>
            </div>

            <nav class="pagination" aria-label="Paginacja">
                <a class="page-arrow" href="#" aria-label="Poprzednia strona">&larr;</a>
                <a class="page-number active" href="#">1</a>
                <a class="page-number" href="#">2</a>
                <a class="page-number" href="#">3</a>
                <a class="page-number" href="#">4</a>
                <span class="page-ellipsis">...</span>
                <a class="page-arrow" href="#" aria-label="Następna strona">&rarr;</a>
            </nav>
        </section>
    </main>
</body>
</html>
