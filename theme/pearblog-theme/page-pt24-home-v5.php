<?php
/**
 * Template Name: PT24.PRO - Homepage V5
 *
 * @package PearBlog
 * @version 6.0.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandStart: '#1464F4',
                        brandEnd: '#7A4FD3'
                    },
                    boxShadow: {
                        soft: '0 20px 60px -28px rgba(15, 23, 42, 0.35)'
                    }
                }
            }
        };
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-slate-50 text-slate-900 antialiased'); ?>>
<?php wp_body_open(); ?>
<div class="min-h-screen">
    <header class="sticky top-0 z-40 border-b border-white/40 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="text-lg font-bold tracking-tight text-slate-900">PT24.PRO</a>
            <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex">
                <a href="#jak-to-dziala" class="transition hover:text-slate-900">Jak to działa</a>
                <a href="#uslugi" class="transition hover:text-slate-900">Usługi</a>
                <a href="#dla-fachowcow" class="transition hover:text-slate-900">Dla fachowców</a>
                <a href="#opinie" class="transition hover:text-slate-900">Opinie</a>
                <a href="#kontakt" class="transition hover:text-slate-900">Kontakt</a>
            </nav>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="/logowanie/" class="rounded-2xl px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Logowanie</a>
                <a href="/dodaj-zlecenie/" class="rounded-2xl bg-gradient-to-r from-brandStart to-brandEnd px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:opacity-95">Dodaj zlecenie</a>
            </div>
        </div>
        <nav class="border-t border-slate-200 px-4 py-3 lg:hidden">
            <div class="flex gap-2 overflow-x-auto whitespace-nowrap text-xs font-semibold text-slate-700">
                <a href="#jak-to-dziala" class="rounded-xl bg-slate-100 px-3 py-2">Jak to działa</a>
                <a href="#uslugi" class="rounded-xl bg-slate-100 px-3 py-2">Usługi</a>
                <a href="#dla-fachowcow" class="rounded-xl bg-slate-100 px-3 py-2">Dla fachowców</a>
                <a href="#opinie" class="rounded-xl bg-slate-100 px-3 py-2">Opinie</a>
                <a href="#kontakt" class="rounded-xl bg-slate-100 px-3 py-2">Kontakt</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="relative overflow-hidden bg-slate-950">
            <div class="absolute inset-0 bg-gradient-to-br from-brandStart/40 via-slate-950 to-brandEnd/40"></div>
            <div class="relative mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-12 lg:px-8 lg:py-20">
                <div class="space-y-6 text-white">
                    <span class="inline-flex rounded-2xl border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white">Marketplace usług lokalnych 24h</span>
                    <h1 class="max-w-xl text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">Znajdź fachowca w swojej okolicy. Szybko i bezpiecznie.</h1>
                    <p class="max-w-xl text-sm text-slate-200 sm:text-base">Zweryfikowani specjaliści dostępni 24/7. Porównaj profile, wybierz najlepszą ofertę i zleć usługę bez stresu.</p>
                    <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="rounded-3xl border border-white/15 bg-white/95 p-3 shadow-soft">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <input type="text" name="usluga" placeholder="Usługa" aria-label="Wpisz rodzaj usługi" required minlength="2" class="h-12 rounded-2xl border border-slate-200 px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brandStart focus:outline-none">
                            <input type="text" name="lokalizacja" placeholder="Lokalizacja" aria-label="Wpisz lokalizację" required minlength="2" class="h-12 rounded-2xl border border-slate-200 px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brandStart focus:outline-none">
                            <button type="submit" class="h-12 rounded-2xl bg-gradient-to-r from-brandStart to-brandEnd px-5 text-sm font-semibold text-white transition hover:opacity-95">Szukaj</button>
                        </div>
                    </form>
                </div>
                <div class="relative">
                    <div class="absolute -inset-2 rounded-[28px] bg-gradient-to-r from-brandStart to-brandEnd opacity-40 blur-2xl"></div>
                    <img
                        src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=1200&q=80"
                        alt="Fachowiec przygotowujący narzędzia do zlecenia"
                        class="relative h-[420px] w-full rounded-[24px] object-cover shadow-soft"
                        loading="lazy"
                    >
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-800">Zweryfikowani fachowcy</p></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-800">Dostępność 24/7</p></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-800">Gwarancja jakości</p></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-800">Bezpieczne zlecenia</p></div>
            </div>
        </section>

        <section id="uslugi" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
            <div class="mb-6 flex items-end justify-between">
                <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">Kategorie usług</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="/hydraulik/" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><p class="font-semibold text-slate-900">Hydraulik</p></a>
                <a href="/elektryk/" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><p class="font-semibold text-slate-900">Elektryk</p></a>
                <a href="/mechanik/" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><p class="font-semibold text-slate-900">Mechanik</p></a>
                <a href="/klimatyzacja/" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><p class="font-semibold text-slate-900">Klimatyzacja</p></a>
                <a href="/informatyk/" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><p class="font-semibold text-slate-900">Informatyk</p></a>
                <a href="/zlota-raczka/" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><p class="font-semibold text-slate-900">Złota rączka</p></a>
            </div>
        </section>

        <section id="jak-to-dziala" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <h2 class="mb-6 text-2xl font-bold text-slate-900 sm:text-3xl">Jak to działa</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <span class="mb-4 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-r from-brandStart to-brandEnd text-sm font-bold text-white">1</span>
                    <h3 class="font-semibold text-slate-900">Wyszukaj usługę</h3>
                </article>
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <span class="mb-4 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-r from-brandStart to-brandEnd text-sm font-bold text-white">2</span>
                    <h3 class="font-semibold text-slate-900">Wybierz fachowca</h3>
                </article>
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <span class="mb-4 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-r from-brandStart to-brandEnd text-sm font-bold text-white">3</span>
                    <h3 class="font-semibold text-slate-900">Zleć i oceń</h3>
                </article>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-2 sm:px-6 lg:px-8">
            <div class="rounded-[24px] bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <h2 class="mb-4 text-2xl font-bold text-slate-900">Najczęściej wyszukiwane usługi w mieście</h2>
                <div class="flex flex-wrap gap-3 text-sm font-medium">
                    <a href="/hydraulik/warszawa/" class="rounded-2xl bg-slate-100 px-4 py-2 text-slate-700 hover:bg-slate-200">Hydraulik Warszawa</a>
                    <a href="/hydraulik/krakow/" class="rounded-2xl bg-slate-100 px-4 py-2 text-slate-700 hover:bg-slate-200">Hydraulik Kraków</a>
                    <a href="/elektryk/warszawa/" class="rounded-2xl bg-slate-100 px-4 py-2 text-slate-700 hover:bg-slate-200">Elektryk Warszawa</a>
                    <a href="/elektryk/krakow/" class="rounded-2xl bg-slate-100 px-4 py-2 text-slate-700 hover:bg-slate-200">Elektryk Kraków</a>
                    <a href="/mechanik/katowice/" class="rounded-2xl bg-slate-100 px-4 py-2 text-slate-700 hover:bg-slate-200">Mechanik Katowice</a>
                    <a href="/informatyk/wroclaw/" class="rounded-2xl bg-slate-100 px-4 py-2 text-slate-700 hover:bg-slate-200">Informatyk Wrocław</a>
                </div>
            </div>
        </section>

        <section id="opinie" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <h2 class="mb-6 text-2xl font-bold text-slate-900 sm:text-3xl">Opinie klientów</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-600">„Zgłosiłem awarię hydrauliki wieczorem i w 30 minut miałem kontakt do fachowca.”</p>
                    <p class="mt-4 text-sm font-semibold text-slate-900">Anna, Warszawa</p>
                </article>
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-600">„Świetna jakość i szybkie porównanie ofert. Bardzo intuicyjna platforma.”</p>
                    <p class="mt-4 text-sm font-semibold text-slate-900">Michał, Kraków</p>
                </article>
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-600">„Dodałem zlecenie i od razu dostałem kilka odpowiedzi od lokalnych specjalistów.”</p>
                    <p class="mt-4 text-sm font-semibold text-slate-900">Karolina, Katowice</p>
                </article>
            </div>
        </section>

        <section id="dla-fachowcow" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <div class="rounded-[24px] bg-gradient-to-r from-brandStart to-brandEnd p-8 text-white shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Dla fachowców</p>
                <h2 class="mt-3 text-2xl font-bold sm:text-3xl">Pozyskuj nowych klientów lokalnie i buduj wiarygodność marki.</h2>
                <a href="/dla-fachowcow/" class="mt-6 inline-flex rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Sprawdź pakiety premium</a>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8 lg:pb-14">
            <div class="rounded-[24px] bg-slate-900 px-6 py-8 text-center text-white sm:px-8">
                <h2 class="text-2xl font-bold sm:text-3xl">Potrzebujesz pomocy 24/7?</h2>
                <p class="mt-2 text-sm text-slate-300">Dodaj zlecenie i otrzymaj odpowiedzi od specjalistów z Twojej okolicy.</p>
                <a href="/dodaj-zlecenie/" class="mt-6 inline-flex rounded-2xl bg-gradient-to-r from-brandStart to-brandEnd px-6 py-3 text-sm font-semibold text-white shadow-soft">Dodaj zlecenie</a>
            </div>
        </section>
    </main>

    <footer id="kontakt" class="border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-900">Usługi</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="/hydraulik/" class="hover:text-slate-900">Hydraulik</a></li>
                    <li><a href="/elektryk/" class="hover:text-slate-900">Elektryk</a></li>
                    <li><a href="/mechanik/" class="hover:text-slate-900">Mechanik</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-900">Miasta</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="/warszawa/" class="hover:text-slate-900">Warszawa</a></li>
                    <li><a href="/krakow/" class="hover:text-slate-900">Kraków</a></li>
                    <li><a href="/katowice/" class="hover:text-slate-900">Katowice</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-900">Dla fachowców</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="/dla-fachowcow/" class="hover:text-slate-900">Rejestracja</a></li>
                    <li><a href="/premium/" class="hover:text-slate-900">Pakiety Premium</a></li>
                    <li><a href="/statystyki/" class="hover:text-slate-900">Statystyki</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-900">Pomoc</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="/kontakt/" class="hover:text-slate-900">Kontakt</a></li>
                    <li><a href="/regulamin/" class="hover:text-slate-900">Regulamin</a></li>
                    <li><a href="/polityka-prywatnosci/" class="hover:text-slate-900">Polityka prywatności</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-200 py-4 text-center text-xs text-slate-500">&copy; <?php echo esc_html(gmdate('Y')); ?> PT24.PRO</div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
