<?php
/**
 * Template Name: Poradnik.pro Landing V5
 *
 * High-conversion landing page for Poradnik.pro
 * Features: Hero with CTA, social proof, features, testimonials, FAQ, lead forms
 *
 * @package PearBlog
 * @version 5.0.0
 */

get_header('minimal'); // Use minimal header for landing pages
?>

<main class="poradnik-landing-v5">
    <!-- Hero Section with Video Background -->
    <section class="plv5-hero">
        <div class="plv5-hero__bg-video">
            <div class="plv5-hero__bg-overlay"></div>
        </div>

        <div class="pb-container">
            <div class="plv5-hero__content">
                <!-- Value Proposition -->
                <div class="plv5-hero__badge">
                    🚀 Ponad 50,000 zadowolonych użytkowników
                </div>

                <h1 class="plv5-hero__title">
                    <?php echo esc_html(get_option('plv5_hero_title', 'Znajdź idealnego wykonawcę w 60 sekund')); ?>
                </h1>

                <p class="plv5-hero__subtitle">
                    <?php echo esc_html(get_option('plv5_hero_subtitle', 'Porównaj oferty, sprawdź opinie i podejmij najlepszą decyzję. Za darmo i bez zobowiązań.')); ?>
                </p>

                <!-- Lead Capture Form -->
                <div class="plv5-hero__form">
                    <form id="plv5HeroForm" class="plv5-form">
                        <div class="plv5-form__row">
                            <input
                                type="text"
                                name="service"
                                class="plv5-form__input plv5-form__input--large"
                                placeholder="Czego potrzebujesz? np. 'remont łazienki', 'kredyt hipoteczny'..."
                                required
                            >
                            <button type="submit" class="plv5-btn plv5-btn--primary plv5-btn--large">
                                <span>Znajdź ekspertów</span>
                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </button>
                        </div>
                        <p class="plv5-form__privacy">
                            🔒 Twoje dane są bezpieczne. Sprawdź <a href="/polityka-prywatnosci/">politykę prywatności</a>
                        </p>
                    </form>
                </div>

                <!-- Trust Signals -->
                <div class="plv5-hero__trust">
                    <div class="plv5-trust-item">
                        <span class="plv5-trust-item__icon">✓</span>
                        <span class="plv5-trust-item__text">100% darmowe</span>
                    </div>
                    <div class="plv5-trust-item">
                        <span class="plv5-trust-item__icon">✓</span>
                        <span class="plv5-trust-item__text">Bez zobowiązań</span>
                    </div>
                    <div class="plv5-trust-item">
                        <span class="plv5-trust-item__icon">✓</span>
                        <span class="plv5-trust-item__text">Weryfikowani eksperci</span>
                    </div>
                    <div class="plv5-trust-item">
                        <span class="plv5-trust-item__icon">✓</span>
                        <span class="plv5-trust-item__text">Odpowiedź w 24h</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="plv5-hero__scroll">
            <span>Przewiń w dół</span>
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
        </div>
    </section>

    <!-- Social Proof - Logos -->
    <section class="plv5-social-proof">
        <div class="pb-container">
            <p class="plv5-social-proof__title">Zaufali nam:</p>
            <div class="plv5-social-proof__logos">
                <?php
                $logos = get_option('plv5_partner_logos', [
                    ['name' => 'Forbes', 'url' => '#'],
                    ['name' => 'TVN', 'url' => '#'],
                    ['name' => 'Rzeczpospolita', 'url' => '#'],
                    ['name' => 'Gazeta Wyborcza', 'url' => '#'],
                ]);

                foreach ($logos as $logo):
                ?>
                    <div class="plv5-social-proof__logo">
                        <span><?php echo esc_html($logo['name']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="plv5-how-it-works">
        <div class="pb-container">
            <h2 class="plv5-section-title">Jak to działa?</h2>
            <p class="plv5-section-subtitle">Trzy proste kroki do idealnego wykonawcy</p>

            <div class="plv5-steps">
                <div class="plv5-step">
                    <div class="plv5-step__number">1</div>
                    <div class="plv5-step__icon">📝</div>
                    <h3 class="plv5-step__title">Opisz potrzebę</h3>
                    <p class="plv5-step__description">
                        Wypełnij krótki formularz. To zajmuje 60 sekund. Powiedz nam czego potrzebujesz i jakie masz oczekiwania.
                    </p>
                </div>

                <div class="plv5-step">
                    <div class="plv5-step__number">2</div>
                    <div class="plv5-step__icon">🔍</div>
                    <h3 class="plv5-step__title">Otrzymaj oferty</h3>
                    <p class="plv5-step__description">
                        Nasi zweryfikowani eksperci otrzymają Twoje zapytanie. Dostaniesz do 5 bezpłatnych ofert w 24 godziny.
                    </p>
                </div>

                <div class="plv5-step">
                    <div class="plv5-step__number">3</div>
                    <div class="plv5-step__icon">✨</div>
                    <h3 class="plv5-step__title">Wybierz najlepszego</h3>
                    <p class="plv5-step__description">
                        Porównaj oferty, sprawdź opinie i oceny. Wybierz wykonawcę który najlepiej spełnia Twoje wymagania.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="plv5-features">
        <div class="pb-container">
            <h2 class="plv5-section-title">Dlaczego Poradnik.pro?</h2>
            <p class="plv5-section-subtitle">Twoja przewaga w poszukiwaniu idealnego wykonawcy</p>

            <div class="plv5-features-grid">
                <div class="plv5-feature">
                    <div class="plv5-feature__icon">🛡️</div>
                    <h3 class="plv5-feature__title">Weryfikowani eksperci</h3>
                    <p class="plv5-feature__description">
                        Każdy wykonawca przechodzi weryfikację. Sprawdzamy dokumenty, uprawnienia i historię realizacji.
                    </p>
                </div>

                <div class="plv5-feature">
                    <div class="plv5-feature__icon">⭐</div>
                    <h3 class="plv5-feature__title">Prawdziwe opinie</h3>
                    <p class="plv5-feature__description">
                        Tylko zweryfikowane opinie od prawdziwych klientów. Bez fałszywych komentarzy i manipulacji.
                    </p>
                </div>

                <div class="plv5-feature">
                    <div class="plv5-feature__icon">💰</div>
                    <h3 class="plv5-feature__title">Oszczędzasz czas i pieniądze</h3>
                    <p class="plv5-feature__description">
                        Porównaj ceny w jednym miejscu. Negocjuj najlepsze warunki. Średnio oszczędzasz 20% budżetu.
                    </p>
                </div>

                <div class="plv5-feature">
                    <div class="plv5-feature__icon">📊</div>
                    <h3 class="plv5-feature__title">Inteligentne dopasowanie</h3>
                    <p class="plv5-feature__description">
                        AI analizuje Twoje potrzeby i automatycznie znajduje najlepszych wykonawców w Twojej okolicy.
                    </p>
                </div>

                <div class="plv5-feature">
                    <div class="plv5-feature__icon">🔒</div>
                    <h3 class="plv5-feature__title">Bezpieczne płatności</h3>
                    <p class="plv5-feature__description">
                        System depozytowy chroni Twoje środki. Płacisz tylko po zrealizowaniu usługi według umowy.
                    </p>
                </div>

                <div class="plv5-feature">
                    <div class="plv5-feature__icon">🎯</div>
                    <h3 class="plv5-feature__title">Wsparcie 24/7</h3>
                    <p class="plv5-feature__description">
                        Nasz zespół jest zawsze gotowy pomóc. Chat, telefon, email - wybierz wygodną formę kontaktu.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="plv5-stats">
        <div class="pb-container">
            <div class="plv5-stats-grid">
                <div class="plv5-stat">
                    <div class="plv5-stat__number" data-count="50000">0</div>
                    <div class="plv5-stat__label">Zadowolonych klientów</div>
                </div>

                <div class="plv5-stat">
                    <div class="plv5-stat__number" data-count="5000">0</div>
                    <div class="plv5-stat__label">Zweryfikowanych ekspertów</div>
                </div>

                <div class="plv5-stat">
                    <div class="plv5-stat__number" data-count="100000">0</div>
                    <div class="plv5-stat__label">Zrealizowanych projektów</div>
                </div>

                <div class="plv5-stat">
                    <div class="plv5-stat__number" data-count="4.8">0</div>
                    <div class="plv5-stat__label">Średnia ocena (z 5)</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="plv5-testimonials">
        <div class="pb-container">
            <h2 class="plv5-section-title">Co mówią nasi klienci?</h2>
            <p class="plv5-section-subtitle">Prawdziwe historie, prawdziwe opinie</p>

            <div class="plv5-testimonials-grid">
                <?php
                $testimonials = get_option('plv5_testimonials', [
                    [
                        'name' => 'Anna Kowalska',
                        'role' => 'Warszawa',
                        'avatar' => '👩',
                        'rating' => 5,
                        'text' => 'Szukałam firm remontowych przez miesiące. Tutaj w 2 dni miałam 4 oferty! Wybrałam najlepszą i zaoszczędziłam 15,000 zł. Polecam!'
                    ],
                    [
                        'name' => 'Piotr Nowak',
                        'role' => 'Kraków',
                        'avatar' => '👨',
                        'rating' => 5,
                        'text' => 'Potrzebowałem kredytu hipotecznego. System dopasował mi 3 najlepsze oferty bankowe. Załatwione w tydzień, bez wychodzenia z domu!'
                    ],
                    [
                        'name' => 'Katarzyna Wiśniewska',
                        'role' => 'Gdańsk',
                        'avatar' => '👩‍💼',
                        'rating' => 5,
                        'text' => 'Rewelacja! Znalazłam firmę sprzątającą w 30 minut. Sprawdzone opinie, jasne ceny. Współpracujemy już rok i jestem bardzo zadowolona.'
                    ]
                ]);

                foreach ($testimonials as $testimonial):
                ?>
                    <div class="plv5-testimonial">
                        <div class="plv5-testimonial__rating">
                            <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                ⭐
                            <?php endfor; ?>
                        </div>
                        <p class="plv5-testimonial__text">
                            "<?php echo esc_html($testimonial['text']); ?>"
                        </p>
                        <div class="plv5-testimonial__author">
                            <div class="plv5-testimonial__avatar">
                                <?php echo $testimonial['avatar']; ?>
                            </div>
                            <div>
                                <div class="plv5-testimonial__name">
                                    <?php echo esc_html($testimonial['name']); ?>
                                </div>
                                <div class="plv5-testimonial__role">
                                    <?php echo esc_html($testimonial['role']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="plv5-cta">
        <div class="pb-container">
            <div class="plv5-cta__content">
                <h2 class="plv5-cta__title">Gotowy na najlepszą ofertę?</h2>
                <p class="plv5-cta__subtitle">Rozpocznij za darmo. Bez zobowiązań. Odpowiedź w 24h.</p>

                <form id="plv5CtaForm" class="plv5-form plv5-form--cta">
                    <div class="plv5-form__row">
                        <input
                            type="email"
                            name="email"
                            class="plv5-form__input plv5-form__input--large"
                            placeholder="Twój email"
                            required
                        >
                        <button type="submit" class="plv5-btn plv5-btn--secondary plv5-btn--large">
                            Rozpocznij teraz
                        </button>
                    </div>
                </form>

                <p class="plv5-cta__note">
                    Dołącz do 50,000+ zadowolonych użytkowników
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="plv5-faq">
        <div class="pb-container">
            <h2 class="plv5-section-title">Najczęściej zadawane pytania</h2>

            <div class="plv5-faq-list">
                <?php
                $faqs = get_option('plv5_faqs', [
                    [
                        'question' => 'Czy korzystanie z platformy jest darmowe?',
                        'answer' => 'Tak! Korzystanie z Poradnik.pro jest w 100% darmowe dla klientów. Nie pobieramy żadnych opłat ani prowizji. Zarabiamy tylko na subskrypcjach dla firm.'
                    ],
                    [
                        'question' => 'Jak długo trwa otrzymanie ofert?',
                        'answer' => 'Większość użytkowników otrzymuje pierwsze oferty w ciągu 2-4 godzin. Gwarantujemy odpowiedź w maksymalnie 24 godziny.'
                    ],
                    [
                        'question' => 'Czy muszę wybrać jedną z otrzymanych ofert?',
                        'answer' => 'Absolutnie nie! Nie masz żadnych zobowiązań. Możesz porównać oferty, negocjować warunki lub zrezygnować bez podania przyczyny.'
                    ],
                    [
                        'question' => 'Skąd wiem, że firmy są zweryfikowane?',
                        'answer' => 'Każda firma przechodzi proces weryfikacji: sprawdzamy NIP, dokumenty, ubezpieczenia i historię współpracy. Tylko 30% aplikacji jest akceptowanych.'
                    ],
                    [
                        'question' => 'Co jeśli nie będę zadowolony z usługi?',
                        'answer' => 'Masz pełne wsparcie naszego zespołu. Pomożemy rozwiązać każdy problem. Oferujemy też system depozytowy - płacisz dopiero po realizacji.'
                    ]
                ]);

                foreach ($faqs as $index => $faq):
                ?>
                    <div class="plv5-faq-item">
                        <button class="plv5-faq-item__question" data-faq-toggle="<?php echo $index; ?>">
                            <span><?php echo esc_html($faq['question']); ?></span>
                            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="plv5-faq-item__answer" data-faq-content="<?php echo $index; ?>">
                            <p><?php echo esc_html($faq['answer']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Final CTA Banner -->
    <section class="plv5-final-cta">
        <div class="pb-container">
            <h2>Nie czekaj. Znajdź swojego wykonawcę już dziś!</h2>
            <a href="#plv5HeroForm" class="plv5-btn plv5-btn--primary plv5-btn--large plv5-smooth-scroll">
                Rozpocznij za darmo
            </a>
        </div>
    </section>
</main>

<?php
get_footer('minimal'); // Use minimal footer for landing pages
?>
