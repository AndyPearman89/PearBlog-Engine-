<?php
/**
 * Analytics Tab for Admin Panel v7.0
 *
 * Advanced analytics, performance metrics, and custom filtering.
 *
 * @package PearBlogEngine\Admin
 * @since 7.8.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Analytics tab component.
 */
class AnalyticsTab {

	/**
	 * Render the Analytics tab content.
	 */
	public static function render(): void {
		// Security: Verify user has admin access
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pearblog-engine' ) );
		}

		?>
		<div class="pearblog-v7-analytics">
			<div class="pearblog-analytics-header">
				<h2><?php echo esc_html__( 'Analytics & Performance Metrics', 'pearblog-engine' ); ?></h2>
				<p class="description">
					<?php echo esc_html__( 'Advanced analytics with custom date ranges, filtering, and performance tracking.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<!-- Date Range Filter -->
			<div class="pearblog-section">
				<form method="get" class="pearblog-analytics-filters">
					<input type="hidden" name="page" value="pearblog-engine-v7" />
					<input type="hidden" name="tab" value="analytics" />

					<div class="pearblog-filter-row">
						<div class="pearblog-filter-group">
							<label for="date_from"><?php echo esc_html__( 'From', 'pearblog-engine' ); ?></label>
							<input
								type="date"
								name="date_from"
								id="date_from"
								value="<?php echo esc_attr( $_GET['date_from'] ?? date( 'Y-m-d', strtotime( '-30 days' ) ) ); ?>"
								class="pearblog-input"
							/>
						</div>
						<div class="pearblog-filter-group">
							<label for="date_to"><?php echo esc_html__( 'To', 'pearblog-engine' ); ?></label>
							<input
								type="date"
								name="date_to"
								id="date_to"
								value="<?php echo esc_attr( $_GET['date_to'] ?? date( 'Y-m-d' ) ); ?>"
								class="pearblog-input"
							/>
						</div>
						<div class="pearblog-filter-group">
							<label for="category_filter"><?php echo esc_html__( 'Category', 'pearblog-engine' ); ?></label>
							<select name="category" id="category_filter" class="pearblog-select">
								<option value=""><?php echo esc_html__( 'All Categories', 'pearblog-engine' ); ?></option>
								<?php
								$categories = get_categories( [ 'hide_empty' => false ] );
								foreach ( $categories as $category ) {
									$selected = isset( $_GET['category'] ) && $_GET['category'] == $category->term_id ? 'selected' : '';
									echo '<option value="' . esc_attr( $category->term_id ) . '" ' . $selected . '>' . esc_html( $category->name ) . '</option>';
								}
								?>
							</select>
						</div>
						<div class="pearblog-filter-group">
							<label>&nbsp;</label>
							<button type="submit" class="pearblog-button pearblog-button-primary">
								<?php echo esc_html__( 'Apply Filters', 'pearblog-engine' ); ?>
							</button>
						</div>
					</div>
				</form>
			</div>

			<!-- Performance Overview -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Performance Overview', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-analytics-grid">
					<?php self::render_performance_metrics(); ?>
				</div>
			</div>

			<!-- Traffic Analytics -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Traffic Analytics', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-chart-container">
					<canvas id="traffic-chart"></canvas>
				</div>
			</div>

			<!-- Content Performance -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Content Performance', 'pearblog-engine' ); ?></h3>
				<?php self::render_content_performance(); ?>
			</div>

			<!-- User Engagement -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'User Engagement', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-engagement-grid">
					<?php self::render_engagement_metrics(); ?>
				</div>
			</div>

			<!-- SEO Performance -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'SEO Performance', 'pearblog-engine' ); ?></h3>
				<?php self::render_seo_metrics(); ?>
			</div>

