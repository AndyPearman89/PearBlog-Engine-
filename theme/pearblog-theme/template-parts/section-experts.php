<?php
/**
 * Template Part: Experts Section
 *
 * Find specialists
 *
 * @package PearBlog
 * @version 3.0.0
 */
?>

<section class="pb-experts-section">
    <div class="pb-container">
        <div class="pb-section-header">
            <span class="pb-section-icon">🧑‍💼</span>
            <h2 class="pb-section-title"><?php _e('Eksperci', 'pearblog-theme'); ?></h2>
        </div>

        <p class="pb-section-subtitle"><?php _e('Znajdź właściwą osobę do zadania', 'pearblog-theme'); ?></p>

        <div class="pb-experts-benefits">
            <div class="pb-experts-benefit">
                <span class="pb-benefit-check">✓</span>
                <span class="pb-benefit-text"><?php _e('profile specjalistów', 'pearblog-theme'); ?></span>
            </div>
            <div class="pb-experts-benefit">
                <span class="pb-benefit-check">✓</span>
                <span class="pb-benefit-text"><?php _e('oceny i opinie', 'pearblog-theme'); ?></span>
            </div>
            <div class="pb-experts-benefit">
                <span class="pb-benefit-check">✓</span>
                <span class="pb-benefit-text"><?php _e('szybkie zapytania', 'pearblog-theme'); ?></span>
            </div>
        </div>

        <div class="pb-experts-cta-wrapper">
            <p class="pb-experts-cta-text">👉 <?php _e('jeden formularz → wiele ofert', 'pearblog-theme'); ?></p>
            <a href="<?php echo esc_url(home_url('/eksperci')); ?>" class="pb-experts-button">
                <?php _e('Znajdź eksperta', 'pearblog-theme'); ?>
            </a>
        </div>
    </div>
</section>
