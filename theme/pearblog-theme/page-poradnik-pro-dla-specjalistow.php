<?php
/**
 * Template Name: Poradnik.PRO - Dla Specjalistów
 * Description: For experts landing page (/dla-specjalistow/)
 *
 * @package PearBlog
 * @version 5.0.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2563EB', dark: '#1D4ED8', light: '#DBEAFE' } }, fontFamily: { display: ['Poppins','system-ui','sans-serif'], body: ['Inter','system-ui','sans-serif'] } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<div class="min-h-screen">
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <a href="/rejestracja-ekspert/" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Załóż konto</a>
        </div>
    </header>

    <main>
        <!-- HERO -->
        <section class="bg-gradient-to-b from-brand-light/40 to-white py-16 lg:py-24">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand">Dla specjalistów i ekspertów</p>
                <h1 class="mt-4 font-display text-4xl font-bold leading-tight sm:text-5xl">
                    Buduj markę eksperta<br>i pozyskuj klientów.
                </h1>
                <p class="mx-auto mt-4 max-w-xl text-base text-slate-500">
                    Dołącz do ponad 8 500 ekspertów. Odpowiadaj na pytania, publikuj artykuły i odbieraj leady bezpośrednio od zainteresowanych klientów.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <a href="/rejestracja-ekspert/" class="rounded-lg bg-brand px-8 py-3.5 text-sm font-semibold text-white hover:bg-brand-dark">Załóż konto za darmo</a>
                    <a href="#pakiety" class="text-sm font-medium text-brand hover:underline">Zobacz pakiety ↓</a>
                </div>
            </div>
        </section>

        <!-- KORZYŚCI -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="mb-8 text-center font-display text-2xl font-bold">Dlaczego eksperci wybierają Poradnik.PRO</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $benefits = [
                    ['📈', 'Widoczność', 'Twój profil widoczny dla tysięcy osób szukających pomocy w Twojej specjalizacji.'],
                    ['💬', 'Leady', 'Otrzymuj zapytania bezpośrednio od osób zainteresowanych Twoimi usługami.'],
                    ['⭐', 'Reputacja', 'Buduj wiarygodność dzięki opiniom i ocenom od zadowolonych klientów.'],
                    ['📝', 'Publikacje', 'Publikuj artykuły eksperckie i buduj pozycję lidera opinii.'],
                    ['📊', 'Statystyki', 'Śledź wyświetlenia, konwersje i ROI w przejrzystym panelu.'],
                    ['🏆', 'Rankingi', 'Wyróżniaj się w rankingach najlepszych specjalistów w swojej branży.'],
                ];
                foreach ($benefits as $b) :
                ?>
                <div class="rounded-xl border border-slate-200 p-6">
                    <span class="text-3xl"><?php echo $b[0]; ?></span>
                    <h3 class="mt-3 text-base font-bold"><?php echo esc_html($b[1]); ?></h3>
                    <p class="mt-1 text-sm text-slate-500"><?php echo esc_html($b[2]); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- PAKIETY -->
        <section id="pakiety" class="bg-slate-50 py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-8 text-center font-display text-2xl font-bold">Pakiety</h2>
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- FREE -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
                        <h3 class="text-lg font-bold">Free</h3>
                        <p class="mt-6"><span class="text-4xl font-bold">0 zł</span><span class="text-sm text-slate-500">/mies.</span></p>
                        <ul class="mt-6 space-y-2 text-left text-sm text-slate-600">
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Profil eksperta</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> 5 odpowiedzi/mies.</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> 1 artykuł/mies.</li>
                            <li class="flex gap-2"><span class="text-slate-300">—</span> Leady</li>
                            <li class="flex gap-2"><span class="text-slate-300">—</span> Wyróżnienie</li>
                        </ul>
                        <a href="/rejestracja-ekspert/" class="mt-8 block rounded-lg border border-slate-200 py-3 text-sm font-semibold hover:bg-slate-50">Rozpocznij za darmo</a>
                    </div>
                    <!-- PREMIUM -->
                    <div class="relative rounded-2xl border-2 border-brand bg-white p-8 text-center shadow-lg">
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand px-4 py-1 text-xs font-semibold text-white">Najpopularniejszy</span>
                        <h3 class="text-lg font-bold">Premium</h3>
                        <p class="mt-6"><span class="text-4xl font-bold">149 zł</span><span class="text-sm text-slate-500">/mies.</span></p>
                        <ul class="mt-6 space-y-2 text-left text-sm text-slate-600">
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Profil rozszerzony</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Nielimitowane odpowiedzi</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Nielimitowane artykuły</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Leady (do 20/mies.)</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Wyróżnienie w wynikach</li>
                        </ul>
                        <a href="/rejestracja-ekspert/?plan=premium" class="mt-8 block rounded-lg bg-brand py-3 text-sm font-semibold text-white hover:bg-brand-dark">Wybierz Premium</a>
                    </div>
                    <!-- PREMIUM+ -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
                        <h3 class="text-lg font-bold">Premium+</h3>
                        <p class="mt-6"><span class="text-4xl font-bold">349 zł</span><span class="text-sm text-slate-500">/mies.</span></p>
                        <ul class="mt-6 space-y-2 text-left text-sm text-slate-600">
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Wszystko z Premium</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Nielimitowane leady</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Priorytetowe wyświetlanie</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Dedykowany opiekun</li>
                            <li class="flex gap-2"><span class="text-green-500">✓</span> Raporty ROI</li>
                        </ul>
                        <a href="/rejestracja-ekspert/?plan=premium-plus" class="mt-8 block rounded-lg border border-slate-200 py-3 text-sm font-semibold hover:bg-slate-50">Wybierz Premium+</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- OPINIE -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="mb-8 text-center font-display text-2xl font-bold">Co mówią eksperci</h2>
            <div class="grid gap-5 sm:grid-cols-3">
                <?php
                $testimonials = [
                    ['Dzięki Poradnik.PRO pozyskuję 15-20 nowych klientów miesięcznie. ROI jest fenomenalny.', 'Mec. Anna Kowalska', 'Prawo'],
                    ['Platforma pozwala mi budować rozpoznawalność jako ekspert. Polecam każdemu specjaliście.', 'dr Tomasz Nowak', 'Finanse'],
                    ['Leady z Poradnik.PRO mają wysoką jakość — to osoby naprawdę zainteresowane moimi usługami.', 'inż. Piotr Zieliński', 'Budownictwo'],
                ];
                foreach ($testimonials as $t) :
                ?>
                <article class="rounded-xl border border-slate-200 p-6">
                    <div class="mb-3 text-amber-400">★★★★★</div>
                    <p class="text-sm text-slate-600">„<?php echo esc_html($t[0]); ?>"</p>
                    <p class="mt-4 text-sm font-semibold"><?php echo esc_html($t[1]); ?></p>
                    <p class="text-xs text-slate-400"><?php echo esc_html($t[2]); ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- FAQ -->
        <section class="bg-slate-50 py-14">
            <div class="mx-auto max-w-3xl px-4 sm:px-6">
                <h2 class="mb-8 text-center font-display text-2xl font-bold">Najczęstsze pytania</h2>
                <div class="space-y-3">
                    <details class="rounded-xl border border-slate-200 bg-white p-5">
                        <summary class="cursor-pointer text-sm font-semibold">Czy mogę zacząć za darmo?</summary>
                        <p class="mt-2 text-sm text-slate-600">Tak! Plan Free pozwala na utworzenie profilu i udzielanie do 5 odpowiedzi miesięcznie bez żadnych opłat.</p>
                    </details>
                    <details class="rounded-xl border border-slate-200 bg-white p-5">
                        <summary class="cursor-pointer text-sm font-semibold">Jak działają leady?</summary>
                        <p class="mt-2 text-sm text-slate-600">Użytkownicy mogą wysyłać Ci wiadomości bezpośrednio przez platformę. Otrzymujesz powiadomienie i możesz odpowiedzieć w panelu.</p>
                    </details>
                    <details class="rounded-xl border border-slate-200 bg-white p-5">
                        <summary class="cursor-pointer text-sm font-semibold">Czy mogę anulować w każdej chwili?</summary>
                        <p class="mt-2 text-sm text-slate-600">Tak, nie ma umów długoterminowych. Możesz anulować subskrypcję w dowolnym momencie z panelu konta.</p>
                    </details>
                </div>
            </div>
        </section>

        <!-- REJESTRACJA CTA -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-slate-900 p-8 text-center text-white sm:p-12">
                <h2 class="font-display text-2xl font-bold sm:text-3xl">Gotowy na więcej klientów?</h2>
                <p class="mx-auto mt-3 max-w-lg text-sm text-slate-300">Załóż konto w 2 minuty. Bez umów, bez prowizji od zleceń.</p>
                <a href="/rejestracja-ekspert/" class="mt-8 inline-block rounded-lg bg-brand px-8 py-3.5 text-sm font-semibold text-white hover:bg-brand-dark">Załóż konto za darmo →</a>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
