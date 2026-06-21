<?php
/**
 * Main Index Template
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <h1 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-8">
        <?php
        if (is_search()) {
            printf(esc_html__('Wyniki wyszukiwania: %s', 'pt24'), get_search_query());
        } elseif (is_archive()) {
            the_archive_title();
        } else {
            esc_html_e('Blog', 'pt24');
        }
        ?>
    </h1>

    <?php if (have_posts()) : ?>
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php while (have_posts()) : the_post(); ?>
        <article class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-soft">
            <?php if (has_post_thumbnail()) : ?>
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('medium_large', ['class' => 'w-full rounded-xl mb-4 object-cover aspect-video']); ?>
            </a>
            <?php endif; ?>
            <h2 class="text-lg font-bold text-slate-900 mb-2">
                <a href="<?php the_permalink(); ?>" class="hover:text-brand-start transition"><?php the_title(); ?></a>
            </h2>
            <p class="text-sm text-slate-500 line-clamp-3"><?php echo esc_html(get_the_excerpt()); ?></p>
            <div class="mt-4 flex items-center gap-2 text-xs text-slate-400">
                <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
            </div>
        </article>
        <?php endwhile; ?>
    </div>

    <nav class="mt-10 flex justify-center">
        <?php
        the_posts_pagination([
            'mid_size'  => 2,
            'prev_text' => '← Poprzednie',
            'next_text' => 'Następne →',
        ]);
        ?>
    </nav>
    <?php else : ?>
    <div class="rounded-2xl bg-white p-12 text-center shadow-card ring-1 ring-slate-200/60">
        <p class="text-slate-500"><?php esc_html_e('Nie znaleziono postów.', 'pt24'); ?></p>
    </div>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
