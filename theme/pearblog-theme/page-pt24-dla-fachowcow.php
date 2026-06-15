<?php
/**
 * Template Name: PT24.PRO - Dla Fachowców
 * Description: Landing page for professionals — packages, benefits, registration CTA.
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
    <meta name="description" content="Dołącz do PT24.PRO jako fachowiec. Pozyskuj klientów lokalnie, buduj wiarygodność i rozwijaj firmę.">
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
    <!-- HEADER -->
    <header class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold tracking-tight">
                <span class="text-slate-900">PT24</span><span class="bg-gradient-to-r from-brand-start to-brand-end bg-clip-text text-transparent">.PRO</span>
            </a>
            <div class="flex items-center gap-3">
                <a href="/logowanie/" class="hidden rounded-xl px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:inline-flex">Logowanie</a>
                <a href="/rejestracja-fachowiec/" class="rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow">Załóż konto</a>
            </div>
        </div>
    </header>

    <main>
        <!-- HERO -->
        <section class="relative overflow-hidden bg-slate-950">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(20,100,244,0.25),transparent_50%)]"></div>
            <div class="relative mx-auto max-w-7xl px-4 py-16 text-center text-white sm:px-6 lg:px-8 lg:py-24">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Platforma dla profesjonalistów</p>
                <h1 class="mt-4 font-display text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">
                    Pozyskuj klientów<br>
                    <span class="bg-gradient-to-r from-pear-blue to-pear-green bg-clip-text text-transparent">bez pośredników.</span>
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-base text-slate-300 sm:text-lg">
                    Dołącz do ponad 12 000 zweryfikowanych specjalistów. Twój profil widoczny 24/7 dla tysięcy klientów szukających usług w Twojej okolicy.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <a href="/rejestracja-fachowiec/" class="rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-8 py-3.5 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow">Załóż konto za darmo</a>
                    <a href="#pakiety" class="rounded-xl border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Zobacz pakiety ↓</a>
                </div>
            </div>
        </section>

        <!-- BENEFITS -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <div class="mb-10 text-center">
                <h2 class="font-display text-3xl font-bold sm:text-4xl">Dlaczego fachowcy wybierają PT24.PRO</h2>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $benefits = [
                    ['🎯', 'Lokalni klienci', 'Trafiasz do osób szukających usług dokładnie w Twojej okolicy.'],
                    ['⭐', 'Buduj reputację', 'System opinii i ocen zwiększa zaufanie nowych klientów.'],
                    ['📊', 'Panel statystyk', 'Śledź wyświetlenia profilu, zapytania i konwersje w jednym miejscu.'],
                    ['🔔', 'Powiadomienia', 'Natychmiastowe alerty o nowych zleceniach w Twojej kategorii.'],
                    ['🛡️', 'Weryfikacja', 'Odznaka zweryfikowanego fachowca wyróżnia Cię na tle konkurencji.'],
                    ['💰', 'Bez prowizji', 'Płacisz stałą opłatę miesięczną — bez prowizji od zleceń.'],
                ];
                foreach ($benefits as $b) :
                ?>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
                    <span class="text-3xl"><?php echo $b[0]; ?></span>
                    <h3 class="mt-4 text-base font-bold text-slate-900"><?php echo esc_html($b[1]); ?></h3>
                    <p class="mt-2 text-sm text-slate-500"><?php echo esc_html($b[2]); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- PRICING PACKAGES -->
        <section id="pakiety" class="bg-white">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
                <div class="mb-10 text-center">
                    <h2 class="font-display text-3xl font-bold sm:text-4xl">Pakiety</h2>
                    <p class="mt-2 text-sm text-slate-500">Wybierz plan dopasowany do Twojej działalności</p>
                </div>
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Starter -->
                    <div class="rounded-2xl border border-slate-200 p-8 text-center">
                        <h3 class="text-lg font-bold text-slate-900">Starter</h3>
                        <p class="mt-1 text-sm text-slate-500">Dla początkujących</p>
                        <p class="mt-6"><span class="text-4xl font-bold text-slate-900">0 zł</span><span class="text-sm text-slate-500">/mies.</span></p>
                        <ul class="mt-6 space-y-3 text-left text-sm text-slate-600">
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Profil podstawowy</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Do 3 odpowiedzi/mies.</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Opinie klientów</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-slate-300">—</span> Wyróżnienie w wynikach</li>
                        </ul>
                        <a href="/rejestracja-fachowiec/" class="mt-8 block rounded-xl border border-slate-200 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Rozpocznij za darmo</a>
                    </div>

                    <!-- Pro -->
                    <div class="relative rounded-2xl border-2 border-brand-start p-8 text-center shadow-glow">
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-start px-4 py-1 text-xs font-semibold text-white">Najpopularniejszy</span>
                        <h3 class="text-lg font-bold text-slate-900">Pro</h3>
                        <p class="mt-1 text-sm text-slate-500">Dla aktywnych fachowców</p>
                        <p class="mt-6"><span class="text-4xl font-bold text-slate-900">99 zł</span><span class="text-sm text-slate-500">/mies.</span></p>
                        <ul class="mt-6 space-y-3 text-left text-sm text-slate-600">
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Profil rozszerzony + galeria</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Nielimitowane odpowiedzi</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Wyróżnienie w wynikach</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Statystyki profilu</li>
                        </ul>
                        <a href="/rejestracja-fachowiec/?plan=pro" class="mt-8 block rounded-xl bg-gradient-to-r from-brand-start to-brand-end py-3 text-sm font-semibold text-white transition hover:shadow-glow">Wybierz Pro</a>
                    </div>

                    <!-- Business -->
                    <div class="rounded-2xl border border-slate-200 p-8 text-center">
                        <h3 class="text-lg font-bold text-slate-900">Business</h3>
                        <p class="mt-1 text-sm text-slate-500">Dla firm i ekip</p>
                        <p class="mt-6"><span class="text-4xl font-bold text-slate-900">249 zł</span><span class="text-sm text-slate-500">/mies.</span></p>
                        <ul class="mt-6 space-y-3 text-left text-sm text-slate-600">
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Wszystko z Pro</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Do 5 pracowników</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Priorytetowe wyświetlanie</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-pear-green">✓</span> Dedykowany opiekun</li>
                        </ul>
                        <a href="/rejestracja-fachowiec/?plan=business" class="mt-8 block rounded-xl border border-slate-200 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Wybierz Business</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- STATS -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-6 rounded-2xl bg-slate-900 p-8 text-center text-white sm:grid-cols-4 sm:p-12">
                <div>
                    <p class="font-display text-3xl font-bold">12 500+</p>
                    <p class="mt-1 text-sm text-slate-400">fachowców</p>
                </div>
                <div>
                    <p class="font-display text-3xl font-bold">87 000+</p>
                    <p class="mt-1 text-sm text-slate-400">zrealizowanych zleceń</p>
                </div>
                <div>
                    <p class="font-display text-3xl font-bold">4.8/5</p>
                    <p class="mt-1 text-sm text-slate-400">średnia ocena</p>
                </div>
                <div>
                    <p class="font-display text-3xl font-bold">95%</p>
                    <p class="mt-1 text-sm text-slate-400">klientów wraca</p>
                </div>
            </div>
        </section>

        <!-- FINAL CTA -->
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-16">
            <div class="rounded-3xl bg-gradient-to-br from-brand-start via-brand-mid to-brand-end p-8 text-center text-white sm:p-12">
                <h2 class="font-display text-2xl font-bold sm:text-3xl">Gotowy na więcej klientów?</h2>
                <p class="mx-auto mt-3 max-w-lg text-sm text-blue-100">Załóż konto w 2 minuty. Bez umów, bez prowizji. Możesz anulować w każdej chwili.</p>
                <a href="/rejestracja-fachowiec/" class="mt-8 inline-flex rounded-xl bg-white px-8 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Załóż konto za darmo →</a>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
            &copy; <?php echo esc_html(gmdate('Y')); ?> PT24.PRO — Marketplace usług lokalnych
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
