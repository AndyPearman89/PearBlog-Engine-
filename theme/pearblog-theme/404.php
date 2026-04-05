<?php
/**
 * The template for displaying 404 error pages
 *
 * @package PearBlog
 * @version 5.1.0
 */

pearblog_render_header();
?>

<main id="main" class="pb-main pb-404-main" role="main">

    <!-- 404 Hero -->
    <div class="pb-404-hero">
        <div class="pb-container pb-text-center">
            <div class="pb-404-code" aria-hidden="true">404</div>
            <h1 class="pb-404-title"><?php esc_html_e('Page Not Found', 'pearblog-theme'); ?></h1>
            <p class="pb-404-desc">
                <?php esc_html_e('The page you\'re looking for doesn\'t exist or has been moved.', 'pearblog-theme'); ?>
            </p>
            <div class="pb-404-actions">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="pb-btn pb-btn-primary">
                    ← <?php esc_html_e('Back to Homepage', 'pearblog-theme'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="pb-container pb-404-content">

        <!-- Search -->
        <div class="pb-404-search">
            <h2><?php esc_html_e('Search for what you need', 'pearblog-theme'); ?></h2>
            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="pb-search-refine-form">
                <input type="search" name="s"
                       placeholder="<?php esc_attr_e('Search articles…', 'pearblog-theme'); ?>"
                       class="pb-search-refine-input" />
                <button type="submit" class="pb-btn pb-btn-primary">
                    <?php esc_html_e('Search', 'pearblog-theme'); ?>
                </button>
            </form>
        </div>

        <!-- Popular posts -->
        <?php
        $popular_posts = get_posts(array(
            'posts_per_page' => 6,
            'orderby'        => 'comment_count',
            'order'          => 'DESC',
        ));

        if (!empty($popular_posts)) :
        ?>
            <div class="pb-404-popular">
                <h2><?php esc_html_e('Popular Articles', 'pearblog-theme'); ?></h2>
                <div class="pb-cards">
                    <?php
                    foreach ($popular_posts as $post) {
                        global $post;
                        setup_postdata($post);
                        pearblog_card($post);
                    }
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Category browser -->
        <div class="pb-404-categories">
            <h2><?php esc_html_e('Browse Categories', 'pearblog-theme'); ?></h2>
            <ul class="pb-404-cat-list">
                <?php
                wp_list_categories(array(
                    'orderby'    => 'count',
                    'order'      => 'DESC',
                    'show_count' => true,
                    'title_li'   => '',
                    'number'     => 12,
                ));
                ?>
            </ul>
        </div>

    </div>
</main>

<?php
pearblog_render_footer();
