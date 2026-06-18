<?php
/**
 * AI Fact-Checker – validates AI-generated claims against a web search API.
 *
 * After content is generated, this module extracts factual claims (sentences
 * containing numbers, dates, statistics, or named entities) and verifies them
 * through the Brave Search API or Perplexity Sonar API.
 *
 * Unverified claims are annotated with a `[FACT CHECK NEEDED]` marker in the
 * content and stored as post meta for editorial review.
 *
 * Configuration:
 *   pearblog_factcheck_enabled      – (bool) enable/disable fact-checking
 *   pearblog_factcheck_api          – 'brave' | 'perplexity'
 *   pearblog_factcheck_api_key      – API key for the chosen provider
 *   pearblog_factcheck_threshold    – min confidence score (0.0–1.0, default 0.6)
 *
 * Post meta:
 *   pearblog_factcheck_results      – JSON: [{claim, verified, confidence, source}]
 *   pearblog_factcheck_warnings     – (int) number of unverified claims
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Fact-checks AI-generated content using a web search API.
 */
class FactChecker {

	/** WP option keys. */
	public const OPTION_ENABLED   = 'pearblog_factcheck_enabled';
	public const OPTION_API       = 'pearblog_factcheck_api';
	public const OPTION_API_KEY   = 'pearblog_factcheck_api_key';
	public const OPTION_THRESHOLD = 'pearblog_factcheck_threshold';

	/** Default confidence threshold for claim verification. */
	public const DEFAULT_THRESHOLD = 0.6;

	/** Post meta keys. */
	public const META_RESULTS  = 'pearblog_factcheck_results';
	public const META_WARNINGS = 'pearblog_factcheck_warnings';

	/** Marker injected around unverified claims in content. */
	public const UNVERIFIED_MARKER = ' <span class="pearblog-factcheck-warning" title="This claim could not be verified">[⚠️ FACT CHECK NEEDED]</span>';

	/** Brave Search API URL. */
	private const BRAVE_API_URL = 'https://api.search.brave.com/res/v1/web/search';

	/** Perplexity Sonar API URL. */
	private const PERPLEXITY_API_URL = 'https://api.perplexity.ai/chat/completions';

	// -----------------------------------------------------------------------

	/**
	 * Whether fact-checking is enabled and configured.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false )
			&& '' !== (string) get_option( self::OPTION_API_KEY, '' );
	}

	/**
	 * Check a piece of content, annotate unverified claims, and persist results.
	 *
	 * @param int    $post_id WordPress post ID (for meta storage).
	 * @param string $content HTML/text content.
	 * @return string Content with unverified claims annotated.
	 */
	public function check_and_annotate( int $post_id, string $content ): string {
		if ( ! $this->is_enabled() ) {
			return $content;
		}

		$claims  = $this->extract_claims( $content );
		$results = [];
		$warnings = 0;

		foreach ( $claims as $claim ) {
			$result = $this->verify_claim( $claim );
			$results[] = $result;

			if ( ! $result['verified'] ) {
				$warnings++;
				// Annotate the unverified claim in content.
				$content = str_replace(
					$claim,
					$claim . self::UNVERIFIED_MARKER,
					$content
				);
			}
		}

		// Persist results as post meta.
		update_post_meta( $post_id, self::META_RESULTS, $results );
		update_post_meta( $post_id, self::META_WARNINGS, $warnings );

		if ( $warnings > 0 ) {
			/**
			 * Action: pearblog_factcheck_warnings
			 *
			 * @param int   $post_id  Post ID.
			 * @param int   $warnings Number of unverified claims.
			 * @param array $results  Full fact-check results.
			 */
			do_action( 'pearblog_factcheck_warnings', $post_id, $warnings, $results );
		}

		return $content;
	}

