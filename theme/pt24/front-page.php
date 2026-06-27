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
            <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#D6E3F5] backdrop-blur-xl">
                <span class="h-2 w-2 rounded-full bg-[#2ED3C6] pt24-dot-pulse"></span>
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
                <?php foreach ( [ 'Hydraulik', 'Elektryk', 'Mechanik', 'Dekarz', 'Pompy Ciepła', 'Fotowoltaika', 'Prawo', 'Remonty' ] as $popular_cat ) : ?>
                    <a href="<?php echo esc_url( home_url( '/' . sanitize_title( $popular_cat ) . '/' ) ); ?>" class="rounded-xl border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-medium text-[#D6E3F5] transition hover:border-[#2ED3C6]/70 hover:bg-white/10">
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
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-xl">
                <div class="text-3xl font-bold text-white" data-counter-target="12000">12 000+</div>
                <div class="mt-1 text-sm text-[#D6E3F5]">Specjalistów</div>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-xl">
                <div class="text-3xl font-bold text-white" data-counter-target="150000">150 000+</div>
                <div class="mt-1 text-sm text-[#D6E3F5]">Zapytania</div>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-xl">
                <div class="text-3xl font-bold text-white" data-counter-target="98">98%</div>
                <div class="mt-1 text-sm text-[#D6E3F5]">Pozytywnych opinii</div>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-xl">
                <div class="text-3xl font-bold text-white" data-counter-target="500">500+</div>
                <div class="mt-1 text-sm text-[#D6E3F5]">Miast</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     TRUST BAR
═══════════════════════════════════════════════════════════════ -->
<section class="border-b border-slate-200 bg-white">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-3 px-4 py-8 sm:px-6 lg:grid-cols-4 lg:px-8">
        <div class="flex items-center gap-3 rounded-xl p-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-pear-green/10">
                <svg class="h-5 w-5 text-pear-green" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-800">Zweryfikowani fachowcy</span>
        </div>
        <div class="flex items-center gap-3 rounded-xl p-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-start/10">
                <svg class="h-5 w-5 text-brand-start" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-800">Dostępność 24/7</span>
        </div>
        <div class="flex items-center gap-3 rounded-xl p-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-end/10">
                <svg class="h-5 w-5 text-brand-end" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-800">Gwarancja jakości</span>
        </div>
        <div class="flex items-center gap-3 rounded-xl p-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-pear-blue/10">
                <svg class="h-5 w-5 text-pear-blue" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-800">Bezpieczne płatności</span>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     CATEGORIES
═══════════════════════════════════════════════════════════════ -->
<section id="uslugi" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
    <div class="mb-8 text-center">
        <h2 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl">Kategorie usług</h2>
        <p class="mt-2 text-sm text-slate-500">Wybierz branżę i znajdź specjalistę</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach (pt24_get_categories() as $cat) : ?>
        <a href="<?php echo esc_url($cat['slug']); ?>" class="group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-soft hover:border-brand-start/30">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-2xl group-hover:bg-brand-start/5"><?php echo $cat['icon']; ?></span>
            <span class="text-sm font-semibold text-slate-900"><?php echo esc_html($cat['name']); ?></span>
            <svg class="ml-auto h-4 w-4 text-slate-300 transition group-hover:text-brand-start" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════════════════════════════ -->
<section id="jak-to-dziala" class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
        <div class="mb-10 text-center">
            <h2 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl">Jak to działa</h2>
            <p class="mt-2 text-sm text-slate-500">3 proste kroki do wykonania zlecenia</p>
        </div>
        <div class="grid gap-6 md:grid-cols-3">
            <article class="relative rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center">
                <span class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-start to-brand-mid text-lg font-bold text-white shadow-glow">1</span>
                <h3 class="text-lg font-bold text-slate-900">Opisz zlecenie</h3>
                <p class="mt-2 text-sm text-slate-500">Podaj rodzaj usługi i lokalizację. Możesz dodać zdjęcia i szczegóły.</p>
            </article>
            <article class="relative rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center">
                <span class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-mid to-brand-end text-lg font-bold text-white shadow-glow">2</span>
                <h3 class="text-lg font-bold text-slate-900">Porównaj oferty</h3>
                <p class="mt-2 text-sm text-slate-500">Otrzymaj wyceny od lokalnych specjalistów. Sprawdź opinie i certyfikaty.</p>
            </article>
            <article class="relative rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center">
                <span class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-pear-blue to-pear-green text-lg font-bold text-white shadow-glow">3</span>
                <h3 class="text-lg font-bold text-slate-900">Zleć i oceń</h3>
                <p class="mt-2 text-sm text-slate-500">Wybierz najlepszą ofertę. Po wykonaniu usługi oceń fachowca.</p>
            </article>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     POPULAR SEARCHES
═══════════════════════════════════════════════════════════════ -->
<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60 sm:p-8">
        <h2 class="mb-5 text-xl font-bold text-slate-900">Popularne wyszukiwania</h2>
        <div class="flex flex-wrap gap-2 text-sm">
            <?php foreach (pt24_get_popular_searches() as $term) :
                $slug = sanitize_title($term);
            ?>
            <a href="/<?php echo esc_attr($slug); ?>/" class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 font-medium text-slate-700 transition hover:border-brand-start/40 hover:bg-brand-start/5 hover:text-brand-start"><?php echo esc_html($term); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     TESTIMONIALS
