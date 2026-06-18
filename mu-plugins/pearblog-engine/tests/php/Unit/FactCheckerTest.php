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
		$GLOBALS['_options']   = [];
		$GLOBALS['_post_meta'] = [];
		$this->checker = new FactChecker();
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_option_enabled_constant(): void {
		$this->assertSame( 'pearblog_factcheck_enabled', FactChecker::OPTION_ENABLED );
	}

	public function test_option_api_constant(): void {
		$this->assertSame( 'pearblog_factcheck_api', FactChecker::OPTION_API );
	}

	public function test_option_api_key_constant(): void {
		$this->assertSame( 'pearblog_factcheck_api_key', FactChecker::OPTION_API_KEY );
	}

	public function test_option_threshold_constant(): void {
		$this->assertSame( 'pearblog_factcheck_threshold', FactChecker::OPTION_THRESHOLD );
	}

	public function test_meta_results_constant(): void {
		$this->assertSame( 'pearblog_factcheck_results', FactChecker::META_RESULTS );
	}

	public function test_meta_warnings_constant(): void {
		$this->assertSame( 'pearblog_factcheck_warnings', FactChecker::META_WARNINGS );
	}

	public function test_default_threshold(): void {
		$this->assertEqualsWithDelta( 0.6, FactChecker::DEFAULT_THRESHOLD, 0.001 );
	}

	public function test_unverified_marker_contains_warning_text(): void {
		$this->assertStringContainsString( 'FACT CHECK', FactChecker::UNVERIFIED_MARKER );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_false_by_default(): void {
		$this->assertFalse( $this->checker->is_enabled() );
	}

	public function test_is_enabled_false_when_no_api_key(): void {
		$GLOBALS['_options'][ FactChecker::OPTION_ENABLED ] = true;
		$this->assertFalse( $this->checker->is_enabled() );
	}

	public function test_is_enabled_true_when_both_configured(): void {
		$GLOBALS['_options'][ FactChecker::OPTION_ENABLED ] = true;
		$GLOBALS['_options'][ FactChecker::OPTION_API_KEY ] = 'brave_key_123';
		$this->assertTrue( $this->checker->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// is_factual_claim
	// -----------------------------------------------------------------------

	public function test_is_factual_claim_false_for_short_sentence(): void {
		$this->assertFalse( $this->checker->is_factual_claim( 'Short.' ) );
	}

	public function test_is_factual_claim_false_for_long_sentence(): void {
		$this->assertFalse( $this->checker->is_factual_claim( str_repeat( 'a', 301 ) ) );
	}

	public function test_is_factual_claim_true_for_percentage(): void {
		$this->assertTrue( $this->checker->is_factual_claim( 'Inflation reached 8.5% in the last quarter of 2023.' ) );
	}

	public function test_is_factual_claim_true_for_pln_amount(): void {
		$this->assertTrue( $this->checker->is_factual_claim( 'Koszt budowy domu wynosi 5000 zł za metr kwadratowy.' ) );
	}

	public function test_is_factual_claim_true_for_mln_unit(): void {
		$this->assertTrue( $this->checker->is_factual_claim( 'Firma osiągnęła 2 mln przychodów w tym roku.' ) );
	}

	public function test_is_factual_claim_true_for_year_range(): void {
		$this->assertTrue( $this->checker->is_factual_claim( 'Projekt realizowany był w latach 2020-2024 przez dużą firmę budowlaną.' ) );
	}

	public function test_is_factual_claim_false_for_plain_text(): void {
		$this->assertFalse( $this->checker->is_factual_claim( 'This is a general statement without any numbers or units.' ) );
	}

	public function test_is_factual_claim_true_for_decimal_number(): void {
		$this->assertTrue( $this->checker->is_factual_claim( 'Temperatura wynosi 36.6 stopni Celsjusza na zewnątrz.' ) );
	}

	// -----------------------------------------------------------------------
	// extract_claims
	// -----------------------------------------------------------------------

	public function test_extract_claims_returns_empty_for_empty_content(): void {
		$claims = $this->checker->extract_claims( '' );
		$this->assertSame( [], $claims );
	}

	public function test_extract_claims_returns_only_factual_claims(): void {
		$content = 'This is a plain sentence. Inflation is at 5% this year. Another plain statement.';
		$claims  = $this->checker->extract_claims( $content );
		$this->assertNotEmpty( $claims );
		foreach ( $claims as $claim ) {
			$this->assertTrue( $this->checker->is_factual_claim( $claim ) );
		}
	}

	public function test_extract_claims_limits_to_10(): void {
		$sentences = '';
		for ( $i = 0; $i < 15; $i++ ) {
			$sentences .= "Produkt kosztuje {$i}000 zł za sztukę i jest bardzo popularny. ";
		}
		$claims = $this->checker->extract_claims( $sentences );
		$this->assertLessThanOrEqual( 10, count( $claims ) );
	}

	public function test_extract_claims_strips_html_tags(): void {
		$content = '<p>Produkt <strong>kosztuje 1000 zł</strong> za sztukę w Polsce.</p>';
		$claims  = $this->checker->extract_claims( $content );
		$this->assertNotEmpty( $claims );
		// The extracted claim should not contain HTML tags.
		foreach ( $claims as $claim ) {
			$this->assertStringNotContainsString( '<', $claim );
		}
	}

	// -----------------------------------------------------------------------
	// check_and_annotate
	// -----------------------------------------------------------------------

	public function test_check_and_annotate_returns_unchanged_when_disabled(): void {
		$content = '<p>Produkt kosztuje 1000 zł za sztukę.</p>';
		$result  = $this->checker->check_and_annotate( 1, $content );
		$this->assertSame( $content, $result );
	}

	public function test_check_and_annotate_stores_results_in_meta(): void {
		$GLOBALS['_options'][ FactChecker::OPTION_ENABLED ] = true;
		$GLOBALS['_options'][ FactChecker::OPTION_API_KEY ] = 'brave_key';
		// With no real API response, claims won't be verified.
		$content = 'Produkt kosztuje 2000 zł za sztukę w Warszawie.';
		$this->checker->check_and_annotate( 5, $content );
		// Post meta should be updated.
		$this->assertArrayHasKey( 5, $GLOBALS['_post_meta'] );
	}

	// -----------------------------------------------------------------------
	// verify_claim
	// -----------------------------------------------------------------------

	public function test_verify_claim_returns_correct_structure(): void {
		$claim  = 'Inflacja wynosi 8% w Polsce w roku 2023.';
		$result = $this->checker->verify_claim( $claim );
		$this->assertArrayHasKey( 'claim', $result );
		$this->assertArrayHasKey( 'verified', $result );
		$this->assertArrayHasKey( 'confidence', $result );
		$this->assertArrayHasKey( 'source', $result );
	}

	public function test_verify_claim_returns_unverified_when_no_api_key(): void {
		$claim  = 'Cena wynosi 500 zł za sztukę w tym kwartale.';
		$result = $this->checker->verify_claim( $claim );
		$this->assertFalse( $result['verified'] );
	}

	public function test_verify_claim_contains_original_claim(): void {
		$claim  = 'Produkt kosztuje 1500 zł za metr kwadratowy.';
		$result = $this->checker->verify_claim( $claim );
		$this->assertSame( $claim, $result['claim'] );
	}

	public function test_verify_claim_confidence_between_0_and_1(): void {
		$claim  = 'PKB wzrósł o 3.5% w roku 2023 według raportu.';
		$result = $this->checker->verify_claim( $claim );
		$this->assertGreaterThanOrEqual( 0.0, $result['confidence'] );
		$this->assertLessThanOrEqual( 1.0, $result['confidence'] );
	}
}
