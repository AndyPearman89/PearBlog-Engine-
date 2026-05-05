<?php
/**
 * Template Name: Poradnik.pro Front Page (Decision Hub)
 *
 * Complete decision hub implementation with:
 * - Hero with large search + autosuggest
 * - 3 quick action tiles
 * - Live "users viewing" section
 * - Content mix (2 poradniki + 1 comparison + 1 ranking)
 * - Expert strip
 * - Bottom CTA
 *
 * Based on PORADNIK-PRO-WIREFRAME-SYSTEM.md specification
 *
 * @package PearBlog
 * @version 3.0.0
 */

get_header(); ?>

<!-- Hero Section with Large Search -->
<section class="hero hero-decision-hub">
    <div class="container">
        <h1>Czego szukasz?</h1>
        <p class="hero-subtitle">Znajdź poradniki, porównania i najlepszych wykonawców</p>

        <!-- Large Search Input with Autosuggest -->
        <div class="search-hub">
            <div class="search-hub-input-wrapper">
                <span class="search-icon">🔍</span>
                <input
                    type="text"
                    id="hub-search-input"
                    class="search-hub-input"
                    placeholder="np. budowa domu, remont mieszkania, hydraulik..."
                    autocomplete="off"
                >
                <button type="button" class="search-hub-clear" id="search-clear" style="display: none;">&times;</button>
            </div>

            <!-- Autosuggest Dropdown -->
            <div class="search-hub-results" id="search-results" style="display: none;">
                <div class="search-results-inner">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Popular Searches -->
        <div class="popular-searches">
            <span class="popular-label">Popularne:</span>
            <a href="#" class="popular-tag" data-search="budowa domu">Budowa domu</a>
            <a href="#" class="popular-tag" data-search="remont mieszkania">Remont mieszkania</a>
            <a href="#" class="popular-tag" data-search="elektryka">Elektryka</a>
            <a href="#" class="popular-tag" data-search="hydraulika">Hydraulika</a>
        </div>
    </div>
</section>

<!-- Quick Action Tiles (3 tiles) -->
<section class="quick-actions-section">
    <div class="container">
        <div class="quick-tiles-grid">
            <a href="<?php echo esc_url(get_post_type_archive_link('poradnik')); ?>" class="quick-tile quick-tile-poradnik">
                <div class="quick-tile-icon">📄</div>
                <h3>Poradniki</h3>
                <p>Kompleksowe przewodniki krok po kroku</p>
                <span class="quick-tile-arrow">→</span>
            </a>

            <a href="<?php echo esc_url(home_url('/porownania/')); ?>" class="quick-tile quick-tile-comparison">
                <div class="quick-tile-icon">🆚</div>
                <h3>Porównania</h3>
                <p>Sprawdź różnice i wybierz najlepsze rozwiązanie</p>
                <span class="quick-tile-arrow">→</span>
            </a>

            <a href="<?php echo esc_url(home_url('/ranking/')); ?>" class="quick-tile quick-tile-ranking">
                <div class="quick-tile-icon">🏆</div>
                <h3>Ranking</h3>
                <p>Najlepiej oceniani wykonawcy w Twojej okolicy</p>
                <span class="quick-tile-arrow">→</span>
            </a>
        </div>
    </div>
</section>

<!-- Live Users Viewing -->
<section class="live-activity-section">
    <div class="container">
        <div class="live-activity-widget">
            <div class="live-activity-indicator">
                <span class="pulse-dot"></span>
                <span class="live-text">Na żywo</span>
            </div>
            <div class="live-activity-content">
                <strong id="live-users-count">247</strong> osób przegląda teraz poradniki budowlane
            </div>
            <div class="live-activity-items" id="live-activity-feed">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>
</section>

