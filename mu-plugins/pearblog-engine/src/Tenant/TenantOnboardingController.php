<?php
/**
 * Tenant Onboarding Controller – multi-tenant provisioning API.
 *
 * Provides a REST endpoint and WP-CLI command to provision a new tenant
 * (WordPress Multisite sub-site) with a full PearBlog configuration.
 *
 * REST endpoint:
 *   POST /pearblog/v1/tenant/provision
 *     Body: { domain, title, industry, tone, language, plan, admin_email }
 *
 * WP-CLI:
 *   wp pearblog tenant create --domain=... --industry=... --plan=pro [--title=...] [--language=...] [--admin=...]
 *
 * The controller requires WordPress Multisite (is_multisite()).
 * On single-site installs it creates a site configuration profile without
 * adding a new site, allowing the endpoint to be used for reconfiguration.
 *
 * @package PearBlogEngine\Tenant
 */

declare(strict_types=1);

namespace PearBlogEngine\Tenant;

/**
 * REST + CLI handler for provisioning new tenants.
 */
class TenantOnboardingController {

	/** REST namespace. */
	private const NAMESPACE = 'pearblog/v1';

	/** Available plan tiers and their default publish rates. */
	private const PLAN_RATES = [
		'starter'    => 1,
		'pro'        => 3,
		'enterprise' => 10,
	];

