<?php
/**
 * WP-CLI command group: `wp pearblog`
 *
 * Commands:
 *   wp pearblog generate [--topic=<topic>] [--publish]
 *   wp pearblog queue list
 *   wp pearblog queue add <topic>
 *   wp pearblog queue clear
 *   wp pearblog stats [--days=<days>]
 *   wp pearblog refresh [--older-than=<days>] [--batch=<n>]
 *   wp pearblog quality score <post_id>
 *   wp pearblog duplicate check <post_id>
 *   wp pearblog links backfill [--batch=<n>]
 *   wp pearblog circuit reset
 *   wp pearblog autopilot start [--mode=<mode>] [--tasks=<tasks>]
 *   wp pearblog autopilot status
 *   wp pearblog autopilot pause
 *   wp pearblog autopilot resume
 *   wp pearblog autopilot next
 *
 * @package PearBlogEngine\CLI
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\CLI\AutopilotRunner;
use PearBlogEngine\Content\ContentRefreshEngine;
use PearBlogEngine\Content\DuplicateDetector;
use PearBlogEngine\Content\QualityScorer;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Pipeline\ContentPipeline;
use PearBlogEngine\SEO\InternalLinker;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Manage the PearBlog Engine content pipeline.
 *
 * @when after_wp_load
 */
class PearBlogCommand {

	/**
	 * Generate and optionally publish one article.
	 *
	 * ## OPTIONS
	 *
	 * [--topic=<topic>]
	 * : Article topic to generate. If omitted, the next topic from the queue is used.
	 *
	 * [--publish]
	 * : Publish immediately (default: draft).
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog generate --topic="Best restaurants in Warsaw" --publish
	 *
	 * @when after_wp_load
	 */
	public function generate( array $args, array $assoc_args ): void {
		$topic   = $assoc_args['topic'] ?? null;
		$publish = isset( $assoc_args['publish'] );

		$context = TenantContext::for_site( get_current_blog_id() );

		if ( null !== $topic ) {
			$queue = new TopicQueue( $context->site_id );
			$queue->push( $topic );
			\WP_CLI::log( "Queued topic: {$topic}" );
		}

		\WP_CLI::log( 'Running content pipeline…' );

		$pipeline = new ContentPipeline( $context );

		try {
			$result = $pipeline->run();
		} catch ( \Throwable $e ) {
			\WP_CLI::error( 'Pipeline failed: ' . $e->getMessage() );
			return;
		}

		if ( null === $result ) {
			\WP_CLI::warning( 'Queue is empty – nothing generated.' );
			return;
		}

		\WP_CLI::success( "Generated post ID {$result['post_id']}: {$result['topic']}" );
		\WP_CLI::log( 'URL: ' . get_permalink( $result['post_id'] ) );
	}

	/**
	 * Manage the topic queue.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   list   List all queued topics.
	 *   add    Add a topic to the queue.
	 *   clear  Empty the entire queue.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog queue list
	 *   wp pearblog queue add "10 tips for hiking in Tatry"
	 *   wp pearblog queue clear
	 *
	 * @subcommand queue
	 */
	public function queue( array $args, array $assoc_args ): void {
		$sub   = $args[0] ?? 'list';
		$queue = new TopicQueue( get_current_blog_id() );

		switch ( $sub ) {
			case 'list':
				$topics = $queue->all();
				if ( empty( $topics ) ) {
					\WP_CLI::log( 'Queue is empty.' );
					return;
				}
				foreach ( $topics as $i => $topic ) {
					\WP_CLI::log( ( $i + 1 ) . ". {$topic}" );
				}
				\WP_CLI::log( count( $topics ) . ' topic(s) in queue.' );
				break;

			case 'add':
				$topic = $args[1] ?? '';
				if ( '' === $topic ) {
					\WP_CLI::error( 'Usage: wp pearblog queue add <topic>' );
					return;
				}
				$queue->push( $topic );
				\WP_CLI::success( "Added: {$topic}" );
				break;

			case 'clear':
				$queue->clear();
				\WP_CLI::success( 'Queue cleared.' );
				break;

			default:
				\WP_CLI::error( "Unknown subcommand: {$sub}. Use list, add, or clear." );
		}
	}

