<?php
/**
 * Unit tests for DuplicateDetector.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\DuplicateDetector;

class DuplicateDetectorTest extends TestCase {

	private DuplicateDetector $detector;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_post_list'] = [];
		$this->detector = new DuplicateDetector();
	}

	public function test_identical_content_is_detected_as_duplicate(): void {
		$content = $this->long_article( 'SEO tips for beginners' );
		$this->seed_post( 1, $content );

		$result = $this->detector->check( $content );

		$this->assertTrue( $result['is_duplicate'] );
		$this->assertGreaterThan( 0.90, $result['similarity'] );
	}

	public function test_completely_different_content_is_not_duplicate(): void {
		$existing = $this->long_article( 'How to cook pasta at home with Italian sauce and cheese' );
		$this->seed_post( 1, $existing );

		$candidate = $this->long_article( 'Hiking trails mountains snowy winter outdoor adventure skiing' );

		$result = $this->detector->check( $candidate );

		$this->assertFalse( $result['is_duplicate'] );
	}

	public function test_empty_candidate_is_never_duplicate(): void {
		$this->seed_post( 1, $this->long_article( 'something' ) );
		$result = $this->detector->check( '' );

		$this->assertFalse( $result['is_duplicate'] );
		$this->assertSame( 0.0, $result['similarity'] );
	}

	public function test_no_existing_posts_means_no_duplicate(): void {
		$result = $this->detector->check( $this->long_article( 'something' ) );

		$this->assertFalse( $result['is_duplicate'] );
	}

	public function test_excluded_post_id_is_skipped(): void {
		$content = $this->long_article( 'unique content about travel hotels booking' );
		$this->seed_post( 5, $content );

		// Checking against itself but excluding its own ID – should not flag.
		$result = $this->detector->check( $content, 5 );

		$this->assertFalse( $result['is_duplicate'] );
	}

	public function test_index_stores_tf_vector_as_post_meta(): void {
		$content = $this->long_article( 'keyword research seo tools ranking' );
		$this->detector->index( 99, $content );

		$stored = $GLOBALS['_post_meta'][99]['_pearblog_tf_vector'][0] ?? null;
		$this->assertIsArray( $stored );
		$this->assertNotEmpty( $stored );
	}

	public function test_custom_threshold_can_be_lower(): void {
		// Moderately similar content (same topic, different wording).
		$existing  = $this->long_article( 'The best travel destinations in Europe include Paris and Rome famous attractions' );
		$candidate = $this->long_article( 'Top travel places in Europe feature Paris Rome and famous historical monuments' );
		$this->seed_post( 1, $existing );

		// With high threshold = 0.99, won't flag.
		$result_high = $this->detector->check( $candidate, 0, 0.99 );
		$this->assertFalse( $result_high['is_duplicate'] );

		// With low threshold = 0.01, will flag (anything non-zero matches).
		$result_low = $this->detector->check( $candidate, 0, 0.01 );
		$this->assertTrue( $result_low['is_duplicate'] );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function long_article( string $seed ): string {
		return str_repeat( $seed . ' ', 200 );
	}

	private function seed_post( int $id, string $content ): void {
		$post              = new \WP_Post();
		$post->ID          = $id;
		$post->post_title  = "Post {$id}";
		$post->post_content = $content;
		$post->post_status = 'publish';

		$GLOBALS['_posts'][ $id ] = $post;
		$GLOBALS['_post_list'][]  = $id;
	}
}
