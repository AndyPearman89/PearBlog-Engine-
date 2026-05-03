<?php
/**
 * Leads & Expert Management Tab for Admin Panel v7.0
 *
 * Lead capture forms, lead management, and expert routing configuration.
 *
 * @package PearBlogEngine\Admin
 * @since 7.4.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Leads & Expert Management tab component.
 */
class LeadsTab {

	/**
	 * Render the Leads tab content.
	 */
	public static function render(): void {
		$leads_enabled   = get_option( 'pearblog_leads_enabled', false );
		$experts_enabled = get_option( 'pearblog_experts_enabled', false );
		$leads_count     = self::count_leads();
		$experts_count   = self::count_experts();
		?>
		<div class="pearblog-v7-leads">
			<div class="pearblog-leads-header">
				<h2><?php echo esc_html__( 'Leads & Expert Management', 'pearblog-engine' ); ?></h2>
				<p class="description">
					<?php echo esc_html__( 'Capture leads from your content and route them to domain experts for conversion.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<!-- Lead Capture Configuration -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Lead Capture Configuration', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-leads-form">
					<input type="hidden" name="action" value="pearblog_save_leads_config" />
					<?php wp_nonce_field( 'pearblog_leads_config', 'pearblog_leads_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_leads_enabled"
								value="1"
								<?php checked( $leads_enabled ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Lead Capture', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Display lead capture forms on content pages', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_leads_placement"><?php echo esc_html__( 'Form Placement', 'pearblog-engine' ); ?></label>
						<select name="pearblog_leads_placement" id="pearblog_leads_placement" class="pearblog-select">
							<option value="before_content" <?php selected( get_option( 'pearblog_leads_placement', 'after_content' ), 'before_content' ); ?>>
								<?php echo esc_html__( 'Before Content', 'pearblog-engine' ); ?>
							</option>
							<option value="after_content" <?php selected( get_option( 'pearblog_leads_placement', 'after_content' ), 'after_content' ); ?>>
								<?php echo esc_html__( 'After Content', 'pearblog-engine' ); ?>
							</option>
							<option value="sidebar" <?php selected( get_option( 'pearblog_leads_placement', 'after_content' ), 'sidebar' ); ?>>
								<?php echo esc_html__( 'Sidebar Widget', 'pearblog-engine' ); ?>
							</option>
							<option value="popup" <?php selected( get_option( 'pearblog_leads_placement', 'after_content' ), 'popup' ); ?>>
								<?php echo esc_html__( 'Exit Intent Popup', 'pearblog-engine' ); ?>
							</option>
						</select>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_leads_heading"><?php echo esc_html__( 'Form Heading', 'pearblog-engine' ); ?></label>
						<input
							type="text"
							name="pearblog_leads_heading"
							id="pearblog_leads_heading"
							value="<?php echo esc_attr( get_option( 'pearblog_leads_heading', 'Get Expert Help' ) ); ?>"
							class="pearblog-input"
						/>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_leads_description"><?php echo esc_html__( 'Form Description', 'pearblog-engine' ); ?></label>
						<textarea
							name="pearblog_leads_description"
							id="pearblog_leads_description"
							rows="3"
							class="pearblog-textarea"
						><?php echo esc_textarea( get_option( 'pearblog_leads_description', 'Connect with our experts for personalized advice.' ) ); ?></textarea>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_leads_fields"><?php echo esc_html__( 'Required Fields', 'pearblog-engine' ); ?></label>
						<?php
						$selected_fields = get_option( 'pearblog_leads_fields', [ 'name', 'email' ] );
						$available_fields = [
							'name'    => __( 'Full Name', 'pearblog-engine' ),
							'email'   => __( 'Email Address', 'pearblog-engine' ),
							'phone'   => __( 'Phone Number', 'pearblog-engine' ),
							'company' => __( 'Company Name', 'pearblog-engine' ),
							'message' => __( 'Message/Question', 'pearblog-engine' ),
						];

						foreach ( $available_fields as $field_key => $field_label ) :
							?>
							<label class="pearblog-checkbox-label">
								<input
									type="checkbox"
									name="pearblog_leads_fields[]"
									value="<?php echo esc_attr( $field_key ); ?>"
									<?php checked( in_array( $field_key, $selected_fields, true ) ); ?>
								/>
								<?php echo esc_html( $field_label ); ?>
							</label>
						<?php endforeach; ?>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Lead Capture Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- Expert Routing Configuration -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Expert Routing', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-leads-form">
					<input type="hidden" name="action" value="pearblog_save_expert_routing" />
					<?php wp_nonce_field( 'pearblog_expert_routing', 'pearblog_expert_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_experts_enabled"
								value="1"
								<?php checked( $experts_enabled ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Expert Routing', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Automatically route leads to domain experts based on content category', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label for="pearblog_default_expert_email"><?php echo esc_html__( 'Default Expert Email', 'pearblog-engine' ); ?></label>
						<input
							type="email"
							name="pearblog_default_expert_email"
							id="pearblog_default_expert_email"
							value="<?php echo esc_attr( get_option( 'pearblog_default_expert_email', get_option( 'admin_email' ) ) ); ?>"
							class="pearblog-input"
						/>
						<p class="description">
							<?php echo esc_html__( 'Fallback email for leads when no category-specific expert is configured.', 'pearblog-engine' ); ?>
						</p>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Expert Routing Settings', 'pearblog-engine' ); ?>
					</button>
				</form>

				<!-- Expert Management Table -->
				<div class="pearblog-experts-list">
					<h4><?php echo esc_html__( 'Category Experts', 'pearblog-engine' ); ?></h4>
					<?php self::render_experts_table(); ?>

					<button
						type="button"
						class="pearblog-button pearblog-button-secondary"
						onclick="document.getElementById('add-expert-form').style.display='block';"
					>
						<?php echo esc_html__( '+ Add Expert', 'pearblog-engine' ); ?>
					</button>
				</div>

				<!-- Add Expert Form (initially hidden) -->
				<div id="add-expert-form" class="pearblog-add-expert-form" style="display:none;">
					<h4><?php echo esc_html__( 'Add New Expert', 'pearblog-engine' ); ?></h4>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="pearblog_add_expert" />
						<?php wp_nonce_field( 'pearblog_add_expert', 'pearblog_add_expert_nonce' ); ?>

						<div class="pearblog-form-row">
							<div class="pearblog-form-col">
								<label for="expert_name"><?php echo esc_html__( 'Expert Name', 'pearblog-engine' ); ?></label>
								<input type="text" name="expert_name" id="expert_name" required class="pearblog-input" />
							</div>
							<div class="pearblog-form-col">
								<label for="expert_email"><?php echo esc_html__( 'Email', 'pearblog-engine' ); ?></label>
								<input type="email" name="expert_email" id="expert_email" required class="pearblog-input" />
							</div>
						</div>

						<div class="pearblog-form-row">
							<div class="pearblog-form-col">
								<label for="expert_category"><?php echo esc_html__( 'Category', 'pearblog-engine' ); ?></label>
								<select name="expert_category" id="expert_category" required class="pearblog-select">
									<option value=""><?php echo esc_html__( 'Select Category', 'pearblog-engine' ); ?></option>
									<?php
									$categories = get_categories( [ 'hide_empty' => false ] );
									foreach ( $categories as $category ) {
										echo '<option value="' . esc_attr( $category->term_id ) . '">' . esc_html( $category->name ) . '</option>';
									}
									?>
								</select>
							</div>
							<div class="pearblog-form-col">
								<label for="expert_phone"><?php echo esc_html__( 'Phone (Optional)', 'pearblog-engine' ); ?></label>
								<input type="tel" name="expert_phone" id="expert_phone" class="pearblog-input" />
							</div>
						</div>

						<div class="pearblog-form-actions">
							<button type="submit" class="pearblog-button pearblog-button-primary">
								<?php echo esc_html__( 'Add Expert', 'pearblog-engine' ); ?>
							</button>
							<button
								type="button"
								class="pearblog-button pearblog-button-secondary"
								onclick="document.getElementById('add-expert-form').style.display='none';"
							>
								<?php echo esc_html__( 'Cancel', 'pearblog-engine' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>

			<!-- Lead Management Dashboard -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Recent Leads', 'pearblog-engine' ); ?></h3>

				<div class="pearblog-leads-stats">
					<div class="pearblog-stat-card">
						<div class="pearblog-stat-value"><?php echo esc_html( number_format( $leads_count['total'] ) ); ?></div>
						<div class="pearblog-stat-label"><?php echo esc_html__( 'Total Leads', 'pearblog-engine' ); ?></div>
					</div>
					<div class="pearblog-stat-card">
						<div class="pearblog-stat-value"><?php echo esc_html( number_format( $leads_count['today'] ) ); ?></div>
						<div class="pearblog-stat-label"><?php echo esc_html__( 'Today', 'pearblog-engine' ); ?></div>
					</div>
					<div class="pearblog-stat-card">
						<div class="pearblog-stat-value"><?php echo esc_html( number_format( $leads_count['week'] ) ); ?></div>
						<div class="pearblog-stat-label"><?php echo esc_html__( 'This Week', 'pearblog-engine' ); ?></div>
					</div>
					<div class="pearblog-stat-card">
						<div class="pearblog-stat-value"><?php echo esc_html( number_format( $leads_count['month'] ) ); ?></div>
						<div class="pearblog-stat-label"><?php echo esc_html__( 'This Month', 'pearblog-engine' ); ?></div>
					</div>
				</div>

				<?php self::render_leads_table(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the experts management table.
	 */
	private static function render_experts_table(): void {
		$experts = get_option( 'pearblog_category_experts', [] );
		?>
		<table class="pearblog-table pearblog-experts-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Expert Name', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Email', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Category', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Phone', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Leads', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Actions', 'pearblog-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $experts ) ) : ?>
					<tr>
						<td colspan="6" class="pearblog-table-empty">
							<?php echo esc_html__( 'No experts configured. Add your first expert to start routing leads.', 'pearblog-engine' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $experts as $expert_id => $expert ) : ?>
						<?php
						$category     = get_category( $expert['category_id'] );
						$category_name = $category ? $category->name : __( 'Unknown', 'pearblog-engine' );
						$leads_count  = self::count_expert_leads( $expert_id );
						?>
						<tr>
							<td><?php echo esc_html( $expert['name'] ); ?></td>
							<td><?php echo esc_html( $expert['email'] ); ?></td>
							<td><?php echo esc_html( $category_name ); ?></td>
							<td><?php echo esc_html( $expert['phone'] ?? '-' ); ?></td>
							<td><?php echo esc_html( number_format( $leads_count ) ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<input type="hidden" name="action" value="pearblog_delete_expert" />
									<input type="hidden" name="expert_id" value="<?php echo esc_attr( $expert_id ); ?>" />
									<?php wp_nonce_field( 'pearblog_delete_expert', 'pearblog_delete_expert_nonce' ); ?>
									<button
										type="submit"
										class="pearblog-button-link pearblog-button-danger"
										onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this expert?', 'pearblog-engine' ) ); ?>');"
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
	 * Render the leads management table.
	 */
	private static function render_leads_table(): void {
		$leads = self::get_recent_leads( 10 );
		?>
		<table class="pearblog-table pearblog-leads-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Date', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Name', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Email', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Phone', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Source', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Expert', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Status', 'pearblog-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $leads ) ) : ?>
					<tr>
						<td colspan="7" class="pearblog-table-empty">
							<?php echo esc_html__( 'No leads captured yet. Enable lead capture to start collecting leads.', 'pearblog-engine' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $leads as $lead ) : ?>
						<tr>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $lead['created_at'] ) ) ); ?></td>
							<td><?php echo esc_html( $lead['name'] ); ?></td>
							<td><?php echo esc_html( $lead['email'] ); ?></td>
							<td><?php echo esc_html( $lead['phone'] ?? '-' ); ?></td>
							<td>
								<?php if ( ! empty( $lead['post_id'] ) ) : ?>
									<a href="<?php echo esc_url( get_permalink( $lead['post_id'] ) ); ?>" target="_blank">
										<?php echo esc_html( get_the_title( $lead['post_id'] ) ); ?>
									</a>
								<?php else : ?>
									-
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $lead['expert_name'] ?? '-' ); ?></td>
							<td>
								<span class="pearblog-status-badge pearblog-status-<?php echo esc_attr( $lead['status'] ?? 'new' ); ?>">
									<?php echo esc_html( ucfirst( $lead['status'] ?? 'new' ) ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Count leads with date filters.
	 *
	 * @return array Lead counts by period.
	 */
	private static function count_leads(): array {
		$leads = get_option( 'pearblog_leads', [] );

		$total = count( $leads );
		$today = 0;
		$week  = 0;
		$month = 0;

		$today_start = strtotime( 'today' );
		$week_start  = strtotime( '-7 days' );
		$month_start = strtotime( '-30 days' );

		foreach ( $leads as $lead ) {
			$lead_time = strtotime( $lead['created_at'] );
			if ( $lead_time >= $today_start ) {
				$today++;
			}
			if ( $lead_time >= $week_start ) {
				$week++;
			}
			if ( $lead_time >= $month_start ) {
				$month++;
			}
		}

		return [
			'total' => $total,
			'today' => $today,
			'week'  => $week,
			'month' => $month,
		];
	}

	/**
	 * Count leads assigned to a specific expert.
	 *
	 * @param string $expert_id Expert identifier.
	 * @return int Number of leads.
	 */
	private static function count_expert_leads( string $expert_id ): int {
		$leads = get_option( 'pearblog_leads', [] );
		$count = 0;

		foreach ( $leads as $lead ) {
			if ( isset( $lead['expert_id'] ) && $lead['expert_id'] === $expert_id ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get recent leads.
	 *
	 * @param int $limit Maximum number of leads to return.
	 * @return array Recent leads.
	 */
	private static function get_recent_leads( int $limit = 10 ): array {
		$leads = get_option( 'pearblog_leads', [] );

		// Sort by created_at descending
		usort( $leads, function ( $a, $b ) {
			return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
		} );

		return array_slice( $leads, 0, $limit );
	}

	/**
	 * Handle lead capture configuration form submission.
	 */
	public static function handle_save_leads_config(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_leads_config', 'pearblog_leads_nonce' );

		$leads_enabled      = isset( $_POST['pearblog_leads_enabled'] ) ? 1 : 0;
		$leads_placement    = sanitize_text_field( $_POST['pearblog_leads_placement'] ?? 'after_content' );
		$leads_heading      = sanitize_text_field( $_POST['pearblog_leads_heading'] ?? 'Get Expert Help' );
		$leads_description  = sanitize_textarea_field( $_POST['pearblog_leads_description'] ?? '' );
		$leads_fields       = isset( $_POST['pearblog_leads_fields'] ) && is_array( $_POST['pearblog_leads_fields'] )
			? array_map( 'sanitize_key', $_POST['pearblog_leads_fields'] )
			: [ 'name', 'email' ];

		update_option( 'pearblog_leads_enabled', $leads_enabled );
		update_option( 'pearblog_leads_placement', $leads_placement );
		update_option( 'pearblog_leads_heading', $leads_heading );
		update_option( 'pearblog_leads_description', $leads_description );
		update_option( 'pearblog_leads_fields', $leads_fields );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'leads',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle expert routing configuration form submission.
	 */
	public static function handle_save_expert_routing(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_expert_routing', 'pearblog_expert_nonce' );

		$experts_enabled      = isset( $_POST['pearblog_experts_enabled'] ) ? 1 : 0;
		$default_expert_email = sanitize_email( $_POST['pearblog_default_expert_email'] ?? get_option( 'admin_email' ) );

		update_option( 'pearblog_experts_enabled', $experts_enabled );
		update_option( 'pearblog_default_expert_email', $default_expert_email );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'leads',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle add expert form submission.
	 */
	public static function handle_add_expert(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_add_expert', 'pearblog_add_expert_nonce' );

		$expert_name     = sanitize_text_field( $_POST['expert_name'] ?? '' );
		$expert_email    = sanitize_email( $_POST['expert_email'] ?? '' );
		$expert_category = absint( $_POST['expert_category'] ?? 0 );
		$expert_phone    = sanitize_text_field( $_POST['expert_phone'] ?? '' );

		if ( empty( $expert_name ) || empty( $expert_email ) || empty( $expert_category ) ) {
			wp_die( esc_html__( 'All required fields must be filled.', 'pearblog-engine' ) );
		}

		$experts = get_option( 'pearblog_category_experts', [] );
		$expert_id = uniqid( 'expert_', true );

		$experts[ $expert_id ] = [
			'name'        => $expert_name,
			'email'       => $expert_email,
			'category_id' => $expert_category,
			'phone'       => $expert_phone,
			'created_at'  => current_time( 'mysql' ),
		];

		update_option( 'pearblog_category_experts', $experts );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'leads',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle delete expert form submission.
	 */
	public static function handle_delete_expert(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_delete_expert', 'pearblog_delete_expert_nonce' );

		$expert_id = sanitize_text_field( $_POST['expert_id'] ?? '' );

		if ( empty( $expert_id ) ) {
			wp_die( esc_html__( 'Invalid expert ID.', 'pearblog-engine' ) );
		}

		$experts = get_option( 'pearblog_category_experts', [] );
		if ( isset( $experts[ $expert_id ] ) ) {
			unset( $experts[ $expert_id ] );
			update_option( 'pearblog_category_experts', $experts );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'leads',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
