<?php
/**
 * Template Name: Poradnik.PRO - Specjaliści
 * Description: Experts listing page with filters (/specjalisci/)
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
            <a href="/dla-specjalistow/" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Dołącz jako ekspert</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="mb-8 font-display text-3xl font-bold">Specjaliści</h1>

        <!-- FILTERS -->
        <section class="mb-8 flex flex-wrap gap-3">
            <select aria-label="Branża" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Branża</option>
                <option>Prawo</option>
                <option>Finanse</option>
                <option>Nieruchomości</option>
                <option>Budowa domu</option>
                <option>Motoryzacja</option>
                <option>Zdrowie</option>
                <option>Biznes</option>
                <option>Technologia</option>
            </select>
            <select aria-label="Miasto" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Miasto</option>
                <option>Warszawa</option>
                <option>Kraków</option>
                <option>Katowice</option>
                <option>Wrocław</option>
                <option>Poznań</option>
                <option>Gdańsk</option>
            </select>
            <select aria-label="Ocena" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Ocena</option>
                <option>4.5+</option>
                <option>4.0+</option>
                <option>3.5+</option>
            </select>
            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand">
                <span>Premium</span>
            </label>
            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand">
                <span>Zweryfikowany</span>
            </label>
            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand">
                <span>Online</span>
            </label>
        </section>

        <!-- KARTY SPECJALISTÓW -->
        <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?php
            $specialists = [
                ['Mec. Anna Kowalska', 'Prawo cywilne', 'Warszawa', '4.9', 312, true, true, true],
                ['dr Tomasz Nowak', 'Finanse osobiste', 'Kraków', '4.8', 245, true, true, false],
                ['inż. Piotr Zieliński', 'Budownictwo', 'Katowice', '4.9', 189, true, false, true],
                ['Karolina Wiśniewska', 'Nieruchomości', 'Wrocław', '4.7', 156, false, true, true],
                ['Mec. Jan Nowak', 'Prawo podatkowe', 'Poznań', '4.8', 198, true, true, false],
                ['dr Maria Lewandowska', 'Medycyna', 'Gdańsk', '4.9', 267, true, true, true],
                ['Marcin Dąbrowski', 'IT / Technologia', 'Warszawa', '4.6', 134, false, true, false],
                ['Agnieszka Wójcik', 'Psychologia', 'Kraków', '4.8', 221, true, false, true],
                ['inż. Robert Kamiński', 'Motoryzacja', 'Katowice', '4.5', 98, false, true, false],
            ];
            foreach ($specialists as $s) :
            ?>
            <article class="rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="relative flex h-12 w-12 items-center justify-center rounded-full bg-brand/10 text-sm font-bold text-brand">
                            <?php echo esc_html(mb_substr($s[0], 0, 1)); ?>
                            <?php if ($s[7]) : ?>
                            <span class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-green-500"></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900"><?php echo esc_html($s[0]); ?></h2>
                            <p class="text-xs text-slate-500"><?php echo esc_html($s[1]); ?> · <?php echo esc_html($s[2]); ?></p>
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <?php if ($s[5]) : ?>
                    <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Premium</span>
                    <?php endif; ?>
                    <?php if ($s[6]) : ?>
                    <span class="rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Zweryfikowany</span>
                    <?php endif; ?>
                </div>
                <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                    <span class="font-semibold text-amber-500">★ <?php echo esc_html($s[3]); ?></span>
                    <span><?php echo esc_html($s[4]); ?> odpowiedzi</span>
                </div>
                <a href="/specjalista/<?php echo esc_attr(sanitize_title($s[0])); ?>/" class="mt-4 block rounded-lg border border-brand/20 bg-brand/5 py-2 text-center text-sm font-semibold text-brand hover:bg-brand hover:text-white">Zobacz profil</a>
            </article>
            <?php endforeach; ?>
        </section>

        <!-- PAGINATION -->
        <nav aria-label="Paginacja" class="mt-10 flex items-center justify-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand text-sm font-semibold text-white">1</span>
            <a href="?page=2" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">2</a>
            <a href="?page=3" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">3</a>
        </nav>
    </main>

    <footer class="mt-10 border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
