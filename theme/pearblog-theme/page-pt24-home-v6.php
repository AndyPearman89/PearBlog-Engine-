<?php
/**
 * Template Name: PT24.PRO - Homepage V6 Mockup
 * Description: Clean mockup for pt24.pro homepage with inline PearBlog logo.
 *
 * @package PearBlog
 * @version 6.1.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PT24.PRO — znajdź zweryfikowanego fachowca w swojej okolicy. Marketplace usług lokalnych dostępny 24/7.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { start: '#1464F4', end: '#7A4FD3', mid: '#4A5FE3' },
                        pear: { green: '#4ADE80', blue: '#60A5FA' }
                    },
                    fontFamily: {
                        display: ['Poppins', 'system-ui', 'sans-serif'],
                        body: ['Inter', 'system-ui', 'sans-serif']
                    },
                    boxShadow: {
                        soft: '0 20px 60px -28px rgba(15,23,42,0.35)',
                        card: '0 4px 24px -4px rgba(15,23,42,0.08)',
                        glow: '0 0 40px -8px rgba(20,100,244,0.3)'
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-slate-50 text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<div class="min-h-screen">
    <!-- ═══════════════════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════════════════ -->
    <header class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <!-- Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-2" aria-label="PT24.PRO — strona główna">
                <svg width="40" height="40" viewBox="0 0 300 300" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs>
                        <linearGradient id="headerPearGrad" x1="55" y1="42" x2="230" y2="287" gradientUnits="userSpaceOnUse">
                            <stop offset="0" stop-color="#60A5FA"/>
                            <stop offset="0.58" stop-color="#4ADE80"/>
                            <stop offset="1" stop-color="#16A34A"/>
                        </linearGradient>
                    </defs>
                    <path d="M148 53C179 53 201 70 209 96C244 104 265 136 259 175C252 220 213 257 167 261C111 266 61 228 54 177C49 141 67 108 95 96C101 70 120 53 148 53Z" fill="url(#headerPearGrad)"/>
                    <path d="M143 51C149 32 166 19 187 19" stroke="#8B5E34" stroke-width="10" stroke-linecap="round"/>
                    <path d="M190 25C208 19 225 25 236 39C217 49 199 45 187 32" fill="#3FAE54"/>
                    <circle cx="148" cy="160" r="37" stroke="#F8FAFC" stroke-opacity="0.92" stroke-width="8"/>
                    <circle cx="148" cy="160" r="20" stroke="#F8FAFC" stroke-opacity="0.86" stroke-width="6"/>
                    <circle cx="148" cy="160" r="5" fill="#F8FAFC"/>
                </svg>
                <span class="font-display text-xl font-bold tracking-tight">
                    <span class="text-slate-900">PT24</span><span class="bg-gradient-to-r from-brand-start to-brand-end bg-clip-text text-transparent">.PRO</span>
                </span>
            </a>

            <!-- Desktop Nav -->
            <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 lg:flex">
                <a href="#jak-to-dziala" class="transition hover:text-brand-start">Jak to działa</a>
                <a href="#uslugi" class="transition hover:text-brand-start">Usługi</a>
                <a href="#dla-fachowcow" class="transition hover:text-brand-start">Dla fachowców</a>
                <a href="#opinie" class="transition hover:text-brand-start">Opinie</a>
                <a href="#kontakt" class="transition hover:text-brand-start">Kontakt</a>
            </nav>

            <!-- CTA -->
            <div class="flex items-center gap-3">
                <a href="/logowanie/" class="hidden rounded-xl px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:inline-flex">Logowanie</a>
                <a href="/dodaj-zlecenie/" class="rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow">Dodaj zlecenie</a>
            </div>
        </div>

        <!-- Mobile nav -->
        <nav class="border-t border-slate-100 px-4 py-2.5 lg:hidden">
            <div class="flex gap-2 overflow-x-auto whitespace-nowrap text-xs font-semibold text-slate-700">
                <a href="#jak-to-dziala" class="rounded-lg bg-slate-100 px-3 py-1.5">Jak to działa</a>
                <a href="#uslugi" class="rounded-lg bg-slate-100 px-3 py-1.5">Usługi</a>
                <a href="#dla-fachowcow" class="rounded-lg bg-slate-100 px-3 py-1.5">Dla fachowców</a>
                <a href="#opinie" class="rounded-lg bg-slate-100 px-3 py-1.5">Opinie</a>
                <a href="#kontakt" class="rounded-lg bg-slate-100 px-3 py-1.5">Kontakt</a>
            </div>
        </nav>
    </header>

    <main>
        <!-- ═══════════════════════════════════════════════════════════════
             HERO SECTION
        ═══════════════════════════════════════════════════════════════ -->
        <section class="relative overflow-hidden bg-slate-950">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(20,100,244,0.25),transparent_50%),radial-gradient(ellipse_at_bottom_left,_rgba(122,79,211,0.2),transparent_50%)]"></div>
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2230%22%20height%3D%2230%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Ccircle%20cx%3D%221%22%20cy%3D%221%22%20r%3D%220.5%22%20fill%3D%22rgba(255%2C255%2C255%2C0.04)%22/%3E%3C/svg%3E')]"></div>

            <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8 lg:py-24">
                <div class="space-y-8 text-white">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.15em] backdrop-blur-sm">
                        <span class="h-2 w-2 rounded-full bg-pear-green animate-pulse"></span>
                        Marketplace usług lokalnych 24h
                    </div>

                    <h1 class="font-display text-4xl font-bold leading-[1.1] sm:text-5xl lg:text-6xl">
                        Znajdź fachowca<br>
                        <span class="bg-gradient-to-r from-pear-blue to-pear-green bg-clip-text text-transparent">w swojej okolicy.</span>
                    </h1>

                    <p class="max-w-lg text-base text-slate-300 sm:text-lg">
                        Zweryfikowani specjaliści dostępni 24/7. Porównaj profile, sprawdź opinie i zleć usługę bez stresu.
                    </p>

                    <!-- Search form -->
                    <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="rounded-2xl border border-white/10 bg-white p-2 shadow-soft sm:p-3">
                        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                            <input type="text" name="usluga" placeholder="Czego szukasz? np. hydraulik" aria-label="Rodzaj usługi" required minlength="2"
                                class="h-12 rounded-xl border-0 bg-slate-50 px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-start/30 focus:outline-none">
                            <input type="text" name="lokalizacja" placeholder="Miasto lub dzielnica" aria-label="Lokalizacja" required minlength="2"
                                class="h-12 rounded-xl border-0 bg-slate-50 px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-start/30 focus:outline-none">
                            <button type="submit" class="h-12 rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-6 text-sm font-semibold text-white transition hover:shadow-glow">
                                Szukaj
                            </button>
                        </div>
                    </form>

                    <!-- Stats -->
                    <div class="flex flex-wrap gap-6 text-sm text-slate-400">
                        <div><span class="text-lg font-bold text-white">12 500+</span><br>fachowców</div>
                        <div><span class="text-lg font-bold text-white">87 000+</span><br>zleceń</div>
                        <div><span class="text-lg font-bold text-white">4.8/5</span><br>średnia ocena</div>
                    </div>
                </div>

                <!-- Hero image -->
                <div class="relative hidden lg:block">
                    <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-brand-start/30 to-brand-end/30 blur-3xl"></div>
                    <img
                        src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=800&q=80"
                        alt="Fachowiec przygotowujący narzędzia"
                        class="relative rounded-3xl object-cover shadow-soft ring-1 ring-white/10"
                        width="640" height="480"
                        loading="eager"
                    >
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
                <?php
                $categories = [
                    ['Hydraulik', '/hydraulik/', '💧'],
                    ['Elektryk', '/elektryk/', '⚡'],
                    ['Mechanik samochodowy', '/mechanik/', '🔧'],
                    ['Klimatyzacja i wentylacja', '/klimatyzacja/', '❄️'],
                    ['Informatyk / IT', '/informatyk/', '💻'],
                    ['Złota rączka', '/zlota-raczka/', '🛠️'],
                    ['Malarz / Wykończenia', '/malarz/', '🎨'],
                    ['Przeprowadzki', '/przeprowadzki/', '📦'],
                    ['Ogrodnik', '/ogrodnik/', '🌱'],
                ];
                foreach ($categories as $cat) :
                ?>
                <a href="<?php echo esc_url($cat[1]); ?>" class="group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-soft hover:border-brand-start/30">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-2xl group-hover:bg-brand-start/5"><?php echo $cat[2]; ?></span>
                    <span class="text-sm font-semibold text-slate-900"><?php echo esc_html($cat[0]); ?></span>
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
                    <?php
                    $popular = [
                        'Hydraulik Warszawa', 'Hydraulik Kraków', 'Elektryk Warszawa',
                        'Elektryk Kraków', 'Mechanik Katowice', 'Informatyk Wrocław',
                        'Klimatyzacja Poznań', 'Złota rączka Gdańsk', 'Malarz Łódź',
                    ];
                    foreach ($popular as $term) :
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
                <article class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60">
                    <div class="mb-3 flex gap-0.5 text-amber-400">★★★★★</div>
                    <p class="text-sm leading-relaxed text-slate-600">„Zgłosiłem awarię hydrauliki wieczorem — w 30 minut miałem kontakt do fachowca. Profesjonalna obsługa!"</p>
                    <p class="mt-4 text-sm font-semibold text-slate-900">Anna K.</p>
                    <p class="text-xs text-slate-400">Warszawa</p>
                </article>
                <article class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60">
                    <div class="mb-3 flex gap-0.5 text-amber-400">★★★★★</div>
                    <p class="text-sm leading-relaxed text-slate-600">„Świetna jakość i szybkie porównanie ofert. Bardzo intuicyjna platforma, polecam każdemu."</p>
                    <p class="mt-4 text-sm font-semibold text-slate-900">Michał W.</p>
                    <p class="text-xs text-slate-400">Kraków</p>
                </article>
                <article class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60">
                    <div class="mb-3 flex gap-0.5 text-amber-400">★★★★★</div>
                    <p class="text-sm leading-relaxed text-slate-600">„Dodałem zlecenie i od razu dostałem kilka odpowiedzi od lokalnych specjalistów. Super system."</p>
                    <p class="mt-4 text-sm font-semibold text-slate-900">Karolina M.</p>
                    <p class="text-xs text-slate-400">Katowice</p>
                </article>
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
    </main>

    <!-- ═══════════════════════════════════════════════════════════════
         FOOTER
    ═══════════════════════════════════════════════════════════════ -->
    <footer id="kontakt" class="border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-5 lg:px-8">
            <!-- Brand -->
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2">
                    <svg width="32" height="32" viewBox="0 0 300 300" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <defs>
                            <linearGradient id="footerPearGrad" x1="55" y1="42" x2="230" y2="287" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="#60A5FA"/>
                                <stop offset="0.58" stop-color="#4ADE80"/>
                                <stop offset="1" stop-color="#16A34A"/>
                            </linearGradient>
                        </defs>
                        <path d="M148 53C179 53 201 70 209 96C244 104 265 136 259 175C252 220 213 257 167 261C111 266 61 228 54 177C49 141 67 108 95 96C101 70 120 53 148 53Z" fill="url(#footerPearGrad)"/>
                        <path d="M143 51C149 32 166 19 187 19" stroke="#8B5E34" stroke-width="10" stroke-linecap="round"/>
                        <path d="M190 25C208 19 225 25 236 39C217 49 199 45 187 32" fill="#3FAE54"/>
                        <circle cx="148" cy="160" r="37" stroke="#F8FAFC" stroke-opacity="0.92" stroke-width="8"/>
                        <circle cx="148" cy="160" r="20" stroke="#F8FAFC" stroke-opacity="0.86" stroke-width="6"/>
                        <circle cx="148" cy="160" r="5" fill="#F8FAFC"/>
                    </svg>
                    <span class="font-display text-lg font-bold tracking-tight text-slate-900">PT24.PRO</span>
                </div>
                <p class="mt-3 max-w-xs text-sm text-slate-500">Marketplace usług lokalnych. Łączymy klientów z zweryfikowanymi fachowcami w całej Polsce.</p>
            </div>

            <!-- Links -->
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900">Usługi</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-500">
                    <li><a href="/hydraulik/" class="hover:text-slate-900">Hydraulik</a></li>
                    <li><a href="/elektryk/" class="hover:text-slate-900">Elektryk</a></li>
                    <li><a href="/mechanik/" class="hover:text-slate-900">Mechanik</a></li>
                    <li><a href="/klimatyzacja/" class="hover:text-slate-900">Klimatyzacja</a></li>
                    <li><a href="/informatyk/" class="hover:text-slate-900">Informatyk</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900">Miasta</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-500">
                    <li><a href="/warszawa/" class="hover:text-slate-900">Warszawa</a></li>
                    <li><a href="/krakow/" class="hover:text-slate-900">Kraków</a></li>
                    <li><a href="/katowice/" class="hover:text-slate-900">Katowice</a></li>
                    <li><a href="/wroclaw/" class="hover:text-slate-900">Wrocław</a></li>
                    <li><a href="/poznan/" class="hover:text-slate-900">Poznań</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900">Informacje</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-500">
                    <li><a href="/dla-fachowcow/" class="hover:text-slate-900">Dla fachowców</a></li>
                    <li><a href="/kontakt/" class="hover:text-slate-900">Kontakt</a></li>
                    <li><a href="/regulamin/" class="hover:text-slate-900">Regulamin</a></li>
                    <li><a href="/polityka-prywatnosci/" class="hover:text-slate-900">Prywatność</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-100 py-5 text-center text-xs text-slate-400">
            &copy; <?php echo esc_html(gmdate('Y')); ?> PT24.PRO — Powered by
            <svg class="inline-block h-3.5 w-auto align-text-bottom" width="80" height="14" viewBox="0 0 1200 210" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="PearBlog">
                <text x="0" y="170" font-family="Poppins,sans-serif" font-size="180" font-weight="800" fill="#1f2937">Pear</text>
                <text x="500" y="170" font-family="Poppins,sans-serif" font-size="180" font-weight="800" fill="#60A5FA">Blog</text>
            </svg>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
