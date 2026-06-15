<?php
/**
 * Template Name: PT24.PRO - Dodaj Zlecenie
 * Description: Multi-step order form — describe service, select location, submit.
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
    <meta name="description" content="Dodaj zlecenie na PT24.PRO — opisz czego potrzebujesz i otrzymaj oferty od zweryfikowanych fachowców.">
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
                        card: '0 4px 24px -4px rgba(15,23,42,0.08)',
                        glow: '0 0 40px -8px rgba(20,100,244,0.3)'
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

<div class="min-h-screen flex flex-col">
    <!-- HEADER -->
    <header class="border-b border-slate-200/60 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold tracking-tight">
                <span class="text-slate-900">PT24</span><span class="bg-gradient-to-r from-brand-start to-brand-end bg-clip-text text-transparent">.PRO</span>
            </a>
            <span class="text-sm text-slate-500">Dodawanie zlecenia</span>
        </div>
    </header>

    <main class="flex-1">
        <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8 lg:py-16">
            <!-- Progress Steps -->
            <div class="mb-10">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col items-center">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-start text-sm font-bold text-white">1</span>
                        <span class="mt-1.5 text-xs font-medium text-brand-start">Opis</span>
                    </div>
                    <div class="mx-2 h-0.5 flex-1 bg-slate-200"></div>
                    <div class="flex flex-col items-center">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-200 bg-white text-sm font-bold text-slate-400">2</span>
                        <span class="mt-1.5 text-xs font-medium text-slate-400">Szczegóły</span>
                    </div>
                    <div class="mx-2 h-0.5 flex-1 bg-slate-200"></div>
                    <div class="flex flex-col items-center">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-200 bg-white text-sm font-bold text-slate-400">3</span>
                        <span class="mt-1.5 text-xs font-medium text-slate-400">Kontakt</span>
                    </div>
                </div>
            </div>

            <!-- FORM -->
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card sm:p-8">
                <input type="hidden" name="action" value="pt24_submit_order">
                <?php wp_nonce_field('pt24_order_nonce', '_pt24nonce'); ?>

                <h1 class="font-display text-2xl font-bold">Opisz zlecenie</h1>
                <p class="mt-1 text-sm text-slate-500">Podaj podstawowe informacje, a my znajdziemy najlepszych fachowców.</p>

                <!-- Category -->
                <div class="mt-8">
                    <label for="kategoria" class="block text-sm font-medium text-slate-700">Kategoria usługi <span class="text-red-500">*</span></label>
                    <select id="kategoria" name="kategoria" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-start focus:ring-2 focus:ring-brand-start/20 focus:outline-none">
                        <option value="">Wybierz kategorię…</option>
                        <option value="hydraulik">Hydraulik</option>
                        <option value="elektryk">Elektryk</option>
                        <option value="mechanik">Mechanik samochodowy</option>
                        <option value="klimatyzacja">Klimatyzacja i wentylacja</option>
                        <option value="informatyk">Informatyk / IT</option>
                        <option value="zlota-raczka">Złota rączka</option>
                        <option value="malarz">Malarz / Wykończenia</option>
                        <option value="przeprowadzki">Przeprowadzki</option>
                        <option value="ogrodnik">Ogrodnik</option>
                        <option value="inne">Inne</option>
                    </select>
                </div>

                <!-- Title -->
                <div class="mt-5">
                    <label for="tytul" class="block text-sm font-medium text-slate-700">Tytuł zlecenia <span class="text-red-500">*</span></label>
                    <input type="text" id="tytul" name="tytul" required minlength="5" maxlength="120" placeholder="np. Naprawa cieknącego kranu w kuchni"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm placeholder:text-slate-400 focus:border-brand-start focus:ring-2 focus:ring-brand-start/20 focus:outline-none">
                </div>

                <!-- Description -->
                <div class="mt-5">
                    <label for="opis" class="block text-sm font-medium text-slate-700">Opis problemu <span class="text-red-500">*</span></label>
                    <textarea id="opis" name="opis" required minlength="20" maxlength="2000" rows="5" placeholder="Opisz szczegóły zlecenia: co trzeba zrobić, kiedy, jakie materiały mogą być potrzebne…"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm placeholder:text-slate-400 focus:border-brand-start focus:ring-2 focus:ring-brand-start/20 focus:outline-none resize-y"></textarea>
                    <p class="mt-1 text-xs text-slate-400">Min. 20 znaków</p>
                </div>

                <!-- Location -->
                <div class="mt-5">
                    <label for="lokalizacja" class="block text-sm font-medium text-slate-700">Lokalizacja <span class="text-red-500">*</span></label>
                    <input type="text" id="lokalizacja" name="lokalizacja" required minlength="2" placeholder="np. Katowice, Ligota"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm placeholder:text-slate-400 focus:border-brand-start focus:ring-2 focus:ring-brand-start/20 focus:outline-none">
                </div>

                <!-- Budget -->
                <div class="mt-5">
                    <label for="budzet" class="block text-sm font-medium text-slate-700">Budżet (orientacyjny)</label>
                    <select id="budzet" name="budzet" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-brand-start focus:ring-2 focus:ring-brand-start/20 focus:outline-none">
                        <option value="">Nie podaję</option>
                        <option value="do-500">Do 500 zł</option>
                        <option value="500-1000">500 – 1 000 zł</option>
                        <option value="1000-3000">1 000 – 3 000 zł</option>
                        <option value="3000-10000">3 000 – 10 000 zł</option>
                        <option value="powyzej-10000">Powyżej 10 000 zł</option>
                    </select>
                </div>

                <!-- Photos -->
                <div class="mt-5">
                    <label for="zdjecia" class="block text-sm font-medium text-slate-700">Zdjęcia (opcjonalne)</label>
                    <div class="mt-1.5 flex items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-8">
                        <div class="text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                            <p class="mt-2 text-sm text-slate-500">Przeciągnij pliki lub <label for="zdjecia" class="cursor-pointer font-semibold text-brand-start hover:underline">wybierz z dysku</label></p>
                            <p class="mt-1 text-xs text-slate-400">JPG, PNG do 5 MB. Maks. 5 plików.</p>
                            <input id="zdjecia" name="zdjecia[]" type="file" accept="image/jpeg,image/png" multiple class="sr-only">
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="mt-8 flex items-center justify-between">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Wróć</a>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-start to-brand-end px-8 py-3 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow">
                        Dalej — szczegóły →
                    </button>
                </div>
            </form>

            <!-- Info box -->
            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500">
                <p class="font-semibold text-slate-700">ℹ️ Jak to działa?</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li>Dodaj zlecenie — to darmowe i zajmuje &lt; 2 min.</li>
                    <li>Otrzymasz oferty od fachowców z Twojej okolicy.</li>
                    <li>Porównaj ceny, opinie i wybierz najlepszego.</li>
                </ul>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
            &copy; <?php echo esc_html(gmdate('Y')); ?> PT24.PRO — Marketplace usług lokalnych
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