<!-- Content Mix: 2 Poradniki + 1 Comparison + 1 Ranking -->
<section class="content-mix-section">
    <div class="container">
        <h2 class="section-title">Polecane dla Ciebie</h2>

        <div class="content-mix-grid">
            <?php
            // Get 2 recent poradniki
            $poradniki = new WP_Query([
                'post_type' => 'post',
                'posts_per_page' => 2,
                'orderby' => 'date',
                'order' => 'DESC',
                'tax_query' => [
                    [
                        'taxonomy' => 'category',
                        'field' => 'slug',
                        'terms' => 'poradnik',
                    ],
                ],
            ]);

            if ($poradniki->have_posts()) :
                while ($poradniki->have_posts()) : $poradniki->the_post();
                    ?>
                    <article class="content-mix-card content-mix-poradnik">
                        <div class="content-mix-badge">📄 Poradnik</div>
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="content-mix-image">
                                <?php the_post_thumbnail('medium'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="content-mix-body">
                            <h3 class="content-mix-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            <p class="content-mix-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
                            <div class="content-mix-meta">
                                <span class="reading-time">⏱️ <?php echo ceil(str_word_count(get_the_content()) / 200); ?> min czytania</span>
                            </div>
                        </div>
                    </article>
                    <?php
                endwhile;
                wp_reset_postdata();
            endif;

            // Get 1 comparison
            $comparison = new WP_Query([
                'post_type' => 'comparison',
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
            ]);

            if ($comparison->have_posts()) :
                while ($comparison->have_posts()) : $comparison->the_post();
                    ?>
                    <article class="content-mix-card content-mix-comparison">
                        <div class="content-mix-badge">🆚 Porównanie</div>
                        <div class="content-mix-body">
                            <h3 class="content-mix-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            <p class="content-mix-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
                            <div class="comparison-preview">
                                <span class="comparison-winner">✓ Zobacz zwycięzcę</span>
                            </div>
                        </div>
                    </article>
                    <?php
                endwhile;
                wp_reset_postdata();
            endif;

            // Get 1 ranking
            $ranking = new WP_Query([
                'post_type' => 'ranking',
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
            ]);

            if ($ranking->have_posts()) :
                while ($ranking->have_posts()) : $ranking->the_post();
                    ?>
                    <article class="content-mix-card content-mix-ranking">
                        <div class="content-mix-badge">🏆 Ranking</div>
                        <div class="content-mix-body">
                            <h3 class="content-mix-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            <p class="content-mix-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
                            <div class="ranking-preview">
                                <span class="ranking-count"><?php echo rand(15, 45); ?> firm w rankingu</span>
                            </div>
                        </div>
                    </article>
                    <?php
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </div>
</section>

<!-- Expert Strip -->
<section class="expert-strip-section">
    <div class="container">
        <div class="expert-strip">
            <div class="expert-strip-content">
                <h2>Szukasz sprawdzonego wykonawcy?</h2>
                <p>Skontaktuj się z najlepiej ocenianymi firmami w Twojej okolicy</p>
            </div>
            <div class="expert-strip-avatars">
                <?php
                // Get top 5 experts
                $experts = new WP_Query([
                    'post_type' => 'expert',
                    'posts_per_page' => 5,
                    'orderby' => 'meta_value_num',
                    'meta_key' => 'rating',
                    'order' => 'DESC',
                ]);

                if ($experts->have_posts()) :
                    echo '<div class="expert-avatars-stack">';
                    while ($experts->have_posts()) : $experts->the_post();
                        if (has_post_thumbnail()) {
                            echo '<div class="expert-avatar">';
                            the_post_thumbnail('thumbnail');
                            echo '</div>';
                        }
                    endwhile;
                    echo '</div>';
                    wp_reset_postdata();
                else :
                    // Fallback avatars
                    for ($i = 1; $i <= 5; $i++) {
                        echo '<div class="expert-avatar expert-avatar-placeholder">' . $i . '</div>';
                    }
                endif;
                ?>
            </div>
            <div class="expert-strip-cta">
                <a href="<?php echo esc_url(home_url('/ranking/')); ?>" class="btn-primary">
                    Zobacz wszystkich wykonawców →
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Category Deep Dive -->
<section class="category-section">
    <div class="container">
        <h2 class="section-title">Popularne kategorie</h2>

        <div class="category-grid">
            <?php
            $categories = [
                ['slug' => 'budowa-domu', 'icon' => '🏗️', 'name' => 'Budowa domu', 'count' => 156],
                ['slug' => 'remont', 'icon' => '🔨', 'name' => 'Remonty', 'count' => 243],
                ['slug' => 'instalacje', 'icon' => '⚡', 'name' => 'Instalacje', 'count' => 187],
                ['slug' => 'wykończenia', 'icon' => '🎨', 'name' => 'Wykończenia', 'count' => 132],
                ['slug' => 'ogrody', 'icon' => '🌳', 'name' => 'Ogrody i teren', 'count' => 98],
                ['slug' => 'dach', 'icon' => '🏠', 'name' => 'Dachy', 'count' => 76],
            ];

            foreach ($categories as $cat) :
                ?>
                <a href="<?php echo esc_url(home_url('/kategoria/' . $cat['slug'])); ?>" class="category-card">
                    <div class="category-icon"><?php echo $cat['icon']; ?></div>
                    <h3><?php echo esc_html($cat['name']); ?></h3>
                    <p class="category-count"><?php echo $cat['count']; ?> artykułów</p>
                    <span class="category-arrow">→</span>
                </a>
                <?php
            endforeach;
            ?>
        </div>
    </div>
</section>

<!-- Bottom CTA -->
<section class="hero bottom-cta-section">
    <div class="container text-center">
        <h2>Nie znalazłeś tego czego szukasz?</h2>
        <p>Skorzystaj z naszego kalkulatora lub zapytaj eksperta</p>
        <div class="bottom-cta-buttons">
            <a href="<?php echo esc_url(home_url('/kalkulator/')); ?>" class="btn-primary">
                🧮 Kalkulator kosztów
            </a>
            <a href="<?php echo esc_url(home_url('/ranking/')); ?>" class="btn-outline">
                👨‍🔧 Znajdź wykonawcę
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
