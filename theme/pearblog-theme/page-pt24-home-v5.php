<?php
/**
 * Template Name: PT24.PRO - Homepage V5
 *
 * V5 streamlined conversion-focused homepage for PT24.pro
 * Simplified layout based on wireframe specification
 *
 * @package PearBlog
 * @version 5.0.0
 */

wp_enqueue_style('pt24-home-v5', get_template_directory_uri() . '/assets/css/pt24-home-v5.css', array(), '5.0.0');
wp_enqueue_script('pt24-landing', get_template_directory_uri() . '/assets/js/pt24-landing.js', array(), '5.0.0', true);
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('pt24-home-v5'); ?>>

<!-- ================================================== -->
<!-- HEADER -->
<!-- ================================================== -->
<header class="pt24-v5-header">
    <div class="pt24-v5-container">
        <div class="pt24-v5-header__inner">
            <div class="pt24-v5-logo">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <strong>PT24.PRO</strong>
                </a>
            </div>

            <nav class="pt24-v5-nav">
                <a href="#uslugi" class="pt24-v5-nav__link">Usługi</a>
                <a href="#miasta" class="pt24-v5-nav__link">Miasta</a>
                <a href="/firmy/" class="pt24-v5-nav__link">Firmy</a>
                <a href="/dodaj-firme/" class="pt24-v5-nav__link pt24-v5-nav__link--cta">Dodaj firmę</a>
                <a href="/kontakt/" class="pt24-v5-nav__link">Kontakt</a>
            </nav>

            <div class="pt24-v5-header__contact">
                <a href="tel:+48123456789" class="pt24-v5-phone">
                    📞 Telefon
                </a>
            </div>
        </div>
    </div>
</header>

<!-- ================================================== -->
<!-- HERO SECTION -->
<!-- ================================================== -->
<section class="pt24-v5-hero">
    <div class="pt24-v5-container">
        <div class="pt24-v5-hero__content">
            <h1 class="pt24-v5-hero__title">
                Znajdź sprawdzonego fachowca w swojej okolicy
            </h1>

            <p class="pt24-v5-hero__text">
                Mechanik, hydraulik, elektryk i inne usługi – szybki kontakt, lokalni specjaliści.
            </p>

            <div class="pt24-v5-hero__actions">
                <a href="#uslugi" class="pt24-v5-btn pt24-v5-btn--primary">
                    Znajdź usługę
                </a>
                <a href="/dodaj-firme/" class="pt24-v5-btn pt24-v5-btn--secondary">
                    Dodaj firmę
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ================================================== -->
<!-- KATEGORIE - GRID 5x -->
<!-- ================================================== -->
<section id="uslugi" class="pt24-v5-services">
    <div class="pt24-v5-container">
        <div class="pt24-v5-services__grid">
            <a href="/mechanik/" class="pt24-v5-service-card">
                <div class="pt24-v5-service-card__icon">🔧</div>
                <h3 class="pt24-v5-service-card__title">Mechanik</h3>
            </a>

            <a href="/hydraulik/" class="pt24-v5-service-card">
                <div class="pt24-v5-service-card__icon">🚰</div>
                <h3 class="pt24-v5-service-card__title">Hydraulik</h3>
            </a>

            <a href="/elektryk/" class="pt24-v5-service-card">
                <div class="pt24-v5-service-card__icon">⚡</div>
                <h3 class="pt24-v5-service-card__title">Elektryk</h3>
            </a>

            <a href="/laweta/" class="pt24-v5-service-card">
                <div class="pt24-v5-service-card__icon">🚗</div>
                <h3 class="pt24-v5-service-card__title">Laweta</h3>
            </a>

            <a href="/wulkanizacja/" class="pt24-v5-service-card">
                <div class="pt24-v5-service-card__icon">🛞</div>
                <h3 class="pt24-v5-service-card__title">Wulkanizacja</h3>
            </a>
        </div>
    </div>
</section>

<!-- ================================================== -->
<!-- JAK TO DZIAŁA - 3 KROKI -->
<!-- ================================================== -->
<section class="pt24-v5-how-it-works">
    <div class="pt24-v5-container">
        <h2 class="pt24-v5-section__title">Jak to działa</h2>

        <div class="pt24-v5-steps">
            <div class="pt24-v5-step">
                <div class="pt24-v5-step__number">1</div>
                <h3 class="pt24-v5-step__title">Wybierz usługę</h3>
            </div>

            <div class="pt24-v5-step">
                <div class="pt24-v5-step__number">2</div>
                <h3 class="pt24-v5-step__title">Wybierz miasto</h3>
            </div>

            <div class="pt24-v5-step">
                <div class="pt24-v5-step__number">3</div>
                <h3 class="pt24-v5-step__title">Zadzwoń do fachowca</h3>
            </div>
        </div>
    </div>
