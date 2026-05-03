<?php
/**
 * Dashboard Tab - Revenue & Performance Overview
 *
 * Real-time KPI dashboard with revenue tracking, top articles, and performance metrics.
 *
 * @package PearBlogEngine\Admin
 * @since 7.2.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Dashboard tab controller for Admin v7.
 */
class DashboardTab {

	/**
	 * Get dashboard KPI data.
	 *
	 * @param int $days Number of days to analyze (default 30).
	 * @return array Dashboard KPI metrics.
	 */
	public static function get_kpis( int $days = 30 ): array {
		global $wpdb;

		$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );
		$end_date   = date( 'Y-m-d' );

		// Get total revenue
		$total_revenue = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(revenue_amount), 0)
				FROM {$wpdb->prefix}pb_revenue
				WHERE revenue_date >= %s AND revenue_date <= %s",
				$start_date,
				$end_date
			)
		);

		// Get total views
		$total_views = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(views), 0)
				FROM {$wpdb->prefix}pb_revenue
				WHERE revenue_date >= %s AND revenue_date <= %s",
				$start_date,
				$end_date
			)
		);

		// Calculate RPM (Revenue Per 1000 views)
		$rpm = $total_views > 0 ? ( $total_revenue / $total_views ) * 1000 : 0;

		// Get articles published count
		$articles_published = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT ID)
				FROM {$wpdb->posts}
				WHERE post_type = 'post'
				AND post_status = 'publish'
				AND post_date >= %s
				AND post_date <= %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Get previous period for trend calculation
		$prev_start = date( 'Y-m-d', strtotime( "-" . ( $days * 2 ) . " days" ) );
		$prev_end   = date( 'Y-m-d', strtotime( "-{$days} days" ) );

		$prev_revenue = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(revenue_amount), 0)
				FROM {$wpdb->prefix}pb_revenue
				WHERE revenue_date >= %s AND revenue_date < %s",
				$prev_start,
				$prev_end
			)
		);

		$prev_articles = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT ID)
				FROM {$wpdb->posts}
				WHERE post_type = 'post'
				AND post_status = 'publish'
				AND post_date >= %s
				AND post_date < %s",
				$prev_start . ' 00:00:00',
				$prev_end . ' 00:00:00'
			)
		);

		// Calculate trends
		$revenue_trend  = self::calculate_trend( (float) $total_revenue, (float) $prev_revenue );
		$articles_trend = self::calculate_trend( $articles_published, $prev_articles );

		return [
			'total_revenue'      => (float) $total_revenue,
			'articles_published' => $articles_published,
			'total_views'        => (int) $total_views,
			'rpm'                => (float) $rpm,
			'revenue_trend'      => $revenue_trend,
			'articles_trend'     => $articles_trend,
			'period_days'        => $days,
		];
	}

	/**
	 * Calculate percentage trend.
	 *
	 * @param float $current Current period value.
	 * @param float $previous Previous period value.
	 * @return array Trend data with percentage and direction.
	 */
	private static function calculate_trend( float $current, float $previous ): array {
		if ( $previous == 0 ) {
			$percentage = $current > 0 ? 100 : 0;
			$direction  = $current > 0 ? 'up' : 'neutral';
		} else {
			$percentage = ( ( $current - $previous ) / $previous ) * 100;
			$direction  = $percentage > 0 ? 'up' : ( $percentage < 0 ? 'down' : 'neutral' );
		}

		return [
			'percentage' => round( $percentage, 1 ),
			'direction'  => $direction,
		];
	}

	/**
	 * Get revenue over time chart data.
	 *
	 * @param int $days Number of days to analyze.
	 * @return array Chart data with labels and values.
	 */
	public static function get_revenue_chart( int $days = 30 ): array {
		global $wpdb;

		$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );
		$end_date   = date( 'Y-m-d' );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT revenue_date, SUM(revenue_amount) as daily_revenue
				FROM {$wpdb->prefix}pb_revenue
				WHERE revenue_date >= %s AND revenue_date <= %s
				GROUP BY revenue_date
				ORDER BY revenue_date ASC",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		$labels = [];
		$data   = [];

		// Fill in missing dates with 0
		$current_date = strtotime( $start_date );
		$end_timestamp = strtotime( $end_date );

		while ( $current_date <= $end_timestamp ) {
			$date_str = date( 'Y-m-d', $current_date );
			$labels[] = date( 'M j', $current_date );

			// Find revenue for this date
			$found = false;
			foreach ( $results as $row ) {
				if ( $row['revenue_date'] === $date_str ) {
					$data[] = (float) $row['daily_revenue'];
					$found  = true;
					break;
				}
			}

			if ( ! $found ) {
				$data[] = 0;
			}

			$current_date = strtotime( '+1 day', $current_date );
		}

		return [
			'labels' => $labels,
			'data'   => $data,
		];
	}

	/**
	 * Get top performing articles by revenue.
	 *
	 * @param int $limit Number of articles to return.
	 * @param int $days Period to analyze.
	 * @return array Top articles with revenue metrics.
	 */
	public static function get_top_articles( int $limit = 10, int $days = 30 ): array {
		global $wpdb;

		$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );
		$end_date   = date( 'Y-m-d' );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					r.post_id,
					p.post_title,
					SUM(r.revenue_amount) as total_revenue,
					SUM(r.views) as total_views,
					AVG(r.rpm) as avg_rpm
				FROM {$wpdb->prefix}pb_revenue r
				INNER JOIN {$wpdb->posts} p ON r.post_id = p.ID
				WHERE r.revenue_date >= %s
				AND r.revenue_date <= %s
				AND p.post_status = 'publish'
				GROUP BY r.post_id, p.post_title
				ORDER BY total_revenue DESC
				LIMIT %d",
				$start_date,
				$end_date,
				$limit
			),
			ARRAY_A
		);

		return array_map( function( $row ) {
			return [
				'post_id'       => (int) $row['post_id'],
				'title'         => $row['post_title'],
				'revenue'       => (float) $row['total_revenue'],
				'views'         => (int) $row['total_views'],
				'rpm'           => (float) $row['avg_rpm'],
				'edit_url'      => get_edit_post_link( (int) $row['post_id'] ),
				'permalink'     => get_permalink( (int) $row['post_id'] ),
			];
		}, $results );
	}

	/**
	 * Get revenue by source breakdown.
	 *
	 * @param int $days Period to analyze.
	 * @return array Revenue breakdown by source.
	 */
	public static function get_revenue_by_source( int $days = 30 ): array {
		global $wpdb;

		$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );
		$end_date   = date( 'Y-m-d' );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					revenue_source,
					SUM(revenue_amount) as total_revenue,
					COUNT(*) as transaction_count
				FROM {$wpdb->prefix}pb_revenue
				WHERE revenue_date >= %s AND revenue_date <= %s
				GROUP BY revenue_source
				ORDER BY total_revenue DESC",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		return array_map( function( $row ) {
			return [
				'source'             => $row['revenue_source'],
				'revenue'            => (float) $row['total_revenue'],
				'transaction_count'  => (int) $row['transaction_count'],
			];
		}, $results );
	}

	/**
	 * Render the dashboard tab HTML.
	 */
	public static function render(): void {
		$kpis = self::get_kpis( 30 );
		$top_articles = self::get_top_articles( 10, 30 );
		$revenue_by_source = self::get_revenue_by_source( 30 );

		?>
		<div class="pearblog-v7-dashboard">
			<div class="dashboard-header">
				<h2><?php echo esc_html__( 'Revenue & Performance Dashboard', 'pearblog-engine' ); ?></h2>
				<div class="dashboard-period">
					<label for="dashboard-period"><?php echo esc_html__( 'Period:', 'pearblog-engine' ); ?></label>
					<select id="dashboard-period" class="dashboard-period-select">
						<option value="7"><?php echo esc_html__( 'Last 7 days', 'pearblog-engine' ); ?></option>
						<option value="30" selected><?php echo esc_html__( 'Last 30 days', 'pearblog-engine' ); ?></option>
						<option value="90"><?php echo esc_html__( 'Last 90 days', 'pearblog-engine' ); ?></option>
					</select>
				</div>
			</div>

			<!-- KPI Cards -->
			<div class="pearblog-kpi-grid">
				<div class="pearblog-kpi-card">
					<div class="kpi-icon">💰</div>
					<div class="kpi-label"><?php echo esc_html__( 'Total Revenue', 'pearblog-engine' ); ?> (<?php echo $kpis['period_days']; ?>d)</div>
					<div class="kpi-value">$<?php echo number_format( $kpis['total_revenue'], 2 ); ?></div>
					<div class="kpi-trend" data-direction="<?php echo esc_attr( $kpis['revenue_trend']['direction'] ); ?>">
						<?php
						echo esc_html(
							( $kpis['revenue_trend']['direction'] === 'up' ? '+' : '' ) .
							$kpis['revenue_trend']['percentage'] . '%'
						);
						?>
					</div>
				</div>

				<div class="pearblog-kpi-card">
					<div class="kpi-icon">📝</div>
					<div class="kpi-label"><?php echo esc_html__( 'Articles Published', 'pearblog-engine' ); ?> (<?php echo $kpis['period_days']; ?>d)</div>
					<div class="kpi-value"><?php echo number_format( $kpis['articles_published'] ); ?></div>
					<div class="kpi-trend" data-direction="<?php echo esc_attr( $kpis['articles_trend']['direction'] ); ?>">
						<?php
						echo esc_html(
							( $kpis['articles_trend']['direction'] === 'up' ? '+' : '' ) .
							$kpis['articles_trend']['percentage'] . '%'
						);
						?>
					</div>
				</div>

				<div class="pearblog-kpi-card">
					<div class="kpi-icon">👁️</div>
					<div class="kpi-label"><?php echo esc_html__( 'Total Views', 'pearblog-engine' ); ?> (<?php echo $kpis['period_days']; ?>d)</div>
					<div class="kpi-value"><?php echo number_format( $kpis['total_views'] ); ?></div>
				</div>

				<div class="pearblog-kpi-card">
					<div class="kpi-icon">💵</div>
					<div class="kpi-label"><?php echo esc_html__( 'RPM (Revenue per 1K)', 'pearblog-engine' ); ?></div>
					<div class="kpi-value">$<?php echo number_format( $kpis['rpm'], 2 ); ?></div>
				</div>
			</div>

			<!-- Revenue Chart -->
			<div class="pearblog-chart-container">
				<h3><?php echo esc_html__( 'Revenue Over Time', 'pearblog-engine' ); ?></h3>
				<canvas id="revenueChart" width="400" height="100"></canvas>
			</div>

			<!-- Revenue by Source -->
			<?php if ( ! empty( $revenue_by_source ) ) : ?>
				<div class="pearblog-revenue-sources">
					<h3><?php echo esc_html__( 'Revenue by Source', 'pearblog-engine' ); ?></h3>
					<div class="revenue-sources-grid">
						<?php foreach ( $revenue_by_source as $source ) : ?>
							<div class="revenue-source-card">
								<div class="source-name"><?php echo esc_html( ucfirst( $source['source'] ) ); ?></div>
								<div class="source-revenue">$<?php echo number_format( $source['revenue'], 2 ); ?></div>
								<div class="source-transactions"><?php echo number_format( $source['transaction_count'] ); ?> transactions</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Top Performing Articles -->
			<div class="pearblog-top-articles">
				<h3><?php echo esc_html__( 'Top Revenue Generating Articles', 'pearblog-engine' ); ?> (<?php echo $kpis['period_days']; ?>d)</h3>
				<?php if ( ! empty( $top_articles ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Title', 'pearblog-engine' ); ?></th>
								<th><?php echo esc_html__( 'Views', 'pearblog-engine' ); ?></th>
								<th><?php echo esc_html__( 'Revenue', 'pearblog-engine' ); ?></th>
								<th><?php echo esc_html__( 'RPM', 'pearblog-engine' ); ?></th>
								<th><?php echo esc_html__( 'Actions', 'pearblog-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $top_articles as $article ) : ?>
								<tr>
									<td>
										<strong>
											<a href="<?php echo esc_url( $article['permalink'] ); ?>" target="_blank">
												<?php echo esc_html( $article['title'] ); ?>
											</a>
										</strong>
									</td>
									<td><?php echo number_format( $article['views'] ); ?></td>
									<td><strong>$<?php echo number_format( $article['revenue'], 2 ); ?></strong></td>
									<td>$<?php echo number_format( $article['rpm'], 2 ); ?></td>
									<td>
										<a href="<?php echo esc_url( $article['edit_url'] ); ?>" class="button button-small">
											<?php echo esc_html__( 'Edit', 'pearblog-engine' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<div class="pearblog-notice pearblog-notice-info">
						<p><?php echo esc_html__( 'No revenue data available yet. Revenue tracking will populate as data is collected.', 'pearblog-engine' ); ?></p>
						<p><?php echo esc_html__( 'To seed demo data for testing:', 'pearblog-engine' ); ?> <code>wp eval "PearBlogEngine\Admin\DatabaseMigration::seed_demo_data();"</code></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
