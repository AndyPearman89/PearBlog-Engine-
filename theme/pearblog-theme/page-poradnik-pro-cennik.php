<?php
/**
 * Template Name: Poradnik.PRO - Cennik
 *
 * Pricing page for specialists who want to advertise on Poradnik.pro.
 * Three tiers: Start (free), Pro (recommended), Premium.
 * Uses the shared purple Inter-based design system (no Tailwind).
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
        /* ===== CENNIK PAGE STYLES ===== */
        .page-hero--cennik {
            background: linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%);
            padding: 56px 0;
        }
        .page-hero--cennik h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        .page-hero--cennik .subtitle {
            font-size: 16px;
            color: var(--gray-600);
            max-width: 600px;
        }

        /* ===== PRICING SECTION ===== */
        .pricing-section {
            padding: 64px 0;
        }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
            align-items: start;
        }

        /* ===== PRICING CARD ===== */
        .pricing-card {
            background: #fff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
            padding: 36px 28px;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        /* Featured / Pro card */
        .pricing-card--featured {
            border: 2px solid var(--purple-primary);
            box-shadow: 0 12px 40px rgba(108, 43, 217, 0.15);
            transform: scale(1.04);
            z-index: 2;
        }
        .pricing-card--featured:hover {
            transform: scale(1.04) translateY(-4px);
            box-shadow: 0 16px 48px rgba(108, 43, 217, 0.2);
        }

        .pricing-badge {
            position: absolute;
            top: -13px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .pricing-card__name {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 6px;
        }
        .pricing-card__desc {
            font-size: 13px;
            color: var(--gray-500);
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .pricing-card__price {
            font-size: 40px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 4px;
            line-height: 1.1;
        }
        .pricing-card__price .currency {
            font-size: 18px;
            vertical-align: super;
            font-weight: 600;
        }
        .pricing-card__price .period {
            font-size: 14px;
            color: var(--gray-500);
            font-weight: 400;
        }
        .pricing-card__price--free {
            font-size: 36px;
            font-weight: 800;
            color: var(--green-accent);
            margin-bottom: 4px;
        }

        .pricing-card__features {
            margin: 24px 0 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .pricing-card__feature {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: var(--gray-700);
            line-height: 1.4;
        }
        .pricing-card__check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #ecfdf5;
            color: var(--green-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            flex-shrink: 0;
            margin-top: 1px;
            font-weight: 700;
        }

        .pricing-card__cta {
            display: block;
            width: 100%;
            padding: 14px;
            border-radius: var(--radius-sm);
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
        }
        .pricing-card__cta--primary {
            background: var(--purple-primary);
            color: #fff;
        }
        .pricing-card__cta--primary:hover {
            background: var(--purple-dark);
        }
        .pricing-card__cta--orange {
            background: var(--orange-cta);
            color: #fff;
        }
        .pricing-card__cta--orange:hover {
            background: var(--orange-hover);
        }
        .pricing-card__cta--outline {
            background: transparent;
            border: 2px solid var(--gray-200);
            color: var(--gray-800);
        }
        .pricing-card__cta--outline:hover {
            border-color: var(--purple-primary);
            color: var(--purple-primary);
        }

        /* ===== FAQ SECTION ===== */
        .faq-section {
            padding: 64px 0 80px;
            border-top: 1px solid var(--gray-200);
        }
        .faq-section__title {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            text-align: center;
            margin-bottom: 40px;
        }
        .faq-list {
            max-width: 760px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .faq-item {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .faq-item:hover {
            box-shadow: var(--shadow-sm);
        }
        .faq-item__question {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
            background: none;
            text-align: left;
            cursor: pointer;
            gap: 12px;
        }
        .faq-item__question:hover {
            color: var(--purple-primary);
        }
        .faq-item__icon {
            font-size: 18px;
            color: var(--gray-400);
            flex-shrink: 0;
            transition: transform 0.2s;
        }
        .faq-item.active .faq-item__icon {
            transform: rotate(180deg);
        }
        .faq-item__answer {
            display: none;
            padding: 0 24px 20px;
            font-size: 14px;
            color: var(--gray-600);
            line-height: 1.7;
        }
        .faq-item.active .faq-item__answer {
            display: block;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 420px;
                margin: 0 auto;
            }
            .pricing-card--featured {
                transform: none;
            }
            .pricing-card--featured:hover {
                transform: translateY(-4px);
            }
        }
        @media (max-width: 768px) {
            .page-hero--cennik {
                padding: 40px 0;
            }
            .page-hero--cennik h1 {
                font-size: 26px;
            }
            .pricing-section {
                padding: 40px 0;
            }
            .faq-section {
                padding: 48px 0 56px;
            }
            .faq-item__question {
                padding: 16px 20px;
                font-size: 14px;
            }
            .faq-item__answer {
                padding: 0 20px 16px;
                font-size: 13px;
            }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php pp_pro_header( '' ); ?>

<main>

    <!-- HERO -->
    <section class="page-hero--cennik">
        <div class="container">
            <nav class="breadcrumb">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona glowna</a>
                <span class="sep">/</span>
                <a href="<?php echo esc_url( home_url( '/dla-specjalistow/' ) ); ?>">Dla specjalistow</a>
                <span class="sep">/</span>
                <span>Cennik</span>
            </nav>
            <h1>Cennik</h1>
            <p class="subtitle">Przejrzyste zasady wspolpracy. Wybierz plan dopasowany do Twoich potrzeb i zacznij pozyskiwac klientow na Poradnik.pro.</p>
        </div>
    </section>

    <!-- PRICING CARDS -->
    <section class="pricing-section">
        <div class="container">
            <div class="pricing-grid">

                <!-- START (Free) -->
                <div class="pricing-card">
                    <div class="pricing-card__name">Start</div>
                    <div class="pricing-card__desc">Idealny na poczatek - poznaj platforme bez zobowiazan i sprawdz, jak dziala Poradnik.pro.</div>
                    <div class="pricing-card__price--free">0 zl<span style="font-size:14px;font-weight:400;color:var(--gray-500);"> /mies.</span></div>
                    <div class="pricing-card__features">
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Profil specjalisty w katalogu</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>5 odpowiedzi na pytania miesiecznie</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Podstawowa odznaka specjalisty</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Widocznosc w wynikach wyszukiwania</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Podstawowe statystyki profilu</span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url( home_url( '/rejestracja/' ) ); ?>" class="pricing-card__cta pricing-card__cta--outline">Zaloz darmowe konto</a>
                </div>

                <!-- PRO (Recommended) -->
                <div class="pricing-card pricing-card--featured">
                    <div class="pricing-badge">Polecany</div>
                    <div class="pricing-card__name">Pro</div>
                    <div class="pricing-card__desc">Dla aktywnych specjalistow, ktorzy chca skutecznie pozyskiwac nowych klientow.</div>
                    <div class="pricing-card__price"><span class="currency">zl </span>149<span class="period"> /mies.</span></div>
                    <div class="pricing-card__features">
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Rozszerzony profil z portfolio</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Nielimitowane odpowiedzi na pytania</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Priorytet w rankingach specjalistow</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Zaawansowana analityka i statystyki</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Odznaka &bdquo;Zweryfikowany Pro&rdquo;</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Publikacja artykulow eksperckich</span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url( home_url( '/rejestracja/?plan=pro' ) ); ?>" class="pricing-card__cta pricing-card__cta--primary">Wybierz plan Pro</a>
                </div>

                <!-- PREMIUM -->
                <div class="pricing-card">
                    <div class="pricing-card__name">Premium</div>
                    <div class="pricing-card__desc">Maksymalna ekspozycja i pelne wsparcie - dla specjalistow stawiajacych na rozwoj.</div>
                    <div class="pricing-card__price"><span class="currency">zl </span>399<span class="period"> /mies.</span></div>
                    <div class="pricing-card__features">
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Wszystko z planu Pro</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Wyroznione miejsce w katalogach</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Dedykowany opiekun konta</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Wlasny branding i personalizacja profilu</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Dostep do API integracyjnego</span>
                        </div>
                        <div class="pricing-card__feature">
                            <span class="pricing-card__check">&#10003;</span>
                            <span>Priorytetowe wsparcie 24/7</span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url( home_url( '/rejestracja/?plan=premium' ) ); ?>" class="pricing-card__cta pricing-card__cta--orange">Wybierz plan Premium</a>
                </div>

            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="faq-section">
        <div class="container">
            <h2 class="faq-section__title">Czesto zadawane pytania</h2>
            <div class="faq-list">

                <div class="faq-item active">
                    <button class="faq-item__question">
                        <span>Jak wygladaja platnosci i rozliczenia?</span>
                        <span class="faq-item__icon">&#9660;</span>
                    </button>
                    <div class="faq-item__answer">
                        Platnosci realizowane sa miesiecznie lub rocznie (z 20% rabatem). Akceptujemy przelewy bankowe, karty platnicze oraz BLIK. Faktura VAT generowana jest automatycznie po kazdej platnosci i dostepna w panelu specjalisty.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-item__question">
                        <span>Czy moge zrezygnowac w dowolnym momencie?</span>
                        <span class="faq-item__icon">&#9660;</span>
                    </button>
                    <div class="faq-item__answer">
                        Tak, mozesz anulowac subskrypcje w kazdej chwili bez dodatkowych kosztow. Po rezygnacji Twoje konto pozostanie aktywne do konca oplaconego okresu rozliczeniowego. Nie pobieramy zadnych oplat za wczesne rozwiazanie umowy.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-item__question">
                        <span>Czy moge zmienic plan w trakcie subskrypcji?</span>
                        <span class="faq-item__icon">&#9660;</span>
                    </button>
                    <div class="faq-item__answer">
                        Oczywiscie. Mozesz przejsc na wyzszy plan w dowolnym momencie - roznica w cenie zostanie proporcjonalnie przeliczona. Obnizenie planu wchodzi w zycie od nastepnego okresu rozliczeniowego.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-item__question">
                        <span>Czy jest darmowy okres probny?</span>
                        <span class="faq-item__icon">&#9660;</span>
                    </button>
                    <div class="faq-item__answer">
                        Plan Start jest calkowicie darmowy i nie wymaga podania karty platniczej. Dodatkowo, przy planach Pro i Premium oferujemy 14-dniowy okres probny, w trakcie ktorego mozesz przetestowac wszystkie funkcje bez zobowiazan.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-item__question">
                        <span>Jakie metody platnosci sa dostepne?</span>
                        <span class="faq-item__icon">&#9660;</span>
                    </button>
                    <div class="faq-item__answer">
                        Akceptujemy karty platnicze (Visa, Mastercard), szybkie przelewy bankowe, BLIK oraz platnosci za posrednictwem PayU. Dla klientow Premium dostepna jest rowniez mozliwosc platnosci na podstawie faktury proforma z 14-dniowym terminem.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-item__question">
                        <span>Czy otrzymam fakture VAT?</span>
                        <span class="faq-item__icon">&#9660;</span>
                    </button>
                    <div class="faq-item__answer">
                        Tak, faktura VAT wystawiana jest automatycznie po kazdej platnosci. Wszystkie faktury sa dostepne do pobrania w formacie PDF z poziomu panelu specjalisty w zakladce &bdquo;Rozliczenia&rdquo;. Mozesz tez ustawic automatyczne wysylanie na wskazany adres e-mail.
                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<?php pp_pro_footer(); ?>

<script>
(function() {
    var items = document.querySelectorAll('.faq-item');
    items.forEach(function(item) {
        var btn = item.querySelector('.faq-item__question');
        btn.addEventListener('click', function() {
            var isActive = item.classList.contains('active');
            items.forEach(function(el) { el.classList.remove('active'); });
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
