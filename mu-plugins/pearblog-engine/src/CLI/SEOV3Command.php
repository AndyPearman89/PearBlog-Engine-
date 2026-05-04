<?php
/**
 * WP-CLI command group: `wp pearblog seo-v3`
 *
 * SEO V3 programmatic landing page generation system.
 *
 * Commands:
 *   wp pearblog seo-v3:stats
 *   wp pearblog seo-v3:keywords --vertical=<vertical> --intent=<intent>
 *   wp pearblog seo-v3:generate --vertical=<vertical> --batch=<n>
 *   wp pearblog seo-v3:verticals
 *   wp pearblog seo-v3:services <vertical>
 *   wp pearblog seo-v3:modifiers
 *
 * @package PearBlogEngine\CLI
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

/**
 * Manage SEO V3 programmatic landing pages.
 *
 * @when after_wp_load
 */
class SEOV3Command {

	/**
	 * Show SEO V3 statistics
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog seo-v3:stats
	 *
	 * @when after_wp_load
	 */
	public function stats( array $args, array $assoc_args ): void {
		global $wpdb;

		\WP_CLI::log( "\n=== SEO V3 Statistics ===\n" );

		// Count SEO V3 landing pages
		$seo_v3_pages = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			WHERE post_type = 'post'
			AND post_status = 'publish'
			AND ID IN (
				SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = 'pearblog_seo_v3_enabled'
				AND meta_value = '1'
			)"
		);

		\WP_CLI::log( "SEO V3 Landing Pages: " . (int) $seo_v3_pages );

