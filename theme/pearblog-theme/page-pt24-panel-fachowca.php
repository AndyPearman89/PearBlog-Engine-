<?php
/**
 * Template Name: PT24.PRO - Panel Fachowca
 * Description: Professional dashboard — manage profile, orders, and reviews.
 *
 * @package PearBlog
 * @version 6.1.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Panel fachowca — zarządzaj profilem, zleceniami i opiniami na PT24.PRO.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { start: '#1464F4', end: '#7A4FD3', mid: '#4A5FE3' },
                        pear: { green: '#4ADE80', blue: '#60A5FA' }
                    },
                    fontFamily: {
                        display: ['Poppins', 'system-ui', 'sans-serif'],
                        body: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-slate-50 text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<div class="min-h-screen">
    <!-- HEADER -->
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold tracking-tight">
                <span class="bg-gradient-to-r from-[#1464F4] to-[#7A4FD3] bg-clip-text text-transparent">PT24</span><span class="text-slate-900">.PRO</span>
            </a>
            <nav class="flex items-center gap-4 text-sm font-medium text-slate-600">
                <a href="<?php echo esc_url(PearBlog_PT24_Pro_Routing::url('for-professionals')); ?>" class="hover:text-brand-mid">Dla fachowców</a>
                <a href="<?php echo esc_url(PearBlog_PT24_Pro_Routing::url('pro-dashboard')); ?>" class="text-brand-mid font-semibold">Panel</a>
            </nav>
        </div>
    </header>

    <!-- DASHBOARD CONTENT -->
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="font-display text-2xl font-bold sm:text-3xl">Panel fachowca</h1>
            <p class="mt-1 text-slate-600">Zarządzaj swoim profilem, zleceniami i opiniami.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">Nowe zlecenia</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">—</div>
                <div class="mt-1 text-xs text-slate-400">Ostatnie 7 dni</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">Wyświetlenia profilu</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">—</div>
                <div class="mt-1 text-xs text-slate-400">Ostatnie 30 dni</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">Opinie</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">—</div>
                <div class="mt-1 text-xs text-slate-400">Łącznie</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">Pozycja w rankingu</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">—</div>
                <div class="mt-1 text-xs text-slate-400">Twoja kategoria</div>
            </div>
        </div>

        <!-- Sections -->
        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <!-- Orders -->
            <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-display text-lg font-bold">Zlecenia</h2>
                <p class="mt-2 text-sm text-slate-500">Aktywne zapytania od klientów w Twojej okolicy.</p>
                <div class="mt-6 text-center py-10 text-slate-400">
                    <p class="text-base">Brak nowych zleceń</p>
                    <p class="mt-1 text-sm">Zlecenia pojawią się tutaj, gdy klienci wyślą zapytanie.</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-display text-lg font-bold">Szybkie akcje</h2>
                <div class="mt-4 space-y-3">
                    <a href="#" class="flex items-center gap-3 rounded-lg border border-slate-100 p-3 text-sm font-medium text-slate-700 transition hover:border-brand-mid hover:text-brand-mid">
                        <span class="text-lg">👤</span> Edytuj profil
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-lg border border-slate-100 p-3 text-sm font-medium text-slate-700 transition hover:border-brand-mid hover:text-brand-mid">
                        <span class="text-lg">📸</span> Dodaj realizację
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-lg border border-slate-100 p-3 text-sm font-medium text-slate-700 transition hover:border-brand-mid hover:text-brand-mid">
                        <span class="text-lg">⭐</span> Poproś o opinię
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-lg border border-slate-100 p-3 text-sm font-medium text-slate-700 transition hover:border-brand-mid hover:text-brand-mid">
                        <span class="text-lg">📊</span> Statystyki
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="mt-12 border-t border-slate-200 bg-white py-8">
        <div class="mx-auto max-w-7xl px-4 text-center text-sm text-slate-500 sm:px-6 lg:px-8">
            <p>&copy; <?php echo esc_html(gmdate('Y')); ?> PT24.PRO — marketplace usług lokalnych.</p>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
