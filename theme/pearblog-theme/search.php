<?php
/**
 * The template for displaying search results
 *
 * @package PearBlog
 */

pearblog_render_header();

$search_query = get_search_query();
?>

<main id="main" class="pb-main pb-search-main" role="main">
    <!-- Search header -->
    <div class="pb-hero pb-search-hero pb-text-center">
        <div class="pb-container">
            <h1 class="pb-hero-title">
                <?php
                if ( $search_query ) {
                    printf(
                        /* translators: %s: search query */
                        esc_html__( 'Search results for: %s', 'pearblog-theme' ),
                        '<span class="pb-search-term">' . esc_html( $search_query ) . '</span>'
                    );
                } else {
                    esc_html_e( 'Search', 'pearblog-theme' );
                }
                ?>
            </h1>
            <?php get_search_form(); ?>
        </div>
    </div>

    <div class="<?php echo esc_attr( pearblog_get_container_class() ); ?>">
        <div class="<?php echo esc_attr( pearblog_get_layout_class() ); ?>">
            <div class="<?php echo esc_attr( pearblog_get_content_class() ); ?>">
                <?php if ( have_posts() ) : ?>
                    <p class="pb-search-count">
                        <?php
                        global $wp_query;
                        echo esc_html( sprintf(
                            /* translators: %d: number of search results */
                            _n( '%d result found.', '%d results found.', $wp_query->found_posts, 'pearblog-theme' ),
                            $wp_query->found_posts
                        ) );
                        ?>
                    </p>

                    <?php pearblog_render_cards(); ?>
                    <?php pearblog_render_pagination(); ?>

                <?php else : ?>
                    <div class="pb-no-posts">
                        <h2><?php esc_html_e( 'Nothing found', 'pearblog-theme' ); ?></h2>
                        <p>
                            <?php
                            esc_html_e( 'Sorry, no results matched your search. Try different keywords.', 'pearblog-theme' );
                            ?>
                        </p>
                        <?php get_search_form(); ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="pb-card-cta">
                            <?php esc_html_e( 'Back to Home', 'pearblog-theme' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php pearblog_render_sidebar(); ?>
        </div>
    </div>
</main>

<?php
pearblog_render_footer();
