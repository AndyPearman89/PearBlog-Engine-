<?php
/**
 * The footer template
 *
 * @package PearBlog
 */

// Render V4 Decision Bar if enabled
if (get_option('poradnik_v4_enabled', false) && get_option('poradnik_decision_bar_enabled', true)) {
    poradnik_decision_bar();
}

pearblog_render_footer();
