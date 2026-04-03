<?php
/**
 * The template for displaying category archive pages
 *
 * @package PearBlog
 */

pearblog_render_header();

$category = get_queried_object();
?>

<main id="main" class="pb-main pb-category-main" role="main">
    <!-- Category Header -->
    <div class="pb-hero pb-category-hero pb-text-center">
        <div class="pb-container">
            <div class="pb-hero-content">
                <!-- Breadcrumbs -->
                <?php echo pearblog_get_breadcrumbs(); ?>

                <!-- H1 - Category Name -->
                <h1 class="pb-hero-title">
                    <?php single_cat_title(); ?>
                </h1>

                <!-- Category Description -->
                <?php if (category_description()) : ?>
                    <div class="pb-hero-intro">
                        <?php echo category_description(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    // Ad slot - header
    pearblog_ads(array(
        'slot_id' => 'category-header',
        'position' => 'header',
    ));
    ?>

    <div class="<?php echo esc_attr(pearblog_get_container_class()); ?>">
        <div class="<?php echo esc_attr(pearblog_get_layout_class()); ?>">
            <div class="<?php echo esc_attr(pearblog_get_content_class()); ?>">
                <?php if (have_posts()) : ?>
                    <div class="pb-category-meta">
                        <p>
                            <?php
                            global $wp_query;
                            $total_posts = $wp_query->found_posts;
                            printf(
                                _n('%d article in this category', '%d articles in this category', $total_posts, 'pearblog-theme'),
                                $total_posts
                            );
                            ?>
                        </p>
                    </div>

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
                        <h2><?php _e('No posts found in this category', 'pearblog-theme'); ?></h2>
                        <p><?php _e('Sorry, no posts were found in this category. Please check back later.', 'pearblog-theme'); ?></p>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="pb-card-cta">
                            <?php _e('Back to Home', 'pearblog-theme'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php pearblog_render_sidebar(); ?>
        </div>
    </div>

    <!-- CTA for category pages -->
    <?php
    pearblog_render_cta(array(
        'title' => sprintf(__('Explore More in %s', 'pearblog-theme'), single_cat_title('', false)),
        'button_text' => __('View All Categories', 'pearblog-theme'),
        'button_url' => home_url('/'),
    ));
    ?>
</main>

<?php
pearblog_render_footer();
