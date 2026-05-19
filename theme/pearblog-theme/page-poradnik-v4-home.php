<?php
/**
 * Template Name: Poradnik.pro V4 Homepage
 *
 * Decision Engine Platform — Tagline System V5
 *
 * @package PearBlog
 * @version 4.1.0
 */

get_header();
?>

<main class="poradnik-v4-home">
    <!-- Hero Section — Decision Start -->
    <section class="poradnik-hero-v4">
        <div class="pb-container">
            <h1 class="poradnik-hero-v4__title">
                <?php echo esc_html(get_option('poradnik_hero_v4_title', 'Od problemu do decyzji.')); ?>
            </h1>

            <p class="poradnik-hero-v4__subtitle">
                <?php echo esc_html(get_option('poradnik_hero_v4_subtitle', 'Porównania, rankingi, koszty i specjaliści w jednym miejscu.')); ?>
            </p>

            <!-- Search -->
            <div class="poradnik-hero-v4__search">
                <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <input
                        type="search"
                        name="s"
                        class="poradnik-hero-v4__search-input"
                        placeholder="np. koszt remontu łazienki, pompa ciepła czy gaz, dobry prawnik Katowice..."
                        value="<?php echo get_search_query(); ?>"
                        autocomplete="off"
                    >
                    <button type="submit" class="poradnik-hero-v4__search-button" aria-label="Szukaj">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="poradnik-hero-v4__quick-actions">
                <?php
                $quick_actions = get_option('poradnik_quick_actions', [
                    ['label' => 'Remont domu', 'url' => '/remont-domu/'],
                    ['label' => 'Kredyt hipoteczny', 'url' => '/kredyt-hipoteczny/'],
                    ['label' => 'Ubezpieczenie', 'url' => '/ubezpieczenie/'],
                    ['label' => 'Firma sprzątająca', 'url' => '/sprzatanie/'],
                ]);

                foreach ($quick_actions as $action):
                ?>
                    <a href="<?php echo esc_url($action['url']); ?>" class="poradnik-hero-v4__quick-action">
                        <?php echo esc_html($action['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Dynamic Suggestions Section -->
    <section class="poradnik-suggestions" style="padding: 3rem 0;">
        <div class="pb-container">
            <div class="poradnik-smart-block">
                <div class="poradnik-smart-block__header">
                    <h2 class="poradnik-smart-block__title">Dla Ciebie</h2>
                    <span class="poradnik-smart-block__badge">Personalizowane</span>
                </div>

                <div class="poradnik-smart-block__content">
                    <?php
                    // Get personalized posts based on user behavior
                    $personalized_posts = get_posts([
                        'posts_per_page' => 3,
                        'post_status' => 'publish',
                        'orderby' => 'date',
                    ]);

                    if ($personalized_posts):
                    ?>
                        <div class="poradnik-comparison">
                            <?php foreach ($personalized_posts as $post): ?>
                                <div class="poradnik-comparison__item">
                                    <?php if (has_post_thumbnail($post)): ?>
                                        <div style="margin-bottom: 1rem;">
                                            <?php echo get_the_post_thumbnail($post, 'medium', ['style' => 'border-radius: 0.5rem;']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <h3 class="poradnik-comparison__title">
                                        <?php echo esc_html($post->post_title); ?>
                                    </h3>

                                    <p style="color: var(--poradnik-text-secondary); font-size: 0.875rem; margin: 1rem 0;">
                                        <?php echo wp_trim_words($post->post_excerpt ?: $post->post_content, 20); ?>
                                    </p>

                                    <a href="<?php echo get_permalink($post); ?>" class="poradnik-comparison__cta">
                                        Czytaj więcej
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Most Chosen Section -->
    <section class="poradnik-most-chosen" style="padding: 2rem 0 3rem;">
        <div class="pb-container">
            <div class="poradnik-smart-block">
                <div class="poradnik-smart-block__header">
                    <h2 class="poradnik-smart-block__title">Najczęściej wybierane</h2>
                    <span class="poradnik-smart-block__badge">Popularne</span>
                </div>

                <div class="poradnik-smart-block__content">
                    <?php
                    // Get popular posts based on views/engagement
                    $popular_posts = get_posts([
                        'posts_per_page' => 5,
                        'post_status' => 'publish',
                        'orderby' => 'comment_count',
                    ]);

                    if ($popular_posts):
                        $ranking_items = array_map(function($post) {
                            return [
                                'name' => $post->post_title,
                                'description' => wp_trim_words($post->post_excerpt ?: $post->post_content, 15),
                                'cta' => 'Zobacz',
                                'url' => get_permalink($post),
                            ];
                        }, $popular_posts);

                        poradnik_ranking([
                            'title' => '',
                            'items' => $ranking_items,
                        ]);
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories as Decision Points -->
    <section class="poradnik-categories" style="padding: 2rem 0 4rem;">
        <div class="pb-container">
            <h2 style="text-align: center; margin-bottom: 2rem; font-size: 1.5rem;">
                Popularne kategorie
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <?php
                $categories = get_categories([
                    'orderby' => 'count',
                    'order' => 'DESC',
                    'number' => 6,
                ]);

                foreach ($categories as $category):
                ?>
                    <a
                        href="<?php echo get_category_link($category); ?>"
                        style="
                            display: flex;
                            flex-direction: column;
                            padding: 1.5rem;
                            background: var(--poradnik-surface);
                            border: 1px solid var(--poradnik-border);
                            border-radius: var(--poradnik-radius-lg);
                            text-decoration: none;
                            transition: var(--poradnik-transition);
                        "
                        onmouseover="this.style.borderColor='var(--poradnik-accent)'"
                        onmouseout="this.style.borderColor='var(--poradnik-border)'"
                    >
                        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--poradnik-text-primary);">
                            <?php echo esc_html($category->name); ?>
                        </h3>
                        <p style="color: var(--poradnik-text-secondary); font-size: 0.875rem; margin-bottom: 1rem;">
                            <?php echo esc_html($category->description ?: 'Odkryj najlepsze rozwiązania'); ?>
                        </p>
                        <span style="color: var(--poradnik-accent); font-size: 0.875rem; font-weight: 500;">
                            <?php echo esc_html($category->count); ?> poradników →
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Example AI Suggestion -->
    <?php
    poradnik_ai_suggestion([
        'title' => 'Inteligentne decyzje zaczynają się tutaj.',
        'text' => 'AI + wiedza + porównania + wykonawcy — opisz problem, a resztą zajmiemy się za Ciebie. Jeden flow od pytania do działania.',
        'action_text' => '🔎 Znajdź rozwiązanie',
        'action_url' => '/znajdz-rozwiazanie/',
    ]);
    ?>
</main>

<?php
get_footer();
?>
