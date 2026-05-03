<?php
/**
 * Funnel Stage Detector – classifies content into TOFU/MOFU/BOFU stages.
 *
 * TOFU (Top of Funnel):    Informational, awareness content
 * MOFU (Middle of Funnel): Consideration, comparison content
 * BOFU (Bottom of Funnel): Decision, conversion content
 *
 * @package PearBlogEngine\Monetization
 */

declare(strict_types=1);

namespace PearBlogEngine\Monetization;

/**
 * Analyzes content to determine its position in the marketing funnel.
 *
 * This classification drives AdSense placement strategy:
 * - TOFU content: Full AdSense allowed (informational traffic)
 * - MOFU content: Limited AdSense (light purchase intent)
 * - BOFU content: AdSense disabled (focus on conversions)
 */
class FunnelStageDetector {

	/** @var string[] Keywords indicating TOFU (awareness) content. */
	private const TOFU_KEYWORDS = [
		// Questions & information seeking
		'co to jest', 'czym jest', 'jak działa', 'dlaczego', 'kiedy',
		'gdzie', 'co to', 'historia', 'przewodnik', 'poradnik',
		'informacje', 'fakty', 'dowiedz się', 'poznaj',
		'what is', 'how does', 'why', 'guide', 'tutorial',
		'introduction', 'basics', 'beginner', 'learn', 'understand',
		// Educational content
		'FAQ', 'pytania', 'odpowiedzi', 'edukacja', 'szkolenie',
		'nauka', 'podstawy', 'dla początkujących',
	];

	/** @var string[] Keywords indicating MOFU (consideration) content. */
	private const MOFU_KEYWORDS = [
		// Comparison & research
		'porównanie', 'vs', 'versus', 'alternatywy', 'opcje',
		'różnice', 'podobieństwa', 'zalety', 'wady',
		'comparison', 'alternatives', 'options', 'pros and cons',
		'differences', 'similarities', 'features',
		// Light intent
		'przegląd', 'recenzja', 'opinie', 'oceny',
		'review', 'overview', 'ratings', 'feedback',
	];

	/** @var string[] Keywords indicating BOFU (decision) content. */
	private const BOFU_KEYWORDS = [
		// Decision & conversion
		'ranking', 'najlepsze', 'top', 'wybierz', 'kup',
		'zamów', 'oferta', 'cena', 'cennik', 'promocja',
		'best', 'top rated', 'buy', 'purchase', 'order',
		'pricing', 'deal', 'discount', 'offer',
		// Expert & lead gen
		'ekspert', 'specjalista', 'doradca', 'konsultant',
		'kalkulator', 'wycena', 'kontakt', 'formularz',
		'expert', 'specialist', 'consultant', 'calculator',
		'quote', 'contact', 'form', 'lead',
		// Commercial intent
		'skontaktuj się', 'zapytaj', 'umów', 'rezerwuj',
		'get started', 'sign up', 'book now', 'schedule',
	];

	/**
	 * Detect the funnel stage of the given content.
	 *
	 * @param string $title   Post title.
	 * @param string $content Post content (HTML or plain text).
	 * @return string One of: 'tofu', 'mofu', 'bofu'.
	 */
	public function detect( string $title, string $content ): string {
		$text = mb_strtolower( $title . ' ' . wp_strip_all_tags( $content ) );

		// Calculate keyword match scores for each stage.
		$tofu_score = $this->calculate_score( $text, self::TOFU_KEYWORDS );
		$mofu_score = $this->calculate_score( $text, self::MOFU_KEYWORDS );
		$bofu_score = $this->calculate_score( $text, self::BOFU_KEYWORDS );

		/**
		 * Filter: pearblog_funnel_stage_scores
		 *
		 * Allows external plugins to adjust funnel stage scoring.
		 *
		 * @param array{tofu: int, mofu: int, bofu: int} $scores Keyword match scores.
		 * @param string $title  Post title.
		 * @param string $text   Normalized content text.
		 */
		$scores = (array) apply_filters( 'pearblog_funnel_stage_scores', [
			'tofu' => $tofu_score,
			'mofu' => $mofu_score,
			'bofu' => $bofu_score,
		], $title, $text );

		$tofu_score = (int) ( $scores['tofu'] ?? 0 );
		$mofu_score = (int) ( $scores['mofu'] ?? 0 );
		$bofu_score = (int) ( $scores['bofu'] ?? 0 );

		// BOFU takes priority (conversion intent is strongest signal).
		// Even a single BOFU keyword should override TOFU keywords.
		if ( $bofu_score > 0 && $bofu_score >= $mofu_score ) {
			return 'bofu';
		}

		// MOFU is secondary (consideration phase).
		// MOFU should win if it has any score, even if TOFU score is higher.
		if ( $mofu_score > 0 ) {
			return 'mofu';
		}

		// Default to TOFU (informational content).
		return 'tofu';
	}

	/**
	 * Calculate keyword match score for a given text.
	 *
	 * @param string   $text     Normalized text to search.
	 * @param string[] $keywords Keywords to match.
	 * @return int Number of keyword matches.
	 */
	private function calculate_score( string $text, array $keywords ): int {
		$score = 0;

		foreach ( $keywords as $keyword ) {
			if ( false !== mb_strpos( $text, mb_strtolower( $keyword ) ) ) {
				++$score;
			}
		}

		return $score;
	}

	/**
	 * Check if AdSense should be enabled for the given funnel stage.
	 *
	 * Strategy:
	 * - TOFU: AdSense ON (monetize informational traffic)
	 * - MOFU: AdSense LIMITED (careful placement, fewer ads)
	 * - BOFU: AdSense OFF (focus on lead generation)
	 *
	 * @param string $funnel_stage One of: 'tofu', 'mofu', 'bofu'.
	 * @return bool True if AdSense should be enabled.
	 */
	public function should_enable_adsense( string $funnel_stage ): bool {
		// Read configuration from WordPress options (with sensible defaults).
		$default_strategy = [
			'tofu' => true,   // AdSense ON for informational content
			'mofu' => true,   // AdSense ON but limited for consideration content
			'bofu' => false,  // AdSense OFF for conversion content
		];

		$strategy = [
			'tofu' => (bool) get_option( 'pearblog_adsense_enable_tofu', $default_strategy['tofu'] ),
			'mofu' => (bool) get_option( 'pearblog_adsense_enable_mofu', $default_strategy['mofu'] ),
			'bofu' => (bool) get_option( 'pearblog_adsense_enable_bofu', $default_strategy['bofu'] ),
		];

		/**
		 * Filter: pearblog_adsense_funnel_strategy
		 *
		 * Allows customization of AdSense enablement by funnel stage.
		 *
		 * @param array{tofu: bool, mofu: bool, bofu: bool} $strategy Funnel stage enablement rules.
		 */
		$strategy = (array) apply_filters( 'pearblog_adsense_funnel_strategy', $strategy );

		return (bool) ( $strategy[ $funnel_stage ] ?? false );
	}

	/**
	 * Check if limited AdSense placement should be used.
	 *
	 * Limited placement = fewer ads, strategic positioning only.
	 *
	 * @param string $funnel_stage One of: 'tofu', 'mofu', 'bofu'.
	 * @return bool True if limited placement should be used.
	 */
	public function should_limit_placement( string $funnel_stage ): bool {
		return 'mofu' === $funnel_stage;
	}
}
