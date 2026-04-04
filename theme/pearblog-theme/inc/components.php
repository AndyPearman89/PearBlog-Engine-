<?php
/**
 * Component Registration and Helpers
 *
 * @package PearBlog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register hero component
 */
function pearblog_hero($args = array()) {
    get_template_part('template-parts/hero', null, $args);
}

/**
 * Register card component
 */
function pearblog_card($post = null, $args = array()) {
    global $post;

    if ($post) {
        setup_postdata($post);
    }

    get_template_part('template-parts/card', null, $args);

    if ($post) {
        wp_reset_postdata();
    }
}

/**
 * Register related posts component
 */
function pearblog_related_posts($args = array()) {
    get_template_part('template-parts/block-related', null, $args);
}

/**
 * Register FAQ component
 */
function pearblog_faq($args = array()) {
    get_template_part('template-parts/block-faq', null, $args);
}

/**
 * Register ads component
 */
function pearblog_ads($args = array()) {
    get_template_part('template-parts/block-ads', null, $args);
}

/**
 * Render cards grid
 */
function pearblog_render_cards($posts = null, $args = array()) {
    if (!$posts) {
        global $wp_query;
        $posts = $wp_query->posts;
    }

    if (empty($posts)) {
        return;
    }
    ?>
    <div class="pb-cards">
        <?php
        foreach ($posts as $post) {
            global $post;
            setup_postdata($post);
            pearblog_card($post, $args);
        }
        wp_reset_postdata();
        ?>
    </div>
    <?php
}

/**
 * Add Schema.org Article markup
 */
function pearblog_add_article_schema() {
    if (!is_singular('post')) {
        return;
    }

    global $post;

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title(),
        'description' => get_the_excerpt(),
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
        'author' => array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $post->post_author),
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => get_option('pearblog_logo_url', ''),
            ),
        ),
    );

    // Add main image if available with full metadata
    $thumbnail = get_the_post_thumbnail_url($post->ID, 'full');
    if ($thumbnail) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $image_meta = wp_get_attachment_metadata($thumbnail_id);
        $image_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);

        // Full image object for Schema.org
        $schema['image'] = array(
            '@type' => 'ImageObject',
            'url' => $thumbnail,
            'width' => $image_meta['width'] ?? 1200,
            'height' => $image_meta['height'] ?? 630,
        );

        // Add caption/description if available
        if (!empty($image_alt)) {
            $schema['image']['caption'] = $image_alt;
            $schema['image']['description'] = $image_alt;
        }
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}
add_action('wp_head', 'pearblog_add_article_schema');

/**
 * Add BreadcrumbList schema
 */
function pearblog_add_breadcrumb_schema() {
    if (is_front_page()) {
        return;
    }

    $items = array();
    $position = 1;

    // Home
    $items[] = array(
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => 'Home',
        'item' => home_url('/'),
    );

    // Category
    if (is_single()) {
        $categories = get_the_category();
        if (!empty($categories)) {
            $category = $categories[0];
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $category->name,
                'item' => get_category_link($category->term_id),
            );
        }
    }

    // Current page
    if (is_singular()) {
        $items[] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => get_the_title(),
            'item' => get_permalink(),
        );
    } elseif (is_category()) {
        $category = get_queried_object();
        $items[] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $category->name,
            'item' => get_category_link($category->term_id),
        );
    }

    if (!empty($items)) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        );

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }
}
add_action('wp_head', 'pearblog_add_breadcrumb_schema');

/**
 * Component shortcodes
 */
function pearblog_hero_shortcode($atts) {
    ob_start();
    pearblog_hero($atts);
    return ob_get_clean();
}
add_shortcode('pearblog_hero', 'pearblog_hero_shortcode');

function pearblog_faq_shortcode($atts, $content = null) {
    ob_start();
    pearblog_faq(array('faq_items' => $atts));
    return ob_get_clean();
}
add_shortcode('pearblog_faq', 'pearblog_faq_shortcode');

function pearblog_cta_shortcode($atts) {
    ob_start();
    pearblog_render_cta($atts);
    return ob_get_clean();
}
add_shortcode('pearblog_cta', 'pearblog_cta_shortcode');
