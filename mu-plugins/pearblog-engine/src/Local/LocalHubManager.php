<?php
/**
 * Local Hub Manager
 *
 * Manages configuration and programmatic generation of local vertical hubs:
 *   hydraulik.pt24.pro, mechanik.pt24.pro, prawnik.pt24.pro, etc.
 *
 * Each hub is a tenant-aware collection of:
 *   - Ranking pages (/ranking/{city}/)
 *   - Guide pages (/poradnik/{slug}/)
 *   - Calculator pages
 *   - Specialist listings
 *   - Local SEO landing pages
 *
 * @package PearBlogEngine\Local
 */

declare(strict_types=1);

namespace PearBlogEngine\Local;

use PearBlogEngine\Core\EventBus;
use PearBlogEngine\Core\FeatureFlags;
use PearBlogEngine\Core\LocalHubGeneratedEvent;
use PearBlogEngine\Core\ModuleRegistry;
use PearBlogEngine\Tenant\TenantContext;

/**
 * LocalHubManager
 *
 * Boots the Local Hub system and exposes hub configuration.
 */
class LocalHubManager {

	/**
	 * Built-in vertical hub definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $hub_definitions = [
		'hydraulik' => [
			'label'    => 'Hydraulik',
			'domain'   => 'hydraulik.pt24.pro',
			'category' => 'hydraulik',
			'icon'     => '🔧',
			'services' => [ 'awaria', 'montaz', 'instalacja', 'naprawy', 'woda-kran' ],
			'color'    => '#3B82F6',
		],
		'mechanik' => [
			'label'    => 'Mechanik',
			'domain'   => 'mechanik.pt24.pro',
			'category' => 'mechanik',
			'icon'     => '🔩',
			'services' => [ 'przeglad', 'naprawy', 'diagnostyka', 'opony', 'klimatyzacja' ],
			'color'    => '#EF4444',
		],
		'elektryk' => [
			'label'    => 'Elektryk',
			'domain'   => 'elektryk.pt24.pro',
			'category' => 'elektryk',
			'icon'     => '⚡',
			'services' => [ 'instalacje', 'awaria', 'montaz', 'pomiary' ],
			'color'    => '#F59E0B',
		],
		'prawnik' => [
			'label'    => 'Prawnik',
			'domain'   => 'prawnik.pt24.pro',
			'category' => 'prawnik',
			'icon'     => '⚖️',
			'services' => [ 'porada', 'umowy', 'rozwod', 'nieruchomosci', 'firma' ],
			'color'    => '#8B5CF6',
		],
		'glazurnik' => [
			'label'    => 'Glazurnik',
			'domain'   => 'glazurnik.pt24.pro',
			'category' => 'glazurnik',
			'icon'     => '🏗️',
			'services' => [ 'kafelki', 'lazienkę', 'kuchnie', 'remont' ],
			'color'    => '#22C55E',
		],
	];

	/** @var LocalHubSEO */
	private LocalHubSEO $seo;

	public function __construct( ?LocalHubSEO $seo = null ) {
		$this->seo = $seo ?? new LocalHubSEO();
	}

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register the Local Hub module: REST API, SEO, cron.
	 */
	public function register(): void {
		if ( FeatureFlags::disabled( 'local_hubs' ) ) {
			return;
		}

		ModuleRegistry::add( 'local_hubs', 'Local Hub Network', '1.0.0', __NAMESPACE__ );

		add_action( 'rest_api_init', function (): void {
			( new LocalHubController( $this ) )->register_routes();
		} );

		// Register local-hub-aware rewrite rules.
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );

		// Register structured data for hub pages.
		add_action( 'wp_head', [ $this->seo, 'output_local_schema' ] );

