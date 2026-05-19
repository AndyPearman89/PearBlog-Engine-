<?php
/**
 * Tenant context – identifies the current site.
 *
 * @package PearBlogEngine\Tenant
 */

declare(strict_types=1);

namespace PearBlogEngine\Tenant;

/**
 * Holds the runtime context for a single tenant (blog/site).
 */
class TenantContext {

	/** @var int WordPress blog/site ID. */
	public readonly int $site_id;

	/** @var string Primary domain for this tenant. */
	public readonly string $domain;

	/** @var SiteProfile Content & behaviour profile. */
	public readonly SiteProfile $profile;

	public function __construct( int $site_id, string $domain, SiteProfile $profile ) {
		$this->site_id = $site_id;
		$this->domain  = $domain;
		$this->profile = $profile;
	}

	/**
	 * Build a TenantContext from WordPress site meta.
	 *
	 * Expected meta keys (stored via update_site_meta / update_option):
	 *   pearblog_industry, pearblog_tone, pearblog_monetization,
	 *   pearblog_publish_rate, pearblog_language
	 *
	 * @param int $site_id WordPress blog ID (defaults to current blog).
	 */
	public static function for_site( int $site_id = 0 ): self {
		if ( $site_id <= 0 ) {
			$site_id = \get_current_blog_id();
		}

		$domain = \get_site_url( $site_id );
		$get_option = static function ( string $key, mixed $default ) use ( $site_id ) {
			$has_blog_option = \function_exists( 'get_blog_option' );
			$multisite       = \function_exists( 'is_multisite' ) ? \is_multisite() : $has_blog_option;

			if ( $multisite && $has_blog_option ) {
				return \get_blog_option( $site_id, $key, $default );
			}

			if ( $has_blog_option ) {
				$site_value = \get_blog_option( $site_id, $key, null );
				if ( null !== $site_value ) {
					return $site_value;
				}
			}

			return \get_option( $key, $default );
		};

		$profile = new SiteProfile(
			industry:         $get_option( 'pearblog_industry', 'general' ),
			tone:             $get_option( 'pearblog_tone', 'neutral' ),
			monetization:     $get_option( 'pearblog_monetization', 'adsense' ),
			publish_rate:     (int) $get_option( 'pearblog_publish_rate', 1 ),
			language:         $get_option( 'pearblog_language', 'en' ),
		);

		return new self( $site_id, $domain, $profile );
	}
}
