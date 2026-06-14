<?php

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\VideoScriptBuilder;

/**
 * @covers \PearBlogEngine\AI\VideoScriptBuilder
 */
class VideoScriptBuilderTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_options']          = [];
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_actions']          = [];
		$GLOBALS['_action_handlers']  = [];
		$GLOBALS['_current_user_can'] = false;
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_platform_constants_defined(): void {
		$this->assertSame( 'youtube', VideoScriptBuilder::PLATFORM_YOUTUBE );
		$this->assertSame( 'tiktok',  VideoScriptBuilder::PLATFORM_TIKTOK );
		$this->assertSame( 'shorts',  VideoScriptBuilder::PLATFORM_SHORTS );
	}

	public function test_platform_constants_are_unique(): void {
		$platforms = [
			VideoScriptBuilder::PLATFORM_YOUTUBE,
			VideoScriptBuilder::PLATFORM_TIKTOK,
			VideoScriptBuilder::PLATFORM_SHORTS,
		];
		$this->assertCount( 3, array_unique( $platforms ) );
	}

	// -----------------------------------------------------------------------
	// get_scripts()
	// -----------------------------------------------------------------------

	public function test_get_scripts_returns_empty_array_when_no_scripts_stored(): void {
		$builder = new VideoScriptBuilder();
		$result  = $builder->get_scripts( 99 );

		$this->assertSame( [], $result );
	}

	public function test_get_scripts_returns_youtube_script(): void {
		$GLOBALS['_post_meta'][1]['pearblog_video_script_youtube'] = [ 'Script text here.' ];

		$builder = new VideoScriptBuilder();
		$result  = $builder->get_scripts( 1 );

		$this->assertArrayHasKey( 'youtube', $result );
		$this->assertSame( 'Script text here.', $result['youtube'] );
	}

	public function test_get_scripts_returns_tiktok_script(): void {
		$GLOBALS['_post_meta'][2]['pearblog_video_script_tiktok'] = [ 'TikTok script.' ];

		$builder = new VideoScriptBuilder();
		$result  = $builder->get_scripts( 2 );

		$this->assertArrayHasKey( 'tiktok', $result );
		$this->assertSame( 'TikTok script.', $result['tiktok'] );
	}

	public function test_get_scripts_returns_shorts_script(): void {
		$GLOBALS['_post_meta'][3]['pearblog_video_script_shorts'] = [ 'Shorts script.' ];

		$builder = new VideoScriptBuilder();
		$result  = $builder->get_scripts( 3 );

		$this->assertArrayHasKey( 'shorts', $result );
		$this->assertSame( 'Shorts script.', $result['shorts'] );
	}

	public function test_get_scripts_returns_all_platforms_when_all_stored(): void {
		$GLOBALS['_post_meta'][5] = [
			'pearblog_video_script_youtube' => [ 'YT script.' ],
			'pearblog_video_script_tiktok'  => [ 'TT script.' ],
			'pearblog_video_script_shorts'  => [ 'Shorts script.' ],
		];

		$builder = new VideoScriptBuilder();
		$result  = $builder->get_scripts( 5 );

		$this->assertCount( 3, $result );
		$this->assertArrayHasKey( 'youtube', $result );
		$this->assertArrayHasKey( 'tiktok',  $result );
		$this->assertArrayHasKey( 'shorts',  $result );
	}

	public function test_get_scripts_skips_empty_string_values(): void {
		$GLOBALS['_post_meta'][6] = [
			'pearblog_video_script_youtube' => [ '' ],
			'pearblog_video_script_tiktok'  => [ 'TT script.' ],
		];

		$builder = new VideoScriptBuilder();
		$result  = $builder->get_scripts( 6 );

		$this->assertArrayNotHasKey( 'youtube', $result );
		$this->assertArrayHasKey( 'tiktok', $result );
	}

	public function test_get_scripts_returns_only_known_platforms(): void {
		$GLOBALS['_post_meta'][7] = [
			'pearblog_video_script_youtube' => [ 'YT.' ],
		];

		$builder  = new VideoScriptBuilder();
		$result   = $builder->get_scripts( 7 );
		$keys     = array_keys( $result );

		foreach ( $keys as $key ) {
			$this->assertContains( $key, [ 'youtube', 'tiktok', 'shorts' ] );
		}
	}

	// -----------------------------------------------------------------------
	// editor_permission()
	// -----------------------------------------------------------------------

	public function test_editor_permission_returns_false_when_no_capability(): void {
		$GLOBALS['_current_user_can'] = false;
		$builder = new VideoScriptBuilder();
		$this->assertFalse( $builder->editor_permission() );
	}

	public function test_editor_permission_returns_true_when_user_has_capability(): void {
		$GLOBALS['_current_user_can'] = true;
		$builder = new VideoScriptBuilder();
		$this->assertTrue( $builder->editor_permission() );
	}

	// -----------------------------------------------------------------------
	// rest_get_scripts()
	// -----------------------------------------------------------------------

	public function test_rest_get_scripts_returns_response_with_post_id(): void {
		$GLOBALS['_post_meta'][10] = [
			'pearblog_video_script_youtube' => [ 'YT script.' ],
		];

		$request = new \WP_REST_Request( 'GET', '', [ 'post_id' => 10 ] );
		$builder  = new VideoScriptBuilder();
		$response = $builder->rest_get_scripts( $request );

		$data = $response->get_data();
		$this->assertSame( 10, $data['post_id'] );
	}

	public function test_rest_get_scripts_returns_scripts_array(): void {
		$GLOBALS['_post_meta'][11] = [
			'pearblog_video_script_tiktok' => [ 'TT.' ],
		];

		$request  = new \WP_REST_Request( 'GET', '', [ 'post_id' => 11 ] );
		$builder  = new VideoScriptBuilder();
		$response = $builder->rest_get_scripts( $request );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'scripts', $data );
		$this->assertIsArray( $data['scripts'] );
	}

	public function test_rest_get_scripts_returns_200_status(): void {
		$request  = new \WP_REST_Request( 'GET', '', [ 'post_id' => 12 ] );
		$builder  = new VideoScriptBuilder();
		$response = $builder->rest_get_scripts( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_rest_get_scripts_returns_empty_scripts_for_no_meta(): void {
		$request  = new \WP_REST_Request( 'GET', '', [ 'post_id' => 999 ] );
		$builder  = new VideoScriptBuilder();
		$response = $builder->rest_get_scripts( $request );

		$data = $response->get_data();
		$this->assertSame( [], $data['scripts'] );
	}
}
