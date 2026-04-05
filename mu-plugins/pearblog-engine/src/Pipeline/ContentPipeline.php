<?php
/**
 * Content pipeline – orchestrates the full content generation flow.
 *
 * v6 Flow (12 steps):
 *   Topic → Queue → Prompt → AI → DuplicateCheck → Draft → SEO →
 *   Monetization → InternalLinker → Image → DuplicateIndex → Publish →
 *   QualityScore
 *
 * @package PearBlogEngine\Pipeline
 */

declare(strict_types=1);

namespace PearBlogEngine\Pipeline;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\AI\ImageGenerator;
use PearBlogEngine\Content\ContentScore;
use PearBlogEngine\Content\ContentValidator;
use PearBlogEngine\Content\PromptBuilderFactory;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Monetization\MonetizationEngine;
use PearBlogEngine\SEO\InternalLinker;
use PearBlogEngine\SEO\SEOEngine;
use PearBlogEngine\Tenant\TenantContext;

/**
 * Runs the complete pipeline for a given tenant.
 *
 * Each call to {@see run()} processes exactly one article (the next topic from
 * the queue).  Call it in a loop (or multiple cron cycles) to process more.
 */
class ContentPipeline {

	/** Similarity threshold (0–100) above which a duplicate is detected. */
	private const DUPLICATE_THRESHOLD = 80;

	/** Minimum quality score to auto-publish (0–100). */
	private const MIN_PUBLISH_SCORE = 50;

	private TenantContext   $context;
	private AIClient        $ai;
	private ImageGenerator  $image_generator;
	private SEOEngine       $seo;
	private InternalLinker  $linker;

	public function __construct(
		TenantContext  $context,
		?AIClient      $ai              = null,
		?ImageGenerator $image_generator = null,
	) {
		$this->context         = $context;
		$this->ai              = $ai ?? new AIClient();
		$this->image_generator = $image_generator ?? new ImageGenerator();
		$this->seo             = new SEOEngine();
		$this->linker          = new InternalLinker();
	}

