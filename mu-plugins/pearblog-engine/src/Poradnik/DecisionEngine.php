<?php
/**
 * Decision Engine
 *
 * Automates actions based on article performance scores.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

/**
 * Class DecisionEngine
 *
 * Automated decision-making for content optimization.
 */
class DecisionEngine {
	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Scoring engine.
	 *
	 * @var ScoringEngine
	 */
	private $scoring_engine;

	/**
	 * AI optimizer.
	 *
	 * @var AIOptimizer
	 */
	private $ai_optimizer;

	/**
	 * A/B tester.
	 *
	 * @var ABTester
	 */
	private $ab_tester;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb           = $wpdb;
		$this->scoring_engine = new ScoringEngine();
		$this->ai_optimizer   = new AIOptimizer();
		$this->ab_tester      = new ABTester();
	}

	/**
	 * Make decision for an article based on its score category.
	 *
	 * @param int $article_id Article ID.
	 * @return array Actions taken.
	 */
	public function decide( int $article_id ): array {
		$score_data = $this->scoring_engine->calculate_score( $article_id );
		$category   = $score_data['category'];

		$actions = array(
			'article_id' => $article_id,
			'score'      => $score_data['total_score'],
			'category'   => $category,
			'decisions'  => array(),
		);

		switch ( $category ) {
			case 'SCALE':
				$actions['decisions'] = $this->handle_scale( $article_id, $score_data );
				break;

			case 'BOOST':
				$actions['decisions'] = $this->handle_boost( $article_id, $score_data );
				break;

			case 'OPTIMIZE':
				$actions['decisions'] = $this->handle_optimize( $article_id, $score_data );
				break;

			case 'DELETE':
				$actions['decisions'] = $this->handle_delete( $article_id, $score_data );
				break;
		}

		return $actions;
	}

	/**
	 * Handle SCALE category (90-100 score).
	 *
	 * Actions: Generate variants, increase internal linking, promote.
	 *
	 * @param int   $article_id Article ID.
	 * @param array $score_data Score data.
	 * @return array Actions taken.
	 */
	private function handle_scale( int $article_id, array $score_data ): array {
		$actions = array();

		// Generate content variants for similar topics
		$actions[] = array(
			'action'      => 'generate_variants',
			'reason'      => 'Top performer - scale to similar topics',
			'status'      => 'queued',
		);

		// Increase internal linking
		$actions[] = array(
			'action'      => 'increase_linking',
			'reason'      => 'Boost visibility of high-performer',
			'status'      => 'queued',
		);

		// Add to homepage/featured
		$actions[] = array(
			'action'      => 'promote',
			'reason'      => 'Feature top content',
			'status'      => 'queued',
		);

		return $actions;
	}

	/**
	 * Handle BOOST category (70-90 score).
	 *
	 * Actions: Increase visibility, add internal links, minor optimizations.
	 *
	 * @param int   $article_id Article ID.
	 * @param array $score_data Score data.
	 * @return array Actions taken.
	 */
	private function handle_boost( int $article_id, array $score_data ): array {
		$actions = array();

		// Add more internal links
		$actions[] = array(
			'action'      => 'add_internal_links',
			'reason'      => 'Increase discoverability',
			'status'      => 'queued',
		);

		// Improve meta description
		$actions[] = array(
			'action'      => 'optimize_meta',
			'reason'      => 'Boost SEO CTR',
			'status'      => 'queued',
		);

		// Minor content enhancements
		if ( $score_data['stats']['avg_time_seconds'] < 60 ) {
			$actions[] = array(
				'action'      => 'enhance_content',
				'reason'      => 'Increase engagement time',
				'status'      => 'queued',
			);
		}

		return $actions;
	}

	/**
	 * Handle OPTIMIZE category (50-70 score).
	 *
	 * Actions: A/B testing, rewrite weak sections, optimize CTAs.
	 *
	 * @param int   $article_id Article ID.
	 * @param array $score_data Score data.
	 * @return array Actions taken.
	 */
	private function handle_optimize( int $article_id, array $score_data ): array {
		$actions = array();

		// Analyze for optimizations
		$optimizations = $this->ai_optimizer->analyze( $article_id );

		foreach ( $optimizations as $opt ) {
			if ( $opt['priority'] === 'critical' || $opt['priority'] === 'high' ) {
				// Apply optimization directly
				$result    = $this->ai_optimizer->optimize( $article_id, $opt['action'] );
				$actions[] = array(
					'action'      => $opt['action'],
					'reason'      => $opt['description'],
					'status'      => is_wp_error( $result ) ? 'failed' : 'completed',
					'result'      => $result,
				);
			}
		}

		// Launch A/B test for CTA if CTR is low
		if ( $score_data['stats']['cta_ctr'] < 5 ) {
			$article   = $this->get_article( $article_id );
			$post      = get_post( $article['post_id'] );

			if ( $post ) {
				// Extract current CTA
				preg_match( '/## (?:Szukasz|Potrzebujesz|Chcesz).+?(?=##|\z)/s', $post->post_content, $matches );
				$current_cta = $matches[0] ?? '';

				if ( $current_cta ) {
					// Generate variant CTA
					$variant_cta = $this->generate_cta_variant( $current_cta );

					// Create A/B test
					$test_id = $this->ab_tester->create_test(
						$article_id,
						'cta_test',
						$current_cta,
						$variant_cta
					);

					$actions[] = array(
						'action'      => 'launch_ab_test',
						'reason'      => 'Test CTA variants to improve CTR',
						'status'      => $test_id ? 'completed' : 'failed',
						'test_id'     => $test_id,
					);
				}
			}
		}

		return $actions;
	}

	/**
	 * Handle DELETE category (0-50 score).
	 *
	 * Actions: Rewrite, redirect, or archive.
	 *
	 * @param int   $article_id Article ID.
	 * @param array $score_data Score data.
	 * @return array Actions taken.
	 */
	private function handle_delete( int $article_id, array $score_data ): array {
		$actions = array();

		// If very low traffic, archive
		if ( $score_data['stats']['views'] < 10 ) {
			$actions[] = array(
				'action'      => 'archive',
				'reason'      => 'Very low traffic - archive article',
				'status'      => 'queued',
			);
		} else {
			// If decent traffic but poor performance, complete rewrite
			$actions[] = array(
				'action'      => 'complete_rewrite',
				'reason'      => 'Poor performance - needs complete overhaul',
				'status'      => 'queued',
			);
		}

		// Find better-performing similar article for redirect
		$similar = $this->find_similar_high_performer( $article_id );
		if ( $similar ) {
			$actions[] = array(
				'action'       => 'redirect',
				'reason'       => 'Redirect to better-performing similar article',
				'status'       => 'queued',
				'redirect_to'  => $similar['id'],
			);
		}

		return $actions;
	}

	/**
	 * Execute all pending decisions.
	 *
	 * @return array Execution results.
	 */
	public function execute_all(): array {
		$articles_table = $this->wpdb->prefix . 'pearblog_articles';
		$stats_table    = $this->wpdb->prefix . 'pearblog_article_stats';

		// Get all articles with recent stats
		$articles = $this->wpdb->get_results(
			"SELECT DISTINCT s.article_id, s.score_category
			FROM {$stats_table} s
			JOIN {$articles_table} a ON s.article_id = a.id
			WHERE a.status = 'published'
			AND s.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
			ORDER BY s.date DESC",
			ARRAY_A
		);

		$results = array(
			'total'     => count( $articles ),
			'processed' => array(),
		);

		foreach ( $articles as $article ) {
			$decisions                = $this->decide( $article['article_id'] );
			$results['processed'][]   = $decisions;
		}

		return $results;
	}

	/**
	 * Get article by ID.
	 *
	 * @param int $article_id Article ID.
	 * @return array|null Article or null.
	 */
	private function get_article( int $article_id ): ?array {
		$table_name = $this->wpdb->prefix . 'pearblog_articles';

		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$article_id
			),
			ARRAY_A
		);
	}

	/**
	 * Generate CTA variant for A/B testing.
	 *
	 * @param string $current_cta Current CTA.
	 * @return string Variant CTA.
	 */
	private function generate_cta_variant( string $current_cta ): string {
		// Simplified variant generation
		// In production, use AI to generate truly different variant
		return str_replace(
			array( 'Szukasz', 'Potrzebujesz', 'Chcesz' ),
			array( 'Szukasz najlepszego', 'Potrzebujesz sprawdzonego', 'Chcesz znaleźć' ),
			$current_cta
		);
	}

	/**
	 * Find similar high-performing article.
	 *
	 * @param int $article_id Article ID.
	 * @return array|null Similar article or null.
	 */
	private function find_similar_high_performer( int $article_id ): ?array {
		$articles_table = $this->wpdb->prefix . 'pearblog_articles';
		$stats_table    = $this->wpdb->prefix . 'pearblog_article_stats';

		$current = $this->get_article( $article_id );
		if ( ! $current ) {
			return null;
		}

		// Find similar article with SCALE or BOOST category
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT a.id, a.topic, s.score
				FROM {$articles_table} a
				JOIN {$stats_table} s ON a.id = s.article_id
				WHERE a.service = %s
				AND a.city = %s
				AND a.id != %d
				AND s.score_category IN ('SCALE', 'BOOST')
				ORDER BY s.score DESC
				LIMIT 1",
				$current['service'],
				$current['city'],
				$article_id
			),
			ARRAY_A
		);
	}
}
