<?php
/**
 * Template Part: Table of Contents (TOC)
 *
 * Auto-generated from H2/H3 headings, sticky sidebar
 *
 * @package PearBlog
 * @version 2.0.0
 */

$post_id = $args['post_id'] ?? get_the_ID();
$content = get_post_field('post_content', $post_id);
$headings = pearblog_extract_headings($content);

if (empty($headings)) {
    return;
}

$title = $args['title'] ?? __('Table of Contents', 'pearblog-theme');
$sticky = $args['sticky'] ?? true;
?>

<nav class="pb-toc <?php echo $sticky ? 'pb-toc-sticky' : ''; ?>" aria-label="<?php echo esc_attr($title); ?>">
    <div class="pb-toc-header">
        <h2 class="pb-toc-title"><?php echo esc_html($title); ?></h2>
        <button class="pb-toc-toggle" aria-label="<?php esc_attr_e('Toggle table of contents', 'pearblog-theme'); ?>">
            <span class="pb-toc-icon">▼</span>
        </button>
    </div>

    <ol class="pb-toc-list">
        <?php foreach ($headings as $heading) : ?>
            <li class="pb-toc-item pb-toc-level-<?php echo esc_attr($heading['level']); ?>">
                <a href="#<?php echo esc_attr($heading['id']); ?>" class="pb-toc-link">
                    <?php echo esc_html($heading['text']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ol>

    <!-- Progress bar -->
    <div class="pb-toc-progress">
        <div class="pb-toc-progress-bar" id="pb-reading-progress"></div>
    </div>
</nav>

<?php
/**
 * Extract headings from content
 */
function pearblog_extract_headings($content) {
    if (empty($content)) {
        return array();
    }

    $headings = array();

    // Match H2 and H3 tags
    preg_match_all('/<h([23])[^>]*>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $level = $match[1];
        $text = strip_tags($match[2]);
        $id = sanitize_title($text);

        $headings[] = array(
            'level' => $level,
            'text' => $text,
            'id' => $id,
        );
    }

    return $headings;
}

/**
 * Add IDs to headings in content
 */
function pearblog_add_heading_ids($content) {
    if (is_admin() || is_feed() || !is_singular()) {
        return $content;
    }

    $config = pb_get_site_config();
    if (!$config['toc_enabled']) {
        return $content;
    }

    // Add IDs to H2 and H3 tags
    $content = preg_replace_callback('/<h([23])[^>]*>(.*?)<\/h\1>/is', function($matches) {
        $level = $matches[1];
        $text = strip_tags($matches[2]);
        $id = sanitize_title($text);

        return '<h' . $level . ' id="' . esc_attr($id) . '">' . $matches[2] . '</h' . $level . '>';
    }, $content);

    return $content;
}
add_filter('the_content', 'pearblog_add_heading_ids', 10);
?>
