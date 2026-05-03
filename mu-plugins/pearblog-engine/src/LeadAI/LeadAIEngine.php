<?php
/**
 * LeadAI Engine Bootstrap
 *
 * Initializes and registers all LeadAI components.
 *
 * @package PearBlog\LeadAI
 */

declare(strict_types=1);

namespace PearBlog\LeadAI;

use PearBlog\LeadAI\API\LeadAIController;
use PearBlog\LeadAI\Infrastructure\LeadAISchema;
use PearBlog\LeadAI\Infrastructure\Queue;
use PearBlog\LeadAI\Application\LeadOrchestrator;

/**
 * Lead AI Engine
 *
 * Main bootstrap class for PT24 AI Lead Engine V2.
 */
class LeadAIEngine {
	private static ?self $instance = null;

	private function __construct() {}

	/**
	 * Get singleton instance.
	 */
	public static function getInstance(): self {
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize LeadAI Engine.
	 */
	public function init(): void {
		// Register activation hook
		register_activation_hook(PEARBLOG_PLUGIN_FILE, [$this, 'activate']);

		// Register REST API
		add_action('rest_api_init', function() {
			(new LeadAIController())->register_routes();
		});

		// Register queue workers
		Queue::registerWorkers();

		// Register SLA monitoring cron
		if (!wp_next_scheduled('pt24_sla_monitor')) {
			wp_schedule_event(strtotime('+5 minutes'), 'pt24_5min', 'pt24_sla_monitor');
		}

		add_action('pt24_sla_monitor', [$this, 'runSLAMonitoring']);

		// Register custom cron schedule (5 minutes)
		add_filter('cron_schedules', function($schedules) {
			$schedules['pt24_5min'] = [
				'interval' => 300,
				'display'  => __('Every 5 Minutes', 'pearblog'),
			];

			return $schedules;
		});

		// Register admin menu
		add_action('admin_menu', [$this, 'registerAdminMenu']);
	}

	/**
	 * Plugin activation.
	 */
	public function activate(): void {
		// Create database tables
		$schema = new LeadAISchema();
		$results = $schema->createTables();

		foreach ($results as $table => $success) {
			if (!$success) {
				error_log("[LeadAI] Failed to create table: {$table}");
			}
		}

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Run SLA monitoring cycle.
	 */
	public function runSLAMonitoring(): void {
		$orchestrator = new LeadOrchestrator();
		$result = $orchestrator->runSLAMonitoring();

		error_log(sprintf(
			'[LeadAI SLA Monitor] Checked %d breached, %d approaching',
			$result['breached_count'],
			$result['approaching_count']
		));
	}

	/**
	 * Register admin menu.
	 */
	public function registerAdminMenu(): void {
		add_menu_page(
			'PT24 Lead AI',
			'Lead AI',
			'manage_options',
			'pt24-lead-ai',
			[$this, 'renderDashboard'],
			'dashicons-networking',
			31
		);

		add_submenu_page(
			'pt24-lead-ai',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'pt24-lead-ai',
			[$this, 'renderDashboard']
		);

		add_submenu_page(
			'pt24-lead-ai',
			'All Leads',
			'All Leads',
			'manage_options',
			'pt24-lead-ai-leads',
			[$this, 'renderLeadsList']
		);

		add_submenu_page(
			'pt24-lead-ai',
			'Settings',
			'Settings',
			'manage_options',
			'pt24-lead-ai-settings',
			[$this, 'renderSettings']
		);
	}

	/**
	 * Render dashboard page.
	 */
	public function renderDashboard(): void {
		include __DIR__ . '/UI/AdminDashboard.php';
	}

	/**
	 * Render leads list page.
	 */
	public function renderLeadsList(): void {
		include __DIR__ . '/UI/LeadsList.php';
	}

	/**
	 * Render settings page.
	 */
	public function renderSettings(): void {
		// Save settings
		if (isset($_POST['pt24_lead_ai_settings']) && check_admin_referer('pt24_lead_ai_settings')) {
			update_option('pt24_sms_enabled', isset($_POST['sms_enabled']));
			update_option('pt24_sms_api_key', sanitize_text_field($_POST['sms_api_key'] ?? ''));
			update_option('pt24_sms_sender', sanitize_text_field($_POST['sms_sender'] ?? 'PT24'));
			update_option('pt24_email_from', sanitize_email($_POST['email_from'] ?? get_option('admin_email')));
			update_option('pt24_email_from_name', sanitize_text_field($_POST['email_from_name'] ?? 'PT24'));

			echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
		}

		// Render settings form
		?>
		<div class="wrap">
			<h1>PT24 Lead AI Settings</h1>

			<form method="post">
				<?php wp_nonce_field('pt24_lead_ai_settings', 'pt24_lead_ai_settings'); ?>

				<h2>SMS Settings</h2>
				<table class="form-table">
					<tr>
						<th>Enable SMS</th>
						<td>
							<label>
								<input type="checkbox" name="sms_enabled" value="1" <?php checked(get_option('pt24_sms_enabled')); ?>>
								Enable SMS notifications to contractors
							</label>
						</td>
					</tr>
					<tr>
						<th>SMS API Key</th>
						<td>
							<input type="text" name="sms_api_key" value="<?php echo esc_attr(get_option('pt24_sms_api_key')); ?>" class="regular-text">
							<p class="description">SMSApi.pl API key</p>
						</td>
					</tr>
					<tr>
						<th>SMS Sender Name</th>
						<td>
							<input type="text" name="sms_sender" value="<?php echo esc_attr(get_option('pt24_sms_sender', 'PT24')); ?>" class="regular-text">
						</td>
					</tr>
				</table>

				<h2>Email Settings</h2>
				<table class="form-table">
					<tr>
						<th>From Email</th>
						<td>
							<input type="email" name="email_from" value="<?php echo esc_attr(get_option('pt24_email_from', get_option('admin_email'))); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th>From Name</th>
						<td>
							<input type="text" name="email_from_name" value="<?php echo esc_attr(get_option('pt24_email_from_name', 'PT24')); ?>" class="regular-text">
						</td>
					</tr>
				</table>

				<?php submit_button('Save Settings'); ?>
			</form>
		</div>
		<?php
	}
}

// Initialize on plugins_loaded
add_action('plugins_loaded', function() {
	LeadAIEngine::getInstance()->init();
}, 20);
