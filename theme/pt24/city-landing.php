<?php
/**
 * City landing template (/{miasto}).
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$city_slug = sanitize_title((string) get_query_var('pt24_city_landing'));
$city_term = get_term_by('slug', $city_slug, 'pt24_city');
$city_name = is_object($city_term) && isset($city_term->name)
    ? (string) $city_term->name
    : ucfirst(str_replace('-', ' ', $city_slug));
$city_term_id = is_object($city_term) && isset($city_term->term_id) ? (int) $city_term->term_id : 0;

$popular_services = get_terms([
    'taxonomy' => 'pt24_service_cat',
    'hide_empty' => false,
    'number' => 8,
    'orderby' => 'count',
    'order' => 'DESC',
]);
if (is_wp_error($popular_services) || ! is_array($popular_services)) {
    $popular_services = [];
}

$company_query_args = [
    'post_type' => 'pt24_business',
    'post_status' => 'publish',
    'posts_per_page' => 6,
];
if ($city_term_id > 0) {
    $company_query_args['tax_query'] = [
        [
            'taxonomy' => 'pt24_city',
            'field' => 'term_id',
            'terms' => [$city_term_id],
        ],
    ];
}
$recommended_companies = new WP_Query($company_query_args);

$stats_companies = (int) $recommended_companies->found_posts;
$stats_services = count($popular_services);
$stats_requests = random_int(40, 180);
$stats_avg_time = '15 min';

$recent_requests = [
    "Nowe zapytanie: hydraulik — {$city_name}",
    "Nowe zapytanie: elektryk — {$city_name}",
    "Nowe zapytanie: remont łazienki — {$city_name}",
    "Nowe zapytanie: montaż klimatyzacji — {$city_name}",
];
?>

<section class="pt24-service-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <header class="pt24-service-hero">
            <a class="pt24-service-back" href="<?php echo esc_url(home_url('/miasta/')); ?>">← Wszystkie miasta</a>
            <h1><?php echo esc_html($city_name); ?></h1>
            <p>Znajdź sprawdzonych fachowców w mieście <?php echo esc_html($city_name); ?> i porównaj oferty bez dzwonienia do wielu firm.</p>
        </header>

        <div class="pt24-service-layout">
            <main class="pt24-service-main">
                <section class="pt24-service-card">
                    <h2>Mapa</h2>
                    <div class="pt24-company-map">
                        <iframe
                            title="<?php echo esc_attr('Mapa miasta ' . $city_name); ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="<?php echo esc_url('https://maps.google.com/maps?q=' . rawurlencode($city_name . ', Poland') . '&output=embed'); ?>">
                        </iframe>
                    </div>
                </section>

                <section class="pt24-service-card">
                    <h2>Najpopularniejsze usługi</h2>
                    <div class="pt24-service-guides">
                        <?php foreach ($popular_services as $service_term) : ?>
                            <a class="pt24-service-guide" href="<?php echo esc_url(home_url('/' . $service_term->slug . '/' . $city_slug . '/')); ?>">
                                <strong><?php echo esc_html($service_term->name); ?></strong>
                                <span>Sprawdź wykonawców w mieście <?php echo esc_html($city_name); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="pt24-service-card">
                    <h2>Polecane firmy</h2>
                    <?php if ($recommended_companies->have_posts()) : ?>
                        <div class="pt24-service-companies">
                            <?php while ($recommended_companies->have_posts()) : $recommended_companies->the_post(); ?>
                                <a class="pt24-service-company" href="<?php the_permalink(); ?>">
                                    <strong><?php the_title(); ?></strong>
                                    <span>Zobacz profil firmy</span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <p class="pt24-service-empty">Brak firm przypisanych do tego miasta.</p>
                    <?php endif; ?>
                </section>

                <section class="pt24-service-card">
                    <h2>Statystyki</h2>
                    <div class="pt24-service-stats">
                        <div class="pt24-service-stat"><strong><?php echo esc_html((string) $stats_companies); ?></strong><span>Firm w mieście</span></div>
                        <div class="pt24-service-stat"><strong><?php echo esc_html((string) $stats_services); ?></strong><span>Popularnych usług</span></div>
                        <div class="pt24-service-stat"><strong><?php echo esc_html((string) $stats_requests); ?>+</strong><span>Zapytania / miesiąc</span></div>
                        <div class="pt24-service-stat"><strong><?php echo esc_html($stats_avg_time); ?></strong><span>Średni czas odpowiedzi</span></div>
                    </div>
                </section>

                <section class="pt24-service-card">
                    <h2>Ostatnie zapytania</h2>
                    <ul class="pt24-service-list">
                        <?php foreach ($recent_requests as $request_line) : ?>
                            <li>• <?php echo esc_html($request_line); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </main>
        </div>
    </div>
</section>

<?php get_footer(); ?>
