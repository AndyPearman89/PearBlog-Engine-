<?php
/**
 * Static marketing pages template.
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

$slug = sanitize_title((string) get_query_var('pt24_static_page'));
if ($slug === '') {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));

    if (! empty($segments) && strtolower((string) $segments[0]) === 'pt24') {
        array_shift($segments);
    }

    if (isset($segments[0]) && ! isset($segments[1])) {
        $slug = sanitize_title((string) $segments[0]);
    }
}
$page = function_exists('pt24_get_frontend_page_by_slug')
    ? pt24_get_frontend_page_by_slug($slug)
    : null;

if (! is_array($page)) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part('404');
    exit;
}

$title = isset($page['title']) ? (string) $page['title'] : 'PT24';
$eyebrow = isset($page['eyebrow']) ? (string) $page['eyebrow'] : 'PT24';
$lead = isset($page['lead']) ? (string) $page['lead'] : '';
$highlights = isset($page['highlights']) && is_array($page['highlights']) ? $page['highlights'] : [];
$sections = isset($page['sections']) && is_array($page['sections']) ? $page['sections'] : [];

$segment = function_exists('pt24_parse_segment_page_slug') ? pt24_parse_segment_page_slug($slug) : null;
$is_segment_page = is_array($segment);

$breadcrumbs = [
    ['label' => 'Strona glowna', 'url' => home_url('/')],
    ['label' => 'Dla firm', 'url' => home_url('/dla-firm/')],
];

$related_city_links = [];
$related_service_links = [];
$service_slug_prefill = '';
$city_prefill = '';

if ($is_segment_page) {
    $service_slug = (string) $segment['service_slug'];
    $city_slug = (string) $segment['city_slug'];
    $service_title = (string) $segment['service_title'];
    $city_title = (string) $segment['city_title'];
    $service_slug_prefill = $service_slug;
    $city_prefill = $city_title;

    $breadcrumbs[] = ['label' => $service_title . ' dla firm', 'url' => home_url('/' . $service_slug . '-dla-firm/')];
    $breadcrumbs[] = ['label' => $service_title . ' ' . $city_title, 'url' => home_url('/' . $slug . '/')];

    $cities = function_exists('pt24_get_segment_city_titles') ? pt24_get_segment_city_titles() : [];
    foreach ($cities as $candidate_city_slug => $candidate_city_title) {
        if ($candidate_city_slug === $city_slug) {
            continue;
        }
        $related_city_links[] = [
            'label' => $service_title . ' dla firm ' . $candidate_city_title,
            'url' => home_url('/' . $service_slug . '-dla-firm-' . $candidate_city_slug . '/'),
        ];
    }

    $services = function_exists('pt24_get_segment_service_titles') ? pt24_get_segment_service_titles() : [];
    foreach ($services as $candidate_service_slug => $candidate_service_title) {
        if ($candidate_service_slug === $service_slug) {
            continue;
        }
        $related_service_links[] = [
            'label' => $candidate_service_title . ' dla firm ' . $city_title,
            'url' => home_url('/' . $candidate_service_slug . '-dla-firm-' . $city_slug . '/'),
        ];
    }
}

if ($service_slug_prefill === '' && preg_match('/^([a-z0-9-]+)-dla-firm$/', $slug, $m) === 1) {
    $service_slug_prefill = sanitize_title((string) $m[1]);
}

$form_notice = isset($_GET['form']) ? sanitize_key((string) $_GET['form']) : '';

get_header();
?>

<section class="pt24-static-hero">
    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
        <nav class="pt24-static-breadcrumbs" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbs as $crumb) : ?>
                <a href="<?php echo esc_url((string) $crumb['url']); ?>"><?php echo esc_html((string) $crumb['label']); ?></a>
            <?php endforeach; ?>
        </nav>
        <span class="pt24-static-eyebrow"><?php echo esc_html($eyebrow); ?></span>
        <h1><?php echo esc_html($title); ?></h1>
        <?php if ($lead !== '') : ?>
            <p><?php echo esc_html($lead); ?></p>
        <?php endif; ?>
        <?php if (! empty($highlights)) : ?>
            <div class="pt24-static-highlights">
                <?php foreach ($highlights as $item) : ?>
                    <span><?php echo esc_html((string) $item); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="pt24-static-content">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <?php if (! empty($sections)) : ?>
            <div class="pt24-static-grid">
                <?php foreach ($sections as $section) : ?>
                    <article class="pt24-static-card">
                        <h2><?php echo esc_html(isset($section['title']) ? (string) $section['title'] : 'Sekcja'); ?></h2>
                        <p><?php echo esc_html(isset($section['text']) ? (string) $section['text'] : ''); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($is_segment_page) : ?>
            <div class="pt24-static-links-grid">
                <section class="pt24-static-links-block">
                    <h3>Powiazane miasta</h3>
                    <div class="pt24-static-link-cloud">
                        <?php foreach ($related_city_links as $link) : ?>
                            <a href="<?php echo esc_url((string) $link['url']); ?>"><?php echo esc_html((string) $link['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="pt24-static-links-block">
                    <h3>Powiazane uslugi w tym miescie</h3>
                    <div class="pt24-static-link-cloud">
                        <?php foreach ($related_service_links as $link) : ?>
                            <a href="<?php echo esc_url((string) $link['url']); ?>"><?php echo esc_html((string) $link['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>

        <section class="pt24-static-form-wrap">
            <h3>Szybki formularz leadowy</h3>
            <p>Zostaw kontakt i opis potrzeby. Odezwiemy sie z dopasowaniem wykonawcy lub przekazemy zgloszenie do firm z Twojego regionu.</p>

            <?php if ($form_notice === 'sent') : ?>
                <p class="pt24-company-success">Dziekujemy. Formularz zostal wyslany poprawnie.</p>
            <?php elseif ($form_notice === 'error') : ?>
                <p class="pt24-company-error">Nie udalo sie wyslac formularza. Sprawdz dane i sprobuj ponownie.</p>
            <?php endif; ?>

            <form class="pt24-static-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="pt24_marketing_lead">
                <input type="hidden" name="page_slug" value="<?php echo esc_attr($slug); ?>">
                <input type="hidden" name="service_slug" value="<?php echo esc_attr($service_slug_prefill); ?>">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/' . $slug . '/')); ?>">
                <?php wp_nonce_field('pt24_marketing_lead', 'pt24_marketing_lead_nonce'); ?>

                <label>
                    Imie i nazwisko
                    <input type="text" name="name" required>
                </label>

                <label>
                    Email
                    <input type="email" name="email" required>
                </label>

                <label>
                    Telefon
                    <input type="text" name="phone" placeholder="np. 500 600 700">
                </label>

                <label>
                    Miasto
                    <input type="text" name="city" value="<?php echo esc_attr($city_prefill); ?>" placeholder="np. Warszawa">
                </label>

                <label>
                    Opis zlecenia
                    <textarea name="message" rows="5" required><?php echo esc_textarea($is_segment_page ? 'Interesuja mnie leady dla tej uslugi i miasta. Prosze o kontakt.' : 'Prosze o kontakt i dopasowanie uslugi.'); ?></textarea>
                </label>

                <button type="submit">Wyslij formularz</button>
            </form>
        </section>

        <div class="pt24-static-cta">
            <h3>Potrzebujesz wykonawcy teraz?</h3>
            <p>Wyslij zapytanie i porownaj oferty sprawdzonych firm lokalnych.</p>
            <div class="pt24-static-cta-actions">
                <a href="<?php echo esc_url(home_url('/dodaj-zlecenie/')); ?>">Dodaj zapytanie</a>
                <a href="<?php echo esc_url(home_url('/uslugi/')); ?>" class="is-secondary">Przegladaj uslugi</a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
