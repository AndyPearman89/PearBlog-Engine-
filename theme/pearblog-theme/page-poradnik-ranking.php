<?php
/**
 * Template Name: Poradnik - Ranking
 *
 * @package PearBlog
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<section class="hero">
    <div class="container">
        <h1>Rankingi fachowców i usług</h1>
        <p>Wybieraj na podstawie ocen, opinii i jakości wykonania.</p>
    </div>
</section>

<section class="ranking-section">
    <div class="container">
        <div class="ranking-list">
            <?php
            $ranking_query = new WP_Query([
                'post_type'      => ['pearblog_ranking', 'ranking'],
                'post_status'    => 'publish',
                'posts_per_page' => 10,
            ]);

            if ($ranking_query->have_posts()) :
                $position = 1;
                while ($ranking_query->have_posts()) :
                    $ranking_query->the_post();
                    ?>
                    <article class="ranking-card">
                        <div class="ranking-position"><?php echo esc_html((string) $position); ?></div>
                        <div class="ranking-content">
                            <h3><?php the_title(); ?></h3>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 22)); ?></p>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="ranking-cta">Sprawdź ranking</a>
                    </article>
                    <?php
                    $position++;
                endwhile;
                wp_reset_postdata();
            else :
                ?>
                <article class="ranking-card">
                    <div class="ranking-position">1</div>
                    <div class="ranking-content">
                        <h3>Rankingi w przygotowaniu</h3>
                        <p>Aktualizujemy listę rankingów. W międzyczasie możesz skorzystać z kalkulatora kosztów.</p>
                    </div>
                    <a href="<?php echo esc_url(home_url('/kalkulator/')); ?>" class="ranking-cta">Otwórz kalkulator</a>
                </article>
                <?php
            endif;
            ?>
        </div>
    </div>
</section>

<?php
get_footer();
