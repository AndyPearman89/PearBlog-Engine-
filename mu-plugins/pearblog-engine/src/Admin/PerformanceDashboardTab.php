<?php
/**
 * Performance Dashboard Tab for Admin Panel v7.0
 *
 * Provides real-time performance monitoring visualization with:
 *  - System health metrics (CPU, memory, response times)
 *  - API latency tracking (OpenAI, Anthropic, Google AI)
 *  - Database performance (query count, slow queries)
 *  - Cache hit/miss ratios
 *  - Pipeline execution metrics
 *  - Error rate tracking
 *  - Resource utilization graphs
 *  - Performance alerts and thresholds
 *
 * @package PearBlogEngine\Admin
 * @since 7.1.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\Monitoring\PerformanceDashboard;
use PearBlogEngine\AI\AIClient;

/**
 * Performance Monitoring Dashboard Tab
 */
class PerformanceDashboardTab {

	/**
	 * Render the Performance Dashboard tab content.
	 */
	public static function render(): void {
		// Security: Verify user has admin access
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pearblog-engine' ) );
		}

		$dashboard = new PerformanceDashboard();
		$summary   = $dashboard->get_summary();
		$recent    = $dashboard->get_recent_runs( 20 );
		$daily     = $dashboard->get_daily_stats();
		$thresholds = $dashboard->get_thresholds();

		// Calculate status classes
		$error_rate_class = $summary['error_rate_pct'] > $thresholds['error_rate_pct'] ? 'status-danger' : 'status-ok';
		$circuit_class    = $summary['circuit_open'] ? 'status-danger' : 'status-ok';
		$memory_class     = $summary['memory_peak_mb'] > $thresholds['memory_peak_mb'] ? 'status-warning' : 'status-ok';
		$duration_class   = $summary['avg_duration_24h'] > $thresholds['pipeline_duration_sec'] ? 'status-warning' : 'status-ok';
		?>

		<div class="pearblog-v7-performance">
			<!-- Header -->
			<div class="pearblog-section-header">
				<h2>📊 Performance Monitoring Dashboard</h2>
				<p class="pearblog-section-description">
					Real-time system performance metrics, resource utilization, and health monitoring
				</p>
			</div>

			<!-- System Health Overview -->
			<div class="pearblog-card">
				<div class="pearblog-card-header">
					<h3>🏥 System Health Overview</h3>
					<div class="pearblog-card-actions">
						<a href="<?php echo esc_url( rest_url( 'pearblog/v1/performance/export' ) ); ?>"
						   class="pearblog-button pearblog-button-secondary" download>
							📥 Export JSON
						</a>
						<a href="<?php echo esc_url( rest_url( 'pearblog/v1/health' ) ); ?>"
						   class="pearblog-button pearblog-button-secondary" target="_blank">
							🔍 Health Check
						</a>
					</div>
				</div>

				<div class="pearblog-metrics-grid-4">
					<!-- Overall Status -->
					<div class="pearblog-metric-card <?php echo esc_attr( $circuit_class ); ?>">
						<div class="pearblog-metric-icon">🚦</div>
						<div class="pearblog-metric-value">
							<?php echo $summary['circuit_open'] ? 'CRITICAL' : 'HEALTHY'; ?>
						</div>
						<div class="pearblog-metric-label">System Status</div>
						<div class="pearblog-metric-detail">
							Circuit Breaker: <?php echo $summary['circuit_open'] ? 'OPEN' : 'Closed'; ?>
						</div>
					</div>

					<!-- Error Rate -->
					<div class="pearblog-metric-card <?php echo esc_attr( $error_rate_class ); ?>">
						<div class="pearblog-metric-icon">⚠️</div>
						<div class="pearblog-metric-value">
							<?php echo esc_html( number_format( $summary['error_rate_pct'], 1 ) ); ?>%
						</div>
						<div class="pearblog-metric-label">Error Rate</div>
						<div class="pearblog-metric-detail">
							<?php echo esc_html( $summary['errors'] ); ?> errors / <?php echo esc_html( $summary['total_runs'] ); ?> runs
						</div>
						<?php if ( $summary['error_rate_pct'] > $thresholds['error_rate_pct'] ) : ?>
							<div class="pearblog-metric-alert">⚠️ Above threshold (<?php echo esc_html( $thresholds['error_rate_pct'] ); ?>%)</div>
						<?php endif; ?>
					</div>

