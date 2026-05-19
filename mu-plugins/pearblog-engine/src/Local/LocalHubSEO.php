<?php
/**
 * Local Hub SEO
 *
 * Generates LocalBusiness structured data (schema.org) and
 * handles local-intent meta tags for hub pages.
 *
 * @package PearBlogEngine\Local
 */

declare(strict_types=1);

namespace PearBlogEngine\Local;

/**
 * LocalHubSEO
 *
 * Outputs JSON-LD structured data and manages local SEO meta
 * for programmatically generated hub pages.
 */
class LocalHubSEO {

	// -----------------------------------------------------------------------
	// Schema output
	// -----------------------------------------------------------------------

	/**
	 * Output LocalBusiness + ItemList JSON-LD for hub pages.
	 * Hooked to wp_head.
	 */
	public function output_local_schema(): void {
		if ( ! is_singular( 'page' ) ) {
			return;
		}

		$post_id  = get_the_ID();
		$vertical = get_post_meta( $post_id, 'pearblog_hub_vertical', true );
		$city     = get_post_meta( $post_id, 'pearblog_hub_city', true );

		if ( ! $vertical || ! $city ) {
			return;
		}

		$schema = $this->build_local_schema( (int) $post_id, $vertical, $city );

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . '</script>' . "\n";
	}

	/**
	 * Build schema.org LocalBusiness + ItemList for a hub page.
	 *
	 * @param int    $post_id
	 * @param string $vertical
	 * @param string $city
	 * @return array<string, mixed>
	 */
	public function build_local_schema( int $post_id, string $vertical, string $city ): array {
		$site_name = get_bloginfo( 'name' );
		$url       = get_permalink( $post_id );
		$city_name = ucfirst( $city );

		return [
			'@context' => 'https://schema.org',
			'@graph'   => [
				[
					'@type'          => 'WebPage',
					'@id'            => $url,
					'name'           => get_the_title( $post_id ),
					'url'            => $url,
					'description'    => "Ranking najlepszych specjalistów {$vertical} w {$city_name}. Porównaj opinie, ceny i znajdź fachowca.",
					'inLanguage'     => 'pl-PL',
					'isPartOf'       => [
						'@type' => 'WebSite',
						'@id'   => home_url( '/' ),
						'name'  => $site_name,
						'url'   => home_url( '/' ),
					],
					'breadcrumb'     => [
						'@type'           => 'BreadcrumbList',
						'itemListElement' => [
							[ '@type' => 'ListItem', 'position' => 1, 'name' => $site_name, 'item' => home_url( '/' ) ],
							[ '@type' => 'ListItem', 'position' => 2, 'name' => ucfirst( $vertical ), 'item' => home_url( "/{$vertical}/" ) ],
							[ '@type' => 'ListItem', 'position' => 3, 'name' => $city_name ],
						],
					],
				],
				[
					'@type'        => 'ItemList',
					'name'         => "Najlepsi {$vertical} w {$city_name}",
					'description'  => "Ranking {$vertical} w {$city_name} — porównaj opinie i ceny",
					'numberOfItems' => 10,
					'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
				],
				[
					'@type'       => 'Service',
					'name'        => ucfirst( $vertical ),
					'serviceType' => ucfirst( $vertical ),
					'areaServed'  => [
						'@type'      => 'City',
						'name'       => $city_name,
						'addressCountry' => 'PL',
					],
					'provider'    => [
						'@type' => 'Organization',
						'name'  => $site_name,
						'url'   => home_url( '/' ),
					],
				],
			],
		];
	}

	// -----------------------------------------------------------------------
	// Meta tags
	// -----------------------------------------------------------------------

	/**
	 * Generate local-intent meta description for a hub page.
	 *
	 * @param string $vertical
	 * @param string $city
	 * @param int    $year
	 * @return string
	 */
	public function meta_description( string $vertical, string $city, int $year = 0 ): string {
		$year     = $year ?: (int) date( 'Y' );
		$city_cap = ucfirst( $city );
		$vert_cap = ucfirst( $vertical );

		return "✅ Najlepszy {$vert_cap} {$city_cap} {$year} — sprawdź ranking, opinie i ceny. "
			. "Porównaj {$vert_cap}ów i wybierz najlepszego specjalistę. Szybki kontakt i darmowa wycena.";
	}

	/**
	 * Generate a canonical URL for a hub page.
	 *
	 * @param string $vertical
	 * @param string $city
	 * @return string
	 */
	public function canonical_url( string $vertical, string $city ): string {
		return trailingslashit( home_url( "/{$vertical}/{$city}" ) );
	}

	/**
	 * Generate hreflang tags for multi-language hubs.
	 *
	 * Currently supports pl only; extend as needed.
	 *
	 * @param string $vertical
	 * @param string $city
	 * @return string HTML
	 */
	public function hreflang_tags( string $vertical, string $city ): string {
		$url = $this->canonical_url( $vertical, $city );
		return "<link rel=\"alternate\" hreflang=\"pl\" href=\"{$url}\" />\n"
			. "<link rel=\"alternate\" hreflang=\"x-default\" href=\"{$url}\" />\n";
	}
}
