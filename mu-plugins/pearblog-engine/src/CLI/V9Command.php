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
 *
 * @package PearBlogEngine\CLI
 * @since   9.0.0
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

use PearBlogEngine\Analytics\PredictiveAnalytics;
use PearBlogEngine\Content\CollaborationManager;

/**
 * V9 CLI command group.
 *
 * @when after_wp_load
 */
class V9Command {

	private PredictiveAnalytics $analytics;
	private CollaborationManager $collab;

	public function __construct() {
		$this->analytics = new PredictiveAnalytics();
		$this->collab    = new CollaborationManager();
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
}
