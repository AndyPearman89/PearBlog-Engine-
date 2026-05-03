<?php
/**
 * Calculator Block V4 — Live matching
 *
 * Interactive calculator with automatic expert matching
 *
 * @package PearBlog
 * @version 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$args = wp_parse_args($args ?? [], [
    'title' => 'Kalkulator',
    'fields' => [],
    'result_format' => '',
    'show_matches' => true,
]);

$calculator_id = 'poradnik-calculator-' . uniqid();
?>

<div class="poradnik-calculator" id="<?php echo esc_attr($calculator_id); ?>">
    <h2 class="poradnik-calculator__title">
        <?php echo esc_html($args['title']); ?>
    </h2>

    <form class="poradnik-calculator__form" data-calculator-id="<?php echo esc_attr($calculator_id); ?>">
        <?php foreach ($args['fields'] as $field): ?>
            <div class="poradnik-calculator__field">
                <label class="poradnik-calculator__label" for="<?php echo esc_attr($field['id'] ?? ''); ?>">
                    <?php echo esc_html($field['label'] ?? ''); ?>
                </label>

                <?php if (($field['type'] ?? 'text') === 'select'): ?>
                    <select
                        class="poradnik-calculator__input"
                        id="<?php echo esc_attr($field['id'] ?? ''); ?>"
                        name="<?php echo esc_attr($field['name'] ?? ''); ?>"
                        <?php echo !empty($field['required']) ? 'required' : ''; ?>
                    >
                        <option value="">Wybierz...</option>
                        <?php if (!empty($field['options']) && is_array($field['options'])): ?>
                            <?php foreach ($field['options'] as $option): ?>
                                <option value="<?php echo esc_attr($option['value'] ?? ''); ?>">
                                    <?php echo esc_html($option['label'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                <?php else: ?>
                    <input
                        type="<?php echo esc_attr($field['type'] ?? 'text'); ?>"
                        class="poradnik-calculator__input"
                        id="<?php echo esc_attr($field['id'] ?? ''); ?>"
                        name="<?php echo esc_attr($field['name'] ?? ''); ?>"
                        placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                        <?php echo !empty($field['required']) ? 'required' : ''; ?>
                        <?php if (!empty($field['min'])): ?>min="<?php echo esc_attr($field['min']); ?>"<?php endif; ?>
                        <?php if (!empty($field['max'])): ?>max="<?php echo esc_attr($field['max']); ?>"<?php endif; ?>
                        <?php if (!empty($field['step'])): ?>step="<?php echo esc_attr($field['step']); ?>"<?php endif; ?>
                    >
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="poradnik-calculator__submit">
            Oblicz i dopasuj ekspertów
        </button>
    </form>

    <div class="poradnik-calculator__result poradnik-hidden" data-result-container>
        <h3 class="poradnik-calculator__result-title">Twój wynik</h3>
        <div class="poradnik-calculator__result-value" data-result-value></div>

        <?php if ($args['show_matches']): ?>
            <div class="poradnik-calculator__matches">
                <h4 class="poradnik-calculator__matches-title">
                    Dopasowani eksperci dla Ciebie
                </h4>
                <div class="poradnik-ranking__list" data-matches-list></div>
            </div>
        <?php endif; ?>
    </div>
</div>
