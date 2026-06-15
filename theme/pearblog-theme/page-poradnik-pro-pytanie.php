<?php
/**
 * Template Name: Poradnik.PRO - Pytanie (Single Question)
 * Description: Single question page (/pytanie/{slug})
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
$question_title = get_the_title() ?: 'Jaki koszt budowy domu 120m² w 2026?';
?>

<div class="min-h-screen">
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <a href="/pytania/" class="text-sm font-medium text-slate-600 hover:text-brand">Wszystkie pytania</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- PYTANIE -->
        <section class="mb-8">
            <h1 class="font-display text-2xl font-bold leading-tight sm:text-3xl"><?php echo esc_html($question_title); ?></h1>
            <div class="mt-3 flex items-center gap-3 text-sm text-slate-500">
                <span class="rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-600">Budowa domu</span>
                <span>Zadano 2 godz. temu</span>
                <span>· 8 odpowiedzi</span>
            </div>
            <p class="mt-4 text-sm text-slate-700">Planuję budowę domu jednorodzinnego ok. 120m². Ile realistycznie powinienem zabudżetować na rok 2026? Interesuje mnie stan deweloperski. Region: Śląsk.</p>
        </section>

        <!-- NAJLEPSZA ODPOWIEDŹ -->
        <section class="mb-8 rounded-xl border-2 border-green-200 bg-green-50/50 p-6">
            <div class="mb-3 flex items-center gap-2">
                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">✓ Najlepsza odpowiedź</span>
            </div>
            <div class="text-sm text-slate-700 leading-relaxed">
                <p>Na Śląsku w 2026 roku realny koszt budowy domu 120m² w stanie deweloperskim to <strong>4 500 – 5 500 zł/m²</strong>, czyli łącznie <strong>540 000 – 660 000 zł</strong>.</p>
                <p class="mt-2">To obejmuje: fundamenty, ściany, dach, okna, instalacje (elektryczna, wod-kan, CO), tynki zewnętrzne. Nie obejmuje: wykończenia wnętrz, ogrodzenia, podjazdu.</p>
            </div>
            <div class="mt-4 flex items-center gap-3 border-t border-green-200 pt-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand/10 text-xs font-bold text-brand">P</div>
                <div>
                    <p class="text-sm font-semibold">inż. Piotr Zieliński</p>
                    <p class="text-xs text-slate-500">Budownictwo · ★ 4.9 · 189 odpowiedzi</p>
                </div>
            </div>
        </section>

        <!-- POZOSTAŁE ODPOWIEDZI -->
        <section class="mb-10 space-y-5">
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Pozostałe odpowiedzi (7)</h2>
            <?php
            $answers = [
                ['Zgadzam się z powyższą wyceną. Dodam, że warto dodać 10-15% buforu na nieprzewidziane wydatki.', 'Adam Nowak', 'Kierownik budowy', '4.6'],
                ['Z mojego doświadczenia — na Śląsku ceny mogą być nieco niższe niż w centralnej Polsce. Orientacyjnie 4 200–5 200 zł/m².', 'Marek Kowalczyk', 'Architekt', '4.5'],
            ];
            foreach ($answers as $a) :
            ?>
            <article class="rounded-xl border border-slate-200 p-5">
                <p class="text-sm text-slate-700"><?php echo esc_html($a[0]); ?></p>
                <div class="mt-3 flex items-center gap-3 border-t border-slate-100 pt-3">
                    <p class="text-sm font-semibold"><?php echo esc_html($a[1]); ?></p>
                    <p class="text-xs text-slate-500"><?php echo esc_html($a[2]); ?> · ★ <?php echo esc_html($a[3]); ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </section>

        <!-- PROFIL EKSPERTA -->
        <section class="mb-10 rounded-xl border border-slate-200 p-6">
            <h2 class="mb-3 text-sm font-bold text-slate-500 uppercase tracking-wider">O ekspercie</h2>
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand/10 text-lg font-bold text-brand">P</div>
                <div>
                    <h3 class="text-base font-bold">inż. Piotr Zieliński</h3>
                    <p class="text-sm text-slate-500">Budownictwo · 15 lat doświadczenia</p>
                    <p class="text-sm text-slate-500">★ 4.9 · 189 odpowiedzi · Zweryfikowany</p>
                </div>
            </div>
            <a href="/specjalista/piotr-zielinski/" class="mt-4 inline-block text-sm font-medium text-brand hover:underline">Zobacz pełny profil →</a>
        </section>

        <!-- POWIĄZANE PYTANIA -->
        <section class="mb-10">
            <h2 class="mb-4 font-display text-lg font-bold">Powiązane pytania</h2>
            <div class="space-y-2">
                <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm font-medium hover:border-brand/30">Ile kosztuje projekt domu?</a>
                <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm font-medium hover:border-brand/30">Budowa domu systemem gospodarczym — czy się opłaca?</a>
                <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm font-medium hover:border-brand/30">Dom parterowy czy piętrowy — co tańsze?</a>
            </div>
        </section>

        <!-- LEAD CTA -->
        <section class="rounded-2xl bg-slate-900 p-6 text-center text-white">
            <h2 class="font-display text-lg font-bold">Masz podobne pytanie?</h2>
            <p class="mt-1 text-sm text-slate-300">Zadaj pytanie — eksperci odpowiedzą w ciągu 24h.</p>
            <a href="/zadaj-pytanie/" class="mt-4 inline-block rounded-lg bg-brand px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie →</a>
        </section>
    </main>

    <footer class="mt-10 border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
