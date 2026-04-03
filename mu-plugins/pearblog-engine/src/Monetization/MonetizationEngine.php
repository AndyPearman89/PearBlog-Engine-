<?php
/**
 * Monetisation engine – injects monetisation markup into generated content.
 *
 * v1: Google AdSense ad units
 * v2: Affiliate links (stub)
 * v3: SaaS CTA (stub)
 *
 * @package PearBlogEngine\Monetization
 */

declare(strict_types=1);

namespace PearBlogEngine\Monetization;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Selects and applies the correct monetisation strategy for a site profile.
 */
class MonetizationEngine {

	/** @var SiteProfile */
	private SiteProfile $profile;

	public function __construct( SiteProfile $profile ) {
		$this->profile = $profile;
	}

	/**
	 * Inject monetisation elements into the article content.
	 *
	 * @param int    $post_id   WordPress post ID (used to retrieve site options).
	 * @param string $content   Article content (HTML or Markdown).
	 * @return string           Content with monetisation markup inserted.
	 */
	public function apply( int $post_id, string $content ): string {
		$monetized = match ( $this->profile->monetization ) {
			'affiliate' => $this->apply_affiliate( $content ),
			'saas'      => $this->apply_saas_cta( $content ),
			default     => $this->apply_adsense( $post_id, $content ),
		};

		/**
		 * Filter: pearblog_monetized_content
		 *
		 * Allows external code to post-process the monetised content.
		 *
		 * @param string      $monetized Content with monetisation injected.
		 * @param int         $post_id   Post ID.
		 * @param SiteProfile $profile   Active site profile.
		 */
		return (string) apply_filters( 'pearblog_monetized_content', $monetized, $post_id, $this->profile );
	}

	// -----------------------------------------------------------------------
	// v1 – AdSense
	// -----------------------------------------------------------------------

	private function apply_adsense( int $post_id, string $content ): string {
		$publisher_id = (string) get_option( 'pearblog_adsense_publisher_id', '' );

		if ( '' === $publisher_id ) {
			return $content;
		}

		$ad_unit = sprintf(
			'<div class="pearblog-ad pearblog-ad--adsense">' .
			'<ins class="adsbygoogle" style="display:block" ' .
			'data-ad-client="%s" data-ad-slot="auto" data-ad-format="auto" ' .
			'data-full-width-responsive="true"></ins>' .
			'<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>' .
			'</div>',
			esc_attr( $publisher_id )
		);

		// Insert the ad unit after the first paragraph.
		$first_para = strpos( $content, '</p>' );
		if ( false !== $first_para ) {
			return substr_replace( $content, "\n\n" . $ad_unit . "\n\n", $first_para + 4, 0 );
		}

		return $content . "\n\n" . $ad_unit;
	}

	// -----------------------------------------------------------------------
	// v2 – Affiliate (stub – to be implemented in v2 release)
	// -----------------------------------------------------------------------

	private function apply_affiliate( string $content ): string {
		/**
		 * Filter: pearblog_affiliate_content
		 * Allows affiliate link injection by a dedicated affiliate plugin.
		 *
		 * @param string $content Raw article content.
		 */
		return (string) apply_filters( 'pearblog_affiliate_content', $content );
	}

	// -----------------------------------------------------------------------
	// v3 – SaaS CTA (stub – to be implemented in v3 release)
	// -----------------------------------------------------------------------

	private function apply_saas_cta( string $content ): string {
		/**
		 * Filter: pearblog_saas_cta_content
		 * Allows SaaS CTA injection by a dedicated integration plugin.
		 *
		 * @param string $content Raw article content.
		 */
		return (string) apply_filters( 'pearblog_saas_cta_content', $content );
	}
}
