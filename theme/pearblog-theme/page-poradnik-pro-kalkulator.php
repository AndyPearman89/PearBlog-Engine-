<?php
/**
 * Template Name: Poradnik.PRO - Kalkulator (Single)
 * Description: Single calculator page (/kalkulator/{slug})
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
$calc_title = get_the_title() ?: 'Kalkulator kredytu hipotecznego';
?>

<div class="min-h-screen">
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <a href="/kalkulatory/" class="text-sm font-medium text-slate-600 hover:text-brand">Wszystkie kalkulatory</a>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <nav aria-label="Ścieżka" class="mb-4 text-sm text-slate-500">
            <a href="/" class="hover:text-brand">Strona główna</a> /
            <a href="/kalkulatory/" class="hover:text-brand">Kalkulatory</a> /
            <span class="text-slate-900"><?php echo esc_html($calc_title); ?></span>
        </nav>

        <h1 class="mb-8 font-display text-3xl font-bold"><?php echo esc_html($calc_title); ?></h1>

        <!-- KALKULATOR -->
        <section class="mb-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <form id="calculator-form" class="space-y-5">
                <div>
                    <label for="kwota" class="block text-sm font-medium text-slate-700">Kwota kredytu (zł)</label>
                    <input type="number" id="kwota" name="kwota" value="400000" min="50000" max="5000000" step="10000"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none">
                </div>
                <div>
                    <label for="okres" class="block text-sm font-medium text-slate-700">Okres (lat)</label>
                    <input type="number" id="okres" name="okres" value="25" min="5" max="35"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none">
                </div>
                <div>
                    <label for="oprocentowanie" class="block text-sm font-medium text-slate-700">Oprocentowanie (%)</label>
                    <input type="number" id="oprocentowanie" name="oprocentowanie" value="7.5" min="1" max="20" step="0.1"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none">
                </div>
                <button type="button" onclick="oblicz()" class="w-full rounded-lg bg-brand py-3 text-sm font-semibold text-white hover:bg-brand-dark">Oblicz</button>
            </form>
        </section>

        <!-- WYNIK -->
        <section id="wynik" class="mb-10 hidden rounded-2xl border-2 border-brand/20 bg-brand-light/30 p-6 sm:p-8">
            <h2 class="mb-4 font-display text-xl font-bold">Wynik</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-white p-4 text-center shadow-sm">
                    <p class="text-xs text-slate-500">Rata miesięczna</p>
                    <p id="rata" class="mt-1 text-2xl font-bold text-brand">—</p>
                </div>
                <div class="rounded-xl bg-white p-4 text-center shadow-sm">
                    <p class="text-xs text-slate-500">Całkowity koszt</p>
                    <p id="calkowity" class="mt-1 text-2xl font-bold text-slate-900">—</p>
                </div>
                <div class="rounded-xl bg-white p-4 text-center shadow-sm">
                    <p class="text-xs text-slate-500">Odsetki łącznie</p>
                    <p id="odsetki" class="mt-1 text-2xl font-bold text-slate-900">—</p>
                </div>
            </div>

            <!-- INTERPRETACJA -->
            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-sm font-bold text-slate-900">💡 Interpretacja</h3>
                <p id="interpretacja" class="mt-2 text-sm text-slate-600">Oblicz wynik, aby zobaczyć interpretację.</p>
            </div>
        </section>

        <!-- FAQ -->
        <section class="mb-10 rounded-xl border border-slate-200 p-6">
            <h2 class="mb-4 font-display text-lg font-bold">FAQ</h2>
            <div class="space-y-3">
                <details class="rounded-lg border border-slate-100 p-3">
                    <summary class="cursor-pointer text-sm font-semibold">Jak działa ten kalkulator?</summary>
                    <p class="mt-2 text-sm text-slate-600">Kalkulator oblicza ratę kredytu na podstawie wzoru annuitetowego (raty równe). Uwzględnia kwotę, okres i oprocentowanie nominalne.</p>
                </details>
                <details class="rounded-lg border border-slate-100 p-3">
                    <summary class="cursor-pointer text-sm font-semibold">Czy wynik jest dokładny?</summary>
                    <p class="mt-2 text-sm text-slate-600">Wynik jest orientacyjny. Ostateczna rata zależy od indywidualnej oferty banku, marży i dodatkowych kosztów.</p>
                </details>
            </div>
        </section>

        <!-- EKSPERCI -->
        <section class="mb-10">
            <h2 class="mb-4 font-display text-lg font-bold">Eksperci kredytowi</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold">dr Tomasz Nowak</p>
                    <p class="text-xs text-slate-500">Finanse · ★ 4.8 · 245 odpowiedzi</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold">Marcin Wiśniewski</p>
                    <p class="text-xs text-slate-500">Bankowość · ★ 4.7 · 178 odpowiedzi</p>
                </div>
            </div>
        </section>

        <!-- LEAD CTA -->
        <section class="rounded-2xl bg-slate-900 p-6 text-center text-white">
            <h2 class="font-display text-lg font-bold">Potrzebujesz indywidualnej analizy?</h2>
            <p class="mt-1 text-sm text-slate-300">Zapytaj eksperta o najlepszą ofertę dla Twojej sytuacji.</p>
            <a href="/zadaj-pytanie/?kategoria=finanse" class="mt-4 inline-block rounded-lg bg-brand px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">Zapytaj eksperta →</a>
        </section>
    </main>

    <footer class="mt-10 border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<script>
function oblicz() {
    const kwota = parseFloat(document.getElementById('kwota').value);
    const lata = parseInt(document.getElementById('okres').value);
    const roczne = parseFloat(document.getElementById('oprocentowanie').value);
    const miesieczne = roczne / 100 / 12;
    const n = lata * 12;
    const rata = kwota * (miesieczne * Math.pow(1 + miesieczne, n)) / (Math.pow(1 + miesieczne, n) - 1);
    const calkowity = rata * n;
    const odsetki = calkowity - kwota;

    document.getElementById('rata').textContent = rata.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' zł';
    document.getElementById('calkowity').textContent = calkowity.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' zł';
    document.getElementById('odsetki').textContent = odsetki.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' zł';
    document.getElementById('interpretacja').textContent = 'Przy kredycie ' + kwota.toLocaleString('pl-PL') + ' zł na ' + lata + ' lat z oprocentowaniem ' + roczne + '%, Twoja miesięczna rata wyniesie ok. ' + rata.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' zł. Łączny koszt odsetek to ' + odsetki.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' zł.';
    document.getElementById('wynik').classList.remove('hidden');
}
</script>

<?php wp_footer(); ?>
</body>
</html>
