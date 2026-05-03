<?php
/**
 * Unit tests for Logging Processors
 *
 * Tests MemoryUsageProcessor, RequestContextProcessor, and WordPressContextProcessor.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Logging\MemoryUsageProcessor;
use PearBlogEngine\Logging\RequestContextProcessor;
use PearBlogEngine\Logging\WordPressContextProcessor;

/**
 * Test MemoryUsageProcessor
 */
class MemoryUsageProcessorTest extends TestCase {

	/**
	 * Test processor adds memory usage to record
	 */
	public function test_adds_memory_usage_to_record(): void {
		$processor = new MemoryUsageProcessor();

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'extra', $processed );
		$this->assertArrayHasKey( 'memory_usage', $processed['extra'] );
	}

	/**
	 * Test processor adds peak memory when enabled
	 */
	public function test_adds_peak_memory_when_enabled(): void {
		$processor = new MemoryUsageProcessor( true );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'memory_peak', $processed['extra'] );
	}

	/**
	 * Test processor does not add peak memory when disabled
	 */
	public function test_does_not_add_peak_memory_when_disabled(): void {
		$processor = new MemoryUsageProcessor( false );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayNotHasKey( 'memory_peak', $processed['extra'] );
	}

	/**
	 * Test memory is formatted as human-readable by default
	 */
	public function test_formats_memory_as_human_readable(): void {
		$processor = new MemoryUsageProcessor( true, true );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertIsString( $processed['extra']['memory_usage'] );
		$this->assertRegExp( '/\d+(\.\d+)?\s+(B|KB|MB|GB)/', $processed['extra']['memory_usage'] );
	}

	/**
	 * Test memory is raw bytes when formatting disabled
	 */
	public function test_returns_raw_bytes_when_formatting_disabled(): void {
		$processor = new MemoryUsageProcessor( true, false );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertIsInt( $processed['extra']['memory_usage'] );
	}
}

/**
 * Test RequestContextProcessor
 */
class RequestContextProcessorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// Reset $_SERVER
		$_SERVER = [];
	}

	/**
	 * Test processor adds request info when available
	 */
	public function test_adds_request_info_when_available(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/test';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';

		$processor = new RequestContextProcessor();

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'extra', $processed );
		$this->assertArrayHasKey( 'request', $processed['extra'] );
		$this->assertSame( 'GET', $processed['extra']['request']['method'] );
		$this->assertSame( '/test', $processed['extra']['request']['uri'] );
		$this->assertSame( '192.168.1.1', $processed['extra']['request']['ip'] );
	}

	/**
	 * Test processor skips when not HTTP request
	 */
	public function test_skips_when_not_http_request(): void {
		$processor = new RequestContextProcessor();

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayNotHasKey( 'request', $processed['extra'] ?? [] );
	}

	/**
	 * Test processor adds user agent when enabled
	 */
	public function test_adds_user_agent_when_enabled(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/test';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';

		$processor = new RequestContextProcessor( true );

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		$this->assertArrayHasKey( 'user_agent', $processed['extra']['request'] );
		$this->assertSame( 'Mozilla/5.0', $processed['extra']['request']['user_agent'] );
	}

	/**
	 * Test processor handles X-Forwarded-For header
	 */
	public function test_handles_x_forwarded_for_header(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/test';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 198.51.100.1';

		$processor = new RequestContextProcessor();

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		// Should use first IP from X-Forwarded-For
		$this->assertSame( '203.0.113.1', $processed['extra']['request']['ip'] );
	}

	/**
	 * Test processor handles Cloudflare IP header
	 */
	public function test_handles_cloudflare_ip_header(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/test';
		$_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.5';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';

		$processor = new RequestContextProcessor();

		$record = [
			'level'   => 'INFO',
			'message' => 'Test',
			'context' => [],
		];

		$processed = $processor->process( $record );

		// Should use Cloudflare IP (higher priority)
		$this->assertSame( '203.0.113.5', $processed['extra']['request']['ip'] );
	}
}

/**
 * Test WordPressContextProcessor
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

		define( 'WP_DEBUG', true );

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
