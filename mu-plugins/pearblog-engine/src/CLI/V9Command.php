<?php
/**
 * WP-CLI V9 command group: `wp pearblog v9`
 *
 * Sub-commands:
 *   wp pearblog v9 analytics forecast           – show stored traffic forecasts
 *   wp pearblog v9 analytics anomalies          – list detected traffic anomalies
 *   wp pearblog v9 analytics refresh            – trigger predictive analytics refresh
 *
 *   wp pearblog v9 ab generate --topic=<t> [--variants=<n>]  – generate AI variants
 *   wp pearblog v9 ab evaluate <test_id>         – Bayesian evaluation of a test
 *
 *   wp pearblog v9 router status                 – show SmartProviderRouter stats
 *   wp pearblog v9 router strategy <strategy>    – set routing strategy
 *
 *   wp pearblog v9 orphans scan                  – scan for orphan pages
 *   wp pearblog v9 orphans list                  – list stored orphan results
 *   wp pearblog v9 orphans fix <post_id>         – apply auto-fix to one orphan
 *
 *   wp pearblog v9 refresh-score [--limit=<n>]  – show content refresh priority queue
 *   wp pearblog v9 refresh-rescore              – re-score all posts
 *
 *   wp pearblog v9 collab status <post_id>       – show collaboration state
 *   wp pearblog v9 collab assign <post_id> --reviewers=<ids> – assign reviewers
 *   wp pearblog v9 collab review <post_id> --decision=<d> [--note=<n>] – submit review
 *
 *   wp pearblog v9 billing usage                – show current billing period AI usage
 *   wp pearblog v9 billing history              – show billing history (last 12 months)
 *
 *   wp pearblog v9 tenant provision --domain=<d> [--plan=<p>] [--industry=<i>] [--language=<l>] – provision tenant
 *   wp pearblog v9 tenant list                  – list provisioned tenants
 *
 *   wp pearblog v9 audit log [--limit=<n>] [--level=<l>] – show audit log entries
 *
 *   wp pearblog v9 pii scan <post_id>           – scan post content for PII
 *   wp pearblog v9 pii export [--days=<n>]      – export PII compliance report
 *
 *   wp pearblog v9 roi report [--days=<n>]      – show ROI / conversion stats
 *
 * @package PearBlogEngine\CLI
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

use PearBlogEngine\Analytics\PredictiveAnalytics;
use PearBlogEngine\Testing\ABTestEngine;
use PearBlogEngine\Testing\AIVariantGenerator;
use PearBlogEngine\Testing\BayesianOptimizer;
use PearBlogEngine\AI\SmartProviderRouter;
use PearBlogEngine\SEO\OrphanPageDetector;
use PearBlogEngine\Content\ContentRefreshPrioritizer;
use PearBlogEngine\Content\CollaborationManager;
use PearBlogEngine\Tenant\BillingEngine;
use PearBlogEngine\Tenant\TenantOnboardingController;
use PearBlogEngine\Pipeline\PipelineAuditLog;
use PearBlogEngine\Security\PIIDetector;
use PearBlogEngine\Security\ComplianceExporter;
use PearBlogEngine\Analytics\ConversionTracker;

/**
 * PearBlog v9.0 commands.
 *
 * @when after_wp_load
 */
class V9Command {

	// -----------------------------------------------------------------------
	// Analytics
	// -----------------------------------------------------------------------

	/**
	 * Show stored traffic forecasts.
	 *
	 * ## OPTIONS
	 * [--limit=<n>]
	 * : Max number of forecasts to display. Default: 20.
	 *
	 * ## EXAMPLES
	 *   wp pearblog v9 analytics forecast --limit=10
	 *
	 * @subcommand analytics forecast
	 */
	public function analytics_forecast( array $args, array $assoc_args ): void {
		$limit     = (int) ( $assoc_args['limit'] ?? 20 );
		$pa        = new PredictiveAnalytics();
		$forecasts = array_slice( $pa->get_forecasts(), 0, $limit, true );

		if ( empty( $forecasts ) ) {
			\WP_CLI::line( 'No forecasts stored. Run: wp pearblog v9 analytics refresh' );
			return;
		}

		$rows = [];
		foreach ( $forecasts as $post_id => $f ) {
			$rows[] = [
				'post_id'    => $post_id,
				'predicted'  => $f['predicted'] ?? 0,
				'trend'      => $f['trend'] ?? '—',
				'confidence' => $f['confidence'] ?? '—',
			];
		}
		\WP_CLI\Utils\format_items( 'table', $rows, [ 'post_id', 'predicted', 'trend', 'confidence' ] );
	}

