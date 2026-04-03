<?php
/**
 * Template Part: Card
 *
 * Article card with title + excerpt + CTA
 *
 * @package PearBlog
 */

// Expect $post to be passed in or use global
global $post;

if (!isset($post) || !$post) {
    return;
}

$post_id = $post->ID;
$title = get_the_title($post_id);
$excerpt = get_the_excerpt($post_id);
$permalink = get_permalink($post_id);
$thumbnail = get_the_post_thumbnail_url($post_id, 'pearblog-card');
$date = get_the_date('', $post_id);
$author = get_the_author_meta('display_name', $post->post_author);

// Allow custom CTA text
$cta_text = $args['cta_text'] ?? __('Read More', 'pearblog-theme');
?>

<article class="pb-card">
    <?php if ($thumbnail) : ?>
        <img src="<?php echo esc_url($thumbnail); ?>"
             alt="<?php echo esc_attr($title); ?>"
             class="pb-card-image"
             loading="lazy">
    <?php endif; ?>

    <div class="pb-card-content">
        <div class="pb-card-meta">
            <time datetime="<?php echo esc_attr(get_the_date('c', $post_id)); ?>">
                <?php echo esc_html($date); ?>
            </time>
            <?php if ($author) : ?>
                <span> &middot; <?php echo esc_html($author); ?></span>
            <?php endif; ?>
        </div>

        <h3 class="pb-card-title">
            <a href="<?php echo esc_url($permalink); ?>">
                <?php echo esc_html($title); ?>
            </a>
        </h3>

        <?php if ($excerpt) : ?>
            <p class="pb-card-excerpt">
                <?php echo esc_html($excerpt); ?>
            </p>
        <?php endif; ?>

        <a href="<?php echo esc_url($permalink); ?>" class="pb-card-cta">
            <?php echo esc_html($cta_text); ?>
        </a>
    </div>
</article>
