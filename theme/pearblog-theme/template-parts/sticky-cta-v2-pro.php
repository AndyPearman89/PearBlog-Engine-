<?php
/**
 * Template Part: Sticky Mobile CTA V2 Pro
 *
 * Fixed bottom CTA for mobile conversion
 *
 * @package PearBlog
 * @version 2.0.0
 */

$args = wp_parse_args($args ?? array(), array(
    'text' => 'Znajdź specjalistę',
    'url' => home_url('/eksperci'),
    'cta_id' => 'sticky-mobile',
));
?>

<div id="v2pro-mobile-cta" class="v2pro-hide-desktop">
    <a
        href="<?php echo esc_url($args['url']); ?>"
        class="v2pro-btn"
        data-cta-id="<?php echo esc_attr($args['cta_id']); ?>"
        data-cta-location="sticky-mobile"
    >
        <?php echo esc_html($args['text']); ?>
    </a>
</div>
