<?php
/**
 * Unit tests for WordPressContextProcessor
 *
 * Tests WordPress context enrichment functionality.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Logging\WordPressContextProcessor;

/**
 * Test WordPressContextProcessor class
 */
class WordPressContextProcessorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// Reset WordPress globals
		$GLOBALS['current_user'] = null;
	}

	/**
	 * Test processor adds WordPress context
	 */
	public function test_adds_wordpress_context(): void {
		$processor = new WordPressContextProcessor();

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'extra', $processed );
		$this->assertArrayHasKey( 'wordpress', $processed['extra'] );
	}

	/**
	 * Test processor adds user info when enabled and user logged in
	 */
	public function test_adds_user_info_when_enabled(): void {
		// Mock logged-in user
		$GLOBALS['current_user'] = (object) [
			'ID'         => 123,
			'user_login' => 'testuser',
			'roles'      => [ 'administrator' ],
		];

		$processor = new WordPressContextProcessor( true );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'user', $processed['extra']['wordpress'] );
		$this->assertSame( 123, $processed['extra']['wordpress']['user']['id'] );
		$this->assertSame( 'testuser', $processed['extra']['wordpress']['user']['username'] );
	}

	/**
	 * Test processor does not add user when not logged in
	 */
	public function test_does_not_add_user_when_not_logged_in(): void {
		$GLOBALS['current_user'] = (object) [ 'ID' => 0 ];

		$processor = new WordPressContextProcessor( true );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayNotHasKey( 'user', $processed['extra']['wordpress'] );
	}

	/**
	 * Test processor adds environment info when enabled
	 */
	public function test_adds_environment_info_when_enabled(): void {
		global $wp_version;
		$wp_version = '6.5.0';

		if ( ! defined( 'WP_DEBUG' ) ) {
			define( 'WP_DEBUG', true );
		}

		$processor = new WordPressContextProcessor( false, true );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'environment', $processed['extra']['wordpress'] );
		$this->assertArrayHasKey( 'wp_version', $processed['extra']['wordpress']['environment'] );
		$this->assertArrayHasKey( 'php_version', $processed['extra']['wordpress']['environment'] );
		$this->assertArrayHasKey( 'is_debug', $processed['extra']['wordpress']['environment'] );
	}

	/**
	 * Test processor adds site ID in multisite
	 */
	public function test_adds_site_id_in_multisite(): void {
		// Mock multisite
		$GLOBALS['_is_multisite'] = true;
		$GLOBALS['_current_blog_id'] = 5;

		$processor = new WordPressContextProcessor();

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'site_id', $processed['extra']['wordpress'] );
		$this->assertSame( 5, $processed['extra']['wordpress']['site_id'] );
	}
}
