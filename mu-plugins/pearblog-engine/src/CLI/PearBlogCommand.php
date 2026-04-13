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
 *   wp pearblog abtest create --topic=<topic> --modifier-a=<mod> --modifier-b=<mod>
 *   wp pearblog abtest list
 *   wp pearblog abtest status <test_id>
 *   wp pearblog abtest promote <test_id>
 *   wp pearblog abtest delete <test_id>
 *   wp pearblog scaffold prompt-builder <ClassName> --industry=<industry>
 *   wp pearblog scaffold provider <ClassName>
 *   wp pearblog audit list [--limit=<n>] [--level=<level>] [--event=<event>]
 *   wp pearblog audit clear
 *   wp pearblog audit stats
 *
 * @package PearBlogEngine\CLI
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\CLI\AutopilotRunner;
use PearBlogEngine\Content\ContentRefreshEngine;
use PearBlogEngine\Testing\ABTestEngine;
use PearBlogEngine\Content\DuplicateDetector;
use PearBlogEngine\Content\QualityScorer;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Pipeline\ContentPipeline;
use PearBlogEngine\Pipeline\PipelineAuditLog;
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
	 * Manage prompt A/B tests.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   create   Create a new A/B test.
	 *   list     List all tests and their current state.
	 *   status   Show details for a specific test.
	 *   promote  Force-promote the winner of a specific test.
	 *   delete   Delete a test.
	 *
	 * ## OPTIONS (create)
	 *
	 * --topic=<topic>
	 * : The topic to test (must match the queue topic exactly).
	 *
	 * --modifier-a=<modifier>
	 * : Additional prompt instructions for variant A.
	 *
	 * --modifier-b=<modifier>
	 * : Additional prompt instructions for variant B.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog abtest create --topic="Best Hiking Gear" --modifier-a="Focus on beginners." --modifier-b="Focus on experts."
	 *   wp pearblog abtest list
	 *   wp pearblog abtest status ab_a1b2c3d4
	 *   wp pearblog abtest promote ab_a1b2c3d4
	 *   wp pearblog abtest delete ab_a1b2c3d4
	 *
	 * @subcommand abtest
	 */
	public function abtest( array $args, array $assoc_args ): void {
		$sub    = $args[0] ?? 'list';
		$engine = new ABTestEngine();

		switch ( $sub ) {
			case 'create':
				$topic      = $assoc_args['topic']      ?? '';
				$modifier_a = $assoc_args['modifier-a'] ?? '';
				$modifier_b = $assoc_args['modifier-b'] ?? '';

				if ( '' === $topic || '' === $modifier_a || '' === $modifier_b ) {
					\WP_CLI::error( 'Usage: wp pearblog abtest create --topic=<topic> --modifier-a=<mod> --modifier-b=<mod>' );
					return;
				}

				$id = $engine->create_test( $topic, $modifier_a, $modifier_b );
				\WP_CLI::success( "Created A/B test ID: {$id}" );
				\WP_CLI::log( "Topic      : {$topic}" );
				\WP_CLI::log( "Modifier A : {$modifier_a}" );
				\WP_CLI::log( "Modifier B : {$modifier_b}" );
				break;

			case 'list':
				$tests = $engine->list_tests();
				if ( empty( $tests ) ) {
					\WP_CLI::log( 'No A/B tests found.' );
					return;
				}

				foreach ( $tests as $test ) {
					$winner  = $test['winner'] ?? 'pending';
					$runs_a  = $test['variants']['a']['runs'];
					$runs_b  = $test['variants']['b']['runs'];
					$avg_a   = $engine->get_average_score( $test['id'], 'a' );
					$avg_b   = $engine->get_average_score( $test['id'], 'b' );
					\WP_CLI::log( sprintf(
						'[%s] %s | winner: %s | A: %d runs / avg %.1f | B: %d runs / avg %.1f',
						$test['id'],
						$test['topic'],
						$winner,
						$runs_a,
						$avg_a,
						$runs_b,
						$avg_b
					) );
				}
				break;

			case 'status':
				$test_id = $args[1] ?? '';
				if ( '' === $test_id ) {
					\WP_CLI::error( 'Usage: wp pearblog abtest status <test_id>' );
					return;
				}

				$test = $engine->get_test( $test_id );
				if ( ! $test ) {
					\WP_CLI::error( "Test not found: {$test_id}" );
					return;
				}

				\WP_CLI::log( "=== A/B Test: {$test['id']} ===" );
				\WP_CLI::log( "Topic       : {$test['topic']}" );
				\WP_CLI::log( "Created     : " . gmdate( 'Y-m-d H:i:s', $test['created_at'] ) );
				\WP_CLI::log( "Winner      : " . ( $test['winner'] ?? 'pending' ) );
				\WP_CLI::log( "Modifier A  : {$test['modifier_a']}" );
				\WP_CLI::log( "Modifier B  : {$test['modifier_b']}" );
				\WP_CLI::log( "Variant A   : {$test['variants']['a']['runs']} runs, avg score " . number_format( $engine->get_average_score( $test_id, 'a' ), 1 ) );
				\WP_CLI::log( "Variant B   : {$test['variants']['b']['runs']} runs, avg score " . number_format( $engine->get_average_score( $test_id, 'b' ), 1 ) );
				break;

			case 'promote':
				$test_id = $args[1] ?? '';
				if ( '' === $test_id ) {
					\WP_CLI::error( 'Usage: wp pearblog abtest promote <test_id>' );
					return;
				}

				$winner = $engine->promote_winner( $test_id );
				if ( null === $winner ) {
					\WP_CLI::warning( "Not enough data to elect a winner for test {$test_id} (min " . ABTestEngine::MIN_ARTICLES_PER_VARIANT . " articles per variant required)." );
					return;
				}

				\WP_CLI::success( "Winner for test {$test_id}: variant {$winner} (modifier: " . $engine->get_winning_modifier( $test_id ) . ')' );
				break;

			case 'delete':
				$test_id = $args[1] ?? '';
				if ( '' === $test_id ) {
					\WP_CLI::error( 'Usage: wp pearblog abtest delete <test_id>' );
					return;
				}

				if ( $engine->delete_test( $test_id ) ) {
					\WP_CLI::success( "Deleted test {$test_id}." );
				} else {
					\WP_CLI::error( "Test not found: {$test_id}" );
				}
				break;

			default:
				\WP_CLI::error( "Unknown subcommand: {$sub}. Use create, list, status, promote, or delete." );
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

	// -----------------------------------------------------------------------
	// scaffold command
	// -----------------------------------------------------------------------

	/**
	 * Generate boilerplate files for extending PearBlog Engine.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   prompt-builder  Scaffold a new industry-specific PromptBuilder class.
	 *   provider        Scaffold a new AIProvider class.
	 *
	 * ## OPTIONS (prompt-builder)
	 *
	 * <ClassName>
	 * : PHP class name for the new builder (e.g. RealEstatePromptBuilder).
	 *
	 * [--industry=<industry>]
	 * : Human-readable industry description (e.g. "real estate").
	 *   Defaults to the class name without "PromptBuilder" suffix.
	 *
	 * [--dir=<dir>]
	 * : Output directory. Defaults to src/Content/ relative to the current
	 *   working directory.
	 *
	 * ## OPTIONS (provider)
	 *
	 * <ClassName>
	 * : PHP class name for the new provider (e.g. MistralProvider).
	 *
	 * [--dir=<dir>]
	 * : Output directory. Defaults to src/AI/ relative to the current
	 *   working directory.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog scaffold prompt-builder RealEstatePromptBuilder --industry="real estate"
	 *   wp pearblog scaffold provider MistralProvider
	 *
	 * @subcommand scaffold
	 */
	public function scaffold( array $args, array $assoc_args ): void {
		$sub = $args[0] ?? '';

		switch ( $sub ) {
			case 'prompt-builder':
				$class_name = $args[1] ?? '';
				if ( '' === $class_name ) {
					\WP_CLI::error( 'Usage: wp pearblog scaffold prompt-builder <ClassName> [--industry=<industry>] [--dir=<dir>]' );
					return;
				}

				$industry = $assoc_args['industry'] ?? str_replace( 'PromptBuilder', '', $class_name );
				$industry = '' !== $industry ? $industry : $class_name;

				$base_dir = rtrim( $assoc_args['dir'] ?? ( getcwd() . '/src/Content' ), '/' );
				$file     = "{$base_dir}/{$class_name}.php";

				if ( file_exists( $file ) ) {
					\WP_CLI::error( "File already exists: {$file}" );
					return;
				}

				$code = $this->generate_prompt_builder_stub( $class_name, $industry );

				if ( ! is_dir( $base_dir ) ) {
					mkdir( $base_dir, 0755, true );
				}

				file_put_contents( $file, $code );
				\WP_CLI::success( "Created prompt builder: {$file}" );
				\WP_CLI::log( "Next steps:" );
				\WP_CLI::log( "  1. Edit {$file} to customise the prompt structure." );
				\WP_CLI::log( "  2. Register the builder in PromptBuilderFactory::make() or via the" );
				\WP_CLI::log( "     pearblog_prompt_builder_class filter." );
				break;

			case 'provider':
				$class_name = $args[1] ?? '';
				if ( '' === $class_name ) {
					\WP_CLI::error( 'Usage: wp pearblog scaffold provider <ClassName> [--dir=<dir>]' );
					return;
				}

				$base_dir = rtrim( $assoc_args['dir'] ?? ( getcwd() . '/src/AI' ), '/' );
				$file     = "{$base_dir}/{$class_name}.php";

				if ( file_exists( $file ) ) {
					\WP_CLI::error( "File already exists: {$file}" );
					return;
				}

				$code = $this->generate_provider_stub( $class_name );

				if ( ! is_dir( $base_dir ) ) {
					mkdir( $base_dir, 0755, true );
				}

				file_put_contents( $file, $code );
				\WP_CLI::success( "Created AI provider: {$file}" );
				\WP_CLI::log( "Next steps:" );
				\WP_CLI::log( "  1. Edit {$file} to implement the complete() method." );
				\WP_CLI::log( "  2. Register the provider in AIProviderFactory::make()." );
				break;

			default:
				\WP_CLI::error( "Unknown scaffold type: '{$sub}'. Available types: prompt-builder, provider." );
		}
	}

	// -----------------------------------------------------------------------
	// audit command
	// -----------------------------------------------------------------------

	/**
	 * Inspect and manage the pipeline audit log.
	 *
	 * ## SUBCOMMANDS
	 *
	 *   list   List recent audit events.
	 *   clear  Erase all stored events.
	 *   stats  Show summary statistics.
	 *
	 * ## OPTIONS (list)
	 *
	 * [--limit=<n>]
	 * : Number of events to display (default: 20, max: 500).
	 *
	 * [--level=<level>]
	 * : Filter by severity: info, warning, error.
	 *
	 * [--event=<event>]
	 * : Filter by event type slug (e.g. pipeline_completed).
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog audit list
	 *   wp pearblog audit list --limit=50 --level=error
	 *   wp pearblog audit list --event=pipeline_completed
	 *   wp pearblog audit clear
	 *   wp pearblog audit stats
	 *
	 * @subcommand audit
	 */
	public function audit( array $args, array $assoc_args ): void {
		$sub = $args[0] ?? 'list';
		$log = new PipelineAuditLog();

		switch ( $sub ) {
			case 'list':
				$limit      = min( (int) ( $assoc_args['limit'] ?? 20 ), PipelineAuditLog::MAX_ENTRIES );
				$level      = $assoc_args['level'] ?? null;
				$event_type = $assoc_args['event'] ?? null;

				$events = $log->get_events( $limit, $level ?: null, $event_type ?: null );

				if ( empty( $events ) ) {
					\WP_CLI::log( 'No audit events found.' );
					return;
				}

				foreach ( $events as $entry ) {
					$ts      = gmdate( 'Y-m-d H:i:s', $entry['timestamp'] );
					$context = empty( $entry['context'] )
						? ''
						: ' | ' . http_build_query( $entry['context'], '', ', ' );
					\WP_CLI::log( sprintf(
						'[%s] [%s] %s%s',
						$ts,
						strtoupper( $entry['level'] ),
						$entry['event'],
						$context
					) );
				}

				\WP_CLI::log( sprintf( 'Showing %d of %d total events.', count( $events ), $log->count() ) );
				break;

			case 'clear':
				$total = $log->count();
				$log->clear();
				\WP_CLI::success( "Cleared {$total} audit event(s)." );
				break;

			case 'stats':
				$all    = $log->get_all_events();
				$total  = count( $all );
				$counts = array_count_values( array_column( $all, 'level' ) );
				$events = array_count_values( array_column( $all, 'event' ) );

				\WP_CLI::log( "=== Audit Log Statistics ===" );
				\WP_CLI::log( "Total events : {$total}" );
				\WP_CLI::log( "Info         : " . ( $counts[ PipelineAuditLog::LEVEL_INFO ]    ?? 0 ) );
				\WP_CLI::log( "Warning      : " . ( $counts[ PipelineAuditLog::LEVEL_WARNING ] ?? 0 ) );
				\WP_CLI::log( "Error        : " . ( $counts[ PipelineAuditLog::LEVEL_ERROR ]   ?? 0 ) );
				\WP_CLI::log( '' );
				\WP_CLI::log( "Top event types:" );
				arsort( $events );
				foreach ( array_slice( $events, 0, 10, true ) as $type => $count ) {
					\WP_CLI::log( sprintf( '  %-40s %d', $type, $count ) );
				}
				break;

			default:
				\WP_CLI::error( "Unknown subcommand: {$sub}. Use list, clear, or stats." );
		}
	}

	// -----------------------------------------------------------------------
	// Scaffold code-generation helpers
	// -----------------------------------------------------------------------

	/**
	 * Generate the PHP source for a new PromptBuilder subclass.
	 *
	 * @param string $class_name PHP class name.
	 * @param string $industry   Industry label for the prompt.
	 * @return string            PHP source code.
	 */
	private function generate_prompt_builder_stub( string $class_name, string $industry ): string {
		$industry_esc = addslashes( $industry );
		$filter_slug  = strtolower( preg_replace( '/(?<=[a-z])([A-Z])/', '_$1', $class_name ) );

		return <<<PHP
<?php
/**
 * {$class_name} – AI prompt builder for the {$industry_esc} niche.
 *
 * Generated by: wp pearblog scaffold prompt-builder {$class_name}
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Builds AI prompts optimised for {$industry_esc} content.
 */
class {$class_name} extends PromptBuilder {

	public function __construct( SiteProfile \$profile ) {
		parent::__construct( \$profile );
	}

	/**
	 * Build a {$industry_esc}-focused prompt.
	 *
	 * @param string \$topic The article topic / keyword.
	 * @return string        Ready-to-use prompt text.
	 */
	public function build( string \$topic ): string {
		\$topic   = trim( \$topic );
		\$profile = \$this->get_profile();

		\$prompt  = "You are an expert {$industry_esc} writer specialising in {\$profile->industry}.\\n";
		\$prompt .= "Write a comprehensive, SEO-optimised article in {\$profile->language} ";
		\$prompt .= "using a {\$profile->tone} tone.\\n\\n";
		\$prompt .= "Topic: {\$topic}\\n\\n";
		\$prompt .= "Requirements:\\n";
		\$prompt .= "- Minimum 1,200 words\\n";
		\$prompt .= "- Include a compelling H1 title\\n";
		\$prompt .= "- Add a meta description (max 160 chars) prefixed with META:\\n";
		\$prompt .= "- Use H2/H3 subheadings for structure\\n";
		// TODO: add {$industry_esc}-specific prompt requirements here.
		\$prompt .= \$this->monetisation_instructions();

		/**
		 * Filter: pearblog_{$filter_slug}_prompt
		 *
		 * Allows further customisation of this industry-specific prompt.
		 *
		 * @param string      \$prompt  The assembled prompt text.
		 * @param string      \$topic   The article topic.
		 * @param SiteProfile \$profile The active site profile.
		 */
		\$prompt = (string) apply_filters( 'pearblog_{$filter_slug}_prompt', \$prompt, \$topic, \$profile );

		return (string) apply_filters( 'pearblog_prompt', \$prompt, \$topic, \$profile );
	}
}
PHP;
	}

	/**
	 * Generate the PHP source for a new AIProvider class.
	 *
	 * @param string $class_name PHP class name.
	 * @return string            PHP source code.
	 */
	private function generate_provider_stub( string $class_name ): string {
		$provider_slug = strtolower( str_replace( 'Provider', '', $class_name ) );

		return <<<PHP
<?php
/**
 * {$class_name} – AI provider implementation.
 *
 * Generated by: wp pearblog scaffold provider {$class_name}
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * AI text-generation provider adapter for {$provider_slug}.
 *
 * Implement the complete() method to proxy requests to your chosen API.
 * Register this provider in AIProviderFactory::make() using the slug
 * '{$provider_slug}'.
 */
class {$class_name} implements AIProviderInterface {

	// -----------------------------------------------------------------------
	// Metadata (required by AIProviderInterface)
	// -----------------------------------------------------------------------

	/** @inheritdoc */
	public static function get_provider_slug(): string {
		return '{$provider_slug}';
	}

	/** @inheritdoc */
	public static function get_provider_label(): string {
		return '{$class_name}'; // TODO: update to a friendly display name.
	}

	/** @inheritdoc */
	public static function get_models(): array {
		return [
			// TODO: add supported model definitions, e.g.:
			// 'model-slug' => [
			// 	'label'                    => 'Model Label',
			// 	'max_tokens'               => 4096,
			// 	'cost_per_1k_input_cents'  => 0.01,
			// 	'cost_per_1k_output_cents' => 0.03,
			// ],
		];
	}

	/** @inheritdoc */
	public static function get_default_model(): string {
		return ''; // TODO: return the default model slug.
	}

	/** @inheritdoc */
	public static function requires_option(): string {
		return 'pearblog_{$provider_slug}_api_key'; // {$provider_slug} is expanded by the heredoc at generation time.
	}

	// -----------------------------------------------------------------------
	// Instance API
	// -----------------------------------------------------------------------

	/** @inheritdoc */
	public function complete( string \$prompt, int \$max_tokens ): array {
		// TODO: implement HTTP call to the {$provider_slug} API.
		// Must return:  [ 'content' => string, 'prompt_tokens' => int, 'completion_tokens' => int ]
		// Throw RateLimitException on rate-limit responses.
		// Throw \\RuntimeException on other errors.
		throw new \\RuntimeException( '{$class_name}::complete() is not yet implemented.' );
	}
}
PHP;
	}
}
