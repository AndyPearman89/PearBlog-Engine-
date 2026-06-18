<?php
/**
 * Template Name: Poradnik.PRO - Dla Specjalistow
 *
 * Landing page for specialists (Dla Specjalistow) on Poradnik.pro.
 * Showcases benefits, how-it-works process, testimonials, pricing preview,
 * and registration CTAs for professionals joining the platform.
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
        /* ===== HERO SECTION ===== */
        .specialist-hero {
            background: linear-gradient(135deg, #1a0a3e 0%, #6c2bd9 100%);
            padding: 80px 0 100px;
            position: relative;
            overflow: hidden;
        }
        .specialist-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(139,92,246,0.2) 0%, transparent 70%);
            border-radius: 50%;
        }
        .specialist-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(249,115,22,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 720px;
            margin: 0 auto;
        }
        .hero-content h1 {
            color: #fff;
            font-size: 42px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
        }
        .hero-content .hero-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 18px;
            line-height: 1.7;
            margin-bottom: 36px;
        }
        .btn-cta-hero {
            display: inline-block;
            background: var(--orange-cta);
            color: #fff;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 700;
            transition: background 0.2s, transform 0.2s;
        }
        .btn-cta-hero:hover {
            background: var(--orange-hover);
            transform: translateY(-2px);
        }
        .hero-note {
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            margin-top: 16px;
        }

        /* ===== STATS BAR ===== */
        .stats-bar {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 32px 48px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            max-width: 800px;
            margin: -50px auto 0;
            position: relative;
            z-index: 10;
            text-align: center;
        }
        .stat-item {}
        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--purple-primary);
        }
        .stat-label {
            font-size: 13px;
            color: var(--gray-500);
            margin-top: 4px;
        }

        /* ===== BENEFITS SECTION ===== */
        .benefits-section {
            padding: 80px 0 64px;
        }
        .section-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-900);
            text-align: center;
            margin-bottom: 16px;
        }
        .section-subtitle {
            font-size: 16px;
            color: var(--gray-500);
            text-align: center;
            margin-bottom: 48px;
            max-width: 560px;
            margin-left: auto;
            margin-right: auto;
        }
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .benefit-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 32px;
            text-align: center;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .benefit-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-4px);
        }
        .benefit-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 16px;
        }
        .benefit-icon.purple { background: #f3e8ff; }
        .benefit-icon.orange { background: #fff7ed; }
        .benefit-icon.green { background: #ecfdf5; }
        .benefit-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 10px;
        }
        .benefit-desc {
            font-size: 14px;
            color: var(--gray-500);
            line-height: 1.6;
        }

        /* ===== HOW IT WORKS ===== */
        .how-section {
            padding: 64px 0;
            background: var(--gray-50);
        }
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-top: 48px;
        }
        .step-card {
            text-align: center;
            position: relative;
        }
        .step-number {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
            margin: 0 auto 16px;
        }
        .step-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        .step-desc {
            font-size: 13px;
            color: var(--gray-500);
            line-height: 1.6;
        }
        .step-connector {
            display: none;
        }

        /* ===== TESTIMONIALS ===== */
        .testimonials-section {
            padding: 64px 0;
        }
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .testimonial-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 28px;
            transition: box-shadow 0.2s;
        }
        .testimonial-card:hover {
            box-shadow: var(--shadow-md);
        }
        .testimonial-stars {
            color: #f59e0b;
            font-size: 14px;
            margin-bottom: 12px;
            letter-spacing: 2px;
        }
        .testimonial-quote {
            font-size: 14px;
            color: var(--gray-700);
            line-height: 1.7;
            margin-bottom: 20px;
            font-style: italic;
        }
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .testimonial-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
        }
        .testimonial-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
        }
        .testimonial-role {
            font-size: 12px;
            color: var(--gray-500);
        }

        /* ===== PRICING PREVIEW ===== */
        .pricing-preview-section {
            padding: 64px 0;
            background: var(--gray-50);
        }
        .pricing-preview-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 48px;
            text-align: center;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: var(--shadow-sm);
        }
        .pricing-preview-card h3 {
            font-size: 22px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 12px;
        }
        .pricing-preview-card p {
            font-size: 15px;
            color: var(--gray-500);
            margin-bottom: 8px;
            line-height: 1.6;
        }
        .pricing-highlight {
            font-size: 36px;
            font-weight: 800;
            color: var(--purple-primary);
            margin: 24px 0 8px;
        }
        .pricing-highlight-note {
            font-size: 14px;
            color: var(--gray-500);
            margin-bottom: 28px;
        }
        .btn-pricing-link {
            display: inline-block;
            background: var(--purple-primary);
            color: #fff;
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-pricing-link:hover {
            background: var(--purple-dark);
        }

        /* ===== FINAL CTA ===== */
        .final-cta-section {
            padding: 64px 0 80px;
        }
        .final-cta-box {
            background: linear-gradient(135deg, #1a0a3e 0%, #6c2bd9 100%);
            border-radius: var(--radius-xl);
            padding: 64px 48px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .final-cta-box::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            border-radius: 50%;
        }
        .final-cta-box h2 {
            color: #fff;
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 16px;
            position: relative;
            z-index: 2;
        }
        .final-cta-box p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
            margin-bottom: 32px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            z-index: 2;
        }
        .final-cta-box .btn-cta-hero {
            position: relative;
            z-index: 2;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .steps-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 32px;
            }
        }
        @media (max-width: 768px) {
            .specialist-hero {
                padding: 56px 0 80px;
            }
            .hero-content h1 {
                font-size: 28px;
            }
            .hero-content .hero-subtitle {
                font-size: 15px;
            }
            .stats-bar {
                grid-template-columns: 1fr;
                padding: 24px;
                gap: 20px;
                margin-top: -40px;
            }
            .benefits-grid {
                grid-template-columns: 1fr;
            }
            .steps-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            .testimonials-grid {
                grid-template-columns: 1fr;
            }
            .pricing-preview-card {
                padding: 32px 24px;
            }
            .final-cta-box {
                padding: 40px 24px;
            }
            .final-cta-box h2 {
                font-size: 24px;
            }
            .section-title {
                font-size: 22px;
            }
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( '' ); ?>

<!-- HERO SECTION -->
<section class="specialist-hero">
    <div class="container">
        <div class="hero-content">
            <h1>Dolacz do sieci ekspertow Poradnik.pro</h1>
            <p class="hero-subtitle">Zbuduj swoja widocznosc online, pozyskuj nowych klientow i rozwijaj praktyke dzieki platformie, na ktorej tysiace uzytkownikow codziennie szuka specjalistow.</p>
            <a href="<?php echo esc_url( home_url( '/rejestracja-specjalisty/' ) ); ?>" class="btn-cta-hero">Zaloz profil za darmo</a>
            <p class="hero-note">Bez zobowiazan &bull; Rejestracja w 2 minuty &bull; Pierwszy miesiac gratis</p>
        </div>
    </div>
</section>

<!-- STATS BAR -->
<div class="container">
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-value">2,400+</div>
            <div class="stat-label">specjalistow na platformie</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">180,000+</div>
            <div class="stat-label">uzytkownikow miesiecznie</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">50,000+</div>
            <div class="stat-label">pytan od uzytkownikow</div>
        </div>
    </div>
</div>

<!-- BENEFITS SECTION -->
<section class="benefits-section">
    <div class="container">
        <h2 class="section-title">Dlaczego warto dolaczyc?</h2>
        <p class="section-subtitle">Poradnik.pro to platforma, ktora laczy ekspertow z osobami potrzebujacymi profesjonalnej pomocy</p>
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon purple">&#128200;</div>
                <h3 class="benefit-title">Wiekszy zasieg</h3>
                <p class="benefit-desc">Twoj profil widoczny dla tysiecy uzytkownikow szukajacych specjalistow w Twojej branze. Dotrzesz do klientow, ktorych nie znajdziesz nigdzie indziej.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon orange">&#11088;</div>
                <h3 class="benefit-title">Buduj reputacje</h3>
                <p class="benefit-desc">Zbieraj opinie, odpowiadaj na pytania i buduj wizerunek eksperta. Twoja aktywnosc przeksztaica sie w zaufanie klientow.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon green">&#128176;</div>
                <h3 class="benefit-title">Pozyskuj klientow</h3>
                <p class="benefit-desc">Otrzymuj bezposrednie zapytania od osob gotowych do wspolpracy. Konwertuj zainteresowanie w realne zlecenia.</p>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS SECTION -->
<section class="how-section">
    <div class="container">
        <h2 class="section-title">Jak to dziala?</h2>
        <p class="section-subtitle">Cztery proste kroki do Twoich nowych klientow</p>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3 class="step-title">Zaloz profil</h3>
                <p class="step-desc">Zarejestruj sie za darmo i utworz swoj profesjonalny profil specjalisty na platformie.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3 class="step-title">Uzupelnij dane</h3>
                <p class="step-desc">Dodaj swoje doswiadczenie, specjalizacje, certyfikaty i obszar dzialania.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3 class="step-title">Odpowiadaj na pytania</h3>
                <p class="step-desc">Pomagaj uzytkownikom, odpowiadajac na ich pytania. Buduj widocznosc i autorytet.</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h3 class="step-title">Zdobywaj klientow</h3>
                <p class="step-desc">Zainteresowani uzytkownicy kontaktuja sie z Toba bezposrednio. Rozwijaj swoja praktyke.</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS SECTION -->
<section class="testimonials-section">
    <div class="container">
        <h2 class="section-title">Co mowia nasi specjalisci?</h2>
        <p class="section-subtitle">Dolacz do grona zadowolonych ekspertow, ktorzy rozwijaja swoja dzialalnosc dzieki Poradnik.pro</p>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p class="testimonial-quote">&ldquo;Od kiedy jestem na Poradnik.pro, mam staly naplyw klientow. Nie musze juz szukac zlecen &mdash; to zlecenia znajduja mnie. Polecam kazdemu specjaliscie.&rdquo;</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">MK</div>
                    <div>
                        <div class="testimonial-name">Marek Kowalski</div>
                        <div class="testimonial-role">Radca prawny, Warszawa</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p class="testimonial-quote">&ldquo;Platforma generuje mi 15-20 zapytan miesiecznie. Konwersja na klientow to okolo 40%. Lepszy ROI niz jakiekolwiek reklamy w Google.&rdquo;</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">AN</div>
                    <div>
                        <div class="testimonial-name">Anna Nowak</div>
                        <div class="testimonial-role">Doradca finansowy, Krakow</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p class="testimonial-quote">&ldquo;Dzieki profilowi na Poradnik.pro zdobylem rozpoznawalnosc w swoim regionie. Klienci cenia sobie moje odpowiedzi i wracaja z konkretnymi zleceniami.&rdquo;</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">PW</div>
                    <div>
                        <div class="testimonial-name">Piotr Wisniewski</div>
                        <div class="testimonial-role">Architekt, Poznan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PRICING PREVIEW SECTION -->
<section class="pricing-preview-section">
    <div class="container">
        <h2 class="section-title">Prosty i przejrzysty cennik</h2>
        <p class="section-subtitle">Zacznij za darmo, rozwijaj sie w swoim tempie</p>
        <div class="pricing-preview-card">
            <h3>Darmowy start dla kazdego specjalisty</h3>
            <p>Zaloz profil, odpowiadaj na pytania i pozyskuj pierwszych klientow bez zadnych oplat. Gdy bedziesz gotowy na wiecej &mdash; wybierz plan Premium.</p>
            <div class="pricing-highlight">0 zl</div>
            <p class="pricing-highlight-note">Plan FREE &mdash; na zawsze, bez zobowiazan</p>
            <p style="font-size: 14px; color: var(--gray-500); margin-bottom: 28px;">Plany Premium juz od <strong style="color: var(--gray-900);">149 zl/mies.</strong> z pelnym dostepem do leadow i priorytetowa widocznoscia.</p>
            <a href="<?php echo esc_url( home_url( '/cennik/' ) ); ?>" class="btn-pricing-link">Zobacz pelny cennik</a>
        </div>
    </div>
</section>

<!-- FINAL CTA SECTION -->
<section class="final-cta-section">
    <div class="container">
        <div class="final-cta-box">
            <h2>Zacznij pozyskiwac klientow juz dzis</h2>
            <p>Dolacz do ponad 2,400 specjalistow, ktorzy rozwijaja swoja dzialalnosc dzieki Poradnik.pro. Rejestracja jest darmowa.</p>
            <a href="<?php echo esc_url( home_url( '/rejestracja-specjalisty/' ) ); ?>" class="btn-cta-hero">Zarejestruj sie za darmo</a>
        </div>
    </div>
</section>

<?php pp_pro_footer(); ?>

<?php wp_footer(); ?>
</body>
</html>
