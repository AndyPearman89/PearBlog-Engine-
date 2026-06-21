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

<article class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
    <?php while (have_posts()) : the_post(); ?>
    <header class="mb-8">
        <h1 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl"><?php the_title(); ?></h1>
    </header>

    <div class="prose prose-slate max-w-none prose-headings:font-display prose-a:text-brand-start">
        <?php the_content(); ?>
    </div>
    <?php endwhile; ?>
</article>

<?php get_footer(); ?>
