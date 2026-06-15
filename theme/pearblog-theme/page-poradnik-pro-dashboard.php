<?php
/**
 * Template Name: Poradnik.PRO - Dashboard Eksperta
 * Description: Expert dashboard page (/konto/)
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
<body <?php body_class('bg-slate-50 text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<div class="min-h-screen">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-600">Witaj, <strong>Anna</strong></span>
                <a href="/wyloguj/" class="text-sm text-slate-500 hover:text-slate-700">Wyloguj</a>
            </div>
        </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[240px_1fr]">
            <!-- SIDEBAR NAV -->
            <aside>
                <nav class="space-y-1">
                    <a href="/konto/" class="block rounded-lg bg-brand/10 px-4 py-2.5 text-sm font-semibold text-brand">Dashboard</a>
                    <a href="/konto/leady/" class="block rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Leady</a>
                    <a href="/konto/pytania/" class="block rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Pytania</a>
                    <a href="/konto/odpowiedzi/" class="block rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Odpowiedzi</a>
                    <a href="/konto/artykuly/" class="block rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Artykuły</a>
                    <a href="/konto/statystyki/" class="block rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Statystyki</a>
                    <a href="/konto/platnosci/" class="block rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Płatności</a>
                </nav>
            </aside>

            <!-- MAIN CONTENT -->
            <main>
                <h1 class="mb-6 font-display text-2xl font-bold">Dashboard</h1>

                <!-- STATS OVERVIEW -->
                <section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="text-xs text-slate-500">Nowe leady</p>
                        <p class="mt-1 text-2xl font-bold text-brand">12</p>
                        <p class="mt-1 text-xs text-green-600">+3 dziś</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="text-xs text-slate-500">Pytania do odpowiedzi</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">5</p>
                        <p class="mt-1 text-xs text-amber-600">2 pilne</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="text-xs text-slate-500">Wyświetlenia profilu</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">1 247</p>
                        <p class="mt-1 text-xs text-green-600">+18% vs tydzień</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <p class="text-xs text-slate-500">Ocena</p>
                        <p class="mt-1 text-2xl font-bold text-amber-500">4.9</p>
                        <p class="mt-1 text-xs text-slate-500">na 48 opinii</p>
                    </div>
                </section>

                <!-- LEADY -->
                <section class="mb-8">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold">Ostatnie leady</h2>
                        <a href="/konto/leady/" class="text-sm text-brand hover:underline">Wszystkie →</a>
                    </div>
                    <div class="space-y-3">
                        <?php
                        $leads = [
                            ['Porady spadkowe — sprawa pilna', 'Jan K.', '15 min temu', 'Nowy'],
                            ['Umowa najmu — przegląd dokumentu', 'Maria W.', '2 godz. temu', 'Nowy'],
                            ['Rozwód — pytanie o alimenty', 'Tomasz S.', '5 godz. temu', 'Przeczytany'],
                        ];
                        foreach ($leads as $l) :
                        ?>
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4">
                            <div>
                                <h3 class="text-sm font-semibold"><?php echo esc_html($l[0]); ?></h3>
                                <p class="mt-0.5 text-xs text-slate-500"><?php echo esc_html($l[1]); ?> · <?php echo esc_html($l[2]); ?></p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $l[3] === 'Nowy' ? 'bg-brand/10 text-brand' : 'bg-slate-100 text-slate-500'; ?>"><?php echo esc_html($l[3]); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- PYTANIA DO ODPOWIEDZI -->
                <section class="mb-8">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold">Pytania oczekujące</h2>
                        <a href="/konto/pytania/" class="text-sm text-brand hover:underline">Wszystkie →</a>
                    </div>
                    <div class="space-y-3">
                        <?php
                        $pending = [
                            ['Czy mogę odziedziczyć długi po rodzicach?', 'Prawo', '10 min temu'],
                            ['Jak wypisać się z testamentu?', 'Prawo', '45 min temu'],
                        ];
                        foreach ($pending as $p) :
                        ?>
                        <a href="#" class="block rounded-lg border border-slate-200 bg-white p-4 hover:border-brand/30">
                            <h3 class="text-sm font-semibold"><?php echo esc_html($p[0]); ?></h3>
                            <p class="mt-0.5 text-xs text-slate-500"><?php echo esc_html($p[1]); ?> · <?php echo esc_html($p[2]); ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- STATYSTYKI -->
                <section>
                    <h2 class="mb-4 text-lg font-bold">Statystyki (ostatnie 30 dni)</h2>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-5 text-center">
                            <p class="text-xs text-slate-500">Odpowiedzi</p>
                            <p class="mt-1 text-2xl font-bold">28</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5 text-center">
                            <p class="text-xs text-slate-500">Artykuły</p>
                            <p class="mt-1 text-2xl font-bold">3</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5 text-center">
                            <p class="text-xs text-slate-500">Przychód</p>
                            <p class="mt-1 text-2xl font-bold text-green-600">2 450 zł</p>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
