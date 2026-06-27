<?php
/**
 * Cities archive template (/miasta).
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$cities = get_terms([
    'taxonomy' => 'pt24_city',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);

if (is_wp_error($cities) || ! is_array($cities)) {
    $cities = [];
}
?>

<section class="pt24-services-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <header class="pt24-services-hero">
            <h1>Miasta</h1>
            <p>Wybierz lokalizację i sprawdź dostępnych wykonawców, usługi i najnowsze zapytania w Twoim mieście.</p>
        </header>

        <?php if (! empty($cities)) : ?>
            <div class="pt24-services-grid">
                <?php foreach ($cities as $city) : ?>
                    <a class="pt24-services-card" href="<?php echo esc_url(home_url('/' . $city->slug . '/')); ?>">
                        <span class="pt24-services-card-icon">📍</span>
                        <h2><?php echo esc_html($city->name); ?></h2>
                        <p>Lokalne usługi, polecane firmy i zapytania z miasta <?php echo esc_html($city->name); ?>.</p>
                        <span class="pt24-services-card-meta">Przejdź do strony miasta</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="pt24-services-empty">Brak miast. Dodaj terminy w taksonomii „Miasta”.</div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
