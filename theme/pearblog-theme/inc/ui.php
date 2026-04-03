<?php
/**
 * UI Helper Functions
 *
 * @package PearBlog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get site logo
 */
function pearblog_get_logo() {
    $logo_url = get_option('pearblog_logo_url', '');

    if (empty($logo_url)) {
        // Fallback to site name
        return '<span class="pb-logo">' . esc_html(get_bloginfo('name')) . '</span>';
    }

    return '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="pb-logo-image">';
}

/**
 * Render navigation menu
 */
function pearblog_render_nav_menu($location = 'primary') {
    if (has_nav_menu($location)) {
        wp_nav_menu(array(
            'theme_location' => $location,
            'menu_class'     => 'pb-menu',
            'container'      => false,
            'depth'          => 2,
        ));
    }
}

/**
 * Get breadcrumbs
 */
function pearblog_get_breadcrumbs() {
    if (is_front_page()) {
        return '';
    }

    $breadcrumbs = array();
    $breadcrumbs[] = '<a href="' . esc_url(home_url('/')) . '">' . __('Home', 'pearblog-theme') . '</a>';

    if (is_category()) {
        $category = get_queried_object();
        $breadcrumbs[] = '<span>' . esc_html($category->name) . '</span>';
    } elseif (is_single()) {
        $categories = get_the_category();
        if (!empty($categories)) {
            $category = $categories[0];
            $breadcrumbs[] = '<a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a>';
        }
        $breadcrumbs[] = '<span>' . esc_html(get_the_title()) . '</span>';
    } elseif (is_page()) {
        $breadcrumbs[] = '<span>' . esc_html(get_the_title()) . '</span>';
    }

    return '<nav class="pb-breadcrumbs" aria-label="Breadcrumb">' . implode(' &raquo; ', $breadcrumbs) . '</nav>';
}

/**
 * Get social share buttons
 */
function pearblog_get_social_share($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $permalink = get_permalink($post_id);
    $title = get_the_title($post_id);

    $share_links = array(
        'twitter' => 'https://twitter.com/intent/tweet?url=' . urlencode($permalink) . '&text=' . urlencode($title),
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($permalink),
        'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($permalink),
    );

    ob_start();
    ?>
    <div class="pb-social-share">
        <span><?php _e('Share:', 'pearblog-theme'); ?></span>
        <?php foreach ($share_links as $network => $url) : ?>
            <a href="<?php echo esc_url($url); ?>"
               target="_blank"
               rel="noopener noreferrer"
               class="pb-share-<?php echo esc_attr($network); ?>"
               aria-label="<?php echo esc_attr(sprintf(__('Share on %s', 'pearblog-theme'), ucfirst($network))); ?>">
                <?php echo esc_html(ucfirst($network)); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render pagination
 */
function pearblog_render_pagination() {
    global $wp_query;

    if ($wp_query->max_num_pages <= 1) {
        return;
    }

    $paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;

    echo '<nav class="pb-pagination" aria-label="' . esc_attr__('Pagination', 'pearblog-theme') . '">';

    echo paginate_links(array(
        'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
        'format'    => '?paged=%#%',
        'current'   => max(1, $paged),
        'total'     => $wp_query->max_num_pages,
        'prev_text' => __('&laquo; Previous', 'pearblog-theme'),
        'next_text' => __('Next &raquo;', 'pearblog-theme'),
        'type'      => 'list',
    ));

    echo '</nav>';
}

/**
 * Get reading time
 */
function pearblog_get_reading_time($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // 200 words per minute

    return sprintf(_n('%d min read', '%d min read', $reading_time, 'pearblog-theme'), $reading_time);
}

/**
 * Get post meta info
 */
function pearblog_get_post_meta($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $date = get_the_date('', $post_id);
    $author = get_the_author_meta('display_name', get_post_field('post_author', $post_id));
    $reading_time = pearblog_get_reading_time($post_id);

    ob_start();
    ?>
    <div class="pb-post-meta">
        <time datetime="<?php echo esc_attr(get_the_date('c', $post_id)); ?>">
            <?php echo esc_html($date); ?>
        </time>
        <span class="pb-meta-separator">&middot;</span>
        <span class="pb-author"><?php echo esc_html($author); ?></span>
        <span class="pb-meta-separator">&middot;</span>
        <span class="pb-reading-time"><?php echo esc_html($reading_time); ?></span>
    </div>
    <?php
    return ob_get_clean();
}
