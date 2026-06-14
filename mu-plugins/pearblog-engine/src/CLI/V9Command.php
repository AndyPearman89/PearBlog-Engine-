<?php
/**
 * WP-CLI command group: `wp pearblog v9`
 *
 * Commands:
 *   wp pearblog v9 variant-generate --topic=<topic> [--type=<type>] [--count=<n>]
 *   wp pearblog v9 variant-ab --topic=<topic> [--type=<type>]
 *   wp pearblog v9 bayesian-status <test_id>
 *   wp pearblog v9 bayesian-simulate <test_id> --conversions-a=<n> --conversions-b=<n> [--trials=<n>]
 *   wp pearblog v9 orphan-detect [--limit=<n>] [--post-type=<type>]
 *   wp pearblog v9 orphan-report [--post-type=<type>]
 *   wp pearblog v9 orphan-suggest <post_id> [--limit=<n>]
 *
 * @package PearBlogEngine\CLI
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

use PearBlogEngine\SEO\OrphanPageDetector;
use PearBlogEngine\Testing\ABTestEngine;
use PearBlogEngine\Testing\AIVariantGenerator;
use PearBlogEngine\Testing\BayesianOptimizer;

/**
 * V9.0 commands: AI variant generation, Bayesian optimisation, orphan detection.
 *
 * @when after_wp_load
 */
class V9Command {

	// -----------------------------------------------------------------------
	// variant-generate
	// -----------------------------------------------------------------------

	/**
	 * Generate AI-powered A/B test variant modifiers for a topic.
	 *
	 * ## OPTIONS
	 *
	 * --topic=<topic>
	 * : The article topic or current headline to generate variants for.
	 *
	 * [--type=<type>]
	 * : Variant type: headline, seo_meta, cta, tone. Default: headline.
	 *
	 * [--count=<n>]
	 * : Number of variants to generate (2–5). Default: 2.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 variant-generate --topic="Best hiking boots 2026" --type=headline --count=3
	 *   wp pearblog v9 variant-generate --topic="Subscribe to our newsletter" --type=cta
	 *
	 * @subcommand variant-generate
	 */
	public function variant_generate( array $args, array $assoc_args ): void {
		$topic = $assoc_args['topic'] ?? '';
		$type  = $assoc_args['type']  ?? 'headline';
		$count = (int) ( $assoc_args['count'] ?? 2 );

		if ( '' === $topic ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 variant-generate --topic=<topic> [--type=<type>] [--count=<n>]' );
			return;
		}

		if ( ! in_array( $type, AIVariantGenerator::VARIANT_TYPES, true ) ) {
			\WP_CLI::error( 'Unknown variant type. Allowed: ' . implode( ', ', AIVariantGenerator::VARIANT_TYPES ) );
			return;
		}

		\WP_CLI::log( "Generating {$count} '{$type}' variants for: {$topic}" );

		$gen      = new AIVariantGenerator();
		$variants = $gen->generate_variants( $topic, $type, $count );

		\WP_CLI::log( '' );
		foreach ( $variants as $key => $modifier ) {
			\WP_CLI::log( strtoupper( $key ) . ': ' . $modifier );
		}

		\WP_CLI::success( count( $variants ) . " variant(s) generated." );
	}

	// -----------------------------------------------------------------------
	// variant-ab
	// -----------------------------------------------------------------------

	/**
	 * Auto-create an A/B test seeded with AI-generated variants.
	 *
	 * ## OPTIONS
	 *
	 * --topic=<topic>
	 * : The article topic to generate variants for and test.
	 *
	 * [--type=<type>]
	 * : Variant type: headline, seo_meta, cta, tone. Default: headline.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 variant-ab --topic="Best laptop 2026" --type=tone
	 *
	 * @subcommand variant-ab
	 */
	public function variant_ab( array $args, array $assoc_args ): void {
		$topic = $assoc_args['topic'] ?? '';
		$type  = $assoc_args['type']  ?? 'headline';

		if ( '' === $topic ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 variant-ab --topic=<topic> [--type=<type>]' );
			return;
		}

		\WP_CLI::log( "Generating AI variants for: {$topic}" );

		$gen      = new AIVariantGenerator();
		$variants = $gen->generate_variants( $topic, $type, 2 );

		$modifier_a = $variants['modifier_a'] ?? '';
		$modifier_b = $variants['modifier_b'] ?? '';

		if ( '' === $modifier_a || '' === $modifier_b ) {
			\WP_CLI::error( 'AI did not return two distinct variants. Try again or adjust the topic.' );
			return;
		}

		$engine = new ABTestEngine();
		$id     = $engine->create_test( $topic, $modifier_a, $modifier_b );

		\WP_CLI::success( "A/B test created: {$id}" );
		\WP_CLI::log( "Type       : {$type}" );
		\WP_CLI::log( "Modifier A : {$modifier_a}" );
		\WP_CLI::log( "Modifier B : {$modifier_b}" );
		\WP_CLI::log( "Tip: use `wp pearblog v9 bayesian-status {$id}` to check convergence." );
	}

