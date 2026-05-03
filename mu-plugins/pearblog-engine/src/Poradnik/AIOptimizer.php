<?php
/**
 * AI Optimizer
 *
 * Applies rule-based optimizations to underperforming content.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

use PearBlog\AI\OpenAIClient;

/**
 * Class AIOptimizer
 *
 * Automated content optimization engine.
 */
class AIOptimizer {
	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * AI client.
	 *
	 * @var OpenAIClient
	 */
	private $ai_client;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb      = $wpdb;
		$this->ai_client = new OpenAIClient();
	}

	/**
	 * Analyze article and determine optimizations needed.
	 *
	 * @param int $article_id Article ID.
	 * @return array Optimization recommendations.
	 */
	public function analyze( int $article_id ): array {
		$stats = $this->get_latest_stats( $article_id );
		if ( ! $stats ) {
			return array( 'error' => 'No statistics found' );
		}

		$optimizations = array();

		// Rule 1: Low CTR → Rewrite CTA
		if ( $stats['cta_ctr'] < 0.05 ) {
			$optimizations[] = array(
				'rule'        => 'low_ctr',
				'action'      => 'rewrite_cta',
				'priority'    => 'high',
				'description' => 'CTA CTR below 5% threshold',
			);
		}

		// Rule 2: Low engagement → Rewrite intro
		if ( $stats['avg_time_seconds'] < 40 ) {
			$optimizations[] = array(
				'rule'        => 'low_engagement',
				'action'      => 'rewrite_intro',
				'priority'    => 'high',
				'description' => 'Average time on page below 40 seconds',
			);
		}

		// Rule 3: No revenue with good traffic → Add/reposition CTA
		if ( $stats['revenue'] == 0 && $stats['views'] > 100 ) {
			$optimizations[] = array(
				'rule'        => 'no_revenue',
				'action'      => 'add_cta',
				'priority'    => 'critical',
				'description' => 'High views but no revenue generated',
			);
		}

		// Rule 4: Low SEO CTR → Rewrite title/meta
		if ( $stats['seo_impressions'] > 1000 && $stats['seo_ctr'] < 0.02 ) {
			$optimizations[] = array(
				'rule'        => 'low_seo_ctr',
				'action'      => 'rewrite_meta',
				'priority'    => 'medium',
				'description' => 'High impressions but low click-through rate',
			);
		}

		// Rule 5: High bounce rate → Improve intro/add images
		if ( $stats['bounce_rate'] > 70 ) {
			$optimizations[] = array(
				'rule'        => 'high_bounce',
				'action'      => 'improve_intro',
				'priority'    => 'high',
				'description' => 'Bounce rate above 70%',
			);
		}

		return $optimizations;
	}

	/**
	 * Apply optimization to an article.
	 *
	 * @param int    $article_id Article ID.
	 * @param string $action Optimization action.
	 * @return array|WP_Error Result or error.
	 */
	public function optimize( int $article_id, string $action ) {
		$article = $this->get_article( $article_id );
		if ( ! $article ) {
			return new \WP_Error( 'article_not_found', 'Article not found' );
		}

		$post = get_post( $article['post_id'] );
		if ( ! $post ) {
			return new \WP_Error( 'post_not_found', 'Post not found' );
		}

		switch ( $action ) {
			case 'rewrite_cta':
				return $this->rewrite_cta( $post );

			case 'rewrite_intro':
				return $this->rewrite_intro( $post );

			case 'add_cta':
				return $this->add_cta( $post );

			case 'reposition_cta':
				return $this->reposition_cta( $post );

			case 'rewrite_meta':
				return $this->rewrite_meta( $post );

			case 'improve_intro':
				return $this->improve_intro( $post );

			default:
				return new \WP_Error( 'invalid_action', 'Invalid optimization action' );
		}
	}

	/**
	 * Rewrite CTA section.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Result.
	 */
	private function rewrite_cta( \WP_Post $post ): array {
		$content = $post->post_content;

		// Extract current CTA
		preg_match( '/## (?:Szukasz|Potrzebujesz|Chcesz).+?(?=##|\z)/s', $content, $matches );
		$current_cta = $matches[0] ?? '';

		if ( empty( $current_cta ) ) {
			return array( 'error' => 'CTA section not found' );
		}

		// Generate new CTA using AI
		$prompt = "Przepisz poniższą sekcję CTA, aby była bardziej przekonująca i naturalna. Zachowaj link do PT24.\n\nStara wersja:\n{$current_cta}";

		$new_cta = $this->ai_client->generate_text( $prompt, array( 'max_tokens' => 200 ) );

		// Replace CTA in content
		$new_content = str_replace( $current_cta, $new_cta, $content );

		// Update post
		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => $new_content,
			)
		);

		return array(
			'success'     => true,
			'action'      => 'rewrite_cta',
			'old_content' => $current_cta,
			'new_content' => $new_cta,
		);
	}

	/**
	 * Rewrite introduction.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Result.
	 */
	private function rewrite_intro( \WP_Post $post ): array {
		$content = $post->post_content;

		// Extract first 2-3 paragraphs
		preg_match( '/^(.+?)(?=##)/s', $content, $matches );
		$current_intro = $matches[1] ?? '';

		if ( empty( $current_intro ) ) {
			return array( 'error' => 'Introduction not found' );
		}

		// Generate new intro using AI
		$prompt = "Przepisz poniższe wprowadzenie, aby było bardziej angażujące i zachęcające do czytania. Zachowaj kluczowe informacje.\n\nStare wprowadzenie:\n{$current_intro}\n\nTemat: {$post->post_title}";

		$new_intro = $this->ai_client->generate_text( $prompt, array( 'max_tokens' => 300 ) );

		// Replace intro in content
		$new_content = preg_replace( '/^.+?(?=##)/s', $new_intro . "\n\n", $content );

		// Update post
		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => $new_content,
			)
		);

		return array(
			'success'     => true,
			'action'      => 'rewrite_intro',
			'old_content' => $current_intro,
			'new_content' => $new_intro,
		);
	}

	/**
	 * Add CTA to content.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Result.
	 */
	private function add_cta( \WP_Post $post ): array {
		$content = $post->post_content;

		// Check if CTA already exists
		if ( strpos( $content, 'pt24.pl' ) !== false ) {
			return array( 'error' => 'CTA already exists' );
		}

		// Generate CTA using AI
		$prompt = "Napisz naturalną sekcję CTA dla artykułu o temacie: {$post->post_title}. Dołącz link do PT24.pl jako marketplace wykonawców.";

		$new_cta = $this->ai_client->generate_text( $prompt, array( 'max_tokens' => 200 ) );

		// Insert CTA before FAQ section
		$new_content = preg_replace(
			'/## Najczęściej zadawane pytania/',
			$new_cta . "\n\n## Najczęściej zadawane pytania",
			$content
		);

		// Update post
		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => $new_content,
			)
		);

		return array(
			'success'     => true,
			'action'      => 'add_cta',
			'new_content' => $new_cta,
		);
	}

	/**
	 * Reposition CTA earlier in content.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Result.
	 */
	private function reposition_cta( \WP_Post $post ): array {
		$content = $post->post_content;

		// Extract current CTA
		preg_match( '/## (?:Szukasz|Potrzebujesz|Chcesz).+?(?=##|\z)/s', $content, $matches );
		$cta = $matches[0] ?? '';

		if ( empty( $cta ) ) {
			return array( 'error' => 'CTA section not found' );
		}

		// Remove CTA from current position
		$content = str_replace( $cta, '', $content );

		// Insert CTA after "Od czego zależy cena" section
		$new_content = preg_replace(
			'/(## Od czego zależy cena\?.*?(?=##))/s',
			"$1\n\n" . $cta,
			$content
		);

		// Update post
		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => $new_content,
			)
		);

		return array(
			'success' => true,
			'action'  => 'reposition_cta',
		);
	}

	/**
	 * Rewrite title and meta description.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Result.
	 */
	private function rewrite_meta( \WP_Post $post ): array {
		// Generate new title using AI
		$prompt_title = "Napisz bardziej klikalne SEO title dla artykułu o temacie: {$post->post_title}. Max 60 znaków. Dodaj element ceny lub porównania.";

		$new_title = $this->ai_client->generate_text( $prompt_title, array( 'max_tokens' => 20 ) );

		// Generate new meta description
		$prompt_meta = "Napisz przekonujący meta description dla artykułu o temacie: {$post->post_title}. Max 160 znaków. Skup się na koszcie i wyborze.";

		$new_meta = $this->ai_client->generate_text( $prompt_meta, array( 'max_tokens' => 50 ) );

		// Update post and meta
		wp_update_post(
			array(
				'ID'         => $post->ID,
				'post_title' => trim( $new_title ),
			)
		);

		update_post_meta( $post->ID, '_yoast_wpseo_metadesc', trim( $new_meta ) );

		return array(
			'success'   => true,
			'action'    => 'rewrite_meta',
			'new_title' => $new_title,
			'new_meta'  => $new_meta,
		);
	}

	/**
	 * Improve introduction with better hook.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Result.
	 */
	private function improve_intro( \WP_Post $post ): array {
		// Similar to rewrite_intro but with focus on hook
		return $this->rewrite_intro( $post );
	}

	/**
	 * Get latest statistics for an article.
	 *
	 * @param int $article_id Article ID.
	 * @return array|null Statistics or null.
	 */
	private function get_latest_stats( int $article_id ): ?array {
		$table_name = $this->wpdb->prefix . 'pearblog_article_stats';

		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE article_id = %d ORDER BY date DESC LIMIT 1",
				$article_id
			),
			ARRAY_A
		);
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
}
