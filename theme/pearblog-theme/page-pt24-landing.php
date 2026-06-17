<?php
/**
 * Template Name: PT24.PRO Landing
 *
 * High-conversion landing page for PT24.pro local business directory
 * Features: Dynamic service/city, lead forms, local companies, pricing, ranking
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Enqueue landing page assets
wp_enqueue_style('pt24-landing', get_template_directory_uri() . '/assets/css/pt24-landing.css', array(), '1.0.0');
wp_enqueue_script('pt24-landing', get_template_directory_uri() . '/assets/js/pt24-landing.js', array(), '1.0.0', true);

// Get URL parameters
$service = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : 'usługę';
$city = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : 'Twojej okolicy';
$service_display = ucfirst(str_replace('-', ' ', $service));
$city_display = ucfirst($city);
$ranking_companies = function_exists('pb_pt24_get_ranking_companies')
    ? pb_pt24_get_ranking_companies($service_display, $city_display)
    : array();

get_header('minimal');
?>

<main class="pt24-landing">
    <!-- HERO SECTION -->
    <section class="pt24-hero">
        <div class="pt24-container">
            <div class="pt24-hero__content">
                <h1 class="pt24-hero__title">
                    <?php echo esc_html($service_display); ?> <?php echo esc_html($city_display); ?> — sprawdź ceny i dostępne firmy
                </h1>

                <p class="pt24-hero__subtitle">
                    Znajdź najlepszych specjalistów w Twojej okolicy i otrzymaj dopasowane oferty.
                </p>

                <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                    Otrzymaj 3 oferty
                </a>

                <!-- Trust Signals -->
                <div class="pt24-hero__trust">
                    <div class="pt24-trust-item">
                        <svg class="pt24-trust-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Darmowe i bez zobowiązań</span>
                    </div>
                    <div class="pt24-trust-item">
                        <svg class="pt24-trust-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Tylko sprawdzone firmy</span>
                    </div>
                    <div class="pt24-trust-item">
                        <svg class="pt24-trust-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Oferty nawet w 24h</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LEAD FORM SECTION -->
    <section class="pt24-form-section" id="pt24-form">
        <div class="pt24-container">
            <div class="pt24-form-card">
                <h2 class="pt24-form__title">Wypełnij formularz i otrzymaj oferty</h2>
                <p class="pt24-form__subtitle">Otrzymasz do 3 spersonalizowanych ofert od sprawdzonych firm</p>

                <form id="pt24LeadForm" class="pt24-form" method="post">
                    <?php wp_nonce_field('pt24_lead_submit', 'pt24_nonce'); ?>

                    <input type="hidden" name="action" value="pt24_submit_lead">
                    <input type="hidden" name="service" value="<?php echo esc_attr($service); ?>">
                    <input type="hidden" name="city" value="<?php echo esc_attr($city); ?>">
                    <input type="hidden" name="source_url" value="<?php echo esc_url($_SERVER['HTTP_REFERER'] ?? ''); ?>">

                    <div class="pt24-form__field">
                        <label for="pt24_service_need" class="pt24-form__label">Co potrzebujesz?</label>
                        <textarea
                            id="pt24_service_need"
                            name="service_need"
                            class="pt24-form__textarea"
                            placeholder="Opisz czego potrzebujesz..."
                            rows="4"
                            required
                        ></textarea>
                    </div>

                    <div class="pt24-form__field">
                        <label for="pt24_city_input" class="pt24-form__label">Miasto</label>
                        <input
                            type="text"
                            id="pt24_city_input"
                            name="city_input"
                            class="pt24-form__input"
                            value="<?php echo esc_attr($city_display); ?>"
                            placeholder="Wpisz miasto"
                            required
                        >
                    </div>

                    <div class="pt24-form__field">
                        <label for="pt24_name" class="pt24-form__label">Imię</label>
                        <input
                            type="text"
                            id="pt24_name"
                            name="name"
                            class="pt24-form__input"
                            placeholder="Twoje imię"
                            required
                        >
                    </div>

                    <div class="pt24-form__field">
                        <label for="pt24_phone" class="pt24-form__label">Telefon</label>
                        <input
                            type="tel"
                            id="pt24_phone"
                            name="phone"
                            class="pt24-form__input"
                            placeholder="+48 123 456 789"
                            required
                        >
                    </div>

                    <div class="pt24-form__field">
                        <label for="pt24_email" class="pt24-form__label">Email</label>
                        <input
                            type="email"
                            id="pt24_email"
                            name="email"
                            class="pt24-form__input"
                            placeholder="twoj@email.pl"
                            required
                        >
                    </div>

                    <div class="pt24-form__field">
                        <label class="pt24-form__checkbox">
                            <input type="checkbox" name="consent" required>
                            <span>Zgadzam się na przetwarzanie danych osobowych w celu otrzymania ofert</span>
                        </label>
                    </div>

                    <button type="submit" class="pt24-btn pt24-btn--secondary pt24-btn--large pt24-btn--full">
                        Darmowa wycena
                    </button>

                    <p class="pt24-form__note">
                        Wysyłając formularz akceptujesz <a href="/regulamin">regulamin</a> i <a href="/polityka-prywatnosci">politykę prywatności</a>
                    </p>
                </form>
            </div>
        </div>
    </section>

    <!-- MAP / PROOF SECTION -->
    <section class="pt24-map-section">
        <div class="pt24-container">
            <h2 class="pt24-section-title">Firmy w Twojej okolicy</h2>

            <div class="pt24-map-grid">
                <div class="pt24-map-feature">
                    <div class="pt24-map-icon">🗺️</div>
                    <h3>Zobacz dostępne firmy</h3>
                    <p>Setki sprawdzonych wykonawców w Twojej okolicy</p>
                </div>

                <div class="pt24-map-feature">
                    <div class="pt24-map-icon">⭐</div>
                    <h3>Porównaj opinie</h3>
                    <p>Prawdziwe recenzje od zadowolonych klientów</p>
                </div>

                <div class="pt24-map-feature">
                    <div class="pt24-map-icon">✅</div>
                    <h3>Wybierz najlepszą</h3>
                    <p>Otrzymaj oferty i zdecyduj bez pośpiechu</p>
                </div>
            </div>
        </div>
    </section>

    <!-- COST BLOCK -->
    <section class="pt24-cost-section">
        <div class="pt24-container">
            <div class="pt24-cost-card">
                <h2 class="pt24-cost__title">
                    Ile kosztuje <?php echo esc_html($service_display); ?> w <?php echo esc_html($city_display); ?>?
                </h2>

                <p class="pt24-cost__intro">Ceny zależą od:</p>

                <ul class="pt24-cost__factors">
                    <li>
                        <svg class="pt24-check-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Zakresu prac</span>
                    </li>
                    <li>
                        <svg class="pt24-check-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Lokalizacji</span>
                    </li>
                    <li>
                        <svg class="pt24-check-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Dostępności</span>
                    </li>
                </ul>

                <p class="pt24-cost__cta-text">
                    Sprawdź realne oferty zamiast zgadywać.
                </p>

                <a href="#pt24-form" class="pt24-btn pt24-btn--accent pt24-smooth-scroll">
                    Sprawdź ceny
                </a>
            </div>
        </div>
    </section>

    <!-- RANKING SECTION -->
    <section class="pt24-ranking-section">
        <div class="pt24-container">
            <h2 class="pt24-section-title">Najlepsze firmy w <?php echo esc_html($city_display); ?></h2>
            <p class="pt24-section-subtitle">Ranking oparty na:</p>

            <div class="pt24-ranking-criteria">
                <div class="pt24-ranking-criterion">
                    <svg class="pt24-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                        <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Opiniach klientów</span>
                </div>
                <div class="pt24-ranking-criterion">
                    <svg class="pt24-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                        <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Skuteczności realizacji</span>
                </div>
                <div class="pt24-ranking-criterion">
                    <svg class="pt24-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                        <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Dostępności terminów</span>
                </div>
            </div>

            <div class="pt24-ranking-grid">
                <?php foreach ($ranking_companies as $index => $company) : ?>
                    <article class="pt24-ranking-card">
                        <div class="pt24-ranking-badge <?php echo esc_attr($company['badge_class']); ?>">
                            <?php echo esc_html($company['badge']); ?>
                        </div>
                        <div class="pt24-ranking-number">#<?php echo esc_html((string) ($index + 1)); ?></div>
                        <h3 class="pt24-ranking-company"><?php echo esc_html($company['name']); ?></h3>
                        <div class="pt24-ranking-rating">
                            <span class="pt24-stars">★★★★★</span>
                            <span class="pt24-rating-value"><?php echo esc_html($company['rating']); ?></span>
                            <span class="pt24-rating-count">(<?php echo esc_html($company['reviews']); ?> opinii)</span>
                        </div>
                        <ul class="pt24-ranking-meta">
                            <li><?php echo esc_html($company['availability']); ?></li>
                            <li><?php echo esc_html($company['response']); ?></li>
                            <li>Zweryfikowany profil</li>
                        </ul>
                        <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-btn--full pt24-smooth-scroll">
                            Otrzymaj ofertę
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECOND CTA SECTION -->
    <section class="pt24-cta-section">
        <div class="pt24-container">
            <div class="pt24-cta-card">
                <h2 class="pt24-cta__title">Nie trać czasu na szukanie</h2>
                <p class="pt24-cta__text">
                    Otrzymaj oferty od firm i wybierz najlepszą opcję.
                </p>
                <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                    Otrzymaj oferty
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section class="pt24-faq-section">
        <div class="pt24-container">
            <h2 class="pt24-section-title">Najczęściej zadawane pytania</h2>

            <div class="pt24-faq-list">
                <div class="pt24-faq-item">
                    <button class="pt24-faq__question" data-faq-toggle="0">
                        <span>Czy to darmowe?</span>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="pt24-faq__answer" data-faq-content="0">
                        <p>Tak, nasz serwis jest w 100% darmowy dla użytkowników. Nie pobieramy żadnych opłat za składanie zapytań ani otrzymywanie ofert.</p>
                    </div>
                </div>

                <div class="pt24-faq-item">
                    <button class="pt24-faq__question" data-faq-toggle="1">
                        <span>Ile ofert dostanę?</span>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="pt24-faq__answer" data-faq-content="1">
                        <p>Otrzymasz do 3 dopasowanych ofert od sprawdzonych firm w Twojej okolicy. Liczba ofert zależy od dostępności wykonawców i specyfiki zapytania.</p>
                    </div>
                </div>

                <div class="pt24-faq-item">
                    <button class="pt24-faq__question" data-faq-toggle="2">
                        <span>Jak szybko otrzymam oferty?</span>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="pt24-faq__answer" data-faq-content="2">
                        <p>Często już w kilka godzin! Większość firm odpowiada w ciągu 24 godzin. Otrzymasz powiadomienie email, gdy firmy prześlą swoje oferty.</p>
                    </div>
                </div>

                <div class="pt24-faq-item">
                    <button class="pt24-faq__question" data-faq-toggle="3">
                        <span>Czy muszę wybrać jedną z ofert?</span>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="pt24-faq__answer" data-faq-content="3">
                        <p>Nie, nie masz żadnych zobowiązań. Możesz porównać oferty, negocjować warunki lub zrezygnować bez podania przyczyny.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FINAL CTA SECTION -->
    <section class="pt24-final-cta">
        <div class="pt24-container">
            <div class="pt24-final-cta__content">
                <h2 class="pt24-final-cta__title">Sprawdź dostępność i ceny teraz</h2>
                <p class="pt24-final-cta__subtitle">Wypełnij formularz i otrzymaj oferty w 24 godziny</p>
                <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                    Otrzymaj ofertę
                </a>
            </div>
        </div>
    </section>
</main>

<?php get_footer('minimal'); ?>
