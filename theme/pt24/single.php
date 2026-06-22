<?php
/**
 * Single Post Template
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
        <div class="mt-4 flex items-center gap-4 text-sm text-slate-500">
            <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
            <span>&middot;</span>
            <span><?php echo esc_html(get_the_author()); ?></span>
        </div>
        <?php if (has_post_thumbnail()) : ?>
        <div class="mt-6">
            <?php the_post_thumbnail('large', ['class' => 'w-full rounded-2xl object-cover']); ?>
        </div>
        <?php endif; ?>
    </header>

    <div class="prose prose-slate max-w-none prose-headings:font-display prose-a:text-brand-start">
        <?php the_content(); ?>
    </div>

    <footer class="mt-10 border-t border-slate-200 pt-6">
        <div class="flex flex-wrap gap-2">
            <?php
            $tags = get_the_tags();
            if ($tags) :
                foreach ($tags as $tag) :
            ?>
            <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-brand-start/40 hover:text-brand-start">
                <?php echo esc_html($tag->name); ?>
            </a>
            <?php
                endforeach;
            endif;
            ?>
        </div>
    </footer>
    <?php endwhile; ?>
</article>

<?php get_footer(); ?>
