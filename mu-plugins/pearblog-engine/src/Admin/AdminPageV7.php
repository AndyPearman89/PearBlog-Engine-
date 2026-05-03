<?php
/**
 * Admin Panel v7.0 - SaaS Control Center
 *
 * Transformed admin interface focused on revenue management and autonomous operations.
 * Replaces settings-focused AdminPage with a comprehensive SaaS control center.
 *
 * @package PearBlogEngine\Admin
 * @since 7.1.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\AI\AIProviderFactory;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Monitoring\PerformanceDashboard;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Admin Panel v7.0 with 10-tab SaaS Control Center architecture.
 */
class AdminPageV7 {

	private const MENU_SLUG  = 'pearblog-engine-v7';
	private const OPTION_GRP = 'pearblog_settings_v7';

	/**
	 * Available tabs in v7 admin.
	 */
	private const TABS = [
		'dashboard'     => '📊 Dashboard',
		'strategy'      => '🧠 Strategy (AI)',
		'content'       => '✍️ Content Engine',
		'seo'           => '🔍 SEO Engine',
		'monetization'  => '💰 Monetization',
		'leads'         => '👥 Leads & Experts',
		'automation'    => '⚙️ Automation',
		'analytics'     => '📈 Analytics',
		'multisite'     => '🌐 Multisite/SaaS',
		'settings'      => '⚙️ Settings',
	];

