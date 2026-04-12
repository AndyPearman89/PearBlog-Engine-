<?php
/**
 * Few-Shot Engine — injects examples from top-performing articles into prompts.
 *
 * The engine queries for published posts with a high quality score and extracts
 * short excerpts to use as writing examples in the AI prompt.  This encourages
 * the model to reproduce the same style, depth, and structure that earned those
 * articles a high score.
 *
 * Storage / configuration:
 *   pearblog_fewshot_enabled     – bool, default true
 *   pearblog_fewshot_max_posts   – int, how many example posts to include (default 3)
 *   pearblog_fewshot_excerpt_len – int, max characters per excerpt (default 400)
 *   pearblog_fewshot_min_score   – float, minimum quality score to qualify (default 70)
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Pulls top-performing articles and formats them as few-shot examples.
 */
class FewShotEngine {

	/** WP option: enable/disable few-shot injection. */
	public const OPTION_ENABLED     = 'pearblog_fewshot_enabled';

	/** WP option: maximum number of example posts. */
	public const OPTION_MAX_POSTS   = 'pearblog_fewshot_max_posts';

	/** WP option: maximum characters per excerpt. */
	public const OPTION_EXCERPT_LEN = 'pearblog_fewshot_excerpt_len';

	/** WP option: minimum quality score to qualify as an example. */
	public const OPTION_MIN_SCORE   = 'pearblog_fewshot_min_score';

	/** Default values. */
	public const DEFAULT_MAX_POSTS   = 3;
	public const DEFAULT_EXCERPT_LEN = 400;
	public const DEFAULT_MIN_SCORE   = 70.0;

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Append few-shot examples to an AI prompt string.
	 *
	 * @param string $prompt   Original prompt text.
	 * @param string $industry Optional industry filter (not applied — reserved for future use).
	 * @return string          Prompt with few-shot block appended, or unchanged if disabled / no examples.
	 */
	public function enrich_prompt( string $prompt, string $industry = '' ): string {
		if ( ! $this->is_enabled() ) {
			return $prompt;
		}

		$examples = $this->get_examples();
		if ( empty( $examples ) ) {
			return $prompt;
		}

		$block  = "\n\n---\n## Writing Style Examples\n";
		$block .= "The following excerpts are from top-performing articles. ";
		$block .= "Use them as a reference for tone, depth, and structure:\n\n";

		foreach ( $examples as $i => $ex ) {
			$n      = $i + 1;
			$block .= "**Example {$n} — \"{$ex['title']}\" (score: {$ex['score']}):**\n";
			$block .= "> {$ex['excerpt']}\n\n";
		}

		$block .= "---\n";

		return $prompt . $block;
	}

	/**
	 * Retrieve formatted few-shot example records.
	 *
	 * Each record has keys: post_id, title, score, excerpt.
	 *
	 * @return array<int, array{post_id: int, title: string, score: float, excerpt: string}>
	 */
	public function get_examples(): array {
		$max_posts   = (int) get_option( self::OPTION_MAX_POSTS, self::DEFAULT_MAX_POSTS );
		$min_score   = (float) get_option( self::OPTION_MIN_SCORE, self::DEFAULT_MIN_SCORE );
		$excerpt_len = (int) get_option( self::OPTION_EXCERPT_LEN, self::DEFAULT_EXCERPT_LEN );

		$posts = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => $max_posts * 3, // Over-fetch to allow for score filtering.
			'fields'         => 'ids',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'meta_key'       => QualityScorer::META_QUALITY_SCORE,
			'meta_query'     => [
				[
					'key'     => QualityScorer::META_QUALITY_SCORE,
					'value'   => $min_score,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				],
			],
		] );

		$examples = [];

		foreach ( $posts as $post_id ) {
			if ( count( $examples ) >= $max_posts ) {
				break;
			}

			$post = get_post( (int) $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$score   = (float) get_post_meta( (int) $post_id, QualityScorer::META_QUALITY_SCORE, true );
			$plain   = wp_strip_all_tags( $post->post_content );
			$excerpt = $this->extract_excerpt( $plain, $excerpt_len );

			if ( '' === $excerpt ) {
				continue;
			}

			$examples[] = [
				'post_id' => (int) $post_id,
				'title'   => get_the_title( (int) $post_id ),
				'score'   => $score,
				'excerpt' => $excerpt,
			];
		}

		return $examples;
	}

	/**
	 * Whether few-shot injection is currently enabled.
	 */
	public function is_enabled(): bool {
		$opt = get_option( self::OPTION_ENABLED, true );
		// Accept boolean true, string "1", or string "true".
		return filter_var( $opt, FILTER_VALIDATE_BOOLEAN );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Extract a leading excerpt from plain text.
	 *
	 * @param string $text     Plain-text content.
	 * @param int    $max_len  Maximum character length.
	 * @return string
	 */
	private function extract_excerpt( string $text, int $max_len ): string {
		$text = trim( $text );
		if ( '' === $text ) {
			return '';
		}

		if ( mb_strlen( $text ) <= $max_len ) {
			return $text;
		}

		// Truncate at the last word boundary before $max_len.
		$truncated = mb_substr( $text, 0, $max_len );
		$last_space = mb_strrpos( $truncated, ' ' );

		if ( $last_space !== false ) {
			$truncated = mb_substr( $truncated, 0, $last_space );
		}

		return rtrim( $truncated ) . '…';
	}
}
