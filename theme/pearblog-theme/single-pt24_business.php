<?php
/**
 * Template for PT24 Business Profiles
 *
 * Single template for business profile pages
 *
 * @package PearBlog
 * @version 1.0.0
 */

// Get business meta
$business_id = get_the_ID();
$phone = get_post_meta($business_id, 'pt24_phone', true);
$email = get_post_meta($business_id, 'pt24_email', true);
$website = get_post_meta($business_id, 'pt24_website', true);
$service_area = get_post_meta($business_id, 'pt24_service_area', true);
$specialization = get_post_meta($business_id, 'pt24_specialization', true);
$years_experience = get_post_meta($business_id, 'pt24_years_experience', true);
$mobile_service = get_post_meta($business_id, 'pt24_mobile_service', true);
$rating = get_post_meta($business_id, 'pt24_rating', true) ?: 5;
$reviews_count = get_post_meta($business_id, 'pt24_reviews_count', true) ?: 0;
$plan = get_post_meta($business_id, 'pt24_plan', true) ?: 'free';

// Get categories and cities
$categories = get_the_terms($business_id, 'pt24_service_cat');
$cities = get_the_terms($business_id, 'pt24_city');

wp_enqueue_style('pt24-landing', get_template_directory_uri() . '/assets/css/pt24-landing.css', array(), '1.0.0');
wp_enqueue_script('pt24-cta-tracking', get_template_directory_uri() . '/assets/js/pt24-cta-tracking.js', array('jquery'), '1.0.0', true);

// Localize script for tracking
wp_localize_script('pt24-cta-tracking', 'pt24Data', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('pt24_tracking'),
    'business_id' => $business_id
]);

get_header('minimal');
?>

<main class="pt24-business-profile">
    <!-- BUSINESS HEADER -->
    <section class="pt24-profile-header">
        <div class="pt24-container">
            <?php if ($plan === 'premium'): ?>
                <div class="pt24-badge pt24-badge--premium">✨ Polecany</div>
            <?php elseif ($plan === 'pro'): ?>
                <div class="pt24-badge pt24-badge--pro">⭐ PRO</div>
            <?php endif; ?>

            <div class="pt24-profile-header__content">
                <h1 class="pt24-profile-header__title">
                    <?php the_title(); ?>
                </h1>

                <?php if ($cities): ?>
                    <p class="pt24-profile-header__location">
                        📍 <?php echo esc_html($cities[0]->name); ?><?php if ($service_area): ?> i okolice<?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if ($reviews_count > 0): ?>
                    <div class="pt24-profile-header__rating">
                        <?php
                        // Display star rating
                        for ($i = 0; $i < 5; $i++) {
                            if ($i < $rating) {
                                echo '⭐';
                            } else {
                                echo '☆';
                            }
                        }
                        ?>
                        <span class="pt24-rating-text">(<?php echo esc_html($reviews_count); ?> opini<?php echo $reviews_count === 1 ? 'a' : ($reviews_count < 5 ? 'e' : 'i'); ?>)</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CONTACT CTA -->
    <section class="pt24-profile-contact-cta">
        <div class="pt24-container">
            <div class="pt24-contact-cta-card">
                <?php if ($phone): ?>
                    <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>"
                       class="pt24-btn pt24-btn--primary pt24-btn--large pt24-btn--phone"
                       data-action="phone_click">
                        📞 <?php echo esc_html($phone); ?>
                    </a>
                <?php endif; ?>

                <?php if ($email): ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>"
                       class="pt24-btn pt24-btn--secondary pt24-btn--large"
                       data-action="email_click">
                        📧 Wyślij email
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ABOUT SECTION -->
    <section class="pt24-profile-about">
        <div class="pt24-container">
            <div class="pt24-profile-content">
                <div class="pt24-profile-main">
                    <h2>O firmie</h2>
                    <?php the_content(); ?>

                    <?php if ($specialization): ?>
                        <h3>Specjalizacja</h3>
                        <p><?php echo esc_html($specialization); ?></p>
                    <?php endif; ?>
                </div>

                <aside class="pt24-profile-sidebar">
                    <!-- SERVICES -->
                    <?php if ($categories): ?>
                        <div class="pt24-profile-box">
                            <h3 class="pt24-profile-box__title">Usługi</h3>
                            <ul class="pt24-profile-services">
                                <?php foreach ($categories as $category): ?>
                                    <li>
                                        <svg class="pt24-check-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <?php echo esc_html(ucfirst($category->name)); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- SERVICE AREA -->
                    <?php if ($service_area): ?>
                        <div class="pt24-profile-box">
                            <h3 class="pt24-profile-box__title">Obszar działania</h3>
                            <p><?php echo esc_html($service_area); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- ADDITIONAL INFO -->
                    <div class="pt24-profile-box">
                        <h3 class="pt24-profile-box__title">Informacje</h3>
                        <ul class="pt24-profile-info">
                            <?php if ($years_experience): ?>
                                <li>📅 <?php echo esc_html($years_experience); ?> lat doświadczenia</li>
                            <?php endif; ?>

                            <?php if ($mobile_service): ?>
                                <li>🚗 Serwis mobilny</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php if ($website): ?>
                        <div class="pt24-profile-box">
                            <a href="<?php echo esc_url($website); ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="pt24-btn pt24-btn--outline pt24-btn--full"
                               data-action="website_click">
                                🌐 Strona internetowa
                            </a>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </section>

    <!-- REVIEWS SECTION -->
    <?php
    $reviews = get_post_meta($business_id, 'pt24_reviews', true);
    if ($reviews && is_array($reviews) && count($reviews) > 0):
    ?>
    <section class="pt24-profile-reviews">
        <div class="pt24-container">
            <h2 class="pt24-section-title">Opinie</h2>

            <div class="pt24-reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="pt24-review">
                        <div class="pt24-review__header">
                            <div class="pt24-review__rating">
                                <?php
                                for ($i = 0; $i < 5; $i++) {
                                    echo $i < ($review['rating'] ?? 5) ? '⭐' : '☆';
                                }
                                ?>
                            </div>
                            <span class="pt24-review__author"><?php echo esc_html($review['author'] ?? 'Anonimowy'); ?></span>
                        </div>
                        <p class="pt24-review__text"><?php echo esc_html($review['text'] ?? ''); ?></p>
                        <?php if (isset($review['date'])): ?>
                            <span class="pt24-review__date"><?php echo esc_html($review['date']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CONTACT CTA BOTTOM -->
    <section class="pt24-profile-contact-cta pt24-profile-contact-cta--bottom">
        <div class="pt24-container">
            <div class="pt24-cta-card">
                <h2 class="pt24-cta__title">Skontaktuj się teraz</h2>
                <div class="pt24-contact-cta-card">
                    <?php if ($phone): ?>
                        <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>"
                           class="pt24-btn pt24-btn--primary pt24-btn--large"
                           data-action="phone_click_bottom">
                            📞 Zadzwoń teraz
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer('minimal'); ?>
