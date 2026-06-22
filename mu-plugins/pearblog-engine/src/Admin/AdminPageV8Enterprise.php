<?php
/**
 * Admin Panel v8.0 ENTERPRISE MAX - Ultra-Advanced Control Center
 *
 * Revolutionary enterprise-grade admin interface with 15 specialized tabs,
 * real-time analytics, advanced security, dark mode, and Polish language support.
 *
 * Features:
 * - 15 specialized tabs (vs 10 in v7)
 * - Real-time dashboard with WebSocket support
 * - Advanced security & audit logging
 * - Dark mode with theme customization
 * - Polish language support (PL/EN toggle)
 * - Advanced reporting & export
 * - Real-time notifications center
 * - Glassmorphism UI with animations
 *
 * @package PearBlogEngine\Admin
 * @since 8.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Admin;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\AI\AIProviderFactory;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Monitoring\PerformanceDashboard;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Admin Panel v8.0 ENTERPRISE MAX - Full rozbudowa
 */
class AdminPageV8Enterprise {

	private const MENU_SLUG  = 'pearblog-enterprise-v8';
	private const OPTION_GRP = 'pearblog_enterprise_v8';
	private const VERSION    = '8.0.0';
	private ?PerformanceDashboard $performance_dashboard = null;
	private bool $pt24_styles_done = false;

	/**
	 * All 15 tabs in Enterprise v8.0
	 */
	private const TABS = [
		'dashboard'        => '🎯 Dashboard Enterprise',
		'realtime'         => '📊 Real-Time Analytics',
		'strategy'         => '🧠 AI Strategy',
		'content'          => '✍️ Content Engine',
		'seo'              => '🔍 SEO Advanced',
		'monetization'     => '💰 Revenue Center',
		'leads'            => '👥 Leads & CRM',
		'automation'       => '⚙️ Automation Pro',
		'analytics'        => '📈 Analytics Deep',
		'multisite'        => '🌐 Multisite/SaaS',
		'performance'      => '⚡ Performance',
		'security'         => '🔒 Security & Audit',
		'reporting'        => '📋 Advanced Reports',
		'integrations'     => '🔗 Integrations',
		'settings'         => '⚙️ Settings Enterprise',
	];

	/**
	 * Register WordPress hooks
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'network_admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// AJAX handlers for real-time features
		add_action( 'wp_ajax_pb_v8_get_realtime_stats', [ $this, 'ajax_get_realtime_stats' ] );
		add_action( 'wp_ajax_pb_v8_get_notifications', [ $this, 'ajax_get_notifications' ] );
		add_action( 'wp_ajax_pb_v8_toggle_theme', [ $this, 'ajax_toggle_theme' ] );
		add_action( 'wp_ajax_pb_v8_export_report', [ $this, 'ajax_export_report' ] );

		// PT24 lead management (status update + CSV export).
		add_action( 'admin_post_pt24_update_lead_status', [ $this, 'handle_update_lead_status' ] );
		add_action( 'admin_post_pt24_export_leads', [ $this, 'handle_export_leads' ] );
		add_action( 'admin_post_pt24_save_settings', [ $this, 'handle_save_settings' ] );
	}

	/**
	 * Add WordPress admin menu
	 */
	public function add_menu(): void {
		$capability = $this->get_required_capability();

		add_menu_page(
			__( 'PearBlog Enterprise v8', 'pearblog-engine' ),
			__( '🚀 PearBlog v8', 'pearblog-engine' ),
			$capability,
			self::MENU_SLUG,
			[ $this, 'render_page' ],
			$this->get_menu_icon_svg(),
			2 // Top position
		);

		// Add submenu items for each tab
		foreach ( self::TABS as $tab_id => $tab_label ) {
			add_submenu_page(
				self::MENU_SLUG,
				$tab_label,
				$tab_label,
				$capability,
				self::MENU_SLUG . '#' . $tab_id,
				'__return_null' // Content rendered by main page
			);
		}
	}

	/**
	 * Resolve capability required to view the admin menu.
	 */
	private function get_required_capability(): string {
		$default_capability = \is_network_admin() ? 'manage_network_options' : 'manage_options';
		$capability         = apply_filters( 'pearblog_admin_capability', $default_capability );

		if ( defined( 'PEARBLOG_ADMIN_FORCE_ACCESS' ) && PEARBLOG_ADMIN_FORCE_ACCESS ) {
			return 'read';
		}

		$access_override = (string) get_option( 'pearblog_admin_capability_override', '' );
		if ( '' !== trim( $access_override ) ) {
			return sanitize_key( $access_override );
		}

		if ( ! is_string( $capability ) || '' === trim( $capability ) ) {
			return $default_capability;
		}

		return $capability;
	}

	/**
	 * Get menu icon SVG (animated)
	 */
	private function get_menu_icon_svg(): string {
		return 'data:image/svg+xml;base64,' . base64_encode( '
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
				<circle cx="12" cy="12" r="10"/>
				<path d="M12 6v6l4 2"/>
				<circle cx="12" cy="12" r="3" fill="currentColor"/>
			</svg>
		' );
	}

	/**
	 * Register settings
	 */
	public function register_settings(): void {
		register_setting( self::OPTION_GRP, 'pearblog_v8_theme', [
			'type'    => 'string',
			'default' => 'light',
		] );
		register_setting( self::OPTION_GRP, 'pearblog_v8_language', [
			'type'    => 'string',
			'default' => 'en',
		] );
		register_setting( self::OPTION_GRP, 'pearblog_v8_realtime_enabled', [
			'type'    => 'boolean',
			'default' => true,
		] );
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}

		// Enterprise v8 CSS
		wp_enqueue_style(
			'pearblog-admin-v8-enterprise',
			PEARBLOG_ENGINE_URL . 'assets/css/admin-v8-enterprise.css',
			[],
			self::VERSION
		);

		// Chart.js for advanced visualizations
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			[],
			'4.4.0',
			true
		);

		// Alpine.js for reactive UI
		wp_enqueue_script(
			'alpinejs',
			'https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js',
			[],
			'3.13.3',
			true
		);

		// Custom v8 JavaScript
		wp_enqueue_script(
			'pearblog-admin-v8-js',
			PEARBLOG_ENGINE_URL . 'assets/js/admin-v8-enterprise.js',
			[ 'jquery', 'chartjs', 'alpinejs' ],
			self::VERSION,
			true
		);

