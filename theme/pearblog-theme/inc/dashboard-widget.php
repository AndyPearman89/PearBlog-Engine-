<?php
/**
 * PearBlog PRO Admin Dashboard Widget
 *
 * Adds an overview widget to the WordPress admin dashboard.
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the PearBlog dashboard widget.
 */
function pearblog_add_dashboard_widget() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_add_dashboard_widget(
		'pearblog_dashboard_widget',
		__( 'PearBlog PRO Overview', 'pearblog-theme' ),
		'pearblog_dashboard_widget_render'
	);
}
add_action( 'wp_dashboard_setup', 'pearblog_add_dashboard_widget' );

/**
 * Render the dashboard widget content.
 */
function pearblog_dashboard_widget_render() {
	$published  = wp_count_posts()->publish;
	$drafts     = wp_count_posts()->draft;
	$scheduled  = wp_count_posts()->future;
	$category_terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'fields' => 'count' ) );
	$categories     = is_wp_error( $category_terms ) ? 0 : (int) $category_terms;

	// Get recent posts.
	$recent_posts = get_posts( array(
		'numberposts' => 5,
		'post_status' => 'publish',
	) );

	// Get queue count if available.
	$queue_count = 0;
	if ( class_exists( 'PearBlogEngine\Content\TopicQueue' ) ) {
		$queue = new \PearBlogEngine\Content\TopicQueue( get_current_blog_id() );
		$queue_count = $queue->count();
	}

	// Calculate content velocity (posts in last 7 days).
	$last_week = get_posts( array(
		'numberposts' => -1,
		'post_status' => 'publish',
		'date_query'  => array(
			array(
				'after' => '1 week ago',
			),
		),
	) );
	$velocity = count( $last_week );

	// Affiliate stats.
	$affiliate_stats = function_exists( 'pearblog_get_affiliate_stats' ) ? pearblog_get_affiliate_stats() : array( 'booking_clicks' => 0, 'airbnb_clicks' => 0, 'total_clicks' => 0 );

	// Revenue summary (last 30 days).
	$revenue = function_exists( 'pearblog_get_revenue_summary' ) ? pearblog_get_revenue_summary( 'all', 30 ) : array( 'total' => 0 );

	// Performance score.
	$perf_score = function_exists( 'pearblog_get_lighthouse_score' ) ? pearblog_get_lighthouse_score() : array( 'performance' => 0 );

	?>
	<style>
		.pb-dashboard-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 12px;
			margin-bottom: 16px;
		}
		.pb-dashboard-stat {
			background: #f0f0f1;
			border-radius: 6px;
			padding: 12px;
			text-align: center;
		}
		.pb-dashboard-stat-number {
			display: block;
			font-size: 24px;
			font-weight: 700;
			color: #2563eb;
			line-height: 1.2;
		}
		.pb-dashboard-stat-label {
			display: block;
			font-size: 11px;
			color: #646970;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		.pb-dashboard-recent {
			margin-top: 12px;
		}
		.pb-dashboard-recent h4 {
			margin: 0 0 8px;
			font-size: 13px;
		}
		.pb-dashboard-recent ul {
			margin: 0;
			padding: 0;
			list-style: none;
		}
		.pb-dashboard-recent li {
			padding: 4px 0;
			border-bottom: 1px solid #f0f0f1;
			font-size: 13px;
		}
		.pb-dashboard-recent li:last-child {
			border-bottom: none;
		}
		.pb-dashboard-recent .pb-post-date {
			float: right;
			color: #646970;
			font-size: 12px;
		}
		.pb-dashboard-actions {
			margin-top: 12px;
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
		}
		.pb-dashboard-actions .button {
			flex: 1;
			text-align: center;
		}
		.pb-dashboard-section-title {
			margin: 16px 0 8px;
			font-size: 12px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			color: #646970;
			border-bottom: 1px solid #f0f0f1;
			padding-bottom: 4px;
		}
		.pb-dashboard-stat--green .pb-dashboard-stat-number { color: #00a32a; }
		.pb-dashboard-stat--amber .pb-dashboard-stat-number { color: #dba617; }
	</style>

	<div class="pb-dashboard-grid">
		<div class="pb-dashboard-stat">
			<span class="pb-dashboard-stat-number"><?php echo esc_html( $published ); ?></span>
			<span class="pb-dashboard-stat-label"><?php esc_html_e( 'Published', 'pearblog-theme' ); ?></span>
		</div>
		<div class="pb-dashboard-stat">
			<span class="pb-dashboard-stat-number"><?php echo esc_html( $drafts ); ?></span>
			<span class="pb-dashboard-stat-label"><?php esc_html_e( 'Drafts', 'pearblog-theme' ); ?></span>
		</div>
		<div class="pb-dashboard-stat">
			<span class="pb-dashboard-stat-number"><?php echo esc_html( $scheduled ); ?></span>
			<span class="pb-dashboard-stat-label"><?php esc_html_e( 'Scheduled', 'pearblog-theme' ); ?></span>
		</div>
		<div class="pb-dashboard-stat">
			<span class="pb-dashboard-stat-number"><?php echo esc_html( $categories ); ?></span>
			<span class="pb-dashboard-stat-label"><?php esc_html_e( 'Categories', 'pearblog-theme' ); ?></span>
		</div>
		<div class="pb-dashboard-stat">
			<span class="pb-dashboard-stat-number"><?php echo esc_html( $velocity ); ?></span>
			<span class="pb-dashboard-stat-label"><?php esc_html_e( 'This Week', 'pearblog-theme' ); ?></span>
		</div>
		<div class="pb-dashboard-stat">
			<span class="pb-dashboard-stat-number"><?php echo esc_html( $queue_count ); ?></span>
			<span class="pb-dashboard-stat-label"><?php esc_html_e( 'Queue', 'pearblog-theme' ); ?></span>
		</div>
	</div>

	<!-- Monetization Row -->
	<h5 class="pb-dashboard-section-title"><?php esc_html_e( 'Monetization (30 days)', 'pearblog-theme' ); ?></h5>
	<div class="pb-dashboard-grid">
		<div class="pb-dashboard-stat pb-dashboard-stat--green">
			<span class="pb-dashboard-stat-number"><?php echo esc_html( number_format( $revenue['total'], 2 ) ); ?></span>
			<span class="pb-dashboard-stat-label"><?php esc_html_e( 'Revenue', 'pearblog-theme' ); ?></span>
		</div>
		<div class="pb-dashboard-stat pb-dashboard-stat--amber">
			<span class="pb-dashboard-stat-number"><?php echo esc_html( $affiliate_stats['total_clicks'] ); ?></span>
			<span class="pb-dashboard-stat-label"><?php esc_html_e( 'Aff. Clicks', 'pearblog-theme' ); ?></span>
		</div>
		<div class="pb-dashboard-stat">
			<span class="pb-dashboard-stat-number"><?php echo esc_html( $perf_score['performance'] ); ?></span>
			<span class="pb-dashboard-stat-label"><?php esc_html_e( 'Perf. Score', 'pearblog-theme' ); ?></span>
		</div>
	</div>

	<?php if ( ! empty( $recent_posts ) ) : ?>
		<div class="pb-dashboard-recent">
			<h4><?php esc_html_e( 'Recently Published', 'pearblog-theme' ); ?></h4>
			<ul>
				<?php foreach ( $recent_posts as $post ) : ?>
					<li>
						<span class="pb-post-date"><?php echo esc_html( get_the_date( 'M j', $post ) ); ?></span>
						<a href="<?php echo esc_url( get_edit_post_link( $post ) ); ?>">
							<?php echo esc_html( wp_trim_words( get_the_title( $post ), 8 ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="pb-dashboard-actions">
		<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'New Post', 'pearblog-theme' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'options-general.php?page=pearblog-engine' ) ); ?>" class="button">
			<?php esc_html_e( 'Engine Settings', 'pearblog-theme' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" class="button">
			<?php esc_html_e( 'Customize', 'pearblog-theme' ); ?>
		</a>
	</div>
	<?php
}

/**
 * Enqueue admin dashboard styles.
 *
 * @param string $hook Admin page hook.
 */
function pearblog_dashboard_admin_styles( $hook ) {
	if ( 'index.php' !== $hook ) {
		return;
	}

	wp_add_inline_style( 'dashboard', '
		#pearblog_dashboard_widget .inside {
			padding: 12px;
		}
	' );
}
add_action( 'admin_enqueue_scripts', 'pearblog_dashboard_admin_styles' );
