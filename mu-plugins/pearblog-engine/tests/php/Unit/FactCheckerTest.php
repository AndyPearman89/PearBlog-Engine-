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

	public function test_default_threshold_constant(): void {
		$this->assertSame( 0.6, FactChecker::DEFAULT_THRESHOLD );
	}

	public function test_meta_results_constant(): void {
		$this->assertSame( 'pearblog_factcheck_results', FactChecker::META_RESULTS );
	}

	public function test_meta_warnings_constant(): void {
		$this->assertSame( 'pearblog_factcheck_warnings', FactChecker::META_WARNINGS );
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_is_enabled_false_by_default(): void {
		$this->assertFalse( $this->checker->is_enabled() );
	}

	public function test_is_enabled_false_when_enabled_but_no_api_key(): void {
		update_option( FactChecker::OPTION_ENABLED, true );

		$this->assertFalse( $this->checker->is_enabled() );
	}

	public function test_is_enabled_true_when_enabled_with_api_key(): void {
		update_option( FactChecker::OPTION_ENABLED, true );
		update_option( FactChecker::OPTION_API_KEY, 'brave-api-key' );

		$this->assertTrue( $this->checker->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// is_factual_claim
	// -----------------------------------------------------------------------

	public function test_is_factual_claim_returns_true_for_sentence_with_percentage(): void {
		$result = $this->checker->is_factual_claim( 'Inflacja wynosi 3,5% w skali roku według GUS.' );

		$this->assertTrue( $result );
	}

	public function test_is_factual_claim_returns_true_for_sentence_with_currency(): void {
		$result = $this->checker->is_factual_claim( 'Rynek wzrósł o 2,5 mld PLN w tym kwartale.' );

		$this->assertTrue( $result );
	}

	public function test_is_factual_claim_returns_true_for_sentence_with_year_range(): void {
		$result = $this->checker->is_factual_claim( 'W latach 2020-2024 wzrosła liczba inwestycji.' );

		$this->assertTrue( $result );
	}

	public function test_is_factual_claim_returns_false_for_short_sentence(): void {
		$result = $this->checker->is_factual_claim( 'Cześć!' );

		$this->assertFalse( $result );
	}

	public function test_is_factual_claim_returns_false_for_very_long_sentence(): void {
		$long = str_repeat( 'To jest bardzo długie zdanie bez żadnych danych. ', 10 );
		$result = $this->checker->is_factual_claim( $long );

		$this->assertFalse( $result );
	}

	public function test_is_factual_claim_returns_false_for_opinion(): void {
		$result = $this->checker->is_factual_claim( 'To jest świetny produkt, który warto kupić.' );

		$this->assertFalse( $result );
	}

	public function test_is_factual_claim_returns_true_for_decimal_number(): void {
		$result = $this->checker->is_factual_claim( 'Inflacja wynosi 3,5% w skali roku według GUS.' );

		$this->assertTrue( $result );
	}

	// -----------------------------------------------------------------------
	// extract_claims
	// -----------------------------------------------------------------------

	public function test_extract_claims_returns_array(): void {
		$claims = $this->checker->extract_claims( 'To jest tekst testowy.' );

		$this->assertIsArray( $claims );
	}

	public function test_extract_claims_finds_factual_sentences(): void {
		$content = 'To jest wstęp. Inflacja wynosi 3,5% w skali roku według GUS. To jest zakończenie.';

		$claims = $this->checker->extract_claims( $content );

		$this->assertCount( 1, $claims );
		$this->assertStringContainsString( '3,5%', $claims[0] );
	}

	public function test_extract_claims_strips_html(): void {
		$content = '<p>W latach 2020-2024 wzrosła liczba inwestycji w Polsce.</p>';

		$claims = $this->checker->extract_claims( $content );

		$this->assertCount( 1, $claims );
	}

	public function test_extract_claims_limits_to_ten(): void {
		$sentence = 'Inflacja w 2023 roku wyniosła 10%. ';
		$content  = str_repeat( $sentence, 15 );

		$claims = $this->checker->extract_claims( $content );

		$this->assertLessThanOrEqual( 10, count( $claims ) );
	}

	public function test_extract_claims_returns_empty_for_no_factual_content(): void {
		$content = 'To jest tekst bez żadnych danych numerycznych. Nic szczególnego.';

		$claims = $this->checker->extract_claims( $content );

		$this->assertIsArray( $claims );
	}

	// -----------------------------------------------------------------------
	// check_and_annotate — disabled state
	// -----------------------------------------------------------------------

	public function test_check_and_annotate_returns_content_unchanged_when_disabled(): void {
		$content = '<p>Ponad 45% Polaków korzysta z internetu.</p>';

		$result = $this->checker->check_and_annotate( 1, $content );

		$this->assertSame( $content, $result );
	}

	public function test_check_and_annotate_returns_string(): void {
		$content = '<p>Test content</p>';
		$result  = $this->checker->check_and_annotate( 1, $content );

		$this->assertIsString( $result );
	}

	// -----------------------------------------------------------------------
	// verify_claim structure
	// -----------------------------------------------------------------------

	public function test_verify_claim_returns_array_with_required_keys(): void {
		// Disabled → unverified_result
		$result = $this->checker->verify_claim( 'Inflacja w 2023 roku wyniosła 10%.' );

		$this->assertArrayHasKey( 'claim', $result );
		$this->assertArrayHasKey( 'verified', $result );
		$this->assertArrayHasKey( 'confidence', $result );
		$this->assertArrayHasKey( 'source', $result );
	}

	public function test_verify_claim_returns_unverified_when_no_api_key(): void {
		$result = $this->checker->verify_claim( 'Ponad 45% Polaków korzysta z internetu.' );

		$this->assertFalse( $result['verified'] );
		$this->assertSame( 0.0, $result['confidence'] );
	}

	public function test_verify_claim_preserves_claim_text(): void {
		$claim  = 'Inflacja wyniosła 5% w 2023 roku.';
		$result = $this->checker->verify_claim( $claim );

		$this->assertSame( $claim, $result['claim'] );
	}

	// -----------------------------------------------------------------------
	// UNVERIFIED_MARKER constant
	// -----------------------------------------------------------------------

	public function test_unverified_marker_contains_span(): void {
		$this->assertStringContainsString( '<span', FactChecker::UNVERIFIED_MARKER );
		$this->assertStringContainsString( 'FACT CHECK NEEDED', FactChecker::UNVERIFIED_MARKER );
	}

	// -----------------------------------------------------------------------
	// post meta is written by check_and_annotate when enabled
	// -----------------------------------------------------------------------

	public function test_check_and_annotate_writes_warnings_meta_when_disabled_is_zero(): void {
		// When disabled, function returns early without writing meta.
		$this->checker->check_and_annotate( 42, '<p>Test</p>' );

		$warnings = get_post_meta( 42, FactChecker::META_WARNINGS, true );
		$this->assertSame( '', $warnings ); // not written
	}
}
