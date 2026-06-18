<?php
/**
 * V9 CLI Command – WP-CLI interface for all V9.0 modules.
 *
 * Provides `wp pearblog v9 <subcommand>` commands for:
 *   - predictive:refresh         – rebuild traffic forecasts
 *   - predictive:anomalies       – show anomalies
 *   - router:status              – show smart-provider-router stats
 *   - router:reset               – reset circuit breakers
 *   - orphans:scan               – scan for orphan pages
 *   - refresh:prioritize         – run content-refresh prioritizer
 *   - collab:workload            – show reviewer workload
 *   - variant:generate           – generate A/B variants for a post title
 *
 * @package PearBlogEngine\CLI
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

use PearBlogEngine\Analytics\PredictiveAnalytics;
use PearBlogEngine\AI\SmartProviderRouter;
use PearBlogEngine\SEO\OrphanPageDetector;
use PearBlogEngine\Content\ContentRefreshPrioritizer;
use PearBlogEngine\Pipeline\CollaborationManager;
use PearBlogEngine\Testing\AIVariantGenerator;

/**
 * V9.0 feature management commands.
 *
 * @package PearBlogEngine
 * @subpackage CLI
 */
class V9Command {

	// -----------------------------------------------------------------------
	// Predictive Analytics
	// -----------------------------------------------------------------------

	/**
	 * Rebuild the 7-day traffic forecast model.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v9 predictive:refresh
	 *
	 * @subcommand predictive:refresh
	 * @when after_wp_load
	 *
	 * @param array<int,string>  $args
	 * @param array<string,mixed> $assoc_args
	 */
	public function predictive_refresh( array $args, array $assoc_args ): void {
		\WP_CLI::log( '🔮 Rebuilding predictive analytics model…' );
		$pa        = new PredictiveAnalytics();
		$forecasts = $pa->refresh();

		if ( empty( $forecasts ) ) {
			\WP_CLI::warning( 'Not enough historical data yet (need ≥7 days).' );
			return;
		}

		\WP_CLI::success( sprintf( 'Forecast built for %d days.', count( $forecasts ) ) );
		foreach ( $forecasts as $date => $pv ) {
			\WP_CLI::log( "  {$date}: {$pv} pageviews" );
		}
	}

	/**
	 * Show current traffic anomalies.
	 *
	 * ## OPTIONS
	 *
	 * [--threshold=<pct>]
	 * : Deviation percentage to trigger anomaly (default: 20)
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v9 predictive:anomalies --threshold=15
	 *
	 * @subcommand predictive:anomalies
	 * @when after_wp_load
	 *
	 * @param array<int,string>  $args
	 * @param array<string,mixed> $assoc_args
	 */
	public function predictive_anomalies( array $args, array $assoc_args ): void {
		$threshold = (float) ( $assoc_args['threshold'] ?? PredictiveAnalytics::DEFAULT_ANOMALY_PCT );
		$pa        = new PredictiveAnalytics();
		$forecast  = get_option( PredictiveAnalytics::OPTION_FORECASTS, [] );
		$actual    = get_option( 'pearblog_ga4_actual_daily', [] );
		$anomalies = $pa->detect_anomalies( $actual, $forecast, $threshold );

		if ( empty( $anomalies ) ) {
			\WP_CLI::success( 'No anomalies detected.' );
			return;
		}

		\WP_CLI::log( sprintf( '⚠ %d anomal%s detected:', count( $anomalies ), count( $anomalies ) === 1 ? 'y' : 'ies' ) );
		foreach ( $anomalies as $a ) {
			\WP_CLI::log( "  {$a['date']}: actual={$a['actual']} forecast={$a['forecast']} deviation={$a['deviation_pct']}%" );
		}
	}

	// -----------------------------------------------------------------------
	// Smart Provider Router
	// -----------------------------------------------------------------------

	/**
	 * Show AI provider routing statistics.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v9 router:status
	 *
	 * @subcommand router:status
	 * @when after_wp_load
	 *
	 * @param array<int,string>  $args
	 * @param array<string,mixed> $assoc_args
	 */
	public function router_status( array $args, array $assoc_args ): void {
		$router    = new SmartProviderRouter();
		$providers = [ 'openai', 'anthropic', 'gemini' ];
		$available = $router->get_available_providers();

		\WP_CLI::log( '🤖 Smart Provider Router Status' );
		\WP_CLI::log( str_repeat( '-', 60 ) );

		foreach ( $providers as $p ) {
			$stats  = $router->get_provider_stats( $p );
			$status = in_array( $p, $available, true ) ? '✅ available' : '❌ unavailable';
			\WP_CLI::log( sprintf(
				'%s  %s  calls=%d  errors=%d  avg_lat=%dms  quality=%.1f',
				$p,
				$status,
				$stats['calls'] ?? 0,
				$stats['errors'] ?? 0,
				$stats['avg_latency_ms'] ?? 0,
				$stats['avg_quality'] ?? 0.0
			) );
		}
	}

