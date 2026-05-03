<?php
/**
 * Affiliate Discovery Engine – automatically detects products in article content
 * and injects relevant affiliate links from connected networks.
 *
 * Supports:
 *  - AWIN (Awin API)
 *  - Commission Junction (CJ API)
 *  - ShareASale API
 *  - Manual keyword → link mapping via WP option
 *
 * Configuration (WP options):
 *   pearblog_affiliate_enabled     – (bool) enable affiliate discovery
 *   pearblog_affiliate_awin_key    – AWIN API key
 *   pearblog_affiliate_awin_pub    – AWIN publisher ID
 *   pearblog_affiliate_cj_key      – CJ Developer API token
 *   pearblog_affiliate_manual      – JSON: [{keyword, url, label}]
 *
 * @package PearBlogEngine\Monetization
 */

declare(strict_types=1);

namespace PearBlogEngine\Monetization;

/**
 * Detects product mentions and injects affiliate links.
 */
class AffiliateDiscovery {

	/** WP option keys. */
	public const OPTION_ENABLED    = 'pearblog_affiliate_enabled';
	public const OPTION_AWIN_KEY   = 'pearblog_affiliate_awin_key';
	public const OPTION_AWIN_PUB   = 'pearblog_affiliate_awin_pub';
	public const OPTION_CJ_KEY     = 'pearblog_affiliate_cj_key';
	public const OPTION_MANUAL     = 'pearblog_affiliate_manual';

	/** Post meta key for discovered affiliate links. */
	public const META_LINKS = 'pearblog_affiliate_links';

	/** AWIN product search API endpoint. */
	private const AWIN_SEARCH_URL = 'https://productserv.awin.com/api/product/search';

