<?php
/**
 * Template Name: Poradnik.PRO - Miasto
 * Description: City landing page (/warszawa/, /krakow/, /katowice/)
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

<?php
$city_name = get_the_title() ?: 'Warszawa';
$city_slug = sanitize_title($city_name);
?>

<div class="min-h-screen">
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <a href="/zadaj-pytanie/" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- HERO -->
        <section class="mb-12 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 text-white sm:p-12">
            <h1 class="font-display text-3xl font-bold sm:text-4xl">Poradnik.PRO — <?php echo esc_html($city_name); ?></h1>
            <p class="mt-3 max-w-xl text-sm text-slate-300">Eksperci, poradniki i odpowiedzi na pytania z Twojego miasta. Znajdź pomoc lokalnie.</p>
            <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="mt-6 flex max-w-lg flex-col gap-2 sm:flex-row">
                <input type="hidden" name="miasto" value="<?php echo esc_attr($city_name); ?>">
                <input type="search" name="s" placeholder="Czego szukasz?" aria-label="Wyszukaj" required
                    class="flex-1 rounded-lg px-4 py-3 text-sm text-slate-900 focus:outline-none">
                <button type="submit" class="rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white hover:bg-brand-dark">Szukaj</button>
            </form>
        </section>

        <!-- SPECJALIŚCI -->
        <section class="mb-12">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="font-display text-xl font-bold">Specjaliści — <?php echo esc_html($city_name); ?></h2>
                <a href="/specjalisci/?miasto=<?php echo esc_attr($city_slug); ?>" class="text-sm font-medium text-brand hover:underline">Wszyscy →</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $cityExperts = [
                    ['Mec. Anna Kowalska', 'Prawo cywilne', '4.9'],
                    ['dr Tomasz Nowak', 'Finanse', '4.8'],
                    ['inż. Piotr Zieliński', 'Budownictwo', '4.9'],
                ];
                foreach ($cityExperts as $e) :
                ?>
                <article class="rounded-xl border border-slate-200 p-5 hover:shadow-sm">
                    <h3 class="text-sm font-semibold"><?php echo esc_html($e[0]); ?></h3>
                    <p class="mt-1 text-xs text-slate-500"><?php echo esc_html($e[1]); ?> · ★ <?php echo esc_html($e[2]); ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- PORADNIKI -->
        <section class="mb-12">
            <h2 class="mb-6 font-display text-xl font-bold">Poradniki — <?php echo esc_html($city_name); ?></h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="#" class="rounded-xl border border-slate-200 p-5 hover:shadow-sm">
                    <h3 class="text-sm font-semibold">Najlepszy prawnik <?php echo esc_html($city_name); ?></h3>
                    <p class="mt-1 text-xs text-slate-400">8 min czytania</p>
                </a>
                <a href="#" class="rounded-xl border border-slate-200 p-5 hover:shadow-sm">
                    <h3 class="text-sm font-semibold">Remont mieszkania <?php echo esc_html($city_name); ?> — koszty</h3>
                    <p class="mt-1 text-xs text-slate-400">12 min czytania</p>
                </a>
                <a href="#" class="rounded-xl border border-slate-200 p-5 hover:shadow-sm">
                    <h3 class="text-sm font-semibold">Ceny nieruchomości <?php echo esc_html($city_name); ?> 2026</h3>
                    <p class="mt-1 text-xs text-slate-400">10 min czytania</p>
                </a>
            </div>
        </section>

        <!-- PYTANIA -->
        <section class="mb-12">
            <h2 class="mb-6 font-display text-xl font-bold">Pytania — <?php echo esc_html($city_name); ?></h2>
            <div class="space-y-3">
                <a href="#" class="block rounded-lg border border-slate-200 p-4 hover:border-brand/30">
                    <h3 class="text-sm font-semibold">Dobry notariusz <?php echo esc_html($city_name); ?> — polecenia?</h3>
                    <p class="mt-1 text-xs text-slate-400">4 odpowiedzi · 1 godz. temu</p>
                </a>
                <a href="#" class="block rounded-lg border border-slate-200 p-4 hover:border-brand/30">
                    <h3 class="text-sm font-semibold">Ile kosztuje remont łazienki w <?php echo esc_html($city_name); ?>?</h3>
                    <p class="mt-1 text-xs text-slate-400">6 odpowiedzi · 3 godz. temu</p>
                </a>
            </div>
        </section>

        <!-- RANKINGI -->
        <section class="mb-12">
            <h2 class="mb-6 font-display text-xl font-bold">Rankingi — <?php echo esc_html($city_name); ?></h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="#" class="rounded-xl border border-slate-200 p-5 hover:shadow-sm">
                    <h3 class="text-sm font-semibold">Najlepsze firmy remontowe <?php echo esc_html($city_name); ?></h3>
                </a>
                <a href="#" class="rounded-xl border border-slate-200 p-5 hover:shadow-sm">
                    <h3 class="text-sm font-semibold">Najlepsi prawnicy <?php echo esc_html($city_name); ?></h3>
                </a>
            </div>
        </section>

        <!-- KALKULATORY -->
        <section class="mb-12">
            <h2 class="mb-6 font-display text-xl font-bold">Kalkulatory</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="/kalkulator/kredyt-hipoteczny/" class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 hover:shadow-sm">
                    <span class="text-xl">🧮</span>
                    <span class="text-sm font-semibold">Kalkulator kredytu</span>
                </a>
                <a href="/kalkulator/koszt-budowy-domu/" class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 hover:shadow-sm">
                    <span class="text-xl">🏠</span>
                    <span class="text-sm font-semibold">Koszt budowy domu</span>
                </a>
                <a href="/kalkulator/wartosc-nieruchomosci/" class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 hover:shadow-sm">
                    <span class="text-xl">📊</span>
                    <span class="text-sm font-semibold">Wartość nieruchomości</span>
                </a>
            </div>
        </section>

        <!-- LEAD ENGINE -->
        <section class="rounded-2xl bg-slate-900 p-8 text-center text-white">
            <h2 class="font-display text-xl font-bold">Szukasz eksperta w <?php echo esc_html($city_name); ?>?</h2>
            <p class="mt-2 text-sm text-slate-300">Opisz swój problem — dopasujemy lokalnego specjalistę.</p>
            <a href="/zadaj-pytanie/?miasto=<?php echo esc_attr($city_slug); ?>" class="mt-6 inline-block rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie →</a>
        </section>
    </main>

    <footer class="mt-10 border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