	/**
	 * Extract factual claims from content.
	 * Targets sentences containing numbers, percentages, years, or statistics.
	 *
	 * @param string $content Plain text or HTML content.
	 * @return string[] Array of claim strings.
	 */
	public function extract_claims( string $content ): array {
		// Strip HTML tags for claim extraction.
		$text = wp_strip_all_tags( $content );

		// Split into sentences.
		$sentences = preg_split( '/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY ) ?: [];

		$claims = [];
		foreach ( $sentences as $sentence ) {
			$sentence = trim( $sentence );
			if ( $this->is_factual_claim( $sentence ) ) {
				$claims[] = $sentence;
			}
		}

		// Limit to 10 claims per article to conserve API quota.
		return array_slice( $claims, 0, 10 );
	}

	/**
	 * Determine if a sentence contains a verifiable factual claim.
	 *
	 * @param string $sentence A single sentence.
	 * @return bool
	 */
	public function is_factual_claim( string $sentence ): bool {
		if ( strlen( $sentence ) < 20 || strlen( $sentence ) > 300 ) {
			return false;
		}

		// Contains number + unit pattern, percentage, year range, etc.
		return (bool) preg_match(
			'/(?:\d[\d,. ]*(?:%|zł|PLN|EUR|USD|mln|mld|tys|kg|km|m²|GHz|MB|GB|TB)(?=\s|$|[[:punct:]])|\d{4}[-–]\d{4}|\b(?:ponad|prawie|przy)\s+\d+%?|\d+[.,]\d+)/iu',
			$sentence
		);
	}

	/**
	 * Verify a single factual claim against the configured search API.
	 *
	 * @param string $claim Factual claim sentence.
	 * @return array{claim: string, verified: bool, confidence: float, source: string}
	 */
	public function verify_claim( string $claim ): array {
		$api = (string) get_option( self::OPTION_API, 'brave' );

		$result = match ( $api ) {
			'perplexity' => $this->verify_via_perplexity( $claim ),
			default      => $this->verify_via_brave( $claim ),
		};

		return $result;
	}

	// -----------------------------------------------------------------------
	// Private verification implementations
	// -----------------------------------------------------------------------

	/**
	 * Verify claim using Brave Search API.
	 *
	 * @param string $claim
	 * @return array{claim: string, verified: bool, confidence: float, source: string}
	 */
	private function verify_via_brave( string $claim ): array {
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );
		if ( '' === $api_key ) {
			return $this->unverified_result( $claim );
		}

		$response = wp_remote_get( self::BRAVE_API_URL . '?' . http_build_query( [
			'q'     => $claim,
			'count' => 3,
		] ), [
			'headers' => [
				'Accept'               => 'application/json',
				'Accept-Encoding'      => 'gzip',
				'X-Subscription-Token' => $api_key,
			],
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $this->unverified_result( $claim );
		}

		$data    = json_decode( wp_remote_retrieve_body( $response ), true );
		$results = $data['web']['results'] ?? [];

		// If we get at least 2 search results, consider the claim verifiable.
		$confidence = min( 1.0, count( $results ) / 3 );
		$threshold  = (float) get_option( self::OPTION_THRESHOLD, self::DEFAULT_THRESHOLD );
		$source     = ! empty( $results[0]['url'] ) ? $results[0]['url'] : '';

		return [
			'claim'      => $claim,
			'verified'   => $confidence >= $threshold,
			'confidence' => round( $confidence, 2 ),
			'source'     => $source,
		];
	}

	/**
	 * Verify claim using Perplexity Sonar API.
	 *
	 * @param string $claim
	 * @return array{claim: string, verified: bool, confidence: float, source: string}
	 */
	private function verify_via_perplexity( string $claim ): array {
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );
		if ( '' === $api_key ) {
			return $this->unverified_result( $claim );
		}

		$body = wp_json_encode( [
			'model'    => 'sonar',
			'messages' => [
				[
					'role'    => 'system',
					'content' => 'You are a fact-checker. Answer only: VERIFIED or UNVERIFIED and one citation URL.',
				],
				[
					'role'    => 'user',
					'content' => "Verify this claim: {$claim}",
				],
			],
			'max_tokens' => 100,
		] );

		$response = wp_remote_post( self::PERPLEXITY_API_URL, [
			'headers' => [
				'Authorization' => "Bearer {$api_key}",
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return $this->unverified_result( $claim );
		}

		$data    = json_decode( wp_remote_retrieve_body( $response ), true );
		$answer  = strtoupper( $data['choices'][0]['message']['content'] ?? '' );
		$verified = str_contains( $answer, 'VERIFIED' ) && ! str_contains( $answer, 'UNVERIFIED' );

		// Extract URL from response.
		preg_match( '/https?:\/\/[^\s]+/', $answer, $matches );
		$source = $matches[0] ?? '';

		return [
			'claim'      => $claim,
			'verified'   => $verified,
			'confidence' => $verified ? 0.9 : 0.2,
			'source'     => $source,
		];
	}

	/**
	 * Return a default unverified result structure.
	 *
	 * @param string $claim
	 * @return array{claim: string, verified: bool, confidence: float, source: string}
	 */
	private function unverified_result( string $claim ): array {
		return [
			'claim'      => $claim,
			'verified'   => false,
			'confidence' => 0.0,
			'source'     => '',
		];
	}
}
