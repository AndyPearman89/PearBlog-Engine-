<?php
/**
 * Unit tests for RequestContextProcessor
 *
 * Tests request context enrichment functionality.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Logging\RequestContextProcessor;

/**
 * Test RequestContextProcessor class
 */
class RequestContextProcessorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// Reset $_SERVER but preserve REQUEST_TIME_FLOAT (required by PHPUnit's timer).
		$rtf = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
		$_SERVER = [];
		if ( $rtf !== null ) {
			$_SERVER['REQUEST_TIME_FLOAT'] = $rtf;
		}
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
