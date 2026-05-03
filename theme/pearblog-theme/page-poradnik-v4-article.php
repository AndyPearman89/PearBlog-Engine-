<?php
/**
 * Template Name: Poradnik.pro V4 Article
 *
 * Decision-focused article template with comparison, calculator, and ranking modules
 *
 * @package PearBlog
 * @version 4.0.0
 */

get_header();
?>

<?php while (have_posts()): the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('poradnik-v4-article'); ?>>
        <div class="pb-container" style="max-width: 800px; padding-top: 2rem;">
            <!-- Breadcrumbs -->
            <?php if (function_exists('pearblog_breadcrumbs')): ?>
                <nav aria-label="Breadcrumb" style="margin-bottom: 1.5rem;">
                    <?php pearblog_breadcrumbs(); ?>
                </nav>
            <?php endif; ?>

            <!-- Title -->
            <header class="entry-header" style="margin-bottom: 2rem;">
                <h1 style="font-size: 2.5rem; font-weight: 700; line-height: 1.1; margin-bottom: 1rem;">
                    <?php the_title(); ?>
                </h1>

                <div style="display: flex; gap: 1rem; align-items: center; color: var(--poradnik-text-secondary); font-size: 0.875rem;">
                    <time datetime="<?php echo get_the_date('c'); ?>">
                        <?php echo get_the_date(); ?>
                    </time>
                    <span>•</span>
                    <span><?php echo esc_html(get_the_author()); ?></span>
                    <?php if (function_exists('pearblog_reading_time')): ?>
                        <span>•</span>
                        <span><?php echo esc_html(pearblog_reading_time()); ?> min czytania</span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Featured Image -->
            <?php if (has_post_thumbnail()): ?>
                <div style="margin-bottom: 2rem; border-radius: var(--poradnik-radius-lg); overflow: hidden;">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>

            <!-- TL;DR Summary -->
            <?php if (get_post_meta(get_the_ID(), '_poradnik_tldr', true)): ?>
                <div class="poradnik-smart-block" style="margin-bottom: 2rem;">
                    <div class="poradnik-smart-block__header">
                        <h2 class="poradnik-smart-block__title">W skrócie</h2>
                        <span class="poradnik-smart-block__badge">TL;DR</span>
                    </div>
                    <div class="poradnik-smart-block__content">
                        <?php echo wp_kses_post(get_post_meta(get_the_ID(), '_poradnik_tldr', true)); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="entry-content" style="line-height: 1.8; font-size: 1.125rem;">
                <?php the_content(); ?>
            </div>

            <!-- Comparison Module -->
            <?php
            $comparison_data = poradnik_get_comparison_data();
            if (!empty($comparison_data)):
                poradnik_comparison([
                    'title' => 'Porównanie opcji',
                    'items' => $comparison_data,
                    'auto_winner' => true,
                ]);
            endif;
            ?>

            <!-- Calculator Module -->
            <?php
            $calculator_config = poradnik_get_calculator_config();
            if (!empty($calculator_config)):
                poradnik_calculator($calculator_config);
            endif;
            ?>

            <!-- Ranking Module -->
            <?php
            $ranking_data = poradnik_get_ranking_data();
            if (!empty($ranking_data)):
                poradnik_ranking([
                    'title' => 'Ranking wykonawców',
                    'items' => $ranking_data,
                    'filters' => [
                        ['label' => 'Wszystkie', 'value' => 'all'],
                        ['label' => 'Twoja lokalizacja', 'value' => 'local'],
                        ['label' => 'Najlepiej oceniani', 'value' => 'top-rated'],
                        ['label' => 'Najszybsi', 'value' => 'fast'],
                    ],
                ]);
            endif;
            ?>

            <!-- FAQ Section -->
            <?php if (function_exists('pearblog_faq_block')): ?>
                <?php
                $faq_items = get_post_meta(get_the_ID(), '_poradnik_faq', true);
                if (!empty($faq_items)):
                ?>
                    <div style="margin-top: 3rem;">
                        <?php pearblog_faq_block(['items' => $faq_items]); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Related Articles -->
            <?php
            $related_posts = pearblog_get_related_posts(get_the_ID(), 3);
            if (!empty($related_posts)):
            ?>
                <div style="margin-top: 3rem;">
                    <div class="poradnik-smart-block">
                        <div class="poradnik-smart-block__header">
                            <h2 class="poradnik-smart-block__title">Powiązane artykuły</h2>
                            <span class="poradnik-smart-block__badge">Może Cię zainteresować</span>
                        </div>
                        <div class="poradnik-smart-block__content">
                            <div style="display: grid; gap: 1rem;">
                                <?php foreach ($related_posts as $related): ?>
                                    <a
                                        href="<?php echo get_permalink($related); ?>"
                                        style="
                                            display: flex;
                                            gap: 1rem;
                                            padding: 1rem;
                                            background: var(--poradnik-bg);
                                            border: 1px solid var(--poradnik-border);
                                            border-radius: var(--poradnik-radius);
                                            text-decoration: none;
                                            transition: var(--poradnik-transition);
                                        "
                                        onmouseover="this.style.borderColor='var(--poradnik-accent)'"
                                        onmouseout="this.style.borderColor='var(--poradnik-border)'"
                                    >
                                        <?php if (has_post_thumbnail($related)): ?>
                                            <div style="flex-shrink: 0; width: 80px; height: 80px; border-radius: var(--poradnik-radius); overflow: hidden;">
                                                <?php echo get_the_post_thumbnail($related, 'thumbnail'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div style="flex: 1;">
                                            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--poradnik-text-primary);">
                                                <?php echo esc_html($related->post_title); ?>
                                            </h3>
                                            <p style="color: var(--poradnik-text-secondary); font-size: 0.875rem; margin: 0;">
                                                <?php echo wp_trim_words($related->post_excerpt ?: $related->post_content, 15); ?>
                                            </p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Lead Capture -->
            <?php if (function_exists('pearblog_lead_form')): ?>
                <div style="margin-top: 3rem;">
                    <?php
                    pearblog_lead_form([
                        'title' => 'Potrzebujesz pomocy eksperta?',
                        'description' => 'Wypełnij formularz, a my polecimy Ci najlepszych specjalistów w Twojej okolicy.',
                        'type' => 'consultation',
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
<?php endwhile; ?>

<?php
get_footer();
?>
