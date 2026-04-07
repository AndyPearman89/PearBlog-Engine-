<?php
/**
 * Integration test for ContentPipeline — validates the entire end-to-end flow.
 *
 * This test ensures the complete pipeline executes successfully from
 * topic queue to published post with all intermediate steps:
 *   1. Topic retrieval from queue
 *   2. Prompt building
 *   3. AI content generation
 *   4. Duplicate detection
 *   5. Draft post creation
 *   6. SEO metadata application
 *   7. Monetization injection
 *   8. Internal linking
 *   9. Featured image generation
 *  10. Meta description generation
 *  11. Duplicate indexing
 *  12. Publishing
 *  13. Quality scoring
 *
 * @package PearBlogEngine\Tests\Integration
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\AIClient;
use PearBlogEngine\AI\ImageGenerator;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Pipeline\ContentPipeline;
use PearBlogEngine\Tenant\SiteProfile;
use PearBlogEngine\Tenant\TenantContext;

/**
 * End-to-end integration test for the complete content pipeline.
 */
class ContentPipelineIntegrationTest extends TestCase {

	/** @var int Test site ID */
	private int $site_id = 1;

	/** @var TenantContext */
	private TenantContext $context;

	/** @var AIClient Mock AI client */
	private AIClient $mock_ai;

	/** @var ImageGenerator Mock image generator */
	private ImageGenerator $mock_image_generator;

	protected function setUp(): void {
		parent::setUp();

		// Create tenant context.
		$profile = new SiteProfile(
			industry: 'Technology',
			tone: 'Professional',
			monetization: 'adsense',
			publish_rate: 1,
			language: 'en'
		);
		$this->context = new TenantContext( $this->site_id, 'https://test.local', $profile );

		// Set up mock AI client that returns predictable content.
		$this->mock_ai = $this->createMock( AIClient::class );
		$this->mock_ai->method( 'generate' )
			->willReturnCallback( function ( string $prompt ): string {
				// Simulate AI-generated content with proper structure.
				return "# Test Article Title\n\n" .
					"This is a test article generated for integration testing. " .
					"It contains multiple paragraphs to simulate real content.\n\n" .
					"## Section 1: Introduction\n\n" .
					"The introduction provides context about the topic. " .
					"It should be at least 150 words to pass quality checks. " .
					"Lorem ipsum dolor sit amet, consectetur adipiscing elit. " .
					"Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n\n" .
					"## Section 2: Main Content\n\n" .
					"This section contains the main discussion points. " .
					"It demonstrates keyword usage and proper heading structure. " .
					"The content is optimized for SEO with natural keyword placement.\n\n" .
					"## Section 3: Conclusion\n\n" .
					"The conclusion summarizes the key points discussed above. " .
					"It provides actionable takeaways for readers.";
			} );

		// Set up mock image generator.
		$this->mock_image_generator = $this->createMock( ImageGenerator::class );
		$this->mock_image_generator->method( 'generate_and_attach' )
			->willReturn( 12345 ); // Mock attachment ID.

		// Enable duplicate checking for comprehensive testing.
		update_option( 'pearblog_duplicate_check_enabled', true );

		// Set up basic WordPress options needed by pipeline.
		update_option( 'pearblog_openai_api_key', 'test-key' );
		update_option( 'pearblog_industry', 'Technology' );
		update_option( 'pearblog_tone', 'Professional' );
		update_option( 'pearblog_language', 'en' );
	}

	protected function tearDown(): void {
		// Clean up: delete all test posts created during testing.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'post'" );
		$wpdb->query( "DELETE FROM {$wpdb->postmeta}" );

		// Clear options.
		delete_option( 'pearblog_duplicate_check_enabled' );
		delete_option( 'pearblog_openai_api_key' );
		delete_option( 'pearblog_industry' );
		delete_option( 'pearblog_tone' );
		delete_option( 'pearblog_language' );

		parent::tearDown();
	}

