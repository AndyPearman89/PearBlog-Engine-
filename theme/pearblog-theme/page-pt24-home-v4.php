<?php
/**
 * Template Name: PT24.PRO - Homepage V4
 *
 * V4 high-conversion homepage for PT24.pro local services platform
 * Modern purple gradient design with optimized conversion flow
 *
 * @package PearBlog
 * @version 4.0.0
 */

wp_enqueue_style('pt24-home-v4', get_template_directory_uri() . '/assets/css/pt24-home-v4.css', array(), '4.0.0');
wp_enqueue_script('pt24-landing', get_template_directory_uri() . '/assets/js/pt24-landing.js', array(), '4.0.0', true);

get_header('minimal');
?>

<main class="pt24-home-v4">
    <!-- ================================================== -->
    <!-- [1] HERO SECTION - Purple Gradient -->
    <!-- ================================================== -->
    <section class="pt24-v4-hero">
        <div class="pt24-container">
            <div class="pt24-v4-hero__content">
                <h1 class="pt24-v4-hero__title">
                    Znajdź fachowca w swojej okolicy. Szybko.
                </h1>

                <p class="pt24-v4-hero__subtitle">
                    Auto się zepsuło? Cieknie rura? Nie ma prądu?<br>
                    Sprawdzeni specjaliści w Twoim mieście — bez pośredników, bez czekania.
                </p>

                <!-- Search Bar -->
                <div class="pt24-v4-hero__search">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <input
                            type="search"
                            name="s"
                            class="pt24-v4-search-input"
                            placeholder="Wpisz usługę i miasto (np. „mechanik Warszawa")"
                            value="<?php echo get_search_query(); ?>"
                            autocomplete="off"
                        >
                        <button type="submit" class="pt24-v4-search-btn" aria-label="Szukaj">
                            🔍 Szukaj
                        </button>
                    </form>
                </div>

                <!-- Trust Signals -->
                <div class="pt24-v4-hero__trust">
                    <div class="pt24-v4-trust-item">
                        <svg class="pt24-v4-trust-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Bez rejestracji
                    </div>
                    <div class="pt24-v4-trust-item">
                        <svg class="pt24-v4-trust-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Kontakt 24/7
                    </div>
                    <div class="pt24-v4-trust-item">
                        <svg class="pt24-v4-trust-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Sprawdzeni fachowcy
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [2] SERVICES GRID - Most Popular Services -->
    <!-- ================================================== -->
    <section class="pt24-v4-services">
        <div class="pt24-container">
            <h2 class="pt24-v4-section-title">Najpopularniejsze usługi</h2>
            <p class="pt24-v4-section-subtitle">Wybierz kategorię i znajdź fachowca w swojej okolicy</p>

            <div class="pt24-v4-services-grid">
                <a href="/mechanik/" class="pt24-v4-service-card">
                    <div class="pt24-v4-service-card__icon">🔧</div>
                    <h3 class="pt24-v4-service-card__title">Mechanik samochodowy</h3>
                    <p class="pt24-v4-service-card__description">Diagnostyka, naprawy, serwis mobilny</p>
                    <span class="pt24-v4-service-card__arrow">→</span>
                </a>

                <a href="/hydraulik/" class="pt24-v4-service-card">
                    <div class="pt24-v4-service-card__icon">🚰</div>
                    <h3 class="pt24-v4-service-card__title">Hydraulik</h3>
                    <p class="pt24-v4-service-card__description">Awarie 24h, remonty, instalacje</p>
                    <span class="pt24-v4-service-card__arrow">→</span>
                </a>

                <a href="/elektryk/" class="pt24-v4-service-card">
                    <div class="pt24-v4-service-card__icon">⚡</div>
                    <h3 class="pt24-v4-service-card__title">Elektryk</h3>
                    <p class="pt24-v4-service-card__description">Awarie prądu, instalacje, przeglądy</p>
                    <span class="pt24-v4-service-card__arrow">→</span>
                </a>

                <a href="/elektryk-samochodowy/" class="pt24-v4-service-card">
                    <div class="pt24-v4-service-card__icon">🔌</div>
                    <h3 class="pt24-v4-service-card__title">Elektryk samochodowy</h3>
                    <p class="pt24-v4-service-card__description">Stacyjki, alarmy, elektryka</p>
                    <span class="pt24-v4-service-card__arrow">→</span>
                </a>

                <a href="/laweta/" class="pt24-v4-service-card">
                    <div class="pt24-v4-service-card__icon">🚗</div>
                    <h3 class="pt24-v4-service-card__title">Laweta</h3>
                    <p class="pt24-v4-service-card__description">Pomoc drogowa 24/7</p>
                    <span class="pt24-v4-service-card__arrow">→</span>
                </a>

                <a href="/wulkanizacja/" class="pt24-v4-service-card">
                    <div class="pt24-v4-service-card__icon">🛞</div>
                    <h3 class="pt24-v4-service-card__title">Wulkanizacja</h3>
                    <p class="pt24-v4-service-card__description">Opony, felgi, wymiana sezonowa</p>
                    <span class="pt24-v4-service-card__arrow">→</span>
                </a>
            </div>

            <div class="pt24-v4-section-cta">
                <a href="/uslugi/" class="pt24-v4-btn pt24-v4-btn-outline">
                    Zobacz wszystkie usługi →
                </a>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [3] HOW IT WORKS - 3 Simple Steps -->
    <!-- ================================================== -->
    <section class="pt24-v4-how-it-works">
        <div class="pt24-container">
            <h2 class="pt24-v4-section-title">Trzy kroki do znalezienia fachowca</h2>

            <div class="pt24-v4-steps">
                <div class="pt24-v4-step">
                    <div class="pt24-v4-step__number">1</div>
                    <h3 class="pt24-v4-step__title">Wybierz usługę</h3>
                    <p class="pt24-v4-step__description">mechanik, hydraulik, elektryk...</p>
                </div>

                <div class="pt24-v4-step">
                    <div class="pt24-v4-step__number">2</div>
                    <h3 class="pt24-v4-step__title">Wybierz miasto</h3>
                    <p class="pt24-v4-step__description">fachowcy w Twojej okolicy</p>
                </div>

                <div class="pt24-v4-step">
                    <div class="pt24-v4-step__number">3</div>
                    <h3 class="pt24-v4-step__title">Zadzwoń</h3>
                    <p class="pt24-v4-step__description">kontakt bezpośredni</p>
                </div>
            </div>

            <p class="pt24-v4-how-it-works__note">
                Bez rejestracji · Bez prowizji · Bez czekania
            </p>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [4] POPULAR CITIES - Quick Access -->
    <!-- ================================================== -->
    <section class="pt24-v4-cities">
        <div class="pt24-container">
            <h2 class="pt24-v4-section-title">Najpopularniejsze miasta</h2>

            <div class="pt24-v4-cities-grid">
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
                    echo '<a href="/mechanik/' . esc_attr($slug) . '/" class="pt24-v4-city-link">' . esc_html($name) . '</a>';
                }
                ?>
            </div>

            <div class="pt24-v4-section-cta">
                <a href="/miasta/" class="pt24-v4-btn pt24-v4-btn-outline">
                    Wszystkie miasta →
                </a>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [5] FOR BUSINESSES - Add Your Business CTA -->
    <!-- ================================================== -->
    <section class="pt24-v4-business">
        <div class="pt24-container">
            <div class="pt24-v4-business-card">
                <h2 class="pt24-v4-business__title">
                    Prowadzisz firmę usługową?<br>
                    Zdobywaj klientów z Google
                </h2>

                <div class="pt24-v4-business-benefits">
                    <div class="pt24-v4-benefit">
                        <svg class="pt24-v4-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Własny profil z opisem i kontaktem</span>
                    </div>
                    <div class="pt24-v4-benefit">
                        <svg class="pt24-v4-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Widoczność w wyszukiwaniu lokalnym</span>
                    </div>
                    <div class="pt24-v4-benefit">
                        <svg class="pt24-v4-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Telefony od klientów z Twojego miasta</span>
                    </div>
                    <div class="pt24-v4-benefit">
                        <svg class="pt24-v4-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Działa 24/7, nawet gdy Ty śpisz</span>
                    </div>
                </div>

                <div class="pt24-v4-business-cta">
                    <a href="/dodaj-firme/" class="pt24-v4-btn pt24-v4-btn-primary pt24-v4-btn-lg">
                        ➕ Dodaj swoją firmę — 14 dni za darmo
                    </a>
                    <p class="pt24-v4-business-note">
                        Bez prowizji za kontakt · Klienci dzwonią bezpośrednio do Ciebie
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- [6] FINAL CTA - Strong Call to Action -->
    <!-- ================================================== -->
    <section class="pt24-v4-final-cta">
        <div class="pt24-container">
            <div class="pt24-v4-final-cta__content">
                <h2 class="pt24-v4-final-cta__title">
                    Potrzebujesz fachowca? Znajdź go w 2 minuty.
                </h2>

                <div class="pt24-v4-final-cta__buttons">
                    <a href="#pt24-v4-services" class="pt24-v4-btn pt24-v4-btn-primary pt24-v4-btn-lg">
                        🔍 Szukam fachowca
                    </a>
                    <a href="/dodaj-firme/" class="pt24-v4-btn pt24-v4-btn-outline pt24-v4-btn-lg">
                        ➕ Dodaję firmę
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer('minimal'); ?>
