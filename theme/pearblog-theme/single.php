<?php
/**
 * The template for displaying all single posts
 *
 * SEO-optimized article page layout with monetization
 *
 * MANDATORY LAYOUT:
 * 1. H1 (main keyword)
 * 2. TL;DR box (top)
 * 3. Ads block (top) ← HIGH CTR
 * 4. Affiliate box (top) ← HIGH $$$
 * 5. Table of Contents
 * 6. Content section
 * 7. Ads block (middle)
 * 8. Content section
 * 9. Affiliate box (middle)
 * 10. Content
 * 11. FAQ
 * 12. Final affiliate CTA
 *
 * @package PearBlog
 * @version 2.0.0
 */

pearblog_render_header();
?>

<main id="main" class="pb-main pb-single-main" role="main">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('pb-article pb-seo-article'); ?>>

            <!-- Breadcrumbs for SEO -->
            <div class="pb-container">
                <?php echo pearblog_get_breadcrumbs(); ?>
            </div>

            <!-- 1. Content Header with H1 -->
            <header class="pb-content-header">
                <div class="pb-container">
                    <!-- H1 - Main Title (Main Keyword) -->
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

                        <!-- 2. TL;DR Box (Top) -->
                        <?php
                        $tldr_items = get_post_meta(get_the_ID(), 'pearblog_tldr', true);
                        if (!empty($tldr_items)) {
                            pearblog_render_tldr($tldr_items);
                        }
                        ?>

                        <!-- 3. Ads Block (Top) - HIGH CTR Position -->
                        <?php
                        pearblog_ads(array(
                            'slot_id' => 'content-top',
                            'position' => 'content',
                        ));
                        ?>

                        <!-- 4. Affiliate Box (Top) - HIGH $$$ Position -->
                        <?php
                        $location = get_post_meta(get_the_ID(), 'pearblog_location', true);
                        if (empty($location)) {
                            // Try to extract from title
                            $location = pearblog_extract_location_from_content(get_the_title());
                        }

                        pearblog_affiliate_box(array(
                            'position' => 'top',
                            'location' => $location,
                            'fallback_enabled' => true,
                        ));
                        ?>

                        <!-- 5. Table of Contents -->
                        <?php
                        // TOC is rendered in sidebar for desktop, inline for mobile
                        // Mobile version rendered here
                        if (wp_is_mobile()) {
                            get_template_part('template-parts/block-toc');
                        }
                        ?>

                        <!-- 6. Content Section (First Part) -->
                        <div class="pb-content-section pb-content-part-1">
                            <?php
                            // Get content and split it for mid-content insertions
                            $content = get_the_content();
                            $content = apply_filters('the_content', $content);

                            // Split content by paragraphs
                            $paragraphs = preg_split('/(<p[^>]*>.*?<\/p>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                            $total_paragraphs = count(array_filter($paragraphs, function($p) {
                                return preg_match('/<p[^>]*>/i', $p);
                            }));

                            // Calculate split points
                            $first_split = ceil($total_paragraphs * 0.33); // After 33%
                            $second_split = ceil($total_paragraphs * 0.66); // After 66%

                            $paragraph_count = 0;
                            $current_output = '';

                            foreach ($paragraphs as $paragraph) {
                                if (preg_match('/<p[^>]*>/i', $paragraph)) {
                                    $paragraph_count++;
                                }

                                $current_output .= $paragraph;

                                // Output first section
                                if ($paragraph_count === $first_split) {
                                    echo $current_output;
                                    $current_output = '';
                                    break;
                                }
                            }
                            ?>
                        </div>

                        <!-- 7. Ads Block (Middle) -->
                        <?php
                        pearblog_ads(array(
                            'slot_id' => 'content-middle',
                            'position' => 'content',
                        ));
                        ?>

                        <!-- 8. Content Section (Second Part) -->
                        <div class="pb-content-section pb-content-part-2">
                            <?php
                            // Continue outputting remaining content until second split
                            $continue = false;
                            foreach ($paragraphs as $paragraph) {
                                if ($continue || $paragraph_count < $first_split) {
                                    if (preg_match('/<p[^>]*>/i', $paragraph)) {
                                        $paragraph_count++;
                                    }

                                    if ($paragraph_count > $first_split) {
                                        echo $paragraph;
                                    }

                                    if ($paragraph_count === $second_split) {
                                        break;
                                    }
                                } else {
                                    $continue = true;
                                }
                            }
                            ?>
                        </div>

                        <!-- 9. Affiliate Box (Middle) -->
                        <?php
                        pearblog_affiliate_box(array(
                            'position' => 'middle',
                            'location' => $location,
                            'fallback_enabled' => true,
                        ));
                        ?>

                        <!-- 10. Content Section (Final Part) -->
                        <div class="pb-content-section pb-content-part-3">
                            <?php
                            // Output remaining content
                            $continue = false;
                            foreach ($paragraphs as $paragraph) {
                                if ($continue || $paragraph_count < $second_split) {
                                    if (preg_match('/<p[^>]*>/i', $paragraph)) {
                                        $paragraph_count++;
                                    }

                                    if ($paragraph_count > $second_split) {
                                        echo $paragraph;
                                    }
                                } else {
                                    $continue = true;
                                }
                            }
                            ?>
                        </div>

                        <!-- Post Tags for SEO -->
                        <?php
                        $tags = get_the_tags();
                        if ($tags) :
                        ?>
                            <div class="pb-post-tags">
                                <strong><?php _e('Tagi:', 'pearblog-theme'); ?></strong>
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

                    <!-- Sidebar (contains TOC for desktop) -->
                    <?php pearblog_render_sidebar(); ?>
                </div>
            </div>

            <!-- 11. FAQ Section with Schema.org markup -->
            <?php
            $faq_items = get_post_meta(get_the_ID(), 'pearblog_faq', true);
            if (!empty($faq_items)) {
                pearblog_faq(array('faq_items' => $faq_items));
            }
            ?>

            <!-- Related Posts Section for Internal Linking (SEO) -->
            <?php
            pearblog_related_posts(array(
                'post_id' => get_the_ID(),
                'limit' => 3,
            ));
            ?>

            <?php
            // Ad slot - before final CTA
            pearblog_ads(array(
                'slot_id' => 'content-bottom',
                'position' => 'content',
            ));
            ?>

            <!-- 12. Final Affiliate CTA -->
            <?php
            pearblog_affiliate_box(array(
                'position' => 'bottom',
                'location' => $location,
                'fallback_enabled' => true,
            ));
            ?>

            <!-- Generic CTA Section -->
            <?php
            pearblog_render_cta(array(
                'title' => __('Podobał Ci się ten artykuł?', 'pearblog-theme'),
                'subtitle' => __('Sprawdź więcej inspirujących treści', 'pearblog-theme'),
                'button_text' => __('Czytaj więcej artykułów', 'pearblog-theme'),
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
