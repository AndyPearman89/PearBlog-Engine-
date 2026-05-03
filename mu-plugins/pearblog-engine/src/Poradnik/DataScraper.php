<?php
/**
 * Poradnik Data Scraper
 *
 * Collects market data from PT24 and other sources for content generation.
 * Respects robots.txt and implements rate limiting.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

/**
 * Class DataScraper
 *
 * Ethical web scraper for service pricing and market data.
 */
class DataScraper {
	/**
	 * Rate limit: minimum seconds between requests.
	 *
	 * @var int
	 */
	private $rate_limit_seconds = 2;

	/**
	 * Last request timestamp.
	 *
	 * @var int
	 */
	private $last_request_time = 0;

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Scrape service data for a specific service and city.
	 *
	 * @param string $service Service name (e.g., "Remont łazienki").
	 * @param string $city City name (e.g., "Warszawa").
	 * @return array|null Scraped data or null on failure.
	 */
	public function scrape_service_data( string $service, string $city ): ?array {
		$this->respect_rate_limit();

		// Check if data already exists and is fresh (< 30 days)
		$existing = $this->get_existing_data( $service, $city );
		if ( $existing && $this->is_data_fresh( $existing['updated_at'], 30 ) ) {
			return $existing;
		}

		// Attempt to scrape from PT24
		$pt24_data = $this->scrape_pt24( $service, $city );
		if ( $pt24_data ) {
			$this->save_service_data( $service, $city, $pt24_data, 'pt24' );
			return $pt24_data;
		}

		// Fallback to external APIs or databases
		$fallback_data = $this->get_fallback_data( $service, $city );
		if ( $fallback_data ) {
			$this->save_service_data( $service, $city, $fallback_data, 'fallback' );
			return $fallback_data;
		}

		return null;
	}

	/**
	 * Scrape data from PT24 marketplace.
	 *
	 * @param string $service Service name.
	 * @param string $city City name.
	 * @return array|null Scraped data or null.
	 */
	private function scrape_pt24( string $service, string $city ): ?array {
		// Build PT24 URL
		$slug = $this->slugify( $service );
		$url  = "https://pt24.pl/{$slug}/{$city}";

		// Check robots.txt
		if ( ! $this->is_allowed_by_robots( $url ) ) {
			error_log( "[PoradnikScraper] Blocked by robots.txt: {$url}" );
			return null;
		}

		// Fetch page content
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 15,
				'user-agent' => 'PoradnikBot/2.0 (+https://poradnik.pro/bot)',
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( '[PoradnikScraper] Error: ' . $response->get_error_message() );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return null;
		}

