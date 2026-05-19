<?php
/**
 * V6 WP-CLI Command — `wp pearblog v6`
 *
 * Sub-commands:
 *   stats            – Print V6 platform module status overview
 *   ab-tests list    – List all A/B tests
 *   ab-tests create  – Create a new A/B test
 *   ab-tests promote – Promote all mature A/B tests
 *   compare list     – List all comparisons
 *   compare refresh  – Refresh AI verdict for a comparison by slug
 *   calc list        – List all calculators
 *   calc run         – Run a calculator and print results
 *   analytics sync   – Trigger full GA4 analytics sync
 *   analytics top    – Print top-performing posts
 *   analytics predictive – Print trending / at-risk / refresh candidates
 *   refresh batch    – Trigger a content-refresh batch run
 *
 * @package PearBlogEngine\CLI
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

use PearBlogEngine\Testing\ABTestEngine;
use PearBlogEngine\Analytics\AnalyticsDashboard;
use PearBlogEngine\Content\ContentRefreshEngine;

/**
 * V6 platform management commands.
 *
 * @when after_wp_load
 */
class V6Command {

	// =========================================================================
	// stats
	// =========================================================================

	/**
	 * Print V6 platform module status overview.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format: table | json | yaml | csv. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 stats
	 *   wp pearblog v6 stats --format=json
	 *
	 * @subcommand stats
	 */
	public function stats( array $args, array $assoc_args ): void {
		$format = $assoc_args['format'] ?? 'table';

		$ab_tests   = ( new ABTestEngine() )->list_tests();
		$running    = array_filter( $ab_tests, fn( $t ) => null === $t['winner'] );
		$completed  = array_filter( $ab_tests, fn( $t ) => null !== $t['winner'] );

		$modules = \PearBlogEngine\Core\ModuleRegistry::all();

		$rows = [
			[ 'module' => 'V6 Modules Registered',   'value' => count( $modules ) ],
			[ 'module' => 'A/B Tests (running)',       'value' => count( $running ) ],
			[ 'module' => 'A/B Tests (completed)',     'value' => count( $completed ) ],
			[ 'module' => 'Analytics Last Sync',       'value' => get_option( 'pearblog_analytics_last_sync', 'never' ) ],
			[ 'module' => 'Content Refreshes (total)', 'value' => $this->get_total_refresh_count() ],
			[ 'module' => 'Compare Module',            'value' => isset( $modules['compare_v6'] ) ? '✓ active' : '✗ inactive' ],
			[ 'module' => 'Calculators Module',        'value' => isset( $modules['calculators_v6'] ) ? '✓ active' : '✗ inactive' ],
			[ 'module' => 'AI Decision Module',        'value' => isset( $modules['ai_decision_v6'] ) ? '✓ active' : '✗ inactive' ],
			[ 'module' => 'Rankings Module',           'value' => isset( $modules['rankings_v6'] ) ? '✓ active' : '✗ inactive' ],
		];

		\WP_CLI\Utils\format_items( $format, $rows, [ 'module', 'value' ] );
	}

	// =========================================================================
	// ab-tests
	// =========================================================================

