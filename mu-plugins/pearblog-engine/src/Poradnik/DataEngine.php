<?php
/**
 * Poradnik Data Engine
 *
 * Cleans, normalizes, and enriches scraped service data.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

/**
 * Class DataEngine
 *
 * Processes and enriches service data for content generation.
 */
class DataEngine {
	/**
	 * Clean and normalize service data.
	 *
	 * @param array $raw_data Raw scraped data.
	 * @return array Cleaned data.
	 */
	public function clean( array $raw_data ): array {
		return array(
			'service'         => $this->clean_service_name( $raw_data['service'] ?? '' ),
			'city'            => $this->clean_city_name( $raw_data['city'] ?? '' ),
			'price_min'       => $this->normalize_price( $raw_data['price_min'] ?? null ),
			'price_max'       => $this->normalize_price( $raw_data['price_max'] ?? null ),
			'price_avg'       => $this->normalize_price( $raw_data['price_avg'] ?? null ),
			'currency'        => $raw_data['currency'] ?? 'PLN',
			'services'        => $this->clean_services_list( $raw_data['services'] ?? array() ),
			'providers_count' => absint( $raw_data['providers_count'] ?? 0 ),
			'faq'             => $this->clean_faq( $raw_data['faq'] ?? array() ),
		);
	}

	/**
	 * Normalize service data.
	 *
	 * @param array $data Cleaned data.
	 * @return array Normalized data.
	 */
	public function normalize( array $data ): array {
		// Calculate average if missing
		if ( ! $data['price_avg'] && $data['price_min'] && $data['price_max'] ) {
			$data['price_avg'] = ( $data['price_min'] + $data['price_max'] ) / 2;
		}

		// Ensure price range is logical
		if ( $data['price_min'] && $data['price_max'] && $data['price_min'] > $data['price_max'] ) {
			list( $data['price_min'], $data['price_max'] ) = array( $data['price_max'], $data['price_min'] );
		}

		// Normalize currency
		$data['currency'] = strtoupper( $data['currency'] );

		return $data;
	}

	/**
	 * Enrich service data with additional metadata.
	 *
	 * @param array $data Normalized data.
	 * @return array Enriched data.
	 */
	public function enrich( array $data ): array {
		$enriched = $data;

		// Add price range category
		$enriched['price_category'] = $this->categorize_price( $data['price_avg'] );

		// Add city metadata
		$enriched['city_metadata'] = $this->get_city_metadata( $data['city'] );

		// Add service category
		$enriched['service_category'] = $this->categorize_service( $data['service'] );

		// Generate FAQ if missing
		if ( empty( $enriched['faq'] ) ) {
			$enriched['faq'] = $this->generate_default_faq( $data['service'], $data['city'] );
		}

		// Add related services
		$enriched['related_services'] = $this->find_related_services( $data['service'] );

		return $enriched;
	}

	/**
	 * Process complete pipeline: clean → normalize → enrich.
	 *
	 * @param array $raw_data Raw scraped data.
	 * @return array Processed data.
	 */
	public function process( array $raw_data ): array {
		$cleaned    = $this->clean( $raw_data );
		$normalized = $this->normalize( $cleaned );
		$enriched   = $this->enrich( $normalized );

		return $enriched;
	}

	/**
	 * Clean service name.
	 *
	 * @param string $service Service name.
	 * @return string Cleaned service name.
	 */
	private function clean_service_name( string $service ): string {
		$service = trim( $service );
		$service = preg_replace( '/\s+/', ' ', $service );
		return ucfirst( mb_strtolower( $service ) );
	}

	/**
	 * Clean city name.
	 *
	 * @param string $city City name.
	 * @return string Cleaned city name.
	 */
	private function clean_city_name( string $city ): string {
		$city = trim( $city );
		$city = preg_replace( '/\s+/', ' ', $city );
		return ucfirst( mb_strtolower( $city ) );
	}

	/**
	 * Normalize price value.
	 *
	 * @param mixed $price Price value.
	 * @return float|null Normalized price.
	 */
	private function normalize_price( $price ): ?float {
		if ( $price === null || $price === '' ) {
			return null;
		}

		$price = (float) $price;
		return $price > 0 ? round( $price, 2 ) : null;
	}

	/**
	 * Clean services list.
	 *
	 * @param array $services Services array.
	 * @return array Cleaned services.
	 */
	private function clean_services_list( array $services ): array {
		$cleaned = array();

		foreach ( $services as $service ) {
			$service = trim( $service );
			if ( ! empty( $service ) && mb_strlen( $service ) > 3 ) {
				$cleaned[] = ucfirst( mb_strtolower( $service ) );
			}
		}

		return array_unique( $cleaned );
	}

