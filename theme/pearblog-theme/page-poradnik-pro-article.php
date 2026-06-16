<?php
/**
 * Template Name: Poradnik.PRO - Poradnik (Article)
 * Description: Single guide/article page (/poradnik/{slug})
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
$article_title = get_the_title() ?: 'Jak napisać testament — krok po kroku';
$article_cat   = 'Prawo';
?>

<div class="min-h-screen">
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <a href="/zadaj-pytanie/" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie</a>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- HERO -->
        <article>
            <header class="mb-8">
                <nav aria-label="Ścieżka" class="mb-4 text-sm text-slate-500">
                    <a href="/" class="hover:text-brand">Strona główna</a> /
                    <a href="/<?php echo esc_attr(sanitize_title($article_cat)); ?>/" class="hover:text-brand"><?php echo esc_html($article_cat); ?></a> /
                    <span class="text-slate-900"><?php echo esc_html($article_title); ?></span>
                </nav>
                <h1 class="font-display text-3xl font-bold leading-tight sm:text-4xl"><?php echo esc_html($article_title); ?></h1>

                <!-- META INFO -->
                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-slate-500">
                    <span>Autor: <strong class="text-slate-700">Mec. Anna Kowalska</strong></span>
                    <span>•</span>
                    <span>Aktualizacja: <?php echo esc_html(gmdate('d.m.Y')); ?></span>
                    <span>•</span>
                    <span>12 min czytania</span>
                </div>
            </header>

            <!-- SPIS TREŚCI -->
            <div class="mb-10 rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h2 class="mb-3 text-sm font-bold text-slate-900">📋 Spis treści</h2>
                <ol class="space-y-1.5 text-sm text-brand">
                    <li><a href="#czym-jest" class="hover:underline">1. Czym jest testament?</a></li>
                    <li><a href="#rodzaje" class="hover:underline">2. Rodzaje testamentów</a></li>
                    <li><a href="#jak-napisac" class="hover:underline">3. Jak napisać testament krok po kroku</a></li>
                    <li><a href="#bledy" class="hover:underline">4. Najczęstsze błędy</a></li>
                    <li><a href="#koszty" class="hover:underline">5. Koszty notarialne</a></li>
                    <li><a href="#faq" class="hover:underline">6. FAQ</a></li>
                </ol>
            </div>

            <!-- TREŚĆ -->
            <div class="prose prose-slate max-w-none">
                <h2 id="czym-jest">1. Czym jest testament?</h2>
                <p>Testament to jednostronne oświadczenie woli spadkodawcy, w którym rozporządza on swoim majątkiem na wypadek śmierci. Jest to jedyny dokument, który pozwala zmienić zasady dziedziczenia ustawowego.</p>

                <h2 id="rodzaje">2. Rodzaje testamentów</h2>
                <p>Polskie prawo wyróżnia trzy podstawowe formy testamentu: własnoręczny (holograficzny), notarialny oraz allograficzny. Każdy z nich ma swoje wymagania formalne.</p>

                <h2 id="jak-napisac">3. Jak napisać testament krok po kroku</h2>
                <p>Aby testament był ważny, musi spełniać określone warunki formalne. Poniżej przedstawiamy szczegółową instrukcję dla testamentu własnoręcznego.</p>

                <h2 id="bledy">4. Najczęstsze błędy</h2>
                <p>Wiele testamentów jest kwestionowanych z powodu błędów formalnych. Oto lista najczęstszych problemów i jak ich uniknąć.</p>

                <h2 id="koszty">5. Koszty notarialne</h2>
                <p>Sporządzenie testamentu u notariusza kosztuje od 50 do 200 zł netto. Cena zależy od złożoności dokumentu.</p>
            </div>

            <!-- FAQ -->
            <section id="faq" class="mt-12 rounded-xl border border-slate-200 p-6">
                <h2 class="mb-5 font-display text-xl font-bold">Najczęściej zadawane pytania</h2>
                <div class="space-y-4">
                    <?php
                    $faqs = [
                        ['Czy testament musi być napisany odręcznie?', 'Tak, testament własnoręczny (holograficzny) musi być w całości napisany ręcznie, opatrzony datą i podpisem.'],
                        ['Czy mogę zmienić testament?', 'Tak, testament można zmienić lub odwołać w każdej chwili poprzez sporządzenie nowego testamentu.'],
                        ['Ile kosztuje testament u notariusza?', 'Koszt sporządzenia testamentu notarialnego wynosi od 50 do 200 zł netto plus VAT.'],
                    ];
                    foreach ($faqs as $faq) :
                    ?>
                    <details class="rounded-lg border border-slate-100 p-4">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-900"><?php echo esc_html($faq[0]); ?></summary>
                        <p class="mt-2 text-sm text-slate-600"><?php echo esc_html($faq[1]); ?></p>
                    </details>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- POWIĄZANE PYTANIA -->
            <section class="mt-10">
                <h2 class="mb-4 font-display text-lg font-bold">Powiązane pytania</h2>
                <div class="space-y-2">
                    <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm hover:border-brand/30">Czy testament można podważyć?</a>
                    <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm hover:border-brand/30">Kto dziedziczy bez testamentu?</a>
                    <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm hover:border-brand/30">Ile czasu ma się na odrzucenie spadku?</a>
                </div>
            </section>

            <!-- POLECANI EKSPERCI -->
            <section class="mt-10">
                <h2 class="mb-4 font-display text-lg font-bold">Eksperci w tej dziedzinie</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-sm font-semibold">Mec. Anna Kowalska</p>
                        <p class="text-xs text-slate-500">Prawo spadkowe · ★ 4.9 · 312 odpowiedzi</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-sm font-semibold">Mec. Jan Nowak</p>
                        <p class="text-xs text-slate-500">Prawo cywilne · ★ 4.8 · 198 odpowiedzi</p>
                    </div>
                </div>
            </section>

            <!-- LEAD BOX -->
            <section class="mt-10 rounded-2xl bg-brand-light/50 p-6 text-center">
                <h2 class="font-display text-lg font-bold">Potrzebujesz indywidualnej porady?</h2>
                <p class="mt-1 text-sm text-slate-600">Zadaj pytanie ekspertowi — odpowiedź w 24h.</p>
                <a href="/zadaj-pytanie/" class="mt-4 inline-block rounded-lg bg-brand px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie →</a>
            </section>

            <!-- POWIĄZANE PORADNIKI -->
            <section class="mt-10">
                <h2 class="mb-4 font-display text-lg font-bold">Powiązane poradniki</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <a href="/poradnik/spadek-poradnik/" class="rounded-lg border border-slate-200 p-4 text-sm font-medium hover:shadow-sm">Spadek — co musisz wiedzieć</a>
                    <a href="/poradnik/rozwod-procedura-koszty/" class="rounded-lg border border-slate-200 p-4 text-sm font-medium hover:shadow-sm">Rozwód — procedura i koszty</a>
                </div>
            </section>
        </article>
    </main>

    <footer class="mt-10 border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
