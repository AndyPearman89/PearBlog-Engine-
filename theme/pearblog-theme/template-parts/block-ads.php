<?php
/**
 * Template Part: Ads Block
 *
 * Monetization ad slots
 *
 * @package PearBlog
 */

$slot_id = $args['slot_id'] ?? 'default';
$position = $args['position'] ?? 'content'; // header, content, sidebar

// Check if ads are enabled
$ads_enabled = get_option('pearblog_ads_enabled', false);

if (!$ads_enabled) {
    return;
}

// Output the ad slot
echo pearblog_adsense_slot($slot_id);
?>
