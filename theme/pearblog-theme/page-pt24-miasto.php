<?php
/**
 * Template Name: PT24.PRO - Miasto
 * Description: City landing page (e.g. /warszawa/, /krakow/).
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
                    },
                    boxShadow: {
                        soft: '0 20px 60px -28px rgba(15,23,42,0.35)',
                        card: '0 4px 24px -4px rgba(15,23,42,0.08)'
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

<?php
$city_name = get_the_title() ?: 'Warszawa';
$city_slug = sanitize_title($city_name);
?>

<div class="min-h-screen">
    <!-- HEADER -->
    <header class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold tracking-tight">
                <span class="text-slate-900">PT24</span><span class="bg-gradient-to-r from-brand-start to-brand-end bg-clip-text text-transparent">.PRO</span>
            </a>
            <a href="/dodaj-zlecenie/" class="rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:shadow-lg">Dodaj zlecenie</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- BREADCRUMBS -->
        <nav aria-label="Ścieżka" class="mb-6 text-sm text-slate-500">
            <ol class="flex items-center gap-1.5">
                <li><a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-brand-start">Strona główna</a></li>
                <li class="text-slate-300">/</li>
                <li><a href="/miasta/" class="hover:text-brand-start">Miasta</a></li>
                <li class="text-slate-300">/</li>
                <li class="font-medium text-slate-900"><?php echo esc_html($city_name); ?></li>
            </ol>
        </nav>

        <!-- HERO -->
        <section class="mb-12 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 text-white sm:p-12">
            <h1 class="font-display text-3xl font-bold sm:text-4xl">Fachowcy w mieście <?php echo esc_html($city_name); ?></h1>
            <p class="mt-3 max-w-xl text-sm text-slate-300">Zweryfikowani specjaliści gotowi do pracy. Znajdź najlepszego fachowca w kategorii, którą potrzebujesz.</p>
            <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="mt-6 flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="lokalizacja" value="<?php echo esc_attr($city_name); ?>">
                <input type="text" name="usluga" placeholder="Czego szukasz? np. hydraulik" aria-label="Rodzaj usługi" required minlength="2"
                    class="h-12 flex-1 rounded-xl border-0 px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-start/30 focus:outline-none">
                <button type="submit" class="h-12 rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-8 text-sm font-semibold text-white transition hover:opacity-90">Szukaj</button>
            </form>
        </section>

        <!-- CATEGORIES IN CITY -->
        <section class="mb-12">
            <h2 class="mb-6 font-display text-2xl font-bold">Popularne usługi — <?php echo esc_html($city_name); ?></h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $city_categories = [
                    ['Hydraulik', '/hydraulik/' . $city_slug . '/', '💧', '45 fachowców'],
                    ['Elektryk', '/elektryk/' . $city_slug . '/', '⚡', '38 fachowców'],
                    ['Mechanik', '/mechanik/' . $city_slug . '/', '🔧', '29 fachowców'],
                    ['Klimatyzacja', '/klimatyzacja/' . $city_slug . '/', '❄️', '22 fachowców'],
                    ['Informatyk', '/informatyk/' . $city_slug . '/', '💻', '51 fachowców'],
                    ['Złota rączka', '/zlota-raczka/' . $city_slug . '/', '🛠️', '33 fachowców'],
                ];
                foreach ($city_categories as $cat) :
                ?>
                <a href="<?php echo esc_url($cat[1]); ?>" class="group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-soft hover:border-brand-start/30">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-2xl group-hover:bg-brand-start/5"><?php echo $cat[2]; ?></span>
                    <div class="flex-1">
                        <span class="text-sm font-semibold text-slate-900"><?php echo esc_html($cat[0]); ?></span>
                        <p class="text-xs text-slate-400"><?php echo esc_html($cat[3]); ?></p>
                    </div>
                    <svg class="h-4 w-4 text-slate-300 transition group-hover:text-brand-start" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- TOP SPECIALISTS -->
        <section class="mb-12">
            <h2 class="mb-6 font-display text-2xl font-bold">Najlepiej oceniani — <?php echo esc_html($city_name); ?></h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $top_specialists = [
                    ['Jan Kowalski', 'Hydraulik', '4.9', 128],
                    ['Marek Zieliński', 'Elektryk', '4.8', 96],
                    ['Anna Nowak', 'Informatyk', '4.9', 74],
                ];
                foreach ($top_specialists as $spec) :
                ?>
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-start/10 text-sm font-bold text-brand-start">
                            <?php echo esc_html(mb_substr($spec[0], 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900"><?php echo esc_html($spec[0]); ?></h3>
                            <p class="text-xs text-slate-500"><?php echo esc_html($spec[1]); ?></p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-3 text-sm text-slate-600">
                        <span class="font-semibold text-amber-500">★ <?php echo esc_html($spec[2]); ?></span>
                        <span><?php echo esc_html($spec[3]); ?> zleceń</span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- DISTRICTS -->
        <section class="mb-12">
            <h2 class="mb-4 text-xl font-bold">Dzielnice — <?php echo esc_html($city_name); ?></h2>
            <div class="flex flex-wrap gap-2">
                <?php
                $districts = ['Śródmieście', 'Mokotów', 'Wola', 'Praga-Południe', 'Ursynów', 'Bielany', 'Bemowo', 'Targówek', 'Ochota', 'Żoliborz'];
                foreach ($districts as $d) :
                ?>
                <a href="/<?php echo esc_attr($city_slug); ?>/<?php echo esc_attr(sanitize_title($d)); ?>/" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-brand-start/40 hover:bg-brand-start/5 hover:text-brand-start">
                    <?php echo esc_html($d); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- SEO CONTENT -->
        <section class="rounded-2xl bg-white p-8 shadow-card ring-1 ring-slate-200/60">
            <h2 class="text-xl font-bold text-slate-900">Usługi fachowców — <?php echo esc_html($city_name); ?></h2>
            <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                <p>Szukasz sprawdzonego fachowca w mieście <?php echo esc_html($city_name); ?>? Na PT24.PRO znajdziesz zweryfikowanych specjalistów z Twojej okolicy. Porównaj profile, przeczytaj opinie innych klientów i wybierz najlepszą ofertę.</p>
                <p>Niezależnie czy potrzebujesz hydraulika, elektryka czy informatyka — nasi fachowcy są dostępni 24/7 i gotowi do realizacji Twojego zlecenia.</p>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="mt-10 border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-4 text-xs text-slate-400">
                <a href="/warszawa/" class="hover:text-slate-600">Warszawa</a>
                <a href="/krakow/" class="hover:text-slate-600">Kraków</a>
                <a href="/katowice/" class="hover:text-slate-600">Katowice</a>
                <a href="/wroclaw/" class="hover:text-slate-600">Wrocław</a>
                <a href="/poznan/" class="hover:text-slate-600">Poznań</a>
                <a href="/gdansk/" class="hover:text-slate-600">Gdańsk</a>
                <a href="/lodz/" class="hover:text-slate-600">Łódź</a>
            </div>
            <p class="mt-4 text-center text-xs text-slate-400">&copy; <?php echo esc_html(gmdate('Y')); ?> PT24.PRO — Marketplace usług lokalnych</p>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