	/**
	 * List detected traffic anomalies.
	 *
	 * @subcommand analytics anomalies
	 */
	public function analytics_anomalies( array $args, array $assoc_args ): void {
		$pa        = new PredictiveAnalytics();
		$anomalies = $pa->get_anomalies();

		if ( empty( $anomalies ) ) {
			\WP_CLI::success( 'No anomalies detected.' );
			return;
		}

		\WP_CLI\Utils\format_items( 'table', $anomalies, [ 'post_id', 'drop_pct', 'prev_views', 'last_views', 'detected_at' ] );
	}

	/**
	 * Trigger a full predictive analytics refresh.
	 *
	 * @subcommand analytics refresh
	 */
	public function analytics_refresh( array $args, array $assoc_args ): void {
		\WP_CLI::line( 'Running predictive analytics refresh…' );
		( new PredictiveAnalytics() )->refresh();
		\WP_CLI::success( 'Analytics refresh complete.' );
	}

	// -----------------------------------------------------------------------
	// A/B Testing
	// -----------------------------------------------------------------------

	/**
	 * Generate AI-powered variant modifiers for a test topic.
	 *
	 * ## OPTIONS
	 * --topic=<topic>
	 * : The article topic.
	 *
	 * [--variants=<n>]
	 * : Number of variants to generate (1–5). Default: 2.
	 *
	 * @subcommand ab generate
	 */
	public function ab_generate( array $args, array $assoc_args ): void {
		$topic    = (string) ( $assoc_args['topic'] ?? '' );
		$variants = (int) ( $assoc_args['variants'] ?? 2 );

		if ( '' === $topic ) {
			\WP_CLI::error( 'Missing --topic parameter.' );
		}

		\WP_CLI::line( "Generating {$variants} variant(s) for: {$topic}" );

		$gen      = new AIVariantGenerator();
		$result   = $gen->generate_variants( $topic, $variants );

		foreach ( $result as $key => $modifier ) {
			\WP_CLI::line( "[{$key}] {$modifier}" );
		}
	}

	/**
	 * Run Bayesian evaluation on an A/B test.
	 *
	 * ## OPTIONS
	 * <test_id>
	 * : The test ID (e.g. ab_1234abcd).
	 *
	 * ## EXAMPLES
	 *   wp pearblog v9 ab evaluate ab_1234abcd
	 *
	 * @subcommand ab evaluate
	 */
	public function ab_evaluate( array $args, array $assoc_args ): void {
		$test_id = $args[0] ?? '';
		if ( '' === $test_id ) {
			\WP_CLI::error( 'Please provide a test ID.' );
		}

		$engine = new ABTestEngine();
		$test   = $engine->get_test( $test_id );

		if ( null === $test ) {
			\WP_CLI::error( "Test not found: {$test_id}" );
		}

		$opt    = new BayesianOptimizer();
		$result = $opt->evaluate( $test );

		\WP_CLI::line( "Test: {$test_id} — topic: {$test['topic']}" );
		\WP_CLI::line( "  P(A beats B): " . round( $result['prob_a_better'] * 100, 1 ) . '%' );
		\WP_CLI::line( '  Confident:    ' . ( $result['confident'] ? 'yes' : 'no (more data needed)' ) );
		\WP_CLI::line( '  Winner:       ' . ( $result['winner'] ?? 'undecided' ) );
		\WP_CLI::line( "  Samples A/B:  {$result['samples_a']} / {$result['samples_b']}" );

		if ( $result['confident'] && $result['winner'] ) {
			\WP_CLI::success( "Promote variant {$result['winner']}." );
		} else {
			\WP_CLI::line( 'Continue collecting data.' );
		}
	}

	// -----------------------------------------------------------------------
	// Router
	// -----------------------------------------------------------------------

