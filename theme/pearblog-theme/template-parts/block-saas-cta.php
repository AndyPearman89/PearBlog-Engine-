<?php
/**
 * Template Part: SaaS CTA Block
 *
 * Displays a SaaS product recommendation box with affiliate/referral link.
 * Used by the theme when rendering SaaS CTA blocks on the frontend.
 *
 * @package PearBlog
 * @version 3.0.0
 */

$product_name = $args['product_name'] ?? '';
$product_url  = $args['product_url'] ?? '';
$description  = $args['description'] ?? '';
$cta_text     = $args['cta_text'] ?? '';
$position     = $args['position'] ?? 'content'; // content, sidebar, footer

if ( empty( $product_name ) || empty( $product_url ) ) {
	return;
}

if ( empty( $cta_text ) ) {
	/* translators: %s: SaaS product name */
	$cta_text = sprintf( __( 'Try %s →', 'pearblog-theme' ), $product_name );
}

if ( empty( $description ) ) {
	/* translators: %s: SaaS product name */
	$description = sprintf( __( 'Recommended tool: %s', 'pearblog-theme' ), $product_name );
}

$box_classes = array(
	'pb-saas-cta',
	'pb-saas-cta-position-' . esc_attr( $position ),
);
?>

<div class="<?php echo esc_attr( implode( ' ', $box_classes ) ); ?>">
	<div class="pb-saas-cta-container">
		<div class="pb-saas-cta-content">
			<p class="pb-saas-cta-title"><?php echo esc_html( $description ); ?></p>
			<a href="<?php echo esc_url( $product_url ); ?>"
			   class="pb-saas-cta-button"
			   target="_blank"
			   rel="noopener sponsored"
			   data-saas-product="<?php echo esc_attr( $product_name ); ?>"
			   data-saas-position="<?php echo esc_attr( $position ); ?>">
				<?php echo esc_html( $cta_text ); ?>
			</a>
		</div>
	</div>
</div>

<!-- SaaS CTA Click Tracking -->
<script>
(function() {
	if (window._pbSaasCTATracked) return;
	window._pbSaasCTATracked = true;
	document.addEventListener('click', function(e) {
		var link = e.target.closest('[data-saas-product]');
		if (!link) return;
		if (typeof gtag !== 'undefined') {
			gtag('event', 'saas_cta_click', {
				'event_category': 'monetization',
				'event_label': link.dataset.saasProduct,
				'saas_position': link.dataset.saasPosition,
				'post_id': <?php echo (int) get_the_ID(); ?>
			});
		}
	});
})();
</script>
