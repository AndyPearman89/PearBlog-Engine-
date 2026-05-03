<?php
/**
 * Automation Tab for Admin Panel v7.0
 *
 * Queue management, scheduling controls, and automation workflows.
 *
 * @package PearBlogEngine\Admin
 * @since 7.6.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\Content\TopicQueue;

/**
 * Automation tab component.
 */
class AutomationTab {

	/**
	 * Render the Automation tab content.
	 */
	public static function render(): void {
		$queue            = new TopicQueue();
		$queue_count      = $queue->count_pending();
		$publish_rate     = get_option( 'pearblog_publish_rate', 1 );
		$auto_publish     = get_option( 'pearblog_auto_publish', true );
		$schedule_enabled = get_option( 'pearblog_schedule_enabled', true );
		$cron_enabled     = get_option( 'pearblog_cron_enabled', true );
		?>
		<div class="pearblog-v7-automation">
			<div class="pearblog-automation-header">
				<h2><?php echo esc_html__( 'Automation & Scheduling', 'pearblog-engine' ); ?></h2>
				<p class="description">
					<?php echo esc_html__( 'Manage content queue, publishing schedule, and automation workflows.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<!-- Queue Status Overview -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Queue Status', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-queue-stats">
					<?php self::render_queue_stats( $queue ); ?>
				</div>
			</div>

			<!-- Publishing Schedule -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Publishing Schedule', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-automation-form">
					<input type="hidden" name="action" value="pearblog_save_schedule" />
					<?php wp_nonce_field( 'pearblog_schedule_settings', 'pearblog_schedule_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_auto_publish"
								value="1"
								<?php checked( $auto_publish ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Auto-Publishing', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Automatically publish content from the queue on schedule', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_publish_rate"><?php echo esc_html__( 'Publishing Rate', 'pearblog-engine' ); ?></label>
						<div class="pearblog-rate-selector">
							<select name="pearblog_publish_rate" id="pearblog_publish_rate" class="pearblog-select">
								<?php for ( $i = 1; $i <= 24; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $publish_rate, $i ); ?>>
										<?php echo esc_html( sprintf( _n( '%d article per hour', '%d articles per hour', $i, 'pearblog-engine' ), $i ) ); ?>
									</option>
								<?php endfor; ?>
							</select>
						</div>
						<p class="description">
							<?php echo esc_html__( 'How many articles to publish from the queue per hour during active hours.', 'pearblog-engine' ); ?>
						</p>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_publish_hours"><?php echo esc_html__( 'Active Publishing Hours', 'pearblog-engine' ); ?></label>
						<div class="pearblog-hours-grid">
							<?php
							$active_hours = get_option( 'pearblog_active_hours', range( 6, 22 ) );
							for ( $hour = 0; $hour < 24; $hour++ ) :
								$hour_label = sprintf( '%02d:00', $hour );
								$is_active  = in_array( $hour, $active_hours, true );
								?>
								<label class="pearblog-hour-checkbox">
									<input
										type="checkbox"
										name="pearblog_active_hours[]"
										value="<?php echo esc_attr( $hour ); ?>"
										<?php checked( $is_active ); ?>
									/>
									<span><?php echo esc_html( $hour_label ); ?></span>
								</label>
							<?php endfor; ?>
						</div>
						<p class="description">
							<?php echo esc_html__( 'Select hours when content should be published. Deselect hours to pause publishing.', 'pearblog-engine' ); ?>
						</p>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_publish_strategy"><?php echo esc_html__( 'Publishing Strategy', 'pearblog-engine' ); ?></label>
						<select name="pearblog_publish_strategy" id="pearblog_publish_strategy" class="pearblog-select">
							<option value="sequential" <?php selected( get_option( 'pearblog_publish_strategy', 'sequential' ), 'sequential' ); ?>>
								<?php echo esc_html__( 'Sequential (oldest first)', 'pearblog-engine' ); ?>
							</option>
							<option value="priority" <?php selected( get_option( 'pearblog_publish_strategy', 'sequential' ), 'priority' ); ?>>
								<?php echo esc_html__( 'Priority-based (high priority first)', 'pearblog-engine' ); ?>
							</option>
							<option value="balanced" <?php selected( get_option( 'pearblog_publish_strategy', 'sequential' ), 'balanced' ); ?>>
								<?php echo esc_html__( 'Balanced (mix of categories)', 'pearblog-engine' ); ?>
							</option>
							<option value="random" <?php selected( get_option( 'pearblog_publish_strategy', 'sequential' ), 'random' ); ?>>
								<?php echo esc_html__( 'Random selection', 'pearblog-engine' ); ?>
							</option>
						</select>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Schedule Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- Topic Queue Management -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Topic Queue', 'pearblog-engine' ); ?></h3>

				<div class="pearblog-queue-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-inline-form">
						<input type="hidden" name="action" value="pearblog_add_topic" />
						<?php wp_nonce_field( 'pearblog_add_topic', 'pearblog_add_topic_nonce' ); ?>
						<input
							type="text"
							name="topic_title"
							placeholder="<?php echo esc_attr__( 'Enter topic or keyword...', 'pearblog-engine' ); ?>"
							class="pearblog-input"
							required
						/>
						<button type="submit" class="pearblog-button pearblog-button-primary">
							<?php echo esc_html__( '+ Add Topic', 'pearblog-engine' ); ?>
						</button>
					</form>

					<div class="pearblog-queue-bulk-actions">
						<button
							type="button"
							class="pearblog-button pearblog-button-secondary"
							onclick="alert('Bulk generation will be implemented');"
						>
							<?php echo esc_html__( 'Generate Topics from Keywords', 'pearblog-engine' ); ?>
						</button>
						<button
							type="button"
							class="pearblog-button pearblog-button-secondary"
							onclick="alert('Bulk delete will be implemented');"
						>
							<?php echo esc_html__( 'Clear Queue', 'pearblog-engine' ); ?>
						</button>
					</div>
				</div>

				<?php self::render_queue_table( $queue ); ?>
			</div>

			<!-- Cron & Background Jobs -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Cron & Background Jobs', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-automation-form">
					<input type="hidden" name="action" value="pearblog_save_cron_settings" />
					<?php wp_nonce_field( 'pearblog_cron_settings', 'pearblog_cron_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_cron_enabled"
								value="1"
								<?php checked( $cron_enabled ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable WordPress Cron', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Use WordPress cron for scheduled tasks (disable if using system cron)', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Cron Settings', 'pearblog-engine' ); ?>
					</button>
				</form>

				<!-- Cron Jobs Status -->
				<div class="pearblog-cron-status">
					<h4><?php echo esc_html__( 'Active Jobs', 'pearblog-engine' ); ?></h4>
					<?php self::render_cron_jobs(); ?>
				</div>
			</div>

			<!-- Automation Workflows -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Automation Workflows', 'pearblog-engine' ); ?></h3>

				<div class="pearblog-workflows-grid">
					<div class="pearblog-workflow-card">
						<div class="pearblog-workflow-header">
							<span class="pearblog-workflow-icon">🔄</span>
							<h4><?php echo esc_html__( 'Auto-Refresh Content', 'pearblog-engine' ); ?></h4>
						</div>
						<p><?php echo esc_html__( 'Automatically update old content with new information and statistics.', 'pearblog-engine' ); ?></p>
						<label class="pearblog-switch">
							<input
								type="checkbox"
								<?php checked( get_option( 'pearblog_auto_refresh', false ) ); ?>
								onchange="alert('Auto-refresh toggle will be implemented');"
							/>
							<span class="pearblog-switch-slider"></span>
						</label>
					</div>

					<div class="pearblog-workflow-card">
						<div class="pearblog-workflow-header">
							<span class="pearblog-workflow-icon">🔗</span>
							<h4><?php echo esc_html__( 'Auto Internal Links', 'pearblog-engine' ); ?></h4>
						</div>
						<p><?php echo esc_html__( 'Automatically add contextual internal links to new and existing content.', 'pearblog-engine' ); ?></p>
						<label class="pearblog-switch">
							<input
								type="checkbox"
								<?php checked( get_option( 'pearblog_internal_links_enabled', true ) ); ?>
								onchange="alert('This is controlled from SEO tab');"
							/>
							<span class="pearblog-switch-slider"></span>
						</label>
					</div>

					<div class="pearblog-workflow-card">
						<div class="pearblog-workflow-header">
							<span class="pearblog-workflow-icon">🖼️</span>
							<h4><?php echo esc_html__( 'Auto Image Generation', 'pearblog-engine' ); ?></h4>
						</div>
						<p><?php echo esc_html__( 'Generate featured images automatically for new articles.', 'pearblog-engine' ); ?></p>
						<label class="pearblog-switch">
							<input
								type="checkbox"
								<?php checked( get_option( 'pearblog_enable_image_generation', true ) ); ?>
								onchange="alert('Image generation toggle will be implemented');"
							/>
							<span class="pearblog-switch-slider"></span>
						</label>
					</div>

					<div class="pearblog-workflow-card">
						<div class="pearblog-workflow-header">
							<span class="pearblog-workflow-icon">📊</span>
							<h4><?php echo esc_html__( 'Auto Analytics Update', 'pearblog-engine' ); ?></h4>
						</div>
						<p><?php echo esc_html__( 'Update article statistics and performance metrics automatically.', 'pearblog-engine' ); ?></p>
						<label class="pearblog-switch">
							<input
								type="checkbox"
								<?php checked( get_option( 'pearblog_auto_analytics', true ) ); ?>
								onchange="alert('Auto analytics toggle will be implemented');"
							/>
							<span class="pearblog-switch-slider"></span>
						</label>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render queue statistics.
	 *
	 * @param TopicQueue $queue Topic queue instance.
	 */
	private static function render_queue_stats( TopicQueue $queue ): void {
		$pending   = $queue->count_pending();
		$completed = $queue->count_completed();
		$failed    = $queue->count_failed();
		$total     = $pending + $completed + $failed;
		?>
		<div class="pearblog-stats-grid">
			<div class="pearblog-stat-box">
				<div class="pearblog-stat-value"><?php echo esc_html( number_format( $pending ) ); ?></div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Pending Topics', 'pearblog-engine' ); ?></div>
			</div>
			<div class="pearblog-stat-box pearblog-stat-success">
				<div class="pearblog-stat-value"><?php echo esc_html( number_format( $completed ) ); ?></div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Completed', 'pearblog-engine' ); ?></div>
			</div>
			<div class="pearblog-stat-box pearblog-stat-warning">
				<div class="pearblog-stat-value"><?php echo esc_html( number_format( $failed ) ); ?></div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Failed', 'pearblog-engine' ); ?></div>
			</div>
			<div class="pearblog-stat-box">
				<div class="pearblog-stat-value"><?php echo esc_html( number_format( $total ) ); ?></div>
				<div class="pearblog-stat-label"><?php echo esc_html__( 'Total', 'pearblog-engine' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render topic queue table.
	 *
	 * @param TopicQueue $queue Topic queue instance.
	 */
	private static function render_queue_table( TopicQueue $queue ): void {
		$topics = $queue->get_pending( 20 );
		?>
		<table class="pearblog-table pearblog-queue-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Topic', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Priority', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Status', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Added', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Actions', 'pearblog-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $topics ) ) : ?>
					<tr>
						<td colspan="5" class="pearblog-table-empty">
							<?php echo esc_html__( 'Queue is empty. Add topics to start generating content.', 'pearblog-engine' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $topics as $topic ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $topic['title'] ?? 'Untitled' ); ?></strong></td>
							<td>
								<span class="pearblog-priority-badge pearblog-priority-<?php echo esc_attr( $topic['priority'] ?? 'normal' ); ?>">
									<?php echo esc_html( ucfirst( $topic['priority'] ?? 'normal' ) ); ?>
								</span>
							</td>
							<td>
								<span class="pearblog-status-badge pearblog-status-<?php echo esc_attr( $topic['status'] ?? 'pending' ); ?>">
									<?php echo esc_html( ucfirst( $topic['status'] ?? 'pending' ) ); ?>
								</span>
							</td>
							<td><?php echo esc_html( isset( $topic['created_at'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $topic['created_at'] ) ) : '-' ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<input type="hidden" name="action" value="pearblog_delete_topic" />
									<input type="hidden" name="topic_id" value="<?php echo esc_attr( $topic['id'] ?? '' ); ?>" />
									<?php wp_nonce_field( 'pearblog_delete_topic', 'pearblog_delete_topic_nonce' ); ?>
									<button
										type="submit"
										class="pearblog-button-link pearblog-button-danger"
										onclick="return confirm('<?php echo esc_js( __( 'Are you sure?', 'pearblog-engine' ) ); ?>');"
									>
										<?php echo esc_html__( 'Delete', 'pearblog-engine' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render cron jobs status.
	 */
	private static function render_cron_jobs(): void {
		$cron_jobs = [
			[
				'name'      => 'Content Publishing',
				'schedule'  => 'Hourly',
				'next_run'  => 'In 23 minutes',
				'status'    => 'active',
			],
			[
				'name'      => 'Content Refresh',
				'schedule'  => 'Daily',
				'next_run'  => 'In 4 hours',
				'status'    => 'active',
			],
			[
				'name'      => 'Analytics Update',
				'schedule'  => 'Every 6 hours',
				'next_run'  => 'In 2 hours',
				'status'    => 'active',
			],
			[
				'name'      => 'Link Checker',
				'schedule'  => 'Weekly',
				'next_run'  => 'In 3 days',
				'status'    => 'active',
			],
		];
		?>
		<table class="pearblog-table pearblog-cron-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Job Name', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Schedule', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Next Run', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Status', 'pearblog-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $cron_jobs as $job ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $job['name'] ); ?></strong></td>
						<td><?php echo esc_html( $job['schedule'] ); ?></td>
						<td><?php echo esc_html( $job['next_run'] ); ?></td>
						<td>
							<span class="pearblog-status-badge pearblog-status-<?php echo esc_attr( $job['status'] ); ?>">
								<?php echo esc_html( ucfirst( $job['status'] ) ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Handle schedule settings form submission.
	 */
	public static function handle_save_schedule(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_schedule_settings', 'pearblog_schedule_nonce' );

		$auto_publish       = isset( $_POST['pearblog_auto_publish'] ) ? 1 : 0;
		$publish_rate       = max( 1, min( 24, absint( $_POST['pearblog_publish_rate'] ?? 1 ) ) );
		$active_hours       = isset( $_POST['pearblog_active_hours'] ) && is_array( $_POST['pearblog_active_hours'] )
			? array_map( 'absint', $_POST['pearblog_active_hours'] )
			: range( 6, 22 );
		$publish_strategy   = sanitize_key( $_POST['pearblog_publish_strategy'] ?? 'sequential' );

		update_option( 'pearblog_auto_publish', $auto_publish );
		update_option( 'pearblog_publish_rate', $publish_rate );
		update_option( 'pearblog_active_hours', $active_hours );
		update_option( 'pearblog_publish_strategy', $publish_strategy );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'automation',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle cron settings form submission.
	 */
	public static function handle_save_cron_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_cron_settings', 'pearblog_cron_nonce' );

		$cron_enabled = isset( $_POST['pearblog_cron_enabled'] ) ? 1 : 0;
		update_option( 'pearblog_cron_enabled', $cron_enabled );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'automation',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle add topic form submission.
	 */
	public static function handle_add_topic(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_add_topic', 'pearblog_add_topic_nonce' );

		$topic_title = sanitize_text_field( $_POST['topic_title'] ?? '' );

		if ( empty( $topic_title ) ) {
			wp_die( esc_html__( 'Topic title is required.', 'pearblog-engine' ) );
		}

		$queue = new TopicQueue();
		$queue->add( $topic_title );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'automation',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle delete topic form submission.
	 */
	public static function handle_delete_topic(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_delete_topic', 'pearblog_delete_topic_nonce' );

		$topic_id = absint( $_POST['topic_id'] ?? 0 );

		if ( empty( $topic_id ) ) {
			wp_die( esc_html__( 'Invalid topic ID.', 'pearblog-engine' ) );
		}

		$queue = new TopicQueue();
		$queue->delete( $topic_id );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'automation',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
