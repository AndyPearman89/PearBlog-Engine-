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

	/** Relative position (0–1) at which the second affiliate box is inserted. */
	private const AFFILIATE_BOX_POSITION_RATIO = 0.66;

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
			'affiliate' => $this->apply_affiliate( $post_id, $content ),
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
	// v2 – Affiliate (Booking.com deep-link boxes)
	// -----------------------------------------------------------------------

	/**
	 * Inject Booking.com affiliate boxes into content at strategic positions.
	 *
	 * Boxes are inserted after the first paragraph and at the 66 % mark.
	 * When no affiliate ID is configured the content is returned unchanged,
	 * but the pearblog_affiliate_content filter still fires so external
	 * plugins can add their own injection.
	 *
	 * @param int    $post_id WordPress post ID.
	 * @param string $content Article content (HTML).
	 * @return string Content with affiliate boxes injected.
	 */
	private function apply_affiliate( int $post_id, string $content ): string {
		$affiliate_id = (string) get_option( 'pearblog_booking_affiliate_id', '' );

		// Detect location: post meta first, then keyword scan of title + body.
		$location = (string) get_post_meta( $post_id, 'pearblog_location', true );
		if ( '' === $location ) {
			$post = get_post( $post_id );
			if ( $post instanceof \WP_Post ) {
				$location = $this->extract_location(
					$post->post_title . ' ' . wp_strip_all_tags( $post->post_content )
				);
			}
		}

		$box              = $this->build_affiliate_box( $affiliate_id, $location );
		$content_with_box = '' !== $box ? $this->inject_affiliate_box( $content, $box ) : $content;

		/**
		 * Filter: pearblog_affiliate_content
		 *
		 * Allows external plugins to post-process or replace affiliate injection.
		 *
		 * @param string $content_with_box Content with affiliate box(es) injected.
		 * @param int    $post_id          Post ID.
		 * @param string $location         Auto-detected location string (may be empty).
		 * @param string $affiliate_id     Booking.com affiliate / partner ID.
		 */
		return (string) apply_filters( 'pearblog_affiliate_content', $content_with_box, $post_id, $location, $affiliate_id );
	}

	/**
	 * Scan text for known location keywords and return the first match.
	 *
	 * The keyword list can be extended via the pearblog_location_keywords filter.
	 *
	 * @param string $text Plain text to search.
	 * @return string Matched location or empty string.
	 */
	private function extract_location( string $text ): string {
		/** @var string[] $keywords */
		$keywords = (array) apply_filters( 'pearblog_location_keywords', [
			'Babia Góra', 'Zakopane', 'Kraków', 'Warszawa', 'Gdańsk',
			'Tatry', 'Karkonosze', 'Bieszczady', 'Mazury', 'Karpacz',
			'Wrocław', 'Poznań', 'Łódź', 'Szczecin', 'Lublin',
			'Białystok', 'Katowice', 'Rzeszów', 'Toruń', 'Bydgoszcz',
		] );

		foreach ( $keywords as $keyword ) {
			if ( false !== stripos( $text, $keyword ) ) {
				return $keyword;
			}
		}

		return '';
	}

	/**
	 * Build a Booking.com deep-link affiliate box.
	 *
	 * Returns an empty string when neither an affiliate ID nor a location is
	 * available, so callers can skip injection gracefully.
	 *
	 * @param string $affiliate_id Booking.com partner/affiliate ID.
	 * @param string $location     Destination location name.
	 * @return string HTML for the affiliate box, or empty string.
	 */
	private function build_affiliate_box( string $affiliate_id, string $location ): string {
		if ( '' === $affiliate_id ) {
			return '';
		}

		$query_args = [ 'aid' => rawurlencode( $affiliate_id ), 'lang' => 'pl' ];
		if ( '' !== $location ) {
			$query_args['ss'] = rawurlencode( $location );
		}
		$search_url = add_query_arg( $query_args, 'https://www.booking.com/searchresults.html' );

		$title = '' !== $location
			/* translators: %s: destination name */
			? sprintf( __( 'Szukasz noclegów w %s?', 'pearblog-engine' ), $location )
			: __( 'Znajdź noclegi w dobrej cenie', 'pearblog-engine' );

		return sprintf(
			'<div class="pearblog-affiliate pearblog-affiliate--booking">' .
			'<p class="pearblog-affiliate__title">%s</p>' .
			'<a class="pearblog-affiliate__cta" href="%s" target="_blank" rel="noopener sponsored">%s</a>' .
			'</div>',
			esc_html( $title ),
			esc_url( $search_url ),
			esc_html__( 'Sprawdź oferty na Booking.com →', 'pearblog-engine' )
		);
	}

	/**
	 * Inject the affiliate box after the first paragraph and at the ~66 % mark.
	 *
	 * @param string $content Full article HTML.
	 * @param string $box     Affiliate box HTML to inject.
	 * @return string Modified content.
	 */
	private function inject_affiliate_box( string $content, string $box ): string {
		// Split on </p> boundaries to work paragraph by paragraph.
		$paragraphs = preg_split( '/(?<=<\/p>)/u', $content, -1, PREG_SPLIT_NO_EMPTY );
		if ( false === $paragraphs ) {
			return $content . "\n\n" . $box;
		}

		$count = count( $paragraphs );
		if ( $count <= 1 ) {
			return $content . "\n\n" . $box;
		}

		$mid_point = (int) round( $count * self::AFFILIATE_BOX_POSITION_RATIO );
		$result    = '';

		foreach ( $paragraphs as $index => $paragraph ) {
			$result .= $paragraph;

			if ( 0 === $index ) {
				$result .= "\n\n" . $box . "\n\n";
			} elseif ( $mid_point > 1 && $index === $mid_point - 1 ) {
				$result .= "\n\n" . $box . "\n\n";
			}
		}

		return $result;
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
