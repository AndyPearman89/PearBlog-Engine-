<?php
/**
 * Subscription Engine
 *
 * SaaS subscription tier management for the specialist marketplace
 * and local hub network.
 *
 * Tiers:
 *   free     — basic listing, 3 leads/month
 *   starter  — enhanced listing, 10 leads/month, silver badge eligible
 *   pro      — premium listing, 30 leads/month, gold badge eligible, analytics
 *   enterprise — white-label hub, unlimited leads, API access
 *
 * @package PearBlogEngine\Revenue
 */

declare(strict_types=1);

namespace PearBlogEngine\Revenue;

use PearBlogEngine\Core\FeatureFlags;
use PearBlogEngine\Core\ModuleRegistry;

/**
 * SubscriptionEngine
 */
class SubscriptionEngine {

	private const OPTION_PREFIX = 'pearblog_sub_';

	/**
	 * Sentinel value meaning this tier has no lead quota limit.
	 * Used instead of PHP_INT_MAX for clarity and safe UI display.
	 */
	private const UNLIMITED_LEADS = -1;

	/**
	 * Tier definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $tiers = [
		'free' => [
			'label'          => 'Free',
			'price_pln'      => 0,
			'leads_month'    => 3,
			'is_premium'     => false,
			'analytics'      => false,
			'api_access'     => false,
			'badge_eligible' => 'none',
		],
		'starter' => [
			'label'          => 'Starter',
			'price_pln'      => 9900,  // 99 PLN in grosz
			'leads_month'    => 10,
			'is_premium'     => true,
			'analytics'      => false,
			'api_access'     => false,
			'badge_eligible' => 'bronze',
		],
		'pro' => [
			'label'          => 'Pro',
			'price_pln'      => 29900,
			'leads_month'    => 30,
			'is_premium'     => true,
			'analytics'      => true,
			'api_access'     => false,
			'badge_eligible' => 'silver',
		],
		'enterprise' => [
			'label'          => 'Enterprise',
			'price_pln'      => 99900,
			'leads_month'    => self::UNLIMITED_LEADS,
			'is_premium'     => true,
			'analytics'      => true,
			'api_access'     => true,
			'badge_eligible' => 'gold',
		],
	];

	// -----------------------------------------------------------------------
	// Boot
	// -----------------------------------------------------------------------

	public function register(): void {
		if ( FeatureFlags::disabled( 'saas_subscriptions' ) ) {
			return;
		}

		ModuleRegistry::add( 'subscriptions', 'Subscription Engine', '1.0.0', __NAMESPACE__ );

		// REST routes.
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	// -----------------------------------------------------------------------
	// Subscription lifecycle
	// -----------------------------------------------------------------------

	/**
	 * Activate or upgrade a subscription for a specialist.
	 *
	 * @param int    $specialist_id
	 * @param string $tier
	 * @param int    $months  Duration in months.
	 * @return bool
	 */
	public function activate( int $specialist_id, string $tier, int $months = 1 ): bool {
		if ( ! isset( self::$tiers[ $tier ] ) ) {
			return false;
		}

		$expires_at = strtotime( "+{$months} months" );

		update_option( self::OPTION_PREFIX . $specialist_id, [
			'tier'         => $tier,
			'activated_at' => time(),
			'expires_at'   => $expires_at,
			'months'       => $months,
		], false );

		// Sync premium flag to specialist meta.
		if ( self::$tiers[ $tier ]['is_premium'] ) {
			update_post_meta( $specialist_id, '_pearblog_is_premium', '1' );
		}

		return true;
	}

	/**
	 * Deactivate a subscription (e.g. cancellation or expiry).
	 *
	 * @param int $specialist_id
	 * @return bool
	 */
	public function deactivate( int $specialist_id ): bool {
		delete_option( self::OPTION_PREFIX . $specialist_id );
		delete_post_meta( $specialist_id, '_pearblog_is_premium' );
		return true;
	}

	/**
	 * Get the current subscription for a specialist.
	 *
	 * @param int $specialist_id
	 * @return array<string, mixed>|null  null if no active subscription.
	 */
	public function get_subscription( int $specialist_id ): ?array {
		$sub = get_option( self::OPTION_PREFIX . $specialist_id );
		if ( ! is_array( $sub ) ) {
			return null;
		}

		// Check expiry.
		if ( isset( $sub['expires_at'] ) && $sub['expires_at'] < time() ) {
			$this->deactivate( $specialist_id );
			return null;
		}

		$tier             = $sub['tier'] ?? 'free';
		$sub['tier_data'] = self::$tiers[ $tier ] ?? [];
		$sub['days_left'] = $sub['expires_at'] ? (int) ceil( ( $sub['expires_at'] - time() ) / DAY_IN_SECONDS ) : null;

		return $sub;
	}

	/**
	 * Check if a specialist has access to a specific feature by tier.
	 *
	 * @param int    $specialist_id
	 * @param string $feature  e.g. 'analytics', 'api_access'
	 * @return bool
	 */
	public function has_feature( int $specialist_id, string $feature ): bool {
		$sub = $this->get_subscription( $specialist_id );
		if ( ! $sub ) {
			return (bool) ( self::$tiers['free'][ $feature ] ?? false );
		}

		$tier = $sub['tier'] ?? 'free';
		return (bool) ( self::$tiers[ $tier ][ $feature ] ?? false );
	}

	/**
	 * Get remaining lead quota for this month.
	 *
	 * @param int $specialist_id
	 * @return int
	 */
	public function remaining_lead_quota( int $specialist_id ): int {
		$sub         = $this->get_subscription( $specialist_id );
		$tier        = $sub['tier'] ?? 'free';
		$monthly_max = (int) ( self::$tiers[ $tier ]['leads_month'] ?? 3 );

		if ( $monthly_max === self::UNLIMITED_LEADS ) {
			return PHP_INT_MAX; // unlimited
		}

		$used_this_month = (int) get_post_meta( $specialist_id, '_pearblog_leads_this_month', true );

		return max( 0, $monthly_max - $used_this_month );
	}

	// -----------------------------------------------------------------------
	// Tier catalog
	// -----------------------------------------------------------------------

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function tiers(): array {
		return self::$tiers;
	}

	// -----------------------------------------------------------------------
	// REST API
	// -----------------------------------------------------------------------

	public function register_routes(): void {
		register_rest_route( 'pearblog/v1', '/subscriptions/tiers', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => fn() => new \WP_REST_Response( [ 'success' => true, 'tiers' => self::$tiers ] ),
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'pearblog/v1', '/subscriptions/(?P<specialist_id>\d+)', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => function ( \WP_REST_Request $req ): \WP_REST_Response {
				$sub = $this->get_subscription( (int) $req->get_param( 'specialist_id' ) );
				return new \WP_REST_Response( [ 'success' => true, 'subscription' => $sub ] );
			},
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		] );

		register_rest_route( 'pearblog/v1', '/subscriptions/(?P<specialist_id>\d+)/activate', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => function ( \WP_REST_Request $req ): \WP_REST_Response {
				$ok = $this->activate(
					(int) $req->get_param( 'specialist_id' ),
					(string) $req->get_param( 'tier' ),
					(int) ( $req->get_param( 'months' ) ?? 1 )
				);
				return new \WP_REST_Response( [ 'success' => $ok ] );
			},
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
			'args'                => [
				'tier'   => [ 'required' => true, 'type' => 'string', 'enum' => array_keys( self::$tiers ) ],
				'months' => [ 'type' => 'integer', 'default' => 1, 'minimum' => 1, 'maximum' => 12 ],
			],
		] );
	}
}
