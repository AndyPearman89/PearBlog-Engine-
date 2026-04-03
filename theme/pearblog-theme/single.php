<?php
/**
 * The template for displaying all single posts
 *
 * SEO Layout: H1, Intro, TL;DR, Sections H2/H3, FAQ, Related, CTA
 *
 * @package PearBlog
 */

pearblog_render_header();
?>

<main id="main" class="pb-main pb-single-main" role="main">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('pb-article'); ?>>
            <!-- Breadcrumbs for SEO -->
            <div class="pb-container">
                <?php echo pearblog_get_breadcrumbs(); ?>
            </div>

            <!-- Content Header -->
            <header class="pb-content-header">
                <div class="pb-container">
                    <!-- H1 - Main Title -->
                    <h1 class="pb-post-title"><?php the_title(); ?></h1>

                    <!-- Post Meta -->
                    <?php echo pearblog_get_post_meta(); ?>

                    <!-- Featured Image -->
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="pb-featured-image">
                            <?php the_post_thumbnail('pearblog-hero', array('loading' => 'eager')); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Main Content Area -->
            <div class="pb-container">
                <div class="<?php echo esc_attr(pearblog_get_layout_class()); ?>">
                    <div class="pb-content-wrapper">
                        <!-- Intro / Excerpt -->
                        <?php if (has_excerpt()) : ?>
                            <div class="pb-intro">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        // Ad slot - after intro
                        pearblog_ads(array(
                            'slot_id' => 'content-top',
                            'position' => 'content',
                        ));
                        ?>

                        <!-- TL;DR Section -->
                        <?php
                        $tldr_items = get_post_meta(get_the_ID(), 'pearblog_tldr', true);
                        if (!empty($tldr_items)) {
                            pearblog_render_tldr($tldr_items);
                        }
                        ?>

                        <!-- Main Content with H2/H3 Structure -->
                        <div class="pb-content-section">
                            <?php the_content(); ?>
                        </div>

                        <?php
                        // Ad slot - mid content
                        pearblog_ads(array(
                            'slot_id' => 'content-middle',
                            'position' => 'content',
                        ));
                        ?>

                        <!-- Post Tags -->
                        <?php
                        $tags = get_the_tags();
                        if ($tags) :
                        ?>
                            <div class="pb-post-tags">
                                <strong><?php _e('Tags:', 'pearblog-theme'); ?></strong>
                                <?php
                                foreach ($tags as $tag) {
                                    echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="pb-tag">' . esc_html($tag->name) . '</a> ';
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- Social Share -->
                        <?php echo pearblog_get_social_share(); ?>
                    </div>

                    <?php pearblog_render_sidebar(); ?>
                </div>
            </div>

            <!-- FAQ Section -->
            <?php
            $faq_items = get_post_meta(get_the_ID(), 'pearblog_faq', true);
            if (!empty($faq_items)) {
                pearblog_faq(array('faq_items' => $faq_items));
            }
            ?>

            <?php
            // Ad slot - before related
            pearblog_ads(array(
                'slot_id' => 'content-bottom',
                'position' => 'content',
            ));
            ?>

            <!-- Related Posts Section for Internal Linking -->
            <?php
            pearblog_related_posts(array(
                'post_id' => get_the_ID(),
                'limit' => 3,
            ));
            ?>

            <!-- CTA Section -->
            <?php
            pearblog_render_cta(array(
                'title' => __('Enjoyed This Article?', 'pearblog-theme'),
                'button_text' => __('Read More Articles', 'pearblog-theme'),
                'button_url' => home_url('/'),
            ));
            ?>

            <!-- Comments -->
            <?php
            if (comments_open() || get_comments_number()) :
            ?>
                <div class="pb-container">
                    <div class="pb-comments">
                        <?php comments_template(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </article>
    <?php endwhile; ?>
</main>

<?php
pearblog_render_footer();
