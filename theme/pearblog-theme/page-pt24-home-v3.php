<?php
/**
 * Template Name: PT24.PRO - Homepage V3 (Production)
 *
 * High-conversion homepage for PT24.pro local services marketplace
 * V3 Production Copy - Lead generation focused
 *
 * @package PearBlog
 * @version 3.0.0
 */

wp_enqueue_style('pt24-home-v3', get_template_directory_uri() . '/assets/css/pt24-home-v3.css', array(), '3.0.0');
wp_enqueue_script('pt24-home-v3', get_template_directory_uri() . '/assets/js/pt24-home-v3.js', array(), '3.0.0', true);

// Localize script for AJAX
wp_localize_script('pt24-home-v3', 'pt24Data', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('pt24_nonce'),
));

get_header('minimal');
?>

<main class="pt24-v3">
    <!-- HERO (ABOVE THE FOLD) -->
    <section class="pt24-v3-hero">
        <div class="pt24-container">
            <div class="pt24-v3-hero__content">
                <h1 class="pt24-v3-hero__title">
                    Znajdź sprawdzonego fachowca w swojej okolicy
                </h1>

                <p class="pt24-v3-hero__subtitle">
                    Porównaj opinie, ceny i otrzymaj wycenę nawet w 15 minut.
                </p>

                <!-- SEARCH BAR -->
                <div class="pt24-v3-search">
                    <div class="pt24-v3-search__input-group">
                        <input
                            type="text"
                            class="pt24-v3-search__input pt24-v3-search__input--service"
                            placeholder="Jakiej usługi szukasz? (np. mechanik, hydraulik)"
                            id="pt24-search-service"
                        >
                        <input
                            type="text"
                            class="pt24-v3-search__input pt24-v3-search__input--city"
                            placeholder="Wpisz miasto"
                            id="pt24-search-city"
                        >
                        <button class="pt24-v3-search__button" id="pt24-search-button">
                            Znajdź fachowca
                        </button>
                    </div>
                </div>

                <div class="pt24-v3-hero__ctas">
                    <a href="#pt24-v3-quote" class="pt24-btn pt24-btn--secondary pt24-smooth-scroll">
                        Wyślij zapytanie
                    </a>
                </div>

                <!-- TRUST BAR -->
                <div class="pt24-v3-trust-bar">
                    <span class="pt24-v3-trust-bar__item">
                        <span class="pt24-v3-trust-bar__icon">⭐</span>
                        4.8/5
                    </span>
                    <span class="pt24-v3-trust-bar__divider">|</span>
                    <span class="pt24-v3-trust-bar__item">25 000+ opinii</span>
                    <span class="pt24-v3-trust-bar__divider">|</span>
                    <span class="pt24-v3-trust-bar__item">12 000+ firm w całej Polsce</span>
                </div>

                <!-- BADGES -->
                <div class="pt24-v3-badges">
                    <span class="pt24-v3-badge">✔ sprawdzone firmy</span>
                    <span class="pt24-v3-badge">✔ szybka odpowiedź</span>
                    <span class="pt24-v3-badge">✔ bez zobowiązań</span>
                </div>
            </div>
        </div>
    </section>

    <!-- SZYBKA WYCENA (LEAD HOOK) -->
    <section class="pt24-v3-quote" id="pt24-v3-quote">
        <div class="pt24-container">
            <div class="pt24-v3-quote__wrapper">
                <div class="pt24-v3-quote__header">
                    <h2 class="pt24-v3-section-title">Otrzymaj wycenę w 15 minut</h2>
                    <p class="pt24-v3-section-subtitle">
                        Opisz swój problem, a lokalni fachowcy prześlą Ci oferty.
                    </p>
                </div>

                <form class="pt24-v3-quote__form" id="pt24-quote-form">
                    <div class="pt24-v3-form-group">
                        <select name="service" class="pt24-v3-form-control" required>
                            <option value="">Wybierz usługę</option>
                            <option value="mechanik">Mechanik samochodowy</option>
                            <option value="elektryk">Elektryk samochodowy</option>
                            <option value="hydraulik">Hydraulik</option>
                            <option value="remonty">Remonty</option>
                            <option value="klimatyzacja">Klimatyzacja</option>
                            <option value="ogrzewanie">Ogrzewanie</option>
                            <option value="sprzatanie">Sprzątanie</option>
                            <option value="ogrodnik">Ogrodnik</option>
                        </select>
                    </div>

                    <div class="pt24-v3-form-group">
                        <input
                            type="text"
                            name="city"
                            class="pt24-v3-form-control"
                            placeholder="Miasto"
                            required
                        >
                    </div>

                    <div class="pt24-v3-form-group">
                        <textarea
                            name="description"
                            class="pt24-v3-form-control pt24-v3-form-control--textarea"
                            placeholder="Krótki opis problemu (np. 'Auto nie odpala, potrzebna diagnoza')"
                            rows="3"
                            required
                        ></textarea>
                    </div>

                    <div class="pt24-v3-form-group">
                        <input
                            type="text"
                            name="name"
                            class="pt24-v3-form-control"
                            placeholder="Imię"
                            required
                        >
                    </div>

                    <div class="pt24-v3-form-group">
                        <input
                            type="tel"
                            name="phone"
                            class="pt24-v3-form-control"
                            placeholder="Telefon"
                            required
                        >
                    </div>

                    <button type="submit" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-btn--full">
                        Wyślij zapytanie
                    </button>

                    <p class="pt24-v3-form-note">
                        Bez zobowiązań. Otrzymasz oferty od lokalnych firm w ciągu 15 minut.
                    </p>
                </form>
            </div>
        </div>
    </section>

    <!-- KATEGORIE (ENTRY POINT) -->
    <section class="pt24-v3-categories">
        <div class="pt24-container">
            <h2 class="pt24-v3-section-title">Popularne usługi</h2>
            <p class="pt24-v3-section-subtitle">Wybierz kategorię i znajdź specjalistę w swojej okolicy.</p>

            <div class="pt24-v3-categories-grid">
                <a href="/mechanik/" class="pt24-v3-category-card">
                    <div class="pt24-v3-category-card__icon">🔧</div>
                    <h3 class="pt24-v3-category-card__title">Mechanik</h3>
                    <span class="pt24-v3-category-card__arrow">→</span>
                </a>

                <a href="/elektryk/" class="pt24-v3-category-card">
                    <div class="pt24-v3-category-card__icon">⚡</div>
                    <h3 class="pt24-v3-category-card__title">Elektryk</h3>
                    <span class="pt24-v3-category-card__arrow">→</span>
                </a>

                <a href="/hydraulik/" class="pt24-v3-category-card">
                    <div class="pt24-v3-category-card__icon">🚰</div>
                    <h3 class="pt24-v3-category-card__title">Hydraulik</h3>
                    <span class="pt24-v3-category-card__arrow">→</span>
                </a>

                <a href="/remonty/" class="pt24-v3-category-card">
                    <div class="pt24-v3-category-card__icon">🏗️</div>
                    <h3 class="pt24-v3-category-card__title">Remonty</h3>
                    <span class="pt24-v3-category-card__arrow">→</span>
                </a>

                <a href="/klimatyzacja/" class="pt24-v3-category-card">
                    <div class="pt24-v3-category-card__icon">❄️</div>
                    <h3 class="pt24-v3-category-card__title">Klimatyzacja</h3>
                    <span class="pt24-v3-category-card__arrow">→</span>
                </a>

                <a href="/ogrzewanie/" class="pt24-v3-category-card">
                    <div class="pt24-v3-category-card__icon">🔥</div>
                    <h3 class="pt24-v3-category-card__title">Ogrzewanie</h3>
                    <span class="pt24-v3-category-card__arrow">→</span>
                </a>

                <a href="/sprzatanie/" class="pt24-v3-category-card">
                    <div class="pt24-v3-category-card__icon">🧹</div>
                    <h3 class="pt24-v3-category-card__title">Sprzątanie</h3>
                    <span class="pt24-v3-category-card__arrow">→</span>
                </a>

                <a href="/ogrodnik/" class="pt24-v3-category-card">
                    <div class="pt24-v3-category-card__icon">🌳</div>
                    <h3 class="pt24-v3-category-card__title">Ogrodnik</h3>
                    <span class="pt24-v3-category-card__arrow">→</span>
                </a>
            </div>

            <div class="pt24-v3-categories-cta">
                <a href="/uslugi/" class="pt24-btn pt24-btn--outline">Zobacz wszystkie usługi</a>
            </div>
        </div>
    </section>

    <!-- JAK TO DZIAŁA (PROSTOTA) -->
    <section class="pt24-v3-how-it-works">
        <div class="pt24-container">
            <div class="pt24-v3-steps">
                <div class="pt24-v3-step">
                    <div class="pt24-v3-step__number">1</div>
                    <h3 class="pt24-v3-step__title">Opisz problem</h3>
                    <p class="pt24-v3-step__description">
                        Dodaj krótkie zapytanie – zajmie to mniej niż minutę
                    </p>
                </div>

                <div class="pt24-v3-step">
                    <div class="pt24-v3-step__number">2</div>
                    <h3 class="pt24-v3-step__title">Otrzymaj oferty</h3>
                    <p class="pt24-v3-step__description">
                        Fachowcy z Twojej okolicy skontaktują się z Tobą
                    </p>
                </div>

                <div class="pt24-v3-step">
                    <div class="pt24-v3-step__number">3</div>
                    <h3 class="pt24-v3-step__title">Wybierz najlepszą opcję</h3>
                    <p class="pt24-v3-step__description">
                        Porównaj ceny i opinie – decyzja należy do Ciebie
                    </p>
                </div>
            </div>

            <div class="pt24-v3-how-it-works-cta">
                <a href="#pt24-v3-quote" class="pt24-btn pt24-btn--primary pt24-smooth-scroll">Wyślij zapytanie</a>
            </div>
        </div>
    </section>

    <!-- RANKINGI (SEO + TRUST) -->
    <section class="pt24-v3-rankings">
        <div class="pt24-container">
            <h2 class="pt24-v3-section-title">Najlepsi fachowcy w Twoim mieście</h2>
            <p class="pt24-v3-section-subtitle">
                Zobacz ranking firm na podstawie opinii i jakości usług.
            </p>

            <div class="pt24-v3-rankings-grid">
                <a href="/mechanik/katowice/" class="pt24-v3-ranking-link">
                    <span class="pt24-v3-ranking-link__icon">🏆</span>
                    <span class="pt24-v3-ranking-link__text">Mechanik Katowice – ranking</span>
                    <span class="pt24-v3-ranking-link__arrow">→</span>
                </a>

                <a href="/elektryk/krakow/" class="pt24-v3-ranking-link">
                    <span class="pt24-v3-ranking-link__icon">🏆</span>
                    <span class="pt24-v3-ranking-link__text">Elektryk Kraków – ranking</span>
                    <span class="pt24-v3-ranking-link__arrow">→</span>
                </a>

                <a href="/hydraulik/warszawa/" class="pt24-v3-ranking-link">
                    <span class="pt24-v3-ranking-link__icon">🏆</span>
                    <span class="pt24-v3-ranking-link__text">Hydraulik Warszawa – ranking</span>
                    <span class="pt24-v3-ranking-link__arrow">→</span>
                </a>

                <a href="/remonty/wroclaw/" class="pt24-v3-ranking-link">
                    <span class="pt24-v3-ranking-link__icon">🏆</span>
                    <span class="pt24-v3-ranking-link__text">Remonty Wrocław – ranking</span>
                    <span class="pt24-v3-ranking-link__arrow">→</span>
                </a>

                <a href="/klimatyzacja/poznan/" class="pt24-v3-ranking-link">
                    <span class="pt24-v3-ranking-link__icon">🏆</span>
                    <span class="pt24-v3-ranking-link__text">Klimatyzacja Poznań – ranking</span>
                    <span class="pt24-v3-ranking-link__arrow">→</span>
                </a>

                <a href="/ogrzewanie/gdansk/" class="pt24-v3-ranking-link">
                    <span class="pt24-v3-ranking-link__icon">🏆</span>
                    <span class="pt24-v3-ranking-link__text">Ogrzewanie Gdańsk – ranking</span>
                    <span class="pt24-v3-ranking-link__arrow">→</span>
                </a>
            </div>

            <div class="pt24-v3-rankings-cta">
                <a href="/rankingi/" class="pt24-btn pt24-btn--outline">Zobacz ranking</a>
            </div>
        </div>
    </section>

    <!-- SOCIAL PROOF (DYNAMIC) -->
    <section class="pt24-v3-social-proof">
        <div class="pt24-container">
            <div class="pt24-v3-social-proof-ticker" id="pt24-social-proof-ticker">
                <div class="pt24-v3-social-proof-item">
                    "Klient z Katowic otrzymał 3 oferty w 12 minut"
                </div>
                <div class="pt24-v3-social-proof-item">
                    "Nowe zapytanie: hydraulik Kraków – wysłano do 4 firm"
                </div>
                <div class="pt24-v3-social-proof-item">
                    "Klient z Warszawy wybrał ofertę – naprawiona w 2 godziny"
                </div>
            </div>
        </div>
    </section>

    <!-- CENY (HIGH INTENT) -->
    <section class="pt24-v3-pricing">
        <div class="pt24-container">
            <h2 class="pt24-v3-section-title">Ile kosztują usługi?</h2>

            <div class="pt24-v3-pricing-grid">
                <div class="pt24-v3-pricing-card">
                    <h3 class="pt24-v3-pricing-card__title">Wymiana oleju</h3>
                    <div class="pt24-v3-pricing-card__price">150–400 zł</div>
                    <p class="pt24-v3-pricing-card__note">+ koszt oleju i filtra</p>
                </div>

                <div class="pt24-v3-pricing-card">
                    <h3 class="pt24-v3-pricing-card__title">Hydraulik</h3>
                    <div class="pt24-v3-pricing-card__price">od 100 zł</div>
                    <p class="pt24-v3-pricing-card__note">za godzinę pracy</p>
                </div>

                <div class="pt24-v3-pricing-card">
                    <h3 class="pt24-v3-pricing-card__title">Elektryk</h3>
                    <div class="pt24-v3-pricing-card__price">od 120 zł</div>
                    <p class="pt24-v3-pricing-card__note">za godzinę pracy</p>
                </div>

                <div class="pt24-v3-pricing-card">
                    <h3 class="pt24-v3-pricing-card__title">Remont łazienki</h3>
                    <div class="pt24-v3-pricing-card__price">8 000–25 000 zł</div>
                    <p class="pt24-v3-pricing-card__note">w zależności od zakresu</p>
                </div>

                <div class="pt24-v3-pricing-card">
                    <h3 class="pt24-v3-pricing-card__title">Klimatyzacja</h3>
                    <div class="pt24-v3-pricing-card__price">2 500–5 000 zł</div>
                    <p class="pt24-v3-pricing-card__note">montaż + urządzenie</p>
                </div>

                <div class="pt24-v3-pricing-card">
                    <h3 class="pt24-v3-pricing-card__title">Pielęgnacja ogrodu</h3>
                    <div class="pt24-v3-pricing-card__price">od 80 zł</div>
                    <p class="pt24-v3-pricing-card__note">za wizytę</p>
                </div>
            </div>

            <p class="pt24-v3-pricing-note">
                Ceny zależą od zakresu pracy i lokalizacji.
            </p>

            <div class="pt24-v3-pricing-cta">
                <a href="#pt24-v3-quote" class="pt24-btn pt24-btn--primary pt24-smooth-scroll">
                    Sprawdź dokładną wycenę
                </a>
            </div>
        </div>
    </section>

    <!-- PORADNIKI (SEO ENGINE) -->
    <section class="pt24-v3-guides">
        <div class="pt24-container">
            <h2 class="pt24-v3-section-title">Poradniki i wskazówki</h2>
            <p class="pt24-v3-section-subtitle">
                Sprawdź najczęstsze problemy i rozwiązania.
            </p>

            <div class="pt24-v3-guides-grid">
                <a href="/poradnik/ile-kosztuje-remont-lazienki/" class="pt24-v3-guide-card">
                    <span class="pt24-v3-guide-card__icon">📖</span>
                    <span class="pt24-v3-guide-card__title">Ile kosztuje remont łazienki?</span>
                    <span class="pt24-v3-guide-card__arrow">→</span>
                </a>

                <a href="/poradnik/auto-nie-odpala-co-robic/" class="pt24-v3-guide-card">
                    <span class="pt24-v3-guide-card__icon">📖</span>
                    <span class="pt24-v3-guide-card__title">Auto nie odpala – co robić?</span>
                    <span class="pt24-v3-guide-card__arrow">→</span>
                </a>

                <a href="/poradnik/jak-wybrac-dobrego-elektryka/" class="pt24-v3-guide-card">
                    <span class="pt24-v3-guide-card__icon">📖</span>
                    <span class="pt24-v3-guide-card__title">Jak wybrać dobrego elektryka?</span>
                    <span class="pt24-v3-guide-card__arrow">→</span>
                </a>

                <a href="/poradnik/awaria-centralnego-ogrzewania/" class="pt24-v3-guide-card">
                    <span class="pt24-v3-guide-card__icon">📖</span>
                    <span class="pt24-v3-guide-card__title">Awaria centralnego ogrzewania – jak reagować?</span>
                    <span class="pt24-v3-guide-card__arrow">→</span>
                </a>

                <a href="/poradnik/klimatyzacja-dom-czy-warto/" class="pt24-v3-guide-card">
                    <span class="pt24-v3-guide-card__icon">📖</span>
                    <span class="pt24-v3-guide-card__title">Klimatyzacja w domu – czy warto?</span>
                    <span class="pt24-v3-guide-card__arrow">→</span>
                </a>

                <a href="/poradnik/jak-przygotowac-ogrod-na-wiosne/" class="pt24-v3-guide-card">
                    <span class="pt24-v3-guide-card__icon">📖</span>
                    <span class="pt24-v3-guide-card__title">Jak przygotować ogród na wiosnę?</span>
                    <span class="pt24-v3-guide-card__arrow">→</span>
                </a>
            </div>

            <div class="pt24-v3-guides-cta">
                <a href="/poradniki/" class="pt24-btn pt24-btn--outline">Zobacz poradniki</a>
            </div>
        </div>
    </section>

    <!-- FINAL CTA (CLOSER) -->
    <section class="pt24-v3-final-cta">
        <div class="pt24-container">
            <div class="pt24-v3-final-cta__content">
                <h2 class="pt24-v3-final-cta__title">
                    Masz problem? Znajdziemy fachowca za Ciebie
                </h2>
                <p class="pt24-v3-final-cta__subtitle">
                    Wyślij zapytanie i otrzymaj oferty od sprawdzonych firm.
                </p>

                <div class="pt24-v3-final-cta__buttons">
                    <a href="#pt24-v3-quote" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                        Wyślij zapytanie
                    </a>
                    <a href="/rankingi/" class="pt24-btn pt24-btn--secondary pt24-btn--large">
                        Sprawdź dostępność
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER SEO LINKS -->
    <section class="pt24-v3-footer-seo">
        <div class="pt24-container">
            <div class="pt24-v3-footer-seo-grid">
                <div class="pt24-v3-footer-seo-column">
                    <h3 class="pt24-v3-footer-seo-title">Miasta</h3>
                    <ul class="pt24-v3-footer-seo-list">
                        <li><a href="/katowice/">Katowice</a></li>
                        <li><a href="/warszawa/">Warszawa</a></li>
                        <li><a href="/krakow/">Kraków</a></li>
                        <li><a href="/wroclaw/">Wrocław</a></li>
                        <li><a href="/poznan/">Poznań</a></li>
                        <li><a href="/gdansk/">Gdańsk</a></li>
                    </ul>
                </div>

                <div class="pt24-v3-footer-seo-column">
                    <h3 class="pt24-v3-footer-seo-title">Usługi</h3>
                    <ul class="pt24-v3-footer-seo-list">
                        <li><a href="/mechanik/">Mechanik</a></li>
                        <li><a href="/elektryk/">Elektryk</a></li>
                        <li><a href="/hydraulik/">Hydraulik</a></li>
                        <li><a href="/remonty/">Remonty</a></li>
                        <li><a href="/klimatyzacja/">Klimatyzacja</a></li>
                        <li><a href="/ogrzewanie/">Ogrzewanie</a></li>
                    </ul>
                </div>

                <div class="pt24-v3-footer-seo-column">
                    <h3 class="pt24-v3-footer-seo-title">Rankingi</h3>
                    <ul class="pt24-v3-footer-seo-list">
                        <li><a href="/rankingi/mechanik/">Najlepsi mechanicy</a></li>
                        <li><a href="/rankingi/elektryk/">Najlepsi elektrycy</a></li>
                        <li><a href="/rankingi/hydraulik/">Najlepsi hydraulicy</a></li>
                        <li><a href="/rankingi/firma-remontowa/">Najlepsze firmy remontowe</a></li>
                    </ul>
                </div>

                <div class="pt24-v3-footer-seo-column">
                    <h3 class="pt24-v3-footer-seo-title">Poradniki</h3>
                    <ul class="pt24-v3-footer-seo-list">
                        <li><a href="/poradniki/">Wszystkie poradniki</a></li>
                        <li><a href="/poradnik/mechanika/">Mechanika</a></li>
                        <li><a href="/poradnik/elektryka/">Elektryka</a></li>
                        <li><a href="/poradnik/hydraulika/">Hydraulika</a></li>
                    </ul>
                </div>

                <div class="pt24-v3-footer-seo-column">
                    <h3 class="pt24-v3-footer-seo-title">Kontakt</h3>
                    <ul class="pt24-v3-footer-seo-list">
                        <li><a href="/kontakt/">Kontakt</a></li>
                        <li><a href="/dla-firm/">Dla firm</a></li>
                        <li><a href="/regulamin/">Regulamin</a></li>
                        <li><a href="/polityka-prywatnosci/">Polityka prywatności</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer('minimal'); ?>
