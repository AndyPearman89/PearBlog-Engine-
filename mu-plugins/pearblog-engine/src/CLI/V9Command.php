<?php
/**
 * WP-CLI command group: `wp pearblog v9`
 *
 * Commands:
 *   wp pearblog v9 forecast <post_id> [--days=<days>]
 *   wp pearblog v9 revenue-forecast [--days=<days>]
 *   wp pearblog v9 anomalies <post_id> [--threshold=<threshold>]
 *   wp pearblog v9 optimize <post_id>
 *   wp pearblog v9 collab assign <post_id> <user_id>
 *   wp pearblog v9 collab request-review <post_id> <reviewer_id> [--notes=<notes>]
 *   wp pearblog v9 collab approve <review_id> [--reviewer=<user_id>]
 *   wp pearblog v9 collab reject <review_id> <feedback> [--reviewer=<user_id>]
 *   wp pearblog v9 collab pending [--reviewer=<user_id>]
 *   wp pearblog v9 collab workload
 *   wp pearblog v9 collab snapshot <post_id> [--label=<label>]
 *   wp pearblog v9 collab history <post_id>
 *   wp pearblog v9 mobile dashboard
 *   wp pearblog v9 mobile queue
 *   wp pearblog v9 ab generate <post_id> [--type=<type>] [--count=<count>]
 *   wp pearblog v9 ab all <post_id> [--count=<count>]
 *   wp pearblog v9 ab summary <post_id> <test_id> <variant_ids>
 *   wp pearblog v9 router status
 *   wp pearblog v9 router stats
 *   wp pearblog v9 router reset-stats
 *   wp pearblog v9 orphans scan [--force]
 *   wp pearblog v9 orphans detail <post_id>
 *   wp pearblog v9 orphans suggest <post_id>
 *   wp pearblog v9 orphans mark-reviewed <post_id>
 *   wp pearblog v9 billing usage
 *   wp pearblog v9 billing reset
 *   wp pearblog v9 tenant create --domain=<domain> [--industry=<industry>] [--tone=<tone>] [--language=<language>] [--plan=<plan>] [--title=<title>] [--admin=<email>]
 *   wp pearblog v9 tenant list
 *   wp pearblog v9 audit run [--export=<file>]
 *   wp pearblog v9 pii scan <post_id> [--redact]
 *   wp pearblog v9 roi article <post_id>
 *   wp pearblog v9 roi snapshot [--refresh]
 *   wp pearblog v9 moderation status
 *   wp pearblog v9 moderation check <post_id>
 *   wp pearblog v9 rbac list
 *   wp pearblog v9 rbac capabilities
 *   wp pearblog v9 compliance report [--days=<days>] [--format=<format>]
 *   wp pearblog v9 amp status
 *   wp pearblog v9 amp convert <post_id>
 *
 * @package PearBlogEngine\CLI
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

use PearBlogEngine\Analytics\PredictiveAnalytics;
use PearBlogEngine\Analytics\ContentROIEngine;
use PearBlogEngine\Content\CollaborationManager;
use PearBlogEngine\Testing\AIVariantGenerator;
use PearBlogEngine\Testing\BayesianOptimizer;
use PearBlogEngine\AI\SmartProviderRouter;
use PearBlogEngine\SEO\OrphanPageDetector;
use PearBlogEngine\Tenant\BillingEngine;
use PearBlogEngine\Tenant\TenantOnboardingController;
use PearBlogEngine\Security\SecurityAuditor;
use PearBlogEngine\Security\PIIDetector;
use PearBlogEngine\Security\ContentModerator;
use PearBlogEngine\Security\RBACManager;
use PearBlogEngine\Security\ComplianceExporter;
use PearBlogEngine\Distribution\AMPGenerator;

/**
 * V9 CLI command group.
 *
 * @when after_wp_load
 */
class V9Command {

	private PredictiveAnalytics $analytics;
	private ContentROIEngine $roi;
	private CollaborationManager $collab;
	private AIVariantGenerator $variant_gen;
	private BayesianOptimizer $bayesian;
	private SmartProviderRouter $router;
	private OrphanPageDetector $orphan_detector;
	private BillingEngine $billing;
	private TenantOnboardingController $tenant;
	private SecurityAuditor $auditor;
	private PIIDetector $pii;
	private ContentModerator $moderator;
	private RBACManager $rbac;
	private ComplianceExporter $compliance;
	private AMPGenerator $amp;

	public function __construct() {
		$this->analytics       = new PredictiveAnalytics();
		$this->roi             = new ContentROIEngine();
		$this->collab          = new CollaborationManager();
		$this->variant_gen     = new AIVariantGenerator();
		$this->bayesian        = new BayesianOptimizer();
		$this->router          = new SmartProviderRouter();
		$this->orphan_detector = new OrphanPageDetector();
		$this->billing         = new BillingEngine();
		$this->tenant          = new TenantOnboardingController();
		$this->auditor         = new SecurityAuditor();
		$this->pii             = new PIIDetector();
		$this->moderator       = new ContentModerator();
		$this->rbac            = new RBACManager();
		$this->compliance      = new ComplianceExporter();
		$this->amp             = new AMPGenerator();
	}

	// -----------------------------------------------------------------------
	// Analytics sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Forecast performance for a post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : Post ID to forecast.
	 *
	 * [--days=<days>]
	 * : Number of future days to project. Default: 30.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 forecast 42
	 *   wp pearblog v9 forecast 42 --days=90
	 *
	 * @param  array<int, string>    $args       Positional args.
	 * @param  array<string, string> $assoc_args Named args.
	 */
	public function forecast( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Please supply a valid post ID.' );
		}

		$days   = (int) ( $assoc_args['days'] ?? 30 );
		$result = $this->analytics->forecast_performance( $post_id, $days );

		\WP_CLI::line( "Post #{$post_id} — {$days}-day forecast" );
		\WP_CLI::line( "Trend      : {$result['trend']}" );
		\WP_CLI::line( "Confidence : " . round( $result['confidence'] * 100, 1 ) . ' %' );
		\WP_CLI::line( "Slope      : {$result['slope']} views/day" );

