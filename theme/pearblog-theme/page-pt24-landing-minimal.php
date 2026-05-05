<?php
/**
 * Template Name: PT24 Landing - Minimal CTA
 *
 * Ultra-minimal, conversion-focused landing page for local services
 * Aggressive direct-response style with maximum phone CTA visibility
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Get dynamic data
$city = get_post_meta(get_the_ID(), 'pt24_city', true) ?: '[MIASTO]';
$service = get_post_meta(get_the_ID(), 'pt24_service_name', true) ?: 'Mechanik samochodowy';
$phone = get_option('pt24_phone_number', '+48 123 456 789');

wp_enqueue_style('pt24-landing-minimal', get_template_directory_uri() . '/assets/css/pt24-landing-minimal.css', array(), '1.0.0');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($service . ' ' . $city); ?> | PT24.PRO</title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('pt24-landing-minimal'); ?>>

<!-- ================================================== -->
<!-- MINIMAL HEADER -->
<!-- ================================================== -->
<header class="pt24-mini-header">
    <div class="pt24-mini-container">
        <div class="pt24-mini-header__inner">
            <div class="pt24-mini-logo">
                <strong>PT24.PRO</strong>
            </div>
            <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>" class="pt24-mini-phone">
                ☎ <?php echo esc_html($phone); ?>
            </a>
        </div>
    </div>
</header>

<!-- ================================================== -->
<!-- HERO -->
<!-- ================================================== -->
<section class="pt24-mini-hero">
    <div class="pt24-mini-container">
        <h1 class="pt24-mini-hero__title">
            <?php echo esc_html($service . ' ' . $city); ?> – szybka pomoc
        </h1>

        <p class="pt24-mini-hero__text">
            Auto nie odpala? Problem z silnikiem?<br>
            Zadzwoń i ogarnij to jeszcze dziś.
        </p>

        <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>" class="pt24-mini-btn pt24-mini-btn--primary">
            ☎ Zadzwoń teraz
        </a>
    </div>
</section>

<!-- ================================================== -->
<!-- PROBLEM -->
<!-- ================================================== -->
<section class="pt24-mini-problem">
    <div class="pt24-mini-container">
        <ul class="pt24-mini-problem__list">
            <li>❌ Auto nie odpala</li>
            <li>❌ Check engine</li>
            <li>❌ Spadek mocy</li>
            <li>❌ Brak czasu na szukanie</li>
        </ul>

        <p class="pt24-mini-problem__solution">
            <strong>👉 Rozwiążemy to szybko</strong>
        </p>
    </div>
</section>

<!-- ================================================== -->
<!-- USŁUGI -->
<!-- ================================================== -->
<section class="pt24-mini-services">
    <div class="pt24-mini-container">
        <ul class="pt24-mini-services__list">
            <li>✔ Diagnostyka komputerowa</li>
            <li>✔ Naprawa silnika</li>
            <li>✔ Elektryk samochodowy</li>
            <li>✔ Mobilny mechanik</li>
            <li>✔ Pomoc drogowa</li>
        </ul>
    </div>
</section>

<!-- ================================================== -->
<!-- CTA BAR -->
<!-- ================================================== -->
<section class="pt24-mini-cta-bar">
    <div class="pt24-mini-container">
        <p class="pt24-mini-cta-bar__text">
            Nie czekaj – problem sam nie zniknie
        </p>
        <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>" class="pt24-mini-btn pt24-mini-btn--cta">
            ☎ <?php echo esc_html($phone); ?>
        </a>
    </div>
</section>

<!-- ================================================== -->
<!-- ZAUFANIE -->
<!-- ================================================== -->
<section class="pt24-mini-trust">
    <div class="pt24-mini-container">
        <ul class="pt24-mini-trust__list">
            <li>✔ Lokalny mechanik z <?php echo esc_html($city); ?></li>
            <li>✔ Szybki dojazd</li>
            <li>✔ Uczciwe ceny</li>
            <li>✔ Dostępność 24/7</li>
        </ul>
    </div>
</section>

<!-- ================================================== -->
<!-- FINAL CTA -->
<!-- ================================================== -->
<section class="pt24-mini-final-cta">
    <div class="pt24-mini-container">
        <h2 class="pt24-mini-final-cta__title">
            Zadzwoń teraz i miej to z głowy
        </h2>

        <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>" class="pt24-mini-btn pt24-mini-btn--final">
            ☎ <?php echo esc_html($phone); ?>
        </a>

        <p class="pt24-mini-final-cta__note">
            Dostępni 24/7 · Szybki dojazd · <?php echo esc_html($city); ?>
        </p>
    </div>
</section>

<!-- ================================================== -->
<!-- MINIMAL FOOTER -->
<!-- ================================================== -->
<footer class="pt24-mini-footer">
    <div class="pt24-mini-container">
        <p>&copy; <?php echo date('Y'); ?> PT24.PRO | <a href="/kontakt/">Kontakt</a> | <a href="/regulamin/">Regulamin</a></p>
    </div>
</footer>

<?php wp_footer(); ?>

<!-- Phone Click Tracking -->
<script>
document.querySelectorAll('a[href^="tel:"]').forEach(function(link) {
    link.addEventListener('click', function() {
        if (typeof gtag !== 'undefined') {
            gtag('event', 'phone_click', {
                'event_category': 'conversion',
                'event_label': '<?php echo esc_js($service . ' ' . $city); ?>'
            });
        }
    });
});
</script>

</body>
</html>
