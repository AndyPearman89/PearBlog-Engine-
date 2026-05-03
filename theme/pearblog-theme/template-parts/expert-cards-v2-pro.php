<?php
/**
 * Template Part: Expert Cards V2 Pro
 *
 * Display expert profiles with ratings
 *
 * @package PearBlog
 * @version 2.0.0
 */

$args = wp_parse_args($args ?? array(), array(
    'title' => 'Zweryfikowani eksperci',
    'experts' => array(
        array(
            'name' => 'Jan Kowalski',
            'rating' => 4.9,
            'reviews' => 128,
            'specialty' => 'Prawo cywilne',
            'url' => home_url('/eksperci/jan-kowalski'),
        ),
        array(
            'name' => 'Anna Nowak',
            'rating' => 4.8,
            'reviews' => 95,
            'specialty' => 'Finanse osobiste',
            'url' => home_url('/eksperci/anna-nowak'),
        ),
        array(
            'name' => 'Piotr Wiśniewski',
            'rating' => 4.7,
            'reviews' => 112,
            'specialty' => 'Budownictwo',
            'url' => home_url('/eksperci/piotr-wisniewski'),
        ),
    ),
));
?>

<section class="v2pro-section">
    <div class="v2pro-container">
        <?php if ($args['title']) : ?>
            <h2 class="v2pro-h2 v2pro-text-center v2pro-mb-xl">
                <?php echo esc_html($args['title']); ?>
            </h2>
        <?php endif; ?>

        <div class="v2pro-experts v2pro-stagger">
            <?php foreach ($args['experts'] as $expert) : ?>
                <div class="v2pro-expert">
                    <div class="v2pro-expert-avatar">
                        <?php echo esc_html(mb_substr($expert['name'], 0, 1)); ?>
                    </div>

                    <div class="v2pro-expert-info">
                        <div class="v2pro-expert-name">
                            <?php echo esc_html($expert['name']); ?>
                        </div>

                        <div class="v2pro-expert-rating">
                            <span class="v2pro-expert-star">⭐</span>
                            <span><?php echo esc_html($expert['rating']); ?></span>
                            <span class="v2pro-text-subtle">(<?php echo esc_html($expert['reviews']); ?> opinii)</span>
                        </div>

                        <div class="v2pro-expert-specialty" style="font-size: 14px; color: var(--v2pro-text-subtle); margin-top: 4px;">
                            <?php echo esc_html($expert['specialty']); ?>
                        </div>
                    </div>

                    <a
                        href="<?php echo esc_url($expert['url']); ?>"
                        class="v2pro-btn"
                        data-cta-id="expert-<?php echo sanitize_title($expert['name']); ?>"
                        data-cta-location="experts-section"
                        style="white-space: nowrap;"
                    >
                        Poproś o ofertę
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="v2pro-text-center v2pro-mt-xl">
            <a href="<?php echo esc_url(home_url('/eksperci')); ?>" class="v2pro-btn v2pro-btn-secondary">
                Zobacz wszystkich ekspertów →
            </a>
        </div>
    </div>
</section>

<style>
.v2pro-experts {
    display: flex;
    flex-direction: column;
    gap: var(--v2pro-space-md);
}

@media (max-width: 767px) {
    .v2pro-expert {
        flex-direction: column;
        text-align: center;
    }

    .v2pro-expert-avatar {
        margin: 0 auto var(--v2pro-space-md);
    }

    .v2pro-expert-info {
        margin-bottom: var(--v2pro-space-md);
    }

    .v2pro-expert .v2pro-btn {
        width: 100%;
    }
}
</style>
