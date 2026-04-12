<?php
/**
 * Plugin bootstrap singleton.
 *
 * @package PearBlogEngine\Core
 */

declare(strict_types=1);

namespace PearBlogEngine\Core;

use PearBlogEngine\API\AutomationController;
use PearBlogEngine\Monitoring\AlertManager;
use PearBlogEngine\Monitoring\HealthController;
use PearBlogEngine\Monitoring\PerformanceDashboard;
use PearBlogEngine\Scheduler\CronManager;
use PearBlogEngine\Admin\AdminPage;
use PearBlogEngine\Admin\ContentCalendar;
use PearBlogEngine\Admin\DashboardWidget;
use PearBlogEngine\Admin\OnboardingWizard;
use PearBlogEngine\Content\ContentRefreshEngine;
use PearBlogEngine\Email\EmailDigest;
use PearBlogEngine\SEO\ProgrammaticSEO;
use PearBlogEngine\SEO\SchemaManager;
use PearBlogEngine\Social\SocialPublisher;
use PearBlogEngine\Webhook\WebhookManager;

/**
 * Plugin class – boots all sub-systems exactly once.
 */
class Plugin {

	/** @var self|null */
	private static ?self $instance = null;

	private function __construct() {}

	/**
	 * Return the singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Attach WordPress hooks and initialise sub-systems.
	 */
	public function boot(): void {
		// Core pipeline & admin.
		( new CronManager() )->register();
		( new AdminPage() )->register();
		( new DashboardWidget() )->register();
		( new ProgrammaticSEO() )->register();
		( new OnboardingWizard() )->register();

		// Performance monitoring.
		( new PerformanceDashboard() )->register();

		// REST API – automation endpoints for external scripts.
		add_action( 'rest_api_init', static function (): void {
			( new AutomationController() )->register_routes();
			( new HealthController() )->register_routes();
		} );

		// SEO: Schema.org structured data output.
		( new SchemaManager() )->register();

		// Monitoring & alerts.
		$this->register_monitoring_hooks();

		// Content refresh cron.
		( new ContentRefreshEngine() )->register();

		// Content calendar (admin + cron dispatch).
		( new ContentCalendar() )->register();

		// Email digest cron.
		( new EmailDigest() )->register();

		// Social media auto-posting.
		( new SocialPublisher() )->register();

		// Webhook event dispatcher.
		( new WebhookManager() )->register();

		// WP-CLI commands.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'pearblog', \PearBlogEngine\CLI\PearBlogCommand::class );
		}
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Register alerting hooks for critical pipeline events.
	 */
	private function register_monitoring_hooks(): void {
		// Track the last successful pipeline completion.
		add_action( 'pearblog_pipeline_completed', static function ( int $post_id ): void {
			// Store as Unix timestamp for consistent numeric comparison in HealthController.
			update_option( 'pearblog_last_pipeline_run', time() );
		} );

		// Alert on circuit-breaker state change (fires when circuit opens in AIClient).
		// This is done by hooking the error_log is not hookable directly; instead we
		// expose an action from AIClient when it records failures above threshold:
		// See AIClient::record_failure() — it logs and callers may hook error events.

		// Alert if cron pipeline errors occur in CronManager.
		add_action( 'pearblog_pipeline_cron_error', static function ( int $site_id, string $message ): void {
			( new AlertManager() )->pipeline_error(
				$message,
				[ 'Site ID' => $site_id ]
			);
		}, 10, 2 );

		// Success notification (info level).
		add_action( 'pearblog_pipeline_completed', static function ( int $post_id, string $topic ): void {
			$alert_on_publish = (bool) get_option( 'pearblog_alert_on_publish', false );
			if ( $alert_on_publish ) {
				( new AlertManager() )->info(
					'Article Published',
					"Topic: {$topic}",
					[ 'Post ID' => $post_id, 'URL' => get_permalink( $post_id ) ]
				);
			}
		}, 20, 2 );
	}
}