═══════════════════════════════════════════════════════════════ -->
<section id="opinie" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
    <div class="mb-8 text-center">
        <h2 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl">Co mówią nasi użytkownicy</h2>
    </div>
    <div class="grid gap-5 md:grid-cols-3">
        <?php foreach (pt24_get_testimonials() as $testimonial) : ?>
        <article class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60">
            <div class="mb-3 flex gap-0.5 text-amber-400">★★★★★</div>
            <p class="text-sm leading-relaxed text-slate-600"><?php echo esc_html($testimonial['text']); ?></p>
            <p class="mt-4 text-sm font-semibold text-slate-900"><?php echo esc_html($testimonial['author']); ?></p>
            <p class="text-xs text-slate-400"><?php echo esc_html($testimonial['location']); ?></p>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     FOR PROFESSIONALS CTA
═══════════════════════════════════════════════════════════════ -->
<section id="dla-fachowcow" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-start via-brand-mid to-brand-end p-8 text-white shadow-soft sm:p-12">
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 h-48 w-48 rounded-full bg-white/5 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Dla fachowców i firm</p>
            <h2 class="mt-4 max-w-lg font-display text-2xl font-bold sm:text-3xl">Pozyskuj nowych klientów lokalnie i buduj wiarygodność marki.</h2>
            <p class="mt-3 max-w-lg text-sm text-blue-100">Dołącz do 12 000+ zweryfikowanych specjalistów. Twój profil widoczny dla tysięcy klientów szukających usług w Twojej okolicy.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="/dla-fachowcow/" class="inline-flex rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Sprawdź pakiety</a>
                <a href="/rejestracja-fachowiec/" class="inline-flex rounded-xl border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Załóż konto →</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     PREMIUM PLANS
═══════════════════════════════════════════════════════════════ -->
<section id="premium" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
    <div class="mb-10 text-center">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-start">Pakiety Premium</p>
        <h2 class="mt-2 font-display text-3xl font-bold text-slate-900 sm:text-4xl">Wybierz plan dla siebie</h2>
        <p class="mt-2 text-sm text-slate-500">Więcej widoczności = więcej zleceń. Anuluj w dowolnym momencie.</p>
    </div>
    <div class="grid gap-6 md:grid-cols-3">
        <!-- Free -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
            <h3 class="text-lg font-bold text-slate-900">Start</h3>
            <p class="mt-1 text-sm text-slate-500">Dla początkujących fachowców</p>
            <div class="mt-5">
                <span class="text-3xl font-bold text-slate-900">0 zł</span>
                <span class="text-sm text-slate-400">/mies.</span>
            </div>
            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Podstawowy profil firmy</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Do 3 kategorii usług</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Widoczność w wynikach</li>
                <li class="flex items-center gap-2"><span class="text-slate-300">✗</span> Bez wyróżnienia</li>
            </ul>
            <a href="/rejestracja-fachowiec/" class="mt-8 block rounded-xl border border-slate-200 py-3 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Załóż konto</a>
        </div>

        <!-- Premium -->
        <div class="relative rounded-2xl border-2 border-brand-start bg-white p-6 shadow-soft ring-1 ring-brand-start/20">
            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-brand-start to-brand-end px-4 py-1 text-xs font-semibold text-white">Najpopularniejszy</span>
            <h3 class="text-lg font-bold text-slate-900">Premium</h3>
            <p class="mt-1 text-sm text-slate-500">Dla aktywnych specjalistów</p>
            <div class="mt-5">
                <span class="text-3xl font-bold text-slate-900">49 zł</span>
                <span class="text-sm text-slate-400">/mies.</span>
            </div>
            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Rozszerzony profil + galeria</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Nielimitowane kategorie</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Wyróżnienie w wynikach</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Odznaka „Zweryfikowany"</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Priorytetowe powiadomienia o zleceniach</li>
            </ul>
            <a href="/premium/" class="mt-8 block rounded-xl bg-gradient-to-r from-brand-start to-brand-end py-3 text-center text-sm font-semibold text-white shadow-soft transition hover:shadow-glow">Wybierz Premium</a>
        </div>

        <!-- Business -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
            <h3 class="text-lg font-bold text-slate-900">Business</h3>
            <p class="mt-1 text-sm text-slate-500">Dla firm i zespołów</p>
            <div class="mt-5">
                <span class="text-3xl font-bold text-slate-900">149 zł</span>
                <span class="text-sm text-slate-400">/mies.</span>
            </div>
            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Wszystko z Premium</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Top pozycja w mieście</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Dedykowana strona firmowa</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Panel analityczny</li>
                <li class="flex items-center gap-2"><span class="text-pear-green">✓</span> Wsparcie priorytetowe</li>
            </ul>
            <a href="/business/" class="mt-8 block rounded-xl border border-slate-200 py-3 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Wybierz Business</a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     FINAL CTA
═══════════════════════════════════════════════════════════════ -->
<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
    <div class="rounded-3xl bg-slate-900 px-6 py-12 text-center text-white sm:px-12">
        <h2 class="font-display text-2xl font-bold sm:text-3xl">Potrzebujesz pomocy teraz?</h2>
        <p class="mx-auto mt-3 max-w-md text-sm text-slate-300">Dodaj zlecenie za darmo i otrzymaj odpowiedzi od sprawdzonych specjalistów z Twojej okolicy w kilka minut.</p>
        <a href="/dodaj-zlecenie/" class="mt-8 inline-flex rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-8 py-3.5 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow">
            Dodaj zlecenie — za darmo
        </a>
    </div>
</section>

<?php get_footer(); ?>
