<?php
/**
 * Template Name: Poradnik.pro Landing V5
 *
 * Full adaptive conversion landing for Poradnik.pro
 * Decision Engine Platform — Tagline System V5
 *
 * @package PearBlog
 * @version 5.3.0
 */

$ab_param = isset($_GET['ab']) ? sanitize_key(wp_unslash($_GET['ab'])) : '';
$ab_cookie = isset($_COOKIE['plv5_ab']) ? sanitize_key(wp_unslash($_COOKIE['plv5_ab'])) : '';

if (in_array($ab_param, ['a', 'b'], true)) {
    $ab_variant = $ab_param;
} elseif (in_array($ab_cookie, ['a', 'b'], true)) {
    $ab_variant = $ab_cookie;
} else {
    $ab_variant = mt_rand(0, 1) ? 'a' : 'b';
}

if (!headers_sent()) {
    setcookie('plv5_ab', $ab_variant, time() + (30 * DAY_IN_SECONDS), '/');
}

$industry_param = isset($_GET['industry']) ? sanitize_key(wp_unslash($_GET['industry'])) : '';
$industry_param_alt = isset($_GET['plv5_industry']) ? sanitize_key(wp_unslash($_GET['plv5_industry'])) : '';
$industry_candidate = $industry_param ?: $industry_param_alt;

$industry_copy_map = [
    'general' => [
        'hero_title' => 'Od problemu do decyzji.',
        'hero_subtitle' => 'Porównania, rankingi, koszty i specjaliści w jednym miejscu.',
        'hero_placeholder' => 'np. koszt remontu łazienki, pompa ciepła czy gaz, dobry prawnik Katowice',
        'cta_placeholder' => 'Np. ile kosztuje budowa domu, najlepszy hydraulik Kraków',
        'panel_title' => 'Co dostajesz na Poradnik.pro?',
        'pills' => ['+100 000 porad', '+20 000 specjalistów', '+500 000 decyzji miesięcznie'],
    ],
    'budownictwo' => [
        'hero_title' => 'Znajdź solidną ekipę budowlaną bez przepłacania',
        'hero_subtitle' => 'Remont, wykończenie, instalacje lub budowa domu. Otrzymasz konkretne oferty od zweryfikowanych wykonawców w Twojej okolicy.',
        'hero_placeholder' => 'Np. remont mieszkania 55 m2, elewacja domu, instalacja elektryczna',
        'cta_placeholder' => 'Np. stan deweloperski domu 120 m2, wykończenie pod klucz',
        'panel_title' => 'Co zyskasz przy inwestycji budowlanej?',
        'pills' => ['Zweryfikowane ekipy', 'Porównanie cen i terminów', 'Mniej ryzyka inwestycji'],
    ],
    'finanse' => [
        'hero_title' => 'Podejmij mądrą decyzję finansową na bazie ofert',
        'hero_subtitle' => 'Kredyt, leasing, refinansowanie i doradztwo. Zbieramy dla Ciebie porównywalne propozycje, żebyś wybrał najlepszy wariant.',
        'hero_placeholder' => 'Np. kredyt hipoteczny 600 tys., konsolidacja zobowiązań, leasing auta',
        'cta_placeholder' => 'Np. refinansowanie kredytu, kredyt firmowy, doradca inwestycyjny',
        'panel_title' => 'Co dostajesz przy tematach finansowych?',
        'pills' => ['Porównywalne oferty', 'Jasne warunki', 'Szybsza decyzja'],
    ],
    'prawo' => [
        'hero_title' => 'Znajdź prawnika dopasowanego do sprawy i budżetu',
        'hero_subtitle' => 'Sprawy cywilne, rodzinne, gospodarcze i administracyjne. Otrzymasz oferty od specjalistów z odpowiednią praktyką.',
        'hero_placeholder' => 'Np. rozwód, umowa B2B, odszkodowanie, spór z deweloperem',
        'cta_placeholder' => 'Np. sprawa karna, analiza umowy, reprezentacja w sądzie',
        'panel_title' => 'Co zyskasz przy sprawach prawnych?',
        'pills' => ['Specjaliści od konkretnych spraw', 'Transparentne warunki', 'Szybki kontakt'],
    ],
    'oze' => [
        'hero_title' => 'Porównaj oferty OZE i wybierz opłacalną inwestycję',
        'hero_subtitle' => 'Fotowoltaika, pompy ciepła, magazyny energii. Otrzymasz wyceny i terminy od sprawdzonych instalatorów.',
        'hero_placeholder' => 'Np. fotowoltaika 10 kWp, pompa ciepła do domu 140 m2',
        'cta_placeholder' => 'Np. magazyn energii + PV, audyt efektywności energetycznej',
        'panel_title' => 'Co dostajesz przy inwestycjach OZE?',
        'pills' => ['Sprawdzeni instalatorzy', 'Konkretne wyceny', 'Lepsza opłacalność'],
    ],
];

