<?php
/**
 * Template Part: Category Blocks V2 Pro
 *
 * Feature category cards with glass styling
 *
 * @package PearBlog
 * @version 2.0.0
 */

$args = wp_parse_args($args ?? array(), array(
    'title' => 'Czego potrzebujesz?',
    'categories' => array(
        array(
            'icon' => '📚',
            'title' => 'Poradniki',
            'description' => 'Kompletne przewodniki krok po kroku',
            'url' => home_url('/poradniki'),
        ),
        array(
            'icon' => '⚖️',
            'title' => 'Porównania',
            'description' => 'Porównaj opcje i wybierz najlepszą',
            'url' => home_url('/porownania'),
        ),
        array(
            'icon' => '🏆',
            'title' => 'Rankingi',
            'description' => 'Najlepsi eksperci i rozwiązania',
            'url' => home_url('/rankingi'),
        ),
    ),
));
?>

<section class="v2pro-section">
    <div class="v2pro-container">
        <?php if ($args['title']) : ?>
            <h2 class="v2pro-h2 v2pro-text-center v2pro-mb-xl">
                <?php echo esc_html($args['title']); ?>
            </h2>
        <?php endif; ?>

        <div class="v2pro-categories v2pro-stagger">
            <?php foreach ($args['categories'] as $category) : ?>
                <a href="<?php echo esc_url($category['url']); ?>" class="v2pro-category-card" style="text-decoration: none; color: inherit;">
                    <span class="v2pro-category-icon">
                        <?php echo esc_html($category['icon']); ?>
                    </span>

                    <h3 class="v2pro-category-title">
                        <?php echo esc_html($category['title']); ?>
                    </h3>

                    <p class="v2pro-category-desc">
                        <?php echo esc_html($category['description']); ?>
                    </p>

                    <span class="v2pro-btn v2pro-btn-secondary" style="margin-top: auto;">
                        Zobacz więcej →
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
