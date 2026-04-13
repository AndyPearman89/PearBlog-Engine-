<?php
/**
 * Unit tests for ObjectCacheAdapter.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Cache\ObjectCacheAdapter;

class ObjectCacheAdapterTest extends TestCase {

	private ObjectCacheAdapter $adapter;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']              = [];
		$GLOBALS['_object_cache']         = [];
		$GLOBALS['_object_cache_global_groups'] = [];
		$GLOBALS['_using_ext_object_cache'] = false;
		$this->adapter = new ObjectCacheAdapter();
	}

	// -----------------------------------------------------------------------
	// get_group
	// -----------------------------------------------------------------------

	public function test_default_group(): void {
		$this->assertSame( ObjectCacheAdapter::DEFAULT_GROUP, $this->adapter->get_group() );
	}

	public function test_custom_group(): void {
		update_option( ObjectCacheAdapter::OPTION_GROUP, 'mysite_pearblog' );
		$this->assertSame( 'mysite_pearblog', $this->adapter->get_group() );
	}

	// -----------------------------------------------------------------------
	// set / get / delete
	// -----------------------------------------------------------------------

	public function test_set_and_get_value(): void {
		$this->adapter->set( 'my_key', 'my_value' );
		$this->assertSame( 'my_value', $this->adapter->get( 'my_key' ) );
	}

	public function test_get_returns_false_on_miss(): void {
		$this->assertFalse( $this->adapter->get( 'non_existent_key' ) );
	}

	public function test_delete_removes_value(): void {
		$this->adapter->set( 'del_key', 'data' );
		$this->adapter->delete( 'del_key' );
		$this->assertFalse( $this->adapter->get( 'del_key' ) );
	}

	public function test_set_overwrites_existing(): void {
		$this->adapter->set( 'k', 'old' );
		$this->adapter->set( 'k', 'new' );
		$this->assertSame( 'new', $this->adapter->get( 'k' ) );
	}

	// -----------------------------------------------------------------------
	// flush_group
	// -----------------------------------------------------------------------

	public function test_flush_group_removes_group_entries(): void {
		$this->adapter->set( 'a', '1' );
		$this->adapter->set( 'b', '2' );
		$this->adapter->flush_group();
		$this->assertFalse( $this->adapter->get( 'a' ) );
		$this->assertFalse( $this->adapter->get( 'b' ) );
	}

	public function test_flush_group_does_not_affect_other_groups(): void {
		// Put something in a different group directly.
		$GLOBALS['_object_cache']['other_group:key'] = 'other_value';
		$this->adapter->set( 'a', '1' );
		$this->adapter->flush_group();
		$this->assertSame( 'other_value', $GLOBALS['_object_cache']['other_group:key'] ?? false );
	}

	// -----------------------------------------------------------------------
	// Typed AI content helpers
	// -----------------------------------------------------------------------

	public function test_set_and_get_ai_content(): void {
		$this->adapter->set_ai_content( 'email marketing', 'profile_hash_123', '<p>Article</p>' );
		$result = $this->adapter->get_ai_content( 'email marketing', 'profile_hash_123' );
		$this->assertSame( '<p>Article</p>', $result );
	}

	public function test_get_ai_content_miss_returns_false(): void {
		$this->assertFalse( $this->adapter->get_ai_content( 'unknown topic', 'hash' ) );
	}

	public function test_ai_content_differs_by_topic(): void {
		$this->adapter->set_ai_content( 'topic_a', 'profile', 'content_a' );
		$this->adapter->set_ai_content( 'topic_b', 'profile', 'content_b' );
		$this->assertSame( 'content_a', $this->adapter->get_ai_content( 'topic_a', 'profile' ) );
		$this->assertSame( 'content_b', $this->adapter->get_ai_content( 'topic_b', 'profile' ) );
	}

	// -----------------------------------------------------------------------
	// Typed SEO meta helpers
	// -----------------------------------------------------------------------

	public function test_set_and_get_seo_meta(): void {
		$this->adapter->set_seo_meta( 42, 'Meta description text.' );
		$this->assertSame( 'Meta description text.', $this->adapter->get_seo_meta( 42 ) );
	}

	public function test_get_seo_meta_miss_returns_false(): void {
		$this->assertFalse( $this->adapter->get_seo_meta( 99999 ) );
	}

	// -----------------------------------------------------------------------
	// Typed link candidates helpers
	// -----------------------------------------------------------------------

	public function test_set_and_get_link_candidates(): void {
		$candidates = [
			[ 'post_id' => 1, 'title' => 'Linked Post' ],
			[ 'post_id' => 2, 'title' => 'Another Post' ],
		];
		$this->adapter->set_link_candidates( 10, $candidates );
		$this->assertSame( $candidates, $this->adapter->get_link_candidates( 10 ) );
	}

	// -----------------------------------------------------------------------
	// Typed duplicate hash helpers
	// -----------------------------------------------------------------------

	public function test_set_and_get_duplicate_hash(): void {
		$this->adapter->set_duplicate_hash( 'abc123hash', 55 );
		$this->assertSame( 55, $this->adapter->get_duplicate_hash( 'abc123hash' ) );
	}

	public function test_get_duplicate_hash_miss_returns_false(): void {
		$this->assertFalse( $this->adapter->get_duplicate_hash( 'unknown_hash' ) );
	}

	// -----------------------------------------------------------------------
	// is_persistent
	// -----------------------------------------------------------------------

	public function test_not_persistent_by_default(): void {
		$this->assertFalse( $this->adapter->is_persistent() );
	}

	public function test_persistent_when_ext_cache_active(): void {
		$GLOBALS['_using_ext_object_cache'] = true;
		$this->assertTrue( $this->adapter->is_persistent() );
	}

	// -----------------------------------------------------------------------
	// register (global groups)
	// -----------------------------------------------------------------------

	public function test_register_adds_global_group(): void {
		$this->adapter->register();
		$this->assertContains(
			ObjectCacheAdapter::DEFAULT_GROUP,
			$GLOBALS['_object_cache_global_groups'] ?? []
		);
	}

	// -----------------------------------------------------------------------
	// cache_key
	// -----------------------------------------------------------------------

	public function test_cache_key_is_md5(): void {
		$key = $this->adapter->cache_key( 'test_logical_key' );
		$this->assertRegExp( '/^[a-f0-9]{32}$/', $key );
	}

	public function test_cache_key_consistent(): void {
		$this->assertSame(
			$this->adapter->cache_key( 'same_key' ),
			$this->adapter->cache_key( 'same_key' )
		);
	}

	public function test_cache_key_differs_for_different_inputs(): void {
		$this->assertNotSame(
			$this->adapter->cache_key( 'key_a' ),
			$this->adapter->cache_key( 'key_b' )
		);
	}
}