</section>

<!-- ================================================== -->
<!-- CTA BAR -->
<!-- ================================================== -->
<section class="pt24-v5-cta-bar">
    <div class="pt24-v5-container">
        <div class="pt24-v5-cta-bar__inner">
            <p class="pt24-v5-cta-bar__text">
                Masz problem? Znajdź fachowca w 2 minuty
            </p>
            <a href="tel:+48123456789" class="pt24-v5-btn pt24-v5-btn--cta">
                Zadzwoń teraz
            </a>
        </div>
    </div>
</section>

<!-- ================================================== -->
<!-- POPULARNE MIASTA -->
<!-- ================================================== -->
<section id="miasta" class="pt24-v5-cities">
    <div class="pt24-v5-container">
        <h2 class="pt24-v5-section__title">Popularne miasta</h2>

        <div class="pt24-v5-cities__list">
            <a href="/ruda-slaska/" class="pt24-v5-city-link">Ruda Śląska</a>
            <a href="/katowice/" class="pt24-v5-city-link">Katowice</a>
            <a href="/krakow/" class="pt24-v5-city-link">Kraków</a>
            <a href="/wroclaw/" class="pt24-v5-city-link">Wrocław</a>
            <a href="/warszawa/" class="pt24-v5-city-link">Warszawa</a>
        </div>
    </div>
</section>

<!-- ================================================== -->
<!-- DLACZEGO MY -->
<!-- ================================================== -->
<section class="pt24-v5-why-us">
    <div class="pt24-v5-container">
        <div class="pt24-v5-benefits">
            <div class="pt24-v5-benefit">
                <div class="pt24-v5-benefit__icon">📍</div>
                <h3 class="pt24-v5-benefit__title">Lokalni fachowcy</h3>
            </div>

            <div class="pt24-v5-benefit">
                <div class="pt24-v5-benefit__icon">⚡</div>
                <h3 class="pt24-v5-benefit__title">Szybki kontakt</h3>
            </div>

            <div class="pt24-v5-benefit">
                <div class="pt24-v5-benefit__icon">🤝</div>
                <h3 class="pt24-v5-benefit__title">Brak pośredników</h3>
            </div>

            <div class="pt24-v5-benefit">
                <div class="pt24-v5-benefit__icon">🕒</div>
                <h3 class="pt24-v5-benefit__title">Dostępność 24/7</h3>
            </div>
        </div>
    </div>
</section>

<!-- ================================================== -->
<!-- DLA FIRM -->
<!-- ================================================== -->
<section class="pt24-v5-for-business">
    <div class="pt24-v5-container">
        <div class="pt24-v5-for-business__content">
            <h2 class="pt24-v5-for-business__title">
                Dodaj swoją firmę i zdobywaj klientów
            </h2>

            <ul class="pt24-v5-for-business__features">
                <li>✓ Własny profil</li>
                <li>✓ Widoczność w Google</li>
                <li>✓ Leady lokalne</li>
            </ul>

            <a href="/dodaj-firme/" class="pt24-v5-btn pt24-v5-btn--primary">
                Dodaj firmę
            </a>
        </div>
    </div>
</section>

<!-- ================================================== -->
<!-- FOOTER -->
<!-- ================================================== -->
<footer class="pt24-v5-footer">
    <div class="pt24-v5-container">
        <div class="pt24-v5-footer__nav">
            <a href="/uslugi/">Usługi</a>
            <a href="/miasta/">Miasta</a>
            <a href="/firmy/">Firmy</a>
            <a href="/regulamin/">Regulamin</a>
            <a href="/kontakt/">Kontakt</a>
        </div>

        <div class="pt24-v5-footer__contact">
            <a href="tel:+48123456789">📞 Telefon</a>
            <a href="mailto:kontakt@pt24.pro">✉️ Email</a>
        </div>

        <div class="pt24-v5-footer__copyright">
            <p>&copy; <?php echo date('Y'); ?> PT24.PRO - Wszystkie prawa zastrzeżone</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
