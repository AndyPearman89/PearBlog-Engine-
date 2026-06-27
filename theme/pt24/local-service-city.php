<?php
/**
 * Service + city landing template (/{usluga}/{miasto}).
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$service_slug = sanitize_title((string) get_query_var('pt24_category'));
$city_slug = sanitize_title((string) get_query_var('pt24_city'));

$service_term = get_term_by('slug', $service_slug, 'pt24_service_cat');
$city_term = get_term_by('slug', $city_slug, 'pt24_city');

$service_name = is_object($service_term) && isset($service_term->name)
    ? (string) $service_term->name
    : ucfirst(str_replace('-', ' ', $service_slug));
$city_name = is_object($city_term) && isset($city_term->name)
    ? (string) $city_term->name
    : ucfirst(str_replace('-', ' ', $city_slug));

$tax_query = ['relation' => 'AND'];
if (is_object($service_term) && isset($service_term->term_id)) {
    $tax_query[] = [
        'taxonomy' => 'pt24_service_cat',
        'field' => 'term_id',
        'terms' => [(int) $service_term->term_id],
    ];
}
if (is_object($city_term) && isset($city_term->term_id)) {
    $tax_query[] = [
        'taxonomy' => 'pt24_city',
        'field' => 'term_id',
        'terms' => [(int) $city_term->term_id],
    ];
}

$contractor_args = [
    'post_type' => 'pt24_business',
    'post_status' => 'publish',
    'posts_per_page' => 10,
];
if (count($tax_query) > 1) {
    $contractor_args['tax_query'] = $tax_query;
}
$contractors = new WP_Query($contractor_args);

if (! $contractors->have_posts() && is_object($city_term) && isset($city_term->term_id)) {
    $contractors = new WP_Query([
        'post_type' => 'pt24_business',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        'tax_query' => [
            [
                'taxonomy' => 'pt24_city',
                'field' => 'term_id',
                'terms' => [(int) $city_term->term_id],
            ],
        ],
    ]);
}

if (! $contractors->have_posts() && is_object($service_term) && isset($service_term->term_id)) {
    $contractors = new WP_Query([
        'post_type' => 'pt24_business',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        'tax_query' => [
            [
                'taxonomy' => 'pt24_service_cat',
                'field' => 'term_id',
                'terms' => [(int) $service_term->term_id],
            ],
        ],
    ]);
}

if (! $contractors->have_posts()) {
    $contractors = new WP_Query([
        'post_type' => 'pt24_business',
        'post_status' => 'publish',
        'posts_per_page' => 10,
    ]);
}

$pricing = [
    'Podstawowa usługa: od 200 zł',
    'Standardowa realizacja: 500–1800 zł',
    'Złożone prace: od 2000 zł',
];

$faq_items = [
    ['q' => "Ile kosztuje {$service_name} w mieście {$city_name}?", 'a' => 'Cena zależy od zakresu prac, terminu i dostępności wykonawców. Po wysłaniu zapytania otrzymasz kilka wycen.'],
    ['q' => 'Jak szybko dostanę odpowiedź?', 'a' => 'Pierwsze oferty najczęściej pojawiają się nawet w 15 minut.'],
    ['q' => 'Czy mogę porównać wiele firm?', 'a' => 'Tak, jedno zapytanie trafia do wielu dopasowanych wykonawców.'],
];

$review_items = [
    "„Bardzo szybki kontakt i konkretna wycena.” — klient, {$city_name}",
    "„Bez problemu porównałem kilka ofert i wybrałem najlepszą.” — klient, {$city_name}",
];

$notice = isset($_GET['inquiry']) ? sanitize_key((string) $_GET['inquiry']) : '';
?>

<section class="pt24-service-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <header class="pt24-service-hero">
            <h1><?php echo esc_html($service_name . ' — ' . $city_name); ?></h1>
            <p>
                Lokalni wykonawcy dla usługi <?php echo esc_html($service_name); ?> w mieście <?php echo esc_html($city_name); ?>.
                Dodaj zapytanie i porównaj oferty od sprawdzonych firm.
            </p>
        </header>

        <div class="pt24-service-layout">
            <main class="pt24-service-main">
                <section class="pt24-service-card">
                    <h2>Lokalny opis</h2>
                    <p>
                        Usługa <?php echo esc_html($service_name); ?> w mieście <?php echo esc_html($city_name); ?> realizowana jest przez firmy działające lokalnie.
                        Dzięki PT24.pro szybko znajdziesz wykonawcę i otrzymasz dopasowane wyceny.
                    </p>
                </section>

                <section class="pt24-service-card">
                    <h2>Cennik</h2>
                    <ul class="pt24-service-list">
                        <?php foreach ($pricing as $price_line) : ?>
                            <li>• <?php echo esc_html($price_line); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="pt24-service-card">
                    <h2>Dostępni wykonawcy</h2>
                    <?php if ($contractors->have_posts()) : ?>
                        <div class="pt24-service-companies">
                            <?php while ($contractors->have_posts()) : $contractors->the_post(); ?>
                                <a class="pt24-service-company" href="<?php the_permalink(); ?>">
                                    <strong><?php the_title(); ?></strong>
                                    <span>Zobacz profil firmy</span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <p class="pt24-service-empty">Brak wykonawców w bazie danych.</p>
                    <?php endif; ?>
                </section>

                <section class="pt24-service-card">
                    <h2>Mapa</h2>
                    <div class="pt24-company-map">
                        <iframe
                            title="<?php echo esc_attr('Mapa: ' . $service_name . ' ' . $city_name); ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="<?php echo esc_url('https://maps.google.com/maps?q=' . rawurlencode($service_name . ', ' . $city_name . ', Poland') . '&output=embed'); ?>">
                        </iframe>
                    </div>
                </section>

                <section class="pt24-service-card">
                    <h2>FAQ</h2>
                    <?php foreach ($faq_items as $faq) : ?>
                        <details class="pt24-faq-item">
                            <summary><?php echo esc_html($faq['q']); ?></summary>
                            <p><?php echo esc_html($faq['a']); ?></p>
                        </details>
                    <?php endforeach; ?>
                </section>

                <section class="pt24-service-card">
                    <h2>Opinie</h2>
                    <ul class="pt24-service-list">
                        <?php foreach ($review_items as $review_line) : ?>
                            <li>• <?php echo esc_html($review_line); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </main>

            <aside class="pt24-service-side">
                <section class="pt24-service-card">
                    <h3>Formularz</h3>
                    <?php if ($notice === 'sent') : ?>
                        <p class="pt24-company-success">Zapytanie zostało wysłane.</p>
                    <?php elseif ($notice === 'error') : ?>
                        <p class="pt24-company-error">Nie udało się wysłać zapytania. Spróbuj ponownie.</p>
                    <?php endif; ?>
                    <form class="pt24-company-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="pt24_service_inquiry">
                        <input type="hidden" name="service_slug" value="<?php echo esc_attr($service_slug); ?>">
                        <?php wp_nonce_field('pt24_service_inquiry_' . $service_slug, 'pt24_service_inquiry_nonce'); ?>
                        <label>
                            Imię i nazwisko
                            <input type="text" name="name" required>
                        </label>
                        <label>
                            Email
                            <input type="email" name="email" required>
                        </label>
                        <label>
                            Miasto
                            <input type="text" name="city" value="<?php echo esc_attr($city_name); ?>">
                        </label>
                        <label>
                            Treść zapytania
                            <textarea name="message" rows="5" required></textarea>
                        </label>
                        <button type="submit">Wyślij zapytanie</button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</section>

<?php get_footer(); ?>