	/**
	 * Reset all provider circuit-breakers.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v9 router:reset
	 *
	 * @subcommand router:reset
	 * @when after_wp_load
	 *
	 * @param array<int,string>  $args
	 * @param array<string,mixed> $assoc_args
	 */
	public function router_reset( array $args, array $assoc_args ): void {
		delete_option( SmartProviderRouter::OPTION_CIRCUIT_STATE );
		delete_option( SmartProviderRouter::OPTION_STATS );
		\WP_CLI::success( 'Circuit breakers and stats reset.' );
	}

	// -----------------------------------------------------------------------
	// Orphan Page Detector
	// -----------------------------------------------------------------------

	/**
	 * Scan for orphan pages (no inbound internal links).
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v9 orphans:scan
	 *
	 * @subcommand orphans:scan
	 * @when after_wp_load
	 *
	 * @param array<int,string>  $args
	 * @param array<string,mixed> $assoc_args
	 */
	public function orphans_scan( array $args, array $assoc_args ): void {
		\WP_CLI::log( '🔍 Scanning for orphan pages…' );
		$detector = new OrphanPageDetector();
		$orphans  = $detector->scan();

		if ( empty( $orphans ) ) {
			\WP_CLI::success( 'No orphan pages found.' );
			return;
		}

		\WP_CLI::warning( sprintf( '%d orphan page%s found:', count( $orphans ), count( $orphans ) === 1 ? '' : 's' ) );
		foreach ( $orphans as $o ) {
			\WP_CLI::log( "  [{$o['age_days']}d] {$o['permalink']}" );
		}
	}

	// -----------------------------------------------------------------------
	// Content Refresh Prioritizer
	// -----------------------------------------------------------------------

	/**
	 * Compute and display the content refresh priority queue.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : Show top N results (default: 20)
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v9 refresh:prioritize --limit=10
	 *
	 * @subcommand refresh:prioritize
	 * @when after_wp_load
	 *
	 * @param array<int,string>  $args
	 * @param array<string,mixed> $assoc_args
	 */
	public function refresh_prioritize( array $args, array $assoc_args ): void {
		\WP_CLI::log( '📅 Running content refresh prioritizer…' );
		$limit     = (int) ( $assoc_args['limit'] ?? 20 );
		$prioritizer = new ContentRefreshPrioritizer();
		$queue       = $prioritizer->run();
		$queue       = array_slice( $queue, 0, $limit );

		if ( empty( $queue ) ) {
			\WP_CLI::success( 'No posts need refreshing.' );
			return;
		}

		\WP_CLI::log( sprintf( 'Top %d posts to refresh:', count( $queue ) ) );
		foreach ( $queue as $row ) {
			$reasons = implode( ', ', $row['reasons'] );
			\WP_CLI::log( "  [score={$row['score']}] post #{$row['post_id']}: {$reasons}" );
		}
	}

	// -----------------------------------------------------------------------
	// Collaboration Manager
	// -----------------------------------------------------------------------

	/**
	 * Show reviewer workload.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v9 collab:workload
	 *
	 * @subcommand collab:workload
	 * @when after_wp_load
	 *
	 * @param array<int,string>  $args
	 * @param array<string,mixed> $assoc_args
	 */
	public function collab_workload( array $args, array $assoc_args ): void {
		$collab   = new CollaborationManager();
		$workload = $collab->get_workload();

		if ( empty( $workload ) ) {
			\WP_CLI::success( 'No posts currently in review.' );
			return;
		}

		\WP_CLI::log( '👥 Reviewer Workload' );
		foreach ( $workload as $row ) {
			$user = get_userdata( $row['reviewer_id'] );
			$name = $user ? $user->user_login : "user#{$row['reviewer_id']}";
			\WP_CLI::log( "  {$name}: {$row['post_count']} post(s) in review" );
		}
	}

	// -----------------------------------------------------------------------
	// AI Variant Generator
	// -----------------------------------------------------------------------

	/**
	 * Generate A/B test title variants for a post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : Post ID to generate variants for.
	 *
	 * [--count=<n>]
	 * : Number of variants to generate (default: 3)
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v9 variant:generate 42 --count=5
	 *
	 * @subcommand variant:generate
	 * @when after_wp_load
	 *
	 * @param array<int,string>  $args
	 * @param array<string,mixed> $assoc_args
	 */
	public function variant_generate( array $args, array $assoc_args ): void {
		if ( empty( $args[0] ) ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 variant:generate <post_id>' );
		}

		$post_id = (int) $args[0];
		$post    = get_post( $post_id );

		if ( ! $post ) {
			\WP_CLI::error( "Post #{$post_id} not found." );
		}

		$count     = (int) ( $assoc_args['count'] ?? 3 );
		$gen       = new AIVariantGenerator();
		$variants  = $gen->generate( 'post_title', $post->post_title, $count );

		if ( empty( $variants ) ) {
			\WP_CLI::warning( 'No variants generated (check AI API key configuration).' );
			return;
		}

		\WP_CLI::log( sprintf( 'Generated %d variants for: "%s"', count( $variants ), $post->post_title ) );
		foreach ( $variants as $i => $v ) {
			\WP_CLI::log( sprintf( '  %d. %s', $i + 1, $v ) );
		}
	}
}
