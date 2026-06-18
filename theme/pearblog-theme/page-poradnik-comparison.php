<?php
/**
 * Template Name: Poradnik - Porównania
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
        <h1>Porównania usług i rozwiązań</h1>
        <p>Sprawdź najważniejsze różnice i podejmij lepszą decyzję.</p>
    </div>
</section>

<section class="container">
    <div class="grid">
        <?php
        $excerpt_word_count = 20;
        $comparison_query = new WP_Query([
            'post_type'      => ['pearblog_comparison', 'comparison'],
            'post_status'    => 'publish',
            'posts_per_page' => 12,
        ]);

        if ($comparison_query->have_posts()) :
            while ($comparison_query->have_posts()) :
                $comparison_query->the_post();
                ?>
                <article class="card">
                    <div class="card-icon">⚖️</div>
                    <h3><?php the_title(); ?></h3>
                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), $excerpt_word_count)); ?></p>
                    <a class="card-link" href="<?php the_permalink(); ?>">Zobacz porównanie →</a>
                </article>
                <?php
            endwhile;
            wp_reset_postdata();
        else :
            ?>
            <article class="card">
                <div class="card-icon">⚖️</div>
                <h3>Porównania w przygotowaniu</h3>
                <p>Aktualnie rozbudowujemy bazę porównań. Wróć za chwilę lub przejdź do rankingów.</p>
                <a class="card-link" href="<?php echo esc_url(home_url('/ranking/')); ?>">Przejdź do rankingów →</a>
            </article>
            <?php
        endif;
        ?>
    </div>
</section>

<?php
get_footer();
