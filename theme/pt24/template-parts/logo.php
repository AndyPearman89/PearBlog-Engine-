<?php
/**
 * Logo SVG Template Part
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$size = isset($args['size']) ? (int) $args['size'] : 40;
$logo_rel_path = '/assets/images/pt24-logo.png';
$logo_abs_path = get_template_directory() . $logo_rel_path;
?>
<?php if ( file_exists( $logo_abs_path ) ) : ?>
    <img
        src="<?php echo esc_url( get_template_directory_uri() . $logo_rel_path ); ?>"
        width="<?php echo esc_attr($size); ?>"
        height="<?php echo esc_attr($size); ?>"
        alt=""
        aria-hidden="true"
        loading="eager"
        decoding="async"
        class="rounded-lg object-contain"
    />
<?php else : ?>
    <span
        aria-hidden="true"
        class="inline-flex items-center justify-center rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 text-white"
        style="width: <?php echo esc_attr($size); ?>px; height: <?php echo esc_attr($size); ?>px;"
    >PT</span>
<?php endif; ?>
