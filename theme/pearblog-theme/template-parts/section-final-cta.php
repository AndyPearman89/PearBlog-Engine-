<?php
/**
 * Template Part: Final CTA Section
 *
 * Conversion-focused final section
 *
 * @package PearBlog
 * @version 3.0.0
 */
?>

<section class="pb-final-cta">
    <div class="pb-container">
        <div class="pb-final-cta-content">
            <h2 class="pb-final-cta-title"><?php _e('To nie jest portal do czytania.', 'pearblog-theme'); ?></h2>
            <p class="pb-final-cta-subtitle"><?php _e('To platforma do działania.', 'pearblog-theme'); ?></p>

            <div class="pb-final-cta-flow">
                <div class="pb-final-cta-step">
                    <span class="pb-final-cta-arrow">👉</span>
                    <span class="pb-final-cta-text"><?php _e('Rozumiesz', 'pearblog-theme'); ?></span>
                </div>
                <div class="pb-final-cta-step">
                    <span class="pb-final-cta-arrow">👉</span>
                    <span class="pb-final-cta-text"><?php _e('Porównujesz', 'pearblog-theme'); ?></span>
                </div>
                <div class="pb-final-cta-step">
                    <span class="pb-final-cta-arrow">👉</span>
                    <span class="pb-final-cta-text"><?php _e('Decydujesz', 'pearblog-theme'); ?></span>
                </div>
                <div class="pb-final-cta-step">
                    <span class="pb-final-cta-arrow">👉</span>
                    <span class="pb-final-cta-text"><?php _e('Kupujesz', 'pearblog-theme'); ?></span>
                </div>
            </div>

            <div class="pb-final-cta-tagline">
                <p class="pb-tagline"><?php _e('Poradnik.pro — od pytania do decyzji', 'pearblog-theme'); ?></p>
            </div>

            <div class="pb-final-cta-buttons">
                <a href="<?php echo esc_url(home_url('/?s=')); ?>" class="pb-final-cta-button-primary">
                    <?php _e('Rozpocznij teraz', 'pearblog-theme'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/eksperci')); ?>" class="pb-final-cta-button-secondary">
                    <?php _e('Znajdź eksperta', 'pearblog-theme'); ?>
                </a>
            </div>
        </div>
    </div>
</section>
