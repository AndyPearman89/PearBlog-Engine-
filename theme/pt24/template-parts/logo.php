<?php
/**
 * Logo SVG Template Part
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$size = isset($args['size']) ? (int) $args['size'] : 40;
$grad_id = 'pearGrad_' . wp_unique_id();
?>
<svg width="<?php echo esc_attr($size); ?>" height="<?php echo esc_attr($size); ?>" viewBox="0 0 300 300" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <linearGradient id="<?php echo esc_attr($grad_id); ?>" x1="55" y1="42" x2="230" y2="287" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#60A5FA"/>
            <stop offset="0.58" stop-color="#4ADE80"/>
            <stop offset="1" stop-color="#16A34A"/>
        </linearGradient>
    </defs>
    <path d="M148 53C179 53 201 70 209 96C244 104 265 136 259 175C252 220 213 257 167 261C111 266 61 228 54 177C49 141 67 108 95 96C101 70 120 53 148 53Z" fill="url(#<?php echo esc_attr($grad_id); ?>)"/>
    <path d="M143 51C149 32 166 19 187 19" stroke="#8B5E34" stroke-width="10" stroke-linecap="round"/>
    <path d="M190 25C208 19 225 25 236 39C217 49 199 45 187 32" fill="#3FAE54"/>
    <circle cx="148" cy="160" r="37" stroke="#F8FAFC" stroke-opacity="0.92" stroke-width="8"/>
    <circle cx="148" cy="160" r="20" stroke="#F8FAFC" stroke-opacity="0.86" stroke-width="6"/>
    <circle cx="148" cy="160" r="5" fill="#F8FAFC"/>
</svg>
