<?php
/**
 * Ranking Block V4 — Personalized rankings
 *
 * Displays dynamic ranking with filter options
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
    'title' => 'Ranking',
    'filters' => [],
]);

if (empty($args['items'])) {
    return;
}
?>

<div class="poradnik-smart-block">
    <div class="poradnik-smart-block__header">
        <h2 class="poradnik-smart-block__title"><?php echo esc_html($args['title']); ?></h2>
        <span class="poradnik-smart-block__badge">Personalizowany</span>
    </div>

    <div class="poradnik-ranking">
        <?php if (!empty($args['filters']) && is_array($args['filters'])): ?>
            <div class="poradnik-ranking__filters">
                <?php foreach ($args['filters'] as $filter_index => $filter): ?>
                    <button
                        class="poradnik-ranking__filter <?php echo $filter_index === 0 ? 'poradnik-ranking__filter--active' : ''; ?>"
                        data-filter="<?php echo esc_attr($filter['value'] ?? ''); ?>"
                    >
                        <?php echo esc_html($filter['label'] ?? ''); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="poradnik-ranking__list">
            <?php foreach ($args['items'] as $index => $item): ?>
                <div class="poradnik-ranking__item" data-position="<?php echo esc_attr($index + 1); ?>">
                    <div class="poradnik-ranking__position">
                        <?php echo esc_html($index + 1); ?>
                    </div>

                    <div class="poradnik-ranking__content">
                        <h3 class="poradnik-ranking__name">
                            <?php echo esc_html($item['name'] ?? ''); ?>
                        </h3>
                        <?php if (!empty($item['description'])): ?>
                            <p class="poradnik-ranking__description">
                                <?php echo esc_html($item['description']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($item['cta'])): ?>
                        <a
                            href="<?php echo esc_url($item['url'] ?? '#'); ?>"
                            class="poradnik-ranking__action"
                        >
                            <?php echo esc_html($item['cta']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
