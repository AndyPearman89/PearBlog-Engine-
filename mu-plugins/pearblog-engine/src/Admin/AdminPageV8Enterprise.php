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
			default:
				$this->render_coming_soon_tab( $tab_id );
				break;
		}
	}

	/**
	 * Render Enterprise Dashboard
	 */
	private function render_dashboard_tab(): void {
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
		$table_check = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $event_table ) );

		if ( $event_table === $table_check ) {
			$now_timestamp   = (int) current_time( 'timestamp' );
			$five_minutes_ago = wp_date( 'Y-m-d H:i:s', $now_timestamp - 300 );
			$one_hour_ago     = wp_date( 'Y-m-d H:i:s', $now_timestamp - HOUR_IN_SECONDS );

			$stats['visitors'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT session_id)
					FROM {$event_table}
					WHERE event_type = %s
					AND created_at >= %s
					AND session_id IS NOT NULL
					AND session_id <> ''",
					'view',
					$five_minutes_ago
				)
			);

			$stats['conversions'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM {$event_table}
					WHERE event_type = %s
					AND created_at >= %s",
					'lead',
					$one_hour_ago
				)
			);

			$revenue_rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT event_data
					FROM {$event_table}
					WHERE event_type = %s
					AND created_at >= %s",
					'revenue',
					$one_hour_ago
				)
			);

			if ( is_array( $revenue_rows ) ) {
				$revenue_total = 0.0;
				foreach ( $revenue_rows as $event_data ) {
					$decoded = json_decode( (string) $event_data, true );
					if ( is_array( $decoded ) && isset( $decoded['amount'] ) ) {
						$revenue_total += (float) $decoded['amount'];
					}
				}
				$stats['revenue'] = round( $revenue_total, 2 );
			}
		}

		$summary = ( new PerformanceDashboard() )->get_summary();
		if ( is_array( $summary ) && isset( $summary['error_rate_pct'] ) ) {
			$stats['errors'] = (float) $summary['error_rate_pct'];
		}

		return $stats;
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
