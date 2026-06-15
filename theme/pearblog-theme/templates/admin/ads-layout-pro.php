<?php
/**
 * Ads Layout Pro admin dashboard template.
 *
 * @var string $strategy
 * @var string $ad_format
 * @var bool $enabled
 * @var array $performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1>Ads Layout Pro</h1>
	<p>Advanced ad placement dashboard for Poradnik.pro.</p>

	<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:24px 0;">
		<div class="card"><h3>Status</h3><p><?php echo esc_html( $enabled ? 'Enabled' : 'Disabled' ); ?></p></div>
		<div class="card"><h3>Strategy</h3><p><?php echo esc_html( ucfirst( (string) $strategy ) ); ?></p></div>
		<div class="card"><h3>Ad Format</h3><p><?php echo esc_html( ucfirst( (string) $ad_format ) ); ?></p></div>
	</div>

	<h2>Recent Performance</h2>
	<pre><?php echo esc_html( wp_json_encode( $performance, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
</div>
