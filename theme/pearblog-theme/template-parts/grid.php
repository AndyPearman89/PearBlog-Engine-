<?php
/**
 * Template Part: Grid Layout
 *
 * Flexible grid for posts with view switcher (grid/list)
 *
 * @package PearBlog
 * @version 2.0.0
 */

$posts = $args['posts'] ?? null;
$view_mode = $args['view_mode'] ?? get_option('pearblog_default_view_mode', 'grid'); // grid or list
$columns = $args['columns'] ?? 3;
$show_featured = $args['show_featured'] ?? false;
$card_style = $args['card_style'] ?? 'default'; // default, minimal, magazine

if (!$posts) {
    global $wp_query;
    $posts = $wp_query->posts;
}

if (empty($posts)) {
    return;
}

$grid_classes = array(
    'pb-grid',
    'pb-grid-view-' . esc_attr($view_mode),
    'pb-grid-columns-' . esc_attr($columns),
    'pb-grid-style-' . esc_attr($card_style),
);
?>

<div class="pb-grid-wrapper">
    <?php if ($args['show_view_switcher'] ?? true) : ?>
        <div class="pb-grid-controls">
            <div class="pb-view-switcher">
                <button class="pb-view-btn pb-view-grid <?php echo $view_mode === 'grid' ? 'active' : ''; ?>"
                        data-view="grid"
                        aria-label="<?php esc_attr_e('Grid view', 'pearblog-theme'); ?>">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <rect x="2" y="2" width="6" height="6" rx="1"/>
                        <rect x="12" y="2" width="6" height="6" rx="1"/>
                        <rect x="2" y="12" width="6" height="6" rx="1"/>
                        <rect x="12" y="12" width="6" height="6" rx="1"/>
                    </svg>
                </button>
                <button class="pb-view-btn pb-view-list <?php echo $view_mode === 'list' ? 'active' : ''; ?>"
                        data-view="list"
                        aria-label="<?php esc_attr_e('List view', 'pearblog-theme'); ?>">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <rect x="2" y="3" width="16" height="2" rx="1"/>
                        <rect x="2" y="9" width="16" height="2" rx="1"/>
                        <rect x="2" y="15" width="16" height="2" rx="1"/>
                    </svg>
                </button>
            </div>

            <?php if ($args['show_filter'] ?? false) : ?>
                <div class="pb-grid-filter">
                    <select class="pb-filter-select" id="pb-category-filter">
                        <option value=""><?php _e('All Categories', 'pearblog-theme'); ?></option>
                        <?php
                        $categories = get_categories();
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="<?php echo esc_attr(implode(' ', $grid_classes)); ?>" id="pb-posts-grid">
        <?php
        $post_count = 0;
        foreach ($posts as $post) :
            global $post;
            setup_postdata($post);
            $post_count++;

            // Featured post (first post if show_featured is true)
            if ($show_featured && $post_count === 1) :
        ?>
                <div class="pb-grid-item pb-featured-post">
                    <article class="pb-card pb-card-featured">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="pb-card-image-wrapper">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('pearblog-hero', array('class' => 'pb-card-image', 'loading' => 'eager')); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="pb-card-content">
                            <div class="pb-card-meta">
                                <?php
                                $categories = get_the_category();
                                if (!empty($categories)) {
                                    echo '<span class="pb-card-category">' . esc_html($categories[0]->name) . '</span>';
                                }
                                ?>
                                <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                    <?php echo esc_html(get_the_date()); ?>
                                </time>
                            </div>

                            <h2 class="pb-card-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <p class="pb-card-excerpt">
                                <?php echo esc_html(get_the_excerpt()); ?>
                            </p>

                            <a href="<?php the_permalink(); ?>" class="pb-card-cta">
                                <?php _e('Read More', 'pearblog-theme'); ?>
                            </a>
                        </div>
                    </article>
                </div>
            <?php else : ?>
                <div class="pb-grid-item" data-category="<?php echo esc_attr(implode(',', wp_get_post_categories($post->ID))); ?>">
                    <?php
                    get_template_part('template-parts/card', null, array(
                        'card_style' => $card_style,
                        'view_mode' => $view_mode,
                    ));
                    ?>
                </div>
            <?php
            endif;
        endforeach;
        wp_reset_postdata();
        ?>
    </div>
</div>

<script>
// View switcher functionality
document.querySelectorAll('.pb-view-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const view = this.dataset.view;
        const grid = document.getElementById('pb-posts-grid');

        // Update active state
        document.querySelectorAll('.pb-view-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        // Update grid class
        grid.className = grid.className.replace(/pb-grid-view-\w+/, 'pb-grid-view-' + view);

        // Save preference
        localStorage.setItem('pb_view_mode', view);
    });
});

// Filter functionality
const filterSelect = document.getElementById('pb-category-filter');
if (filterSelect) {
    filterSelect.addEventListener('change', function() {
        const categoryId = this.value;
        const items = document.querySelectorAll('.pb-grid-item');

        items.forEach(function(item) {
            if (!categoryId || item.dataset.category.split(',').includes(categoryId)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

// Load saved view preference
const savedView = localStorage.getItem('pb_view_mode');
if (savedView) {
    const btn = document.querySelector('[data-view="' + savedView + '"]');
    if (btn) btn.click();
}
</script>
