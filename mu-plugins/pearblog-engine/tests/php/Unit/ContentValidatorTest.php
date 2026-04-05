<?php
/**
 * Unit tests for ContentValidator.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\ContentValidator;

class ContentValidatorTest extends TestCase {

	private ContentValidator $validator;

	protected function setUp(): void {
		parent::setUp();
		$this->validator = new ContentValidator();
	}

	// -----------------------------------------------------------------------
	// Generic validation
	// -----------------------------------------------------------------------

	public function test_valid_generic_content_passes(): void {
		$content = $this->build_content(
			has_meta: true,
			has_h1: true,
			word_count: 1200
		);

		$result = $this->validator->validate( $content, 'generic' );

		$this->assertTrue( $result['valid'] );
		$this->assertEmpty( $result['errors'] );
	}

	public function test_missing_meta_description_is_an_error(): void {
		$content = "# My Title\n\n" . str_repeat( 'word ', 1200 );
		$result  = $this->validator->validate( $content, 'generic' );

		$this->assertFalse( $result['valid'] );
		$this->assertStringContainsString( 'META', $result['errors'][0] );
	}

	public function test_missing_h1_is_an_error(): void {
		$content = "META: A fine description\n\n" . str_repeat( 'word ', 1200 );
		$result  = $this->validator->validate( $content, 'generic' );

		$this->assertFalse( $result['valid'] );
		$error_messages = implode( ' ', $result['errors'] );
		$this->assertStringContainsString( 'H1', $error_messages );
	}

	public function test_low_word_count_is_a_warning_not_error(): void {
		$content = "META: Desc\n\n# Title\n\n" . str_repeat( 'word ', 50 );
		$result  = $this->validator->validate( $content, 'generic' );

		$this->assertTrue( $result['valid'] ); // Still valid, just warned.
		$this->assertNotEmpty( $result['warnings'] );
	}

	// -----------------------------------------------------------------------
	// Travel-specific validation
	// -----------------------------------------------------------------------

	public function test_valid_travel_content_passes(): void {
		$content = $this->build_travel_content();
		$result  = $this->validator->validate( $content, 'travel' );

		$this->assertTrue( $result['valid'], implode( ', ', $result['errors'] ) );
	}

	public function test_travel_content_missing_faq_section_fails(): void {
		$content = $this->build_travel_content( include_faq: false );
		$result  = $this->validator->validate( $content, 'travel' );

		$this->assertFalse( $result['valid'] );
		$this->assertStringContainsString( 'FAQ', implode( ' ', $result['errors'] ) );
	}

	// -----------------------------------------------------------------------
	// Quality checks
	// -----------------------------------------------------------------------

	public function test_generic_ai_phrase_detected_as_warning(): void {
		$content = "META: Desc\n\n# Title\n\n" . str_repeat( 'word ', 1200 ) . "\nIn today's digital age, everything changes.";
		$result  = $this->validator->validate( $content, 'generic' );

		$warnings = implode( ' ', $result['warnings'] );
		$this->assertStringContainsString( "In today's digital age", $warnings );
	}

	public function test_format_report_includes_status(): void {
		$result = [ 'valid' => true, 'errors' => [], 'warnings' => [] ];
		$report = $this->validator->format_report( $result );

		$this->assertStringContainsString( 'VALID', $report );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function build_content(
		bool $has_meta   = true,
		bool $has_h1     = true,
		int  $word_count = 1200
	): string {
		$content = '';
		if ( $has_meta ) {
			$content .= "META: A great article about something.\n\n";
		}
		if ( $has_h1 ) {
			$content .= "# The Main Title\n\n";
		}
		$content .= str_repeat( 'word ', $word_count );
		return $content;
	}

	private function build_travel_content( bool $include_faq = true ): string {
		$faq_section = $include_faq ? "\n## FAQ\n\n### Question?\n\nAnswer text.\n\n" : '';

		return "META: A travel article about visiting places.\n\n" .
			"# Visit Amazing Places\n\n" .
			str_repeat( 'word ', 1000 ) .
			"\n## TL;DR\n\n- Point 1\n- Point 2\n\n" .
			"\n## Noclegi\n\nStay here.\n\n" .
			"\n## Praktyczne\n\nUseful tips.\n\n" .
			$faq_section;
	}
}
