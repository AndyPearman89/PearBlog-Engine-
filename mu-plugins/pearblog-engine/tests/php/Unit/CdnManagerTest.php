<?php
/**
 * Unit tests for CdnManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Cache\CdnManager;

class CdnManagerTest extends TestCase {

	private CdnManager $cdn;
	private string     $tmp_file;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']        = [];
		$GLOBALS['_post_meta']      = [];
		$GLOBALS['_attached_files'] = [];
		$GLOBALS['_http_response']  = null;
		$GLOBALS['_actions_fired']  = [];

		$this->cdn = new CdnManager();

		// Create a real temp file for upload tests.
		$this->tmp_file = tempnam( sys_get_temp_dir(), 'pearblog_cdn_test_' );
		file_put_contents( $this->tmp_file, 'fake image data' );
	}

	protected function tearDown(): void {
		if ( file_exists( $this->tmp_file ) ) {
			@unlink( $this->tmp_file );
		}
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// is_enabled / get_provider
	// -----------------------------------------------------------------------

	public function test_disabled_by_default(): void {
		$this->assertFalse( $this->cdn->is_enabled() );
	}

	public function test_enable_via_option(): void {
		update_option( CdnManager::OPTION_ENABLED, true );
		$this->assertTrue( $this->cdn->is_enabled() );
	}

	public function test_default_provider_is_bunnycdn(): void {
		$this->assertSame( CdnManager::DEFAULT_PROVIDER, $this->cdn->get_provider() );
		$this->assertSame( CdnManager::PROVIDER_BUNNYCDN, $this->cdn->get_provider() );
	}

	public function test_custom_provider(): void {
		update_option( CdnManager::OPTION_PROVIDER, 'cloudflare' );
		$this->assertSame( CdnManager::PROVIDER_CLOUDFLARE, $this->cdn->get_provider() );
	}

	public function test_should_not_delete_local_by_default(): void {
		$this->assertFalse( $this->cdn->should_delete_local() );
	}

	// -----------------------------------------------------------------------
	// offload_attachment — disabled guard
	// -----------------------------------------------------------------------

	public function test_offload_returns_null_when_disabled(): void {
		$this->assertNull( $this->cdn->offload_attachment( 1 ) );
	}

	// -----------------------------------------------------------------------
	// offload_attachment — already offloaded guard
	// -----------------------------------------------------------------------

	public function test_offload_returns_existing_cdn_url(): void {
		update_option( CdnManager::OPTION_ENABLED, true );
		update_post_meta( 10, CdnManager::META_CDN_URL, 'https://cdn.example.com/image.jpg' );

		$result = $this->cdn->offload_attachment( 10 );
		$this->assertSame( 'https://cdn.example.com/image.jpg', $result );
	}

	// -----------------------------------------------------------------------
	// offload_attachment — missing file guard
	// -----------------------------------------------------------------------

	public function test_offload_returns_null_when_file_not_found(): void {
		update_option( CdnManager::OPTION_ENABLED, true );
		$GLOBALS['_attached_files'][99] = '/non/existent/path/image.jpg';

		$this->assertNull( $this->cdn->offload_attachment( 99 ) );
	}

	// -----------------------------------------------------------------------
	// offload_attachment — BunnyCDN upload success
	// -----------------------------------------------------------------------

	public function test_offload_bunnycdn_stores_cdn_url_meta(): void {
		update_option( CdnManager::OPTION_ENABLED, true );
		update_option( CdnManager::OPTION_PROVIDER, 'bunnycdn' );
		update_option( CdnManager::OPTION_BUNNY_API_KEY, 'secret-key' );
		update_option( CdnManager::OPTION_BUNNY_ZONE_NAME, 'my-zone' );
		update_option( CdnManager::OPTION_BUNNY_PULL_URL, 'https://my-zone.b-cdn.net' );

		$GLOBALS['_attached_files'][5] = $this->tmp_file;
		$GLOBALS['_http_response'] = [ 'response' => [ 'code' => 201 ], 'body' => '' ];

		$cdn_url = $this->cdn->offload_attachment( 5 );

		$this->assertNotNull( $cdn_url );
		$this->assertStringContainsString( 'b-cdn.net', $cdn_url );
		$this->assertStringContainsString( 'pearblog', $cdn_url );

		$stored = get_post_meta( 5, CdnManager::META_CDN_URL, true );
		$this->assertSame( $cdn_url, $stored );
	}

	public function test_offload_bunnycdn_stores_provider_meta(): void {
		update_option( CdnManager::OPTION_ENABLED, true );
		update_option( CdnManager::OPTION_PROVIDER, 'bunnycdn' );
		update_option( CdnManager::OPTION_BUNNY_API_KEY, 'k' );
		update_option( CdnManager::OPTION_BUNNY_ZONE_NAME, 'z' );
		update_option( CdnManager::OPTION_BUNNY_PULL_URL, 'https://z.b-cdn.net' );

		$GLOBALS['_attached_files'][6] = $this->tmp_file;
		$GLOBALS['_http_response'] = [ 'response' => [ 'code' => 201 ], 'body' => '' ];

		$this->cdn->offload_attachment( 6 );

		$this->assertSame( CdnManager::PROVIDER_BUNNYCDN, get_post_meta( 6, CdnManager::META_PROVIDER, true ) );
	}

	// -----------------------------------------------------------------------
	// offload_attachment — BunnyCDN upload failure
	// -----------------------------------------------------------------------

	public function test_offload_bunnycdn_returns_null_on_api_error(): void {
		update_option( CdnManager::OPTION_ENABLED, true );
		update_option( CdnManager::OPTION_PROVIDER, 'bunnycdn' );
		update_option( CdnManager::OPTION_BUNNY_API_KEY, 'k' );
		update_option( CdnManager::OPTION_BUNNY_ZONE_NAME, 'z' );
		update_option( CdnManager::OPTION_BUNNY_PULL_URL, 'https://z.b-cdn.net' );

		$GLOBALS['_attached_files'][7] = $this->tmp_file;
		$GLOBALS['_http_response'] = [ 'response' => [ 'code' => 401 ], 'body' => '' ];

		$this->assertNull( $this->cdn->offload_attachment( 7 ) );
	}

	public function test_offload_bunnycdn_returns_null_when_unconfigured(): void {
		update_option( CdnManager::OPTION_ENABLED, true );
		update_option( CdnManager::OPTION_PROVIDER, 'bunnycdn' );
		// No API key set.
		$GLOBALS['_attached_files'][8] = $this->tmp_file;

		$this->assertNull( $this->cdn->offload_attachment( 8 ) );
	}

	// -----------------------------------------------------------------------
	// offload_attachment — fires action on success
	// -----------------------------------------------------------------------

	public function test_offload_fires_action_on_success(): void {
		update_option( CdnManager::OPTION_ENABLED, true );
		update_option( CdnManager::OPTION_PROVIDER, 'bunnycdn' );
		update_option( CdnManager::OPTION_BUNNY_API_KEY, 'k' );
		update_option( CdnManager::OPTION_BUNNY_ZONE_NAME, 'z' );
		update_option( CdnManager::OPTION_BUNNY_PULL_URL, 'https://z.b-cdn.net' );

		$GLOBALS['_attached_files'][20] = $this->tmp_file;
		$GLOBALS['_http_response'] = [ 'response' => [ 'code' => 200 ], 'body' => '' ];

		$fired = false;
		add_action( 'pearblog_cdn_offloaded', function() use ( &$fired ) {
			$fired = true;
		} );

		$this->cdn->offload_attachment( 20 );
		$this->assertTrue( $fired );
	}

	// -----------------------------------------------------------------------
	// filter_attachment_url
	// -----------------------------------------------------------------------

	public function test_filter_returns_cdn_url_for_offloaded_attachment(): void {
		update_post_meta( 15, CdnManager::META_CDN_URL, 'https://cdn.example.com/img.jpg' );
		$result = $this->cdn->filter_attachment_url( 'https://local.example.com/img.jpg', 15 );
		$this->assertSame( 'https://cdn.example.com/img.jpg', $result );
	}

	public function test_filter_returns_original_url_when_no_cdn_meta(): void {
		$original = 'https://local.example.com/no-cdn.jpg';
		$result   = $this->cdn->filter_attachment_url( $original, 100 );
		$this->assertSame( $original, $result );
	}

	// -----------------------------------------------------------------------
	// remove_from_cdn
	// -----------------------------------------------------------------------

	public function test_remove_returns_false_when_no_cdn_url(): void {
		$this->assertFalse( $this->cdn->remove_from_cdn( 1 ) );
	}

	public function test_remove_bunny_deletes_and_clears_meta(): void {
		update_post_meta( 30, CdnManager::META_CDN_URL, 'https://z.b-cdn.net/pearblog/img.jpg' );
		update_post_meta( 30, CdnManager::META_PROVIDER, CdnManager::PROVIDER_BUNNYCDN );
		update_option( CdnManager::OPTION_BUNNY_API_KEY, 'key' );
		update_option( CdnManager::OPTION_BUNNY_ZONE_NAME, 'z' );

		$GLOBALS['_http_response'] = [ 'response' => [ 'code' => 200 ], 'body' => '' ];

		$result = $this->cdn->remove_from_cdn( 30 );
		$this->assertTrue( $result );
		$this->assertSame( '', (string) get_post_meta( 30, CdnManager::META_CDN_URL, true ) );
	}

	// -----------------------------------------------------------------------
	// upload_to_provider dispatch
	// -----------------------------------------------------------------------

	public function test_upload_to_provider_uses_configured_provider(): void {
		update_option( CdnManager::OPTION_PROVIDER, 'bunnycdn' );
		update_option( CdnManager::OPTION_BUNNY_API_KEY, 'k' );
		update_option( CdnManager::OPTION_BUNNY_ZONE_NAME, 'z' );
		update_option( CdnManager::OPTION_BUNNY_PULL_URL, 'https://z.b-cdn.net' );

		$GLOBALS['_http_response'] = [ 'response' => [ 'code' => 201 ], 'body' => '' ];

		$url = $this->cdn->upload_to_provider( $this->tmp_file, 99 );
		$this->assertNotNull( $url );
		$this->assertStringContainsString( 'b-cdn.net', (string) $url );
	}

	// -----------------------------------------------------------------------
	// Meta key constants
	// -----------------------------------------------------------------------

	public function test_meta_cdn_url_constant(): void {
		$this->assertSame( '_pearblog_cdn_url', CdnManager::META_CDN_URL );
	}

	public function test_meta_provider_constant(): void {
		$this->assertSame( '_pearblog_cdn_provider', CdnManager::META_PROVIDER );
	}
}
