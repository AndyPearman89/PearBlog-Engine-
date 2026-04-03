<?php
/**
 * Content validator – ensures travel content meets quality standards.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Validates travel content structure and quality.
 */
class ContentValidator {

	/**
	 * Validate travel content structure.
	 *
	 * @param string $content    The HTML content to validate.
	 * @param string $content_type Type: 'travel', 'beskidy', or 'generic'.
	 * @return array{valid: bool, errors: string[], warnings: string[]}
	 */
	public function validate( string $content, string $content_type = 'generic' ): array {
		$errors   = [];
		$warnings = [];

		// Generic validations for all content types.
		$this->validate_meta_description( $content, $errors );
		$this->validate_h1_title( $content, $errors );
		$this->validate_word_count( $content, $warnings );

		// Travel-specific validations.
		if ( 'travel' === $content_type || 'beskidy' === $content_type ) {
			$this->validate_travel_sections( $content, $errors, $warnings );
		}

		// Beskidy-specific validations.
		if ( 'beskidy' === $content_type ) {
			$this->validate_beskidy_sections( $content, $errors, $warnings );
		}

		// Quality checks.
		$this->validate_content_quality( $content, $warnings );

		return [
			'valid'    => empty( $errors ),
			'errors'   => $errors,
			'warnings' => $warnings,
		];
	}

	/**
	 * Validate meta description presence.
	 */
	private function validate_meta_description( string $content, array &$errors ): void {
		if ( ! preg_match( '/^META:\s*(.+)$/mi', $content ) ) {
			$errors[] = 'Missing META description at the beginning of content';
		}
	}

	/**
	 * Validate H1 title presence.
	 */
	private function validate_h1_title( string $content, array &$errors ): void {
		if ( ! preg_match( '/<h1[^>]*>(.+?)<\/h1>/i', $content ) &&
		     ! preg_match( '/^#\s+(.+)$/m', $content ) ) {
			$errors[] = 'Missing H1 title';
		}
	}

	/**
	 * Validate minimum word count.
	 */
	private function validate_word_count( string $content, array &$warnings ): void {
		// Strip HTML and count words.
		$text       = strip_tags( $content );
		$word_count = str_word_count( $text );

		if ( $word_count < 1000 ) {
			$warnings[] = "Content is too short: {$word_count} words (minimum 1,000 recommended)";
		}
	}

	/**
	 * Validate travel-specific sections.
	 */
	private function validate_travel_sections( string $content, array &$errors, array &$warnings ): void {
		$required_sections = [
			'TL;DR'                  => '/(?:<h2[^>]*>|##\s*)TL;?DR/i',
			'Noclegi|Accommodation'  => '/(?:<h2[^>]*>|##\s*)(Noclegi|Where to Stay|Unterkunft)/i',
			'Praktyczne|Practical'   => '/(?:<h2[^>]*>|##\s*)(Praktyczne|Practical|Praktisch)/i',
			'FAQ'                    => '/(?:<h2[^>]*>|##\s*)FAQ/i',
		];

		foreach ( $required_sections as $section => $pattern ) {
			if ( ! preg_match( $pattern, $content ) ) {
				$errors[] = "Missing required section: {$section}";
			}
		}

		// Check for TL;DR bullet points.
		if ( preg_match( '/(?:<h2[^>]*>|##\s*)TL;?DR/i', $content ) ) {
			$tldr_section = $this->extract_section( $content, 'TL;?DR' );
			if ( $tldr_section && ! preg_match( '/<li[^>]*>|^[\*\-]\s/m', $tldr_section ) ) {
				$warnings[] = 'TL;DR section should contain bullet points';
			}
		}
	}

