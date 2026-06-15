<?php
/**
 * RPM Lead Fusion admin dashboard template.
 *
 * @var array $summary
 * @var array $top_posts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1>RPM Lead Fusion</h1>
	<p>Revenue and lead tracking dashboard for Poradnik.pro.</p>

	<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:24px 0;">
		<div class="card"><h3>Total Revenue</h3><p><?php echo esc_html( number_format( (float) ( $summary['total_revenue'] ?? 0 ), 2 ) ); ?></p></div>
		<div class="card"><h3>Total Leads</h3><p><?php echo esc_html( number_format( (int) ( $summary['total_leads'] ?? 0 ) ) ); ?></p></div>
		<div class="card"><h3>Total Views</h3><p><?php echo esc_html( number_format( (int) ( $summary['total_views'] ?? 0 ) ) ); ?></p></div>
		<div class="card"><h3>Overall RPM</h3><p><?php echo esc_html( number_format( (float) ( $summary['overall_rpm'] ?? 0 ), 2 ) ); ?></p></div>
	</div>

	<h2>Top Posts by RPM</h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th>Title</th>
				<th>RPM</th>
				<th>Revenue</th>
				<th>Views</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $top_posts ) ) : ?>
				<?php foreach ( $top_posts as $post ) : ?>
					<tr>
						<td><?php echo esc_html( $post['title'] ?? '' ); ?></td>
						<td><?php echo esc_html( number_format( (float) ( $post['rpm'] ?? 0 ), 2 ) ); ?></td>
						<td><?php echo esc_html( number_format( (float) ( $post['revenue'] ?? 0 ), 2 ) ); ?></td>
						<td><?php echo esc_html( number_format( (int) ( $post['views'] ?? 0 ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="4">No performance data available yet.</td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