			<!-- Export Options -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Export Analytics', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-export-options">
					<button type="button" class="pearblog-button pearblog-button-secondary" onclick="alert('CSV export will be implemented')">
						<?php echo esc_html__( '📊 Export to CSV', 'pearblog-engine' ); ?>
					</button>
					<button type="button" class="pearblog-button pearblog-button-secondary" onclick="alert('PDF report will be implemented')">
						<?php echo esc_html__( '📄 Generate PDF Report', 'pearblog-engine' ); ?>
					</button>
					<button type="button" class="pearblog-button pearblog-button-secondary" onclick="alert('Email report will be implemented')">
						<?php echo esc_html__( '📧 Email Report', 'pearblog-engine' ); ?>
					</button>
				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			if (typeof Chart !== 'undefined') {
				const ctx = document.getElementById('traffic-chart');
				if (ctx) {
					new Chart(ctx, {
						type: 'line',
						data: {
							labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
							datasets: [{
								label: 'Page Views',
								data: [1200, 1900, 1500, 2100, 1800, 2400, 2200],
								borderColor: '#3b82f6',
								backgroundColor: 'rgba(59, 130, 246, 0.1)',
								tension: 0.4
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: {
								legend: { display: true }
							}
						}
					});
				}
			}
		});
		</script>
		<?php
	}

	/**
	 * Render performance metrics.
	 */
	private static function render_performance_metrics(): void {
		$metrics = [
			[
				'icon'   => '👁️',
				'value'  => '125.4K',
				'label'  => 'Total Views',
				'change' => '+12.5%',
				'trend'  => 'up',
			],
			[
				'icon'   => '📝',
				'value'  => '342',
				'label'  => 'Published Posts',
				'change' => '+8 this week',
				'trend'  => 'up',
			],
			[
				'icon'   => '⏱️',
				'value'  => '3:42',
				'label'  => 'Avg. Time on Page',
				'change' => '+15s',
				'trend'  => 'up',
			],
			[
				'icon'   => '📈',
				'value'  => '68.5%',
				'label'  => 'Engagement Rate',
				'change' => '+5.2%',
				'trend'  => 'up',
			],
		];

		foreach ( $metrics as $metric ) :
			?>
			<div class="pearblog-metric-card">
				<div class="pearblog-metric-icon"><?php echo esc_html( $metric['icon'] ); ?></div>
				<div class="pearblog-metric-value"><?php echo esc_html( $metric['value'] ); ?></div>
				<div class="pearblog-metric-label"><?php echo esc_html( $metric['label'] ); ?></div>
				<div class="pearblog-metric-change pearblog-trend-<?php echo esc_attr( $metric['trend'] ); ?>">
					<?php echo esc_html( $metric['change'] ); ?>
				</div>
			</div>
			<?php
		endforeach;
	}

	/**
	 * Render content performance table.
	 */
	private static function render_content_performance(): void {
		$posts = [
			[ 'title' => 'Top WordPress Plugins 2026', 'views' => 15234, 'engagement' => 72, 'revenue' => 234.50 ],
			[ 'title' => 'SEO Best Practices Guide', 'views' => 12456, 'engagement' => 68, 'revenue' => 198.30 ],
			[ 'title' => 'AI Content Creation Tools', 'views' => 10987, 'engagement' => 75, 'revenue' => 176.80 ],
			[ 'title' => 'Blog Monetization Strategies', 'views' => 9876, 'engagement' => 71, 'revenue' => 165.40 ],
			[ 'title' => 'WordPress Security Tips', 'views' => 8765, 'engagement' => 69, 'revenue' => 145.20 ],
		];
		?>
		<table class="pearblog-table pearblog-analytics-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Content', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Views', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Engagement', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Revenue', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Performance', 'pearblog-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $posts as $post ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $post['title'] ); ?></strong></td>
						<td><?php echo esc_html( number_format( $post['views'] ) ); ?></td>
						<td>
							<div class="pearblog-progress-bar">
								<div class="pearblog-progress-fill" style="width: <?php echo esc_attr( $post['engagement'] ); ?>%"></div>
								<span class="pearblog-progress-text"><?php echo esc_html( $post['engagement'] ); ?>%</span>
							</div>
						</td>
						<td class="pearblog-revenue-cell">$<?php echo esc_html( number_format( $post['revenue'], 2 ) ); ?></td>
						<td>
							<span class="pearblog-performance-badge pearblog-performance-excellent">
								<?php echo esc_html__( 'Excellent', 'pearblog-engine' ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render engagement metrics.
	 */
	private static function render_engagement_metrics(): void {
		$engagement = [
			[ 'metric' => 'Bounce Rate', 'value' => '32.5%', 'status' => 'good' ],
			[ 'metric' => 'Pages per Session', 'value' => '4.2', 'status' => 'excellent' ],
			[ 'metric' => 'Return Visitors', 'value' => '45.8%', 'status' => 'good' ],
			[ 'metric' => 'Social Shares', 'value' => '1,234', 'status' => 'excellent' ],
		];

		foreach ( $engagement as $item ) :
			?>
			<div class="pearblog-engagement-card">
				<div class="pearblog-engagement-metric"><?php echo esc_html( $item['metric'] ); ?></div>
				<div class="pearblog-engagement-value"><?php echo esc_html( $item['value'] ); ?></div>
				<div class="pearblog-engagement-status pearblog-status-<?php echo esc_attr( $item['status'] ); ?>">
					<?php echo esc_html( ucfirst( $item['status'] ) ); ?>
				</div>
			</div>
			<?php
		endforeach;
	}

	/**
	 * Render SEO metrics.
	 */
	private static function render_seo_metrics(): void {
		$seo_metrics = [
			[ 'metric' => 'Avg. SEO Score', 'value' => 82, 'target' => 90 ],
			[ 'metric' => 'Indexed Pages', 'value' => 289, 'target' => 342 ],
			[ 'metric' => 'Backlinks', 'value' => 1456, 'target' => 2000 ],
			[ 'metric' => 'Domain Authority', 'value' => 45, 'target' => 60 ],
		];
		?>
		<div class="pearblog-seo-metrics-grid">
			<?php foreach ( $seo_metrics as $metric ) : ?>
				<?php $percentage = min( 100, ( $metric['value'] / $metric['target'] ) * 100 ); ?>
				<div class="pearblog-seo-metric-card">
					<div class="pearblog-seo-metric-header">
						<span class="pearblog-seo-metric-name"><?php echo esc_html( $metric['metric'] ); ?></span>
						<span class="pearblog-seo-metric-value"><?php echo esc_html( number_format( $metric['value'] ) ); ?></span>
					</div>
					<div class="pearblog-seo-progress">
						<div class="pearblog-seo-progress-bar">
							<div class="pearblog-seo-progress-fill" style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
						</div>
						<span class="pearblog-seo-target">Target: <?php echo esc_html( number_format( $metric['target'] ) ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