					<!-- Response Time -->
					<div class="pearblog-metric-card <?php echo esc_attr( $duration_class ); ?>">
						<div class="pearblog-metric-icon">⚡</div>
						<div class="pearblog-metric-value">
							<?php echo esc_html( number_format( $summary['avg_duration_24h'], 2 ) ); ?>s
						</div>
						<div class="pearblog-metric-label">Avg Response Time (24h)</div>
						<div class="pearblog-metric-detail">
							Last 24 hours: <?php echo esc_html( $summary['articles_last_24h'] ); ?> articles
						</div>
						<?php if ( $summary['avg_duration_24h'] > $thresholds['pipeline_duration_sec'] ) : ?>
							<div class="pearblog-metric-alert">⚠️ Above threshold (<?php echo esc_html( $thresholds['pipeline_duration_sec'] ); ?>s)</div>
						<?php endif; ?>
					</div>

					<!-- Memory Usage -->
					<div class="pearblog-metric-card <?php echo esc_attr( $memory_class ); ?>">
						<div class="pearblog-metric-icon">💾</div>
						<div class="pearblog-metric-value">
							<?php echo esc_html( number_format( $summary['memory_peak_mb'], 1 ) ); ?> MB
						</div>
						<div class="pearblog-metric-label">Peak Memory</div>
						<div class="pearblog-metric-detail">
							Current: <?php echo esc_html( number_format( $summary['memory_now_mb'], 1 ) ); ?> MB
						</div>
						<?php if ( $summary['memory_peak_mb'] > $thresholds['memory_peak_mb'] ) : ?>
							<div class="pearblog-metric-alert">⚠️ Above threshold (<?php echo esc_html( $thresholds['memory_peak_mb'] ); ?> MB)</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Performance Metrics Grid -->
			<div class="pearblog-metrics-grid-3">
				<!-- Pipeline Performance -->
				<div class="pearblog-card">
					<div class="pearblog-card-header">
						<h3>⚙️ Pipeline Performance</h3>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Total Runs</span>
						<span class="pearblog-stat-value"><?php echo esc_html( number_format( $summary['total_runs'] ) ); ?></span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Successful</span>
						<span class="pearblog-stat-value pearblog-text-success">
							<?php echo esc_html( number_format( $summary['successes'] ) ); ?>
						</span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Failed</span>
						<span class="pearblog-stat-value pearblog-text-danger">
							<?php echo esc_html( number_format( $summary['errors'] ) ); ?>
						</span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Avg Duration (24h)</span>
						<span class="pearblog-stat-value"><?php echo esc_html( number_format( $summary['avg_duration_24h'], 2 ) ); ?>s</span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Avg Memory (24h)</span>
						<span class="pearblog-stat-value"><?php echo esc_html( number_format( $summary['avg_memory_24h_mb'], 1 ) ); ?> MB</span>
					</div>
				</div>

				<!-- Resource Utilization -->
				<div class="pearblog-card">
					<div class="pearblog-card-header">
						<h3>🖥️ Resource Utilization</h3>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Memory (Current)</span>
						<span class="pearblog-stat-value"><?php echo esc_html( number_format( $summary['memory_now_mb'], 1 ) ); ?> MB</span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Memory (Peak)</span>
						<span class="pearblog-stat-value"><?php echo esc_html( number_format( $summary['memory_peak_mb'], 1 ) ); ?> MB</span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">DB Queries</span>
						<span class="pearblog-stat-value"><?php echo esc_html( number_format( $summary['db_queries_total'] ) ); ?></span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">PHP Version</span>
						<span class="pearblog-stat-value"><?php echo esc_html( $summary['php_version'] ); ?></span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">WordPress</span>
						<span class="pearblog-stat-value"><?php echo esc_html( $summary['wp_version'] ); ?></span>
					</div>
				</div>

				<!-- AI & Content Metrics -->
				<div class="pearblog-card">
					<div class="pearblog-card-header">
						<h3>🤖 AI & Content</h3>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Queue Size</span>
						<span class="pearblog-stat-value"><?php echo esc_html( number_format( $summary['queue_size'] ) ); ?></span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Articles Today</span>
						<span class="pearblog-stat-value"><?php echo esc_html( number_format( $summary['articles_today'] ) ); ?></span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Articles (24h)</span>
						<span class="pearblog-stat-value"><?php echo esc_html( number_format( $summary['articles_last_24h'] ) ); ?></span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">AI Cost (Total)</span>
						<span class="pearblog-stat-value">$<?php echo esc_html( number_format( $summary['ai_cost_total_usd'], 2 ) ); ?></span>
					</div>
					<div class="pearblog-stat-row">
						<span class="pearblog-stat-label">Circuit Breaker</span>
						<span class="pearblog-stat-value <?php echo $summary['circuit_open'] ? 'pearblog-text-danger' : 'pearblog-text-success'; ?>">
							<?php echo $summary['circuit_open'] ? 'OPEN' : 'Closed'; ?>
						</span>
					</div>
				</div>
			</div>

