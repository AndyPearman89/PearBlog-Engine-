<?php
/**
 * Template Name: Poradnik.PRO - Strona Główna V5
 * Description: Full IA homepage — Hero, Search, Stats, Categories, Specialists, Q&A, Rankings, Calculators, Reviews, Lead Engine.
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
    <meta name="description" content="Poradnik.PRO — od problemu do decyzji w kilka minut. Poradniki, rankingi, kalkulatory i zweryfikowani eksperci.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#2563EB', dark: '#1D4ED8', light: '#DBEAFE' },
                        accent: { DEFAULT: '#F59E0B', dark: '#D97706' },
                        surface: { DEFAULT: '#F8FAFC', alt: '#F1F5F9' }
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
<body <?php body_class('bg-white text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<div class="min-h-screen">
    <!-- HEADER -->
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold tracking-tight">
                <span class="text-brand">Poradnik</span><span class="text-slate-900">.PRO</span>
            </a>
            <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex">
                <a href="/poradniki/" class="hover:text-brand">Poradniki</a>
                <a href="/pytania/" class="hover:text-brand">Pytania</a>
                <a href="/specjalisci/" class="hover:text-brand">Specjaliści</a>
                <a href="/rankingi/" class="hover:text-brand">Rankingi</a>
                <a href="/kalkulatory/" class="hover:text-brand">Kalkulatory</a>
            </nav>
            <div class="flex items-center gap-3">
                <a href="/konto/" class="hidden text-sm font-medium text-slate-600 hover:text-brand sm:inline-flex">Moje konto</a>
                <a href="/zadaj-pytanie/" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie</a>
            </div>
        </div>
    </header>

    <main>
        <!-- ══════ HERO ══════ -->
        <section class="bg-gradient-to-b from-brand-light/40 to-white py-16 lg:py-24">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
                <h1 class="font-display text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">
                    Od problemu do <span class="text-brand">decyzji</span><br>w kilka minut.
                </h1>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-500">
                    Poradniki, porównania, rankingi i zweryfikowani eksperci w jednym miejscu. Nie szukasz dalej — tu kończy się research.
                </p>

                <!-- SEARCH -->
                <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="mx-auto mt-8 max-w-2xl">
                    <div class="flex rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg shadow-slate-200/50">
                        <input type="search" name="s" placeholder="Czego szukasz? np. kredyt hipoteczny, remont łazienki…" aria-label="Wyszukaj" required
                            class="flex-1 rounded-lg border-0 bg-transparent px-4 py-3 text-sm focus:outline-none">
                        <button type="submit" class="rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white hover:bg-brand-dark">Szukaj</button>
                    </div>
                </form>

                <!-- STATS -->
                <div class="mx-auto mt-10 flex max-w-lg flex-wrap justify-center gap-8 text-sm text-slate-500">
                    <div><span class="block text-2xl font-bold text-slate-900">15 000+</span>poradników</div>
                    <div><span class="block text-2xl font-bold text-slate-900">42 000+</span>pytań</div>
                    <div><span class="block text-2xl font-bold text-slate-900">8 500+</span>ekspertów</div>
                </div>
            </div>
        </section>

        <!-- ══════ POPULARNE KATEGORIE ══════ -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="mb-8 text-center font-display text-2xl font-bold sm:text-3xl">Popularne kategorie</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <?php
                $categories = [
                    ['⚖️', 'Prawo', '/prawo/', 'Porady prawne, umowy, spadki'],
                    ['💰', 'Finanse', '/finanse/', 'Kredyty, inwestycje, podatki'],
                    ['🏠', 'Nieruchomości', '/nieruchomosci/', 'Kupno, sprzedaż, wynajem'],
                    ['🏗️', 'Budowa domu', '/budowa-domu/', 'Projekty, materiały, koszty'],
                    ['🚗', 'Motoryzacja', '/motoryzacja/', 'Zakup, serwis, ubezpieczenia'],
                    ['🏥', 'Zdrowie', '/zdrowie/', 'Leczenie, profilaktyka, NFZ'],
                    ['💼', 'Biznes', '/biznes/', 'Firma, marketing, prawo pracy'],
                    ['💻', 'Technologia', '/technologia/', 'Sprzęt, oprogramowanie, AI'],
                ];
                foreach ($categories as $cat) :
                ?>
                <a href="<?php echo esc_url($cat[2]); ?>" class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-brand/30 hover:shadow-md">
                    <span class="text-2xl"><?php echo $cat[0]; ?></span>
                    <div>
                        <span class="text-sm font-semibold text-slate-900 group-hover:text-brand"><?php echo esc_html($cat[1]); ?></span>
                        <p class="text-xs text-slate-400"><?php echo esc_html($cat[3]); ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ══════ POLECANI SPECJALIŚCI ══════ -->
        <section class="bg-surface py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex items-center justify-between">
                    <h2 class="font-display text-2xl font-bold">Polecani specjaliści</h2>
                    <a href="/specjalisci/" class="text-sm font-medium text-brand hover:underline">Zobacz wszystkich →</a>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <?php
                    $experts = [
                        ['Mec. Anna Kowalska', 'Prawo cywilne', '4.9', 312],
                        ['dr Tomasz Nowak', 'Finanse osobiste', '4.8', 245],
                        ['inż. Piotr Zieliński', 'Budownictwo', '4.9', 189],
                        ['Karolina Wiśniewska', 'Nieruchomości', '4.7', 156],
                    ];
                    foreach ($experts as $e) :
                    ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand/10 text-sm font-bold text-brand"><?php echo esc_html(mb_substr($e[0], 0, 1)); ?></div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900"><?php echo esc_html($e[0]); ?></h3>
                                <p class="text-xs text-slate-500"><?php echo esc_html($e[1]); ?></p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                            <span class="font-semibold text-amber-500">★ <?php echo esc_html($e[2]); ?></span>
                            <span><?php echo esc_html($e[3]); ?> odpowiedzi</span>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ══════ NAJNOWSZE PYTANIA ══════ -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <h2 class="font-display text-2xl font-bold">Najnowsze pytania</h2>
                <a href="/pytania/" class="text-sm font-medium text-brand hover:underline">Wszystkie pytania →</a>
            </div>
            <div class="space-y-4">
                <?php
                $questions = [
                    ['Czy mogę odliczyć remont od podatku?', 'Finanse', '3 odpowiedzi', '2 min temu'],
                    ['Jaki koszt budowy domu 120m² w 2026?', 'Budowa domu', '5 odpowiedzi', '8 min temu'],
                    ['Kredyt hipoteczny a umowa zlecenie', 'Finanse', '2 odpowiedzi', '15 min temu'],
                    ['Jak sprzedać mieszkanie bez pośrednika?', 'Nieruchomości', '7 odpowiedzi', '22 min temu'],
                ];
                foreach ($questions as $q) :
                ?>
                <a href="#" class="block rounded-xl border border-slate-200 bg-white p-4 transition hover:border-brand/30 hover:shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900"><?php echo esc_html($q[0]); ?></h3>
                            <div class="mt-1 flex items-center gap-3 text-xs text-slate-400">
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-600"><?php echo esc_html($q[1]); ?></span>
                                <span><?php echo esc_html($q[2]); ?></span>
                            </div>
                        </div>
                        <span class="whitespace-nowrap text-xs text-slate-400"><?php echo esc_html($q[3]); ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ══════ POPULARNE RANKINGI ══════ -->
        <section class="bg-surface py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-8 font-display text-2xl font-bold">Popularne rankingi</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <?php
                    $rankings = [
                        ['Najlepsze konta osobiste 2026', '/ranking/konta-osobiste/', 'Finanse'],
                        ['Ranking firm budowlanych', '/ranking/firmy-budowlane/', 'Budowa domu'],
                        ['Najlepsze ubezpieczenia OC/AC', '/ranking/ubezpieczenia-oc-ac/', 'Motoryzacja'],
                        ['Kredyty hipoteczne — porównanie', '/ranking/kredyty-hipoteczne/', 'Finanse'],
                        ['Najlepsi prawnicy online', '/ranking/prawnicy-online/', 'Prawo'],
                        ['Ranking kancelarii podatkowych', '/ranking/kancelarie-podatkowe/', 'Biznes'],
                    ];
                    foreach ($rankings as $r) :
                    ?>
                    <a href="<?php echo esc_url($r[1]); ?>" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                        <span class="text-xs font-medium text-brand"><?php echo esc_html($r[2]); ?></span>
                        <h3 class="mt-1 text-sm font-semibold text-slate-900"><?php echo esc_html($r[0]); ?></h3>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ══════ KALKULATORY ══════ -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="mb-8 font-display text-2xl font-bold">Kalkulatory</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $calculators = [
                    ['🧮', 'Kalkulator kredytu hipotecznego', '/kalkulator/kredyt-hipoteczny/'],
                    ['🏠', 'Kalkulator kosztów budowy domu', '/kalkulator/koszt-budowy-domu/'],
                    ['📊', 'Kalkulator zdolności kredytowej', '/kalkulator/zdolnosc-kredytowa/'],
                    ['💰', 'Kalkulator oszczędności', '/kalkulator/oszczednosci/'],
                    ['🚗', 'Kalkulator kosztów samochodu', '/kalkulator/koszt-samochodu/'],
                    ['📈', 'Kalkulator ROI inwestycji', '/kalkulator/roi-inwestycji/'],
                ];
                foreach ($calculators as $c) :
                ?>
                <a href="<?php echo esc_url($c[2]); ?>" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-brand/30 hover:shadow-sm">
                    <span class="text-2xl"><?php echo $c[0]; ?></span>
                    <span class="text-sm font-semibold text-slate-900"><?php echo esc_html($c[1]); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ══════ OPINIE ══════ -->
        <section class="bg-surface py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-8 text-center font-display text-2xl font-bold">Co mówią użytkownicy</h2>
                <div class="grid gap-5 sm:grid-cols-3">
                    <?php
                    $reviews = [
                        ['Dzięki kalkulatorowi wiedziałem dokładnie na co mnie stać. Oszczędziłem 30 000 zł na budowie.', 'Marcin K.', 'Budowa domu'],
                        ['Znalazłam prawnika w 10 minut. Sprawa spadkowa załatwiona profesjonalnie.', 'Katarzyna M.', 'Prawo'],
                        ['Ranking kredytów pomógł mi wybrać najlepszą ofertę. Polecam każdemu!', 'Tomasz W.', 'Finanse'],
                    ];
                    foreach ($reviews as $rev) :
                    ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-6">
                        <div class="mb-3 text-amber-400">★★★★★</div>
                        <p class="text-sm text-slate-600">„<?php echo esc_html($rev[0]); ?>"</p>
                        <p class="mt-4 text-sm font-semibold text-slate-900"><?php echo esc_html($rev[1]); ?></p>
                        <p class="text-xs text-slate-400"><?php echo esc_html($rev[2]); ?></p>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ══════ LEAD ENGINE ══════ -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-slate-900 p-8 text-center text-white sm:p-12">
                <h2 class="font-display text-2xl font-bold sm:text-3xl">Potrzebujesz indywidualnej porady?</h2>
                <p class="mx-auto mt-3 max-w-lg text-sm text-slate-300">Opisz swój problem — dopasujemy najlepszego eksperta do Twojej sytuacji. Odpowiedź w ciągu 24h.</p>
                <form class="mx-auto mt-6 flex max-w-md flex-col gap-3 sm:flex-row">
                    <input type="email" placeholder="Twój e-mail" aria-label="E-mail" required class="flex-1 rounded-lg px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand">
                    <button type="submit" class="rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white hover:bg-brand-dark">Wyślij zapytanie</button>
                </form>
            </div>
        </section>

        <!-- ══════ NAJPOPULARNIEJSZE WYSZUKIWANIA ══════ -->
        <section class="border-t border-slate-100 bg-surface-alt py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-5 text-lg font-bold text-slate-900">Najpopularniejsze wyszukiwania</h2>
                <div class="flex flex-wrap gap-2">
                    <?php
                    $searches = ['kredyt hipoteczny', 'koszt budowy domu', 'remont łazienki', 'rozwód koszty', 'pompa ciepła', 'fotowoltaika', 'ubezpieczenie OC', 'konto firmowe', 'prawnik online', 'dietetyk'];
                    foreach ($searches as $s) :
                    ?>
                    <a href="/<?php echo esc_attr(sanitize_title($s)); ?>/" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:border-brand/40 hover:text-brand"><?php echo esc_html($s); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <span class="font-display text-lg font-bold"><span class="text-brand">Poradnik</span>.PRO</span>
                    <p class="mt-3 max-w-xs text-sm text-slate-500">Od problemu do decyzji. Łączymy wiedzę, narzędzia i ekspertów w jednym miejscu.</p>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900">Produkty</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-500">
                        <li><a href="/poradniki/" class="hover:text-slate-900">Poradniki</a></li>
                        <li><a href="/pytania/" class="hover:text-slate-900">Pytania</a></li>
                        <li><a href="/rankingi/" class="hover:text-slate-900">Rankingi</a></li>
                        <li><a href="/kalkulatory/" class="hover:text-slate-900">Kalkulatory</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900">Kategorie</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-500">
                        <li><a href="/prawo/" class="hover:text-slate-900">Prawo</a></li>
                        <li><a href="/finanse/" class="hover:text-slate-900">Finanse</a></li>
                        <li><a href="/nieruchomosci/" class="hover:text-slate-900">Nieruchomości</a></li>
                        <li><a href="/budowa-domu/" class="hover:text-slate-900">Budowa domu</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900">Informacje</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-500">
                        <li><a href="/dla-specjalistow/" class="hover:text-slate-900">Dla specjalistów</a></li>
                        <li><a href="/cennik/" class="hover:text-slate-900">Cennik</a></li>
                        <li><a href="/kontakt/" class="hover:text-slate-900">Kontakt</a></li>
                        <li><a href="/regulamin/" class="hover:text-slate-900">Regulamin</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-10 border-t border-slate-100 pt-6 text-center text-xs text-slate-400">
                &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO — Wszelkie prawa zastrzeżone
            </div>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
