<?php
/**
 * Unit tests for ContentModerator.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Security\ContentModerator;

class ContentModeratorTest extends TestCase {

	private ContentModerator $moderator;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_actions']   = [];
		$this->moderator = new ContentModerator();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant(): void {
		$this->assertSame( 'pearblog_moderation_enabled', ContentModerator::OPTION_ENABLED );
	}

	public function test_option_action_constant(): void {
		$this->assertSame( 'pearblog_moderation_action', ContentModerator::OPTION_ACTION );
	}

	public function test_meta_result_constant(): void {
		$this->assertSame( 'pearblog_moderation_result', ContentModerator::META_RESULT );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_false_by_default(): void {
		$this->assertFalse( $this->moderator->is_enabled() );
	}

	public function test_is_enabled_false_when_enabled_but_no_api_key(): void {
		update_option( ContentModerator::OPTION_ENABLED, true );

		$this->assertFalse( $this->moderator->is_enabled() );
	}

	public function test_is_enabled_true_when_enabled_and_api_key_set(): void {
		update_option( ContentModerator::OPTION_ENABLED, true );
		update_option( 'pearblog_openai_api_key', 'sk-test-key' );

		$this->assertTrue( $this->moderator->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// check — disabled state
	// -----------------------------------------------------------------------

	public function test_check_returns_not_flagged_when_disabled(): void {
		$result = $this->moderator->check( 1, '<p>Good content here.</p>' );

		$this->assertFalse( $result['flagged'] );
	}

	public function test_check_returns_action_none_when_disabled(): void {
		$result = $this->moderator->check( 1, '<p>Good content here.</p>' );

		$this->assertSame( 'none', $result['action'] );
	}

	public function test_check_returns_empty_categories_when_disabled(): void {
		$result = $this->moderator->check( 1, '<p>Test content</p>' );

		$this->assertSame( [], $result['categories'] );
		$this->assertSame( [], $result['scores'] );
	}

	public function test_check_returns_array_with_required_keys(): void {
		$result = $this->moderator->check( 1, '<p>Test</p>' );

		$this->assertArrayHasKey( 'flagged', $result );
		$this->assertArrayHasKey( 'action', $result );
		$this->assertArrayHasKey( 'categories', $result );
		$this->assertArrayHasKey( 'scores', $result );
	}

	// -----------------------------------------------------------------------
	// check — enabled with api key (API returns empty → unflagged)
	// -----------------------------------------------------------------------

	public function test_check_enabled_without_api_key_returns_pass(): void {
		update_option( ContentModerator::OPTION_ENABLED, true );
		update_option( 'pearblog_openai_api_key', 'sk-test-key' );
		// wp_remote_post is stubbed to return empty body → default unflagged result.

		$result = $this->moderator->check( 1, 'Some content.' );

		$this->assertFalse( $result['flagged'] );
		$this->assertSame( 'pass', $result['action'] );
	}

	public function test_check_enabled_writes_meta_to_post(): void {
		update_option( ContentModerator::OPTION_ENABLED, true );
		update_option( 'pearblog_openai_api_key', 'sk-test-key' );

		$this->moderator->check( 42, 'Test content.' );

		$meta = get_post_meta( 42, ContentModerator::META_RESULT, true );
		$this->assertIsArray( $meta );
	}

	// -----------------------------------------------------------------------
	// Default action is block
	// -----------------------------------------------------------------------

	public function test_default_moderation_action_is_block(): void {
		$action = get_option( ContentModerator::OPTION_ACTION, 'block' );

		$this->assertSame( 'block', $action );
	}
}
