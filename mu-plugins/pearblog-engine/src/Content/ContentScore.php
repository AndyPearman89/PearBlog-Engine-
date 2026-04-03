<?php
/**
 * Content score – value object representing the result of content scoring.
 *
 * The score is split into three dimensions:
 *
 *  - Length    (0–40 pts)  – rewards articles that meet the word-count target.
 *  - Structure (0–40 pts)  – rewards well-organised content (headings, paragraphs, lists).
 *  - Quality   (0–20 pts)  – rewards presence of SEO elements (meta, H1, CTA).
 *
 * Total: 0–100 pts.  A content item passes publishing if total >= min_score.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Immutable content scoring result.
 */
class ContentScore {

	/** Maximum points for length scoring. */
	public const MAX_LENGTH    = 40;

	/** Maximum points for structure scoring. */
	public const MAX_STRUCTURE = 40;

	/** Maximum points for quality scoring. */
	public const MAX_QUALITY   = 20;

	/** Total possible score. */
	public const MAX_TOTAL     = self::MAX_LENGTH + self::MAX_STRUCTURE + self::MAX_QUALITY;

	/** @var int Points awarded for content length (0–40). */
	public readonly int $length;

	/** @var int Points awarded for content structure (0–40). */
	public readonly int $structure;

	/** @var int Points awarded for content quality signals (0–20). */
	public readonly int $quality;

	/** @var int Total score (0–100). */
	public readonly int $total;

	/** @var bool Whether the total meets or exceeds the required min_score. */
	public readonly bool $passes;

	/** @var string[] Human-readable list of identified issues. */
	public readonly array $issues;

	public function __construct(
		int    $length,
		int    $structure,
		int    $quality,
		int    $min_score,
		array  $issues = [],
	) {
		$this->length    = max( 0, min( $length,    self::MAX_LENGTH ) );
		$this->structure = max( 0, min( $structure, self::MAX_STRUCTURE ) );
		$this->quality   = max( 0, min( $quality,   self::MAX_QUALITY ) );
		$this->total     = $this->length + $this->structure + $this->quality;
		$this->passes    = $this->total >= $min_score;
		$this->issues    = array_values( $issues );
	}

	/**
	 * Human-readable one-line summary.
	 */
	public function summary(): string {
		$status = $this->passes ? '✓ PASS' : '✗ FAIL';
		return sprintf(
			'Score: %d/100 [Length: %d/%d | Structure: %d/%d | Quality: %d/%d] %s',
			$this->total,
			$this->length,    self::MAX_LENGTH,
			$this->structure, self::MAX_STRUCTURE,
			$this->quality,   self::MAX_QUALITY,
			$status,
		);
	}
}
