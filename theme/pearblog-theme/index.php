<?php
/**
 * The main template file - Homepage V3
 *
 * High-conversion landing page for poradnik.pro
 *
 * @package PearBlog
 * @version 3.0.0
 */

pearblog_render_header();

// Check if V3 layout is enabled
$use_v3_layout = get_option('pearblog_homepage_version', 'v3') === 'v3';
?>

<main id="main" class="pb-main <?php echo $use_v3_layout ? 'pb-main-v3' : ''; ?>" role="main">
    <?php if ($use_v3_layout) : ?>
        <!-- V3 High-Conversion Layout -->

        <?php
        // Hero section with search
        pearblog_hero(array(
            'title' => get_option('pearblog_hero_title', 'Rozwiąż problem w jednym miejscu.'),
            'subtitle' => get_option('pearblog_hero_subtitle', 'Znajdź odpowiedź, porównaj opcje i wybierz najlepiej — bez chaosu i tracenia czasu.'),
            'version' => 'v3',
        ));
        ?>

        <?php
        // Quick Actions - Decision Entry Points
        pearblog_quick_actions();
        ?>

        <?php
        // Trending Topics
        pearblog_trending();
        ?>

        <?php
        // How It Works Flow
        pearblog_how_it_works();
        ?>

        <?php
        // Features Sections (Poradniki, Porównania, Rankingi, Kalkulatory)
        pearblog_features();
        ?>

        <?php
        // AI Advisor
        pearblog_ai_advisor();
        ?>

        <?php
        // Experts Section
        pearblog_experts_section();
        ?>

        <?php
        // For Specialists CTA
        pearblog_for_specialists();
        ?>

        <?php
        // Why It Works
        pearblog_why_it_works();
        ?>

        <?php
        // Final CTA
        pearblog_final_cta();
        ?>

    <?php else : ?>
        <!-- V2 Standard Layout -->

        <?php
        // Hero section
        pearblog_hero(array(
            'title' => get_option('pearblog_hero_title', get_bloginfo('name')),
            'intro' => get_option('pearblog_hero_intro', get_bloginfo('description')),
            'version' => 'v2',
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
    <?php endif; ?>
</main>

<?php
pearblog_render_footer();
