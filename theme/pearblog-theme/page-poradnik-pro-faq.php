<?php
/**
 * Template Name: Poradnik.PRO - FAQ
 * Description: Single FAQ page (/faq/{slug})
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
$faq_question = get_the_title() ?: 'Czy mogę odliczyć remont od podatku?';
?>

<div class="min-h-screen">
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <a href="/pytania/" class="text-sm font-medium text-slate-600 hover:text-brand">Wszystkie FAQ</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- PYTANIE -->
        <section class="mb-8">
            <h1 class="font-display text-2xl font-bold leading-tight sm:text-3xl"><?php echo esc_html($faq_question); ?></h1>
            <p class="mt-2 text-sm text-slate-500">Kategoria: <a href="/finanse/" class="text-brand hover:underline">Finanse</a> · Aktualizacja: <?php echo esc_html(gmdate('d.m.Y')); ?></p>
        </section>

        <!-- ODPOWIEDŹ -->
        <section class="mb-10 rounded-xl border border-slate-200 bg-slate-50 p-6">
            <div class="prose prose-slate max-w-none text-sm">
                <p>Tak, w pewnych przypadkach możesz odliczyć koszty remontu od podatku. Dotyczy to przede wszystkim osób prowadzących działalność gospodarczą, które remontują lokal użytkowy lub mieszkanie wykorzystywane w działalności.</p>
                <p>Osoby fizyczne mogą skorzystać z ulgi termomodernizacyjnej (do 53 000 zł) na określone prace związane z ociepleniem budynku, wymianą okien czy instalacją paneli słonecznych.</p>
                <h3>Warunki odliczenia:</h3>
                <ul>
                    <li>Remont dotyczy nieruchomości wykorzystywanej w działalności</li>
                    <li>Posiadasz faktury dokumentujące wydatki</li>
                    <li>Prace kwalifikują się jako termomodernizacja (dla osób prywatnych)</li>
                </ul>
            </div>
            <div class="mt-4 flex items-center gap-3 border-t border-slate-200 pt-4 text-xs text-slate-500">
                <span>Odpowiedź: <strong class="text-slate-700">dr Tomasz Nowak</strong></span>
                <span>· Ekspert finansowy · ★ 4.8</span>
            </div>
        </section>

        <!-- POWIĄZANE PYTANIA -->
        <section class="mb-10">
            <h2 class="mb-4 font-display text-lg font-bold">Powiązane pytania</h2>
            <div class="space-y-2">
                <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm font-medium hover:border-brand/30">Jakie wydatki można odliczyć od podatku?</a>
                <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm font-medium hover:border-brand/30">Czy wymiana okien to remont czy modernizacja?</a>
                <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm font-medium hover:border-brand/30">Ulga termomodernizacyjna — kto może skorzystać?</a>
                <a href="#" class="block rounded-lg border border-slate-200 p-3 text-sm font-medium hover:border-brand/30">Jak rozliczyć remont w działalności gospodarczej?</a>
            </div>
        </section>

        <!-- EKSPERCI -->
        <section class="mb-10">
            <h2 class="mb-4 font-display text-lg font-bold">Eksperci w tej dziedzinie</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold">dr Tomasz Nowak</p>
                    <p class="text-xs text-slate-500">Finanse · ★ 4.8 · 245 odpowiedzi</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold">Mec. Paweł Kowalczyk</p>
                    <p class="text-xs text-slate-500">Prawo podatkowe · ★ 4.7 · 132 odpowiedzi</p>
                </div>
            </div>
        </section>

        <!-- LEAD BOX -->
        <section class="rounded-2xl bg-brand-light/50 p-6 text-center">
            <h2 class="font-display text-lg font-bold">Masz dodatkowe pytanie?</h2>
            <p class="mt-1 text-sm text-slate-600">Zadaj pytanie ekspertowi — odpowiedź w ciągu 24h, za darmo.</p>
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