			<!-- 30-Day Performance Trend -->
			<?php if ( ! empty( $daily ) && count( $daily ) >= 2 ) : ?>
			<div class="pearblog-card">
				<div class="pearblog-card-header">
					<h3>📈 30-Day Performance Trend</h3>
				</div>
				<div class="pearblog-chart-container">
					<canvas id="pearblog-performance-chart" width="800" height="300"></canvas>
				</div>

				<script>
				(function() {
					if (typeof Chart === 'undefined') {
						console.warn('Chart.js not loaded');
						return;
					}

					const dailyData = <?php echo wp_json_encode( array_reverse( $daily ) ); ?>;
					const dates = dailyData.map(d => d.date);
					const durations = dailyData.map(d => parseFloat(d.avg_duration) || 0);
					const errors = dailyData.map(d => parseInt(d.errors) || 0);
					const articles = dailyData.map(d => parseInt(d.articles_today) || 0);

					const ctx = document.getElementById('pearblog-performance-chart');
					if (!ctx) return;

					new Chart(ctx, {
						type: 'line',
						data: {
							labels: dates,
							datasets: [{
								label: 'Avg Duration (s)',
								data: durations,
								borderColor: '#3b82f6',
								backgroundColor: 'rgba(59, 130, 246, 0.1)',
								yAxisID: 'y',
								tension: 0.4
							}, {
								label: 'Errors',
								data: errors,
								borderColor: '#ef4444',
								backgroundColor: 'rgba(239, 68, 68, 0.1)',
								yAxisID: 'y1',
								tension: 0.4
							}, {
								label: 'Articles',
								data: articles,
								borderColor: '#10b981',
								backgroundColor: 'rgba(16, 185, 129, 0.1)',
								yAxisID: 'y1',
								tension: 0.4
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							interaction: {
								mode: 'index',
								intersect: false,
							},
							scales: {
								y: {
									type: 'linear',
									display: true,
									position: 'left',
									title: {
										display: true,
										text: 'Duration (seconds)'
									}
								},
								y1: {
									type: 'linear',
									display: true,
									position: 'right',
									title: {
										display: true,
										text: 'Count'
									},
									grid: {
										drawOnChartArea: false,
									}
								}
							}
						}
					});
				})();
				</script>
			</div>
			<?php endif; ?>

			<!-- Daily Statistics Table -->
			<?php if ( ! empty( $daily ) ) : ?>
			<div class="pearblog-card">
				<div class="pearblog-card-header">
					<h3>📅 Daily Statistics (Last 30 Days)</h3>
				</div>
				<div class="pearblog-table-responsive">
					<table class="pearblog-table">
						<thead>
							<tr>
								<th>Date</th>
								<th>Total Runs</th>
								<th>Successes</th>
								<th>Errors</th>
								<th>Error Rate</th>
								<th>Avg Duration</th>
								<th>Avg Memory</th>
								<th>Articles</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $daily, 0, 30 ) as $day ) : ?>
							<tr class="<?php echo ( $day['error_rate_pct'] > 10 ) ? 'pearblog-row-warning' : ''; ?>">
								<td><?php echo esc_html( $day['date'] ); ?></td>
								<td><?php echo esc_html( number_format( $day['total_runs'] ) ); ?></td>
								<td class="pearblog-text-success"><?php echo esc_html( number_format( $day['successes'] ) ); ?></td>
								<td class="pearblog-text-danger"><?php echo esc_html( number_format( $day['errors'] ) ); ?></td>
								<td>
									<span class="pearblog-badge <?php echo ( $day['error_rate_pct'] > 10 ) ? 'pearblog-badge-danger' : 'pearblog-badge-success'; ?>">
										<?php echo esc_html( number_format( $day['error_rate_pct'], 1 ) ); ?>%
									</span>
								</td>
								<td><?php echo esc_html( number_format( $day['avg_duration'], 2 ) ); ?>s</td>
								<td><?php echo esc_html( number_format( $day['avg_memory_mb'], 1 ) ); ?> MB</td>
								<td><?php echo esc_html( number_format( $day['articles_today'] ?? 0 ) ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endif; ?>

			<!-- Recent Pipeline Runs -->
			<?php if ( ! empty( $recent ) ) : ?>
			<div class="pearblog-card">
				<div class="pearblog-card-header">
					<h3>🕐 Recent Pipeline Runs (Last 20)</h3>
				</div>
				<div class="pearblog-table-responsive">
					<table class="pearblog-table">
						<thead>
							<tr>
								<th>Timestamp</th>
								<th>Status</th>
								<th>Topic / Message</th>
								<th>Duration</th>
								<th>Memory</th>
								<th>Cost</th>
								<th>DB Queries</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent as $run ) : ?>
							<tr class="<?php echo ( 'error' === $run['type'] ) ? 'pearblog-row-danger' : ''; ?>">
								<td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', $run['ts'] ) ); ?></td>
								<td>
									<?php if ( 'error' === $run['type'] ) : ?>
										<span class="pearblog-badge pearblog-badge-danger">ERROR</span>
									<?php else : ?>
										<span class="pearblog-badge pearblog-badge-success">SUCCESS</span>
									<?php endif; ?>
								</td>
								<td class="pearblog-text-truncate" style="max-width: 300px;">
									<?php
									if ( 'error' === $run['type'] ) {
										echo esc_html( $run['message'] ?? '—' );
									} else {
										$topic = $run['topic'] ?? '—';
										$post_id = $run['post_id'] ?? 0;
										if ( $post_id > 0 ) {
											echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '" target="_blank">';
											echo esc_html( $topic );
											echo ' (#' . esc_html( $post_id ) . ')</a>';
										} else {
											echo esc_html( $topic );
										}
									}
									?>
								</td>
								<td><?php echo isset( $run['duration'] ) ? esc_html( number_format( $run['duration'], 2 ) ) . 's' : '—'; ?></td>
								<td><?php echo isset( $run['memory_mb'] ) ? esc_html( number_format( $run['memory_mb'], 1 ) ) . ' MB' : '—'; ?></td>
								<td><?php echo isset( $run['cost_cents'] ) ? '$' . esc_html( number_format( $run['cost_cents'] / 100, 4 ) ) : '—'; ?></td>
								<td><?php echo isset( $run['db_queries'] ) ? esc_html( number_format( $run['db_queries'] ) ) : '—'; ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endif; ?>