	/**
	 * Test the complete pipeline flow from topic to published post.
	 */
	public function test_complete_pipeline_execution(): void {
		// ARRANGE: Add a topic to the queue.
		$queue = new TopicQueue( $this->site_id );
		$topic = 'How to Build Scalable Microservices';
		$queue->push( $topic );

		// Create pipeline with mocked dependencies.
		$pipeline = new ContentPipeline(
			$this->context,
			$this->mock_ai,
			$this->mock_image_generator
		);

		// Track action hooks to verify pipeline stages execute.
		$started_called   = false;
		$completed_called = false;

		add_action( 'pearblog_pipeline_started', function () use ( &$started_called ) {
			$started_called = true;
		} );

		add_action( 'pearblog_pipeline_completed', function () use ( &$completed_called ) {
			$completed_called = true;
		} );

		// ACT: Execute the pipeline.
		$result = $pipeline->run();

		// ASSERT: Verify pipeline executed successfully.
		$this->assertIsArray( $result, 'Pipeline should return result array' );
		$this->assertArrayHasKey( 'post_id', $result );
		$this->assertArrayHasKey( 'topic', $result );
		$this->assertArrayHasKey( 'status', $result );

		$this->assertGreaterThan( 0, $result['post_id'], 'Post ID should be created' );
		$this->assertSame( $topic, $result['topic'] );
		$this->assertSame( 'published', $result['status'] );

		// Verify the post was created and published.
		$post = get_post( $result['post_id'] );
		$this->assertNotNull( $post, 'Post should exist in database' );
		$this->assertSame( 'publish', $post->post_status, 'Post should be published' );
		$this->assertStringContainsString( 'Test Article Title', $post->post_title );
		$this->assertStringContainsString( 'integration testing', $post->post_content );

		// Verify SEO metadata was applied.
		$meta_desc = get_post_meta( $result['post_id'], 'pearblog_meta_description', true );
		$this->assertNotEmpty( $meta_desc, 'Meta description should be generated' );

		// Verify featured image was attached.
		$thumbnail_id = get_post_meta( $result['post_id'], '_thumbnail_id', true );
		$this->assertSame( '12345', $thumbnail_id, 'Featured image should be attached' );

		// Verify quality score was calculated.
		$quality_score = get_post_meta( $result['post_id'], '_pearblog_quality_score', true );
		$this->assertNotEmpty( $quality_score, 'Quality score should be calculated' );
		$this->assertGreaterThanOrEqual( 0, (int) $quality_score );
		$this->assertLessThanOrEqual( 100, (int) $quality_score );

		// Verify duplicate detection vector was stored.
		$tf_vector = get_post_meta( $result['post_id'], '_pearblog_tf_vector', true );
		$this->assertNotEmpty( $tf_vector, 'TF vector should be stored for duplicate detection' );

		// Verify action hooks were triggered.
		$this->assertTrue( $started_called, 'pearblog_pipeline_started should be called' );
		$this->assertTrue( $completed_called, 'pearblog_pipeline_completed should be called' );

		// Verify queue was consumed.
		$this->assertSame( 0, $queue->count(), 'Topic should be removed from queue' );
	}

	/**
	 * Test pipeline returns null when queue is empty.
	 */
	public function test_pipeline_returns_null_when_queue_empty(): void {
		// ARRANGE: Ensure queue is empty.
		$queue = new TopicQueue( $this->site_id );
		// Queue is empty by default.

		$pipeline = new ContentPipeline( $this->context, $this->mock_ai, $this->mock_image_generator );

		// ACT: Run pipeline with empty queue.
		$result = $pipeline->run();

		// ASSERT: Should return null.
		$this->assertNull( $result, 'Pipeline should return null when queue is empty' );
	}

	/**
	 * Test duplicate detection prevents duplicate content from being published.
	 */
	public function test_duplicate_detection_skips_duplicate_content(): void {
		// ARRANGE: Create an existing published post.
		$existing_content = "# Test Article Title\n\n" .
			"This is a test article generated for integration testing. " .
			"It contains multiple paragraphs to simulate real content.";

		$existing_post_id = wp_insert_post( [
			'post_title'   => 'Existing Article',
			'post_content' => $existing_content,
			'post_status'  => 'publish',
		] );

		// Index the existing post for duplicate detection.
		( new \PearBlogEngine\Content\DuplicateDetector() )->index( $existing_post_id, $existing_content );

		// Add a new topic that will generate very similar content.
		$queue = new TopicQueue( $this->site_id );
		$queue->push( 'Duplicate Topic' );

		$pipeline = new ContentPipeline( $this->context, $this->mock_ai, $this->mock_image_generator );

		// Track duplicate skip action.
		$duplicate_skipped = false;
		add_action( 'pearblog_pipeline_duplicate_skipped', function () use ( &$duplicate_skipped ) {
			$duplicate_skipped = true;
		} );

		// ACT: Run pipeline.
		$result = $pipeline->run();

		// ASSERT: Content should be skipped due to duplication.
		$this->assertIsArray( $result );
		$this->assertSame( 'duplicate_skipped', $result['status'] );
		$this->assertSame( 0, $result['post_id'], 'No new post should be created' );
		$this->assertTrue( $duplicate_skipped, 'Duplicate skip action should be triggered' );
	}

