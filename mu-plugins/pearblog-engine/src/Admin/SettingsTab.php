<?php
/**
 * Settings Tab for Admin Panel v7.0
 *
 * Global system configuration and advanced settings.
 *
 * @package PearBlogEngine\Admin
 * @since 7.10.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Settings tab component.
 */
class SettingsTab {

	/**
	 * Render the Settings tab content.
	 */
	public static function render(): void {
		?>
		<div class="pearblog-v7-settings">
			<div class="pearblog-settings-header">
				<h2><?php echo esc_html__( 'Settings & Configuration', 'pearblog-engine' ); ?></h2>
				<p class="description">
					<?php echo esc_html__( 'Global system configuration, API keys, and advanced settings.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<!-- System Information -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'System Information', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-info-grid">
					<?php self::render_system_info(); ?>
				</div>
			</div>

			<!-- General Settings -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'General Settings', 'pearblog-engine' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-settings-form">
					<input type="hidden" name="action" value="pearblog_save_general_settings" />
					<?php wp_nonce_field( 'pearblog_general_settings', 'pearblog_general_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_v7_revenue_enabled"
								value="1"
								<?php checked( get_option( 'pearblog_v7_revenue_enabled', false ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Revenue Tracking', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Enable per-article revenue attribution and tracking', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_debug_mode"
								value="1"
								<?php checked( get_option( 'pearblog_debug_mode', false ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Debug Mode', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Enable detailed logging for troubleshooting (not recommended for production)', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_auto_update"
								value="1"
								<?php checked( get_option( 'pearblog_auto_update', true ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Automatic Updates', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Automatically update PearBlog Engine to the latest version', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save General Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- AI Configuration -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'AI Configuration', 'pearblog-engine' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-settings-form">
					<input type="hidden" name="action" value="pearblog_save_ai_config" />
					<?php wp_nonce_field( 'pearblog_ai_config', 'pearblog_ai_nonce' ); ?>

					<div class="pearblog-form-group">
						<label for="pearblog_default_provider">
							<strong><?php echo esc_html__( 'Default AI Provider', 'pearblog-engine' ); ?></strong>
						</label>
						<select name="pearblog_default_provider" id="pearblog_default_provider" class="pearblog-select">
							<option value="openai" <?php selected( get_option( 'pearblog_default_provider', 'openai' ), 'openai' ); ?>>OpenAI</option>
							<option value="anthropic" <?php selected( get_option( 'pearblog_default_provider', 'openai' ), 'anthropic' ); ?>>Anthropic</option>
							<option value="google" <?php selected( get_option( 'pearblog_default_provider', 'openai' ), 'google' ); ?>>Google AI</option>
							<option value="azure" <?php selected( get_option( 'pearblog_default_provider', 'openai' ), 'azure' ); ?>>Azure OpenAI</option>
						</select>
						<p class="pearblog-description"><?php echo esc_html__( 'Select the default AI provider for content generation', 'pearblog-engine' ); ?></p>
					</div>

					<div class="pearblog-form-group">
						<label for="pearblog_ai_temperature">
							<strong><?php echo esc_html__( 'AI Temperature', 'pearblog-engine' ); ?></strong>
						</label>
						<input
							type="number"
							name="pearblog_ai_temperature"
							id="pearblog_ai_temperature"
							class="pearblog-input"
							min="0"
							max="2"
							step="0.1"
							value="<?php echo esc_attr( get_option( 'pearblog_ai_temperature', '0.7' ) ); ?>"
						/>
						<p class="pearblog-description"><?php echo esc_html__( 'Controls creativity vs consistency (0.0 = consistent, 2.0 = creative)', 'pearblog-engine' ); ?></p>
					</div>

					<div class="pearblog-form-group">
						<label for="pearblog_max_tokens">
							<strong><?php echo esc_html__( 'Max Tokens per Request', 'pearblog-engine' ); ?></strong>
						</label>
						<input
							type="number"
							name="pearblog_max_tokens"
							id="pearblog_max_tokens"
							class="pearblog-input"
							min="100"
							max="16000"
							step="100"
							value="<?php echo esc_attr( get_option( 'pearblog_max_tokens', '4000' ) ); ?>"
						/>
						<p class="pearblog-description"><?php echo esc_html__( 'Maximum number of tokens for AI responses', 'pearblog-engine' ); ?></p>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save AI Configuration', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- Performance Settings -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Performance & Caching', 'pearblog-engine' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-settings-form">
					<input type="hidden" name="action" value="pearblog_save_performance_settings" />
					<?php wp_nonce_field( 'pearblog_performance_settings', 'pearblog_performance_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_cache_enabled"
								value="1"
								<?php checked( get_option( 'pearblog_cache_enabled', true ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Enable Caching', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Cache AI responses and content to reduce API costs', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-group">
						<label for="pearblog_cache_duration">
							<strong><?php echo esc_html__( 'Cache Duration (hours)', 'pearblog-engine' ); ?></strong>
						</label>
						<input
							type="number"
							name="pearblog_cache_duration"
							id="pearblog_cache_duration"
							class="pearblog-input"
							min="1"
							max="168"
							value="<?php echo esc_attr( get_option( 'pearblog_cache_duration', '24' ) ); ?>"
						/>
						<p class="pearblog-description"><?php echo esc_html__( 'How long to cache AI responses (1-168 hours)', 'pearblog-engine' ); ?></p>
					</div>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_lazy_load"
								value="1"
								<?php checked( get_option( 'pearblog_lazy_load', true ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Lazy Load Images', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Improve page load performance by lazy loading images', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Performance Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- Security Settings -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Security & Privacy', 'pearblog-engine' ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-settings-form">
					<input type="hidden" name="action" value="pearblog_save_security_settings" />
					<?php wp_nonce_field( 'pearblog_security_settings', 'pearblog_security_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_2fa_required"
								value="1"
								<?php checked( get_option( 'pearblog_2fa_required', false ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Require 2FA for Admin', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Require two-factor authentication for administrator accounts', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_api_key_encryption"
								value="1"
								<?php checked( get_option( 'pearblog_api_key_encryption', true ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Encrypt API Keys', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Store AI API keys encrypted in database (recommended)', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_audit_log"
								value="1"
								<?php checked( get_option( 'pearblog_audit_log', false ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Audit Logging', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Log all admin actions for security audit trail', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Security Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>

			<!-- Data Management -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Data Management', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-data-actions">
					<div class="pearblog-action-card">
						<div class="pearblog-action-icon">📥</div>
						<h4><?php echo esc_html__( 'Export Data', 'pearblog-engine' ); ?></h4>
						<p><?php echo esc_html__( 'Export all PearBlog settings and configurations', 'pearblog-engine' ); ?></p>
						<button type="button" class="pearblog-button pearblog-button-secondary" onclick="alert('Export feature will be implemented')">
							<?php echo esc_html__( 'Export Settings', 'pearblog-engine' ); ?>
						</button>
					</div>

					<div class="pearblog-action-card">
						<div class="pearblog-action-icon">📤</div>
						<h4><?php echo esc_html__( 'Import Data', 'pearblog-engine' ); ?></h4>
						<p><?php echo esc_html__( 'Import settings from another PearBlog installation', 'pearblog-engine' ); ?></p>
						<button type="button" class="pearblog-button pearblog-button-secondary" onclick="alert('Import feature will be implemented')">
							<?php echo esc_html__( 'Import Settings', 'pearblog-engine' ); ?>
						</button>
					</div>

					<div class="pearblog-action-card">
						<div class="pearblog-action-icon">🗑️</div>
						<h4><?php echo esc_html__( 'Clear Cache', 'pearblog-engine' ); ?></h4>
						<p><?php echo esc_html__( 'Clear all cached AI responses and data', 'pearblog-engine' ); ?></p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="pearblog_clear_cache" />
							<?php wp_nonce_field( 'pearblog_clear_cache', 'pearblog_cache_nonce' ); ?>
							<button type="submit" class="pearblog-button pearblog-button-danger">
								<?php echo esc_html__( 'Clear Cache', 'pearblog-engine' ); ?>
							</button>
						</form>
					</div>

					<div class="pearblog-action-card pearblog-action-card-danger">
						<div class="pearblog-action-icon">⚠️</div>
						<h4><?php echo esc_html__( 'Reset to Defaults', 'pearblog-engine' ); ?></h4>
						<p><?php echo esc_html__( 'Reset all PearBlog settings to default values', 'pearblog-engine' ); ?></p>
						<button type="button" class="pearblog-button pearblog-button-danger" onclick="if(confirm('<?php echo esc_js( __( 'Are you sure? This will reset all settings.', 'pearblog-engine' ) ); ?>')) alert('Reset feature will be implemented')">
							<?php echo esc_html__( 'Reset Settings', 'pearblog-engine' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Version Information -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Version & Support', 'pearblog-engine' ); ?></h3>
				<div class="pearblog-version-info">
					<div class="pearblog-version-card">
						<strong><?php echo esc_html__( 'Admin Interface', 'pearblog-engine' ); ?></strong>
						<span class="pearblog-version-badge">v7.0 (SaaS Control Center)</span>
					</div>
					<div class="pearblog-version-card">
						<strong><?php echo esc_html__( 'PearBlog Engine', 'pearblog-engine' ); ?></strong>
						<span class="pearblog-version-badge">v7.10.0</span>
					</div>
					<div class="pearblog-version-card">
						<strong><?php echo esc_html__( 'WordPress Version', 'pearblog-engine' ); ?></strong>
						<span class="pearblog-version-badge"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
					</div>
					<div class="pearblog-version-card">
						<strong><?php echo esc_html__( 'PHP Version', 'pearblog-engine' ); ?></strong>
						<span class="pearblog-version-badge"><?php echo esc_html( phpversion() ); ?></span>
					</div>
				</div>
				<p class="description" style="margin-top: 20px;">
					<?php echo esc_html__( 'To switch back to v6 admin interface, add define(\'PEARBLOG_ADMIN_VERSION\', \'v6\'); to wp-config.php', 'pearblog-engine' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render system information cards.
	 */
	private static function render_system_info(): void {
		$memory_limit = ini_get( 'memory_limit' );
		$max_execution = ini_get( 'max_execution_time' );
		$upload_max = ini_get( 'upload_max_filesize' );
		$is_https = is_ssl() ? __( 'Yes', 'pearblog-engine' ) : __( 'No', 'pearblog-engine' );

		$info_items = [
			[ 'label' => __( 'Memory Limit', 'pearblog-engine' ), 'value' => $memory_limit ],
			[ 'label' => __( 'Max Execution Time', 'pearblog-engine' ), 'value' => $max_execution . 's' ],
			[ 'label' => __( 'Upload Max Size', 'pearblog-engine' ), 'value' => $upload_max ],
			[ 'label' => __( 'HTTPS Enabled', 'pearblog-engine' ), 'value' => $is_https ],
		];
		?>
		<?php foreach ( $info_items as $item ) : ?>
			<div class="pearblog-info-card">
				<div class="pearblog-info-label"><?php echo esc_html( $item['label'] ); ?></div>
				<div class="pearblog-info-value"><?php echo esc_html( $item['value'] ); ?></div>
			</div>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Handle general settings form submission.
	 */
	public static function handle_save_general_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_general_settings', 'pearblog_general_nonce' );

		$revenue_enabled = isset( $_POST['pearblog_v7_revenue_enabled'] ) ? 1 : 0;
		$debug_mode      = isset( $_POST['pearblog_debug_mode'] ) ? 1 : 0;
		$auto_update     = isset( $_POST['pearblog_auto_update'] ) ? 1 : 0;

		update_option( 'pearblog_v7_revenue_enabled', $revenue_enabled );
		update_option( 'pearblog_debug_mode', $debug_mode );
		update_option( 'pearblog_auto_update', $auto_update );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'settings',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle AI configuration form submission.
	 */
	public static function handle_save_ai_config(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_ai_config', 'pearblog_ai_nonce' );

		$default_provider = sanitize_text_field( $_POST['pearblog_default_provider'] ?? 'openai' );
		$ai_temperature   = floatval( $_POST['pearblog_ai_temperature'] ?? 0.7 );
		$max_tokens       = intval( $_POST['pearblog_max_tokens'] ?? 4000 );

		update_option( 'pearblog_default_provider', $default_provider );
		update_option( 'pearblog_ai_temperature', $ai_temperature );
		update_option( 'pearblog_max_tokens', $max_tokens );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'settings',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle performance settings form submission.
	 */
	public static function handle_save_performance_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_performance_settings', 'pearblog_performance_nonce' );

		$cache_enabled  = isset( $_POST['pearblog_cache_enabled'] ) ? 1 : 0;
		$cache_duration = intval( $_POST['pearblog_cache_duration'] ?? 24 );
		$lazy_load      = isset( $_POST['pearblog_lazy_load'] ) ? 1 : 0;

		update_option( 'pearblog_cache_enabled', $cache_enabled );
		update_option( 'pearblog_cache_duration', $cache_duration );
		update_option( 'pearblog_lazy_load', $lazy_load );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'settings',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle security settings form submission.
	 */
	public static function handle_save_security_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_security_settings', 'pearblog_security_nonce' );

		$tfa_required         = isset( $_POST['pearblog_2fa_required'] ) ? 1 : 0;
		$api_key_encryption   = isset( $_POST['pearblog_api_key_encryption'] ) ? 1 : 0;
		$audit_log            = isset( $_POST['pearblog_audit_log'] ) ? 1 : 0;

		update_option( 'pearblog_2fa_required', $tfa_required );
		update_option( 'pearblog_api_key_encryption', $api_key_encryption );
		update_option( 'pearblog_audit_log', $audit_log );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'settings',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle clear cache action.
	 */
	public static function handle_clear_cache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_clear_cache', 'pearblog_cache_nonce' );

		// Clear WordPress transients
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pearblog_%' OR option_name LIKE '_transient_timeout_pearblog_%'" );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'settings',
					'updated' => 'cache_cleared',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
