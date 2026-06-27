<?php
/**
 * Single service template (/uslugi/{slug}).
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

$service_slug = sanitize_title((string) get_query_var('pt24_service_hub'));
$service_term = get_term_by('slug', $service_slug, 'pt24_service_cat');

if (! is_object($service_term) || is_wp_error($service_term)) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    get_header();
    ?>
    <section class="pt24-services-page">
        <div class="mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-slate-900">Nie znaleziono usługi</h1>
            <p class="mt-4 text-slate-600">Ta kategoria usługi nie istnieje lub została usunięta.</p>
            <a class="mt-8 inline-flex rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white" href="<?php echo esc_url(home_url('/uslugi/')); ?>">Wróć do listy usług</a>
        </div>
    </section>
    <?php
    get_footer();
    return;
}

get_header();

$service_name = (string) $service_term->name;
$service_description = trim((string) $service_term->description);

$service_posts = get_posts([
    'post_type' => 'pt24_service',
    'posts_per_page' => 1,
    'post_status' => 'publish',
    'tax_query' => [
        [
            'taxonomy' => 'pt24_service_cat',
            'field' => 'term_id',
            'terms' => [(int) $service_term->term_id],
        ],
    ],
]);

$service_post = ! empty($service_posts) ? $service_posts[0] : null;

if ($service_description === '' && is_object($service_post)) {
    $service_description = trim((string) apply_filters('the_content', $service_post->post_content));
}
if ($service_description === '') {
    $service_description = 'Kompleksowa usługa realizowana przez zweryfikowane firmy z Twojej okolicy. Wyślij jedno zapytanie i porównaj oferty bez telefonów i długiego szukania.';
}

$common_jobs = [];
$price_ranges = [];
$faq_items = [];

if (is_object($service_post)) {
    $common_jobs_raw = (string) get_post_meta((int) $service_post->ID, 'pt24_common_jobs', true);
    $price_ranges_raw = (string) get_post_meta((int) $service_post->ID, 'pt24_price_ranges', true);
    $faq_raw = (string) get_post_meta((int) $service_post->ID, 'pt24_service_faq', true);

    if ($common_jobs_raw !== '') {
        $common_jobs = preg_split('/\r\n|\r|\n|,/', $common_jobs_raw);
    }
    if ($price_ranges_raw !== '') {
        $price_ranges = preg_split('/\r\n|\r|\n/', $price_ranges_raw);
    }
    if ($faq_raw !== '') {
        $faq_lines = preg_split('/\r\n|\r|\n/', $faq_raw);
        if (is_array($faq_lines)) {
            foreach ($faq_lines as $faq_line) {
                $parts = array_map('trim', explode('|', (string) $faq_line, 2));
                if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                    $faq_items[] = ['q' => $parts[0], 'a' => $parts[1]];
                }
            }
        }
    }
}

$common_jobs = array_values(array_filter(array_map('trim', $common_jobs)));
$price_ranges = array_values(array_filter(array_map('trim', $price_ranges)));

if (empty($common_jobs)) {
    $common_jobs = [
        "Montaż i naprawa — {$service_name}",
        "Pilne awarie oraz interwencje tego samego dnia",
        'Przeglądy okresowe i konserwacja',
        'Wycena i realizacja nowych instalacji',
    ];
}

if (empty($price_ranges)) {
    $price_ranges = [
        'Wizyta / diagnoza: od 150 zł',
        'Standardowa usługa: 300–1200 zł',
        'Złożone realizacje: od 1500 zł',
    ];
}

if (empty($faq_items)) {
    $faq_items = [
        [
            'q' => "Jak szybko otrzymam oferty dla usługi „{$service_name}”?",
            'a' => 'Najczęściej pierwsze odpowiedzi pojawiają się w ciągu kilkunastu minut od dodania zapytania.',
        ],
        [
            'q' => 'Czy firmy są zweryfikowane?',
            'a' => 'Tak, profile firm mają system opinii i weryfikacji, dzięki czemu możesz bezpiecznie porównać wykonawców.',
        ],
        [
            'q' => 'Czy dodanie zapytania jest bezpłatne?',
            'a' => 'Tak, wysłanie zapytania i porównanie ofert w PT24.pro jest bezpłatne dla klientów.',
        ],
    ];
}

$companies = new WP_Query([
    'post_type' => 'pt24_business',
    'post_status' => 'publish',
    'posts_per_page' => 8,
    'tax_query' => [
        [
            'taxonomy' => 'pt24_service_cat',
            'field' => 'term_id',
            'terms' => [(int) $service_term->term_id],
        ],
    ],
]);

$guides = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    's' => $service_name,
]);

$notice = isset($_GET['inquiry']) ? sanitize_key((string) $_GET['inquiry']) : '';
?>

<section class="pt24-service-page">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <header class="pt24-service-hero">
            <a class="pt24-service-back" href="<?php echo esc_url(home_url('/uslugi/')); ?>">← Wszystkie usługi</a>
            <h1><?php echo esc_html($service_name); ?></h1>
            <p><?php echo esc_html(wp_strip_all_tags($service_description)); ?></p>
        </header>

        <div class="pt24-service-layout">
            <main class="pt24-service-main">
                <section class="pt24-service-card">
                    <h2>Opis usługi</h2>
                    <p><?php echo esc_html(wp_strip_all_tags($service_description)); ?></p>
                </section>

                <section class="pt24-service-card">
                    <h2>Najczęstsze zlecenia</h2>
                    <ul class="pt24-service-list">
                        <?php foreach ($common_jobs as $job) : ?>
                            <li>• <?php echo esc_html($job); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="pt24-service-card">
                    <h2>Orientacyjne ceny</h2>
                    <ul class="pt24-service-list">
                        <?php foreach ($price_ranges as $price) : ?>
                            <li>• <?php echo esc_html($price); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="pt24-service-card">
                    <h2>Firmy</h2>
                    <?php if ($companies->have_posts()) : ?>
                        <div class="pt24-service-companies">
                            <?php while ($companies->have_posts()) : $companies->the_post(); ?>
                                <a class="pt24-service-company" href="<?php the_permalink(); ?>">
                                    <strong><?php the_title(); ?></strong>
                                    <span>Zobacz profil firmy</span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <p class="pt24-service-empty">Brak firm przypisanych do tej usługi.</p>
                    <?php endif; ?>
                </section>

                <section class="pt24-service-card">
                    <h2>FAQ</h2>
                    <div class="pt24-service-faq">
                        <?php foreach ($faq_items as $faq) : ?>
                            <details class="pt24-faq-item">
                                <summary><?php echo esc_html((string) $faq['q']); ?></summary>
                                <p><?php echo esc_html((string) $faq['a']); ?></p>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="pt24-service-card">
                    <h2>Poradniki powiązane</h2>
                    <?php if ($guides->have_posts()) : ?>
                        <div class="pt24-service-guides">
                            <?php while ($guides->have_posts()) : $guides->the_post(); ?>
                                <a class="pt24-service-guide" href="<?php the_permalink(); ?>">
                                    <strong><?php the_title(); ?></strong>
                                    <span><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <p class="pt24-service-empty">Brak poradników powiązanych z tą usługą.</p>
                    <?php endif; ?>
                </section>
            </main>

            <aside class="pt24-service-side">
                <section class="pt24-service-card">
                    <h3>Formularz zapytania</h3>
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
                            <input type="text" name="city">
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
