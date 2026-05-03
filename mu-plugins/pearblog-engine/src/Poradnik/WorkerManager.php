<?php
/**
 * Background Workers
 *
 * Manages async tasks for content generation, scoring, and optimization.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

/**
 * Class WorkerManager
 *
 * Orchestrates background workers.
 */
class WorkerManager {
	/**
	 * Register all workers.
	 */
	public function register(): void {
		// Register WP-Cron hooks
		add_action( 'poradnik_generate_worker', array( $this, 'run_generate_worker' ) );
		add_action( 'poradnik_scoring_worker', array( $this, 'run_scoring_worker' ) );
		add_action( 'poradnik_optimize_worker', array( $this, 'run_optimize_worker' ) );
		add_action( 'poradnik_publish_worker', array( $this, 'run_publish_worker' ) );

		// Schedule cron jobs if not already scheduled
		if ( ! wp_next_scheduled( 'poradnik_scoring_worker' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 05:00' ), 'daily', 'poradnik_scoring_worker' );
		}

		if ( ! wp_next_scheduled( 'poradnik_optimize_worker' ) ) {
			wp_schedule_event( strtotime( 'next sunday 01:00' ), 'weekly', 'poradnik_optimize_worker' );
		}
	}

	/**
	 * Run generate worker.
	 *
	 * Processes queued content generation tasks.
	 *
	 * @param array $args Worker arguments.
	 */
	public function run_generate_worker( array $args = array() ): void {
		$batch_size = $args['batch_size'] ?? 10;

		$generator = new ContentGenerator();
		$result    = $generator->process_queue( $batch_size );

		error_log( sprintf(
			'[Poradnik Generate Worker] Processed %d/%d tasks',
			$result['processed'],
			$result['total']
		) );
	}

	/**
	 * Run scoring worker.
	 *
	 * Calculates scores for all published articles.
	 */
	public function run_scoring_worker(): void {
		$scoring_engine = new ScoringEngine();
		$date           = current_time( 'Y-m-d' );
		$result         = $scoring_engine->calculate_all_scores( $date );

		error_log( sprintf(
			'[Poradnik Scoring Worker] Scored %d articles on %s (Success: %d, Failed: %d)',
			$result['total'],
			$date,
			$result['success'],
			$result['failed']
		) );
	}

	/**
	 * Run optimize worker.
	 *
	 * Applies optimizations and runs decision engine.
	 */
	public function run_optimize_worker(): void {
		$decision_engine = new DecisionEngine();
		$result          = $decision_engine->execute_all();

		error_log( sprintf(
			'[Poradnik Optimize Worker] Processed %d articles',
			$result['total']
		) );

		// Complete A/B tests that have sufficient data
		$this->complete_ab_tests();
	}

	/**
	 * Run publish worker.
	 *
	 * Publishes articles from review status.
	 *
	 * @param array $args Worker arguments.
	 */
	public function run_publish_worker( array $args = array() ): void {
		global $wpdb;

		$limit         = $args['limit'] ?? 5;
		$table_name    = $wpdb->prefix . 'pearblog_articles';

		// Get articles in review status
		$articles = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE status = 'review' ORDER BY created_at ASC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		$published = 0;

		foreach ( $articles as $article ) {
			// Update post status to publish
			$result = wp_update_post(
				array(
					'ID'          => $article['post_id'],
					'post_status' => 'publish',
				)
			);

			if ( ! is_wp_error( $result ) ) {
				// Update article status
				$wpdb->update(
					$table_name,
					array( 'status' => 'published' ),
					array( 'id' => $article['id'] ),
					array( '%s' ),
					array( '%d' )
				);

				$published++;
			}
		}

		error_log( sprintf(
			'[Poradnik Publish Worker] Published %d/%d articles',
			$published,
			count( $articles )
		) );
	}

	/**
	 * Complete A/B tests with sufficient data.
	 */
	private function complete_ab_tests(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pearblog_ab_tests';

		// Get running tests with enough data
		$tests = $wpdb->get_results(
			"SELECT id FROM {$table_name}
			WHERE status = 'running'
			AND variant_a_views >= 100
			AND variant_b_views >= 100",
			ARRAY_A
		);

		$ab_tester = new ABTester();
		$completed = 0;

		foreach ( $tests as $test ) {
			$result = $ab_tester->complete_test( $test['id'] );
			if ( $result ) {
				// Apply winner
				$ab_tester->apply_winner( $test['id'] );
				$completed++;
			}
		}

		if ( $completed > 0 ) {
			error_log( sprintf(
				'[Poradnik Optimize Worker] Completed %d A/B tests',
				$completed
			) );
		}
	}

	/**
	 * Dispatch async task.
	 *
	 * @param string $worker Worker name.
	 * @param array  $args Worker arguments.
	 * @return bool True if scheduled.
	 */
	public function dispatch( string $worker, array $args = array() ): bool {
		$hook = "poradnik_{$worker}_worker";
		return (bool) wp_schedule_single_event( time(), $hook, array( $args ) );
	}
}

/**
 * Content Generator Worker
 *
 * Generates content from queued topics.
 */
class ContentGenerator {
	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Process content generation queue.
	 *
	 * @param int $batch_size Number of items to process.
	 * @return array Results.
	 */
	public function process_queue( int $batch_size = 10 ): array {
		$table_name = $this->wpdb->prefix . 'pearblog_articles';

		// Get articles in draft status that need content generation
		$articles = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE status = 'draft' AND post_id IS NULL LIMIT %d",
				$batch_size
			),
			ARRAY_A
		);

