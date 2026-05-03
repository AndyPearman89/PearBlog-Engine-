<?php
/**
 * Plugin bootstrap singleton.
 *
 * @package PearBlogEngine\Core
 */

declare(strict_types=1);

namespace PearBlogEngine\Core;

use PearBlogEngine\AI\FactChecker;
use PearBlogEngine\AI\PodcastGenerator;
use PearBlogEngine\AI\PromptOptimizer;
use PearBlogEngine\AI\StreamingAIClient;
use PearBlogEngine\AI\VideoScriptBuilder;
use PearBlogEngine\Analytics\CohortEngine;
use PearBlogEngine\Analytics\ContentROIEngine;
use PearBlogEngine\Analytics\PredictiveEngine;
use PearBlogEngine\API\AutomationController;
use PearBlogEngine\API\DashboardController;
use PearBlogEngine\Cache\QueryOptimizer;
use PearBlogEngine\DecisionPlatform\DecisionPlatformManager;
use PearBlogEngine\DecisionPlatform\PriceComparison;
use PearBlogEngine\DecisionPlatform\QuizEngine;
use PearBlogEngine\Distribution\AMPGenerator;
use PearBlogEngine\Email\EmailDigest;
use PearBlogEngine\Email\NewsletterBuilder;
use PearBlogEngine\Integration\ZapierManager;
use PearBlogEngine\Monetization\AffiliateDiscovery;
use PearBlogEngine\Monetization\CROEngine;
use PearBlogEngine\Monetization\PaywallEngine;
use PearBlogEngine\Monetization\RevenueTracker;
use PearBlogEngine\Monitoring\AlertManager;
use PearBlogEngine\Monitoring\HealthController;
use PearBlogEngine\Monitoring\PerformanceDashboard;
use PearBlogEngine\Pipeline\ApprovalWorkflow;
use PearBlogEngine\Pipeline\AsyncQueueManager;
use PearBlogEngine\Pipeline\ContentImportExport;
use PearBlogEngine\Pipeline\PipelineAuditLog;
use PearBlogEngine\Scheduler\CronManager;
use PearBlogEngine\Scheduler\PublishScheduler;
use PearBlogEngine\Security\ComplianceExporter;
use PearBlogEngine\Security\ContentModerator;
use PearBlogEngine\Security\PIIDetector;
use PearBlogEngine\Security\RBACManager;
use PearBlogEngine\SEO\CoreWebVitalsMonitor;
use PearBlogEngine\SEO\HreflangManager;
use PearBlogEngine\SEO\ProgrammaticSEO;
use PearBlogEngine\SEO\SchemaManager;
use PearBlogEngine\SEO\SearchConsoleClient;
use PearBlogEngine\SEO\TopicalAuthorityEngine;
use PearBlogEngine\Social\PushNotificationPublisher;
use PearBlogEngine\Social\SocialPublisher;
use PearBlogEngine\Tenant\BillingEngine;
use PearBlogEngine\Tenant\TenantIsolator;
use PearBlogEngine\Tenant\TenantOnboardingController;
use PearBlogEngine\Testing\ABTestEngine;
use PearBlogEngine\Admin\WhiteLabelManager;
use PearBlogEngine\Analytics\AnalyticsDashboard;
use PearBlogEngine\API\GraphQLController;
use PearBlogEngine\Cache\CdnManager;
use PearBlogEngine\Content\ContentRefreshEngine;
use PearBlogEngine\Content\ReadabilityAnalyzer;
use PearBlogEngine\Content\TopicResearchEngine;
use PearBlogEngine\Admin\AdminPage;
use PearBlogEngine\Admin\AdminPageV7;
use PearBlogEngine\Admin\ContentCalendar;
use PearBlogEngine\Admin\DashboardWidget;
use PearBlogEngine\Admin\OnboardingWizard;
use PearBlogEngine\Keywords\KeywordClusterEngine;
use PearBlogEngine\Monitoring\SLAManager;
use PearBlogEngine\Pipeline\BackgroundProcessor;
use PearBlogEngine\Scheduler\TimeZoneScheduler;
use PearBlogEngine\SEO\XmlSitemapManager;
use PearBlogEngine\Social\SocialCalendar;
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

		// Feature flag: Use v7 admin if enabled, otherwise v6
		$admin_version = defined( 'PEARBLOG_ADMIN_VERSION' ) ? PEARBLOG_ADMIN_VERSION : 'v6';
		if ( 'v7' === $admin_version ) {
			( new AdminPageV7() )->register();
		} else {
			( new AdminPage() )->register();
		}

		( new DashboardWidget() )->register();
		( new ProgrammaticSEO() )->register();
		( new OnboardingWizard() )->register();

		// Performance monitoring.
		( new PerformanceDashboard() )->register();

		// Event-sourced pipeline audit log.
		( new PipelineAuditLog() )->register();

		// Smart content planning.
		( new TopicResearchEngine() )->register();
		( new PublishScheduler() )->register();

		// Bulk import / export REST endpoints.
		( new ContentImportExport() )->register();

		// REST API – automation endpoints for external scripts.
		add_action( 'rest_api_init', static function (): void {
			( new AutomationController() )->register_routes();
			( new HealthController() )->register_routes();
			( new DashboardController() )->register_routes();
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

		// A/B Testing Framework – split-test prompt variants.
		( new ABTestEngine() )->register();

		// Decision Platform – Poradnik.pro Enterprise features.
		( new DecisionPlatformManager() )->register();

		// ----------------------------------------------------------------
		// Enterprise v8.0 – P0 Modules
		// ----------------------------------------------------------------

		// Revenue ROI Dashboard.
		( new ContentROIEngine() )->register();

		// Content Approval Workflow.
		( new ApprovalWorkflow() )->register();

		// Google Search Console integration.
		( new SearchConsoleClient() )->register();

		// ----------------------------------------------------------------
		// Enterprise v8.0 – P1 Modules
		// ----------------------------------------------------------------

		// AI Fact-Checker (hooks into pipeline via action).
		add_action( 'pearblog_pipeline_completed', static function ( int $post_id ): void {
			$checker = new FactChecker();
			if ( ! $checker->is_enabled() ) {
				return;
			}
			$post = get_post( $post_id );
			if ( $post ) {
				$checker->check_and_annotate( $post_id, $post->post_content );
			}
		}, 15 );

		// Prompt Optimizer.
		( new PromptOptimizer() )->register();

		// Tenant Billing Engine.
		( new BillingEngine() )->register();

		// Push Notifications.
		( new PushNotificationPublisher() )->register();

		// ----------------------------------------------------------------
		// Enterprise v8.0 – P2 Modules
		// ----------------------------------------------------------------

		// AI Streaming live preview.
		( new StreamingAIClient() )->register();

		// Video Script Builder.
		( new VideoScriptBuilder() )->register();

		// RBAC Manager.
		( new RBACManager() )->register();

		// Content Moderation (blocks harmful content before publish).
		$moderator = new ContentModerator();
		add_action( 'pearblog_pipeline_completed', static function ( int $post_id ) use ( $moderator ): void {
			if ( ! $moderator->is_enabled() ) {
				return;
			}
			$post = get_post( $post_id );
			if ( $post ) {
				$moderator->check( $post_id, $post->post_content );
			}
		}, 8 );

		// PII Detection (scans before publish).
		$pii_detector = new PIIDetector();
		add_action( 'pearblog_pipeline_completed', static function ( int $post_id ) use ( $pii_detector ): void {
			$post = get_post( $post_id );
			if ( $post ) {
				$pii_detector->scan_and_persist( $post_id, $post->post_content );
			}
		}, 9 );

		// Compliance Exporter.
		( new ComplianceExporter() )->register();

		// Affiliate Discovery.
		( new AffiliateDiscovery() )->register();

		// Paywall Engine.
		( new PaywallEngine() )->register();

		// Topical Authority Engine.
		( new TopicalAuthorityEngine() )->register();

		// Core Web Vitals Monitor.
		( new CoreWebVitalsMonitor() )->register();

		// Zapier / Make.com Integration.
		( new ZapierManager() )->register();

		// Newsletter Builder.
		( new NewsletterBuilder() )->register();

		// AMP Generator.
		( new AMPGenerator() )->register();

		// Cohort & Funnel Analytics.
		( new CohortEngine() )->register();

		// Predictive Traffic Engine.
		( new PredictiveEngine() )->register();

		// Async Queue Manager.
		( new AsyncQueueManager() )->register();

		// Distributed Lock Manager (initialised globally for use by CronManager).
		$GLOBALS['pearblog_lock_manager'] = new DistributedLockManager();

		// Tenant Isolator (multisite data separation).
		( new TenantIsolator() )->register();

		// Decision Quiz Engine.
		( new QuizEngine() )->register();

		// Price Comparison Engine.
		( new PriceComparison() )->register();

		// Revenue Tracker.
		( new RevenueTracker() )->register();

		// CRO Engine (A/B testing for CTAs).
		( new CROEngine() )->register();

		// Podcast Generator.
		( new PodcastGenerator() )->register();

		// Hreflang Manager (international SEO).
		( new HreflangManager() )->register();

		// Tenant Onboarding Controller.
		( new TenantOnboardingController() )->register();

		// Query Optimizer (persistent cache + slow-query monitor).
		( new QueryOptimizer() )->register();

		// ----------------------------------------------------------------
		// Enterprise v8.1 – Previously-unregistered modules
		// ----------------------------------------------------------------

		// GraphQL API endpoint (standalone + WPGraphQL extension).
		( new GraphQLController() )->register();

		// White-Label Manager (agency branding overrides).
		( new WhiteLabelManager() )->register();

		// Analytics Dashboard (GA4 sync + admin tab).
		( new AnalyticsDashboard() )->register();

		// CDN Manager (image offload to BunnyCDN / Cloudflare Images).
		( new CdnManager() )->register();

		// Keyword Cluster Engine (GA4 search-term clustering).
		( new KeywordClusterEngine() )->register();

		// SLA Manager (uptime / pipeline SLA targets + breach alerts).
		( new SLAManager() )->register();

		// Background Processor (WP-Cron-based async pipeline queue).
		( new BackgroundProcessor() )->register();

		// ----------------------------------------------------------------
		// Enterprise v8.1 – New Modules
		// ----------------------------------------------------------------

		// Readability Analyzer (Flesch, FK grade, passive voice, meta box).
		( new ReadabilityAnalyzer() )->register();

		// Timezone Scheduler (publish at locally optimal hours per market).
		( new TimeZoneScheduler() )->register();

		// XML Sitemap Manager (images, video, news, index, SE pinging).
		( new XmlSitemapManager() )->register();

		// Social Calendar (multi-platform post scheduling).
		( new SocialCalendar() )->register();

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

