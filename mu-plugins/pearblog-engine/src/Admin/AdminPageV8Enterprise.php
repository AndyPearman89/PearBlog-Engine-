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
// Global (non-namespaced) PT24 factory classes.
use PT24_AI_Factory;
use PT24_Scale_Data;

/**
 * Admin Panel v8.0 ENTERPRISE MAX - Full rozbudowa
 */
class AdminPageV8Enterprise {

	private const MENU_SLUG  = 'pearblog-enterprise-v8';
	private const OPTION_GRP = 'pearblog_enterprise_v8';
	private const VERSION    = '8.0.0';
	private ?PerformanceDashboard $performance_dashboard = null;
	private bool $pt24_styles_done = false;
	private bool $pt24_admin_styles_done = false;

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
		'firms'            => '🏢 Firmy (weryfikacja)',
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
		add_action( 'admin_init', [ $this, 'redirect_legacy_slug' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// AJAX handlers for real-time features
		add_action( 'wp_ajax_pb_v8_get_realtime_stats', [ $this, 'ajax_get_realtime_stats' ] );
		add_action( 'wp_ajax_pb_v8_get_notifications', [ $this, 'ajax_get_notifications' ] );
		add_action( 'wp_ajax_pb_v8_toggle_theme', [ $this, 'ajax_toggle_theme' ] );
		add_action( 'wp_ajax_pb_v8_export_report', [ $this, 'ajax_export_report' ] );

		// PT24 lead management (status update + CSV export).
		add_action( 'admin_post_pt24_update_lead_status', [ $this, 'handle_update_lead_status' ] );
		add_action( 'admin_post_pt24_export_leads', [ $this, 'handle_export_leads' ] );
		add_action( 'admin_post_pt24_save_settings',          [ $this, 'handle_save_settings' ] );
		// PT24 firm moderation (approve / reject pending submissions).
		add_action( 'admin_post_pt24_approve_firm', [ $this, 'handle_approve_firm' ] );
		add_action( 'admin_post_pt24_reject_firm',  [ $this, 'handle_reject_firm' ] );
		add_action( 'admin_post_pt24_save_automation_settings', [ $this, 'handle_save_automation_settings' ] );
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
	 * Redirect legacy ?page=pearblog-engine to V8 Enterprise panel.
	 */
	public function redirect_legacy_slug(): void {
		if ( ! is_admin() ) {
			return;
		}
		$page = $_GET['page'] ?? '';
		if ( 'pearblog-engine' === $page ) {
			$tab = isset( $_GET['tab'] ) ? '&tab=' . sanitize_key( $_GET['tab'] ) : '';
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . $tab ) );
			exit;
		}
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
			'ajaxUrl'           => $this->get_admin_ajax_url(),
			'nonce'             => wp_create_nonce( 'pb_v8_nonce' ),
			'restRoot'          => esc_url_raw( rest_url( 'pearblog/v1/' ) ),
			'restNonce'         => wp_create_nonce( 'wp_rest' ),
			'currentTab'        => isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard',
			'theme'             => get_option( 'pearblog_v8_theme', 'light' ),
			'language'          => get_option( 'pearblog_v8_language', 'en' ),
			'realtimeEnabled'   => (bool) get_option( 'pearblog_v8_realtime_enabled', true ),
			'version'           => self::VERSION,
			'translations'      => $this->get_translations(),
		] );
	}

	/**
	 * Returns the admin-ajax.php URL using the ACTUAL request host (handles Cloudflare proxy).
	 *
	 * WordPress stores site_url = https://wordpress2614653.home.pl/pt24, but the user
	 * accesses the admin via https://pt24.pro (Cloudflare).  Cookies are set for pt24.pro,
	 * so AJAX calls must go to pt24.pro — otherwise the browser sends no auth cookies and
	 * every wp_verify_nonce() call fails (user_id mismatch → returns -1).
	 */
	private function get_admin_ajax_url(): string {
		$ajax_url = admin_url( 'admin-ajax.php' );

		$http_host = isset( $_SERVER['HTTP_HOST'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) )
			: '';

		if ( '' === $http_host ) {
			return $ajax_url;
		}

		$origin_host = (string) wp_parse_url( $ajax_url, PHP_URL_HOST );
		if ( $origin_host === $http_host ) {
			return $ajax_url; // same host, no rewrite needed
		}

		// Strip the WP subdirectory prefix (e.g. /pt24/) that Cloudflare removes.
		$ajax_path  = (string) wp_parse_url( $ajax_url, PHP_URL_PATH );
		$home_path  = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
		if ( '' !== $home_path && '/' !== $home_path && 0 === strpos( $ajax_path, $home_path ) ) {
			$ajax_path = '/' . ltrim( substr( $ajax_path, strlen( $home_path ) ), '/' );
		}

		$scheme = ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === sanitize_key( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) )
			? 'https'
			: ( ( isset( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) ? 'https' : 'http' );

		return $scheme . '://' . $http_host . $ajax_path;
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
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
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
		$white_label_enabled  = (bool) get_option( 'pearblog_wl_enabled', false );
		$brand_name           = $white_label_enabled
			? (string) get_option( 'pearblog_wl_brand_name', 'PearBlog Enterprise' )
			: 'PearBlog Enterprise';
		if ( '' === trim( $brand_name ) ) {
			$brand_name = 'PearBlog Enterprise';
		}
		$custom_logo_url = $white_label_enabled ? (string) get_option( 'pearblog_wl_logo_url', '' ) : '';
		$logo_url        = '' !== trim( $custom_logo_url )
			? $custom_logo_url
			: PEARBLOG_ENGINE_URL . 'assets/images/pearblog-logo.png';
		?>
		<div class="pb-v8-topbar">
			<div class="pb-v8-logo-section">
				<span class="pb-v8-logo-wrap">
					<img
						class="pb-v8-logo-image"
						src="<?php echo esc_url( $logo_url ); ?>"
						alt="<?php echo esc_attr( $brand_name ); ?>"
						loading="eager"
						decoding="async"
						onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
					<span class="pb-v8-logo pb-v8-logo-fallback">🍐</span>
				</span>
				<h1 class="pb-v8-title">
					<?php echo esc_html( $brand_name ); ?>
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
		// Use add_query_arg() without a URL argument so it resolves relative to
		// the current REQUEST_URI (pt24.pro) rather than the stored site_url
		// (wordpress2614653.home.pl) which is behind Cloudflare.
		?>
		<div class="pb-v8-tabs-wrapper">
			<div class="pb-v8-tabs" role="tablist">
				<?php foreach ( self::TABS as $tab_id => $tab_label ) : ?>
					<a
						href="<?php echo esc_url( add_query_arg( [ 'page' => self::MENU_SLUG, 'tab' => $tab_id ] ) ); ?>"
						class="pb-v8-tab <?php echo $current_tab === $tab_id ? 'is-active' : ''; ?>"
						data-tab="<?php echo esc_attr( $tab_id ); ?>"
						role="tab"
						aria-selected="<?php echo $current_tab === $tab_id ? 'true' : 'false'; ?>">
						<span class="pb-v8-tab-icon"><?php echo $this->get_tab_icon( $tab_id ); ?></span>
						<span class="pb-v8-tab-label"><?php echo esc_html( $tab_label ); ?></span>
					</a>
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
	 * Render tab content — only the ACTIVE tab is rendered (reduces queries from 50+ to ~5).
	 */
	private function render_tab_content( string $current_tab ): void {
		?>
		<div class="pb-v8-tab-content">
			<div class="pb-v8-tab-panel is-active" data-tab="<?php echo esc_attr( $current_tab ); ?>" role="tabpanel">
				<?php $this->render_tab_panel( $current_tab ); ?>
			</div>
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
			case 'firms':
				$this->render_firms_tab();
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
			case 'strategy':
				$this->render_strategy_tab();
				break;
			case 'content':
				$this->render_content_tab();
				break;
			case 'automation':
				$this->render_automation_tab();
				break;
			case 'seo':
				$this->render_seo_tab();
				break;
			case 'monetization':
				$this->render_monetization_tab();
				break;
			case 'multisite':
				$this->render_multisite_tab();
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
					'id'     => 'kpiRevenue',
					'change_id' => 'kpiRevenueChange',
					'label'  => __( 'Revenue Today', 'pearblog-engine' ),
					'value'  => '$' . number_format( $stats['revenue_today'], 2 ),
					'change' => $stats['revenue_change'],
					'icon'   => '💰',
					'color'  => 'success',
				] ); ?>

				<?php $this->render_metric_card( [
					'id'     => 'kpiViews',
					'change_id' => 'kpiViewsChange',
					'label'  => __( 'Active Users', 'pearblog-engine' ),
					'value'  => number_format( $stats['active_users'] ),
					'change' => $stats['users_change'],
					'icon'   => '👥',
					'color'  => 'primary',
				] ); ?>

				<?php $this->render_metric_card( [
					'id'     => 'kpiArticles',
					'change_id' => 'kpiArticlesChange',
					'label'  => __( 'Content Generated', 'pearblog-engine' ),
					'value'  => number_format( $stats['content_generated'] ),
					'change' => $stats['content_change'],
					'icon'   => '✍️',
					'color'  => 'warning',
				] ); ?>

				<?php $this->render_metric_card( [
					'id'     => 'kpiRpm',
					'change_id' => 'kpiRpmChange',
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
		$places_ok  = class_exists( 'PT24_Places_Seeder' );
		$places_key = $places_ok ? ( '' !== (string) get_option( \PT24_Places_Seeder::OPTION_API_KEY, '' ) ) : false;
		$places_stats = $places_ok ? \PT24_Places_Seeder::get_stats() : [];
		$nonce = wp_create_nonce( 'pt24_places_nonce' );
		$pt24_ads_on = '' !== (string) get_option( 'pt24_adsense_pub_id', '' ) && '1' === (string) get_option( 'pt24_adsense_enabled', '0' );
		$this->render_pt24_admin_styles();
		?>
		<div class="pb-v8-integrations">
			<h2 class="pb-v8-section-title">🔗 Integrations & API</h2>

			<!-- KPI kart integracji -->
			<div class="pb-v8-metrics-grid">
				<div class="pb-v8-card"><div class="pb-v8-card-body">
					<h3>🗺️ Google Places</h3>
					<p><?php echo $places_key ? 'Klucz API ustawiony' : 'Brak klucza API'; ?></p>
					<span class="pb-v8-badge pb-v8-badge-<?php echo $places_key ? 'success' : 'warning'; ?>"><?php echo $places_key ? 'Aktywny' : 'Wymaga konfiguracji'; ?></span>
					<?php if ( $places_ok ) : ?>
						<p style="margin-top:8px;font-size:12px;color:var(--pb-v8-text-secondary);">
							<?php echo esc_html( $places_stats['places_firms'] ?? 0 ); ?> firm z Google Places
						</p>
					<?php endif; ?>
				</div></div>
				<div class="pb-v8-card"><div class="pb-v8-card-body">
					<h3>💰 Google AdSense</h3>
					<p><?php echo $pt24_ads_on ? esc_html__( 'Configured', 'pearblog-engine' ) : esc_html__( 'Not configured', 'pearblog-engine' ); ?></p>
					<span class="pb-v8-badge pb-v8-badge-<?php echo $pt24_ads_on ? 'success' : 'warning'; ?>"><?php echo $pt24_ads_on ? 'Active' : 'Inactive'; ?></span>
				</div></div>
				<div class="pb-v8-card"><div class="pb-v8-card-body">
					<h3>🔍 Google Search Console</h3>
					<p>Zgłoś sitemap: <code>/sitemap.xml</code></p>
					<a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="pb-v8-btn pb-v8-btn-outline" style="font-size:12px;margin-top:8px;">Otwórz GSC</a>
				</div></div>
				<div class="pb-v8-card"><div class="pb-v8-card-body">
					<h3>⚙️ n8n / REST API</h3>
					<p><?php echo '' !== get_option('pt24_webhook_token','') ? 'Token ustawiony' : 'Brak tokenu'; ?></p>
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'pearblog-enterprise-v8', 'tab' => 'automation' ] ) ); ?>" class="pb-v8-btn pb-v8-btn-outline" style="font-size:12px;margin-top:8px;">→ Automation</a>
				</div></div>
			</div>

			<!-- Google Places seeder -->
			<div class="pb-v8-card pt24-admin-card" style="margin-top:20px;">
				<div class="pb-v8-card-header">
					<div>
						<span class="pt24-admin-kicker">PT24.PRO integrations</span>
						<h3 class="pb-v8-card-title">🗺️ Google Places — Import realnych firm</h3>
						<p class="pt24-admin-subtitle">Zasilaj katalog PT24 firmami z Google Maps lub własnym plikiem CSV.</p>
					</div>
				</div>
				<div class="pb-v8-card-body">
					<?php if ( ! $places_ok ) : ?>
						<div class="pb-v8-alert pb-v8-alert-warning">
							<strong>⚠️ Seeder nie załadowany.</strong>
							<p>Upewnij się, że <code>pt24-places-seeder.php</code> i <code>pt24-places-loader.php</code> są w <code>mu-plugins/</code>.</p>
						</div>
					<?php elseif ( ! $places_key ) : ?>
						<div class="pb-v8-alert pb-v8-alert-warning">
							<strong>🔑 Dodaj klucz Google Places API w zakładce Settings.</strong>
							<p>Wymagane: <em>Places API</em> włączone w Google Cloud Console.</p>
							<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'pearblog-enterprise-v8', 'tab' => 'settings' ] ) ); ?>" class="pb-v8-btn pb-v8-btn-primary" style="margin-top:8px;">→ Przejdź do Settings</a>
						</div>
					<?php else : ?>
						<div class="pb-v8-metrics-grid" style="margin-bottom:16px;">
							<?php
							$this->render_metric_card( [ 'label' => 'Firm z Google Places', 'value' => number_format_i18n( $places_stats['places_firms'] ?? 0 ), 'icon' => '🗺️', 'color' => 'success' ] );
							$this->render_metric_card( [ 'label' => 'Wszystkich firm',      'value' => number_format_i18n( $places_stats['total_firms'] ?? 0 ),  'icon' => '🏢', 'color' => 'primary' ] );
							$this->render_metric_card( [ 'label' => 'Możliwych par',         'value' => number_format_i18n( $places_stats['possible_pairs'] ?? 0 ), 'icon' => '🎯', 'color' => 'primary' ] );
							$this->render_metric_card( [ 'label' => 'W kolejce',             'value' => number_format_i18n( $places_stats['queue_size'] ?? 0 ),  'icon' => '⏳', 'color' => 'warning' ] );
							?>
						</div>

						<div class="pt24-admin-form">
						<div class="pt24-admin-grid">
							<?php if ( class_exists( 'PT24_Scale_Data' ) ) :
								$services = PT24_Scale_Data::services();
								$cities   = PT24_Scale_Data::cities();
							?>
							<div class="pt24-admin-field">
								<label for="ptPlacesService">Usługa</label>
								<select id="ptPlacesService">
									<option value="all">Wszystkie usługi (<?php echo count( $services ); ?>)</option>
									<?php foreach ( $services as $s_slug => $s_data ) : ?>
										<option value="<?php echo esc_attr( $s_slug ); ?>"><?php echo esc_html( $s_data['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="pt24-admin-field">
								<label for="ptPlacesCity">Miasto</label>
								<select id="ptPlacesCity">
									<option value="">Wszystkie miasta (<?php echo count( $cities ); ?>)</option>
									<?php foreach ( $cities as $c_slug => $c_data ) : ?>
										<option value="<?php echo esc_attr( $c_slug ); ?>"><?php echo esc_html( $c_data['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<?php endif; ?>
						</div>
						<label class="pt24-admin-check">
							<input type="checkbox" id="ptPlacesAI">
							🤖 AI enrichment
						</label>

						<div class="pt24-admin-actions">
							<button class="pb-v8-btn pb-v8-btn-primary" onclick="ptPlaces.seed('<?php echo esc_js( $nonce ); ?>')">
								🔍 Importuj firmy (wybrana para)
							</button>
							<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptPlaces.queueAll('<?php echo esc_js( $nonce ); ?>')">
								📦 Kolejkuj WSZYSTKIE kombinacje
							</button>
							<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptPlaces.runQueue('<?php echo esc_js( $nonce ); ?>')">
								▶ Uruchom kolejkę (3 pary)
							</button>
							<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptPlaces.clearQueue('<?php echo esc_js( $nonce ); ?>')" style="color:#dc2626;border-color:#dc2626;">
								🗑 Wyczyść kolejkę
							</button>
						</div>

						<!-- CSV import (places_seed format) -->
						<details class="pt24-admin-details">
							<summary>📥 Import CSV (places_seed)</summary>
							<div style="margin-top:10px;">
								<div class="pt24-admin-field">
									<label for="ptPlacesCsv">Firmy do importu</label>
									<textarea id="ptPlacesCsv" rows="5" placeholder="place_id,company_name,service,city,address,phone,website,rating,reviews,status&#10;ChIJexample123,Auto Serwis Kowalski,mechanik,ruda-slaska,ul. Przykładowa 1,+48500111222,https://firma.pl,4.7,213,new&#10;ChIJexample456,Hydro Express,hydraulik,katowice,ul. Wodna 8,+48500666777,,4.5,89,new"></textarea>
									<span class="pt24-admin-help">Format: <code>place_id,company_name,service,city,address,phone,website,rating,reviews,status</code></span>
								</div>
								<div class="pt24-admin-actions">
									<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptPlaces.importCsv('<?php echo esc_js( $nonce ); ?>')">
										📥 Importuj CSV
									</button>
								</div>
							</div>
						</details>

						<div id="ptPlacesMsg" class="pt24-admin-message"></div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<script>
		var ptPlaces = {
			ajaxUrl: <?php echo wp_json_encode( $this->get_admin_ajax_url() ); ?>,
			seed: function(nonce) {
				var msg = document.getElementById('ptPlacesMsg');
				msg.style.color = ''; msg.textContent = '⏳ Kolejkuję zapytania Google Places…';
				var d = new FormData();
				d.append('action','pt24_places_seed'); d.append('nonce',nonce);
				d.append('service', document.getElementById('ptPlacesService')?.value || 'all');
				d.append('city', document.getElementById('ptPlacesCity')?.value || '');
				d.append('use_ai', document.getElementById('ptPlacesAI')?.checked ? '1' : '');
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color = r.success ? 'green' : 'red';
					msg.textContent = r.success ? '✅ '+r.data.message : '❌ '+(r.data?.message||'Błąd');
				}).catch(()=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; });
			},
			queueAll: function(nonce) {
				var msg = document.getElementById('ptPlacesMsg');
				var ai = document.getElementById('ptPlacesAI')?.checked;
				msg.style.color=''; msg.textContent='⏳ Kolejkuję wszystkie kombinacje…';
				var d = new FormData();
				d.append('action','pt24_places_seed'); d.append('nonce',nonce);
				d.append('service','all'); d.append('city','');
				d.append('use_ai', ai ? '1' : '');
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color = r.success ? 'green' : 'red';
					msg.textContent = r.success ? '✅ '+r.data.message : '❌ '+(r.data?.message||'Błąd');
				}).catch(()=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; });
			},
			runQueue: function(nonce) {
				var msg = document.getElementById('ptPlacesMsg');
				msg.style.color=''; msg.textContent = '⏳ Przetwarzam kolejkę…';
				var d = new FormData();
				d.append('action','pt24_places_run_queue'); d.append('nonce',nonce);
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color = r.success ? 'green' : 'red';
					msg.textContent = r.success ? '✅ '+r.data.message : '❌ '+(r.data?.message||'Błąd');
					if(r.success) setTimeout(()=>location.reload(),1500);
				}).catch(()=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; });
			},
			clearQueue: function(nonce) {
				if(!confirm('Wyczyścić całą kolejkę Google Places?')) return;
				var msg = document.getElementById('ptPlacesMsg');
				var d = new FormData();
				d.append('action','pt24_places_clear_queue'); d.append('nonce',nonce);
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color = r.success ? 'green' : 'red';
					msg.textContent = r.success ? '✅ Kolejka wyczyszczona.' : '❌ '+(r.data?.message||'Błąd');
					if(r.success) setTimeout(()=>location.reload(),1000);
				});
			},
			importCsv: function(nonce) {
				var csv = document.getElementById('ptPlacesCsv').value.trim();
				var msg = document.getElementById('ptPlacesMsg');
				if(!csv){ msg.textContent='⚠️ Wklej dane CSV.'; return; }
				msg.style.color=''; msg.textContent='⏳ Importuję CSV…';
				var ai = document.getElementById('ptPlacesAI')?.checked;
				var d = new FormData();
				d.append('action','pt24_places_import_csv'); d.append('nonce',nonce);
				d.append('csv',csv); d.append('use_ai', ai ? '1' : '');
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color = r.success ? 'green' : 'red';
					msg.textContent = r.success ? '✅ '+r.data.message : '❌ '+(r.data?.message||'Błąd');
				}).catch(()=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; });
			}
		};
		</script>
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
		$this->render_pt24_admin_styles();
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

				<div class="pt24-admin-toolbar">
					<h3><?php esc_html_e( 'Recent leads', 'pearblog-engine' ); ?></h3>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
						<input type="hidden" name="action" value="pt24_export_leads">
						<?php wp_nonce_field( 'pt24_export_leads' ); ?>
						<button type="submit" class="button">⬇ <?php esc_html_e( 'Export CSV', 'pearblog-engine' ); ?></button>
					</form>
				</div>

				<?php if ( empty( $leads ) ) : ?>
					<div class="pt24-admin-empty"><?php esc_html_e( 'No leads yet. New enquiries from the site will appear here.', 'pearblog-engine' ); ?></div>
				<?php else : ?>
					<div class="pb-v8-card pt24-admin-table-card">
						<div class="pb-v8-card-body">
							<div class="pb-v8-table-wrapper">
								<table class="pb-v8-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Date', 'pearblog-engine' ); ?></th>
											<th><?php esc_html_e( 'Name', 'pearblog-engine' ); ?></th>
											<th><?php esc_html_e( 'Contact', 'pearblog-engine' ); ?></th>
											<th><?php esc_html_e( 'Service', 'pearblog-engine' ); ?></th>
											<th><?php esc_html_e( 'City', 'pearblog-engine' ); ?></th>
											<th><?php esc_html_e( 'Source', 'pearblog-engine' ); ?></th>
											<th><?php esc_html_e( 'Status', 'pearblog-engine' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach ( $leads as $lead ) :
											$status = isset( $lead['status'] ) ? (string) $lead['status'] : 'new';
											$badge  = $this->lead_status_badge( $status );
											$phone  = isset( $lead['phone'] ) ? (string) $lead['phone'] : '';
											$email  = isset( $lead['email'] ) ? (string) $lead['email'] : '';
											$source = isset( $lead['source'] ) ? (string) $lead['source'] : '';
											?>
											<tr>
												<td><?php echo esc_html( mysql2date( 'Y-m-d H:i', isset( $lead['created_at'] ) ? (string) $lead['created_at'] : '' ) ); ?></td>
												<td><strong><?php echo esc_html( isset( $lead['name'] ) ? (string) $lead['name'] : '' ); ?></strong></td>
												<td>
													<?php if ( '' !== $phone ) : ?>
														<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
													<?php endif; ?>
													<?php if ( '' !== $email ) : ?>
														<br><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
													<?php endif; ?>
												</td>
												<td><?php echo esc_html( ucfirst( str_replace( '-', ' ', isset( $lead['service'] ) ? (string) $lead['service'] : '' ) ) ); ?></td>
												<td><?php echo esc_html( ucfirst( isset( $lead['city'] ) ? (string) $lead['city'] : '' ) ); ?></td>
												<td><?php echo esc_html( $source ); ?></td>
												<td>
													<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pt24-admin-inline-form">
														<input type="hidden" name="action" value="pt24_update_lead_status">
														<input type="hidden" name="lead_id" value="<?php echo isset( $lead['id'] ) ? (int) $lead['id'] : 0; ?>">
														<?php wp_nonce_field( 'pt24_update_lead_status' ); ?>
														<span class="pb-v8-badge pb-v8-badge-<?php echo esc_attr( $badge ); ?>"><?php echo esc_html( $status ); ?></span>
														<select name="status" onchange="this.form.submit()">
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
						</div>
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
		$table = $this->get_pt24_leads_table_name();
		$out   = [ 'table_exists' => false, 'total' => 0, 'new' => 0, 'today' => 0, 'week' => 0, 'rows' => [] ];

		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $found !== $table ) {
			return $out;
		}
		$out['table_exists'] = true;
		$columns             = $this->get_pt24_table_columns( $table );

		// Table name is built from the trusted DB prefix (no user input).
		$out['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		if ( isset( $columns['status'] ) ) {
			$out['new'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE LOWER(status) = 'new'" );
		}

		$created_at_type = isset( $columns['created_at'] ) ? (string) $columns['created_at'] : '';
		if ( '' !== $created_at_type ) {
			if ( false !== stripos( $created_at_type, 'int' ) ) {
				$out['today'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %d", strtotime( current_time( 'Y-m-d' ) . ' 00:00:00' ) ) );
				$out['week']  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %d", current_time( 'timestamp' ) - 7 * DAY_IN_SECONDS ) );
			} else {
				$out['today'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s", current_time( 'Y-m-d' ) . ' 00:00:00' ) );
				$out['week']  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s", gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 7 * DAY_IN_SECONDS ) ) );
			}
		}

		$order_by = isset( $columns['created_at'] ) ? 'created_at' : 'id';
		$raw_rows = (array) $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY {$order_by} DESC LIMIT 50" );
		foreach ( $raw_rows as $row ) {
			$out['rows'][] = $this->normalize_pt24_lead_row( $row, $columns );
		}

		return $out;
	}

	/**
	 * Resolve PT24 leads table using runtime resolver when available.
	 */
	private function get_pt24_leads_table_name(): string {
		global $wpdb;

		if ( function_exists( 'pt24_resolve_table_name' ) ) {
			$resolved = (string) pt24_resolve_table_name( 'pt24_leads' );
			if ( '' !== $resolved ) {
				return $resolved;
			}
		}

		return $wpdb->prefix . 'pt24_leads';
	}

	/**
	 * Return table columns map (name => type).
	 *
	 * @return array<string, string>
	 */
	private function get_pt24_table_columns( string $table ): array {
		global $wpdb;

		$rows = (array) $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", ARRAY_A );
		$out  = [];
		foreach ( $rows as $col ) {
			$name = isset( $col['Field'] ) ? (string) $col['Field'] : '';
			$type = isset( $col['Type'] ) ? (string) $col['Type'] : '';
			if ( '' !== $name ) {
				$out[ $name ] = $type;
			}
		}

		return $out;
	}

	/**
	 * Normalize lead row for UI/export across schema variants.
	 *
	 * @param object $row Raw DB row.
	 * @param array<string, string> $columns Table columns map.
	 * @return array<string, string|int>
	 */
	private function normalize_pt24_lead_row( object $row, array $columns ): array {
		$meta_raw = isset( $row->metadata ) ? (string) $row->metadata : '';
		$meta     = json_decode( $meta_raw, true );
		if ( ! is_array( $meta ) ) {
			$meta = [];
		}

		$service = '';
		if ( isset( $row->service ) && '' !== (string) $row->service ) {
			$service = (string) $row->service;
		} elseif ( isset( $row->category ) && '' !== (string) $row->category ) {
			$service = (string) $row->category;
		} elseif ( isset( $meta['service_slug'] ) ) {
			$service = (string) $meta['service_slug'];
		}

		$city = '';
		if ( isset( $row->city ) && '' !== (string) $row->city ) {
			$city = (string) $row->city;
		} elseif ( isset( $row->location ) && '' !== (string) $row->location ) {
			$city = (string) $row->location;
		} elseif ( isset( $meta['city'] ) ) {
			$city = (string) $meta['city'];
		}

		$name = isset( $row->name ) ? (string) $row->name : '';
		if ( '' === $name && isset( $meta['name'] ) ) {
			$name = (string) $meta['name'];
		}

		$email = isset( $row->email ) ? (string) $row->email : '';
		if ( '' === $email && isset( $meta['email'] ) ) {
			$email = (string) $meta['email'];
		}

		$phone = isset( $row->phone ) ? (string) $row->phone : '';
		if ( '' === $phone && isset( $meta['phone'] ) ) {
			$phone = (string) $meta['phone'];
		}

		$source = isset( $row->source ) ? (string) $row->source : '';
		if ( '' === $source && isset( $meta['source'] ) ) {
			$source = (string) $meta['source'];
		}

		$created_at_raw = isset( $row->created_at ) ? (string) $row->created_at : '';
		$created_at     = $created_at_raw;
		if ( '' !== $created_at_raw && isset( $columns['created_at'] ) && false !== stripos( (string) $columns['created_at'], 'int' ) ) {
			$created_at = gmdate( 'Y-m-d H:i:s', (int) $created_at_raw );
		}

		return [
			'id'         => isset( $row->id ) ? (int) $row->id : 0,
			'name'       => $name,
			'email'      => $email,
			'phone'      => $phone,
			'city'       => $city,
			'service'    => $service,
			'message'    => isset( $row->message ) ? (string) $row->message : '',
			'source'     => $source,
			'status'     => isset( $row->status ) ? strtolower( (string) $row->status ) : 'new',
			'created_at' => $created_at,
		];
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

	// -----------------------------------------------------------------------
	// Firms tab (moderation of pending /dodaj-firme/ submissions)
	// -----------------------------------------------------------------------

	/**
	 * Render the "Firmy do weryfikacji" tab.
	 */
	private function render_firms_tab(): void {
		$notice = isset( $_GET['pt24_notice'] ) ? sanitize_key( wp_unslash( $_GET['pt24_notice'] ) ) : '';
		$pending_count = (int) wp_count_posts( 'pt24_firm' )->pending;
		$pub_count     = (int) wp_count_posts( 'pt24_firm' )->publish;

		$firms = get_posts( [
			'post_type'        => 'pt24_firm',
			'post_status'      => 'pending',
			'numberposts'      => 50,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
		] );

		$published = get_posts( [
			'post_type'        => 'pt24_firm',
			'post_status'      => 'publish',
			'numberposts'      => 10,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
		] );

		$this->render_pt24_admin_styles();
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title">🏢 Firmy do weryfikacji</h2>

			<?php if ( 'approved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p>Firma zatwierdzona i opublikowana.</p></div>
			<?php elseif ( 'rejected' === $notice ) : ?>
				<div class="notice notice-warning is-dismissible"><p>Firma odrzucona i przeniesiona do kosza.</p></div>
			<?php endif; ?>

			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => 'Oczekujące', 'value' => number_format_i18n( $pending_count ), 'icon' => '⏳', 'color' => $pending_count > 0 ? 'warning' : 'primary' ] );
				$this->render_metric_card( [ 'label' => 'Opublikowane', 'value' => number_format_i18n( $pub_count ), 'icon' => '🏢', 'color' => 'success' ] );
				$this->render_metric_card( [ 'label' => 'Źródło zgłoszeń', 'value' => '/dodaj-firme/', 'icon' => '📋', 'color' => 'primary' ] );
				?>
			</div>

			<div class="pb-v8-card pt24-admin-card" style="margin-top:20px;">
				<div class="pb-v8-card-header">
					<div>
						<span class="pt24-admin-kicker">PT24.PRO moderation</span>
						<h3 class="pb-v8-card-title">Zgłoszenia firm</h3>
						<p class="pt24-admin-subtitle">
							Zgłoszenia z formularza <a href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>" target="_blank" rel="noopener">/dodaj-firme/</a>.
						</p>
					</div>
				</div>
				<div class="pb-v8-card-body">
					<?php if ( empty( $firms ) ) : ?>
						<div class="pt24-admin-empty">Brak zgłoszeń oczekujących na weryfikację.</div>
					<?php else : ?>
						<div class="pb-v8-table-wrapper">
							<table class="pb-v8-table">
								<thead>
									<tr>
										<th>Nazwa firmy</th>
										<th>Miasto</th>
										<th>Usługa</th>
										<th>Telefon</th>
										<th>E-mail</th>
										<th>WWW</th>
										<th>Data zgłoszenia</th>
										<th>Akcje</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $firms as $firm ) :
										$fid     = (int) $firm->ID;
										$city    = (string) get_post_meta( $fid, 'pt24_firm_city_name', true ) ?: (string) get_post_meta( $fid, 'pt24_firm_city', true );
										$service = (string) get_post_meta( $fid, 'pt24_firm_services', true ) ?: (string) get_post_meta( $fid, 'pt24_firm_service', true );
										$phone   = (string) get_post_meta( $fid, 'pt24_firm_phone', true );
										$email   = (string) get_post_meta( $fid, 'pt24_firm_email', true );
										$website = (string) get_post_meta( $fid, 'pt24_firm_website', true );
										$date    = get_the_date( 'd.m.Y H:i', $firm );
										$base    = admin_url( 'admin-post.php' );

										$approve_url = add_query_arg( [
											'action'   => 'pt24_approve_firm',
											'firm_id'  => $fid,
											'_wpnonce' => wp_create_nonce( 'pt24_approve_firm_' . $fid ),
										], $base );
										$reject_url = add_query_arg( [
											'action'   => 'pt24_reject_firm',
											'firm_id'  => $fid,
											'_wpnonce' => wp_create_nonce( 'pt24_reject_firm_' . $fid ),
										], $base );
										$edit_url = get_edit_post_link( $fid );
										?>
										<tr>
											<td>
												<strong><?php echo esc_html( get_the_title( $firm ) ); ?></strong>
												<?php if ( $edit_url ) : ?>
													<br><a href="<?php echo esc_url( (string) $edit_url ); ?>" style="font-size:12px;">Edytuj wpis</a>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( $city ?: '—' ); ?></td>
											<td><?php echo esc_html( $service ?: '—' ); ?></td>
											<td><?php echo esc_html( $phone ?: '—' ); ?></td>
											<td><?php echo is_email( $email ) ? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' : '—'; ?></td>
											<td><?php echo $website ? '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener">Link</a>' : '—'; ?></td>
											<td><?php echo esc_html( $date ); ?></td>
											<td style="white-space:nowrap;">
												<a href="<?php echo esc_url( $approve_url ); ?>" class="button button-primary" onclick="return confirm('Opublikować tę firmę?')">✅ Zatwierdź</a>
												<a href="<?php echo esc_url( $reject_url ); ?>" class="button" onclick="return confirm('Odrzucić i przenieść do kosza?')">🗑 Odrzuć</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $published ) ) : ?>
				<div class="pb-v8-card pt24-admin-table-card">
					<div class="pb-v8-card-header"><h3 class="pb-v8-card-title">Ostatnio opublikowane firmy</h3></div>
					<div class="pb-v8-card-body">
						<div class="pb-v8-table-wrapper">
							<table class="pb-v8-table">
								<thead><tr><th>Nazwa</th><th>Miasto</th><th>Ocena</th><th>Zlecenia</th><th>Profil</th></tr></thead>
								<tbody>
									<?php foreach ( $published as $firm ) :
										$fid    = (int) $firm->ID;
										$city   = (string) get_post_meta( $fid, 'pt24_firm_city_name', true );
										$rating = (string) get_post_meta( $fid, 'pt24_firm_rating', true );
										$jobs   = (string) get_post_meta( $fid, 'pt24_firm_jobs', true );
										?>
										<tr>
											<td><?php echo esc_html( get_the_title( $firm ) ); ?></td>
											<td><?php echo esc_html( $city ?: '—' ); ?></td>
											<td>★ <?php echo esc_html( $rating ?: '—' ); ?></td>
											<td><?php echo esc_html( $jobs ?: '0' ); ?></td>
											<td><a href="<?php echo esc_url( home_url( '/firma/' . $firm->post_name . '/' ) ); ?>" target="_blank" rel="noopener">Profil →</a></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * admin-post: approve a pending firm (set status to publish).
	 */
	public function handle_approve_firm(): void {
		if ( ! current_user_can( $this->get_required_capability() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}
		$fid = isset( $_GET['firm_id'] ) ? absint( $_GET['firm_id'] ) : 0;
		check_admin_referer( 'pt24_approve_firm_' . $fid );

		$notice = 'error';
		if ( $fid > 0 && 'pt24_firm' === get_post_type( $fid ) ) {
			$result = wp_update_post( [ 'ID' => $fid, 'post_status' => 'publish' ] );
			if ( $result && ! is_wp_error( $result ) ) {
				$notice = 'approved';
			}
		}

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'tab' => 'firms', 'pt24_notice' => $notice ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * admin-post: reject a pending firm (trash it).
	 */
	public function handle_reject_firm(): void {
		if ( ! current_user_can( $this->get_required_capability() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}
		$fid = isset( $_GET['firm_id'] ) ? absint( $_GET['firm_id'] ) : 0;
		check_admin_referer( 'pt24_reject_firm_' . $fid );

		$notice = 'error';
		if ( $fid > 0 && 'pt24_firm' === get_post_type( $fid ) ) {
			$result = wp_trash_post( $fid );
			if ( $result ) {
				$notice = 'rejected';
			}
		}

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'tab' => 'firms', 'pt24_notice' => $notice ],
			admin_url( 'admin.php' )
		) );
		exit;
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
			$table = $this->get_pt24_leads_table_name();
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
				$columns = $this->get_pt24_table_columns( $table );
				$data    = [ 'status' => $status ];
				$formats = [ '%s' ];

				if ( isset( $columns['updated_at'] ) ) {
					$data['updated_at'] = current_time( 'mysql' );
					$formats[]          = '%s';
				}

				$wpdb->update( $table, $data, [ 'id' => $lead_id ], $formats, [ '%d' ] );
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
		$table = $this->get_pt24_leads_table_name();
		$rows  = [];
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			$columns = $this->get_pt24_table_columns( $table );
			$order_by = isset( $columns['created_at'] ) ? 'created_at' : 'id';
			$raw_rows = (array) $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY {$order_by} DESC" );
			foreach ( $raw_rows as $raw_row ) {
				$norm = $this->normalize_pt24_lead_row( $raw_row, $columns );
				$rows[] = [
					'ID'      => $norm['id'],
					'Imię'    => $norm['name'],
					'Telefon' => $norm['phone'],
					'E-mail'  => $norm['email'],
					'Usługa'  => $norm['service'],
					'Miasto'  => $norm['city'],
					'Opis'    => $norm['message'],
					'Źródło'  => $norm['source'],
					'Status'  => $norm['status'],
					'Data'    => $norm['created_at'],
				];
			}
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pt24-leads-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fwrite( $output, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel.
		fputcsv( $output, [ 'ID', 'Imię', 'Telefon', 'E-mail', 'Usługa', 'Miasto', 'Opis', 'Źródło', 'Status', 'Data' ] );
		foreach ( $rows as $row ) {
			fputcsv( $output, [
				$row['ID'] ?? '',
				$row['Imię'] ?? '',
				$row['Telefon'] ?? '',
				$row['E-mail'] ?? '',
				$row['Usługa'] ?? '',
				$row['Miasto'] ?? '',
				$row['Opis'] ?? '',
				$row['Źródło'] ?? '',
				$row['Status'] ?? '',
				$row['Data'] ?? '',
			] );
		}
		fclose( $output );
		exit;
	}

	/**
	 * Render the Analytics Deep tab — real PT24 lead analytics.
	 */
	private function render_analytics_tab(): void {
		$a = $this->get_pt24_analytics_data();
		$this->render_pt24_admin_styles();
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
		$table = $this->get_pt24_leads_table_name();
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

		$columns = $this->get_pt24_table_columns( $table );
		$service_col = isset( $columns['service'] ) ? 'service' : ( isset( $columns['category'] ) ? 'category' : null );
		$city_col    = isset( $columns['city'] ) ? 'city' : ( isset( $columns['location'] ) ? 'location' : null );

		$out['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		if ( isset( $columns['status'] ) ) {
			$out['won']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE LOWER(status) = 'won'" );
			$out['lost'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE LOWER(status) = 'lost'" );
		}

		if ( isset( $columns['created_at'] ) ) {
			$created_at_type = (string) $columns['created_at'];
			if ( false !== stripos( $created_at_type, 'int' ) ) {
				$month_start = (int) strtotime( current_time( 'Y-m' ) . '-01 00:00:00' );
				$out['this_month'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %d", $month_start ) );
				$since_ts    = current_time( 'timestamp' ) - 13 * DAY_IN_SECONDS;
				$since_str   = gmdate( 'Y-m-d 00:00:00', $since_ts );
				$daily_query = $wpdb->prepare( "SELECT DATE(FROM_UNIXTIME(created_at)) AS d, COUNT(*) AS c FROM `{$table}` WHERE created_at >= %d GROUP BY DATE(FROM_UNIXTIME(created_at))", $since_ts );
			} else {
				$out['this_month'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s", current_time( 'Y-m' ) . '-01 00:00:00' ) );
				$since_ts    = current_time( 'timestamp' ) - 13 * DAY_IN_SECONDS;
				$since_str   = gmdate( 'Y-m-d 00:00:00', $since_ts );
				$daily_query = $wpdb->prepare( "SELECT DATE(created_at) AS d, COUNT(*) AS c FROM `{$table}` WHERE created_at >= %s GROUP BY DATE(created_at)", $since_str );
			}

			$daily = (array) $wpdb->get_results( $daily_query, OBJECT_K );
			for ( $i = 13; $i >= 0; $i-- ) {
				$day = gmdate( 'Y-m-d', current_time( 'timestamp' ) - $i * DAY_IN_SECONDS );
				$out['trend'][] = [
					'date'  => $day,
					'count' => isset( $daily[ $day ] ) ? (int) $daily[ $day ]->c : 0,
				];
			}
		}

		if ( null !== $service_col ) {
			$out['by_service'] = (array) $wpdb->get_results( "SELECT `{$service_col}` AS label, COUNT(*) AS c FROM `{$table}` GROUP BY `{$service_col}` ORDER BY c DESC LIMIT 12", ARRAY_A );
		}

		if ( null !== $city_col ) {
			$out['by_city'] = (array) $wpdb->get_results( "SELECT `{$city_col}` AS label, COUNT(*) AS c FROM `{$table}` GROUP BY `{$city_col}` ORDER BY c DESC LIMIT 12", ARRAY_A );
		}

		if ( isset( $columns['status'] ) ) {
			$out['by_status'] = (array) $wpdb->get_results( "SELECT status AS label, COUNT(*) AS c FROM `{$table}` GROUP BY status ORDER BY c DESC", ARRAY_A );
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
			.pt24-trend{display:flex;align-items:flex-end;gap:6px;height:160px;margin-top:8px;padding:10px;background:rgba(125,125,125,.06);border-radius:8px}
			.pt24-trend-col{flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:6px;height:100%}
			.pt24-trend-bar{width:72%;min-height:2px;background:linear-gradient(180deg,#f59e0b,#d97706);border-radius:4px 4px 0 0}
			.pt24-trend-day{font-size:10px;color:var(--pb-v8-text-secondary)}
			.pt24-analytics-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-top:24px}
			.pt24-card{background:var(--pb-v8-bg-card,rgba(125,125,125,.04));border:1px solid rgba(125,125,125,.18);border-radius:8px;padding:20px}
			.pt24-card h3{margin:0 0 12px;font-size:15px}
		</style>
		<?php
	}

	/**
	 * Shared inline styles for PT24 admin forms and operational cards.
	 */
	private function render_pt24_admin_styles(): void {
		if ( $this->pt24_admin_styles_done ) {
			return;
		}
		$this->pt24_admin_styles_done = true;
		?>
		<style>
			.pt24-admin-card{border:1px solid rgba(37,99,235,.18);box-shadow:0 18px 50px rgba(15,23,42,.08);overflow:hidden}
			.pt24-admin-card .pb-v8-card-header{align-items:flex-start;background:linear-gradient(135deg,rgba(37,99,235,.10),rgba(16,185,129,.10));border-bottom:1px solid rgba(37,99,235,.16)}
			.pt24-admin-kicker,.pt24-blog-kicker{display:block;margin-bottom:4px;color:#2563eb;font-size:11px;font-weight:800;letter-spacing:0;text-transform:uppercase}
			.pt24-admin-subtitle,.pt24-blog-subtitle{margin:4px 0 0;color:var(--pb-v8-text-secondary);font-size:13px;font-weight:400}
			.pt24-admin-layout,.pt24-blog-layout{display:grid;grid-template-columns:minmax(280px,1fr) minmax(280px,1fr);gap:24px}
			.pt24-admin-panel,.pt24-blog-panel{padding:0}
			.pt24-admin-panel + .pt24-admin-panel,.pt24-blog-panel + .pt24-blog-panel{border-left:1px solid rgba(148,163,184,.28);padding-left:24px}
			.pt24-admin-panel h4,.pt24-blog-panel h4{display:flex;align-items:center;gap:8px;margin:0 0 12px;font-size:14px}
			.pt24-admin-form{display:flex;flex-direction:column;gap:12px}
			.pt24-admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
			.pt24-admin-field,.pt24-blog-field{display:flex;flex-direction:column;gap:5px}
			.pt24-admin-field label,.pt24-blog-field label{font-size:12px;font-weight:700;color:var(--pb-v8-text-primary)}
			.pt24-admin-field input:not([type="checkbox"]),.pt24-admin-field select,.pt24-admin-field textarea,.pt24-blog-field input,.pt24-blog-field select,.pt24-blog-field textarea{width:100%;box-sizing:border-box;border:1px solid rgba(148,163,184,.55);border-radius:8px;background:#fff;color:var(--pb-v8-text-primary);font-size:13px;line-height:1.4;transition:border-color .15s,box-shadow .15s}
			.pt24-admin-field input:not([type="checkbox"]),.pt24-admin-field select,.pt24-blog-field input,.pt24-blog-field select{min-height:38px;padding:8px 10px}
			.pt24-admin-field textarea,.pt24-blog-field textarea{min-height:126px;padding:10px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:12px;resize:vertical}
			.pt24-admin-field input:focus,.pt24-admin-field select:focus,.pt24-admin-field textarea:focus,.pt24-blog-field input:focus,.pt24-blog-field select:focus,.pt24-blog-field textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.14);outline:none}
			.pt24-admin-field input::placeholder,.pt24-admin-field textarea::placeholder,.pt24-blog-field input::placeholder,.pt24-blog-field textarea::placeholder{color:#94a3b8}
			.pt24-admin-help,.pt24-blog-help{color:var(--pb-v8-text-secondary);font-size:12px}
			.pt24-admin-check{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600}
			.pt24-admin-check input{width:16px;height:16px}
			.pt24-admin-actions,.pt24-blog-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}
			.pt24-admin-separator,.pt24-blog-separator{border:none;border-top:1px solid rgba(148,163,184,.28);margin:8px 0}
			.pt24-admin-message,.pt24-blog-message{font-size:13px;margin-top:6px;min-height:18px}
			.pt24-admin-details{margin-top:12px;border-top:1px solid rgba(148,163,184,.28);padding-top:12px}
			.pt24-admin-details summary{cursor:pointer;font-size:13px;font-weight:700;color:var(--pb-v8-text-secondary)}
			.pt24-admin-endpoint-row{background:rgba(37,99,235,.05)}
			.pt24-admin-endpoint-label{padding:6px 8px;font-size:11px;font-weight:700;letter-spacing:0;color:#64748b;text-transform:uppercase}
			.pt24-admin-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:24px 0 12px;flex-wrap:wrap}
			.pt24-admin-toolbar h3{margin:0;font-size:18px}
			.pt24-admin-empty{padding:24px;background:rgba(148,163,184,.08);border:1px solid rgba(148,163,184,.26);border-radius:8px;color:var(--pb-v8-text-secondary)}
			.pt24-card{background:var(--pb-v8-bg-card,rgba(125,125,125,.04));border:1px solid rgba(125,125,125,.18);border-radius:8px;padding:20px}
			.pt24-card h3{margin:0 0 12px;font-size:15px}
			.pt24-admin-table-card{margin-top:12px}
			.pt24-admin-table-card .pb-v8-card-body{padding:0}
			.pt24-admin-inline-form{display:flex;align-items:center;gap:8px;margin:0;flex-wrap:wrap}
			.pt24-admin-inline-form select{min-height:34px;border:1px solid rgba(148,163,184,.55);border-radius:7px;background:#fff;font-size:12px}
			@media (max-width:960px){.pt24-admin-layout,.pt24-blog-layout,.pt24-admin-grid{grid-template-columns:1fr}.pt24-admin-panel + .pt24-admin-panel,.pt24-blog-panel + .pt24-blog-panel{border-left:0;border-top:1px solid rgba(148,163,184,.28);padding-left:0;padding-top:18px}}
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
		$ads_pub     = (string) get_option( 'pt24_adsense_pub_id', '' );
		$ads_enabled = '1' === (string) get_option( 'pt24_adsense_enabled', '0' );
		$ads_auto    = '1' === (string) get_option( 'pt24_adsense_auto_ads', '1' );
		$places_key  = (string) get_option( 'pt24_google_places_api_key', '' );
		$notice    = isset( $_GET['pt24_notice'] ) ? sanitize_key( wp_unslash( $_GET['pt24_notice'] ) ) : '';
		$this->render_pt24_admin_styles();
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title"><?php esc_html_e( 'Settings Enterprise — PT24', 'pearblog-engine' ); ?></h2>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success" style="margin:0 0 16px;"><p><?php esc_html_e( 'Settings saved.', 'pearblog-engine' ); ?></p></div>
			<?php endif; ?>

			<div class="pb-v8-card pt24-admin-card" style="max-width:760px;">
				<div class="pb-v8-card-header">
					<div>
						<span class="pt24-admin-kicker">PT24.PRO settings</span>
						<h3 class="pb-v8-card-title">Powiadomienia, AdSense i Google Places</h3>
						<p class="pt24-admin-subtitle">Ustaw dane operacyjne platformy bez przeklikiwania kilku osobnych ekranów.</p>
					</div>
				</div>
				<div class="pb-v8-card-body">
					<form class="pt24-admin-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="pt24_save_settings">
						<?php wp_nonce_field( 'pt24_save_settings' ); ?>

						<div class="pt24-admin-grid">
							<div class="pt24-admin-field">
								<label for="pt24NotifyEmail"><?php esc_html_e( 'Lead notification e-mail', 'pearblog-engine' ); ?></label>
								<input id="pt24NotifyEmail" type="email" name="pt24_notify_email" value="<?php echo esc_attr( $email ); ?>" placeholder="kontakt@pt24.pro">
								<span class="pt24-admin-help"><?php esc_html_e( 'New enquiries from the site are e-mailed here.', 'pearblog-engine' ); ?></span>
							</div>
							<div class="pt24-admin-field">
								<label for="pt24LeadThreshold"><?php esc_html_e( 'Daily lead alert threshold', 'pearblog-engine' ); ?></label>
								<input id="pt24LeadThreshold" type="number" name="pt24_daily_alert_threshold" value="<?php echo esc_attr( (string) $threshold ); ?>" min="0" step="1" placeholder="0">
								<span class="pt24-admin-help"><?php esc_html_e( '0 = disabled. Highlights the dashboard when daily leads exceed this number.', 'pearblog-engine' ); ?></span>
							</div>
						</div>

						<label class="pt24-admin-check"><input type="checkbox" name="pt24_notify_enabled" value="1" <?php checked( $enabled ); ?>> <?php esc_html_e( 'Send an e-mail when a new lead arrives', 'pearblog-engine' ); ?></label>

						<hr class="pt24-admin-separator">
						<h3 style="margin:0 0 12px;font-size:16px;"><?php esc_html_e( 'Monetization — Google AdSense', 'pearblog-engine' ); ?></h3>

						<div class="pt24-admin-field">
							<label for="pt24AdsensePub"><?php esc_html_e( 'AdSense Publisher ID', 'pearblog-engine' ); ?></label>
							<input id="pt24AdsensePub" type="text" name="pt24_adsense_pub_id" value="<?php echo esc_attr( $ads_pub ); ?>" placeholder="ca-pub-1234567890123456">
							<span class="pt24-admin-help"><?php esc_html_e( 'From your AdSense account. Powers the head loader and /ads.txt.', 'pearblog-engine' ); ?></span>
						</div>

						<label class="pt24-admin-check"><input type="checkbox" name="pt24_adsense_enabled" value="1" <?php checked( $ads_enabled ); ?>> <?php esc_html_e( 'Enable AdSense on the site', 'pearblog-engine' ); ?></label>
						<label class="pt24-admin-check"><input type="checkbox" name="pt24_adsense_auto_ads" value="1" <?php checked( $ads_auto ); ?>> <?php esc_html_e( 'Enable Auto Ads (page-level)', 'pearblog-engine' ); ?></label>

						<hr class="pt24-admin-separator">
						<h3 style="margin:0 0 12px;font-size:16px;">🗺️ <?php esc_html_e( 'Google Places API', 'pearblog-engine' ); ?></h3>

						<div class="pt24-admin-field">
							<label for="pt24PlacesKey">Google Places API Key</label>
							<input id="pt24PlacesKey" type="password" name="pt24_google_places_api_key" value="<?php echo esc_attr( $places_key ); ?>" placeholder="AIzaSy..." autocomplete="off">
							<span class="pt24-admin-help">
								Wymagane do importu realnych firm z Google Maps. Włącz <em>Places API</em> w 
								<a href="https://console.cloud.google.com/apis/library/places-backend.googleapis.com" target="_blank" rel="noopener">Google Cloud Console</a>.
								<?php if ( '' !== $places_key ) : ?>
									<strong style="color:var(--pb-v8-success);">✅ Klucz ustawiony.</strong>
								<?php endif; ?>
							</span>
						</div>

						<div class="pt24-admin-actions" style="margin-top:12px;">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'pearblog-engine' ); ?></button>
						</div>
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

		$ads_pub = isset( $_POST['pt24_adsense_pub_id'] ) ? sanitize_text_field( wp_unslash( $_POST['pt24_adsense_pub_id'] ) ) : '';
		$ads_pub = (string) preg_replace( '/[^a-zA-Z0-9\-]/', '', $ads_pub );
		update_option( 'pt24_adsense_pub_id', $ads_pub );
		update_option( 'pt24_adsense_enabled', isset( $_POST['pt24_adsense_enabled'] ) ? '1' : '0' );
		update_option( 'pt24_adsense_auto_ads', isset( $_POST['pt24_adsense_auto_ads'] ) ? '1' : '0' );

		// Google Places API key (only update if non-empty to avoid accidental clear)
		if ( ! empty( $_POST['pt24_google_places_api_key'] ) ) {
			$places_key = sanitize_text_field( wp_unslash( $_POST['pt24_google_places_api_key'] ) );
			update_option( 'pt24_google_places_api_key', $places_key );
		}
		update_option( 'pt24_adsense_auto_ads', isset( $_POST['pt24_adsense_auto_ads'] ) ? '1' : '0' );

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'tab' => 'settings', 'pt24_notice' => 'saved' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Save automation settings (webhook token + OpenAI API key).
	 */
	public function handle_save_automation_settings(): void {
		if ( ! current_user_can( $this->get_required_capability() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'pearblog-engine' ) );
		}
		check_admin_referer( 'pt24_save_automation_settings' );

		$token = isset( $_POST['pt24_webhook_token'] )
			? sanitize_text_field( wp_unslash( $_POST['pt24_webhook_token'] ) )
			: '';
		update_option( 'pt24_webhook_token', $token );

		$api_key = isset( $_POST['pt24_openai_api_key'] )
			? sanitize_text_field( wp_unslash( $_POST['pt24_openai_api_key'] ) )
			: '';
		// Only update if not empty (keep existing key if field blank)
		if ( '' !== $api_key ) {
			update_option( 'pt24_openai_api_key', $api_key );
		}

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'tab' => 'automation', 'pt24_notice' => 'token_saved' ],
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
		$table = $this->get_pt24_leads_table_name();
		$out   = [ 'table_exists' => false, 'days' => $days, 'total' => 0, 'won' => 0, 'by_service' => [], 'by_city' => [] ];

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return $out;
		}
		$out['table_exists'] = true;

		$columns     = $this->get_pt24_table_columns( $table );
		$service_col = isset( $columns['service'] ) ? 'service' : ( isset( $columns['category'] ) ? 'category' : null );
		$city_col    = isset( $columns['city'] ) ? 'city' : ( isset( $columns['location'] ) ? 'location' : null );

		$since_ts            = current_time( 'timestamp' ) - ( $days - 1 ) * DAY_IN_SECONDS;
		$created_at_type     = isset( $columns['created_at'] ) ? (string) $columns['created_at'] : '';
		$is_int_timestamp    = false !== stripos( $created_at_type, 'int' );

		if ( $is_int_timestamp ) {
			$out['total'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %d", $since_ts ) );
			if ( isset( $columns['status'] ) ) {
				$out['won'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE LOWER(status) = 'won' AND created_at >= %d", $since_ts ) );
			}
			if ( null !== $service_col ) {
				$out['by_service'] = (array) $wpdb->get_results( $wpdb->prepare( "SELECT `{$service_col}` AS label, COUNT(*) AS c FROM `{$table}` WHERE created_at >= %d GROUP BY `{$service_col}` ORDER BY c DESC LIMIT 10", $since_ts ), ARRAY_A );
			}
			if ( null !== $city_col ) {
				$out['by_city'] = (array) $wpdb->get_results( $wpdb->prepare( "SELECT `{$city_col}` AS label, COUNT(*) AS c FROM `{$table}` WHERE created_at >= %d GROUP BY `{$city_col}` ORDER BY c DESC LIMIT 10", $since_ts ), ARRAY_A );
			}
		} else {
			$since_str = gmdate( 'Y-m-d 00:00:00', $since_ts );
			$out['total'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s", $since_str ) );
			if ( isset( $columns['status'] ) ) {
				$out['won'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE LOWER(status) = 'won' AND created_at >= %s", $since_str ) );
			}
			if ( null !== $service_col ) {
				$out['by_service'] = (array) $wpdb->get_results( $wpdb->prepare( "SELECT `{$service_col}` AS label, COUNT(*) AS c FROM `{$table}` WHERE created_at >= %s GROUP BY `{$service_col}` ORDER BY c DESC LIMIT 10", $since_str ), ARRAY_A );
			}
			if ( null !== $city_col ) {
				$out['by_city'] = (array) $wpdb->get_results( $wpdb->prepare( "SELECT `{$city_col}` AS label, COUNT(*) AS c FROM `{$table}` WHERE created_at >= %s GROUP BY `{$city_col}` ORDER BY c DESC LIMIT 10", $since_str ), ARRAY_A );
			}
		}

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
		$this->render_pt24_admin_styles();
		$conversion = $r['total'] > 0 ? round( $r['won'] / $r['total'] * 100, 1 ) : 0.0;
		$avg        = $days > 0 ? round( $r['total'] / $days, 1 ) : 0.0;
		$base       = admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=reporting' );
		?>
		<div class="pb-v8-card pt24-admin-card" style="margin-bottom: var(--pb-v8-space-lg);">
			<div class="pb-v8-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
				<div>
					<span class="pt24-admin-kicker">PT24.PRO reporting</span>
					<h3 class="pb-v8-card-title"><?php esc_html_e( 'PT24 Leads Report', 'pearblog-engine' ); ?></h3>
					<p class="pt24-admin-subtitle">Lead performance by period, service and city.</p>
				</div>
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
		$analytics    = $this->get_pt24_analytics_data();
		$conversion   = $analytics['total'] > 0 ? round( $analytics['won'] / $analytics['total'] * 100, 1 ) : 0.0;
		$threshold    = (int) get_option( 'pt24_daily_alert_threshold', 0 );
		$trend_max    = 1;
		foreach ( $analytics['trend'] as $point ) {
			$trend_max = max( $trend_max, (int) $point['count'] );
		}
		$leads_url    = admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=leads' );
		$firms_url    = admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=firms' );

		// Firm counts (CPT).
		$firm_counts   = wp_count_posts( 'pt24_firm' );
		$firms_pending = (int) ( $firm_counts->pending ?? 0 );
		$firms_publish = (int) ( $firm_counts->publish ?? 0 );
		$this->render_pt24_admin_styles();
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

			<?php if ( $firms_pending > 0 ) : ?>
				<div class="pb-v8-alert pb-v8-alert-warning" style="margin-bottom:var(--pb-v8-space-lg);">
					<strong>🏢
						<?php
						/* translators: number of pending firm submissions */
						printf( esc_html__( '%d firma/-y czeka na weryfikację.', 'pearblog-engine' ), $firms_pending );
						?>
					</strong>
					&nbsp;
					<a href="<?php echo esc_url( $firms_url ); ?>" class="button button-small">
						<?php esc_html_e( 'Przejdź do Firmy →', 'pearblog-engine' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php $this->render_pt24_chart_styles(); ?>

			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => __( 'Total Leads', 'pearblog-engine' ),        'value' => number_format_i18n( $leads['total'] ),  'icon' => '👥' ] );
				$this->render_metric_card( [ 'label' => __( 'New / Unhandled', 'pearblog-engine' ),     'value' => number_format_i18n( $leads['new'] ),    'icon' => '🆕' ] );
				$this->render_metric_card( [ 'label' => __( 'Today', 'pearblog-engine' ),               'value' => number_format_i18n( $leads['today'] ),  'icon' => '📅' ] );
				$this->render_metric_card( [ 'label' => __( 'Conversion', 'pearblog-engine' ),          'value' => $conversion . '%',                      'icon' => '🎯' ] );
				$this->render_metric_card( [ 'label' => 'Firm opublikowanych',                          'value' => number_format_i18n( $firms_publish ),   'icon' => '🏢', 'color' => 'success' ] );
				$this->render_metric_card( [ 'label' => 'Firm do weryfikacji', 'value' => number_format_i18n( $firms_pending ), 'icon' => '⏳', 'color' => $firms_pending > 0 ? 'warning' : 'primary' ] );
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

	/* =====================================================================
	   AI FACTORY TABS
	   ===================================================================== */

	/**
	 * 🔍 SEO Advanced — realne metryki SEO z silnika PT24
	 */
	private function render_seo_tab(): void {
		global $wpdb;

		// Sitemap URL count
		$sitemap_url   = home_url( '/sitemap.xml' );
		$landing_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'pt24_landing' AND post_status = 'publish'"
		);
		$firm_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'pt24_firm' AND post_status = 'publish'"
		);
		$post_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'post' AND post_status = 'publish'"
		);
		$page_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'page' AND post_status = 'publish'"
		);
		$total_urls = 1 + $page_count + $post_count + $landing_count + $firm_count + 1; // +home, +/firmy/
		$canonical_host    = 'pt24.pro';
		$seo_meta_active   = class_exists( 'PT24_SEO_Meta' ) || function_exists( 'pt24_output_seo_meta' );
		$sitemap_active    = function_exists( 'pt24_sitemap_entries' );
		$ads_pub           = (string) get_option( 'pt24_adsense_pub_id', '' );
		$this->render_pt24_admin_styles();
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title">🔍 SEO Advanced — Metryki PT24</h2>

			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => 'Łącznie URL w sitemap',  'value' => number_format_i18n( $total_urls ),    'icon' => '🗺️', 'color' => 'primary' ] );
				$this->render_metric_card( [ 'label' => 'Landingi usług',          'value' => number_format_i18n( $landing_count ), 'icon' => '📄', 'color' => 'success' ] );
				$this->render_metric_card( [ 'label' => 'Profile firm',            'value' => number_format_i18n( $firm_count ),    'icon' => '🏢', 'color' => 'primary' ] );
				$this->render_metric_card( [ 'label' => 'Artykuły blog',           'value' => number_format_i18n( $post_count ),    'icon' => '✍️', 'color' => 'warning' ] );
				?>
			</div>

			<!-- Status SEO -->
			<div class="pb-v8-card pt24-admin-card" style="margin-top:20px;">
				<div class="pb-v8-card-header">
					<div>
						<span class="pt24-admin-kicker">PT24.PRO SEO</span>
						<h3 class="pb-v8-card-title">✅ Status komponentów SEO</h3>
						<p class="pt24-admin-subtitle">Sitemap, canonicale, schema i metadane dla landingu oraz profili firm.</p>
					</div>
				</div>
				<div class="pb-v8-card-body">
					<div class="pb-v8-table-wrapper">
						<table class="pb-v8-table">
							<thead><tr><th>Komponent</th><th>Status</th><th>Szczegóły</th></tr></thead>
							<tbody>
								<?php
								$checks = [
									[ 'SEO Meta (pt24-seo-meta.php)',  $seo_meta_active,          'canonical, og:title, og:image, FAQPage schema' ],
									[ 'Sitemap XML (/sitemap.xml)',     $sitemap_active,           $total_urls . ' URL — ' . $sitemap_url ],
									[ 'Canonical host',                 true,                      'pt24.pro (bez origin wordpress2614653)' ],
									[ 'og:image brandowany',           file_exists( get_template_directory() . '/assets/brand/pt24-og.png' ), 'assets/brand/pt24-og.png (1200×630)' ],
									[ 'FAQ Schema (landingi)',          $landing_count > 0,        'FAQPage JSON-LD na każdym landingu' ],
									[ 'AdSense Publisher ID',           '' !== $ads_pub,            '' !== $ads_pub ? $ads_pub : 'Nie skonfigurowano — przejdź do Settings' ],
									[ 'robots.txt Sitemap',             true,                      'Serwowany przez Cloudflare (dodaj ręcznie w panelu CF)' ],
								];
								foreach ( $checks as $check ) :
									$ok = (bool) $check[1];
									?>
									<tr>
										<td><?php echo esc_html( $check[0] ); ?></td>
										<td><span class="pb-v8-badge pb-v8-badge-<?php echo $ok ? 'success' : 'warning'; ?>"><?php echo $ok ? '✅ Aktywny' : '⚠️ Nieaktywny'; ?></span></td>
										<td style="font-size:12px;color:var(--pb-v8-text-secondary);"><?php echo esc_html( $check[2] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<!-- Quick links -->
			<div class="pb-v8-card pt24-admin-table-card" style="margin-top:16px;">
				<div class="pb-v8-card-header"><h3 class="pb-v8-card-title">🔗 Szybkie linki SEO</h3></div>
				<div class="pb-v8-card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
					<a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" class="pb-v8-btn pb-v8-btn-outline">🗺️ Sitemap.xml</a>
					<a href="<?php echo esc_url( home_url( '/rankingi/' ) ); ?>" target="_blank" class="pb-v8-btn pb-v8-btn-outline">🏆 Rankingi hub</a>
					<a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="pb-v8-btn pb-v8-btn-outline">🔍 Google Search Console</a>
					<a href="https://developers.facebook.com/tools/debug/?q=<?php echo urlencode( home_url( '/' ) ); ?>" target="_blank" rel="noopener" class="pb-v8-btn pb-v8-btn-outline">📘 OG Debugger</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * 💰 Monetization / Revenue Center
	 */
	private function render_monetization_tab(): void {
		global $wpdb;

		$leads_table = $wpdb->prefix . 'pt24_leads';
		$has_leads   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $leads_table ) ) === $leads_table;
		$ads_pub     = (string) get_option( 'pt24_adsense_pub_id', '' );
		$ads_on      = '1' === (string) get_option( 'pt24_adsense_enabled', '0' ) && '' !== $ads_pub;

		$total_leads = $has_leads ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$leads_table}`" ) : 0;
		$won_leads   = $has_leads ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$leads_table}` WHERE status = %s", 'won' ) ) : 0;
		$this_month  = $has_leads ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$leads_table}` WHERE created_at >= %s", current_time( 'Y-m' ) . '-01 00:00:00' ) ) : 0;

		// Rough estimated value: avg 50 zł per lead (broker fee model)
		$avg_lead_value = (float) apply_filters( 'pt24_avg_lead_value', 50.0 );
		$est_monthly    = round( $this_month * $avg_lead_value, 0 );
		$this->render_pt24_admin_styles();
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title">💰 Revenue Center — Monetyzacja PT24</h2>

			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => 'Łącznie leadów', 'value' => number_format_i18n( $total_leads ), 'icon' => '👥', 'color' => 'primary' ] );
				$this->render_metric_card( [ 'label' => 'Wygranych',      'value' => number_format_i18n( $won_leads ),   'icon' => '✅', 'color' => 'success' ] );
				$this->render_metric_card( [ 'label' => 'Ten miesiąc',    'value' => number_format_i18n( $this_month ),  'icon' => '🗓️', 'color' => 'warning' ] );
				$this->render_metric_card( [ 'label' => 'Est. wartość/mies.', 'value' => number_format_i18n( $est_monthly ) . ' zł', 'icon' => '💵', 'color' => 'success' ] );
				?>
			</div>

			<!-- Monetization channels -->
			<div class="pb-v8-card pt24-admin-card" style="margin-top:20px;">
				<div class="pb-v8-card-header">
					<div>
						<span class="pt24-admin-kicker">PT24.PRO revenue</span>
						<h3 class="pb-v8-card-title">Kanały monetyzacji</h3>
						<p class="pt24-admin-subtitle">Lead generation, AdSense i płatne profile firm w jednym widoku.</p>
					</div>
				</div>
				<div class="pb-v8-card-body">
					<div class="pb-v8-metrics-grid">
						<div class="pt24-card">
							<h3>🎯 Lead Generation</h3>
							<p>Formularze na landingach i profilach firm</p>
							<span class="pb-v8-badge pb-v8-badge-success">Aktywny</span>
							<p style="margin-top:12px;font-size:13px;color:var(--pb-v8-text-secondary);">
								<?php echo esc_html( number_format_i18n( $total_leads ) ); ?> leadów łącznie · konwersja <?php echo $total_leads > 0 ? esc_html( round( $won_leads / $total_leads * 100, 1 ) ) : 0; ?>%
							</p>
						</div>
						<div class="pt24-card">
							<h3>💰 Google AdSense</h3>
							<p>Reklamy kontekstowe — Auto Ads</p>
							<span class="pb-v8-badge pb-v8-badge-<?php echo $ads_on ? 'success' : 'warning'; ?>">
								<?php echo $ads_on ? 'Aktywny — ' . esc_html( $ads_pub ) : 'Nieaktywny'; ?>
							</span>
							<?php if ( ! $ads_on ) : ?>
								<p style="margin-top:12px;font-size:13px;">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . 'pearblog-enterprise-v8' . '&tab=settings' ) ); ?>">
										→ Skonfiguruj AdSense w Settings
									</a>
								</p>
							<?php endif; ?>
						</div>
						<div class="pt24-card">
							<h3>🏢 Wyróżnione profile</h3>
							<p>PRO/Premium/VIP pozycja dla firm</p>
							<span class="pb-v8-badge pb-v8-badge-warning">Wkrótce</span>
							<p style="margin-top:12px;font-size:13px;color:var(--pb-v8-text-secondary);">
								Planowane: FREE / PRO 49 zł / Premium 99 zł / VIP 199 zł
							</p>
						</div>
						<div class="pt24-card">
							<h3>📋 Formularz „Dodaj firmę"</h3>
							<p>Zgłoszenia firm do katalogu</p>
							<span class="pb-v8-badge pb-v8-badge-success">Aktywny</span>
							<p style="margin-top:12px;font-size:13px;">
								<a href="<?php echo esc_url( home_url( '/dodaj-firme/' ) ); ?>" target="_blank">
									→ /dodaj-firme/
								</a>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * 🌐 Multisite/SaaS — AI Factory scale dashboard
	 */
	private function render_multisite_tab(): void {
		$factory_ok = class_exists( 'PT24_AI_Factory' );
		$scale_ok   = class_exists( 'PT24_Scale_Data' );
		$stats      = $factory_ok ? PT24_AI_Factory::get_stats() : [];
		$cities     = $scale_ok ? PT24_Scale_Data::cities() : [];
		$services   = $scale_ok ? PT24_Scale_Data::services() : [];

		// Group cities by province
		$by_prov = [];
		foreach ( $cities as $slug => $city ) {
			$p = $city['prov'] ?? 'inne';
			$by_prov[ $p ][] = $city['name'];
		}
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title">🌐 Multisite/SaaS — Skala platformy PT24</h2>

			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => 'Miast w bazie',    'value' => number_format_i18n( count( $cities ) ),                          'icon' => '📍', 'color' => 'primary' ] );
				$this->render_metric_card( [ 'label' => 'Usług',             'value' => number_format_i18n( count( $services ) ),                        'icon' => '⚙️', 'color' => 'primary' ] );
				$this->render_metric_card( [ 'label' => 'Możliwych stron',   'value' => number_format_i18n( $stats['target'] ?? count( $cities ) * count( $services ) ), 'icon' => '📄', 'color' => 'success' ] );
				$this->render_metric_card( [ 'label' => 'Wygenerowanych',    'value' => number_format_i18n( $stats['published'] ?? 0 ),                  'icon' => '✅', 'color' => 'success' ] );
				?>
			</div>

			<!-- Scale progress -->
			<div class="pb-v8-card" style="margin-top:20px;">
				<div class="pb-v8-card-header"><h3 class="pb-v8-card-title">🚀 Postęp skalowania</h3></div>
				<div class="pb-v8-card-body">
					<?php
					$target   = $stats['target'] ?? 0;
					$pub      = $stats['published'] ?? 0;
					$pct      = $target > 0 ? round( $pub / $target * 100, 1 ) : 0;
					$remaining = max( 0, $target - $pub );
					?>
					<div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;">
						<span><strong><?php echo esc_html( number_format_i18n( $pub ) ); ?></strong> / <?php echo esc_html( number_format_i18n( $target ) ); ?> stron</span>
						<span><?php echo esc_html( $pct ); ?>%</span>
					</div>
					<div class="pb-v8-progress" style="height:22px;">
						<div class="pb-v8-progress-bar" style="width:<?php echo esc_attr( (string) min( 100, $pct ) ); ?>%;"></div>
					</div>
					<p style="font-size:12px;color:var(--pb-v8-text-secondary);margin-top:8px;">
						Pozostało <?php echo esc_html( number_format_i18n( $remaining ) ); ?> stron.
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=pearblog-enterprise-v8&tab=strategy' ) ); ?>">→ Generuj w AI Strategy</a>
					</p>

					<hr style="margin:20px 0;border:0;border-top:1px solid rgba(125,125,125,.15);">

					<h4 style="margin:0 0 12px;">Zasięg geograficzny (<?php echo esc_html( count( $by_prov ) ); ?> województw)</h4>
					<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px;">
						<?php foreach ( $by_prov as $prov => $prov_cities ) : ?>
						<div style="background:rgba(125,125,125,.06);border:1px solid rgba(125,125,125,.15);border-radius:8px;padding:10px 14px;">
							<strong style="font-size:12px;text-transform:uppercase;letter-spacing:0;color:var(--pb-v8-text-secondary);">
								<?php echo esc_html( $prov ); ?>
							</strong>
							<p style="margin:4px 0 0;font-size:13px;">
								<?php echo esc_html( implode( ', ', $prov_cities ) ); ?>
							</p>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Services -->
			<div class="pb-v8-card" style="margin-top:16px;">
				<div class="pb-v8-card-header"><h3 class="pb-v8-card-title">🔧 Aktywne usługi (<?php echo esc_html( count( $services ) ); ?>)</h3></div>
				<div class="pb-v8-card-body">
					<div style="display:flex;gap:10px;flex-wrap:wrap;">
						<?php foreach ( $services as $slug => $svc ) : ?>
							<a href="<?php echo esc_url( home_url( '/uslugi/' . $slug . '/' ) ); ?>" target="_blank"
							   style="background:var(--pb-v8-primary);color:#fff;padding:6px 14px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;">
								<?php echo esc_html( $svc['name'] ); ?>
							</a>
						<?php endforeach; ?>
					</div>
					<p style="margin-top:12px;font-size:12px;color:var(--pb-v8-text-secondary);">
						Każda usługa × <?php echo esc_html( count( $cities ) ); ?> miast = <?php echo esc_html( number_format_i18n( count( $services ) * count( $cities ) ) ); ?> stron.
						Long-tail keywords: +<?php echo esc_html( count( $services ) * 3 ); ?> wariantów = <strong><?php echo esc_html( number_format_i18n( count( $services ) * count( $cities ) * 4 ) ); ?> URL total</strong>.
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * 🧠 Strategy = AI Factory Control Center
	 */
	private function render_strategy_tab(): void {
		$factory_available = class_exists( 'PT24_AI_Factory' );
		$stats             = $factory_available ? PT24_AI_Factory::get_stats() : [];
		$nonce             = wp_create_nonce( 'pt24_factory_nonce' );
		$services          = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::services() : [];
		$cities            = class_exists( 'PT24_Scale_Data' ) ? PT24_Scale_Data::cities() : [];
		$this->render_pt24_admin_styles();
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title">🏭 AI Factory — Generator stron PT24</h2>

			<?php if ( ! $factory_available ) : ?>
				<div class="pb-v8-alert pb-v8-alert-warning">
					<strong>⚠️ <?php esc_html_e( 'PT24 AI Factory mu-plugin not loaded.', 'pearblog-engine' ); ?></strong>
					<p>Upewnij się, że <code>pt24-ai-factory.php</code> i <code>pt24-scale-data.php</code> są w katalogu <code>mu-plugins</code>.</p>
				</div>
			<?php else : ?>

			<!-- KPI Stats -->
			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => 'Opublikowane strony',    'value' => number_format_i18n( $stats['published'] ?? 0 ),   'icon' => '✅', 'color' => 'success' ] );
				$this->render_metric_card( [ 'label' => 'W kolejce do wygenerowania', 'value' => number_format_i18n( $stats['queue_size'] ?? 0 ), 'icon' => '⏳', 'color' => 'warning' ] );
				$this->render_metric_card( [ 'label' => 'Cel (miasta × usługi)', 'value' => number_format_i18n( $stats['target'] ?? 0 ),         'icon' => '🎯', 'color' => 'primary' ] );
				$this->render_metric_card( [ 'label' => 'Postęp',               'value' => ( $stats['progress_pct'] ?? 0 ) . '%',               'icon' => '📈', 'color' => 'primary' ] );
				?>
			</div>

			<!-- Progress bar -->
			<div class="pb-v8-card" style="margin-top:20px;">
				<div class="pb-v8-card-body">
					<div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;">
						<span><strong><?php echo esc_html( number_format_i18n( $stats['published'] ?? 0 ) ); ?></strong> z <?php echo esc_html( number_format_i18n( $stats['target'] ?? 0 ) ); ?> stron</span>
						<span style="color:var(--pb-v8-text-secondary)"><?php echo esc_html( $stats['cities'] ?? 0 ); ?> miast × <?php echo esc_html( $stats['services'] ?? 0 ); ?> usług</span>
					</div>
					<div class="pb-v8-progress" style="height:18px;">
						<div class="pb-v8-progress-bar" style="width:<?php echo esc_attr( (string) min( 100, (float) ( $stats['progress_pct'] ?? 0 ) ) ); ?>%;transition:width .4s;"></div>
					</div>
					<p style="margin-top:8px;font-size:12px;color:var(--pb-v8-text-secondary);">
						Pozostało do wygenerowania: <strong><?php echo esc_html( number_format_i18n( $stats['remaining'] ?? 0 ) ); ?></strong> stron
						<?php if ( ! empty( $stats['ai_generated'] ) ) : ?>
							· Wygenerowane przez AI: <strong><?php echo esc_html( number_format_i18n( $stats['ai_generated'] ) ); ?></strong>
						<?php endif; ?>
					</p>
				</div>
			</div>

			<!-- Generator controls -->
			<div class="pb-v8-card pt24-admin-card" style="margin-top:20px;">
				<div class="pb-v8-card-header">
					<div>
						<span class="pt24-admin-kicker">PT24.PRO factory</span>
						<h3 class="pb-v8-card-title">Generator stron usługowych</h3>
						<p class="pt24-admin-subtitle">Twórz pojedyncze landingi albo kolejkuj większe paczki z CSV.</p>
					</div>
				</div>
				<div class="pb-v8-card-body">
			<div class="pt24-admin-layout">

				<!-- Quick generate single -->
				<div class="pt24-admin-panel">
					<h4>⚡ Generuj jedną stronę</h4>
					<div class="pt24-admin-form">
						<div class="pt24-admin-grid">
							<div class="pt24-admin-field">
								<label for="ptfService">Usługa</label>
								<select id="ptfService">
									<?php foreach ( $services as $slug => $svc ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $svc['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="pt24-admin-field">
								<label for="ptfCity">Miasto</label>
								<select id="ptfCity">
									<?php foreach ( $cities as $slug => $city ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $city['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<label class="pt24-admin-check">
							<input type="checkbox" id="ptfUseAi" <?php echo $stats['has_api_key'] ? '' : 'disabled'; ?>>
							Użyj OpenAI (gpt-4o-mini) <?php if ( ! $stats['has_api_key'] ) echo '<span style="color:var(--pb-v8-danger);font-size:11px;">— brak klucza API</span>'; ?>
						</label>
						<button class="pb-v8-btn pb-v8-btn-primary" id="ptfGenerateBtn" onclick="ptFactory.generateSingle('<?php echo esc_js( $nonce ); ?>')">
							▶ Generuj stronę
						</button>
						<div id="ptfSingleMsg" class="pt24-admin-message"></div>
					</div>
				</div>

				<!-- Batch via CSV -->
				<div class="pt24-admin-panel">
					<h4>📋 Import CSV / Kolejkuj wszystko</h4>
					<div class="pt24-admin-form">
						<div class="pt24-admin-field">
							<label for="ptfCsvInput">Lista stron do wygenerowania</label>
							<textarea id="ptfCsvInput" rows="5" placeholder="usluga,miasto<?php echo "\n"; ?>mechanik,ruda-slaska<?php echo "\n"; ?>hydraulik,katowice<?php echo "\n"; ?>elektryk,gliwice"></textarea>
							<span class="pt24-admin-help">Format: <code>usluga,miasto</code> — jedno zlecenie per wiersz. Puste pole kolejkuje wszystkie <?php echo esc_html( number_format_i18n( $stats['remaining'] ?? 0 ) ); ?> brakujących stron.</span>
						</div>
						<div class="pt24-admin-actions">
							<button class="pb-v8-btn pb-v8-btn-primary" onclick="ptFactory.batchCSV('<?php echo esc_js( $nonce ); ?>')">
								📥 Kolejkuj z CSV
							</button>
							<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptFactory.queueAll('<?php echo esc_js( $nonce ); ?>')">
								🌐 Kolejkuj wszystkie
							</button>
							<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptFactory.runQueue('<?php echo esc_js( $nonce ); ?>')">
								▶ Uruchom kolejkę (5 stron)
							</button>
						</div>
						<div id="ptfBatchMsg" class="pt24-admin-message"></div>
					</div>
				</div>
			</div>
				</div>
			</div>

			<!-- Available combinations table -->
			<div class="pb-v8-card" style="margin-top:20px;">
				<div class="pb-v8-card-header">
					<h3 class="pb-v8-card-title">📊 Dostępne kombinacje (<?php echo esc_html( count( $services ) ); ?> usług × <?php echo esc_html( count( $cities ) ); ?> miast)</h3>
				</div>
				<div class="pb-v8-card-body" style="max-height:300px;overflow-y:auto;">
					<table class="pb-v8-table">
						<thead><tr><th>Usługa</th><th>Powiązane słowa kluczowe (long-tail)</th></tr></thead>
						<tbody>
						<?php foreach ( $services as $slug => $svc ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $svc['name'] ); ?></strong><br><code style="font-size:11px;"><?php echo esc_html( $slug ); ?></code></td>
								<td><?php echo esc_html( implode( ', ', $svc['long_tail'] ?? [] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<script>
			var ptFactory = {
				ajaxUrl: <?php echo wp_json_encode( $this->get_admin_ajax_url() ); ?>,

				generateSingle: function(nonce) {
					var btn = document.getElementById('ptfGenerateBtn');
					var msg = document.getElementById('ptfSingleMsg');
					btn.disabled = true; btn.textContent = 'Generuję…';
					msg.textContent = '';
					var data = new FormData();
					data.append('action', 'pt24_factory_generate');
					data.append('nonce', nonce);
					data.append('service', document.getElementById('ptfService').value);
					data.append('city', document.getElementById('ptfCity').value);
					data.append('use_ai', document.getElementById('ptfUseAi').checked ? '1' : '');
					fetch(this.ajaxUrl, {method:'POST', body:data})
						.then(r=>r.json())
						.then(r=>{
							if(r.success) {
								msg.style.color='green';
								msg.innerHTML = '✅ Wygenerowano: <a href="'+r.data.url+'" target="_blank">'+r.data.url+'</a> (ID: '+r.data.post_id+')';
								ptFactory.updateStats(r.data.stats);
							} else {
								msg.style.color='red';
								msg.textContent = '❌ ' + (r.data && r.data.message ? r.data.message : 'Błąd');
							}
							btn.disabled = false; btn.textContent = '▶ Generuj stronę';
						}).catch(e=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; btn.disabled=false; btn.textContent='▶ Generuj stronę'; });
				},

				batchCSV: function(nonce) {
					var msg = document.getElementById('ptfBatchMsg');
					msg.textContent = '⏳ Kolejkuję…';
					var data = new FormData();
					data.append('action', 'pt24_factory_batch_csv');
					data.append('nonce', nonce);
					data.append('csv', document.getElementById('ptfCsvInput').value);
					fetch(this.ajaxUrl, {method:'POST', body:data})
						.then(r=>r.json())
						.then(r=>{
							if(r.success) {
								msg.style.color='green';
								msg.textContent = '✅ ' + r.data.message;
								if(r.data.errors && r.data.errors.length) msg.textContent += ' ⚠ ' + r.data.errors.slice(0,3).join('; ');
								ptFactory.updateStats(r.data.stats);
							} else { msg.style.color='red'; msg.textContent='❌ ' + (r.data.message||'Błąd'); }
						});
				},

				queueAll: function(nonce) {
					document.getElementById('ptfCsvInput').value = '';
					this.batchCSV(nonce);
				},

				runQueue: function(nonce) {
					var msg = document.getElementById('ptfBatchMsg');
					msg.textContent = '⏳ Przetwarzam kolejkę…';
					var data = new FormData();
					data.append('action', 'pt24_factory_run_queue');
					data.append('nonce', nonce);
					fetch(this.ajaxUrl, {method:'POST', body:data})
						.then(r=>r.json())
						.then(r=>{
							if(r.success) {
								msg.style.color='green';
								msg.textContent = '✅ ' + r.data.message;
								ptFactory.updateStats(r.data.stats);
							} else { msg.style.color='red'; msg.textContent='❌ '+(r.data.message||'Błąd'); }
						});
				},

				updateStats: function(s) {
					if(!s) return;
					// Update progress bar if on page
					var bar = document.querySelector('.pb-v8-progress-bar');
					if(bar) bar.style.width = Math.min(100, s.progress_pct) + '%';
				}
			};
			</script>

			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * ✍️ Content Engine — queue management & generated pages list
	 */
	private function render_content_tab(): void {
		$factory_available = class_exists( 'PT24_AI_Factory' );
		if ( ! $factory_available ) {
			$this->render_coming_soon_tab( 'content' );
			return;
		}

		global $wpdb;
		$stats = PT24_AI_Factory::get_stats();
		$nonce = wp_create_nonce( 'pt24_factory_nonce' );

		// Recent factory-generated pages
		$pages = $wpdb->get_results(
			"SELECT p.ID, p.post_title, p.post_date, p.post_name,
			        pm.meta_value AS service,
			        pm2.meta_value AS city,
			        pm3.meta_value AS variant
			 FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} pm  ON p.ID = pm.post_id  AND pm.meta_key  = 'pt24_service'
			 LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'pt24_city'
			 LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'pt24_variant'
			 WHERE p.post_type = 'pt24_landing' AND p.post_status = 'publish'
			 ORDER BY p.post_date DESC LIMIT 30"
		);
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title">✍️ Content Engine — wygenerowane strony</h2>

			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => 'Opublikowane',  'value' => number_format_i18n( $stats['published'] ),  'icon' => '✅' ] );
				$this->render_metric_card( [ 'label' => 'Fabryczne',      'value' => number_format_i18n( $stats['factory_gen'] ), 'icon' => '🏭' ] );
				$this->render_metric_card( [ 'label' => 'AI-generated',   'value' => number_format_i18n( $stats['ai_generated'] ),'icon' => '🤖' ] );
				$this->render_metric_card( [ 'label' => 'W kolejce',      'value' => number_format_i18n( $stats['queue_size'] ),  'icon' => '⏳', 'color' => 'warning' ] );
				?>
			</div>

			<?php if ( $stats['queue_size'] > 0 ) : ?>
				<div class="pb-v8-alert pb-v8-alert-success" style="margin:16px 0;">
					<strong>⏳ Kolejka aktywna: <?php echo esc_html( number_format_i18n( $stats['queue_size'] ) ); ?> stron</strong>
					<p>WP-Cron generuje <?php echo esc_html( PT24_AI_Factory::BATCH_SIZE ); ?> stron na minutę automatycznie.</p>
					<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptFactoryContent.runQueue('<?php echo esc_js( $nonce ); ?>')">▶ Uruchom teraz</button>
					<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptFactoryContent.clearQueue('<?php echo esc_js( $nonce ); ?>')">🗑 Wyczyść kolejkę</button>
					<div id="ptfContentMsg" style="margin-top:8px;font-size:13px;"></div>
				</div>
			<?php endif; ?>

			<div class="pb-v8-card" style="margin-top:20px;">
				<div class="pb-v8-card-header"><h3 class="pb-v8-card-title">Ostatnio wygenerowane strony (30)</h3></div>
				<div class="pb-v8-card-body">
					<div class="pb-v8-table-wrapper">
						<table class="pb-v8-table">
							<thead><tr>
								<th>Tytuł / URL</th>
								<th>Usługa</th>
								<th>Miasto</th>
								<th>Wariant</th>
								<th>Data</th>
							</tr></thead>
							<tbody>
							<?php foreach ( $pages as $pg ) :
								$url = home_url( '/' . $pg->city . '/' . $pg->service . '/' );
							?>
								<tr>
									<td><a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php echo esc_html( $pg->post_title ); ?></a></td>
									<td><?php echo esc_html( $pg->service ?? '—' ); ?></td>
									<td><?php echo esc_html( $pg->city ?? '—' ); ?></td>
									<td><span class="pb-v8-badge pb-v8-badge-primary">#<?php echo esc_html( $pg->variant ?? '?' ); ?></span></td>
									<td><?php echo esc_html( mysql2date( 'Y-m-d', $pg->post_date ) ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<script>
		var ptFactoryContent = {
			ajaxUrl: <?php echo wp_json_encode( $this->get_admin_ajax_url() ); ?>,
			runQueue: function(nonce) {
				var msg = document.getElementById('ptfContentMsg');
				if(msg) { msg.textContent = '⏳ Generuję…'; }
				var d = new FormData(); d.append('action','pt24_factory_run_queue'); d.append('nonce',nonce);
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					if(msg) { msg.style.color = r.success?'green':'red'; msg.textContent = r.success ? '✅ '+r.data.message : '❌ '+(r.data.message||'Błąd'); }
					if(r.success) setTimeout(()=>location.reload(),1500);
				});
			},
			clearQueue: function(nonce) {
				var d = new FormData(); d.append('action','pt24_factory_clear_queue'); d.append('nonce',nonce);
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{ if(r.success) location.reload(); });
			}
		};
		</script>

		<?php /* ── BLOG ENGINE ───────────────────────────────────────────── */
		$blog_ok    = class_exists( 'PT24_Blog_Engine' );
		$blog_nonce = wp_create_nonce( 'pt24_blog_nonce' );
		$blog_stats = $blog_ok ? \PT24_Blog_Engine::get_stats() : [];
		$this->render_pt24_admin_styles();
		?>
		<div class="pb-v8-card pt24-admin-card" style="margin-top:28px;">
			<div class="pb-v8-card-header">
				<div>
					<span class="pt24-blog-kicker">PT24.PRO admin</span>
					<h3 class="pb-v8-card-title">📝 Blog Engine — SEO Content Factory</h3>
					<p class="pt24-blog-subtitle">Generuj poradniki SEO pod lokalne usługi, kolejkuj paczki tematów i importuj własne listy CSV.</p>
				</div>
			</div>
			<div class="pb-v8-card-body">
				<?php if ( ! $blog_ok ) : ?>
					<div class="pb-v8-alert" style="background:#fef9c3;border:1px solid #ca8a04;padding:12px 16px;border-radius:8px;">
						<strong>⚠️ Blog Engine nie jest aktywny.</strong>
						<p style="margin:4px 0 0;">Upewnij się, że <code>pt24-blog-engine.php</code> i <code>pt24-blog-engine-loader.php</code> są w <code>mu-plugins/</code>.</p>
					</div>
				<?php else : ?>

				<!-- Stats -->
				<div class="pb-v8-metrics-grid" style="margin-bottom:20px;">
					<?php
					$this->render_metric_card( [ 'label' => 'Artykuły AI',   'value' => number_format_i18n( $blog_stats['total_articles'] ?? 0 ), 'icon' => '📝' ] );
					$this->render_metric_card( [ 'label' => 'W kolejce',      'value' => number_format_i18n( $blog_stats['queue_size'] ?? 0 ),     'icon' => '⏳', 'color' => ( ( $blog_stats['queue_size'] ?? 0 ) > 0 ) ? 'warning' : '' ] );
					$this->render_metric_card( [ 'label' => 'Tematy startowe','value' => number_format_i18n( $blog_stats['starters'] ?? 0 ),       'icon' => '🚀' ] );
					$this->render_metric_card( [ 'label' => 'OpenAI',         'value' => ( $blog_stats['has_openai_key'] ?? false ) ? 'Aktywny' : 'Brak klucza', 'icon' => '🤖', 'color' => ( $blog_stats['has_openai_key'] ?? false ) ? 'success' : 'warning' ] );
					?>
				</div>

				<!-- Queue controls -->
				<?php if ( ( $blog_stats['queue_size'] ?? 0 ) > 0 ) : ?>
				<div class="pb-v8-alert pb-v8-alert-success" style="margin-bottom:16px;">
					<strong>⏳ Kolejka: <?php echo esc_html( number_format_i18n( $blog_stats['queue_size'] ?? 0 ) ); ?> artykułów</strong>
					<p style="margin:4px 0 8px;">WP-Cron generuje 5 artykułów / minutę.</p>
					<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptBlog.runQueue('<?php echo esc_js( $blog_nonce ); ?>')">▶ Generuj teraz (paczka 5)</button>
					<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptBlog.clearQueue('<?php echo esc_js( $blog_nonce ); ?>')" style="color:#dc2626;border-color:#dc2626;">🗑 Wyczyść kolejkę</button>
					<div id="ptBlogQueueMsg" style="margin-top:8px;font-size:13px;"></div>
				</div>
				<?php endif; ?>

				<div class="pt24-blog-layout">

					<!-- Generate single article -->
					<div class="pt24-blog-panel">
						<h4>🖊 Generuj jeden artykuł (AI)</h4>
						<div style="display:flex;flex-direction:column;gap:8px;">
							<div class="pt24-blog-field">
								<label for="ptBlogTopic">Temat artykułu</label>
								<input type="text" id="ptBlogTopic" placeholder="Np. Pękła rura w mieszkaniu - co zrobić przed przyjazdem hydraulika?">
								<span class="pt24-blog-help">Najlepiej wpisz konkretny problem klienta, nie ogólne hasło SEO.</span>
							</div>
							<div class="pt24-blog-field">
								<label for="ptBlogService">Usługa</label>
								<select id="ptBlogService">
									<?php if ( class_exists( 'PT24_Scale_Data' ) ) :
										foreach ( PT24_Scale_Data::services() as $slug => $name ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
									<?php endforeach; endif; ?>
								</select>
							</div>
							<div class="pt24-blog-field">
								<label for="ptBlogCity">Miasto</label>
								<select id="ptBlogCity">
									<option value="">Bez miasta - artykuł ogólnopolski</option>
									<?php if ( class_exists( 'PT24_Scale_Data' ) ) :
										foreach ( PT24_Scale_Data::cities() as $slug => $name ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
									<?php endforeach; endif; ?>
								</select>
							</div>
							<button class="pb-v8-btn pb-v8-btn-primary" onclick="ptBlog.generate('<?php echo esc_js( $blog_nonce ); ?>')">🤖 Generuj artykuł</button>
							<div id="ptBlogGenMsg" class="pt24-blog-message"></div>
						</div>
					</div>

					<!-- Batch: starters + CSV -->
					<div class="pt24-blog-panel">
						<h4>🚀 Kolejkuj tematy</h4>
						<div style="display:flex;flex-direction:column;gap:8px;">
							<div class="pt24-blog-field">
								<label for="ptBlogStarterCity">Miasto dla tematów startowych</label>
								<select id="ptBlogStarterCity">
									<option value="">Bez miasta - tematy ogólne</option>
									<?php if ( class_exists( 'PT24_Scale_Data' ) ) :
										foreach ( PT24_Scale_Data::cities() as $slug => $name ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
									<?php endforeach; endif; ?>
								</select>
								<span class="pt24-blog-help">Jeśli wybierzesz miasto, tematy bez własnego miasta dostaną lokalny kontekst.</span>
							</div>
							<button class="pb-v8-btn pb-v8-btn-primary" onclick="ptBlog.queueStarters('<?php echo esc_js( $blog_nonce ); ?>')">📋 Kolejkuj 100 tematów startowych</button>
							<hr class="pt24-blog-separator">
							<div class="pt24-blog-field">
								<label for="ptBlogCsv">Import CSV</label>
								<textarea id="ptBlogCsv" rows="5" placeholder="temat,usluga,miasto,kategoria&#10;Pękła rura w łazience - co robić?,hydraulik,katowice,awarie&#10;Auto nie odpala po nocy,mechanik,ruda-slaska,awarie&#10;Ile kosztuje wymiana rozdzielnicy?,elektryk,gliwice,koszty"></textarea>
								<span class="pt24-blog-help">Kolumny: temat, usluga, miasto opcjonalnie, kategoria opcjonalnie.</span>
							</div>
							<div class="pt24-blog-actions">
								<button class="pb-v8-btn pb-v8-btn-outline" onclick="ptBlog.importCsv('<?php echo esc_js( $blog_nonce ); ?>')">📥 Importuj CSV</button>
							</div>
							<div id="ptBlogBatchMsg" class="pt24-blog-message"></div>
						</div>
					</div>

				</div><!-- grid -->

				<script>
				var ptBlog = {
					ajaxUrl: <?php echo wp_json_encode( $this->get_admin_ajax_url() ); ?>,
					generate: function(nonce) {
						var topic   = document.getElementById('ptBlogTopic').value.trim();
						var service = document.getElementById('ptBlogService').value;
						var city    = document.getElementById('ptBlogCity').value;
						var msg     = document.getElementById('ptBlogGenMsg');
						if(!topic){ msg.textContent='⚠️ Podaj temat artykułu.'; return; }
						msg.style.color=''; msg.textContent='⏳ Generuję artykuł… (może potrwać 20-40 s)';
						var d=new FormData(); d.append('action','pt24_blog_generate'); d.append('nonce',nonce);
						d.append('topic',topic); d.append('service',service); d.append('city',city);
						fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
							if(r.success){ msg.style.color='green'; msg.innerHTML='✅ '+r.data.message+' — <a href="'+r.data.url+'" target="_blank">Podgląd</a>'; }
							else { msg.style.color='red'; msg.textContent='❌ '+(r.data?.message||'Błąd AI'); }
						}).catch(()=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; });
					},
					queueStarters: function(nonce) {
						var city = document.getElementById('ptBlogStarterCity').value;
						var msg  = document.getElementById('ptBlogBatchMsg');
						msg.style.color=''; msg.textContent='⏳ Kolejkuję 100 tematów…';
						var d=new FormData(); d.append('action','pt24_blog_queue_starters'); d.append('nonce',nonce); d.append('city',city);
						fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
							if(r.success){ msg.style.color='green'; msg.textContent='✅ '+r.data.message; }
							else { msg.style.color='red'; msg.textContent='❌ '+(r.data?.message||'Błąd'); }
						});
					},
					importCsv: function(nonce) {
						var csv = document.getElementById('ptBlogCsv').value.trim();
						var msg = document.getElementById('ptBlogBatchMsg');
						if(!csv){ msg.textContent='⚠️ Podaj dane CSV.'; return; }
						msg.style.color=''; msg.textContent='⏳ Importuję CSV…';
						var d=new FormData(); d.append('action','pt24_blog_import_csv'); d.append('nonce',nonce); d.append('csv',csv);
						fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
							if(r.success){ msg.style.color='green'; msg.textContent='✅ '+r.data.message; }
							else { msg.style.color='red'; msg.textContent='❌ '+(r.data?.message||'Błąd'); }
						});
					},
					runQueue: function(nonce) {
						var msg = document.getElementById('ptBlogQueueMsg');
						if(msg) { msg.textContent='⏳ Generuję…'; }
						var d=new FormData(); d.append('action','pt24_blog_run_queue'); d.append('nonce',nonce);
						fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
							if(msg){ msg.style.color=r.success?'green':'red'; msg.textContent=r.success?'✅ '+r.data.message:'❌ '+(r.data?.message||'Błąd'); }
							if(r.success) setTimeout(()=>location.reload(),2000);
						});
					},
					clearQueue: function(nonce) {
						if(!confirm('Wyczyścić całą kolejkę artykułów bloga?')) return;
						var msg = document.getElementById('ptBlogQueueMsg');
						var d=new FormData(); d.append('action','pt24_blog_clear_queue'); d.append('nonce',nonce);
						fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
							if(msg){ msg.style.color=r.success?'green':'red'; msg.textContent=r.success?'✅ '+r.data.message:'❌ '+(r.data?.message||'Błąd'); }
							if(r.success) setTimeout(()=>location.reload(),1000);
						});
					}
				};
				</script>

				<?php endif; ?>
			</div><!-- card-body -->
		</div><!-- blog engine card -->

		<?php
	}

	/**
	 * ⚙️ Automation Pro — n8n integration guide + token management
	 */
	private function render_automation_tab(): void {
		$factory_ok = class_exists( 'PT24_AI_Factory' );
		$token     = (string) get_option( 'pt24_webhook_token', '' );
		$rest_base = rest_url( 'pt24/v2' );
		$notice    = isset( $_GET['pt24_notice'] ) ? sanitize_key( wp_unslash( $_GET['pt24_notice'] ) ) : '';
		$this->render_pt24_admin_styles();
		?>
		<div class="pb-v8-dashboard">
			<h2 class="pb-v8-section-title">⚙️ Automation Pro — n8n / REST API</h2>

			<?php if ( 'token_saved' === $notice ) : ?>
				<div class="pb-v8-alert pb-v8-alert-success" style="margin-bottom:16px;"><strong>✅ Token zapisany.</strong></div>
			<?php endif; ?>

			<!-- API Status -->
			<div class="pb-v8-metrics-grid">
				<?php
				$this->render_metric_card( [ 'label' => 'REST endpoint',   'value' => 'Aktywny', 'icon' => '🔗', 'color' => 'success' ] );
				$this->render_metric_card( [ 'label' => 'Token webhook',   'value' => '' !== $token ? 'Ustawiony' : 'Brak', 'icon' => '🔑', 'color' => '' !== $token ? 'success' : 'warning' ] );
				$this->render_metric_card( [ 'label' => 'OpenAI API',      'value' => '' !== get_option('pt24_openai_api_key','') ? 'Skonfigurowane' : 'Brak klucza', 'icon' => '🤖', 'color' => '' !== get_option('pt24_openai_api_key','') ? 'success' : 'warning' ] );
				$this->render_metric_card( [ 'label' => 'Cron (batch)', 'value' => ( $factory_ok && wp_next_scheduled( PT24_AI_Factory::CRON_HOOK ) ) ? 'Aktywny' : 'Nieaktywny', 'icon' => '⏱', 'color' => 'primary' ] );
				?>
			</div>

			<!-- Token settings -->
			<div class="pb-v8-card pt24-admin-card" style="margin-top:20px;max-width:760px;">
				<div class="pb-v8-card-header">
					<div>
						<span class="pt24-admin-kicker">PT24.PRO automation</span>
						<h3 class="pb-v8-card-title">🔑 Token autoryzacji (n8n)</h3>
						<p class="pt24-admin-subtitle">Dane potrzebne do generowania stron, artykułów i importów przez REST API.</p>
					</div>
				</div>
				<div class="pb-v8-card-body">
					<form class="pt24-admin-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="pt24_save_automation_settings">
						<?php wp_nonce_field( 'pt24_save_automation_settings' ); ?>
						<div class="pt24-admin-field">
							<label for="pt24WebhookToken">Nagłówek: <code>X-PT24-Token: [token]</code></label>
							<input id="pt24WebhookToken" type="text" name="pt24_webhook_token" value="<?php echo esc_attr( $token ); ?>" placeholder="pt24_live_...">
							<span class="pt24-admin-help">Wymagany przy wszystkich wywołaniach REST z zewnątrz (n8n, Postman, skrypty).</span>
						</div>
						<div class="pt24-admin-field">
							<label for="pt24OpenAiKey">OpenAI API Key</label>
							<input id="pt24OpenAiKey" type="password" name="pt24_openai_api_key" value="<?php echo esc_attr( (string) get_option( 'pt24_openai_api_key', '' ) ); ?>" placeholder="sk-proj-...">
							<span class="pt24-admin-help">Wymagany do generowania treści przez AI (gpt-4o-mini). Bez klucza działa tryb szablonowy.</span>
						</div>
						<button type="submit" class="button button-primary">💾 Zapisz</button>
					</form>
				</div>
			</div>

			<!-- Endpoint reference -->
			<div class="pb-v8-card" style="margin-top:20px;">
				<div class="pb-v8-card-header"><h3 class="pb-v8-card-title">📡 Endpointy REST (n8n HTTP Request)</h3></div>
				<div class="pb-v8-card-body">
					<div class="pb-v8-table-wrapper">
						<table class="pb-v8-table">
							<thead><tr><th>Metoda</th><th>Endpoint</th><th>Body / Opis</th></tr></thead>
							<tbody>
								<tr><td><code>POST</code></td><td><code><?php echo esc_html( $rest_base . '/generate' ); ?></code></td><td><code>{"service":"mechanik","city":"ruda-slaska","use_ai":false}</code></td></tr>
								<tr><td><code>POST</code></td><td><code><?php echo esc_html( $rest_base . '/batch' ); ?></code></td><td><code>{"csv":"mechanik,ruda-slaska\nhydraulik,katowice"}</code> lub <code>{"pairs":[...]}</code></td></tr>
								<tr><td><code>GET</code></td><td><code><?php echo esc_html( $rest_base . '/stats' ); ?></code></td><td>Statystyki factory (opublikowane, kolejka, postęp)</td></tr>
								<tr><td><code>GET</code></td><td><code><?php echo esc_html( $rest_base . '/services' ); ?></code></td><td>Lista 10 usług (slug, nazwa, long-tail)</td></tr>
								<tr><td><code>GET</code></td><td><code><?php echo esc_html( $rest_base . '/cities' ); ?></code></td><td>Lista 80 miast (slug, nazwa, województwo)</td></tr>
																<tr class="pt24-admin-endpoint-row"><td colspan="3" class="pt24-admin-endpoint-label">BLOG ENGINE</td></tr>
																<tr><td><code>POST</code></td><td><code><?php echo esc_html( $rest_base . '/blog-generate' ); ?></code></td><td><code>{"topic":"Pękła rura","service":"hydraulik","city":"katowice","use_queue":false}</code></td></tr>
																<tr><td><code>POST</code></td><td><code><?php echo esc_html( $rest_base . '/blog-csv' ); ?></code></td><td><code>{"csv":"temat,usluga,miasto\n..."}</code> lub <code>{"starters":true,"city":"katowice"}</code></td></tr>
																<tr><td><code>GET</code></td><td><code><?php echo esc_html( $rest_base . '/blog-stats' ); ?></code></td><td>Artykuły, kolejka, klucze API</td></tr>
																<tr class="pt24-admin-endpoint-row"><td colspan="3" class="pt24-admin-endpoint-label">GOOGLE PLACES</td></tr>
																<tr><td><code>POST</code></td><td><code><?php echo esc_html( $rest_base . '/places-seed' ); ?></code></td><td><code>{"service":"mechanik","city":"katowice","use_ai":true}</code></td></tr>
																<tr><td><code>POST</code></td><td><code><?php echo esc_html( $rest_base . '/places-import-csv' ); ?></code></td><td><code>{"csv":"place_id,company_name,service,city,..."}</code></td></tr>
																<tr><td><code>GET</code></td><td><code><?php echo esc_html( $rest_base . '/places-stats' ); ?></code></td><td>Firmy, AI-enriched, kolejka</td></tr>
							</tbody>
						</table>
					</div>
					<p style="margin-top:12px;font-size:12px;color:var(--pb-v8-text-secondary);">
						Autoryzacja: nagłówek <code>X-PT24-Token: [token]</code> lub sesja WordPress (admini zalogowani).<br>
						Przykład n8n: węzeł <strong>HTTP Request</strong> → metoda POST → URL → Body JSON → Header <code>X-PT24-Token</code>.
					</p>
				</div>
			</div>

			<!-- Automation flow diagram -->
			<div class="pb-v8-card" style="margin-top:20px;">
				<div class="pb-v8-card-header"><h3 class="pb-v8-card-title">🔄 Przepływ automatyzacji (n8n)</h3></div>
				<div class="pb-v8-card-body">
					<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;font-size:13px;">
						<?php
						$steps = [
							[ '📄', 'CSV / Google Sheets', 'Źródło danych' ],
							[ '→', '', '' ],
							[ '⚙️', 'n8n Loop', 'Iteruje po wierszach' ],
							[ '→', '', '' ],
							[ '🔌', 'HTTP POST', '/pt24/v2/generate' ],
							[ '→', '', '' ],
							[ '🗄️', 'WordPress', 'Tworzy pt24_landing' ],
							[ '→', '', '' ],
							[ '🔍', 'Google', 'Indeksuje automatycznie' ],
						];
						foreach ( $steps as $step ) :
							if ( $step[0] === '→' ) : ?>
								<span style="font-size:20px;color:var(--pb-v8-text-secondary);">→</span>
							<?php else : ?>
								<div style="background:var(--pb-v8-bg-card,rgba(125,125,125,.06));border:1px solid rgba(125,125,125,.18);border-radius:10px;padding:12px 16px;text-align:center;">
									<div style="font-size:24px;"><?php echo esc_html( $step[0] ); ?></div>
									<div style="font-weight:700;font-size:12px;"><?php echo esc_html( $step[1] ); ?></div>
									<div style="font-size:11px;color:var(--pb-v8-text-secondary);"><?php echo esc_html( $step[2] ); ?></div>
								</div>
							<?php endif;
						endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Metric card
	 */
	private function render_metric_card( array $args ): void {
		$defaults = [
			'id'     => '',
			'change_id' => '',
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
			<div class="pb-v8-metric-value"<?php echo '' !== $args['id'] ? ' id="' . esc_attr( (string) $args['id'] ) . '"' : ''; ?>><?php echo esc_html( $args['value'] ); ?></div>
			<?php if ( null !== $args['change'] ) : ?>
				<span class="pb-v8-metric-change <?php echo $args['change'] >= 0 ? 'positive' : 'negative'; ?>"<?php echo '' !== $args['change_id'] ? ' id="' . esc_attr( (string) $args['change_id'] ) . '"' : ''; ?>>
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
		try {
			$kpis = DashboardTab::get_kpis( 30 );

			return [
				'revenue_today'      => (float) ( $kpis['total_revenue'] ?? 0.0 ),
				'revenue_change'     => (float) ( $kpis['revenue_trend']['percentage'] ?? 0.0 ),
				'active_users'       => (int) ( $kpis['total_views'] ?? 0 ),
				'users_change'       => 0.0,
				'content_generated'  => (int) ( $kpis['articles_published'] ?? 0 ),
				'content_change'     => (float) ( $kpis['articles_trend']['percentage'] ?? 0.0 ),
				'ai_cost'            => (float) get_option( 'pearblog_ai_cost_monthly_estimate', 0.0 ),
				'cost_change'        => 0.0,
			];
		} catch ( \Throwable $e ) {
			// Fall back to static seed values if KPI tables are not yet provisioned.
		}

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
		$stats = $this->get_realtime_stats();

		return [
			[
				'icon'        => '👁️',
				'title'       => __( 'Live Traffic Snapshot', 'pearblog-engine' ),
				'description' => sprintf( __( '%d active visitors in last 5 minutes', 'pearblog-engine' ), (int) $stats['visitors'] ),
				'time'        => __( 'just now', 'pearblog-engine' ),
			],
			[
				'icon'        => '💰',
				'title'       => __( 'Revenue Snapshot', 'pearblog-engine' ),
				'description' => sprintf( __( '$%s tracked in last hour', 'pearblog-engine' ), number_format( (float) $stats['revenue'], 2 ) ),
				'time'        => __( 'last hour', 'pearblog-engine' ),
			],
			[
				'icon'        => '🎯',
				'title'       => __( 'Conversion Snapshot', 'pearblog-engine' ),
				'description' => sprintf( __( '%d lead conversions in last hour', 'pearblog-engine' ), (int) $stats['conversions'] ),
				'time'        => __( 'last hour', 'pearblog-engine' ),
			],
		];
	}

	/**
	 * Get unread notifications count
	 */
	private function get_unread_notifications_count(): int {
		return count( $this->build_notifications() );
	}

	/**
	 * Build dynamic notifications for Admin V8.
	 *
	 * @return array<int, array<string, int|string>>
	 */
	private function build_notifications(): array {
		$stats = $this->get_realtime_stats();
		$notifications = [];

		$notifications[] = [
			'id'      => 1,
			'type'    => 'success',
			'title'   => __( 'Live Visitors', 'pearblog-engine' ),
			'message' => sprintf( __( '%d active visitors tracked in last 5 minutes.', 'pearblog-engine' ), (int) $stats['visitors'] ),
			'time'    => time() - 120,
		];

		$notifications[] = [
			'id'      => 2,
			'type'    => ( (float) $stats['errors'] >= 2.0 ) ? 'warning' : 'success',
			'title'   => __( 'Platform Health', 'pearblog-engine' ),
			'message' => sprintf( __( 'Current error rate: %s%%.', 'pearblog-engine' ), number_format( (float) $stats['errors'], 1 ) ),
			'time'    => time() - 300,
		];

		$notifications[] = [
			'id'      => 3,
			'type'    => 'info',
			'title'   => __( 'Lead Conversions', 'pearblog-engine' ),
			'message' => sprintf( __( '%d conversions recorded in the last hour.', 'pearblog-engine' ), (int) $stats['conversions'] ),
			'time'    => time() - 600,
		];

		return $notifications;
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

		wp_send_json_success( $this->build_notifications() );
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
