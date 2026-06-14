<?php
/**
 * Unit tests for PIIDetector.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Security\PIIDetector;

class PIIDetectorTest extends TestCase {

	private PIIDetector $detector;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_post_meta'] = [];
		$GLOBALS['_options']   = [];
		$this->detector        = new PIIDetector();
	}

	// -----------------------------------------------------------------------
	// scan — clean content
	// -----------------------------------------------------------------------

	public function test_scan_returns_no_findings_for_clean_content(): void {
		$result = $this->detector->scan( 'This article discusses WordPress best practices.' );

		$this->assertFalse( $result['found'] );
		$this->assertSame( [], $result['types'] );
		$this->assertSame( [], $result['findings'] );
	}

	// -----------------------------------------------------------------------
	// scan — email detection
	// -----------------------------------------------------------------------

	public function test_scan_detects_email_address(): void {
		$result = $this->detector->scan( 'Contact us at john.doe@company.pl for support.' );

		$this->assertTrue( $result['found'] );
		$this->assertContains( 'email', $result['types'] );
		$this->assertNotEmpty( $result['findings']['email'] );
	}

	public function test_scan_ignores_example_com_email(): void {
		$result = $this->detector->scan( 'Send a message to admin@example.com.' );

		// example.com emails are allowlisted — should NOT be flagged.
		$this->assertNotContains( 'email', $result['types'] );
	}

	public function test_scan_ignores_test_com_email(): void {
		$result = $this->detector->scan( 'Contact test@test.com for demo.' );

		$this->assertNotContains( 'email', $result['types'] );
	}

	// -----------------------------------------------------------------------
	// scan — PESEL detection
	// -----------------------------------------------------------------------

	public function test_scan_detects_pesel_number(): void {
		$result = $this->detector->scan( 'PESEL obywatela: 85030612345.' );

		$this->assertTrue( $result['found'] );
		$this->assertContains( 'pesel', $result['types'] );
	}

	// -----------------------------------------------------------------------
	// scan — phone number detection
	// -----------------------------------------------------------------------

	public function test_scan_detects_polish_phone_number(): void {
		$result = $this->detector->scan( 'Zadzwoń pod numer 600 123 456 po szczegóły.' );

		$this->assertTrue( $result['found'] );
		$this->assertContains( 'phone_pl', $result['types'] );
	}

	public function test_scan_detects_phone_with_country_code(): void {
		$result = $this->detector->scan( 'Numer telefonu: +48 500 200 300.' );

		$this->assertTrue( $result['found'] );
		$this->assertContains( 'phone_pl', $result['types'] );
	}

	// -----------------------------------------------------------------------
	// scan — credit card detection
	// -----------------------------------------------------------------------

	public function test_scan_detects_credit_card_number(): void {
		$result = $this->detector->scan( 'Karta: 4111 1111 1111 1111 jest używana w przykładach.' );

		$this->assertTrue( $result['found'] );
		$this->assertContains( 'credit_card', $result['types'] );
	}

	// -----------------------------------------------------------------------
	// scan — IPv4 detection
	// -----------------------------------------------------------------------

	public function test_scan_detects_ipv4_address(): void {
		$result = $this->detector->scan( 'Server IP: 192.168.1.100.' );

		$this->assertTrue( $result['found'] );
		$this->assertContains( 'ipv4', $result['types'] );
	}

	// -----------------------------------------------------------------------
	// scan — IBAN detection
	// -----------------------------------------------------------------------

	public function test_scan_detects_iban(): void {
		$result = $this->detector->scan( 'Przelew na konto PL61 1090 1014 0000 0712 1981 2874.' );

		$this->assertTrue( $result['found'] );
		$this->assertContains( 'iban', $result['types'] );
	}

	// -----------------------------------------------------------------------
	// scan — passport detection
	// -----------------------------------------------------------------------

	public function test_scan_detects_polish_passport(): void {
		$result = $this->detector->scan( 'Paszport AA1234567 jest ważny do 2030.' );

		$this->assertTrue( $result['found'] );
		$this->assertContains( 'passport_pl', $result['types'] );
	}

	// -----------------------------------------------------------------------
	// scan — multiple PII types
	// -----------------------------------------------------------------------

	public function test_scan_detects_multiple_pii_types(): void {
		$content = 'Email: jan.kowalski@firma.pl, tel 600 100 200, IP: 10.0.0.1.';
		$result  = $this->detector->scan( $content );

		$this->assertTrue( $result['found'] );
		$this->assertGreaterThanOrEqual( 2, count( $result['types'] ) );
	}

	// -----------------------------------------------------------------------
	// scan — HTML stripping
	// -----------------------------------------------------------------------

	public function test_scan_strips_html_tags_before_scanning(): void {
		$content = '<p>Contact <strong>admin@company.io</strong> for help.</p>';
		$result  = $this->detector->scan( $content );

		$this->assertTrue( $result['found'] );
		$this->assertContains( 'email', $result['types'] );
	}

	// -----------------------------------------------------------------------
	// scan — deduplication
	// -----------------------------------------------------------------------

	public function test_scan_deduplicates_findings(): void {
		$content = 'Email: admin@firma.pl and also admin@firma.pl again.';
		$result  = $this->detector->scan( $content );

		$this->assertCount( 1, $result['findings']['email'] );
	}

	// -----------------------------------------------------------------------
	// redact
	// -----------------------------------------------------------------------

	public function test_redact_replaces_email(): void {
		$redacted = $this->detector->redact( 'Contact jan@firma.pl today.' );

		$this->assertStringNotContainsString( 'jan@firma.pl', $redacted );
		$this->assertStringContainsString( '[EMAIL REDACTED]', $redacted );
	}

	public function test_redact_replaces_ipv4(): void {
		$redacted = $this->detector->redact( 'Server at 10.0.0.1 is down.' );

		$this->assertStringNotContainsString( '10.0.0.1', $redacted );
		$this->assertStringContainsString( '[IP REDACTED]', $redacted );
	}

	public function test_redact_replaces_credit_card(): void {
		$redacted = $this->detector->redact( 'Use card 4111 1111 1111 1111 for payment.' );

		$this->assertStringNotContainsString( '4111', $redacted );
		$this->assertStringContainsString( '[CARD REDACTED]', $redacted );
	}

	public function test_redact_returns_unchanged_clean_content(): void {
		$original = 'This is a safe article about WordPress tips and tricks.';
		$redacted = $this->detector->redact( $original );

		$this->assertSame( $original, $redacted );
	}

	public function test_redact_replaces_all_occurrences(): void {
		$content  = 'Call 600 100 200 or 700 200 300 for help.';
		$redacted = $this->detector->redact( $content );

		$this->assertStringNotContainsString( '600 100 200', $redacted );
		$this->assertStringNotContainsString( '700 200 300', $redacted );
	}

	// -----------------------------------------------------------------------
	// scan_and_persist
	// -----------------------------------------------------------------------

	public function test_scan_and_persist_saves_found_flag(): void {
		$this->detector->scan_and_persist( 99, 'Contact me at jan@firma.pl.' );

		$found = get_post_meta( 99, PIIDetector::META_FOUND, true );
		$this->assertTrue( (bool) $found );
	}

	public function test_scan_and_persist_saves_empty_types_for_clean_content(): void {
		$this->detector->scan_and_persist( 100, 'Safe content about WordPress.' );

		$types = get_post_meta( 100, PIIDetector::META_TYPES, true );
		$this->assertSame( [], $types );
	}

	public function test_scan_and_persist_returns_same_result_as_scan(): void {
		$content  = 'IP address: 192.168.0.1';
		$from_scan    = $this->detector->scan( $content );
		$from_persist = $this->detector->scan_and_persist( 101, $content );

		$this->assertSame( $from_scan['found'], $from_persist['found'] );
		$this->assertSame( $from_scan['types'], $from_persist['types'] );
	}
}