	/**
	 * Validate Beskidy-specific sections.
	 */
	private function validate_beskidy_sections( string $content, array &$errors, array &$warnings ): void {
		$beskidy_sections = [
			'Warunki i pogoda|Weather' => '/(?:<h2[^>]*>|##\s*)(Warunki|Weather|Wetter)/i',
			'Plan dnia|Itinerary'      => '/(?:<h2[^>]*>|##\s*)(Plan dnia|Day Plan|Itinerary|Tagesplan)/i',
		];

		foreach ( $beskidy_sections as $section => $pattern ) {
			if ( ! preg_match( $pattern, $content ) ) {
				$warnings[] = "Missing recommended Beskidy section: {$section}";
			}
		}

		// Check for Plan B alternative.
		if ( preg_match( '/(?:<h2[^>]*>|##\s*)Plan dnia/i', $content ) ) {
			if ( ! preg_match( '/(?:<h3[^>]*>|###\s*)(Plan B|alternatywa|alternative)/i', $content ) ) {
				$warnings[] = 'Day plan should include Plan B alternative for bad weather';
			}
		}
	}

	/**
	 * Validate content quality (detect AI clichés and generic phrases).
	 */
	private function validate_content_quality( string $content, array &$warnings ): void {
		$generic_phrases = [
			'In today\'s digital age',
			'It goes without saying',
			'At the end of the day',
			'In conclusion',
			'Last but not least',
			'Needless to say',
			'It is important to note',
		];

		$text = strip_tags( $content );

		foreach ( $generic_phrases as $phrase ) {
			if ( stripos( $text, $phrase ) !== false ) {
				$warnings[] = "Generic AI phrase detected: '{$phrase}' - consider rewriting";
			}
		}

		// Check for keyword stuffing (same phrase repeated too often).
		$this->check_keyword_stuffing( $text, $warnings );
	}

	/**
	 * Check for potential keyword stuffing.
	 */
	private function check_keyword_stuffing( string $text, array &$warnings ): void {
		// Extract 2-4 word phrases and count their frequency.
		preg_match_all( '/\b(\w+(?:\s+\w+){1,3})\b/u', $text, $matches );

		if ( empty( $matches[1] ) ) {
			return;
		}

		$phrase_counts = array_count_values( $matches[1] );
		$word_count    = str_word_count( $text );

		foreach ( $phrase_counts as $phrase => $count ) {
			// Skip very short phrases.
			if ( strlen( $phrase ) < 10 ) {
				continue;
			}

			// Calculate phrase density.
			$density = ( $count / max( $word_count, 1 ) ) * 100;

			// Flag if phrase appears more than 2% of total words.
			if ( $density > 2.0 && $count > 5 ) {
				$warnings[] = "Potential keyword stuffing: phrase '{$phrase}' appears {$count} times ({$density}% density)";
				break; // Only report first instance.
			}
		}
	}

	/**
	 * Extract a section from content by heading pattern.
	 *
	 * @param string $content The full content.
	 * @param string $heading Heading pattern (without delimiters).
	 * @return string|null    Section content or null if not found.
	 */
	private function extract_section( string $content, string $heading ): ?string {
		// Try HTML h2.
		if ( preg_match( "/<h2[^>]*>{$heading}<\/h2>(.*?)(?=<h2|$)/is", $content, $matches ) ) {
			return $matches[1];
		}

		// Try markdown ##.
		if ( preg_match( "/##\s*{$heading}\s*\n(.*?)(?=\n##|$)/is", $content, $matches ) ) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * Get a human-readable validation report.
	 *
	 * @param array $validation_result Result from validate().
	 * @return string                  Formatted report.
	 */
	public function format_report( array $validation_result ): string {
		$report = "Content Validation Report\n";
		$report .= "========================\n\n";

		if ( $validation_result['valid'] ) {
			$report .= "✓ Status: VALID\n\n";
		} else {
			$report .= "✗ Status: INVALID\n\n";
		}

		if ( ! empty( $validation_result['errors'] ) ) {
			$report .= "ERRORS (" . count( $validation_result['errors'] ) . "):\n";
			foreach ( $validation_result['errors'] as $i => $error ) {
				$report .= sprintf( "  %d. %s\n", $i + 1, $error );
			}
			$report .= "\n";
		}

		if ( ! empty( $validation_result['warnings'] ) ) {
			$report .= "WARNINGS (" . count( $validation_result['warnings'] ) . "):\n";
			foreach ( $validation_result['warnings'] as $i => $warning ) {
				$report .= sprintf( "  %d. %s\n", $i + 1, $warning );
			}
		}

		return $report;
	}
}