		// Parse pricing data
		return $this->parse_pt24_html( $body, $service, $city );
	}

	/**
	 * Parse PT24 HTML to extract pricing and service data.
	 *
	 * @param string $html HTML content.
	 * @param string $service Service name.
	 * @param string $city City name.
	 * @return array|null Parsed data.
	 */
	private function parse_pt24_html( string $html, string $service, string $city ): ?array {
		// This is a simplified parser. In production, use proper HTML parsing.
		$data = array(
			'service'         => $service,
			'city'            => $city,
			'price_min'       => null,
			'price_max'       => null,
			'price_avg'       => null,
			'currency'        => 'PLN',
			'services'        => array(),
			'providers_count' => 0,
			'faq'             => array(),
		);

		// Extract price ranges using regex (simplified)
		if ( preg_match( '/od\s+(\d+)\s+zł/i', $html, $matches ) ) {
			$data['price_min'] = (float) $matches[1];
		}

		if ( preg_match( '/do\s+(\d+)\s+zł/i', $html, $matches ) ) {
			$data['price_max'] = (float) $matches[1];
		}

		// Calculate average if both min and max found
		if ( $data['price_min'] && $data['price_max'] ) {
			$data['price_avg'] = ( $data['price_min'] + $data['price_max'] ) / 2;
		}

		// Extract provider count
		if ( preg_match( '/(\d+)\s+(firm|wykonawców|specjalistów)/i', $html, $matches ) ) {
			$data['providers_count'] = (int) $matches[1];
		}

		// Extract services list (simplified)
		if ( preg_match_all( '/<li[^>]*>([^<]+(?:remont|montaż|wymiana)[^<]*)<\/li>/i', $html, $matches ) ) {
			$data['services'] = array_slice( array_unique( $matches[1] ), 0, 10 );
		}

		return $data;
	}

	/**
	 * Get fallback data from external sources or defaults.
	 *
	 * @param string $service Service name.
	 * @param string $city City name.
	 * @return array|null Fallback data.
	 */
	private function get_fallback_data( string $service, string $city ): ?array {
		// Use industry averages as fallback
		$averages = $this->get_industry_averages( $service );
		if ( ! $averages ) {
			return null;
		}

		return array(
			'service'         => $service,
			'city'            => $city,
			'price_min'       => $averages['min'],
			'price_max'       => $averages['max'],
			'price_avg'       => $averages['avg'],
			'currency'        => 'PLN',
			'services'        => $averages['services'] ?? array(),
			'providers_count' => 0,
			'faq'             => array(),
		);
	}

	/**
	 * Get industry average pricing.
	 *
	 * @param string $service Service name.
	 * @return array|null Average pricing data.
	 */
	private function get_industry_averages( string $service ): ?array {
		// Industry averages database (simplified)
		$averages = array(
			'remont łazienki'      => array(
				'min'      => 5000,
				'max'      => 25000,
				'avg'      => 15000,
				'services' => array( 'Wymiana płytek', 'Montaż armatury', 'Instalacja wodno-kanalizacyjna' ),
			),
			'malowanie mieszkania' => array(
				'min'      => 2000,
				'max'      => 10000,
				'avg'      => 6000,
				'services' => array( 'Malowanie ścian', 'Malowanie sufitów', 'Gładzie' ),
			),
			'wymiana okien'        => array(
				'min'      => 1000,
				'max'      => 5000,
				'avg'      => 3000,
				'services' => array( 'Okna PCV', 'Okna drewniane', 'Okna aluminiowe' ),
			),
		);

		$service_lower = mb_strtolower( $service );
		foreach ( $averages as $key => $data ) {
			if ( stripos( $service_lower, $key ) !== false ) {
				return $data;
			}
		}

		return null;
	}

	/**
	 * Save service data to database.
	 *
	 * @param string $service Service name.
	 * @param string $city City name.
	 * @param array  $data Service data.
	 * @param string $source Data source.
	 * @return bool True on success.
	 */
	private function save_service_data( string $service, string $city, array $data, string $source ): bool {
		$table_name = $this->wpdb->prefix . 'pearblog_service_data';

		$insert_data = array(
			'service'         => $service,
			'city'            => $city,
			'price_min'       => $data['price_min'],
			'price_max'       => $data['price_max'],
			'price_avg'       => $data['price_avg'],
			'currency'        => $data['currency'] ?? 'PLN',
			'services_json'   => ! empty( $data['services'] ) ? wp_json_encode( $data['services'] ) : null,
			'providers_count' => $data['providers_count'] ?? 0,
			'faq_json'        => ! empty( $data['faq'] ) ? wp_json_encode( $data['faq'] ) : null,
			'data_source'     => $source,
			'scraped_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		);

		// Check if record exists
		$existing = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE service = %s AND city = %s",
				$service,
				$city
			)
		);

		if ( $existing ) {
			// Update existing record
			return (bool) $this->wpdb->update(
				$table_name,
				$insert_data,
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%d', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}

		// Insert new record
		return (bool) $this->wpdb->insert(
			$table_name,
			$insert_data,
			array( '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get existing service data from database.
	 *
	 * @param string $service Service name.
	 * @param string $city City name.
	 * @return array|null Existing data or null.
	 */
	private function get_existing_data( string $service, string $city ): ?array {
		$table_name = $this->wpdb->prefix . 'pearblog_service_data';

		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE service = %s AND city = %s",
				$service,
				$city
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		// Decode JSON fields
		$row['services'] = ! empty( $row['services_json'] ) ? json_decode( $row['services_json'], true ) : array();
		$row['faq']      = ! empty( $row['faq_json'] ) ? json_decode( $row['faq_json'], true ) : array();

		return $row;
	}

	/**
	 * Check if data is fresh.
	 *
	 * @param string $updated_at Last update timestamp.
	 * @param int    $days Maximum age in days.
	 * @return bool True if fresh, false if stale.
	 */
	private function is_data_fresh( string $updated_at, int $days = 30 ): bool {
		$updated_timestamp = strtotime( $updated_at );
		$age_seconds       = time() - $updated_timestamp;
		$age_days          = $age_seconds / DAY_IN_SECONDS;

		return $age_days < $days;
	}

	/**
	 * Respect rate limiting.
	 */
	private function respect_rate_limit(): void {
		$time_since_last_request = time() - $this->last_request_time;

		if ( $time_since_last_request < $this->rate_limit_seconds ) {
			$sleep_seconds = $this->rate_limit_seconds - $time_since_last_request;
			sleep( $sleep_seconds );
		}

		$this->last_request_time = time();
	}

	/**
	 * Check if URL is allowed by robots.txt.
	 *
	 * @param string $url URL to check.
	 * @return bool True if allowed, false if disallowed.
	 */
	private function is_allowed_by_robots( string $url ): bool {
		// Simplified robots.txt check
		// In production, use a proper robots.txt parser
		$parsed_url = wp_parse_url( $url );
		$robots_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/robots.txt';

		$response = wp_remote_get( $robots_url, array( 'timeout' => 5 ) );
		if ( is_wp_error( $response ) ) {
			return true; // Allow if robots.txt not accessible
		}

		$robots_content = wp_remote_retrieve_body( $response );
		if ( strpos( $robots_content, 'Disallow: /' ) !== false ) {
			return false;
		}

		return true;
	}

	/**
	 * Convert string to URL-friendly slug.
	 *
	 * @param string $text Text to slugify.
	 * @return string Slug.
	 */
	private function slugify( string $text ): string {
		$text = mb_strtolower( $text );
		$text = str_replace(
			array( 'ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż' ),
			array( 'a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z' ),
			$text
		);
		$text = preg_replace( '/[^a-z0-9]+/', '-', $text );
		$text = trim( $text, '-' );

		return $text;
	}
}
