<?php
/**
 * PearBlog PRO Gutenberg Blocks
 *
 * Server-side registration of custom Gutenberg blocks.
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Gutenberg blocks and enqueue editor assets.
 */
function pearblog_register_blocks() {
	// Only register if block editor is available.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	// Enqueue block editor assets.
	add_action( 'enqueue_block_editor_assets', 'pearblog_enqueue_block_editor_assets' );

	// Register server-side rendered blocks.
	register_block_type( 'pearblog/hero', array(
		'attributes'      => array(
			'title'     => array( 'type' => 'string', 'default' => '' ),
			'subtitle'  => array( 'type' => 'string', 'default' => '' ),
			'styleType' => array( 'type' => 'string', 'default' => 'gradient' ),
			'imageUrl'  => array( 'type' => 'string', 'default' => '' ),
			'videoUrl'  => array( 'type' => 'string', 'default' => '' ),
			'ctaText'   => array( 'type' => 'string', 'default' => '' ),
			'ctaUrl'    => array( 'type' => 'string', 'default' => '' ),
		),
		'render_callback' => 'pearblog_render_hero_block',
		'editor_script'   => 'pearblog-blocks-editor',
		'editor_style'    => 'pearblog-blocks-editor-style',
	) );

	register_block_type( 'pearblog/faq', array(
		'attributes'      => array(
			'title' => array( 'type' => 'string', 'default' => 'Frequently Asked Questions' ),
			'items' => array( 'type' => 'string', 'default' => '[]' ),
		),
		'render_callback' => 'pearblog_render_faq_block',
		'editor_script'   => 'pearblog-blocks-editor',
	) );

	register_block_type( 'pearblog/cta', array(
		'attributes'      => array(
			'title'      => array( 'type' => 'string', 'default' => 'Ready to Get Started?' ),
			'subtitle'   => array( 'type' => 'string', 'default' => '' ),
			'buttonText' => array( 'type' => 'string', 'default' => 'Learn More' ),
			'buttonUrl'  => array( 'type' => 'string', 'default' => '' ),
			'style'      => array( 'type' => 'string', 'default' => 'gradient' ),
			'type'       => array( 'type' => 'string', 'default' => 'default' ),
		),
		'render_callback' => 'pearblog_render_cta_block',
		'editor_script'   => 'pearblog-blocks-editor',
	) );

	register_block_type( 'pearblog/related-posts', array(
		'attributes'      => array(
			'title' => array( 'type' => 'string', 'default' => 'Related Articles' ),
			'count' => array( 'type' => 'number', 'default' => 3 ),
		),
		'render_callback' => 'pearblog_render_related_posts_block',
		'editor_script'   => 'pearblog-blocks-editor',
	) );

	register_block_type( 'pearblog/toc', array(
		'attributes'      => array(
			'title'  => array( 'type' => 'string', 'default' => 'Table of Contents' ),
			'sticky' => array( 'type' => 'boolean', 'default' => true ),
		),
		'render_callback' => 'pearblog_render_toc_block',
		'editor_script'   => 'pearblog-blocks-editor',
	) );

	register_block_type( 'pearblog/ad-slot', array(
		'attributes'      => array(
			'position' => array( 'type' => 'string', 'default' => 'content' ),
			'label'    => array( 'type' => 'string', 'default' => 'Advertisement' ),
		),
		'render_callback' => 'pearblog_render_ad_slot_block',
		'editor_script'   => 'pearblog-blocks-editor',
	) );
}
add_action( 'init', 'pearblog_register_blocks' );

/**
 * Enqueue block editor scripts and styles.
 */
function pearblog_enqueue_block_editor_assets() {
	wp_enqueue_script(
		'pearblog-blocks-editor',
		PEARBLOG_URI . '/assets/js/blocks-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		PEARBLOG_VERSION,
		true
	);

	wp_enqueue_style(
		'pearblog-blocks-editor-style',
		PEARBLOG_URI . '/assets/css/blocks-editor.css',
		array( 'wp-edit-blocks' ),
		PEARBLOG_VERSION
	);
}

// ---------------------------------------------------------------------------
// Block render callbacks
// ---------------------------------------------------------------------------

