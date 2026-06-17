<?php
/**
 * Template Name: Poradnik.pro Landing V5
 *
 * Full adaptive conversion landing for Poradnik.pro
 *
 * @package PearBlog
 * @version 5.2.0
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
$industry_aliases = [
    'budowa' => 'budownictwo',
    'construction' => 'budownictwo',
    'finance' => 'finanse',
    'legal' => 'prawo',
    'energy' => 'oze',
    'nieruchomosci' => 'estate',
    'real-estate' => 'estate',
    'real_estate' => 'estate',
    'estate-strong' => 'estate',
    'estate_strong' => 'estate',
];

if (array_key_exists($industry_candidate, $industry_aliases)) {
    $industry_candidate = $industry_aliases[$industry_candidate];
}

$industry_copy_map = [
    'general' => [
        'hero_title' => 'Od pytania do najlepszej oferty w 60 sekund',
        'hero_subtitle' => 'Opisujesz potrzebę, my znajdujemy sprawdzonych specjalistów. Porównujesz i wybierasz bez presji, bez opłat i bez zobowiązań.',
        'hero_placeholder' => 'Np. remont łazienki, kredyt hipoteczny, instalacja fotowoltaiki',
        'cta_placeholder' => 'Np. projekt domu, audyt prawny, instalacja pompy ciepła',
        'panel_title' => 'Co dostajesz po zgłoszeniu?',
        'pills' => ['50 000+ użytkowników', '100% darmowe dla klientów', 'Pierwsze oferty nawet w 2h'],
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
    'estate' => [
        'hero_title' => 'Znajdź sprawdzonego specjalistę od nieruchomości bez błądzenia',
        'hero_subtitle' => 'Zakup, sprzedaż, wynajem i finansowanie nieruchomości. Otrzymasz oferty od zweryfikowanych ekspertów i porównasz je na spokojnie.',
        'hero_placeholder' => 'Np. sprzedaż mieszkania 58 m2, zakup domu pod Krakowem, najem inwestycyjny',
        'cta_placeholder' => 'Np. wycena nieruchomości, home staging, doradztwo kredytowe do zakupu',
        'panel_title' => 'Co zyskujesz przy tematach nieruchomości?',
        'pills' => ['Eksperci od lokalnego rynku', 'Jasne porównanie kosztów i warunków', 'Mniej ryzyka przy decyzji'],
    ],
];

$industry_key = array_key_exists($industry_candidate, $industry_copy_map) ? $industry_candidate : 'general';
$industry_copy = $industry_copy_map[$industry_key];

$ab_copy = [
    'a' => [
        'kicker' => 'Poradnik.pro dla decyzji bez chaosu',
        'hero_button' => 'Znajdź ekspertów',
        'cta_kicker' => 'Ostatni krok',
        'cta_title' => 'Opisz temat i odbierz najlepsze oferty',
        'cta_subtitle' => 'Nie trać kolejnych dni na szukanie po omacku. Jedno zgłoszenie i pełne porównanie na tacy.',
        'cta_button' => 'Wyślij zgłoszenie',
        'mobile_cta' => 'Zacznij teraz',
    ],
    'b' => [
        'kicker' => 'Poradnik.pro: szybkie porównanie, świadoma decyzja',
        'hero_button' => 'Porównaj oferty',
        'cta_kicker' => 'Zamknij temat dziś',
        'cta_title' => 'Jedno zgłoszenie. Kilka ofert. Lepsza decyzja.',
        'cta_subtitle' => 'Wypełnij formularz i zobacz konkretne propozycje od zweryfikowanych specjalistów bez zobowiązań.',
        'cta_button' => 'Chcę oferty',
        'mobile_cta' => 'Porównaj oferty',
    ],
];

$variant_copy = $ab_copy[$ab_variant];

$hero_title = get_option('plv5_hero_title', $industry_copy['hero_title']);
$hero_subtitle = get_option('plv5_hero_subtitle', $industry_copy['hero_subtitle']);

get_header('minimal');
?>

<main class="poradnik-landing-v5" data-ab-variant="<?php echo esc_attr($ab_variant); ?>" data-industry="<?php echo esc_attr($industry_key); ?>" data-landing-version="5.2.0">
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
                    <input type="hidden" name="landing_version" value="5.2.0">

                    <label class="plv5-form__label" for="plv5-service">Jakiej usługi szukasz?</label>
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
                    <li>Krótki briefing potrzeb i budżetu</li>
                    <li>Do 5 dopasowanych ofert od zweryfikowanych firm</li>
                    <li>Porównanie cen, terminów i opinii w jednym miejscu</li>
                    <li>Wsparcie zespołu Poradnik.pro przy wyborze</li>
                </ul>

                <div class="plv5-mini-stats">
                    <div>
                        <strong class="plv5-stat__number" data-count="50000">0</strong>
                        <span>klientów</span>
                    </div>
                    <div>
                        <strong class="plv5-stat__number" data-count="5000">0</strong>
                        <span>ekspertów</span>
                    </div>
                    <div>
                        <strong class="plv5-stat__number" data-count="4.8">0</strong>
                        <span>średnia ocena</span>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="plv5-trust-strip" data-reveal>
        <div class="pb-container">
            <p class="plv5-trust-strip__title">Wspominani w mediach i branży:</p>
            <div class="plv5-trust-strip__logos">
                <?php
                $logos = get_option('plv5_partner_logos', [
                    ['name' => 'Forbes'],
                    ['name' => 'Business Insider'],
                    ['name' => 'Rzeczpospolita'],
                    ['name' => 'Money.pl'],
                    ['name' => 'Gazeta Wyborcza'],
                ]);

                foreach ($logos as $logo):
                ?>
                    <span><?php echo esc_html($logo['name']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="plv5-how" id="jak-to-dziala">
        <div class="pb-container">
            <div class="plv5-section-head" data-reveal>
                <p class="plv5-kicker">Proces</p>
                <h2>Jak działa Poradnik.pro?</h2>
                <p>Minimalny wysiłek z Twojej strony. Maksymalna kontrola nad wyborem wykonawcy.</p>
            </div>

            <div class="plv5-steps">
                <article class="plv5-step" data-reveal>
                    <span class="plv5-step__index">01</span>
                    <h3>Opisz potrzebę</h3>
                    <p>Napisz czego szukasz, gdzie i na kiedy. Formularz zajmuje około minuty.</p>
                </article>
                <article class="plv5-step" data-reveal>
                    <span class="plv5-step__index">02</span>
                    <h3>Odbierz oferty</h3>
                    <p>Zweryfikowane firmy odpowiadają konkretną ceną, terminem i zakresem realizacji.</p>
                </article>
                <article class="plv5-step" data-reveal>
                    <span class="plv5-step__index">03</span>
                    <h3>Porównaj i wybierz</h3>
                    <p>Masz pełny obraz rynku i wybierasz na swoich warunkach, bez presji sprzedażowej.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="plv5-advantage">
        <div class="pb-container plv5-advantage__grid">
            <div class="plv5-advantage__copy" data-reveal>
                <p class="plv5-kicker">Pełna adaptacja decyzji</p>
                <h2>Front, który prowadzi użytkownika od problemu do decyzji</h2>
                <p>
                    Przebudowaliśmy doświadczenie tak, aby użytkownik zawsze wiedział, co zrobić dalej.
                    Czytelna hierarchia informacji, szybkie formularze i konkretne argumenty zamiast marketingowego szumu.
                </p>
                <a href="#plv5CtaForm" class="plv5-btn plv5-btn--ghost plv5-smooth-scroll">Rozpocznij teraz</a>
            </div>
            <div class="plv5-advantage__cards">
                <article class="plv5-adv-card" data-reveal>
                    <h3>Weryfikacja firm</h3>
                    <p>Sprawdzenie danych i wiarygodności przed dopuszczeniem do platformy.</p>
                </article>
                <article class="plv5-adv-card" data-reveal>
                    <h3>Realne opinie</h3>
                    <p>Oceny od klientów po zakończonych współpracach, bez sztucznych recenzji.</p>
                </article>
                <article class="plv5-adv-card" data-reveal>
                    <h3>Lepsze warunki</h3>
                    <p>Konkurencyjne oferty i większa szansa na oszczędność czasu oraz budżetu.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="plv5-stats" data-reveal>
        <div class="pb-container plv5-stats-grid">
            <div class="plv5-stat">
                <strong class="plv5-stat__number" data-count="100000">0</strong>
                <span>Zrealizowanych projektów</span>
            </div>
            <div class="plv5-stat">
                <strong class="plv5-stat__number" data-count="98">0</strong>
                <span>% pozytywnych rekomendacji</span>
            </div>
            <div class="plv5-stat">
                <strong class="plv5-stat__number" data-count="24">0</strong>
                <span>godziny maks. na pierwszą odpowiedź</span>
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
                <input type="hidden" name="landing_version" value="5.2.0">

                <label class="plv5-form__label" for="plv5-cta-service">Temat zapytania</label>
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
