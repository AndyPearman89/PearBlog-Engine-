<?php
/**
 * Sponsored Placement Manager
 *
 * Orchestrates ad placements across the platform:
 *   - Ranking page sponsored slots (delegated to SponsorEngine)
 *   - Homepage featured blocks
 *   - Category page banners
 *   - Sidebar visibility boosts
 *
 * @package PearBlogEngine\Revenue
 */

declare(strict_types=1);

namespace PearBlogEngine\Revenue;

use PearBlogEngine\Core\FeatureFlags;
use PearBlogEngine\Core\ModuleRegistry;
use PearBlogEngine\Rankings\SponsorEngine;

/**
 * SponsoredPlacement
 */
class SponsoredPlacement {

	private SponsorEngine $sponsor_engine;

	private const PLACEMENTS_OPTION = 'pearblog_sponsored_placements';

	/**
	 * Platform placement types.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $placement_types = [
		'ranking_gold'   => [ 'label' => 'Ranking — Pozycja 1',  'price_pln' => 49900 ],
		'ranking_silver' => [ 'label' => 'Ranking — Pozycja 2-3', 'price_pln' => 29900 ],
		'homepage_hero'  => [ 'label' => 'Homepage — Hero block', 'price_pln' => 79900 ],
		'category_banner' => [ 'label' => 'Category page banner', 'price_pln' => 19900 ],
		'sidebar_boost'  => [ 'label' => 'Sidebar — Polecany',   'price_pln' => 9900  ],
	];

	public function __construct( ?SponsorEngine $sponsor_engine = null ) {
		$this->sponsor_engine = $sponsor_engine ?? new SponsorEngine();
	}

	// -----------------------------------------------------------------------
	// Boot
	// -----------------------------------------------------------------------

	public function register(): void {
		if ( FeatureFlags::disabled( 'sponsored_rankings' ) ) {
			return;
		}

		ModuleRegistry::add( 'sponsored_placements', 'Sponsored Placements', '1.0.0', __NAMESPACE__ );

		// REST API.
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );

		// Cron: remove expired global placements.
		if ( ! wp_next_scheduled( 'pearblog_purge_expired_placements' ) ) {
			wp_schedule_event( time(), 'daily', 'pearblog_purge_expired_placements' );
		}
		add_action( 'pearblog_purge_expired_placements', [ $this, 'purge_expired' ] );
	}

	// -----------------------------------------------------------------------
	// Placement management
	// -----------------------------------------------------------------------

	/**
	 * Create a sponsored placement.
	 *
	 * For ranking placements, delegates to SponsorEngine.
	 * For other placement types, stores in a global option.
	 *
	 * @param int    $specialist_id
	 * @param string $placement_type  Key from $placement_types.
	 * @param string $category        Required for ranking placements.
	 * @param string $city            Optional for ranking placements.
	 * @param int    $days            Duration.
	 * @return bool
	 */
	public function create(
		int    $specialist_id,
		string $placement_type,
		string $category = '',
		string $city     = '',
		int    $days     = 30
	): bool {
		if ( ! isset( self::$placement_types[ $placement_type ] ) ) {
			return false;
		}

		// Ranking placements → SponsorEngine.
		if ( str_starts_with( $placement_type, 'ranking_' ) ) {
			$tier = str_replace( 'ranking_', '', $placement_type ) . '_sponsor';
			return $this->sponsor_engine->activate( $specialist_id, $tier, $category, $city, $days );
		}

		// Other placements → global option store.
		$placements = $this->get_all_placements();
		$placements[] = [
			'specialist_id'  => $specialist_id,
			'placement_type' => $placement_type,
			'category'       => $category,
			'city'           => $city,
			'activated_at'   => time(),
			'expires_at'     => time() + ( $days * DAY_IN_SECONDS ),
		];

		return update_option( self::PLACEMENTS_OPTION, $placements, false );
	}

	/**
	 * Get all active placements of a specific type.
	 *
	 * @param string $placement_type
	 * @return array<array<string, mixed>>
	 */
	public function get_active( string $placement_type ): array {
		if ( str_starts_with( $placement_type, 'ranking_' ) ) {
			return []; // Rankings handled by SponsorEngine directly.
		}

		$now = time();
		return array_values( array_filter(
			$this->get_all_placements(),
			fn( $p ) => $p['placement_type'] === $placement_type && ( $p['expires_at'] ?? 0 ) > $now
		) );
	}

	/**
	 * Remove expired global placements.
	 *
	 * @return int Removed count.
	 */
	public function purge_expired(): int {
		$now         = time();
		$placements  = $this->get_all_placements();
		$cleaned     = array_values( array_filter( $placements, fn( $p ) => ( $p['expires_at'] ?? 0 ) > $now ) );
		$removed     = count( $placements ) - count( $cleaned );
		update_option( self::PLACEMENTS_OPTION, $cleaned, false );

		// Also purge ranking sponsor engine.
		$this->sponsor_engine->purge_expired();

		return $removed;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function placement_types(): array {
		return self::$placement_types;
	}

	// -----------------------------------------------------------------------
	// REST API
	// -----------------------------------------------------------------------

	public function register_routes(): void {
		register_rest_route( 'pearblog/v1', '/placements/types', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => fn() => new \WP_REST_Response( [ 'success' => true, 'types' => self::$placement_types ] ),
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'pearblog/v1', '/placements', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => function ( \WP_REST_Request $req ): \WP_REST_Response {
				$ok = $this->create(
					(int) $req->get_param( 'specialist_id' ),
					(string) $req->get_param( 'placement_type' ),
					(string) ( $req->get_param( 'category' ) ?? '' ),
					(string) ( $req->get_param( 'city' ) ?? '' ),
					(int)    ( $req->get_param( 'days' ) ?? 30 )
				);
				return new \WP_REST_Response( [ 'success' => $ok ], $ok ? 201 : 422 );
			},
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
			'args'                => [
				'specialist_id'  => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
				'placement_type' => [ 'required' => true, 'type' => 'string', 'enum' => array_keys( self::$placement_types ) ],
				'category'       => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'default' => '' ],
				'city'           => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'default' => '' ],
				'days'           => [ 'type' => 'integer', 'default' => 30, 'minimum' => 1, 'maximum' => 365 ],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/** @return array<array<string, mixed>> */
	private function get_all_placements(): array {
		$raw = get_option( self::PLACEMENTS_OPTION, [] );
		return is_array( $raw ) ? $raw : [];
	}
}
