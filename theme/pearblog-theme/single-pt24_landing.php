<?php
/**
 * Template for PT24 Landing Pages
 *
 * Single template for programmatically generated PT24 landing pages
 * Uses post meta for dynamic content rendering
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Get post meta
$service = get_post_meta(get_the_ID(), 'pt24_service', true);
$city = get_post_meta(get_the_ID(), 'pt24_city', true);
$service_display = get_post_meta(get_the_ID(), 'pt24_service_display', true) ?: ucfirst(str_replace('-', ' ', $service));
$city_display = get_post_meta(get_the_ID(), 'pt24_city_display', true) ?: ucfirst($city);
$h1 = get_post_meta(get_the_ID(), 'pt24_h1', true) ?: "$service_display $city_display — sprawdź ceny i dostępne firmy";
$hero_text = get_post_meta(get_the_ID(), 'pt24_hero_text', true) ?: "Znajdź najlepszych specjalistów w $city_display i otrzymaj dopasowane oferty.";

// SEO meta
$meta_title = get_post_meta(get_the_ID(), 'pt24_meta_title', true) ?: "$service_display $city_display — ceny i oferty";
$meta_description = get_post_meta(get_the_ID(), 'pt24_meta_description', true) ?: "Znajdź $service_display w $city_display. Sprawdź ceny i dostępne firmy.";

// Enqueue landing page assets
wp_enqueue_style('pt24-landing', get_template_directory_uri() . '/assets/css/pt24-landing.css', array(), '1.0.0');
wp_enqueue_script('pt24-landing', get_template_directory_uri() . '/assets/js/pt24-landing.js', array(), '1.0.0', true);

// Add SEO meta tags
add_action('wp_head', function() use ($meta_title, $meta_description) {
    echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($meta_title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($meta_description) . '">' . "\n";
    echo '<title>' . esc_html($meta_title) . ' | ' . get_bloginfo('name') . '</title>' . "\n";
}, 1);

get_header('minimal');
?>

<main class="pt24-landing">
    <!-- HERO SECTION -->
    <section class="pt24-hero">
        <div class="pt24-container">
            <div class="pt24-hero__content">
                <h1 class="pt24-hero__title">
                    <?php echo esc_html($h1); ?>
                </h1>

                <p class="pt24-hero__subtitle">
                    <?php echo esc_html($hero_text); ?>
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
                        <span>Dostępności firm</span>
                    </li>
                </ul>

                <p class="pt24-cost__cta-text">
                    Nie zgaduj — sprawdź realne oferty.
                </p>

                <a href="#pt24-form" class="pt24-btn pt24-btn--accent pt24-btn--large pt24-smooth-scroll">
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
                    <span>Skuteczności</span>
                </div>
                <div class="pt24-ranking-criterion">
                    <svg class="pt24-check-icon" width="24" height="24" viewBox="0 0 20 20" fill="none">
                        <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Dostępności</span>
                </div>
            </div>

            <div class="pt24-ranking-placeholder">
                <p>🏆 Lista najlepszych firm będzie dostępna wkrótce</p>
            </div>
        </div>
    </section>

    <!-- SECOND CTA SECTION -->
    <section class="pt24-cta-section">
        <div class="pt24-container">
            <div class="pt24-cta-card">
                <h2 class="pt24-cta__title">Nie trać czasu na szukanie</h2>
                <p class="pt24-cta__text">
                    Otrzymaj oferty i wybierz najlepszą opcję.
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
                    <button class="pt24-faq__question" data-faq-toggle="0" aria-expanded="false">
                        <span>Czy to darmowe?</span>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="pt24-faq__answer" data-faq-content="0">
                        <p>Tak — korzystanie jest bezpłatne.</p>
                    </div>
                </div>

                <div class="pt24-faq-item">
                    <button class="pt24-faq__question" data-faq-toggle="1" aria-expanded="false">
                        <span>Ile ofert otrzymam?</span>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="pt24-faq__answer" data-faq-content="1">
                        <p>Do 3 dopasowanych propozycji.</p>
                    </div>
                </div>

                <div class="pt24-faq-item">
                    <button class="pt24-faq__question" data-faq-toggle="2" aria-expanded="false">
                        <span>Jak szybko dostanę odpowiedź?</span>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="pt24-faq__answer" data-faq-content="2">
                        <p>Często w ciągu kilku godzin.</p>
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
                <p class="pt24-final-cta__subtitle">Wypełnij formularz i otrzymaj oferty</p>
                <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                    Otrzymaj ofertę
                </a>
            </div>
        </div>
    </section>
</main>

<?php get_footer('minimal'); ?>
