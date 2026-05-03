<?php
/**
 * Comparison Block V4 — Auto-winner system
 *
 * Displays comparison items with automatic winner detection
 *
 * @package PearBlog
 * @version 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$args = wp_parse_args($args ?? [], [
    'items' => [],
    'title' => '',
    'auto_winner' => true,
]);

if (empty($args['items'])) {
    return;
}
?>

<div class="poradnik-smart-block">
    <?php if (!empty($args['title'])): ?>
        <div class="poradnik-smart-block__header">
            <h2 class="poradnik-smart-block__title"><?php echo esc_html($args['title']); ?></h2>
            <span class="poradnik-smart-block__badge">Porównanie</span>
        </div>
    <?php endif; ?>

    <div class="poradnik-comparison">
        <?php foreach ($args['items'] as $index => $item): ?>
            <?php
            $is_winner = $args['auto_winner'] && $index === 0;
            $item_classes = ['poradnik-comparison__item'];
            if ($is_winner) {
                $item_classes[] = 'poradnik-comparison__item--winner';
            }
            ?>
            <div class="<?php echo esc_attr(implode(' ', $item_classes)); ?>">
                <?php if ($is_winner): ?>
                    <span class="poradnik-comparison__winner-badge">
                        ✓ Najlepszy wybór
                    </span>
                <?php endif; ?>

                <h3 class="poradnik-comparison__title">
                    <?php echo esc_html($item['title'] ?? ''); ?>
                </h3>

                <?php if (!empty($item['description'])): ?>
                    <p class="poradnik-comparison__description">
                        <?php echo esc_html($item['description']); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($item['features']) && is_array($item['features'])): ?>
                    <ul class="poradnik-comparison__features">
                        <?php foreach ($item['features'] as $feature): ?>
                            <li class="poradnik-comparison__feature">
                                <span class="poradnik-comparison__feature-icon">✓</span>
                                <?php echo esc_html($feature); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($item['price'])): ?>
                    <div class="poradnik-comparison__price">
                        <?php echo esc_html($item['price']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($item['cta'])): ?>
                    <a
                        href="<?php echo esc_url($item['url'] ?? '#'); ?>"
                        class="poradnik-comparison__cta"
                        <?php if ($is_winner): ?>
                            data-winner="true"
                        <?php endif; ?>
                    >
                        <?php echo esc_html($item['cta']); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
