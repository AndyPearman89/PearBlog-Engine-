<?php
/**
 * Content pipeline – orchestrates the full content generation flow.
 *
 * Flow:
 *   Topic → Queue → Prompt → AI → Duplicate Check → SEO → Monetization
 *         → Internal Linking → Publish → Quality Scoring → Alerts
 *
 * @package PearBlogEngine\Pipeline
 */

declare(strict_types=1);

namespace PearBlogEngine\Pipeline;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\AI\ImageGenerator;
use PearBlogEngine\Content\DuplicateDetector;
use PearBlogEngine\Content\PromptBuilderFactory;
use PearBlogEngine\Content\QualityScorer;
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

	/** @var TenantContext */
	private TenantContext $context;

	/** @var AIClient */
	private AIClient $ai;

	/** @var ImageGenerator */
	private ImageGenerator $image_generator;

	/** @var SEOEngine */
	private SEOEngine $seo;

	/** @var bool */
	private bool $duplicate_check_enabled;

	public function __construct( TenantContext $context, ?AIClient $ai = null, ?ImageGenerator $image_generator = null ) {
		$this->context                 = $context;
		$this->ai                      = $ai ?? new AIClient();
		$this->image_generator         = $image_generator ?? new ImageGenerator();
		$this->seo                     = new SEOEngine();
		$this->duplicate_check_enabled = (bool) get_option( 'pearblog_duplicate_check_enabled', true );
	}

	/**
	 * Process the next topic from the queue for this tenant.
	 *
	 * @return array{post_id: int, topic: string, status: string}|null
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

		// Step 3 – Duplicate content check (before creating any post).
		if ( $this->duplicate_check_enabled ) {
			$dup_result = ( new DuplicateDetector() )->check( $raw_content );
			if ( $dup_result['is_duplicate'] ) {
				error_log( sprintf(
					'PearBlog Engine: Duplicate content detected for topic "%s" (similarity %.2f vs post %d "%s") – skipping.',
					$topic,
					$dup_result['similarity'],
					$dup_result['matched_post_id'],
					$dup_result['matched_title']
				) );

				/**
				 * Action: pearblog_pipeline_duplicate_skipped
				 *
				 * @param string $topic      Skipped topic.
				 * @param array  $dup_result Duplicate detection result.
				 */
				do_action( 'pearblog_pipeline_duplicate_skipped', $topic, $dup_result );

				return [
					'post_id' => 0,
					'topic'   => $topic,
					'status'  => 'duplicate_skipped',
				];
			}
		}

		// Step 4 – Create a draft post so we have a post ID for meta operations.
		$post_id = $this->create_draft_post( $topic, $raw_content );

		// Step 5 – Apply SEO metadata.
		$seo_data = $this->seo->apply( $post_id, $raw_content );

		// Step 6 – Inject monetisation.
		$monetizer     = new MonetizationEngine( $this->context->profile );
		$final_content = $monetizer->apply( $post_id, $seo_data['content'] );

		// Step 7 – Inject internal links.
		$final_content = ( new InternalLinker() )->apply( $final_content, $post_id );

		// Step 8 – Generate and attach featured image (AI-generated).
		$this->generate_featured_image( $post_id, $seo_data['title'] ?: $topic );

		// Step 9 – Store TF vector for future duplicate detection.
		( new \PearBlogEngine\Content\DuplicateDetector() )->index( $post_id, $final_content );

		// Step 10 – Update post with final content and publish.
		wp_update_post( [
			'ID'           => $post_id,
			'post_title'   => $seo_data['title'] ?: $topic,
			'post_content' => $final_content,
			'post_status'  => 'publish',
		] );

		// Step 11 – Score quality (non-blocking).
		try {
			( new QualityScorer() )->score( $post_id );
		} catch ( \Throwable $e ) {
			error_log( 'PearBlog Engine: Quality scoring failed – ' . $e->getMessage() );
		}

		/**
		 * Action: pearblog_pipeline_completed
		 *
		 * @param int           $post_id Post ID of the published article.
		 * @param string        $topic   Original topic.
		 * @param TenantContext $context Active tenant context.
		 */
		do_action( 'pearblog_pipeline_completed', $post_id, $topic, $this->context );

		return [
			'post_id' => $post_id,
			'topic'   => $topic,
			'status'  => 'published',
		];
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Insert a draft post so that we have an ID for subsequent meta updates.
	 *
	 * @param string $topic   Fallback title.
	 * @param string $content Initial content.
	 * @return int            New post ID.
	 * @throws \RuntimeException On WP_Error from wp_insert_post.
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
	 *
	 * @param int    $post_id Post ID.
	 * @param string $title   Post title for image generation.
	 * @return void
	 */
	private function generate_featured_image( int $post_id, string $title ): void {
		try {
			$attachment_id = $this->image_generator->generate_and_attach( $post_id, $title );

			if ( null !== $attachment_id ) {
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
}
