<?php
/**
 * Template Name: Poradnik.PRO - Poradnik (Article)
 * Description: Single guide/article page (/poradnik/{slug})
 *
 * @package PearBlog
 * @version 6.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/poradnik-pro-shared.php';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php pp_pro_shared_styles(); ?>
    <style>
        /* ===== ARTICLE LAYOUT ===== */
        .article-wrapper {
            max-width: 820px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }

        /* ===== BREADCRUMB (article-specific overrides) ===== */
        .article-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--gray-400);
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        .article-breadcrumb a {
            color: var(--gray-500);
            transition: color 0.2s;
        }
        .article-breadcrumb a:hover {
            color: var(--purple-primary);
        }
        .article-breadcrumb .sep {
            color: var(--gray-300);
            font-size: 11px;
        }
        .article-breadcrumb .current {
            color: var(--gray-700);
            font-weight: 500;
        }

        /* ===== ARTICLE HEADER ===== */
        .article-header {
            margin-bottom: 40px;
        }
        .article-category-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f3e8ff;
            color: var(--purple-primary);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
            margin-bottom: 16px;
        }
        .article-title {
            font-size: 36px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.25;
            margin-bottom: 16px;
        }
        .article-subtitle {
            font-size: 17px;
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .article-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 13px;
            color: var(--gray-500);
            padding-bottom: 24px;
            border-bottom: 1px solid var(--gray-200);
        }
        .article-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .article-meta-item strong {
            color: var(--gray-700);
            font-weight: 600;
        }
        .meta-icon {
            font-size: 15px;
        }

        /* ===== TABLE OF CONTENTS ===== */
        .toc-box {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 24px 28px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-sm);
        }
        .toc-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .toc-title-icon {
            width: 28px;
            height: 28px;
            background: #f3e8ff;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--purple-primary);
        }
        .toc-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .toc-list li {
            margin-bottom: 10px;
        }
        .toc-list li:last-child {
            margin-bottom: 0;
        }
        .toc-list a {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--gray-600);
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            transition: all 0.2s;
        }
        .toc-list a:hover {
            background: #f3e8ff;
            color: var(--purple-primary);
        }
        .toc-number {
            width: 24px;
            height: 24px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-500);
            flex-shrink: 0;
        }
        .toc-list a:hover .toc-number {
            background: var(--purple-primary);
            color: #fff;
        }

        /* ===== ARTICLE CONTENT ===== */
        .article-content h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 48px 0 16px;
            padding-top: 16px;
        }
        .article-content h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
            margin: 32px 0 12px;
        }
        .article-content p {
            font-size: 15px;
            color: var(--gray-700);
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .article-content ul,
        .article-content ol {
            padding-left: 24px;
            margin-bottom: 20px;
            list-style: disc;
        }
        .article-content ol {
            list-style: decimal;
        }
        .article-content li {
            font-size: 15px;
            color: var(--gray-700);
            line-height: 1.7;
            margin-bottom: 10px;
        }
        .article-content strong {
            color: var(--gray-900);
            font-weight: 600;
        }

        /* Info/Tip boxes */
        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-left: 4px solid var(--blue-accent);
            border-radius: var(--radius-sm);
            padding: 20px 24px;
            margin: 24px 0;
        }
        .info-box p {
            margin-bottom: 0;
            font-size: 14px;
            color: var(--gray-700);
        }
        .info-box-title {
            font-weight: 700;
            color: var(--blue-accent);
            margin-bottom: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .warning-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid var(--yellow-accent);
            border-radius: var(--radius-sm);
            padding: 20px 24px;
            margin: 24px 0;
        }
        .warning-box p {
            margin-bottom: 0;
            font-size: 14px;
            color: var(--gray-700);
        }
        .warning-box-title {
            font-weight: 700;
            color: #b45309;
            margin-bottom: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Steps list */
        .steps-list {
            list-style: none;
            padding: 0;
            margin: 24px 0;
            counter-reset: step-counter;
        }
        .steps-list li {
            counter-increment: step-counter;
            position: relative;
            padding: 20px 20px 20px 64px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            margin-bottom: 12px;
            font-size: 15px;
            color: var(--gray-700);
            line-height: 1.6;
        }
        .steps-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 20px;
            top: 20px;
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }
        .steps-list li strong {
            display: block;
            color: var(--gray-900);
            font-weight: 700;
            margin-bottom: 4px;
        }

        /* Costs table */
        .costs-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            font-size: 14px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }
        .costs-table thead {
            background: var(--gray-50);
        }
        .costs-table th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 700;
            color: var(--gray-800);
            border-bottom: 1px solid var(--gray-200);
            font-size: 13px;
        }
        .costs-table td {
            padding: 14px 16px;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }
        .costs-table tr:last-child td {
            border-bottom: none;
        }
        .costs-table tr:hover {
            background: var(--gray-50);
        }

        /* ===== FAQ ACCORDION ===== */
        .faq-section {
            margin-top: 48px;
            padding-top: 32px;
            border-top: 1px solid var(--gray-200);
        }
        .faq-section-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 24px;
        }
        .faq-item {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            margin-bottom: 12px;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .faq-item:hover {
            box-shadow: var(--shadow-sm);
        }
        .faq-item summary {
            padding: 18px 24px;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            list-style: none;
            transition: background 0.2s;
        }
        .faq-item summary::-webkit-details-marker {
            display: none;
        }
        .faq-item summary::after {
            content: '+';
            font-size: 18px;
            font-weight: 500;
            color: var(--purple-primary);
            transition: transform 0.2s;
            flex-shrink: 0;
            margin-left: 12px;
        }
        .faq-item[open] summary::after {
            content: '-';
        }
        .faq-item[open] summary {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        .faq-answer {
            padding: 18px 24px;
            font-size: 14px;
            color: var(--gray-600);
            line-height: 1.7;
        }

        /* ===== RELATED GUIDES ===== */
        .related-section {
            margin-top: 48px;
            padding-top: 32px;
            border-top: 1px solid var(--gray-200);
        }
        .related-section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 20px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .related-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 20px;
            transition: box-shadow 0.2s, border-color 0.2s;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .related-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--purple-light);
        }
        .related-card-category {
            font-size: 11px;
            font-weight: 600;
            color: var(--purple-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .related-card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
            line-height: 1.4;
        }
        .related-card-meta {
            font-size: 12px;
            color: var(--gray-400);
        }

        /* ===== EXPERT BOX ===== */
        .expert-box {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 28px;
            margin-top: 48px;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            box-shadow: var(--shadow-sm);
        }
        .expert-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f3e8ff, #ede9fe);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }
        .expert-info {
            flex: 1;
        }
        .expert-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--purple-primary);
            margin-bottom: 4px;
        }
        .expert-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }
        .expert-role {
            font-size: 13px;
            color: var(--gray-500);
            margin-bottom: 8px;
        }
        .expert-stats {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 12px;
            color: var(--gray-500);
        }
        .expert-stat {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .expert-stat-icon {
            color: #f59e0b;
        }
        .expert-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--purple-primary);
            color: #fff;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
            align-self: center;
            flex-shrink: 0;
        }
        .expert-btn:hover {
            background: var(--purple-dark);
        }

        /* ===== CTA BOX ===== */
        .cta-box {
            background: linear-gradient(135deg, #1a0a3e, #4c1d95);
            border-radius: var(--radius-xl);
            padding: 40px;
            margin-top: 48px;
            text-align: center;
        }
        .cta-box h2 {
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .cta-box p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin-bottom: 24px;
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
        }
        .cta-box-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--orange-cta);
            color: #fff;
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 700;
            transition: background 0.2s;
            box-shadow: 0 4px 16px rgba(249,115,22,0.3);
        }
        .cta-box-btn:hover {
            background: var(--orange-hover);
        }
        .cta-box-badges {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            margin-top: 20px;
        }
        .cta-box-badge {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .cta-box-badge::before {
            content: '';
            width: 16px;
            height: 16px;
            background: rgba(16,185,129,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .article-title {
                font-size: 26px;
            }
            .article-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .related-grid {
                grid-template-columns: 1fr;
            }
            .expert-box {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .expert-stats {
                justify-content: center;
            }
            .cta-box {
                padding: 28px 20px;
            }
            .cta-box-badges {
                flex-direction: column;
                gap: 10px;
            }
            .steps-list li {
                padding-left: 56px;
            }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php pp_pro_header( 'poradniki' ); ?>

<main class="article-wrapper">
    <!-- BREADCRUMB -->
    <nav class="article-breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
        <span class="sep">/</span>
        <a href="<?php echo esc_url( home_url( '/poradniki/' ) ); ?>">Poradniki</a>
        <span class="sep">/</span>
        <a href="<?php echo esc_url( home_url( '/kategoria/prawo/' ) ); ?>">Prawo</a>
        <span class="sep">/</span>
        <span class="current">Jak napisac testament</span>
    </nav>

    <article>
        <!-- ARTICLE HEADER -->
        <header class="article-header">
            <span class="article-category-badge">Prawo spadkowe</span>
            <h1 class="article-title">Jak napisac testament &mdash; krok po kroku</h1>
            <p class="article-subtitle">Kompletny przewodnik po sporzadzaniu testamentu w Polsce. Dowiedz sie, jakie sa wymagania formalne, ile to kosztuje i jak uniknac najczestszych bledow.</p>
            <div class="article-meta">
                <div class="article-meta-item">
                    <span class="meta-icon">&#9998;</span>
                    <span>Autor: <strong>Mec. Anna Kowalska</strong></span>
                </div>
                <div class="article-meta-item">
                    <span class="meta-icon">&#128197;</span>
                    <span>Aktualizacja: <?php echo esc_html( gmdate( 'd.m.Y' ) ); ?></span>
                </div>
                <div class="article-meta-item">
                    <span class="meta-icon">&#9201;</span>
                    <span>12 min czytania</span>
                </div>
                <div class="article-meta-item">
                    <span class="meta-icon">&#10003;</span>
                    <span>Zweryfikowany przez eksperta</span>
                </div>
            </div>
        </header>

        <!-- TABLE OF CONTENTS -->
        <div class="toc-box">
            <div class="toc-title">
                <span class="toc-title-icon">&#9776;</span>
                Spis tresci
            </div>
            <ol class="toc-list">
                <li><a href="#czym-jest"><span class="toc-number">1</span> Czym jest testament?</a></li>
                <li><a href="#rodzaje"><span class="toc-number">2</span> Rodzaje testamentow w polskim prawie</a></li>
                <li><a href="#kto-moze"><span class="toc-number">3</span> Kto moze sporzadzic testament?</a></li>
                <li><a href="#jak-napisac"><span class="toc-number">4</span> Jak napisac testament krok po kroku</a></li>
                <li><a href="#bledy"><span class="toc-number">5</span> Najczestsze bledy przy sporzadzaniu testamentu</a></li>
                <li><a href="#koszty"><span class="toc-number">6</span> Koszty sporzadzenia testamentu</a></li>
                <li><a href="#zmiana"><span class="toc-number">7</span> Zmiana i odwolanie testamentu</a></li>
                <li><a href="#faq"><span class="toc-number">8</span> Najczesciej zadawane pytania</a></li>
            </ol>
        </div>

        <!-- ARTICLE CONTENT -->
        <div class="article-content">
            <!-- Section 1 -->
            <h2 id="czym-jest">1. Czym jest testament?</h2>
            <p>Testament to jednostronne oswiadczenie woli spadkodawcy, w ktorym rozporzadza on swoim majatkiem na wypadek smierci. Jest to jedyny dokument, ktory pozwala zmienic zasady dziedziczenia ustawowego i samodzielnie zdecydowac, kto i w jakim zakresie odziedziczy nasz majatek.</p>
            <p>Testament jest dokumentem osobistym &mdash; nie mozna go sporzadzic przez przedstawiciela. Kazda osoba pelnolenia i posiadajaca pelna zdolnosc do czynnosci prawnych moze sporzadzic testament. Wazne jest, aby w chwili sporzadzania testamentu spadkodawca dzialal swiadomie i swobodnie.</p>

            <div class="info-box">
                <div class="info-box-title">&#8505; Podstawa prawna</div>
                <p>Regulacje dotyczace testamentow znajduja sie w Kodeksie cywilnym, w art. 941&ndash;990. Okreslaja one formy testamentow, warunki ich waznosci oraz zasady interpretacji.</p>
            </div>

            <!-- Section 2 -->
            <h2 id="rodzaje">2. Rodzaje testamentow w polskim prawie</h2>
            <p>Polskie prawo wyroznia kilka form testamentu, ktore mozna podzielic na dwie glowne kategorie: testamenty zwykle i testamenty szczegolne.</p>

            <h3>Testamenty zwykle</h3>
            <ul>
                <li><strong>Testament wlasnoreczny (holograficzny)</strong> &mdash; napisany w calosci reka spadkodawcy, opatrzony data i podpisem. Najprostsza i najczestsza forma.</li>
                <li><strong>Testament notarialny</strong> &mdash; sporzadzony w formie aktu notarialnego przez notariusza. Najbezpieczniejsza forma, trudna do podwazenia.</li>
                <li><strong>Testament allograficzny (urzedowy)</strong> &mdash; oswiadczenie woli zlozone ustnie wobec wójta, burmistrza, prezydenta miasta, starosty, marszalka wojewodztwa, sekretarza powiatu lub gminy w obecnosci dwoch swiadkow.</li>
            </ul>

            <h3>Testamenty szczegolne</h3>
            <ul>
                <li><strong>Testament ustny</strong> &mdash; sporzadzony w sytuacji obawy rychlej smierci lub szczegolnych okolicznosci uniemozliwiajacych inna forme.</li>
                <li><strong>Testament podrozny</strong> &mdash; sporzadzony na polskim statku morskim lub powietrznym.</li>
                <li><strong>Testament wojskowy</strong> &mdash; przewidziany dla zolnierzy w czasie mobilizacji lub wojny.</li>
            </ul>

            <div class="warning-box">
                <div class="warning-box-title">&#9888; Uwaga</div>
                <p>Testamenty szczegolne traca moc z uplywem szesciu miesiecy od ustania okolicznosci uzasadniajacych ich sporzadzenie, chyba ze spadkodawca zmarl przed uplywem tego terminu.</p>
            </div>

            <!-- Section 3 -->
            <h2 id="kto-moze">3. Kto moze sporzadzic testament?</h2>
            <p>Aby testament byl wazny, spadkodawca musi spelniac nastepujace warunki:</p>
            <ul>
                <li>Byc osobą pelnoletnia (ukonczenie 18 lat)</li>
                <li>Posiadac pelna zdolnosc do czynnosci prawnych</li>
                <li>Dzialac swiadomie i swobodnie (bez przymusu, grozy czy bledu)</li>
                <li>Nie byc osobą ubezwlasnowolniona (calkowicie lub czesciowo)</li>
            </ul>
            <p>Testament sporzadzony przez osobe niemajaca pelnej zdolnosci do czynnosci prawnych jest niewazny i nie moze byc konwalidowany (naprawiony) nawet jesli ta osoba pozniej uzyska pelna zdolnosc.</p>

            <!-- Section 4 -->
            <h2 id="jak-napisac">4. Jak napisac testament krok po kroku</h2>
            <p>Ponizej przedstawiamy szczegolowa instrukcje sporzadzenia testamentu wlasnorecznego, ktory jest najczesciej wybierana forma w Polsce.</p>

            <ol class="steps-list">
                <li>
                    <strong>Przygotuj sie mentalnie i zebierz informacje</strong>
                    Zastanow sie, komu chcesz przekazac swoj majatek. Przygotuj liste wszystkich spadkobiercow z ich pelnymi danymi (imie, nazwisko, data urodzenia, stopien pokrewienstwa).
                </li>
                <li>
                    <strong>Napisz caly testament odreczne</strong>
                    Wez czysta kartke papieru i dlugopis. Caly tekst testamentu musi byc napisany Twoja reka &mdash; nie moze byc wydrukowany ani napisany przez inna osobe. Pisz czytelnie.
                </li>
                <li>
                    <strong>Umiec date sporzadzenia</strong>
                    Na poczatku lub na koncu dokumentu wpisz pelna date (dzien, miesiac, rok). Brak daty nie powoduje automatycznej niewaznosci, ale moze byc podstawa do podwazenia testamentu.
                </li>
                <li>
                    <strong>Jasno okresl spadkobiercow i udzialy</strong>
                    Wskazuj spadkobiercow w sposob niebudzacy watpliwosci. Podaj pelne imiona i nazwiska oraz okresli udzialy w spadku (np. &quot;1/2 calosci spadku&quot;, &quot;caly majatek&quot;).
                </li>
                <li>
                    <strong>Mozesz dodac zapisy i polecenia</strong>
                    Oprócz powolania spadkobiercow mozesz umiescic zapisy (przekazanie konkretnych przedmiotow) oraz polecenia (zobowiazanie spadkobiercy do okreslonego dzialania).
                </li>
                <li>
                    <strong>Zloz podpis</strong>
                    Na samym koncu testamentu zloz pelny podpis (imie i nazwisko). Podpis powinien byc taki, jakiego zwykle uzywasz. Sam inicjal lub parafka nie wystarczy.
                </li>
                <li>
                    <strong>Przechowaj testament w bezpiecznym miejscu</strong>
                    Zdeponuj testament u notariusza (Notarialny Rejestr Testamentow &mdash; NORT), w banku lub w innym bezpiecznym miejscu. Poinformuj zaufana osobe o jego istnieniu.
                </li>
            </ol>

            <div class="info-box">
                <div class="info-box-title">&#128161; Porada eksperta</div>
                <p>Zalecamy zarejestrowanie testamentu w Notarialnym Rejestrze Testamentow (NORT). Rejestracja kosztuje ok. 100 zl i gwarantuje, ze testament zostanie odnaleziony po smierci spadkodawcy.</p>
            </div>

            <!-- Section 5 -->
            <h2 id="bledy">5. Najczestsze bledy przy sporzadzaniu testamentu</h2>
            <p>Wiele testamentow jest kwestionowanych lub uznawanych za niewazne z powodu bledow formalnych. Oto najczestsze problemy:</p>

            <ul>
                <li><strong>Napisanie testamentu na komputerze</strong> &mdash; testament wlasnoreczny musi byc w calosci napisany reka. Wydrukowany dokument, nawet podpisany odreczne, jest niewazny.</li>
                <li><strong>Brak podpisu lub niepelny podpis</strong> &mdash; podpis musi znajdowac sie na koncu testamentu i zawierac co najmniej nazwisko.</li>
                <li><strong>Niejasne okreslenie spadkobiercow</strong> &mdash; uzywanie przezwisk lub nieprecyzyjnych okreslen moze prowadzic do sporow.</li>
                <li><strong>Sporzadzenie testamentu pod wplywem presji</strong> &mdash; testament sporzadzony pod wplywem grozy, bledu lub podstepu jest niewazny.</li>
                <li><strong>Brak daty</strong> &mdash; choc nie zawsze powoduje niewaznosc, brak daty moze uniemozliwic ustalenie kolejnosci testamentow.</li>
                <li><strong>Wspólny testament malzonkow</strong> &mdash; w Polsce testament musi byc sporządzony indywidualnie. Wspólny testament dwóch osob jest niewazny.</li>
                <li><strong>Pominięcie uprawnionych do zachowku</strong> &mdash; nawet wydziedziczenie nie jest skuteczne bez podania przyczyny.</li>
            </ul>

            <div class="warning-box">
                <div class="warning-box-title">&#9888; Czeste nieporozumienie</div>
                <p>Testament nie musi byc sporzadzony na specjalnym formularzu ani poswiadczony przez notariusza, aby byc wazny. Wystarczy, ze zostanie w calosci napisany odreczne, opatrzony data i podpisany.</p>
            </div>

            <!-- Section 6 -->
            <h2 id="koszty">6. Koszty sporzadzenia testamentu</h2>
            <p>Koszty zaleza od wybranej formy testamentu. Ponizej przedstawiamy orientacyjne koszty:</p>

            <table class="costs-table">
                <thead>
                    <tr>
                        <th>Rodzaj testamentu</th>
                        <th>Koszt netto</th>
                        <th>Koszt z VAT (23%)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Testament wlasnoreczny</td>
                        <td>0 zl (darmowy)</td>
                        <td>0 zl</td>
                    </tr>
                    <tr>
                        <td>Testament notarialny (prosty)</td>
                        <td>50 zl</td>
                        <td>61,50 zl</td>
                    </tr>
                    <tr>
                        <td>Testament notarialny (z zapisem windykacyjnym)</td>
                        <td>150 zl</td>
                        <td>184,50 zl</td>
                    </tr>
                    <tr>
                        <td>Testament notarialny (zlozony, z poleceniami)</td>
                        <td>150&ndash;200 zl</td>
                        <td>184,50&ndash;246 zl</td>
                    </tr>
                    <tr>
                        <td>Rejestracja w NORT</td>
                        <td>ok. 100 zl</td>
                        <td>ok. 123 zl</td>
                    </tr>
                </tbody>
            </table>

            <p>Do kosztow notarialnych nalezy doliczyc ewentualna oplate za wypisy aktu notarialnego (6 zl netto za strone). W przypadku wizyty notariusza poza kancelaria (np. w szpitalu) koszty moga byc wyzsze.</p>

            <!-- Section 7 -->
            <h2 id="zmiana">7. Zmiana i odwolanie testamentu</h2>
            <p>Testament mozna zmienic lub odwolac w kazdym czasie. Istnieja trzy sposoby odwolania testamentu:</p>
            <ul>
                <li><strong>Sporzadzenie nowego testamentu</strong> &mdash; nowszy testament automatycznie odwoluje postanowienia wczesniejszego w zakresie, w jakim sa sprzeczne.</li>
                <li><strong>Zniszczenie testamentu</strong> &mdash; podarcie, spalenie lub inne zniszczenie dokumentu z zamiarem jego odwolania.</li>
                <li><strong>Dokonanie zmian w dokumencie</strong> &mdash; przekreslenie tresci, usuniecie postanowien z zamiarem ich odwolania.</li>
            </ul>

            <div class="info-box">
                <div class="info-box-title">&#128161; Wazna informacja</div>
                <p>W przypadku odwolania testamentu notarialnego, zalecamy wizyte u notariusza w celu sporzadzenia protokolu odwolania. Zapewni to jednoznacznosc Twojej decyzji.</p>
            </div>
        </div>

        <!-- FAQ SECTION -->
        <section class="faq-section" id="faq">
            <h2 class="faq-section-title">Najczesciej zadawane pytania</h2>
            <?php
            $faqs = array(
                array(
                    'q' => 'Czy testament musi byc napisany odreczne?',
                    'a' => 'Tak, testament wlasnoreczny (holograficzny) musi byc w calosci napisany reka spadkodawcy. Nie moze byc wydrukowany na komputerze ani napisany przez inna osobe. Ta zasada dotyczy calego tekstu, nie tylko podpisu. Alternatywnie mozna sporzadzic testament notarialny, ktory jest przygotowywany przez notariusza.',
                ),
                array(
                    'q' => 'Czy moge zmienic testament po jego sporzadzeniu?',
                    'a' => 'Tak, testament mozna zmienic lub odwolac w kazdej chwili. Mozesz sporzadzic nowy testament (ktory automatycznie zastapi poprzedni w zakresie sprzecznych postanowien), zniszczyc stary testament lub dokonac w nim zmian. Nie ma limitu liczby zmian.',
                ),
                array(
                    'q' => 'Ile kosztuje sporzadzenie testamentu u notariusza?',
                    'a' => 'Koszt sporzadzenia prostego testamentu notarialnego wynosi 50 zl netto (61,50 zl brutto z VAT). Testament z zapisem windykacyjnym to 150 zl netto. Dodatkowe koszty to wypisy (6 zl/strona) oraz ewentualna rejestracja w NORT (ok. 100 zl).',
                ),
                array(
                    'q' => 'Czy testament mozna podwazyc?',
                    'a' => 'Tak, testament mozna podwazyc w sadzie. Najczestsze powody to: brak zdolnosci testowania (np. choroba psychiczna), wady oswiadczenia woli (blad, grozba, podstep), niezachowanie formy (np. brak podpisu) lub sporzadzenie testamentu przez osobe niepelnolenia.',
                ),
                array(
                    'q' => 'Co sie dzieje, jesli nie zostawie testamentu?',
                    'a' => 'W przypadku braku testamentu stosuje sie dziedziczenie ustawowe. Majatek dziedziczy najpierw malzonek i dzieci w rownych czesciach (malzonek minimum 1/4). Jesli nie ma dzieci, dziedzicza malzonek i rodzice spadkodawcy. Kolejnosc dziedziczenia ustawowego reguluje Kodeks cywilny.',
                ),
                array(
                    'q' => 'Czy swiadkowie sa potrzebni przy testamencie wlasnorecznym?',
                    'a' => 'Nie, testament wlasnoreczny nie wymaga obecnosci swiadkow. Wystarczy, ze jest w calosci napisany reka spadkodawcy, opatrzony data i podpisany. Swiadkowie sa wymagani przy testamencie allograficznym (2 swiadkow) oraz ustnym (3 swiadkow).',
                ),
                array(
                    'q' => 'Jak dlugo jest wazny testament?',
                    'a' => 'Testament zwykly (wlasnoreczny, notarialny, allograficzny) nie traci waznosci z uplywem czasu. Jest wazny dopoki nie zostanie odwolany lub zastapiony nowym. Tylko testamenty szczegolne (ustny, podrozny, wojskowy) traca moc po 6 miesiacach od ustania okolicznosci uzasadniajacych ich sporzadzenie.',
                ),
            );
            foreach ( $faqs as $faq ) :
            ?>
                <details class="faq-item">
                    <summary><?php echo esc_html( $faq['q'] ); ?></summary>
                    <div class="faq-answer"><?php echo esc_html( $faq['a'] ); ?></div>
                </details>
            <?php endforeach; ?>
        </section>

        <!-- EXPERT BOX -->
        <div class="expert-box">
            <div class="expert-avatar">&#128105;&#8205;&#9878;&#65039;</div>
            <div class="expert-info">
                <div class="expert-label">Autor artykulu</div>
                <div class="expert-name">Mec. Anna Kowalska</div>
                <div class="expert-role">Adwokat, specjalistka prawa spadkowego i rodzinnego</div>
                <div class="expert-stats">
                    <span class="expert-stat"><span class="expert-stat-icon">&#9733;</span> 4.9 (312 opinii)</span>
                    <span class="expert-stat">&#9998; 156 artykulow</span>
                    <span class="expert-stat">&#128172; 1 240 odpowiedzi</span>
                </div>
            </div>
            <a href="<?php echo esc_url( home_url( '/specjalista/anna-kowalska/' ) ); ?>" class="expert-btn">Zobacz profil</a>
        </div>

        <!-- RELATED GUIDES -->
        <section class="related-section">
            <h2 class="related-section-title">Powiazane poradniki</h2>
            <div class="related-grid">
                <a href="<?php echo esc_url( home_url( '/poradnik/spadek-poradnik/' ) ); ?>" class="related-card">
                    <span class="related-card-category">Prawo spadkowe</span>
                    <span class="related-card-title">Spadek &mdash; co musisz wiedziec. Kompletny przewodnik</span>
                    <span class="related-card-meta">15 min czytania &middot; Aktualizacja: <?php echo esc_html( gmdate( 'd.m.Y' ) ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/poradnik/zachowek-ile-wynosi/' ) ); ?>" class="related-card">
                    <span class="related-card-category">Prawo spadkowe</span>
                    <span class="related-card-title">Zachowek &mdash; ile wynosi i kto moze go zadac?</span>
                    <span class="related-card-meta">10 min czytania &middot; Aktualizacja: <?php echo esc_html( gmdate( 'd.m.Y' ) ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/poradnik/dziedziczenie-ustawowe/' ) ); ?>" class="related-card">
                    <span class="related-card-category">Prawo spadkowe</span>
                    <span class="related-card-title">Dziedziczenie ustawowe &mdash; kto dziedziczy bez testamentu?</span>
                    <span class="related-card-meta">8 min czytania &middot; Aktualizacja: <?php echo esc_html( gmdate( 'd.m.Y' ) ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/poradnik/odrzucenie-spadku/' ) ); ?>" class="related-card">
                    <span class="related-card-category">Prawo spadkowe</span>
                    <span class="related-card-title">Odrzucenie spadku &mdash; terminy, procedura, konsekwencje</span>
                    <span class="related-card-meta">11 min czytania &middot; Aktualizacja: <?php echo esc_html( gmdate( 'd.m.Y' ) ); ?></span>
                </a>
            </div>
        </section>

        <!-- CTA BOX -->
        <section class="cta-box">
            <h2>Potrzebujesz indywidualnej porady prawnej?</h2>
            <p>Zadaj pytanie naszym ekspertom prawa spadkowego. Otrzymasz odpowiedz w ciagu 24 godzin.</p>
            <a href="<?php echo esc_url( home_url( '/zadaj-pytanie/' ) ); ?>" class="cta-box-btn">Zadaj pytanie ekspertowi &#8594;</a>
            <div class="cta-box-badges">
                <span class="cta-box-badge">Odpowiedz w 24h</span>
                <span class="cta-box-badge">Zweryfikowani prawnicy</span>
                <span class="cta-box-badge">Gwarancja satysfakcji</span>
            </div>
        </section>
    </article>
</main>

<?php pp_pro_footer(); ?>

<?php wp_footer(); ?>
</body>
</html>
