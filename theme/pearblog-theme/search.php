<?php
/**
 * The template for displaying search results pages
 *
 * @package PearBlog
 * @version 5.1.0
 */

pearblog_render_header();

$query_str = get_search_query();
$result_count = $GLOBALS['wp_query']->found_posts;
?>

<main id="main" class="pb-main pb-search-main" role="main">

    <!-- Search results header -->
    <div class="pb-search-results-header">
        <div class="pb-container">
            <h1 class="pb-search-title">
                <?php
                if ($query_str) {
                    printf(
                        /* translators: %s: search query */
                        esc_html__('Search results for: "%s"', 'pearblog-theme'),
                        '<span class="pb-search-query">' . esc_html($query_str) . '</span>'
                    );
                } else {
                    esc_html_e('Search', 'pearblog-theme');
                }
                ?>
            </h1>
            <?php if ($query_str) : ?>
                <p class="pb-search-count">
                    <?php
                    printf(
                        /* translators: %d: number of results */
                        esc_html(_n( '%d result found', '%d results found', $result_count, 'pearblog-theme' )),
                        $result_count
                    );
                    ?>
                </p>
            <?php endif; ?>

            <!-- Inline search form to refine query -->
            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="pb-search-refine-form">
                <input type="search" name="s" value="<?php echo esc_attr($query_str); ?>"
                       placeholder="<?php esc_attr_e('Refine search…', 'pearblog-theme'); ?>"
                       class="pb-search-refine-input" />
                <button type="submit" class="pb-btn pb-btn-primary">
                    <?php esc_html_e('Search', 'pearblog-theme'); ?>
                </button>
            </form>
        </div>
    </div>

    <div class="pb-container pb-search-results-body">
        <?php if (have_posts()) : ?>
            <div class="<?php echo esc_attr(pearblog_get_layout_class()); ?>">
                <div class="<?php echo esc_attr(pearblog_get_content_class()); ?>">
                    <?php pearblog_render_cards(); ?>
                    <?php pearblog_render_pagination(); ?>
                </div>
                <?php pearblog_render_sidebar(); ?>
            </div>
        <?php else : ?>
            <div class="pb-no-results">
                <div class="pb-no-results-icon" aria-hidden="true">🔍</div>
                <h2><?php esc_html_e('No results found', 'pearblog-theme'); ?></h2>
                <p>
                    <?php
                    printf(
                        /* translators: %s: search query */
                        esc_html__('Nothing found for "%s". Try a different search term or browse categories below.', 'pearblog-theme'),
                        esc_html($query_str)
                    );
                    ?>
                </p>

                <!-- Category browser -->
                <div class="pb-category-browser">
                    <h3><?php esc_html_e('Browse Categories', 'pearblog-theme'); ?></h3>
                    <?php
                    wp_list_categories(array(
                        'orderby'    => 'count',
                        'order'      => 'DESC',
                        'show_count' => true,
                        'title_li'   => '',
                        'number'     => 10,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
pearblog_render_footer();
