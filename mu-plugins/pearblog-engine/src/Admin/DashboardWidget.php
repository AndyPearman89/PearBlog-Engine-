<?php
/**
 * WordPress Dashboard Widget — PearBlog Engine pipeline stats.
 *
 * Registers a right-column widget on the WP admin dashboard showing
 * a real-time snapshot of the autonomous pipeline health:
 *
 *  - Topic queue size
 *  - Posts published today
 *  - Total AI-generated posts (all time)
 *  - Images missing alt text
 *  - Last pipeline run timestamp
 *
 * @package PearBlogEngine\Admin
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\AI\ImageAnalyzer;
use PearBlogEngine\Content\TopicQueue;

/**
 * WP Dashboard widget showing live PearBlog Engine pipeline stats.
 */
class DashboardWidget {

	private const WIDGET_ID   = 'pearblog_engine_pipeline_stats';
	private const WIDGET_NAME = 'PearBlog Engine — Pipeline Stats';

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'add_widget' ] );
		add_action( 'admin_head-index.php', [ $this, 'inline_styles' ] );
	}

	/**
	 * Add the widget to the right column of the dashboard.
	 */
	public function add_widget(): void {
		wp_add_dashboard_widget(
			self::WIDGET_ID,
			self::WIDGET_NAME,
			[ $this, 'render' ],
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render the widget HTML.
	 */
	public function render(): void {
		$queue        = new TopicQueue( get_current_blog_id() );
		$img_analyzer = new ImageAnalyzer();
		$img_summary  = $img_analyzer->get_summary();

		// Posts published today.
		$today         = wp_date( 'Y-m-d' );
		$posts_today   = (int) ( new \WP_Query( [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'date_query'     => [ [ 'after' => $today, 'inclusive' => true ] ],
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => false,
		] ) )->found_posts;

		// Last pipeline run (stored as option by CronManager or pipeline itself).
		$last_run_raw = (string) get_option( 'pearblog_last_pipeline_run', '' );
		$last_run     = $last_run_raw ? human_time_diff( (int) $last_run_raw, time() ) . ' ' . __( 'ago', 'pearblog-engine' ) : __( 'Never', 'pearblog-engine' );

		// OpenAI key status.
		$openai_ok = ! empty( get_option( 'pearblog_openai_api_key', '' ) ) || defined( 'PEARBLOG_OPENAI_API_KEY' );

		// Admin page URL for quick links.
		$admin_url = admin_url( 'admin.php?page=pearblog-engine' );

		?>
		<div class="pb-dw-wrap">

			<!-- Status row -->
			<div class="pb-dw-status-row">
				<span class="pb-dw-status-dot <?php echo $openai_ok ? 'pb-dw-dot-green' : 'pb-dw-dot-red'; ?>"></span>
				<span class="pb-dw-status-label">
					<?php echo $openai_ok
						? esc_html__( 'OpenAI connected', 'pearblog-engine' )
						: esc_html__( 'OpenAI key missing', 'pearblog-engine' ); ?>
				</span>
				<span class="pb-dw-last-run">
					<?php esc_html_e( 'Last run:', 'pearblog-engine' ); ?>
					<strong><?php echo esc_html( $last_run ); ?></strong>
				</span>
			</div>

			<!-- Stats grid -->
			<div class="pb-dw-grid">
				<div class="pb-dw-stat">
					<div class="pb-dw-stat-num <?php echo $queue->count() > 0 ? 'pb-dw-num-blue' : ''; ?>">
						<?php echo esc_html( (string) $queue->count() ); ?>
					</div>
					<div class="pb-dw-stat-label"><?php esc_html_e( 'In Queue', 'pearblog-engine' ); ?></div>
				</div>
				<div class="pb-dw-stat">
					<div class="pb-dw-stat-num <?php echo $posts_today > 0 ? 'pb-dw-num-green' : ''; ?>">
						<?php echo esc_html( (string) $posts_today ); ?>
					</div>
					<div class="pb-dw-stat-label"><?php esc_html_e( 'Published Today', 'pearblog-engine' ); ?></div>
				</div>
				<div class="pb-dw-stat">
					<div class="pb-dw-stat-num"><?php echo esc_html( (string) $img_summary['ai_generated'] ); ?></div>
					<div class="pb-dw-stat-label"><?php esc_html_e( 'AI Images', 'pearblog-engine' ); ?></div>
				</div>
				<div class="pb-dw-stat">
					<div class="pb-dw-stat-num <?php echo $img_summary['missing_alt'] > 0 ? 'pb-dw-num-orange' : ''; ?>">
						<?php echo esc_html( (string) $img_summary['missing_alt'] ); ?>
					</div>
					<div class="pb-dw-stat-label"><?php esc_html_e( 'Missing Alt', 'pearblog-engine' ); ?></div>
				</div>
			</div>

			<!-- Posts without images alert -->
			<?php if ( $img_summary['posts_without_images'] > 0 ) : ?>
				<div class="pb-dw-alert pb-dw-alert-warning">
					<?php printf(
						/* translators: %d: number of posts */
						esc_html( _n(
							'%d post is missing a featured image.',
							'%d posts are missing a featured image.',
							$img_summary['posts_without_images'],
							'pearblog-engine'
						) ),
						(int) $img_summary['posts_without_images']
					); ?>
					<a href="<?php echo esc_url( $admin_url . '&tab=images' ); ?>">
						<?php esc_html_e( 'Fix now →', 'pearblog-engine' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<!-- Queue empty alert -->
			<?php if ( $queue->count() === 0 ) : ?>
				<div class="pb-dw-alert pb-dw-alert-info">
					<?php esc_html_e( 'Topic queue is empty — pipeline is idle.', 'pearblog-engine' ); ?>
					<a href="<?php echo esc_url( $admin_url . '&tab=queue' ); ?>">
						<?php esc_html_e( 'Add topics →', 'pearblog-engine' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<!-- Quick links -->
			<div class="pb-dw-links">
				<a href="<?php echo esc_url( $admin_url ); ?>" class="pb-dw-link-btn pb-dw-link-primary">
					⚙️ <?php esc_html_e( 'Engine Settings', 'pearblog-engine' ); ?>
				</a>
				<a href="<?php echo esc_url( $admin_url . '&tab=queue' ); ?>" class="pb-dw-link-btn">
					📋 <?php esc_html_e( 'Queue', 'pearblog-engine' ); ?>
				</a>
				<a href="<?php echo esc_url( $admin_url . '&tab=seo' ); ?>" class="pb-dw-link-btn">
					📈 <?php esc_html_e( 'SEO Audit', 'pearblog-engine' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Output minimal inline CSS for the widget (scoped to avoid conflicts).
	 */
	public function inline_styles(): void {
		?>
		<style id="pb-dw-styles">
		.pb-dw-wrap { font-size: 13px; color: #1e1e1e; }
		.pb-dw-status-row {
			display: flex; align-items: center; gap: 8px;
			padding: 8px 0 12px; border-bottom: 1px solid #e0e0e0; margin-bottom: 14px;
		}
		.pb-dw-status-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
		.pb-dw-dot-green { background: #00a32a; }
		.pb-dw-dot-red   { background: #d63638; }
		.pb-dw-status-label { flex: 1; font-weight: 500; }
		.pb-dw-last-run { color: #757575; font-size: 12px; }
		.pb-dw-grid {
			display: grid; grid-template-columns: repeat(4, 1fr);
			gap: 10px; margin-bottom: 14px;
		}
		.pb-dw-stat { text-align: center; }
		.pb-dw-stat-num {
			font-size: 1.75rem; font-weight: 700; line-height: 1;
			margin-bottom: 4px; color: #3c434a;
		}
		.pb-dw-num-blue   { color: #2271b1; }
		.pb-dw-num-green  { color: #00a32a; }
		.pb-dw-num-orange { color: #dba617; }
		.pb-dw-stat-label { font-size: 11px; color: #757575; text-transform: uppercase; letter-spacing: .04em; }
		.pb-dw-alert {
			padding: 8px 12px; border-radius: 4px; margin-bottom: 10px;
			font-size: 12px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
		}
		.pb-dw-alert a { font-weight: 600; white-space: nowrap; }
		.pb-dw-alert-warning { background: #fcf9e8; border-left: 3px solid #dba617; color: #7a5c00; }
		.pb-dw-alert-warning a { color: #9a6f00; }
		.pb-dw-alert-info { background: #f0f6fc; border-left: 3px solid #2271b1; color: #1a5276; }
		.pb-dw-alert-info a { color: #135e96; }
		.pb-dw-links { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; padding-top: 12px; border-top: 1px solid #e0e0e0; }
		.pb-dw-link-btn {
			padding: 5px 12px; border-radius: 3px; font-size: 12px;
			text-decoration: none; border: 1px solid #c3c4c7; color: #3c434a;
			background: #fff; transition: background .15s;
		}
		.pb-dw-link-btn:hover { background: #f6f7f7; color: #1d2327; }
		.pb-dw-link-primary { background: #2271b1; border-color: #2271b1; color: #fff !important; }
		.pb-dw-link-primary:hover { background: #135e96; border-color: #135e96; }
		</style>
		<?php
	}
}
