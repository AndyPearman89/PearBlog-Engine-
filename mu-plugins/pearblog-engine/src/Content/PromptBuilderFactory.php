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

		// Check for e-commerce / shopping content.
		if ( self::matches_keywords( $industry, [ 'ecommerce', 'e-commerce', 'shop', 'shopping', 'product', 'review', 'amazon', 'dropship' ] ) ) {
			return new EcommercePromptBuilder( $profile );
		}

		// Check for technology / software content.
		if ( self::matches_keywords( $industry, [ 'tech', 'technology', 'software', 'developer', 'coding', 'programming', 'saas', 'ai', 'gadget' ] ) ) {
			return new TechPromptBuilder( $profile );
		}

		// Check for health / wellness content.
		if ( self::matches_keywords( $industry, [ 'health', 'wellness', 'fitness', 'medical', 'nutrition', 'diet', 'exercise', 'mental health' ] ) ) {
			return new HealthPromptBuilder( $profile );
		}

		// Check for personal finance / investing content.
		if ( self::matches_keywords( $industry, [ 'finance', 'investing', 'money', 'budget', 'crypto', 'stock', 'financial', 'retirement' ] ) ) {
			return new FinancePromptBuilder( $profile );
		}

		// Check for food / recipe content.
		if ( self::matches_keywords( $industry, [ 'food', 'recipe', 'cooking', 'baking', 'culinary', 'restaurant', 'cuisine', 'meal' ] ) ) {
			return new FoodPromptBuilder( $profile );
		}

		// Default to generic prompt builder.
		return new PromptBuilder( $profile );
	}

	/**
	 * Generic keyword matching helper.
	 *
	 * @param string   $industry Industry string.
	 * @param string[] $keywords Keywords to match against.
	 * @return bool
	 */
	private static function matches_keywords( string $industry, array $keywords ): bool {
		foreach ( $keywords as $keyword ) {
			if ( stripos( $industry, $keyword ) !== false ) {
				return true;
			}
		}
		return false;
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
