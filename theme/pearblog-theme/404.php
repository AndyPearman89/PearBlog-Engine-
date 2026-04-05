<?php
/**
 * The template for displaying 404 pages (page not found)
 *
 * @package PearBlog
 */

pearblog_render_header();
?>

<main id="main" class="pb-main pb-error-main" role="main">
    <div class="pb-hero pb-error-hero pb-text-center">
        <div class="pb-container">
            <div class="pb-error-code" aria-hidden="true">404</div>
            <h1 class="pb-hero-title"><?php esc_html_e( 'Page Not Found', 'pearblog-theme' ); ?></h1>
            <p class="pb-hero-intro">
                <?php esc_html_e( 'The page you are looking for has been moved, deleted, or may never have existed.', 'pearblog-theme' ); ?>
            </p>

            <div class="pb-error-actions">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="pb-cta-button">
                    <?php esc_html_e( 'Back to Home', 'pearblog-theme' ); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="<?php echo esc_attr( pearblog_get_container_class() ); ?>" style="text-align:center; padding: 40px 0;">
        <h2><?php esc_html_e( 'Try searching instead', 'pearblog-theme' ); ?></h2>
        <?php get_search_form(); ?>

        <?php
        // Show recent posts as fallback content
        $recent = get_posts( [
            'numberposts' => 6,
            'post_status' => 'publish',
        ] );
        if ( $recent ) :
        ?>
            <h3 style="margin-top: 2em;"><?php esc_html_e( 'Recent Articles', 'pearblog-theme' ); ?></h3>
            <ul class="pb-error-recent-list" style="list-style:none; padding:0; display:flex; flex-wrap:wrap; gap:12px; justify-content:center; margin-top:12px;">
                <?php foreach ( $recent as $post ) : ?>
                    <li>
                        <a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
                            <?php echo esc_html( get_the_title( $post ) ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

<?php
pearblog_render_footer();
