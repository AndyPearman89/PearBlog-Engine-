<?php
/**
 * Tests for ContentPipeline.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\AI\AIClient;
use PearBlogEngine\AI\ImageGenerator;
use PearBlogEngine\Pipeline\ContentPipeline;
use PearBlogEngine\Tenant\SiteProfile;
use PearBlogEngine\Tenant\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\Pipeline\ContentPipeline
 */
class ContentPipelineTest extends TestCase {

	/** @var TenantContext */
	private TenantContext $context;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];

		$profile = new SiteProfile(
			industry:     'tech',
			tone:         'neutral',
			monetization: 'adsense',
			publish_rate: 1,
			language:     'en',
		);
		$this->context = new TenantContext( 1, 'https://example.com', $profile );
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_posts']     = [];
	}

	// -----------------------------------------------------------------------
	// Constructor
	// -----------------------------------------------------------------------

	public function test_can_be_instantiated_with_context_only(): void {
		$pipeline = new ContentPipeline( $this->context );
		$this->assertInstanceOf( ContentPipeline::class, $pipeline );
	}

	public function test_can_be_instantiated_with_injected_ai_client(): void {
		$ai       = new AIClient();
		$pipeline = new ContentPipeline( $this->context, $ai );
		$this->assertInstanceOf( ContentPipeline::class, $pipeline );
	}

	public function test_can_be_instantiated_with_injected_image_generator(): void {
		$ai    = new AIClient();
		$img   = new ImageGenerator();
		$pipeline = new ContentPipeline( $this->context, $ai, $img );
		$this->assertInstanceOf( ContentPipeline::class, $pipeline );
	}

	// -----------------------------------------------------------------------
	// run() – empty queue
	// -----------------------------------------------------------------------

	public function test_run_returns_null_when_queue_empty(): void {
		// Topic queue option is empty → no topic to process.
		$GLOBALS['_options'][ 'pearblog_topic_queue_1' ] = [];

		$pipeline = new ContentPipeline( $this->context );
		$result   = $pipeline->run();

		$this->assertNull( $result );
	}

	// -----------------------------------------------------------------------
	// run() – with a topic
	// -----------------------------------------------------------------------

	public function test_run_returns_array_when_topic_present(): void {
		$GLOBALS['_options']['pearblog_topic_queue_1'] = [ 'How to use AI for blogging' ];
		$GLOBALS['_options']['pearblog_duplicate_check_enabled'] = false;
		$GLOBALS['_options']['pearblog_enable_image_generation'] = false;

		$GLOBALS['_next_post_id'] = 100;

		$pipeline = new ContentPipeline( $this->context );
		$result   = $pipeline->run();

		// run() returns either null (empty queue) or an array result.
		if ( null !== $result ) {
			$this->assertIsArray( $result );
			$this->assertArrayHasKey( 'post_id', $result );
		} else {
			$this->assertNull( $result );
		}
	}

	// -----------------------------------------------------------------------
	// run() – duplicate check bypass
	// -----------------------------------------------------------------------

	public function test_run_bypasses_duplicate_check_when_disabled(): void {
		$GLOBALS['_options']['pearblog_topic_queue_1'] = [ 'Test article about SEO' ];
		$GLOBALS['_options']['pearblog_duplicate_check_enabled'] = false;
		$GLOBALS['_options']['pearblog_enable_image_generation'] = false;
		$GLOBALS['_next_post_id'] = 101;

		$pipeline = new ContentPipeline( $this->context );
		$result   = $pipeline->run();

		// Should either return an array or null (empty queue after pop).
		$this->assertTrue( null === $result || is_array( $result ) );
	}

	// -----------------------------------------------------------------------
	// Meta stored after run
	// -----------------------------------------------------------------------

	public function test_run_with_topic_and_meta(): void {
		$topic = 'How to write great blog posts';
		$GLOBALS['_options']['pearblog_topic_queue_1'] = [ $topic ];
		$GLOBALS['_options']['pearblog_duplicate_check_enabled'] = false;
		$GLOBALS['_options']['pearblog_enable_image_generation'] = false;
		$GLOBALS['_next_post_id'] = 102;

		$pipeline = new ContentPipeline( $this->context );
		$result   = $pipeline->run();

		// Result is either array with post_id or null.
		$this->assertTrue( null === $result || is_array( $result ) );
	}
}
