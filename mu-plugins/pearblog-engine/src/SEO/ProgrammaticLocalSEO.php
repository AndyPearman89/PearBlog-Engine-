<?php
/**
 * Programmatic SEO Helper for Local Variants
 *
 * Generates local city variants for "ile kosztuje" content.
 * Creates programmatic URLs and internal linking structures.
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

/**
 * Helper class for programmatic local SEO content generation.
 */
class ProgrammaticLocalSEO {

	/**
	 * Major Polish cities for programmatic content.
	 *
	 * @var array
	 */
	private const MAJOR_CITIES = [
		'warszawa'   => 'Warszawie',
		'krakow'     => 'Krakowie',
		'katowice'   => 'Katowicach',
		'wroclaw'    => 'Wrocławiu',
		'poznan'     => 'Poznaniu',
		'gdansk'     => 'Gdańsku',
		'szczecin'   => 'Szczecinie',
		'bydgoszcz'  => 'Bydgoszczy',
		'lublin'     => 'Lublinie',
		'bialystok'  => 'Białymstoku',
		'czestochowa' => 'Częstochowie',
		'radom'      => 'Radomiu',
		'sosnowiec'  => 'Sosnowcu',
		'torun'      => 'Toruniu',
		'kielce'     => 'Kielcach',
	];

	/**
	 * Generate local variant URLs for programmatic SEO.
	 *
	 * @param string $service Service slug (e.g., "budowa-domu").
	 * @param int    $year    Year for the content.
	 * @param array  $cities  Optional custom city list (slug => locative_name).
	 * @return array          Array of city variants with URLs and titles.
	 */
	public static function generate_local_variants( string $service, int $year, array $cities = [] ): array {
		$cities = ! empty( $cities ) ? $cities : self::MAJOR_CITIES;
		$variants = [];

		foreach ( $cities as $slug => $locative_name ) {
			$variants[] = [
				'city_slug'      => $slug,
				'city_locative'  => $locative_name,
				'url'            => "/ile-kosztuje-{$service}-{$slug}-{$year}",
				'title'          => "Ile kosztuje {$service} w {$locative_name}",
				'meta_title'     => "Ile kosztuje {$service} w {$locative_name} {$year}? Ceny",
				'h1'             => "Ile kosztuje {$service} w {$locative_name} w {$year}?",
			];
		}

		return $variants;
	}

	/**
	 * Generate internal linking HTML for local variants.
	 *
	 * @param string $service Service name/slug.
	 * @param int    $year    Year.
	 * @param array  $cities  Optional city list.
	 * @return string         HTML list of internal links.
	 */
	public static function generate_local_links_html( string $service, int $year, array $cities = [] ): string {
		$variants = self::generate_local_variants( $service, $year, $cities );

		$html = "<div class=\"programmatic-local-links\">\n";
		$html .= "<h2>Ile kosztuje {$service} w innych miastach?</h2>\n";
		$html .= "<p>Ceny mogą się różnić w zależności od lokalizacji. Sprawdź szczegółowe informacje dla swojego miasta:</p>\n";
		$html .= "<ul class=\"city-links-list\">\n";

		foreach ( $variants as $variant ) {
			$html .= sprintf(
				"<li><a href=\"%s\">%s</a></li>\n",
				esc_url( $variant['url'] ),
				esc_html( $variant['title'] )
			);
		}

		$html .= "</ul>\n";
		$html .= "</div>\n";

		return $html;
	}

	/**
	 * Create TOPIC CPT entries for programmatic local variants.
	 *
	 * @param string $service     Service name.
	 * @param string $base_keyword Base keyword.
	 * @param int    $year        Year.
	 * @param array  $cities      Optional city list.
	 * @return array              Array of created topic IDs.
	 */
	public static function create_local_topics( string $service, string $base_keyword, int $year, array $cities = [] ): array {
		if ( ! class_exists( 'PearBlogEngine\Content\TopicCPT' ) ) {
			return [];
		}

		$variants = self::generate_local_variants( $service, $year, $cities );
		$topic_ids = [];

		foreach ( $variants as $variant ) {
			$topic_id = \PearBlogEngine\Content\TopicCPT::create_topic( [
				'title'       => $variant['title'],
				'keyword'     => "ile kosztuje {$base_keyword} {$variant['city_locative']}",
				'intent_type' => 'local',
				'city'        => $variant['city_locative'],
				'service'     => $service,
				'content'     => "Programmatically generated local variant for {$variant['city_locative']}.",
			] );

			if ( ! is_wp_error( $topic_id ) ) {
				$topic_ids[] = $topic_id;

				// Store URL slug for reference
				update_post_meta( $topic_id, '_pb_target_slug', $variant['url'] );
			}
		}

		return $topic_ids;
	}

	/**
	 * Get locative form of city name (for "w [city]" phrases).
	 *
	 * @param string $city_nominative City name in nominative case.
	 * @return string                 City name in locative case.
	 */
	public static function get_city_locative( string $city_nominative ): string {
		$city_slug = sanitize_title( $city_nominative );

		// Check if we have a predefined locative form
		if ( isset( self::MAJOR_CITIES[ $city_slug ] ) ) {
			return self::MAJOR_CITIES[ $city_slug ];
		}

		// Simple heuristics for Polish locative case
		// This is simplified - full Polish grammar would need more rules
		$lowercase = mb_strtolower( $city_nominative );

		// Cities ending in -a usually change to -ie
		if ( mb_substr( $lowercase, -1 ) === 'a' ) {
			return mb_substr( $city_nominative, 0, -1 ) . 'ie';
		}

		// Cities ending in consonants often add -e or -u
		// Default: add -ie (safest generic option)
		return $city_nominative . 'ie';
	}

	/**
	 * Generate CSV export of local variants for bulk import.
	 *
	 * @param string $service Service slug.
	 * @param int    $year    Year.
	 * @param array  $cities  Optional city list.
	 * @return string         CSV content.
	 */
	public static function export_variants_csv( string $service, int $year, array $cities = [] ): string {
		$variants = self::generate_local_variants( $service, $year, $cities );

		$csv = "keyword,intent_type,city,service,title,url\n";

		foreach ( $variants as $variant ) {
			$csv .= sprintf(
				"\"%s\",\"local\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
				str_replace( '"', '""', "ile kosztuje {$service} " . $variant['city_locative'] ),
				str_replace( '"', '""', $variant['city_locative'] ),
				str_replace( '"', '""', $service ),
				str_replace( '"', '""', $variant['title'] ),
				str_replace( '"', '""', $variant['url'] )
			);
		}

		return $csv;
	}
}
