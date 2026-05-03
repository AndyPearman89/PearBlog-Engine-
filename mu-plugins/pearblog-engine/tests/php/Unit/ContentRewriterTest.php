<?php
/**
 * Unit tests for ContentRewriter.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\ContentRewriter;

class ContentRewriterTest extends TestCase {

	private ContentRewriter $rewriter;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [ 'pearblog_rewrite_enabled' => true ];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_actions']   = [];
		$GLOBALS['_posts']     = [];

		// Stub AI client that returns predictable output.
		$ai = $this->create_ai_stub( 'Rewritten content.' );
		$this->rewriter = new ContentRewriter( $ai );
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['_current_user_can'] );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_defaults_to_true(): void {
		$GLOBALS['_options'] = [];
		$r = new ContentRewriter( $this->create_ai_stub( '' ) );
		$this->assertTrue( $r->is_enabled() );
	}

	public function test_is_enabled_respects_option(): void {
		$GLOBALS['_options']['pearblog_rewrite_enabled'] = false;
		$r = new ContentRewriter( $this->create_ai_stub( '' ) );
		$this->assertFalse( $r->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// register()
	// -----------------------------------------------------------------------

	public function test_register_adds_rest_api_init_action(): void {
		$this->rewriter->register();
		$this->assertNotEmpty( $GLOBALS['_actions']['rest_api_init'] ?? [] );
	}

	public function test_register_disabled_skips_hooks(): void {
		$GLOBALS['_options']['pearblog_rewrite_enabled'] = false;
		$r = new ContentRewriter( $this->create_ai_stub( '' ) );
		$r->register();
		$this->assertEmpty( $GLOBALS['_actions']['rest_api_init'] ?? [] );
	}

	// -----------------------------------------------------------------------
	// full_rewrite
	// -----------------------------------------------------------------------

	public function test_full_rewrite_returns_success_array(): void {
		$post                  = $this->make_post( 1 );
		$GLOBALS['_posts'][1]  = $post;

		$result = $this->rewriter->full_rewrite( $post );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 1, $result['post_id'] );
		$this->assertSame( ContentRewriter::MODE_FULL, $result['mode'] );
	}

	public function test_full_rewrite_returns_error_on_empty_ai(): void {
		$ai      = $this->create_ai_stub( '' );
		$rewriter = new ContentRewriter( $ai );
		$post    = $this->make_post( 2 );
		$result  = $rewriter->full_rewrite( $post );
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	// -----------------------------------------------------------------------
	// refresh_rewrite
	// -----------------------------------------------------------------------

	public function test_refresh_rewrite_returns_success_array(): void {
		$post                  = $this->make_post( 3, 'Old content here.' );
		$GLOBALS['_posts'][3]  = $post;

		$result = $this->rewriter->refresh_rewrite( $post );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( ContentRewriter::MODE_REFRESH, $result['mode'] );
	}

	public function test_refresh_rewrite_applies_to_post_meta(): void {
		$post                  = $this->make_post( 4 );
		$GLOBALS['_posts'][4]  = $post;

		$this->rewriter->refresh_rewrite( $post );

		$last = get_post_meta( 4, '_pearblog_last_rewrite', true );
		$this->assertGreaterThan( 0, (int) $last );
	}

	// -----------------------------------------------------------------------
	// rewrite_section
	// -----------------------------------------------------------------------

	public function test_rewrite_section_error_when_heading_not_found(): void {
		$post = $this->make_post( 5, '<p>No headings here.</p>' );
		$result = $this->rewriter->rewrite_section( $post, 'Missing Heading' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'section_not_found', $result->get_error_code() );
	}

	public function test_rewrite_section_returns_success_when_heading_found(): void {
		$content = '<h2>Introduction</h2><p>Some text about the intro.</p>';
		$post    = $this->make_post( 6, $content );
		$GLOBALS['_posts'][6] = $post;

		$result  = $this->rewriter->rewrite_section( $post, 'Introduction' );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( ContentRewriter::MODE_SECTION, $result['mode'] );
	}

	// -----------------------------------------------------------------------
	// apply_rewrite / log
	// -----------------------------------------------------------------------

	public function test_apply_rewrite_appends_log_entry(): void {
		$post = $this->make_post( 7 );

		$this->rewriter->apply_rewrite( $post, 'New content.', ContentRewriter::MODE_FULL );
		$this->rewriter->apply_rewrite( $post, 'Even newer content.', ContentRewriter::MODE_REFRESH );

		$log = $this->rewriter->get_log( 7 );
		$this->assertCount( 2, $log );
	}

	public function test_log_is_ordered_newest_first(): void {
		$post = $this->make_post( 8 );

		$this->rewriter->apply_rewrite( $post, 'First.', ContentRewriter::MODE_FULL );
		$this->rewriter->apply_rewrite( $post, 'Second.', ContentRewriter::MODE_REFRESH );

		$log = $this->rewriter->get_log( 8 );
		// Newest first: second entry should be at index 0.
		$this->assertSame( ContentRewriter::MODE_REFRESH, $log[0]['mode'] );
		$this->assertSame( ContentRewriter::MODE_FULL, $log[1]['mode'] );
	}

	public function test_log_entry_has_required_fields(): void {
		$post = $this->make_post( 9 );
		$this->rewriter->apply_rewrite( $post, 'New content.', ContentRewriter::MODE_REFRESH );

		$log   = $this->rewriter->get_log( 9 );
		$entry = $log[0];

		$this->assertArrayHasKey( 'mode', $entry );
		$this->assertArrayHasKey( 'timestamp', $entry );
		$this->assertArrayHasKey( 'word_count', $entry );
		$this->assertArrayHasKey( 'prev_length', $entry );
		$this->assertArrayHasKey( 'new_length', $entry );
	}

	public function test_empty_log_returns_empty_array(): void {
		$this->assertSame( [], $this->rewriter->get_log( 999 ) );
	}

	// -----------------------------------------------------------------------
	// get_batch_candidates
	// -----------------------------------------------------------------------

	public function test_get_batch_candidates_returns_array(): void {
		$candidates = $this->rewriter->get_batch_candidates( 5 );
		$this->assertIsArray( $candidates );
	}

	// -----------------------------------------------------------------------
	// REST permission
	// -----------------------------------------------------------------------

	public function test_rest_permission_true_for_admin(): void {
		$GLOBALS['_current_user_can'] = true;
		$request = new \WP_REST_Request();
		$this->assertTrue( $this->rewriter->rest_permission( $request ) );
	}

	public function test_rest_permission_false_no_api_key_not_admin(): void {
		$GLOBALS['_current_user_can']    = false;
		$GLOBALS['_options']['pearblog_api_key'] = '';
		$request = new \WP_REST_Request();
		$this->assertFalse( $this->rewriter->rest_permission( $request ) );
	}

	public function test_rest_permission_true_with_valid_bearer(): void {
		$GLOBALS['_current_user_can']    = false;
		$GLOBALS['_options']['pearblog_api_key'] = 'my-key';
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer my-key' );
		$this->assertTrue( $this->rewriter->rest_permission( $request ) );
	}

	// -----------------------------------------------------------------------
	// REST handlers
	// -----------------------------------------------------------------------

	public function test_rest_rewrite_returns_404_for_missing_post(): void {
		$GLOBALS['_current_user_can'] = true;

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 999 );
		$request->set_param( 'mode', ContentRewriter::MODE_REFRESH );

		$response = $this->rewriter->rest_rewrite( $request );
		$this->assertSame( 404, $response->status );
	}

	public function test_rest_rewrite_returns_200_for_existing_post(): void {
		$GLOBALS['_current_user_can'] = true;
		$post                = $this->make_post( 10 );
		$GLOBALS['_posts'][10] = $post;

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 10 );
		$request->set_param( 'mode', ContentRewriter::MODE_REFRESH );

		$response = $this->rewriter->rest_rewrite( $request );
		$this->assertSame( 200, $response->status );
	}

	public function test_rest_get_log_returns_200(): void {
		$GLOBALS['_current_user_can'] = true;
		$request = new \WP_REST_Request();
		$request->set_param( 'id', 999 );

		$response = $this->rewriter->rest_get_log( $request );
		$this->assertSame( 200, $response->status );
		$this->assertIsArray( $response->data );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function make_post( int $id, string $content = 'Original post content.' ): \WP_Post {
		return new \WP_Post( [
			'ID'           => $id,
			'post_title'   => "Test Post {$id}",
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_author'  => 1,
		] );
	}

	private function create_ai_stub( string $response ): \PearBlogEngine\AI\AIClient {
		return new class( $response ) extends \PearBlogEngine\AI\AIClient {
			public function __construct( private string $resp ) {}
			public function generate( string $prompt, int $max_tokens = 2048 ): string {
				return $this->resp;
			}
		};
	}
}