	// -----------------------------------------------------------------------
	// bayesian-status
	// -----------------------------------------------------------------------

	/**
	 * Show the Bayesian win probabilities for an existing A/B test.
	 *
	 * Reads variant scores from ABTestEngine and feeds them into a
	 * BayesianOptimizer to display estimated win probabilities and
	 * convergence status.
	 *
	 * ## OPTIONS
	 *
	 * <test_id>
	 * : The A/B test ID (e.g. ab_c3d4e5f6).
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 bayesian-status ab_c3d4e5f6
	 *
	 * @subcommand bayesian-status
	 */
	public function bayesian_status( array $args, array $assoc_args ): void {
		$test_id = $args[0] ?? '';
		if ( '' === $test_id ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 bayesian-status <test_id>' );
			return;
		}

		$engine = new ABTestEngine();
		$test   = $engine->get_test( $test_id );
		if ( ! $test ) {
			\WP_CLI::error( "Test not found: {$test_id}" );
			return;
		}

		// Build the optimizer from accumulated scores.
		$opt = new BayesianOptimizer( [ 'a', 'b' ] );
		$this->feed_optimizer( $opt, $engine, $test_id, 'a' );
		$this->feed_optimizer( $opt, $engine, $test_id, 'b' );

		$probs     = $opt->get_probabilities();
		$leading   = $opt->get_leading_arm();
		$converged = $opt->is_converged();
		$obs       = $opt->get_total_observations();

		\WP_CLI::log( "=== Bayesian Status: {$test_id} ===" );
		\WP_CLI::log( "Topic       : {$test['topic']}" );
		\WP_CLI::log( sprintf( "Variant A   : %.1f%% win prob (%d runs, avg score %.1f)",
			$probs['a'] * 100,
			$test['variants']['a']['runs'],
			$engine->get_average_score( $test_id, 'a' )
		) );
		\WP_CLI::log( sprintf( "Variant B   : %.1f%% win prob (%d runs, avg score %.1f)",
			$probs['b'] * 100,
			$test['variants']['b']['runs'],
			$engine->get_average_score( $test_id, 'b' )
		) );
		\WP_CLI::log( "Leading arm : " . strtoupper( $leading ) );
		\WP_CLI::log( "Observations: {$obs}" );
		\WP_CLI::log( "Converged   : " . ( $converged ? 'YES (≥95% confidence)' : 'NO — need more data' ) );

		if ( $converged ) {
			\WP_CLI::success( "Test has converged. Promote variant " . strtoupper( $leading ) . " with: wp pearblog abtest promote {$test_id}" );
		}
	}

	// -----------------------------------------------------------------------
	// bayesian-simulate
	// -----------------------------------------------------------------------