	/**
	 * Clean FAQ data.
	 *
	 * @param array $faq FAQ array.
	 * @return array Cleaned FAQ.
	 */
	private function clean_faq( array $faq ): array {
		$cleaned = array();

		foreach ( $faq as $item ) {
			if ( ! empty( $item['question'] ) && ! empty( $item['answer'] ) ) {
				$cleaned[] = array(
					'question' => trim( $item['question'] ),
					'answer'   => trim( $item['answer'] ),
				);
			}
		}

		return $cleaned;
	}

	/**
	 * Categorize price into ranges.
	 *
	 * @param float|null $price Average price.
	 * @return string Price category.
	 */
	private function categorize_price( ?float $price ): string {
		if ( ! $price ) {
			return 'unknown';
		}

		if ( $price < 1000 ) {
			return 'budget';
		} elseif ( $price < 5000 ) {
			return 'standard';
		} elseif ( $price < 15000 ) {
			return 'premium';
		} else {
			return 'luxury';
		}
	}

	/**
	 * Get city metadata.
	 *
	 * @param string $city City name.
	 * @return array City metadata.
	 */
	private function get_city_metadata( string $city ): array {
		// City data (simplified)
		$city_data = array(
			'warszawa' => array(
				'population'  => 1800000,
				'voivodeship' => 'Mazowieckie',
			),
			'kraków'   => array(
				'population'  => 780000,
				'voivodeship' => 'Małopolskie',
			),
			'wrocław'  => array(
				'population'  => 640000,
				'voivodeship' => 'Dolnośląskie',
			),
			'poznań'   => array(
				'population'  => 535000,
				'voivodeship' => 'Wielkopolskie',
			),
		);

		$city_lower = mb_strtolower( $city );
		return $city_data[ $city_lower ] ?? array(
			'population'  => null,
			'voivodeship' => null,
		);
	}

	/**
	 * Categorize service type.
	 *
	 * @param string $service Service name.
	 * @return string Service category.
	 */
	private function categorize_service( string $service ): string {
		$service_lower = mb_strtolower( $service );

		$categories = array(
			'remont'   => array( 'remont', 'malowanie', 'tapetowanie', 'gładzie' ),
			'budowa'   => array( 'budowa', 'rozbudowa', 'nadbudowa', 'fundamenty' ),
			'auto'     => array( 'auto', 'samochód', 'mechanik', 'warsztat', 'lakiernia' ),
			'finanse'  => array( 'kredyt', 'pożyczka', 'ubezpieczenie', 'leasing' ),
			'ogród'    => array( 'ogród', 'trawnik', 'pielęgnacja', 'koszenie' ),
			'instalacja' => array( 'instalacja', 'elektryka', 'hydraulika', 'wentylacja' ),
		);

		foreach ( $categories as $category => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $service_lower, $keyword ) !== false ) {
					return $category;
				}
			}
		}

		return 'inne';
	}

	/**
	 * Generate default FAQ for service.
	 *
	 * @param string $service Service name.
	 * @param string $city City name.
	 * @return array FAQ array.
	 */
	private function generate_default_faq( string $service, string $city ): array {
		return array(
			array(
				'question' => "Ile kosztuje {$service} w {$city}?",
				'answer'   => "Cena {$service} w {$city} zależy od wielu czynników, m.in. zakresu prac, użytych materiałów i wybranego wykonawcy.",
			),
			array(
				'question' => "Jak długo trwa {$service}?",
				'answer'   => "Czas realizacji {$service} zależy od zakresu prac i dostępności wykonawcy.",
			),
			array(
				'question' => "Jak wybrać wykonawcę do {$service}?",
				'answer'   => "Przy wyborze wykonawcy warto zwrócić uwagę na opinie, doświadczenie i portfolio realizacji.",
			),
			array(
				'question' => "Czy mogę negocjować cenę {$service}?",
				'answer'   => "Tak, cena jest zawsze do negocjacji. Warto porównać oferty kilku wykonawców.",
			),
		);
	}

	/**
	 * Find related services.
	 *
	 * @param string $service Service name.
	 * @return array Related services.
	 */
	private function find_related_services( string $service ): array {
		$service_lower = mb_strtolower( $service );

		$related = array(
			'remont łazienki'      => array( 'Remont kuchni', 'Wymiana płytek', 'Instalacja hydrauliczna' ),
			'malowanie mieszkania' => array( 'Gładzie ścian', 'Tapetowanie', 'Lakierowanie' ),
			'wymiana okien'        => array( 'Montaż drzwi', 'Parapety', 'Rolety zewnętrzne' ),
		);

		foreach ( $related as $key => $services ) {
			if ( strpos( $service_lower, $key ) !== false ) {
				return $services;
			}
		}

		return array();
	}
}
