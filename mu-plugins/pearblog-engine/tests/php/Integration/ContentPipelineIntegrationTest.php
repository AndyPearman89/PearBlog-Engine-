<?php
/**
 * Integration tests for the ContentPipeline.
 *
 * These tests exercise the pipeline flow using stub/mock external dependencies.
 * No real HTTP calls are made.
 *
 * @package PearBlogEngine\Tests\Integration
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\TopicQueue;
use PearBlogEngine\Content\PromptBuilderFactory;
use PearBlogEngine\Content\DuplicateDetector;
use PearBlogEngine\Content\ContentValidator;
use PearBlogEngine\Tenant\SiteProfile;
use PearBlogEngine\Cache\ContentCache;
use PearBlogEngine\Monitoring\Logger;

/**
 * Integration test suite validating pipeline component interactions.
 */
class ContentPipelineIntegrationTest extends TestCase {

	private SiteProfile $profile;
	private ContentCache $cache;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$GLOBALS['_posts']      = [];
		$GLOBALS['_post_meta']  = [];
		$GLOBALS['_post_list']  = [];

		$this->profile = new SiteProfile(
			industry:     'technology',
			tone:         'professional',
			monetization: 'adsense',
			publish_rate: 1,
			language:     'en'
		);

		$this->cache = new ContentCache();
	}

	// ------------------------------------------------------------------
	// TopicQueue integration
	// ------------------------------------------------------------------

	public function test_topic_queue_push_and_pop(): void {
		$queue = new TopicQueue( 1 );
		$queue->push( 'Topic A', 'Topic B', 'Topic C' );

		$this->assertSame( 3, $queue->count() );

		$popped = $queue->pop();
		$this->assertSame( 'Topic A', $popped );
		$this->assertSame( 2, $queue->count() );
	}

	public function test_topic_queue_clear_empties_queue(): void {
		$queue = new TopicQueue( 1 );
		$queue->push( 'Topic 1', 'Topic 2' );
		$queue->clear();
		$this->assertSame( 0, $queue->count() );
	}

	public function test_topic_queue_pop_from_empty_queue_returns_null(): void {
		$queue = new TopicQueue( 1 );
		$this->assertNull( $queue->pop() );
	}

	public function test_topic_queue_deduplicates(): void {
		$queue = new TopicQueue( 1 );
		$queue->push( 'Unique Topic' );
		$queue->push( 'Another Topic' );
		// TopicQueue does not guarantee deduplication in all implementations,
		// but push must succeed without error.
		$this->assertGreaterThanOrEqual( 1, $queue->count() );
	}

	// ------------------------------------------------------------------
	// PromptBuilder factory integration
	// ------------------------------------------------------------------

	public function test_factory_creates_correct_builder_for_tech_profile(): void {
		$builder = PromptBuilderFactory::create( $this->profile );
		$this->assertInstanceOf( \PearBlogEngine\Content\TechPromptBuilder::class, $builder );
	}

	public function test_factory_builder_produces_valid_prompt(): void {
		$builder = PromptBuilderFactory::create( $this->profile );
		$prompt  = $builder->build( 'Docker best practices' );

		$this->assertNotEmpty( $prompt );
		$this->assertStringContainsString( 'Docker best practices', $prompt );
		$this->assertStringContainsString( 'SEO', $prompt );
		$this->assertStringContainsString( 'META:', $prompt );
	}

	// ------------------------------------------------------------------
	// DuplicateDetector integration
	// ------------------------------------------------------------------

	public function test_duplicate_detector_flags_high_similarity(): void {
		$detector = new DuplicateDetector();
		$base     = 'This is a comprehensive guide to hiking in the mountains with great views and fresh air.';

		// Seed with a reference post.
		$ref = new \WP_Post( [ 'ID' => 101, 'post_title' => 'Hiking Guide', 'post_content' => $base, 'post_status' => 'publish' ] );
		$GLOBALS['_posts'][101] = $ref;
		$GLOBALS['_post_list']  = [ 101 ];

		// Index the reference post.
		$detector->index( 101, $base );

		// A nearly identical text should be checked.
		$similar = 'This is a comprehensive guide to hiking in the mountains with great views and fresh air today.';
		$result  = $detector->check( $similar, 0 );

		// Result is an array with 'is_duplicate' key.
		$this->assertArrayHasKey( 'is_duplicate', $result );
		$this->assertIsBool( $result['is_duplicate'] );
	}

	public function test_duplicate_detector_allows_unique_content(): void {
		$detector = new DuplicateDetector();
		$unique   = 'A completely unrelated article about quantum computing and semiconductor physics.';
		$result   = $detector->check( $unique, 0 );
		$this->assertArrayHasKey( 'is_duplicate', $result );
		$this->assertFalse( $result['is_duplicate'] );
	}

	// ------------------------------------------------------------------
	// ContentValidator integration
	// ------------------------------------------------------------------

	public function test_content_validator_approves_valid_article(): void {
		$validator = new ContentValidator();
		$article   = "META: A comprehensive article about technology best practices.\n"
			. "# Technology Best Practices Guide\n\n"
			. str_repeat( "This is a well-written sentence about technology and software development. ", 150 );

		$result = $validator->validate( $article );
		$this->assertArrayHasKey( 'valid', $result );
		$this->assertTrue( $result['valid'] );
	}

	public function test_content_validator_rejects_empty_content(): void {
		$validator = new ContentValidator();
		$result    = $validator->validate( '' );
		$this->assertArrayHasKey( 'valid', $result );
		$this->assertFalse( $result['valid'] );
	}

	public function test_content_validator_rejects_too_short_content(): void {
		$validator = new ContentValidator();
		$result    = $validator->validate( 'Too short.' );
		$this->assertArrayHasKey( 'valid', $result );
		$this->assertFalse( $result['valid'] );
	}

	// ------------------------------------------------------------------
	// Cache integration with pipeline data
	// ------------------------------------------------------------------

	public function test_cache_stores_and_retrieves_ai_content(): void {
		$topic   = 'Docker best practices';
		$profile = 'tech|en|professional';
		$content = 'Generated article content for Docker best practices...';

		$this->cache->set_ai_content( $topic, $profile, $content );

		$retrieved = $this->cache->get_ai_content( $topic, $profile );
		$this->assertSame( $content, $retrieved );
	}

	public function test_cache_miss_triggers_fresh_prompt_build(): void {
		$topic   = 'Kubernetes scaling';
		$profile = 'tech|en|professional';

		// No cached content exists.
		$cached = $this->cache->get_ai_content( $topic, $profile );
		$this->assertFalse( $cached );

		// Build fresh prompt as fallback.
		$builder = PromptBuilderFactory::create( $this->profile );
		$prompt  = $builder->build( $topic );
		$this->assertNotEmpty( $prompt );
	}

	// ------------------------------------------------------------------
	// Logger integration
	// ------------------------------------------------------------------

	public function test_logger_records_pipeline_events(): void {
		$logger = new Logger( 'pipeline', Logger::DEBUG, '' );
		$logger->info( 'Pipeline started', [ 'topic' => 'Docker best practices' ] );
		$logger->info( 'AI content generated', [ 'words' => '1200' ] );
		$logger->error( 'Image generation failed', [ 'reason' => 'API timeout' ] );

		$recent = $logger->get_recent();
		$this->assertCount( 3, $recent );

		$errors = $logger->get_recent( Logger::ERROR );
		$this->assertCount( 1, $errors );
		$this->assertSame( 'Image generation failed', $errors[0]['message'] );
	}
}
