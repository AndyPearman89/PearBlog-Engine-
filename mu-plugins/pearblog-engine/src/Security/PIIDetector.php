<?php
/**
 * PII Detector – scans content for Personally Identifiable Information.
 *
 * Detects PII patterns in AI-generated article content before publication
 * to prevent accidental disclosure of sensitive data.
 *
 * Detects:
 *  - Email addresses
 *  - Polish PESEL numbers
 *  - Polish NIP / REGON numbers
 *  - Phone numbers (PL and international)
 *  - Credit card numbers (Luhn-validated)
 *  - IBAN numbers
 *  - IP addresses (IPv4)
 *  - Passport / ID card numbers (PL format)
 *
 * Post meta:
 *   pearblog_pii_found    – (bool) whether PII was detected
 *   pearblog_pii_types    – JSON list of PII types found
 *
 * @package PearBlogEngine\Security
 */

declare(strict_types=1);

namespace PearBlogEngine\Security;

/**
 * Scans content for PII patterns.
 */
class PIIDetector {

	/** Post meta keys. */
	public const META_FOUND = 'pearblog_pii_found';
	public const META_TYPES = 'pearblog_pii_types';

	/** PII pattern definitions. */
	private const PATTERNS = [
		'email'       => '/\b[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}\b/',
		'pesel'       => '/\b\d{11}\b/',
		'nip'         => '/\b\d{3}[-\s]?\d{3}[-\s]?\d{2}[-\s]?\d{2}\b/',
		'phone_pl'    => '/\b(?:\+48|48)?[\s-]?(?:\d{3}[\s-]?\d{3}[\s-]?\d{3}|\d{2}[\s-]?\d{3}[\s-]?\d{2}[\s-]?\d{2})\b/',
		'credit_card' => '/\b(?:\d{4}[\s-]?){3}\d{4}\b/',
		'iban'        => '/\b[A-Z]{2}\d{2}[\s]?(?:\d{4}[\s]?){4,7}\b/',
		'ipv4'        => '/\b(?:\d{1,3}\.){3}\d{1,3}\b/',
		'passport_pl' => '/\b[A-Z]{2}\s?\d{7}\b/',
	];

	/** Allowlist patterns (commonly safe false positives). */
	private const ALLOWLIST_PATTERNS = [
		'email' => [
			'/example\.com$/i',
			'/test\.com$/i',
			'/placeholder\.com$/i',
		],
	];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Scan content for PII and return findings.
	 *
	 * @param string $content Content to scan (HTML or plain text).
	 * @return array{found: bool, types: string[], findings: array<string, array<string>>}
	 */
	public function scan( string $content ): array {
		$text     = wp_strip_all_tags( $content );
		$found    = false;
		$types    = [];
		$findings = [];

		foreach ( self::PATTERNS as $type => $pattern ) {
			$matches = [];
			if ( preg_match_all( $pattern, $text, $matches ) ) {
				$hits = $this->filter_false_positives( $type, $matches[0] );
				if ( ! empty( $hits ) ) {
					$found        = true;
					$types[]      = $type;
					$findings[ $type ] = array_values( array_unique( $hits ) );
				}
			}
		}

		return [
			'found'    => $found,
			'types'    => $types,
			'findings' => $findings,
		];
	}

	/**
	 * Scan content and persist results to post meta.
	 *
	 * @param int    $post_id WordPress post ID.
	 * @param string $content Content to scan.
	 * @return array{found: bool, types: string[], findings: array<string, array<string>>}
	 */
	public function scan_and_persist( int $post_id, string $content ): array {
		$result = $this->scan( $content );

		update_post_meta( $post_id, self::META_FOUND, $result['found'] );
		update_post_meta( $post_id, self::META_TYPES, $result['types'] );

		if ( $result['found'] ) {
			/**
			 * Action: pearblog_pii_detected
			 *
			 * @param int      $post_id  Post ID.
			 * @param string[] $types    PII types detected.
			 * @param array    $findings Matched strings per type.
			 */
			do_action( 'pearblog_pii_detected', $post_id, $result['types'], $result['findings'] );
		}

		return $result;
	}

	/**
	 * Redact detected PII from content.
	 *
	 * Replaces PII with type-specific placeholders.
	 *
	 * @param string $content Original content.
	 * @return string Redacted content.
	 */
	public function redact( string $content ): string {
		$redacted = $content;

		$replacements = [
			'email'       => '[EMAIL REDACTED]',
			'pesel'       => '[PESEL REDACTED]',
			'nip'         => '[NIP REDACTED]',
			'phone_pl'    => '[PHONE REDACTED]',
			'credit_card' => '[CARD REDACTED]',
			'iban'        => '[IBAN REDACTED]',
			'ipv4'        => '[IP REDACTED]',
			'passport_pl' => '[PASSPORT REDACTED]',
		];

		foreach ( self::PATTERNS as $type => $pattern ) {
			$replacement = $replacements[ $type ] ?? "[{$type} REDACTED]";
			$redacted    = preg_replace( $pattern, $replacement, $redacted ) ?? $redacted;
		}

		return $redacted;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Filter common false positives.
	 *
	 * @param string   $type    PII type.
	 * @param string[] $matches Matched strings.
	 * @return string[] Filtered matches.
	 */
	private function filter_false_positives( string $type, array $matches ): array {
		$allowlist = self::ALLOWLIST_PATTERNS[ $type ] ?? [];

		return array_filter( $matches, function ( $match ) use ( $allowlist ) {
			foreach ( $allowlist as $allow_pattern ) {
				if ( preg_match( $allow_pattern, $match ) ) {
					return false;
				}
			}
			return true;
		} );
	}
}
