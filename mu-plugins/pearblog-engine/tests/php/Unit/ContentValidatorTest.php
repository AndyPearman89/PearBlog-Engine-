<?php
/**
 * Unit tests for ContentValidator.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Content\ContentValidator;
use PHPUnit\Framework\TestCase;

class ContentValidatorTest extends TestCase {

	private ContentValidator $validator;

	protected function setUp(): void {
		$this->validator = new ContentValidator();
	}

	public function test_valid_generic_content(): void {
		$content = "META: A description of the content.\n\n"
		         . "# My Title\n\n"
		         . str_repeat( 'Lorem ipsum dolor sit amet. ', 200 ); // ~1200 words

		$result = $this->validator->validate( $content, 'generic' );

		$this->assertTrue( $result['valid'] );
		$this->assertEmpty( $result['errors'] );
	}

	public function test_missing_meta_description_is_error(): void {
		$content = "# My Title\n\nBody content here.";

		$result = $this->validator->validate( $content, 'generic' );

		$this->assertFalse( $result['valid'] );
		$this->assertContains( 'Missing META description at the beginning of content', $result['errors'] );
	}

	public function test_missing_h1_is_error(): void {
		$content = "META: Description\n\nBody content without heading.";

		$result = $this->validator->validate( $content, 'generic' );

		$this->assertFalse( $result['valid'] );
		$this->assertContains( 'Missing H1 title', $result['errors'] );
	}

	public function test_short_content_generates_warning(): void {
		$content = "META: Short article\n\n# Short\n\nThis is very short content.";

		$result = $this->validator->validate( $content, 'generic' );

		$this->assertTrue( $result['valid'] ); // warnings don't invalidate
		$this->assertNotEmpty( $result['warnings'] );
	}

	public function test_travel_content_needs_sections(): void {
		$content = "META: Travel article\n\n# Travel Guide\n\nJust body text.";

		$result = $this->validator->validate( $content, 'travel' );

		// Should have errors for missing travel-specific sections.
		$this->assertFalse( $result['valid'] );
		$this->assertNotEmpty( $result['errors'] );
	}

	public function test_ai_cliche_detection(): void {
		$content = "META: A description\n\n# Title\n\n"
		         . "In today's digital age, it goes without saying that "
		         . str_repeat( 'Content padding. ', 200 );

		$result = $this->validator->validate( $content, 'generic' );

		$has_cliche_warning = false;
		foreach ( $result['warnings'] as $warning ) {
			if ( str_contains( $warning, 'Generic AI phrase' ) ) {
				$has_cliche_warning = true;
				break;
			}
		}

		$this->assertTrue( $has_cliche_warning, 'Should detect AI cliché phrases.' );
	}

	public function test_format_report(): void {
		$result = [
			'valid'    => false,
			'errors'   => [ 'Missing H1 title' ],
			'warnings' => [ 'Content too short' ],
		];

		$report = $this->validator->format_report( $result );

		$this->assertStringContainsString( '✗ Status: INVALID', $report );
		$this->assertStringContainsString( 'Missing H1 title', $report );
		$this->assertStringContainsString( 'Content too short', $report );
	}
}
