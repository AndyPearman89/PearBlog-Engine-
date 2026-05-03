<?php
/**
 * Template for PT24 Ranking Pages
 *
 * Displays ranked list of service providers for city/service combination
 * URL structure: /ranking/{city}/{service}
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Get post meta
$service = get_post_meta(get_the_ID(), 'pt24_service', true);
$city = get_post_meta(get_the_ID(), 'pt24_city', true);
$service_display = get_post_meta(get_the_ID(), 'pt24_service_display', true) ?: ucfirst(str_replace('-', ' ', $service));
$city_display = get_post_meta(get_the_ID(), 'pt24_city_display', true) ?: ucfirst($city);

// SEO meta
$meta_title = "Najlepsi $service_display w $city_display — ranking i opinie";
$meta_description = "Ranking najlepszych $service_display w $city_display. Porównaj opinie, ceny i dostępność. Sprawdzone firmy z oceną klientów.";

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

<main class="pt24-landing pt24-ranking">
    <!-- HERO SECTION -->
    <section class="pt24-hero">
        <div class="pt24-container">
            <div class="pt24-hero__content">
                <h1 class="pt24-hero__title">
                    Najlepsi <?php echo esc_html($service_display); ?> w <?php echo esc_html($city_display); ?>
                </h1>

                <p class="pt24-hero__subtitle">
                    Sprawdzone firmy z opiniami i oceną klientów. Porównaj i wybierz najlepszą.
                </p>

                <a href="#ranking-list" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                    Zobacz ranking
                </a>
            </div>
        </div>
    </section>

    <!-- TOP 3 FEATURED SECTION -->
    <section class="pt24-top3-section" id="ranking-list">
        <div class="pt24-container">
            <h2 class="pt24-section-title">Top 3 — Najlepszy Wybór</h2>

            <div class="pt24-top3-grid">
                <!-- #1 TOP WYBÓR -->
                <div class="pt24-ranking-card pt24-ranking-card--premium-plus">
                    <div class="pt24-ranking-badge pt24-badge--top">
                        ⭐ TOP WYBÓR
                    </div>
                    <div class="pt24-ranking-number">#1</div>
                    <h3 class="pt24-ranking-name">Najlepsza Firma <?php echo esc_html($service_display); ?></h3>
                    <div class="pt24-ranking-rating">
                        <span class="pt24-stars">⭐⭐⭐⭐⭐</span>
                        <span class="pt24-rating-value">4.9</span>
                        <span class="pt24-rating-count">(127 opinii)</span>
                    </div>
                    <div class="pt24-ranking-features">
                        <div class="pt24-feature">✓ Dostępny dziś</div>
                        <div class="pt24-feature">✓ Gwarancja 2 lata</div>
                        <div class="pt24-feature">✓ Bezpłatny dojazd</div>
                    </div>
                    <div class="pt24-ranking-actions">
                        <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-smooth-scroll">
                            Zapytaj o wycenę
                        </a>
                        <a href="#" class="pt24-btn pt24-btn--secondary">
                            Zobacz profil
                        </a>
                    </div>
                </div>

                <!-- #2 POLECANY -->
                <div class="pt24-ranking-card pt24-ranking-card--premium">
                    <div class="pt24-ranking-badge pt24-badge--recommended">
                        ✔ POLECANY
                    </div>
                    <div class="pt24-ranking-number">#2</div>
                    <h3 class="pt24-ranking-name">Sprawdzona Firma <?php echo esc_html($service_display); ?></h3>
                    <div class="pt24-ranking-rating">
                        <span class="pt24-stars">⭐⭐⭐⭐</span>
                        <span class="pt24-rating-value">4.7</span>
                        <span class="pt24-rating-count">(89 opinii)</span>
                    </div>
                    <div class="pt24-ranking-features">
                        <div class="pt24-feature">✓ Dostępny jutro</div>
                        <div class="pt24-feature">✓ Gwarancja 1 rok</div>
                        <div class="pt24-feature">✓ Darmowa wycena</div>
                    </div>
                    <div class="pt24-ranking-actions">
                        <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-smooth-scroll">
                            Zapytaj
                        </a>
                        <a href="#" class="pt24-btn pt24-btn--secondary">
                            Profil
                        </a>
                    </div>
                </div>

                <!-- #3 POLECANY -->
                <div class="pt24-ranking-card pt24-ranking-card--premium">
                    <div class="pt24-ranking-badge pt24-badge--recommended">
                        ✔ POLECANY
                    </div>
                    <div class="pt24-ranking-number">#3</div>
                    <h3 class="pt24-ranking-name">Zaufana Firma <?php echo esc_html($service_display); ?></h3>
                    <div class="pt24-ranking-rating">
                        <span class="pt24-stars">⭐⭐⭐⭐</span>
                        <span class="pt24-rating-value">4.6</span>
                        <span class="pt24-rating-count">(76 opinii)</span>
                    </div>
                    <div class="pt24-ranking-features">
                        <div class="pt24-feature">✓ Dostępny w tym tygodniu</div>
                        <div class="pt24-feature">✓ Konkurencyjne ceny</div>
                        <div class="pt24-feature">✓ Szybka realizacja</div>
                    </div>
                    <div class="pt24-ranking-actions">
                        <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-smooth-scroll">
                            Zapytaj
                        </a>
                        <a href="#" class="pt24-btn pt24-btn--secondary">
                            Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STICKY CTA BAR -->
    <div class="pt24-sticky-cta">
        <div class="pt24-container">
            <div class="pt24-sticky-content">
                <span class="pt24-sticky-text">Chcesz otrzymać oferty?</span>
                <a href="#pt24-form" class="pt24-btn pt24-btn--accent pt24-smooth-scroll">
                    Otrzymaj 3 oferty
                </a>
            </div>
        </div>
    </div>

    <!-- FULL RANKING LIST -->
    <section class="pt24-full-ranking">
        <div class="pt24-container">
            <h2 class="pt24-section-title">Wszystkie firmy w <?php echo esc_html($city_display); ?></h2>
            <p class="pt24-section-subtitle">Pełna lista sprawdzonych <?php echo esc_html($service_display); ?></p>

            <div class="pt24-ranking-list">
                <?php for ($i = 4; $i <= 10; $i++): ?>
                <div class="pt24-ranking-item">
                    <div class="pt24-ranking-number-small">#<?php echo $i; ?></div>
                    <div class="pt24-ranking-info">
                        <h3 class="pt24-ranking-name-small">Firma <?php echo esc_html($service_display); ?> #<?php echo $i; ?></h3>
                        <div class="pt24-ranking-rating-small">
                            <span class="pt24-stars-small">⭐⭐⭐⭐</span>
                            <span class="pt24-rating-value-small">4.<?php echo 5 - ($i - 4); ?></span>
                            <span class="pt24-rating-count-small">(<?php echo rand(30, 70); ?> opinii)</span>
                        </div>
                    </div>
                    <div class="pt24-ranking-actions-small">
                        <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-btn--small pt24-smooth-scroll">
                            Zapytaj
                        </a>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="pt24-ranking-note">
                <p>💡 Ranking aktualizowany codziennie na podstawie opinii klientów</p>
            </div>
        </div>
    </section>

    <!-- LEAD FORM SECTION -->
    <section class="pt24-form-section" id="pt24-form">
        <div class="pt24-container">
            <div class="pt24-form-card">
                <h2 class="pt24-form__title">Otrzymaj oferty od najlepszych firm</h2>
                <p class="pt24-form__subtitle">Wypełnij formularz i otrzymaj do 3 ofert w 24h</p>

                <form id="pt24LeadForm" class="pt24-form" method="post">
                    <?php wp_nonce_field('pt24_lead_submit', 'pt24_nonce'); ?>

                    <input type="hidden" name="action" value="pt24_submit_lead">
                    <input type="hidden" name="service" value="<?php echo esc_attr($service); ?>">
                    <input type="hidden" name="city" value="<?php echo esc_attr($city); ?>">
                    <input type="hidden" name="source_url" value="<?php echo esc_url($_SERVER['REQUEST_URI'] ?? ''); ?>">

                    <div class="pt24-form__field">
                        <label for="pt24_service_need" class="pt24-form__label">Czego potrzebujesz?</label>
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
                            <span>Zgadzam się na przetwarzanie danych w celu otrzymania ofert</span>
                        </label>
                    </div>

                    <button type="submit" class="pt24-btn pt24-btn--secondary pt24-btn--large pt24-btn--full">
                        Wyślij zapytanie
                    </button>

                    <p class="pt24-form__note">
                        Otrzymasz do 3 ofert w 24h. Bez zobowiązań.
                    </p>
                </form>
            </div>
        </div>
    </section>

    <!-- COST SECTION -->
    <section class="pt24-cost-section">
        <div class="pt24-container">
            <div class="pt24-cost-card">
                <h2 class="pt24-cost__title">
                    Ile kosztuje <?php echo esc_html($service_display); ?> w <?php echo esc_html($city_display); ?>?
                </h2>

                <p class="pt24-cost__intro">Ceny zależą od zakresu usługi i lokalizacji</p>

                <div class="pt24-cost__cta-text">
                    <p>Porównaj oferty od najlepszych firm i wybierz najkorzystniejszą cenę.</p>
                </div>

                <a href="#pt24-form" class="pt24-btn pt24-btn--accent pt24-btn--large pt24-smooth-scroll">
                    Sprawdź ceny
                </a>
            </div>
        </div>
    </section>

    <!-- FINAL CTA SECTION -->
    <section class="pt24-final-cta">
        <div class="pt24-container">
            <div class="pt24-final-cta__content">
                <h2 class="pt24-final-cta__title">Gotowy na oferty?</h2>
                <p class="pt24-final-cta__subtitle">Wypełnij formularz i otrzymaj do 3 spersonalizowanych ofert</p>
                <a href="#pt24-form" class="pt24-btn pt24-btn--primary pt24-btn--large pt24-smooth-scroll">
                    Otrzymaj ofertę
                </a>
            </div>
        </div>
    </section>
</main>

<style>
/* Additional styles for ranking page */
.pt24-top3-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

