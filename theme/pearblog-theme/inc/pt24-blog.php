<?php
/**
 * PT24.PRO — blog archive routing.
 *
 * The shared theme's index.php is the poradnik.pro V3 conversion layout, which
 * would otherwise render on PT24's /blog/ posts index. Route the posts index to
 * the dedicated PT24 blog template instead. Loaded only on the PT24 install
 * (host-guarded require), so poradnik.pro / mucharski.pl are untouched.
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Use the PT24 blog archive template for the posts index (/blog/).
 *
 * @param string $template Resolved template path.
 * @return string
 */
function pt24_blog_template_include( $template ) {
	if ( is_home() && ! is_front_page() ) {
		$custom = locate_template( 'pt24-blog-archive.php' );
		if ( $custom ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'pt24_blog_template_include', 99 );
