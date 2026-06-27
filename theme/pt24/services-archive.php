<?php
/**
 * Services archive template (/uslugi).
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$icon_map = [
    'hydraulik' => '💧',
    'elektryk' => '⚡',
    'mechanik' => '🔧',
    'dekarz' => '🏠',
    'pompy-ciepla' => '♨️',
    'fotowoltaika' => '☀️',
    'klimatyzacja' => '❄️',
    'brukarz' => '🧱',
    'remonty' => '🛠️',
    'ogrodnik' => '🌿',
    'malarz' => '🎨',
    'geodeta' => '📐',
    'kominiarz' => '🏭',
    'stolarz' => '🪚',
    'szklarz' => '🪟',
    'alarmy' => '🔔',
    'monitoring' => '📹',
    'rolety' => '🪟',
    'instalacje-gazowe' => '🔥',
    'serwis-agd' => '🧰',
];

$terms = get_terms([
    'taxonomy' => 'pt24_service_cat',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);

if (is_wp_error($terms) || ! is_array($terms)) {
    $terms = [];
}
?>

<section class="pt24-services-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <header class="pt24-services-hero">
            <h1>Usługi</h1>
            <p>Lista wszystkich kategorii usług dostępnych na PT24.pro.</p>
        </header>

        <?php if (! empty($terms)) : ?>
            <div class="pt24-services-grid">
                <?php foreach ($terms as $term) : ?>
                    <?php
                    $slug = (string) $term->slug;
                    $icon = isset($icon_map[$slug]) ? $icon_map[$slug] : '🔹';
                    $items = (int) $term->count;
                    ?>
                    <a class="pt24-services-card" href="<?php echo esc_url(home_url('/uslugi/' . $slug . '/')); ?>">
                        <span class="pt24-services-card-icon"><?php echo esc_html($icon); ?></span>
                        <h2><?php echo esc_html($term->name); ?></h2>
                        <p><?php echo esc_html($term->description !== '' ? $term->description : 'Sprawdź najczęstsze zlecenia, ceny, firmy i poradniki.'); ?></p>
                        <span class="pt24-services-card-meta"><?php echo esc_html($items); ?> wpisów powiązanych</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="pt24-services-empty">Brak kategorii usług. Dodaj terminy w taksonomii „Kategorie Usług”.</div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
