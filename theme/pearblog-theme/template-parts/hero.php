<?php
/**
 * Template Part: Hero
 *
 * Dynamic header with gradient/image, title + intro
 *
 * @package PearBlog
 */

// Default values
$hero_title = get_option('pearblog_hero_title', get_bloginfo('name'));
$hero_intro = get_option('pearblog_hero_intro', get_bloginfo('description'));
$hero_image = get_option('pearblog_hero_image', '');
$hero_use_gradient = get_option('pearblog_hero_gradient', true);

// Allow override via args
$args = wp_parse_args($args ?? array(), array(
    'title' => $hero_title,
    'intro' => $hero_intro,
    'image' => $hero_image,
    'use_gradient' => $hero_use_gradient,
));

$hero_classes = array('pb-hero', 'pb-text-center');
$hero_style = '';

if (!empty($args['image'])) {
    $hero_classes[] = 'has-image';
    $hero_style = 'background-image: url(' . esc_url($args['image']) . ');';
}
?>

<section class="<?php echo esc_attr(implode(' ', $hero_classes)); ?>" <?php echo !empty($hero_style) ? 'style="' . esc_attr($hero_style) . '"' : ''; ?>>
    <div class="pb-container">
        <div class="pb-hero-content">
            <?php if (!empty($args['title'])) : ?>
                <h1 class="pb-hero-title"><?php echo esc_html($args['title']); ?></h1>
            <?php endif; ?>

            <?php if (!empty($args['intro'])) : ?>
                <p class="pb-hero-intro"><?php echo esc_html($args['intro']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
