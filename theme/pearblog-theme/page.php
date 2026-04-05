<?php
/**
 * The template for displaying static pages
 *
 * @package PearBlog
 */

pearblog_render_header();
?>

<main id="main" class="pb-main pb-page-main" role="main">
    <div class="<?php echo esc_attr( pearblog_get_container_class() ); ?>">
        <div class="<?php echo esc_attr( pearblog_get_layout_class() ); ?>">
            <div class="<?php echo esc_attr( pearblog_get_content_class() ); ?>">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'pb-page-article' ); ?>>
                        <header class="pb-page-header">
                            <h1 class="pb-page-title"><?php the_title(); ?></h1>
                        </header>

                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="pb-featured-image">
                                <?php the_post_thumbnail( 'pearblog-hero', [ 'loading' => 'eager' ] ); ?>
                            </div>
                        <?php endif; ?>

                        <div class="pb-page-content">
                            <?php the_content(); ?>
                        </div>

                        <?php
                        wp_link_pages( [
                            'before' => '<nav class="pb-page-links"><span>' . __( 'Pages:', 'pearblog-theme' ) . '</span>',
                            'after'  => '</nav>',
                        ] );
                        ?>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php pearblog_render_sidebar(); ?>
        </div>
    </div>
</main>

<?php
pearblog_render_footer();
