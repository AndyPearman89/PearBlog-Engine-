<?php
/**
 * PearBlog Analytics Admin Page
 *
 * Full analytics dashboard showing content performance, engagement metrics,
 * and A/B test results.
 *
 * @package PearBlog
 * @version 2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the analytics admin page.
 */
function pearblog_analytics_register_page() {
	add_menu_page(
		__( 'PearBlog Analytics', 'pearblog-theme' ),
		__( 'PB Analytics', 'pearblog-theme' ),
		'edit_posts',
		'pearblog-analytics',
		'pearblog_analytics_render_page',
		'dashicons-chart-area',
		30
	);
}
add_action( 'admin_menu', 'pearblog_analytics_register_page' );

/**
 * Enqueue admin styles for the analytics page.
 *
 * @param string $hook Current admin page hook.
 */
function pearblog_analytics_admin_styles( $hook ) {
	if ( 'toplevel_page_pearblog-analytics' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'pearblog-analytics',
		PEARBLOG_URI . '/assets/css/analytics-admin.css',
		array(),
		PEARBLOG_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'pearblog_analytics_admin_styles' );

/**
 * Get content performance summary data.
 *
 * @return array Performance data.
 */
function pearblog_analytics_get_content_summary() {
	$counts = wp_count_posts();

	$last_7  = get_posts( array(
		'numberposts' => -1,
		'post_status' => 'publish',
		'date_query'  => array( array( 'after' => '7 days ago' ) ),
		'fields'      => 'ids',
	) );

	$last_30 = get_posts( array(
		'numberposts' => -1,
		'post_status' => 'publish',
		'date_query'  => array( array( 'after' => '30 days ago' ) ),
		'fields'      => 'ids',
	) );

	$queue_count = 0;
	if ( class_exists( 'PearBlogEngine\Content\TopicQueue' ) ) {
		$queue = new \PearBlogEngine\Content\TopicQueue( get_current_blog_id() );
		$queue_count = $queue->count();
	}

	return array(
		'published'       => (int) $counts->publish,
		'drafts'          => (int) $counts->draft,
		'scheduled'       => (int) $counts->future,
		'last_7_days'     => count( $last_7 ),
		'last_30_days'    => count( $last_30 ),
		'queue_remaining' => $queue_count,
	);
}

/**
 * Get top-performing posts by comment count and engagement.
 *
 * @param int $limit Number of posts to return.
 * @return array Top posts with metrics.
 */
function pearblog_analytics_get_top_posts( $limit = 10 ) {
	$posts = get_posts( array(
		'numberposts' => $limit,
		'post_status' => 'publish',
		'orderby'     => 'comment_count',
		'order'       => 'DESC',
	) );

	$result = array();
	foreach ( $posts as $post ) {
		$popularity = get_post_meta( $post->ID, 'pb_popularity_score', true );
		$result[]   = array(
			'id'         => $post->ID,
			'title'      => get_the_title( $post ),
			'date'       => get_the_date( 'Y-m-d', $post ),
			'comments'   => (int) $post->comment_count,
			'popularity' => $popularity ? round( (float) $popularity, 1 ) : 0,
			'edit_url'   => get_edit_post_link( $post, 'raw' ),
			'view_url'   => get_permalink( $post ),
		);
	}

	return $result;
}

/**
 * Get active and completed A/B tests.
 *
 * @return array A/B test data grouped by status.
 */
function pearblog_analytics_get_ab_tests() {
	global $wpdb;

	// Active tests.
	$active_ids = $wpdb->get_col(
		"SELECT post_id FROM {$wpdb->postmeta}
		WHERE meta_key = 'pb_ab_test_enabled' AND meta_value = '1'
		GROUP BY post_id
		LIMIT 20"
	);

	// Completed tests.
	$completed_ids = $wpdb->get_col(
		"SELECT post_id FROM {$wpdb->postmeta}
		WHERE meta_key = 'pb_ab_test_completed' AND meta_value != ''
		GROUP BY post_id
		ORDER BY meta_value DESC
		LIMIT 20"
	);

	$active    = array();
	$completed = array();

	foreach ( $active_ids as $post_id ) {
		$results = function_exists( 'pb_get_ab_test_results' ) ? pb_get_ab_test_results( (int) $post_id ) : null;
		$active[] = array(
			'post_id'   => (int) $post_id,
			'title'     => get_the_title( $post_id ),
			'variant_a' => get_post_meta( $post_id, 'pb_headline_variant_a', true ),
			'variant_b' => get_post_meta( $post_id, 'pb_headline_variant_b', true ),
			'results'   => $results,
			'edit_url'  => get_edit_post_link( $post_id, 'raw' ),
		);
	}

	foreach ( $completed_ids as $post_id ) {
		$winner      = get_post_meta( $post_id, 'pb_ab_test_winner', true );
		$completed_at = get_post_meta( $post_id, 'pb_ab_test_completed', true );
		$completed[] = array(
			'post_id'      => (int) $post_id,
			'title'        => get_the_title( $post_id ),
			'winner'       => $winner,
			'completed_at' => $completed_at,
			'edit_url'     => get_edit_post_link( $post_id, 'raw' ),
		);
	}

	return array(
		'active'    => $active,
		'completed' => $completed,
	);
}

/**
 * Get category distribution data.
 *
 * @return array Category counts.
 */
function pearblog_analytics_get_category_distribution() {
	$terms = get_terms( array(
		'taxonomy'   => 'category',
		'hide_empty' => false,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 15,
	) );

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$result = array();
	foreach ( $terms as $term ) {
		$result[] = array(
			'name'  => $term->name,
			'count' => (int) $term->count,
			'url'   => get_term_link( $term ),
		);
	}

	return $result;
}

/**
 * Get revenue and affiliate summary for analytics.
 *
 * @return array Revenue and affiliate data.
 */
function pearblog_analytics_get_revenue_data() {
	$revenue   = function_exists( 'pearblog_get_revenue_summary' ) ? pearblog_get_revenue_summary( 'all', 30 ) : array( 'total' => 0, 'daily' => array(), 'types' => array() );
	$affiliate = function_exists( 'pearblog_get_affiliate_stats' ) ? pearblog_get_affiliate_stats() : array( 'booking_clicks' => 0, 'airbnb_clicks' => 0, 'total_clicks' => 0 );

	return array(
		'revenue'   => $revenue,
		'affiliate' => $affiliate,
	);
}

/**
 * Get publishing trend data (posts per day for last 30 days).
 *
 * @return array Daily post counts keyed by date.
 */
function pearblog_analytics_get_publishing_trend() {
	global $wpdb;

	$rows = $wpdb->get_results(
		"SELECT DATE(post_date) AS pub_date, COUNT(*) AS cnt
		FROM {$wpdb->posts}
		WHERE post_status = 'publish'
		  AND post_type   = 'post'
		  AND post_date   >= DATE_SUB(NOW(), INTERVAL 30 DAY)
		GROUP BY pub_date
		ORDER BY pub_date ASC",
		ARRAY_A
	);

	$trend = array();
	for ( $i = 29; $i >= 0; $i-- ) {
		$date = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
		$trend[ $date ] = 0;
	}
	foreach ( $rows as $row ) {
		if ( isset( $trend[ $row['pub_date'] ] ) ) {
			$trend[ $row['pub_date'] ] = (int) $row['cnt'];
		}
	}

	return $trend;
}

/**
 * Render the analytics admin page.
 */
function pearblog_analytics_render_page() {
	$summary    = pearblog_analytics_get_content_summary();
	$top_posts  = pearblog_analytics_get_top_posts( 10 );
	$ab_tests   = pearblog_analytics_get_ab_tests();
	$categories = pearblog_analytics_get_category_distribution();
	$rev_data   = pearblog_analytics_get_revenue_data();
	$pub_trend  = pearblog_analytics_get_publishing_trend();

	$revenue   = $rev_data['revenue'];
	$affiliate = $rev_data['affiliate'];

	?>
	<div class="wrap pb-analytics-wrap">
		<h1><?php esc_html_e( 'PearBlog Analytics', 'pearblog-theme' ); ?></h1>

		<!-- Content Overview -->
		<div class="pb-analytics-section">
			<h2><?php esc_html_e( 'Content Overview', 'pearblog-theme' ); ?></h2>
			<div class="pb-analytics-cards">
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( $summary['published'] ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Published', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( $summary['drafts'] ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Drafts', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( $summary['scheduled'] ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Scheduled', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( $summary['last_7_days'] ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Last 7 Days', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( $summary['last_30_days'] ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Last 30 Days', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( $summary['queue_remaining'] ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Queue', 'pearblog-theme' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Revenue & Affiliate Stats -->
		<div class="pb-analytics-section">
			<h2><?php esc_html_e( 'Revenue & Affiliate (Last 30 Days)', 'pearblog-theme' ); ?></h2>
			<div class="pb-analytics-cards">
				<div class="pb-analytics-card pb-analytics-card--green">
					<span class="pb-analytics-card-number"><?php echo esc_html( number_format( $revenue['total'], 2 ) ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Total Revenue', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( number_format( (float) ( $revenue['types']['ad'] ?? 0 ), 2 ) ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Ad Revenue', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( number_format( (float) ( $revenue['types']['affiliate'] ?? 0 ), 2 ) ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Affiliate Revenue', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card pb-analytics-card--amber">
					<span class="pb-analytics-card-number"><?php echo esc_html( $affiliate['total_clicks'] ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Total Aff. Clicks', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( $affiliate['booking_clicks'] ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Booking Clicks', 'pearblog-theme' ); ?></span>
				</div>
				<div class="pb-analytics-card">
					<span class="pb-analytics-card-number"><?php echo esc_html( $affiliate['airbnb_clicks'] ); ?></span>
					<span class="pb-analytics-card-label"><?php esc_html_e( 'Airbnb Clicks', 'pearblog-theme' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Publishing Trend (30 days) -->
		<div class="pb-analytics-section">
			<h2><?php esc_html_e( 'Publishing Trend (Last 30 Days)', 'pearblog-theme' ); ?></h2>
			<div class="pb-analytics-trend">
				<?php
				$max_posts = max( 1, max( $pub_trend ) );
				foreach ( $pub_trend as $date => $count ) :
					$pct = round( ( $count / $max_posts ) * 100 );
					$label = gmdate( 'j', strtotime( $date ) );
				?>
					<div class="pb-analytics-trend-bar" title="<?php echo esc_attr( $date . ': ' . $count . ' ' . _n( 'post', 'posts', $count, 'pearblog-theme' ) ); ?>">
						<div class="pb-analytics-trend-fill" style="height: <?php echo esc_attr( $pct ); ?>%;"></div>
						<span class="pb-analytics-trend-label"><?php echo esc_html( $label ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="pb-analytics-columns">
			<!-- Top Posts -->
			<div class="pb-analytics-section pb-analytics-col-main">
				<h2><?php esc_html_e( 'Top Posts', 'pearblog-theme' ); ?></h2>
				<?php if ( ! empty( $top_posts ) ) : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'pearblog-theme' ); ?></th>
								<th><?php esc_html_e( 'Date', 'pearblog-theme' ); ?></th>
								<th><?php esc_html_e( 'Comments', 'pearblog-theme' ); ?></th>
								<th><?php esc_html_e( 'Popularity', 'pearblog-theme' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'pearblog-theme' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $top_posts as $post_data ) : ?>
								<tr>
									<td><?php echo esc_html( wp_trim_words( $post_data['title'], 10 ) ); ?></td>
									<td><?php echo esc_html( $post_data['date'] ); ?></td>
									<td><?php echo esc_html( $post_data['comments'] ); ?></td>
									<td>
										<?php if ( $post_data['popularity'] > 0 ) : ?>
											<span class="pb-analytics-score"><?php echo esc_html( $post_data['popularity'] ); ?></span>
										<?php else : ?>
											<span class="pb-analytics-no-data">—</span>
										<?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( $post_data['edit_url'] ); ?>"><?php esc_html_e( 'Edit', 'pearblog-theme' ); ?></a>
										|
										<a href="<?php echo esc_url( $post_data['view_url'] ); ?>" target="_blank"><?php esc_html_e( 'View', 'pearblog-theme' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="pb-analytics-empty"><?php esc_html_e( 'No published posts yet.', 'pearblog-theme' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- Category Distribution -->
			<div class="pb-analytics-section pb-analytics-col-side">
				<h2><?php esc_html_e( 'Categories', 'pearblog-theme' ); ?></h2>
				<?php if ( ! empty( $categories ) ) : ?>
					<ul class="pb-analytics-category-list">
						<?php foreach ( $categories as $cat ) : ?>
							<li>
								<span class="pb-analytics-cat-name"><?php echo esc_html( $cat['name'] ); ?></span>
								<span class="pb-analytics-cat-count"><?php echo esc_html( $cat['count'] ); ?></span>
								<div class="pb-analytics-cat-bar">
									<?php
									$max = ( $categories[0]['count'] > 0 ) ? $categories[0]['count'] : 1;
									$pct = round( ( $cat['count'] / $max ) * 100 );
									?>
									<div class="pb-analytics-cat-bar-fill" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="pb-analytics-empty"><?php esc_html_e( 'No categories found.', 'pearblog-theme' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- A/B Testing -->
		<div class="pb-analytics-section">
			<h2><?php esc_html_e( 'A/B Tests', 'pearblog-theme' ); ?></h2>

			<?php if ( ! empty( $ab_tests['active'] ) ) : ?>
				<h3><?php esc_html_e( 'Active Tests', 'pearblog-theme' ); ?></h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Post', 'pearblog-theme' ); ?></th>
							<th><?php esc_html_e( 'Variant A', 'pearblog-theme' ); ?></th>
							<th><?php esc_html_e( 'Variant B', 'pearblog-theme' ); ?></th>
							<th><?php esc_html_e( 'A Impressions', 'pearblog-theme' ); ?></th>
							<th><?php esc_html_e( 'B Impressions', 'pearblog-theme' ); ?></th>
							<th><?php esc_html_e( 'A CTR', 'pearblog-theme' ); ?></th>
							<th><?php esc_html_e( 'B CTR', 'pearblog-theme' ); ?></th>
							<th><?php esc_html_e( 'Status', 'pearblog-theme' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $ab_tests['active'] as $test ) : ?>
							<?php
							$a_data = $test['results']['a'] ?? array( 'impressions' => 0, 'ctr' => 0 );
							$b_data = $test['results']['b'] ?? array( 'impressions' => 0, 'ctr' => 0 );
							$winner = $test['results']['winner'] ?? null;
							?>
							<tr>
								<td><a href="<?php echo esc_url( $test['edit_url'] ); ?>"><?php echo esc_html( wp_trim_words( $test['title'], 8 ) ); ?></a></td>
								<td><?php echo esc_html( wp_trim_words( $test['variant_a'], 6 ) ); ?></td>
								<td><?php echo esc_html( wp_trim_words( $test['variant_b'], 6 ) ); ?></td>
								<td><?php echo esc_html( $a_data['impressions'] ?? 0 ); ?></td>
								<td><?php echo esc_html( $b_data['impressions'] ?? 0 ); ?></td>
								<td><?php echo esc_html( number_format( (float) ( $a_data['ctr'] ?? 0 ), 1 ) ); ?>%</td>
								<td><?php echo esc_html( number_format( (float) ( $b_data['ctr'] ?? 0 ), 1 ) ); ?>%</td>
								<td>
									<?php if ( $winner ) : ?>
										<span class="pb-analytics-winner"><?php
											printf(
												/* translators: %s: winning variant letter */
												esc_html__( 'Winner: %s', 'pearblog-theme' ),
												esc_html( strtoupper( $winner ) )
											);
										?></span>
									<?php else : ?>
										<span class="pb-analytics-running"><?php esc_html_e( 'Running', 'pearblog-theme' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $ab_tests['completed'] ) ) : ?>
				<h3><?php esc_html_e( 'Completed Tests', 'pearblog-theme' ); ?></h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Post', 'pearblog-theme' ); ?></th>
							<th><?php esc_html_e( 'Winner', 'pearblog-theme' ); ?></th>
							<th><?php esc_html_e( 'Completed', 'pearblog-theme' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $ab_tests['completed'] as $test ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( $test['edit_url'] ); ?>"><?php echo esc_html( wp_trim_words( $test['title'], 10 ) ); ?></a></td>
								<td><span class="pb-analytics-winner"><?php echo esc_html( strtoupper( $test['winner'] ) ); ?></span></td>
								<td><?php
									$ts = strtotime( $test['completed_at'] );
									echo esc_html( $ts ? date_i18n( get_option( 'date_format' ), $ts ) : $test['completed_at'] );
								?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( empty( $ab_tests['active'] ) && empty( $ab_tests['completed'] ) ) : ?>
				<p class="pb-analytics-empty">
					<?php esc_html_e( 'No A/B tests configured. Enable A/B testing on individual posts via the post editor sidebar.', 'pearblog-theme' ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
