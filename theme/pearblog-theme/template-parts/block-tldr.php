<?php
/**
 * Template part: TL;DR summary block.
 *
 * Renders a quick-summary box at the top of single posts.
 *
 * Expected $args:
 *   'tldr' => array of summary strings.
 *
 * @package PearBlog
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tldr_items = $args['tldr'] ?? array();

if ( empty( $tldr_items ) || ! is_array( $tldr_items ) ) {
	return;
}
?>
<div class="pb-tldr">
	<h2 class="pb-tldr-title"><?php esc_html_e( 'TL;DR - Quick Summary', 'pearblog-theme' ); ?></h2>
	<ul>
		<?php foreach ( $tldr_items as $item ) : ?>
			<li><?php echo esc_html( $item ); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