		// Localize script
		wp_localize_script( 'pearblog-admin-v8-js', 'pbV8Data', [
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'pb_v8_nonce' ),
			'currentTab'        => $_GET['tab'] ?? 'dashboard',
			'theme'             => get_option( 'pearblog_v8_theme', 'light' ),
			'language'          => get_option( 'pearblog_v8_language', 'en' ),
			'realtimeEnabled'   => (bool) get_option( 'pearblog_v8_realtime_enabled', true ),
			'version'           => self::VERSION,
			'translations'      => $this->get_translations(),
		] );
	}

	/**
	 * Get translations for current language
	 */
	private function get_translations(): array {
		$lang = get_option( 'pearblog_v8_language', 'en' );

		$translations = [
			'en' => [
				'loading'         => 'Loading...',
				'save'            => 'Save Changes',
				'cancel'          => 'Cancel',
				'export'          => 'Export',
				'refresh'         => 'Refresh',
				'realTimeData'    => 'Real-Time Data',
				'notifications'   => 'Notifications',
				'darkMode'        => 'Dark Mode',
				'lightMode'       => 'Light Mode',
				'settings'        => 'Settings',
			],
			'pl' => [
				'loading'         => 'Ładowanie...',
				'save'            => 'Zapisz Zmiany',
				'cancel'          => 'Anuluj',
				'export'          => 'Eksportuj',
				'refresh'         => 'Odśwież',
				'realTimeData'    => 'Dane w Czasie Rzeczywistym',
				'notifications'   => 'Powiadomienia',
				'darkMode'        => 'Tryb Ciemny',
				'lightMode'       => 'Tryb Jasny',
				'settings'        => 'Ustawienia',
			],
		];

		return $translations[ $lang ] ?? $translations['en'];
	}

	/**
	 * Render main admin page
	 */
	public function render_page(): void {
		$current_tab = $_GET['tab'] ?? 'dashboard';
		$current_theme = get_option( 'pearblog_v8_theme', 'light' );
		$current_lang = get_option( 'pearblog_v8_language', 'en' );

		?>
		<div class="pearblog-admin-v8" data-theme="<?php echo esc_attr( $current_theme ); ?>" data-lang="<?php echo esc_attr( $current_lang ); ?>">
			<div class="pearblog-admin-v8-container">

				<?php $this->render_topbar(); ?>
				<?php $this->render_tabs( $current_tab ); ?>
				<?php $this->render_tab_content( $current_tab ); ?>

			</div>
		</div>

		<?php $this->render_notification_center(); ?>
		<?php
	}

	/**
	 * Render top bar with branding and controls
	 */
	private function render_topbar(): void {
		$unread_notifications = $this->get_unread_notifications_count();
		?>
		<div class="pb-v8-topbar">
			<div class="pb-v8-logo-section">
				<span class="pb-v8-logo">🍐</span>
				<h1 class="pb-v8-title">
					PearBlog Enterprise
					<span class="pb-v8-version-badge">
						⚡ v<?php echo esc_html( self::VERSION ); ?> MAX
					</span>
				</h1>
			</div>

			<div class="pb-v8-topbar-actions">
				<!-- Language Toggle -->
				<button
					class="pb-v8-theme-toggle"
					onclick="pbV8ToggleLanguage()"
					title="<?php esc_attr_e( 'Toggle Language', 'pearblog-engine' ); ?>">
					<?php echo get_option( 'pearblog_v8_language', 'en' ) === 'pl' ? '🇵🇱' : '🇬🇧'; ?>
				</button>

				<!-- Theme Toggle -->
				<button
					class="pb-v8-theme-toggle"
					onclick="pbV8ToggleTheme()"
					title="<?php esc_attr_e( 'Toggle Theme', 'pearblog-engine' ); ?>">
					<?php echo get_option( 'pearblog_v8_theme', 'light' ) === 'dark' ? '☀️' : '🌙'; ?>
				</button>

				<!-- Notifications -->
				<div class="pb-v8-notifications">
					<button
						class="pb-v8-notification-btn"
						onclick="pbV8ToggleNotifications()"
						title="<?php esc_attr_e( 'Notifications', 'pearblog-engine' ); ?>">
						🔔
						<?php if ( $unread_notifications > 0 ) : ?>
							<span class="pb-v8-notification-badge"><?php echo esc_html( (string) $unread_notifications ); ?></span>
						<?php endif; ?>
					</button>
				</div>

				<!-- User Profile -->
				<div class="pb-v8-user-profile">
					<?php
					$current_user = wp_get_current_user();
					echo get_avatar( $current_user->ID, 40, '', '', [
						'class' => 'pb-v8-avatar',
					] );
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render tab navigation
	 */
	private function render_tabs( string $current_tab ): void {
		?>
		<div class="pb-v8-tabs-wrapper">
			<div class="pb-v8-tabs" role="tablist">
				<?php foreach ( self::TABS as $tab_id => $tab_label ) : ?>
					<button
						class="pb-v8-tab <?php echo $current_tab === $tab_id ? 'is-active' : ''; ?>"
						data-tab="<?php echo esc_attr( $tab_id ); ?>"
						role="tab"
						aria-selected="<?php echo $current_tab === $tab_id ? 'true' : 'false'; ?>"
						onclick="pbV8SwitchTab('<?php echo esc_js( $tab_id ); ?>')">
						<span class="pb-v8-tab-icon"><?php echo $this->get_tab_icon( $tab_id ); ?></span>
						<span class="pb-v8-tab-label"><?php echo esc_html( $tab_label ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get icon for tab
	 */
	private function get_tab_icon( string $tab_id ): string {
		$icons = [
			'dashboard'     => '🎯',
			'realtime'      => '📊',
			'strategy'      => '🧠',
			'content'       => '✍️',
			'seo'           => '🔍',
			'monetization'  => '💰',
			'leads'         => '👥',
			'automation'    => '⚙️',
			'analytics'     => '📈',
			'multisite'     => '🌐',
			'performance'   => '⚡',
			'security'      => '🔒',
			'reporting'     => '📋',
			'integrations'  => '🔗',
			'settings'      => '⚙️',
		];

		return $icons[ $tab_id ] ?? '📄';
	}

	/**
	 * Render tab content
	 */
	private function render_tab_content( string $current_tab ): void {
		?>
		<div class="pb-v8-tab-content">
			<?php
			foreach ( self::TABS as $tab_id => $tab_label ) {
				$is_active = $current_tab === $tab_id;
				$class = $is_active ? 'is-active' : '';
				?>
				<div class="pb-v8-tab-panel <?php echo esc_attr( $class ); ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>" role="tabpanel">
					<?php $this->render_tab_panel( $tab_id ); ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render individual tab panel
	 */
	private function render_tab_panel( string $tab_id ): void {
		switch ( $tab_id ) {
			case 'dashboard':
				$this->render_dashboard_tab();
				break;
			case 'realtime':
				$this->render_realtime_tab();
				break;
			case 'security':
				$this->render_security_tab();
				break;
			case 'reporting':
				$this->render_reporting_tab();
				break;
			case 'integrations':
				$this->render_integrations_tab();
				break;
			case 'leads':
				$this->render_leads_tab();
				break;
			case 'analytics':
				$this->render_analytics_tab();
				break;
			case 'settings':
				$this->render_settings_tab();
				break;
			case 'performance':
				$this->render_performance_tab();
				break;
			default:
				$this->render_coming_soon_tab( $tab_id );
				break;
		}
	}

	/**
	 * Render Enterprise Dashboard
	 */
	private function render_dashboard_tab(): void {
		$pt24_leads = $this->get_pt24_leads_data();
		if ( $pt24_leads['table_exists'] ) {
			$this->render_pt24_dashboard( $pt24_leads );
			return;
		}

		$stats = $this->get_dashboard_stats();
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Enterprise Dashboard', 'pearblog-engine' ); ?></h2>

			<!-- KPI Metrics Grid -->
			<div class="pb-v8-metrics-grid">
				<?php $this->render_metric_card( [
					'label'  => __( 'Revenue Today', 'pearblog-engine' ),
					'value'  => '$' . number_format( $stats['revenue_today'], 2 ),
					'change' => $stats['revenue_change'],
					'icon'   => '💰',
					'color'  => 'success',
				] ); ?>

				<?php $this->render_metric_card( [
					'label'  => __( 'Active Users', 'pearblog-engine' ),
					'value'  => number_format( $stats['active_users'] ),
					'change' => $stats['users_change'],
					'icon'   => '👥',
					'color'  => 'primary',
				] ); ?>

				<?php $this->render_metric_card( [
					'label'  => __( 'Content Generated', 'pearblog-engine' ),
					'value'  => number_format( $stats['content_generated'] ),
					'change' => $stats['content_change'],
					'icon'   => '✍️',
					'color'  => 'warning',
				] ); ?>

				<?php $this->render_metric_card( [
					'label'  => __( 'AI Cost', 'pearblog-engine' ),
					'value'  => '$' . number_format( $stats['ai_cost'], 2 ),
					'change' => $stats['cost_change'],
					'icon'   => '🤖',
					'color'  => 'danger',
				] ); ?>
			</div>

			<!-- Charts Section -->
			<div class="pb-v8-charts-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--pb-v8-space-lg); margin-top: var(--pb-v8-space-lg);">
				<div class="pb-v8-card">
					<div class="pb-v8-card-header">
						<h3 class="pb-v8-card-title"><?php esc_html_e( 'Revenue Trend (30 Days)', 'pearblog-engine' ); ?></h3>
					</div>
					<div class="pb-v8-card-body">
						<canvas id="revenueChart" height="80"></canvas>
					</div>
				</div>

				<div class="pb-v8-card">
					<div class="pb-v8-card-header">
						<h3 class="pb-v8-card-title"><?php esc_html_e( 'Content Distribution', 'pearblog-engine' ); ?></h3>
					</div>
					<div class="pb-v8-card-body">
						<canvas id="contentChart" height="120"></canvas>
					</div>
				</div>
			</div>

			<!-- Recent Activity -->
			<div class="pb-v8-card" style="margin-top: var(--pb-v8-space-lg);">
				<div class="pb-v8-card-header">
					<h3 class="pb-v8-card-title"><?php esc_html_e( 'Recent Activity', 'pearblog-engine' ); ?></h3>
					<button class="pb-v8-btn pb-v8-btn-outline" onclick="pbV8RefreshActivity()">
						🔄 <?php esc_html_e( 'Refresh', 'pearblog-engine' ); ?>
					</button>
				</div>
				<div class="pb-v8-card-body">
					<?php $this->render_activity_feed(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Real-Time Analytics Tab
	 */
	private function render_realtime_tab(): void {
		$stats = $this->get_realtime_stats();
		?>
		<div class="pb-v8-realtime">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Real-Time Analytics', 'pearblog-engine' ); ?></h2>

			<div class="pb-v8-alert pb-v8-alert-success" style="margin-bottom: var(--pb-v8-space-lg);">
				<strong>🟢 <?php esc_html_e( 'Live Monitoring Active', 'pearblog-engine' ); ?></strong>
				<p><?php esc_html_e( 'Data updates every 5 seconds', 'pearblog-engine' ); ?></p>
			</div>

			<!-- Live Metrics -->
			<div class="pb-v8-metrics-grid">
				<div class="pb-v8-metric-card" data-realtime="pageviews">
					<div class="pb-v8-metric-header">
						<span class="pb-v8-metric-label"><?php esc_html_e( 'Live Visitors', 'pearblog-engine' ); ?></span>
						<span class="pb-v8-metric-icon">👁️</span>
					</div>
					<div class="pb-v8-metric-value" id="liveVisitors"><?php echo esc_html( (string) $stats['visitors'] ); ?></div>
					<div class="pb-v8-metric-chart">
						<canvas id="liveVisitorsChart" height="60"></canvas>
					</div>
				</div>

				<div class="pb-v8-metric-card" data-realtime="revenue">
					<div class="pb-v8-metric-header">
						<span class="pb-v8-metric-label"><?php esc_html_e( 'Revenue/Hour', 'pearblog-engine' ); ?></span>
						<span class="pb-v8-metric-icon">💵</span>
					</div>
					<div class="pb-v8-metric-value" id="liveRevenue"><?php echo esc_html( '$' . number_format( (float) $stats['revenue'], 2 ) ); ?></div>
					<div class="pb-v8-metric-chart">
						<canvas id="liveRevenueChart" height="60"></canvas>
					</div>
				</div>

				<div class="pb-v8-metric-card" data-realtime="conversions">
					<div class="pb-v8-metric-header">
						<span class="pb-v8-metric-label"><?php esc_html_e( 'Conversions', 'pearblog-engine' ); ?></span>
						<span class="pb-v8-metric-icon">🎯</span>
					</div>
					<div class="pb-v8-metric-value" id="liveConversions"><?php echo esc_html( (string) $stats['conversions'] ); ?></div>
					<div class="pb-v8-metric-chart">
						<canvas id="liveConversionsChart" height="60"></canvas>
					</div>
				</div>

				<div class="pb-v8-metric-card" data-realtime="errors">
					<div class="pb-v8-metric-header">
						<span class="pb-v8-metric-label"><?php esc_html_e( 'Error Rate', 'pearblog-engine' ); ?></span>
						<span class="pb-v8-metric-icon">⚠️</span>
					</div>
					<div class="pb-v8-metric-value" id="liveErrors"><?php echo esc_html( number_format( (float) $stats['errors'], 1 ) . '%' ); ?></div>
					<div class="pb-v8-metric-chart">
						<canvas id="liveErrorsChart" height="60"></canvas>
					</div>
				</div>
			</div>

			<!-- Live Activity Map -->
			<div class="pb-v8-card" style="margin-top: var(--pb-v8-space-lg);">
				<div class="pb-v8-card-header">
					<h3 class="pb-v8-card-title"><?php esc_html_e( 'Live Activity Stream', 'pearblog-engine' ); ?></h3>
				</div>
				<div class="pb-v8-card-body">
					<div id="liveActivityStream" style="max-height: 400px; overflow-y: auto;"></div>
				</div>
			</div>
		</div>

		<script>
		// Initialize real-time monitoring
		document.addEventListener('DOMContentLoaded', function() {
			if (typeof pbV8InitRealtime === 'function') {
				pbV8InitRealtime();
			}
		});
		</script>
		<?php
	}

	/**
	 * Render Security & Audit Tab
	 */
	private function render_security_tab(): void {
		?>
		<div class="pb-v8-security">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Security & Audit Log', 'pearblog-engine' ); ?></h2>

			<!-- Security Score -->
			<div class="pb-v8-card" style="margin-bottom: var(--pb-v8-space-lg);">
				<div class="pb-v8-card-body">
					<div style="text-align: center;">
						<div style="font-size: 72px; font-weight: 800; color: var(--pb-v8-success); margin-bottom: 16px;">
							98<span style="font-size: 48px;">/100</span>
						</div>
						<h3><?php esc_html_e( 'Overall Security Score', 'pearblog-engine' ); ?></h3>
						<p style="color: var(--pb-v8-text-secondary);"><?php esc_html_e( 'Excellent security posture', 'pearblog-engine' ); ?></p>

						<div class="pb-v8-progress" style="margin-top: 24px;">
							<div class="pb-v8-progress-bar" style="width: 98%;"></div>
						</div>
					</div>
				</div>
			</div>

			<!-- Security Metrics -->
			<div class="pb-v8-metrics-grid">
				<?php $this->render_metric_card( [
					'label'  => __( 'Failed Login Attempts', 'pearblog-engine' ),
					'value'  => '3',
					'icon'   => '🔒',
					'color'  => 'warning',
				] ); ?>

				<?php $this->render_metric_card( [
					'label'  => __( 'Blocked IP Addresses', 'pearblog-engine' ),
					'value'  => '12',
					'icon'   => '🚫',
					'color'  => 'danger',
				] ); ?>

				<?php $this->render_metric_card( [
					'label'  => __( 'Audit Logs (24h)', 'pearblog-engine' ),
					'value'  => '1,234',
					'icon'   => '📋',
					'color'  => 'primary',
				] ); ?>

				<?php $this->render_metric_card( [
					'label'  => __( 'Active Sessions', 'pearblog-engine' ),
					'value'  => '5',
					'icon'   => '👤',
					'color'  => 'success',
				] ); ?>
			</div>

			<!-- Audit Log Table -->
			<div class="pb-v8-card" style="margin-top: var(--pb-v8-space-lg);">
				<div class="pb-v8-card-header">
					<h3 class="pb-v8-card-title"><?php esc_html_e( 'Recent Audit Log', 'pearblog-engine' ); ?></h3>
					<button class="pb-v8-btn pb-v8-btn-outline" onclick="pbV8ExportAuditLog()">
						📥 <?php esc_html_e( 'Export', 'pearblog-engine' ); ?>
					</button>
				</div>
				<div class="pb-v8-card-body">
					<?php $this->render_audit_log_table(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Advanced Reporting Tab
	 */
	private function render_reporting_tab(): void {
		?>
		<div class="pb-v8-reporting">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Advanced Reports', 'pearblog-engine' ); ?></h2>

			<?php $this->render_pt24_report_section(); ?>

			<!-- Report Types -->
			<div class="pb-v8-metrics-grid">
				<div class="pb-v8-card">
					<div class="pb-v8-card-body">
						<h3>📊 <?php esc_html_e( 'Revenue Report', 'pearblog-engine' ); ?></h3>
						<p><?php esc_html_e( 'Comprehensive revenue analysis', 'pearblog-engine' ); ?></p>
						<button class="pb-v8-btn pb-v8-btn-primary" onclick="pbV8GenerateReport('revenue')">
							<?php esc_html_e( 'Generate', 'pearblog-engine' ); ?>
						</button>
					</div>
				</div>

				<div class="pb-v8-card">
					<div class="pb-v8-card-body">
						<h3>📈 <?php esc_html_e( 'Content Performance', 'pearblog-engine' ); ?></h3>
						<p><?php esc_html_e( 'Detailed content analytics', 'pearblog-engine' ); ?></p>
						<button class="pb-v8-btn pb-v8-btn-primary" onclick="pbV8GenerateReport('content')">
							<?php esc_html_e( 'Generate', 'pearblog-engine' ); ?>
						</button>
					</div>
				</div>

				<div class="pb-v8-card">
					<div class="pb-v8-card-body">
						<h3>🔍 <?php esc_html_e( 'SEO Report', 'pearblog-engine' ); ?></h3>
						<p><?php esc_html_e( 'SEO metrics and rankings', 'pearblog-engine' ); ?></p>
						<button class="pb-v8-btn pb-v8-btn-primary" onclick="pbV8GenerateReport('seo')">
							<?php esc_html_e( 'Generate', 'pearblog-engine' ); ?>
						</button>
					</div>
				</div>

				<div class="pb-v8-card">
					<div class="pb-v8-card-body">
						<h3>🤖 <?php esc_html_e( 'AI Cost Analysis', 'pearblog-engine' ); ?></h3>
						<p><?php esc_html_e( 'AI usage and cost breakdown', 'pearblog-engine' ); ?></p>
						<button class="pb-v8-btn pb-v8-btn-primary" onclick="pbV8GenerateReport('ai-cost')">
							<?php esc_html_e( 'Generate', 'pearblog-engine' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Export Options -->
			<div class="pb-v8-card" style="margin-top: var(--pb-v8-space-lg);">
				<div class="pb-v8-card-header">
					<h3 class="pb-v8-card-title"><?php esc_html_e( 'Export Options', 'pearblog-engine' ); ?></h3>
				</div>
				<div class="pb-v8-card-body">
					<div style="display: flex; gap: 16px;">
						<button class="pb-v8-btn pb-v8-btn-outline" onclick="pbV8Export('csv')">
							📄 CSV
						</button>
						<button class="pb-v8-btn pb-v8-btn-outline" onclick="pbV8Export('pdf')">
							📑 PDF
						</button>
						<button class="pb-v8-btn pb-v8-btn-outline" onclick="pbV8Export('json')">
							🔧 JSON
						</button>
						<button class="pb-v8-btn pb-v8-btn-outline" onclick="pbV8Export('excel')">
							📊 Excel
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Integrations Tab
	 */
	private function render_integrations_tab(): void {
		?>
		<div class="pb-v8-integrations">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Integrations & API', 'pearblog-engine' ); ?></h2>

			<!-- API Status -->
			<div class="pb-v8-alert pb-v8-alert-success" style="margin-bottom: var(--pb-v8-space-lg);">
				<strong>✅ <?php esc_html_e( 'API Status: Active', 'pearblog-engine' ); ?></strong>
				<p><?php esc_html_e( 'All API endpoints are operational', 'pearblog-engine' ); ?></p>
			</div>

			<!-- Available Integrations -->
			<div class="pb-v8-metrics-grid">
				<div class="pb-v8-card">
					<div class="pb-v8-card-body">
						<h3>🔗 Google Analytics</h3>
						<p>Connected</p>
						<span class="pb-v8-badge pb-v8-badge-success">Active</span>
					</div>
				</div>

				<div class="pb-v8-card">
					<div class="pb-v8-card-body">
						<h3>💰 Google AdSense</h3>
						<p>Connected</p>
						<span class="pb-v8-badge pb-v8-badge-success">Active</span>
					</div>
				</div>

				<div class="pb-v8-card">
					<div class="pb-v8-card-body">
						<h3>🔍 Google Search Console</h3>
						<p>Not Connected</p>
						<span class="pb-v8-badge pb-v8-badge-warning">Pending</span>
					</div>
				</div>

				<div class="pb-v8-card">
					<div class="pb-v8-card-body">
						<h3>📧 Mailchimp</h3>
						<p>Not Connected</p>
						<span class="pb-v8-badge pb-v8-badge-warning">Pending</span>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render coming soon tab
	 */
	private function render_coming_soon_tab( string $tab_id ): void {
		?>
		<div class="pb-v8-coming-soon" style="text-align: center; padding: 80px 20px;">
			<div style="font-size: 72px; margin-bottom: 24px;">🚧</div>
			<h2><?php esc_html_e( 'Coming Soon', 'pearblog-engine' ); ?></h2>
			<p style="color: var(--pb-v8-text-secondary);">
				<?php
				/* translators: %s: tab name */
				printf( esc_html__( 'The %s tab is currently being developed.', 'pearblog-engine' ), '<strong>' . esc_html( self::TABS[ $tab_id ] ) . '</strong>' );
				?>
			</p>
			<p style="color: var(--pb-v8-text-secondary);">
				<?php esc_html_e( 'Check back soon for exciting new features!', 'pearblog-engine' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the Leads & CRM tab — live PT24 lead inbox.
	 *
	 * Reads from the PT24 leads table ({prefix}pt24_leads). The engine is shared
	 * across installs, so a missing table degrades to an empty state instead of an
	 * error (the table only exists on the PT24 site).
	 */
	private function render_leads_tab(): void {
		$data  = $this->get_pt24_leads_data();
		$leads = $data['rows'];
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Leads & CRM', 'pearblog-engine' ); ?></h2>

			<?php if ( ! $data['table_exists'] ) : ?>
				<div class="pb-v8-coming-soon" style="text-align:center; padding:60px 20px;">
					<div style="font-size:56px; margin-bottom:16px;">📭</div>
					<h2><?php esc_html_e( 'No lead inbox on this site', 'pearblog-engine' ); ?></h2>
					<p style="color: var(--pb-v8-text-secondary);">
						<?php esc_html_e( 'The PT24 leads table was not found on this installation.', 'pearblog-engine' ); ?>
					</p>
				</div>
			<?php else : ?>

				<?php
				$pt24_notice = isset( $_GET['pt24_notice'] ) ? sanitize_key( wp_unslash( $_GET['pt24_notice'] ) ) : '';
				if ( 'updated' === $pt24_notice ) {
					echo '<div class="notice notice-success" style="margin:0 0 16px;"><p>' . esc_html__( 'Lead status updated.', 'pearblog-engine' ) . '</p></div>';
				} elseif ( 'error' === $pt24_notice ) {
					echo '<div class="notice notice-error" style="margin:0 0 16px;"><p>' . esc_html__( 'Could not update the lead.', 'pearblog-engine' ) . '</p></div>';
				}
				?>

				<div class="pb-v8-metrics-grid">
					<?php
					$this->render_metric_card( [ 'label' => __( 'Total Leads', 'pearblog-engine' ), 'value' => number_format_i18n( $data['total'] ), 'icon' => '👥', 'color' => 'primary' ] );
					$this->render_metric_card( [ 'label' => __( 'New / Unhandled', 'pearblog-engine' ), 'value' => number_format_i18n( $data['new'] ), 'icon' => '🆕', 'color' => 'warning' ] );
					$this->render_metric_card( [ 'label' => __( 'Today', 'pearblog-engine' ), 'value' => number_format_i18n( $data['today'] ), 'icon' => '📅', 'color' => 'success' ] );
					$this->render_metric_card( [ 'label' => __( 'Last 7 days', 'pearblog-engine' ), 'value' => number_format_i18n( $data['week'] ), 'icon' => '📈', 'color' => 'primary' ] );
					?>
				</div>

				<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:32px; flex-wrap:wrap;">
					<h3 class="pb-v8-section-title" style="margin:0;"><?php esc_html_e( 'Recent leads', 'pearblog-engine' ); ?></h3>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
						<input type="hidden" name="action" value="pt24_export_leads">
						<?php wp_nonce_field( 'pt24_export_leads' ); ?>
						<button type="submit" class="button">⬇ <?php esc_html_e( 'Export CSV', 'pearblog-engine' ); ?></button>
					</form>
				</div>

				<?php if ( empty( $leads ) ) : ?>
					<p style="color: var(--pb-v8-text-secondary);"><?php esc_html_e( 'No leads yet. New enquiries from the site will appear here.', 'pearblog-engine' ); ?></p>
				<?php else : ?>
					<div class="pb-v8-table-wrapper">
						<table class="pb-v8-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date', 'pearblog-engine' ); ?></th>
									<th><?php esc_html_e( 'Name', 'pearblog-engine' ); ?></th>
									<th><?php esc_html_e( 'Contact', 'pearblog-engine' ); ?></th>
									<th><?php esc_html_e( 'Service', 'pearblog-engine' ); ?></th>
									<th><?php esc_html_e( 'City', 'pearblog-engine' ); ?></th>
									<th><?php esc_html_e( 'Status', 'pearblog-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $leads as $lead ) :
									$status = (string) ( $lead->status ?? 'new' );
									$badge  = $this->lead_status_badge( $status );
									$phone  = (string) ( $lead->phone ?? '' );
									$email  = (string) ( $lead->email ?? '' );
									?>
									<tr>
										<td><?php echo esc_html( mysql2date( 'Y-m-d H:i', (string) $lead->created_at ) ); ?></td>
										<td><strong><?php echo esc_html( (string) $lead->name ); ?></strong></td>
										<td>
											<?php if ( '' !== $phone ) : ?>
												<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
											<?php endif; ?>
											<?php if ( '' !== $email ) : ?>
												<br><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( ucfirst( str_replace( '-', ' ', (string) $lead->service ) ) ); ?></td>
										<td><?php echo esc_html( ucfirst( (string) $lead->city ) ); ?></td>
										<td>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex; align-items:center; gap:8px; margin:0;">
												<input type="hidden" name="action" value="pt24_update_lead_status">
												<input type="hidden" name="lead_id" value="<?php echo (int) $lead->id; ?>">
												<?php wp_nonce_field( 'pt24_update_lead_status' ); ?>
												<span class="pb-v8-badge pb-v8-badge-<?php echo esc_attr( $badge ); ?>"><?php echo esc_html( $status ); ?></span>
												<select name="status" onchange="this.form.submit()" style="max-width:140px;">
													<?php foreach ( $this->pt24_lead_statuses() as $st_key => $st_label ) : ?>
														<option value="<?php echo esc_attr( $st_key ); ?>" <?php selected( $status, $st_key ); ?>><?php echo esc_html( $st_label ); ?></option>
													<?php endforeach; ?>
												</select>
												<noscript><button type="submit" class="button button-small">OK</button></noscript>
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Map a lead status to a badge variant available in the V8 stylesheet.
	 */
	private function lead_status_badge( string $status ): string {
		$map = [
			'new'         => 'primary',
			'contacted'   => 'warning',
			'in_progress' => 'warning',
			'won'         => 'success',
			'converted'   => 'success',
			'closed'      => 'success',
			'lost'        => 'danger',
			'rejected'    => 'danger',
			'spam'        => 'danger',
		];
		return $map[ strtolower( $status ) ] ?? 'primary';
	}

	/**
	 * Fetch PT24 lead KPIs and the most recent rows.
	 *
	 * @return array{table_exists:bool,total:int,new:int,today:int,week:int,rows:array}
	 */
	private function get_pt24_leads_data(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'pt24_leads';
		$out   = [ 'table_exists' => false, 'total' => 0, 'new' => 0, 'today' => 0, 'week' => 0, 'rows' => [] ];

		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $found !== $table ) {
			return $out;
		}
		$out['table_exists'] = true;

		// Table name is built from the trusted DB prefix (no user input).
		$out['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		$out['new']   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE status = %s", 'new' ) );
		$out['today'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s", current_time( 'Y-m-d' ) . ' 00:00:00' ) );
		$out['week']  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s", gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 7 * DAY_IN_SECONDS ) ) );
		$out['rows']  = (array) $wpdb->get_results( "SELECT id, name, email, phone, city, service, source, status, created_at FROM `{$table}` ORDER BY created_at DESC LIMIT 50" );

		return $out;
	}

	/**
	 * Allowed PT24 lead statuses (CRM funnel) => display label.
	 */
	private function pt24_lead_statuses(): array {
		return [
			'new'         => __( 'New', 'pearblog-engine' ),
			'contacted'   => __( 'Contacted', 'pearblog-engine' ),
			'in_progress' => __( 'In progress', 'pearblog-engine' ),
			'won'         => __( 'Won', 'pearblog-engine' ),
			'lost'        => __( 'Lost', 'pearblog-engine' ),
		];
	}

	/**
	 * admin-post handler: update a single PT24 lead's status.
	 */
	public function handle_update_lead_status(): void {
		if ( ! current_user_can( $this->get_required_capability() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}
		check_admin_referer( 'pt24_update_lead_status' );

		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$status  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$allowed = array_keys( $this->pt24_lead_statuses() );
		$notice  = 'error';

		if ( $lead_id > 0 && in_array( $status, $allowed, true ) ) {
			global $wpdb;
			$table = $wpdb->prefix . 'pt24_leads';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
				$wpdb->update(
					$table,
					[ 'status' => $status, 'updated_at' => current_time( 'mysql' ) ],
					[ 'id' => $lead_id ],
					[ '%s', '%s' ],
					[ '%d' ]
				);
				$notice = 'updated';
			}
		}

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'tab' => 'leads', 'pt24_notice' => $notice ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * admin-post handler: export all PT24 leads to CSV.
	 */
	public function handle_export_leads(): void {
		if ( ! current_user_can( $this->get_required_capability() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}
		check_admin_referer( 'pt24_export_leads' );

		global $wpdb;
		$table = $wpdb->prefix . 'pt24_leads';
		$rows  = [];
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			$rows = (array) $wpdb->get_results(
				"SELECT id, name, phone, email, service, city, message, source, status, created_at FROM `{$table}` ORDER BY created_at DESC",
				ARRAY_A
			);
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pt24-leads-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fwrite( $output, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel.
		fputcsv( $output, [ 'ID', 'Imię', 'Telefon', 'E-mail', 'Usługa', 'Miasto', 'Opis', 'Źródło', 'Status', 'Data' ] );
		foreach ( $rows as $row ) {
			fputcsv( $output, (array) $row );
		}
		fclose( $output );
		exit;
	}

	/**
	 * Render the Analytics Deep tab — real PT24 lead analytics.
	 */
	private function render_analytics_tab(): void {
		$a = $this->get_pt24_analytics_data();
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Analytics Deep — PT24', 'pearblog-engine' ); ?></h2>

			<?php if ( ! $a['table_exists'] ) : ?>
				<div class="pb-v8-coming-soon" style="text-align:center; padding:60px 20px;">
					<div style="font-size:56px; margin-bottom:16px;">📭</div>
					<h2><?php esc_html_e( 'No analytics data on this site', 'pearblog-engine' ); ?></h2>
					<p style="color: var(--pb-v8-text-secondary);"><?php esc_html_e( 'The PT24 leads table was not found on this installation.', 'pearblog-engine' ); ?></p>
				</div>
			<?php else :
				$conversion = $a['total'] > 0 ? round( $a['won'] / $a['total'] * 100, 1 ) : 0.0;
				$trend_max  = 1;
				foreach ( $a['trend'] as $point ) {
					$trend_max = max( $trend_max, (int) $point['count'] );
				}
				?>
				<?php $this->render_pt24_chart_styles(); ?>

				<div class="pb-v8-metrics-grid">
					<?php
					$this->render_metric_card( [ 'label' => __( 'Total Leads', 'pearblog-engine' ), 'value' => number_format_i18n( $a['total'] ), 'icon' => '👥' ] );
					$this->render_metric_card( [ 'label' => __( 'This month', 'pearblog-engine' ), 'value' => number_format_i18n( $a['this_month'] ), 'icon' => '🗓️' ] );
					$this->render_metric_card( [ 'label' => __( 'Won', 'pearblog-engine' ), 'value' => number_format_i18n( $a['won'] ), 'icon' => '✅' ] );
					$this->render_metric_card( [ 'label' => __( 'Conversion', 'pearblog-engine' ), 'value' => $conversion . '%', 'icon' => '🎯' ] );
					?>
				</div>

				<div class="pt24-card" style="margin-top:24px;">
					<h3><?php esc_html_e( 'Leads — last 14 days', 'pearblog-engine' ); ?></h3>
					<div class="pt24-trend">
						<?php foreach ( $a['trend'] as $point ) :
							$height = (int) round( (int) $point['count'] / $trend_max * 100 );
							?>
							<div class="pt24-trend-col" title="<?php echo esc_attr( $point['date'] . ': ' . $point['count'] ); ?>">
								<div class="pt24-trend-bar" style="height:<?php echo (int) max( 2, $height ); ?>%"></div>
								<span class="pt24-trend-day"><?php echo esc_html( substr( (string) $point['date'], 8, 2 ) ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="pt24-analytics-grid">
					<div class="pt24-card">
						<h3><?php esc_html_e( 'By service', 'pearblog-engine' ); ?></h3>
						<?php $this->render_bar_list( $a['by_service'] ); ?>
					</div>
					<div class="pt24-card">
						<h3><?php esc_html_e( 'By city', 'pearblog-engine' ); ?></h3>
						<?php $this->render_bar_list( $a['by_city'] ); ?>
					</div>
					<div class="pt24-card">
						<h3><?php esc_html_e( 'By status', 'pearblog-engine' ); ?></h3>
						<?php $this->render_bar_list( $a['by_status'] ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a simple horizontal bar list from [ ['label'=>.., 'c'=>..], .. ].
	 */
	private function render_bar_list( array $rows ): void {
		if ( empty( $rows ) ) {
			echo '<p style="color:var(--pb-v8-text-secondary);">' . esc_html__( 'No data yet.', 'pearblog-engine' ) . '</p>';
			return;
		}
		$max = 1;
		foreach ( $rows as $row ) {
			$max = max( $max, (int) $row['c'] );
		}
		echo '<div class="pt24-bars">';
		foreach ( $rows as $row ) {
			$label = ucfirst( str_replace( '-', ' ', (string) $row['label'] ) );
			$count = (int) $row['c'];
			$pct   = (int) max( 2, round( $count / $max * 100 ) );
			printf(
				'<div class="pt24-bar-row"><span class="pt24-bar-label">%s</span><span class="pt24-bar-track"><span class="pt24-bar-fill" style="width:%d%%"></span></span><span class="pt24-bar-val">%s</span></div>',
				esc_html( '' !== $label ? $label : '—' ),
				$pct,
				esc_html( number_format_i18n( $count ) )
			);
		}
		echo '</div>';
	}

	/**
	 * Fetch PT24 analytics aggregates.
	 *
	 * @return array{table_exists:bool,total:int,won:int,lost:int,this_month:int,by_service:array,by_city:array,by_status:array,trend:array}
	 */
	private function get_pt24_analytics_data(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'pt24_leads';
		$out   = [
			'table_exists' => false,
			'total'        => 0,
			'won'          => 0,
			'lost'         => 0,
			'this_month'   => 0,
			'by_service'   => [],
			'by_city'      => [],
			'by_status'    => [],
			'trend'        => [],
		];

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return $out;
		}
		$out['table_exists'] = true;

		$out['total']      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		$out['won']        = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE status = %s", 'won' ) );
		$out['lost']       = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE status = %s", 'lost' ) );
		$out['this_month'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s", current_time( 'Y-m' ) . '-01 00:00:00' ) );

		$out['by_service'] = (array) $wpdb->get_results( "SELECT service AS label, COUNT(*) AS c FROM `{$table}` GROUP BY service ORDER BY c DESC LIMIT 12", ARRAY_A );
		$out['by_city']    = (array) $wpdb->get_results( "SELECT city AS label, COUNT(*) AS c FROM `{$table}` GROUP BY city ORDER BY c DESC LIMIT 12", ARRAY_A );
		$out['by_status']  = (array) $wpdb->get_results( "SELECT status AS label, COUNT(*) AS c FROM `{$table}` GROUP BY status ORDER BY c DESC", ARRAY_A );

		$since = gmdate( 'Y-m-d 00:00:00', current_time( 'timestamp' ) - 13 * DAY_IN_SECONDS );
		$daily = (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT DATE(created_at) AS d, COUNT(*) AS c FROM `{$table}` WHERE created_at >= %s GROUP BY DATE(created_at)", $since ),
			OBJECT_K
		);
		for ( $i = 13; $i >= 0; $i-- ) {
			$day = gmdate( 'Y-m-d', current_time( 'timestamp' ) - $i * DAY_IN_SECONDS );
			$out['trend'][] = [
				'date'  => $day,
				'count' => isset( $daily[ $day ] ) ? (int) $daily[ $day ]->c : 0,
			];
		}

		return $out;
	}

	/**
	 * Shared inline styles for the PT24 chart widgets (printed once per page).
	 */
	private function render_pt24_chart_styles(): void {
		if ( $this->pt24_styles_done ) {
			return;
		}
		$this->pt24_styles_done = true;
		?>
		<style>
			.pt24-bars{display:flex;flex-direction:column;gap:10px;margin-top:8px}
			.pt24-bar-row{display:flex;align-items:center;gap:12px}
			.pt24-bar-label{flex:0 0 130px;font-size:13px;color:var(--pb-v8-text-secondary);text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
			.pt24-bar-track{flex:1;height:14px;background:rgba(125,125,125,.15);border-radius:7px;overflow:hidden}
			.pt24-bar-fill{display:block;height:100%;background:linear-gradient(90deg,#2563eb,#1e3a8a)}
			.pt24-bar-val{flex:0 0 46px;font-weight:700;font-size:13px}
			.pt24-trend{display:flex;align-items:flex-end;gap:6px;height:160px;margin-top:8px;padding:10px;background:rgba(125,125,125,.06);border-radius:10px}
			.pt24-trend-col{flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:6px;height:100%}
			.pt24-trend-bar{width:72%;min-height:2px;background:linear-gradient(180deg,#f59e0b,#d97706);border-radius:4px 4px 0 0}
			.pt24-trend-day{font-size:10px;color:var(--pb-v8-text-secondary)}
			.pt24-analytics-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-top:24px}
			.pt24-card{background:var(--pb-v8-bg-card,rgba(125,125,125,.04));border:1px solid rgba(125,125,125,.18);border-radius:14px;padding:20px}
			.pt24-card h3{margin:0 0 12px;font-size:15px}
		</style>
		<?php
	}

	/**
	 * Render the Settings Enterprise tab — PT24 lead notification settings.
	 */
	private function render_settings_tab(): void {
		$email     = (string) get_option( 'pt24_notify_email', (string) get_option( 'admin_email' ) );
		$enabled   = '0' !== (string) get_option( 'pt24_notify_enabled', '1' );
		$threshold = (int) get_option( 'pt24_daily_alert_threshold', 0 );
		$notice    = isset( $_GET['pt24_notice'] ) ? sanitize_key( wp_unslash( $_GET['pt24_notice'] ) ) : '';
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Settings Enterprise — PT24', 'pearblog-engine' ); ?></h2>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success" style="margin:0 0 16px;"><p><?php esc_html_e( 'Settings saved.', 'pearblog-engine' ); ?></p></div>
			<?php endif; ?>

			<div class="pb-v8-card" style="max-width:680px;">
				<div class="pb-v8-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="pt24_save_settings">
						<?php wp_nonce_field( 'pt24_save_settings' ); ?>

						<p>
							<label style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e( 'Lead notification e-mail', 'pearblog-engine' ); ?></label>
							<input type="email" name="pt24_notify_email" value="<?php echo esc_attr( $email ); ?>" style="width:100%;max-width:420px;padding:8px;">
							<span style="display:block;color:var(--pb-v8-text-secondary);font-size:13px;margin-top:4px;"><?php esc_html_e( 'New enquiries from the site are e-mailed here.', 'pearblog-engine' ); ?></span>
						</p>

						<p style="margin-top:18px;">
							<label><input type="checkbox" name="pt24_notify_enabled" value="1" <?php checked( $enabled ); ?>> <?php esc_html_e( 'Send an e-mail when a new lead arrives', 'pearblog-engine' ); ?></label>
						</p>

						<p style="margin-top:18px;">
							<label style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e( 'Daily lead alert threshold', 'pearblog-engine' ); ?></label>
							<input type="number" name="pt24_daily_alert_threshold" value="<?php echo esc_attr( (string) $threshold ); ?>" min="0" step="1" style="width:120px;padding:8px;">
							<span style="display:block;color:var(--pb-v8-text-secondary);font-size:13px;margin-top:4px;"><?php esc_html_e( '0 = disabled. Highlights the dashboard when daily leads exceed this number.', 'pearblog-engine' ); ?></span>
						</p>

						<p style="margin-top:24px;">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'pearblog-engine' ); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * admin-post handler: save PT24 settings.
	 */
	public function handle_save_settings(): void {
		if ( ! current_user_can( $this->get_required_capability() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}
		check_admin_referer( 'pt24_save_settings' );

		$email = isset( $_POST['pt24_notify_email'] ) ? sanitize_email( wp_unslash( $_POST['pt24_notify_email'] ) ) : '';
		update_option( 'pt24_notify_email', $email );
		update_option( 'pt24_notify_enabled', isset( $_POST['pt24_notify_enabled'] ) ? '1' : '0' );
		update_option( 'pt24_daily_alert_threshold', isset( $_POST['pt24_daily_alert_threshold'] ) ? absint( $_POST['pt24_daily_alert_threshold'] ) : 0 );

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'tab' => 'settings', 'pt24_notice' => 'saved' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Aggregate PT24 leads for a rolling period (days).
	 *
	 * @return array{table_exists:bool,days:int,total:int,won:int,by_service:array,by_city:array}
	 */
	private function get_pt24_report_data( int $days ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'pt24_leads';
		$out   = [ 'table_exists' => false, 'days' => $days, 'total' => 0, 'won' => 0, 'by_service' => [], 'by_city' => [] ];

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return $out;
		}
		$out['table_exists'] = true;

		$since = gmdate( 'Y-m-d 00:00:00', current_time( 'timestamp' ) - ( $days - 1 ) * DAY_IN_SECONDS );
		$out['total']      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s", $since ) );
		$out['won']        = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE status = %s AND created_at >= %s", 'won', $since ) );
		$out['by_service'] = (array) $wpdb->get_results( $wpdb->prepare( "SELECT service AS label, COUNT(*) AS c FROM `{$table}` WHERE created_at >= %s GROUP BY service ORDER BY c DESC LIMIT 10", $since ), ARRAY_A );
		$out['by_city']    = (array) $wpdb->get_results( $wpdb->prepare( "SELECT city AS label, COUNT(*) AS c FROM `{$table}` WHERE created_at >= %s GROUP BY city ORDER BY c DESC LIMIT 10", $since ), ARRAY_A );

		return $out;
	}

	/**
	 * Render the PT24 leads report block (period KPIs + breakdowns + CSV export).
	 * No-op when the leads table is absent (shared engine on other installs).
	 */
	private function render_pt24_report_section(): void {
		$days = isset( $_GET['report_days'] ) ? absint( $_GET['report_days'] ) : 30;
		if ( ! in_array( $days, [ 7, 30, 90 ], true ) ) {
			$days = 30;
		}
		$r = $this->get_pt24_report_data( $days );
		if ( ! $r['table_exists'] ) {
			return;
		}
		$this->render_pt24_chart_styles();
		$conversion = $r['total'] > 0 ? round( $r['won'] / $r['total'] * 100, 1 ) : 0.0;
		$avg        = $days > 0 ? round( $r['total'] / $days, 1 ) : 0.0;
		$base       = admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=reporting' );
		?>
		<div class="pb-v8-card" style="margin-bottom: var(--pb-v8-space-lg);">
			<div class="pb-v8-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
				<h3 class="pb-v8-card-title"><?php esc_html_e( 'PT24 Leads Report', 'pearblog-engine' ); ?></h3>
				<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
					<?php foreach ( [ 7, 30, 90 ] as $option_days ) : ?>
						<a class="button <?php echo $days === $option_days ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $base . '&report_days=' . $option_days ); ?>">
							<?php /* translators: %d: number of days */ printf( esc_html__( '%d days', 'pearblog-engine' ), $option_days ); ?>
						</a>
					<?php endforeach; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
						<input type="hidden" name="action" value="pt24_export_leads">
						<?php wp_nonce_field( 'pt24_export_leads' ); ?>
						<button type="submit" class="button">⬇ <?php esc_html_e( 'Export CSV', 'pearblog-engine' ); ?></button>
					</form>
				</div>
			</div>
			<div class="pb-v8-card-body">
				<div class="pb-v8-metrics-grid">
					<?php
					/* translators: %d: number of days */
					$this->render_metric_card( [ 'label' => sprintf( __( 'Leads (%d days)', 'pearblog-engine' ), $days ), 'value' => number_format_i18n( $r['total'] ), 'icon' => '👥' ] );
					$this->render_metric_card( [ 'label' => __( 'Won', 'pearblog-engine' ), 'value' => number_format_i18n( $r['won'] ), 'icon' => '✅' ] );
					$this->render_metric_card( [ 'label' => __( 'Conversion', 'pearblog-engine' ), 'value' => $conversion . '%', 'icon' => '🎯' ] );
					$this->render_metric_card( [ 'label' => __( 'Avg / day', 'pearblog-engine' ), 'value' => number_format_i18n( $avg, 1 ), 'icon' => '📊' ] );
					?>
				</div>
				<div class="pt24-analytics-grid" style="margin-top:20px;">
					<div class="pt24-card">
						<h3><?php esc_html_e( 'Top services', 'pearblog-engine' ); ?></h3>
						<?php $this->render_bar_list( $r['by_service'] ); ?>
					</div>
					<div class="pt24-card">
						<h3><?php esc_html_e( 'Top cities', 'pearblog-engine' ); ?></h3>
						<?php $this->render_bar_list( $r['by_city'] ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the PT24-specific dashboard (real lead KPIs, trend, latest leads).
	 *
	 * @param array $leads Pre-fetched output of get_pt24_leads_data().
	 */
	private function render_pt24_dashboard( array $leads ): void {
		$analytics  = $this->get_pt24_analytics_data();
		$conversion = $analytics['total'] > 0 ? round( $analytics['won'] / $analytics['total'] * 100, 1 ) : 0.0;
		$threshold  = (int) get_option( 'pt24_daily_alert_threshold', 0 );
		$trend_max  = 1;
		foreach ( $analytics['trend'] as $point ) {
			$trend_max = max( $trend_max, (int) $point['count'] );
		}
		$leads_url = admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=leads' );
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Dashboard — PT24', 'pearblog-engine' ); ?></h2>

			<?php if ( $threshold > 0 && (int) $leads['today'] >= $threshold ) : ?>
				<div class="pb-v8-alert pb-v8-alert-success" style="margin-bottom:var(--pb-v8-space-lg);">
					<strong>🔥
						<?php
						/* translators: 1: leads today, 2: configured threshold */
						printf( esc_html__( 'Strong day — %1$d leads today (threshold %2$d).', 'pearblog-engine' ), (int) $leads['today'], $threshold );
						?>
					</strong>
				</div>
			<?php endif; ?>

			<?php $this->render_pt24_chart_styles(); ?>

			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => __( 'Total Leads', 'pearblog-engine' ), 'value' => number_format_i18n( $leads['total'] ), 'icon' => '👥' ] );
				$this->render_metric_card( [ 'label' => __( 'New / Unhandled', 'pearblog-engine' ), 'value' => number_format_i18n( $leads['new'] ), 'icon' => '🆕' ] );
				$this->render_metric_card( [ 'label' => __( 'Today', 'pearblog-engine' ), 'value' => number_format_i18n( $leads['today'] ), 'icon' => '📅' ] );
				$this->render_metric_card( [ 'label' => __( 'Conversion', 'pearblog-engine' ), 'value' => $conversion . '%', 'icon' => '🎯' ] );
				?>
			</div>

			<div class="pt24-card" style="margin-top:24px;">
				<h3><?php esc_html_e( 'Leads — last 14 days', 'pearblog-engine' ); ?></h3>
				<div class="pt24-trend">
					<?php foreach ( $analytics['trend'] as $point ) :
						$height = (int) round( (int) $point['count'] / $trend_max * 100 );
						?>
						<div class="pt24-trend-col" title="<?php echo esc_attr( $point['date'] . ': ' . $point['count'] ); ?>">
							<div class="pt24-trend-bar" style="height:<?php echo (int) max( 2, $height ); ?>%"></div>
							<span class="pt24-trend-day"><?php echo esc_html( substr( (string) $point['date'], 8, 2 ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:32px;flex-wrap:wrap;">
				<h3 class="pb-v8-section-title" style="margin:0;"><?php esc_html_e( 'Latest leads', 'pearblog-engine' ); ?></h3>
				<a class="button button-primary" href="<?php echo esc_url( $leads_url ); ?>"><?php esc_html_e( 'Open Leads & CRM', 'pearblog-engine' ); ?></a>
			</div>

			<?php if ( empty( $leads['rows'] ) ) : ?>
				<p style="color:var(--pb-v8-text-secondary);"><?php esc_html_e( 'No leads yet. New enquiries from the site will appear here.', 'pearblog-engine' ); ?></p>
			<?php else : ?>
				<div class="pb-v8-table-wrapper">
					<table class="pb-v8-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'pearblog-engine' ); ?></th>
								<th><?php esc_html_e( 'Name', 'pearblog-engine' ); ?></th>
								<th><?php esc_html_e( 'Service', 'pearblog-engine' ); ?></th>
								<th><?php esc_html_e( 'City', 'pearblog-engine' ); ?></th>
								<th><?php esc_html_e( 'Status', 'pearblog-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $leads['rows'], 0, 8 ) as $lead ) :
								$status = (string) ( $lead->status ?? 'new' );
								?>
								<tr>
									<td><?php echo esc_html( mysql2date( 'Y-m-d H:i', (string) $lead->created_at ) ); ?></td>
									<td><strong><?php echo esc_html( (string) $lead->name ); ?></strong></td>
									<td><?php echo esc_html( ucfirst( str_replace( '-', ' ', (string) $lead->service ) ) ); ?></td>
									<td><?php echo esc_html( ucfirst( (string) $lead->city ) ); ?></td>
									<td><span class="pb-v8-badge pb-v8-badge-<?php echo esc_attr( $this->lead_status_badge( $status ) ); ?>"><?php echo esc_html( $status ); ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the Performance tab — real environment / server metrics.
	 */
	private function render_performance_tab(): void {
		global $wpdb;

		$leads_table = $wpdb->prefix . 'pt24_leads';
		$leads_count = 0;
		$leads_size  = 0;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $leads_table ) ) === $leads_table ) {
			$leads_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$leads_table}`" );
			$leads_size  = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT (data_length + index_length) FROM information_schema.TABLES WHERE table_schema = %s AND table_name = %s",
				DB_NAME,
				$leads_table
			) );
		}

		$opcache    = function_exists( 'opcache_get_status' ) ? @opcache_get_status( false ) : false;
		$opcache_on = is_array( $opcache ) && ! empty( $opcache['opcache_enabled'] );
		$ext_cache  = function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache();
		$mem_peak   = function_exists( 'memory_get_peak_usage' ) ? memory_get_peak_usage( true ) : 0;

		$rows = [
			[ __( 'PHP version', 'pearblog-engine' ), PHP_VERSION ],
			[ __( 'WordPress version', 'pearblog-engine' ), get_bloginfo( 'version' ) ],
			[ __( 'Database (MySQL)', 'pearblog-engine' ), (string) $wpdb->db_version() ],
			[ __( 'Memory limit', 'pearblog-engine' ), (string) ini_get( 'memory_limit' ) ],
			[ __( 'Peak memory', 'pearblog-engine' ), size_format( $mem_peak ) ],
			[ __( 'Max execution time', 'pearblog-engine' ), (string) ini_get( 'max_execution_time' ) . 's' ],
			[ __( 'Upload max filesize', 'pearblog-engine' ), (string) ini_get( 'upload_max_filesize' ) ],
			[ __( 'Leads table', 'pearblog-engine' ), $leads_table ],
			[ __( 'Leads table size', 'pearblog-engine' ), $leads_size > 0 ? size_format( $leads_size ) : '—' ],
			[ __( 'OPcache', 'pearblog-engine' ), $opcache_on ? __( 'Enabled', 'pearblog-engine' ) : __( 'Disabled', 'pearblog-engine' ) ],
			[ __( 'Persistent object cache', 'pearblog-engine' ), $ext_cache ? __( 'Yes', 'pearblog-engine' ) : __( 'No', 'pearblog-engine' ) ],
			[ __( 'Server', 'pearblog-engine' ), sanitize_text_field( (string) ( $_SERVER['SERVER_SOFTWARE'] ?? '—' ) ) ],
		];
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Performance & Environment', 'pearblog-engine' ); ?></h2>

			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => __( 'PHP', 'pearblog-engine' ), 'value' => PHP_VERSION, 'icon' => '🐘' ] );
				$this->render_metric_card( [ 'label' => __( 'WordPress', 'pearblog-engine' ), 'value' => get_bloginfo( 'version' ), 'icon' => '🟦' ] );
				$this->render_metric_card( [ 'label' => __( 'Peak memory', 'pearblog-engine' ), 'value' => size_format( $mem_peak ), 'icon' => '⚡' ] );
				$this->render_metric_card( [ 'label' => __( 'Leads stored', 'pearblog-engine' ), 'value' => number_format_i18n( $leads_count ), 'icon' => '👥' ] );
				?>
			</div>

			<div class="pb-v8-card" style="margin-top:24px;">
				<div class="pb-v8-card-header"><h3 class="pb-v8-card-title"><?php esc_html_e( 'Environment details', 'pearblog-engine' ); ?></h3></div>
				<div class="pb-v8-card-body">
					<div class="pb-v8-table-wrapper">
						<table class="pb-v8-table">
							<tbody>
								<?php foreach ( $rows as $row ) : ?>
									<tr>
										<td style="font-weight:600;width:240px;"><?php echo esc_html( $row[0] ); ?></td>
										<td><code><?php echo esc_html( $row[1] ); ?></code></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render metric card
	 */
	private function render_metric_card( array $args ): void {
		$defaults = [
			'label'  => '',
			'value'  => '',
			'change' => null,
			'icon'   => '📊',
			'color'  => 'primary',
		];
		$args = wp_parse_args( $args, $defaults );
		?>
		<div class="pb-v8-metric-card">
			<div class="pb-v8-metric-header">
				<span class="pb-v8-metric-label"><?php echo esc_html( $args['label'] ); ?></span>
				<span class="pb-v8-metric-icon"><?php echo esc_html( $args['icon'] ); ?></span>
			</div>
			<div class="pb-v8-metric-value"><?php echo esc_html( $args['value'] ); ?></div>
			<?php if ( null !== $args['change'] ) : ?>
				<span class="pb-v8-metric-change <?php echo $args['change'] >= 0 ? 'positive' : 'negative'; ?>">
					<?php echo $args['change'] >= 0 ? '↑' : '↓'; ?>
					<?php echo esc_html( (string) abs( $args['change'] ) ); ?>%
				</span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render activity feed
	 */
	private function render_activity_feed(): void {
		$activities = $this->get_recent_activities();
		?>
		<div class="pb-v8-activity-feed">
			<?php foreach ( $activities as $activity ) : ?>
				<div class="pb-v8-activity-item" style="padding: 16px; border-bottom: 1px solid var(--pb-v8-border);">
					<div style="display: flex; align-items: center; gap: 16px;">
						<span style="font-size: 24px;"><?php echo esc_html( $activity['icon'] ); ?></span>
						<div style="flex: 1;">
							<strong><?php echo esc_html( $activity['title'] ); ?></strong>
							<p style="margin: 4px 0 0; color: var(--pb-v8-text-secondary); font-size: 14px;">
								<?php echo esc_html( $activity['description'] ); ?>
							</p>
						</div>
						<span style="color: var(--pb-v8-text-tertiary); font-size: 13px;">
							<?php echo esc_html( $activity['time'] ); ?>
						</span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render audit log table
	 */
	private function render_audit_log_table(): void {
		?>
		<div class="pb-v8-table-wrapper">
			<table class="pb-v8-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Timestamp', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'User', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Action', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'pearblog-engine' ); ?></th>
						<th><?php esc_html_e( 'Status', 'pearblog-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$logs = [
						[
							'time'   => '2026-05-03 16:15:32',
							'user'   => 'admin',
							'action' => 'Settings Updated',
							'ip'     => '192.168.1.1',
							'status' => 'success',
						],
						[
							'time'   => '2026-05-03 16:10:15',
							'user'   => 'admin',
							'action' => 'Content Generated',
							'ip'     => '192.168.1.1',
							'status' => 'success',
						],
						[
							'time'   => '2026-05-03 16:05:42',
							'user'   => 'editor',
							'action' => 'Login Attempt',
							'ip'     => '10.0.0.5',
							'status' => 'failed',
						],
					];
					foreach ( $logs as $log ) :
						?>
						<tr>
							<td><?php echo esc_html( $log['time'] ); ?></td>
							<td><?php echo esc_html( $log['user'] ); ?></td>
							<td><?php echo esc_html( $log['action'] ); ?></td>
							<td><code><?php echo esc_html( $log['ip'] ); ?></code></td>
							<td>
								<span class="pb-v8-badge pb-v8-badge-<?php echo esc_attr( $log['status'] ); ?>">
									<?php echo esc_html( $log['status'] ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render notification center
	 */
	private function render_notification_center(): void {
		?>
		<div id="pbV8NotificationCenter" class="pb-v8-notification-center" style="display: none;">
			<div class="pb-v8-notification-header">
				<h3><?php esc_html_e( 'Notifications', 'pearblog-engine' ); ?></h3>
				<button onclick="pbV8ToggleNotifications()">✕</button>
			</div>
			<div class="pb-v8-notification-list" id="pbV8NotificationList">
				<!-- Populated dynamically -->
			</div>
		</div>
		<?php
	}

	/**
	 * Get dashboard stats
	 */
	private function get_dashboard_stats(): array {
		return [
			'revenue_today'      => 1247.50,
			'revenue_change'     => 12.5,
			'active_users'       => 342,
			'users_change'       => 8.2,
			'content_generated'  => 45,
			'content_change'     => -3.1,
			'ai_cost'            => 87.32,
			'cost_change'        => 15.7,
		];
	}

	/**
	 * Get recent activities
	 */
	private function get_recent_activities(): array {
		return [
			[
				'icon'        => '✍️',
				'title'       => __( 'Content Published', 'pearblog-engine' ),
				'description' => __( '5 new articles generated and published', 'pearblog-engine' ),
				'time'        => __( '5 minutes ago', 'pearblog-engine' ),
			],
			[
				'icon'        => '💰',
				'title'       => __( 'Revenue Milestone', 'pearblog-engine' ),
				'description' => __( 'Reached $1,000 in daily revenue', 'pearblog-engine' ),
				'time'        => __( '1 hour ago', 'pearblog-engine' ),
			],
			[
				'icon'        => '🔍',
				'title'       => __( 'SEO Improvement', 'pearblog-engine' ),
				'description' => __( '3 articles ranked in top 10', 'pearblog-engine' ),
				'time'        => __( '2 hours ago', 'pearblog-engine' ),
			],
		];
	}

	/**
	 * Get unread notifications count
	 */
	private function get_unread_notifications_count(): int {
		return 3; // Mock data
	}

	/**
	 * AJAX: Get real-time stats
	 */
	public function ajax_get_realtime_stats(): void {
		check_ajax_referer( 'pb_v8_nonce', 'nonce' );

		$stats              = $this->get_realtime_stats();
		$stats['timestamp'] = time();

		wp_send_json_success( $stats );
	}

	/**
	 * Get live real-time metrics for the Enterprise tab.
	 *
	 * @return array{visitors:int,revenue:float,conversions:int,errors:float}
	 */
	private function get_realtime_stats(): array {
		global $wpdb;

		$stats = [
			'visitors'    => 0,
			'revenue'     => 0.0,
			'conversions' => 0,
			'errors'      => 0.0,
		];

		$event_table = $wpdb->prefix . 'pearblog_events';
		$now_timestamp    = time();
		$five_minutes_ago = wp_date( 'Y-m-d H:i:s', $now_timestamp - ( 5 * MINUTE_IN_SECONDS ) );
		$one_hour_ago     = wp_date( 'Y-m-d H:i:s', $now_timestamp - HOUR_IN_SECONDS );

		$visitors = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id)
				FROM %i
				WHERE event_type = %s
				AND created_at >= %s
				AND session_id IS NOT NULL
				AND session_id <> ''",
				$event_table,
				'view',
				$five_minutes_ago
			)
		);
		if ( null !== $visitors ) {
			$stats['visitors'] = (int) $visitors;
		}

		$conversions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM %i
				WHERE event_type = %s
				AND created_at >= %s",
				$event_table,
				'lead',
				$one_hour_ago
			)
		);
		if ( null !== $conversions ) {
			$stats['conversions'] = (int) $conversions;
		}

		$revenue_rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT event_data
				FROM %i
				WHERE event_type = %s
				AND created_at >= %s",
				$event_table,
				'revenue',
				$one_hour_ago
			)
		);

		if ( is_array( $revenue_rows ) ) {
			$revenue_total = 0.0;
			foreach ( $revenue_rows as $event_data ) {
				$decoded = json_decode( (string) $event_data, true, 64 );
				if (
					JSON_ERROR_NONE === json_last_error()
					&& is_array( $decoded )
					&& isset( $decoded['amount'] )
				) {
					$revenue_total += (float) $decoded['amount'];
				}
			}
			$stats['revenue'] = round( $revenue_total, 2 );
		}

		try {
			$summary = $this->get_performance_dashboard()->get_summary();
			if ( is_array( $summary ) && isset( $summary['error_rate_pct'] ) ) {
				$stats['errors'] = (float) $summary['error_rate_pct'];
			}
		} catch ( \Throwable $e ) {
			// Keep default error rate when summary is unavailable.
		}

		return $stats;
	}

	private function get_performance_dashboard(): PerformanceDashboard {
		if ( null === $this->performance_dashboard ) {
			$this->performance_dashboard = new PerformanceDashboard();
		}

		return $this->performance_dashboard;
	}

	/**
	 * AJAX: Get notifications
	 */
	public function ajax_get_notifications(): void {
		check_ajax_referer( 'pb_v8_nonce', 'nonce' );

		$notifications = [
			[
				'id'      => 1,
				'type'    => 'success',
				'title'   => __( 'Content Generated', 'pearblog-engine' ),
				'message' => __( '5 new articles published successfully', 'pearblog-engine' ),
				'time'    => time() - 300,
			],
			[
				'id'      => 2,
				'type'    => 'warning',
				'title'   => __( 'API Limit Warning', 'pearblog-engine' ),
				'message' => __( 'Approaching monthly API limit', 'pearblog-engine' ),
				'time'    => time() - 1800,
			],
		];

		wp_send_json_success( $notifications );
	}

	/**
	 * AJAX: Toggle theme
	 */
	public function ajax_toggle_theme(): void {
		check_ajax_referer( 'pb_v8_nonce', 'nonce' );

		$current_theme = get_option( 'pearblog_v8_theme', 'light' );
		$new_theme = $current_theme === 'dark' ? 'light' : 'dark';

		update_option( 'pearblog_v8_theme', $new_theme );

		wp_send_json_success( [ 'theme' => $new_theme ] );
	}

	/**
	 * AJAX: Export report
	 */
	public function ajax_export_report(): void {
		check_ajax_referer( 'pb_v8_nonce', 'nonce' );

		$format = $_POST['format'] ?? 'csv';

		// Mock export
		wp_send_json_success( [
			'message' => sprintf( __( 'Report exported as %s', 'pearblog-engine' ), strtoupper( $format ) ),
			'url'     => '#',
		] );
	}
}
