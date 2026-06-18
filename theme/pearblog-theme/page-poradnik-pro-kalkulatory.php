<?php
/**
 * Template Name: Poradnik.pro - Kalkulatory
 *
 * Calculators listing page for Poradnik.pro. Uses the shared design system
 * (purple Inter-based, no Tailwind) with WordPress integration.
 *
 * @package PearBlog
 * @subpackage PoradnikPro
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
        /* ===== PAGE HERO (green variant for calculators) ===== */
        .page-hero {
            background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%);
            padding: 48px 0;
        }

        /* ===== CALCULATORS SECTION ===== */
        .calculators-section { padding: 48px 0 64px; }
        .section-intro {
            text-align: center;
            max-width: 640px;
            margin: 0 auto 40px;
        }
        .section-intro h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        .section-intro p {
            font-size: 15px;
            color: var(--gray-500);
        }

        /* ===== CALCULATOR GRID ===== */
        .calculators-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .calc-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 28px;
            text-align: center;
            transition: box-shadow 0.2s, transform 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .calc-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .calc-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 16px;
        }
        .calc-icon.green { background: #d1fae5; }
        .calc-icon.blue { background: #dbeafe; }
        .calc-icon.orange { background: #ffedd5; }
        .calc-icon.purple { background: #f3e8ff; }
        .calc-icon.red { background: #fee2e2; }
        .calc-icon.yellow { background: #fef3c7; }
        .calc-icon.teal { background: #ccfbf1; }
        .calc-icon.pink { background: #fce7f3; }
        .calc-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        .calc-desc {
            font-size: 13px;
            color: var(--gray-500);
            line-height: 1.6;
            margin-bottom: 16px;
            flex-grow: 1;
        }
        .calc-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            font-size: 12px;
            color: var(--gray-400);
            margin-bottom: 16px;
        }
        .btn-calc {
            display: inline-block;
            background: var(--green-accent);
            color: #fff;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-calc:hover { background: #059669; }

        /* ===== HOW IT WORKS ===== */
        .how-it-works {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 40px;
            margin-bottom: 40px;
        }
        .how-it-works h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            text-align: center;
            margin-bottom: 32px;
        }
        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }
        .step { text-align: center; }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--purple-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            margin: 0 auto 12px;
        }
        .step-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 6px;
        }
        .step-desc {
            font-size: 13px;
            color: var(--gray-500);
            line-height: 1.5;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .calculators-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .calculators-grid { grid-template-columns: 1fr; }
            .steps { grid-template-columns: 1fr; gap: 24px; }
            .page-hero h1 { font-size: 26px; }
            .how-it-works { padding: 24px; }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'kalkulatory' ); ?>

<!-- PAGE HERO -->
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
            <span class="sep">/</span>
            <span>Kalkulatory</span>
        </div>
        <h1>Kalkulatory</h1>
        <p>Sprawdz liczby zanim podejmiesz decyzje. Precyzyjne wyliczenia w kilka sekund, oparte na aktualnych danych rynkowych.</p>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="calculators-section">
    <div class="container">
        <div class="how-it-works">
            <h2>Jak dzialaja nasze kalkulatory?</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Podaj parametry</h3>
                    <p class="step-desc">Kwota, okres, oprocentowanie lub metraz - wypelnienie formularza zajmie kilka chwil.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Otrzymaj wyliczenie</h3>
                    <p class="step-desc">Realna kalkulacja oparta na aktualnych wskaznikach i cenach rynkowych.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Podejmij decyzje</h3>
                    <p class="step-desc">Porownaj warianty i wybierz najlepsza opcje dla Twojej sytuacji finansowej.</p>
                </div>
            </div>
        </div>

        <div class="section-intro">
            <h2>Wybierz kalkulator</h2>
            <p>Wszystkie kalkulatory sa bezplatne i nie wymagaja rejestracji. Wyniki otrzymasz natychmiast.</p>
        </div>

        <!-- CALCULATORS GRID -->
        <div class="calculators-grid">

            <!-- 1. Kredyt hipoteczny -->
            <a href="<?php echo esc_url( home_url( '/kalkulator/kredyt-hipoteczny/' ) ); ?>" class="calc-card">
                <div class="calc-icon green">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <h3 class="calc-title">Kredyt hipoteczny</h3>
                <p class="calc-desc">Oblicz miesieczna rate kredytu hipotecznego, calkowity koszt zobowiazania i harmonogram splat dla roznych wariantow oprocentowania.</p>
                <div class="calc-meta">
                    <span>~2 min</span>
                    <span>34k obliczen</span>
                </div>
                <span class="btn-calc">Oblicz</span>
            </a>

            <!-- 2. Zdolnosc kredytowa -->
            <a href="<?php echo esc_url( home_url( '/kalkulator/zdolnosc-kredytowa/' ) ); ?>" class="calc-card">
                <div class="calc-icon blue">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <h3 class="calc-title">Zdolnosc kredytowa</h3>
                <p class="calc-desc">Sprawdz jaka maksymalna kwote kredytu mozesz uzyskac na podstawie Twoich dochodow, zobowiazan i wydatkow gospodarstwa domowego.</p>
                <div class="calc-meta">
                    <span>~3 min</span>
                    <span>28k obliczen</span>
                </div>
                <span class="btn-calc">Oblicz</span>
            </a>

            <!-- 3. Koszt budowy domu -->
            <a href="<?php echo esc_url( home_url( '/kalkulator/koszt-budowy-domu/' ) ); ?>" class="calc-card">
                <div class="calc-icon orange">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="6" width="22" height="16" rx="2"/><path d="M1 10h22"/><path d="M12 6V2"/><path d="M8 2h8"/></svg>
                </div>
                <h3 class="calc-title">Koszt budowy domu</h3>
                <p class="calc-desc">Oszacuj calkowity koszt budowy domu jednorodzinnego - od fundamentow po wykonczenie, z uwzglednieniem aktualnych cen materialow.</p>
                <div class="calc-meta">
                    <span>~3 min</span>
                    <span>21k obliczen</span>
                </div>
                <span class="btn-calc">Oblicz</span>
            </a>

            <!-- 4. Kalkulator OC -->
            <a href="<?php echo esc_url( home_url( '/kalkulator/oc/' ) ); ?>" class="calc-card">
                <div class="calc-icon red">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3 class="calc-title">Kalkulator OC</h3>
                <p class="calc-desc">Porownaj ceny ubezpieczenia OC i AC od roznych towarzystw. Wprowadz dane pojazdu i sprawdz orientacyjna skladke roczna.</p>
                <div class="calc-meta">
                    <span>~2 min</span>
                    <span>19k obliczen</span>
                </div>
                <span class="btn-calc">Oblicz</span>
            </a>

            <!-- 5. Kalkulator wynagrodzen -->
            <a href="<?php echo esc_url( home_url( '/kalkulator/wynagrodzenia/' ) ); ?>" class="calc-card">
                <div class="calc-icon purple">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
                <h3 class="calc-title">Kalkulator wynagrodzen</h3>
                <p class="calc-desc">Przelicz wynagrodzenie brutto na netto i odwrotnie. Uwzglednij forme zatrudnienia: UoP, zlecenie, dzielo lub B2B.</p>
                <div class="calc-meta">
                    <span>~1 min</span>
                    <span>45k obliczen</span>
                </div>
                <span class="btn-calc">Oblicz</span>
            </a>

            <!-- 6. ROI fotowoltaiki -->
            <a href="<?php echo esc_url( home_url( '/kalkulator/roi-fotowoltaiki/' ) ); ?>" class="calc-card">
                <div class="calc-icon yellow">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                </div>
                <h3 class="calc-title">ROI fotowoltaiki</h3>
                <p class="calc-desc">Czy panele sloneczne sie oplaca? Oblicz zwrot z inwestycji, roczne oszczednosci i czas amortyzacji instalacji fotowoltaicznej.</p>
                <div class="calc-meta">
                    <span>~2 min</span>
                    <span>16k obliczen</span>
                </div>
                <span class="btn-calc">Oblicz</span>
            </a>

            <!-- 7. Koszt remontu -->
            <a href="<?php echo esc_url( home_url( '/kalkulator/koszt-remontu/' ) ); ?>" class="calc-card">
                <div class="calc-icon teal">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
                <h3 class="calc-title">Koszt remontu</h3>
                <p class="calc-desc">Oszacuj budzet remontu mieszkania lub domu. Podaj metraz, zakres prac i standard wykonczenia, a otrzymasz szacunkowy kosztorys.</p>
                <div class="calc-meta">
                    <span>~2 min</span>
                    <span>23k obliczen</span>
                </div>
                <span class="btn-calc">Oblicz</span>
            </a>

            <!-- 8. Rata leasingu -->
            <a href="<?php echo esc_url( home_url( '/kalkulator/rata-leasingu/' ) ); ?>" class="calc-card">
                <div class="calc-icon pink">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#db2777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <h3 class="calc-title">Rata leasingu</h3>
                <p class="calc-desc">Oblicz miesieczna rate leasingu operacyjnego lub finansowego. Porownaj calkowity koszt dla roznych okresow i wplat wlasnych.</p>
                <div class="calc-meta">
                    <span>~2 min</span>
                    <span>12k obliczen</span>
                </div>
                <span class="btn-calc">Oblicz</span>
            </a>

            <!-- 9. Kalkulator oszczednosci energii -->
            <a href="<?php echo esc_url( home_url( '/kalkulator/oszczednosc-energii/' ) ); ?>" class="calc-card">
                <div class="calc-icon green">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                </div>
                <h3 class="calc-title">Oszczednosc energii</h3>
                <p class="calc-desc">Ile zaoszczedzisz wymieniajac okna, ocieplajac dom lub instalujac pompe ciepla? Oblicz roczna redukcje kosztow ogrzewania.</p>
                <div class="calc-meta">
                    <span>~2 min</span>
                    <span>9k obliczen</span>
                </div>
                <span class="btn-calc">Oblicz</span>
            </a>

        </div><!-- .calculators-grid -->
    </div>
</section>

<?php pp_pro_footer(); ?>

<?php wp_footer(); ?>
</body>
</html>
