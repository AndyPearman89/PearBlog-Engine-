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
     * Render the PT24.PRO footer (services, cities, info, contact).
     */
    function pt24_render_full_footer(): void {
        $home      = home_url( '/' );
        $site_name = get_bloginfo( 'name' );
        $site_desc = get_bloginfo( 'description' );
        $year      = gmdate( 'Y' );

        // Flagship city/service used to build valid landing links.
        $services = array(
            'hydraulik'       => 'Hydraulik',
            'elektryk'        => 'Elektryk',
            'mechanik'        => 'Mechanik',
            'pompa-ciepla'    => 'Pompa ciepła',
            'remont-lazienki' => 'Remont łazienki',
            'fotowoltaika'    => 'Fotowoltaika',
        );
        $cities = array(
            'warszawa' => 'Warszawa',
            'krakow'   => 'Kraków',
            'wroclaw'  => 'Wrocław',
            'poznan'   => 'Poznań',
            'gdansk'   => 'Gdańsk',
            'katowice' => 'Katowice',
        );
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
                            <?php echo $site_desc ? esc_html( $site_desc ) : 'PT24.PRO łączy Cię ze sprawdzonymi, lokalnymi fachowcami. Opisz zlecenie raz i odbieraj oferty od specjalistów z Twojej okolicy.'; ?>
                        </p>
                        <p class="pt24-footer__contact">
                            <span class="pt24-ico pt24-ico--tag" aria-hidden="true"></span>
                            <a href="mailto:kontakt@pt24.pro">kontakt@pt24.pro</a>
                        </p>
                    </div>

                    <!-- Usługi -->
                    <div class="pp-footer-col">
                        <h3 class="pp-footer-heading">Usługi</h3>
                        <ul class="pp-footer-links">
                            <?php foreach ( $services as $slug => $label ) : ?>
                                <li><a href="<?php echo esc_url( $home . 'warszawa/' . $slug . '/' ); ?>"><?php echo esc_html( $label ); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Miasta -->
                    <div class="pp-footer-col">
                        <h3 class="pp-footer-heading">Miasta</h3>
                        <ul class="pp-footer-links">
                            <?php foreach ( $cities as $slug => $label ) : ?>
                                <li><a href="<?php echo esc_url( $home . $slug . '/hydraulik/' ); ?>"><?php echo esc_html( $label ); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Informacje -->
                    <div class="pp-footer-col">
                        <h3 class="pp-footer-heading">Informacje</h3>
                        <ul class="pp-footer-links">
                            <li><a href="<?php echo esc_url( $home . 'jak-to-dziala/' ); ?>">Jak to działa</a></li>
                            <li><a href="<?php echo esc_url( $home . 'dla-firm/' ); ?>">Dla firm</a></li>
                            <li><a href="<?php echo esc_url( $home . 'o-nas/' ); ?>">O nas</a></li>
                            <li><a href="<?php echo esc_url( $home . 'kontakt/' ); ?>">Kontakt</a></li>
                            <li><a href="<?php echo esc_url( $home . 'polityka-prywatnosci/' ); ?>">Polityka prywatności</a></li>
                            <li><a href="<?php echo esc_url( $home . 'regulamin/' ); ?>">Regulamin</a></li>
                        </ul>
                    </div>

                </div>

                <!-- Popularne kombinacje (SEO) -->
                <div class="pp-footer-cities">
                    <h3 class="pp-footer-heading">Popularne usługi w miastach</h3>
                    <div class="pp-footer-city-links">
                        <a href="<?php echo esc_url( $home . 'warszawa/hydraulik/' ); ?>">Hydraulik Warszawa</a>
                        <a href="<?php echo esc_url( $home . 'krakow/elektryk/' ); ?>">Elektryk Kraków</a>
                        <a href="<?php echo esc_url( $home . 'wroclaw/mechanik/' ); ?>">Mechanik Wrocław</a>
                        <a href="<?php echo esc_url( $home . 'poznan/pompa-ciepla/' ); ?>">Pompa ciepła Poznań</a>
                        <a href="<?php echo esc_url( $home . 'gdansk/fotowoltaika/' ); ?>">Fotowoltaika Gdańsk</a>
                        <a href="<?php echo esc_url( $home . 'katowice/remont-lazienki/' ); ?>">Remont łazienki Katowice</a>
                    </div>
                </div>

                <!-- Bottom -->
                <div class="pp-footer-bottom">
                    <p class="pp-footer-copyright">
                        &copy; <?php echo esc_html( $year ); ?>
                        <a href="<?php echo esc_url( $home ); ?>"><?php echo esc_html( $site_name ?: 'PT24.PRO' ); ?></a>
                        — Wszelkie prawa zastrzeżone.
                    </p>
                    <p class="pp-footer-powered">
                        Powered by <a href="https://pearblog.pro" target="_blank" rel="noopener">PearBlog Engine</a>
                    </p>
                </div>

            </div>
        </footer>
        <?php
    }
endif;
