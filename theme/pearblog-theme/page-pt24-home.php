<?php
/**
 * Template Name: PT24.PRO - Homepage
 *
 * Main homepage for PT24.pro local services platform
 *
 * @package PearBlog
 * @version 1.0.0
 */

wp_enqueue_style('pt24-landing', get_template_directory_uri() . '/assets/css/pt24-landing.css', array(), '1.0.0');
wp_enqueue_script('pt24-landing', get_template_directory_uri() . '/assets/js/pt24-landing.js', array(), '1.0.0', true);

get_header('minimal');
?>

<main class="pt24-homepage">
    <!-- HERO SECTION -->
    <section class="pt24-hero pt24-hero--home">
        <div class="pt24-container">
            <div class="pt24-hero__content">
                <h1 class="pt24-hero__title">
                    Znajdź fachowca w swojej okolicy. Szybko.
                </h1>

                <p class="pt24-hero__subtitle">
                    Auto się zepsuło? Cieknie rura? Nie ma prądu?<br>
                    Sprawdzeni specjaliści w Twoim mieście — bez pośredników, bez czekania.
                </p>

                <div class="pt24-hero__ctas">
                    <a href="#pt24-services" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                        🔍 Znajdź usługę
                    </a>
                    <a href="#pt24-business" class="pt24-btn pt24-btn--secondary pt24-btn--large pt24-smooth-scroll">
                        ➕ Dodaj firmę
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- SERVICES SECTION -->
    <section class="pt24-services" id="pt24-services">
        <div class="pt24-container">
            <h2 class="pt24-section-title">Wybierz usługę</h2>
            <p class="pt24-section-subtitle">Najczęściej szukane kategorie</p>

            <div class="pt24-services-grid">
                <a href="/mechanik/" class="pt24-service-card">
                    <div class="pt24-service-card__icon">🔧</div>
                    <h3 class="pt24-service-card__title">Mechanik samochodowy</h3>
                    <p class="pt24-service-card__description">Diagnostyka, naprawy, mobilny serwis</p>
                    <span class="pt24-service-card__arrow">→</span>
                </a>

                <a href="/hydraulik/" class="pt24-service-card">
                    <div class="pt24-service-card__icon">🚰</div>
                    <h3 class="pt24-service-card__title">Hydraulik</h3>
                    <p class="pt24-service-card__description">Awarie, remonty, instalacje</p>
                    <span class="pt24-service-card__arrow">→</span>
                </a>

                <a href="/elektryk/" class="pt24-service-card">
                    <div class="pt24-service-card__icon">⚡</div>
                    <h3 class="pt24-service-card__title">Elektryk samochodowy</h3>
                    <p class="pt24-service-card__description">Elektryka, stacyjki, alarmy</p>
                    <span class="pt24-service-card__arrow">→</span>
                </a>

                <a href="/laweta/" class="pt24-service-card">
                    <div class="pt24-service-card__icon">🚗</div>
                    <h3 class="pt24-service-card__title">Laweta</h3>
                    <p class="pt24-service-card__description">Pomoc drogowa 24h</p>
                    <span class="pt24-service-card__arrow">→</span>
                </a>

                <a href="/wulkanizacja/" class="pt24-service-card">
                    <div class="pt24-service-card__icon">🛞</div>
                    <h3 class="pt24-service-card__title">Wulkanizacja</h3>
                    <p class="pt24-service-card__description">Opony, felgi, sezonowe</p>
                    <span class="pt24-service-card__arrow">→</span>
                </a>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS SECTION -->
    <section class="pt24-how-it-works">
        <div class="pt24-container">
            <h2 class="pt24-section-title">Trzy kroki do znalezienia fachowca</h2>

            <div class="pt24-steps">
                <div class="pt24-step">
                    <div class="pt24-step__number">1</div>
                    <h3 class="pt24-step__title">Wybierz usługę</h3>
                    <p class="pt24-step__description">mechanik, hydraulik, elektryk...</p>
                </div>

                <div class="pt24-step">
                    <div class="pt24-step__number">2</div>
                    <h3 class="pt24-step__title">Wybierz miasto</h3>
                    <p class="pt24-step__description">znajdujemy fachowców w Twojej okolicy</p>
                </div>

                <div class="pt24-step">
                    <div class="pt24-step__number">3</div>
                    <h3 class="pt24-step__title">Zadzwoń</h3>
                    <p class="pt24-step__description">kontakt bezpośredni, bez formularzy</p>
                </div>
            </div>

            <p class="pt24-how-it-works__note">
                Bez rejestracji. Bez prowizji. Bez tracenia czasu.
            </p>
        </div>
    </section>

    <!-- FOR BUSINESSES SECTION -->
    <section class="pt24-business" id="pt24-business">
        <div class="pt24-container">
            <div class="pt24-business-content">
                <h2 class="pt24-section-title">Dodaj firmę i zdobywaj klientów z Google</h2>

                <div class="pt24-business-benefits">
                    <div class="pt24-benefit">
                        <svg class="pt24-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Własny profil z opisem i kontaktem</span>
                    </div>
                    <div class="pt24-benefit">
                        <svg class="pt24-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Widoczność w wyszukiwaniu lokalnym</span>
                    </div>
                    <div class="pt24-benefit">
                        <svg class="pt24-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Telefony od klientów z Twojego miasta</span>
                    </div>
                    <div class="pt24-benefit">
                        <svg class="pt24-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Działa 24/7, nawet gdy Ty śpisz</span>
                    </div>
                </div>

                <div class="pt24-business-cta">
                    <h3>Dlaczego warto:</h3>
                    <p>Klienci szukają Cię w Google → trafiają na pt24.pro → dzwonią do Ciebie.</p>
                    <p>Płacisz tylko za widoczność, nie za każdy kontakt.</p>
                    <p>Bez pośredników — klient dzwoni bezpośrednio.</p>

                    <a href="/dodaj-firme/" class="pt24-btn pt24-btn--accent pt24-btn--large">
                        ➕ Dodaj swoją firmę — 14 dni za darmo
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- POPULAR CITIES SECTION -->
    <section class="pt24-cities">
        <div class="pt24-container">
            <h2 class="pt24-section-title">Popularne miasta</h2>

            <div class="pt24-cities-grid">
                <?php
                $cities = [
                    'warszawa' => 'Warszawa',
                    'krakow' => 'Kraków',
                    'wroclaw' => 'Wrocław',
                    'katowice' => 'Katowice',
                    'gdansk' => 'Gdańsk',
                    'poznan' => 'Poznań',
                    'lodz' => 'Łódź',
                    'ruda-slaska' => 'Ruda Śląska',
                    'zabrze' => 'Zabrze',
                    'gliwice' => 'Gliwice',
                    'bytom' => 'Bytom',
                    'sosnowiec' => 'Sosnowiec',
                ];

                foreach ($cities as $slug => $name) {
                    echo '<a href="/mechanik/' . esc_attr($slug) . '/" class="pt24-city-link">' . esc_html($name) . '</a>';
                }
                ?>
            </div>

            <p class="pt24-cities-more">
                <a href="/miasta/">Wszystkie miasta →</a>
            </p>
        </div>
    </section>

    <!-- FINAL CTA SECTION -->
    <section class="pt24-final-cta pt24-final-cta--home">
        <div class="pt24-container">
            <div class="pt24-final-cta__content">
                <h2 class="pt24-final-cta__title">Masz problem? Znajdź fachowca w 2 minuty.</h2>

                <div class="pt24-final-cta__buttons">
                    <a href="#pt24-services" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                        🔍 Szukam usługi
                    </a>
                    <a href="/dodaj-firme/" class="pt24-btn pt24-btn--secondary pt24-btn--large">
                        ➕ Dodaję firmę
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer('minimal'); ?>
