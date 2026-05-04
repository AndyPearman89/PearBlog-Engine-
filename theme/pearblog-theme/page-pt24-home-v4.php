<?php
/**
 * Template Name: PT24.PRO - Homepage V4 (HI-PRO)
 *
 * Ultra high-conversion homepage for PT24.pro
 * V4 HI-PRO - Premium conversion optimization
 *
 * @package PearBlog
 * @version 4.0.0
 */

wp_enqueue_style('pt24-home-v4', get_template_directory_uri() . '/assets/css/pt24-home-v4.css', array(), '4.0.0');
wp_enqueue_script('pt24-home-v4', get_template_directory_uri() . '/assets/js/pt24-home-v4.js', array(), '4.0.0', true);

// Localize script for AJAX
wp_localize_script('pt24-home-v4', 'pt24Data', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('pt24_nonce'),
));

get_header('minimal');
?>

<main class="pt24-v4">
    <!-- [1] HERO – PREMIUM CONVERSION -->
    <section class="pt24-v4-hero">
        <div class="pt24-container">
            <div class="pt24-v4-hero__content">
                <h1 class="pt24-v4-hero__title">
                    Znajdź sprawdzonego fachowca w swojej okolicy
                </h1>

                <p class="pt24-v4-hero__subtitle">
                    Porównaj oferty, sprawdź opinie i otrzymaj wycenę nawet w 15 minut — bez dzwonienia i bez stresu.
                </p>

                <!-- SEARCH BAR -->
                <div class="pt24-v4-search">
                    <div class="pt24-v4-search__input-group">
                        <input
                            type="text"
                            class="pt24-v4-search__input"
                            placeholder="Jakiej usługi potrzebujesz?"
                            id="pt24-v4-search-service"
                        >
                        <input
                            type="text"
                            class="pt24-v4-search__input"
                            placeholder="Twoje miasto"
                            id="pt24-v4-search-city"
                        >
                        <button class="pt24-v4-search__button pt24-v4-btn--primary" id="pt24-v4-search-button">
                            Znajdź fachowca
                        </button>
                    </div>
                </div>

                <!-- CTAs -->
                <div class="pt24-v4-hero__ctas">
                    <a href="#pt24-v4-lead-block" class="pt24-btn pt24-btn--secondary-outline pt24-smooth-scroll">
                        Otrzymaj wycenę
                    </a>
                </div>

                <!-- TRUST BAR -->
                <div class="pt24-v4-trust-bar">
                    <span class="pt24-v4-trust-bar__item">
                        <span class="pt24-v4-trust-bar__icon">⭐</span>
                        4.8/5
                    </span>
                    <span class="pt24-v4-trust-bar__divider">|</span>
                    <span class="pt24-v4-trust-bar__item">25 000+ opinii</span>
                    <span class="pt24-v4-trust-bar__divider">|</span>
                    <span class="pt24-v4-trust-bar__item">12 000+ firm w Polsce</span>
                </div>

                <!-- BADGES -->
                <div class="pt24-v4-badges">
                    <span class="pt24-v4-badge">✔ tylko sprawdzone firmy</span>
                    <span class="pt24-v4-badge">✔ odpowiedzi nawet w 15 min</span>
                    <span class="pt24-v4-badge">✔ 100% bez zobowiązań</span>
                </div>

                <!-- MICROCOPY -->
                <p class="pt24-v4-hero__microcopy">
                    Nie musisz dzwonić — fachowcy odezwą się do Ciebie.
                </p>
            </div>
        </div>
    </section>

    <!-- [2] SMART LEAD BLOCK (NO FRICTION) -->
    <section class="pt24-v4-lead-block" id="pt24-v4-lead-block">
        <div class="pt24-container">
            <div class="pt24-v4-lead-block__wrapper">
                <div class="pt24-v4-lead-block__header">
                    <h2 class="pt24-v4-section-title">Otrzymaj dopasowane oferty w kilka minut</h2>
                    <p class="pt24-v4-section-subtitle">
                        Opisz problem, a system automatycznie dopasuje do Ciebie najlepszych fachowców.
                    </p>
                </div>

                <form class="pt24-v4-lead-form" id="pt24-v4-lead-form">
                    <div class="pt24-v4-form-group">
                        <label for="lead-service" class="pt24-v4-form-label">Usługa</label>
                        <select name="service" id="lead-service" class="pt24-v4-form-control" required>
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

                    <div class="pt24-v4-form-group">
                        <label for="lead-location" class="pt24-v4-form-label">
                            Lokalizacja
                            <span class="pt24-v4-form-label-hint">(automatycznie wykryta)</span>
                        </label>
                        <input
                            type="text"
                            name="location"
                            id="lead-location"
                            class="pt24-v4-form-control"
                            placeholder="Miasto zostanie wykryte automatycznie"
                            required
                        >
                        <button type="button" class="pt24-v4-detect-location" id="detect-location-btn">
                            📍 Wykryj moją lokalizację
                        </button>
                    </div>

                    <div class="pt24-v4-form-group">
                        <label for="lead-description" class="pt24-v4-form-label">Opis problemu</label>
                        <textarea
                            name="description"
                            id="lead-description"
                            class="pt24-v4-form-control pt24-v4-form-control--textarea"
                            placeholder="Np. 'Auto nie odpala po nocy, potrzebna diagnoza'"
                            rows="4"
                            required
                        ></textarea>
                    </div>

                    <div class="pt24-v4-form-row">
                        <div class="pt24-v4-form-group">
                            <label for="lead-name" class="pt24-v4-form-label">Imię</label>
                            <input
                                type="text"
                                name="name"
                                id="lead-name"
                                class="pt24-v4-form-control"
                                placeholder="Twoje imię"
                                required
                            >
                        </div>

                        <div class="pt24-v4-form-group">
                            <label for="lead-phone" class="pt24-v4-form-label">Telefon</label>
                            <input
                                type="tel"
                                name="phone"
                                id="lead-phone"
                                class="pt24-v4-form-control"
                                placeholder="+48 XXX XXX XXX"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-btn--full">
                        Wyślij zapytanie
                    </button>

                    <!-- TRUST MICRO -->
                    <div class="pt24-v4-trust-micro">
                        <span class="pt24-v4-trust-micro__item">✔ dopasowanie do Twojej lokalizacji</span>
                        <span class="pt24-v4-trust-micro__item">✔ tylko dostępni fachowcy</span>
                        <span class="pt24-v4-trust-micro__item">✔ szybkie odpowiedzi</span>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- [3] CATEGORY GRID – VISUAL ENTRY -->
    <section class="pt24-v4-categories">
        <div class="pt24-container">
            <h2 class="pt24-v4-section-title">Najczęściej wybierane usługi</h2>

            <div class="pt24-v4-category-grid">
                <a href="/mechanik/" class="pt24-v4-category-card">
                    <div class="pt24-v4-category-card__icon">🔧</div>
                    <h3 class="pt24-v4-category-card__title">Mechanik</h3>
                </a>

                <a href="/elektryk/" class="pt24-v4-category-card">
                    <div class="pt24-v4-category-card__icon">⚡</div>
                    <h3 class="pt24-v4-category-card__title">Elektryk</h3>
                </a>

                <a href="/hydraulik/" class="pt24-v4-category-card">
                    <div class="pt24-v4-category-card__icon">🚿</div>
                    <h3 class="pt24-v4-category-card__title">Hydraulik</h3>
                </a>

                <a href="/remonty/" class="pt24-v4-category-card">
                    <div class="pt24-v4-category-card__icon">🧱</div>
                    <h3 class="pt24-v4-category-card__title">Remonty</h3>
                </a>

                <a href="/klimatyzacja/" class="pt24-v4-category-card">
                    <div class="pt24-v4-category-card__icon">❄️</div>
                    <h3 class="pt24-v4-category-card__title">Klimatyzacja</h3>
                </a>

                <a href="/ogrzewanie/" class="pt24-v4-category-card">
                    <div class="pt24-v4-category-card__icon">🔥</div>
                    <h3 class="pt24-v4-category-card__title">Ogrzewanie</h3>
                </a>

                <a href="/sprzatanie/" class="pt24-v4-category-card">
                    <div class="pt24-v4-category-card__icon">🧹</div>
                    <h3 class="pt24-v4-category-card__title">Sprzątanie</h3>
                </a>

                <a href="/ogrodnik/" class="pt24-v4-category-card">
                    <div class="pt24-v4-category-card__icon">🌿</div>
                    <h3 class="pt24-v4-category-card__title">Ogrodnik</h3>
                </a>
            </div>

            <div class="pt24-v4-categories-cta">
                <a href="/uslugi/" class="pt24-btn pt24-btn--outline">Zobacz wszystkie usługi</a>
            </div>
        </div>
    </section>

    <!-- [4] HOW IT WORKS – ULTRA SIMPLE -->
    <section class="pt24-v4-how-it-works">
        <div class="pt24-container">
            <h2 class="pt24-v4-section-title">Jak to działa?</h2>

            <div class="pt24-v4-steps">
                <div class="pt24-v4-step">
                    <div class="pt24-v4-step__number">1</div>
                    <h3 class="pt24-v4-step__title">Dodaj zapytanie</h3>
                    <p class="pt24-v4-step__description">
                        Wpisz problem — zajmie to mniej niż minutę
                    </p>
                </div>

                <div class="pt24-v4-step">
                    <div class="pt24-v4-step__number">2</div>
                    <h3 class="pt24-v4-step__title">Otrzymaj oferty</h3>
                    <p class="pt24-v4-step__description">
                        Fachowcy z Twojej okolicy zgłoszą się do Ciebie
                    </p>
                </div>

                <div class="pt24-v4-step">
                    <div class="pt24-v4-step__number">3</div>
                    <h3 class="pt24-v4-step__title">Wybierz najlepszą opcję</h3>
                    <p class="pt24-v4-step__description">
                        Porównaj ceny, opinie i dostępność
                    </p>
                </div>
            </div>

            <div class="pt24-v4-how-it-works-cta">
                <a href="#pt24-v4-lead-block" class="pt24-btn pt24-btn--primary pt24-smooth-scroll">
                    Wyślij zapytanie
                </a>
            </div>
        </div>
    </section>

    <!-- [5] LIVE ACTIVITY (REAL-TIME TRUST) -->
    <section class="pt24-v4-live-activity">
        <div class="pt24-container">
            <div class="pt24-v4-live-feed" id="pt24-v4-live-feed">
                <div class="pt24-v4-live-item">
                    <span class="pt24-v4-live-pulse"></span>
                    "Klient z Katowic otrzymał 4 oferty w 9 minut"
                </div>
                <div class="pt24-v4-live-item">
                    <span class="pt24-v4-live-pulse"></span>
                    "Nowe zapytanie: elektryk Kraków – wysłane do 3 firm"
                </div>
                <div class="pt24-v4-live-item">
                    <span class="pt24-v4-live-pulse"></span>
                    "Mechanik z Warszawy odebrał zlecenie – odpowiedź w 7 minut"
                </div>
            </div>
        </div>
    </section>

    <!-- [6] TOP RANKINGS (SEO + AUTHORITY) -->
    <section class="pt24-v4-rankings">
        <div class="pt24-container">
            <h2 class="pt24-v4-section-title">Najlepsi fachowcy w Twoim mieście</h2>
            <p class="pt24-v4-section-subtitle">
                Rankingi oparte na opiniach, jakości usług i czasie odpowiedzi.
            </p>

            <div class="pt24-v4-rankings-grid">
                <a href="/mechanik/katowice/" class="pt24-v4-ranking-link">
                    <span class="pt24-v4-ranking-icon">🏆</span>
                    <span class="pt24-v4-ranking-text">Mechanik Katowice – ranking</span>
                    <span class="pt24-v4-ranking-arrow">→</span>
                </a>

                <a href="/elektryk/warszawa/" class="pt24-v4-ranking-link">
                    <span class="pt24-v4-ranking-icon">🏆</span>
                    <span class="pt24-v4-ranking-text">Elektryk Warszawa – ranking</span>
                    <span class="pt24-v4-ranking-arrow">→</span>
                </a>

                <a href="/hydraulik/krakow/" class="pt24-v4-ranking-link">
                    <span class="pt24-v4-ranking-icon">🏆</span>
                    <span class="pt24-v4-ranking-text">Hydraulik Kraków – ranking</span>
                    <span class="pt24-v4-ranking-arrow">→</span>
                </a>

                <a href="/remonty/wroclaw/" class="pt24-v4-ranking-link">
                    <span class="pt24-v4-ranking-icon">🏆</span>
                    <span class="pt24-v4-ranking-text">Remonty Wrocław – ranking</span>
                    <span class="pt24-v4-ranking-arrow">→</span>
                </a>

                <a href="/klimatyzacja/poznan/" class="pt24-v4-ranking-link">
                    <span class="pt24-v4-ranking-icon">🏆</span>
                    <span class="pt24-v4-ranking-text">Klimatyzacja Poznań – ranking</span>
                    <span class="pt24-v4-ranking-arrow">→</span>
                </a>

                <a href="/ogrzewanie/gdansk/" class="pt24-v4-ranking-link">
                    <span class="pt24-v4-ranking-icon">🏆</span>
                    <span class="pt24-v4-ranking-text">Ogrzewanie Gdańsk – ranking</span>
                    <span class="pt24-v4-ranking-arrow">→</span>
                </a>
            </div>

            <div class="pt24-v4-rankings-cta">
                <a href="/rankingi/" class="pt24-btn pt24-btn--outline">Zobacz ranking</a>
            </div>
        </div>
    </section>

    <!-- [7] COST INSIGHT (DECISION DRIVER) -->
    <section class="pt24-v4-cost-insight">
        <div class="pt24-container">
            <h2 class="pt24-v4-section-title">Ile to kosztuje?</h2>

            <div class="pt24-v4-pricing-grid">
                <div class="pt24-v4-pricing-card">
                    <h3 class="pt24-v4-pricing-title">Wymiana oleju</h3>
                    <div class="pt24-v4-pricing-amount">150–400 zł</div>
                </div>

                <div class="pt24-v4-pricing-card">
                    <h3 class="pt24-v4-pricing-title">Hydraulik</h3>
                    <div class="pt24-v4-pricing-amount">od 100 zł</div>
                </div>

                <div class="pt24-v4-pricing-card">
                    <h3 class="pt24-v4-pricing-title">Elektryk</h3>
                    <div class="pt24-v4-pricing-amount">od 120 zł</div>
                </div>

                <div class="pt24-v4-pricing-card">
                    <h3 class="pt24-v4-pricing-title">Remont łazienki</h3>
                    <div class="pt24-v4-pricing-amount">8 000–25 000 zł</div>
                </div>

                <div class="pt24-v4-pricing-card">
                    <h3 class="pt24-v4-pricing-title">Klimatyzacja</h3>
                    <div class="pt24-v4-pricing-amount">2 500–5 000 zł</div>
                </div>

                <div class="pt24-v4-pricing-card">
                    <h3 class="pt24-v4-pricing-title">Pielęgnacja ogrodu</h3>
                    <div class="pt24-v4-pricing-amount">od 80 zł</div>
                </div>
            </div>

            <p class="pt24-v4-cost-insight__note">
                Ceny mogą się różnić nawet o 40% — porównaj oferty zanim wybierzesz.
            </p>

            <div class="pt24-v4-cost-insight-cta">
                <a href="#pt24-v4-lead-block" class="pt24-btn pt24-btn--primary pt24-smooth-scroll">
                    Sprawdź dokładną wycenę
                </a>
            </div>
        </div>
    </section>

    <!-- [8] CONTENT HUB (SEO + EDUCATION) -->
    <section class="pt24-v4-content-hub">
        <div class="pt24-container">
            <h2 class="pt24-v4-section-title">Poradniki i wskazówki</h2>
            <p class="pt24-v4-section-subtitle">
                Zobacz najczęstsze problemy i sprawdzone rozwiązania.
            </p>

            <div class="pt24-v4-guides-grid">
                <a href="/poradnik/ile-kosztuje-remont-lazienki/" class="pt24-v4-guide-card">
                    <span class="pt24-v4-guide-icon">📖</span>
                    <span class="pt24-v4-guide-title">Ile kosztuje remont łazienki?</span>
                    <span class="pt24-v4-guide-arrow">→</span>
                </a>

                <a href="/poradnik/auto-nie-odpala-co-robic/" class="pt24-v4-guide-card">
                    <span class="pt24-v4-guide-icon">📖</span>
                    <span class="pt24-v4-guide-title">Auto nie odpala – co robić?</span>
                    <span class="pt24-v4-guide-arrow">→</span>
                </a>

                <a href="/poradnik/jak-wybrac-dobrego-hydraulika/" class="pt24-v4-guide-card">
                    <span class="pt24-v4-guide-icon">📖</span>
                    <span class="pt24-v4-guide-title">Jak wybrać dobrego hydraulika?</span>
                    <span class="pt24-v4-guide-arrow">→</span>
                </a>

                <a href="/poradnik/awaria-centralnego-ogrzewania/" class="pt24-v4-guide-card">
                    <span class="pt24-v4-guide-icon">📖</span>
                    <span class="pt24-v4-guide-title">Awaria ogrzewania – jak reagować?</span>
                    <span class="pt24-v4-guide-arrow">→</span>
                </a>

                <a href="/poradnik/klimatyzacja-dom-czy-warto/" class="pt24-v4-guide-card">
                    <span class="pt24-v4-guide-icon">📖</span>
                    <span class="pt24-v4-guide-title">Klimatyzacja w domu – czy warto?</span>
                    <span class="pt24-v4-guide-arrow">→</span>
                </a>

                <a href="/poradnik/jak-przygotowac-ogrod-na-wiosne/" class="pt24-v4-guide-card">
                    <span class="pt24-v4-guide-icon">📖</span>
                    <span class="pt24-v4-guide-title">Jak przygotować ogród na wiosnę?</span>
                    <span class="pt24-v4-guide-arrow">→</span>
                </a>
            </div>

            <div class="pt24-v4-content-hub-cta">
                <a href="/poradniki/" class="pt24-btn pt24-btn--outline">Zobacz poradniki</a>
            </div>
        </div>
    </section>

    <!-- [9] FINAL CTA – STRONG CLOSE -->
    <section class="pt24-v4-final-cta">
        <div class="pt24-container">
            <div class="pt24-v4-final-cta__content">
                <h2 class="pt24-v4-final-cta__title">
                    Masz problem? Znajdziemy fachowca za Ciebie
                </h2>
                <p class="pt24-v4-final-cta__subtitle">
                    Wyślij jedno zapytanie i otrzymaj oferty od sprawdzonych specjalistów.
                </p>

                <div class="pt24-v4-final-cta__buttons">
                    <a href="#pt24-v4-lead-block" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                        Wyślij zapytanie
                    </a>
                    <a href="/rankingi/" class="pt24-btn pt24-btn--secondary-light pt24-btn--large">
                        Znajdź fachowca
                    </a>
                </div>

                <!-- TRUST BULLETS -->
                <div class="pt24-v4-final-trust">
                    <span class="pt24-v4-final-trust__item">✔ szybkie odpowiedzi</span>
                    <span class="pt24-v4-final-trust__item">✔ sprawdzone firmy</span>
                    <span class="pt24-v4-final-trust__item">✔ bez zobowiązań</span>
                </div>
            </div>
        </div>
    </section>

    <!-- [10] FOOTER – SEO + SCALE -->
    <section class="pt24-v4-footer-seo">
        <div class="pt24-container">
            <div class="pt24-v4-footer-grid">
                <div class="pt24-v4-footer-column">
                    <h3 class="pt24-v4-footer-title">Miasta</h3>
                    <ul class="pt24-v4-footer-list">
                        <li><a href="/katowice/">Katowice</a></li>
                        <li><a href="/warszawa/">Warszawa</a></li>
                        <li><a href="/krakow/">Kraków</a></li>
                        <li><a href="/wroclaw/">Wrocław</a></li>
                        <li><a href="/poznan/">Poznań</a></li>
                        <li><a href="/gdansk/">Gdańsk</a></li>
                    </ul>
                </div>

                <div class="pt24-v4-footer-column">
                    <h3 class="pt24-v4-footer-title">Usługi</h3>
                    <ul class="pt24-v4-footer-list">
                        <li><a href="/mechanik/">Mechanik</a></li>
                        <li><a href="/elektryk/">Elektryk</a></li>
                        <li><a href="/hydraulik/">Hydraulik</a></li>
                        <li><a href="/remonty/">Remonty</a></li>
                        <li><a href="/klimatyzacja/">Klimatyzacja</a></li>
                        <li><a href="/ogrzewanie/">Ogrzewanie</a></li>
                    </ul>
                </div>

                <div class="pt24-v4-footer-column">
                    <h3 class="pt24-v4-footer-title">Rankingi</h3>
                    <ul class="pt24-v4-footer-list">
                        <li><a href="/rankingi/mechanik/">Najlepsi mechanicy</a></li>
                        <li><a href="/rankingi/elektryk/">Najlepsi elektrycy</a></li>
                        <li><a href="/rankingi/hydraulik/">Najlepsi hydraulicy</a></li>
                        <li><a href="/rankingi/firma-remontowa/">Najlepsze firmy remontowe</a></li>
                    </ul>
                </div>

                <div class="pt24-v4-footer-column">
                    <h3 class="pt24-v4-footer-title">Poradniki</h3>
                    <ul class="pt24-v4-footer-list">
                        <li><a href="/poradniki/">Wszystkie poradniki</a></li>
                        <li><a href="/poradnik/mechanika/">Mechanika</a></li>
                        <li><a href="/poradnik/elektryka/">Elektryka</a></li>
                        <li><a href="/poradnik/hydraulika/">Hydraulika</a></li>
                    </ul>
                </div>

                <div class="pt24-v4-footer-column">
                    <h3 class="pt24-v4-footer-title">Dla firm</h3>
                    <ul class="pt24-v4-footer-list">
                        <li><a href="/dodaj-firme/">Dodaj firmę</a></li>
                        <li><a href="/dla-firm/">Dla firm</a></li>
                        <li><a href="/kontakt/">Kontakt</a></li>
                        <li><a href="/regulamin/">Regulamin</a></li>
                        <li><a href="/polityka-prywatnosci/">Polityka prywatności</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer('minimal'); ?>
