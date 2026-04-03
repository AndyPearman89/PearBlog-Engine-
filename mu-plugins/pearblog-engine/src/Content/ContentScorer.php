<?php
/**
 * Content scorer – analyses generated content and produces a {@see ContentScore}.
 *
 * Scoring rubric
 * ──────────────
 *
 * Length dimension (0–40 pts):
 *   ≥ 1 500 words → 40  (excellent)
 *   ≥ 1 000 words → 30  (good)
 *   ≥   600 words → 20  (acceptable)
 *   ≥   300 words → 10  (thin)
 *            < 300 →  0  (too short)
 *
 * Structure dimension (0–40 pts):
 *   H2 headings (≥ 3)           → 15 pts
 *   H3 headings (≥ 2)           → 10 pts
 *   Paragraphs   (≥ 5)          → 10 pts
 *   Lists (ul/ol or markdown)   →  5 pts
 *
 * Quality dimension (0–20 pts):
 *   META: description present   →  8 pts
 *   H1 title present            →  7 pts
 *   CTA paragraph present       →  5 pts
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Stateless content scorer.
 */
class ContentScorer {

	/** @var int Minimum score required to pass (configurable per tenant). */
	private int $min_score;

	public function __construct( int $min_score = 50 ) {
		$this->min_score = max( 0, min( $min_score, 100 ) );
	}

	/**
	 * Analyse content and return a {@see ContentScore}.
	 *
	 * @param string $content Raw content (HTML or Markdown).
	 * @return ContentScore
	 */
	public function score( string $content ): ContentScore {
		$issues = [];

		[ $length_pts,    $length_issues    ] = $this->score_length( $content );
		[ $structure_pts, $structure_issues ] = $this->score_structure( $content );
		[ $quality_pts,   $quality_issues   ] = $this->score_quality( $content );

		$issues = array_merge( $length_issues, $structure_issues, $quality_issues );

		return new ContentScore(
			length:    $length_pts,
			structure: $structure_pts,
			quality:   $quality_pts,
			min_score: $this->min_score,
			issues:    $issues,
		);
	}

	// -----------------------------------------------------------------------
	// Scoring dimensions
	// -----------------------------------------------------------------------

	/**
	 * Score content based on word count.
	 *
	 * @return array{0: int, 1: string[]}  [points, issues]
	 */
	private function score_length( string $content ): array {
		$word_count = $this->word_count( $content );
		$issues     = [];

		if ( $word_count >= 1500 ) {
			$pts = 40;
		} elseif ( $word_count >= 1000 ) {
			$pts = 30;
		} elseif ( $word_count >= 600 ) {
			$pts = 20;
		} elseif ( $word_count >= 300 ) {
			$pts = 10;
			$issues[] = sprintf( 'Content is thin (%d words). Aim for 1 000+.', $word_count );
		} else {
			$pts      = 0;
			$issues[] = sprintf( 'Content is too short (%d words). Minimum 300 required.', $word_count );
		}

		return [ $pts, $issues ];
	}

	/**
	 * Score content based on heading/paragraph/list structure.
	 *
	 * @return array{0: int, 1: string[]}
	 */
	private function score_structure( string $content ): array {
		$pts    = 0;
		$issues = [];

		$h2_count  = $this->count_pattern( $content, '/<h2[\s>]|^##\s/mi' );
		$h3_count  = $this->count_pattern( $content, '/<h3[\s>]|^###\s/mi' );
		$p_count   = $this->count_pattern( $content, '/<\/p>|^\n\n/mi' );
		$has_lists = (bool) preg_match( '/<[ou]l[\s>]|^\s*[-*]\s/mi', $content );

		if ( $h2_count >= 3 ) {
			$pts += 15;
		} else {
			$issues[] = sprintf( 'Only %d H2 headings found; aim for at least 3.', $h2_count );
		}

		if ( $h3_count >= 2 ) {
			$pts += 10;
		} else {
			$issues[] = sprintf( 'Only %d H3 headings found; aim for at least 2.', $h3_count );
		}

		if ( $p_count >= 5 ) {
			$pts += 10;
		} else {
			$issues[] = 'Content has fewer than 5 paragraphs.';
		}

		if ( $has_lists ) {
			$pts += 5;
		} else {
			$issues[] = 'No lists (ul/ol) detected; consider adding bullet points.';
		}

		return [ $pts, $issues ];
	}

	/**
	 * Score presence of essential SEO quality signals.
	 *
	 * @return array{0: int, 1: string[]}
	 */
	private function score_quality( string $content ): array {
		$pts    = 0;
		$issues = [];

		$has_meta = (bool) preg_match( '/^META:\s*.+/mi', $content );
		$has_h1   = (bool) preg_match( '/^#\s.+|<h1[\s>]/mi', $content );
		$has_cta  = (bool) preg_match( '/call.to.action|click here|learn more|get started|sign up|subscribe|contact us/i', $content );

		if ( $has_meta ) {
			$pts += 8;
		} else {
			$issues[] = 'META description line missing (prefix a line with "META:").';
		}

		if ( $has_h1 ) {
			$pts += 7;
		} else {
			$issues[] = 'H1 title missing.';
		}

		if ( $has_cta ) {
			$pts += 5;
		} else {
			$issues[] = 'No call-to-action detected.';
		}

		return [ $pts, $issues ];
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function word_count( string $content ): int {
		$plain = wp_strip_all_tags( $content );
		return str_word_count( $plain );
	}

	private function count_pattern( string $content, string $pattern ): int {
		return (int) preg_match_all( $pattern, $content );
	}
}
