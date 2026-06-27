<?php
/**
 * Specific service landing template (/{konkretna-usluga}).
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$service_slug = sanitize_title((string) get_query_var('pt24_specific_service'));
$service_label_map = [
    'montaz-klimatyzacji' => 'Montaż klimatyzacji',
    'udraznianie-kanalizacji' => 'Udrażnianie kanalizacji',
    'awaria-pradu' => 'Awaria prądu',
    'wymiana-dachu' => 'Wymiana dachu',
];
$service_name = isset($service_label_map[$service_slug])
    ? $service_label_map[$service_slug]
    : ucfirst(str_replace('-', ' ', $service_slug));

$target_cities = ['katowice', 'gliwice', 'zabrze', 'bytom', 'krakow', 'warszawa'];
?>

<section class="pt24-service-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <header class="pt24-service-hero">
            <h1><?php echo esc_html($service_name); ?></h1>
            <p>
                Strona usługi o wysokiej intencji zakupowej. Porównaj oferty firm i przejdź do wersji lokalnej dla wybranego miasta.
            </p>
        </header>

        <div class="pt24-service-layout">
            <main class="pt24-service-main">
                <section class="pt24-service-card">
                    <h2>Opis usługi</h2>
                    <p>
                        <?php echo esc_html($service_name); ?> realizowany przez zweryfikowanych wykonawców.
                        Dodaj zapytanie i wybierz najkorzystniejszą ofertę.
                    </p>
                </section>

                <section class="pt24-service-card">
                    <h2>Wybierz miasto</h2>
                    <div class="pt24-service-guides">
                        <?php foreach ($target_cities as $city_slug) : ?>
                            <a class="pt24-service-guide" href="<?php echo esc_url(home_url('/' . $service_slug . '/' . $city_slug . '/')); ?>">
                                <strong><?php echo esc_html(ucfirst($city_slug)); ?></strong>
                                <span><?php echo esc_html($service_name . ' w tym mieście'); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>
</section>

<?php get_footer(); ?>
