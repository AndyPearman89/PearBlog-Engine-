<?php
/**
 * Template Name: Poradnik.PRO - Porównanie
 * Description: Single comparison page (A vs B decision view with specs, pricing, verdict).
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
$option_a = get_field('option_a') ?: 'Pompa ciepła';
$option_b = get_field('option_b') ?: 'Gaz ziemny';
$title = esc_html($option_a) . ' vs ' . esc_html($option_b);
$verdict = get_field('verdict') ?: 'Pompa ciepła wygrywa w długoterminowej perspektywie kosztowej.';
?>

<!-- Breadcrumb -->
<nav class="max-w-5xl mx-auto px-4 py-4 text-sm text-slate-500">
    <a href="/" class="hover:text-brand">Strona główna</a>
    <span class="mx-1">/</span>
    <a href="/porownania/" class="hover:text-brand">Porównania</a>
    <span class="mx-1">/</span>
    <span class="text-slate-800"><?php echo $title; ?></span>
</nav>

<!-- Hero -->
<header class="max-w-5xl mx-auto px-4 py-8 text-center">
    <span class="inline-block bg-blue-100 text-brand font-semibold text-xs uppercase tracking-wider px-3 py-1 rounded-full mb-4">🆚 Porównanie</span>
    <h1 class="font-display text-3xl md:text-4xl font-extrabold text-slate-900 mb-4"><?php echo $title; ?></h1>
    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Jasne różnice, realne koszty, konkretny werdykt — podejmij najlepszą decyzję.</p>
</header>

<!-- Comparison Table -->
<section class="max-w-5xl mx-auto px-4 py-8">
    <div class="grid md:grid-cols-2 gap-6">
        <!-- Option A -->
        <div class="border-2 border-brand/20 rounded-2xl p-6 bg-blue-50/30">
            <div class="text-center mb-6">
                <span class="text-4xl">🅰️</span>
                <h2 class="font-display text-2xl font-bold mt-2"><?php echo esc_html($option_a); ?></h2>
            </div>
            <ul class="space-y-3">
                <li class="flex items-start gap-2"><span class="text-green-500 mt-0.5">✓</span><span>Niższe koszty eksploatacji w długiej perspektywie</span></li>
                <li class="flex items-start gap-2"><span class="text-green-500 mt-0.5">✓</span><span>Ekologiczne rozwiązanie</span></li>
                <li class="flex items-start gap-2"><span class="text-green-500 mt-0.5">✓</span><span>Możliwość dofinansowania</span></li>
                <li class="flex items-start gap-2"><span class="text-red-500 mt-0.5">✗</span><span>Wyższy koszt początkowy</span></li>
                <li class="flex items-start gap-2"><span class="text-red-500 mt-0.5">✗</span><span>Wymaga odpowiedniego terenu</span></li>
            </ul>
        </div>
        <!-- Option B -->
        <div class="border-2 border-amber-200 rounded-2xl p-6 bg-amber-50/30">
            <div class="text-center mb-6">
                <span class="text-4xl">🅱️</span>
                <h2 class="font-display text-2xl font-bold mt-2"><?php echo esc_html($option_b); ?></h2>
            </div>
            <ul class="space-y-3">
                <li class="flex items-start gap-2"><span class="text-green-500 mt-0.5">✓</span><span>Niższy koszt instalacji</span></li>
                <li class="flex items-start gap-2"><span class="text-green-500 mt-0.5">✓</span><span>Sprawdzona technologia</span></li>
                <li class="flex items-start gap-2"><span class="text-green-500 mt-0.5">✓</span><span>Szybki montaż</span></li>
                <li class="flex items-start gap-2"><span class="text-red-500 mt-0.5">✗</span><span>Rosnące ceny paliwa</span></li>
                <li class="flex items-start gap-2"><span class="text-red-500 mt-0.5">✗</span><span>Emisja CO₂</span></li>
            </ul>
        </div>
    </div>
</section>

<!-- Cost Comparison -->
<section class="max-w-5xl mx-auto px-4 py-8">
    <h2 class="font-display text-2xl font-bold text-center mb-6">💰 Porównanie kosztów</h2>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse rounded-xl overflow-hidden shadow-sm">
            <thead>
                <tr class="bg-slate-100">
                    <th class="text-left p-4 font-semibold">Kryterium</th>
                    <th class="text-center p-4 font-semibold text-brand"><?php echo esc_html($option_a); ?></th>
                    <th class="text-center p-4 font-semibold text-amber-600"><?php echo esc_html($option_b); ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr><td class="p-4">Koszt instalacji</td><td class="p-4 text-center">35 000 – 55 000 zł</td><td class="p-4 text-center">8 000 – 15 000 zł</td></tr>
                <tr class="bg-slate-50"><td class="p-4">Koszt roczny (ogrzewanie 150m²)</td><td class="p-4 text-center">3 000 – 4 500 zł</td><td class="p-4 text-center">6 000 – 9 000 zł</td></tr>
                <tr><td class="p-4">Żywotność</td><td class="p-4 text-center">15–25 lat</td><td class="p-4 text-center">15–20 lat</td></tr>
                <tr class="bg-slate-50"><td class="p-4">Dofinansowanie</td><td class="p-4 text-center">do 45 000 zł</td><td class="p-4 text-center">—</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Verdict -->
<section class="max-w-5xl mx-auto px-4 py-8">
    <div class="bg-gradient-to-br from-brand/5 to-blue-50 border border-brand/20 rounded-2xl p-8 text-center">
        <span class="text-3xl mb-4 block">🏆</span>
        <h2 class="font-display text-2xl font-bold mb-3">Werdykt</h2>
        <p class="text-lg text-slate-700 max-w-2xl mx-auto"><?php echo esc_html($verdict); ?></p>
    </div>
</section>

<!-- CTA -->
<section class="max-w-5xl mx-auto px-4 py-12 text-center">
    <h2 class="font-display text-2xl font-bold mb-4">Potrzebujesz pomocy w wyborze?</h2>
    <p class="text-slate-600 mb-6">Skontaktuj się z naszymi ekspertami, którzy doradzą najlepsze rozwiązanie.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="/specjalisci/" class="inline-flex items-center justify-center px-6 py-3 bg-brand text-white font-semibold rounded-xl hover:bg-brand-dark transition">🧑‍💼 Znajdź specjalistę</a>
        <a href="/kalkulator/" class="inline-flex items-center justify-center px-6 py-3 border-2 border-brand text-brand font-semibold rounded-xl hover:bg-blue-50 transition">🧮 Sprawdź koszty</a>
    </div>
</section>

<!-- Related Comparisons -->
<section class="max-w-5xl mx-auto px-4 py-12 border-t border-slate-100">
    <h2 class="font-display text-xl font-bold mb-6">Podobne porównania</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="/porownanie/fotowoltaika-vs-prad-z-sieci/" class="block p-4 bg-slate-50 rounded-xl hover:bg-blue-50 transition">
            <span class="font-semibold text-slate-800">Fotowoltaika vs prąd z sieci</span>
            <span class="block text-sm text-slate-500 mt-1">Sprawdź, co się bardziej opłaca</span>
        </a>
        <a href="/porownanie/remont-vs-pod-klucz/" class="block p-4 bg-slate-50 rounded-xl hover:bg-blue-50 transition">
            <span class="font-semibold text-slate-800">Remont vs wykończenie pod klucz</span>
            <span class="block text-sm text-slate-500 mt-1">Czas, koszty, wygoda</span>
        </a>
        <a href="/porownanie/okna-pvc-vs-drewniane/" class="block p-4 bg-slate-50 rounded-xl hover:bg-blue-50 transition">
            <span class="font-semibold text-slate-800">Okna PCV vs drewniane</span>
            <span class="block text-sm text-slate-500 mt-1">Izolacja, cena, estetyka</span>
        </a>
    </div>
</section>

<?php wp_footer(); ?>
</body>
</html>
