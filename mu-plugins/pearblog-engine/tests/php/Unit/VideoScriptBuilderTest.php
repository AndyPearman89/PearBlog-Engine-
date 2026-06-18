<?php
/**
 * Unit tests for VideoScriptBuilder.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\VideoScriptBuilder;

class VideoScriptBuilderTest extends TestCase {

	private VideoScriptBuilder $builder;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_posts']            = [];
		$GLOBALS['_actions']          = [];
		$GLOBALS['_current_user_can'] = true;
		$this->builder = new VideoScriptBuilder();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_platform_youtube_constant(): void {
		$this->assertSame( 'youtube', VideoScriptBuilder::PLATFORM_YOUTUBE );
	}

	public function test_platform_tiktok_constant(): void {
		$this->assertSame( 'tiktok', VideoScriptBuilder::PLATFORM_TIKTOK );
	}

	public function test_platform_shorts_constant(): void {
		$this->assertSame( 'shorts', VideoScriptBuilder::PLATFORM_SHORTS );
	}

	// -----------------------------------------------------------------------
	// generate — throws for missing post
	// -----------------------------------------------------------------------

	public function test_generate_throws_for_missing_post(): void {
		$this->expectException( \RuntimeException::class );
		$this->builder->generate( 99999 );
	}

	public function test_generate_throws_exception_with_post_id(): void {
		try {
			$this->builder->generate( 12345 );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( '12345', $e->getMessage() );
		}
	}

	// -----------------------------------------------------------------------
	// get_scripts
	// -----------------------------------------------------------------------

	public function test_get_scripts_returns_empty_array_when_no_meta(): void {
		$scripts = $this->builder->get_scripts( 1 );
		$this->assertIsArray( $scripts );
		$this->assertEmpty( $scripts );
	}

	public function test_get_scripts_returns_stored_youtube_script(): void {
		$GLOBALS['_post_meta'][1]['pearblog_video_script_youtube'] = [ 'My youtube script' ];
		$scripts = $this->builder->get_scripts( 1 );
		$this->assertArrayHasKey( 'youtube', $scripts );
		$this->assertSame( 'My youtube script', $scripts['youtube'] );
	}

	public function test_get_scripts_returns_stored_tiktok_script(): void {
		$GLOBALS['_post_meta'][1]['pearblog_video_script_tiktok'] = [ 'My tiktok script' ];
		$scripts = $this->builder->get_scripts( 1 );
		$this->assertArrayHasKey( 'tiktok', $scripts );
	}

	public function test_get_scripts_returns_stored_shorts_script(): void {
		$GLOBALS['_post_meta'][1]['pearblog_video_script_shorts'] = [ 'My shorts script' ];
		$scripts = $this->builder->get_scripts( 1 );
		$this->assertArrayHasKey( 'shorts', $scripts );
	}

	public function test_get_scripts_omits_empty_platforms(): void {
		$GLOBALS['_post_meta'][1]['pearblog_video_script_youtube'] = [ 'script' ];
		// tiktok and shorts not set → only youtube returned.
		$scripts = $this->builder->get_scripts( 1 );
		$this->assertCount( 1, $scripts );
	}

	// -----------------------------------------------------------------------
	// register
	// -----------------------------------------------------------------------

	public function test_register_adds_rest_api_init(): void {
		$this->builder->register();
		$this->assertTrue( isset( $GLOBALS['_actions']['rest_api_init'] ) );
	}

	// -----------------------------------------------------------------------
	// editor_permission
	// -----------------------------------------------------------------------

	public function test_editor_permission_returns_true_for_editor(): void {
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( $this->builder->editor_permission() );
	}

	public function test_editor_permission_returns_false_for_non_editor(): void {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( $this->builder->editor_permission() );
	}

	// -----------------------------------------------------------------------
	// REST get_scripts
	// -----------------------------------------------------------------------

	public function test_rest_get_scripts_returns_response_object(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'post_id', 1 );
		$result = $this->builder->rest_get_scripts( $req );
		$this->assertInstanceOf( \WP_REST_Response::class, $result );
	}

	public function test_rest_get_scripts_returns_200_status(): void {
		$req = new \WP_REST_Request();
		$req->set_param( 'post_id', 1 );
		$result = $this->builder->rest_get_scripts( $req );
		$this->assertSame( 200, $result->get_status() );
	}
}
