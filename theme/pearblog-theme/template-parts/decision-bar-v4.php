<?php
/**
 * Sticky Decision Bar V4
 *
 * Always-accessible decision tools at bottom of screen
 *
 * @package PearBlog
 * @version 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$config = pb_get_site_config();

// Check if decision bar is enabled
if (!get_option('poradnik_decision_bar_enabled', true)) {
    return;
}
?>

<div class="poradnik-decision-bar" role="navigation" aria-label="Decision tools">
    <button
        class="poradnik-decision-bar__action"
        data-action="compare"
        aria-label="Porównaj opcje"
    >
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <span>Porównaj</span>
    </button>

    <button
        class="poradnik-decision-bar__action"
        data-action="calculate"
        aria-label="Oblicz koszty"
    >
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        <span>Policz</span>
    </button>

    <button
        class="poradnik-decision-bar__action poradnik-decision-bar__action--primary"
        data-action="ask-ai"
        aria-label="Zapytaj AI"
    >
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
        </svg>
        <span>Zapytaj</span>
    </button>
</div>
