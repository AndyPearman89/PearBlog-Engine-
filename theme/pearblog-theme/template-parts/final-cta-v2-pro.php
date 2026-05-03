<?php
/**
 * Template Part: Final CTA V2 Pro
 *
 * Bold final call-to-action section
 *
 * @package PearBlog
 * @version 2.0.0
 */

$args = wp_parse_args($args ?? array(), array(
    'title' => 'Rozwiąż problem teraz',
    'subtitle' => 'Nie czekaj — znajdź specjalistę i uzyskaj konkretną pomoc już dziś',
    'cta_text' => 'Znajdź specjalistę',
    'cta_url' => home_url('/eksperci'),
));
?>

<section class="v2pro-final-cta">
    <div class="v2pro-container">
        <div class="v2pro-final-cta-content">
            <h2 class="v2pro-final-cta-title v2pro-gradient-text">
                <?php echo esc_html($args['title']); ?>
            </h2>

            <?php if ($args['subtitle']) : ?>
                <p class="v2pro-subtitle v2pro-mb-xl">
                    <?php echo esc_html($args['subtitle']); ?>
                </p>
            <?php endif; ?>

            <a
                href="<?php echo esc_url($args['cta_url']); ?>"
                class="v2pro-btn"
                data-cta-id="final-cta"
                data-cta-location="final"
            >
                <?php echo esc_html($args['cta_text']); ?>
            </a>
        </div>
    </div>
</section>
