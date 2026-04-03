<?php
/**
 * Factory for creating appropriate prompt builders based on site configuration.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

use PearBlogEngine\Tenant\SiteProfile;

/**
 * Creates the appropriate prompt builder based on industry and settings.
 */
class PromptBuilderFactory {

	/**
	 * Create a prompt builder instance for the given profile.
	 *
	 * @param SiteProfile $profile The site profile.
	 * @return PromptBuilder        Appropriate prompt builder instance.
	 */
	public static function create( SiteProfile $profile ): PromptBuilder {
		/**
		 * Filter: pearblog_prompt_builder_class
		 *
		 * Allows themes/plugins to override the prompt builder class selection.
		 *
		 * @param string|null $builder_class The class name to use, or null for auto-detection.
		 * @param SiteProfile $profile       The site profile.
		 */
		$builder_class = apply_filters( 'pearblog_prompt_builder_class', null, $profile );

		if ( $builder_class && class_exists( $builder_class ) ) {
			return new $builder_class( $profile );
		}

		// Auto-detect based on industry.
		$industry = strtolower( trim( $profile->industry ) );

		// Check for Beskidy-specific content.
		if ( self::is_beskidy_content( $industry, $profile ) ) {
			return new MultiLanguageTravelBuilder( $profile );
		}

		// Check for general travel content.
		if ( self::is_travel_content( $industry ) ) {
			return new TravelPromptBuilder( $profile );
		}

		// Default to generic prompt builder.
		return new PromptBuilder( $profile );
	}

	/**
	 * Check if this is Beskidy-specific content.
	 *
	 * @param string      $industry Industry string.
	 * @param SiteProfile $profile  Site profile.
	 * @return bool                 True if Beskidy content.
	 */
	private static function is_beskidy_content( string $industry, SiteProfile $profile ): bool {
		// Check industry for Beskidy keywords.
		$beskidy_keywords = [ 'beskidy', 'beskid', 'po.beskidzku', 'mountains poland' ];

		foreach ( $beskidy_keywords as $keyword ) {
			if ( stripos( $industry, $keyword ) !== false ) {
				return true;
			}
		}

		/**
		 * Filter: pearblog_is_beskidy_content
		 *
		 * Allows manual override for Beskidy content detection.
		 *
		 * @param bool        $is_beskidy Current detection result.
		 * @param string      $industry   Industry string.
		 * @param SiteProfile $profile    Site profile.
		 */
		return (bool) apply_filters( 'pearblog_is_beskidy_content', false, $industry, $profile );
	}

	/**
	 * Check if this is general travel content.
	 *
	 * @param string $industry Industry string.
	 * @return bool            True if travel content.
	 */
	private static function is_travel_content( string $industry ): bool {
		$travel_keywords = [
			'travel',
			'tourism',
			'vacation',
			'hiking',
			'mountains',
			'adventure',
			'outdoor',
			'destinations',
			'trips',
			'podróże',
		];

		foreach ( $travel_keywords as $keyword ) {
			if ( stripos( $industry, $keyword ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
