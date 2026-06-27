<?php
/**
 * Front Page Template
 *
 * Main homepage based on mockup v6.
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// Hero search fallback: redirect homepage query params to the canonical
// service/city route used by PT24 custom templates.
$pt24_city_query = isset($_GET['lokalizacja']) ? sanitize_title((string) wp_unslash($_GET['lokalizacja'])) : '';
$pt24_service_query = isset($_GET['kategoria']) ? sanitize_title((string) wp_unslash($_GET['kategoria'])) : '';
if ($pt24_city_query !== '' && $pt24_service_query !== '') {
    wp_safe_redirect(home_url('/' . $pt24_service_query . '/' . $pt24_city_query . '/'));
    exit;
}

get_header();
?>

<!-- ═══════════════════════════════════════════════════════════════
     HERO SECTION (PREMIUM ENTERPRISE SAAS)
═══════════════════════════════════════════════════════════════ -->
<section class="pt24-hero relative overflow-hidden">
    <div class="pt24-hero-grid absolute inset-0"></div>
    <div class="pt24-hero-particles absolute inset-0"></div>

    <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:gap-14 lg:px-8 lg:py-20">
        <!-- Left column -->
        <div class="space-y-7">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#D6E3F5] backdrop-blur-xl">
                <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/pt24-logo.png' ); ?>" alt="" aria-hidden="true" class="h-6 w-6 rounded-md object-cover">
                PT24.PRO
            </div>

            <h1 class="max-w-xl font-display text-4xl font-bold leading-[1.08] text-white sm:text-5xl lg:text-[3.45rem]">
                Znajdź sprawdzonego fachowca w swojej okolicy.
            </h1>

            <p class="max-w-xl text-base leading-relaxed text-[#D6E3F5] sm:text-lg">
                Wyślij jedno zgłoszenie, a lokalni specjaliści sami prześlą Ci swoje oferty.
                Bez telefonów. Bez szukania. Szybko i wygodnie.
            </p>

            <div class="flex flex-wrap gap-3">
                <a href="/dodaj-zlecenie/" class="pt24-hero-btn inline-flex items-center justify-center rounded-2xl px-6 py-3.5 text-sm font-semibold text-[#081426]">
                    Dodaj zapytanie
                </a>
                <a href="#uslugi" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-xl transition hover:border-[#2ED3C6]/60 hover:bg-white/10">
                    Znajdź fachowca
                </a>
            </div>

            <div class="grid gap-2 text-sm text-[#D6E3F5] sm:grid-cols-2">
                <div class="inline-flex items-center gap-2"><span class="text-[#00E6B8]">✔</span> Zweryfikowane firmy</div>
                <div class="inline-flex items-center gap-2"><span class="text-[#00E6B8]">✔</span> Odpowiedzi nawet w 15 minut</div>
                <div class="inline-flex items-center gap-2"><span class="text-[#00E6B8]">✔</span> Tysiące wykonawców</div>
            </div>

            <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="rounded-3xl border border-white/15 bg-white/10 p-3 backdrop-blur-xl">
                <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                    <input
                        type="text"
                        name="lokalizacja"
                        placeholder="Lokalizacja"
                        aria-label="Lokalizacja"
                        required
                        minlength="2"
                        class="h-12 rounded-2xl border border-white/10 bg-[#0D1F38]/80 px-4 text-sm text-white placeholder:text-[#D6E3F5]/70 focus:border-[#2ED3C6] focus:outline-none">
                    <input
                        type="text"
                        name="kategoria"
                        placeholder="Kategoria"
                        aria-label="Kategoria"
                        required
                        minlength="2"
                        class="h-12 rounded-2xl border border-white/10 bg-[#0D1F38]/80 px-4 text-sm text-white placeholder:text-[#D6E3F5]/70 focus:border-[#2ED3C6] focus:outline-none">
                    <button type="submit" class="pt24-hero-btn h-12 rounded-2xl px-6 text-sm font-semibold text-[#081426]">
                        Szukaj
                    </button>
                </div>
            </form>

            <div class="flex flex-wrap gap-2.5">
                <?php foreach ( [ 'Hydraulik', 'Elektryk', 'Mechanik', 'Dekarz', 'Pompy Ciepla', 'Fotowoltaika', 'Prawo', 'Remonty' ] as $popular_cat ) : ?>
                    <?php $popular_slug = sanitize_title( $popular_cat ); ?>
                    <a href="<?php echo esc_url( home_url( '/uslugi/' . $popular_slug . '/' ) ); ?>" class="rounded-xl border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-medium text-[#D6E3F5] transition hover:border-[#2ED3C6]/70 hover:bg-white/10">
                        <?php echo esc_html( $popular_cat ); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="pt24-mobile-map lg:hidden">
                <div class="pt24-mobile-map-inner">
                    <div class="pt24-mobile-glow"></div>
                    <div class="pt24-mobile-marker m1">Warszawa</div>
                    <div class="pt24-mobile-marker m2">Kraków</div>
                    <div class="pt24-mobile-marker m3">Wrocław</div>
                </div>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/15 bg-white/5 px-3 py-2 text-xs text-[#D6E3F5] backdrop-blur-xl">Hydraulik · ⭐ 4.9 · 8 min</div>
                    <div class="rounded-2xl border border-white/15 bg-white/5 px-3 py-2 text-xs text-[#D6E3F5] backdrop-blur-xl">Nowe oferty · 5 wykonawców</div>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div class="relative hidden lg:block">
            <div class="pt24-map-stage">
                <div class="pt24-map-poland"></div>

                <div class="pt24-connection-line pt24-line-1"></div>
                <div class="pt24-connection-line pt24-line-2"></div>
                <div class="pt24-connection-line pt24-line-3"></div>

                <div class="pt24-map-marker pt24-m1"><span>Warszawa</span></div>
                <div class="pt24-map-marker pt24-m2"><span>Kraków</span></div>
                <div class="pt24-map-marker pt24-m3"><span>Wrocław</span></div>
                <div class="pt24-map-marker pt24-m4"><span>Poznań</span></div>
                <div class="pt24-map-marker pt24-m5"><span>Gdańsk</span></div>
                <div class="pt24-map-marker pt24-m6"><span>Katowice</span></div>

                <div class="pt24-float-card pt24-c1">
                    <strong>Hydraulik</strong>
                    <small>⭐ 4.9 · Odpowiedź za 8 min</small>
                </div>
                <div class="pt24-float-card pt24-c2">
                    <strong>Mechanik</strong>
                    <small>Nowa oferta</small>
                </div>
                <div class="pt24-float-card pt24-c3">
                    <strong>Elektryk</strong>
                    <small>Zweryfikowany ✔</small>
                </div>
                <div class="pt24-float-card pt24-c4">
                    <strong>Klimatyzacja</strong>
                    <small>5 ofert</small>
                </div>
                <div class="pt24-float-card pt24-c5">
                    <strong>AI Matching</strong>
                    <small>Dopasowano 12 wykonawców</small>
                </div>
                <div class="pt24-float-card pt24-c6">
                    <strong>Lead #92841</strong>
                    <small>Nowe zapytanie · 2 min temu</small>
                </div>
            </div>
        </div>
    </div>

    <div class="relative mx-auto mt-2 max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <?php
        // Reuse stats from Section 3 if already queried, otherwise query fresh.
        if ( ! isset( $pt24_firms_count ) ) {
            global $wpdb;
            $pt24_firms_count = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
                'pt24_firm', 'publish'
            ) );
            $pt24_leads_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pt24_leads" );
            $pt24_cities_count = class_exists( 'PearBlog_PT24_Landing_CPT' )
                ? count( PearBlog_PT24_Landing_CPT::get_cities() )
                : 12;
        }
        ?>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-xl">
                <div class="text-3xl font-bold text-white" data-counter-target="<?php echo (int) $pt24_firms_count; ?>"><?php echo esc_html( number_format( $pt24_firms_count, 0, ',', ' ' ) ); ?>+</div>
                <div class="mt-1 text-sm text-[#D6E3F5]">Specjalistów</div>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-xl">
                <div class="text-3xl font-bold text-white" data-counter-target="<?php echo (int) $pt24_leads_count; ?>"><?php echo esc_html( number_format( $pt24_leads_count, 0, ',', ' ' ) ); ?>+</div>
                <div class="mt-1 text-sm text-[#D6E3F5]">Zapytania</div>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-xl">
                <div class="text-3xl font-bold text-white" data-counter-target="98">98%</div>
                <div class="mt-1 text-sm text-[#D6E3F5]">Pozytywnych opinii</div>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-xl">
                <div class="text-3xl font-bold text-white" data-counter-target="<?php echo (int) $pt24_cities_count; ?>"><?php echo (int) $pt24_cities_count; ?>+</div>
                <div class="mt-1 text-sm text-[#D6E3F5]">Miast</div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 2 — HOW IT WORKS -->
<section id="jak-to-dziala" class="pt24-section bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="mb-10 text-center">
            <h2 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl">Jak działa PT24.pro?</h2>
            <p class="mt-2 text-sm text-slate-500 sm:text-base">Trzy proste kroki do znalezienia najlepszego wykonawcy.</p>
        </div>
        <div class="grid gap-6 lg:grid-cols-3">
            <article class="pt24-glass-card p-7">
                <div class="mb-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-blue-500 text-2xl">📝</div>
                <h3 class="text-xl font-bold text-slate-900">Dodaj zapytanie</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">Opisz czego potrzebujesz. Dodaj zdjęcia, lokalizację i termin wykonania.</p>
                <div class="pt24-upload-anim mt-5" aria-hidden="true"></div>
            </article>
            <article class="pt24-glass-card p-7">
                <div class="mb-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-500 text-2xl">🤖</div>
                <h3 class="text-xl font-bold text-slate-900">AI dopasowuje specjalistów</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">System automatycznie wyszukuje firmy z odpowiedniej branży i wysyła zapytanie tylko do najbardziej dopasowanych wykonawców.</p>
                <div class="pt24-ai-network mt-5" aria-hidden="true"></div>
            </article>
            <article class="pt24-glass-card p-7">
                <div class="mb-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-400 to-blue-600 text-2xl">💬</div>
                <h3 class="text-xl font-bold text-slate-900">Otrzymujesz oferty</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">Firmy kontaktują się z Tobą z wyceną. Porównujesz ceny i wybierasz najlepszą ofertę.</p>
                <div class="pt24-notify-stack mt-5" aria-hidden="true">
                    <span>Nowa oferta · Hydraulik</span>
                    <span>Zweryfikowana firma · Elektryk</span>
                    <span>4 odpowiedzi · Remont</span>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- SECTION 3 — MARKETPLACE STATISTICS (dynamic from DB) -->
<section class="bg-slate-950">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
        <?php
        global $wpdb;
        $pt24_firms_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
            'pt24_firm', 'publish'
        ) );
        $pt24_leads_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pt24_leads" );
        $pt24_cities_count = class_exists( 'PearBlog_PT24_Landing_CPT' )
            ? count( PearBlog_PT24_Landing_CPT::get_cities() )
            : ( class_exists( 'PearBlog_PT24_Pro_Routing' ) ? count( PearBlog_PT24_Pro_Routing::get_cities() ) : 12 );
        ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="pt24-kpi-card">
                <div class="pt24-kpi-number" data-counter-target="<?php echo (int) $pt24_firms_count; ?>"><?php echo esc_html( number_format( $pt24_firms_count, 0, ',', ' ' ) ); ?>+</div>
                <div class="pt24-kpi-label">Zweryfikowanych firm</div>
            </div>
            <div class="pt24-kpi-card">
                <div class="pt24-kpi-number" data-counter-target="<?php echo (int) $pt24_leads_count; ?>"><?php echo esc_html( number_format( $pt24_leads_count, 0, ',', ' ' ) ); ?>+</div>
                <div class="pt24-kpi-label">Wysłanych zapytań</div>
            </div>
            <div class="pt24-kpi-card">
                <div class="pt24-kpi-number" data-counter-target="<?php echo (int) $pt24_cities_count; ?>"><?php echo (int) $pt24_cities_count; ?>+</div>
                <div class="pt24-kpi-label">Miast</div>
            </div>
            <div class="pt24-kpi-card">
                <div class="pt24-kpi-number" data-counter-target="98">98%</div>
                <div class="pt24-kpi-label">Pozytywnych opinii</div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 4 — CATEGORIES (dynamic from DB) -->
<section id="uslugi" class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="mb-10 text-center">
            <h2 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl">Kategorie</h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <?php
            $pt24_icon_map = [
                'hydraulik' => '💧', 'elektryk' => '⚡', 'mechanik' => '🚗',
                'dekarz' => '🏠', 'pompy-ciepla' => '♨️', 'fotowoltaika' => '☀️',
                'klimatyzacja' => '❄️', 'brukarz' => '🧱', 'remonty' => '🛠️',
                'remont' => '🛠️', 'ogrodnik' => '🌿', 'malarz' => '🎨',
                'geodeta' => '📐', 'kominiarz' => '🏭', 'stolarz' => '🪚',
                'szklarz' => '🪟', 'alarmy' => '🔐', 'monitoring' => '📹',
                'rolety' => '🪟', 'instalacje-gazowe' => '🔥', 'serwis-agd' => '🧰',
                'glazurnik' => '🔲', 'murarz' => '🧱', 'instalator' => '🔌',
                'sprzatanie' => '🧹', 'przeprowadzki' => '📦',
                'pompa-ciepla' => '♨️', 'remont-lazienki' => '🚿',
            ];
            // Use CPT services if available, else theme helper.
            if ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
                $pt24_svc_map = PearBlog_PT24_Landing_CPT::get_services();
            } elseif ( class_exists( 'PearBlog_PT24_Pro_Routing' ) ) {
                $pt24_svc_map = PearBlog_PT24_Pro_Routing::get_all_services();
            } else {
                $pt24_svc_map = [
                    'hydraulik' => 'Hydraulik',
                    'elektryk' => 'Elektryk',
                    'mechanik' => 'Mechanik',
                    'dekarz' => 'Dekarz',
                    'klimatyzacja' => 'Klimatyzacja',
                    'pompy-ciepla' => 'Pompy Ciepla',
                    'fotowoltaika' => 'Fotowoltaika',
                    'remonty' => 'Remonty',
                ];
            }

            // Pre-fetch all firm services in a single query to avoid N+1.
            global $wpdb;
            $pt24_all_firm_services = $wpdb->get_col( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} pm
                 JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_key = 'pt24_firm_services'",
                'pt24_firm', 'publish'
            ) );
            // Count occurrences of each service slug across all firms.
            $pt24_svc_counts = [];
            foreach ( $pt24_all_firm_services as $csv ) {
                foreach ( array_filter( array_map( 'trim', explode( ',', (string) $csv ) ) ) as $s ) {
                    $pt24_svc_counts[ $s ] = ( $pt24_svc_counts[ $s ] ?? 0 ) + 1;
                }
            }

            foreach ( $pt24_svc_map as $pt24_cat_slug => $pt24_cat_name ) :
                $pt24_cat_slug = sanitize_title( (string) $pt24_cat_slug );
                if ( '' === $pt24_cat_slug ) {
                    continue;
                }
                $pt24_cat_count = $pt24_svc_counts[ $pt24_cat_slug ] ?? 0;
                $pt24_cat_icon = $pt24_icon_map[ $pt24_cat_slug ] ?? '🔹';
            ?>
            <a href="<?php echo esc_url( home_url( '/uslugi/' . $pt24_cat_slug . '/' ) ); ?>" class="pt24-category-card">
                <span class="pt24-category-icon"><?php echo esc_html( $pt24_cat_icon ); ?></span>
                <span class="pt24-category-title"><?php echo esc_html( $pt24_cat_name ); ?></span>
                <span class="pt24-category-meta"><?php echo (int) $pt24_cat_count; ?> firm</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SECTION 5 — INTERACTIVE POLAND MAP -->
<section class="pt24-map-live relative overflow-hidden">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="mb-8 text-center">
            <h2 class="font-display text-3xl font-bold text-white sm:text-4xl">Interaktywna mapa Polski</h2>
        </div>
        <?php
        $pt24_map_regions = [
            [
                'slug' => 'zachodniopomorskie',
                'name' => 'Zachodniopomorskie',
                'd' => 'M 64,76 L 136,68 L 184,84 L 248,76 L 276,104 L 296,136 L 268,168 L 232,192 L 196,216 L 136,204 L 96,188 L 76,156 L 64,116 Z',
                'city_slug' => 'poznan',
                'city_name' => 'Poznan',
                'service' => 'Elektryk',
                'time' => '15 min',
                'offers' => '7 firm online',
            ],
            [
                'slug' => 'pomorskie',
                'name' => 'Pomorskie',
                'd' => 'M 284,64 L 368,56 L 456,52 L 512,68 L 556,104 L 536,136 L 492,156 L 436,176 L 384,196 L 324,172 L 284,112 Z',
                'city_slug' => 'gdansk',
                'city_name' => 'Gdansk',
                'service' => 'Dekarz',
                'time' => '13 min',
                'offers' => '10 firm online',
            ],
            [
                'slug' => 'warminsko-mazurskie',
                'name' => 'Warminsko-Mazurskie',
                'd' => 'M 516,72 L 616,68 L 712,76 L 796,84 L 836,124 L 812,164 L 776,196 L 724,216 L 604,224 L 568,216 L 536,184 Z',
                'city_slug' => 'warszawa',
                'city_name' => 'Warszawa',
                'service' => 'Hydraulik',
                'time' => '14 min',
                'offers' => '6 firm online',
            ],
            [
                'slug' => 'podlaskie',
                'name' => 'Podlaskie',
                'd' => 'M 792,104 L 884,128 L 936,156 L 976,224 L 984,276 L 936,316 L 856,336 L 796,276 L 776,184 Z',
                'city_slug' => 'warszawa',
                'city_name' => 'Warszawa',
                'service' => 'Remonty',
                'time' => '16 min',
                'offers' => '5 firm online',
            ],
            [
                'slug' => 'lubuskie',
                'name' => 'Lubuskie',
                'd' => 'M 64,228 L 136,236 L 196,244 L 236,276 L 256,336 L 232,376 L 176,404 L 116,424 L 64,356 Z',
                'city_slug' => 'wroclaw',
                'city_name' => 'Wroclaw',
                'service' => 'Brukarz',
                'time' => '12 min',
                'offers' => '6 firm online',
            ],
            [
                'slug' => 'wielkopolskie',
                'name' => 'Wielkopolskie',
                'd' => 'M 216,224 L 336,232 L 396,244 L 436,276 L 456,324 L 436,376 L 376,404 L 304,416 L 236,396 Z',
                'city_slug' => 'poznan',
                'city_name' => 'Poznan',
                'service' => 'Klimatyzacja',
                'time' => '10 min',
                'offers' => '12 firm online',
            ],
            [
                'slug' => 'kujawsko-pomorskie',
                'name' => 'Kujawsko-Pomorskie',
                'd' => 'M 348,216 L 476,220 L 536,236 L 576,276 L 596,324 L 556,376 L 476,396 L 416,384 L 376,336 Z',
                'city_slug' => 'poznan',
                'city_name' => 'Poznan',
                'service' => 'Elektryk',
                'time' => '11 min',
                'offers' => '8 firm online',
            ],
            [
                'slug' => 'mazowieckie',
                'name' => 'Mazowieckie',
                'd' => 'M 596,224 L 716,228 L 816,236 L 876,284 L 896,356 L 856,384 L 776,396 L 696,376 L 636,336 Z',
                'city_slug' => 'warszawa',
                'city_name' => 'Warszawa',
                'service' => 'Elektryk',
                'time' => '8 min',
                'offers' => '14 firm online',
            ],
            [
                'slug' => 'dolnoslaskie',
                'name' => 'Dolnoslaskie',
                'd' => 'M 96,428 L 196,436 L 284,456 L 316,516 L 336,576 L 296,616 L 216,636 L 124,616 L 76,556 Z',
                'city_slug' => 'wroclaw',
                'city_name' => 'Wroclaw',
                'service' => 'Remonty',
                'time' => '12 min',
                'offers' => '11 firm online',
            ],
            [
                'slug' => 'opolskie',
                'name' => 'Opolskie',
                'd' => 'M 296,424 L 376,416 L 456,428 L 496,476 L 496,536 L 456,576 L 376,596 L 316,576 L 296,496 Z',
                'city_slug' => 'katowice',
                'city_name' => 'Katowice',
                'service' => 'Hydraulik',
                'time' => '9 min',
                'offers' => '7 firm online',
            ],
            [
                'slug' => 'lodzkie',
                'name' => 'Lodzkie',
                'd' => 'M 456,376 L 576,376 L 656,396 L 696,456 L 696,516 L 636,556 L 556,576 L 476,536 Z',
                'city_slug' => 'warszawa',
                'city_name' => 'Warszawa',
                'service' => 'Remonty',
                'time' => '10 min',
                'offers' => '9 firm online',
            ],
            [
                'slug' => 'lubelskie',
                'name' => 'Lubelskie',
                'd' => 'M 776,396 L 876,404 L 956,436 L 996,496 L 1004,576 L 936,636 L 856,656 L 796,596 L 776,516 Z',
                'city_slug' => 'warszawa',
                'city_name' => 'Warszawa',
                'service' => 'Instalacje',
                'time' => '13 min',
                'offers' => '6 firm online',
            ],
            [
                'slug' => 'slaskie',
                'name' => 'Slaskie',
                'd' => 'M 288,576 L 376,568 L 456,584 L 496,636 L 476,704 L 396,736 L 316,724 L 276,656 Z',
                'city_slug' => 'katowice',
                'city_name' => 'Katowice',
                'service' => 'Mechanik',
                'time' => '8 min',
                'offers' => '13 firm online',
            ],
            [
                'slug' => 'malopolskie',
                'name' => 'Malopolskie',
                'd' => 'M 464,596 L 576,604 L 676,636 L 716,696 L 676,756 L 576,776 L 476,756 L 476,676 Z',
                'city_slug' => 'krakow',
                'city_name' => 'Krakow',
                'service' => 'Hydraulik',
                'time' => '9 min',
                'offers' => '12 firm online',
            ],
            [
                'slug' => 'swietokrzyskie',
                'name' => 'Swietokrzyskie',
                'd' => 'M 636,536 L 736,536 L 796,576 L 816,636 L 756,696 L 676,684 L 636,616 Z',
                'city_slug' => 'krakow',
                'city_name' => 'Krakow',
                'service' => 'Elektryk',
                'time' => '11 min',
                'offers' => '7 firm online',
            ],
            [
                'slug' => 'podkarpackie',
                'name' => 'Podkarpackie',
                'd' => 'M 796,596 L 896,616 L 976,656 L 1004,716 L 956,776 L 856,796 L 776,756 L 776,676 Z',
                'city_slug' => 'krakow',
                'city_name' => 'Krakow',
                'service' => 'Remonty',
                'time' => '12 min',
                'offers' => '8 firm online',
            ],
        ];
        $pt24_default_region = $pt24_map_regions[7];
        $pt24_map_service_slug = [
            'Hydraulik' => 'hydraulik',
            'Elektryk' => 'elektryk',
            'Mechanik' => 'mechanik',
            'Remonty' => 'remonty',
            'Dekarz' => 'dekarz',
            'Klimatyzacja' => 'klimatyzacja',
            'Brukarz' => 'brukarz',
            'Instalacje' => 'instalacje',
        ];
        ?>
        <div class="pt24-map-live-stage">
            <div class="pt24-map-live-canvas" role="application" aria-label="Interaktywna mapa wojewodztw Polski">
                <svg class="pt24-map-poland-svg" viewBox="0 0 1040 840" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">
                    <?php foreach ($pt24_map_regions as $region_index => $region_item) : ?>
                        <?php
                        $region_service_slug = $pt24_map_service_slug[ $region_item['service'] ] ?? 'hydraulik';
                        $region_url = home_url('/' . $region_service_slug . '-dla-firm-' . $region_item['city_slug'] . '/');
                        ?>
                        <path
                            class="pt24-map-region<?php echo $region_item['slug'] === $pt24_default_region['slug'] ? ' is-active' : ''; ?>"
                            d="<?php echo esc_attr((string) $region_item['d']); ?>"
                            data-map-city-slug="<?php echo esc_attr((string) $region_item['slug']); ?>"
                            data-map-city="<?php echo esc_attr((string) $region_item['name']); ?>"
                            data-map-service="<?php echo esc_attr((string) $region_item['service']); ?>"
                            data-map-time="<?php echo esc_attr((string) $region_item['time']); ?>"
                            data-map-offers="<?php echo esc_attr((string) $region_item['offers']); ?>"
                            data-map-url="<?php echo esc_url($region_url); ?>"
                            tabindex="0"
                            role="button"
                        >
                            <title><?php echo esc_html((string) $region_item['name']); ?></title>
                        </path>
                    <?php endforeach; ?>
                </svg>
            </div>

            <aside class="pt24-map-live-panel" aria-live="polite">
                <p class="pt24-map-live-panel-label">Wybrane wojewodztwo</p>
                <h3 data-map-selected-city><?php echo esc_html((string) $pt24_default_region['name']); ?></h3>
                <p class="pt24-map-live-panel-copy">
                    Najszybciej odpowiadajaca kategoria: <strong data-map-selected-service><?php echo esc_html((string) $pt24_default_region['service']); ?></strong>
                </p>
                <div class="pt24-map-live-panel-stats">
                    <span><strong data-map-selected-time><?php echo esc_html((string) $pt24_default_region['time']); ?></strong> sredni czas odpowiedzi</span>
                    <span><strong data-map-selected-offers><?php echo esc_html((string) $pt24_default_region['offers']); ?></strong> w tej chwili</span>
                </div>
                <a
                    class="pt24-map-live-panel-link"
                    data-map-selected-link
                    href="<?php
                    $pt24_default_service_slug = $pt24_map_service_slug[ $pt24_default_region['service'] ] ?? 'hydraulik';
                    echo esc_url(home_url('/' . $pt24_default_service_slug . '-dla-firm-' . $pt24_default_region['city_slug'] . '/'));
                    ?>"
                >
                    Zobacz oferty w regionie <?php echo esc_html((string) $pt24_default_region['name']); ?>
                </a>

                <div class="pt24-map-live-city-grid">
                    <?php foreach ($pt24_map_regions as $region_item) : ?>
                        <?php
                        $region_service_slug = $pt24_map_service_slug[ $region_item['service'] ] ?? 'hydraulik';
                        $region_url = home_url('/' . $region_service_slug . '-dla-firm-' . $region_item['city_slug'] . '/');
                        ?>
                        <button
                            type="button"
                            class="pt24-map-city-chip<?php echo $region_item['slug'] === $pt24_default_region['slug'] ? ' is-active' : ''; ?>"
                            data-map-city-slug="<?php echo esc_attr((string) $region_item['slug']); ?>"
                            data-map-city="<?php echo esc_attr((string) $region_item['name']); ?>"
                            data-map-service="<?php echo esc_attr((string) $region_item['service']); ?>"
                            data-map-time="<?php echo esc_attr((string) $region_item['time']); ?>"
                            data-map-offers="<?php echo esc_attr((string) $region_item['offers']); ?>"
                            data-map-url="<?php echo esc_url($region_url); ?>"
                        >
                            <?php echo esc_html((string) $region_item['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div class="pt24-live-feed-item">Mazowieckie · 4 nowe zapytania · Elektryk</div>
            <div class="pt24-live-feed-item">Slaskie · 3 nowe oferty · Mechanik</div>
            <div class="pt24-live-feed-item">Pomorskie · Zweryfikowana firma · Dekarz</div>
        </div>
    </div>
</section>

<!-- SECTION 6 — BENEFITS -->
<section class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
            <article class="pt24-benefit-card"><h3>Oszczędź czas</h3><p>Jedno zapytanie trafia do wielu firm — nie musisz dzwonić do każdej osobno.</p></article>
            <article class="pt24-benefit-card"><h3>Zweryfikowane firmy</h3><p>System opinii i weryfikacji gwarantuje jakość usług.</p></article>
            <article class="pt24-benefit-card"><h3>Konkurencyjne ceny</h3><p>Porównaj kilka ofert i wybierz najkorzystniejszą wycenę.</p></article>
            <article class="pt24-benefit-card"><h3>Szybki kontakt</h3><p>Otrzymujesz oferty nawet w kilkanaście minut.</p></article>
        </div>
    </div>
</section>

<!-- SECTION 7 — FOR COMPANIES -->
<section id="dla-fachowcow" class="bg-slate-950">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-20">
        <div>
            <h2 class="font-display text-3xl font-bold text-white sm:text-4xl">Pozyskuj nowych klientów każdego dnia.</h2>
            <p class="mt-4 text-[#D6E3F5]">Dashboard leadów, przychodów i konwersji w jednym miejscu. Skup się na realizacji zleceń, a PT24 zadba o dopływ klientów.</p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="/rejestracja-fachowiec/" class="pt24-hero-btn rounded-2xl px-6 py-3 text-sm font-semibold text-[#081426]">Dołącz jako firma</a>
                <a href="/dla-fachowcow/" class="rounded-2xl border border-white/25 bg-white/5 px-6 py-3 text-sm font-semibold text-white">Dowiedz się więcej</a>
            </div>
        </div>
        <div class="pt24-dashboard-mock">
            <div class="pt24-chart">Leady: +42%</div>
            <div class="pt24-chart">Revenue: +31%</div>
            <div class="pt24-chart">Conversion: 18.4%</div>
        </div>
    </div>
</section>

<!-- SECTION 8 — REVIEWS (dynamic from theme helper) -->
<section id="opinie" class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="mb-10 text-center">
            <h2 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl">Opinie klientów</h2>
        </div>
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <?php
            $pt24_reviews = function_exists( 'pt24_get_testimonials' ) ? pt24_get_testimonials() : [
                [ 'text' => '„Świetny kontakt. W 10 minut miałem 3 konkretne oferty."', 'author' => 'Klient', 'location' => 'Katowice' ],
                [ 'text' => '„Bardzo szybkie dopasowanie fachowca i transparentna wycena."', 'author' => 'Klient', 'location' => 'Warszawa' ],
                [ 'text' => '„Najwygodniejszy sposób na znalezienie wykonawcy bez stresu."', 'author' => 'Klient', 'location' => 'Kraków' ],
            ];
            foreach ( $pt24_reviews as $review_item ) :
            ?>
            <article class="pt24-review-card">
                <div class="pt24-review-head">
                    <span class="pt24-review-avatar"></span>
                    <span class="text-amber-400">★★★★★</span>
                </div>
                <p class="mt-4 text-slate-700"><?php echo esc_html( $review_item['text'] ); ?></p>
                <p class="mt-3 text-sm font-semibold text-slate-900"><?php echo esc_html( $review_item['author'] ?? '' ); ?> · <?php echo esc_html( $review_item['location'] ?? '' ); ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SECTION 9 — FAQ -->
<section class="bg-slate-50">
    <div class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <h2 class="mb-8 text-center font-display text-3xl font-bold text-slate-900 sm:text-4xl">FAQ</h2>
        <?php
        $faq_items = [
            [ 'Jak dodać zapytanie?', 'Kliknij przycisk „Dodaj zapytanie”, opisz usługę i wybierz lokalizację.' ],
            [ 'Czy PT24.pro jest darmowe dla klientów?', 'Tak, dodanie zapytania i otrzymywanie ofert jest bezpłatne.' ],
            [ 'Ile czasu czeka się na odpowiedzi?', 'Pierwsze odpowiedzi zwykle pojawiają się w ciągu kilkunastu minut.' ],
            [ 'Czy firmy są zweryfikowane?', 'Tak, prowadzimy proces weryfikacji i ocen użytkowników.' ],
            [ 'Czy mogę porównać oferty?', 'Tak, otrzymujesz kilka ofert i wybierasz najkorzystniejszą.' ],
            [ 'W jakich miastach działa PT24?', 'Platforma działa w setkach miast w całej Polsce.' ],
            [ 'Jak działa AI dopasowanie?', 'Silnik analizuje branżę, lokalizację i dostępność wykonawców.' ],
            [ 'Czy mogę dodać zdjęcia do zapytania?', 'Tak, zdjęcia pomagają szybciej i dokładniej wycenić usługę.' ],
            [ 'Jak firma może dołączyć do PT24?', 'Wystarczy rejestracja konta firmowego i uzupełnienie profilu.' ],
            [ 'Czy mogę edytować zapytanie po wysłaniu?', 'Tak, po zalogowaniu możesz aktualizować treść zapytania.' ],
        ];
        foreach ( $faq_items as $faq_item ) :
        ?>
        <details class="pt24-faq-item">
            <summary><?php echo esc_html( $faq_item[0] ); ?></summary>
            <p><?php echo esc_html( $faq_item[1] ); ?></p>
        </details>
        <?php endforeach; ?>
    </div>
</section>

<!-- SECTION 10 — FINAL CTA -->
<section class="pt24-final-cta relative overflow-hidden">
    <div class="pt24-final-bg absolute inset-0"></div>
    <div class="pt24-final-particle p1"></div>
    <div class="pt24-final-particle p2"></div>
    <div class="pt24-final-particle p3"></div>
    <div class="pt24-final-map-glow"></div>
    <div class="relative mx-auto max-w-5xl px-4 py-16 text-center sm:px-6 lg:px-8 lg:py-24">
        <h2 class="font-display text-3xl font-bold text-white sm:text-4xl">Dodaj zapytanie i otrzymaj pierwsze oferty jeszcze dziś.</h2>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="/dodaj-zlecenie/" class="pt24-hero-btn rounded-2xl px-8 py-4 text-base font-semibold text-[#081426]">Dodaj zapytanie</a>
            <a href="#uslugi" class="rounded-2xl border border-white/30 bg-white/5 px-8 py-4 text-base font-semibold text-white">Znajdź specjalistę</a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
