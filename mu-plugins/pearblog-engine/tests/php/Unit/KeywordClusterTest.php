<?php
/**
 * Unit tests for KeywordCluster.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Keywords\KeywordCluster;

class KeywordClusterTest extends TestCase {

	public function test_constructor_sets_properties(): void {
		$cluster = new KeywordCluster( 'Email Marketing', [ 'newsletter tips', 'email campaigns' ] );

		$this->assertSame( 'Email Marketing', $cluster->pillar );
		$this->assertSame( [ 'newsletter tips', 'email campaigns' ], $cluster->supporting );
	}

	public function test_cluster_id_is_derived_from_pillar(): void {
		$cluster = new KeywordCluster( 'Email Marketing' );
		$this->assertSame( 'email-marketing', $cluster->cluster_id );
	}

	public function test_all_keywords_returns_pillar_first(): void {
		$cluster = new KeywordCluster( 'SEO', [ 'on-page seo', 'link building' ] );
		$all     = $cluster->all_keywords();

		$this->assertSame( 'SEO', $all[0] );
		$this->assertCount( 3, $all );
	}

	public function test_keywords_as_string(): void {
		$cluster = new KeywordCluster( 'Travel', [ 'hotels', 'flights' ] );
		$this->assertSame( 'Travel, hotels, flights', $cluster->keywords_as_string() );
	}

	public function test_to_array_and_from_array_roundtrip(): void {
		$original = new KeywordCluster( 'Content Marketing', [ 'blog posts', 'case studies' ] );
		$arr      = $original->to_array();
		$restored = KeywordCluster::from_array( $arr );

		$this->assertSame( $original->pillar,     $restored->pillar );
		$this->assertSame( $original->supporting,  $restored->supporting );
		$this->assertSame( $original->cluster_id,  $restored->cluster_id );
	}

	public function test_empty_supporting_array_is_normalised(): void {
		$cluster = new KeywordCluster( 'SEO', [ '', '  ', 'valid-kw' ] );
		$this->assertSame( [ 'valid-kw' ], $cluster->supporting );
	}

	public function test_pillar_is_trimmed(): void {
		$cluster = new KeywordCluster( '  whitespace  ' );
		$this->assertSame( 'whitespace', $cluster->pillar );
	}
}
