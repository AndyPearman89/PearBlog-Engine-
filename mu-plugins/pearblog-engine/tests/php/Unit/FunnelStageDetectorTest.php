<?php
/**
 * Tests for FunnelStageDetector.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PearBlogEngine\Monetization\FunnelStageDetector;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PearBlogEngine\Monetization\FunnelStageDetector
 */
class FunnelStageDetectorTest extends TestCase {

	private FunnelStageDetector $detector;

	protected function setUp(): void {
		parent::setUp();
		$this->detector = new FunnelStageDetector();
	}

	// -----------------------------------------------------------------------
	// TOFU (Top of Funnel) Detection Tests
	// -----------------------------------------------------------------------

	public function test_detects_tofu_content_with_what_is_keyword(): void {
		$title   = 'Co to jest AdSense i jak działa?';
		$content = 'Dowiedz się wszystko o Google AdSense - przewodnik dla początkujących.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'tofu', $stage );
	}

	public function test_detects_tofu_content_with_guide_keyword(): void {
		$title   = 'Kompletny przewodnik po WordPress';
		$content = 'Nauczysz się podstaw WordPress w tym poradniku.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'tofu', $stage );
	}

	public function test_detects_tofu_content_with_faq_keyword(): void {
		$title   = 'FAQ - Najczęściej zadawane pytania o SEO';
		$content = 'Znajdź odpowiedzi na pytania o pozycjonowanie stron.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'tofu', $stage );
	}

	public function test_detects_tofu_english_content(): void {
		$title   = 'What is Machine Learning? A Beginner\'s Guide';
		$content = 'Learn the basics of machine learning and understand how it works.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'tofu', $stage );
	}

	// -----------------------------------------------------------------------
	// MOFU (Middle of Funnel) Detection Tests
	// -----------------------------------------------------------------------

	public function test_detects_mofu_content_with_comparison_keyword(): void {
		$title   = 'WordPress vs Drupal - porównanie CMS';
		$content = 'Przegląd różnic między WordPress a Drupal. Zalety i wady każdego rozwiązania.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'mofu', $stage );
	}

	public function test_detects_mofu_content_with_review_keyword(): void {
		$title   = 'Recenzja iPhone 15 Pro - opinie i oceny';
		$content = 'Przeczytaj szczegółową recenzję iPhone 15 Pro z naszymi opiniami.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'mofu', $stage );
	}

	public function test_detects_mofu_content_with_alternatives_keyword(): void {
		$title   = 'Best alternatives to Photoshop in 2026';
		$content = 'Overview of the top alternatives to Adobe Photoshop with pros and cons.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'mofu', $stage );
	}

	// -----------------------------------------------------------------------
	// BOFU (Bottom of Funnel) Detection Tests
	// -----------------------------------------------------------------------

	public function test_detects_bofu_content_with_ranking_keyword(): void {
		$title   = 'Ranking najlepszych hostingów w Polsce 2026';
		$content = 'Top 10 najlepszych firm hostingowych. Sprawdź ceny i wybierz hosting.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'bofu', $stage );
	}

	public function test_detects_bofu_content_with_best_keyword(): void {
		$title   = 'Best SEO Tools for 2026 - Top Rated';
		$content = 'Find the best SEO tools to buy. Compare pricing and get started today.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'bofu', $stage );
	}

	public function test_detects_bofu_content_with_calculator_keyword(): void {
		$title   = 'Kalkulator kredytu hipotecznego';
		$content = 'Oblicz ratę kredytu. Skontaktuj się z ekspertem kredytowym.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'bofu', $stage );
	}

	public function test_detects_bofu_content_with_expert_keyword(): void {
		$title   = 'Ekspert SEO - konsultacje i wycena';
		$content = 'Umów spotkanie z naszym specjalistą SEO. Wypełnij formularz kontaktowy.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'bofu', $stage );
	}

	// -----------------------------------------------------------------------
	// Edge Cases and Priority Tests
	// -----------------------------------------------------------------------

	public function test_bofu_takes_priority_over_tofu(): void {
		$title   = 'Co to jest hosting? Ranking najlepszych hostingów';
		$content = 'Dowiedz się czym jest hosting (TOFU) i wybierz najlepszy (BOFU).';

		$stage = $this->detector->detect( $title, $content );

		// BOFU should win because conversion intent is strongest
		$this->assertSame( 'bofu', $stage );
	}

	public function test_mofu_takes_priority_over_tofu(): void {
		$title   = 'Co to jest CRM? Porównanie systemów CRM';
		$content = 'Przewodnik po systemach CRM z porównaniem rozwiązań.';

		$stage = $this->detector->detect( $title, $content );

		// MOFU should win because consideration intent is present
		$this->assertSame( 'mofu', $stage );
	}

