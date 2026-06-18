<?php
/**
 * Template Name: Poradnik - Kalkulator
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
        <h1>Kalkulatory decyzji i kosztów</h1>
        <p>Policz koszty i porównaj scenariusze przed podjęciem decyzji.</p>
    </div>
</section>

<section class="calculator-section">
    <div class="container">
        <div class="grid">
            <?php
            $calculator_query = new WP_Query([
                'post_type'      => ['pearblog_calculator', 'calculator'],
                'post_status'    => 'publish',
                'posts_per_page' => 9,
            ]);

            if ($calculator_query->have_posts()) :
                while ($calculator_query->have_posts()) :
                    $calculator_query->the_post();
                    ?>
                    <article class="card">
                        <div class="card-icon">🧮</div>
                        <h3><?php the_title(); ?></h3>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 18)); ?></p>
                        <a class="btn-primary" href="<?php the_permalink(); ?>">Uruchom kalkulator</a>
                    </article>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                ?>
                <article class="card">
                    <div class="card-icon">🧮</div>
                    <h3>Kalkulatory w przygotowaniu</h3>
                    <p>Kalkulatory są właśnie uzupełniane. Sprawdź porównania lub rankingi, aby przyspieszyć decyzję.</p>
                    <a class="btn-primary" href="<?php echo esc_url(home_url('/porownania/')); ?>">Przejdź do porównań</a>
                </article>
                <?php
            endif;
            ?>
        </div>
    </div>
</section>

<?php
get_footer();
