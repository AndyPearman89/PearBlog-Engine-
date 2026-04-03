<?php
/**
 * Template Part: Related Posts
 *
 * Related articles section for internal linking
 *
 * @package PearBlog
 */

$post_id = $args['post_id'] ?? get_the_ID();
$limit = $args['limit'] ?? 3;
$title = $args['title'] ?? __('Related Articles', 'pearblog-theme');

$related_posts = pearblog_get_related_posts($post_id, $limit);

if (empty($related_posts)) {
    return;
}
?>

<section class="pb-related">
    <div class="pb-container">
        <h2 class="pb-related-title"><?php echo esc_html($title); ?></h2>

        <ul class="pb-related-list">
            <?php foreach ($related_posts as $related_post) : ?>
                <li class="pb-related-item">
                    <?php
                    $related_thumbnail = get_the_post_thumbnail($related_post->ID, 'pearblog-thumbnail');
                    if ($related_thumbnail) {
                        echo $related_thumbnail;
                    }
                    ?>
                    <h3>
                        <a href="<?php echo esc_url(get_permalink($related_post->ID)); ?>">
                            <?php echo esc_html(get_the_title($related_post->ID)); ?>
                        </a>
                    </h3>
                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt($related_post->ID), 15)); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