@media (min-width: 768px) {
    .pt24-top3-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.pt24-ranking-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    position: relative;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
}

.pt24-ranking-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
}

.pt24-ranking-card--premium-plus {
    border: 3px solid #f59e0b;
}

.pt24-ranking-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.875rem;
}

.pt24-badge--top {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.pt24-badge--recommended {
    background: #10b981;
    color: white;
}

.pt24-ranking-number {
    font-size: 3rem;
    font-weight: 800;
    color: #e5e7eb;
    text-align: center;
    margin-bottom: 1rem;
}

.pt24-ranking-name {
    font-size: 1.25rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 1rem;
}

.pt24-ranking-rating {
    text-align: center;
    margin-bottom: 1.5rem;
}

.pt24-stars {
    font-size: 1.25rem;
}

.pt24-rating-value {
    font-weight: 700;
    margin-left: 0.5rem;
}

.pt24-ranking-features {
    margin-bottom: 1.5rem;
}

.pt24-feature {
    padding: 0.5rem 0;
    color: #10b981;
    font-weight: 600;
}

.pt24-ranking-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.pt24-sticky-cta {
    position: sticky;
    top: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 0;
    z-index: 100;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.pt24-sticky-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.pt24-sticky-text {
    font-weight: 600;
}

.pt24-ranking-list {
    margin-top: 2rem;
}

.pt24-ranking-item {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.pt24-ranking-number-small {
    font-size: 1.5rem;
    font-weight: 700;
    color: #9ca3af;
    min-width: 50px;
}

.pt24-ranking-info {
    flex: 1;
}

.pt24-ranking-name-small {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.pt24-ranking-rating-small {
    font-size: 0.875rem;
}

.pt24-ranking-note {
    text-align: center;
    margin-top: 2rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
}
</style>

<?php get_footer('minimal'); ?>
