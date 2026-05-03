<?php
/**
 * Multisite/SaaS Tab for Admin Panel v7.0
 *
 * Multi-tenant management and SaaS control center.
 *
 * @package PearBlogEngine\Admin
 * @since 7.9.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

/**
 * Multisite/SaaS tab component.
 */
class MultisiteTab {

	/**
	 * Render the Multisite/SaaS tab content.
	 */
	public static function render(): void {
		$is_multisite = is_multisite();
		?>
		<div class="pearblog-v7-multisite">
			<div class="pearblog-multisite-header">
				<h2><?php echo esc_html__( 'Multisite / SaaS Control', 'pearblog-engine' ); ?></h2>
				<p class="description">
					<?php echo esc_html__( 'Manage multiple sites from a central dashboard with enterprise SaaS features.', 'pearblog-engine' ); ?>
				</p>
			</div>

			<?php if ( ! $is_multisite ) : ?>
				<!-- Multisite Not Enabled -->
				<div class="pearblog-notice pearblog-notice-warning">
					<p><strong><?php echo esc_html__( 'WordPress Multisite Not Enabled', 'pearblog-engine' ); ?></strong></p>
					<p><?php echo esc_html__( 'To use multi-tenant features, enable WordPress Multisite in wp-config.php', 'pearblog-engine' ); ?></p>
				</div>

				<div class="pearblog-section">
					<h3><?php echo esc_html__( 'Enable Multisite', 'pearblog-engine' ); ?></h3>
					<div class="pearblog-code-block">
						<code>define('WP_ALLOW_MULTISITE', true);</code>
					</div>
					<p class="description">
						<?php echo esc_html__( 'Add this line to your wp-config.php file to enable WordPress Multisite.', 'pearblog-engine' ); ?>
					</p>
				</div>
			<?php else : ?>
				<!-- Network Overview -->
				<div class="pearblog-section">
					<h3><?php echo esc_html__( 'Network Overview', 'pearblog-engine' ); ?></h3>
					<div class="pearblog-network-stats">
						<?php self::render_network_stats(); ?>
					</div>
				</div>

				<!-- Sites Management -->
				<div class="pearblog-section">
					<h3><?php echo esc_html__( 'Sites Management', 'pearblog-engine' ); ?></h3>
					<?php self::render_sites_table(); ?>
				</div>

				<!-- SaaS Features -->
				<div class="pearblog-section">
					<h3><?php echo esc_html__( 'SaaS Features', 'pearblog-engine' ); ?></h3>
					<div class="pearblog-saas-features-grid">
						<?php self::render_saas_features(); ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Centralized Settings -->
			<div class="pearblog-section">
				<h3><?php echo esc_html__( 'Centralized Configuration', 'pearblog-engine' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pearblog-multisite-form">
					<input type="hidden" name="action" value="pearblog_save_multisite_settings" />
					<?php wp_nonce_field( 'pearblog_multisite_settings', 'pearblog_multisite_nonce' ); ?>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_centralized_api_keys"
								value="1"
								<?php checked( get_site_option( 'pearblog_centralized_api_keys', false ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Centralized API Keys', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Share AI API keys across all sites in the network', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_network_billing"
								value="1"
								<?php checked( get_site_option( 'pearblog_network_billing', false ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Network Billing', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Unified billing for all sites in the network', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<div class="pearblog-form-section">
						<label class="pearblog-toggle-label">
							<input
								type="checkbox"
								name="pearblog_cross_site_analytics"
								value="1"
								<?php checked( get_site_option( 'pearblog_cross_site_analytics', false ) ); ?>
							/>
							<span class="pearblog-toggle-text">
								<strong><?php echo esc_html__( 'Cross-Site Analytics', 'pearblog-engine' ); ?></strong>
								<span class="pearblog-toggle-description"><?php echo esc_html__( 'Aggregate analytics across all network sites', 'pearblog-engine' ); ?></span>
							</span>
						</label>
					</div>

					<button type="submit" class="pearblog-button pearblog-button-primary">
						<?php echo esc_html__( 'Save Settings', 'pearblog-engine' ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render network statistics.
	 */
	private static function render_network_stats(): void {
		// Mock data - would be replaced with real network stats
		$stats = [
			[ 'icon' => '🌐', 'value' => '12', 'label' => 'Active Sites' ],
			[ 'icon' => '📝', 'value' => '4,234', 'label' => 'Total Posts' ],
			[ 'icon' => '👥', 'value' => '234', 'label' => 'Network Users' ],
			[ 'icon' => '💰', 'value' => '$12.5K', 'label' => 'Network Revenue' ],
		];
		?>
		<div class="pearblog-stats-grid">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="pearblog-stat-box">
					<div class="pearblog-stat-icon"><?php echo esc_html( $stat['icon'] ); ?></div>
					<div class="pearblog-stat-value"><?php echo esc_html( $stat['value'] ); ?></div>
					<div class="pearblog-stat-label"><?php echo esc_html( $stat['label'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render sites management table.
	 */
	private static function render_sites_table(): void {
		// Mock data - would be replaced with real sites data
		$sites = [
			[ 'name' => 'Main Site', 'url' => 'example.com', 'posts' => 234, 'status' => 'active' ],
			[ 'name' => 'Blog Site', 'url' => 'blog.example.com', 'posts' => 456, 'status' => 'active' ],
			[ 'name' => 'News Site', 'url' => 'news.example.com', 'posts' => 789, 'status' => 'active' ],
		];
		?>
		<table class="pearblog-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Site Name', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'URL', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Posts', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Status', 'pearblog-engine' ); ?></th>
					<th><?php echo esc_html__( 'Actions', 'pearblog-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $sites as $site ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $site['name'] ); ?></strong></td>
						<td><?php echo esc_html( $site['url'] ); ?></td>
						<td><?php echo esc_html( number_format( $site['posts'] ) ); ?></td>
						<td>
							<span class="pearblog-status-badge pearblog-status-<?php echo esc_attr( $site['status'] ); ?>">
								<?php echo esc_html( ucfirst( $site['status'] ) ); ?>
							</span>
						</td>
						<td>
							<button type="button" class="pearblog-button-link" onclick="alert('Manage site')">
								<?php echo esc_html__( 'Manage', 'pearblog-engine' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render SaaS features.
	 */
	private static function render_saas_features(): void {
		$features = [
			[ 'icon' => '🔐', 'title' => 'SSO Integration', 'desc' => 'Single Sign-On across all network sites', 'status' => 'active' ],
			[ 'icon' => '📊', 'title' => 'Usage Metering', 'desc' => 'Track API usage and content generation per site', 'status' => 'active' ],
			[ 'icon' => '💳', 'title' => 'Subscription Management', 'desc' => 'Manage plans and billing for each site', 'status' => 'coming_soon' ],
			[ 'icon' => '🎨', 'title' => 'White Label', 'desc' => 'Customize branding for each network site', 'status' => 'coming_soon' ],
		];
		?>
		<?php foreach ( $features as $feature ) : ?>
			<div class="pearblog-feature-card">
				<div class="pearblog-feature-icon"><?php echo esc_html( $feature['icon'] ); ?></div>
				<h4><?php echo esc_html( $feature['title'] ); ?></h4>
				<p><?php echo esc_html( $feature['desc'] ); ?></p>
				<span class="pearblog-feature-status pearblog-status-<?php echo esc_attr( $feature['status'] ); ?>">
					<?php echo esc_html( $feature['status'] === 'active' ? 'Active' : 'Coming Soon' ); ?>
				</span>
			</div>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Handle multisite settings form submission.
	 */
	public static function handle_save_multisite_settings(): void {
		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_multisite_settings', 'pearblog_multisite_nonce' );

		$centralized_keys    = isset( $_POST['pearblog_centralized_api_keys'] ) ? 1 : 0;
		$network_billing     = isset( $_POST['pearblog_network_billing'] ) ? 1 : 0;
		$cross_site_analytics = isset( $_POST['pearblog_cross_site_analytics'] ) ? 1 : 0;

		update_site_option( 'pearblog_centralized_api_keys', $centralized_keys );
		update_site_option( 'pearblog_network_billing', $network_billing );
		update_site_option( 'pearblog_cross_site_analytics', $cross_site_analytics );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'pearblog-engine-v7',
					'tab'     => 'multisite',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