	/**
	 * Show SmartProviderRouter status and per-provider stats.
	 *
	 * @subcommand router status
	 */
	public function router_status( array $args, array $assoc_args ): void {
		$router   = new SmartProviderRouter();
		$strategy = $router->get_strategy();
		$health   = $router->get_health();
		$stats    = $router->get_stats();

		\WP_CLI::line( "Active strategy: {$strategy}" );
		\WP_CLI::line( '' );

		$rows = [];
		foreach ( $stats as $slug => $s ) {
			$avg_latency = $s['total_calls'] > 0 ? (int) ( $s['total_latency_ms'] / $s['total_calls'] ) : 0;
			$rows[]      = [
				'provider'    => $slug,
				'health'      => $health[ $slug ] ?? 'healthy',
				'calls'       => $s['total_calls'],
				'success'     => $s['success'],
				'failures'    => $s['failures'],
				'avg_ms'      => $avg_latency,
			];
		}

		if ( empty( $rows ) ) {
			\WP_CLI::line( 'No calls recorded yet.' );
		} else {
			\WP_CLI\Utils\format_items( 'table', $rows, [ 'provider', 'health', 'calls', 'success', 'failures', 'avg_ms' ] );
		}
	}

	/**
	 * Set the routing strategy.
	 *
	 * ## OPTIONS
	 * <strategy>
	 * : One of: cost_optimised, quality_first, round_robin, failover.
	 *
	 * @subcommand router strategy
	 */
	public function router_strategy( array $args, array $assoc_args ): void {
		$strategy = $args[0] ?? '';
		if ( '' === $strategy ) {
			\WP_CLI::error( 'Please provide a strategy: ' . implode( ', ', SmartProviderRouter::STRATEGIES ) );
		}

		try {
			( new SmartProviderRouter() )->set_strategy( $strategy );
			\WP_CLI::success( "Routing strategy set to: {$strategy}" );
		} catch ( \InvalidArgumentException $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
	}

	// -----------------------------------------------------------------------
	// Orphan pages
	// -----------------------------------------------------------------------

	/**
	 * Scan all published posts for orphan pages.
	 *
	 * @subcommand orphans scan
	 */
	public function orphans_scan( array $args, array $assoc_args ): void {
		\WP_CLI::line( 'Scanning for orphan pages…' );
		$detector = new OrphanPageDetector();
		$orphans  = $detector->scan();
		\WP_CLI::success( count( $orphans ) . ' orphan page(s) found.' );
	}

	/**
	 * List stored orphan pages.
	 *
	 * @subcommand orphans list
	 */
	public function orphans_list( array $args, array $assoc_args ): void {
		$orphans = ( new OrphanPageDetector() )->get_orphans();

		if ( empty( $orphans ) ) {
			\WP_CLI::success( 'No orphan pages detected.' );
			return;
		}

		\WP_CLI\Utils\format_items( 'table', $orphans, [ 'post_id', 'title', 'quality_score', 'detected_at' ] );
	}

	/**
	 * Apply auto-fix for a single orphan page.
	 *
	 * ## OPTIONS
	 * <post_id>
	 * : The post ID of the orphan to fix.
	 *
	 * @subcommand orphans fix
	 */
	public function orphans_fix( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Please provide a valid post_id.' );
		}

		$fixed = ( new OrphanPageDetector() )->apply_fix( $post_id );
		if ( $fixed ) {
			\WP_CLI::success( "Orphan fix applied to post #{$post_id}." );
		} else {
			\WP_CLI::warning( "Could not auto-fix orphan #{$post_id}. Apply a link manually." );
		}
	}

	// -----------------------------------------------------------------------
	// Content refresh prioritization
	// -----------------------------------------------------------------------

