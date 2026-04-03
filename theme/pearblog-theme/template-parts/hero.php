<?php
/**
 * Template Part: Hero v2 PRO
 *
 * Dynamic header with gradient/image/video, title + subtitle + CTA
 *
 * @package PearBlog
 * @version 2.0.0
 */

$config = pb_get_site_config();

// Default values from config
$hero_title = $config['hero_title'];
$hero_subtitle = $config['hero_subtitle'];
$hero_style_type = $config['hero_style']; // gradient, image, video
$hero_image = $config['hero_image'];
$hero_video = $config['hero_video'];

// Allow override via args
$args = wp_parse_args($args ?? array(), array(
    'title' => $hero_title,
    'subtitle' => $hero_subtitle,
    'style_type' => $hero_style_type,
    'image' => $hero_image,
    'video' => $hero_video,
    'cta_text' => get_option('pearblog_hero_cta_text', ''),
    'cta_url' => get_option('pearblog_hero_cta_url', ''),
));

$hero_classes = array('pb-hero', 'pb-text-center', 'pb-hero-' . esc_attr($args['style_type']));
$hero_style = '';

if ($args['style_type'] === 'image' && !empty($args['image'])) {
    $hero_classes[] = 'has-image';
    $hero_style = 'background-image: url(' . esc_url($args['image']) . ');';
} elseif ($args['style_type'] === 'video' && !empty($args['video'])) {
    $hero_classes[] = 'has-video';
}
?>

<section class="<?php echo esc_attr(implode(' ', $hero_classes)); ?>" <?php echo !empty($hero_style) ? 'style="' . esc_attr($hero_style) . '"' : ''; ?>>
    <?php if ($args['style_type'] === 'video' && !empty($args['video'])) : ?>
        <div class="pb-hero-video-wrapper">
            <video class="pb-hero-video" autoplay muted loop playsinline>
                <source src="<?php echo esc_url($args['video']); ?>" type="video/mp4">
            </video>
            <div class="pb-hero-video-overlay"></div>
        </div>
    <?php endif; ?>

    <div class="pb-container">
        <div class="pb-hero-content">
            <?php if (!empty($args['title'])) : ?>
                <h1 class="pb-hero-title"><?php echo esc_html($args['title']); ?></h1>
            <?php endif; ?>

            <?php if (!empty($args['subtitle'])) : ?>
                <p class="pb-hero-subtitle"><?php echo esc_html($args['subtitle']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['cta_text']) && !empty($args['cta_url'])) : ?>
                <div class="pb-hero-cta">
                    <a href="<?php echo esc_url($args['cta_url']); ?>" class="pb-hero-cta-button">
                        <?php echo esc_html($args['cta_text']); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