	/**
	 * Display publishing statistics.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Number of days to look back (default: 30).
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog stats --days=7
	 *
	 */
	public function stats( array $args, array $assoc_args ): void {
		$days   = (int) ( $assoc_args['days'] ?? 30 );
		$cutoff = gmdate( 'Y-m-d', time() - ( $days * DAY_IN_SECONDS ) );

		$posts = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => [ [ 'after' => $cutoff, 'inclusive' => true ] ],
		] );

		$count    = count( $posts );
		$cost     = AIClient::get_total_cost_cents();
		$last_run = get_option( 'pearblog_last_pipeline_run', 'never' );

		\WP_CLI::log( "=== PearBlog Stats (last {$days} days) ===" );
		\WP_CLI::log( "Articles published : {$count}" );
		\WP_CLI::log( 'Total AI cost      : $' . number_format( $cost / 100, 4 ) );
		\WP_CLI::log( "Last pipeline run  : {$last_run}" );
		\WP_CLI::log( 'Circuit breaker    : ' . ( AIClient::is_circuit_open() ? 'OPEN (blocked)' : 'closed (ok)' ) );
		\WP_CLI::log( 'Queue size         : ' . ( new TopicQueue( get_current_blog_id() ) )->count() );
	}

	/**
	 * Refresh stale published posts.
	 *
	 * ## OPTIONS
	 *
	 * [--older-than=<days>]
	 * : Refresh posts older than this many days (default: 90).
	 *
	 * [--batch=<n>]
	 * : Number of posts to refresh per run (default: 3).
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog refresh --older-than=60 --batch=5
	 *
	 */
	public function refresh( array $args, array $assoc_args ): void {
		$older_than = (int) ( $assoc_args['older-than'] ?? ContentRefreshEngine::DEFAULT_STALE_DAYS );
		$batch      = (int) ( $assoc_args['batch']      ?? ContentRefreshEngine::DEFAULT_BATCH_SIZE );

		\WP_CLI::log( "Refreshing up to {$batch} posts older than {$older_than} days…" );

		$engine  = new ContentRefreshEngine();
		$results = $engine->run_batch( $older_than, $batch );

		if ( empty( $results ) ) {
			\WP_CLI::log( 'No stale posts found.' );
			return;
		}

		foreach ( $results as $post_id => $status ) {
			\WP_CLI::log( "Post {$post_id}: {$status}" );
		}

		\WP_CLI::success( count( $results ) . ' post(s) processed.' );
	}

	/**
	 * Quality score a post.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog quality score 42
	 *
	 * @subcommand quality
	 */
	public function quality( array $args, array $assoc_args ): void {
		$sub     = $args[0] ?? 'score';
		$post_id = (int) ( $args[1] ?? 0 );

		if ( 'score' === $sub && $post_id > 0 ) {
			$scorer = new QualityScorer();
			$scores = $scorer->score( $post_id );

			\WP_CLI::log( "=== Quality Score for Post {$post_id} ===" );
			\WP_CLI::log( "Composite     : {$scores['composite']} / 100" );
			\WP_CLI::log( "Readability   : {$scores['readability']} / 100" );
			\WP_CLI::log( "Heading score : {$scores['heading_score']} / 100" );
			\WP_CLI::log( "Keyword density: {$scores['keyword_density']}%" );
			\WP_CLI::log( "Word count    : {$scores['word_count']}" );
			return;
		}

		\WP_CLI::error( 'Usage: wp pearblog quality score <post_id>' );
	}

	/**
	 * Check a post for duplicate content.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog duplicate check 42
	 *
	 * @subcommand duplicate
	 */
	public function duplicate( array $args, array $assoc_args ): void {
		$sub     = $args[0] ?? 'check';
		$post_id = (int) ( $args[1] ?? 0 );

		if ( 'check' === $sub && $post_id > 0 ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				\WP_CLI::error( "Post {$post_id} not found." );
				return;
			}

			$detector = new DuplicateDetector();
			$result   = $detector->check( $post->post_content, $post_id );

			if ( $result['is_duplicate'] ) {
				\WP_CLI::warning( "DUPLICATE DETECTED (similarity: {$result['similarity']})" );
				\WP_CLI::log( "Matched post ID : {$result['matched_post_id']}" );
				\WP_CLI::log( "Matched title   : {$result['matched_title']}" );
			} else {
				\WP_CLI::success( "No duplicate detected (highest similarity: {$result['similarity']})" );
			}
			return;
		}

		\WP_CLI::error( 'Usage: wp pearblog duplicate check <post_id>' );
	}

	/**
	 * Backfill internal links for all published posts.
	 *
	 * ## OPTIONS
	 *
	 * [--batch=<n>]
	 * : Number of posts per batch (default: 20).
	 *
	 * @subcommand links
	 */
	public function links( array $args, array $assoc_args ): void {
		$sub   = $args[0] ?? 'backfill';
		$batch = (int) ( $assoc_args['batch'] ?? 20 );

		if ( 'backfill' === $sub ) {
			\WP_CLI::log( "Backfilling internal links (batch: {$batch})…" );
			$linker  = new InternalLinker();
			$updated = $linker->backfill( $batch );
			\WP_CLI::success( "{$updated} post(s) updated with internal links." );
			return;
		}

		\WP_CLI::error( 'Usage: wp pearblog links backfill [--batch=<n>]' );
	}

	/**
	 * Manage the AI circuit breaker.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   status  Show current circuit-breaker state.
	 *   reset   Force-close the circuit (re-enable AI calls).
	 *
	 * @subcommand circuit
	 */
	public function circuit( array $args, array $assoc_args ): void {
		$sub = $args[0] ?? 'status';

		switch ( $sub ) {
			case 'status':
				$open = AIClient::is_circuit_open();
				\WP_CLI::log( 'Circuit breaker: ' . ( $open ? 'OPEN (AI calls blocked)' : 'closed (ok)' ) );
				break;

			case 'reset':
				AIClient::reset_circuit();
				\WP_CLI::success( 'Circuit breaker reset – AI calls re-enabled.' );
				break;

			default:
				\WP_CLI::error( "Unknown subcommand: {$sub}. Use status or reset." );
		}
	}

	/**
	 * Manage the enterprise autopilot system.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   start   Start the autopilot task runner.
	 *   status  Display current autopilot status and progress.
	 *   pause   Pause the running autopilot.
	 *   resume  Resume a paused autopilot.
	 *   next    Force-advance to the next task.
	 *
	 * ## OPTIONS
	 *
	 * [--mode=<mode>]
	 * : Autopilot mode. Accepts 'enterprise' or 'standard'. Default: enterprise.
	 *
	 * [--tasks=<tasks>]
	 * : Tasks to execute. 'all' for every task, or comma-separated IDs (e.g. '1.1,1.2'). Default: all.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog autopilot start --mode=enterprise --tasks=all
	 *   wp pearblog autopilot status
	 *   wp pearblog autopilot pause
	 *   wp pearblog autopilot resume
	 *   wp pearblog autopilot next
	 *
	 * @subcommand autopilot
	 */
	public function autopilot( array $args, array $assoc_args ): void {
		$sub = $args[0] ?? 'status';

		switch ( $sub ) {
			case 'start':
				$mode  = $assoc_args['mode']  ?? 'enterprise';
				$tasks = $assoc_args['tasks'] ?? 'all';

				$result = AutopilotRunner::start( $mode, $tasks );

				if ( $result['success'] ) {
					\WP_CLI::success( $result['message'] );
					$this->display_autopilot_status();
				} else {
					\WP_CLI::error( $result['message'] );
				}
				break;

			case 'status':
				$this->display_autopilot_status();
				break;

			case 'pause':
				$result = AutopilotRunner::pause();
				if ( $result['success'] ) {
					\WP_CLI::success( $result['message'] );
				} else {
					\WP_CLI::error( $result['message'] );
				}
				break;

			case 'resume':
				$result = AutopilotRunner::resume();
				if ( $result['success'] ) {
					\WP_CLI::success( $result['message'] );
				} else {
					\WP_CLI::error( $result['message'] );
				}
				break;

			case 'next':
				$result = AutopilotRunner::next();
				if ( $result['success'] ) {
					\WP_CLI::success( $result['message'] );
					$this->display_autopilot_status();
				} else {
					\WP_CLI::error( $result['message'] );
				}
				break;

			default:
				\WP_CLI::error( "Unknown subcommand: {$sub}. Use start, status, pause, resume, or next." );
		}
	}

	/**
	 * Print a formatted autopilot status table to the CLI.
	 */
	private function display_autopilot_status(): void {
		$summary = AutopilotRunner::get_status_summary();

		\WP_CLI::log( '=== PearBlog Autopilot ===' );
		\WP_CLI::log( 'Status        : ' . strtoupper( $summary['status'] ) );
		\WP_CLI::log( 'Mode          : ' . ( $summary['mode'] ?: '—' ) );
		\WP_CLI::log( 'Current task  : ' . ( $summary['current_task'] ? "{$summary['current_task']} – {$summary['current_task_name']}" : '—' ) );
		\WP_CLI::log( sprintf( 'Progress      : %d / %d (%.1f%%)', $summary['completed'] + $summary['failed'], $summary['total'], $summary['progress_pct'] ) );
		\WP_CLI::log( 'Completed     : ' . $summary['completed'] );
		\WP_CLI::log( 'Failed        : ' . $summary['failed'] );
		\WP_CLI::log( 'Remaining     : ' . $summary['remaining'] );
		\WP_CLI::log( 'Started at    : ' . ( $summary['start_time'] ?? '—' ) );
	}
}