$industry_key = array_key_exists($industry_candidate, $industry_copy_map) ? $industry_candidate : 'general';
$industry_copy = $industry_copy_map[$industry_key];

$ab_copy = [
    'a' => [
        'kicker' => 'Decision Engine Platform',
        'hero_button' => '🔎 Znajdź rozwiązanie',
        'cta_kicker' => 'Zamknij research dziś',
        'cta_title' => 'Opisz problem. Znajdziemy rozwiązanie.',
        'cta_subtitle' => 'Porównania, rankingi i eksperci w jednym miejscu — bez chaosu i godzin szukania.',
        'cta_button' => '🔎 Znajdź rozwiązanie',
        'mobile_cta' => 'Znajdź rozwiązanie',
    ],
    'b' => [
        'kicker' => 'Tu kończy się research.',
        'hero_button' => '🔎 Znajdź rozwiązanie',
        'cta_kicker' => 'Decyduj teraz',
        'cta_title' => 'Tu kończy się research. Zaczyna się działanie.',
        'cta_subtitle' => 'Poradniki, porównania, kalkulatory i eksperci — w jednym systemie. Decyduj w minutach.',
        'cta_button' => '🔎 Znajdź rozwiązanie',
        'mobile_cta' => 'Decyduj teraz',
    ],
];

$variant_copy = $ab_copy[$ab_variant];

$hero_title = get_option('plv5_hero_title', $industry_copy['hero_title']);
$hero_subtitle = get_option('plv5_hero_subtitle', $industry_copy['hero_subtitle']);

get_header('minimal');
?>

