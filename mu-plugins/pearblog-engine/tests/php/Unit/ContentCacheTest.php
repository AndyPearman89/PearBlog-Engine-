<?php
/**
 * Unit tests for ContentCache.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Cache\ContentCache;

class ContentCacheTest extends TestCase {

	private ContentCache $cache;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$this->cache = new ContentCache();
	}

	public function test_set_and_get_value(): void {
		$this->cache->set( 'my-key', 'hello world' );
		$this->assertSame( 'hello world', $this->cache->get( 'my-key' ) );
	}

	public function test_get_returns_false_for_missing_key(): void {
		$this->assertFalse( $this->cache->get( 'nonexistent' ) );
	}

	public function test_delete_removes_value(): void {
		$this->cache->set( 'delete-me', 'value' );
		$this->assertSame( 'value', $this->cache->get( 'delete-me' ) );
		$this->cache->delete( 'delete-me' );
		$this->assertFalse( $this->cache->get( 'delete-me' ) );
	}

	public function test_set_and_get_ai_content(): void {
		$this->cache->set_ai_content( 'my topic', 'profile-hash', 'Generated article content here.' );
		$result = $this->cache->get_ai_content( 'my topic', 'profile-hash' );
		$this->assertSame( 'Generated article content here.', $result );
	}

	public function test_ai_content_miss_returns_false(): void {
		$this->assertFalse( $this->cache->get_ai_content( 'unknown topic', 'profile' ) );
	}

	public function test_seo_meta_set_and_get(): void {
		$this->cache->set_seo_meta( 42, 'Great meta description.' );
		$this->assertSame( 'Great meta description.', $this->cache->get_seo_meta( 42 ) );
	}

	public function test_seo_meta_miss_returns_false(): void {
		$this->assertFalse( $this->cache->get_seo_meta( 9999 ) );
	}

	public function test_link_candidates_set_and_get(): void {
		$candidates = [ [ 'id' => 1, 'title' => 'Post A' ], [ 'id' => 2, 'title' => 'Post B' ] ];
		$this->cache->set_link_candidates( 10, $candidates );
		$this->assertSame( $candidates, $this->cache->get_link_candidates( 10 ) );
	}

	public function test_duplicate_hash_set_and_get(): void {
		$hash = md5( 'some article content fingerprint' );
		$this->cache->set_duplicate_hash( $hash, 99 );
		$this->assertSame( 99, $this->cache->get_duplicate_hash( $hash ) );
	}

	public function test_stats_count_hits_and_misses(): void {
		$this->cache->set( 'k1', 'v1' );
		$this->cache->get( 'k1' ); // hit
		$this->cache->get( 'missing' ); // miss

		$stats = $this->cache->get_stats();
		$this->assertSame( 1, $stats['hits'] );
		$this->assertSame( 1, $stats['misses'] );
		$this->assertSame( 1, $stats['writes'] );
	}

	public function test_hit_rate_calculation(): void {
		$this->cache->set( 'k', 'v' );
		$this->cache->get( 'k' ); // hit
		$this->cache->get( 'k' ); // hit
		$this->cache->get( 'nope' ); // miss

		$stats = $this->cache->get_stats();
		$this->assertEqualsWithDelta( 66.7, $stats['hit_rate'], 0.5 );
	}

	public function test_reset_stats_zeroes_counters(): void {
		$this->cache->set( 'x', 'y' );
		$this->cache->get( 'x' );
		$this->cache->reset_stats();

		$stats = $this->cache->get_stats();
		$this->assertSame( 0, $stats['hits'] );
		$this->assertSame( 0, $stats['misses'] );
		$this->assertSame( 0, $stats['writes'] );
	}

	public function test_flush_removes_all_tracked_entries(): void {
		$this->cache->set( 'a', '1' );
		$this->cache->set( 'b', '2' );
		$this->assertSame( '1', $this->cache->get( 'a' ) );

		$deleted = $this->cache->flush();
		$this->assertGreaterThan( 0, $deleted );
		$this->assertFalse( $this->cache->get( 'a' ) );
	}

	public function test_different_keys_store_independently(): void {
		$this->cache->set( 'key1', 'value1' );
		$this->cache->set( 'key2', 'value2' );
		$this->assertSame( 'value1', $this->cache->get( 'key1' ) );
		$this->assertSame( 'value2', $this->cache->get( 'key2' ) );
	}

	public function test_set_stores_array_value(): void {
		$data = [ 'a' => 1, 'b' => [ 'nested' => true ] ];
		$this->cache->set( 'array-key', $data );
		$this->assertSame( $data, $this->cache->get( 'array-key' ) );
	}
}
