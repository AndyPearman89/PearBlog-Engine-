<?php
/**
 * Template Name: Poradnik.PRO - Profil Specjalisty
 * Description: Expert profile page (/specjalista/{slug})
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
$expert_name = get_the_title() ?: 'Mec. Anna Kowalska';
?>

<div class="min-h-screen">
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold"><span class="text-brand">Poradnik</span>.PRO</a>
            <a href="/specjalisci/" class="text-sm font-medium text-slate-600 hover:text-brand">Wszyscy specjaliści</a>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- HERO -->
        <section class="mb-10 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
            <div class="flex flex-col items-start gap-6 sm:flex-row">
                <!-- ZDJĘCIE -->
                <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-3xl font-bold text-brand">
                    <?php echo esc_html(mb_substr($expert_name, 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="font-display text-2xl font-bold"><?php echo esc_html($expert_name); ?></h1>
                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Zweryfikowany</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Prawo cywilne · Prawo spadkowe · Warszawa</p>

                    <!-- OCENY -->
                    <div class="mt-4 flex items-center gap-6 text-sm">
                        <div><span class="text-lg font-bold text-amber-500">★ 4.9</span><span class="text-slate-500"> / 5</span></div>
                        <div><span class="font-bold">312</span> <span class="text-slate-500">odpowiedzi</span></div>
                        <div><span class="font-bold">98%</span> <span class="text-slate-500">poleca</span></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- OPINIE -->
        <section class="mb-10">
            <h2 class="mb-4 font-display text-xl font-bold">Opinie (48)</h2>
            <div class="space-y-4">
                <?php
                $reviews = [
                    ['Profesjonalna i rzetelna pomoc. Polecam każdemu kto potrzebuje porady prawnej.', 'Marcin W.', '5/5', '3 dni temu'],
                    ['Szybka i konkretna odpowiedź. Sprawa spadkowa rozwiązana bez problemów.', 'Katarzyna M.', '5/5', '1 tydzień temu'],
                    ['Bardzo pomocna, wyjaśniła wszystkie zawiłości prawne prostym językiem.', 'Tomasz K.', '5/5', '2 tygodnie temu'],
                ];
                foreach ($reviews as $r) :
                ?>
                <article class="rounded-xl border border-slate-200 p-5">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="font-semibold text-amber-500"><?php echo esc_html($r[2]); ?></span>
                        <span>·</span>
                        <span><?php echo esc_html($r[3]); ?></span>
                    </div>
                    <p class="mt-2 text-sm text-slate-700">„<?php echo esc_html($r[0]); ?>"</p>
                    <p class="mt-2 text-xs font-medium text-slate-500">— <?php echo esc_html($r[1]); ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- SPECJALIZACJE -->
        <section class="mb-10">
            <h2 class="mb-4 font-display text-xl font-bold">Specjalizacje</h2>
            <div class="flex flex-wrap gap-2">
                <?php
                $specs = ['Prawo cywilne', 'Prawo spadkowe', 'Prawo rodzinne', 'Umowy', 'Nieruchomości', 'Odszkodowania'];
                foreach ($specs as $sp) :
                ?>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700"><?php echo esc_html($sp); ?></span>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- O EKSPERCIE -->
        <section class="mb-10">
            <h2 class="mb-4 font-display text-xl font-bold">O ekspercie</h2>
            <div class="text-sm leading-relaxed text-slate-600">
                <p>Adwokat z 15-letnim doświadczeniem. Specjalizuję się w prawie cywilnym, spadkowym i rodzinnym. Ukończyłam Wydział Prawa i Administracji Uniwersytetu Warszawskiego. Członek Okręgowej Rady Adwokackiej w Warszawie.</p>
                <p class="mt-2">Na Poradnik.PRO udzielam porad prawnych od 2024 roku. Odpowiedziałam na ponad 300 pytań użytkowników z oceną 4.9/5.</p>
            </div>
        </section>

        <!-- ARTYKUŁY EKSPERTA -->
        <section class="mb-10">
            <h2 class="mb-4 font-display text-xl font-bold">Artykuły</h2>
            <div class="space-y-3">
                <a href="/poradnik/jak-napisac-testament/" class="block rounded-lg border border-slate-200 p-4 hover:shadow-sm">
                    <h3 class="text-sm font-semibold">Jak napisać testament — krok po kroku</h3>
                    <p class="mt-1 text-xs text-slate-400">12 min czytania · 2 450 wyświetleń</p>
                </a>
                <a href="/poradnik/spadek-poradnik/" class="block rounded-lg border border-slate-200 p-4 hover:shadow-sm">
                    <h3 class="text-sm font-semibold">Spadek — co musisz wiedzieć</h3>
                    <p class="mt-1 text-xs text-slate-400">10 min czytania · 1 890 wyświetleń</p>
                </a>
            </div>
        </section>

        <!-- ODPOWIEDZI EKSPERTA -->
        <section class="mb-10">
            <h2 class="mb-4 font-display text-xl font-bold">Ostatnie odpowiedzi</h2>
            <div class="space-y-3">
                <a href="#" class="block rounded-lg border border-slate-200 p-4 hover:shadow-sm">
                    <p class="text-xs text-slate-500">Odpowiedź na:</p>
                    <h3 class="mt-0.5 text-sm font-semibold">Czy mogę odziedziczyć długi po rodzicach?</h3>
                </a>
                <a href="#" class="block rounded-lg border border-slate-200 p-4 hover:shadow-sm">
                    <p class="text-xs text-slate-500">Odpowiedź na:</p>
                    <h3 class="mt-0.5 text-sm font-semibold">Jak wypisać się z testamentu?</h3>
                </a>
            </div>
        </section>

        <!-- FORMULARZ KONTAKTOWY -->
        <section class="mb-10 rounded-2xl border border-slate-200 bg-slate-50 p-6">
            <h2 class="mb-4 font-display text-xl font-bold">Wyślij wiadomość</h2>
            <form class="space-y-4">
                <div>
                    <label for="temat" class="block text-sm font-medium text-slate-700">Temat</label>
                    <input type="text" id="temat" name="temat" required placeholder="Czego dotyczy Twoje pytanie?" class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none">
                </div>
                <div>
                    <label for="wiadomosc" class="block text-sm font-medium text-slate-700">Wiadomość</label>
                    <textarea id="wiadomosc" name="wiadomosc" required rows="4" placeholder="Opisz swoją sytuację…" class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none resize-y"></textarea>
                </div>
                <button type="submit" class="rounded-lg bg-brand px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">Wyślij wiadomość</button>
            </form>
        </section>

        <!-- LEAD ENGINE -->
        <section class="rounded-2xl bg-brand-light/50 p-6 text-center">
            <h2 class="font-display text-lg font-bold">Potrzebujesz pilnej porady?</h2>
            <p class="mt-1 text-sm text-slate-600">Zadaj pytanie publicznie — eksperci odpowiedzą w ciągu 24h.</p>
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
