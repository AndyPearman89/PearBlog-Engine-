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
}