	/**
	 * List all A/B tests.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Filter by status: all | running | completed. Default: all.
	 *
	 * [--format=<format>]
	 * : Output format. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 ab-tests list
	 *   wp pearblog v6 ab-tests list --status=running
	 *
	 * @subcommand ab-tests list
	 */
	public function ab_tests_list( array $args, array $assoc_args ): void {
		$engine  = new ABTestEngine();
		$tests   = $engine->list_tests();
		$status  = $assoc_args['status'] ?? 'all';
		$format  = $assoc_args['format'] ?? 'table';

		if ( 'running' === $status ) {
			$tests = array_filter( $tests, fn( $t ) => null === $t['winner'] );
		} elseif ( 'completed' === $status ) {
			$tests = array_filter( $tests, fn( $t ) => null !== $t['winner'] );
		}

		if ( empty( $tests ) ) {
			\WP_CLI::line( 'No A/B tests found.' );
			return;
		}

		$rows = [];
		foreach ( $tests as $test_id => $test ) {
			$avg_a  = $engine->get_average_score( $test_id, 'a' );
			$avg_b  = $engine->get_average_score( $test_id, 'b' );
			$rows[] = [
				'id'      => $test_id,
				'topic'   => mb_substr( $test['topic'], 0, 40 ),
				'status'  => null !== $test['winner'] ? 'completed' : 'running',
				'winner'  => $test['winner'] ?? '-',
				'runs_a'  => $test['variants']['a']['runs'],
				'runs_b'  => $test['variants']['b']['runs'],
				'avg_a'   => number_format( $avg_a, 1 ),
				'avg_b'   => number_format( $avg_b, 1 ),
			];
		}

		\WP_CLI\Utils\format_items( $format, $rows, [ 'id', 'topic', 'status', 'winner', 'runs_a', 'runs_b', 'avg_a', 'avg_b' ] );
	}

	/**
	 * Create a new A/B test.
	 *
	 * ## OPTIONS
	 *
	 * <topic>
	 * : The topic string to split-test.
	 *
	 * --modifier-a=<modifier>
	 * : Prompt modifier for variant A.
	 *
	 * --modifier-b=<modifier>
	 * : Prompt modifier for variant B.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 ab-tests create "How to fix a leaky tap" --modifier-a="Include step-by-step numbered list" --modifier-b="Write in conversational Q&A format"
	 *
	 * @subcommand ab-tests create
	 */
	public function ab_tests_create( array $args, array $assoc_args ): void {
		if ( empty( $args[0] ) ) {
			\WP_CLI::error( 'Please provide a <topic> argument.' );
		}

		$topic      = $args[0];
		$modifier_a = $assoc_args['modifier-a'] ?? '';
		$modifier_b = $assoc_args['modifier-b'] ?? '';

		if ( '' === $modifier_a || '' === $modifier_b ) {
			\WP_CLI::error( '--modifier-a and --modifier-b are required.' );
		}

		$engine  = new ABTestEngine();
		$test_id = $engine->create_test( $topic, $modifier_a, $modifier_b );

		\WP_CLI::success( "Created A/B test: {$test_id}" );
		\WP_CLI::line( "  Topic:      {$topic}" );
		\WP_CLI::line( "  Modifier A: {$modifier_a}" );
		\WP_CLI::line( "  Modifier B: {$modifier_b}" );
	}

