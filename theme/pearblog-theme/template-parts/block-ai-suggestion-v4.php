<?php
/**
 * AI Inline Suggestion V4
 *
 * Context-aware AI recommendation inline with content
 *
 * @package PearBlog
 * @version 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$args = wp_parse_args($args ?? [], [
    'title' => 'Rekomendacja AI',
    'text' => '',
    'action_text' => 'Zobacz szczegóły',
    'action_url' => '#',
]);

if (empty($args['text'])) {
    return;
}
?>

<div class="poradnik-ai-suggestion poradnik-fade-in">
    <svg class="poradnik-ai-suggestion__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
    </svg>

    <div class="poradnik-ai-suggestion__content">
        <div class="poradnik-ai-suggestion__title">
            <?php echo esc_html($args['title']); ?>
        </div>

        <div class="poradnik-ai-suggestion__text">
            <?php echo wp_kses_post($args['text']); ?>
        </div>

        <?php if (!empty($args['action_text']) && !empty($args['action_url'])): ?>
            <a href="<?php echo esc_url($args['action_url']); ?>" class="poradnik-ai-suggestion__action">
                <?php echo esc_html($args['action_text']); ?>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        <?php endif; ?>
    </div>
</div>
