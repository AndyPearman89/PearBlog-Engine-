<?php
/**
 * PT24 Enterprise Integration Manager
 *
 * Orchestrates all subsystems: LeadAI, Content Linking, Analytics, Multisite
 * This serves as the central coordination hub for full enterprise integration.
 *
 * @package PearBlog\PT24Enterprise\Integration
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PT24_Integration_Manager {

	/**
	 * Singleton instance
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize all subsystems
	 */
	private function __construct() {
		// Hook initialization
		add_action( 'pt24_init', [ $this, 'init_leadai_system' ], 10 );
		add_action( 'pt24_init', [ $this, 'init_content_linking' ], 20 );
		add_action( 'pt24_init', [ $this, 'init_analytics' ], 30 );
		add_action( 'pt24_init', [ $this, 'init_multisite' ], 40 );
		add_action( 'pt24_init', [ $this, 'setup_api_routes' ], 50 );

		// Admin hooks
		add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );
		add_action( 'wp_footer', [ $this, 'inject_tracking_script' ] );

		// Cron hooks
		add_action( 'pt24_daily_sync', [ $this, 'daily_sync' ] );
		add_action( 'pt24_hourly_cleanup', [ $this, 'hourly_cleanup' ] );

		// Schedule recurring tasks
		$this->schedule_recurring_tasks();
	}

	/**
	 * Initialize LeadAI System
	 */
	public function init_leadai_system() {
		if ( ! PT24_ENABLE_LEADAI ) {
			return;
		}

		pt24_log( 'INIT_LEADAI', [ 'queue_enabled' => PT24_LEADAI_QUEUE_ENABLED ] );

		// Register LeadAI hooks
		add_action( 'wp_insert_post', [ $this, 'capture_lead_from_form' ], 10, 2 );
		add_filter( 'pt24_lead_score', [ $this, 'calculate_lead_score' ], 10, 2 );

		// Register cron for lead processing
		if ( PT24_LEADAI_QUEUE_ENABLED ) {
			add_action( 'pt24_process_lead_queue', [ $this, 'process_lead_queue' ] );
			if ( ! wp_next_scheduled( 'pt24_process_lead_queue' ) ) {
				wp_schedule_event( time(), 'every_5_minutes', 'pt24_process_lead_queue' );
			}
		}

		do_action( 'pt24_leadai_initialized' );
	}

	/**
	 * Initialize Content Linking System
	 */
	public function init_content_linking() {
		if ( ! PT24_ENABLE_CONTENT_LINKING ) {
			return;
		}

		pt24_log( 'INIT_CONTENT_LINKING', [] );

		// Register hooks
		add_filter( 'the_content', [ $this, 'inject_internal_links' ], 20 );
		add_action( 'save_post_post', [ $this, 'sync_content_metadata' ], 10, 2 );
		add_action( 'save_post_pt24_landing', [ $this, 'sync_landing_metadata' ], 10, 2 );

		// Register API endpoints
		add_action( 'rest_api_init', [ $this, 'register_content_linking_routes' ] );

		do_action( 'pt24_content_linking_initialized' );
	}

	/**
	 * Initialize Analytics System
	 */
	public function init_analytics() {
		if ( ! PT24_ENABLE_ANALYTICS ) {
			return;
		}

		pt24_log( 'INIT_ANALYTICS', [] );

		// Track page views
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_analytics_tracker' ] );

		// Register analytics API endpoints
		add_action( 'rest_api_init', [ $this, 'register_analytics_routes' ] );

		// Setup analytics dashboard
		add_action( 'admin_menu', [ $this, 'register_analytics_menu' ] );

		do_action( 'pt24_analytics_initialized' );
	}

	/**
	 * Initialize Multisite System
	 */
	public function init_multisite() {
		if ( ! is_multisite() || ! PT24_ENABLE_MULTISITE ) {
			return;
		}

		pt24_log( 'INIT_MULTISITE', [ 'main_site_id' => PT24_MAIN_SITE_ID ] );

		// Register multisite sync
		add_action( 'switch_blog', [ $this, 'on_switch_blog' ], 10, 2 );
		add_action( 'pt24_sync_multisite', [ $this, 'sync_multisite_data' ] );

		// Schedule sync
		if ( ! wp_next_scheduled( 'pt24_sync_multisite' ) ) {
			wp_schedule_event( time(), 'hourly', 'pt24_sync_multisite' );
		}

		do_action( 'pt24_multisite_initialized' );
	}

	/**
	 * Setup API Routes
	 */
	public function setup_api_routes() {
		// All REST routes are now defined in pt24-enterprise-config.php
		pt24_log( 'API_ROUTES_SETUP', [ 'namespace' => 'pt24/v1' ] );
		do_action( 'pt24_api_routes_registered' );
	}

	/**
	 * Register Admin Menus
	 */
	public function register_admin_menus() {
		// The main PearBlog v8 menu already exists
		// This adds any PT24-specific submenus
		add_submenu_page(
			'pearblog-enterprise-v8',
			'Lead System Configuration',
			'Lead System',
			'manage_options',
			'pt24-lead-config',
			[ $this, 'render_lead_config_page' ]
		);

		add_submenu_page(
			'pearblog-enterprise-v8',
			'PT24 Integration Status',
			'Integration Status',
			'manage_options',
			'pt24-integration-status',
			[ $this, 'render_integration_status_page' ]
		);

		add_submenu_page(
			'pearblog-enterprise-v8',
			'PT24 API Settings',
			'API Configuration',
			'manage_options',
			'pt24-api-config',
			[ $this, 'render_api_config_page' ]
		);
	}

	/**
	 * Render Lead Configuration Page
	 */
	public function render_lead_config_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( 'LeadAI Configuration' ); ?></h1>
			
			<div class="card">
				<h2><?php echo esc_html( 'Status' ); ?></h2>
				<p>
					<?php
					echo PT24_LEADAI_ENABLED ? 
						'<span style="color: green;">✓ LeadAI is ENABLED</span>' :
						'<span style="color: red;">✗ LeadAI is DISABLED</span>';
					?>
				</p>
			</div>

			<div class="card">
				<h2><?php echo esc_html( 'Current Configuration' ); ?></h2>
				<table class="widefat striped">
					<tr>
						<th><?php echo esc_html( 'Setting' ); ?></th>
						<th><?php echo esc_html( 'Value' ); ?></th>
					</tr>
					<tr>
						<td><?php echo esc_html( 'Queue Processing' ); ?></td>
						<td><?php echo PT24_LEADAI_QUEUE_ENABLED ? 'Enabled' : 'Disabled'; ?></td>
					</tr>
					<tr>
						<td><?php echo esc_html( 'Batch Size' ); ?></td>
						<td><?php echo esc_html( PT24_LEADAI_BATCH_SIZE ); ?></td>
					</tr>
					<tr>
						<td><?php echo esc_html( 'SMS Provider' ); ?></td>
						<td><?php echo PT24_SMSAPI_ENABLED ? 'SMSApi.pl' : 'Disabled'; ?></td>
					</tr>
					<tr>
						<td><?php echo esc_html( 'Email Notifications' ); ?></td>
						<td><?php echo PT24_EMAIL_ENABLED ? 'Enabled' : 'Disabled'; ?></td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Integration Status Page
	 */
	public function render_integration_status_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		global $wpdb;

		$config = pt24_get_full_config();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( 'PT24 Enterprise Integration Status' ); ?></h1>
			
			<div class="card">
				<h2><?php echo esc_html( 'Platform Status' ); ?></h2>
				<table class="widefat striped">
					<tr>
						<th><?php echo esc_html( 'Component' ); ?></th>
						<th><?php echo esc_html( 'Status' ); ?></th>
					</tr>
					<tr>
						<td><?php echo esc_html( 'PT24 Core' ); ?></td>
						<td><span style="color: green;">✓ Active</span></td>
					</tr>
					<tr>
						<td><?php echo esc_html( 'PearBlog Engine' ); ?></td>
						<td><?php echo is_plugin_active( 'pearblog-engine/pearblog-engine.php' ) ? '<span style="color: green;">✓ Active</span>' : '<span style="color: red;">✗ Inactive</span>'; ?></td>
					</tr>
					<tr>
						<td><?php echo esc_html( 'LeadAI System' ); ?></td>
						<td><?php echo PT24_LEADAI_ENABLED ? '<span style="color: green;">✓ Enabled</span>' : '<span style="color: gray;">○ Disabled</span>'; ?></td>
					</tr>
					<tr>
						<td><?php echo esc_html( 'Content Linking' ); ?></td>
						<td><?php echo PT24_ENABLE_CONTENT_LINKING ? '<span style="color: green;">✓ Enabled</span>' : '<span style="color: gray;">○ Disabled</span>'; ?></td>
					</tr>
					<tr>
						<td><?php echo esc_html( 'Analytics' ); ?></td>
						<td><?php echo PT24_ENABLE_ANALYTICS ? '<span style="color: green;">✓ Enabled</span>' : '<span style="color: gray;">○ Disabled</span>'; ?></td>
					</tr>
				</table>
			</div>

			<div class="card">
				<h2><?php echo esc_html( 'Database Tables' ); ?></h2>
				<table class="widefat striped">
					<tr>
						<th><?php echo esc_html( 'Table' ); ?></th>
						<th><?php echo esc_html( 'Exists' ); ?></th>
						<th><?php echo esc_html( 'Records' ); ?></th>
					</tr>
					<?php foreach ( [ 'content_meta', 'content_links', 'lead_attribution', 'analytics' ] as $table_key ): ?>
						<?php
						$table_name = $config['database'][ $table_key ];
						$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
						$count      = $exists ? $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) : 0;
						?>
						<tr>
							<td><?php echo esc_html( $table_key ); ?></td>
							<td><?php echo $exists ? '<span style="color: green;">✓ Yes</span>' : '<span style="color: red;">✗ No</span>'; ?></td>
							<td><?php echo esc_html( $count ); ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render API Configuration Page
	 */
	public function render_api_config_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( 'PT24 API Configuration' ); ?></h1>
			
			<div class="card">
				<h2><?php echo esc_html( 'API Endpoints' ); ?></h2>
				<p><?php echo esc_html( rest_url( 'pt24/v1/' ) ); ?></p>
				<ul>
					<li><code>GET /pt24/v1/health</code> - System health check</li>
					<li><code>GET /pt24/v1/config</code> - Get configuration (admin only)</li>
					<li><code>GET /pt24/v1/dashboard/stats</code> - Get dashboard statistics</li>
				</ul>
			</div>

			<div class="card">
				<h2><?php echo esc_html( 'OpenAI Configuration' ); ?></h2>
				<table class="widefat striped">
					<tr>
						<th><?php echo esc_html( 'Setting' ); ?></th>
						<th><?php echo esc_html( 'Value' ); ?></th>
					</tr>
					<tr>
						<td><?php echo esc_html( 'Model' ); ?></td>
						<td><?php echo esc_html( PT24_OPENAI_MODEL ); ?></td>
					</tr>
					<tr>
						<td><?php echo esc_html( 'Timeout (seconds)' ); ?></td>
						<td><?php echo esc_html( PT24_OPENAI_TIMEOUT ); ?></td>
					</tr>
					<tr>
						<td><?php echo esc_html( 'Max Tokens' ); ?></td>
						<td><?php echo esc_html( PT24_OPENAI_MAX_TOKENS ); ?></td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Capture Lead from Form Submission
	 */
	public function capture_lead_from_form( $post_id, $post ) {
		// This will be implemented by LeadAI system
		do_action( 'pt24_capture_lead', $post_id, $post );
	}

	/**
	 * Calculate Lead Score
	 */
	public function calculate_lead_score( $score, $lead_data ) {
		// Scoring formula: urgency(30) + budget(20) + clarity(20) + location(15) + demand(15)
		$weighted_score = (
			( isset( $lead_data['urgency'] ) ? $lead_data['urgency'] * 30 : 0 ) +
			( isset( $lead_data['budget'] ) ? $lead_data['budget'] * 20 : 0 ) +
			( isset( $lead_data['clarity'] ) ? $lead_data['clarity'] * 20 : 0 ) +
			( isset( $lead_data['location'] ) ? $lead_data['location'] * 15 : 0 ) +
			( isset( $lead_data['demand'] ) ? $lead_data['demand'] * 15 : 0 )
		) / 100;

		return min( 100, max( 0, $weighted_score ) );
	}

	/**
	 * Process Lead Queue
	 */
	public function process_lead_queue() {
		pt24_log( 'PROCESS_LEAD_QUEUE', [ 'batch_size' => PT24_LEADAI_BATCH_SIZE ] );
		do_action( 'pt24_lead_queue_processing' );
	}

	/**
	 * Inject Internal Links in Content
	 */
	public function inject_internal_links( $content ) {
		if ( ! is_single() ) {
			return $content;
		}

		// Find related landing pages and inject links
		global $post;
		$related_landings = $this->find_related_landings( $post->ID );

		if ( ! empty( $related_landings ) ) {
			// Inject links at strategic positions
			$content = $this->inject_cta_links( $content, $related_landings );
		}

		return $content;
	}

	/**
	 * Find Related Landing Pages
	 */
	private function find_related_landings( $post_id, $limit = 3 ) {
		global $wpdb;

		$post_meta = get_post_meta( $post_id, '_pt24_content_meta', true );
		if ( ! $post_meta ) {
			return [];
		}

		// Find landings for same category/city
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts 
				 WHERE post_type = 'pt24_landing' 
				 AND post_status = 'publish'
				 AND ID != %d
				 LIMIT %d",
				$post_id,
				$limit
			)
		);

		return wp_list_pluck( $results, 'ID' );
	}

	/**
	 * Inject CTA Links
	 */
	private function inject_cta_links( $content, $landing_ids ) {
		// Add CTA section before closing tags
		$cta_html = '<div class="pt24-cta-section">';
		foreach ( $landing_ids as $landing_id ) {
			$landing = get_post( $landing_id );
			$cta_html .= sprintf(
				'<a href="%s" class="pt24-link-cta">%s</a>',
				esc_url( get_permalink( $landing_id ) ),
				esc_html( $landing->post_title )
			);
		}
		$cta_html .= '</div>';

		// Inject before closing paragraph or section
		$content = preg_replace( '/(<\/p>)(?!.*<\/p>)/s', $cta_html . '$1', $content, 1 );

		return $content;
	}

	/**
	 * Sync Content Metadata
	 */
	public function sync_content_metadata( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Sync metadata to content_meta table
		do_action( 'pt24_sync_content_meta', $post_id, $post );
	}

	/**
	 * Sync Landing Metadata
	 */
	public function sync_landing_metadata( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Sync metadata to content_meta table
		do_action( 'pt24_sync_landing_meta', $post_id, $post );
	}

	/**
	 * Register Content Linking API Routes
	 */
	public function register_content_linking_routes() {
		register_rest_route( 'pt24/v1', '/content-links', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_content_links' ],
			'permission_callback' => 'pt24_rest_permission_check',
		] );

		register_rest_route( 'pt24/v1', '/content-links', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_create_content_link' ],
			'permission_callback' => 'pt24_rest_admin_permission',
		] );
	}

	/**
	 * REST: Get Content Links
	 */
	public function rest_get_content_links( $request ) {
		global $wpdb;

		$post_id = $request->get_param( 'post_id' );
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM " . PT24_TABLE_CONTENT_LINKS . " WHERE content_id = %d",
				$post_id
			)
		);

		return rest_ensure_response( $results );
	}

	/**
	 * REST: Create Content Link
	 */
	public function rest_create_content_link( $request ) {
		global $wpdb;

		$params = $request->get_json_params();

		$wpdb->insert(
			PT24_TABLE_CONTENT_LINKS,
			[
				'content_id'   => $params['content_id'],
				'target_type'  => $params['target_type'],
				'target_id'    => $params['target_id'],
				'link_text'    => $params['link_text'],
				'link_context' => $params['link_context'] ?? null,
				'position'     => $params['position'] ?? 'body',
			]
		);

		return rest_ensure_response( [ 'id' => $wpdb->insert_id ] );
	}

	/**
	 * Register Analytics Routes
	 */
	public function register_analytics_routes() {
		register_rest_route( 'pt24/v1', '/analytics/events', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_track_event' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'pt24/v1', '/analytics/report', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_get_analytics_report' ],
			'permission_callback' => 'pt24_rest_admin_permission',
		] );
	}

	/**
	 * REST: Track Analytics Event
	 */
	public function rest_track_event( $request ) {
		global $wpdb;

		$params = $request->get_json_params();

		$wpdb->insert(
			PT24_TABLE_ANALYTICS,
			[
				'event_type'  => $params['event_type'],
				'post_id'     => $params['post_id'] ?? null,
				'event_data'  => wp_json_encode( $params['data'] ?? [] ),
				'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'ip_address'  => $this->get_client_ip(),
			]
		);

		return rest_ensure_response( [ 'tracked' => true ] );
	}

	/**
	 * REST: Get Analytics Report
	 */
	public function rest_get_analytics_report( $request ) {
		global $wpdb;

		$days   = $request->get_param( 'days' ) ?? 30;
		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					event_type,
					COUNT(*) as count,
					DATE(created_at) as date
				FROM " . PT24_TABLE_ANALYTICS . "
				WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				GROUP BY event_type, DATE(created_at)",
				$days
			)
		);

		return rest_ensure_response( $result );
	}

	/**
	 * Enqueue Analytics Tracker
	 */
	public function enqueue_analytics_tracker() {
		if ( is_admin() || is_robot() ) {
			return;
		}

		wp_register_script(
			'pt24-analytics-tracker',
			plugins_url( 'assets/js/analytics-tracker.js', dirname( __FILE__ ) . '/pt24-enterprise-config.php' ),
			[],
			'1.0.0',
			true
		);

		wp_localize_script(
			'pt24-analytics-tracker',
			'pt24Analytics',
			[
				'apiUrl' => rest_url( 'pt24/v1/analytics/' ),
				'nonce'  => wp_create_nonce( 'pt24_analytics' ),
			]
		);

		wp_enqueue_script( 'pt24-analytics-tracker' );
	}

	/**
	 * Register Analytics Menu
	 */
	public function register_analytics_menu() {
		add_submenu_page(
			'pearblog-enterprise-v8',
			'Analytics Reports',
			'Analytics',
			'manage_options',
			'pt24-analytics',
			[ $this, 'render_analytics_page' ]
		);
	}

	/**
	 * Render Analytics Page
	 */
	public function render_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( 'PT24 Analytics Dashboard' ); ?></h1>
			<div id="pt24-analytics-dashboard"></div>
			<script>
				// Analytics dashboard will be loaded via React/Vue component
				document.addEventListener('DOMContentLoaded', function() {
					// Load analytics dashboard
					console.log('PT24 Analytics Dashboard initialized');
				});
			</script>
		</div>
		<?php
	}

	/**
	 * Inject Tracking Script in Footer
	 */
	public function inject_tracking_script() {
		if ( is_admin() || is_robot() ) {
			return;
		}

		?>
		<script>
			if (typeof pt24Analytics !== 'undefined') {
				// Track page view
				fetch(pt24Analytics.apiUrl + 'events', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': pt24Analytics.nonce
					},
					body: JSON.stringify({
						event_type: 'page_view',
						post_id: <?php echo absint( get_queried_object_id() ); ?>,
						data: {
							url: window.location.href,
							referrer: document.referrer,
							timestamp: new Date().toISOString()
						}
					})
				});
			}
		</script>
		<?php
	}

	/**
	 * Sync Multisite Data
	 */
	public function sync_multisite_data() {
		if ( ! is_multisite() ) {
			return;
		}

		pt24_log( 'SYNC_MULTISITE', [] );

		// Sync data across subsites
		$sites = get_sites( [ 'fields' => 'ids' ] );
		foreach ( $sites as $site_id ) {
			switch_to_blog( $site_id );
			do_action( 'pt24_sync_subsite_data' );
			restore_current_blog();
		}
	}

	/**
	 * On Switch Blog (multisite)
	 */
	public function on_switch_blog( $new_blog_id, $old_blog_id ) {
		// Perform any necessary operations when switching blogs
		do_action( 'pt24_switched_blog', $new_blog_id, $old_blog_id );
	}

	/**
	 * Schedule Recurring Tasks
	 */
	private function schedule_recurring_tasks() {
		// Daily sync
		if ( ! wp_next_scheduled( 'pt24_daily_sync' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 2:00 AM' ), 'daily', 'pt24_daily_sync' );
		}

		// Hourly cleanup
		if ( ! wp_next_scheduled( 'pt24_hourly_cleanup' ) ) {
			wp_schedule_event( time() + 3600, 'hourly', 'pt24_hourly_cleanup' );
		}
	}

	/**
	 * Daily Sync
	 */
	public function daily_sync() {
		pt24_log( 'DAILY_SYNC', [ 'timestamp' => current_time( 'mysql' ) ] );

		// Sync analytics
		// Sync multisite data
		// Update caches
		// Generate reports
		do_action( 'pt24_daily_sync_complete' );
	}

	/**
	 * Hourly Cleanup
	 */
	public function hourly_cleanup() {
		global $wpdb;

		pt24_log( 'HOURLY_CLEANUP', [] );

		// Clean old analytics
		$wpdb->query(
			"DELETE FROM " . PT24_TABLE_ANALYTICS . "
			 WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
		);

		// Cleanup expired transients
		delete_transients_by_prefix( PT24_CACHE_PREFIX );

		do_action( 'pt24_hourly_cleanup_complete' );
	}

	/**
	 * Get Client IP
	 */
	private function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		}

		return sanitize_text_field( $ip );
	}
}

// Initialize manager on plugins_loaded
add_action(
	'plugins_loaded',
	function () {
		PT24_Integration_Manager::get_instance();
	},
	5
);