	/**
	 * Promote all mature A/B tests (past 7-day threshold).
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 ab-tests promote
	 *
	 * @subcommand ab-tests promote
	 */
	public function ab_tests_promote( array $args, array $assoc_args ): void {
		$engine  = new ABTestEngine();
		$results = $engine->promote_mature_tests();

		if ( empty( $results ) ) {
			\WP_CLI::line( 'No mature tests found to promote.' );
			return;
		}

		$rows = [];
		foreach ( $results as $test_id => $winner ) {
			$rows[] = [
				'test_id' => $test_id,
				'winner'  => null !== $winner ? $winner : 'insufficient data',
			];
		}

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'test_id', 'winner' ] );
		\WP_CLI::success( sprintf( 'Evaluated %d tests, promoted %d.', count( $results ), count( array_filter( $results ) ) ) );
	}

	// =========================================================================
	// analytics
	// =========================================================================

	/**
	 * Trigger a full GA4 analytics sync for all published posts.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 analytics sync
	 *
	 * @subcommand analytics sync
	 */
	public function analytics_sync( array $args, array $assoc_args ): void {
		\WP_CLI::line( 'Syncing GA4 analytics for all published posts...' );
		$dashboard = new AnalyticsDashboard();
		$updated   = $dashboard->sync_all_posts();
		\WP_CLI::success( "Synced GA4 data for {$updated} post(s)." );
	}

	/**
	 * Print top-performing posts ranked by performance score.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : Number of posts to show. Default: 20.
	 *
	 * [--format=<format>]
	 * : Output format. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 analytics top --limit=10
	 *
	 * @subcommand analytics top
	 */
	public function analytics_top( array $args, array $assoc_args ): void {
		$limit     = (int) ( $assoc_args['limit'] ?? 20 );
		$format    = $assoc_args['format'] ?? 'table';
		$dashboard = new AnalyticsDashboard();
		$posts     = $dashboard->get_top_performing_posts( $limit );

		if ( empty( $posts ) ) {
			\WP_CLI::line( 'No analytics data found. Run: wp pearblog v6 analytics sync' );
			return;
		}

		$rows = [];
		foreach ( $posts as $p ) {
			$rows[] = [
				'post_id'     => $p['post_id'],
				'title'       => mb_substr( $p['title'], 0, 45 ),
				'views_30d'   => $p['views_30d'],
				'quality'     => number_format( $p['quality_score'], 1 ),
				'performance' => number_format( $p['performance_score'], 1 ),
			];
		}

		\WP_CLI\Utils\format_items( $format, $rows, [ 'post_id', 'title', 'views_30d', 'quality', 'performance' ] );
	}

	/**
	 * Print predictive insights: trending, at-risk, and refresh candidates.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 analytics predictive
	 *
	 * @subcommand analytics predictive
	 */
	public function analytics_predictive( array $args, array $assoc_args ): void {
		$format    = $assoc_args['format'] ?? 'table';
		$dashboard = new AnalyticsDashboard();
		$posts     = $dashboard->get_top_performing_posts( 200 );

		$trending       = [];
		$at_risk        = [];
		$refresh_needed = [];

		foreach ( $posts as $p ) {
			$views_30d    = $p['views_30d'];
			$views_7d     = (int) get_post_meta( $p['post_id'], AnalyticsDashboard::META_VIEWS_7D, true );
			$trend        = (string) get_post_meta( $p['post_id'], '_pearblog_traffic_trend', true );
			$refreshed_at = (string) get_post_meta( $p['post_id'], '_pearblog_refreshed_at', true );
			$refresh_count= (int) get_post_meta( $p['post_id'], '_pearblog_refresh_count', true );

			$daily_30d = $views_30d / 30;
			$daily_7d  = $views_7d  / 7;
			$momentum  = $daily_30d > 0 ? round( $daily_7d / $daily_30d, 2 ) : 0.0;

			$days_since = PHP_INT_MAX;
			if ( '' !== $refreshed_at ) {
				$days_since = (int) floor( ( time() - strtotime( $refreshed_at ) ) / DAY_IN_SECONDS );
			}

			$row = [
				'post_id'    => $p['post_id'],
				'title'      => mb_substr( $p['title'], 0, 40 ),
				'views_30d'  => $views_30d,
				'momentum'   => number_format( $momentum, 2 ),
				'trend'      => $trend ?: 'unknown',
			];

			if ( $momentum >= 1.5 ) {
				$trending[] = $row;
			}

			if ( ( 'declining' === $trend || $momentum < 0.5 ) && $views_30d > 50 ) {
				$at_risk[] = $row;
			}

			if ( $days_since > 90 && $p['quality_score'] < 80 ) {
				$row['days_since_refresh'] = $days_since === PHP_INT_MAX ? 'never' : $days_since;
				$row['refresh_count']      = $refresh_count;
				$refresh_needed[] = $row;
			}
		}

		\WP_CLI::line( "\n=== Trending Posts (" . count( $trending ) . ") ===" );
		if ( ! empty( $trending ) ) {
			\WP_CLI\Utils\format_items( $format, array_slice( $trending, 0, 10 ), [ 'post_id', 'title', 'views_30d', 'momentum' ] );
		}

		\WP_CLI::line( "\n=== At-Risk Posts (" . count( $at_risk ) . ") ===" );
		if ( ! empty( $at_risk ) ) {
			\WP_CLI\Utils\format_items( $format, array_slice( $at_risk, 0, 10 ), [ 'post_id', 'title', 'views_30d', 'momentum', 'trend' ] );
		}

		\WP_CLI::line( "\n=== Refresh Candidates (" . count( $refresh_needed ) . ") ===" );
		if ( ! empty( $refresh_needed ) ) {
			\WP_CLI\Utils\format_items( $format, array_slice( $refresh_needed, 0, 10 ), [ 'post_id', 'title', 'days_since_refresh', 'refresh_count' ] );
		}
	}

	// =========================================================================
	// compare
	// =========================================================================

	/**
	 * List comparisons stored in the database.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : Number to return. Default: 20.
	 *
	 * [--format=<format>]
	 * : Output format. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 compare list
	 *
	 * @subcommand compare list
	 */
	public function compare_list( array $args, array $assoc_args ): void {
		global $wpdb;

		$limit  = (int) ( $assoc_args['limit'] ?? 20 );
		$format = $assoc_args['format'] ?? 'table';

		$table = $wpdb->prefix . 'pearblog_comparisons';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, slug, title, created_at, updated_at FROM `{$table}` ORDER BY updated_at DESC LIMIT %d", $limit ),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			\WP_CLI::line( 'No comparisons found.' );
			return;
		}

		\WP_CLI\Utils\format_items( $format, $rows, [ 'id', 'slug', 'title', 'created_at', 'updated_at' ] );
	}

	// =========================================================================
	// calc
	// =========================================================================

	/**
	 * List calculators stored in the database.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : Number to return. Default: 20.
	 *
	 * [--format=<format>]
	 * : Output format. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 calc list
	 *
	 * @subcommand calc list
	 */
	public function calc_list( array $args, array $assoc_args ): void {
		global $wpdb;

		$limit  = (int) ( $assoc_args['limit'] ?? 20 );
		$format = $assoc_args['format'] ?? 'table';

		$table = $wpdb->prefix . 'pearblog_calculators';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, slug, title, category, created_at FROM `{$table}` ORDER BY created_at DESC LIMIT %d", $limit ),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			\WP_CLI::line( 'No calculators found.' );
			return;
		}

		\WP_CLI\Utils\format_items( $format, $rows, [ 'id', 'slug', 'title', 'category', 'created_at' ] );
	}

	// =========================================================================
	// refresh
	// =========================================================================

	/**
	 * Trigger a content-refresh batch run.
	 *
	 * ## OPTIONS
	 *
	 * [--stale-days=<n>]
	 * : Minimum post age (days) to qualify for refresh. Default: 90.
	 *
	 * [--batch=<n>]
	 * : Maximum posts to refresh per run. Default: 3.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v6 refresh batch
	 *   wp pearblog v6 refresh batch --stale-days=60 --batch=5
	 *
	 * @subcommand refresh batch
	 */
	public function refresh_batch( array $args, array $assoc_args ): void {
		$stale_days = (int) ( $assoc_args['stale-days'] ?? 90 );
		$batch      = (int) ( $assoc_args['batch'] ?? 3 );

		\WP_CLI::line( "Running content refresh (stale_days={$stale_days}, batch={$batch})..." );

		$engine  = new ContentRefreshEngine();
		$results = $engine->run_batch( $stale_days, $batch );

		if ( empty( $results ) ) {
			\WP_CLI::line( 'No stale posts found for refresh.' );
			return;
		}

		$rows = [];
		foreach ( $results as $post_id => $status ) {
			$rows[] = [
				'post_id' => $post_id,
				'title'   => mb_substr( get_the_title( $post_id ), 0, 50 ),
				'status'  => $status,
			];
		}

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'post_id', 'title', 'status' ] );

		$refreshed = count( array_filter( $results, fn( $s ) => 'refreshed' === $s ) );
		\WP_CLI::success( "Refreshed {$refreshed} of " . count( $results ) . ' post(s).' );
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	private function get_total_refresh_count(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$sum = $wpdb->get_var(
			"SELECT SUM(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key = '_pearblog_refresh_count'"
		);
		return (int) $sum;
	}

	// -----------------------------------------------------------------------
	// Smart Provider Router commands  (wp pearblog v6 router *)
	// -----------------------------------------------------------------------

	/**
	 * Show Smart Provider Router status: chain, budget, spend, per-provider stats.
	 *
	 * ## OPTIONS
	 *
	 * [--reset]
	 * : Reset all router stats and daily spend.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v6 router status
	 *     wp pearblog v6 router status --reset
	 *
	 * @subcommand router-status
	 */
	public function router_status( array $args, array $assoc_args ): void {
		$router = new \PearBlogEngine\AI\SmartProviderRouter();

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'reset', false ) ) {
			$router->reset_stats();
			\WP_CLI::success( 'Router stats and daily spend reset.' );
		}

		$status = $router->get_status();

		\WP_CLI::line( '== Smart Provider Router Status ==' );
		\WP_CLI::line( 'Provider chain : ' . implode( ' → ', $status['chain'] ) );
		\WP_CLI::line( sprintf(
			'Budget         : %d ¢ / day  |  Spent today: %d ¢  |  Remaining: %d ¢',
			$status['budget_cents'],
			$status['spend_today_cents'],
			$status['remaining_cents']
		) );
		\WP_CLI::line( '' );

		if ( empty( $status['stats'] ) ) {
			\WP_CLI::line( 'No routing stats recorded yet.' );
			return;
		}

		$rows = [];
		foreach ( $status['stats'] as $slug => $s ) {
			$rows[] = [
				'Provider' => $slug,
				'Requests' => $s['requests'],
				'Tokens'   => $s['tokens'],
				'Cost(¢)'  => $s['cost_cents'],
				'Errors'   => $s['errors'],
			];
		}
		\WP_CLI\Utils\format_items( 'table', $rows, [ 'Provider', 'Requests', 'Tokens', 'Cost(¢)', 'Errors' ] );
	}

	// -----------------------------------------------------------------------
	// Refresh Prioritizer command  (wp pearblog v6 refresh score)
	// -----------------------------------------------------------------------

	/**
	 * Show posts ranked by refresh urgency using ContentRefreshPrioritizer.
	 *
	 * ## OPTIONS
	 *
	 * [--stale-days=<days>]
	 * : Minimum days since last refresh to qualify. Default: 30.
	 *
	 * [--limit=<n>]
	 * : Number of posts to display. Default: 20.
	 *
	 * [--min-score=<score>]
	 * : Minimum priority score (0-100) to include. Default: 10.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog v6 refresh score
	 *     wp pearblog v6 refresh score --stale-days=60 --limit=50
	 *
	 * @subcommand refresh-score
	 */
	public function refresh_score( array $args, array $assoc_args ): void {
		$stale_days = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'stale-days', 30 );
		$limit      = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'limit', 20 );
		$min_score  = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'min-score', 10 );

		$prioritizer = new \PearBlogEngine\Content\ContentRefreshPrioritizer();
		$queue       = $prioritizer->get_priority_queue( $stale_days, $limit, $min_score );

		if ( empty( $queue ) ) {
			\WP_CLI::success( "No posts meet the refresh criteria (stale_days={$stale_days}, min_score={$min_score})." );
			return;
		}

		$rows = [];
		foreach ( $queue as $entry ) {
			$rows[] = [
				'ID'       => $entry['post_id'],
				'Score'    => number_format( $entry['score'], 1 ),
				'Age(d)'   => $entry['age_days'],
				'Quality'  => number_format( $entry['quality'], 1 ),
				'Trend'    => $entry['trend'],
				'Views30d' => $entry['views_30d'],
			];
		}

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'ID', 'Score', 'Age(d)', 'Quality', 'Trend', 'Views30d' ] );
		\WP_CLI::success( sprintf( '%d posts shown (stale_days=%d, min_score=%d).', count( $rows ), $stale_days, $min_score ) );
	}
}