		// Cron: re-generate hub pages weekly.
		if ( ! wp_next_scheduled( 'pearblog_regenerate_local_hubs' ) ) {
			wp_schedule_event( time(), 'weekly', 'pearblog_regenerate_local_hubs' );
		}
		add_action( 'pearblog_regenerate_local_hubs', [ $this, 'regenerate_all_hubs' ] );
	}

	// -----------------------------------------------------------------------
	// Hub management
	// -----------------------------------------------------------------------

	/**
	 * Return configuration for a specific vertical hub.
	 *
	 * @param string $vertical  e.g. 'hydraulik'
	 * @return array<string, mixed>|null
	 */
	public function get_hub( string $vertical ): ?array {
		return self::$hub_definitions[ $vertical ] ?? null;
	}

	/**
	 * Return all registered hub definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function all_hubs(): array {
		// Merge built-ins with any custom hubs stored in options.
		$custom = get_option( 'pearblog_custom_local_hubs', [] );
		$custom = is_array( $custom ) ? $custom : [];
		return array_merge( self::$hub_definitions, $custom );
	}

	/**
	 * Register a custom local hub at runtime.
	 *
	 * @param string               $vertical  Slug identifier.
	 * @param array<string, mixed> $config
	 * @return bool
	 */
	public function register_hub( string $vertical, array $config ): bool {
		$vertical = sanitize_key( $vertical );
		if ( isset( self::$hub_definitions[ $vertical ] ) ) {
			return false; // Cannot override built-ins via this method.
		}

		$custom                 = get_option( 'pearblog_custom_local_hubs', [] );
		$custom[ $vertical ]    = $config;
		return update_option( 'pearblog_custom_local_hubs', $custom, false );
	}

	// -----------------------------------------------------------------------
	// Programmatic page generation
	// -----------------------------------------------------------------------

	/**
	 * Generate all local hub ranking pages for a vertical + city list.
	 *
	 * @param string        $vertical
	 * @param array<string> $cities
	 * @return array<array{hub_slug: string, post_id: int}>
	 */
	public function generate_hub_pages( string $vertical, array $cities ): array {
		$hub = $this->get_hub( $vertical );
		if ( ! $hub ) {
			return [];
		}

		$results = [];
		foreach ( $cities as $city ) {
			$result = $this->generate_single_page( $vertical, $hub, $city );
			if ( $result ) {
				$results[] = $result;
				EventBus::dispatch( new LocalHubGeneratedEvent( $result['hub_slug'], $city, $vertical ) );
			}
		}

		return $results;
	}

	/**
	 * Generate or update a single hub ranking page.
	 *
	 * @param string               $vertical
	 * @param array<string, mixed> $hub
	 * @param string               $city
	 * @return array{hub_slug: string, post_id: int}|null
	 */
	private function generate_single_page( string $vertical, array $hub, string $city ): ?array {
		$slug     = sanitize_title( "{$vertical}-{$city}" );
		$title    = "Najlepszy {$hub['label']} - {$city} | Ranking {$this->current_year()}";
		$content  = $this->build_hub_page_content( $hub, $city );

		// Check if page exists.
		$existing = get_posts( [
			'post_type'      => 'page',
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		] );

		$post_data = [
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		];

		if ( ! empty( $existing ) ) {
			$post_data['ID'] = $existing[0]->ID;
			$post_id = wp_update_post( $post_data );
		} else {
			$post_id = wp_insert_post( $post_data );
		}

		if ( is_wp_error( $post_id ) || $post_id === 0 ) {
			return null;
		}

		// Store hub meta.
		update_post_meta( $post_id, 'pearblog_hub_vertical', $vertical );
		update_post_meta( $post_id, 'pearblog_hub_city', $city );
		update_post_meta( $post_id, 'pearblog_hub_generated_at', time() );

		return [ 'hub_slug' => $slug, 'post_id' => (int) $post_id ];
	}

	/**
	 * Build default HTML content for a hub page.
	 *
	 * In production, this would call the AI content engine.
	 *
	 * @param array<string, mixed> $hub
	 * @param string               $city
	 * @return string
	 */
	private function build_hub_page_content( array $hub, string $city ): string {
		$label    = esc_html( $hub['label'] );
		$city_esc = esc_html( $city );
		$year     = $this->current_year();
		$services = implode( ', ', array_map( 'esc_html', $hub['services'] ?? [] ) );

		return "<!-- wp:paragraph -->\n"
			. "<p>Szukasz sprawdzonego {$label} w {$city_esc}? Nasz ranking {$year} pomoże Ci wybrać najlepszego specjalistę z opiniami klientów, cenami i szybkim kontaktem.</p>\n"
			. "<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:shortcode -->[ranking category=\"{$hub['category']}\" city=\"{$city_esc}\" limit=\"10\"]<!-- /wp:shortcode -->\n\n"
			. "<!-- wp:paragraph -->\n"
			. "<p>Usługi: {$services}</p>\n"
			. "<!-- /wp:paragraph -->";
	}

	/**
	 * Regenerate all hub pages (cron callback).
	 *
	 * @return int Total pages generated.
	 */
	public function regenerate_all_hubs(): int {
		$cities = $this->get_major_cities();
		$total  = 0;

		foreach ( array_keys( $this->all_hubs() ) as $vertical ) {
			$results = $this->generate_hub_pages( $vertical, $cities );
			$total  += count( $results );
		}

		return $total;
	}

	// -----------------------------------------------------------------------
	// Rewrite rules
	// -----------------------------------------------------------------------

	/**
	 * Add rewrite rules for hub URL patterns.
	 * Pattern: /vertical/city/  →  hub page.
	 */
	public function add_rewrite_rules(): void {
		$verticals = implode( '|', array_map( 'preg_quote', array_keys( $this->all_hubs() ) ) );

		add_rewrite_rule(
			"({$verticals})/([a-z0-9-]+)/?$",
			'index.php?pagename=$matches[1]-$matches[2]',
			'top'
		);
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/** @return list<string> Major Polish cities. */
	private function get_major_cities(): array {
		return [
			'warszawa', 'krakow', 'wroclaw', 'poznan', 'gdansk',
			'szczecin', 'bydgoszcz', 'lublin', 'katowice', 'lodz',
		];
	}

	private function current_year(): int {
		return (int) date( 'Y' );
	}
}
