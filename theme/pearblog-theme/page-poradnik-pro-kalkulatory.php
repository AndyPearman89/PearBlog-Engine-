<?php
/**
 * Template Name: Poradnik.PRO - Kalkulatory
 * Description: Calculator listing page (/kalkulatory/)
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
            <a href="/zadaj-pytanie/" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Zadaj pytanie</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="mb-4 font-display text-3xl font-bold">Kalkulatory</h1>
        <p class="mb-10 max-w-2xl text-base text-slate-500">Policz realne koszty zanim podejmiesz decyzję. Każdy kalkulator opiera się na aktualnych danych rynkowych.</p>

        <?php
        $calc_categories = [
            ['Kredyty', [
                ['Kalkulator kredytu hipotecznego', '/kalkulator/kredyt-hipoteczny/', 'Oblicz ratę i całkowity koszt kredytu'],
                ['Kalkulator zdolności kredytowej', '/kalkulator/zdolnosc-kredytowa/', 'Sprawdź ile możesz pożyczyć'],
                ['Kalkulator refinansowania', '/kalkulator/refinansowanie/', 'Sprawdź czy opłaca się zmienić bank'],
            ]],
            ['Budowa domu', [
                ['Kalkulator kosztów budowy', '/kalkulator/koszt-budowy-domu/', 'Oszacuj koszt budowy domu'],
                ['Kalkulator materiałów', '/kalkulator/materialy-budowlane/', 'Ile materiałów potrzebujesz'],
                ['Kalkulator termomodernizacji', '/kalkulator/termomodernizacja/', 'Ile zaoszczędzisz na ogrzewaniu'],
            ]],
            ['Nieruchomości', [
                ['Kalkulator wartości nieruchomości', '/kalkulator/wartosc-nieruchomosci/', 'Oszacuj wartość mieszkania/domu'],
                ['Kalkulator kosztów notariusza', '/kalkulator/koszt-notariusza/', 'Oblicz opłaty przy transakcji'],
                ['Kalkulator opłacalności wynajmu', '/kalkulator/wynajem-vs-zakup/', 'Wynajem czy zakup — co lepsze'],
            ]],
            ['Finanse', [
                ['Kalkulator oszczędności', '/kalkulator/oszczednosci/', 'Ile uzbierasz w danym czasie'],
                ['Kalkulator ROI inwestycji', '/kalkulator/roi-inwestycji/', 'Oblicz zwrot z inwestycji'],
                ['Kalkulator podatku PIT', '/kalkulator/podatek-pit/', 'Oblicz podatek dochodowy'],
            ]],
            ['Biznes', [
                ['Kalkulator kosztów firmy', '/kalkulator/koszty-firmy/', 'Ile kosztuje prowadzenie firmy'],
                ['Kalkulator ZUS', '/kalkulator/zus/', 'Oblicz składki ZUS'],
                ['Kalkulator marży', '/kalkulator/marza/', 'Policz marżę produktu'],
            ]],
            ['Motoryzacja', [
                ['Kalkulator kosztów samochodu', '/kalkulator/koszt-samochodu/', 'Ile kosztuje utrzymanie auta'],
                ['Kalkulator OC/AC', '/kalkulator/oc-ac/', 'Porównaj ubezpieczenia'],
                ['Kalkulator paliwa', '/kalkulator/paliwo/', 'Oblicz koszt podróży'],
            ]],
        ];

        foreach ($calc_categories as $group) :
        ?>
        <section class="mb-10">
            <h2 class="mb-4 font-display text-xl font-bold"><?php echo esc_html($group[0]); ?></h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($group[1] as $calc) : ?>
                <a href="<?php echo esc_url($calc[1]); ?>" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <h3 class="text-sm font-semibold text-slate-900"><?php echo esc_html($calc[0]); ?></h3>
                    <p class="mt-1 text-xs text-slate-500"><?php echo esc_html($calc[2]); ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </main>

    <footer class="border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
