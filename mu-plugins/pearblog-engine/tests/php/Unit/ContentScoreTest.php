<?php
/**
 * Unit tests for ContentScore value object.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Content\ContentScore;
use PHPUnit\Framework\TestCase;

class ContentScoreTest extends TestCase {

	public function test_perfect_score(): void {
		$score = new ContentScore( 40, 40, 20, 50 );

		$this->assertSame( 100, $score->total );
		$this->assertTrue( $score->passes );
		$this->assertSame( 40, $score->length );
		$this->assertSame( 40, $score->structure );
		$this->assertSame( 20, $score->quality );
	}

	public function test_below_min_score_fails(): void {
		$score = new ContentScore( 10, 10, 5, 50 );

		$this->assertSame( 25, $score->total );
		$this->assertFalse( $score->passes );
	}

	public function test_exact_min_score_passes(): void {
		$score = new ContentScore( 20, 20, 10, 50 );

		$this->assertSame( 50, $score->total );
		$this->assertTrue( $score->passes );
	}

	public function test_values_are_clamped(): void {
		// Exceeding max values should be clamped.
		$score = new ContentScore( 999, 999, 999, 50 );

		$this->assertSame( 40, $score->length );
		$this->assertSame( 40, $score->structure );
		$this->assertSame( 20, $score->quality );
		$this->assertSame( 100, $score->total );
	}

	public function test_negative_values_are_clamped_to_zero(): void {
		$score = new ContentScore( -10, -5, -1, 50 );

		$this->assertSame( 0, $score->length );
		$this->assertSame( 0, $score->structure );
		$this->assertSame( 0, $score->quality );
		$this->assertSame( 0, $score->total );
	}

	public function test_issues_are_preserved(): void {
		$issues = [ 'Missing meta description.', 'Content too short.' ];
		$score  = new ContentScore( 10, 10, 5, 50, $issues );

		$this->assertSame( $issues, $score->issues );
	}

	public function test_summary_format(): void {
		$score = new ContentScore( 30, 25, 15, 50 );
		$summary = $score->summary();

		$this->assertStringContainsString( '70/100', $summary );
		$this->assertStringContainsString( '✓ PASS', $summary );
	}

	public function test_summary_fail_format(): void {
		$score = new ContentScore( 10, 10, 5, 50 );
		$summary = $score->summary();

		$this->assertStringContainsString( '25/100', $summary );
		$this->assertStringContainsString( '✗ FAIL', $summary );
	}
}
