<?php
/**
 * Template Part: For Specialists Section
 *
 * CTA for service providers
 *
 * @package PearBlog
 * @version 3.0.0
 */
?>

<section class="pb-for-specialists">
    <div class="pb-container">
        <div class="pb-for-specialists-content">
            <div class="pb-section-header">
                <span class="pb-section-icon">💼</span>
                <h2 class="pb-section-title"><?php _e('Dla specjalistów', 'pearblog-theme'); ?></h2>
            </div>

            <p class="pb-for-specialists-hook"><?php _e('Klienci szukają Twoich usług', 'pearblog-theme'); ?></p>

            <div class="pb-for-specialists-benefits">
                <div class="pb-specialist-benefit">
                    <span class="pb-benefit-icon">✔</span>
                    <span><?php _e('widoczność w rankingach', 'pearblog-theme'); ?></span>
                </div>
                <div class="pb-specialist-benefit">
                    <span class="pb-benefit-icon">✔</span>
                    <span><?php _e('zapytania od realnych klientów', 'pearblog-theme'); ?></span>
                </div>
                <div class="pb-specialist-benefit">
                    <span class="pb-benefit-icon">✔</span>
                    <span><?php _e('budowa marki eksperta', 'pearblog-theme'); ?></span>
                </div>
            </div>

            <div class="pb-for-specialists-cta">
                <p class="pb-for-specialists-cta-text">👉 <?php _e('Dołącz do Poradnik.pro', 'pearblog-theme'); ?></p>
                <a href="<?php echo esc_url(home_url('/dla-specjalistow')); ?>" class="pb-for-specialists-button">
                    <?php _e('Zarejestruj się teraz', 'pearblog-theme'); ?>
                </a>
            </div>
        </div>
    </div>
</section>
