<?php
/**
 * Affiliate Copy Generator admin dashboard template.
 *
 * @var array $custom_templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1>Affiliate Copy Generator</h1>
	<p>Generate and manage conversion-focused affiliate copy templates.</p>

	<h2>Saved Custom Templates</h2>
	<?php if ( ! empty( $custom_templates ) && is_array( $custom_templates ) ) : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Template</th>
					<th>Preview</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $custom_templates as $name => $template ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $name ); ?></td>
						<td><code><?php echo esc_html( wp_trim_words( wp_json_encode( $template ), 20 ) ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p>No custom templates saved yet.</p>
	<?php endif; ?>
</div>