<main class="poradnik-landing-v5" data-ab-variant="<?php echo esc_attr($ab_variant); ?>" data-industry="<?php echo esc_attr($industry_key); ?>" data-landing-version="5.3.0">
    <section class="plv5-hero" id="top">
        <div class="plv5-hero__noise" aria-hidden="true"></div>
        <div class="pb-container plv5-grid">
            <div class="plv5-hero__content" data-reveal>
                <p class="plv5-kicker"><?php echo esc_html($variant_copy['kicker']); ?></p>
                <h1 class="plv5-hero__title"><?php echo esc_html($hero_title); ?></h1>
                <p class="plv5-hero__subtitle"><?php echo esc_html($hero_subtitle); ?></p>

                <div class="plv5-pill-row" aria-label="Najważniejsze korzyści">
                    <?php foreach ($industry_copy['pills'] as $pill): ?>
                        <span class="plv5-pill"><?php echo esc_html($pill); ?></span>
                    <?php endforeach; ?>
                </div>

                <form id="plv5HeroForm" class="plv5-form" novalidate>
                    <input type="hidden" name="ab_variant" value="<?php echo esc_attr($ab_variant); ?>">
                    <input type="hidden" name="industry" value="<?php echo esc_attr($industry_key); ?>">
                    <input type="hidden" name="landing_version" value="5.3.0">

                    <label class="plv5-form__label" for="plv5-service">Czego szukasz lub jaki masz problem?</label>
                    <div class="plv5-form__row">
                        <input
                            id="plv5-service"
                            type="text"
                            name="service"
                            class="plv5-form__input"
                            placeholder="<?php echo esc_attr($industry_copy['hero_placeholder']); ?>"
                            required
                        >
                        <button type="submit" class="plv5-btn plv5-btn--primary">
                            <?php echo esc_html($variant_copy['hero_button']); ?>
                        </button>
                    </div>

                    <label class="plv5-form__label" for="plv5-email">Email do kontaktu (opcjonalnie, ale przyspiesza odpowiedź)</label>
                    <div class="plv5-form__row plv5-form__row--compact">
                        <input
                            id="plv5-email"
                            type="email"
                            name="email"
                            class="plv5-form__input"
                            placeholder="twoj@email.pl"
                        >
                    </div>

                    <p class="plv5-form__privacy">
                        Wysyłając formularz akceptujesz <a href="/polityka-prywatnosci/">politykę prywatności</a>.
                    </p>
                </form>
            </div>

            <aside class="plv5-hero__panel" data-reveal>
                <h2><?php echo esc_html($industry_copy['panel_title']); ?></h2>
                <ul class="plv5-checklist">
                    <li>Tłumaczymy problem i porównujemy opcje</li>
                    <li>Pokazujemy realne koszty przed decyzją</li>
                    <li>Łączymy z właściwym specjalistą w Twojej okolicy</li>
                    <li>Prowadzenie od problemu do gotowego działania</li>
                </ul>

                <div class="plv5-mini-stats">
                    <div>
                        <strong class="plv5-stat__number" data-count="100000">0</strong>
                        <span>porad</span>
                    </div>
                    <div>
                        <strong class="plv5-stat__number" data-count="20000">0</strong>
                        <span>specjalistów</span>
                    </div>
                    <div>
                        <strong class="plv5-stat__number" data-count="500000">0</strong>
                        <span>decyzji miesięcznie</span>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="plv5-trust-strip plv5-trust-bar-v2" data-reveal>
        <div class="pb-container plv5-trust-bar__grid">
            <div class="plv5-trust-bar__item">
                <strong class="plv5-trust-bar__number">+100 000</strong>
                <span class="plv5-trust-bar__label">porad</span>
            </div>
            <div class="plv5-trust-bar__sep" aria-hidden="true"></div>
            <div class="plv5-trust-bar__item">
                <strong class="plv5-trust-bar__number">+20 000</strong>
                <span class="plv5-trust-bar__label">specjalistów</span>
            </div>
            <div class="plv5-trust-bar__sep" aria-hidden="true"></div>
            <div class="plv5-trust-bar__item">
                <strong class="plv5-trust-bar__number">+500 000</strong>
                <span class="plv5-trust-bar__label">decyzji miesięcznie</span>
            </div>
        </div>
    </section>

    <section class="plv5-how" id="jak-to-dziala">
        <div class="pb-container">
            <div class="plv5-section-head" data-reveal>
                <p class="plv5-kicker">Content &rarr; Decision &rarr; Lead</p>
                <h2>Jak działa Poradnik.pro?</h2>
                <p>Od wyszukiwania do wykonania — bez chaosu i godzin szukania.</p>
            </div>

            <div class="plv5-steps">
                <article class="plv5-step" data-reveal>
                    <span class="plv5-step__index">01</span>
                    <h3>Zrozum temat</h3>
                    <p>Wpadasz z SEO lub wyszukiwania. Dostajesz analizę, porównanie i rankingi — bez chaosu.</p>
                </article>
                <article class="plv5-step" data-reveal>
                    <span class="plv5-step__index">02</span>
                    <h3>Porównaj i policz</h3>
                    <p>Przeglądasz opcje, widzisz koszty w kalkulatorach i wybierasz najlepsze rozwiązanie.</p>
                </article>
                <article class="plv5-step" data-reveal>
                    <span class="plv5-step__index">03</span>
                    <h3>Działaj ze specjalistą</h3>
                    <p>Decyzja to początek. Wysyłasz lead, specjalista kontaktuje się z Tobą. Czas na realizację.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- ================================================== -->
    <!-- CONTENT CATEGORIES — Conversion Headlines V5       -->
    <!-- ================================================== -->
    <section class="plv5-categories" id="kategorie">
        <div class="pb-container">
            <div class="plv5-section-head" data-reveal>
                <p class="plv5-kicker">Platforma decyzji</p>
                <h2>Wszystko, czego potrzebujesz do decyzji</h2>
            </div>

            <div class="plv5-cat-grid">
                <article class="plv5-cat-card" data-reveal>
                    <span class="plv5-cat-card__icon" aria-hidden="true">📖</span>
                    <h3>Poradniki</h3>
                    <p class="plv5-cat-card__headline">Zrozum temat zanim wydasz pieniądze.</p>
                    <a href="<?php echo esc_url(home_url('/poradniki/')); ?>" class="plv5-cat-card__link">Przeglądaj poradniki</a>
                </article>
                <article class="plv5-cat-card" data-reveal>
                    <span class="plv5-cat-card__icon" aria-hidden="true">⚖️</span>
                    <h3>Porównania</h3>
                    <p class="plv5-cat-card__headline">Najważniejszy moment to wybór.</p>
                    <a href="<?php echo esc_url(home_url('/porownania/')); ?>" class="plv5-cat-card__link">Zobacz porównania</a>
                </article>
                <article class="plv5-cat-card" data-reveal>
                    <span class="plv5-cat-card__icon" aria-hidden="true">🏆</span>
                    <h3>Rankingi</h3>
                    <p class="plv5-cat-card__headline">Nie szukasz. Wybierasz sprawdzonych.</p>
                    <a href="<?php echo esc_url(home_url('/rankingi/')); ?>" class="plv5-cat-card__link">Przeglądaj rankingi</a>
                </article>
                <article class="plv5-cat-card" data-reveal>
                    <span class="plv5-cat-card__icon" aria-hidden="true">🧮</span>
                    <h3>Kalkulatory</h3>
                    <p class="plv5-cat-card__headline">Liczby przed decyzją.</p>
                    <a href="<?php echo esc_url(home_url('/kalkulatory/')); ?>" class="plv5-cat-card__link">Oblicz koszty</a>
                </article>
                <article class="plv5-cat-card" data-reveal>
                    <span class="plv5-cat-card__icon" aria-hidden="true">🤖</span>
                    <h3>AI Doradca</h3>
                    <p class="plv5-cat-card__headline">Powiedz czego potrzebujesz. Resztę analizujemy za Ciebie.</p>
                    <a href="<?php echo esc_url(home_url('/ai-doradca/')); ?>" class="plv5-cat-card__link">Zapytaj AI</a>
                </article>
                <article class="plv5-cat-card" data-reveal>
                    <span class="plv5-cat-card__icon" aria-hidden="true">🧑‍💼</span>
                    <h3>Eksperci</h3>
                    <p class="plv5-cat-card__headline">Decyzja to początek. Teraz czas na wykonanie.</p>
                    <a href="<?php echo esc_url(home_url('/eksperci/')); ?>" class="plv5-cat-card__link">Znajdź specjalistę</a>
                </article>
            </div>
        </div>
    </section>

    <section class="plv5-advantage">
        <div class="pb-container plv5-advantage__grid">
            <div class="plv5-advantage__copy" data-reveal>
                <p class="plv5-kicker">Decision Engine Platform</p>
                <h2>Nie czytasz godzinami. Decydujesz w minutach.</h2>
                <p>
                    Poradnik.pro upraszcza decyzje, skraca research i pokazuje najlepsze opcje.
                    AI + wiedza + porównania + wykonawcy — połączone w jednym flow.
                </p>
                <a href="#plv5CtaForm" class="plv5-btn plv5-btn--ghost plv5-smooth-scroll">🔎 Znajdź rozwiązanie</a>
            </div>
            <div class="plv5-advantage__cards">
                <article class="plv5-adv-card" data-reveal>
                    <h3>Marketplace decyzji</h3>
                    <p>Porównania, rankingi i kalkulatory razem — wybierasz na podstawie faktów, nie domysłów.</p>
                </article>
                <article class="plv5-adv-card" data-reveal>
                    <h3>AI-assisted search</h3>
                    <p>Inteligentne rekomendacje, które tłumaczą problem i prowadzą do najlepszej opcji.</p>
                </article>
                <article class="plv5-adv-card" data-reveal>
                    <h3>Lead engine</h3>
                    <p>Gotowy do działania? Specjalista z Twoją okolicy odbierze zgłoszenie i wróci w godzinach.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="plv5-stats" data-reveal>
        <div class="pb-container plv5-stats-grid">
            <div class="plv5-stat">
                <strong class="plv5-stat__number" data-count="100000">0</strong>
                <span>porad na platformie</span>
            </div>
            <div class="plv5-stat">
                <strong class="plv5-stat__number" data-count="20000">0</strong>
                <span>specjalistów</span>
            </div>
            <div class="plv5-stat">
                <strong class="plv5-stat__number" data-count="500000">0</strong>
                <span>decyzji miesięcznie</span>
            </div>
            <div class="plv5-stat">
                <strong class="plv5-stat__number" data-count="4.8">0</strong>
                <span>średnia ocena platformy</span>
            </div>
        </div>
    </section>

    <section class="plv5-testimonials">
        <div class="pb-container">
            <div class="plv5-section-head" data-reveal>
                <p class="plv5-kicker">Opinie użytkowników</p>
                <h2>Co mówią klienci Poradnik.pro</h2>
            </div>

            <div class="plv5-testimonials-grid">
                <?php
                $testimonials = get_option('plv5_testimonials', [
                    [
                        'name' => 'Anna Kowalska',
                        'role' => 'Warszawa',
                        'rating' => 5,
                        'text' => 'Wysłałam jedno zapytanie i po kilku godzinach miałam trzy konkretne oferty. Różnica jakości ogromna.',
                    ],
                    [
                        'name' => 'Piotr Nowak',
                        'role' => 'Kraków',
                        'rating' => 5,
                        'text' => 'Dzięki porównaniu warunków szybko wybrałem wykonawcę i nie przepłaciłem. Bardzo praktyczne narzędzie.',
                    ],
                    [
                        'name' => 'Katarzyna Wiśniewska',
                        'role' => 'Gdańsk',
                        'rating' => 5,
                        'text' => 'Największy plus to wiarygodność firm i przejrzystość całego procesu. Wszystko jasno opisane.',
                    ],
                ]);

                foreach ($testimonials as $testimonial):
                ?>
                    <article class="plv5-testimonial" data-reveal>
                        <div class="plv5-testimonial__rating" aria-label="Ocena klienta">
                            <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                <span>★</span>
                            <?php endfor; ?>
                        </div>
                        <p><?php echo esc_html($testimonial['text']); ?></p>
                        <footer>
                            <strong><?php echo esc_html($testimonial['name']); ?></strong>
                            <span><?php echo esc_html($testimonial['role']); ?></span>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="plv5-faq" id="faq">
        <div class="pb-container">
            <div class="plv5-section-head" data-reveal>
                <p class="plv5-kicker">FAQ</p>
                <h2>Najczęściej zadawane pytania</h2>
            </div>

            <div class="plv5-faq-list">
                <?php
                $faqs = get_option('plv5_faqs', [
                    [
                        'question' => 'Czy korzystanie z platformy jest darmowe?',
                        'answer' => 'Tak, dla klientów korzystanie z Poradnik.pro jest darmowe. Nie pobieramy opłat za wysłanie zapytania i porównanie ofert.',
                    ],
                    [
                        'question' => 'Jak szybko otrzymam pierwsze oferty?',
                        'answer' => 'Pierwsze odpowiedzi często pojawiają się w ciągu 2-4 godzin, a maksymalny deklarowany czas to 24 godziny.',
                    ],
                    [
                        'question' => 'Czy muszę wybrać jedną z ofert?',
                        'answer' => 'Nie. Otrzymujesz oferty bez zobowiązań. Decyzja należy wyłącznie do Ciebie.',
                    ],
                    [
                        'question' => 'W jaki sposób weryfikujecie firmy?',
                        'answer' => 'Sprawdzamy dane rejestrowe, wiarygodność i historię realizacji. Do platformy trafiają tylko firmy spełniające nasze kryteria jakości.',
                    ],
                ]);

                foreach ($faqs as $index => $faq):
                ?>
                    <article class="plv5-faq-item" data-reveal>
                        <h3>
                            <button
                                class="plv5-faq-item__question"
                                data-faq-toggle="<?php echo esc_attr((string) $index); ?>"
                                aria-expanded="false"
                                aria-controls="plv5-faq-<?php echo esc_attr((string) $index); ?>"
                                id="plv5-faq-btn-<?php echo esc_attr((string) $index); ?>"
                            >
                                <span><?php echo esc_html($faq['question']); ?></span>
                                <span class="plv5-faq-item__icon" aria-hidden="true">+</span>
                            </button>
                        </h3>
                        <div
                            class="plv5-faq-item__answer"
                            id="plv5-faq-<?php echo esc_attr((string) $index); ?>"
                            data-faq-content="<?php echo esc_attr((string) $index); ?>"
                            role="region"
                            aria-labelledby="plv5-faq-btn-<?php echo esc_attr((string) $index); ?>"
                        >
                            <p><?php echo esc_html($faq['answer']); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="plv5-cta" id="kontakt">
        <div class="pb-container plv5-cta__wrap" data-reveal>
            <div>
                <p class="plv5-kicker"><?php echo esc_html($variant_copy['cta_kicker']); ?></p>
                <h2><?php echo esc_html($variant_copy['cta_title']); ?></h2>
                <p><?php echo esc_html($variant_copy['cta_subtitle']); ?></p>
            </div>

            <form id="plv5CtaForm" class="plv5-form" novalidate>
                <input type="hidden" name="ab_variant" value="<?php echo esc_attr($ab_variant); ?>">
                <input type="hidden" name="industry" value="<?php echo esc_attr($industry_key); ?>">
                <input type="hidden" name="landing_version" value="5.3.0">

                <label class="plv5-form__label" for="plv5-cta-service">Opisz problem lub czego szukasz</label>
                <input
                    id="plv5-cta-service"
                    type="text"
                    name="service"
                    class="plv5-form__input"
                    placeholder="<?php echo esc_attr($industry_copy['cta_placeholder']); ?>"
                    required
                >

                <label class="plv5-form__label" for="plv5-cta-email">Adres email</label>
                <input
                    id="plv5-cta-email"
                    type="email"
                    name="email"
                    class="plv5-form__input"
                    placeholder="twoj@email.pl"
                    required
                >

                <button type="submit" class="plv5-btn plv5-btn--primary plv5-btn--full">
                    <?php echo esc_html($variant_copy['cta_button']); ?>
                </button>

                <div class="plv5-cta-alt-actions">
                    <a href="<?php echo esc_url(home_url('/zapytaj/')); ?>" class="plv5-cta-alt-link">❓ Zadaj pytanie</a>
                    <a href="<?php echo esc_url(home_url('/eksperci/')); ?>" class="plv5-cta-alt-link">🧑‍💼 Znajdź specjalistę</a>
                </div>
            </form>
        </div>
    </section>

    <a href="#kontakt" class="plv5-mobile-cta plv5-smooth-scroll" aria-label="Przejdź do formularza kontaktowego">
        <?php echo esc_html($variant_copy['mobile_cta']); ?>
    </a>
</main>

<?php
get_footer('minimal');
?>