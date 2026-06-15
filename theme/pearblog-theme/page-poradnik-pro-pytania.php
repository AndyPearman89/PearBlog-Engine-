<?php
/**
 * Template Name: Poradnik.PRO - Pytania (Q&A Archive)
 * Description: Q&A archive page with filters (/pytania/)
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
            <a href="/zadaj-pytanie/" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="mb-8 font-display text-3xl font-bold">Pytania i odpowiedzi</h1>

        <!-- FILTERS -->
        <section class="mb-8 flex flex-wrap items-center gap-2">
            <a href="/pytania/?sort=najnowsze" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">Najnowsze</a>
            <a href="/pytania/?sort=popularne" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Popularne</a>
            <a href="/pytania/?sort=bez-odpowiedzi" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Bez odpowiedzi</a>
            <a href="/pytania/?sort=moje" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Moje pytania</a>
        </section>

        <div class="grid gap-8 lg:grid-cols-[1fr_300px]">
            <!-- LISTA PYTAŃ -->
            <section class="space-y-4">
                <?php
                $questions = [
                    ['Czy mogę odliczyć remont od podatku?', 'Finanse', 5, '2 min temu', true],
                    ['Jaki koszt budowy domu 120m² w 2026?', 'Budowa domu', 8, '8 min temu', true],
                    ['Kredyt hipoteczny a umowa zlecenie — czy dostanę?', 'Finanse', 3, '15 min temu', true],
                    ['Jak sprzedać mieszkanie bez pośrednika?', 'Nieruchomości', 12, '22 min temu', true],
                    ['Rozwód bez orzekania o winie — ile trwa?', 'Prawo', 7, '35 min temu', true],
                    ['Pompa ciepła czy gaz — co wybrać?', 'Budowa domu', 0, '1 godz. temu', false],
                    ['Ile kosztuje notariusz przy zakupie działki?', 'Nieruchomości', 0, '2 godz. temu', false],
                ];
                foreach ($questions as $q) :
                ?>
                <a href="/pytanie/<?php echo esc_attr(sanitize_title($q[0])); ?>/" class="block rounded-xl border border-slate-200 p-5 transition hover:border-brand/30 hover:shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900"><?php echo esc_html($q[0]); ?></h2>
                            <div class="mt-2 flex items-center gap-3 text-xs text-slate-400">
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-600"><?php echo esc_html($q[1]); ?></span>
                                <?php if ($q[2] > 0) : ?>
                                <span class="text-green-600 font-medium"><?php echo esc_html($q[2]); ?> odpowiedzi</span>
                                <?php else : ?>
                                <span class="text-amber-600 font-medium">Czeka na odpowiedź</span>
                                <?php endif; ?>
                                <span><?php echo esc_html($q[3]); ?></span>
                            </div>
                        </div>
                        <?php if ($q[4]) : ?>
                        <span class="mt-1 flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-xs text-green-700">✓</span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>

                <!-- PAGINATION -->
                <nav aria-label="Paginacja" class="flex items-center justify-center gap-2 pt-6">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand text-sm font-semibold text-white">1</span>
                    <a href="?page=2" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">2</a>
                    <a href="?page=3" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">3</a>
                </nav>
            </section>

            <!-- SIDEBAR -->
            <aside class="space-y-6">
                <!-- KATEGORIE -->
                <div class="rounded-xl border border-slate-200 p-5">
                    <h3 class="mb-3 text-sm font-bold">Kategorie</h3>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li><a href="/prawo/" class="hover:text-brand">Prawo (1 240)</a></li>
                        <li><a href="/finanse/" class="hover:text-brand">Finanse (980)</a></li>
                        <li><a href="/nieruchomosci/" class="hover:text-brand">Nieruchomości (756)</a></li>
                        <li><a href="/budowa-domu/" class="hover:text-brand">Budowa domu (645)</a></li>
                        <li><a href="/motoryzacja/" class="hover:text-brand">Motoryzacja (412)</a></li>
                        <li><a href="/zdrowie/" class="hover:text-brand">Zdrowie (389)</a></li>
                    </ul>
                </div>

                <!-- EKSPERCI ONLINE -->
                <div class="rounded-xl border border-slate-200 p-5">
                    <h3 class="mb-3 text-sm font-bold">Eksperci online</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            <span class="text-sm">Mec. Anna Kowalska</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            <span class="text-sm">dr Tomasz Nowak</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            <span class="text-sm">inż. Piotr Zieliński</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <footer class="mt-10 border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
