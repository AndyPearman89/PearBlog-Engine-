<?php
/**
 * Plugin bootstrap singleton.
 *
 * @package PearBlogEngine\Core
 */

declare(strict_types=1);

namespace PearBlogEngine\Core;

use PearBlogEngine\API\AutomationController;
use PearBlogEngine\API\DashboardController;
use PearBlogEngine\API\TopicsController;
use PearBlogEngine\API\PoradnikV3API;
use PearBlogEngine\API\SearchSuggestAPI;
use PearBlogEngine\Monitoring\AlertManager;
use PearBlogEngine\Monitoring\HealthController;
use PearBlogEngine\Monitoring\PerformanceDashboard;
use PearBlogEngine\Pipeline\ContentImportExport;
use PearBlogEngine\Pipeline\PipelineAuditLog;
use PearBlogEngine\Scheduler\CronManager;
use PearBlogEngine\Scheduler\PublishScheduler;
use PearBlogEngine\Content\TopicResearchEngine;
use PearBlogEngine\Content\TopicCPT;
use PearBlogEngine\Content\FAQBlockCPT;
use PearBlogEngine\Content\CTABlockCPT;
use PearBlogEngine\Content\RelatedEntityManager;
use PearBlogEngine\Content\PostMetaManager;
use PearBlogEngine\Admin\AdminPage;
use PearBlogEngine\Admin\AdminPageV7;
use PearBlogEngine\Admin\AdminPageV8Enterprise;
use PearBlogEngine\Admin\ContentCalendar;
use PearBlogEngine\Admin\DashboardWidget;
use PearBlogEngine\Admin\OnboardingWizard;
use PearBlogEngine\Admin\OnboardingWizardV2;
use PearBlogEngine\Content\ContentRefreshEngine;
use PearBlogEngine\Email\EmailDigest;
use PearBlogEngine\SEO\ProgrammaticSEO;
use PearBlogEngine\SEO\SchemaManager;
use PearBlogEngine\Social\SocialPublisher;
use PearBlogEngine\Testing\ABTestEngine;
use PearBlogEngine\Webhook\WebhookManager;
use PearBlogEngine\DecisionPlatform\DecisionPlatformManager;
use PearBlogEngine\Analytics\ConversionFlowTracker;
use PearBlogEngine\Database\PoradnikV3Schema;
use PearBlogEngine\Integration\PT24Bridge;
// V6 new modules.
use PearBlogEngine\Core\EventBus;
use PearBlogEngine\Core\FeatureFlags;
use PearBlogEngine\Core\ModuleRegistry;
use PearBlogEngine\Rankings\RankingService;
use PearBlogEngine\Rankings\RankingsController;
use PearBlogEngine\Specialists\SpecialistsModule;
use PearBlogEngine\Local\LocalHubManager;
use PearBlogEngine\Search\SearchEngine;
use PearBlogEngine\Revenue\SubscriptionEngine;
use PearBlogEngine\Revenue\SponsoredPlacement;
// V6 continuation modules.
use PearBlogEngine\Compare\CompareController;
use PearBlogEngine\Compare\ComparisonSchema;
use PearBlogEngine\Calculators\CalculatorController;
use PearBlogEngine\Calculators\CalculatorsSchema;
use PearBlogEngine\AI\DecisionAssistant;
// V6 REST controllers.
use PearBlogEngine\Testing\ABTestController;
use PearBlogEngine\Analytics\AnalyticsController;
use PearBlogEngine\API\GraphQLController;

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
		// Core architecture CPTs and entities.
		( new TopicCPT() )->register();
		( new FAQBlockCPT() )->register();
		( new CTABlockCPT() )->register();
		( new RelatedEntityManager() )->register();
		( new PostMetaManager() )->register();

		// Core pipeline & admin.
		( new CronManager() )->register();

		// Feature flag: Use v8 Enterprise, v7, or v6 admin
		$admin_version = defined( 'PEARBLOG_ADMIN_VERSION' ) ? PEARBLOG_ADMIN_VERSION : 'v8';
		if ( 'v8' === $admin_version || 'v8-enterprise' === $admin_version ) {
			( new AdminPageV8Enterprise() )->register();
		} elseif ( 'v7' === $admin_version ) {
			( new AdminPageV7() )->register();
		} else {
			( new AdminPage() )->register();
		}

		( new DashboardWidget() )->register();
		( new ProgrammaticSEO() )->register();
		( new OnboardingWizard() )->register();
		( new OnboardingWizardV2() )->register();

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
			( new TopicsController() )->register_routes();
			( new PoradnikV3API() )->register_routes();
			SearchSuggestAPI::register_routes();
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

		// Poradnik V3 – Conversion tracking and analytics.
		ConversionFlowTracker::init();

		// ── V6 Platform Modules ──────────────────────────────────────────────

		// Rankings V6: weighted scoring + sponsor engine + REST /pearblog/v1/rankings.
		add_action( 'rest_api_init', static function (): void {
			( new RankingsController() )->register_routes();
		} );
		ModuleRegistry::add( 'rankings_v6', 'Rankings V6 (Score + Sponsor)', '1.0.0', 'PearBlogEngine\\Rankings' );

		// Specialists Marketplace: profiles, reviews, badges, verification.
		( new SpecialistsModule() )->register();

		// Local Hub Network: vertical hubs, programmatic pages, local SEO.
		( new LocalHubManager() )->register();

		// Search Engine: WP + Meilisearch/Typesense abstraction + autocomplete.
		( new SearchEngine() )->register();

		// Revenue: subscriptions + sponsored placements.
		( new SubscriptionEngine() )->register();
		( new SponsoredPlacement() )->register();

		// Compare Engine: pros/cons + AI verdict + REST /pearblog/v1/compare.
		add_action( 'rest_api_init', static function (): void {
			( new CompareController() )->register_routes();
		} );
		ModuleRegistry::add( 'compare_v6', 'Compare Engine V6', '1.0.0', 'PearBlogEngine\\Compare' );

		// Calculator Engine: formula runner + recommendations + REST /pearblog/v1/calculators.
		add_action( 'rest_api_init', static function (): void {
			( new CalculatorController() )->register_routes();
		} );
		ModuleRegistry::add( 'calculators_v6', 'Calculator Engine V6', '1.0.0', 'PearBlogEngine\\Calculators' );

		// AI Decision Layer: RecommendationEngine + DecisionAssistant advisor + FAQ generator.
		add_action( 'rest_api_init', static function (): void {
			( new DecisionAssistant() )->register_routes();
			// V6 A/B Testing REST API.
			( new ABTestController() )->register_routes();
			// V6 Analytics REST API.
			( new AnalyticsController() )->register_routes();
			// GraphQL endpoint (standalone + WPGraphQL extension).
			( new GraphQLController() )->register_rest_route();
		} );
		add_action( 'graphql_register_types', static function (): void {
			( new GraphQLController() )->register_graphql_types();
		} );
		ModuleRegistry::add( 'ai_decision_v6', 'AI Decision Assistant V6', '1.0.0', 'PearBlogEngine\\AI' );

		// Create V3 database tables on activation.
		register_activation_hook( PEARBLOG_PLUGIN_FILE, static function (): void {
			PoradnikV3Schema::create_tables();
			PoradnikV3Schema::update_version( '3.0.0' );
			ComparisonSchema::create_tables();
			CalculatorsSchema::create_tables();
		} );

		// PT24 Integration – Content-to-Lead bridge.
		( new PT24Bridge() )->init();

		// WP-CLI commands.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'pearblog', \PearBlogEngine\CLI\PearBlogCommand::class );

			\WP_CLI::add_command( 'pearblog seo-v3', \PearBlogEngine\CLI\SEOV3Command::class );
			// SEO Keyword Generator CLI commands
			require_once PEARBLOG_PLUGIN_DIR . '/src/SEO/KeywordGeneratorCLI.php';
			\WP_CLI::add_command( 'pearblog integration', \PearBlogEngine\CLI\IntegrationCommand::class );
			\WP_CLI::add_command( 'pearblog security', \PearBlogEngine\CLI\SecurityCommand::class );
			\WP_CLI::add_command( 'pearblog v6', \PearBlogEngine\CLI\V6Command::class );
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

