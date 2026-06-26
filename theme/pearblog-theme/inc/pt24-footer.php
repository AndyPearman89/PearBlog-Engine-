<?php
/**
 * PT24.PRO — dedicated site footer.
 *
 * The theme is SHARED with poradnik.pro, whose pp_render_full_footer() outputs
 * poradnik-specific columns (prawo/finanse categories, /specjalisci/ cities,
 * PoradnikPRO social links). On the PT24 install that footer is wrong, so this
 * file provides pt24_render_full_footer() with PT24 branding and valid links
 * (real service x city landings + the seeded static pages). It reuses the
 * existing .pp-footer markup/classes so poradnik-pro-navigation.css styles it
 * with zero additional CSS.
 *
 * This file is required ONLY on the PT24 install (host-guarded in functions.php),
 * so the function never exists on poradnik.pro and that footer is untouched.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'pt24_render_full_footer' ) ) :
/**
 * Render the PT24.PRO footer (Dla klientów, Dla firm, Informacje, Kontakt).
 */
function pt24_render_full_footer(): void {
    $home      = home_url( '/' );
    $site_name = get_bloginfo( 'name' );
    $year      = gmdate( 'Y' );
    ?>
    <footer class="pp-footer pt24-footer" role="contentinfo">
        <div class="pb-container">

            <div class="pp-footer-grid">

                <!-- Brand -->
                <div class="pp-footer-col pp-footer-brand">
                    <a href="<?php echo esc_url( $home ); ?>" class="pp-footer-logo" rel="home">
                        <?php echo pearblog_get_logo(); ?>
                    </a>
                    <p class="pp-footer-desc">
                        Łączymy klientów z najlepszymi fachowcami w Polsce. Szybko, bezpiecznie i bez zobowiązań.
                    </p>
                    <!-- Social icons -->
                    <div class="pt24-footer__social" aria-label="Media społecznościowe">
                        <a href="https://facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="PT24.PRO na Facebook">f</a>
                        <a href="https://instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="PT24.PRO na Instagram">in</a>
                        <a href="https://youtube.com/" target="_blank" rel="noopener noreferrer" aria-label="YouTube" title="PT24.PRO na YouTube">yt</a>
                        <a href="https://linkedin.com/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" title="PT24.PRO na LinkedIn">li</a>
                    </div>
                </div>

                <!-- Dla klientów -->
                <div class="pp-footer-col">
                    <h3 class="pp-footer-heading">Dla klientów</h3>
                    <ul class="pp-footer-links">
                        <li><a href="<?php echo esc_url( $home . 'jak-to-dziala/' ); ?>">Jak to działa</a></li>
                        <li><a href="<?php echo esc_url( $home . 'dodaj-zapytanie/' ); ?>">Dodaj zapytanie</a></li>
                        <li><a href="<?php echo esc_url( $home . 'szukaj/' ); ?>">Znajdź fachowca</a></li>
                        <li><a href="<?php echo esc_url( $home . 'miasto/' ); ?>">Miasta</a></li>
                        <li><a href="<?php echo esc_url( $home . 'faq/' ); ?>">Najczęstsze pytania</a></li>
                        <li><a href="<?php echo esc_url( $home . 'kontakt/' ); ?>">Kontakt</a></li>
                    </ul>
                </div>

                <!-- Dla firm -->
                <div class="pp-footer-col">
                    <h3 class="pp-footer-heading">Dla firm</h3>
                    <ul class="pp-footer-links">
                        <li><a href="<?php echo esc_url( $home . 'dodaj-firme/' ); ?>">Zarejestruj firmę</a></li>
                        <li><a href="<?php echo esc_url( $home . 'rankingi/' ); ?>">Rankingi fachowców</a></li>
                        <li><a href="<?php echo esc_url( $home . 'uslugi/' ); ?>">Wszystkie usługi</a></li>
                        <li><a href="<?php echo esc_url( $home . 'dla-firm/' ); ?>">Pakiety i cennik</a></li>
                        <li><a href="<?php echo esc_url( $home . 'jak-to-dziala-dla-firm/' ); ?>">Jak działa dla firm</a></li>
                        <li><a href="<?php echo esc_url( $home . 'regulamin-firm/' ); ?>">Regulamin firm</a></li>
                        <li><a href="<?php echo esc_url( $home . 'blog/' ); ?>">Baza wiedzy</a></li>
                    </ul>
                </div>

                <!-- Informacje -->
                <div class="pp-footer-col">
                    <h3 class="pp-footer-heading">Informacje</h3>
                    <ul class="pp-footer-links">
                        <li><a href="<?php echo esc_url( $home . 'o-nas/' ); ?>">O nas</a></li>
                        <li><a href="<?php echo esc_url( $home . 'rankingi/' ); ?>">Rankingi</a></li>
                        <li><a href="<?php echo esc_url( $home . 'uslugi/' ); ?>">Usługi</a></li>
                        <li><a href="<?php echo esc_url( $home . 'regulamin/' ); ?>">Regulamin</a></li>
                        <li><a href="<?php echo esc_url( $home . 'polityka-prywatnosci/' ); ?>">Polityka prywatności</a></li>
                        <li><a href="<?php echo esc_url( $home . 'rodo/' ); ?>">RODO</a></li>
                        <li><a href="<?php echo esc_url( $home . 'blog/' ); ?>">Blog</a></li>
                    </ul>
                </div>

                <!-- Kontakt -->
                <div class="pp-footer-col">
                    <h3 class="pp-footer-heading">Kontakt</h3>
                    <ul class="pp-footer-links pt24-footer__contact-list">
                        <li><span class="pt24-ico pt24-ico--tag" aria-hidden="true"></span> <a href="tel:+48123456789">+48 123 456 789</a></li>
                        <li><span class="pt24-ico pt24-ico--tag" aria-hidden="true"></span> <a href="mailto:kontakt@pt24.pro">kontakt@pt24.pro</a></li>
                        <li><span class="pt24-ico pt24-ico--pin" aria-hidden="true"></span> Cała Polska</li>
                    </ul>
                </div>

            </div>

            <!-- Bottom -->
            <div class="pp-footer-bottom">
                <p class="pp-footer-copyright">
                    &copy; <?php echo esc_html( $year ); ?>
                    <strong><a href="<?php echo esc_url( $home ); ?>">PT24.pro</a></strong>
                    – Portal firm i leadów. Wszelkie prawa zastrzeżone.
                </p>
                <p class="pp-footer-badges">
                    <span class="pt24-footer__badge"><span class="pt24-ico pt24-ico--lock" aria-hidden="true"></span> SSL Bezpieczne dane</span>
                    <span class="pt24-footer__badge"><span class="pt24-ico pt24-ico--shield" aria-hidden="true"></span> Bezpieczne płatności</span>
                </p>
            </div>

        </div>
    </footer>

        <!-- Schema.org Organization markup -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": <?php echo wp_json_encode( $site_name ?: 'PT24.PRO' ); ?>,
            "url": <?php echo wp_json_encode( home_url( '/' ) ); ?>,
            "logo": <?php echo wp_json_encode( home_url( '/wp-content/themes/pearblog-theme/assets/brand/pt24-og.png' ) ); ?>,
            "description": "PT24.PRO łączy klientów ze sprawdzonymi fachowcami: hydraulik, elektryk, mechanik, fotowoltaika i inne usługi w całej Polsce.",
            "contactPoint": {
                "@type": "ContactPoint",
                "email": "kontakt@pt24.pro",
                "contactType": "customer service"
            },
            "areaServed": "PL",
            "foundingDate": "2026"
        }
        </script>
        <?php
    }
endif;
