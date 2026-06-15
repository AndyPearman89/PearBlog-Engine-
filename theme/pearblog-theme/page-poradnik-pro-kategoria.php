<?php
/**
 * Template Name: Poradnik.PRO - Kategoria
 * Description: Category archive page (/prawo/, /finanse/, /nieruchomosci/ etc.)
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
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2563EB', dark: '#1D4ED8', light: '#DBEAFE' }, accent: '#F59E0B' }, fontFamily: { display: ['Poppins','system-ui','sans-serif'], body: ['Inter','system-ui','sans-serif'] } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<?php
$cat_name = get_the_title() ?: 'Prawo';
$cat_slug = sanitize_title($cat_name);
$cat_desc = 'Poradniki, pytania i eksperci w kategorii „' . esc_html($cat_name) . '". Znajdź odpowiedzi i podejmij najlepszą decyzję.';
?>

<div class="min-h-screen">
    <!-- HEADER -->
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <a href="/zadaj-pytanie/" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- HERO -->
        <section class="mb-12 rounded-2xl bg-brand-light/50 p-8 sm:p-12">
            <h1 class="font-display text-3xl font-bold sm:text-4xl"><?php echo esc_html($cat_name); ?></h1>
            <p class="mt-2 max-w-2xl text-base text-slate-600"><?php echo esc_html($cat_desc); ?></p>
        </section>

        <!-- POPULARNE PORADNIKI -->
        <section class="mb-14">
            <h2 class="mb-6 font-display text-xl font-bold">Popularne poradniki</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $guides = [
                    ['Jak napisać testament — krok po kroku', '/poradnik/jak-napisac-testament/', '12 min czytania'],
                    ['Rozwód — procedura i koszty 2026', '/poradnik/rozwod-procedura-koszty/', '15 min czytania'],
                    ['Spadek — co musisz wiedzieć', '/poradnik/spadek-poradnik/', '10 min czytania'],
                    ['Umowa najmu — wzór i pułapki', '/poradnik/umowa-najmu/', '8 min czytania'],
                    ['Reklamacja towaru — prawa konsumenta', '/poradnik/reklamacja-towaru/', '7 min czytania'],
                    ['Alimenty — jak ustalić kwotę', '/poradnik/alimenty-kwota/', '9 min czytania'],
                ];
                foreach ($guides as $g) :
                ?>
                <a href="<?php echo esc_url($g[1]); ?>" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <h3 class="text-sm font-semibold text-slate-900"><?php echo esc_html($g[0]); ?></h3>
                    <p class="mt-1 text-xs text-slate-400"><?php echo esc_html($g[2]); ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- NAJNOWSZE PYTANIA -->
        <section class="mb-14">
            <h2 class="mb-6 font-display text-xl font-bold">Najnowsze pytania</h2>
            <div class="space-y-3">
                <?php
                $catQuestions = [
                    ['Czy można odziedziczyć długi?', '5 odpowiedzi', '10 min temu'],
                    ['Ile kosztuje akt notarialny?', '3 odpowiedzi', '25 min temu'],
                    ['Jak wypisać się z testamentu?', '2 odpowiedzi', '1 godz. temu'],
                ];
                foreach ($catQuestions as $q) :
                ?>
                <a href="#" class="block rounded-lg border border-slate-200 p-4 transition hover:border-brand/30">
                    <h3 class="text-sm font-semibold text-slate-900"><?php echo esc_html($q[0]); ?></h3>
                    <p class="mt-1 text-xs text-slate-400"><?php echo esc_html($q[1]); ?> • <?php echo esc_html($q[2]); ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- POLECANI EKSPERCI -->
        <section class="mb-14">
            <h2 class="mb-6 font-display text-xl font-bold">Polecani eksperci — <?php echo esc_html($cat_name); ?></h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $catExperts = [
                    ['Mec. Anna Kowalska', '4.9', 312],
                    ['Mec. Jan Nowak', '4.8', 198],
                    ['dr Katarzyna Wiśniewska', '4.7', 156],
                ];
                foreach ($catExperts as $e) :
                ?>
                <article class="rounded-xl border border-slate-200 bg-white p-5">
                    <h3 class="text-sm font-semibold"><?php echo esc_html($e[0]); ?></h3>
                    <p class="mt-1 text-xs text-slate-500">★ <?php echo esc_html($e[1]); ?> · <?php echo esc_html($e[2]); ?> odpowiedzi</p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- RANKINGI -->
        <section class="mb-14">
            <h2 class="mb-6 font-display text-xl font-bold">Rankingi</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="/ranking/prawnicy-online/" class="rounded-xl border border-slate-200 p-5 hover:shadow-md">
                    <h3 class="text-sm font-semibold">Najlepsi prawnicy online 2026</h3>
                </a>
                <a href="/ranking/kancelarie-podatkowe/" class="rounded-xl border border-slate-200 p-5 hover:shadow-md">
                    <h3 class="text-sm font-semibold">Ranking kancelarii podatkowych</h3>
                </a>
            </div>
        </section>

        <!-- KALKULATORY -->
        <section class="mb-14">
            <h2 class="mb-6 font-display text-xl font-bold">Kalkulatory</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="/kalkulator/alimenty/" class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 hover:shadow-sm">
                    <span class="text-xl">🧮</span>
                    <span class="text-sm font-semibold">Kalkulator alimentów</span>
                </a>
                <a href="/kalkulator/koszt-notariusza/" class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 hover:shadow-sm">
                    <span class="text-xl">🧮</span>
                    <span class="text-sm font-semibold">Kalkulator kosztów notariusza</span>
                </a>
            </div>
        </section>

        <!-- LEAD CTA -->
        <section class="rounded-2xl bg-slate-900 p-8 text-center text-white">
            <h2 class="font-display text-xl font-bold">Potrzebujesz pomocy w kategorii „<?php echo esc_html($cat_name); ?>"?</h2>
            <p class="mt-2 text-sm text-slate-300">Opisz swój problem — znajdziemy najlepszego eksperta.</p>
            <a href="/zadaj-pytanie/?kategoria=<?php echo esc_attr($cat_slug); ?>" class="mt-6 inline-block rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie →</a>
        </section>
    </main>

    <footer class="mt-10 border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
