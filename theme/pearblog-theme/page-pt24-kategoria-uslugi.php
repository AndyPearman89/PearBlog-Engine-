<?php
/**
 * Template Name: PT24.PRO - Kategoria Usługi
 * Description: Service category archive page (e.g. /hydraulik/, /elektryk/).
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
                        card: '0 4px 24px -4px rgba(15,23,42,0.08)'
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

<?php
// Example data — in production these come from CPT/taxonomy queries.
$category_name  = get_the_title() ?: 'Hydraulik';
$category_slug  = sanitize_title($category_name);
$category_desc  = 'Znajdź sprawdzonego specjalistę w kategorii „' . esc_html($category_name) . '" w swojej okolicy. Porównaj profile, opinie i ceny.';
?>

<div class="min-h-screen">
    <!-- HEADER (simplified) -->
    <header class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold tracking-tight">
                <span class="text-slate-900">PT24</span><span class="bg-gradient-to-r from-brand-start to-brand-end bg-clip-text text-transparent">.PRO</span>
            </a>
            <a href="/dodaj-zlecenie/" class="rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:shadow-lg">Dodaj zlecenie</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- BREADCRUMBS -->
        <nav aria-label="Ścieżka" class="mb-6 text-sm text-slate-500">
            <ol class="flex items-center gap-1.5">
                <li><a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-brand-start">Strona główna</a></li>
                <li class="text-slate-300">/</li>
                <li><a href="/uslugi/" class="hover:text-brand-start">Usługi</a></li>
                <li class="text-slate-300">/</li>
                <li class="font-medium text-slate-900"><?php echo esc_html($category_name); ?></li>
            </ol>
        </nav>

        <!-- HERO -->
        <section class="mb-10">
            <h1 class="font-display text-3xl font-bold sm:text-4xl"><?php echo esc_html($category_name); ?> — znajdź specjalistę</h1>
            <p class="mt-2 max-w-2xl text-base text-slate-500"><?php echo esc_html($category_desc); ?></p>
        </section>

        <!-- FILTERS -->
        <section class="mb-8 flex flex-wrap items-center gap-3">
            <label class="text-sm font-medium text-slate-700">Miasto:</label>
            <select aria-label="Wybierz miasto" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-brand-start/30 focus:outline-none">
                <option value="">Wszystkie</option>
                <option value="warszawa">Warszawa</option>
                <option value="krakow">Kraków</option>
                <option value="katowice">Katowice</option>
                <option value="wroclaw">Wrocław</option>
                <option value="poznan">Poznań</option>
                <option value="gdansk">Gdańsk</option>
            </select>
            <label class="ml-4 text-sm font-medium text-slate-700">Sortuj:</label>
            <select aria-label="Sortowanie" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-brand-start/30 focus:outline-none">
                <option value="ocena">Najwyższa ocena</option>
                <option value="zlecenia">Najwięcej zleceń</option>
                <option value="cena">Najniższa cena</option>
            </select>
        </section>

        <!-- SPECIALIST CARDS -->
        <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?php
            $specialists = [
                ['name' => 'Jan Kowalski', 'city' => 'Warszawa', 'rating' => '4.9', 'jobs' => 128, 'price' => 'od 150 zł/h', 'verified' => true],
                ['name' => 'Marek Zieliński', 'city' => 'Kraków', 'rating' => '4.8', 'jobs' => 96, 'price' => 'od 130 zł/h', 'verified' => true],
                ['name' => 'Piotr Nowak', 'city' => 'Katowice', 'rating' => '4.7', 'jobs' => 74, 'price' => 'od 120 zł/h', 'verified' => true],
                ['name' => 'Adam Wiśniewski', 'city' => 'Wrocław', 'rating' => '4.9', 'jobs' => 115, 'price' => 'od 140 zł/h', 'verified' => true],
                ['name' => 'Tomasz Lewandowski', 'city' => 'Poznań', 'rating' => '4.6', 'jobs' => 63, 'price' => 'od 110 zł/h', 'verified' => false],
                ['name' => 'Krzysztof Dąbrowski', 'city' => 'Gdańsk', 'rating' => '4.8', 'jobs' => 89, 'price' => 'od 135 zł/h', 'verified' => true],
            ];
            foreach ($specialists as $spec) :
            ?>
            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card transition hover:-translate-y-0.5 hover:shadow-soft">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900"><?php echo esc_html($spec['name']); ?></h3>
                        <p class="text-xs text-slate-500"><?php echo esc_html($spec['city']); ?></p>
                    </div>
                    <?php if ($spec['verified']) : ?>
                    <span class="inline-flex items-center gap-1 rounded-full bg-pear-green/10 px-2.5 py-1 text-xs font-semibold text-green-700">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Zweryfikowany
                    </span>
                    <?php endif; ?>
                </div>
                <div class="mt-4 flex items-center gap-4 text-sm text-slate-600">
                    <span class="flex items-center gap-1 font-semibold text-amber-500">★ <?php echo esc_html($spec['rating']); ?></span>
                    <span><?php echo esc_html($spec['jobs']); ?> zleceń</span>
                    <span class="ml-auto font-semibold text-slate-900"><?php echo esc_html($spec['price']); ?></span>
                </div>
                <a href="/<?php echo esc_attr($category_slug); ?>/<?php echo esc_attr(sanitize_title($spec['name'])); ?>/" class="mt-5 block rounded-xl border border-brand-start/20 bg-brand-start/5 py-2.5 text-center text-sm font-semibold text-brand-start transition group-hover:bg-brand-start group-hover:text-white">
                    Zobacz profil
                </a>
            </article>
            <?php endforeach; ?>
        </section>

        <!-- PAGINATION -->
        <nav aria-label="Paginacja" class="mt-10 flex items-center justify-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-start text-sm font-semibold text-white">1</span>
            <a href="?page=2" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50">2</a>
            <a href="?page=3" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50">3</a>
            <span class="text-slate-400">…</span>
            <a href="?page=12" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50">12</a>
        </nav>

        <!-- SEO CONTENT -->
        <section class="mt-14 rounded-2xl bg-white p-8 shadow-card ring-1 ring-slate-200/60">
            <h2 class="text-xl font-bold text-slate-900">Usługi — <?php echo esc_html($category_name); ?></h2>
            <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                <p>Szukasz sprawdzonego specjalisty w kategorii „<?php echo esc_html($category_name); ?>"? Na PT24.PRO znajdziesz zweryfikowanych fachowców z Twojej okolicy. Porównaj profile, przeczytaj opinie innych klientów i wybierz najlepszą ofertę.</p>
                <p>Każdy specjalista na naszej platformie przechodzi weryfikację tożsamości i kwalifikacji. Dzięki systemowi ocen i opinii masz pewność, że wybierasz sprawdzoną osobę do swojego zlecenia.</p>
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
