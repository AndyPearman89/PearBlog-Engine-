<?php
/**
 * Template Part: PT24 CTA Block
 *
 * Displays call-to-action for PT24.pro with various styles
 *
 * Variables available:
 * - $service: Service slug
 * - $city: City slug
 * - $pt24_url: Generated PT24 URL
 * - $style: CTA style (hybrid, card, banner, inline)
 * - $title: CTA title
 * - $cta_text: Button text
 * - $post_id: Current post ID
 *
 * @package PearBlog
 * @subpackage PT24Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Set defaults
$style = $style ?? 'hybrid';
$title = $title ?? 'Sprawdź ceny i dostępne firmy w Twojej okolicy';
$cta_text = $cta_text ?? 'Zobacz oferty';
$post_id = $post_id ?? get_the_ID();

// Generate unique ID for tracking
$cta_id = 'pt24-cta-' . uniqid();
?>

<?php if ($style === 'hybrid' || $style === 'card'): ?>
<!-- PT24 CTA Block - Hybrid/Card Style -->
<div class="pt24-cta pt24-cta--<?php echo esc_attr($style); ?>" data-cta-id="<?php echo esc_attr($cta_id); ?>" data-service="<?php echo esc_attr($service); ?>" data-city="<?php echo esc_attr($city); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
    <div class="pt24-cta__container">
        <div class="pt24-cta__content">
            <h3 class="pt24-cta__title"><?php echo esc_html($title); ?></h3>

            <ul class="pt24-cta__benefits">
                <li class="pt24-cta__benefit">
                    <svg class="pt24-cta__check" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Porównanie ofert</span>
                </li>
                <li class="pt24-cta__benefit">
                    <svg class="pt24-cta__check" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Firmy lokalne</span>
                </li>
                <li class="pt24-cta__benefit">
                    <svg class="pt24-cta__check" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Aktualne ceny</span>
                </li>
            </ul>

            <a href="<?php echo esc_url($pt24_url); ?>"
               class="pt24-cta__button"
               target="_blank"
               rel="noopener"
               data-pt24-track="click">
                <?php echo esc_html($cta_text); ?>
                <svg class="pt24-cta__arrow" width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M7.5 15l5-5-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

            <p class="pt24-cta__note">Darmowe wyceny • Bez zobowiązań • Odpowiedź w 24h</p>
        </div>

        <?php if ($style === 'hybrid' || $style === 'card'): ?>
        <div class="pt24-cta__visual">
            <div class="pt24-cta__icon">
                <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <path d="M24 4l6 12 13 2-9 9 2 13-12-6-12 6 2-13-9-9 13-2 6-12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="pt24-cta__badge">
                <span class="pt24-cta__badge-text">⭐ 4.8/5</span>
                <span class="pt24-cta__badge-subtitle">50,000+ opinii</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($style === 'banner'): ?>
<!-- PT24 CTA Block - Banner Style -->
<div class="pt24-cta pt24-cta--banner" data-cta-id="<?php echo esc_attr($cta_id); ?>" data-service="<?php echo esc_attr($service); ?>" data-city="<?php echo esc_attr($city); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
    <div class="pt24-cta__banner-content">
        <div class="pt24-cta__banner-text">
            <h4 class="pt24-cta__banner-title"><?php echo esc_html($title); ?></h4>
            <p class="pt24-cta__banner-subtitle">Sprawdzone firmy w Twojej okolicy</p>
        </div>
        <a href="<?php echo esc_url($pt24_url); ?>"
           class="pt24-cta__banner-button"
           target="_blank"
           rel="noopener"
           data-pt24-track="click">
            <?php echo esc_html($cta_text); ?>
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M7.5 15l5-5-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    </div>
</div>

<?php elseif ($style === 'inline'): ?>
<!-- PT24 CTA Block - Inline Style -->
<div class="pt24-cta pt24-cta--inline" data-cta-id="<?php echo esc_attr($cta_id); ?>" data-service="<?php echo esc_attr($service); ?>" data-city="<?php echo esc_attr($city); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
    <div class="pt24-cta__inline-content">
        <div class="pt24-cta__inline-icon">💡</div>
        <div class="pt24-cta__inline-text">
            <strong><?php echo esc_html($title); ?></strong>
            <span>Otrzymaj bezpłatne oferty od sprawdzonych firm</span>
        </div>
        <a href="<?php echo esc_url($pt24_url); ?>"
           class="pt24-cta__inline-button"
           target="_blank"
           rel="noopener"
           data-pt24-track="click">
            <?php echo esc_html($cta_text); ?> →
        </a>
    </div>
</div>

<?php endif; ?>