	public function test_empty_content_defaults_to_tofu(): void {
		$title   = '';
		$content = '';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'tofu', $stage );
	}

	public function test_neutral_content_defaults_to_tofu(): void {
		$title   = 'Historia Internetu';
		$content = 'Internet powstał w latach 60. XX wieku jako projekt ARPANET.';

		$stage = $this->detector->detect( $title, $content );

		// No strong signals, should default to TOFU
		$this->assertSame( 'tofu', $stage );
	}

	// -----------------------------------------------------------------------
	// AdSense Strategy Tests
	// -----------------------------------------------------------------------

	public function test_adsense_enabled_for_tofu_by_default(): void {
		$enabled = $this->detector->should_enable_adsense( 'tofu' );

		$this->assertTrue( $enabled );
	}

	public function test_adsense_enabled_for_mofu_by_default(): void {
		$enabled = $this->detector->should_enable_adsense( 'mofu' );

		$this->assertTrue( $enabled );
	}

	public function test_adsense_disabled_for_bofu_by_default(): void {
		$enabled = $this->detector->should_enable_adsense( 'bofu' );

		$this->assertFalse( $enabled );
	}

	public function test_limited_placement_for_mofu(): void {
		$limited = $this->detector->should_limit_placement( 'mofu' );

		$this->assertTrue( $limited );
	}

	public function test_no_limited_placement_for_tofu(): void {
		$limited = $this->detector->should_limit_placement( 'tofu' );

		$this->assertFalse( $limited );
	}

	public function test_no_limited_placement_for_bofu(): void {
		$limited = $this->detector->should_limit_placement( 'bofu' );

		$this->assertFalse( $limited );
	}

	// -----------------------------------------------------------------------
	// Case Sensitivity Tests
	// -----------------------------------------------------------------------

	public function test_detection_is_case_insensitive(): void {
		$title_lower = 'co to jest wordpress?';
		$title_upper = 'CO TO JEST WORDPRESS?';
		$title_mixed = 'Co To JeSt WordPress?';
		$content     = 'Content';

		$stage_lower = $this->detector->detect( $title_lower, $content );
		$stage_upper = $this->detector->detect( $title_upper, $content );
		$stage_mixed = $this->detector->detect( $title_mixed, $content );

		$this->assertSame( 'tofu', $stage_lower );
		$this->assertSame( 'tofu', $stage_upper );
		$this->assertSame( 'tofu', $stage_mixed );
	}

	// -----------------------------------------------------------------------
	// Multi-language Tests
	// -----------------------------------------------------------------------

	public function test_detects_polish_tofu_keywords(): void {
		$keywords = [ 'co to jest', 'jak działa', 'przewodnik', 'poradnik', 'podstawy' ];

		foreach ( $keywords as $keyword ) {
			$stage = $this->detector->detect( $keyword, '' );
			$this->assertSame( 'tofu', $stage, "Failed for keyword: {$keyword}" );
		}
	}

	public function test_detects_english_tofu_keywords(): void {
		$keywords = [ 'what is', 'how does', 'guide', 'tutorial', 'basics' ];

		foreach ( $keywords as $keyword ) {
			$stage = $this->detector->detect( $keyword, '' );
			$this->assertSame( 'tofu', $stage, "Failed for keyword: {$keyword}" );
		}
	}

	public function test_detects_polish_bofu_keywords(): void {
		$keywords = [ 'ranking', 'najlepsze', 'kalkulator', 'ekspert', 'kup' ];

		foreach ( $keywords as $keyword ) {
			$stage = $this->detector->detect( $keyword, '' );
			$this->assertSame( 'bofu', $stage, "Failed for keyword: {$keyword}" );
		}
	}

	public function test_detects_english_bofu_keywords(): void {
		$keywords = [ 'best', 'top rated', 'buy', 'calculator', 'expert' ];

		foreach ( $keywords as $keyword ) {
			$stage = $this->detector->detect( $keyword, '' );
			$this->assertSame( 'bofu', $stage, "Failed for keyword: {$keyword}" );
		}
	}

	// -----------------------------------------------------------------------
	// Real-World Content Examples
	// -----------------------------------------------------------------------

	public function test_real_world_tofu_article(): void {
		$title   = 'Jak założyć bloga w 2026 roku? Kompletny przewodnik';
		$content = 'Dowiedz się jak założyć swojego bloga krok po kroku. Nauczysz się ' .
				   'podstaw WordPressa i zrozumiesz jak działa SEO dla początkujących. ' .
				   'Ten poradnik przeprowadzi Cię przez cały proces tworzenia bloga.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'tofu', $stage );
		$this->assertTrue( $this->detector->should_enable_adsense( $stage ) );
		$this->assertFalse( $this->detector->should_limit_placement( $stage ) );
	}

	public function test_real_world_mofu_article(): void {
		$title   = 'WordPress vs Wix - porównanie platform do tworzenia stron';
		$content = 'Szczegółowa recenzja i porównanie WordPress i Wix. Sprawdzamy zalety ' .
				   'i wady każdej platformy, porównujemy opcje i funkcje. Dowiedz się, ' .
				   'która platforma jest lepsza dla Twojego projektu.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'mofu', $stage );
		$this->assertTrue( $this->detector->should_enable_adsense( $stage ) );
		$this->assertTrue( $this->detector->should_limit_placement( $stage ) );
	}

	public function test_real_world_bofu_article(): void {
		$title   = 'Ranking 10 Najlepszych Hostingów WordPress 2026';
		$content = 'Top 10 najlepszych hostingów WordPress w Polsce. Sprawdź ceny, ' .
				   'zamów hosting już dziś. Kalkulator kosztów i porównanie ofert. ' .
				   'Skontaktuj się z ekspertem i wybierz idealne rozwiązanie dla Twojej strony.';

		$stage = $this->detector->detect( $title, $content );

		$this->assertSame( 'bofu', $stage );
		$this->assertFalse( $this->detector->should_enable_adsense( $stage ) );
		$this->assertFalse( $this->detector->should_limit_placement( $stage ) );
	}
}