	/**
	 * Attach WordPress admin hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Admin POST handlers
		add_action( 'admin_post_pearblog_v7_save_settings', [ $this, 'handle_save_settings' ] );
	}

	/**
	 * Register WordPress admin menu.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'PearBlog v7', 'pearblog-engine' ),
			__( 'PearBlog v7', 'pearblog-engine' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ],
			'data:image/svg+xml;base64,' . base64_encode( $this->get_menu_icon() ),
			25
		);

		// Add submenu for direct tab access
		foreach ( self::TABS as $tab_id => $tab_label ) {
			add_submenu_page(
				self::MENU_SLUG,
				$tab_label,
				$tab_label,
				'manage_options',
				self::MENU_SLUG . '#' . $tab_id,
				[ $this, 'render_page' ]
			);
		}
	}

	/**
	 * Get menu icon SVG.
	 */
	private function get_menu_icon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<circle cx="12" cy="12" r="10"/>
			<path d="M12 6v6l4 2"/>
		</svg>';
	}

	/**
	 * Register settings with WordPress.
	 */
	public function register_settings(): void {
		register_setting( self::OPTION_GRP, 'pearblog_admin_version' );
		register_setting( self::OPTION_GRP, 'pearblog_v7_revenue_enabled' );
		register_setting( self::OPTION_GRP, 'pearblog_v7_leads_enabled' );
		register_setting( self::OPTION_GRP, 'pearblog_v7_experts_enabled' );
	}

	/**
	 * Enqueue admin CSS/JS only on our page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}

		// Enqueue v7 admin stylesheet
		wp_enqueue_style(
			'pearblog-admin-v7',
			PEARBLOG_ENGINE_URL . 'assets/css/admin-v7.css',
			[],
			PEARBLOG_ENGINE_VERSION
		);

		// Enqueue Chart.js for dashboard
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			[],
			'4.4.0',
			true
		);

		// Enqueue v7 admin JavaScript
		wp_enqueue_script(
			'pearblog-admin-v7',
			PEARBLOG_ENGINE_URL . 'assets/js/admin-v7.js',
			[ 'jquery', 'chartjs' ],
			PEARBLOG_ENGINE_VERSION,
			true
		);

		// Localize script with API data
		wp_localize_script(
			'pearblog-admin-v7',
			'pearblogAdminV7',
			[
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'pearblog_admin_v7' ),
				'restUrl'   => rest_url( 'pearblog/v1/' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Render the admin page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'pearblog-engine' ) );
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
		if ( ! array_key_exists( $current_tab, self::TABS ) ) {
			$current_tab = 'dashboard';
		}

		?>
		<div class="wrap pearblog-admin-v7">
			<h1 class="pearblog-v7-title">
				<span class="pearblog-logo">🍐</span>
				<?php echo esc_html__( 'PearBlog Engine v7.0', 'pearblog-engine' ); ?>
				<span class="pearblog-version-badge">SaaS Control Center</span>
			</h1>

			<!-- Tab Navigation -->
			<nav class="pearblog-v7-tabs">
				<?php foreach ( self::TABS as $tab_id => $tab_label ) : ?>
					<button
						class="pearblog-v7-tab <?php echo $tab_id === $current_tab ? 'is-active' : ''; ?>"
						data-tab="<?php echo esc_attr( $tab_id ); ?>"
						onclick="window.location.href='<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=' . $tab_id ) ); ?>'"
					>
						<?php echo esc_html( $tab_label ); ?>
					</button>
				<?php endforeach; ?>
			</nav>

			<!-- Tab Content -->
			<div class="pearblog-v7-content">
				<?php $this->render_tab_content( $current_tab ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render content for specific tab.
	 *
	 * @param string $tab_id Tab identifier.
	 */
	private function render_tab_content( string $tab_id ): void {
		switch ( $tab_id ) {
			case 'dashboard':
				$this->render_dashboard_tab();
				break;
			case 'strategy':
				$this->render_strategy_tab();
				break;
			case 'content':
				$this->render_content_tab();
				break;
			case 'seo':
				$this->render_seo_tab();
				break;
			case 'monetization':
				$this->render_monetization_tab();
				break;
			case 'leads':
				$this->render_leads_tab();
				break;
			case 'automation':
				$this->render_automation_tab();
				break;
			case 'analytics':
				$this->render_analytics_tab();
				break;
			case 'multisite':
				$this->render_multisite_tab();
				break;
			case 'settings':
				$this->render_settings_tab();
				break;
			default:
				$this->render_dashboard_tab();
		}
	}

	/**
	 * Render Dashboard tab - Revenue & Performance Overview.
	 */
	private function render_dashboard_tab(): void {
		DashboardTab::render();
	}

	/**
	 * Render Strategy (AI) tab - AI-driven content strategy.
	 */
	private function render_strategy_tab(): void {
		?>
		<div class="pearblog-v7-strategy">
			<h2><?php echo esc_html__( 'AI Content Strategy', 'pearblog-engine' ); ?></h2>
			<p><?php echo esc_html__( 'Configure AI-driven keyword discovery and content planning.', 'pearblog-engine' ); ?></p>

			<div class="pearblog-notice pearblog-notice-info">
				<p><strong><?php echo esc_html__( 'Coming in Phase 3 (v7.3 - August 2026)', 'pearblog-engine' ); ?></strong></p>
				<p><?php echo esc_html__( 'AI Strategy tab will include keyword automation, intent priority, and autonomous topic discovery.', 'pearblog-engine' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Content Engine tab - Batch content operations.
	 */
	private function render_content_tab(): void {
		?>
		<div class="pearblog-v7-content-engine">
			<h2><?php echo esc_html__( 'Content Engine', 'pearblog-engine' ); ?></h2>
			<p><?php echo esc_html__( 'Batch content generation, updates, and template management.', 'pearblog-engine' ); ?></p>

			<div class="pearblog-notice pearblog-notice-info">
				<p><strong><?php echo esc_html__( 'Coming in Phase 3 (v7.3 - August 2026)', 'pearblog-engine' ); ?></strong></p>
				<p><?php echo esc_html__( 'Content Engine will enable batch generation of 10-100 articles and content templates.', 'pearblog-engine' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render SEO Engine tab - SEO automation.
	 */
	private function render_seo_tab(): void {
		?>
		<div class="pearblog-v7-seo">
			<h2><?php echo esc_html__( 'SEO Engine', 'pearblog-engine' ); ?></h2>
			<p><?php echo esc_html__( 'Automated SEO optimization and programmatic SEO.', 'pearblog-engine' ); ?></p>

			<div class="pearblog-notice pearblog-notice-info">
				<p><strong><?php echo esc_html__( 'Migrating from v6 Settings', 'pearblog-engine' ); ?></strong></p>
				<p><?php echo esc_html__( 'SEO features from the v6 admin will be enhanced and moved here.', 'pearblog-engine' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Monetization tab - Revenue tracking & ad management.
	 */
	private function render_monetization_tab(): void {
		?>
		<div class="pearblog-v7-monetization">
			<h2><?php echo esc_html__( 'Monetization & Revenue Tracking', 'pearblog-engine' ); ?></h2>
			<p><?php echo esc_html__( 'Track revenue per article and optimize ad placement.', 'pearblog-engine' ); ?></p>

			<div class="pearblog-notice pearblog-notice-info">
				<p><strong><?php echo esc_html__( 'Coming in Phase 5 (v7.5 - October 2026)', 'pearblog-engine' ); ?></strong></p>
				<p><?php echo esc_html__( 'Enhanced monetization controls with per-article revenue tracking.', 'pearblog-engine' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Leads & Experts tab - Lead management.
	 */
	private function render_leads_tab(): void {
		?>
		<div class="pearblog-v7-leads">
			<h2><?php echo esc_html__( 'Leads & Expert Management', 'pearblog-engine' ); ?></h2>
			<p><?php echo esc_html__( 'Capture leads and route to domain experts.', 'pearblog-engine' ); ?></p>

			<div class="pearblog-notice pearblog-notice-info">
				<p><strong><?php echo esc_html__( 'Coming in Phase 4 (v7.4 - September 2026)', 'pearblog-engine' ); ?></strong></p>
				<p><?php echo esc_html__( 'Lead capture forms with automatic expert routing.', 'pearblog-engine' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Automation tab - Queue & scheduling.
	 */
	private function render_automation_tab(): void {
		?>
		<div class="pearblog-v7-automation">
			<h2><?php echo esc_html__( 'Automation & Scheduling', 'pearblog-engine' ); ?></h2>
			<p><?php echo esc_html__( 'Manage topic queue and publishing schedule.', 'pearblog-engine' ); ?></p>

			<div class="pearblog-notice pearblog-notice-info">
				<p><strong><?php echo esc_html__( 'Migrating from v6 Settings', 'pearblog-engine' ); ?></strong></p>
				<p><?php echo esc_html__( 'Automation features from v6 will be enhanced and moved here.', 'pearblog-engine' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Analytics tab - Advanced metrics.
	 */
	private function render_analytics_tab(): void {
		?>
		<div class="pearblog-v7-analytics">
			<h2><?php echo esc_html__( 'Analytics & Performance Metrics', 'pearblog-engine' ); ?></h2>
			<p><?php echo esc_html__( 'Advanced filtering and performance analysis.', 'pearblog-engine' ); ?></p>

			<div class="pearblog-notice pearblog-notice-info">
				<p><strong><?php echo esc_html__( 'Coming in Phase 2 (v7.2 - July 2026)', 'pearblog-engine' ); ?></strong></p>
				<p><?php echo esc_html__( 'Enhanced analytics with custom date ranges and filtering.', 'pearblog-engine' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Multisite/SaaS tab - Multi-tenant management.
	 */
	private function render_multisite_tab(): void {
		?>
		<div class="pearblog-v7-multisite">
			<h2><?php echo esc_html__( 'Multisite / SaaS Control', 'pearblog-engine' ); ?></h2>
			<p><?php echo esc_html__( 'Manage multiple sites from a central dashboard.', 'pearblog-engine' ); ?></p>

			<div class="pearblog-notice pearblog-notice-info">
				<p><strong><?php echo esc_html__( 'Coming in Phase 5 (v7.5 - October 2026)', 'pearblog-engine' ); ?></strong></p>
				<p><?php echo esc_html__( 'Central SaaS dashboard for managing multiple PearBlog sites.', 'pearblog-engine' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Settings tab - Configuration.
	 */
	private function render_settings_tab(): void {
		?>
		<div class="pearblog-v7-settings">
			<h2><?php echo esc_html__( 'Settings & Configuration', 'pearblog-engine' ); ?></h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pearblog_v7_save_settings" />
				<?php wp_nonce_field( 'pearblog_v7_settings', 'pearblog_v7_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label><?php echo esc_html__( 'Admin Version', 'pearblog-engine' ); ?></label>
						</th>
						<td>
							<p><strong><?php echo esc_html__( 'v7.0 (SaaS Control Center)', 'pearblog-engine' ); ?></strong></p>
							<p class="description">
								<?php echo esc_html__( 'You are using the new v7.0 admin interface. To switch back to v6, add define(\'PEARBLOG_ADMIN_VERSION\', \'v6\'); to wp-config.php', 'pearblog-engine' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="pearblog_v7_revenue_enabled"><?php echo esc_html__( 'Revenue Tracking', 'pearblog-engine' ); ?></label>
						</th>
						<td>
							<label>
								<input
									type="checkbox"
									id="pearblog_v7_revenue_enabled"
									name="pearblog_v7_revenue_enabled"
									value="1"
									<?php checked( get_option( 'pearblog_v7_revenue_enabled', false ) ); ?>
								/>
								<?php echo esc_html__( 'Enable per-article revenue tracking', 'pearblog-engine' ); ?>
							</label>
							<p class="description">
								<?php echo esc_html__( 'Track revenue generated by each article (requires Phase 5 implementation).', 'pearblog-engine' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'pearblog-engine' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle settings form submission.
	 */
	public function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'pearblog-engine' ) );
		}

		check_admin_referer( 'pearblog_v7_settings', 'pearblog_v7_nonce' );

		// Save revenue tracking setting
		$revenue_enabled = isset( $_POST['pearblog_v7_revenue_enabled'] ) ? 1 : 0;
		update_option( 'pearblog_v7_revenue_enabled', $revenue_enabled );

		// Redirect back with success message
		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => self::MENU_SLUG,
					'tab'     => 'settings',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