		// Count by vertical
		$verticals = $this->get_verticals();
		foreach ( $verticals as $slug => $name ) {
			$count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
				INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
				WHERE p.post_type = 'post'
				AND p.post_status = 'publish'
				AND pm1.meta_key = 'pearblog_seo_v3_enabled'
				AND pm1.meta_value = '1'
				AND pm2.meta_key = 'pearblog_seo_v3_vertical'
				AND pm2.meta_value = %s",
				$slug
			) );
			\WP_CLI::log( "  - {$name}: " . (int) $count );
		}

		// Count keywords generated
		$keywords_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options}
			WHERE option_name LIKE 'pearblog_seo_v3_keywords_%'"
		);
		\WP_CLI::log( "\nKeyword Sets Generated: " . (int) $keywords_count );

		// Count by intent
		$intents = [ 'transactional', 'informational', 'commercial', 'navigational' ];
		foreach ( $intents as $intent ) {
			$count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options}
				WHERE option_name LIKE %s",
				'pearblog_seo_v3_keywords_' . $intent . '_%'
			) );
			\WP_CLI::log( "  - " . ucfirst( $intent ) . ": " . (int) $count );
		}

		\WP_CLI::log( "\n" );
	}

	/**
	 * Generate keywords for a vertical and intent
	 *
	 * ## OPTIONS
	 *
	 * --vertical=<vertical>
	 * : Vertical/industry slug (elektryk, hydraulik, mechanik, etc.)
	 *
	 * --intent=<intent>
	 * : Search intent (transactional, informational, commercial, navigational)
	 *
	 * [--limit=<number>]
	 * : Number of keywords to generate
	 * ---
	 * default: 50
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog seo-v3:keywords --vertical=elektryk --intent=transactional
	 *     wp pearblog seo-v3:keywords --vertical=hydraulik --intent=informational --limit=100
	 *
	 * @when after_wp_load
	 */
	public function keywords( array $args, array $assoc_args ): void {
		$vertical = $assoc_args['vertical'] ?? null;
		$intent   = $assoc_args['intent'] ?? null;
		$limit    = (int) ( $assoc_args['limit'] ?? 50 );

		if ( ! $vertical || ! $intent ) {
			\WP_CLI::error( 'Both --vertical and --intent are required' );
			return;
		}

		$verticals = $this->get_verticals();
		if ( ! isset( $verticals[ $vertical ] ) ) {
			\WP_CLI::error( "Invalid vertical: {$vertical}. Use 'wp pearblog seo-v3:verticals' to see available verticals." );
			return;
		}

		$valid_intents = [ 'transactional', 'informational', 'commercial', 'navigational' ];
		if ( ! in_array( $intent, $valid_intents, true ) ) {
			\WP_CLI::error( "Invalid intent: {$intent}. Valid intents: " . implode( ', ', $valid_intents ) );
			return;
		}

		\WP_CLI::log( "Generating {$limit} {$intent} keywords for {$verticals[$vertical]}..." );

		$keywords = $this->generate_keywords( $vertical, $intent, $limit );

		// Store keywords
		$option_key = "pearblog_seo_v3_keywords_{$intent}_{$vertical}";
		update_option( $option_key, $keywords );

		\WP_CLI::success( "Generated " . count( $keywords ) . " keywords" );
		\WP_CLI::log( "\nSample keywords:" );

		$sample = array_slice( $keywords, 0, 10 );
		foreach ( $sample as $keyword ) {
			\WP_CLI::log( "  - {$keyword}" );
		}

		if ( count( $keywords ) > 10 ) {
			\WP_CLI::log( "\n  ... and " . ( count( $keywords ) - 10 ) . " more" );
		}

		\WP_CLI::log( "\nKeywords saved to option: {$option_key}" );
	}

	/**
	 * Generate landing pages for a vertical
	 *
	 * ## OPTIONS
	 *
	 * --vertical=<vertical>
	 * : Vertical/industry slug (elektryk, hydraulik, mechanik, etc.)
	 *
	 * [--batch=<number>]
	 * : Number of pages to generate
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--intent=<intent>]
	 * : Keyword intent to use (transactional, informational, commercial, navigational)
	 * ---
	 * default: transactional
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog seo-v3:generate --vertical=hydraulik --batch=100
	 *     wp pearblog seo-v3:generate --vertical=elektryk --batch=50 --intent=commercial
	 *
	 * @when after_wp_load
	 */
	public function generate( array $args, array $assoc_args ): void {
		$vertical = $assoc_args['vertical'] ?? null;
		$batch    = (int) ( $assoc_args['batch'] ?? 10 );
		$intent   = $assoc_args['intent'] ?? 'transactional';

		if ( ! $vertical ) {
			\WP_CLI::error( '--vertical is required' );
			return;
		}

		$verticals = $this->get_verticals();
		if ( ! isset( $verticals[ $vertical ] ) ) {
			\WP_CLI::error( "Invalid vertical: {$vertical}. Use 'wp pearblog seo-v3:verticals' to see available verticals." );
			return;
		}

		\WP_CLI::log( "Generating {$batch} landing pages for {$verticals[$vertical]}..." );

		// Load or generate keywords
		$option_key = "pearblog_seo_v3_keywords_{$intent}_{$vertical}";
		$keywords   = get_option( $option_key );

		if ( ! $keywords || ! is_array( $keywords ) ) {
			\WP_CLI::log( "No keywords found for {$intent}/{$vertical}. Generating..." );
			$keywords = $this->generate_keywords( $vertical, $intent, $batch );
			update_option( $option_key, $keywords );
		}

		if ( empty( $keywords ) ) {
			\WP_CLI::error( 'No keywords available to generate pages' );
			return;
		}

		// Take keywords for batch
		$keywords_to_use = array_slice( $keywords, 0, $batch );

		$progress   = \WP_CLI\Utils\make_progress_bar( 'Generating landing pages', count( $keywords_to_use ) );
		$generated  = 0;
		$skipped    = 0;

		foreach ( $keywords_to_use as $keyword ) {
			// Check if page already exists for this keyword
			$existing = get_posts( [
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => 'pearblog_seo_v3_enabled',
						'value' => '1',
					],
					[
						'key'   => 'pearblog_seo_v3_keyword',
						'value' => $keyword,
					],
				],
			] );

			if ( ! empty( $existing ) ) {
				$skipped++;
				$progress->tick();
				continue;
			}

			// Create landing page
			$post_id = $this->create_landing_page( $vertical, $keyword, $intent );

			if ( $post_id ) {
				$generated++;
			}

			$progress->tick();
		}

		$progress->finish();

		\WP_CLI::success( "Generated: {$generated} pages" );
		\WP_CLI::log( "Skipped: {$skipped} pages (already exist)" );
	}

	/**
	 * List available verticals
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog seo-v3:verticals
	 *
	 * @when after_wp_load
	 */
	public function verticals( array $args, array $assoc_args ): void {
		\WP_CLI::log( "\n=== Available Verticals ===\n" );

		$verticals = $this->get_verticals();

		foreach ( $verticals as $slug => $name ) {
			\WP_CLI::log( "{$slug} => {$name}" );
		}

		\WP_CLI::log( "\n" );
	}

	/**
	 * List services for a vertical
	 *
	 * ## OPTIONS
	 *
	 * <vertical>
	 * : Vertical slug (elektryk, hydraulik, etc.)
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog seo-v3:services elektryk
	 *     wp pearblog seo-v3:services hydraulik
	 *
	 * @when after_wp_load
	 */
	public function services( array $args, array $assoc_args ): void {
		$vertical = $args[0] ?? null;

		if ( ! $vertical ) {
			\WP_CLI::error( 'Vertical slug is required' );
			return;
		}

		$verticals = $this->get_verticals();
		if ( ! isset( $verticals[ $vertical ] ) ) {
			\WP_CLI::error( "Invalid vertical: {$vertical}. Use 'wp pearblog seo-v3:verticals' to see available verticals." );
			return;
		}

		\WP_CLI::log( "\n=== Services for {$verticals[$vertical]} ===\n" );

		$services = $this->get_services( $vertical );

		foreach ( $services as $service ) {
			\WP_CLI::log( "  - {$service}" );
		}

		\WP_CLI::log( "\n" );
	}

	/**
	 * List available modifiers
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog seo-v3:modifiers
	 *
	 * @when after_wp_load
	 */
	public function modifiers( array $args, array $assoc_args ): void {
		\WP_CLI::log( "\n=== Available Modifiers ===\n" );

		$modifiers = $this->get_modifiers();

		\WP_CLI::log( "Location modifiers:" );
		foreach ( $modifiers['locations'] as $mod ) {
			\WP_CLI::log( "  - {$mod}" );
		}

		\WP_CLI::log( "\nQuality modifiers:" );
		foreach ( $modifiers['quality'] as $mod ) {
			\WP_CLI::log( "  - {$mod}" );
		}

		\WP_CLI::log( "\nUrgency modifiers:" );
		foreach ( $modifiers['urgency'] as $mod ) {
			\WP_CLI::log( "  - {$mod}" );
		}

		\WP_CLI::log( "\n" );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Get available verticals
	 *
	 * @return array<string, string> Vertical slug => name
	 */
	private function get_verticals(): array {
		return [
			'elektryk'    => 'Elektryk samochodowy',
			'hydraulik'   => 'Hydraulik',
			'mechanik'    => 'Mechanik samochodowy',
			'laweta'      => 'Laweta',
			'wulkanizacja' => 'Wulkanizacja',
			'klimatyzacja' => 'Klimatyzacja',
			'lakiernik'   => 'Lakiernik',
			'blacharstwo'  => 'Blacharstwo',
		];
	}

	/**
	 * Get services for a vertical
	 *
	 * @param string $vertical Vertical slug
	 * @return string[] Services
	 */
	private function get_services( string $vertical ): array {
		$services = [
			'elektryk'    => [
				'diagnostyka elektryczna',
				'naprawa instalacji',
				'wymiana alternator',
				'naprawa rozrusznika',
				'programowanie sterowników',
				'naprawa świateł',
			],
			'hydraulik'   => [
				'udrażnianie rur',
				'naprawa toalet',
				'montaż baterii',
				'naprawa grzejników',
				'wymiana rur',
				'montaż kotła',
			],
			'mechanik'    => [
				'diagnostyka komputerowa',
				'wymiana oleju',
				'naprawa silnika',
				'wymiana rozrządu',
				'naprawa zawieszenia',
				'naprawa hamulców',
			],
			'laweta'      => [
				'transport auta',
				'laweta 24h',
				'holowanie pojazdu',
				'pomoc drogowa',
			],
			'wulkanizacja' => [
				'wymiana opon',
				'wyważanie kół',
				'naprawa felg',
				'geometria zawieszenia',
			],
			'klimatyzacja' => [
				'uzupełnianie czynnika',
				'serwis klimatyzacji',
				'naprawa sprężarki',
				'dezynfekcja klimatyzacji',
			],
			'lakiernik'   => [
				'lakierowanie auta',
				'naprawa lakieru',
				'polerowanie',
				'usuwanie rys',
			],
			'blacharstwo'  => [
				'naprawa karoserii',
				'prostowanie blach',
				'wymiana części',
				'spawanie',
			],
		];

		return $services[ $vertical ] ?? [];
	}

	/**
	 * Get modifiers for keyword generation
	 *
	 * @return array{locations: string[], quality: string[], urgency: string[]}
	 */
	private function get_modifiers(): array {
		return [
			'locations' => [
				'warszawa',
				'kraków',
				'wrocław',
				'poznań',
				'gdańsk',
				'łódź',
				'szczecin',
				'katowice',
			],
			'quality'   => [
				'tani',
				'dobry',
				'profesjonalny',
				'sprawdzony',
				'polecany',
				'najlepszy',
			],
			'urgency'   => [
				'24h',
				'pilne',
				'szybko',
				'natychmiast',
				'weekend',
			],
		];
	}

	/**
	 * Generate keywords for a vertical and intent
	 *
	 * @param string $vertical Vertical slug
	 * @param string $intent   Search intent
	 * @param int    $limit    Maximum keywords to generate
	 * @return string[] Keywords
	 */
	private function generate_keywords( string $vertical, string $intent, int $limit ): array {
		$verticals = $this->get_verticals();
		$services  = $this->get_services( $vertical );
		$modifiers = $this->get_modifiers();
		$keywords  = [];

		$vertical_name = $verticals[ $vertical ] ?? $vertical;

		// Generate based on intent
		switch ( $intent ) {
			case 'transactional':
				// "[service] [location]"
				// "[quality] [vertical] [location]"
				foreach ( $services as $service ) {
					foreach ( $modifiers['locations'] as $location ) {
						$keywords[] = "{$service} {$location}";
						if ( count( $keywords ) >= $limit ) {
							return $keywords;
						}
					}
				}

				foreach ( $modifiers['quality'] as $quality ) {
					foreach ( $modifiers['locations'] as $location ) {
						$keywords[] = "{$quality} {$vertical_name} {$location}";
						if ( count( $keywords ) >= $limit ) {
							return $keywords;
						}
					}
				}

				// "[urgency] [vertical] [location]"
				foreach ( $modifiers['urgency'] as $urgency ) {
					foreach ( $modifiers['locations'] as $location ) {
						$keywords[] = "{$urgency} {$vertical_name} {$location}";
						if ( count( $keywords ) >= $limit ) {
							return $keywords;
						}
					}
				}
				break;

			case 'informational':
				// "jak [service]"
				// "ile kosztuje [service]"
				$info_prefixes = [ 'jak', 'ile kosztuje', 'co to jest', 'dlaczego' ];
				foreach ( $services as $service ) {
					foreach ( $info_prefixes as $prefix ) {
						$keywords[] = "{$prefix} {$service}";
						if ( count( $keywords ) >= $limit ) {
							return $keywords;
						}
					}
				}
				break;

			case 'commercial':
				// "ceny [service]"
				// "[vertical] opinie"
				foreach ( $services as $service ) {
					$keywords[] = "ceny {$service}";
					$keywords[] = "{$service} opinie";
					if ( count( $keywords ) >= $limit ) {
						return $keywords;
					}
				}

				foreach ( $modifiers['locations'] as $location ) {
					$keywords[] = "{$vertical_name} {$location} opinie";
					$keywords[] = "ceny {$vertical_name} {$location}";
					if ( count( $keywords ) >= $limit ) {
						return $keywords;
					}
				}
				break;

			case 'navigational':
				// "[vertical] [location]"
				// "[vertical] w [location]"
				foreach ( $modifiers['locations'] as $location ) {
					$keywords[] = "{$vertical_name} {$location}";
					$keywords[] = "{$vertical_name} w {$location}";
					if ( count( $keywords ) >= $limit ) {
						return $keywords;
					}
				}
				break;
		}

		return array_unique( $keywords );
	}

	/**
	 * Create a landing page
	 *
	 * @param string $vertical Vertical slug
	 * @param string $keyword  Target keyword
	 * @param string $intent   Search intent
	 * @return int|false Post ID or false on failure
	 */
	private function create_landing_page( string $vertical, string $keyword, string $intent ) {
		$verticals = $this->get_verticals();
		$vertical_name = $verticals[ $vertical ] ?? $vertical;

		// Create title and content
		$title = ucfirst( $keyword ) . " — Najlepsza oferta 2024";
		$content = $this->generate_landing_content( $vertical_name, $keyword, $intent );

		// Create post
		$post_id = wp_insert_post( [
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'post',
		] );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return false;
		}

		// Add SEO V3 meta
		update_post_meta( $post_id, 'pearblog_seo_v3_enabled', '1' );
		update_post_meta( $post_id, 'pearblog_seo_v3_vertical', $vertical );
		update_post_meta( $post_id, 'pearblog_seo_v3_keyword', $keyword );
		update_post_meta( $post_id, 'pearblog_seo_v3_intent', $intent );

		// SEO meta
		$meta_description = "Szukasz {$keyword}? ✓ Sprawdź najlepsze oferty ✓ Porównaj ceny ✓ Zaufani specjaliści. Szybka wycena online!";
		update_post_meta( $post_id, 'pearblog_meta_description', $meta_description );

		return $post_id;
	}

	/**
	 * Generate landing page content
	 *
	 * @param string $vertical_name Vertical display name
	 * @param string $keyword       Target keyword
	 * @param string $intent        Search intent
	 * @return string HTML content
	 */
	private function generate_landing_content( string $vertical_name, string $keyword, string $intent ): string {
		$content = '';

		$content .= "<h2>Szukasz: {$keyword}?</h2>\n\n";
		$content .= "<p>Znajdź najlepszych specjalistów w Twojej okolicy. Porównaj oferty, sprawdź opinie i wybierz idealnego wykonawcę.</p>\n\n";

		if ( 'transactional' === $intent ) {
			$content .= "<h2>Dlaczego warto skorzystać z naszej platformy?</h2>\n\n";
			$content .= "<ul>\n";
			$content .= "<li>Sprawdzeni i zweryfikowani specjaliści</li>\n";
			$content .= "<li>Porównanie cen i ofert w jednym miejscu</li>\n";
			$content .= "<li>Szybka odpowiedź — nawet w 15 minut</li>\n";
			$content .= "<li>Bezpłatne zapytanie ofertowe</li>\n";
			$content .= "</ul>\n\n";
		}

		$content .= "<h2>Jak to działa?</h2>\n\n";
		$content .= "<ol>\n";
		$content .= "<li>Wypełnij formularz zapytania</li>\n";
		$content .= "<li>Otrzymaj oferty od sprawdzonych specjalistów</li>\n";
		$content .= "<li>Porównaj ceny i wybierz najlepszą ofertę</li>\n";
		$content .= "<li>Umów się na dogodny termin</li>\n";
		$content .= "</ol>\n\n";

		if ( 'informational' === $intent ) {
			$content .= "<h2>Co warto wiedzieć?</h2>\n\n";
			$content .= "<p>Profesjonalna usługa {$vertical_name} wymaga odpowiedniego doświadczenia i narzędzi. ";
			$content .= "Wybierając sprawdzonego specjalistę, masz gwarancję jakości i bezpieczeństwa wykonanych prac.</p>\n\n";
		}

		$content .= "<h2>Najczęściej zadawane pytania</h2>\n\n";
		$content .= "<h3>Ile kosztuje {$keyword}?</h3>\n";
		$content .= "<p>Cena zależy od zakresu prac, lokalizacji oraz doświadczenia specjalisty. Wypełnij formularz, aby otrzymać bezpłatną wycenę.</p>\n\n";

		$content .= "<h3>Jak długo trwa realizacja?</h3>\n";
		$content .= "<p>Czas realizacji zależy od rodzaju usługi. Większość prac jest wykonywana w ciągu 1-3 dni roboczych.</p>\n\n";

		return $content;
	}
}
