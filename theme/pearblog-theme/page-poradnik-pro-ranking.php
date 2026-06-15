<?php
/**
 * Template Name: Poradnik.PRO - Ranking
 * Description: Single ranking page (/ranking/{slug})
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
$ranking_title = get_the_title() ?: 'Najlepsze konta osobiste 2026';
?>

<div class="min-h-screen">
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <a href="/rankingi/" class="text-sm font-medium text-slate-600 hover:text-brand">Wszystkie rankingi</a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- HERO -->
        <section class="mb-10">
            <nav aria-label="Ścieżka" class="mb-4 text-sm text-slate-500">
                <a href="/" class="hover:text-brand">Strona główna</a> /
                <a href="/rankingi/" class="hover:text-brand">Rankingi</a> /
                <span class="text-slate-900"><?php echo esc_html($ranking_title); ?></span>
            </nav>
            <h1 class="font-display text-3xl font-bold sm:text-4xl"><?php echo esc_html($ranking_title); ?></h1>
            <p class="mt-2 text-sm text-slate-500">Aktualizacja: <?php echo esc_html(gmdate('d.m.Y')); ?> · Porównanie oparte na danych</p>
        </section>

        <!-- TABELA RANKINGOWA -->
        <section class="mb-12 overflow-hidden rounded-xl border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 font-semibold">#</th>
                        <th class="px-4 py-3 font-semibold">Nazwa</th>
                        <th class="px-4 py-3 font-semibold">Ocena</th>
                        <th class="px-4 py-3 font-semibold hidden sm:table-cell">Koszty</th>
                        <th class="px-4 py-3 font-semibold">Akcja</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                    $items = [
                        ['mBank eKonto', '4.8/5', '0 zł/mies.', 'https://mbank.pl'],
                        ['ING Konto Direct', '4.7/5', '0 zł/mies.', 'https://ing.pl'],
                        ['Millennium 360°', '4.6/5', '0 zł/mies.', 'https://millennium.pl'],
                        ['PKO Konto za Zero', '4.5/5', '0 zł/mies.', 'https://pkobp.pl'],
                        ['Santander Konto Jakie Chcę', '4.4/5', '0 zł/mies.', 'https://santander.pl'],
                    ];
                    foreach ($items as $i => $item) :
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-4 font-bold text-brand"><?php echo esc_html($i + 1); ?></td>
                        <td class="px-4 py-4 font-semibold"><?php echo esc_html($item[0]); ?></td>
                        <td class="px-4 py-4 text-amber-600 font-medium"><?php echo esc_html($item[1]); ?></td>
                        <td class="px-4 py-4 hidden sm:table-cell text-slate-500"><?php echo esc_html($item[2]); ?></td>
                        <td class="px-4 py-4"><a href="<?php echo esc_url($item[3]); ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-brand/10 px-3 py-1.5 text-xs font-semibold text-brand hover:bg-brand hover:text-white">Sprawdź</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- ANALIZA -->
        <section class="mb-12 prose prose-slate max-w-none">
            <h2>Analiza</h2>
            <p>Porównaliśmy najpopularniejsze konta osobiste dostępne na polskim rynku. Pod uwagę wzięliśmy opłaty miesięczne, dostępność bankomatów, jakość aplikacji mobilnej oraz dodatkowe korzyści.</p>
        </section>

        <!-- PORÓWNANIE -->
        <section class="mb-12">
            <h2 class="mb-4 font-display text-xl font-bold">Porównanie kluczowych cech</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-4 py-3">Cecha</th>
                            <th class="px-4 py-3">mBank</th>
                            <th class="px-4 py-3">ING</th>
                            <th class="px-4 py-3">Millennium</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr><td class="px-4 py-3 font-medium">Karta debetowa</td><td class="px-4 py-3 text-green-600">0 zł</td><td class="px-4 py-3 text-green-600">0 zł</td><td class="px-4 py-3 text-green-600">0 zł</td></tr>
                        <tr><td class="px-4 py-3 font-medium">Przelewy natychmiastowe</td><td class="px-4 py-3 text-green-600">Tak</td><td class="px-4 py-3 text-green-600">Tak</td><td class="px-4 py-3 text-green-600">Tak</td></tr>
                        <tr><td class="px-4 py-3 font-medium">Cashback</td><td class="px-4 py-3 text-green-600">Tak</td><td class="px-4 py-3 text-red-500">Nie</td><td class="px-4 py-3 text-green-600">Tak</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- FAQ -->
        <section class="mb-12 rounded-xl border border-slate-200 p-6">
            <h2 class="mb-4 font-display text-lg font-bold">FAQ</h2>
            <div class="space-y-3">
                <details class="rounded-lg border border-slate-100 p-3">
                    <summary class="cursor-pointer text-sm font-semibold">Jak wybraliśmy najlepsze konta?</summary>
                    <p class="mt-2 text-sm text-slate-600">Ranking opiera się na analizie opłat, funkcjonalności, opinii użytkowników i dostępności usług.</p>
                </details>
                <details class="rounded-lg border border-slate-100 p-3">
                    <summary class="cursor-pointer text-sm font-semibold">Czy ranking jest obiektywny?</summary>
                    <p class="mt-2 text-sm text-slate-600">Tak, nie przyjmujemy opłat za pozycję w rankingu. Linki partnerskie nie wpływają na kolejność.</p>
                </details>
            </div>
        </section>

        <!-- POLECANI EKSPERCI -->
        <section class="mb-12">
            <h2 class="mb-4 font-display text-lg font-bold">Eksperci finansowi</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold">dr Tomasz Nowak</p>
                    <p class="text-xs text-slate-500">Finanse osobiste · ★ 4.8</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold">Marcin Wiśniewski</p>
                    <p class="text-xs text-slate-500">Bankowość · ★ 4.7</p>
                </div>
            </div>
        </section>

        <!-- POWIĄZANE RANKINGI -->
        <section>
            <h2 class="mb-4 font-display text-lg font-bold">Powiązane rankingi</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <a href="/ranking/kredyty-hipoteczne/" class="rounded-lg border border-slate-200 p-4 text-sm font-medium hover:shadow-sm">Kredyty hipoteczne — porównanie</a>
                <a href="/ranking/konta-oszczednosciowe/" class="rounded-lg border border-slate-200 p-4 text-sm font-medium hover:shadow-sm">Najlepsze konta oszczędnościowe</a>
            </div>
        </section>
    </main>

    <footer class="mt-10 border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