	/**
	 * Simulate a Bayesian A/B test with synthetic conversion counts.
	 *
	 * ## OPTIONS
	 *
	 * <test_id>
	 * : The A/B test ID (used for labelling only).
	 *
	 * --conversions-a=<n>
	 * : Number of simulated successes for variant A.
	 *
	 * --conversions-b=<n>
	 * : Number of simulated successes for variant B.
	 *
	 * [--trials=<n>]
	 * : Total trials per variant (default: 100).
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 bayesian-simulate ab_abc123 --conversions-a=60 --conversions-b=45 --trials=100
	 *
	 * @subcommand bayesian-simulate
	 */
	public function bayesian_simulate( array $args, array $assoc_args ): void {
		$test_id       = $args[0] ?? 'sim';
		$conversions_a = (int) ( $assoc_args['conversions-a'] ?? 0 );
		$conversions_b = (int) ( $assoc_args['conversions-b'] ?? 0 );
		$trials        = (int) ( $assoc_args['trials'] ?? 100 );

		if ( $trials <= 0 ) {
			\WP_CLI::error( '--trials must be a positive integer.' );
			return;
		}

		$failures_a = max( 0, $trials - $conversions_a );
		$failures_b = max( 0, $trials - $conversions_b );

		$opt = new BayesianOptimizer( [ 'a', 'b' ] );

		for ( $i = 0; $i < $conversions_a; $i++ ) {
			$opt->update( 'a', true );
		}
		for ( $i = 0; $i < $failures_a; $i++ ) {
			$opt->update( 'a', false );
		}
		for ( $i = 0; $i < $conversions_b; $i++ ) {
			$opt->update( 'b', true );
		}
		for ( $i = 0; $i < $failures_b; $i++ ) {
			$opt->update( 'b', false );
		}

		$probs   = $opt->get_probabilities();
		$leading = $opt->get_leading_arm();

		\WP_CLI::log( "=== Bayesian Simulation: {$test_id} ===" );
		\WP_CLI::log( sprintf( "Variant A: %d/%d conversions → %.1f%% win prob", $conversions_a, $trials, $probs['a'] * 100 ) );
		\WP_CLI::log( sprintf( "Variant B: %d/%d conversions → %.1f%% win prob", $conversions_b, $trials, $probs['b'] * 100 ) );
		\WP_CLI::log( "Leading arm : " . strtoupper( $leading ) );
		\WP_CLI::log( "Converged   : " . ( $opt->is_converged() ? 'YES' : 'NO' ) );
	}

	// -----------------------------------------------------------------------
	// orphan-detect
	// -----------------------------------------------------------------------

	/**
	 * List published posts that receive zero inbound internal links.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : Maximum number of orphan pages to display. Default: 25.
	 *
	 * [--post-type=<type>]
	 * : WordPress post type to scan. Default: post.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 orphan-detect --limit=50
	 *   wp pearblog v9 orphan-detect --post-type=page
	 *
	 * @subcommand orphan-detect
	 */
	public function orphan_detect( array $args, array $assoc_args ): void {
		$limit     = (int) ( $assoc_args['limit']     ?? 25 );
		$post_type = $assoc_args['post-type'] ?? 'post';

		\WP_CLI::log( "Scanning for orphan {$post_type}s (limit: {$limit})…" );

		$detector = new OrphanPageDetector();
		$orphans  = $detector->get_orphan_pages( [ 'limit' => $limit, 'post_type' => $post_type ] );

		if ( empty( $orphans ) ) {
			\WP_CLI::success( 'No orphan pages found — great internal linking!' );
			return;
		}

		\WP_CLI::log( '' );
		foreach ( $orphans as $orphan ) {
			\WP_CLI::log( sprintf(
				'[%d] (score %.1f) %s',
				$orphan['post_id'],
				$orphan['quality_score'],
				$orphan['title']
			) );
			\WP_CLI::log( '    ' . $orphan['url'] );
		}

		\WP_CLI::log( '' );
		\WP_CLI::warning( count( $orphans ) . " orphan page(s) detected. Run `wp pearblog v9 orphan-suggest <post_id>` to find donor posts." );
	}

	// -----------------------------------------------------------------------
	// orphan-report
	// -----------------------------------------------------------------------

