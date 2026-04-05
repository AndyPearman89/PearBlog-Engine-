<?php
/**
 * Admin Dashboard Widget – PearBlog Engine status overview.
 *
 * Shows queue size, posts published today, AI images generated,
 * posts with missing alt texts, and last pipeline run timestamp.
 *
 * @package PearBlogEngine\Admin
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\Content\TopicQueue;

/**
 * Registers the PearBlog Engine status widget on the WP admin dashboard.
 */
class DashboardWidget {

	private const WIDGET_ID = 'pearblog_engine_status';

	/**
	 * Attach WordPress hooks.
	 */
	public function register(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'add_widget' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Register the dashboard widget.
	 */
	public function add_widget(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			self::WIDGET_ID,
			__( 'PearBlog Engine Status', 'pearblog-engine' ),
			[ $this, 'render' ]
		);
	}

	/**
	 * Enqueue widget styles (scoped to the dashboard page).
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_styles( string $hook ): void {
		if ( 'index.php' !== $hook ) {
			return;
		}

		wp_add_inline_style( 'dashboard', '
			#pearblog_engine_status .inside { padding: 0; }
			.pb-dw-grid {
				display: grid;
				grid-template-columns: repeat(2, 1fr);
				gap: 1px;
				background: #f0f0f1;
				border-top: 1px solid #f0f0f1;
			}
			.pb-dw-stat {
				background: #fff;
				padding: 14px 16px;
				display: flex;
				flex-direction: column;
			}
			.pb-dw-number {
				font-size: 22px;
				font-weight: 700;
				color: #2563eb;
				line-height: 1.2;
			}
			.pb-dw-number--green { color: #00a32a; }
			.pb-dw-number--amber { color: #dba617; }
			.pb-dw-number--red   { color: #d63638; }
			.pb-dw-label {
				font-size: 11px;
				color: #646970;
				text-transform: uppercase;
				letter-spacing: 0.4px;
				margin-top: 2px;
			}
			.pb-dw-footer {
				padding: 10px 16px;
				font-size: 12px;
				color: #646970;
				border-top: 1px solid #f0f0f1;
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			.pb-dw-footer a { font-weight: 600; }
		' );
	}

	/**
	 * Render the widget content.
	 */
	public function render(): void {
		$blog_id    = get_current_blog_id();
		$queue      = new TopicQueue( $blog_id );
		$queue_size = $queue->count();

		// Posts published today.
		$today_posts = (int) ( new \WP_Query( [
			'post_status' => 'publish',
			'date_query'  => [ [ 'after' => 'today midnight' ] ],
			'fields'      => 'ids',
			'no_found_rows' => false,
		] ) )->found_posts;

		// AI-generated images (posts with _pearblog_ai_image meta).
		global $wpdb;
		$ai_images = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id)
			 FROM {$wpdb->postmeta}
			 WHERE meta_key = '_pearblog_ai_image'
			   AND meta_value = '1'"
		);

		// Posts missing featured image alt text.
		$missing_alts = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE p.post_status = 'publish'
			   AND p.post_type   = 'post'
			   AND pm.meta_key   = '_thumbnail_id'
			   AND NOT EXISTS (
			       SELECT 1 FROM {$wpdb->postmeta} alt
			       WHERE alt.post_id  = pm.meta_value
			         AND alt.meta_key = %s
			         AND alt.meta_value != ''
			   )",
			'_wp_attachment_image_alt'
		) );

		// Last pipeline run timestamp.
		$last_run     = get_option( 'pearblog_last_pipeline_run', 0 );
		$last_run_str = $last_run
			? sprintf(
				/* translators: %s: human-readable time ago */
				__( '%s ago', 'pearblog-engine' ),
				human_time_diff( (int) $last_run )
			)
			: __( 'Never', 'pearblog-engine' );

		// Color-code missing alts: green = 0, amber = 1-5, red = 6+.
		$alts_class = '';
		if ( $missing_alts === 0 ) {
			$alts_class = 'pb-dw-number--green';
		} elseif ( $missing_alts <= 5 ) {
			$alts_class = 'pb-dw-number--amber';
		} else {
			$alts_class = 'pb-dw-number--red';
		}

		?>
		<div class="pb-dw-grid">
			<div class="pb-dw-stat">
				<span class="pb-dw-number"><?php echo esc_html( $queue_size ); ?></span>
				<span class="pb-dw-label"><?php esc_html_e( 'Queue', 'pearblog-engine' ); ?></span>
			</div>
			<div class="pb-dw-stat">
				<span class="pb-dw-number pb-dw-number--green"><?php echo esc_html( $today_posts ); ?></span>
				<span class="pb-dw-label"><?php esc_html_e( 'Published Today', 'pearblog-engine' ); ?></span>
			</div>
			<div class="pb-dw-stat">
				<span class="pb-dw-number"><?php echo esc_html( $ai_images ); ?></span>
				<span class="pb-dw-label"><?php esc_html_e( 'AI Images', 'pearblog-engine' ); ?></span>
			</div>
			<div class="pb-dw-stat">
				<span class="pb-dw-number <?php echo esc_attr( $alts_class ); ?>"><?php echo esc_html( $missing_alts ); ?></span>
				<span class="pb-dw-label"><?php esc_html_e( 'Missing Alt Texts', 'pearblog-engine' ); ?></span>
			</div>
		</div>
		<div class="pb-dw-footer">
			<span><?php
				printf(
					/* translators: %s: time string */
					esc_html__( 'Last pipeline run: %s', 'pearblog-engine' ),
					esc_html( $last_run_str )
				);
			?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pearblog-engine' ) ); ?>">
				<?php esc_html_e( 'Engine →', 'pearblog-engine' ); ?>
			</a>
		</div>
		<?php
	}
}
