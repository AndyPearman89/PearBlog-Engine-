<?php
/**
 * Template Part: Hero v3 - Search-First High Conversion
 *
 * Decision-focused hero with search, quick actions, and stats
 *
 * @package PearBlog
 * @version 3.0.0
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
    'version' => get_option('pearblog_hero_version', 'v3'), // v2 or v3
));

$hero_classes = array('pb-hero', 'pb-text-center', 'pb-hero-' . esc_attr($args['style_type']));
$hero_style = '';

if ($args['style_type'] === 'image' && !empty($args['image'])) {
    $hero_classes[] = 'has-image';
    $hero_style = 'background-image: url(' . esc_url($args['image']) . ');';
} elseif ($args['style_type'] === 'video' && !empty($args['video'])) {
    $hero_classes[] = 'has-video';
}

// Check if using V3 version
$is_v3 = ($args['version'] === 'v3');
?>

<section class="<?php echo esc_attr(implode(' ', $hero_classes)); ?> <?php echo $is_v3 ? 'pb-hero-v3' : ''; ?>" <?php echo !empty($hero_style) ? 'style="' . esc_attr($hero_style) . '"' : ''; ?>>
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
            <?php if ($is_v3) : ?>
                <!-- V3 Hero: Search-First Decision Platform -->
                <h1 class="pb-hero-title"><?php echo esc_html($args['title'] ?: 'Rozwiąż problem w jednym miejscu.'); ?></h1>

                <p class="pb-hero-subtitle">
                    <?php echo esc_html($args['subtitle'] ?: 'Znajdź odpowiedź, porównaj opcje i wybierz najlepiej — bez chaosu i tracenia czasu.'); ?>
                </p>

                <!-- Search Box -->
                <div class="pb-hero-search">
                    <form role="search" method="get" class="pb-hero-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <div class="pb-hero-search-input-wrapper">
                            <span class="pb-hero-search-icon">🔍</span>
                            <input
                                type="search"
                                class="pb-hero-search-input"
                                placeholder="<?php esc_attr_e('„koszt remontu łazienki", „pompa ciepła czy gaz", „dobry prawnik Katowice"', 'pearblog-theme'); ?>"
                                value="<?php echo get_search_query(); ?>"
                                name="s"
                            />
                            <button type="submit" class="pb-hero-search-button">
                                <?php _e('Szukaj', 'pearblog-theme'); ?>
                            </button>
                        </div>
                    </form>

                    <!-- Quick Action Buttons -->
                    <div class="pb-hero-quick-actions">
                        <a href="<?php echo esc_url(home_url('/pytanie')); ?>" class="pb-hero-quick-btn">
                            <span class="pb-hero-quick-icon">❓</span>
                            <?php _e('Zadaj pytanie', 'pearblog-theme'); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/eksperci')); ?>" class="pb-hero-quick-btn">
                            <span class="pb-hero-quick-icon">🧑‍💼</span>
                            <?php _e('Znajdź specjalistę', 'pearblog-theme'); ?>
                        </a>
                    </div>
                </div>

                <!-- Stats Counter -->
                <div class="pb-hero-stats">
                    <div class="pb-hero-stat">
                        <span class="pb-hero-stat-number">+50 000</span>
                        <span class="pb-hero-stat-label"><?php _e('porad', 'pearblog-theme'); ?></span>
                    </div>
                    <div class="pb-hero-stat-divider">•</div>
                    <div class="pb-hero-stat">
                        <span class="pb-hero-stat-number">+10 000</span>
                        <span class="pb-hero-stat-label"><?php _e('ekspertów', 'pearblog-theme'); ?></span>
                    </div>
                    <div class="pb-hero-stat-divider">•</div>
                    <div class="pb-hero-stat">
                        <span class="pb-hero-stat-number"><?php _e('tysiące', 'pearblog-theme'); ?></span>
                        <span class="pb-hero-stat-label"><?php _e('decyzji dziennie', 'pearblog-theme'); ?></span>
                    </div>
                </div>

            <?php else : ?>
                <!-- V2 Hero: Original Version -->
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
            <?php endif; ?>
        </div>
    </div>
</section>
