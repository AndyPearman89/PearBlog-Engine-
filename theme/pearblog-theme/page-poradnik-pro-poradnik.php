<?php
/**
 * Template Name: Poradnik.pro - Poradnik
 *
 * Standalone guide/article page template for Poradnik.pro.
 */
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jak sprzedać działkę? Kompletny poradnik krok po kroku | Poradnik.pro</title>
    <meta name="description" content="Praktyczny poradnik o sprzedaży działki: dokumenty, wycena, przygotowanie nieruchomości i współpraca z ekspertem.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --purple-primary: #6c2bd9;
            --purple-dark: #1a0a3e;
            --purple-light: #8b5cf6;
            --orange-cta: #f97316;
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
            --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 12px 30px rgba(15, 23, 42, 0.08);
            --max-width: 1200px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }
        button { border: 0; background: none; font: inherit; cursor: pointer; }
        .container { width: min(var(--max-width), calc(100% - 32px)); margin: 0 auto; }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--gray-200);
        }
        .header-inner {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--purple-dark);
            font-size: 1.25rem;
            font-weight: 800;
        }
        .logo-mark {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
        }
        .main-nav {
            display: flex;
            align-items: center;
            gap: 28px;
            color: var(--gray-600);
            font-size: 0.95rem;
            font-weight: 500;
        }
        .main-nav a:hover { color: var(--purple-primary); }
        .header-cta {
            padding: 12px 20px;
            border-radius: 999px;
            background: var(--purple-primary);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            box-shadow: var(--shadow-sm);
        }

        .page-shell { padding: 28px 0 64px; }
        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            color: var(--gray-500);
            font-size: 0.9rem;
        }
        .breadcrumb span:last-child { color: var(--gray-700); font-weight: 600; }
        .breadcrumb-sep { color: var(--gray-400); }

        .article-intro {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 28px;
        }
        .article-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            padding: 8px 14px;
            background: #f3e8ff;
            color: var(--purple-primary);
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
        }
        .article-intro h1 {
            max-width: 840px;
            color: var(--gray-900);
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1.08;
            letter-spacing: -0.03em;
            margin-bottom: 24px;
        }
        .author-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .author-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gray-200), var(--gray-300));
            flex: 0 0 56px;
        }
        .author-copy strong {
            display: block;
            color: var(--gray-900);
            font-size: 1rem;
            margin-bottom: 2px;
        }
        .author-copy span {
            display: block;
            color: var(--gray-500);
            font-size: 0.92rem;
        }
        .meta-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .meta-pill {
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 0.88rem;
            font-weight: 600;
        }
        .share-btn {
            padding: 12px 18px;
            border: 1px solid var(--gray-200);
            border-radius: 999px;
            background: #fff;
            color: var(--gray-700);
            font-weight: 700;
            box-shadow: var(--shadow-sm);
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.85fr) minmax(300px, 1fr);
            gap: 28px;
            align-items: start;
        }
        .main-column,
        .sidebar > * {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 24px;
            box-shadow: var(--shadow-sm);
        }
        .main-column { padding: 32px; }
        .sidebar {
            display: grid;
            gap: 20px;
            position: sticky;
            top: 96px;
        }
        .sidebar section,
        .sidebar article,
        .cta-box { padding: 24px; }

        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }
        .section-title h2,
        .sidebar h3 {
            color: var(--gray-900);
            font-size: 1.25rem;
            line-height: 1.2;
        }
        .section-title span {
            color: var(--gray-500);
            font-size: 0.88rem;
            font-weight: 600;
        }

        .toc-list {
            display: grid;
            gap: 12px;
            margin-bottom: 32px;
        }
        .toc-item {
            display: grid;
            grid-template-columns: 38px 1fr;
            gap: 14px;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            background: var(--gray-50);
            border: 1px solid var(--gray-100);
        }
        .toc-number {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #efe7ff;
            color: var(--purple-primary);
            font-weight: 800;
        }
        .toc-item a {
            align-self: center;
            color: var(--gray-700);
            font-weight: 600;
        }

        .article-content h2 {
            color: var(--gray-900);
            font-size: 1.65rem;
            margin: 36px 0 16px;
            line-height: 1.2;
        }
        .article-content p {
            margin-bottom: 16px;
            color: var(--gray-700);
            font-size: 1rem;
        }
        .article-content ul {
            padding-left: 22px;
            margin: 0 0 18px;
            color: var(--gray-700);
        }
        .article-content li { margin-bottom: 10px; }

        .expert-tip {
            margin: 28px 0;
            padding: 22px 24px;
            background: linear-gradient(135deg, rgba(108, 43, 217, 0.08), rgba(139, 92, 246, 0.16));
            border: 1px solid rgba(108, 43, 217, 0.18);
            border-radius: var(--radius-lg);
        }
        .expert-tip strong {
            display: inline-block;
            color: var(--purple-dark);
            margin-bottom: 10px;
            font-size: 1rem;
        }
        .expert-tip p { margin: 0; }

        .faq-list {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }
        .faq-item {
            padding: 20px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            background: var(--gray-50);
        }
        .faq-item h3 {
            color: var(--gray-900);
            font-size: 1rem;
            margin-bottom: 8px;
        }
        .faq-item p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.96rem;
        }

        .sidebar h3 {
            margin-bottom: 18px;
            font-size: 1.1rem;
        }
        .expert-card {
            display: grid;
            grid-template-columns: 52px 1fr;
            gap: 14px;
            align-items: center;
            padding: 16px 0;
            border-top: 1px solid var(--gray-100);
        }
        .expert-card:first-of-type { border-top: 0; padding-top: 0; }
        .expert-info strong {
            display: block;
            color: var(--gray-900);
            font-size: 0.98rem;
            margin-bottom: 2px;
        }
        .expert-info span {
            display: block;
            color: var(--gray-500);
            font-size: 0.88rem;
        }
        .rating {
            margin-top: 8px;
            color: var(--orange-cta);
            font-size: 0.88rem;
            font-weight: 700;
        }
        .small-btn {
            display: inline-flex;
            margin-top: 10px;
            padding: 9px 14px;
            border-radius: 999px;
            background: var(--gray-900);
            color: #fff;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .cta-box {
            background: linear-gradient(180deg, var(--purple-primary), var(--purple-dark));
            color: #fff;
        }
        .cta-box p {
            color: rgba(255,255,255,0.82);
            margin-bottom: 18px;
            font-size: 0.96rem;
        }
        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 13px 18px;
            border-radius: 999px;
            background: var(--orange-cta);
            color: #fff;
            font-weight: 800;
            box-shadow: var(--shadow-sm);
        }

        .related-card {
            display: grid;
            grid-template-columns: 92px 1fr;
            gap: 14px;
            align-items: center;
            padding: 14px 0;
            border-top: 1px solid var(--gray-100);
        }
        .related-card:first-of-type { border-top: 0; padding-top: 0; }
        .thumb {
            height: 72px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #ede9fe, #dbeafe);
        }
        .related-copy strong {
            display: block;
            color: var(--gray-900);
            font-size: 0.95rem;
            margin-bottom: 4px;
            line-height: 1.35;
        }
        .related-copy span {
            color: var(--gray-500);
            font-size: 0.84rem;
        }

        .site-footer {
            margin-top: 56px;
            padding: 28px 0 40px;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-500);
            font-size: 0.9rem;
            background: #fff;
        }
        .footer-inner {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        @media (max-width: 980px) {
            .content-grid { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .main-nav { display: none; }
        }

        @media (max-width: 720px) {
            .container { width: min(var(--max-width), calc(100% - 24px)); }
            .header-inner,
            .author-row,
            .author-meta { align-items: flex-start; }
            .header-inner {
                min-height: auto;
                padding: 16px 0;
                flex-wrap: wrap;
            }
            .article-intro,
            .main-column,
            .sidebar section,
            .sidebar article,
            .cta-box { padding: 22px; border-radius: 20px; }
            .related-card,
            .expert-card { grid-template-columns: 1fr; }
            .thumb { width: 100%; height: 120px; }
            .meta-pills { width: 100%; }
            .share-btn { width: 100%; justify-content: center; display: inline-flex; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="#" class="logo" aria-label="Poradnik.pro">
                <span class="logo-mark">P</span>
                <span>Poradnik.pro</span>
            </a>
            <nav class="main-nav" aria-label="Główna nawigacja">
                <a href="#">Kategorie</a>
                <a href="#">Poradniki</a>
                <a href="#">Eksperci</a>
                <a href="#">Kontakt</a>
            </nav>
            <a href="#" class="header-cta">Znajdź eksperta</a>
        </div>
    </header>

    <main class="page-shell">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="#">Strona główna</a>
                <span class="breadcrumb-sep">&gt;</span>
                <a href="#">Poradniki</a>
                <span class="breadcrumb-sep">&gt;</span>
                <a href="#">Nieruchomości</a>
                <span class="breadcrumb-sep">&gt;</span>
                <span>Jak sprzedać działkę?</span>
            </nav>

            <section class="article-intro">
                <span class="article-tag">Poradnik sprzedaży nieruchomości</span>
                <h1>Jak sprzedać działkę? Kompletny poradnik krok po kroku</h1>
                <div class="author-row">
                    <div class="author-meta">
                        <div class="avatar" aria-hidden="true"></div>
                        <div class="author-copy">
                            <strong>Jan Kowalski</strong>
                            <span>Doradca nieruchomości</span>
                        </div>
                        <div class="meta-pills" aria-label="Metadane artykułu">
                            <span class="meta-pill">12.06.2026</span>
                            <span class="meta-pill">9 min czytania</span>
                        </div>
                    </div>
                    <button class="share-btn" type="button">Udostępnij</button>
                </div>
            </section>

            <div class="content-grid">
                <article class="main-column">
                    <section>
                        <div class="section-title">
                            <h2>Spis treści</h2>
                            <span>3 kroki startowe</span>
                        </div>
                        <div class="toc-list">
                            <div class="toc-item">
                                <span class="toc-number">1</span>
                                <a href="#przygotowanie">Przygotowanie działki do sprzedaży</a>
                            </div>
                            <div class="toc-item">
                                <span class="toc-number">2</span>
                                <a href="#dokumenty">Dokumenty potrzebne do sprzedaży</a>
                            </div>
                            <div class="toc-item">
                                <span class="toc-number">3</span>
                                <a href="#cena">Ustalenie ceny działki i strategii ogłoszenia</a>
                            </div>
                        </div>
                    </section>

                    <section class="article-content">
                        <h2 id="przygotowanie">1. Przygotowanie działki do sprzedaży</h2>
                        <p>Zanim wystawisz działkę na sprzedaż, zadbaj o jej czytelne przedstawienie. Kupujący szybciej podejmują decyzję, gdy wiedzą, gdzie przebiegają granice, jaki jest dojazd do nieruchomości oraz czy teren jest uporządkowany. W praktyce oznacza to nie tylko przygotowanie dokumentów, ale też wykonanie prostych działań wizualnych, które zwiększają wiarygodność oferty.</p>
                        <p>Warto rozpocząć od sprawdzenia aktualnego stanu działki w terenie. Usuń zalegające odpady, skoś wysoką trawę i zadbaj o dojazd, jeśli od dawna nikt z niego nie korzystał. Jeżeli działka ma duży potencjał inwestycyjny, przygotuj mapkę z zaznaczonym dostępem do mediów oraz orientacyjnym układem najbliższej infrastruktury.</p>
                        <ul>
                            <li>Zweryfikuj granice działki i numer ewidencyjny.</li>
                            <li>Sprawdź, czy działka ma dostęp do drogi publicznej.</li>
                            <li>Przygotuj zdjęcia wykonane przy dobrym świetle i z kilku perspektyw.</li>
                        </ul>

                        <div class="expert-tip">
                            <strong>Wskazówka eksperta</strong>
                            <p>Jeśli działka jest trudna do oceny na podstawie samych zdjęć, pokaż w ogłoszeniu prosty schemat: dojazd, media, strony świata i odległość od najważniejszych punktów. To skraca liczbę pytań od kupujących i podnosi jakość leadów.</p>
                        </div>

                        <h2 id="dokumenty">2. Dokumenty potrzebne do sprzedaży</h2>
                        <p>Najważniejsze dokumenty to numer księgi wieczystej, wypis i wyrys z ewidencji gruntów, a w wielu przypadkach również decyzja o warunkach zabudowy albo informacje z miejscowego planu zagospodarowania przestrzennego. Kupujący często chcą poznać przeznaczenie gruntu jeszcze przed rozmową o cenie.</p>
                        <p>Jeżeli działka jest obciążona służebnością, hipoteką albo współwłasnością, przygotuj jasne wyjaśnienie tych kwestii. Dobrze opracowany komplet dokumentów przyspiesza negocjacje i pozwala uniknąć sytuacji, w której zainteresowany klient rezygnuje po pierwszej analizie prawnej.</p>

                        <h2 id="cena">3. Ustalenie ceny działki i strategii ogłoszenia</h2>
                        <p>Cena powinna uwzględniać nie tylko lokalizację i metraż, ale także przeznaczenie działki, dostęp do mediów, kształt parceli oraz realny popyt w okolicy. Porównaj podobne oferty aktywne i ceny transakcyjne, jeśli masz do nich dostęp. Warto też zdecydować, czy publikujesz ogłoszenie szeroko, czy kierujesz je do konkretnej grupy kupujących, np. inwestorów lub osób budujących dom.</p>
                        <p>Dobrze opisane ogłoszenie powinno odpowiadać na podstawowe pytania jeszcze przed pierwszym kontaktem. Im bardziej precyzyjny opis, tym większa szansa, że odezwą się osoby naprawdę zainteresowane zakupem.</p>
                    </section>

                    <section style="margin-top: 40px;">
                        <div class="section-title">
                            <h2>Najczęściej zadawane pytania</h2>
                            <span>FAQ</span>
                        </div>
                        <div class="faq-list">
                            <div class="faq-item">
                                <h3>Czy do sprzedaży działki potrzebny jest pośrednik?</h3>
                                <p>Nie, ale wsparcie specjalisty może przyspieszyć wycenę, przygotowanie oferty i negocjacje, szczególnie przy działkach inwestycyjnych lub z nieuregulowanymi formalnościami.</p>
                            </div>
                            <div class="faq-item">
                                <h3>Jak sprawdzić, czy działka jest budowlana?</h3>
                                <p>Najlepiej przeanalizować miejscowy plan zagospodarowania albo wystąpić o warunki zabudowy. To podstawowe źródła informacji o przeznaczeniu gruntu.</p>
                            </div>
                            <div class="faq-item">
                                <h3>Co najbardziej wpływa na cenę działki?</h3>
                                <p>Lokalizacja, dostęp do drogi, uzbrojenie terenu, przeznaczenie w planie miejscowym i sytuacja prawna. Duże znaczenie ma też atrakcyjność okolicy dla konkretnego typu kupującego.</p>
                            </div>
                        </div>
                    </section>

                    <section style="margin-top: 40px;">
                        <div class="section-title">
                            <h2>Powiązane pytania</h2>
                            <span>Q&amp;A</span>
                        </div>
                        <div class="faq-list">
                            <div class="faq-item">
                                <h3><a href="/pytanie/jak-sprawdzic-plan-zagospodarowania" style="color:inherit;">Jak sprawdzić plan zagospodarowania przestrzennego?</a></h3>
                                <p>Dowiedz się, gdzie i jak uzyskać informacje o przeznaczeniu terenu w miejscowym planie zagospodarowania.</p>
                            </div>
                            <div class="faq-item">
                                <h3><a href="/pytanie/ile-trwa-sprzedaz-dzialki" style="color:inherit;">Ile czasu trwa sprzedaż działki?</a></h3>
                                <p>Średni czas sprzedaży i czynniki wpływające na szybkość transakcji.</p>
                            </div>
                            <div class="faq-item">
                                <h3><a href="/pytanie/podatek-od-sprzedazy-dzialki" style="color:inherit;">Jaki podatek zapłacę od sprzedaży działki?</a></h3>
                                <p>Zasady opodatkowania i zwolnienia przy sprzedaży nieruchomości gruntowej.</p>
                            </div>
                        </div>
                    </section>
                </article>

                <aside class="sidebar">
                    <section>
                        <h3>Polecani eksperci</h3>
                        <article class="expert-card">
                            <div class="avatar" aria-hidden="true" style="width:52px;height:52px;flex-basis:52px;"></div>
                            <div class="expert-info">
                                <strong>Anna Nowak</strong>
                                <span>Pośredniczka nieruchomości</span>
                                <div class="rating">★ 4.9 · 186 opinii</div>
                                <a href="#" class="small-btn">Sprawdź</a>
                            </div>
                        </article>
                        <article class="expert-card">
                            <div class="avatar" aria-hidden="true" style="width:52px;height:52px;flex-basis:52px;"></div>
                            <div class="expert-info">
                                <strong>Piotr Zieliński</strong>
                                <span>Doradca inwestycyjny</span>
                                <div class="rating">★ 4.8 · 132 opinie</div>
                                <a href="#" class="small-btn">Sprawdź</a>
                            </div>
                        </article>
                        <article class="expert-card">
                            <div class="avatar" aria-hidden="true" style="width:52px;height:52px;flex-basis:52px;"></div>
                            <div class="expert-info">
                                <strong>Katarzyna Wójcik</strong>
                                <span>Ekspertka ds. wyceny gruntów</span>
                                <div class="rating">★ 5.0 · 94 opinie</div>
                                <a href="#" class="small-btn">Sprawdź</a>
                            </div>
                        </article>
                    </section>

                    <section class="cta-box">
                        <h3>Potrzebujesz pomocy?</h3>
                        <p>Skontaktuj się z ekspertem, który pomoże Ci działać szybko i bezpiecznie.</p>
                        <a href="#" class="cta-button">Wyślij zapytanie</a>
                    </section>

                    <section>
                        <h3>Powiązane artykuły</h3>
                        <article class="related-card">
                            <div class="thumb" aria-hidden="true"></div>
                            <div class="related-copy">
                                <strong>Ile kosztuje geodeta przy podziale działki?</strong>
                                <span>6 min czytania</span>
                            </div>
                        </article>
                        <article class="related-card">
                            <div class="thumb" aria-hidden="true"></div>
                            <div class="related-copy">
                                <strong>Jak przygotować działkę do sprzedaży inwestorowi?</strong>
                                <span>8 min czytania</span>
                            </div>
                        </article>
                        <article class="related-card">
                            <div class="thumb" aria-hidden="true"></div>
                            <div class="related-copy">
                                <strong>Umowa przedwstępna sprzedaży gruntu — na co uważać?</strong>
                                <span>7 min czytania</span>
                            </div>
                        </article>
                    </section>
                </aside>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <span>© 2026 Poradnik.pro — przewodniki, eksperci i bezpieczne decyzje.</span>
            <span>Kontakt · Polityka prywatności · Regulamin</span>
        </div>
    </footer>
</body>
</html>
