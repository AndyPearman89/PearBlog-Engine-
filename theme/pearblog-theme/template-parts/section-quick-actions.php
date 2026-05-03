<?php
/**
 * Template Part: Quick Actions Section
 *
 * 4 decision pathways for user navigation
 *
 * @package PearBlog
 * @version 3.0.0
 */
?>

<section class="pb-quick-actions">
    <div class="pb-container">
        <h2 class="pb-section-title"><?php _e('Wybierz swoją ścieżkę:', 'pearblog-theme'); ?></h2>

        <div class="pb-quick-actions-grid">
            <a href="<?php echo esc_url(home_url('/poradniki')); ?>" class="pb-quick-action-card">
                <div class="pb-quick-action-icon">📘</div>
                <h3 class="pb-quick-action-title"><?php _e('Poradniki', 'pearblog-theme'); ?></h3>
                <p class="pb-quick-action-desc"><?php _e('Konkretne odpowiedzi krok po kroku', 'pearblog-theme'); ?></p>
            </a>

            <a href="<?php echo esc_url(home_url('/porownania')); ?>" class="pb-quick-action-card">
                <div class="pb-quick-action-icon">🆚</div>
                <h3 class="pb-quick-action-title"><?php _e('Porównania', 'pearblog-theme'); ?></h3>
                <p class="pb-quick-action-desc"><?php _e('Zobacz, co naprawdę się opłaca', 'pearblog-theme'); ?></p>
            </a>

            <a href="<?php echo esc_url(home_url('/rankingi')); ?>" class="pb-quick-action-card">
                <div class="pb-quick-action-icon">🏆</div>
                <h3 class="pb-quick-action-title"><?php _e('Rankingi', 'pearblog-theme'); ?></h3>
                <p class="pb-quick-action-desc"><?php _e('Najlepsi specjaliści w Twojej okolicy', 'pearblog-theme'); ?></p>
            </a>

            <a href="<?php echo esc_url(home_url('/kalkulatory')); ?>" class="pb-quick-action-card">
                <div class="pb-quick-action-icon">🧮</div>
                <h3 class="pb-quick-action-title"><?php _e('Kalkulatory', 'pearblog-theme'); ?></h3>
                <p class="pb-quick-action-desc"><?php _e('Policz koszt w 30 sekund', 'pearblog-theme'); ?></p>
            </a>
        </div>
    </div>
</section>
