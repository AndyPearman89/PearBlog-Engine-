<?php
/**
 * Single business profile template (/firma/{slug}).
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    $business_id = get_the_ID();

    $city_terms = get_the_terms($business_id, 'pt24_city');
    $service_terms = get_the_terms($business_id, 'pt24_service_cat');

    $city_names = [];
    if (is_array($city_terms)) {
        foreach ($city_terms as $term) {
            $city_names[] = $term->name;
        }
    }

    $service_names = [];
    if (is_array($service_terms)) {
        foreach ($service_terms as $term) {
            $service_names[] = $term->name;
        }
    }

    $area_text = get_post_meta($business_id, 'pt24_area', true);
    if (!is_string($area_text) || $area_text === '') {
        $area_text = !empty($city_names) ? implode(', ', $city_names) : 'Cała Polska';
    }

    $hours_text = get_post_meta($business_id, 'pt24_hours', true);
    if (!is_string($hours_text) || $hours_text === '') {
        $hours_text = "Pon–Pt: 8:00–18:00\nSob: 9:00–14:00\nNdz: Nieczynne";
    }

    $certs_text = get_post_meta($business_id, 'pt24_certificates', true);
    $certs = [];
    if (is_string($certs_text) && trim($certs_text) !== '') {
        $certs = preg_split('/\r\n|\r|\n|,/', $certs_text);
    }
    if (empty($certs)) {
        $certs = ['Certyfikat jakości usług', 'Weryfikacja PT24', 'Ubezpieczenie OC działalności'];
    }

    $gallery_meta = get_post_meta($business_id, 'pt24_gallery_urls', true);
    $gallery_urls = [];
    if (is_string($gallery_meta) && trim($gallery_meta) !== '') {
        $gallery_urls = preg_split('/\r\n|\r|\n|,/', $gallery_meta);
    }
    $gallery_urls = array_values(array_filter(array_map('trim', $gallery_urls)));

    if (empty($gallery_urls) && has_post_thumbnail($business_id)) {
        $thumb = get_the_post_thumbnail_url($business_id, 'large');
        if (is_string($thumb) && $thumb !== '') {
            $gallery_urls = [$thumb, $thumb, $thumb];
        }
    }

    $notice = isset($_GET['contact']) ? sanitize_key((string) $_GET['contact']) : '';
    ?>

    <section class="pt24-company-page">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <header class="pt24-company-hero">
                <div>
                    <h1 class="pt24-company-title"><?php the_title(); ?></h1>
                    <p class="pt24-company-meta">
                        <?php echo esc_html(!empty($service_names) ? implode(' · ', $service_names) : 'Usługi lokalne'); ?>
                        <?php if (!empty($city_names)) : ?>
                            · <?php echo esc_html(implode(', ', $city_names)); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="pt24-company-badges">
                    <span>✔ Zweryfikowana firma</span>
                    <span>⚡ Szybki kontakt</span>
                </div>
            </header>

            <div class="pt24-company-layout">
                <main class="pt24-company-main">
                    <section class="pt24-company-card">
                        <h2>Opis działalności</h2>
                        <div class="prose prose-slate max-w-none">
                            <?php the_content(); ?>
                        </div>
                    </section>

                    <section class="pt24-company-card">
                        <h2>Zdjęcia realizacji</h2>
                        <div class="pt24-company-gallery">
                            <?php if (!empty($gallery_urls)) : ?>
                                <?php foreach ($gallery_urls as $url) : ?>
                                    <img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy" decoding="async">
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="pt24-company-empty">Brak zdjęć realizacji.</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="pt24-company-card">
                        <h2>Certyfikaty</h2>
                        <ul class="pt24-company-list">
                            <?php foreach ($certs as $cert) : ?>
                                <li>🏅 <?php echo esc_html(trim((string) $cert)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="pt24-company-card">
                        <h2>Opinie</h2>
                        <?php if (comments_open() || get_comments_number() > 0) : ?>
                            <div class="pt24-company-reviews">
                                <?php
                                wp_list_comments([
                                    'style' => 'div',
                                    'avatar_size' => 40,
                                    'short_ping' => true,
                                ]);
                                ?>
                            </div>
                        <?php else : ?>
                            <p class="pt24-company-empty">Brak opinii. Bądź pierwszy i dodaj opinię po realizacji usługi.</p>
                        <?php endif; ?>
                    </section>
                </main>

                <aside class="pt24-company-side">
                    <section class="pt24-company-card">
                        <h3>Obszar działania</h3>
                        <p><?php echo esc_html($area_text); ?></p>
                    </section>

                    <section class="pt24-company-card">
                        <h3>Godziny pracy</h3>
                        <pre><?php echo esc_html($hours_text); ?></pre>
                    </section>

                    <section class="pt24-company-card">
                        <h3>Mapa</h3>
                        <div class="pt24-company-map">
                            <iframe
                                title="Mapa obszaru działania"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                src="<?php echo esc_url('https://maps.google.com/maps?q=' . rawurlencode($area_text) . '&output=embed'); ?>">
                            </iframe>
                        </div>
                    </section>

                    <section class="pt24-company-card">
                        <h3>Formularz kontaktowy</h3>
                        <?php if ($notice === 'sent') : ?>
                            <p class="pt24-company-success">Wiadomość została wysłana.</p>
                        <?php elseif ($notice === 'error') : ?>
                            <p class="pt24-company-error">Nie udało się wysłać wiadomości. Spróbuj ponownie.</p>
                        <?php endif; ?>
                        <form class="pt24-company-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="pt24_business_contact">
                            <input type="hidden" name="business_id" value="<?php echo esc_attr((string) $business_id); ?>">
                            <?php wp_nonce_field('pt24_business_contact_' . $business_id, 'pt24_contact_nonce'); ?>
                            <label>
                                Imię i nazwisko
                                <input type="text" name="name" required>
                            </label>
                            <label>
                                Email
                                <input type="email" name="email" required>
                            </label>
                            <label>
                                Telefon
                                <input type="text" name="phone">
                            </label>
                            <label>
                                Wiadomość
                                <textarea name="message" rows="5" required></textarea>
                            </label>
                            <button type="submit">Wyślij zapytanie</button>
                        </form>
                    </section>
                </aside>
            </div>
        </div>
    </section>

<?php endwhile; ?>

<?php get_footer(); ?>
