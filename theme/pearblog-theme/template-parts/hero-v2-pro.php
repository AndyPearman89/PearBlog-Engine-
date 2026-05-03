<?php
/**
 * Template Part: Hero V2 Pro - Mobile-First Neon AI
 *
 * High-conversion hero with neon gradient and mobile-first layout
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Default args
$args = wp_parse_args($args ?? array(), array(
    'title' => 'Rozwiąż problem w kilka minut',
    'subtitle' => 'Eksperci, porady i konkretne rozwiązania',
    'cta_text' => 'Znajdź specjalistę',
    'cta_url' => home_url('/eksperci'),
    'show_badges' => true,
    'show_search' => false,
));
?>

<section class="v2pro-hero">
    <div class="v2pro-container">
        <div class="v2pro-hero-content v2pro-fade-in">
            <h1 class="v2pro-hero-title v2pro-gradient-text">
                <?php echo esc_html($args['title']); ?>
            </h1>

            <p class="v2pro-hero-subtitle">
                <?php echo esc_html($args['subtitle']); ?>
            </p>

            <?php if ($args['show_search']) : ?>
                <!-- Search Input -->
                <div class="v2pro-input-wrapper v2pro-mb-lg">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <input
                            type="search"
                            name="s"
                            class="v2pro-input"
                            placeholder="<?php esc_attr_e('Czego szukasz...', 'pearblog-theme'); ?>"
                            value="<?php echo get_search_query(); ?>"
                        />
                    </form>
                </div>
            <?php endif; ?>

            <!-- Primary CTA -->
            <div class="v2pro-mb-lg">
                <a
                    href="<?php echo esc_url($args['cta_url']); ?>"
                    class="v2pro-btn"
                    data-cta-id="hero-primary"
                    data-cta-location="hero"
                >
                    <?php echo esc_html($args['cta_text']); ?>
                </a>
            </div>

            <?php if ($args['show_badges']) : ?>
                <!-- Trust Badges -->
                <div class="v2pro-hero-badges v2pro-stagger">
                    <div class="v2pro-badge">
                        <span>✔</span>
                        <?php _e('Zweryfikowani eksperci', 'pearblog-theme'); ?>
                    </div>
                    <div class="v2pro-badge">
                        <span>✔</span>
                        <?php _e('Szybka odpowiedź', 'pearblog-theme'); ?>
                    </div>
                    <div class="v2pro-badge">
                        <span>✔</span>
                        <?php _e('Realne rozwiązania', 'pearblog-theme'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