/**
 * Render the Hero block on the front end.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function pearblog_render_hero_block( $attributes ) {
	ob_start();
	pearblog_hero( array(
		'title'      => $attributes['title'],
		'subtitle'   => $attributes['subtitle'],
		'style_type' => $attributes['styleType'],
		'image'      => $attributes['imageUrl'],
		'video'      => $attributes['videoUrl'],
		'cta_text'   => $attributes['ctaText'],
		'cta_url'    => $attributes['ctaUrl'],
	) );
	return ob_get_clean();
}

/**
 * Render the FAQ block on the front end.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function pearblog_render_faq_block( $attributes ) {
	$items = json_decode( $attributes['items'], true );

	if ( ! is_array( $items ) || empty( $items ) ) {
		return '';
	}

	ob_start();
	pearblog_faq( array(
		'title'     => $attributes['title'],
		'faq_items' => $items,
	) );
	return ob_get_clean();
}

/**
 * Render the CTA block on the front end.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function pearblog_render_cta_block( $attributes ) {
	ob_start();
	get_template_part( 'template-parts/block-cta', null, array(
		'type'        => $attributes['type'],
		'title'       => $attributes['title'],
		'subtitle'    => $attributes['subtitle'],
		'button_text' => $attributes['buttonText'],
		'button_url'  => $attributes['buttonUrl'],
		'style'       => $attributes['style'],
	) );
	return ob_get_clean();
}

/**
 * Render the Related Posts block on the front end.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function pearblog_render_related_posts_block( $attributes ) {
	ob_start();
	pearblog_related_posts( array(
		'title' => $attributes['title'],
		'limit' => $attributes['count'],
	) );
	return ob_get_clean();
}

/**
 * Render the Table of Contents block on the front end.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function pearblog_render_toc_block( $attributes ) {
	ob_start();
	get_template_part( 'template-parts/block-toc', null, array(
		'title'  => $attributes['title'],
		'sticky' => $attributes['sticky'],
	) );
	return ob_get_clean();
}

/**
 * Render the Ad Slot block on the front end.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function pearblog_render_ad_slot_block( $attributes ) {
	ob_start();
	pearblog_ads( array(
		'position' => $attributes['position'],
		'label'    => $attributes['label'],
	) );
	return ob_get_clean();
}

// ---------------------------------------------------------------------------
// Block patterns
// ---------------------------------------------------------------------------

/**
 * Register block patterns for page builder integration.
 */
function pearblog_register_block_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	register_block_pattern_category( 'pearblog', array(
		'label' => __( 'PearBlog PRO', 'pearblog-theme' ),
	) );

	// Landing page pattern.
	register_block_pattern( 'pearblog/landing-page', array(
		'title'       => __( 'Landing Page', 'pearblog-theme' ),
		'description' => __( 'A full landing page with hero, features, and CTA.', 'pearblog-theme' ),
		'categories'  => array( 'pearblog' ),
		'content'     => '<!-- wp:pearblog/hero {"title":"Welcome to Our Blog","subtitle":"Discover amazing content","ctaText":"Get Started","ctaUrl":"#"} /-->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3>Feature One</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Describe your first key feature or benefit here.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3>Feature Two</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Describe your second key feature or benefit here.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3>Feature Three</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Describe your third key feature or benefit here.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- wp:pearblog/cta {"title":"Ready to Get Started?","subtitle":"Join thousands of happy readers.","buttonText":"Subscribe Now","buttonUrl":"#","style":"gradient"} /-->',
	) );

	// Blog post with SEO layout pattern.
	register_block_pattern( 'pearblog/seo-article', array(
		'title'       => __( 'SEO Article Layout', 'pearblog-theme' ),
		'description' => __( 'Optimized article layout with TOC, ads, and FAQ.', 'pearblog-theme' ),
		'categories'  => array( 'pearblog' ),
		'content'     => '<!-- wp:pearblog/toc {"title":"Table of Contents"} /-->
<!-- wp:pearblog/ad-slot {"position":"top"} /-->
<!-- wp:heading -->
<h2>Introduction</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Your introduction content goes here.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2>Main Section</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Your main content goes here.</p>
<!-- /wp:paragraph -->
<!-- wp:pearblog/ad-slot {"position":"middle"} /-->
<!-- wp:heading -->
<h2>Conclusion</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Your conclusion goes here.</p>
<!-- /wp:paragraph -->
<!-- wp:pearblog/faq {"title":"Frequently Asked Questions","items":"[{\"question\":\"What is this about?\",\"answer\":\"This article covers...\"}]"} /-->
<!-- wp:pearblog/related-posts {"title":"You May Also Like","count":3} /-->',
	) );

	// Newsletter signup pattern.
	register_block_pattern( 'pearblog/newsletter-signup', array(
		'title'       => __( 'Newsletter Signup', 'pearblog-theme' ),
		'description' => __( 'Email capture CTA with lead form.', 'pearblog-theme' ),
		'categories'  => array( 'pearblog' ),
		'content'     => '<!-- wp:pearblog/cta {"title":"Stay Updated","subtitle":"Get the latest articles delivered to your inbox.","buttonText":"Subscribe","buttonUrl":"#","style":"gradient","type":"lead"} /-->',
	) );
}
add_action( 'init', 'pearblog_register_block_patterns' );

/**
 * Add PearBlog block category.
 *
 * @param array                    $categories Existing categories.
 * @param WP_Block_Editor_Context  $context    Block editor context.
 * @return array Modified categories.
 */
function pearblog_block_categories( $categories, $context ) {
	return array_merge(
		array(
			array(
				'slug'  => 'pearblog',
				'title' => __( 'PearBlog PRO', 'pearblog-theme' ),
				'icon'  => 'edit',
			),
		),
		$categories
	);
}
add_filter( 'block_categories_all', 'pearblog_block_categories', 10, 2 );