	/**
	 * Process the next topic from the queue for this tenant.
	 *
	 * @return array{post_id: int, topic: string, status: string, score?: int}|null
	 *         Result array, or null when the queue is empty.
	 * @throws \RuntimeException On AI or publish failures.
	 */
	public function run(): ?array {
		$queue = new TopicQueue( $this->context->site_id );

		$topic = $queue->pop();
		if ( null === $topic ) {
			return null;
		}

		/**
		 * Action: pearblog_pipeline_started
		 *
		 * @param string        $topic   The article topic.
		 * @param TenantContext $context Active tenant context.
		 */
		do_action( 'pearblog_pipeline_started', $topic, $this->context );

		// Step 1 – Build prompt using the appropriate builder.
		$builder = PromptBuilderFactory::create( $this->context->profile );
		$prompt  = $builder->build( $topic );

		// Step 2 – Generate content via AI.
		$raw_content = $this->ai->generate( $prompt );

		// Step 3 – Duplicate check (block if similarity ≥ 80 %).
		if ( $this->is_duplicate( $raw_content ) ) {
			error_log( sprintf(
				'PearBlog Engine: Duplicate detected for topic "%s" – skipped.',
				$topic
			) );

			/**
			 * Action: pearblog_pipeline_duplicate
			 *
			 * @param string        $topic       The article topic that was skipped.
			 * @param TenantContext $context     Active tenant context.
			 */
			do_action( 'pearblog_pipeline_duplicate', $topic, $this->context );

			return [
				'post_id' => 0,
				'topic'   => $topic,
				'status'  => 'duplicate_skipped',
			];
		}

		// Step 4 – Create a draft post so we have a post ID for meta operations.
		$post_id = $this->create_draft_post( $topic, $raw_content );

		// Step 5 – Apply SEO metadata.
		$seo_data = $this->seo->apply( $post_id, $raw_content );

		// Step 6 – Inject monetisation.
		$monetizer     = new MonetizationEngine( $this->context->profile );
		$content       = $monetizer->apply( $post_id, $seo_data['content'] );

		// Step 7 – Internal links.
		$content = $this->linker->apply( $post_id, $content );

		// Step 8 – Generate and attach featured image (AI-generated).
		$this->generate_featured_image( $post_id, $seo_data['title'] ?: $topic );

		// Step 9 – Index content for future duplicate detection.
		$this->index_for_duplicate_check( $post_id, $raw_content );

		// Step 10 – Quality score (content validation + scoring).
		$score = $this->score_content( $post_id, $seo_data['content'] );

		// Step 11 – Determine publish status based on quality score.
		$publish_status = $score->passes ? 'publish' : 'draft';

		if ( ! $score->passes ) {
			error_log( sprintf(
				'PearBlog Engine: Post %d scored %d/100 (below %d) – saved as draft.',
				$post_id,
				$score->total,
				self::MIN_PUBLISH_SCORE
			) );
		}

		// Step 12 – Update post with final content and publish/draft.
		wp_update_post( [
			'ID'           => $post_id,
			'post_title'   => $seo_data['title'] ?: $topic,
			'post_content' => $content,
			'post_status'  => $publish_status,
		] );

		// Store quality score as post meta.
		update_post_meta( $post_id, '_pearblog_quality_score', $score->total );
		update_post_meta( $post_id, '_pearblog_quality_issues', $score->issues );

		// Track last pipeline run timestamp.
		update_option( 'pearblog_last_pipeline_run', time(), false );

		/**
		 * Action: pearblog_pipeline_completed
		 *
		 * @param int           $post_id Post ID of the published/drafted article.
		 * @param string        $topic   Original topic.
		 * @param TenantContext $context Active tenant context.
		 */
		do_action( 'pearblog_pipeline_completed', $post_id, $topic, $this->context );

		return [
			'post_id' => $post_id,
			'topic'   => $topic,
			'status'  => 'publish' === $publish_status ? 'published' : 'draft_low_quality',
			'score'   => $score->total,
		];
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Insert a draft post.
	 */
	private function create_draft_post( string $topic, string $content ): int {
		$post_id = wp_insert_post( [
			'post_title'   => $topic,
			'post_content' => $content,
			'post_status'  => 'draft',
			'post_author'  => get_current_user_id() ?: 1,
		], true );

		if ( is_wp_error( $post_id ) ) {
			throw new \RuntimeException(
				'PearBlog Engine: Failed to create draft post – ' . $post_id->get_error_message()
			);
		}

		return $post_id;
	}

	/**
	 * Generate and attach a featured image using DALL-E 3.
	 */
	private function generate_featured_image( int $post_id, string $title ): void {
		try {
			$attachment_id = $this->image_generator->generate_and_attach( $post_id, $title );

			if ( null !== $attachment_id ) {
				update_post_meta( $post_id, '_pearblog_ai_image', '1' );
				error_log( sprintf(
					'PearBlog Engine: Generated featured image (ID: %d) for post %d',
					$attachment_id,
					$post_id
				) );
			}
		} catch ( \Throwable $e ) {
			error_log( sprintf(
				'PearBlog Engine: Failed to generate featured image for post %d – %s',
				$post_id,
				$e->getMessage()
			) );
		}
	}

	// -----------------------------------------------------------------------
	// Duplicate detection
	// -----------------------------------------------------------------------

	/**
	 * Check whether the generated content is too similar to existing posts.
	 *
	 * Uses a simple simhash-like comparison of word trigrams against stored
	 * fingerprints.  Can be disabled via the `pearblog_duplicate_check_enabled`
	 * option.
	 */
	private function is_duplicate( string $content ): bool {
		if ( ! (bool) get_option( 'pearblog_duplicate_check_enabled', true ) ) {
			return false;
		}

		$new_fingerprint = $this->fingerprint( $content );

		// Compare against recent posts' fingerprints.
		$stored = get_option( 'pearblog_content_fingerprints', [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		foreach ( $stored as $fp ) {
			$similarity = $this->compare_fingerprints( $new_fingerprint, $fp );
			if ( $similarity >= self::DUPLICATE_THRESHOLD ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Store the content fingerprint for future duplicate checks.
	 */
	private function index_for_duplicate_check( int $post_id, string $content ): void {
		$fingerprint = $this->fingerprint( $content );

		$stored = get_option( 'pearblog_content_fingerprints', [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		// Keep only the last 200 fingerprints.
		$stored[ $post_id ] = $fingerprint;
		if ( count( $stored ) > 200 ) {
			$stored = array_slice( $stored, -200, null, true );
		}

		update_option( 'pearblog_content_fingerprints', $stored, false );
	}

	/**
	 * Generate a set of word trigrams as a content fingerprint.
	 *
	 * @return string[] Unique trigram set.
	 */
	private function fingerprint( string $content ): array {
		$text  = mb_strtolower( wp_strip_all_tags( $content ) );
		$words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

		if ( false === $words || count( $words ) < 3 ) {
			return [];
		}

		$trigrams = [];
		$count    = count( $words );

		for ( $i = 0; $i <= $count - 3; $i++ ) {
			$trigrams[] = $words[ $i ] . ' ' . $words[ $i + 1 ] . ' ' . $words[ $i + 2 ];
		}

		return array_values( array_unique( $trigrams ) );
	}

	/**
	 * Compare two fingerprints and return a similarity percentage (0–100).
	 */
	private function compare_fingerprints( array $a, array $b ): float {
		if ( empty( $a ) || empty( $b ) ) {
			return 0.0;
		}

		$intersection = count( array_intersect( $a, $b ) );
		$union        = count( array_unique( array_merge( $a, $b ) ) );

		return $union > 0 ? ( $intersection / $union ) * 100 : 0.0;
	}

	// -----------------------------------------------------------------------
	// Quality scoring
	// -----------------------------------------------------------------------

	/**
	 * Score the generated content and return a ContentScore value object.
	 */
	private function score_content( int $post_id, string $content ): ContentScore {
		$text       = wp_strip_all_tags( $content );
		$word_count = str_word_count( $text );
		$issues     = [];

		// Length scoring (0–40 pts).
		$length_score = 0;
		if ( $word_count >= 2000 ) {
			$length_score = 40;
		} elseif ( $word_count >= 1500 ) {
			$length_score = 35;
		} elseif ( $word_count >= 1000 ) {
			$length_score = 28;
		} elseif ( $word_count >= 500 ) {
			$length_score = 18;
		} else {
			$length_score = (int) ( ( $word_count / 500 ) * 18 );
			$issues[]     = "Content too short: {$word_count} words (target: 1,000+)";
		}

		// Structure scoring (0–40 pts).
		$structure_score = 0;
		$h2_count        = preg_match_all( '/<h2[^>]*>/i', $content );
		$h3_count        = preg_match_all( '/<h3[^>]*>/i', $content );
		$p_count         = preg_match_all( '/<p[^>]*>/i', $content );
		$list_count      = preg_match_all( '/<[ou]l[^>]*>/i', $content );

		$structure_score += min( 15, $h2_count * 3 );       // H2 headings
		$structure_score += min( 10, $h3_count * 2 );       // H3 sub-headings
		$structure_score += min( 10, $p_count );             // Paragraphs
		$structure_score += min( 5, $list_count * 2 );       // Lists

		if ( $h2_count < 2 ) {
			$issues[] = 'Few H2 headings — content may lack structure.';
		}

		// Quality scoring (0–20 pts).
		$quality_score = 0;

		// Meta description present?
		$meta = get_post_meta( $post_id, 'pearblog_meta_description', true );
		if ( ! empty( $meta ) ) {
			$quality_score += 5;
		} else {
			$issues[] = 'Missing meta description.';
		}

		// Featured image?
		if ( has_post_thumbnail( $post_id ) ) {
			$quality_score += 5;
		} else {
			$issues[] = 'No featured image.';
		}

		// Internal links?
		$internal_links = (int) get_post_meta( $post_id, '_pearblog_internal_links_count', true );
		if ( $internal_links > 0 ) {
			$quality_score += 5;
		}

		// No AI clichés?
		$validator  = new ContentValidator();
		$validation = $validator->validate( $content, 'generic' );
		if ( empty( $validation['warnings'] ) ) {
			$quality_score += 5;
		} else {
			$issues = array_merge( $issues, array_slice( $validation['warnings'], 0, 3 ) );
		}

		return new ContentScore(
			$length_score,
			$structure_score,
			$quality_score,
			self::MIN_PUBLISH_SCORE,
			$issues,
		);
	}
}
