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
 *
 * @package PearBlogEngine\CLI
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

use PearBlogEngine\Analytics\PredictiveAnalytics;
use PearBlogEngine\Content\CollaborationManager;
use PearBlogEngine\Testing\AIVariantGenerator;
use PearBlogEngine\Testing\BayesianOptimizer;
use PearBlogEngine\AI\SmartProviderRouter;
use PearBlogEngine\SEO\OrphanPageDetector;

/**
 * V9 CLI command group.
 *
 * @when after_wp_load
 */
class V9Command {

	private PredictiveAnalytics $analytics;
	private CollaborationManager $collab;
	private AIVariantGenerator $variant_gen;
	private BayesianOptimizer $bayesian;
	private SmartProviderRouter $router;
	private OrphanPageDetector $orphan_detector;

	public function __construct() {
		$this->analytics       = new PredictiveAnalytics();
		$this->collab          = new CollaborationManager();
		$this->variant_gen     = new AIVariantGenerator();
		$this->bayesian        = new BayesianOptimizer();
		$this->router          = new SmartProviderRouter();
		$this->orphan_detector = new OrphanPageDetector();
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
}
