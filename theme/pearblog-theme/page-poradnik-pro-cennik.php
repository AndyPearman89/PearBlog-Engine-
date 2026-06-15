<?php
/**
 * Template Name: Poradnik.PRO - Cennik
 * Description: Pricing / packages page (/cennik/)
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
            <a href="/rejestracja-ekspert/" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Załóż konto</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="mb-12 text-center">
            <h1 class="font-display text-3xl font-bold sm:text-4xl">Cennik</h1>
            <p class="mt-2 text-base text-slate-500">Wybierz plan dopasowany do Twoich potrzeb. Bez ukrytych opłat.</p>
        </div>

        <!-- PAKIETY -->
        <section class="mb-16 grid gap-6 lg:grid-cols-3">
            <!-- FREE -->
            <div class="rounded-2xl border border-slate-200 p-8 text-center">
                <h2 class="text-lg font-bold">Free</h2>
                <p class="mt-1 text-sm text-slate-500">Na start</p>
                <p class="mt-6"><span class="text-5xl font-bold">0</span><span class="text-lg text-slate-500"> zł/mies.</span></p>
                <ul class="mt-8 space-y-3 text-left text-sm text-slate-600">
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Profil eksperta</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> 5 odpowiedzi / miesiąc</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> 1 artykuł / miesiąc</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Podstawowe statystyki</li>
                    <li class="flex gap-2"><span class="text-slate-300">—</span> Leady</li>
                    <li class="flex gap-2"><span class="text-slate-300">—</span> Wyróżnienie profilu</li>
                    <li class="flex gap-2"><span class="text-slate-300">—</span> Odznaka Premium</li>
                </ul>
                <a href="/rejestracja-ekspert/" class="mt-8 block rounded-lg border border-slate-200 py-3 text-sm font-semibold hover:bg-slate-50">Rozpocznij za darmo</a>
            </div>

            <!-- PREMIUM -->
            <div class="relative rounded-2xl border-2 border-brand p-8 text-center shadow-lg">
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand px-4 py-1 text-xs font-semibold text-white">Najpopularniejszy</span>
                <h2 class="text-lg font-bold">Premium</h2>
                <p class="mt-1 text-sm text-slate-500">Dla aktywnych ekspertów</p>
                <p class="mt-6"><span class="text-5xl font-bold">149</span><span class="text-lg text-slate-500"> zł/mies.</span></p>
                <ul class="mt-8 space-y-3 text-left text-sm text-slate-600">
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Profil rozszerzony + galeria</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Nielimitowane odpowiedzi</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Nielimitowane artykuły</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Zaawansowane statystyki</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Do 20 leadów / miesiąc</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Wyróżnienie w wynikach</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Odznaka Premium</li>
                </ul>
                <a href="/rejestracja-ekspert/?plan=premium" class="mt-8 block rounded-lg bg-brand py-3 text-sm font-semibold text-white hover:bg-brand-dark">Wybierz Premium</a>
            </div>

            <!-- PREMIUM+ -->
            <div class="rounded-2xl border border-slate-200 p-8 text-center">
                <h2 class="text-lg font-bold">Premium+</h2>
                <p class="mt-1 text-sm text-slate-500">Dla firm i liderów</p>
                <p class="mt-6"><span class="text-5xl font-bold">349</span><span class="text-lg text-slate-500"> zł/mies.</span></p>
                <ul class="mt-8 space-y-3 text-left text-sm text-slate-600">
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Wszystko z Premium</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Nielimitowane leady</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Priorytetowe wyświetlanie</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Dedykowany opiekun</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> Raporty ROI</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> API dostęp</li>
                    <li class="flex gap-2"><span class="text-green-500">✓</span> White-label widget</li>
                </ul>
                <a href="/rejestracja-ekspert/?plan=premium-plus" class="mt-8 block rounded-lg border border-slate-200 py-3 text-sm font-semibold hover:bg-slate-50">Wybierz Premium+</a>
            </div>
        </section>

        <!-- PORÓWNANIE FUNKCJI -->
        <section class="mb-16">
            <h2 class="mb-6 text-center font-display text-2xl font-bold">Porównanie funkcji</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Funkcja</th>
                            <th class="px-4 py-3 text-center font-semibold">Free</th>
                            <th class="px-4 py-3 text-center font-semibold text-brand">Premium</th>
                            <th class="px-4 py-3 text-center font-semibold">Premium+</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr><td class="px-4 py-3">Profil eksperta</td><td class="px-4 py-3 text-center text-green-600">✓</td><td class="px-4 py-3 text-center text-green-600">✓ Rozszerzony</td><td class="px-4 py-3 text-center text-green-600">✓ Rozszerzony</td></tr>
                        <tr><td class="px-4 py-3">Odpowiedzi / mies.</td><td class="px-4 py-3 text-center">5</td><td class="px-4 py-3 text-center text-brand font-semibold">∞</td><td class="px-4 py-3 text-center font-semibold">∞</td></tr>
                        <tr><td class="px-4 py-3">Artykuły / mies.</td><td class="px-4 py-3 text-center">1</td><td class="px-4 py-3 text-center text-brand font-semibold">∞</td><td class="px-4 py-3 text-center font-semibold">∞</td></tr>
                        <tr><td class="px-4 py-3">Leady</td><td class="px-4 py-3 text-center text-slate-400">—</td><td class="px-4 py-3 text-center">do 20</td><td class="px-4 py-3 text-center font-semibold">∞</td></tr>
                        <tr><td class="px-4 py-3">Wyróżnienie w wynikach</td><td class="px-4 py-3 text-center text-slate-400">—</td><td class="px-4 py-3 text-center text-green-600">✓</td><td class="px-4 py-3 text-center text-green-600">✓ Priorytet</td></tr>
                        <tr><td class="px-4 py-3">Odznaka</td><td class="px-4 py-3 text-center text-slate-400">—</td><td class="px-4 py-3 text-center text-green-600">Premium</td><td class="px-4 py-3 text-center text-green-600">Premium+</td></tr>
                        <tr><td class="px-4 py-3">Statystyki</td><td class="px-4 py-3 text-center">Podstawowe</td><td class="px-4 py-3 text-center">Zaawansowane</td><td class="px-4 py-3 text-center">Zaawansowane + ROI</td></tr>
                        <tr><td class="px-4 py-3">Dedykowany opiekun</td><td class="px-4 py-3 text-center text-slate-400">—</td><td class="px-4 py-3 text-center text-slate-400">—</td><td class="px-4 py-3 text-center text-green-600">✓</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- CTA -->
        <section class="rounded-2xl bg-brand-light/50 p-8 text-center sm:p-12">
            <h2 class="font-display text-2xl font-bold">Nie wiesz, który plan wybrać?</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">Zacznij od planu Free — możesz zmienić na Premium w dowolnym momencie z panelu konta.</p>
            <a href="/rejestracja-ekspert/" class="mt-6 inline-block rounded-lg bg-brand px-8 py-3.5 text-sm font-semibold text-white hover:bg-brand-dark">Załóż konto za darmo →</a>
        </section>
    </main>

    <footer class="mt-14 border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