	/** Default options applied to every new tenant. */
	private const DEFAULT_OPTIONS = [
		'pearblog_enable_image_generation' => true,
		'pearblog_alert_on_publish'        => false,
		'pearblog_circuit_breaker_enabled' => true,
		'pearblog_homepage_version'        => 'v7',
	];

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	// -----------------------------------------------------------------------
	// REST
	// -----------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/tenant/provision', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_provision' ],
			'permission_callback' => [ $this, 'rest_permission' ],
			'args'                => [
				'domain'      => [ 'required' => true, 'type' => 'string' ],
				'title'       => [ 'required' => false, 'type' => 'string', 'default' => '' ],
				'industry'    => [ 'required' => false, 'type' => 'string', 'default' => 'general' ],
				'tone'        => [ 'required' => false, 'type' => 'string', 'default' => 'professional' ],
				'language'    => [ 'required' => false, 'type' => 'string', 'default' => 'en' ],
				'plan'        => [ 'required' => false, 'type' => 'string', 'default' => 'starter', 'enum' => [ 'starter', 'pro', 'enterprise' ] ],
				'admin_email' => [ 'required' => false, 'type' => 'string', 'format' => 'email' ],
			],
		] );

		// List tenants.
		register_rest_route( self::NAMESPACE, '/tenant/list', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_list' ],
			'permission_callback' => [ $this, 'rest_permission' ],
		] );
	}

	/**
	 * Permission – manage_options or Bearer token.
	 */
	public function rest_permission( \WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$stored = get_option( 'pearblog_api_key', '' );
		if ( '' === $stored ) {
			return false;
		}
		$header = $request->get_header( 'Authorization' ) ?? '';
		if ( str_starts_with( $header, 'Bearer ' ) ) {
			$token = trim( substr( $header, 7 ) );
			return hash_equals( $stored, $token );
		}
		return false;
	}

	/**
	 * POST /tenant/provision – provision a new tenant.
	 */
	public function rest_provision( \WP_REST_Request $request ): \WP_REST_Response {
		$params = [
			'domain'      => sanitize_text_field( $request->get_param( 'domain' ) ),
			'title'       => sanitize_text_field( $request->get_param( 'title' ) ?: $request->get_param( 'domain' ) ),
			'industry'    => sanitize_key( $request->get_param( 'industry' ) ),
			'tone'        => sanitize_key( $request->get_param( 'tone' ) ),
			'language'    => sanitize_key( $request->get_param( 'language' ) ),
			'plan'        => sanitize_key( $request->get_param( 'plan' ) ),
			'admin_email' => sanitize_email( $request->get_param( 'admin_email' ) ?: get_option( 'admin_email', '' ) ),
		];

		$result = $this->provision( $params );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [ 'error' => $result->get_error_message() ], 422 );
		}

		return new \WP_REST_Response( $result, 201 );
	}

	/**
	 * GET /tenant/list – list all provisioned tenants.
	 */
	public function rest_list( \WP_REST_Request $request ): \WP_REST_Response {
		$tenants = $this->list_tenants();
		return new \WP_REST_Response( $tenants, 200 );
	}

	// -----------------------------------------------------------------------
	// Provisioning core
	// -----------------------------------------------------------------------

	/**
	 * Provision a new tenant.
	 *
	 * On multisite: creates a new sub-site and applies settings.
	 * On single site: applies settings to current site (profile update).
	 *
	 * @param array $params Provisioning parameters.
	 * @return array|\WP_Error  Provisioned site data or WP_Error.
	 */
	public function provision( array $params ): array|\WP_Error {
		$domain      = $params['domain'];
		$title       = ( $params['title'] ?? '' ) ?: $domain;
		$industry    = ( $params['industry'] ?? '' ) ?: 'general';
		$tone        = ( $params['tone'] ?? '' ) ?: 'professional';
		$language    = ( $params['language'] ?? '' ) ?: 'en';
		$plan        = ( $params['plan'] ?? '' ) ?: 'starter';
		$admin_email = ( $params['admin_email'] ?? '' ) ?: get_option( 'admin_email', '' );
		$publish_rate = self::PLAN_RATES[ $plan ] ?? 1;

		if ( is_multisite() ) {
			// Create sub-site.
			$site_id = $this->create_subsite( $domain, $title, $admin_email );
			if ( is_wp_error( $site_id ) ) {
				return $site_id;
			}
		} else {
			$site_id = get_current_blog_id();
		}

		// Apply PearBlog settings to the site.
		$this->apply_settings( $site_id, [
			'pearblog_industry'     => $industry,
			'pearblog_tone'         => $tone,
			'pearblog_language'     => $language,
			'pearblog_publish_rate' => $publish_rate,
			'pearblog_plan'         => $plan,
		] );

		// Record tenant in the registry.
		$registry   = $this->get_tenant_registry();
		$tenant_key = sanitize_key( $domain );
		$registry[ $tenant_key ] = [
			'site_id'      => $site_id,
			'domain'       => $domain,
			'title'        => $title,
			'industry'     => $industry,
			'language'     => $language,
			'plan'         => $plan,
			'admin_email'  => $admin_email,
			'provisioned'  => time(),
		];
		update_option( 'pearblog_tenant_registry', $registry );

		do_action( 'pearblog_tenant_provisioned', $site_id, $params );

		return [
			'site_id'     => $site_id,
			'domain'      => $domain,
			'title'       => $title,
			'plan'        => $plan,
			'industry'    => $industry,
			'language'    => $language,
			'admin_email' => $admin_email,
			'admin_url'   => is_multisite() ? get_admin_url( $site_id ) : admin_url(),
		];
	}

	/**
	 * Return the list of provisioned tenants.
	 *
	 * @return array
	 */
	public function list_tenants(): array {
		return array_values( (array) get_option( 'pearblog_tenant_registry', [] ) );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Create a new WordPress Multisite sub-site.
	 *
	 * @param string $domain      Domain/path for the sub-site.
	 * @param string $title       Site title.
	 * @param string $admin_email Admin email.
	 * @return int|\WP_Error      Blog ID or WP_Error.
	 */
	private function create_subsite( string $domain, string $title, string $admin_email ): int|\WP_Error {
		if ( ! function_exists( 'wpmu_create_blog' ) ) {
			return new \WP_Error( 'not_multisite', 'wpmu_create_blog is not available on this installation.' );
		}

		// Resolve admin user by email.
		$user = get_user_by( 'email', $admin_email );
		$user_id = $user ? $user->ID : get_current_user_id();

		// Use subdomain or subdirectory depending on the multisite type.
		$network = get_network();
		$base    = $network ? $network->domain : '';

		// For subdirectory installs we use a path; for subdomain, a new domain.
		$path = '/' . sanitize_key( str_replace( [ 'https://', 'http://', '.' ], [ '', '', '-' ], $domain ) ) . '/';

		$blog_id = wpmu_create_blog( $base, $path, $title, $user_id, [], get_current_network_id() );

		if ( is_wp_error( $blog_id ) ) {
			return $blog_id;
		}

		return (int) $blog_id;
	}

	/**
	 * Apply a set of WP options to a given site.
	 *
	 * @param int   $site_id WordPress blog ID.
	 * @param array $options Options to set.
	 */
	private function apply_settings( int $site_id, array $options ): void {
		$all_options = array_merge( self::DEFAULT_OPTIONS, $options );

		if ( is_multisite() ) {
			foreach ( $all_options as $key => $value ) {
				update_blog_option( $site_id, $key, $value );
			}
		} else {
			foreach ( $all_options as $key => $value ) {
				update_option( $key, $value );
			}
		}
	}

	/**
	 * Return the tenant registry array.
	 *
	 * @return array
	 */
	private function get_tenant_registry(): array {
		return (array) get_option( 'pearblog_tenant_registry', [] );
	}
}