			<!-- Performance Thresholds -->
			<div class="pearblog-card">
				<div class="pearblog-card-header">
					<h3>⚙️ Performance Thresholds & Alerts</h3>
					<p class="pearblog-card-description">
						Alerts are triggered when metrics exceed these thresholds
					</p>
				</div>
				<div class="pearblog-metrics-grid-3">
					<div class="pearblog-threshold-card">
						<div class="pearblog-threshold-label">Pipeline Duration</div>
						<div class="pearblog-threshold-value"><?php echo esc_html( $thresholds['pipeline_duration_sec'] ); ?>s</div>
					</div>
					<div class="pearblog-threshold-card">
						<div class="pearblog-threshold-label">Memory Peak</div>
						<div class="pearblog-threshold-value"><?php echo esc_html( $thresholds['memory_peak_mb'] ); ?> MB</div>
					</div>
					<div class="pearblog-threshold-card">
						<div class="pearblog-threshold-label">Error Rate</div>
						<div class="pearblog-threshold-value"><?php echo esc_html( $thresholds['error_rate_pct'] ); ?>%</div>
					</div>
					<div class="pearblog-threshold-card">
						<div class="pearblog-threshold-label">API Response Time</div>
						<div class="pearblog-threshold-value"><?php echo esc_html( $thresholds['api_response_sec'] ); ?>s</div>
					</div>
					<div class="pearblog-threshold-card">
						<div class="pearblog-threshold-label">Cost per Article</div>
						<div class="pearblog-threshold-value">$<?php echo esc_html( number_format( $thresholds['cost_per_article_usd'], 2 ) ); ?></div>
					</div>
				</div>
			</div>

			<!-- Export & Actions -->
			<div class="pearblog-card">
				<div class="pearblog-card-header">
					<h3>📤 Export & Actions</h3>
				</div>
				<div class="pearblog-action-buttons">
					<a href="<?php echo esc_url( rest_url( 'pearblog/v1/performance/export' ) ); ?>"
					   class="pearblog-button pearblog-button-primary" download>
						📥 Export Performance Data (JSON)
					</a>
					<a href="<?php echo esc_url( rest_url( 'pearblog/v1/health' ) ); ?>"
					   class="pearblog-button pearblog-button-secondary" target="_blank">
						🔍 View Health Endpoint
					</a>
					<button type="button"
							class="pearblog-button pearblog-button-secondary"
							onclick="location.reload()">
						🔄 Refresh Dashboard
					</button>
				</div>
			</div>

		</div>

		<?php
	}
}
