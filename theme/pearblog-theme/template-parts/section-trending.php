<?php
/**
 * Template Part: Trending Topics Section
 *
 * Popular decisions and searches
 *
 * @package PearBlog
 * @version 3.0.0
 */

$trending_items = array(
    'Ile kosztuje remont mieszkania 2026?',
    'Pompa ciepła vs gaz — co wybrać?',
    'Najlepsza firma remontowa Katowice',
    'Koszt budowy domu za m²',
);
?>

<section class="pb-trending">
    <div class="pb-container">
        <div class="pb-trending-header">
            <span class="pb-trending-badge">🔥</span>
            <h2 class="pb-section-title"><?php _e('Trending Teraz', 'pearblog-theme'); ?></h2>
        </div>

        <p class="pb-trending-subtitle"><?php _e('Najczęściej podejmowane decyzje:', 'pearblog-theme'); ?></p>

        <div class="pb-trending-list">
            <?php foreach ($trending_items as $item) : ?>
                <a href="<?php echo esc_url(home_url('/?s=' . urlencode($item))); ?>" class="pb-trending-item">
                    <span class="pb-trending-arrow">→</span>
                    <span class="pb-trending-text"><?php echo esc_html($item); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="pb-trending-cta">
            <p class="pb-trending-cta-text">👉 <?php _e('Wejdź → sprawdź → zdecyduj', 'pearblog-theme'); ?></p>
        </div>
    </div>
</section>