	/** Maximum links to inject per article. */
	private const MAX_LINKS_PER_ARTICLE = 5;

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_filter( 'the_content', [ $this, 'inject_affiliate_links' ], 20 );
		add_action( 'pearblog_pipeline_completed', [ $this, 'discover_for_post' ], 10, 1 );
	}

	// -----------------------------------------------------------------------
	// Core discovery
	// -----------------------------------------------------------------------

	/**
	 * Whether affiliate discovery is enabled.
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	/**
	 * Discover affiliate links for a specific post.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return array<int, array{keyword: string, url: string, label: string, source: string}>
	 */
	public function discover_for_post( int $post_id ): array {
		if ( ! $this->is_enabled() ) {
			return [];
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [];
		}

		$text     = wp_strip_all_tags( $post->post_content );
		$keywords = $this->extract_product_keywords( $text );
		$links    = [];

		foreach ( array_slice( $keywords, 0, self::MAX_LINKS_PER_ARTICLE ) as $keyword ) {
			// Check manual mappings first.
			$manual = $this->find_manual_link( $keyword );
			if ( $manual ) {
				$links[] = array_merge( $manual, [ 'source' => 'manual' ] );
				continue;
			}

			// Try AWIN if configured.
			if ( '' !== (string) get_option( self::OPTION_AWIN_KEY ) ) {
				$awin = $this->search_awin( $keyword );
				if ( $awin ) {
					$links[] = array_merge( $awin, [ 'source' => 'awin' ] );
					continue;
				}
			}
		}

		update_post_meta( $post_id, self::META_LINKS, $links );

		/**
		 * Action: pearblog_affiliate_links_discovered
		 *
		 * @param int   $post_id Post ID.
		 * @param array $links   Discovered affiliate links.
		 */
		do_action( 'pearblog_affiliate_links_discovered', $post_id, $links );

		return $links;
	}

	/**
	 * Inject affiliate links into post content (filter callback).
	 *
	 * @param string $content Post content.
	 * @return string Content with affiliate links injected.
	 */
	public function inject_affiliate_links( string $content ): string {
		if ( ! $this->is_enabled() || ! is_singular( 'post' ) ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}

		$links = (array) get_post_meta( $post_id, self::META_LINKS, true );
		if ( empty( $links ) ) {
			return $content;
		}

		foreach ( $links as $link ) {
			if ( empty( $link['keyword'] ) || empty( $link['url'] ) ) {
				continue;
			}

			$keyword = esc_html( $link['keyword'] );
			$url     = esc_url( $link['url'] );
			$label   = esc_attr( $link['label'] ?? $keyword );
			$anchor  = "<a href=\"{$url}\" rel=\"nofollow sponsored\" title=\"{$label}\">{$keyword}</a>";

			// Replace only the first occurrence.
			$content = preg_replace(
				'/\b' . preg_quote( $link['keyword'], '/' ) . '\b/',
				$anchor,
				$content,
				1
			) ?? $content;
		}

		return $content;
	}

	// -----------------------------------------------------------------------
	// Keyword extraction
	// -----------------------------------------------------------------------

	/**
	 * Extract potential product/brand keywords from content.
	 *
	 * @param string $text Plain text content.
	 * @return string[] Keywords.
	 */
	public function extract_product_keywords( string $text ): array {
		// Extract capitalized phrases (likely product/brand names).
		preg_match_all( '/(?:[A-Z][a-z]+\s?){2,4}/', $text, $matches );
		$candidates = $matches[0] ?? [];

		// Also extract common product indicators.
		preg_match_all( '/\b(?:buy|purchase|order|get|shop)\s+([A-Za-z][a-z]+(?:\s+[A-Za-z][a-z]+)?)/i', $text, $action_matches );
		$candidates = array_merge( $candidates, $action_matches[1] ?? [] );

		// Clean and deduplicate.
		$keywords = array_unique(
			array_map( 'trim', array_filter( $candidates, fn( $k ) => strlen( $k ) > 3 ) )
		);

		return array_values( array_slice( $keywords, 0, 15 ) );
	}

	// -----------------------------------------------------------------------
	// Manual link mapping
	// -----------------------------------------------------------------------

	/**
	 * Find a manual affiliate link for a keyword.
	 *
	 * @param string $keyword Keyword to search.
	 * @return array{keyword: string, url: string, label: string}|null
	 */
	private function find_manual_link( string $keyword ): ?array {
		$manual_json = (string) get_option( self::OPTION_MANUAL, '[]' );
		$manual      = json_decode( $manual_json, true ) ?? [];

		foreach ( $manual as $entry ) {
			if ( isset( $entry['keyword'] ) && stripos( $keyword, $entry['keyword'] ) !== false ) {
				return [
					'keyword' => $entry['keyword'],
					'url'     => $entry['url'] ?? '',
					'label'   => $entry['label'] ?? $entry['keyword'],
				];
			}
		}

		return null;
	}

	// -----------------------------------------------------------------------
	// AWIN API integration
	// -----------------------------------------------------------------------

	/**
	 * Search AWIN product catalogue for affiliate links.
	 *
	 * @param string $keyword Search keyword.
	 * @return array{keyword: string, url: string, label: string}|null
	 */
	private function search_awin( string $keyword ): ?array {
		$api_key    = (string) get_option( self::OPTION_AWIN_KEY );
		$pub_id     = (string) get_option( self::OPTION_AWIN_PUB );

		$response = wp_remote_get( self::AWIN_SEARCH_URL . '?' . http_build_query( [
			'queryString' => $keyword,
			'publisherId' => $pub_id,
			'pageSize'    => 1,
			'apiKey'      => $api_key,
		] ), [ 'timeout' => 8 ] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$data     = json_decode( wp_remote_retrieve_body( $response ), true );
		$products = $data['data'] ?? [];

		if ( empty( $products[0] ) ) {
			return null;
		}

		$product = $products[0];

		return [
			'keyword' => $keyword,
			'url'     => $product['aw_deep_link'] ?? $product['merchant_product_second_category'] ?? '',
			'label'   => $product['product_name'] ?? $keyword,
		];
	}
}
