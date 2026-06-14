<?php
/**
 * Unit tests for TenantIsolator.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Tenant\TenantIsolator;

// Define master secret for deterministic key derivation in tests.
if ( ! defined( 'PEARBLOG_MASTER_SECRET' ) ) {
	define( 'PEARBLOG_MASTER_SECRET', 'test-master-secret-32-chars-long!!' );
}

class TenantIsolatorTest extends TestCase {

	private TenantIsolator $isolator;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']         = [];
		$GLOBALS['_current_blog_id'] = 1;
		$this->isolator              = new TenantIsolator();
	}

	// -----------------------------------------------------------------------
	// get_sensitive_options
	// -----------------------------------------------------------------------

	public function test_get_sensitive_options_returns_array(): void {
		$options = $this->isolator->get_sensitive_options();

		$this->assertIsArray( $options );
		$this->assertNotEmpty( $options );
	}

	public function test_get_sensitive_options_contains_openai_key(): void {
		$options = $this->isolator->get_sensitive_options();

		$this->assertContains( 'pearblog_openai_api_key', $options );
	}

	public function test_get_sensitive_options_contains_anthropic_key(): void {
		$options = $this->isolator->get_sensitive_options();

		$this->assertContains( 'pearblog_anthropic_api_key', $options );
	}

	public function test_get_sensitive_options_contains_stripe_secret(): void {
		$options = $this->isolator->get_sensitive_options();

		$this->assertContains( 'pearblog_billing_stripe_secret', $options );
	}

	public function test_get_sensitive_options_has_no_duplicates(): void {
		$options = $this->isolator->get_sensitive_options();

		$this->assertCount( count( $options ), array_unique( $options ) );
	}

	// -----------------------------------------------------------------------
	// is_encrypted — before encryption
	// -----------------------------------------------------------------------

	public function test_is_encrypted_returns_false_before_encryption(): void {
		$this->assertFalse( $this->isolator->is_encrypted( 'pearblog_openai_api_key' ) );
	}

	// -----------------------------------------------------------------------
	// encrypt_option / decrypt_option — round-trip
	// -----------------------------------------------------------------------

	public function test_encrypt_option_returns_true(): void {
		$result = $this->isolator->encrypt_option( 'pearblog_openai_api_key', 'sk-test123' );

		$this->assertTrue( $result );
	}

	public function test_is_encrypted_returns_true_after_encryption(): void {
		$this->isolator->encrypt_option( 'pearblog_openai_api_key', 'sk-test123' );

		$this->assertTrue( $this->isolator->is_encrypted( 'pearblog_openai_api_key' ) );
	}

	public function test_decrypt_option_returns_original_value(): void {
		$original = 'sk-abcdefghijklmno12345';
		$this->isolator->encrypt_option( 'pearblog_openai_api_key', $original );

		$decrypted = $this->isolator->decrypt_option( 'pearblog_openai_api_key' );
		$this->assertSame( $original, $decrypted );
	}

	public function test_encrypt_decrypt_round_trip_with_special_chars(): void {
		$value = 'secret!@#$%^&*()_+-=[]{}|;\':",./<>?';
		$this->isolator->encrypt_option( 'pearblog_anthropic_api_key', $value );

		$decrypted = $this->isolator->decrypt_option( 'pearblog_anthropic_api_key' );
		$this->assertSame( $value, $decrypted );
	}

	public function test_encrypt_decrypt_round_trip_with_long_value(): void {
		$value = str_repeat( 'a', 500 );
		$this->isolator->encrypt_option( 'pearblog_ga4_credentials', $value );

		$decrypted = $this->isolator->decrypt_option( 'pearblog_ga4_credentials' );
		$this->assertSame( $value, $decrypted );
	}

	// -----------------------------------------------------------------------
	// decrypt_option — fallback for unencrypted values
	// -----------------------------------------------------------------------

	public function test_decrypt_option_falls_back_to_plain_value(): void {
		update_option( 'my_plain_option', 'plain-value' );

		$result = $this->isolator->decrypt_option( 'my_plain_option' );
		$this->assertSame( 'plain-value', $result );
	}

	public function test_decrypt_option_returns_empty_string_when_not_set(): void {
		$result = $this->isolator->decrypt_option( 'nonexistent_option' );

		$this->assertSame( '', $result );
	}

	// -----------------------------------------------------------------------
	// Different site IDs produce different keys
	// -----------------------------------------------------------------------

	public function test_same_option_different_site_produces_different_ciphertext(): void {
		$secret = 'my-api-key';

		// Encrypt on site 1.
		$GLOBALS['_current_blog_id'] = 1;
		$isolator1 = new TenantIsolator();
		$isolator1->encrypt_option( 'pearblog_openai_api_key', $secret );
		$cipher_site1 = get_option( 'pbenc_pearblog_openai_api_key', '' );

		// Encrypt on site 2.
		$GLOBALS['_options']         = [];
		$GLOBALS['_current_blog_id'] = 2;
		$isolator2 = new TenantIsolator();
		$isolator2->encrypt_option( 'pearblog_openai_api_key', $secret );
		$cipher_site2 = get_option( 'pbenc_pearblog_openai_api_key', '' );

		$this->assertNotSame( $cipher_site1, $cipher_site2 );
	}

	public function test_same_option_decrypts_correctly_per_site(): void {
		$secret = 'site-specific-key';

		// Site 1.
		$GLOBALS['_options']         = [];
		$GLOBALS['_current_blog_id'] = 1;
		$isolator1 = new TenantIsolator();
		$isolator1->encrypt_option( 'pearblog_openai_api_key', $secret );
		$this->assertSame( $secret, $isolator1->decrypt_option( 'pearblog_openai_api_key' ) );

		// Site 2.
		$GLOBALS['_options']         = [];
		$GLOBALS['_current_blog_id'] = 2;
		$isolator2 = new TenantIsolator();
		$isolator2->encrypt_option( 'pearblog_openai_api_key', $secret );
		$this->assertSame( $secret, $isolator2->decrypt_option( 'pearblog_openai_api_key' ) );
	}

	// -----------------------------------------------------------------------
	// Encrypted value is not stored as plaintext
	// -----------------------------------------------------------------------

	public function test_encrypted_value_is_not_stored_as_plaintext(): void {
		$secret = 'sk-supersecret';
		$this->isolator->encrypt_option( 'pearblog_openai_api_key', $secret );

		// Verify the encrypted option does NOT contain the raw secret.
		$stored = get_option( 'pbenc_pearblog_openai_api_key', '' );
		$this->assertStringNotContainsString( $secret, $stored );
	}
}
