<?php
/**
 * Unit tests for FactChecker.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\AI\FactChecker;

class FactCheckerTest extends TestCase {

	private FactChecker $checker;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_post_meta']  = [];
		$this->checker          = new FactChecker();
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_returns_false_by_default(): void {
		$this->assertFalse( $this->checker->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_enabled_but_no_api_key(): void {
		$GLOBALS['_options'][ FactChecker::OPTION_ENABLED ] = true;
		$this->assertFalse( $this->checker->is_enabled() );
	}

	public function test_is_enabled_returns_false_when_api_key_set_but_disabled(): void {
		$GLOBALS['_options'][ FactChecker::OPTION_ENABLED ] = false;
		$GLOBALS['_options'][ FactChecker::OPTION_API_KEY ] = 'test-key';
		$this->assertFalse( $this->checker->is_enabled() );
	}

	public function test_is_enabled_returns_true_when_enabled_and_api_key_set(): void {
		$GLOBALS['_options'][ FactChecker::OPTION_ENABLED ] = true;
		$GLOBALS['_options'][ FactChecker::OPTION_API_KEY ] = 'brave-api-key-123';
		$this->assertTrue( $this->checker->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// is_factual_claim
	// -----------------------------------------------------------------------

	public function test_is_factual_claim_returns_true_for_sentence_with_pln_unit(): void {
		$this->assertTrue( $this->checker->is_factual_claim( 'Koszt instalacji wynosi 12 500 PLN rocznie.' ) );
	}

	public function test_is_factual_claim_returns_true_for_sentence_with_eur_amount(): void {
		$this->assertTrue( $this->checker->is_factual_claim( 'Inwestycja kosztowała 500 EUR w tym roku.' ) );
	}

	public function test_is_factual_claim_returns_true_for_year_range(): void {
		// Hyphen-dash year ranges match (\d{4}-\d{4}).
		$this->assertTrue( $this->checker->is_factual_claim( 'Projekt był realizowany w latach 2020-2023.' ) );
	}

	public function test_is_factual_claim_returns_false_for_short_sentence(): void {
		$this->assertFalse( $this->checker->is_factual_claim( 'Tak.' ) );
	}

	public function test_is_factual_claim_returns_false_for_very_long_sentence(): void {
		$long = str_repeat( 'a ', 200 ); // > 300 chars
		$this->assertFalse( $this->checker->is_factual_claim( $long ) );
	}

	public function test_is_factual_claim_returns_false_for_opinion_sentence(): void {
		$this->assertFalse( $this->checker->is_factual_claim( 'Nowoczesne technologie zmieniają nasz świat na lepsze.' ) );
	}

	public function test_is_factual_claim_returns_true_for_sentence_with_ponad(): void {
		$this->assertTrue( $this->checker->is_factual_claim( 'Koszt remontu przekroczył ponad 50 000 złotych.' ) );
	}

	public function test_is_factual_claim_returns_true_for_sentence_with_mln(): void {
		$this->assertTrue( $this->checker->is_factual_claim( 'Inwestycja wyniosła 2 mln euro w tym kwartale.' ) );
	}

	// -----------------------------------------------------------------------
	// extract_claims
	// -----------------------------------------------------------------------

	public function test_extract_claims_returns_empty_array_for_plain_text(): void {
		$content = 'To jest artykuł o WordPress. Pomaga w tworzeniu stron.';
		$claims  = $this->checker->extract_claims( $content );
		$this->assertSame( [], $claims );
	}

	public function test_extract_claims_finds_mln_sentence(): void {
		$content = 'Inwestycja wyniosła 2 mln EUR w tym roku. To jest znacząca zmiana dla rynku.';
		$claims  = $this->checker->extract_claims( $content );
		$this->assertNotEmpty( $claims );
		$this->assertStringContainsString( 'mln', $claims[0] );
	}

	public function test_extract_claims_strips_html_tags(): void {
		$content = '<p>Koszt wynosi <strong>5 kg</strong> za sztukę.</p>';
		$claims  = $this->checker->extract_claims( $content );
		$this->assertNotEmpty( $claims );
	}

	public function test_extract_claims_limits_to_ten_results(): void {
		// Build 15 factual sentences using mln (a reliably matching unit).
		$sentences = [];
		for ( $i = 1; $i <= 15; $i++ ) {
			$sentences[] = "Wartość projektu numer {$i} wynosi {$i} mln EUR w tym kwartale.";
		}
		$content = implode( ' ', $sentences );
		$claims  = $this->checker->extract_claims( $content );
		$this->assertCount( 10, $claims );
	}

	public function test_extract_claims_returns_array(): void {
		$claims = $this->checker->extract_claims( 'No factual claims here.' );
		$this->assertIsArray( $claims );
	}

	// -----------------------------------------------------------------------
	// check_and_annotate — disabled
	// -----------------------------------------------------------------------

	public function test_check_and_annotate_returns_unchanged_content_when_disabled(): void {
		$content = '<p>Artykuł z faktem: inwestycja wyniosła 20 mln EUR w tym roku.</p>';
		$result  = $this->checker->check_and_annotate( 1, $content );
		$this->assertSame( $content, $result );
	}

	public function test_check_and_annotate_does_not_write_meta_when_disabled(): void {
		$content = 'Tekst z wartością 15 kg materiału.';
		$this->checker->check_and_annotate( 99, $content );
		$this->assertEmpty( $GLOBALS['_post_meta'] );
	}

	// -----------------------------------------------------------------------
	// constants
	// -----------------------------------------------------------------------

	public function test_unverified_marker_constant_contains_warning_text(): void {
		$this->assertStringContainsString( 'FACT CHECK NEEDED', FactChecker::UNVERIFIED_MARKER );
	}

	public function test_meta_results_constant_is_defined(): void {
		$this->assertSame( 'pearblog_factcheck_results', FactChecker::META_RESULTS );
	}

	public function test_meta_warnings_constant_is_defined(): void {
		$this->assertSame( 'pearblog_factcheck_warnings', FactChecker::META_WARNINGS );
	}

	public function test_default_threshold_is_between_zero_and_one(): void {
		$this->assertGreaterThan( 0.0, FactChecker::DEFAULT_THRESHOLD );
		$this->assertLessThanOrEqual( 1.0, FactChecker::DEFAULT_THRESHOLD );
	}

	// -----------------------------------------------------------------------
	// verify_claim — disabled (no api key → unverified result)
	// -----------------------------------------------------------------------

	public function test_verify_claim_returns_expected_structure(): void {
		// No API key set → verify_via_brave returns unverified_result.
		$result = $this->checker->verify_claim( 'Ceny wzrosły o 10% w tym roku.' );

		$this->assertArrayHasKey( 'claim', $result );
		$this->assertArrayHasKey( 'verified', $result );
		$this->assertArrayHasKey( 'confidence', $result );
		$this->assertArrayHasKey( 'source', $result );
	}

	public function test_verify_claim_returns_false_when_no_api_key(): void {
		$result = $this->checker->verify_claim( 'Wartość wynosi 5 000 zł.' );
		$this->assertFalse( $result['verified'] );
		$this->assertSame( 0.0, $result['confidence'] );
	}

	public function test_verify_claim_preserves_claim_text(): void {
		$claim  = 'Stopa bezrobocia spadła do 3,5% w marcu.';
		$result = $this->checker->verify_claim( $claim );
		$this->assertSame( $claim, $result['claim'] );
	}
}