		$results = array(
			'total'     => count( $articles ),
			'processed' => 0,
			'failed'    => 0,
		);

		foreach ( $articles as $article ) {
			$result = $this->generate_article( $article );

			if ( $result ) {
				$results['processed']++;
			} else {
				$results['failed']++;
			}
		}

		return $results;
	}

	/**
	 * Generate article content.
	 *
	 * @param array $article Article data.
	 * @return bool True on success.
	 */
	private function generate_article( array $article ): bool {
		// Get service data
		$scraper = new DataScraper();
		$service_data = $scraper->scrape_service_data( $article['service'] ?? $article['topic'], $article['city'] ?? '' );

		if ( ! $service_data ) {
			return false;
		}

		// Generate content using PoradnikPromptBuilder
		$profile = new \PearBlog\Tenant\SiteProfile(
			array(
				'industry' => 'poradnik',
				'tone'     => 'professional',
			)
		);

		$prompt_builder = new \PearBlog\Content\PoradnikPromptBuilder( $profile );
		$prompt         = $prompt_builder->build( $article['topic'] );

		// Add service data to prompt
		$prompt .= "\n\n## Dane rynkowe:\n";
		$prompt .= "- Cena minimalna: {$service_data['price_min']} PLN\n";
		$prompt .= "- Cena maksymalna: {$service_data['price_max']} PLN\n";
		$prompt .= "- Cena średnia: {$service_data['price_avg']} PLN\n";

		// Generate content using AI
		$ai_client = new \PearBlog\AI\OpenAIClient();
		$content   = $ai_client->generate_text( $prompt, array( 'max_tokens' => 2000 ) );

		if ( empty( $content ) ) {
			return false;
		}

		// Create WordPress post
		$post_id = wp_insert_post(
			array(
				'post_title'   => $article['topic'] . ' - ile kosztuje i jak wybrać',
				'post_content' => $content,
				'post_status'  => 'draft',
				'post_type'    => 'post',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Update article with post_id
		$this->wpdb->update(
			$this->wpdb->prefix . 'pearblog_articles',
			array(
				'post_id' => $post_id,
				'status'  => 'review',
			),
			array( 'id' => $article['id'] ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return true;
	}
}
