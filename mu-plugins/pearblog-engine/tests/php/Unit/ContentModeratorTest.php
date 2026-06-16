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
		$this->moderator       = new ContentModerator();
	}

	// -----------------------------------------------------------------------
	// Option constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant_value(): void {
		$this->assertSame( 'pearblog_moderation_enabled', ContentModerator::OPTION_ENABLED );
	}

	public function test_option_action_constant_value(): void {
		$this->assertSame( 'pearblog_moderation_action', ContentModerator::OPTION_ACTION );
	}

	public function test_meta_result_constant_value(): void {
		$this->assertSame( 'pearblog_moderation_result', ContentModerator::META_RESULT );
	}

	// -----------------------------------------------------------------------
	// is_enabled — disabled paths
	// -----------------------------------------------------------------------

	public function test_is_enabled_returns_false_by_default(): void {
		$this->assertFalse( $this->moderator->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_enabled_but_no_api_key(): void {
		update_option( ContentModerator::OPTION_ENABLED, true );
		$this->assertFalse( $this->moderator->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_api_key_set_but_disabled(): void {
		update_option( 'pearblog_openai_api_key', 'sk-test-key' );
		$this->assertFalse( $this->moderator->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// is_enabled — enabled path
	// -----------------------------------------------------------------------

	public function test_is_enabled_returns_true_when_both_enabled_and_api_key_present(): void {
		update_option( ContentModerator::OPTION_ENABLED, true );
		update_option( 'pearblog_openai_api_key', 'sk-test-key' );
		$this->assertTrue( $this->moderator->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// check — disabled path (no API call, fast return)
	// -----------------------------------------------------------------------

	public function test_check_returns_array_when_disabled(): void {
		$result = $this->moderator->check( 1, 'Some content.' );
		$this->assertIsArray( $result );
	}

	public function test_check_returns_false_flagged_when_disabled(): void {
		$result = $this->moderator->check( 1, 'Some content.' );
		$this->assertFalse( $result['flagged'] );
	}

	public function test_check_returns_action_none_when_disabled(): void {
		$result = $this->moderator->check( 1, 'Some content.' );
		$this->assertSame( 'none', $result['action'] );
	}

	public function test_check_returns_empty_categories_when_disabled(): void {
		$result = $this->moderator->check( 1, 'Some content.' );
		$this->assertSame( [], $result['categories'] );
	}

	public function test_check_returns_empty_scores_when_disabled(): void {
		$result = $this->moderator->check( 1, 'Some content.' );
		$this->assertSame( [], $result['scores'] );
	}

	// -----------------------------------------------------------------------
	// check — enabled, API stub returns empty body → not flagged
	// -----------------------------------------------------------------------

	public function test_check_result_has_flagged_key(): void {
		$result = $this->moderator->check( 1, 'Test article.' );
		$this->assertArrayHasKey( 'flagged', $result );
	}

	public function test_check_result_has_action_key(): void {
		$result = $this->moderator->check( 1, 'Test article.' );
		$this->assertArrayHasKey( 'action', $result );
	}

	public function test_check_result_has_categories_key(): void {
		$result = $this->moderator->check( 1, 'Test article.' );
		$this->assertArrayHasKey( 'categories', $result );
	}

	public function test_check_result_has_scores_key(): void {
		$result = $this->moderator->check( 1, 'Test article.' );
		$this->assertArrayHasKey( 'scores', $result );
	}

	public function test_check_passes_when_api_returns_empty_body(): void {
		update_option( ContentModerator::OPTION_ENABLED, true );
		update_option( 'pearblog_openai_api_key', 'sk-test-key' );
		// wp_remote_post stub returns empty body → not flagged
		$result = $this->moderator->check( 10, 'Clean article about WordPress best practices.' );
		$this->assertFalse( $result['flagged'] );
		$this->assertSame( 'pass', $result['action'] );
	}

	public function test_check_persists_result_in_post_meta_when_enabled(): void {
		update_option( ContentModerator::OPTION_ENABLED, true );
		update_option( 'pearblog_openai_api_key', 'sk-test-key' );
		$this->moderator->check( 42, 'Article text for persistence test.' );
		$meta = get_post_meta( 42, ContentModerator::META_RESULT, true );
		$this->assertIsArray( $meta );
	}
}