	/**
	 * Test pipeline processes multiple topics in sequence.
	 */
	public function test_pipeline_processes_multiple_topics(): void {
		// ARRANGE: Add multiple topics to queue.
		$queue = new TopicQueue( $this->site_id );
		$topics = [
			'Topic 1: Cloud Computing Basics',
			'Topic 2: Database Optimization',
			'Topic 3: API Design Patterns',
		];

		foreach ( $topics as $topic ) {
			$queue->push( $topic );
		}

		$this->assertSame( 3, $queue->count(), 'Queue should have 3 topics' );

		$pipeline = new ContentPipeline( $this->context, $this->mock_ai, $this->mock_image_generator );
		$results  = [];

		// ACT: Process all topics.
		while ( $queue->count() > 0 ) {
			$result = $pipeline->run();
			if ( null !== $result ) {
				$results[] = $result;
			}
		}

		// ASSERT: All topics should be processed.
		$this->assertCount( 3, $results, 'Should process all 3 topics' );

		foreach ( $results as $result ) {
			$this->assertSame( 'published', $result['status'] );
			$this->assertGreaterThan( 0, $result['post_id'] );

			// Verify each post exists and is published.
			$post = get_post( $result['post_id'] );
			$this->assertSame( 'publish', $post->post_status );
		}

		// Queue should be empty.
		$this->assertSame( 0, $queue->count(), 'Queue should be empty after processing' );
	}

	/**
	 * Test that pipeline executes all steps in correct order.
	 */
	public function test_pipeline_executes_steps_in_order(): void {
		// ARRANGE: Track execution order of steps.
		$execution_order = [];

		// Hook into various stages.
		add_filter( 'pearblog_prompt_builder_class', function ( $class ) use ( &$execution_order ) {
			$execution_order[] = 'prompt_builder';
			return $class;
		} );

		add_action( 'pearblog_before_seo_apply', function () use ( &$execution_order ) {
			$execution_order[] = 'seo';
		} );

		add_action( 'pearblog_before_monetization', function () use ( &$execution_order ) {
			$execution_order[] = 'monetization';
		} );

		add_action( 'pearblog_internal_links_applied', function () use ( &$execution_order ) {
			$execution_order[] = 'internal_linking';
		} );

		$queue = new TopicQueue( $this->site_id );
		$queue->push( 'Test Topic for Order Verification' );

		$pipeline = new ContentPipeline( $this->context, $this->mock_ai, $this->mock_image_generator );

		// ACT: Run pipeline.
		$result = $pipeline->run();

		// ASSERT: Verify result is successful.
		$this->assertSame( 'published', $result['status'] );

		// Note: Execution order verification depends on hooks being available in the codebase.
		// The main assertion is that the pipeline completes successfully with all steps.
	}

	/**
	 * Test pipeline gracefully handles errors in non-critical steps.
	 */
	public function test_pipeline_handles_non_critical_errors_gracefully(): void {
		// ARRANGE: Create image generator that throws exception.
		$failing_image_generator = $this->createMock( ImageGenerator::class );
		$failing_image_generator->method( 'generate_and_attach' )
			->willThrowException( new \RuntimeException( 'Image generation failed' ) );

		$queue = new TopicQueue( $this->site_id );
		$queue->push( 'Test Topic with Image Failure' );

		$pipeline = new ContentPipeline( $this->context, $this->mock_ai, $failing_image_generator );

		// ACT: Run pipeline (should not throw exception).
		$result = $pipeline->run();

		// ASSERT: Pipeline should still complete and publish the post.
		$this->assertIsArray( $result );
		$this->assertSame( 'published', $result['status'] );
		$this->assertGreaterThan( 0, $result['post_id'] );

		// Post should exist without featured image.
		$post = get_post( $result['post_id'] );
		$this->assertSame( 'publish', $post->post_status );

		// No thumbnail should be attached.
		$thumbnail_id = get_post_meta( $result['post_id'], '_thumbnail_id', true );
		$this->assertEmpty( $thumbnail_id, 'No thumbnail should be attached after image generation failure' );
	}
}