	/**
	 * Show the smart content refresh priority queue.
	 *
	 * ## OPTIONS
	 * [--limit=<n>]
	 * : Number of posts to display. Default: 20.
	 *
	 * @subcommand refresh-score
	 */
	public function refresh_score( array $args, array $assoc_args ): void {
		$limit  = (int) ( $assoc_args['limit'] ?? 20 );
		$queue  = ( new ContentRefreshPrioritizer() )->get_ranked_queue( $limit );

		if ( empty( $queue ) ) {
			\WP_CLI::line( 'No scores computed yet. Run: wp pearblog v9 refresh-rescore' );
			return;
		}

		$rows = array_map( static fn( $item ) => [
			'post_id'      => $item['post_id'],
			'score'        => $item['score'],
			'trend_pts'    => $item['factors']['trend_pts'] ?? 0,
			'age_pts'      => $item['factors']['age_pts'] ?? 0,
			'quality_pts'  => $item['factors']['quality_pts'] ?? 0,
			'decay_pts'    => $item['factors']['decay_pts'] ?? 0,
		], $queue );

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'post_id', 'score', 'trend_pts', 'age_pts', 'quality_pts', 'decay_pts' ] );
	}

	/**
	 * Re-score all posts for the content refresh priority queue.
	 *
	 * @subcommand refresh-rescore
	 */
	public function refresh_rescore( array $args, array $assoc_args ): void {
		\WP_CLI::line( 'Re-scoring all posts…' );
		$count = ( new ContentRefreshPrioritizer() )->rescore_all();
		\WP_CLI::success( "{$count} post(s) scored." );
	}

	// -----------------------------------------------------------------------
	// Collaboration
	// -----------------------------------------------------------------------

	/**
	 * Show collaboration state for a post.
	 *
	 * ## OPTIONS
	 * <post_id>
	 *
	 * @subcommand collab status
	 */
	public function collab_status( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Please provide a valid post_id.' );
		}

		$state = ( new CollaborationManager() )->get_state( $post_id );

		\WP_CLI::line( "Post #: {$post_id}" );
		\WP_CLI::line( "Status:    {$state['status']}" );
		\WP_CLI::line( 'Reviewers: ' . implode( ', ', $state['reviewers'] ) );
		\WP_CLI::line( 'Comments:  ' . count( $state['comments'] ) );
		\WP_CLI::line( 'History:   ' . count( $state['history'] ) . ' event(s)' );
	}

	/**
	 * Assign reviewers to a post.
	 *
	 * ## OPTIONS
	 * <post_id>
	 *
	 * --reviewers=<ids>
	 * : Comma-separated user IDs.
	 *
	 * @subcommand collab assign
	 */
	public function collab_assign( array $args, array $assoc_args ): void {
		$post_id      = (int) ( $args[0] ?? 0 );
		$raw_ids      = (string) ( $assoc_args['reviewers'] ?? '' );

		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Please provide a valid post_id.' );
		}

		$reviewer_ids = array_map( 'intval', explode( ',', $raw_ids ) );
		$reviewer_ids = array_filter( $reviewer_ids );

		if ( empty( $reviewer_ids ) ) {
			\WP_CLI::error( 'Please provide at least one reviewer ID with --reviewers=<ids>.' );
		}

		$mgr = new CollaborationManager();
		$mgr->assign_reviewers( $post_id, $reviewer_ids );
		$mgr->set_status( $post_id, 'in_review' );

		\WP_CLI::success( "Reviewers assigned to post #{$post_id}." );
	}

	/**
	 * Submit a review decision for a post.
	 *
	 * ## OPTIONS
	 * <post_id>
	 *
	 * --decision=<decision>
	 * : One of: approved, changes_requested, rejected.
	 *
	 * [--note=<note>]
	 * : Optional note.
	 *
	 * @subcommand collab review
	 */
	public function collab_review( array $args, array $assoc_args ): void {
		$post_id  = (int) ( $args[0] ?? 0 );
		$decision = (string) ( $assoc_args['decision'] ?? '' );
		$note     = (string) ( $assoc_args['note'] ?? '' );

		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Please provide a valid post_id.' );
		}

		$valid = [ 'approved', 'changes_requested', 'rejected' ];
		if ( ! in_array( $decision, $valid, true ) ) {
			\WP_CLI::error( 'Invalid decision. Use: ' . implode( ', ', $valid ) );
		}

		( new CollaborationManager() )->submit_review( $post_id, $decision, $note );
		\WP_CLI::success( "Review submitted: {$decision} for post #{$post_id}." );
	}

	// -----------------------------------------------------------------------
	// Billing
	// -----------------------------------------------------------------------

	/**
	 * Show current billing period AI usage.
	 *
	 * @subcommand billing usage
	 */
	public function billing_usage( array $args, array $assoc_args ): void {
		$engine  = new BillingEngine();
		$usage   = $engine->get_current_usage();
		$quota   = (float) get_option( BillingEngine::OPTION_QUOTA, BillingEngine::DEFAULT_QUOTA );
		$pct     = $engine->get_usage_percentage();
		$period  = gmdate( 'Y-m-d', $usage['period_start'] );

		\WP_CLI::log( sprintf( 'Period start : %s', $period ) );
		\WP_CLI::log( sprintf( 'Generations  : %d', $usage['generations'] ) );
		\WP_CLI::log( sprintf( 'AI spend     : $%.2f', $usage['cost_cents'] / 100 ) );
		\WP_CLI::log( sprintf( 'Monthly quota: $%.2f', $quota / 100 ) );
		\WP_CLI::log( sprintf( 'Usage        : %.1f%%', $pct ) );

		if ( $pct >= 100.0 ) {
			\WP_CLI::warning( 'Monthly quota exceeded!' );
		}
	}

	/**
	 * Show billing usage history (last 12 months).
	 *
	 * @subcommand billing history
	 */
	public function billing_history( array $args, array $assoc_args ): void {
		$history = (array) get_option( BillingEngine::OPTION_USAGE_HISTORY, [] );

		if ( empty( $history ) ) {
			\WP_CLI::log( 'No billing history found.' );
			return;
		}

		$rows = [];
		foreach ( $history as $period ) {
			$rows[] = [
				'Period start'  => gmdate( 'Y-m', $period['period_start'] ?? 0 ),
				'Generations'   => $period['generations'] ?? 0,
				'Spend (USD)'   => sprintf( '$%.2f', ( $period['cost_cents'] ?? 0 ) / 100 ),
			];
		}

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'Period start', 'Generations', 'Spend (USD)' ] );
	}

	// -----------------------------------------------------------------------
	// Tenant
	// -----------------------------------------------------------------------

	/**
	 * Provision a new tenant.
	 *
	 * ## OPTIONS
	 * --domain=<domain>
	 * : Tenant domain.
	 *
	 * [--title=<title>]
	 * : Site title. Defaults to domain.
	 *
	 * [--industry=<industry>]
	 * : Industry/niche. Default: general.
	 *
	 * [--tone=<tone>]
	 * : Writing tone. Default: professional.
	 *
	 * [--language=<language>]
	 * : ISO language code. Default: en.
	 *
	 * [--plan=<plan>]
	 * : Plan tier: starter | pro | enterprise. Default: starter.
	 *
	 * [--admin=<email>]
	 * : Admin email for the new site.
	 *
	 * @subcommand tenant provision
	 */
	public function tenant_provision( array $args, array $assoc_args ): void {
		$domain = (string) ( $assoc_args['domain'] ?? '' );
		if ( '' === $domain ) {
			\WP_CLI::error( 'Please provide --domain=<domain>.' );
		}

		$params = [
			'domain'      => $domain,
			'title'       => (string) ( $assoc_args['title'] ?? '' ),
			'industry'    => (string) ( $assoc_args['industry'] ?? 'general' ),
			'tone'        => (string) ( $assoc_args['tone'] ?? 'professional' ),
			'language'    => (string) ( $assoc_args['language'] ?? 'en' ),
			'plan'        => (string) ( $assoc_args['plan'] ?? 'starter' ),
			'admin_email' => (string) ( $assoc_args['admin'] ?? get_option( 'admin_email', '' ) ),
		];

		$result = ( new TenantOnboardingController() )->provision( $params );

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}

		\WP_CLI::success( sprintf(
			"Tenant provisioned — site_id: %d, domain: %s, plan: %s",
			$result['site_id'],
			$result['domain'],
			$result['plan']
		) );
		\WP_CLI::log( 'Admin URL: ' . $result['admin_url'] );
	}

	/**
	 * List all provisioned tenants.
	 *
	 * @subcommand tenant list
	 */
	public function tenant_list( array $args, array $assoc_args ): void {
		$tenants = ( new TenantOnboardingController() )->list_tenants();

		if ( empty( $tenants ) ) {
			\WP_CLI::log( 'No tenants provisioned yet.' );
			return;
		}

		$rows = array_map( static function ( array $t ): array {
			return [
				'Site ID'  => $t['site_id'] ?? '',
				'Domain'   => $t['domain'] ?? '',
				'Plan'     => $t['plan'] ?? '',
				'Industry' => $t['industry'] ?? '',
				'Language' => $t['language'] ?? '',
			];
		}, $tenants );

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'Site ID', 'Domain', 'Plan', 'Industry', 'Language' ] );
	}

	// -----------------------------------------------------------------------
	// Audit log
	// -----------------------------------------------------------------------

	/**
	 * Show recent pipeline audit log entries.
	 *
	 * ## OPTIONS
	 * [--limit=<n>]
	 * : Number of entries to display. Default: 25.
	 *
	 * [--level=<level>]
	 * : Filter by severity: info | warning | error.
	 *
	 * @subcommand audit log
	 */
	public function audit_log( array $args, array $assoc_args ): void {
		$limit = max( 1, (int) ( $assoc_args['limit'] ?? 25 ) );
		$level = isset( $assoc_args['level'] ) ? (string) $assoc_args['level'] : null;

		$entries = ( new PipelineAuditLog() )->get_events( $limit, $level );

		if ( empty( $entries ) ) {
			\WP_CLI::log( 'No audit log entries found.' );
			return;
		}

		$rows = array_map( static function ( array $e ): array {
			return [
				'Time'    => $e['timestamp'] ?? '',
				'Level'   => strtoupper( $e['level'] ?? 'INFO' ),
				'Event'   => $e['event'] ?? '',
			];
		}, $entries );

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'Time', 'Level', 'Event' ] );
	}

	// -----------------------------------------------------------------------
	// PII / GDPR
	// -----------------------------------------------------------------------

	/**
	 * Scan a post for PII (Personally Identifiable Information).
	 *
	 * ## OPTIONS
	 * <post_id>
	 * : ID of the post to scan.
	 *
	 * @subcommand pii scan
	 */
	public function pii_scan( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Please provide a valid post_id.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			\WP_CLI::error( "Post #{$post_id} not found." );
		}

		$detector = new PIIDetector();
		$content  = $post->post_content ?? '';
		$findings = $detector->scan( $content );

		if ( empty( $findings ) ) {
			\WP_CLI::success( "No PII detected in post #{$post_id}." );
			return;
		}

		\WP_CLI::warning( sprintf( '%d PII match(es) found in post #%d:', count( $findings ), $post_id ) );
		foreach ( $findings as $type => $matches ) {
			\WP_CLI::log( sprintf( '  [%s] %d match(es)', strtoupper( $type ), count( (array) $matches ) ) );
		}
	}

	/**
	 * Export PII compliance report.
	 *
	 * ## OPTIONS
	 * [--days=<n>]
	 * : Number of days to include in the report. Default: 30.
	 *
	 * [--format=<format>]
	 * : Output format: json | csv. Default: json.
	 *
	 * @subcommand pii export
	 */
	public function pii_export( array $args, array $assoc_args ): void {
		$days   = max( 1, (int) ( $assoc_args['days'] ?? 30 ) );
		$format = (string) ( $assoc_args['format'] ?? 'json' );

		$exporter = new ComplianceExporter();
		$report   = $exporter->build_report( $days, $format );

		if ( 'csv' === $format ) {
			\WP_CLI::log( $exporter->to_csv( $report ) );
		} else {
			\WP_CLI::log( wp_json_encode( $report, JSON_PRETTY_PRINT ) );
		}

		\WP_CLI::success( sprintf( 'PII compliance report generated (%d days).', $days ) );
	}

	// -----------------------------------------------------------------------
	// ROI
	// -----------------------------------------------------------------------

	/**
	 * Show ROI and conversion statistics.
	 *
	 * ## OPTIONS
	 * [--days=<n>]
	 * : Number of days to analyze. Default: 30.
	 *
	 * @subcommand roi report
	 */
	public function roi_report( array $args, array $assoc_args ): void {
		$days    = max( 1, (int) ( $assoc_args['days'] ?? 30 ) );
		$tracker = new ConversionTracker();
		$totals  = $tracker->get_totals();
		$funnel  = $tracker->get_funnel_view();

		\WP_CLI::log( sprintf( '=== ROI Report — Last %d days ===', $days ) );
		\WP_CLI::log( '' );

		if ( ! empty( $totals ) ) {
			\WP_CLI::log( 'Conversion totals:' );
			foreach ( $totals as $event_type => $count ) {
				\WP_CLI::log( sprintf( '  %-25s %d', $event_type . ':', (int) $count ) );
			}
			\WP_CLI::log( '' );
		}

		if ( ! empty( $funnel ) ) {
			\WP_CLI::log( 'Funnel view:' );
			foreach ( $funnel as $stage => $data ) {
				if ( is_array( $data ) ) {
					\WP_CLI::log( sprintf( '  [%s] count=%d', $stage, $data['count'] ?? 0 ) );
				}
			}
		}

		\WP_CLI::success( 'ROI report complete.' );
	}
}
