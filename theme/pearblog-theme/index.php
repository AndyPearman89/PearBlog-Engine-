<?php
/**
 * The main template file - Homepage
 *
 * @package PearBlog
 */

pearblog_render_header();
?>

<main id="main" class="pb-main" role="main">
    <?php
    // Hero section
    pearblog_hero(array(
        'title' => get_option('pearblog_hero_title', get_bloginfo('name')),
        'intro' => get_option('pearblog_hero_intro', get_bloginfo('description')),
    ));
    ?>

    <?php
    // Ad slot - header
    pearblog_ads(array(
        'slot_id' => 'header',
        'position' => 'header',
    ));
    ?>

    <div class="<?php echo esc_attr(pearblog_get_container_class()); ?>">
        <div class="<?php echo esc_attr(pearblog_get_layout_class()); ?>">
            <div class="<?php echo esc_attr(pearblog_get_content_class()); ?>">
                <?php if (have_posts()) : ?>
                    <?php
                    // Render cards grid
                    pearblog_render_cards();
                    ?>

                    <?php
                    // Pagination
                    pearblog_render_pagination();
                    ?>

                <?php else : ?>
                    <div class="pb-no-posts">
                        <h2><?php _e('No posts found', 'pearblog-theme'); ?></h2>
                        <p><?php _e('Sorry, no posts were found. Please check back later.', 'pearblog-theme'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php pearblog_render_sidebar(); ?>
        </div>
    </div>

    <?php
    // CTA section
    pearblog_render_cta(array(
        'title' => __('Want to Stay Updated?', 'pearblog-theme'),
        'button_text' => __('Subscribe Now', 'pearblog-theme'),
        'button_url' => home_url('/subscribe'),
    ));
    ?>
</main>

<?php
pearblog_render_footer();
