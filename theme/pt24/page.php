<?php
/**
 * Single Page Template
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<article class="pt24-static-content">
    <?php while (have_posts()) : the_post(); ?>
    <header class="pt24-static-hero">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <span class="pt24-static-eyebrow">Strona</span>
            <h1><?php the_title(); ?></h1>
            <p>Komplet informacji o ofercie PT24 oraz praktyczne wskazowki dla klientow i wykonawcow.</p>
        </div>
    </header>

    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="prose prose-slate max-w-none prose-headings:font-display prose-a:text-brand-start">
            <?php the_content(); ?>
        </div>

        <div class="pt24-static-cta mt-10">
            <h3>Przejdz do kolejnego kroku</h3>
            <p>Wyslij zapytanie lub przegladaj uslugi i profile firm w Twojej okolicy.</p>
            <div class="pt24-static-cta-actions">
                <a href="<?php echo esc_url(home_url('/dodaj-zlecenie/')); ?>">Dodaj zapytanie</a>
                <a class="is-secondary" href="<?php echo esc_url(home_url('/uslugi/')); ?>">Zobacz uslugi</a>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</article>

<?php get_footer(); ?>
