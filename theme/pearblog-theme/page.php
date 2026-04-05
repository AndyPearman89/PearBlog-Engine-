<?php
/**
 * The template for displaying static pages
 *
 * @package PearBlog
 * @version 5.1.0
 */

pearblog_render_header();
?>

<main id="main" class="pb-main pb-page-main" role="main">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('pb-article pb-page-article'); ?>>

            <!-- Page Header -->
            <header class="pb-page-header">
                <div class="pb-container">
                    <?php echo pearblog_get_breadcrumbs(); ?>
                    <h1 class="pb-page-title"><?php the_title(); ?></h1>
                </div>
            </header>

            <!-- Featured Image -->
            <?php if (has_post_thumbnail()) : ?>
                <div class="pb-page-hero-image">
                    <div class="pb-container">
                        <?php the_post_thumbnail('pearblog-hero', array(
                            'loading' => 'eager',
                            'class'   => 'pb-page-hero-img',
                        )); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="pb-container">
                <div class="<?php echo esc_attr(pearblog_get_layout_class()); ?>">
                    <div class="<?php echo esc_attr(pearblog_get_content_class()); ?>">
                        <div class="pb-entry-content">
                            <?php the_content(); ?>
                        </div>

                        <!-- Page links (for multi-page content) -->
                        <?php
                        wp_link_pages(array(
                            'before' => '<div class="pb-page-links">' . __('Pages:', 'pearblog-theme'),
                            'after'  => '</div>',
                        ));
                        ?>

                        <!-- Social share -->
                        <?php echo pearblog_get_social_share(); ?>
                    </div>

                    <?php pearblog_render_sidebar(); ?>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
</main>

<?php
pearblog_render_footer();