	/**
	 * Display the full link equity distribution report.
	 *
	 * ## OPTIONS
	 *
	 * [--post-type=<type>]
	 * : WordPress post type to analyse. Default: post.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 orphan-report
	 *   wp pearblog v9 orphan-report --post-type=page
	 *
	 * @subcommand orphan-report
	 */
	public function orphan_report( array $args, array $assoc_args ): void {
		$post_type = $assoc_args['post-type'] ?? 'post';

		\WP_CLI::log( "Building link equity report for post_type={$post_type}…" );

		$detector = new OrphanPageDetector();
		$dist     = $detector->get_link_equity_distribution( $post_type );

		\WP_CLI::log( '' );
		\WP_CLI::log( "=== Link Equity Distribution ({$post_type}s) ===" );
		\WP_CLI::log( sprintf( "Total posts   : %d", $dist['total_posts'] ) );
		\WP_CLI::log( sprintf( "Orphans (0)   : %d (%.1f%%)", $dist['orphan_count'], $dist['orphan_pct'] ) );
		\WP_CLI::log( sprintf( "1–2 links     : %d", $dist['buckets']['1-2'] ) );
		\WP_CLI::log( sprintf( "3–5 links     : %d", $dist['buckets']['3-5'] ) );
		\WP_CLI::log( sprintf( "6–10 links    : %d", $dist['buckets']['6-10'] ) );
		\WP_CLI::log( sprintf( "11+ links     : %d", $dist['buckets']['11+'] ) );

		if ( $dist['orphan_count'] > 0 ) {
			\WP_CLI::warning( "Run `wp pearblog v9 orphan-detect` to list the orphan pages." );
		} else {
			\WP_CLI::success( 'All posts are linked — excellent internal linking health.' );
		}
	}

	// -----------------------------------------------------------------------
	// orphan-suggest
	// -----------------------------------------------------------------------

	/**
	 * Suggest donor posts that could add an internal link to a given post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : ID of the target (orphan) post.
	 *
	 * [--limit=<n>]
	 * : Maximum donor suggestions. Default: 5.
	 *
	 * ## EXAMPLES
	 *
	 *   wp pearblog v9 orphan-suggest 42
	 *   wp pearblog v9 orphan-suggest 42 --limit=10
	 *
	 * @subcommand orphan-suggest
	 */
	public function orphan_suggest( array $args, array $assoc_args ): void {
		$post_id = (int) ( $args[0] ?? 0 );
		$limit   = (int) ( $assoc_args['limit'] ?? OrphanPageDetector::MAX_DONOR_SUGGESTIONS );

		if ( $post_id <= 0 ) {
			\WP_CLI::error( 'Usage: wp pearblog v9 orphan-suggest <post_id> [--limit=<n>]' );
			return;
		}

		$title    = get_the_title( $post_id );
		\WP_CLI::log( "Finding donor posts for: [{$post_id}] {$title}" );

		$detector = new OrphanPageDetector();
		$donors   = $detector->suggest_links_for_orphan( $post_id, $limit );

		if ( empty( $donors ) ) {
			\WP_CLI::warning( 'No donor posts found. Try adding keyword cluster meta to this post first.' );
			return;
		}

		\WP_CLI::log( '' );
		foreach ( $donors as $donor ) {
			\WP_CLI::log( sprintf(
				'[%d] (overlap %.0f%%) %s',
				$donor['post_id'],
				$donor['overlap_score'] * 100,
				$donor['title']
			) );
			\WP_CLI::log( '    ' . $donor['url'] );
		}

		\WP_CLI::success( count( $donors ) . " donor post(s) found." );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Feed per-variant article scores into a BayesianOptimizer as reward signals.
	 *
	 * Uses quality score ≥ 70 as a "conversion" (binary reward).
	 *
	 * @param BayesianOptimizer $opt     Optimizer to update.
	 * @param ABTestEngine      $engine  ABTestEngine instance.
	 * @param string            $test_id Test ID.
	 * @param string            $variant Variant label ('a' or 'b').
	 */
	private function feed_optimizer(
		BayesianOptimizer $opt,
		ABTestEngine      $engine,
		string            $test_id,
		string            $variant
	): void {
		$test = $engine->get_test( $test_id );
		if ( ! $test ) {
			return;
		}

		$scores = $test['variants'][ $variant ]['scores'] ?? [];
		foreach ( $scores as $score ) {
			$opt->update( $variant, (float) $score >= 70.0 );
		}

		// If no granular scores, use average vs 70 threshold for each run.
		if ( empty( $scores ) ) {
			$runs = (int) ( $test['variants'][ $variant ]['runs'] ?? 0 );
			$avg  = $engine->get_average_score( $test_id, $variant );
			for ( $i = 0; $i < $runs; $i++ ) {
				$opt->update( $variant, $avg >= 70.0 );
			}
		}
	}
}