		$table = [];
		foreach ( $result['projected_views'] as $i => $v ) {
			$table[] = [ 'Day' => $i + 1, 'Projected Views' => $v ];
		}

		\WP_CLI\Utils\format_items( 'table', $table, [ 'Day', 'Projected Views' ] );
	}

	/**
	 * Forecast site-level revenue.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Number of future days to project. Default: 90.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 revenue-forecast
	 *   wp pearblog v9 revenue-forecast --days=30
	 *
	 * @param  array<int, string>    $args       Positional args.
	 * @param  array<string, string> $assoc_args Named args.
	 */
	public function revenue_forecast( array $args, array $assoc_args ): void {
		$days   = (int) ( $assoc_args['days'] ?? 90 );
		$result = $this->analytics->get_revenue_forecast( $days );

		\WP_CLI::line( "{$days}-day revenue forecast" );
		\WP_CLI::line( "Trend           : {$result['trend']}" );
		\WP_CLI::line( "Confidence      : " . round( $result['confidence'] * 100, 1 ) . ' %' );
		\WP_CLI::success( "Total projected : \${$result['total_projected']}" );
	}

	/**
	 * Detect anomalies in a post's traffic.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : Post ID to analyse.
	 *
	 * [--threshold=<threshold>]
	 * : Z-score threshold (default: 2.0).
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 anomalies 42
	 *   wp pearblog v9 anomalies 42 --threshold=3
	 *
	 * @param  array<int, string>    $args       Positional args.
	 * @param  array<string, string> $assoc_args Named args.
	 */
	public function anomalies( array $args, array $assoc_args ): void {
		$post_id   = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Please supply a valid post ID.' );
		}

		$threshold = (float) ( $assoc_args['threshold'] ?? 2.0 );
		$result    = $this->analytics->get_anomalies( $post_id, 'views', $threshold );

		\WP_CLI::line( "Post #{$post_id} — anomaly detection (z ≥ {$threshold})" );
		\WP_CLI::line( "Total days analysed : {$result['total_days']}" );
		\WP_CLI::line( "Mean daily views    : {$result['mean']}" );
		\WP_CLI::line( "Std deviation       : {$result['std_dev']}" );

		if ( empty( $result['anomalies'] ) ) {
			\WP_CLI::success( 'No anomalies detected.' );
			return;
		}

		\WP_CLI\Utils\format_items(
			'table',
			$result['anomalies'],
			[ 'day', 'value', 'z_score' ]
		);
	}

	/**
	 * Generate content optimisation recommendations for a post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : Post ID to optimise.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 optimize 42
	 *
	 * @param  array<int, string>    $args       Positional args.
	 * @param  array<string, string> $assoc_args Named args.
	 */
	public function optimize( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Please supply a valid post ID.' );
		}

		$result = $this->analytics->recommend_optimizations( $post_id );

		\WP_CLI::line( "Optimisation score: {$result['score']}/100" );
		foreach ( $result['recommendations'] as $i => $rec ) {
			\WP_CLI::line( ( $i + 1 ) . ". {$rec}" );
		}
	}

	// -----------------------------------------------------------------------
	// Collaboration sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Manage content collaboration (reviews, comments, versioning).
	 *
	 * ## SUBCOMMANDS
	 *
	 *   assign           Assign an editor to a post.
	 *   request-review   Request a review for a post.
	 *   approve          Approve a review request.
	 *   reject           Reject a review request.
	 *   pending          List pending reviews.
	 *   workload         Show team workload summary.
	 *   snapshot         Snapshot a post's current content.
	 *   history          Show version history for a post.
	 *
	 * @param  array<int, string>    $args       Positional args.
	 * @param  array<string, string> $assoc_args Named args.
	 */
	public function collab( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'assign':
				$this->collab_assign( $args, $assoc_args );
				break;
			case 'request-review':
				$this->collab_request_review( $args, $assoc_args );
				break;
			case 'approve':
				$this->collab_approve( $args, $assoc_args );
				break;
			case 'reject':
				$this->collab_reject( $args, $assoc_args );
				break;
			case 'pending':
				$this->collab_pending( $args, $assoc_args );
				break;
			case 'workload':
				$this->collab_workload( $args, $assoc_args );
				break;
			case 'snapshot':
				$this->collab_snapshot( $args, $assoc_args );
				break;
			case 'history':
				$this->collab_history( $args, $assoc_args );
				break;
			default:
				\WP_CLI::error( "Unknown sub-command '{$sub}'. Run `wp help pearblog v9 collab`." );
		}
	}

	private function collab_assign( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		$user_id = (int) ( $args[1] ?? 0 );
		if ( $post_id <= 0 || $user_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 collab assign <post_id> <user_id>' );
		}
		$this->collab->assign_editor( $post_id, $user_id );
		\WP_CLI::success( "Editor #{$user_id} assigned to post #{$post_id}." );
	}

	private function collab_request_review( array $args, array $assoc_args ): void {
		$post_id     = (int) ( $args[0] ?? 0 );
		$reviewer_id = (int) ( $args[1] ?? 0 );
		if ( $post_id <= 0 || $reviewer_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 collab request-review <post_id> <reviewer_id> [--notes=<notes>]' );
		}
		$notes     = $assoc_args['notes'] ?? '';
		$review_id = $this->collab->create_review_request( $post_id, $reviewer_id, $notes );
		\WP_CLI::success( "Review request #{$review_id} created for post #{$post_id}." );
	}

	private function collab_approve( array $args, array $assoc_args ): void {
		$review_id   = (int) ( $args[0] ?? 0 );
		$reviewer_id = (int) ( $assoc_args['reviewer'] ?? get_current_user_id() );
		if ( $review_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 collab approve <review_id>' );
		}
		$ok = $this->collab->approve_content( $review_id, $reviewer_id );
		$ok ? \WP_CLI::success( "Review #{$review_id} approved." )
		    : \WP_CLI::error( "Review #{$review_id} not found." );
	}

	private function collab_reject( array $args, array $assoc_args ): void {
		$review_id   = (int) ( $args[0] ?? 0 );
		$feedback    = $args[1] ?? '';
		$reviewer_id = (int) ( $assoc_args['reviewer'] ?? get_current_user_id() );
		if ( $review_id <= 0 || '' === trim( $feedback ) ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 collab reject <review_id> <feedback>' );
		}
		$ok = $this->collab->reject_content( $review_id, $reviewer_id, $feedback );
		$ok ? \WP_CLI::success( "Review #{$review_id} rejected." )
		    : \WP_CLI::error( "Review #{$review_id} not found or feedback missing." );
	}

	private function collab_pending( array $args, array $assoc_args ): void {
		$reviewer_id = (int) ( $assoc_args['reviewer'] ?? 0 );
		$pending     = $this->collab->get_pending_reviews( $reviewer_id );

		if ( empty( $pending ) ) {
			\WP_CLI::success( 'No pending reviews.' );
			return;
		}

		$table = array_map( static function ( array $r ): array {
			return [
				'ID'          => $r['id'],
				'Post'        => $r['post_id'],
				'Reviewer'    => $r['reviewer_id'],
				'Notes'       => mb_strimwidth( $r['notes'], 0, 40, '…' ),
				'Created'     => gmdate( 'Y-m-d H:i', $r['created_at'] ),
			];
		}, $pending );

		\WP_CLI\Utils\format_items( 'table', $table, [ 'ID', 'Post', 'Reviewer', 'Notes', 'Created' ] );
	}

	private function collab_workload( array $args, array $assoc_args ): void {
		$workload = $this->collab->get_team_workload();

		if ( empty( $workload ) ) {
			\WP_CLI::success( 'No workload data available.' );
			return;
		}

		\WP_CLI\Utils\format_items(
			'table',
			$workload,
			[ 'user_id', 'assigned_posts', 'pending_reviews' ]
		);
	}

	private function collab_snapshot( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 collab snapshot <post_id> [--label=<label>]' );
		}
		$post    = get_post( $post_id );
		if ( ! $post ) {
			\WP_CLI::error( "Post #{$post_id} not found." );
		}
		$label   = $assoc_args['label'] ?? '';
		$version = $this->collab->snapshot_version( $post_id, $post->post_content, get_current_user_id(), $label );
		\WP_CLI::success( "Snapshotted post #{$post_id} as version #{$version}." );
	}

	private function collab_history( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 collab history <post_id>' );
		}
		$history = $this->collab->get_content_history( $post_id );

		if ( empty( $history ) ) {
			\WP_CLI::success( 'No version history.' );
			return;
		}

		$table = array_map( static function ( array $v ): array {
			return [
				'Version' => $v['version'],
				'User'    => $v['user_id'],
				'Label'   => $v['label'],
				'Hash'    => substr( $v['hash'], 0, 8 ),
				'Created' => gmdate( 'Y-m-d H:i', $v['created_at'] ),
			];
		}, $history );

		\WP_CLI\Utils\format_items( 'table', $table, [ 'Version', 'User', 'Label', 'Hash', 'Created' ] );
	}

	// -----------------------------------------------------------------------
	// Mobile sub-commands (convenience wrappers — no HTTP needed)
	// -----------------------------------------------------------------------

	/**
	 * Show mobile dashboard snapshot.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 mobile dashboard
	 *
	 * @param  array<int, string>    $args       Positional args.
	 * @param  array<string, string> $assoc_args Named args.
	 */
	public function mobile( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'dashboard':
				$last_run = (int) get_option( 'pearblog_last_pipeline_run', 0 );
				$fc       = $this->analytics->get_revenue_forecast( 7 );
				\WP_CLI::line( 'Last pipeline run : ' . ( $last_run ? human_time_diff( $last_run ) . ' ago' : 'never' ) );
				\WP_CLI::line( '7-day rev forecast: $' . $fc['total_projected'] . ' (' . $fc['trend'] . ')' );
				$pending = count( $this->collab->get_pending_reviews() );
				\WP_CLI::line( "Pending reviews   : {$pending}" );
				break;
			case 'queue':
				$posts = get_posts( [
					'post_status' => 'draft',
					'meta_key'    => '_pearblog_generated',
					'meta_value'  => '1',
					'numberposts' => 20,
				] );
				if ( empty( $posts ) ) {
					\WP_CLI::success( 'Queue is empty.' );
					return;
				}
				$table = array_map( static fn( $p ) => [
					'ID'    => $p->ID,
					'Title' => mb_strimwidth( get_the_title( $p->ID ), 0, 60, '…' ),
					'Date'  => $p->post_date,
				], $posts );
				\WP_CLI\Utils\format_items( 'table', $table, [ 'ID', 'Title', 'Date' ] );
				break;
			default:
				\WP_CLI::error( "Unknown mobile sub-command '{$sub}'." );
		}
	}

	// -----------------------------------------------------------------------
	// A/B Testing sub-commands (F3)
	// -----------------------------------------------------------------------

	/**
	 * Generate A/B test variants and manage Bayesian test summaries.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   generate     Generate content variants for a post.
	 *   all          Generate variants for all content types.
	 *   summary      Show Bayesian optimizer summary for a test.
	 *
	 * @param  array<int, string>    $args       Positional args.
	 * @param  array<string, string> $assoc_args Named args.
	 */
	public function ab( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'generate':
				$this->ab_generate( $args, $assoc_args );
				break;
			case 'all':
				$this->ab_all( $args, $assoc_args );
				break;
			case 'summary':
				$this->ab_summary( $args, $assoc_args );
				break;
			default:
				\WP_CLI::error( "Unknown ab sub-command '{$sub}'. Try: generate, all, summary." );
		}
	}

	private function ab_generate( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 ab generate <post_id> [--type=<type>] [--count=<count>]' );
		}

		$type   = $assoc_args['type']  ?? AIVariantGenerator::TYPE_HEADLINE;
		$count  = (int) ( $assoc_args['count'] ?? AIVariantGenerator::DEFAULT_VARIANT_COUNT );
		$result = $this->variant_gen->generate( $post_id, $type, $count, false );

		\WP_CLI::line( "Post #{$post_id} — {$count} {$type} variant(s) (source: {$result['source']})" );
		\WP_CLI::line( "Original: {$result['original']}" );
		foreach ( $result['variants'] as $i => $v ) {
			\WP_CLI::line( ( $i + 1 ) . ". {$v}" );
		}
	}

	private function ab_all( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 ab all <post_id> [--count=<count>]' );
		}

		$count   = (int) ( $assoc_args['count'] ?? AIVariantGenerator::DEFAULT_VARIANT_COUNT );
		$results = $this->variant_gen->generate_all( $post_id, $count );

		foreach ( $results as $type => $result ) {
			\WP_CLI::line( "── {$type} ──" );
			foreach ( $result['variants'] as $i => $v ) {
				\WP_CLI::line( '  ' . ( $i + 1 ) . ". {$v}" );
			}
		}
		\WP_CLI::success( 'Variants generated for all types.' );
	}

	private function ab_summary( array $args, array $assoc_args ): void {
		$post_id     = (int) ( $args[0] ?? 0 );
		$test_id     = $args[1] ?? '';
		$variant_csv = $args[2] ?? '';

		if ( $post_id <= 0 || '' === $test_id || '' === $variant_csv ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 ab summary <post_id> <test_id> <A,B,...>' );
		}

		$variants = array_filter( array_map( 'trim', explode( ',', $variant_csv ) ) );
		$summary  = $this->bayesian->summary( $post_id, $test_id, $variants );

		\WP_CLI::line( "Test: {$summary['test_id']} | Total impressions: {$summary['total_impressions']} | Ready: " . ( $summary['ready'] ? 'yes' : 'no' ) );

		$table = [];
		foreach ( $summary['variants'] as $vid => $vdata ) {
			$table[] = [
				'Variant'     => $vid,
				'Impressions' => $vdata['impressions'],
				'Conversions' => $vdata['conversions'],
				'Rate'        => round( $vdata['rate'] * 100, 2 ) . ' %',
				'Win Prob'    => round( ( $summary['win_probabilities'][ $vid ] ?? 0.0 ) * 100, 1 ) . ' %',
			];
		}
		\WP_CLI\Utils\format_items( 'table', $table, [ 'Variant', 'Impressions', 'Conversions', 'Rate', 'Win Prob' ] );

		if ( null !== $summary['winner'] ) {
			\WP_CLI::success( "Winner: {$summary['winner']}" );
		}
	}

	// -----------------------------------------------------------------------
	// Smart Provider Router sub-commands (F7)
	// -----------------------------------------------------------------------

	/**
	 * View or reset the smart AI provider router.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   status       Show routing order for each content type.
	 *   stats        Show provider performance statistics.
	 *   reset-stats  Clear all provider statistics.
	 *
	 * @param  array<int, string>    $args       Positional args.
	 * @param  array<string, string> $assoc_args Named args.
	 */
	public function router( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'status':
				$this->router_status( $args, $assoc_args );
				break;
			case 'stats':
				$this->router_stats( $args, $assoc_args );
				break;
			case 'reset-stats':
				$this->router->reset_stats();
				\WP_CLI::success( 'Provider statistics reset.' );
				break;
			default:
				\WP_CLI::error( "Unknown router sub-command '{$sub}'. Try: status, stats, reset-stats." );
		}
	}

	private function router_status( array $args, array $assoc_args ): void {
		$types = [
			SmartProviderRouter::CONTENT_LONG_FORM,
			SmartProviderRouter::CONTENT_SHORT_FORM,
			SmartProviderRouter::CONTENT_CODE,
			SmartProviderRouter::CONTENT_CREATIVE,
			SmartProviderRouter::CONTENT_FACTUAL,
			SmartProviderRouter::CONTENT_TRANSLATION,
		];

		$table = [];
		foreach ( $types as $type ) {
			$ordered  = $this->router->get_ordered_providers( $type );
			$table[]  = [
				'Content Type' => $type,
				'Routing Order' => implode( ' → ', $ordered ),
			];
		}

		\WP_CLI\Utils\format_items( 'table', $table, [ 'Content Type', 'Routing Order' ] );
		\WP_CLI::line( 'Budget today: $' . round( $this->router->get_today_cost() / 100, 4 ) .
		               ' / $' . round( $this->router->get_daily_budget() / 100, 2 ) );
	}

	private function router_stats( array $args, array $assoc_args ): void {
		$stats = $this->router->get_stats();

		if ( empty( $stats ) ) {
			\WP_CLI::success( 'No statistics recorded yet.' );
			return;
		}

		$table = [];
		foreach ( $stats as $slug => $s ) {
			$total   = ( $s['successes'] ?? 0 ) + ( $s['failures'] ?? 0 );
			$rate    = $total > 0 ? round( ( $s['successes'] ?? 0 ) / $total * 100, 1 ) : 0;
			$table[] = [
				'Provider'    => $slug,
				'Successes'   => $s['successes'] ?? 0,
				'Failures'    => $s['failures'] ?? 0,
				'Success Rate' => $rate . ' %',
				'Total Tokens' => $s['total_tokens'] ?? 0,
				'Cost (¢)'    => round( $s['total_cost_cents'] ?? 0.0, 2 ),
			];
		}

		\WP_CLI\Utils\format_items( 'table', $table, [ 'Provider', 'Successes', 'Failures', 'Success Rate', 'Total Tokens', 'Cost (¢)' ] );
	}

	// -----------------------------------------------------------------------
	// Orphan Page Detector sub-commands (F8)
	// -----------------------------------------------------------------------

	/**
	 * Detect and manage orphaned pages (F8 SEO Automation Suite).
	 *
	 * ## SUBCOMMANDS
	 *
	 *   scan           Scan all published content for orphan pages.
	 *   detail         Show details for a specific orphan.
	 *   suggest        Generate linking suggestions for an orphan.
	 *   mark-reviewed  Mark an orphan as reviewed/fixed.
	 *
	 * @param  array<int, string>    $args       Positional args.
	 * @param  array<string, string> $assoc_args Named args.
	 */
	public function orphans( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'scan':
				$this->orphans_scan( $args, $assoc_args );
				break;
			case 'detail':
				$this->orphans_detail( $args, $assoc_args );
				break;
			case 'suggest':
				$this->orphans_suggest( $args, $assoc_args );
				break;
			case 'mark-reviewed':
				$this->orphans_mark_reviewed( $args, $assoc_args );
				break;
			default:
				\WP_CLI::error( "Unknown orphans sub-command '{$sub}'. Try: scan, detail, suggest, mark-reviewed." );
		}
	}

	private function orphans_scan( array $args, array $assoc_args ): void {
		$force  = isset( $assoc_args['force'] );
		$result = $this->orphan_detector->scan( $force );

		\WP_CLI::line( "Scanned: {$result['total_scanned']} posts | Orphans: {$result['orphan_count']} | Cached: " . ( $result['cached'] ? 'yes' : 'no' ) );

		if ( empty( $result['orphans'] ) ) {
			\WP_CLI::success( 'No orphan pages found.' );
			return;
		}

		$table = [];
		foreach ( $result['orphans'] as $post_id ) {
			$table[] = [
				'ID'    => $post_id,
				'Title' => mb_strimwidth( (string) get_the_title( $post_id ), 0, 60, '…' ),
				'URL'   => (string) get_permalink( $post_id ),
			];
		}

		\WP_CLI\Utils\format_items( 'table', $table, [ 'ID', 'Title', 'URL' ] );
	}

	private function orphans_detail( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 orphans detail <post_id>' );
		}

		$detail = $this->orphan_detector->get_orphan_detail( $post_id );

		\WP_CLI::line( "Post    : #{$detail['post_id']} — {$detail['title']}" );
		\WP_CLI::line( "URL     : {$detail['url']}" );
		\WP_CLI::line( "Type    : {$detail['post_type']}" );
		\WP_CLI::line( "Inbound : {$detail['inbound_count']} link(s)" );
		\WP_CLI::line( "Reviewed: " . ( $detail['is_reviewed'] ? 'yes' : 'no' ) );

		if ( ! empty( $detail['suggestions'] ) ) {
			\WP_CLI::line( 'Suggested linking posts: ' . implode( ', ', $detail['suggestions'] ) );
		}
	}

	private function orphans_suggest( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 orphans suggest <post_id>' );
		}

		$suggestions = $this->orphan_detector->generate_suggestions( $post_id );

		if ( empty( $suggestions ) ) {
			\WP_CLI::success( 'No suggestions found.' );
			return;
		}

		\WP_CLI::line( 'Suggested linking posts (add links from these to post #' . $post_id . '):' );
		foreach ( $suggestions as $sid ) {
			\WP_CLI::line( "  #{$sid} — " . get_the_title( $sid ) );
		}
	}

	private function orphans_mark_reviewed( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 orphans mark-reviewed <post_id>' );
		}

		$this->orphan_detector->mark_reviewed( $post_id );
		\WP_CLI::success( "Post #{$post_id} marked as reviewed. It will be excluded from future scans." );
	}

	// -----------------------------------------------------------------------
	// Billing sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Show or reset AI token billing usage.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   usage   — Display current billing cycle usage and quota.
	 *   reset   — Reset the billing cycle (use with care).
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 billing usage
	 *   wp pearblog v9 billing reset
	 *
	 * @param  array<int, string>    $args
	 * @param  array<string, string> $assoc_args
	 */
	public function billing( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'usage':
				$this->billing_usage();
				break;
			case 'reset':
				$this->billing_reset();
				break;
			default:
				\WP_CLI::error( "Unknown billing sub-command '{$sub}'. Try: usage, reset." );
		}
	}

	private function billing_usage(): void {
		$usage = $this->billing->get_current_usage();
		$pct   = $this->billing->get_usage_percentage();

		\WP_CLI::line( sprintf( 'Cycle usage : %.2f¢ / %.2f¢ (%.1f%%)', $usage['used_cents'], $usage['quota_cents'], $pct ) );
		\WP_CLI::line( sprintf( 'Cycle start : %s', $usage['cycle_start'] ?? 'unknown' ) );

		if ( $pct >= 100 ) {
			\WP_CLI::warning( 'Quota exhausted — new generation requests will be blocked until the cycle resets.' );
		} elseif ( $pct >= 80 ) {
			\WP_CLI::warning( sprintf( 'Approaching quota limit (%.1f%% used).', $pct ) );
		} else {
			\WP_CLI::success( sprintf( 'Quota OK (%.1f%% used).', $pct ) );
		}
	}

	private function billing_reset(): void {
		$this->billing->reset_billing_cycle();
		\WP_CLI::success( 'Billing cycle reset. Usage counters cleared.' );
	}

	// -----------------------------------------------------------------------
	// Tenant sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Manage multi-tenant provisioning.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   create   — Provision a new tenant site.
	 *   list     — List all provisioned tenants.
	 *
	 * ## OPTIONS (create)
	 *
	 * --domain=<domain>
	 * : Domain for the new tenant (required).
	 *
	 * [--industry=<industry>]
	 * : Content industry/niche. Default: general.
	 *
	 * [--tone=<tone>]
	 * : Writing tone. Default: professional.
	 *
	 * [--language=<language>]
	 * : ISO 639-1 language code. Default: en.
	 *
	 * [--plan=<plan>]
	 * : Plan tier: starter, pro, enterprise. Default: starter.
	 *
	 * [--title=<title>]
	 * : Site title. Defaults to domain.
	 *
	 * [--admin=<email>]
	 * : Admin email address.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 tenant create --domain=acme.com --industry=technology --plan=pro
	 *   wp pearblog v9 tenant list
	 *
	 * @param  array<int, string>    $args
	 * @param  array<string, string> $assoc_args
	 */
	public function tenant( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'create':
				$this->tenant_create( $assoc_args );
				break;
			case 'list':
				$this->tenant_list();
				break;
			default:
				\WP_CLI::error( "Unknown tenant sub-command '{$sub}'. Try: create, list." );
		}
	}

	private function tenant_create( array $assoc_args ): void {
		$domain = $assoc_args['domain'] ?? '';
		if ( '' === $domain ) {
			\WP_CLI::error( '--domain is required.' );
		}

		$params = [
			'domain'      => $domain,
			'title'       => $assoc_args['title'] ?? '',
			'industry'    => $assoc_args['industry'] ?? 'general',
			'tone'        => $assoc_args['tone'] ?? 'professional',
			'language'    => $assoc_args['language'] ?? 'en',
			'plan'        => $assoc_args['plan'] ?? 'starter',
			'admin_email' => $assoc_args['admin'] ?? '',
		];

		$result = $this->tenant->provision( $params );

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}

		\WP_CLI::success( "Tenant provisioned: {$result['domain']} (site #{$result['site_id']}, plan: {$result['plan']})" );
		\WP_CLI::line( 'Admin URL: ' . $result['admin_url'] );
	}

	private function tenant_list(): void {
		$tenants = $this->tenant->list_tenants();

		if ( empty( $tenants ) ) {
			\WP_CLI::line( 'No tenants provisioned yet.' );
			return;
		}

		$rows = array_map( static function ( array $t ): array {
			return [
				'Domain'      => $t['domain'],
				'Title'       => $t['title'] ?? '',
				'Plan'        => $t['plan'] ?? '',
				'Industry'    => $t['industry'] ?? '',
				'Language'    => $t['language'] ?? '',
				'Provisioned' => isset( $t['provisioned'] ) ? gmdate( 'Y-m-d H:i', $t['provisioned'] ) : '',
			];
		}, $tenants );

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'Domain', 'Title', 'Plan', 'Industry', 'Language', 'Provisioned' ] );
	}

	// -----------------------------------------------------------------------
	// Security audit sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Run the OWASP Top 10 security audit.
	 *
	 * ## OPTIONS
	 *
	 * [--export=<file>]
	 * : Write full JSON report to this file path.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 audit run
	 *   wp pearblog v9 audit run --export=/tmp/audit.json
	 *
	 * @param  array<int, string>    $args
	 * @param  array<string, string> $assoc_args
	 */
	public function audit( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		if ( 'run' !== $sub ) {
			\WP_CLI::error( "Unknown audit sub-command '{$sub}'. Try: run." );
		}

		\WP_CLI::line( 'Running OWASP Top 10 2021 security audit…' );
		$results = $this->auditor->run_full_audit();
		$summary = $results['summary'] ?? [];

		\WP_CLI::line( sprintf(
			'Risk score: %d/100 | Checks: %d | Pass: %d | Warn: %d | Fail: %d',
			$results['risk_score'] ?? 0,
			$summary['total'] ?? 0,
			$summary['passed'] ?? 0,
			$summary['warnings'] ?? 0,
			$summary['failures'] ?? 0,
		) );

		$export = $assoc_args['export'] ?? '';
		if ( '' !== $export ) {
			file_put_contents( $export, $this->auditor->export_json() );
			\WP_CLI::success( "Full report exported to {$export}" );
		}

		$score = $results['risk_score'] ?? 0;
		if ( $score > 50 ) {
			\WP_CLI::warning( "Risk score {$score}/100 — review and address failures before production." );
		} else {
			\WP_CLI::success( "Risk score {$score}/100 — site is in good shape." );
		}
	}

	// -----------------------------------------------------------------------
	// PII scanner sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Scan a post for PII (Personally Identifiable Information).
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : ID of the post to scan.
	 *
	 * [--redact]
	 * : Output redacted content instead of listing PII findings.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 pii scan 42
	 *   wp pearblog v9 pii scan 42 --redact
	 *
	 * @param  array<int, string>    $args
	 * @param  array<string, string> $assoc_args
	 */
	public function pii( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		if ( 'scan' !== $sub ) {
			\WP_CLI::error( "Unknown pii sub-command '{$sub}'. Try: scan." );
		}

		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 pii scan <post_id> [--redact]' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			\WP_CLI::error( "Post #{$post_id} not found." );
		}

		$content  = $post->post_content ?? '';
		$findings = $this->pii->scan_and_persist( $post_id, $content );

		if ( empty( $findings['matches'] ) ) {
			\WP_CLI::success( "No PII found in post #{$post_id}." );
			return;
		}

		\WP_CLI::warning( sprintf( 'Found %d PII match(es) in post #%d:', count( $findings['matches'] ), $post_id ) );
		foreach ( $findings['matches'] as $match ) {
			\WP_CLI::line( sprintf( '  [%s] %s', $match['type'], $match['value'] ) );
		}

		if ( isset( $assoc_args['redact'] ) ) {
			$redacted = $this->pii->redact( $content );
			\WP_CLI::line( "\n--- Redacted content ---\n" . mb_strimwidth( $redacted, 0, 500, '…' ) );
		}
	}

	// -----------------------------------------------------------------------
	// ROI sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Content ROI reporting.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   article <post_id>   — Show ROI metrics for a single article.
	 *   snapshot [--refresh] — Show (or refresh) the site-wide ROI snapshot.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 roi article 42
	 *   wp pearblog v9 roi snapshot
	 *   wp pearblog v9 roi snapshot --refresh
	 *
	 * @param  array<int, string>    $args
	 * @param  array<string, string> $assoc_args
	 */
	public function roi( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'article':
				$this->roi_article( $args );
				break;
			case 'snapshot':
				$this->roi_snapshot( $assoc_args );
				break;
			default:
				\WP_CLI::error( "Unknown roi sub-command '{$sub}'. Try: article, snapshot." );
		}
	}

	private function roi_article( array $args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 roi article <post_id>' );
		}

		$roi = $this->roi->compute_article_roi( $post_id );

		\WP_CLI::line( sprintf( 'Post #%d: %s', $post_id, $roi['title'] ?? 'Untitled' ) );
		\WP_CLI::line( sprintf( '  Sessions (30d) : %d', $roi['sessions_30d'] ?? 0 ) );
		\WP_CLI::line( sprintf( '  Cost           : $%.4f (%.0f¢)', $roi['cost_usd'] ?? 0, $roi['cost_cents'] ?? 0 ) );
		\WP_CLI::line( sprintf( '  Revenue        : $%.4f (%.0f¢)', $roi['revenue_usd'] ?? 0, $roi['revenue_cents'] ?? 0 ) );
		\WP_CLI::line( sprintf( '  ROI            : %.0f¢ (%s)', $roi['roi_cents'] ?? 0, ( $roi['is_profitable'] ?? false ) ? '✓ profitable' : '✗ unprofitable' ) );
		\WP_CLI::line( sprintf( '  ROI %%          : %.1f%%', $roi['roi_pct'] ?? 0 ) );
		\WP_CLI::line( sprintf( '  RPM            : %.0f¢', $roi['rpm_cents'] ?? 0 ) );
		\WP_CLI::line( sprintf( '  Break-even     : %d sessions', $roi['break_even_sessions'] ?? 0 ) );
	}

	private function roi_snapshot( array $assoc_args ): void {
		if ( isset( $assoc_args['refresh'] ) ) {
			\WP_CLI::line( 'Refreshing ROI snapshot…' );
			$this->roi->refresh();
			\WP_CLI::success( 'Snapshot refreshed.' );
		}

		$snap = $this->roi->get_snapshot();

		if ( empty( $snap ) ) {
			\WP_CLI::line( 'No snapshot available. Run: wp pearblog v9 roi snapshot --refresh' );
			return;
		}

		\WP_CLI::line( sprintf( 'Generated : %s', $snap['generated_at'] ?? 'unknown' ) );
		\WP_CLI::line( sprintf( 'Articles  : %d', $snap['total_articles'] ?? 0 ) );
		\WP_CLI::line( sprintf( 'Total cost: $%.2f', ( $snap['total_cost_cents'] ?? 0 ) / 100 ) );
		\WP_CLI::line( sprintf( 'Total rev : $%.2f', ( $snap['total_revenue_cents'] ?? 0 ) / 100 ) );
		\WP_CLI::line( sprintf( 'Net ROI   : $%.2f', ( $snap['total_roi_cents'] ?? 0 ) / 100 ) );
		\WP_CLI::line( sprintf( 'Profitable: %d / %d articles', $snap['profitable_count'] ?? 0, $snap['total_articles'] ?? 0 ) );
	}

	// -----------------------------------------------------------------------
	// Moderation sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Content moderation commands.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   status              — Show whether content moderation is enabled.
	 *   check <post_id>     — Run moderation check on a post's content.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 moderation status
	 *   wp pearblog v9 moderation check 42
	 *
	 * @param  array<int, string>    $args
	 * @param  array<string, string> $assoc_args
	 */
	public function moderation( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'status':
				$this->moderation_status();
				break;
			case 'check':
				$this->moderation_check( $args );
				break;
			default:
				\WP_CLI::error( "Unknown moderation sub-command '{$sub}'. Try: status, check." );
		}
	}

	private function moderation_status(): void {
		$enabled = $this->moderator->is_enabled();
		\WP_CLI::line( 'Content Moderation: ' . ( $enabled ? '✓ enabled' : '✗ disabled' ) );

		if ( ! $enabled ) {
			$has_key = '' !== (string) get_option( 'pearblog_openai_api_key', '' );
			$has_opt = (bool) get_option( ContentModerator::OPTION_ENABLED, false );
			\WP_CLI::line( sprintf( '  Option enabled : %s', $has_opt ? 'yes' : 'no' ) );
			\WP_CLI::line( sprintf( '  API key set    : %s', $has_key ? 'yes' : 'no' ) );
		} else {
			$action = (string) get_option( ContentModerator::OPTION_ACTION, 'block' );
			\WP_CLI::line( sprintf( '  Action on flag: %s', $action ) );
		}
	}

	private function moderation_check( array $args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 moderation check <post_id>' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			\WP_CLI::error( "Post #{$post_id} not found." );
		}

		\WP_CLI::line( "Running moderation check on post #{$post_id}…" );
		$result = $this->moderator->check( $post_id, $post->post_content ?? '' );

		\WP_CLI::line( sprintf( 'Flagged : %s', $result['flagged'] ? 'YES' : 'no' ) );
		\WP_CLI::line( sprintf( 'Action  : %s', $result['action'] ) );

		if ( ! empty( $result['categories'] ) ) {
			$flagged_cats = array_keys( array_filter( $result['categories'] ) );
			\WP_CLI::line( 'Categories flagged: ' . ( $flagged_cats ? implode( ', ', $flagged_cats ) : 'none' ) );
		}

		if ( $result['flagged'] ) {
			\WP_CLI::warning( 'Content was flagged by moderation.' );
		} else {
			\WP_CLI::success( 'Content passed moderation.' );
		}
	}

	// -----------------------------------------------------------------------
	// RBAC sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Role-Based Access Control commands.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   list           — List all PearBlog capabilities per role.
	 *   capabilities   — Show all registered PearBlog capabilities.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 rbac list
	 *   wp pearblog v9 rbac capabilities
	 *
	 * @param  array<int, string>    $args
	 * @param  array<string, string> $assoc_args
	 */
	public function rbac( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'list':
				$this->rbac_list();
				break;
			case 'capabilities':
				$this->rbac_capabilities();
				break;
			default:
				\WP_CLI::error( "Unknown rbac sub-command '{$sub}'. Try: list, capabilities." );
		}
	}

	private function rbac_list(): void {
		$overrides = (array) get_option( RBACManager::OPTION_OVERRIDES, [] );
		$roles     = [ 'administrator', 'editor', 'author', 'contributor' ];

		\WP_CLI::line( 'PearBlog RBAC — capability assignments:' );
		\WP_CLI::line( str_repeat( '-', 60 ) );

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}

			\WP_CLI::line( sprintf( "\n%s:", ucfirst( $role_name ) ) );
			foreach ( RBACManager::CAPABILITIES as $cap ) {
				$granted = (bool) ( $role->capabilities[ $cap ] ?? false );
				\WP_CLI::line( sprintf( '  %-40s %s', $cap, $granted ? '✓' : '✗' ) );
			}
		}
	}

	private function rbac_capabilities(): void {
		\WP_CLI::line( 'All PearBlog custom capabilities:' );
		foreach ( RBACManager::CAPABILITIES as $cap ) {
			\WP_CLI::line( '  ' . $cap );
		}
		\WP_CLI::line( sprintf( "\nTotal: %d capabilities", count( RBACManager::CAPABILITIES ) ) );
	}

	// -----------------------------------------------------------------------
	// Compliance sub-commands
	// -----------------------------------------------------------------------

	/**
	 * Compliance reporting commands.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   report [--days=<days>] [--format=<format>]
	 *     — Generate a GDPR/SOC2 compliance report.
	 *       format: json (default) | csv
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 compliance report
	 *   wp pearblog v9 compliance report --days=90 --format=csv
	 *
	 * @param  array<int, string>    $args
	 * @param  array<string, string> $assoc_args
	 */
	public function compliance( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'report':
				$this->compliance_report( $assoc_args );
				break;
			default:
				\WP_CLI::error( "Unknown compliance sub-command '{$sub}'. Try: report." );
		}
	}

	private function compliance_report( array $assoc_args ): void {
		$days   = (int) ( $assoc_args['days'] ?? 30 );
		$format = (string) ( $assoc_args['format'] ?? 'json' );

		\WP_CLI::line( sprintf( 'Building compliance report (%d days, %s format)…', $days, $format ) );

		$report = $this->compliance->build_report( $days, $format );

		\WP_CLI::line( sprintf( 'Report ID     : %s', $report['report_id'] ) );
		\WP_CLI::line( sprintf( 'Generated at  : %s', $report['generated_at'] ) );
		\WP_CLI::line( sprintf( 'Period        : %s → %s', $report['period_from'], $report['period_to'] ) );
		\WP_CLI::line( sprintf( 'Total events  : %d', $report['total_events'] ) );

		if ( ! empty( $report['events_by_level'] ) ) {
			\WP_CLI::line( 'Events by level:' );
			foreach ( $report['events_by_level'] as $level => $count ) {
				\WP_CLI::line( sprintf( '  %-10s %d', $level, $count ) );
			}
		}

		if ( 'csv' === $format ) {
			$csv      = $this->compliance->to_csv( $report );
			$filename = $report['report_id'] . '.csv';
			file_put_contents( $filename, $csv );
			\WP_CLI::success( sprintf( 'CSV exported to %s (%d bytes)', $filename, strlen( $csv ) ) );
		} else {
			\WP_CLI::success( sprintf( 'Report generated: %d audit events in the last %d days.', $report['total_events'], $days ) );
		}
	}

	// -----------------------------------------------------------------------
	// AMP sub-commands
	// -----------------------------------------------------------------------

	/**
	 * AMP (Accelerated Mobile Pages) commands.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   status           — Show AMP enabled status and configuration.
	 *   convert <post_id> — Convert a post's content to AMP HTML and preview.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 amp status
	 *   wp pearblog v9 amp convert 42
	 *
	 * @param  array<int, string>    $args
	 * @param  array<string, string> $assoc_args
	 */
	public function amp( array $args, array $assoc_args ): void {
		$sub = array_shift( $args );

		switch ( $sub ) {
			case 'status':
				$this->amp_status();
				break;
			case 'convert':
				$this->amp_convert( $args );
				break;
			default:
				\WP_CLI::error( "Unknown amp sub-command '{$sub}'. Try: status, convert." );
		}
	}

	private function amp_status(): void {
		$enabled      = (bool) get_option( AMPGenerator::OPTION_ENABLED, false );
		$analytics_id = (string) get_option( AMPGenerator::OPTION_ANALYTICS, '' );
		$adsense_id   = (string) get_option( AMPGenerator::OPTION_ADSENSE, '' );

		\WP_CLI::line( 'AMP Status: ' . ( $enabled ? '✓ enabled' : '✗ disabled' ) );
		\WP_CLI::line( sprintf( '  Analytics ID : %s', $analytics_id ?: '(not set)' ) );
		\WP_CLI::line( sprintf( '  AdSense ID   : %s', $adsense_id ?: '(not set)' ) );

		if ( $enabled ) {
			\WP_CLI::line( '  AMP URL pattern: ?amp=1 appended to post permalink' );
		}
	}

	private function amp_convert( array $args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 amp convert <post_id>' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			\WP_CLI::error( "Post #{$post_id} not found." );
		}

		\WP_CLI::line( sprintf( 'Converting post #%d to AMP content…', $post_id ) );
		$amp_content = $this->amp->convert_to_amp_content( $post->post_content ?? '' );

		\WP_CLI::line( sprintf( 'Original length : %d bytes', strlen( $post->post_content ?? '' ) ) );
		\WP_CLI::line( sprintf( 'AMP length      : %d bytes', strlen( $amp_content ) ) );
		\WP_CLI::line( "\n--- AMP content preview (first 800 chars) ---" );
		\WP_CLI::line( mb_strimwidth( $amp_content, 0, 800, '…' ) );
		\WP_CLI::success( 'AMP conversion complete.' );
	}
}
