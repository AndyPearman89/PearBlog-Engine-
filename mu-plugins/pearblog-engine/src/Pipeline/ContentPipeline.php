<?php
/**
 * Content pipeline – orchestrates the full content generation flow.
 *
 * Flow:
 *   Topic → Queue → Prompt → AI → SEO → Monetization → Publish
 *
 * @package PearBlogEngine\Pipeline
 */

declare(strict_types=1);

namespace PearBlogEngine\Pipeline;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\Content\PromptBuilder;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Monetization\MonetizationEngine;
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

	/** @var SEOEngine */
	private SEOEngine $seo;

	public function __construct( TenantContext $context, ?AIClient $ai = null ) {
		$this->context = $context;
		$this->ai      = $ai ?? new AIClient();
		$this->seo     = new SEOEngine();
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

		// Step 1 – Build prompt.
		$builder = new PromptBuilder( $this->context->profile );
		$prompt  = $builder->build( $topic );

		// Step 2 – Generate content via AI.
		$raw_content = $this->ai->generate( $prompt );

		// Step 3 – Create a draft post so we have a post ID for meta operations.
		$post_id = $this->create_draft_post( $topic, $raw_content );

		// Step 4 – Apply SEO metadata.
		$seo_data = $this->seo->apply( $post_id, $raw_content );

		// Step 5 – Inject monetisation.
		$monetizer       = new MonetizationEngine( $this->context->profile );
		$final_content   = $monetizer->apply( $post_id, $seo_data['content'] );

		// Step 6 – Update post with final content and publish.
		wp_update_post( [
			'ID'           => $post_id,
			'post_title'   => $seo_data['title'] ?: $topic,
			'post_content' => $final_content,
			'post_status'  => 'publish',
		] );

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
}
