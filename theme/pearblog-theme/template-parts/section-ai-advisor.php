<?php
/**
 * Template Part: AI Advisor Section
 *
 * AI-powered recommendations
 *
 * @package PearBlog
 * @version 3.0.0
 */
?>

<section class="pb-ai-advisor">
    <div class="pb-container">
        <div class="pb-ai-advisor-content">
            <div class="pb-ai-advisor-header">
                <span class="pb-ai-advisor-icon">🤖</span>
                <h2 class="pb-section-title"><?php _e('AI Doradca', 'pearblog-theme'); ?></h2>
            </div>

            <p class="pb-ai-advisor-question"><?php _e('Nie wiesz co wybrać?', 'pearblog-theme'); ?></p>

            <div class="pb-ai-advisor-form">
                <p class="pb-ai-advisor-prompt"><?php _e('Podaj:', 'pearblog-theme'); ?></p>
                <ul class="pb-ai-advisor-inputs">
                    <li><?php _e('budżet', 'pearblog-theme'); ?></li>
                    <li><?php _e('lokalizację', 'pearblog-theme'); ?></li>
                    <li><?php _e('cel', 'pearblog-theme'); ?></li>
                </ul>

                <a href="<?php echo esc_url(home_url('/ai-doradca')); ?>" class="pb-ai-advisor-button">
                    <?php _e('Uzyskaj rekomendację AI', 'pearblog-theme'); ?>
                </a>
            </div>

            <p class="pb-ai-advisor-cta">👉 <?php _e('dostaniesz konkretną rekomendację + gotowe opcje', 'pearblog-theme'); ?></p>
        </div>
    </div>
</section>
